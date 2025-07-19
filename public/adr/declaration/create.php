<?php
/**
 * Titre: Formulaire déclaration ADR - Version simplifiée
 * Chemin: /public/adr/declaration/create.php
 * Version: 0.5 beta + build auto
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit;
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

$page_title = 'Déclaration ADR';
$current_module = 'adr';
$module_css = true;

$transporteurs = [
    'xpo' => 'XPO Logistics',
    'heppner' => 'Heppner'
];

// Calcul quotas du jour - table existante
$quotas = ['xpo' => 0, 'heppner' => 0];
try {
    $stmt = $db->query("
        SELECT transporteur, SUM(total_points_adr) as total_points
        FROM gul_adr_expeditions 
        WHERE DATE(date_creation) = CURDATE() AND statut != 'brouillon'
        GROUP BY transporteur
    ");
    
    while ($row = $stmt->fetch()) {
        $quotas[$row['transporteur']] = (int)$row['total_points'];
    }
} catch (Exception $e) {
    error_log("Erreur calcul quotas: " . $e->getMessage());
}

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'search_products') {
            $query = $_POST['query'] ?? '';
            if (strlen($query) < 2) {
                echo json_encode(['success' => false, 'message' => 'Requête trop courte']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT code_produit, nom_produit, numero_un, categorie_transport
                FROM gul_adr_products 
                WHERE (code_produit LIKE ? OR nom_produit LIKE ?) 
                AND actif = 1 AND numero_un IS NOT NULL
                ORDER BY code_produit ASC
                LIMIT 10
            ");
            $pattern = '%' . $query . '%';
            $stmt->execute([$pattern, $pattern]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'products' => $products]);
            
        } elseif ($action === 'search_destinataires') {
            $query = $_POST['query'] ?? '';
            if (strlen($query) < 2) {
                echo json_encode(['success' => false, 'message' => 'Requête trop courte']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT nom, adresse_complete, code_postal, ville, pays, telephone, email
                FROM gul_adr_destinataires_frequents 
                WHERE nom LIKE ? OR ville LIKE ? OR code_postal LIKE ?
                ORDER BY frequence_utilisation DESC, derniere_utilisation DESC
                LIMIT 10
            ");
            $pattern = '%' . $query . '%';
            $stmt->execute([$pattern, $pattern, $pattern]);
            $destinataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'destinataires' => $destinataires]);
            
        } elseif ($action === 'save_declaration') {
            $transporteur = $_POST['transporteur'] ?? '';
            $produits = json_decode($_POST['produits'] ?? '[]', true);
            $destinataire = json_decode($_POST['destinataire'] ?? '{}', true);
            $expedition = json_decode($_POST['expedition'] ?? '{}', true);
            
            if (empty($transporteur) || empty($produits) || empty($destinataire['nom'])) {
                echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
                exit;
            }
            
            $db->beginTransaction();
            
            try {
                // Calcul total points
                $total_points = 0;
                foreach ($produits as $produit) {
                    $quantite = (float)$produit['quantite'];
                    $categorie = $produit['categorie'] ?? '3';
                    
                    // Calcul points selon catégorie
                    switch ($categorie) {
                        case '1': $points = $quantite * 50; break;
                        case '2': $points = $quantite * 3; break;
                        case '3': $points = $quantite * 1; break;
                        default: $points = 0;
                    }
                    $total_points += $points;
                }
                
                // 1. Créer l'expédition - numéro auto-généré par trigger
                $stmt = $db->prepare("
                    INSERT INTO gul_adr_expeditions 
                    (transporteur, destinataire_nom, destinataire_adresse, destinataire_code_postal, 
                     destinataire_ville, destinataire_pays, destinataire_telephone, destinataire_email,
                     date_expedition, total_points_adr, statut, cree_par)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'valide', ?)
                ");
                
                $stmt->execute([
                    $transporteur,
                    $destinataire['nom'],
                    $destinataire['adresse'] ?? '',
                    $destinataire['code_postal'],
                    $destinataire['ville'],
                    $destinataire['pays'] ?? 'France',
                    $destinataire['telephone'] ?? null,
                    $destinataire['email'] ?? null,
                    $total_points,
                    $_SESSION['user']['username'] ?? 'unknown'
                ]);
                
                $expedition_id = $db->lastInsertId();
                
                // 2. Ajouter les lignes produits
                $stmt_ligne = $db->prepare("
                    INSERT INTO gul_adr_expedition_lignes 
                    (expedition_id, code_produit, quantite_declaree, unite_quantite, points_adr_calcules, ordre_ligne)
                    VALUES (?, ?, ?, 'kg', ?, ?)
                ");
                
                foreach ($produits as $index => $produit) {
                    $quantite = (float)$produit['quantite'];
                    $categorie = $produit['categorie'] ?? '3';
                    
                    switch ($categorie) {
                        case '1': $points = $quantite * 50; break;
                        case '2': $points = $quantite * 3; break;
                        case '3': $points = $quantite * 1; break;
                        default: $points = 0;
                    }
                    
                    $stmt_ligne->execute([
                        $expedition_id,
                        $produit['code'],
                        $quantite,
                        $points,
                        $index + 1
                    ]);
                }
                
                // 3. Mettre à jour destinataire fréquent
                $stmt_dest = $db->prepare("
                    INSERT INTO gul_adr_destinataires_frequents 
                    (nom, adresse_complete, code_postal, ville, pays, telephone, email, frequence_utilisation, cree_par)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
                    ON DUPLICATE KEY UPDATE 
                    frequence_utilisation = frequence_utilisation + 1,
                    derniere_utilisation = CURRENT_TIMESTAMP
                ");
                
                $stmt_dest->execute([
                    $destinataire['nom'],
                    $destinataire['adresse'] ?? '',
                    $destinataire['code_postal'],
                    $destinataire['ville'],
                    $destinataire['pays'] ?? 'France',
                    $destinataire['telephone'] ?? null,
                    $destinataire['email'] ?? null,
                    $_SESSION['user']['username'] ?? 'unknown'
                ]);
                
                // 4. Récupérer le numéro généré
                $stmt_num = $db->prepare("SELECT numero_expedition FROM gul_adr_expeditions WHERE id = ?");
                $stmt_num->execute([$expedition_id]);
                $numero_expedition = $stmt_num->fetchColumn();
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'expedition_id' => $expedition_id,
                    'numero_expedition' => $numero_expedition,
                    'total_points' => $total_points,
                    'message' => "Déclaration $numero_expedition créée avec succès"
                ]);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Action inconnue']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
    exit;
}

include ROOT_PATH . '/templates/header.php';
?>

<style>
.declaration-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.quotas-display {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    position: sticky;
    top: 20px;
    z-index: 100;
}

.quota-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.quota-item:last-child {
    margin-bottom: 0;
}

.quota-bar {
    width: 200px;
    height: 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
    overflow: hidden;
    margin: 0 1rem;
}

.quota-fill {
    height: 100%;
    background: white;
    transition: width 0.3s ease;
}

.quota-fill.warning { background: #ffc107; }
.quota-fill.danger { background: #dc3545; }

.form-sections {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.form-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.form-section.full-width {
    grid-column: 1 / -1;
}

.form-group {
    margin-bottom: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #ff6b35;
}

.search-container {
    position: relative;
}

.suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e9ecef;
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
}

.suggestion-item {
    padding: 0.75rem;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
}

.suggestion-item:hover {
    background: #f8f9fa;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.products-table th,
.products-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.products-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary { background: #ff6b35; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-secondary { background: #6c757d; color: white; }

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.alert-success { background: #d4edda; color: #155724; }
.alert-danger { background: #f8d7da; color: #721c24; }

@media (max-width: 768px) {
    .form-sections { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .quota-item { flex-direction: column; text-align: center; }
    .quota-bar { width: 100%; margin: 0.5rem 0; }
}
</style>

<main class="main-content">
    <div class="declaration-container">
        
        <!-- Quotas -->
        <div class="quotas-display">
            <h3>⚖️ Quotas ADR du jour</h3>
            <?php foreach ($quotas as $trans => $points): ?>
            <?php 
                $percentage = ($points / 1000) * 100;
                $fill_class = $percentage > 90 ? 'danger' : ($percentage > 75 ? 'warning' : '');
            ?>
            <div class="quota-item">
                <strong><?= strtoupper($trans) ?></strong>
                <div class="quota-bar">
                    <div class="quota-fill <?= $fill_class ?>" 
                         style="width: <?= min($percentage, 100) ?>%"></div>
                </div>
                <span><?= $points ?> / 1000 pts</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Formulaire -->
        <form id="declarationForm">
            
            <div class="form-sections">
                
                <!-- Transporteur -->
                <div class="form-section">
                    <h2>🚛 Transporteur</h2>
                    <div class="form-group">
                        <label for="transporteur">Transporteur *</label>
                        <select id="transporteur" name="transporteur" class="form-control" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($transporteurs as $code => $nom): ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Destinataire -->
                <div class="form-section">
                    <h2>📍 Destinataire</h2>
                    
                    <div class="form-group">
                        <label for="search-destinataire">Rechercher</label>
                        <div class="search-container">
                            <input type="text" id="search-destinataire" class="form-control"
                                   placeholder="Nom ou ville..." autocomplete="off">
                            <div id="destinataire-suggestions" class="suggestions"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="dest_nom">Nom / Raison sociale *</label>
                        <input type="text" id="dest_nom" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dest_cp">Code postal *</label>
                            <input type="text" id="dest_cp" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="dest_ville">Ville *</label>
                            <input type="text" id="dest_ville" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="dest_adresse">Adresse</label>
                        <textarea id="dest_adresse" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <!-- Produits -->
            <div class="form-section full-width">
                <h2>⚠️ Produits ADR</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="search-produit">Rechercher produit</label>
                        <div class="search-container">
                            <input type="text" id="search-produit" class="form-control"
                                   placeholder="Code ou nom..." autocomplete="off">
                            <div id="produit-suggestions" class="suggestions"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="produit_quantite">Quantité (kg)</label>
                        <input type="number" id="produit_quantite" class="form-control" 
                               step="0.1" min="0">
                    </div>
                </div>
                
                <button type="button" onclick="addProduct()" class="btn btn-primary">
                    ➕ Ajouter produit
                </button>
                
                <table id="products-table" class="products-table" style="display: none;">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Produit</th>
                            <th>ONU</th>
                            <th>Cat.</th>
                            <th>Quantité</th>
                            <th>Points</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody"></tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td colspan="5">TOTAL POINTS ADR</td>
                            <td id="total-points">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Actions -->
            <div class="form-section full-width">
                <button type="submit" class="btn btn-success">
                    💾 Enregistrer déclaration
                </button>
            </div>
        </form>
        
        <div id="messages"></div>
    </div>
</main>

<script>
let selectedProducts = [];
let selectedProduct = null;

// Recherche destinataires
document.getElementById('search-destinataire').addEventListener('input', function() {
    const query = this.value;
    if (query.length < 2) {
        document.getElementById('destinataire-suggestions').style.display = 'none';
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=search_destinataires&query=' + encodeURIComponent(query)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showDestinatairesSuggestions(data.destinataires);
        }
    });
});

// Recherche produits
document.getElementById('search-produit').addEventListener('input', function() {
    const query = this.value;
    if (query.length < 2) {
        document.getElementById('produit-suggestions').style.display = 'none';
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=search_products&query=' + encodeURIComponent(query)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showProductSuggestions(data.products);
        }
    });
});

function showDestinatairesSuggestions(destinataires) {
    const container = document.getElementById('destinataire-suggestions');
    
    if (destinataires.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.innerHTML = destinataires.map(dest => `
        <div class="suggestion-item" onclick="selectDestinataire(${JSON.stringify(dest).replace(/"/g, '&quot;')})">
            <strong>${dest.nom}</strong><br>
            <small>${dest.code_postal} ${dest.ville}</small>
        </div>
    `).join('');
    
    container.style.display = 'block';
}

function selectDestinataire(dest) {
    document.getElementById('dest_nom').value = dest.nom;
    document.getElementById('dest_cp').value = dest.code_postal;
    document.getElementById('dest_ville').value = dest.ville;
    document.getElementById('dest_adresse').value = dest.adresse_complete || '';
    document.getElementById('destinataire-suggestions').style.display = 'none';
}

function showProductSuggestions(products) {
    const container = document.getElementById('produit-suggestions');
    
    if (products.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.innerHTML = products.map(prod => `
        <div class="suggestion-item" onclick="selectProduct(${JSON.stringify(prod).replace(/"/g, '&quot;')})">
            <strong>${prod.code_produit}</strong> - ${prod.nom_produit}<br>
            <small>ONU: ${prod.numero_un} | Cat: ${prod.categorie_transport}</small>
        </div>
    `).join('');
    
    container.style.display = 'block';
}

function selectProduct(product) {
    selectedProduct = product;
    document.getElementById('search-produit').value = `${product.code_produit} - ${product.nom_produit}`;
    document.getElementById('produit-suggestions').style.display = 'none';
}

function addProduct() {
    if (!selectedProduct) {
        alert('Sélectionnez un produit');
        return;
    }
    
    const quantite = parseFloat(document.getElementById('produit_quantite').value);
    if (!quantite || quantite <= 0) {
        alert('Quantité invalide');
        return;
    }
    
    // Calcul points
    let points = 0;
    switch (selectedProduct.categorie_transport) {
        case '1': points = quantite * 50; break;
        case '2': points = quantite * 3; break;
        case '3': points = quantite * 1; break;
        default: points = 0;
    }
    
    const product = {
        code: selectedProduct.code_produit,
        nom: selectedProduct.nom_produit,
        onu: selectedProduct.numero_un,
        categorie: selectedProduct.categorie_transport,
        quantite: quantite,
        points: points
    };
    
    selectedProducts.push(product);
    updateProductsTable();
    
    // Reset
    document.getElementById('search-produit').value = '';
    document.getElementById('produit_quantite').value = '';
    selectedProduct = null;
}

function removeProduct(index) {
    selectedProducts.splice(index, 1);
    updateProductsTable();
}

function updateProductsTable() {
    const table = document.getElementById('products-table');
    const tbody = document.getElementById('products-tbody');
    
    if (selectedProducts.length === 0) {
        table.style.display = 'none';
        return;
    }
    
    tbody.innerHTML = selectedProducts.map((prod, index) => `
        <tr>
            <td>${prod.code}</td>
            <td>${prod.nom}</td>
            <td>${prod.onu}</td>
            <td>${prod.categorie}</td>
            <td>${prod.quantite} kg</td>
            <td>${prod.points}</td>
            <td><button type="button" onclick="removeProduct(${index})" class="btn btn-sm" style="background:#dc3545;color:white;">✕</button></td>
        </tr>
    `).join('');
    
    const totalPoints = selectedProducts.reduce((sum, prod) => sum + prod.points, 0);
    document.getElementById('total-points').textContent = totalPoints;
    
    table.style.display = 'table';
}

// Soumission formulaire
document.getElementById('declarationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (selectedProducts.length === 0) {
        alert('Ajoutez au moins un produit');
        return;
    }
    
    const data = {
        action: 'save_declaration',
        transporteur: document.getElementById('transporteur').value,
        destinataire: JSON.stringify({
            nom: document.getElementById('dest_nom').value,
            code_postal: document.getElementById('dest_cp').value,
            ville: document.getElementById('dest_ville').value,
            adresse: document.getElementById('dest_adresse').value
        }),
        produits: JSON.stringify(selectedProducts)
    };
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: Object.keys(data).map(key => key + '=' + encodeURIComponent(data[key])).join('&')
    })
    .then(response => response.json())
    .then(result => {
        const messages = document.getElementById('messages');
        if (result.success) {
            messages.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
            // Reset form
            selectedProducts = [];
            updateProductsTable();
            document.getElementById('declarationForm').reset();
            // Actualiser quotas
            location.reload();
        } else {
            messages.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    });
});

// Cacher suggestions au clic ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container')) {
        document.querySelectorAll('.suggestions').forEach(s => s.style.display = 'none');
    }
});
</script>

<?php include ROOT_PATH . '/templates/footer.php'; ?>

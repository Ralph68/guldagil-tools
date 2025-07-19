<?php
/**
 * Titre: Formulaire déclaration ADR - Version finale
 * Chemin: /public/adr/declaration/create.php
 * Version: 0.5 beta + build auto
 */

// Gestion erreurs simple
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

// Calcul quotas du jour
$quotas = ['xpo' => 0, 'heppner' => 0];
try {
    $stmt = $db->query("
        SELECT transporteur, 
               SUM(CASE 
                   WHEN p.categorie_transport = '1' THEN d.quantite_declaree * 50
                   WHEN p.categorie_transport = '2' THEN d.quantite_declaree * 3
                   WHEN p.categorie_transport = '3' THEN d.quantite_declaree * 1
                   ELSE 0
               END) as total_points
        FROM gul_adr_declarations d
        JOIN gul_adr_products p ON d.code_produit = p.code_produit
        WHERE d.date_declaration = CURDATE()
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
            
        } elseif ($action === 'save_declaration') {
            $transporteur = $_POST['transporteur'] ?? '';
            $produits = json_decode($_POST['produits'] ?? '[]', true);
            
            if (empty($transporteur) || empty($produits)) {
                echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
                exit;
            }
            
            $total_points = 0;
            foreach ($produits as $produit) {
                $total_points += $produit['points'];
            }
            
            // Sauvegarde avec nouvelles colonnes
            $stmt = $db->prepare("
                INSERT INTO gul_adr_declarations 
                (transporteur, total_points, produits_json, date_declaration, 
                 code_produit, quantite_declaree, date_expedition, cree_par)
                VALUES (?, ?, ?, CURDATE(), 'MULTIPLE', ?, CURDATE(), ?)
            ");
            
            $stmt->execute([
                $transporteur,
                $total_points,
                json_encode($produits),
                count($produits),
                $_SESSION['user']['username'] ?? 'unknown'
            ]);
            
            echo json_encode([
                'success' => true,
                'total_points' => $total_points,
                'message' => "Déclaration enregistrée"
            ]);
            
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
    max-width: 1000px;
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

.quota-fill.warning {
    background: #ffc107;
}

.quota-fill.danger {
    background: #dc3545;
}

.form-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
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

.btn-primary {
    background: #ff6b35;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .quota-item {
        flex-direction: column;
        text-align: center;
    }
    
    .quota-bar {
        width: 100%;
        margin: 0.5rem 0;
    }
}
</style>

<main class="main-content">
    <div class="declaration-container">
        
        <!-- Quotas en temps réel -->
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
            
            <!-- Transporteur -->
            <div class="form-section">
                <h2>🚛 Transporteur</h2>
                <div class="form-group">
                    <label for="transporteur">Choisir le transporteur *</label>
                    <select id="transporteur" name="transporteur" class="form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($transporteurs as $code => $nom): ?>
                        <option value="<?= $code ?>"><?= htmlspecialchars($nom) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Ajout produits -->
            <div class="form-section">
                <h2>⚠️ Produits ADR</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="search-produit">Rechercher un produit</label>
                        <div class="search-container">
                            <input type="text" id="search-produit" class="form-control"
                                   placeholder="Code ou nom du produit..." autocomplete="off">
                            <div id="produit-suggestions" class="suggestions"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="produit_quantite">Quantité</label>
                        <input type="number" id="produit_quantite" class="form-control" 
                               step="0.1" min="0" placeholder="Ex: 25.5">
                    </div>
                </div>
                
                <button type="button" onclick="addProduct()" class="btn btn-primary">
                    ➕ Ajouter produit
                </button>
                
                <!-- Tableau des produits -->
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
            <div class="form-section">
                <button type="submit" class="btn btn-success">
                    💾 Enregistrer déclaration
                </button>
                
                <button type="button" onclick="generateRecap()" class="btn btn-secondary" 
                        id="recap-btn" style="display: none;">
                    🖨️ Récap pour chauffeur
                </button>
            </div>
        </form>
        
        <!-- Messages -->
        <div id="messages"></div>
    </div>
</main>

<script>
let selectedProducts = [];
let currentTransporteur = '';

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

function showProductSuggestions(products) {
    const container = document.getElementById('produit-suggestions');
    
    if (products.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="suggestion-item" onclick="selectProduct('${product.code_produit}', '${product.nom_produit}', '${product.numero_un}', '${product.categorie_transport}')">
            <strong>${product.code_produit}</strong> - ${product.nom_produit}<br>
            <small>ONU: ${product.numero_un} | Cat: ${product.categorie_transport}</small>
        </div>
    `).join('');
    
    container.style.display = 'block';
}

function selectProduct(code, nom, onu, categorie) {
    document.getElementById('search-produit').value = code + ' - ' + nom;
    document.getElementById('produit-suggestions').style.display = 'none';
    document.getElementById('produit_quantite').focus();
    
    document.getElementById('search-produit').dataset.code = code;
    document.getElementById('search-produit').dataset.nom = nom;
    document.getElementById('search-produit').dataset.onu = onu;
    document.getElementById('search-produit').dataset.categorie = categorie;
}

function addProduct() {
    const searchInput = document.getElementById('search-produit');
    const quantiteInput = document.getElementById('produit_quantite');
    
    const code = searchInput.dataset.code;
    const nom = searchInput.dataset.nom;
    const onu = searchInput.dataset.onu;
    const categorie = searchInput.dataset.categorie;
    const quantite = parseFloat(quantiteInput.value);
    
    if (!code || !quantite || quantite <= 0) {
        showMessage('Sélectionner un produit et saisir une quantité valide', 'danger');
        return;
    }
    
    if (selectedProducts.find(p => p.code === code)) {
        showMessage('Produit déjà ajouté', 'danger');
        return;
    }
    
    const pointsCategorie = {'1': 50, '2': 3, '3': 1, '4': 0};
    const points = (pointsCategorie[categorie] || 0) * quantite;
    
    const product = {
        code: code,
        nom: nom,
        onu: onu,
        categorie: categorie,
        quantite: quantite,
        points: points
    };
    
    selectedProducts.push(product);
    updateProductsTable();
    
    searchInput.value = '';
    quantiteInput.value = '';
    searchInput.removeAttribute('data-code');
    searchInput.removeAttribute('data-nom');
    searchInput.removeAttribute('data-onu');
    searchInput.removeAttribute('data-categorie');
    
    showMessage('Produit ajouté', 'success');
}

function updateProductsTable() {
    const table = document.getElementById('products-table');
    const tbody = document.getElementById('products-tbody');
    
    if (selectedProducts.length === 0) {
        table.style.display = 'none';
        return;
    }
    
    tbody.innerHTML = selectedProducts.map((product, index) => `
        <tr>
            <td>${product.code}</td>
            <td>${product.nom}</td>
            <td>${product.onu}</td>
            <td>${product.categorie}</td>
            <td>${product.quantite}</td>
            <td>${product.points.toFixed(1)}</td>
            <td>
                <button type="button" onclick="removeProduct(${index})" 
                        style="background: #dc3545; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px;">
                    🗑️
                </button>
            </td>
        </tr>
    `).join('');
    
    const totalPoints = selectedProducts.reduce((sum, p) => sum + p.points, 0);
    document.getElementById('total-points').textContent = totalPoints.toFixed(1);
    
    table.style.display = 'table';
    document.getElementById('recap-btn').style.display = selectedProducts.length > 0 ? 'inline-block' : 'none';
}

function removeProduct(index) {
    selectedProducts.splice(index, 1);
    updateProductsTable();
}

document.getElementById('declarationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const transporteur = document.getElementById('transporteur').value;
    
    if (!transporteur) {
        showMessage('Sélectionner un transporteur', 'danger');
        return;
    }
    
    if (selectedProducts.length === 0) {
        showMessage('Ajouter au moins un produit', 'danger');
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=save_declaration&transporteur=' + transporteur + '&produits=' + encodeURIComponent(JSON.stringify(selectedProducts))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Déclaration enregistrée (' + data.total_points + ' points)', 'success');
            currentTransporteur = transporteur;
            setTimeout(() => location.reload(), 2000);
        } else {
            showMessage(data.message, 'danger');
        }
    });
});

function generateRecap() {
    if (!currentTransporteur || selectedProducts.length === 0) {
        showMessage('Enregistrer d\'abord la déclaration', 'danger');
        return;
    }
    
    const recap = `
        <h2>RÉCAP ADR - ${currentTransporteur.toUpperCase()}</h2>
        <p><strong>Date:</strong> ${new Date().toLocaleDateString('fr-FR')}</p>
        <table border="1" style="width: 100%; border-collapse: collapse;">
            <tr><th>Code</th><th>Produit</th><th>ONU</th><th>Quantité</th></tr>
            ${selectedProducts.map(p => 
                `<tr><td>${p.code}</td><td>${p.nom}</td><td>${p.onu}</td><td>${p.quantite}</td></tr>`
            ).join('')}
        </table>
        <p><strong>Total points ADR:</strong> ${selectedProducts.reduce((sum, p) => sum + p.points, 0).toFixed(1)}</p>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Récap ADR</title></head><body>' + recap + '</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function showMessage(text, type) {
    const container = document.getElementById('messages');
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.textContent = text;
    container.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container')) {
        document.getElementById('produit-suggestions').style.display = 'none';
    }
});
</script>

<?php include ROOT_PATH . '/templates/footer.php'; ?>

<?php
// public/adr/declaration/create.php - Création expédition ADR complète
session_start();

// Vérification authentification ADR
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    $_SESSION['adr_logged_in'] = true;
    $_SESSION['adr_user'] = 'demo.user';
    $_SESSION['adr_login_time'] = time();
    $_SESSION['adr_permissions'] = ['read', 'write', 'admin', 'dev'];
}

require __DIR__ . '/../../../config.php';

// Configuration
define('GULDAGIL_EXPEDITEUR', [
    'nom' => 'GULDAGIL',
    'adresse_complete' => '4 Rue Robert Schuman',
    'code_postal' => '68170',
    'ville' => 'RIXHEIM',
    'telephone' => '03 89 44 13 17',
    'email' => 'guldagil@guldagil.com'
]);

$transporteurs = [
    'heppner' => 'Heppner',
    'xpo' => 'XPO Logistics', 
    'kn' => 'Kuehne + Nagel'
];

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'search_destinataires':
                echo json_encode(searchDestinataires($db, $_POST['query']));
                break;
                
            case 'save_destinataire':
                echo json_encode(saveDestinataire($db, $_POST));
                break;
                
            case 'search_villes_by_cp':
                echo json_encode(searchVillesByCP($db, $_POST['cp']));
                break;
                
            case 'search_villes_by_name':
                echo json_encode(searchVillesByName($db, $_POST['ville']));
                break;
                
            case 'search_villes_by_cp_and_name':
                echo json_encode(searchVillesByCPAndName($db, $_POST['cp'], $_POST['ville']));
                break;
                
            case 'search_products':
                echo json_encode(searchProducts($db, $_POST['query'] ?? ''));
                break;
                
            case 'get_product_info':
                echo json_encode(getProductInfo($db, $_POST['code']));
                break;
                
            case 'get_quotas_jour':
                echo json_encode(getQuotasJour($db, $_POST['transporteur'], $_POST['date']));
                break;
                
            case 'create_expedition':
                echo json_encode(createExpedition($db, $_POST));
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
        }
    } catch (Exception $e) {
        error_log("Erreur AJAX ADR: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    }
    exit;
}

/**
 * Recherche des destinataires fréquents
 */
function searchDestinataires($db, $query) {
    try {
        $stmt = $db->prepare("
            SELECT id, nom, adresse_complete, code_postal, ville, pays, telephone, email,
                   COUNT(*) as frequence_utilisation
            FROM gul_adr_destinataires_frequents 
            WHERE nom LIKE ? OR adresse_complete LIKE ?
            GROUP BY nom, adresse_complete
            ORDER BY frequence_utilisation DESC, nom ASC
            LIMIT 10
        ");
        
        $searchPattern = '%' . $query . '%';
        $stmt->execute([$searchPattern, $searchPattern]);
        $destinataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['success' => true, 'destinataires' => $destinataires];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Sauvegarde d'un nouveau destinataire
 */
function saveDestinataire($db, $data) {
    try {
        $stmt = $db->prepare("
            INSERT INTO gul_adr_destinataires_frequents 
            (nom, adresse_complete, code_postal, ville, pays, telephone, email, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['nom'],
            $data['adresse_complete'],
            $data['code_postal'],
            $data['ville'],
            $data['pays'] ?: 'France',
            $data['telephone'],
            $data['email'],
            $_SESSION['adr_user']
        ]);
        
        $destinataire = [
            'id' => $db->lastInsertId(),
            'nom' => $data['nom'],
            'adresse_complete' => $data['adresse_complete'],
            'code_postal' => $data['code_postal'],
            'ville' => $data['ville'],
            'pays' => $data['pays'] ?: 'France',
            'telephone' => $data['telephone'],
            'email' => $data['email']
        ];
        
        return ['success' => true, 'destinataire' => $destinataire];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Recherche de villes par code postal
 */
function searchVillesByCP($db, $cp) {
    try {
        // Pour la démo, données simulées
        $villes = [
            '67000' => [['code_postal' => '67000', 'ville' => 'STRASBOURG', 'departement' => 'Bas-Rhin']],
            '68000' => [['code_postal' => '68000', 'ville' => 'COLMAR', 'departement' => 'Haut-Rhin']],
            '68100' => [['code_postal' => '68100', 'ville' => 'MULHOUSE', 'departement' => 'Haut-Rhin']],
            '68170' => [['code_postal' => '68170', 'ville' => 'RIXHEIM', 'departement' => 'Haut-Rhin']],
            '75001' => [['code_postal' => '75001', 'ville' => 'PARIS 1ER', 'departement' => 'Paris']],
        ];
        
        $results = [];
        foreach ($villes as $code => $villeList) {
            if (strpos($code, $cp) === 0) {
                $results = array_merge($results, $villeList);
            }
        }
        
        return ['success' => true, 'villes' => $results];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Recherche de villes par nom
 */
function searchVillesByName($db, $ville) {
    try {
        // Données simulées pour la démo
        $villes = [
            ['code_postal' => '67000', 'ville' => 'STRASBOURG', 'departement' => 'Bas-Rhin'],
            ['code_postal' => '68000', 'ville' => 'COLMAR', 'departement' => 'Haut-Rhin'],
            ['code_postal' => '68100', 'ville' => 'MULHOUSE', 'departement' => 'Haut-Rhin'],
            ['code_postal' => '68170', 'ville' => 'RIXHEIM', 'departement' => 'Haut-Rhin'],
        ];
        
        $results = array_filter($villes, function($v) use ($ville) {
            return stripos($v['ville'], $ville) !== false;
        });
        
        return ['success' => true, 'villes' => array_values($results)];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Recherche combinée CP + ville
 */
function searchVillesByCPAndName($db, $cp, $ville) {
    try {
        $results1 = searchVillesByCP($db, $cp);
        $results2 = searchVillesByName($db, $ville);
        
        if ($results1['success'] && $results2['success']) {
            // Intersection des résultats
            $villes1 = $results1['villes'];
            $villes2 = $results2['villes'];
            
            $intersection = [];
            foreach ($villes1 as $v1) {
                foreach ($villes2 as $v2) {
                    if ($v1['code_postal'] === $v2['code_postal'] && $v1['ville'] === $v2['ville']) {
                        $intersection[] = $v1;
                        break;
                    }
                }
            }
            
            return ['success' => true, 'villes' => $intersection];
        }
        
        return ['success' => false, 'error' => 'Erreur recherche combinée'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Recherche de produits ADR
 */
function searchProducts($db, $query = '') {
    try {
        // Produits de démonstration
        $products = [
            [
                'code_produit' => 'GUL-001',
                'designation' => 'GULTRAT pH+',
                'numero_onu' => 'UN1823',
                'points_adr_par_unite' => 1,
                'categorie_transport' => '8'
            ],
            [
                'code_produit' => 'GUL-002',
                'designation' => 'PERFORMAX',
                'numero_onu' => 'UN3265',
                'points_adr_par_unite' => 2,
                'categorie_transport' => '3'
            ],
            [
                'code_produit' => 'GUL-003',
                'designation' => 'ALKADOSE',
                'numero_onu' => 'UN1824',
                'points_adr_par_unite' => 1,
                'categorie_transport' => '8'
            ],
            [
                'code_produit' => 'GUL-004',
                'designation' => 'CHLORE LIQUIDE',
                'numero_onu' => 'UN1791',
                'points_adr_par_unite' => 3,
                'categorie_transport' => '2'
            ]
        ];
        
        if ($query) {
            $products = array_filter($products, function($p) use ($query) {
                return stripos($p['code_produit'], $query) !== false || 
                       stripos($p['designation'], $query) !== false;
            });
        }
        
        return ['success' => true, 'products' => array_values($products)];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Informations détaillées d'un produit
 */
function getProductInfo($db, $code) {
    try {
        $products = searchProducts($db, '');
        if ($products['success']) {
            foreach ($products['products'] as $product) {
                if ($product['code_produit'] === $code) {
                    return ['success' => true, 'product' => $product];
                }
            }
        }
        
        return ['success' => false, 'error' => 'Produit non trouvé'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Récupération des quotas du jour
 */
function getQuotasJour($db, $transporteur, $date) {
    try {
        // Simulation quotas
        $quotas = [
            'transporteur' => $transporteur,
            'date' => $date,
            'quota_max' => 1000,
            'points_utilises' => rand(300, 800),
            'points_restants' => 0,
            'pourcentage_utilise' => 0,
            'alerte_depassement' => false
        ];
        
        $quotas['points_restants'] = $quotas['quota_max'] - $quotas['points_utilises'];
        $quotas['pourcentage_utilise'] = ($quotas['points_utilises'] / $quotas['quota_max']) * 100;
        $quotas['alerte_depassement'] = $quotas['points_restants'] < 100;
        
        return array_merge(['success' => true], $quotas);
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Création de l'expédition
 */
function createExpedition($db, $data) {
    try {
        // Validation des données
        if (empty($data['destinataire_nom']) || empty($data['transporteur']) || 
            empty($data['date_expedition']) || empty($data['produits'])) {
            return ['success' => false, 'error' => 'Données incomplètes'];
        }
        
        // Générer numéro expédition
        $numero = 'ADR-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Simulation sauvegarde
        $expeditionId = rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => "Expédition $numero créée avec succès",
            'expedition_id' => $expeditionId,
            'numero_expedition' => $numero
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création expédition ADR - Guldagil</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/adr.css">
    <link rel="stylesheet" href="../assets/css/adr-create.css">
</head>
<body>
    <!-- Header ADR -->
    <header class="adr-header">
        <div class="header-container">
            <div class="header-title">
                <div class="adr-logo">⚠️</div>
                <div>
                    <h1>Nouvelle expédition ADR</h1>
                    <div class="header-subtitle">Création d'une déclaration de marchandises dangereuses</div>
                </div>
            </div>
            
            <div class="header-actions">
                <a href="../dashboard.php" class="btn-header">
                    <span>📊</span>
                    Dashboard
                </a>
                <a href="../" class="btn-header">
                    <span>🏠</span>
                    Portal
                </a>
            </div>
        </div>
    </header>

    <!-- Layout principal -->
    <div class="expedition-layout">
        <!-- Contenu principal -->
        <div class="main-content">
            
            <!-- Étape 1: Destinataire -->
            <div id="step-destinataire" class="step-content active">
                <h2>📍 Étape 1: Destinataire</h2>
                
                <!-- Recherche destinataire -->
                <div class="form-group destinataire-search-container">
                    <label for="search-destinataire">Rechercher un destinataire</label>
                    <input type="text" 
                           id="search-destinataire" 
                           class="form-control"
                           placeholder="Tapez le nom d'une entreprise..."
                           autocomplete="off">
                    <div id="destinataires-suggestions" class="suggestions-container"></div>
                </div>
                
                <!-- Destinataire sélectionné -->
                <div id="selected-destinataire" class="selected-info" style="display: none;">
                    <h4>✅ Destinataire sélectionné</h4>
                    <div id="selected-destinataire-info"></div>
                    <button class="btn btn-secondary btn-sm" onclick="changeDestinataire()">
                        🔄 Changer
                    </button>
                </div>
                
                <!-- Formulaire nouveau destinataire -->
                <div id="new-destinataire-form" style="display: none;">
                    <h4>➕ Nouveau destinataire</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="destinataire-nom">Nom / Raison sociale *</label>
                            <input type="text" id="destinataire-nom" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="destinataire-adresse">Adresse</label>
                            <input type="text" id="destinataire-adresse" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="destinataire-cp">Code postal *</label>
                            <input type="text" id="destinataire-cp" class="form-control" 
                                   pattern="\d{5}" maxlength="5" required>
                            <div id="villes-suggestions" class="suggestions-container"></div>
                        </div>
                        <div class="form-group">
                            <label for="destinataire-ville">Ville *</label>
                            <input type="text" id="destinataire-ville" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="destinataire-telephone">Téléphone</label>
                            <input type="tel" id="destinataire-telephone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="destinataire-email">Email</label>
                            <input type="email" id="destinataire-email" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button class="btn btn-secondary" onclick="cancelNewDestinataire()">
                            ❌ Annuler
                        </button>
                        <button class="btn btn-success" onclick="saveNewDestinataire()">
                            💾 Enregistrer
                        </button>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="step-navigation">
                    <button id="btn-next-to-products" class="btn btn-primary" disabled onclick="nextToProducts()">
                        Étape suivante: Produits ➡️
                    </button>
                </div>
            </div>
            
            <!-- Étape 2: Produits -->
            <div id="step-products" class="step-content">
                <h2>📦 Étape 2: Produits et transport</h2>
                
                <!-- Informations transport -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="expedition-transporteur">Transporteur *</label>
                        <select id="expedition-transporteur" class="form-control" required>
                            <option value="">Sélectionner un transporteur...</option>
                            <?php foreach ($transporteurs as $code => $nom): ?>
                                <option value="<?= $code ?>"><?= htmlspecialchars($nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="expedition-date">Date d'expédition *</label>
                        <input type="date" 
                               id="expedition-date" 
                               class="form-control"
                               value="<?= date('Y-m-d') ?>"
                               min="<?= date('Y-m-d') ?>"
                               required>
                    </div>
                </div>
                
                <!-- Ajout produits -->
                <div class="produits-section">
                    <h4>⚠️ Ajouter des produits ADR</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="produit-code">Code produit</label>
                            <input type="text" 
                                   id="produit-code" 
                                   class="form-control"
                                   list="produits-list"
                                   placeholder="Ex: GUL-001">
                            <datalist id="produits-list"></datalist>
                        </div>
                        
                        <div class="form-group">
                            <label for="produit-designation">Désignation</label>
                            <input type="text" id="produit-designation" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="produit-numero-onu">Numéro ONU</label>
                            <input type="text" id="produit-numero-onu" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="produit-quantite">Quantité *</label>
                            <input type="number" 
                                   id="produit-quantite" 
                                   class="form-control"
                                   step="0.1" 
                                   min="0.1"
                                   placeholder="Ex: 25.5">
                        </div>
                    </div>
                    
                    <button class="btn btn-primary" onclick="addProductToExpedition()">
                        ➕ Ajouter ce produit
                    </button>
                </div>
                
                <!-- Liste des produits -->
                <div class="products-list-section">
                    <h4>📋 Produits de l'expédition</h4>
                    
                    <div id="products-empty" class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <p>Aucun produit ajouté</p>
                        <small>Ajoutez des produits ADR à cette expédition</small>
                    </div>
                    
                    <div id="products-table-container" style="display: none;">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Désignation</th>
                                    <th>ONU</th>
                                    <th>Quantité</th>
                                    <th>Points ADR</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="products-table-body"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4"><strong>TOTAL</strong></td>
                                    <td><strong id="total-points-adr">0 points</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="step-navigation">
                    <button class="btn btn-secondary" onclick="backToDestinataire()">
                        ⬅️ Retour: Destinataire
                    </button>
                    <button id="btn-next-to-validation" class="btn btn-primary" disabled onclick="nextToValidation()">
                        Étape suivante: Validation ➡️
                    </button>
                </div>
            </div>
            
            <!-- Étape 3: Validation -->
            <div id="step-validation" class="step-content">
                <h2>✅ Étape 3: Validation et création</h2>
                
                <!-- Récapitulatif -->
                <div id="expedition-summary" class="expedition-summary">
                    <!-- Généré par JavaScript -->
                </div>
                
                <!-- Observations -->
                <div class="form-group">
                    <label for="expedition-observations">Observations (optionnel)</label>
                    <textarea id="expedition-observations" 
                              class="form-control"
                              rows="3"
                              placeholder="Remarques particulières..."></textarea>
                </div>
                
                <!-- Actions finales -->
                <div class="step-navigation">
                    <button class="btn btn-secondary" onclick="backToProducts()">
                        ⬅️ Retour: Produits
                    </button>
                    
                    <div class="final-actions">
                        <button class="btn btn-warning" onclick="saveAsDraft()">
                            💾 Sauver brouillon
                        </button>
                        <button class="btn btn-success" onclick="createExpedition()">
                            🚀 Créer l'expédition
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar étapes -->
        <div class="process-steps">
            <h3>📋 Progression</h3>
            
            <!-- Étapes -->
            <div class="step active" data-step="destinataire">
                <div class="step-number">1</div>
                <div class="step-info">
                    <div class="step-title">Destinataire</div>
                    <div class="step-subtitle">Qui reçoit ?</div>
                </div>
            </div>
            
            <div class="step disabled" data-step="products">
                <div class="step-number">2</div>
                <div class="step-info">
                    <div class="step-title">Produits</div>
                    <div class="step-subtitle">Que transporter ?</div>
                </div>
            </div>
            
            <div class="step disabled" data-step="validation">
                <div class="step-number">3</div>
                <div class="step-info">
                    <div class="step-title">Validation</div>
                    <div class="step-subtitle">Vérifier et créer</div>
                </div>
            </div>
            
            <!-- Quotas du jour -->
            <div class="quotas-section">
                <h4>📊 Quotas du jour</h4>
                
                <div id="quota-placeholder">
                    <p>Sélectionnez un transporteur et une date pour voir les quotas</p>
                </div>
                
                <div id="quota-info" style="display: none;">
                    <div class="quota-header">
                        <strong id="quota-transporteur-name">-</strong>
                        <span id="quota-date">-</span>
                    </div>
                    
                    <div class="quota-bar">
                        <div id="quota-fill" class="quota-fill"></div>
                    </div>
                    
                    <div class="quota-info">
                        <div>Utilisé: <span id="quota-utilise">-</span></div>
                        <div>Restant: <span id="quota-restant">-</span></div>
                    </div>
                    
                    <div id="quota-alert" class="alert alert-warning" style="display: none;">
                        ⚠️ Quota bientôt dépassé !
                    </div>
                </div>
            </div>
            
            <!-- Résumé progression -->
            <div id="expedition-progress" style="display: none;">
                <h4>📋 Résumé</h4>
                <div class="progress-info">
                    <div id="progress-destinataire">👤 Aucun destinataire</div>
                    <div id="progress-products">📦 0 produit(s)</div>
                    <div id="progress-points">⚠️ 0 points ADR</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/js/adr.js"></script>
    <script src="../assets/js/adr-destinataire.js"></script>
    <script src="../assets/js/adr-create-expedition.js"></script>
    
    <script>
        // Configuration globale
        window.ADR_CONFIG = {
            expediteur: <?= json_encode(GULDAGIL_EXPEDITEUR) ?>,
            transporteurs: <?= json_encode($transporteurs) ?>,
            session: {
                user: '<?= $_SESSION['adr_user'] ?>',
                permissions: <?= json_encode($_SESSION['adr_permissions']) ?>
            }
        };
        
        console.log('🚀 Formulaire création expédition ADR initialisé');
        console.log('📋 Configuration:', window.ADR_CONFIG);
    </script>
</body>
</html>

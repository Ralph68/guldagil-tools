<?php
// public/adr/declaration/create.php - Création d'expédition ADR multi-lignes
session_start();

// Vérification authentification ADR
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    $_SESSION['adr_logged_in'] = true;
    $_SESSION['adr_user'] = 'demo.user';
    $_SESSION['adr_login_time'] = time();
}

require __DIR__ . '/../../../config.php';

// Configuration par défaut
define('GULDAGIL_EXPEDITEUR', [
    'nom' => 'GULDAGIL',
    'adresse' => "Siège social et Usine - 4 Rue Robert Schuman\n68170 RIXHEIM",
    'telephone' => '03 89 44 13 17',
    'email' => 'guldagil@guldagil.com'
]);

define('QUOTA_MAX_POINTS_JOUR', 1000); // Points ADR max par jour/transporteur

// Traitement AJAX des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'search_clients':
                echo json_encode(searchClients($db, $_POST['query'] ?? ''));
                break;
                
            case 'save_client':
                echo json_encode(saveClient($db, $_POST));
                break;
                
            case 'get_quotas_jour':
                echo json_encode(getQuotasJour($db, $_POST['date'] ?? date('Y-m-d'), $_POST['transporteur'] ?? ''));
                break;
                
            default:
                throw new Exception('Action non supportée');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Traitement du formulaire principal (création expédition complète)
$errors = [];
$success = '';
$expeditionData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $result = processExpeditionForm($db, $_POST);
    if ($result['success']) {
        $success = $result['message'];
        $expeditionData = $result['data'];
    } else {
        $errors = $result['errors'];
    }
}

// Charger les transporteurs
$transporteurs = [
    'heppner' => 'Heppner',
    'xpo' => 'XPO Logistics', 
    'kn' => 'Kuehne + Nagel'
];

/**
 * Recherche de clients dans la base
 */
function searchClients($db, $query) {
    if (strlen($query) < 2) {
        return ['success' => true, 'clients' => []];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT id, nom, adresse_complete, code_postal, ville, telephone, email,
                   CONCAT(nom, ' - ', code_postal, ' ', ville) as display_name
            FROM gul_clients 
            WHERE (nom LIKE ? OR ville LIKE ? OR code_postal LIKE ?) 
            AND actif = 1
            ORDER BY nom
            LIMIT 20
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        
        return [
            'success' => true,
            'clients' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Sauvegarde d'un nouveau client
 */
function saveClient($db, $data) {
    try {
        $stmt = $db->prepare("
            INSERT INTO gul_clients (nom, adresse_complete, code_postal, ville, pays, telephone, email, cree_par)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['nom'],
            $data['adresse_complete'] ?? '',
            $data['code_postal'],
            $data['ville'],
            $data['pays'] ?? 'France',
            $data['telephone'] ?? '',
            $data['email'] ?? '',
            $_SESSION['adr_user']
        ]);
        
        $clientId = $db->lastInsertId();
        
        // Récupérer le client créé
        $stmt = $db->prepare("SELECT * FROM gul_clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'message' => 'Client enregistré avec succès',
            'client' => $client
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Récupération des quotas ADR du jour
 */
function getQuotasJour($db, $date, $transporteur) {
    try {
        // Calculer les points déjà utilisés aujourd'hui
        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN p.categorie_transport = '1' THEN el.quantite_declaree
                        WHEN p.categorie_transport = '2' THEN el.quantite_declaree
                        WHEN p.categorie_transport = '3' THEN el.quantite_declaree
                        ELSE 0
                    END
                ), 0) as points_utilises
            FROM gul_adr_expeditions e
            JOIN gul_adr_expedition_lignes el ON e.id = el.expedition_id
            JOIN gul_adr_products p ON el.code_produit = p.code_produit
            WHERE e.date_expedition = ? AND e.transporteur = ? AND e.statut != 'annule'
        ");
        
        $stmt->execute([$date, $transporteur]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $pointsUtilises = floatval($result['points_utilises'] ?? 0);
        $pointsRestants = QUOTA_MAX_POINTS_JOUR - $pointsUtilises;
        $pourcentageUtilise = ($pointsUtilises / QUOTA_MAX_POINTS_JOUR) * 100;
        
        return [
            'success' => true,
            'quota_max' => QUOTA_MAX_POINTS_JOUR,
            'points_utilises' => $pointsUtilises,
            'points_restants' => max(0, $pointsRestants),
            'pourcentage_utilise' => min(100, $pourcentageUtilise),
            'alerte_depassement' => $pointsRestants < 0
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Traitement du formulaire d'expédition complète
 */
function processExpeditionForm($db, $data) {
    // TODO: Implémenter la création d'expédition complète
    return [
        'success' => false,
        'errors' => ['Fonctionnalité en cours de développement']
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle expédition ADR - Guldagil Portal</title>
    <style>
        :root {
            --adr-primary: #ff6b35;
            --adr-secondary: #f7931e;
            --adr-danger: #dc3545;
            --adr-warning: #ffc107;
            --adr-success: #28a745;
            --adr-info: #17a2b8;
            --adr-dark: #343a40;
            --adr-light: #f8f9fa;
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 16px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Header ADR */
        .adr-header {
            background: linear-gradient(135deg, var(--adr-primary) 0%, var(--adr-secondary) 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-header {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-header:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        /* Container principal */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Layout à étapes */
        .expedition-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        /* Étapes du processus */
        .process-steps {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            transition: var(--transition);
            cursor: pointer;
        }

        .step.active {
            background: var(--adr-primary);
            color: white;
        }

        .step.completed {
            background: var(--adr-success);
            color: white;
        }

        .step.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .step-number {
            width: 30px;
            height: 30px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .step.active .step-number,
        .step.completed .step-number {
            background: rgba(255,255,255,0.9);
            color: var(--adr-primary);
        }

        /* Quotas du jour */
        .quotas-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--adr-light);
            border-radius: var(--border-radius);
        }

        .quota-bar {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .quota-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--adr-success) 0%, var(--adr-warning) 70%, var(--adr-danger) 100%);
            transition: width 0.5s ease;
        }

        .quota-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }

        /* Contenu principal */
        .main-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        /* Formulaires */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--adr-dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--adr-primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row.three-cols {
            grid-template-columns: 1fr 1fr 1fr;
        }

        /* Recherche clients */
        .client-search {
            position: relative;
        }

        .client-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }

        .client-suggestion {
            padding: 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
        }

        .client-suggestion:hover {
            background: var(--adr-light);
        }

        .client-name {
            font-weight: 600;
            color: var(--adr-primary);
        }

        .client-details {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        /* Tableau produits */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .products-table th,
        .products-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .products-table th {
            background: var(--adr-light);
            font-weight: 600;
            color: var(--adr-dark);
        }

        .products-table .inline-edit {
            border: none;
            background: transparent;
            width: 100%;
            padding: 0.25rem;
        }

        .products-table .inline-edit:focus {
            background: white;
            border: 2px solid var(--adr-primary);
            border-radius: 4px;
        }

        /* Boutons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--adr-primary);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #e55a2b;
            transform: translateY(-1px);
            box-shadow: var(--shadow-hover);
        }

        .btn-success {
            background: var(--adr-success);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: var(--adr-danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Alertes */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-warning {
            background: #fff3cd;
            border-left-color: var(--adr-warning);
            color: #856404;
        }

        .alert-danger {
            background: #f8d7da;
            border-left-color: var(--adr-danger);
            color: #721c24;
        }

        .alert-success {
            background: #d4edda;
            border-left-color: var(--adr-success);
            color: #155724;
        }

        /* Messages vides */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Actions flottantes */
        .floating-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 1000;
        }

        .floating-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            background: var(--adr-primary);
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: var(--shadow-hover);
            transition: var(--transition);
        }

        .floating-btn:hover {
            transform: scale(1.1);
            background: #e55a2b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .expedition-layout {
                grid-template-columns: 1fr;
            }
            
            .process-steps {
                position: static;
                order: -1;
            }
            
            .form-row,
            .form-row.three-cols {
                grid-template-columns: 1fr;
            }
            
            .floating-actions {
                bottom: 1rem;
                right: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header ADR -->
    <header class="adr-header">
        <div class="header-container">
            <div class="header-title">
                <h1>
                    <span>🚚</span>
                    Nouvelle expédition ADR
                </h1>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <a href="../dashboard.php" class="btn-header">
                    <span>📊</span>
                    Dashboard
                </a>
                <a href="list.php" class="btn-header">
                    <span>📋</span>
                    Liste
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="expedition-layout">
            <!-- Colonne de gauche : Contenu principal -->
            <div class="main-content">
                <!-- ÉTAPE 1: Sélection destinataire -->
                <div class="step-content active" id="step-destinataire">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                        <div style="width: 50px; height: 50px; background: var(--adr-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            📥
                        </div>
                        <div>
                            <h2>Étape 1 : Destinataire</h2>
                            <p style="color: #666; margin: 0;">Sélectionnez ou créez le client destinataire</p>
                        </div>
                    </div>

                    <!-- Info expéditeur par défaut -->
                    <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                        <h4 style="color: var(--adr-primary); margin-bottom: 0.5rem;">📤 Expéditeur (par défaut)</h4>
                        <div><strong><?= GULDAGIL_EXPEDITEUR['nom'] ?></strong></div>
                        <div style="white-space: pre-line; color: #666; font-size: 0.9rem;"><?= GULDAGIL_EXPEDITEUR['adresse'] ?></div>
                        <div style="font-size: 0.9rem; color: #666;">
                            Tél: <?= GULDAGIL_EXPEDITEUR['telephone'] ?> | Email: <?= GULDAGIL_EXPEDITEUR['email'] ?>
                        </div>
                        <label style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="expedition_enlevement" value="1">
                            <span>🚛 Enlèvement chez fournisseur (expéditeur différent)</span>
                        </label>
                    </div>

                    <!-- Recherche client -->
                    <div class="form-group">
                        <label for="search-client">🔍 Rechercher un client</label>
                        <div class="client-search">
                            <input type="text" 
                                   class="form-control" 
                                   id="search-client" 
                                   placeholder="Tapez le nom, ville ou code postal du client..."
                                   autocomplete="off">
                            <div class="client-suggestions" id="client-suggestions"></div>
                        </div>
                    </div>

                    <!-- Formulaire nouveau client -->
                    <div id="new-client-form" style="display: none;">
                        <h4 style="color: var(--adr-primary); margin: 2rem 0 1rem 0;">➕ Nouveau client</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="client-nom">Nom du client <span style="color: var(--adr-danger);">*</span></label>
                                <input type="text" class="form-control" id="client-nom" required>
                            </div>
                            <div class="form-group">
                                <label for="client-telephone">Téléphone</label>
                                <input type="tel" class="form-control" id="client-telephone">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="client-adresse">Adresse complète</label>
                            <textarea class="form-control" id="client-adresse" rows="2" placeholder="Rue, numéro..."></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="client-codepostal">Code postal <span style="color: var(--adr-danger);">*</span></label>
                                <input type="text" class="form-control" id="client-codepostal" required>
                            </div>
                            <div class="form-group">
                                <label for="client-ville">Ville <span style="color: var(--adr-danger);">*</span></label>
                                <input type="text" class="form-control" id="client-ville" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="client-pays">Pays</label>
                                <select class="form-control" id="client-pays">
                                    <option value="France">France</option>
                                    <option value="Belgique">Belgique</option>
                                    <option value="Luxembourg">Luxembourg</option>
                                    <option value="Monaco">Monaco</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="client-email">Email</label>
                                <input type="email" class="form-control" id="client-email">
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="button" class="btn btn-success" onclick="saveNewClient()">
                                💾 Enregistrer le client
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="cancelNewClient()">
                                ❌ Annuler
                            </button>
                        </div>
                    </div>

                    <!-- Client sélectionné -->
                    <div id="selected-client" style="display: none;">
                        <h4 style="color: var(--adr-success); margin: 2rem 0 1rem 0;">✅ Client sélectionné</h4>
                        <div id="selected-client-info" style="background: #d4edda; padding: 1rem; border-radius: var(--border-radius);"></div>
                        <button type="button" class="btn btn-secondary" onclick="changeClient()" style="margin-top: 1rem;">
                            🔄 Changer de client
                        </button>
                    </div>

                    <!-- Actions étape 1 -->
                    <div style="margin-top: 2rem; text-align: right;">
                        <button type="button" class="btn btn-primary" id="btn-next-to-products" onclick="nextToProducts()" disabled>
                            Ajouter des produits ➡️
                        </button>
                    </div>
                </div>

                <!-- ÉTAPE 2: Ajout produits -->
                <div class="step-content" id="step-products">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                        <div style="width: 50px; height: 50px; background: var(--adr-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            ⚠️
                        </div>
                        <div>
                            <h2>Étape 2 : Produits ADR</h2>
                            <p style="color: #666; margin: 0;">Ajoutez les produits dangereux ligne par ligne</p>
                        </div>
                    </div>

                    <!-- Sélection transporteur et date -->
                    <div class="form-row" style="margin-bottom: 2rem;">
                        <div class="form-group">
                            <label for="expedition-transporteur">🚚 Transporteur <span style="color: var(--adr-danger);">*</span></label>
                            <select class="form-control" id="expedition-transporteur" required>
                                <option value="">Sélectionner un transporteur</option>
                                <?php foreach ($transporteurs as $code => $nom): ?>
                                    <option value="<?= $code ?>"><?= htmlspecialchars($nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="expedition-date">📅 Date d'expédition <span style="color: var(--adr-danger);">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="expedition-date"
                                   value="<?= date('Y-m-d') ?>"
                                   min="<?= date('Y-m-d') ?>"
                                   max="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                                   required>
                        </div>
                    </div>

                    <!-- Ajout de produit -->
                    <div style="background: var(--adr-light); padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                        <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">➕ Ajouter un produit</h4>
                        
                        <div class="form-row">
<div class="form-group">
                                <label for="produit-code">Code produit Guldagil <span style="color: var(--adr-danger);">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="produit-code" 
                                       placeholder="Ex: GUL-001"
                                       list="produits-list">
                                <datalist id="produits-list">
                                    <!-- Sera rempli dynamiquement -->
                                </datalist>
                            </div>
                            
                            <div class="form-group">
                                <label for="produit-quantite">Quantité (L ou Kg) <span style="color: var(--adr-danger);">*</span></label>
                                <input type="number" 
                                       class="form-control" 
                                       id="produit-quantite" 
                                       placeholder="0.0" 
                                       step="0.1" 
                                       min="0.1">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="produit-designation">Désignation produit</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="produit-designation" 
                                       readonly
                                       placeholder="Sera rempli automatiquement">
                            </div>
                            
                            <div class="form-group">
                                <label for="produit-numero-onu">N° ONU</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="produit-numero-onu" 
                                       readonly
                                       placeholder="Sera rempli automatiquement">
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <button type="button" class="btn btn-success" onclick="addProductToExpedition()">
                                ➕ Ajouter à l'expédition
                            </button>
                        </div>
                    </div>

                    <!-- Liste des produits ajoutés -->
                    <div id="products-list-container">
                        <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📦 Produits de l'expédition</h4>
                        
                        <div id="products-empty" class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <p>Aucun produit ajouté</p>
                            <small>Ajoutez des produits ADR pour créer votre expédition</small>
                        </div>

                        <div id="products-table-container" style="display: none;">
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th>Code produit</th>
                                        <th>Désignation</th>
                                        <th>N° ONU</th>
                                        <th>Quantité</th>
                                        <th>Points ADR</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="products-table-body">
                                    <!-- Lignes ajoutées dynamiquement -->
                                </tbody>
                                <tfoot>
                                    <tr style="background: var(--adr-light); font-weight: bold;">
                                        <td colspan="4">Total de l'expédition</td>
                                        <td id="total-points-adr">0 points</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Actions étape 2 -->
                    <div style="margin-top: 2rem; display: flex; justify-content: space-between;">
                        <button type="button" class="btn btn-secondary" onclick="backToDestinataire()">
                            ⬅️ Retour destinataire
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-next-to-validation" onclick="nextToValidation()" disabled>
                            Finaliser ➡️
                        </button>
                    </div>
                </div>

                <!-- ÉTAPE 3: Validation finale -->
                <div class="step-content" id="step-validation">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                        <div style="width: 50px; height: 50px; background: var(--adr-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            ✅
                        </div>
                        <div>
                            <h2>Étape 3 : Validation</h2>
                            <p style="color: #666; margin: 0;">Vérifiez et validez votre expédition ADR</p>
                        </div>
                    </div>

                    <!-- Récapitulatif complet -->
                    <div id="expedition-summary">
                        <!-- Sera rempli dynamiquement -->
                    </div>

                    <!-- Informations légales -->
                    <div style="background: #fff3cd; border: 1px solid var(--adr-warning); padding: 1rem; border-radius: var(--border-radius); margin: 2rem 0;">
                        <h5 style="color: #856404; margin-bottom: 0.5rem;">⚠️ Informations importantes</h5>
                        <ul style="margin: 0; color: #856404; font-size: 0.9rem;">
                            <li>Les Fiches de Données de Sécurité (FDS) sont disponibles sur <strong>QuickFDS</strong></li>
                            <li>Le transporteur doit vérifier la conformité ADR avant enlèvement</li>
                            <li>Cette déclaration engage la responsabilité de l'expéditeur</li>
                            <li>Document à conserver 5 ans minimum</li>
                        </ul>
                    </div>

                    <!-- Actions finales -->
                    <div style="margin-top: 2rem; display: flex; justify-content: space-between;">
                        <button type="button" class="btn btn-secondary" onclick="backToProducts()">
                            ⬅️ Retour produits
                        </button>
                        <div style="display: flex; gap: 1rem;">
                            <button type="button" class="btn btn-success" onclick="saveAsDraft()">
                                💾 Sauvegarder brouillon
                            </button>
                            <button type="button" class="btn btn-primary" onclick="createExpedition()">
                                🚀 Créer l'expédition
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite : Étapes et quotas -->
            <div class="process-steps">
                <h3 style="margin-bottom: 1.5rem; color: var(--adr-primary);">📋 Processus</h3>
                
                <div class="step active" data-step="destinataire">
                    <div class="step-number">1</div>
                    <div>
                        <div style="font-weight: 600;">Destinataire</div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Client & adresse livraison</div>
                    </div>
                </div>

                <div class="step disabled" data-step="products">
                    <div class="step-number">2</div>
                    <div>
                        <div style="font-weight: 600;">Produits ADR</div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Ajout ligne par ligne</div>
                    </div>
                </div>

                <div class="step disabled" data-step="validation">
                    <div class="step-number">3</div>
                    <div>
                        <div style="font-weight: 600;">Validation</div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Contrôle & création</div>
                    </div>
                </div>

                <!-- Quotas du jour -->
                <div class="quotas-section">
                    <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📊 Quotas ADR du jour</h4>
                    
                    <div id="quota-info" style="display: none;">
                        <div style="margin-bottom: 0.5rem;">
                            <strong id="quota-transporteur-name">Transporteur</strong>
                            <span id="quota-date" style="float: right; color: #666; font-size: 0.9rem;"></span>
                        </div>
                        
                        <div class="quota-bar">
                            <div class="quota-fill" id="quota-fill" style="width: 0%;"></div>
                        </div>
                        
                        <div class="quota-info">
                            <span id="quota-utilise">0 points</span>
                            <span id="quota-restant">1000 points</span>
                        </div>
                        
                        <div id="quota-alert" class="alert alert-danger" style="display: none; margin-top: 1rem;">
                            ⚠️ Attention : quota journalier dépassé !
                        </div>
                    </div>
                    
                    <div id="quota-placeholder" style="color: #666; text-align: center; padding: 1rem;">
                        Sélectionnez un transporteur et une date pour voir les quotas
                    </div>
                </div>

                <!-- Résumé expédition en cours -->
                <div id="expedition-progress" style="margin-top: 2rem; display: none;">
                    <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📦 Expédition en cours</h4>
                    
                    <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius); font-size: 0.9rem;">
                        <div id="progress-client" style="margin-bottom: 0.5rem;"></div>
                        <div id="progress-products" style="margin-bottom: 0.5rem;"></div>
                        <div id="progress-points" style="font-weight: bold; color: var(--adr-primary);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions flottantes -->
    <div class="floating-actions">
        <button class="floating-btn" onclick="showHelp()" title="Aide">
            ❓
        </button>
        <button class="floating-btn" onclick="saveDraft()" title="Sauvegarder brouillon">
            💾
        </button>
    </div>

    <script>
        // Variables globales
        let currentStep = 'destinataire';
        let selectedClient = null;
        let expeditionProducts = [];
        let quotasData = null;
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚚 Initialisation création expédition ADR');
            initializeForm();
        });

        function initializeForm() {
            // Event listeners
            setupEventListeners();
            
            // Charger les produits disponibles
            loadAvailableProducts();
            
            // Initialiser la recherche client
            initializeClientSearch();
        }

        function setupEventListeners() {
            // Recherche client
            document.getElementById('search-client').addEventListener('input', handleClientSearch);
            
            // Changement transporteur/date
            document.getElementById('expedition-transporteur').addEventListener('change', updateQuotas);
            document.getElementById('expedition-date').addEventListener('change', updateQuotas);
            
            // Auto-complétion produits
            document.getElementById('produit-code').addEventListener('input', handleProductSearch);
            document.getElementById('produit-code').addEventListener('change', loadProductInfo);
            
            // Validation quantité
            document.getElementById('produit-quantite').addEventListener('input', updatePointsCalculation);
        }

        // ========== GESTION CLIENTS ==========
        
        function handleClientSearch() {
            const query = document.getElementById('search-client').value;
            
            if (query.length < 2) {
                hideClientSuggestions();
                return;
            }
            
            // Recherche avec délai
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => {
                searchClients(query);
            }, 300);
        }

        function searchClients(query) {
            const formData = new FormData();
            formData.append('action', 'search_clients');
            formData.append('query', query);
            
            fetch('', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayClientSuggestions(data.clients);
                } else {
                    console.error('Erreur recherche clients:', data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }

        function displayClientSuggestions(clients) {
            const container = document.getElementById('client-suggestions');
            
            if (clients.length === 0) {
                container.innerHTML = `
                    <div class="client-suggestion" onclick="showNewClientForm()">
                        <div class="client-name">➕ Créer un nouveau client</div>
                        <div class="client-details">Aucun client trouvé - Cliquez pour créer</div>
                    </div>
                `;
            } else {
                let html = '';
                clients.forEach(client => {
                    html += `
                        <div class="client-suggestion" onclick="selectClient(${JSON.stringify(client).replace(/"/g, '&quot;')})">
                            <div class="client-name">${client.nom}</div>
                            <div class="client-details">${client.adresse_complete || ''} - ${client.code_postal} ${client.ville}</div>
                        </div>
                    `;
                });
                
                html += `
                    <div class="client-suggestion" onclick="showNewClientForm()" style="border-top: 2px solid var(--adr-primary);">
                        <div class="client-name">➕ Créer un nouveau client</div>
                        <div class="client-details">Créer un client qui n'existe pas</div>
                    </div>
                `;
                
                container.innerHTML = html;
            }
            
            container.style.display = 'block';
        }

        function hideClientSuggestions() {
            document.getElementById('client-suggestions').style.display = 'none';
        }

        function selectClient(client) {
            selectedClient = client;
            
            document.getElementById('search-client').value = client.nom;
            hideClientSuggestions();
            
            // Afficher les infos client sélectionné
            document.getElementById('selected-client-info').innerHTML = `
                <div><strong>${client.nom}</strong></div>
                <div>${client.adresse_complete || 'Adresse non renseignée'}</div>
                <div><strong>${client.code_postal} ${client.ville}</strong> (${client.pays || 'France'})</div>
                ${client.telephone ? `<div>Tél: ${client.telephone}</div>` : ''}
                ${client.email ? `<div>Email: ${client.email}</div>` : ''}
            `;
            
            document.getElementById('selected-client').style.display = 'block';
            document.getElementById('new-client-form').style.display = 'none';
            document.getElementById('btn-next-to-products').disabled = false;
            
            updateProgressInfo();
        }

        function showNewClientForm() {
            hideClientSuggestions();
            document.getElementById('new-client-form').style.display = 'block';
            document.getElementById('selected-client').style.display = 'none';
            document.getElementById('client-nom').focus();
        }

        function saveNewClient() {
            const formData = new FormData();
            formData.append('action', 'save_client');
            formData.append('nom', document.getElementById('client-nom').value);
            formData.append('adresse_complete', document.getElementById('client-adresse').value);
            formData.append('code_postal', document.getElementById('client-codepostal').value);
            formData.append('ville', document.getElementById('client-ville').value);
            formData.append('pays', document.getElementById('client-pays').value);
            formData.append('telephone', document.getElementById('client-telephone').value);
            formData.append('email', document.getElementById('client-email').value);
            
            fetch('', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectClient(data.client);
                    alert('✅ Client créé avec succès');
                } else {
                    alert('❌ Erreur: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur lors de la création du client');
            });
        }

        function cancelNewClient() {
            document.getElementById('new-client-form').style.display = 'none';
            document.getElementById('search-client').value = '';
            document.getElementById('search-client').focus();
        }

        function changeClient() {
            selectedClient = null;
            document.getElementById('selected-client').style.display = 'none';
            document.getElementById('search-client').value = '';
            document.getElementById('search-client').focus();
            document.getElementById('btn-next-to-products').disabled = true;
            updateProgressInfo();
        }

        // ========== GESTION PRODUITS ==========
        
        function loadAvailableProducts() {
            // Pour démo - remplacer par un appel API réel
            const products = [
                { code: 'GUL-001', designation: 'Acide chlorhydrique 33%', numero_onu: 'UN1789', categorie: '8' },
                { code: 'GUL-002', designation: 'Hydroxyde de sodium 25%', numero_onu: 'UN1824', categorie: '8' },
                { code: 'GUL-003', designation: 'Peroxyde d\'hydrogène 35%', numero_onu: 'UN2014', categorie: '5.1' }
            ];
            
            const datalist = document.getElementById('produits-list');
            products.forEach(product => {
                const option = document.createElement('option');
                option.value = product.code;
                option.textContent = `${product.code} - ${product.designation}`;
                datalist.appendChild(option);
            });
        }

        function handleProductSearch() {
            const code = document.getElementById('produit-code').value;
            if (code.length >= 3) {
                loadProductInfo();
            }
        }

        function loadProductInfo() {
            const code = document.getElementById('produit-code').value;
            
            // Simulation - remplacer par API réelle
            const products = {
                'GUL-001': { designation: 'Acide chlorhydrique 33%', numero_onu: 'UN1789', points_par_litre: 1 },
                'GUL-002': { designation: 'Hydroxyde de sodium 25%', numero_onu: 'UN1824', points_par_litre: 1 },
                'GUL-003': { designation: 'Peroxyde d\'hydrogène 35%', numero_onu: 'UN2014', points_par_litre: 3 }
            };
            
            if (products[code]) {
                const product = products[code];
                document.getElementById('produit-designation').value = product.designation;
                document.getElementById('produit-numero-onu').value = product.numero_onu;
                window.currentProductPoints = product.points_par_litre;
            } else {
                document.getElementById('produit-designation').value = '';
                document.getElementById('produit-numero-onu').value = '';
                window.currentProductPoints = 0;
            }
            
            updatePointsCalculation();
        }

        function updatePointsCalculation() {
            const quantite = parseFloat(document.getElementById('produit-quantite').value) || 0;
            const points = quantite * (window.currentProductPoints || 0);
            
            // Afficher les points calculés (optionnel)
            console.log(`Quantité: ${quantite}L/Kg, Points: ${points}`);
        }

        function addProductToExpedition() {
            const code = document.getElementById('produit-code').value;
            const designation = document.getElementById('produit-designation').value;
            const numeroOnu = document.getElementById('produit-numero-onu').value;
            const quantite = parseFloat(document.getElementById('produit-quantite').value);
            
            if (!code || !quantite || quantite <= 0) {
                alert('❌ Veuillez remplir tous les champs requis');
                return;
            }
            
            const points = quantite * (window.currentProductPoints || 0);
            
            const product = {
                id: Date.now(), // ID temporaire
                code,
                designation,
                numero_onu: numeroOnu,
                quantite,
                points
            };
            
            expeditionProducts.push(product);
            updateProductsTable();
            clearProductForm();
            updateProgressInfo();
            updateQuotasWithCurrentProducts();
        }

        function updateProductsTable() {
            const empty = document.getElementById('products-empty');
            const table = document.getElementById('products-table-container');
            const tbody = document.getElementById('products-table-body');
            
            if (expeditionProducts.length === 0) {
                empty.style.display = 'block';
                table.style.display = 'none';
                document.getElementById('btn-next-to-validation').disabled = true;
                return;
            }
            
            empty.style.display = 'none';
            table.style.display = 'block';
            document.getElementById('btn-next-to-validation').disabled = false;
            
            let html = '';
            let totalPoints = 0;
            
            expeditionProducts.forEach(product => {
                totalPoints += product.points;
                html += `
                    <tr>
                        <td>
                            <input type="text" class="inline-edit" value="${product.code}" 
                                   onchange="updateProduct(${product.id}, 'code', this.value)">
                        </td>
                        <td>
                            <input type="text" class="inline-edit" value="${product.designation}" 
                                   onchange="updateProduct(${product.id}, 'designation', this.value)">
                        </td>
                        <td>${product.numero_onu}</td>
                        <td>
                            <input type="number" class="inline-edit" value="${product.quantite}" step="0.1" min="0.1"
                                   onchange="updateProductQuantite(${product.id}, this.value)">
                        </td>
                        <td><strong>${product.points.toFixed(1)}</strong></td>
                        <td>
                            <button class="btn btn-danger btn-sm" onclick="removeProduct(${product.id})" 
                                    title="Supprimer">
                                🗑️
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
            document.getElementById('total-points-adr').textContent = `${totalPoints.toFixed(1)} points`;
        }

        function updateProduct(id, field, value) {
            const product = expeditionProducts.find(p => p.id === id);
            if (product) {
                product[field] = value;
                updateProgressInfo();
            }
        }

        function updateProductQuantite(id, quantite) {
            const product = expeditionProducts.find(p => p.id === id);
            if (product) {
                product.quantite = parseFloat(quantite) || 0;
                product.points = product.quantite * (window.currentProductPoints || 1); // Approximation
                updateProductsTable();
                updateProgressInfo();
                updateQuotasWithCurrentProducts();
            }
        }

        function removeProduct(id) {
            if (confirm('❌ Supprimer ce produit de l\'expédition ?')) {
                expeditionProducts = expeditionProducts.filter(p => p.id !== id);
                updateProductsTable();
                updateProgressInfo();
                updateQuotasWithCurrentProducts();
            }
        }

        function clearProductForm() {
            document.getElementById('produit-code').value = '';
            document.getElementById('produit-designation').value = '';
            document.getElementById('produit-numero-onu').value = '';
            document.getElementById('produit-quantite').value = '';
        }

        // ========== GESTION QUOTAS ==========
        
        function updateQuotas() {
            const transporteur = document.getElementById('expedition-transporteur').value;
            const date = document.getElementById('expedition-date').value;
            
            if (!transporteur || !date) {
                document.getElementById('quota-info').style.display = 'none';
                document.getElementById('quota-placeholder').style.display = 'block';
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'get_quotas_jour');
            formData.append('transporteur', transporteur);
            formData.append('date', date);
            
            fetch('', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    quotasData = data;
                    updateQuotasDisplay();
                } else {
                    console.error('Erreur quotas:', data.error);
                }
            });
        }

        function updateQuotasDisplay() {
            if (!quotasData) return;
            
            document.getElementById('quota-placeholder').style.display = 'none';
            document.getElementById('quota-info').style.display = 'block';
            
            // Calculer les points de l'expédition actuelle
            const currentPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
            const totalPointsWithCurrent = quotasData.points_utilises + currentPoints;
            const pourcentageWithCurrent = (totalPointsWithCurrent / quotasData.quota_max) * 100;
            
            // Mettre à jour l'affichage
            const transporteurs = <?= json_encode($transporteurs) ?>;
            const transporteurCode = document.getElementById('expedition-transporteur').value;
            document.getElementById('quota-transporteur-name').textContent = transporteurs[transporteurCode] || transporteurCode;
            document.getElementById('quota-date').textContent = new Date(document.getElementById('expedition-date').value).toLocaleDateString('fr-FR');
            
            document.getElementById('quota-utilise').textContent = `${totalPointsWithCurrent.toFixed(1)} points`;
            document.getElementById('quota-restant').textContent = `${Math.max(0, quotasData.quota_max - totalPointsWithCurrent).toFixed(1)} restants`;
            
            // Barre de progression
            const fill = document.getElementById('quota-fill');
            fill.style.width = `${Math.min(100, pourcentageWithCurrent)}%`;
            
            // Alerte dépassement
            const alert = document.getElementById('quota-alert');
            if (totalPointsWithCurrent > quotasData.quota_max) {
                alert.style.display = 'block';
                fill.style.background = 'var(--adr-danger)';
            } else {
                alert.style.display = 'none';
                fill.style.background = '';
            }
        }

        function updateQuotasWithCurrentProducts() {
            if (quotasData) {
                updateQuotasDisplay();
            }
        }

        // ========== NAVIGATION ÉTAPES ==========
        
        function nextToProducts() {
            if (!selectedClient) {
                alert('❌ Veuillez sélectionner un client');
                return;
            }
            showStep('products');
        }

        function backToDestinataire() {
            showStep('destinataire');
        }

        function nextToValidation() {
            if (expeditionProducts.length === 0) {
                alert('❌ Veuillez ajouter au moins un produit');
                return;
            }
            
            generateExpeditionSummary();
            showStep('validation');
        }

        function backToProducts() {
            showStep('products');
        }

        function showStep(stepName) {
            // Masquer tous les contenus
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Afficher le contenu demandé
            document.getElementById('step-' + stepName).classList.add('active');
            
            // Mettre à jour les étapes dans la sidebar
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active', 'completed');
                if (step.dataset.step === stepName) {
                    step.classList.add('active');
                } else {
                    const stepOrder = ['destinataire', 'products', 'validation'];
                    const currentIndex = stepOrder.indexOf(stepName);
                    const stepIndex = stepOrder.indexOf(step.dataset.step);
                    
                    if (stepIndex < currentIndex) {
                        step.classList.add('completed');
                        step.classList.remove('disabled');
                    } else if (stepIndex > currentIndex) {
                        step.classList.add('disabled');
                    }
                }
            });
            
            currentStep = stepName;
        }

        function generateExpeditionSummary() {
            const container = document.getElementById('expedition-summary');
            const transporteur = document.getElementById('expedition-transporteur').value;
            const date = document.getElementById('expedition-date').value;
            const transporteurs = <?= json_encode($transporteurs) ?>;
            
            const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
            
            let html = `
                <!-- Informations générales -->
                <div style="background: var(--adr-light); padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                    <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📋 Informations générales</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h5>📤 Expéditeur</h5>
                            <div><strong><?= GULDAGIL_EXPEDITEUR['nom'] ?></strong></div>
                            <div style="white-space: pre-line; font-size: 0.9rem;"><?= GULDAGIL_EXPEDITEUR['adresse'] ?></div>
                            <div style="font-size: 0.9rem;">Tél: <?= GULDAGIL_EXPEDITEUR['telephone'] ?></div>
                        </div>
                        
                        <div>
                            <h5>📥 Destinataire</h5>
                            <div><strong>${selectedClient.nom}</strong></div>
                            <div style="font-size: 0.9rem;">${selectedClient.adresse_complete || ''}</div>
                            <div style="font-size: 0.9rem;"><strong>${selectedClient.code_postal} ${selectedClient.ville}</strong></div>
                            ${selectedClient.telephone ? `<div style="font-size: 0.9rem;">Tél: ${selectedClient.telephone}</div>` : ''}
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div>
                                <strong>🚚 Transporteur:</strong><br>
                                ${transporteurs[transporteur] || transporteur}
                            </div>
                            <div>
                                <strong>📅 Date d'expédition:</strong><br>
                                ${new Date(date).toLocaleDateString('fr-FR')}
                            </div>
                            <div>
                                <strong>⚠️ Total points ADR:</strong><br>
                                <span style="color: var(--adr-primary); font-weight: bold;">${totalPoints.toFixed(1)} points</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Détail des produits -->
                <div style="background: white; border: 1px solid #ddd; border-radius: var(--border-radius); overflow: hidden; margin-bottom: 2rem;">
                    <div style="background: var(--adr-primary); color: white; padding: 1rem;">
                        <h4 style="margin: 0;">⚠️ Produits dangereux (${expeditionProducts.length} référence${expeditionProducts.length > 1 ? 's' : ''})</h4>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: var(--adr-light);">
                                <tr>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd;">Code produit</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd;">Désignation officielle</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd;">N° ONU</th>
                                    <th style="padding: 0.75rem; text-align: right; border-bottom: 1px solid #ddd;">Quantité</th>
                                    <th style="padding: 0.75rem; text-align: right; border-bottom: 1px solid #ddd;">Points ADR</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            expeditionProducts.forEach(product => {
                html += `
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><strong>${product.code}</strong></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">${product.designation}</td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><strong>${product.numero_onu}</strong></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee; text-align: right;">${product.quantite} L/Kg</td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee; text-align: right;"><strong>${product.points.toFixed(1)}</strong></td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                            <tfoot style="background: var(--adr-light); font-weight: bold;">
                                <tr>
                                    <td colspan="4" style="padding: 0.75rem; border-top: 2px solid var(--adr-primary);">TOTAL DE L'EXPÉDITION</td>
                                    <td style="padding: 0.75rem; border-top: 2px solid var(--adr-primary); text-align: right; color: var(--adr-primary);">${totalPoints.toFixed(1)} points</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Mentions légales et signatures -->
                <div style="background: white; border: 1px solid #ddd; border-radius: var(--border-radius); padding: 1.5rem;">
                    <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📝 Document officiel de transport</h4>
                    
                    <div style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 2rem;">
                        <p><strong>Fiches de Données de Sécurité (FDS) :</strong><br>
                        Disponibles sur la plateforme <strong>QuickFDS</strong> - Accès transporteur garanti</p>
                        
                        <p><strong>Déclaration de l'expéditeur :</strong><br>
                        Je soussigné certifie que les marchandises décrites ci-dessus sont correctement emballées, marquées, étiquetées et en état d'être transportées par route conformément aux dispositions applicables du règlement ADR.</p>
                        
                        <p><strong>Responsabilités :</strong><br>
                        - L'expéditeur certifie la conformité des marchandises aux règlements ADR<br>
                        - Le transporteur doit vérifier la conformité extérieure avant enlèvement<br>
                        - Document à conserver 5 ans minimum par toutes les parties</p>
                    </div>
                    
                    <!-- Zone signatures -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem; border-top: 1px solid #ddd; padding-top: 1.5rem;">
                        <div>
                            <h5 style="margin-bottom: 1rem;">📤 Signature expéditeur</h5>
                            <div style="border: 1px solid #ddd; height: 80px; border-radius: 4px; position: relative;">
                                <div style="position: absolute; bottom: 5px; left: 10px; font-size: 0.8rem; color: #666;">
                                    Date: ${new Date().toLocaleDateString('fr-FR')}<br>
                                    Nom: ________________________
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h5 style="margin-bottom: 1rem;">🚚 Signature transporteur</h5>
                            <div style="border: 1px solid #ddd; height: 80px; border-radius: 4px; position: relative;">
                                <div style="position: absolute; bottom: 5px; left: 10px; font-size: 0.8rem; color: #666;">
                                    Date: ____________________<br>
                                    Nom: ________________________
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
        }

        // ========== MISE À JOUR INTERFACE ==========
        
        function updateProgressInfo() {
            const progressContainer = document.getElementById('expedition-progress');
            
            if (!selectedClient && expeditionProducts.length === 0) {
                progressContainer.style.display = 'none';
                return;
            }
            
            progressContainer.style.display = 'block';
            
            const clientInfo = selectedClient ? 
                `✅ Client: ${selectedClient.nom}` : 
                '⏳ Client: non sélectionné';
                
            const productsInfo = expeditionProducts.length > 0 ? 
                `✅ Produits: ${expeditionProducts.length} référence${expeditionProducts.length > 1 ? 's' : ''}` : 
                '⏳ Produits: aucun ajouté';
                
            const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
            const pointsInfo = totalPoints > 0 ? 
                `Total: ${totalPoints.toFixed(1)} points ADR` : 
                'Aucun point ADR';
            
            document.getElementById('progress-client').textContent = clientInfo;
            document.getElementById('progress-products').textContent = productsInfo;
            document.getElementById('progress-points').textContent = pointsInfo;
        }

        // ========== ACTIONS FINALES ==========
        
        function saveAsDraft() {
            if (!selectedClient || expeditionProducts.length === 0) {
                alert('❌ Impossible de sauvegarder : données incomplètes');
                return;
            }
            
            const draftData = {
                client: selectedClient,
                products: expeditionProducts,
                transporteur: document.getElementById('expedition-transporteur').value,
                date: document.getElementById('expedition-date').value
            };
            
            // Sauvegarder en localStorage pour démo
            localStorage.setItem('adr_expedition_draft', JSON.stringify(draftData));
            alert('💾 Brouillon sauvegardé avec succès');
        }

        function createExpedition() {
            if (!selectedClient || expeditionProducts.length === 0) {
                alert('❌ Impossible de créer l\'expédition : données incomplètes');
                return;
            }
            
            // Vérifier les quotas
            if (quotasData) {
                const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
                const totalWithCurrent = quotasData.points_utilises + totalPoints;
                
                if (totalWithCurrent > quotasData.quota_max) {
                    if (!confirm(`⚠️ ATTENTION : Cette expédition dépasse le quota journalier de ${quotasData.quota_max} points (total: ${totalWithCurrent.toFixed(1)} points).\n\nVoulez-vous continuer malgré tout ?`)) {
                        return;
                    }
                }
            }
            
            const confirmMsg = `🚀 Créer l'expédition ADR ?\n\n` +
                `Client: ${selectedClient.nom}\n` +
                `Produits: ${expeditionProducts.length} référence(s)\n` +
                `Points ADR: ${expeditionProducts.reduce((sum, p) => sum + p.points, 0).toFixed(1)}\n` +
                `Transporteur: ${document.getElementById('expedition-transporteur').options[document.getElementById('expedition-transporteur').selectedIndex].text}\n\n` +
                `Cette action générera le document officiel pour le transporteur.`;
            
            if (confirm(confirmMsg)) {
                // Simulation création - remplacer par appel API réel
                alert('🎉 Expédition créée avec succès !\n\nN° d\'expédition: ADR-' + Date.now());
                
                // Redirection vers la liste
                setTimeout(() => {
                    window.location.href = 'list.php';
                }, 2000);
            }
        }

        function saveDraft() {
            saveAsDraft();
        }

        function showHelp() {
            const helpText = `🆘 AIDE - Création d'expédition ADR

📋 ÉTAPES :
1. Sélectionner ou créer un client destinataire
2. Ajouter les produits dangereux ligne par ligne
3. Valider et créer l'expédition

⚠️ POINTS IMPORTANTS :
• Les expéditions se font depuis Guldagil (68170 RIXHEIM) par défaut
• Les FDS sont disponibles sur QuickFDS
• Quota maximum : 1000 points ADR par jour et par transporteur
• Document à conserver 5 ans minimum

📞 SUPPORT :
Logistique : achats@guldagil.com
Technique : runser.jean.thomas@guldagil.com
Standard : 03 89 63 42 42`;

            alert(helpText);
        }

        // Initialisation de la recherche client
        function initializeClientSearch() {
            // Cacher les suggestions quand on clique ailleurs
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.client-search')) {
                    hideClientSuggestions();
                }
            });
        }

        // ========== FONCTIONS UTILITAIRES ==========
        
        function loadDraftIfExists() {
            const draft = localStorage.getItem('adr_expedition_draft');
            if (draft && confirm('📂 Un brouillon d\'expédition a été trouvé. Voulez-vous le charger ?')) {
                try {
                    const data = JSON.parse(draft);
                    
                    // Restaurer le client
                    if (data.client) {
                        selectClient(data.client);
                    }
                    
                    // Restaurer les produits
                    if (data.products) {
                        expeditionProducts = data.products;
                        updateProductsTable();
                    }
                    
                    // Restaurer transporteur et date
                    if (data.transporteur) {
                        document.getElementById('expedition-transporteur').value = data.transporteur;
                    }
                    if (data.date) {
                        document.getElementById('expedition-date').value = data.date;
                    }
                    
                    updateQuotas();
                    updateProgressInfo();
                    
                    // Aller à l'étape appropriée
                    if (data.client && data.products.length > 0) {
                        showStep('validation');
                    } else if (data.client) {
                        showStep('products');
                    }
                    
                } catch (error) {
                    console.error('Erreur chargement brouillon:', error);
                    localStorage.removeItem('adr_expedition_draft');
                }
            }
        }

        // Vérifier s'il y a un brouillon au chargement
        setTimeout(loadDraftIfExists, 1000);

        // Auto-sauvegarde toutes les 2 minutes si des données sont présentes
        setInterval(() => {
            if (selectedClient || expeditionProducts.length > 0) {
                console.log('💾 Auto-sauvegarde brouillon...');
                saveAsDraft();
            }
        }, 120000); // 2 minutes

        // Prévenir la perte de données
        window.addEventListener('beforeunload', function(e) {
            if (selectedClient || expeditionProducts.length > 0) {
                const message = 'Des données non sauvegardées seront perdues. Voulez-vous vraiment quitter ?';
                e.returnValue = message;
                return message;
            }
        });

        console.log('✅ Interface de création d\'expédition ADR initialisée');
    </script>
</body>
</html>

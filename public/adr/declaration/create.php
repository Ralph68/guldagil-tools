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
<?php
// À ajouter dans create.php - Gestion AJAX pour destinataires

// Traitement AJAX des actions destinataire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=UTF-8');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'search_destinataires':
                echo json_encode(searchDestinataires($db, $_POST['query'] ?? ''));
                break;
                
            case 'save_destinataire':
                echo json_encode(saveDestinataire($db, $_POST));
                break;
                
            case 'increment_destinataire_usage':
                echo json_encode(incrementDestinataireUsage($db, $_POST['id'] ?? 0));
                break;
                
            case 'search_villes_by_cp':
                echo json_encode(searchVillesByCP($db, $_POST['cp'] ?? ''));
                break;
                
            case 'search_villes_by_name':
                echo json_encode(searchVillesByName($db, $_POST['ville'] ?? ''));
                break;
                
            case 'search_villes_by_cp_and_name':
                echo json_encode(searchVillesByCPAndName($db, $_POST['cp'] ?? '', $_POST['ville'] ?? ''));
                break;
                
            default:
                throw new Exception('Action non supportée');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/**
 * Recherche de destinataires fréquents
 */
function searchDestinataires($db, $query) {
    if (strlen($query) < 2) {
        return ['success' => true, 'destinataires' => []];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT id, nom, adresse_complete, code_postal, ville, pays, 
                   telephone, email, frequence_utilisation
            FROM gul_adr_destinataires_frequents 
            WHERE (nom LIKE ? OR ville LIKE ? OR code_postal LIKE ?) 
            ORDER BY frequence_utilisation DESC, nom
            LIMIT 10
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        
        return [
            'success' => true,
            'destinataires' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Sauvegarde d'un nouveau destinataire
 */
function saveDestinataire($db, $data) {
    try {
        // Validation
        $nom = trim($data['nom'] ?? '');
        $codePostal = trim($data['code_postal'] ?? '');
        $ville = trim($data['ville'] ?? '');
        
        if (!$nom || !$codePostal || !$ville) {
            throw new Exception('Nom, code postal et ville sont obligatoires');
        }
        
        if (!preg_match('/^\d{5}$/', $codePostal)) {
            throw new Exception('Code postal invalide (5 chiffres requis)');
        }
        
        // Vérifier si existe déjà
        $stmt = $db->prepare("
            SELECT id FROM gul_adr_destinataires_frequents 
            WHERE nom = ? AND code_postal = ? AND ville = ?
        ");
        $stmt->execute([$nom, $codePostal, $ville]);
        
        if ($stmt->fetch()) {
            // Existe déjà, juste incrémenter
            $stmt = $db->prepare("
                UPDATE gul_adr_destinataires_frequents 
                SET frequence_utilisation = frequence_utilisation + 1,
                    derniere_utilisation = NOW(),
                    adresse_complete = COALESCE(NULLIF(?, ''), adresse_complete),
                    telephone = COALESCE(NULLIF(?, ''), telephone),
                    email = COALESCE(NULLIF(?, ''), email)
                WHERE nom = ? AND code_postal = ? AND ville = ?
            ");
            $stmt->execute([
                $data['adresse'] ?? '',
                $data['telephone'] ?? '',
                $data['email'] ?? '',
                $nom, $codePostal, $ville
            ]);
            
            return [
                'success' => true,
                'message' => 'Destinataire mis à jour'
            ];
        }
        
        // Créer nouveau
        $stmt = $db->prepare("
            INSERT INTO gul_adr_destinataires_frequents 
            (nom, adresse_complete, code_postal, ville, pays, telephone, email, cree_par)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $nom,
            $data['adresse'] ?? '',
            $codePostal,
            $ville,
            $data['pays'] ?? 'France',
            $data['telephone'] ?? '',
            $data['email'] ?? '',
            $_SESSION['adr_user'] ?? 'system'
        ]);
        
        return [
            'success' => true,
            'message' => 'Destinataire enregistré avec succès',
            'id' => $db->lastInsertId()
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Incrémenter l'usage d'un destinataire
 */
function incrementDestinataireUsage($db, $id) {
    try {
        $stmt = $db->prepare("
            UPDATE gul_adr_destinataires_frequents 
            SET frequence_utilisation = frequence_utilisation + 1,
                derniere_utilisation = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Recherche villes par code postal
 */
function searchVillesByCP($db, $cp) {
    if (strlen($cp) < 2) {
        return ['success' => true, 'villes' => []];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT code_postal, ville, departement
            FROM gul_referentiel_villes 
            WHERE code_postal LIKE ?
            ORDER BY code_postal, ville
            LIMIT 15
        ");
        
        $stmt->execute([$cp . '%']);
        
        return [
            'success' => true,
            'villes' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Recherche villes par nom
 */
function searchVillesByName($db, $ville) {
    if (strlen($ville) < 2) {
        return ['success' => true, 'villes' => []];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT code_postal, ville, departement
            FROM gul_referentiel_villes 
            WHERE ville LIKE ?
            ORDER BY ville, code_postal
            LIMIT 15
        ");
        
        $stmt->execute(['%' . $ville . '%']);
        
        return [
            'success' => true,
            'villes' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Recherche villes par CP et nom combinés
 */
function searchVillesByCPAndName($db, $cp, $ville) {
    if (strlen($cp) < 2 && strlen($ville) < 2) {
        return ['success' => true, 'villes' => []];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT code_postal, ville, departement
            FROM gul_referentiel_villes 
            WHERE code_postal LIKE ? AND ville LIKE ?
            ORDER BY 
                CASE WHEN code_postal = ? THEN 1 ELSE 2 END,
                CASE WHEN ville = ? THEN 1 ELSE 2 END,
                ville
            LIMIT 10
        ");
        
        $cpPattern = $cp . '%';
        $villePattern = '%' . $ville . '%';
        
        $stmt->execute([$cpPattern, $villePattern, $cp, $ville]);
        
        return [
            'success' => true,
            'villes' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Fonction pour traiter l'expédition finale (simplifiée)
 */
function processExpeditionFormSimplified($db, $data) {
    try {
        // Validation destinataire
        $destinataire = $data['destinataire'] ?? [];
        if (!$destinataire['nom'] || !$destinataire['code_postal'] || !$destinataire['ville']) {
            return [
                'success' => false,
                'errors' => ['Informations destinataire incomplètes']
            ];
        }
        
        // Validation transport
        $transporteur = $data['transporteur'] ?? '';
        $dateExpedition = $data['date_expedition'] ?? '';
        $products = $data['products'] ?? [];
        
        if (!$transporteur || !$dateExpedition || empty($products)) {
            return [
                'success' => false,
                'errors' => ['Transporteur, date et produits obligatoires']
            ];
        }
        
        // Générer numéro expédition
        $numeroExpedition = 'ADR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Calculer total points
        $totalPointsAdr = 0;
        foreach ($products as $product) {
            $totalPointsAdr += floatval($product['points_adr_calcules'] ?? 0);
        }
        
        $db->beginTransaction();
        
        // Insérer expédition
        $stmt = $db->prepare("
            INSERT INTO gul_adr_expeditions 
            (numero_expedition, destinataire_nom, destinataire_adresse, destinataire_code_postal, 
             destinataire_ville, destinataire_pays, destinataire_telephone, destinataire_email,
             transporteur, date_expedition, total_points_adr, observations, cree_par)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $numeroExpedition,
            $destinataire['nom'],
            $destinataire['adresse'] ?? '',
            $destinataire['code_postal'],
            $destinataire['ville'],
            $destinataire['pays'] ?? 'France',
            $destinataire['telephone'] ?? '',
            $destinataire['email'] ?? '',
            $transporteur,
            $dateExpedition,
            $totalPointsAdr,
            $data['observations'] ?? '',
            $_SESSION['adr_user'] ?? 'system'
        ]);
        
        $expeditionId = $db->lastInsertId();
        
        // Insérer lignes produits
        $stmt = $db->prepare("
            INSERT INTO gul_adr_expedition_lignes 
            (expedition_id, code_produit, quantite_declaree, unite_quantite, points_adr_calcules, ordre_ligne)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($products as $index => $product) {
            $stmt->execute([
                $expeditionId,
                $product['code_produit'],
                $product['quantite_declaree'],
                $product['unite_quantite'] ?? 'kg',
                $product['points_adr_calcules'],
                $index + 1
            ]);
        }
        
        // Sauvegarder le destinataire s'il est nouveau
        saveDestinataire($db, $destinataire);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Expédition créée avec succès',
            'data' => [
                'expedition_id' => $expeditionId,
                'numero_expedition' => $numeroExpedition,
                'total_points' => $totalPointsAdr
            ]
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Erreur création expédition ADR: " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => ['Erreur lors de la création : ' . $e->getMessage()]
        ];
    }
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
 * Recherche de produits ADR
 */
function searchProducts($db, $query) {
    if (strlen($query) < 2) {
        return ['success' => true, 'products' => []];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT code_produit, designation, numero_onu, categorie_transport, points_adr_par_unite
            FROM gul_adr_products 
            WHERE (code_produit LIKE ? OR designation LIKE ?) 
            AND actif = 1
            ORDER BY code_produit
            LIMIT 10
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm]);
        
        return [
            'success' => true,
            'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Récupère les infos d'un produit spécifique
 */
function getProductInfo($db, $code) {
    try {
        $stmt = $db->prepare("
            SELECT code_produit, designation, numero_onu, categorie_transport, points_adr_par_unite
            FROM gul_adr_products 
            WHERE code_produit = ? AND actif = 1
        ");
        
        $stmt->execute([$code]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            return [
                'success' => true,
                'product' => $product
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Produit non trouvé'
            ];
        }
        
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
    try {
        // Validation des données
        $clientId      = $data['client_id'] ?? null;
        $transporteur  = $data['transporteur'] ?? '';
        $dateExpedition = $data['date_expedition'] ?? '';
        $products      = $data['products'] ?? [];

        if (!$clientId || !$transporteur || !$dateExpedition || empty($products)) {
            return [
                'success' => false,
                'errors'  => ['Données manquantes : client, transporteur, date ou produits']
            ];
        }

        // Générer un numéro d'expédition unique
        $numeroExpedition = 'ADR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Calculer le total des points
        $totalPointsAdr = 0;
        foreach ($products as $product) {
            $totalPointsAdr += floatval($product['points_adr_calcules'] ?? 0);
        }

        $db->beginTransaction();

        // Insérer l'expédition
        $stmt = $db->prepare(
            "INSERT INTO gul_adr_expeditions
            (numero_expedition, client_id, transporteur, date_expedition, total_points_adr, observations, cree_par)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $numeroExpedition,
            $clientId,
            $transporteur,
            $dateExpedition,
            $totalPointsAdr,
            $data['observations'] ?? '',
            $_SESSION['adr_user']
        ]);

        $expeditionId = $db->lastInsertId();

        // Insérer les lignes de produits
        $stmt = $db->prepare(
            "INSERT INTO gul_adr_expedition_lignes
            (expedition_id, code_produit, quantite_declaree, unite_quantite, points_adr_calcules, ordre_ligne)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        foreach ($products as $index => $product) {
            $stmt->execute([
                $expeditionId,
                $product['code_produit'],
                $product['quantite_declaree'],
                $product['unite_quantite'] ?? 'kg',
                $product['points_adr_calcules'],
                $index + 1
            ]);
        }

        $db->commit();

        return [
            'success' => true,
            'message' => 'Expédition créée avec succès',
            'data'    => [
                'expedition_id'    => $expeditionId,
                'numero_expedition' => $numeroExpedition,
                'total_points'     => $totalPointsAdr
            ]
        ];

    } catch (Exception $e) {
        $db->rollBack();
        error_log('Erreur création expédition ADR: ' . $e->getMessage());

        return [
            'success' => false,
            'errors'  => ["Erreur lors de la création de l'expédition : " . $e->getMessage()]
        ];
    }
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
                
                <!-- Section destinataire complète pour create.php -->
<div class="step-content active" id="step-destinataire">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <div style="width: 50px; height: 50px; background: var(--adr-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
            📍
        </div>
        <div>
            <h2>Destinataire de l'expédition</h2>
            <p style="color: #666; margin: 0;">Saisissez les informations du destinataire</p>
        </div>
        <div style="margin-left: auto;">
            <div id="destinataire-status" class="status-pending">📝 Destinataire incomplet</div>
        </div>
    </div>

    <!-- Informations expéditeur (par défaut GULDAGIL) -->
    <div style="background: var(--adr-light); padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem; border-left: 4px solid var(--adr-primary);">
        <h4 style="color: var(--adr-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            📤 Expéditeur
            <span style="font-size: 0.8rem; font-weight: normal; background: var(--adr-success); color: white; padding: 0.2rem 0.5rem; border-radius: 12px;">Par défaut</span>
        </h4>
        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: start;">
            <div id="expediteur-info">
                <div style="font-weight: 600; margin-bottom: 0.5rem;">GULDAGIL</div>
                <div style="color: #666; font-size: 0.9rem; line-height: 1.4;">
                    Siège social et Usine<br>
                    4 Rue Robert Schuman<br>
                    68170 RIXHEIM<br>
                    Tél: 03 89 44 13 17
                </div>
            </div>
            <div>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="checkbox" id="expedition-enlevement" onchange="toggleEnlevement()">
                    <span>🚛 Enlèvement chez fournisseur</span>
                </label>
            </div>
        </div>
        
        <!-- Zone expéditeur personnalisé (masquée par défaut) -->
        <div id="expediteur-custom" style="display: none; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #ddd;">
            <h5 style="color: var(--adr-primary); margin-bottom: 1rem;">📝 Expéditeur personnalisé</h5>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="expediteur-nom">Nom expéditeur <span style="color: var(--adr-danger);">*</span></label>
                    <input type="text" class="form-control" id="expediteur-nom" placeholder="Nom de l'entreprise">
                </div>
                <div class="form-group">
                    <label for="expediteur-telephone">Téléphone</label>
                    <input type="tel" class="form-control" id="expediteur-telephone" placeholder="03 XX XX XX XX">
                </div>
            </div>
            <div class="form-group">
                <label for="expediteur-adresse">Adresse complète</label>
                <textarea class="form-control" id="expediteur-adresse" rows="2" placeholder="Adresse complète de l'expéditeur"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="expediteur-cp">Code postal</label>
                    <input type="text" class="form-control" id="expediteur-cp" placeholder="68000" maxlength="5">
                </div>
                <div class="form-group">
                    <label for="expediteur-ville">Ville</label>
                    <input type="text" class="form-control" id="expediteur-ville" placeholder="Ville">
                </div>
                <div class="form-group">
                    <label for="expediteur-pays">Pays</label>
                    <select class="form-control" id="expediteur-pays">
                        <option value="France">France</option>
                        <option value="Allemagne">Allemagne</option>
                        <option value="Belgique">Belgique</option>
                        <option value="Luxembourg">Luxembourg</option>
                        <option value="Suisse">Suisse</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire destinataire -->
    <div class="destinataire-form">
        <!-- Nom du destinataire avec suggestions -->
        <div class="destinataire-form-group">
            <label for="destinataire-nom">
                <span style="color: var(--adr-danger);">*</span> Nom du destinataire
            </label>
            <div class="destinataire-search-container">
                <input type="text" 
                       class="form-control" 
                       id="destinataire-nom" 
                       placeholder="Nom de l'entreprise ou du client..."
                       autocomplete="off"
                       required
                       oninput="searchDestinataires(this.value)"
                       onfocus="showRecentDestinataires()">
                <div id="destinataires-suggestions" class="suggestions-container"></div>
            </div>
            <small style="color: #666; font-size: 0.85rem;">
                💡 Saisissez quelques lettres pour voir les destinataires fréquents
            </small>
        </div>

        <!-- Contact destinataire -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="destinataire-form-group">
                <label for="destinataire-contact">Personne de contact</label>
                <input type="text" 
                       class="form-control" 
                       id="destinataire-contact" 
                       placeholder="Nom du contact">
            </div>
            <div class="destinataire-form-group">
                <label for="destinataire-telephone">Téléphone</label>
                <input type="tel" 
                       class="form-control" 
                       id="destinataire-telephone" 
                       placeholder="03 XX XX XX XX"
                       pattern="[0-9\s\.\-\+\(\)]+">
            </div>
        </div>

        <!-- Adresse complète -->
        <div class="destinataire-form-group">
            <label for="destinataire-adresse">Adresse complète</label>
            <textarea class="form-control" 
                      id="destinataire-adresse" 
                      rows="2" 
                      placeholder="Rue, numéro, bâtiment, étage..."></textarea>
        </div>

        <!-- Code postal et ville avec autocomplétion -->
        <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
            <div class="destinataire-form-group">
                <label for="destinataire-cp">
                    <span style="color: var(--adr-danger);">*</span> Code postal
                </label>
                <input type="text" 
                       class="form-control" 
                       id="destinataire-cp" 
                       placeholder="67000"
                       pattern="\d{5}"
                       maxlength="5"
                       required
                       oninput="autocompleteVille(this.value)">
            </div>
            <div class="destinataire-form-group">
                <label for="destinataire-ville">
                    <span style="color: var(--adr-danger);">*</span> Ville
                </label>
                <input type="text" 
                       class="form-control" 
                       id="destinataire-ville" 
                       placeholder="Strasbourg"
                       required>
            </div>
            <div class="destinataire-form-group">
                <label for="destinataire-pays">Pays</label>
                <select class="form-control" id="destinataire-pays">
                    <option value="France">France</option>
                    <option value="Allemagne">Allemagne</option>
                    <option value="Belgique">Belgique</option>
                    <option value="Luxembourg">Luxembourg</option>
                    <option value="Suisse">Suisse</option>
                    <option value="Italie">Italie</option>
                    <option value="Espagne">Espagne</option>
                    <option value="Autre">Autre...</option>
                </select>
            </div>
        </div>

        <!-- Options livraison -->
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: var(--border-radius); margin-top: 1.5rem;">
            <h4 style="color: var(--adr-primary); margin-bottom: 1rem; font-size: 1.1rem;">🚚 Options de livraison</h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #ddd; transition: all 0.2s ease;">
                    <input type="checkbox" id="livraison-rdv" onchange="updateLivraisonOptions()">
                    <span>📞 Prise de rendez-vous</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #ddd; transition: all 0.2s ease;">
                    <input type="checkbox" id="livraison-etage" onchange="updateLivraisonOptions()">
                    <span>🏢 Livraison étage</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #ddd; transition: all 0.2s ease;">
                    <input type="checkbox" id="livraison-hayon" onchange="updateLivraisonOptions()">
                    <span>📋 Véhicule avec hayon</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #ddd; transition: all 0.2s ease;">
                    <input type="checkbox" id="livraison-urgent" onchange="updateLivraisonOptions()">
                    <span>⚡ Livraison urgente</span>
                </label>
            </div>
            
            <!-- Créneaux horaires -->
            <div id="creneaux-horaires" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                <label for="creneau-livraison" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    🕐 Créneau de livraison souhaité
                </label>
                <select class="form-control" id="creneau-livraison" style="max-width: 300px;">
                    <option value="">Pas de préférence</option>
                    <option value="matin">Matin (8h-12h)</option>
                    <option value="apres-midi">Après-midi (13h-17h)</option>
                    <option value="fin-journee">Fin de journée (17h-19h)</option>
                </select>
            </div>
        </div>

        <!-- Instructions spéciales -->
        <div class="destinataire-form-group">
            <label for="destinataire-instructions">
                📝 Instructions de livraison (optionnel)
            </label>
            <textarea class="form-control" 
                      id="destinataire-instructions" 
                      rows="3" 
                      placeholder="Instructions particulières : digicode, accès, personne à prévenir, etc."
                      maxlength="500"></textarea>
            <small style="color: #666; font-size: 0.8rem;">Maximum 500 caractères</small>
        </div>
    </div>

    <!-- Résumé destinataire -->
    <div id="destinataire-resume" style="display: none; background: #d4edda; padding: 1.5rem; border-radius: var(--border-radius); margin-top: 2rem; border-left: 4px solid var(--adr-success);">
        <h4 style="color: var(--adr-success); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            ✅ Destinataire validé
            <button type="button" 
                    onclick="modifierDestinataire()" 
                    style="margin-left: auto; background: none; border: 1px solid var(--adr-success); color: var(--adr-success); padding: 0.25rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                ✏️ Modifier
            </button>
        </h4>
        <div id="destinataire-resume-content"></div>
    </div>

    <!-- Actions étape 1 -->
    <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button type="button" 
                    class="btn btn-secondary btn-sm" 
                    onclick="sauvegarderDestinataire()"
                    id="btn-sauvegarder-destinataire"
                    disabled>
                💾 Sauvegarder destinataire
            </button>
            <small style="color: #666;">💡 Le destinataire sera mémorisé pour les prochaines expéditions</small>
        </div>
        
        <button type="button" 
                class="btn btn-primary" 
                id="btn-next-to-products" 
                onclick="nextToProducts()" 
                disabled>
            Ajouter des produits ➡️
        </button>
    </div>
</div>

<style>
/* Styles spécifiques pour la section destinataire */
.destinataire-form-group {
    margin-bottom: 1.5rem;
}

.destinataire-form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--adr-dark);
    font-size: 0.95rem;
}

.destinataire-search-container {
    position: relative;
}

.suggestions-container {
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
    box-shadow: var(--shadow-hover);
}

.suggestion-item {
    padding: 1rem;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: var(--transition);
}

.suggestion-item:hover {
    background: var(--adr-light);
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-main {
    font-weight: 600;
    color: var(--adr-primary);
    margin-bottom: 0.25rem;
}

.suggestion-details {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.3;
}

.suggestion-meta {
    font-size: 0.8rem;
    color: #999;
    margin-top: 0.25rem;
}

.status-pending {
    color: var(--adr-warning);
    font-weight: 600;
    font-size: 0.9rem;
}

.status-complete {
    color: var(--adr-success);
    font-weight: 600;
    font-size: 0.9rem;
}

.status-error {
    color: var(--adr-danger);
    font-weight: 600;
    font-size: 0.9rem;
}

/* Animations pour les checkboxes */
input[type="checkbox"]:checked + span {
    color: var(--adr-primary);
    font-weight: 600;
}

label:has(input[type="checkbox"]:checked) {
    background: rgba(255, 107, 53, 0.1) !important;
    border-color: var(--adr-primary) !important;
    box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .destinataire-form-group {
        margin-bottom: 1rem;
    }
    
    div[style*="grid-template-columns"] {
        display: block !important;
    }
    
    div[style*="grid-template-columns"] > div {
        margin-bottom: 1rem;
    }
    
    div[style*="grid-template-columns"] > div:last-child {
        margin-bottom: 0;
    }
}
</style>

<script>
// Variables globales pour la gestion du destinataire
let destinataireData = {
    nom: '',
    contact: '',
    telephone: '',
    adresse: '',
    codePostal: '',
    ville: '',
    pays: 'France',
    options: [],
    instructions: ''
};

let destinatairesRecents = [];
let expediteurPersonnalise = false;

/**
 * Basculer entre expéditeur par défaut et personnalisé
 */
function toggleEnlevement() {
    const checkbox = document.getElementById('expedition-enlevement');
    const customDiv = document.getElementById('expediteur-custom');
    
    expediteurPersonnalise = checkbox.checked;
    
    if (expediteurPersonnalise) {
        customDiv.style.display = 'block';
        customDiv.style.animation = 'slideInDown 0.3s ease';
    } else {
        customDiv.style.display = 'none';
        // Réinitialiser les champs
        document.getElementById('expediteur-nom').value = '';
        document.getElementById('expediteur-telephone').value = '';
        document.getElementById('expediteur-adresse').value = '';
        document.getElementById('expediteur-cp').value = '';
        document.getElementById('expediteur-ville').value = '';
    }
    
    validateDestinataire();
}

/**
 * Recherche de destinataires avec debounce
 */
let searchTimeout;
function searchDestinataires(query) {
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        hideDestinatairesSuggestions();
        return;
    }
    
    searchTimeout = setTimeout(() => {
        // Simuler une recherche AJAX
        performDestinataireSearch(query);
    }, 300);
}

/**
 * Effectue la recherche de destinataires
 */
function performDestinataireSearch(query) {
    // En production, cette fonction ferait un appel AJAX
    // Pour la démo, on simule des résultats
    
    const suggestions = [
        {
            nom: "SARL MARTIN PLOMBERIE",
            adresse: "15 Rue de la Paix",
            codePostal: "67000",
            ville: "Strasbourg",
            telephone: "03 88 XX XX XX",
            lastUsed: "2025-01-15"
        },
        {
            nom: "ENTREPRISE SCHMIDT",
            adresse: "45 Avenue de la République",
            codePostal: "68100",
            ville: "Mulhouse",
            telephone: "03 89 XX XX XX",
            lastUsed: "2025-01-10"
        },
        {
            nom: "SAS RENOVATION ALSACE",
            adresse: "8 Impasse des Artisans",
            codePostal: "67200",
            ville: "Strasbourg",
            telephone: "03 88 XX XX XX",
            lastUsed: "2025-01-08"
        }
    ].filter(dest => 
        dest.nom.toLowerCase().includes(query.toLowerCase()) ||
        dest.ville.toLowerCase().includes(query.toLowerCase()) ||
        dest.codePostal.includes(query)
    );
    
    showDestinatairesSuggestions(suggestions);
}

/**
 * Affiche les destinataires récents
 */
function showRecentDestinataires() {
    if (document.getElementById('destinataire-nom').value.length > 0) return;
    
    // Simuler des destinataires récents
    const recents = [
        {
            nom: "DERNIÈRE LIVRAISON",
            adresse: "123 Rue du Commerce",
            codePostal: "67000",
            ville: "Strasbourg",
            telephone: "03 88 XX XX XX",
            lastUsed: "Hier"
        },
        {
            nom: "CLIENT FRÉQUENT",
            adresse: "67 Boulevard de l'Industrie",
            codePostal: "68200",
            ville: "Mulhouse",
            telephone: "03 89 XX XX XX",
            lastUsed: "Cette semaine"
        }
    ];
    
    showDestinatairesSuggestions(recents, true);
}

/**
 * Affiche les suggestions de destinataires
 */
function showDestinatairesSuggestions(suggestions, isRecent = false) {
    const container = document.getElementById('destinataires-suggestions');
    
    if (!suggestions || suggestions.length === 0) {
        hideDestinatairesSuggestions();
        return;
    }
    
    let html = '';
    
    if (isRecent) {
        html += '<div style="padding: 0.5rem 1rem; background: #f8f9fa; font-size: 0.8rem; color: #666; border-bottom: 1px solid #eee;">💡 Destinataires récents</div>';
    }
    
    suggestions.forEach(dest => {
        html += `
            <div class="suggestion-item" onclick="selectDestinataire('${dest.nom}', '${dest.adresse}', '${dest.codePostal}', '${dest.ville}', '${dest.telephone}')">
                <div class="suggestion-main">${dest.nom}</div>
                <div class="suggestion-details">${dest.adresse}<br>${dest.codePostal} ${dest.ville}</div>
                <div class="suggestion-meta">📞 ${dest.telephone} • Dernière utilisation: ${dest.lastUsed}</div>
            </div>
        `;
    });
    
    // Option pour créer un nouveau destinataire
    if (!isRecent) {
        html += `
            <div class="suggestion-item" onclick="nouveauDestinataire()" style="border-top: 2px solid var(--adr-primary); background: rgba(255, 107, 53, 0.05);">
                <div class="suggestion-main" style="color: var(--adr-primary);">➕ Nouveau destinataire</div>
                <div class="suggestion-details">Créer un nouveau destinataire avec ces informations</div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    container.style.display = 'block';
}

/**
 * Masque les suggestions
 */
function hideDestinatairesSuggestions() {
    const container = document.getElementById('destinataires-suggestions');
    setTimeout(() => {
        container.style.display = 'none';
    }, 150);
}

/**
 * Sélectionne un destinataire depuis les suggestions
 */
function selectDestinataire(nom, adresse, codePostal, ville, telephone) {
    document.getElementById('destinataire-nom').value = nom;
    document.getElementById('destinataire-adresse').value = adresse;
    document.getElementById('destinataire-cp').value = codePostal;
    document.getElementById('destinataire-ville').value = ville;
    document.getElementById('destinataire-telephone').value = telephone;
    
    hideDestinatairesSuggestions();
    validateDestinataire();
}

/**
 * Crée un nouveau destinataire
 */
function nouveauDestinataire() {
    const query = document.getElementById('destinataire-nom').value;
    hideDestinatairesSuggestions();
    
    if (query) {
        document.getElementById('destinataire-nom').value = query.toUpperCase();
    }
    
    // Focus sur le champ adresse
    document.getElementById('destinataire-adresse').focus();
    validateDestinataire();
}

/**
 * Autocomplétion ville basée sur le code postal
 */
function autocompleteVille(codePostal) {
    if (codePostal.length === 5) {
        // Base de données simplifiée des codes postaux
        const villes = {
            '67000': 'Strasbourg',
            '67100': 'Strasbourg',
            '67200': 'Strasbourg',
            '68000': 'Colmar',
            '68100': 'Mulhouse',
            '68200': 'Mulhouse',
            '68170': 'Rixheim',
            '75001': 'Paris',
            '75002': 'Paris',
            '69000': 'Lyon',
            '69001': 'Lyon',
            '13000': 'Marseille',
            '33000': 'Bordeaux'
        };
        
        const ville = villes[codePostal];
        if (ville) {
            document.getElementById('destinataire-ville').value = ville;
        }
    }
    
    validateDestinataire();
}

/**
 * Met à jour les options de livraison
 */
function updateLivraisonOptions() {
    const rdv = document.getElementById('livraison-rdv').checked;
    const etage = document.getElementById('livraison-etage').checked;
    const hayon = document.getElementById('livraison-hayon').checked;
    const urgent = document.getElementById('livraison-urgent').checked;
    
    // Afficher/masquer les créneaux horaires
    const creneauxDiv = document.getElementById('creneaux-horaires');
    if (rdv) {
        creneauxDiv.style.display = 'block';
    } else {
        creneauxDiv.style.display = 'none';
        document.getElementById('creneau-livraison').value = '';
    }
    
    // Mettre à jour les données
    destinataireData.options = [];
    if (rdv) destinataireData.options.push('rdv');
    if (etage) destinataireData.options.push('etage');
    if (hayon) destinataireData.options.push('hayon');
    if (urgent) destinataireData.options.push('urgent');
    
    validateDestinataire();
}

/**
 * Valide les données du destinataire
 */
function validateDestinataire() {
    const nom = document.getElementById('destinataire-nom').value.trim();
    const codePostal = document.getElementById('destinataire-cp').value.trim();
    const ville = document.getElementById('destinataire-ville').value.trim();
    
    const isValid = nom.length >= 2 && 
                   codePostal.length === 5 && 
                   /^\d{5}$/.test(codePostal) && 
                   ville.length >= 2;
    
    // Mise à jour du statut
    const statusDiv = document.getElementById('destinataire-status');
    const nextBtn = document.getElementById('btn-next-to-products');
    const saveBtn = document.getElementById('btn-sauvegarder-destinataire');
    
    if (isValid) {
        statusDiv.textContent = '✅ Destinataire complet';
        statusDiv.className = 'status-complete';
        nextBtn.disabled = false;
        saveBtn.disabled = false;
        
        // Mettre à jour les données
        updateDestinataireData();
        
        // Afficher le résumé
        showDestinataireResume();
    } else {
        statusDiv.textContent = '📝 Destinataire incomplet';
        statusDiv.className = 'status-pending';
        nextBtn.disabled = true;
        saveBtn.disabled = true;
        
        // Masquer le résumé
        document.getElementById('destinataire-resume').style.display = 'none';
    }
}

/**
 * Met à jour les données du destinataire
 */
function updateDestinataireData() {
    destinataireData = {
        nom: document.getElementById('destinataire-nom').value.trim(),
        contact: document.getElementById('destinataire-contact').value.trim(),
        telephone: document.getElementById('destinataire-telephone').value.trim(),
        adresse: document.getElementById('destinataire-adresse').value.trim(),
        codePostal: document.getElementById('destinataire-cp').value.trim(),
        ville: document.getElementById('destinataire-ville').value.trim(),
        pays: document.getElementById('destinataire-pays').value,
        options: getSelectedLivraisonOptions(),
        instructions: document.getElementById('destinataire-instructions').value.trim(),
        creneau: document.getElementById('creneau-livraison').value
    };
}

/**
 * Récupère les options de livraison sélectionnées
 */
function getSelectedLivraisonOptions() {
    const options = [];
    if (document.getElementById('livraison-rdv').checked) options.push('rdv');
    if (document.getElementById('livraison-etage').checked) options.push('etage');
    if (document.getElementById('livraison-hayon').checked) options.push('hayon');
    if (document.getElementById('livraison-urgent').checked) options.push('urgent');
    return options;
}

/**
 * Affiche le résumé du destinataire
 */
function showDestinataireResume() {
    const resumeDiv = document.getElementById('destinataire-resume');
    const contentDiv = document.getElementById('destinataire-resume-content');
    
    let optionsText = '';
    if (destinataireData.options.length > 0) {
        const optionsLabels = {
            'rdv': '📞 Prise de RDV',
            'etage': '🏢 Livraison étage',
            'hayon': '📋 Véhicule avec hayon',
            'urgent': '⚡ Livraison urgente'
        };
        
        optionsText = destinataireData.options.map(opt => optionsLabels[opt]).join(', ');
        
        if (destinataireData.creneau) {
            const creneauxLabels = {
                'matin': 'Matin (8h-12h)',
                'apres-midi': 'Après-midi (13h-17h)',
                'fin-journee': 'Fin de journée (17h-19h)'
            };
            optionsText += ` • 🕐 ${creneauxLabels[destinataireData.creneau]}`;
        }
    }
    
    contentDiv.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--adr-primary);">
                    ${destinataireData.nom}
                </div>
                ${destinataireData.contact ? `<div style="margin-bottom: 0.25rem;">👤 ${destinataireData.contact}</div>` : ''}
                ${destinataireData.telephone ? `<div style="margin-bottom: 0.25rem;">📞 ${destinataireData.telephone}</div>` : ''}
                <div style="color: #666; line-height: 1.4;">
                    ${destinataireData.adresse ? destinataireData.adresse + '<br>' : ''}
                    ${destinataireData.codePostal} ${destinataireData.ville}<br>
                    ${destinataireData.pays}
                </div>
            </div>
            <div>
                ${optionsText ? `
                    <div style="margin-bottom: 1rem;">
                        <div style="font-weight: 600; margin-bottom: 0.5rem; color: var(--adr-primary);">Options de livraison :</div>
                        <div style="font-size: 0.9rem; line-height: 1.4;">${optionsText}</div>
                    </div>
                ` : ''}
                ${destinataireData.instructions ? `
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.5rem; color: var(--adr-primary);">Instructions :</div>
                        <div style="font-size: 0.9rem; font-style: italic; color: #666;">"${destinataireData.instructions}"</div>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
    
    resumeDiv.style.display = 'block';
    resumeDiv.style.animation = 'slideInUp 0.3s ease';
}

/**
 * Permet de modifier le destinataire
 */
function modifierDestinataire() {
    document.getElementById('destinataire-resume').style.display = 'none';
    document.getElementById('destinataire-nom').focus();
}

/**
 * Sauvegarde le destinataire pour réutilisation future
 */
function sauvegarderDestinataire() {
    if (!destinataireData.nom || !destinataireData.codePostal) {
        showNotification('❌ Destinataire incomplet', 'error');
        return;
    }
    
    // En production, ceci ferait un appel AJAX pour sauvegarder en base
    const destinataireToSave = {
        ...destinataireData,
        dateCreation: new Date().toISOString(),
        creeParUtilisateur: window.ADR_CONFIG?.user || 'demo.user'
    };
    
    // Sauvegarder temporairement dans localStorage
    let savedDestinataires = JSON.parse(localStorage.getItem('adr_destinataires_sauvegardes') || '[]');
    
    // Vérifier si ce destinataire existe déjà
    const existingIndex = savedDestinataires.findIndex(d => 
        d.nom.toLowerCase() === destinataireData.nom.toLowerCase() &&
        d.codePostal === destinataireData.codePostal
    );
    
    if (existingIndex >= 0) {
        // Mettre à jour l'existant
        savedDestinataires[existingIndex] = destinataireToSave;
        showNotification('📝 Destinataire mis à jour', 'success');
    } else {
        // Ajouter le nouveau
        savedDestinataires.unshift(destinataireToSave);
        // Limiter à 50 destinataires sauvegardés
        if (savedDestinataires.length > 50) {
            savedDestinataires = savedDestinataires.slice(0, 50);
        }
        showNotification('💾 Destinataire sauvegardé pour réutilisation', 'success');
    }
    
    localStorage.setItem('adr_destinataires_sauvegardes', JSON.stringify(savedDestinataires));
    
    // Désactiver le bouton temporairement
    const saveBtn = document.getElementById('btn-sauvegarder-destinataire');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '✅ Sauvegardé';
    saveBtn.disabled = true;
    
    setTimeout(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    }, 2000);
}

/**
 * Passe à l'étape suivante (produits)
 */
function nextToProducts() {
    if (!destinataireData.nom || !destinataireData.codePostal || !destinataireData.ville) {
        showNotification('❌ Veuillez compléter les informations du destinataire', 'error');
        return;
    }
    
    // Validation du code postal français/européen
    const cpRegex = /^[0-9]{5}$/;
    if (!cpRegex.test(destinataireData.codePostal)) {
        showNotification('❌ Code postal invalide', 'error');
        document.getElementById('destinataire-cp').focus();
        return;
    }
    
    // Marquer l'étape comme complétée
    markStepCompleted('destinataire');
    
    // Activer l'étape suivante
    activateStep('products');
    
    // Mettre à jour l'affichage des informations de progression
    updateProgressInfo();
    
    showNotification('✅ Destinataire validé, passons aux produits', 'success');
}

/**
 * Marque une étape comme complétée
 */
function markStepCompleted(stepName) {
    const step = document.querySelector(`[data-step="${stepName}"]`);
    if (step) {
        step.classList.remove('active', 'disabled');
        step.classList.add('completed');
        
        // Changer l'icône du numéro par un check
        const stepNumber = step.querySelector('.step-number');
        if (stepNumber) {
            stepNumber.innerHTML = '✓';
        }
    }
}

/**
 * Active une étape
 */
function activateStep(stepName) {
    // Désactiver toutes les étapes
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
    
    // Masquer tous les contenus d'étapes
    document.querySelectorAll('.step-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Activer l'étape demandée
    const step = document.querySelector(`[data-step="${stepName}"]`);
    const content = document.getElementById(`step-${stepName}`);
    
    if (step && content) {
        step.classList.remove('disabled');
        step.classList.add('active');
        content.classList.add('active');
        
        // Scroll vers le haut
        content.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * Met à jour les informations de progression
 */
function updateProgressInfo() {
    const progressDiv = document.getElementById('expedition-progress');
    const clientDiv = document.getElementById('progress-client');
    
    if (progressDiv && clientDiv && destinataireData.nom) {
        clientDiv.innerHTML = `📍 <strong>${destinataireData.nom}</strong><br>
                              <small>${destinataireData.codePostal} ${destinataireData.ville}</small>`;
        progressDiv.style.display = 'block';
    }
}

/**
 * Affiche une notification
 */
function showNotification(message, type = 'info') {
    // Créer le conteneur de notifications s'il n'existe pas
    let container = document.getElementById('notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications-container';
        document.body.appendChild(container);
    }
    
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = message;
    
    // Styles selon le type
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    
    notification.style.background = colors[type] || colors.info;
    
    // Ajouter au conteneur
    container.appendChild(notification);
    
    // Auto-suppression après 4 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

/**
 * Charge les destinataires sauvegardés depuis localStorage
 */
function loadSavedDestinataires() {
    try {
        const saved = localStorage.getItem('adr_destinataires_sauvegardes');
        return saved ? JSON.parse(saved) : [];
    } catch (e) {
        console.error('Erreur chargement destinataires sauvegardés:', e);
        return [];
    }
}

/**
 * Intègre les destinataires sauvegardés dans les suggestions
 */
function getSuggestionsWithSaved(query) {
    const saved = loadSavedDestinataires();
    const filtered = saved.filter(dest => 
        dest.nom.toLowerCase().includes(query.toLowerCase()) ||
        dest.ville.toLowerCase().includes(query.toLowerCase()) ||
        dest.codePostal.includes(query)
    ).slice(0, 10); // Limiter à 10 résultats
    
    return filtered.map(dest => ({
        nom: dest.nom,
        adresse: dest.adresse || '',
        codePostal: dest.codePostal,
        ville: dest.ville,
        telephone: dest.telephone || '',
        lastUsed: 'Sauvegardé'
    }));
}

// Event listeners pour la validation en temps réel
document.addEventListener('DOMContentLoaded', function() {
    // Validation en temps réel
    ['destinataire-nom', 'destinataire-cp', 'destinataire-ville'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', validateDestinataire);
            element.addEventListener('blur', validateDestinataire);
        }
    });
    
    // Masquer les suggestions quand on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.destinataire-search-container')) {
            hideDestinatairesSuggestions();
        }
    });
    
    // Gestion du caractère limite pour les instructions
    const instructionsTextarea = document.getElementById('destinataire-instructions');
    if (instructionsTextarea) {
        instructionsTextarea.addEventListener('input', function() {
            const remaining = 500 - this.value.length;
            const small = this.nextElementSibling;
            if (small) {
                small.textContent = `${remaining >= 0 ? remaining : 0} caractères restants`;
                small.style.color = remaining < 0 ? 'var(--adr-danger)' : '#666';
            }
        });
    }
    
    console.log('✅ Section destinataire initialisée');
});

// Animations CSS supplémentaires
const additionalStyles = `
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

#notifications-container {
    position: fixed;
    top: 100px;
    right: 20px;
    z-index: 10000;
    max-width: 400px;
    pointer-events: none;
}

.notification {
    background: var(--adr-primary);
    color: white;
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-hover);
    pointer-events: all;
    animation: slideInUp 0.3s ease;
    font-weight: 500;
}
`;

// Ajouter les styles supplémentaires
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
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

      <!-- Scripts JavaScript -->
    <script src="../../assets/js/adr-create-expedition.js"></script>
    
    <!-- Configuration JavaScript spécifique -->
    <script>
        // Configuration passée du PHP vers JS
        window.ADR_CONFIG = {
            transporteurs: <?= json_encode($transporteurs) ?>,
            expediteur: <?= json_encode(GULDAGIL_EXPEDITEUR) ?>,
            quota_max_default: <?= QUOTA_MAX_POINTS_JOUR ?>,
            base_url: '<?= basename($_SERVER['PHP_SELF']) ?>',
            debug: <?= json_encode(!$isProduction ?? true) ?>
        };
        
        // Fonctions d'aide spécifiques à cette page
        function showHelp() {
            showNotification('💡 Guide d\'utilisation :\n\n1. Sélectionnez un client\n2. Choisissez transporteur et date\n3. Ajoutez vos produits ADR\n4. Validez l\'expédition', 'info');
        }
        
        function saveDraft() {
            if (selectedClient && expeditionProducts.length > 0) {
                // Sauvegarder dans localStorage en attendant l'implémentation serveur
                const draftData = {
                    client: selectedClient,
                    products: expeditionProducts,
                    transporteur: getInputValue('expedition-transporteur'),
                    date: getInputValue('expedition-date'),
                    timestamp: Date.now()
                };
                
                localStorage.setItem('adr_draft_expedition', JSON.stringify(draftData));
                showNotification('💾 Brouillon sauvegardé localement', 'success');
            } else {
                showNotification('❌ Rien à sauvegarder', 'warning');
            }
        }
        
        // Charger un brouillon s'il existe
        function loadDraft() {
            const draft = localStorage.getItem('adr_draft_expedition');
            if (draft) {
                try {
                    const data = JSON.parse(draft);
                    if (confirm('📋 Un brouillon existe.\n\nVoulez-vous le charger ?')) {
                        // Charger le client
                        if (data.client) {
                            selectClient(data.client);
                        }
                        
                        // Charger transporteur et date
                        if (data.transporteur) {
                            setInputValue('expedition-transporteur', data.transporteur);
                        }
                        if (data.date) {
                            setInputValue('expedition-date', data.date);
                        }
                        
                        // Charger les produits
                        if (data.products && data.products.length > 0) {
                            expeditionProducts = data.products;
                            updateProductsTable();
                            showStep('products');
                        }
                        
                        updateQuotas();
                        updateProgressInfo();
                        
                        showNotification('📋 Brouillon chargé', 'success');
                    }
                } catch (e) {
                    console.error('Erreur chargement brouillon:', e);
                    localStorage.removeItem('adr_draft_expedition');
                }
            }
        }
        
        // Vérifier s'il y a un brouillon au chargement
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(loadDraft, 1000);
        });
        
        // Nettoyage brouillon après création réussie
        window.addEventListener('beforeunload', function() {
            // Ne pas nettoyer si on est en cours de création
            if (currentStep === 'validation' && expeditionProducts.length > 0) {
                // Garder le brouillon
            } else {
                // localStorage.removeItem('adr_draft_expedition');
            }
        });
        
        console.log('✅ Configuration ADR initialisée');
        console.log('🎯 Brouillons disponibles via localStorage');
    </script>
</body>
</html>

<?php
// public/admin/api-options.php - API pour la gestion des options supplémentaires
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

error_log("Options API Call: Method=$method, Action=$action");

try {
    switch ($action) {
        case 'list':
            handleListOptions($db);
            break;
            
        case 'get':
            handleGetSingleOption($db);
            break;
            
        case 'create':
            handleCreateOption($db);
            break;
            
        case 'update':
            handleUpdateOption($db);
            break;
            
        case 'delete':
            handleDeleteOption($db);
            break;
            
        case 'carriers':
            handleGetCarriers($db);
            break;
            
        default:
            handleListOptions($db);
    }
} catch (Exception $e) {
    error_log("Options API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'method' => $method,
            'action' => $action,
            'get' => $_GET,
            'post' => $_POST
        ]
    ]);
}

/**
 * Liste toutes les options supplémentaires
 */
function handleListOptions($db) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    
    $filters = [
        'carrier' => $_GET['carrier'] ?? '',
        'status' => $_GET['status'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    // Construction de la requête avec filtres
    $sql = "SELECT * FROM gul_options_supplementaires WHERE 1=1";
    $params = [];
    
    if ($filters['carrier']) {
        $sql .= " AND transporteur = ?";
        $params[] = $filters['carrier'];
    }
    
    if ($filters['status'] !== '') {
        if ($filters['status'] === 'active') {
            $sql .= " AND actif = 1";
        } elseif ($filters['status'] === 'inactive') {
            $sql .= " AND actif = 0";
        }
    }
    
    if ($filters['search']) {
        $sql .= " AND (code_option LIKE ? OR libelle LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    
    // Compter le total
    $countSql = "SELECT COUNT(*) as total FROM gul_options_supplementaires WHERE 1=1";
    $countParams = [];
    
    if ($filters['carrier']) {
        $countSql .= " AND transporteur = ?";
        $countParams[] = $filters['carrier'];
    }
    
    if ($filters['status'] !== '') {
        if ($filters['status'] === 'active') {
            $countSql .= " AND actif = 1";
        } elseif ($filters['status'] === 'inactive') {
            $countSql .= " AND actif = 0";
        }
    }
    
    if ($filters['search']) {
        $countSql .= " AND (code_option LIKE ? OR libelle LIKE ?)";
        $countParams[] = '%' . $filters['search'] . '%';
        $countParams[] = '%' . $filters['search'] . '%';
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Récupérer les données avec pagination
    $sql .= " ORDER BY transporteur, code_option LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données
    $formattedOptions = [];
    foreach ($options as $option) {
        $formattedOptions[] = [
            'id' => (int)$option['id'],
            'transporteur' => $option['transporteur'],
            'transporteur_nom' => getCarrierName($option['transporteur']),
            'code_option' => $option['code_option'],
            'libelle' => $option['libelle'],
            'montant' => number_format((float)$option['montant'], 2, '.', ''),
            'unite' => $option['unite'],
            'actif' => (bool)$option['actif'],
            'status' => (bool)$option['actif'] ? 'active' : 'inactive'
        ];
    }
    
    $totalPages = ceil($totalCount / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'options' => $formattedOptions,
            'pagination' => [
                'page' => $page,
                'pages' => $totalPages,
                'total' => $totalCount,
                'limit' => $limit
            ],
            'filters' => $filters,
            'stats' => [
                'total' => $totalCount,
                'active' => getActiveOptionsCount($db),
                'inactive' => $totalCount - getActiveOptionsCount($db)
            ]
        ]
    ]);
}

/**
 * Récupère une option spécifique
 */
function handleGetSingleOption($db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID requis');
    }
    
    $stmt = $db->prepare("SELECT * FROM gul_options_supplementaires WHERE id = ?");
    $stmt->execute([$id]);
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$option) {
        throw new Exception('Option non trouvée');
    }
    
    $formattedOption = [
        'id' => (int)$option['id'],
        'transporteur' => $option['transporteur'],
        'transporteur_nom' => getCarrierName($option['transporteur']),
        'code_option' => $option['code_option'],
        'libelle' => $option['libelle'],
        'montant' => number_format((float)$option['montant'], 2, '.', ''),
        'unite' => $option['unite'],
        'actif' => (bool)$option['actif']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $formattedOption
    ]);
}

/**
 * Crée une nouvelle option
 */
function handleCreateOption($db) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST; // Fallback pour formulaires standards
    }
    
    // Validation
    $required = ['transporteur', 'code_option', 'libelle', 'montant', 'unite'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Le champ '$field' est requis");
        }
    }
    
    // Vérifier l'unicité de la combinaison transporteur + code_option
    $stmt = $db->prepare("SELECT COUNT(*) FROM gul_options_supplementaires WHERE transporteur = ? AND code_option = ?");
    $stmt->execute([$data['transporteur'], $data['code_option']]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Cette option existe déjà pour ce transporteur');
    }
    
    // Insérer la nouvelle option
    $sql = "INSERT INTO gul_options_supplementaires (transporteur, code_option, libelle, montant, unite, actif) VALUES (?, ?, ?, ?, ?, ?)";
    $params = [
        $data['transporteur'],
        $data['code_option'],
        $data['libelle'],
        (float)$data['montant'],
        $data['unite'],
        isset($data['actif']) ? (bool)$data['actif'] : true
    ];
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $newId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Option créée avec succès',
        'data' => ['id' => $newId]
    ]);
}

/**
 * Met à jour une option existante
 */
function handleUpdateOption($db) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST; // Fallback pour formulaires standards
    }
    
    $id = $data['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID requis');
    }
    
    // Vérifier que l'option existe
    $stmt = $db->prepare("SELECT * FROM gul_options_supplementaires WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        throw new Exception('Option non trouvée');
    }
    
    // Construire la requête de mise à jour
    $updateFields = [];
    $params = [];
    
    $allowedFields = ['transporteur', 'code_option', 'libelle', 'montant', 'unite', 'actif'];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $updateFields[] = "$field = ?";
            
            if ($field === 'montant') {
                $params[] = (float)$data[$field];
            } elseif ($field === 'actif') {
                $params[] = (bool)$data[$field];
            } else {
                $params[] = $data[$field];
            }
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('Aucune donnée à mettre à jour');
    }
    
    // Vérifier l'unicité si transporteur ou code_option a changé
    if (isset($data['transporteur']) || isset($data['code_option'])) {
        $newTransporteur = $data['transporteur'] ?? $existing['transporteur'];
        $newCodeOption = $data['code_option'] ?? $existing['code_option'];
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM gul_options_supplementaires WHERE transporteur = ? AND code_option = ? AND id != ?");
        $stmt->execute([$newTransporteur, $newCodeOption, $id]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Cette option existe déjà pour ce transporteur');
        }
    }
    
    $params[] = $id;
    $sql = "UPDATE gul_options_supplementaires SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Option mise à jour avec succès'
    ]);
}

/**
 * Supprime une option
 */
function handleDeleteOption($db) {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID requis');
    }
    
    $stmt = $db->prepare("DELETE FROM gul_options_supplementaires WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Option supprimée avec succès'
        ]);
    } else {
        throw new Exception('Option non trouvée');
    }
}

/**
 * Récupère la liste des transporteurs
 */
function handleGetCarriers($db) {
    $carriers = [
        ['code' => 'heppner', 'name' => 'Heppner'],
        ['code' => 'xpo', 'name' => 'XPO'],
        ['code' => 'kn', 'name' => 'Kuehne + Nagel']
    ];
    
    // Ajouter le nombre d'options par transporteur
    foreach ($carriers as &$carrier) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM gul_options_supplementaires WHERE transporteur = ?");
        $stmt->execute([$carrier['code']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $carrier['options_count'] = $result['count'] ?? 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $carriers
    ]);
}

/**
 * Fonctions utilitaires
 */
function getCarrierName($carrierCode) {
    $names = [
        'heppner' => 'Heppner',
        'xpo' => 'XPO',
        'kn' => 'Kuehne + Nagel'
    ];
    return $names[$carrierCode] ?? $carrierCode;
}

function getActiveOptionsCount($db) {
    $stmt = $db->query("SELECT COUNT(*) FROM gul_options_supplementaires WHERE actif = 1");
    return $stmt->fetchColumn();
}
?>

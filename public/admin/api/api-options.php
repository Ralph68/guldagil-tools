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

error_log("API Options Call: Method=$method, Action=$action");

try {
    switch ($action) {
        case 'list':
            handleListOptions($db);
            break;
            
        case 'create':
            handleCreateOption($db);
            break;
            
        case 'get':
            handleGetSingleOption($db);
            break;
            
        case 'delete':
            handleDeleteOption($db);
            break;
            
        case 'toggle':
            handleToggleOption($db);
            break;
            
        default:
            if ($method === 'PUT') {
                handleUpdateOption($db);
            } else {
                handleListOptions($db);
            }
    }
} catch (Exception $e) {
    error_log("API Options Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Liste les options supplémentaires
 */
function handleListOptions($db) {
    $carrier = $_GET['carrier'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $sql = "SELECT * FROM gul_options_supplementaires WHERE 1=1";
    $params = [];
    
    if ($carrier) {
        $sql .= " AND transporteur = ?";
        $params[] = $carrier;
    }
    
    if ($status === 'active') {
        $sql .= " AND actif = 1";
    } elseif ($status === 'inactive') {
        $sql .= " AND actif = 0";
    }
    
    $sql .= " ORDER BY transporteur, code_option";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les options
    $formattedOptions = [];
    foreach ($options as $option) {
        $formattedOptions[] = [
            'id' => (int)$option['id'],
            'transporteur' => $option['transporteur'],
            'transporteur_name' => getCarrierName($option['transporteur']),
            'code_option' => $option['code_option'],
            'libelle' => $option['libelle'],
            'montant' => (float)$option['montant'],
            'unite' => $option['unite'],
            'actif' => (bool)$option['actif']
        ];
    }
    
    // Calculer les statistiques
    $stats = calculateOptionsStats($db, $carrier);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'options' => $formattedOptions,
            'stats' => $stats
        ]
    ]);
}

/**
 * Crée une nouvelle option
 */
function handleCreateOption($db) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Données JSON invalides');
    }
    
    $required = ['transporteur', 'code_option', 'libelle', 'montant', 'unite'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new Exception("Le champ '$field' est requis");
        }
    }
    
    // Vérifier l'unicité
    $stmt = $db->prepare("SELECT id FROM gul_options_supplementaires WHERE transporteur = ? AND code_option = ?");
    $stmt->execute([$data['transporteur'], $data['code_option']]);
    if ($stmt->fetch()) {
        throw new Exception("Une option avec ce code existe déjà pour ce transporteur");
    }
    
    $sql = "INSERT INTO gul_options_supplementaires (transporteur, code_option, libelle, montant, unite, actif) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $data['transporteur'],
        $data['code_option'],
        $data['libelle'],
        (float)$data['montant'],
        $data['unite'],
        isset($data['actif']) ? (int)$data['actif'] : 1
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Option créée avec succès',
        'id' => $db->lastInsertId()
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
        'transporteur_name' => getCarrierName($option['transporteur']),
        'code_option' => $option['code_option'],
        'libelle' => $option['libelle'],
        'montant' => (float)$option['montant'],
        'unite' => $option['unite'],
        'actif' => (bool)$option['actif']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $formattedOption
    ]);
}

/**
 * Met à jour une option
 */
function handleUpdateOption($db) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Données JSON invalides');
    }
    
    $id = $data['id'] ?? null;
    if (!$id) {
        throw new Exception('ID requis');
    }
    
    $updateFields = [];
    $params = [];
    
    $allowedFields = ['libelle', 'montant', 'unite', 'actif'];
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $updateFields[] = "$field = ?";
            if ($field === 'montant') {
                $params[] = (float)$data[$field];
            } elseif ($field === 'actif') {
                $params[] = (int)$data[$field];
            } else {
                $params[] = $data[$field];
            }
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('Aucune donnée à mettre à jour');
    }
    
    $params[] = $id;
    $sql = "UPDATE gul_options_supplementaires SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Option mise à jour avec succès'
        ]);
    } else {
        throw new Exception('Aucune option trouvée avec cet ID');
    }
}

/**
 * Supprime une option
 */
function handleDeleteOption($db) {
    $id = (int)($_GET['id'] ?? 0);
    
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
        throw new Exception('Aucune option trouvée avec cet ID');
    }
}

/**
 * Active/désactive une option
 */
function handleToggleOption($db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID requis');
    }
    
    // Récupérer l'état actuel
    $stmt = $db->prepare("SELECT actif FROM gul_options_supplementaires WHERE id = ?");
    $stmt->execute([$id]);
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$option) {
        throw new Exception('Option non trouvée');
    }
    
    $newStatus = $option['actif'] ? 0 : 1;
    
    $stmt = $db->prepare("UPDATE gul_options_supplementaires SET actif = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    
    $statusText = $newStatus ? 'activée' : 'désactivée';
    
    echo json_encode([
        'success' => true,
        'message' => "Option $statusText avec succès",
        'new_status' => (bool)$newStatus
    ]);
}

/**
 * Calcule les statistiques des options
 */
function calculateOptionsStats($db, $carrierFilter = '') {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN actif = 0 THEN 1 ELSE 0 END) as inactive,
                transporteur
            FROM gul_options_supplementaires";
    
    $params = [];
    if ($carrierFilter) {
        $sql .= " WHERE transporteur = ?";
        $params[] = $carrierFilter;
    }
    
    $sql .= " GROUP BY transporteur";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'total' => 0,
        'active' => 0,
        'inactive' => 0,
        'by_carrier' => []
    ];
    
    foreach ($results as $row) {
        $stats['total'] += $row['total'];
        $stats['active'] += $row['active'];
        $stats['inactive'] += $row['inactive'];
        $stats['by_carrier'][$row['transporteur']] = (int)$row['total'];
    }
    
    return $stats;
}

/**
 * Retourne le nom du transporteur
 */
function getCarrierName($carrierId) {
    $mapping = [
        'heppner' => 'Heppner',
        'xpo' => 'XPO',
        'kn' => 'Kuehne + Nagel'
    ];
    return $mapping[$carrierId] ?? $carrierId;
}
?>

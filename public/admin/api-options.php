<?php
// public/admin/api-options.php - API pour les options supplémentaires
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
    error_log("Options API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'method' => $method,
            'action' => $action,
            'get' => $_GET
        ]
    ]);
}

/**
 * Liste toutes les options
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
    
    $formattedOptions = [];
    foreach ($options as $option) {
        $formattedOptions[] = [
            'id' => $option['id'],
            'transporteur' => $option['transporteur'], // Garder le code (xpo, heppner, kn)
            'transporteur_name' => getCarrierDisplayName($option['transporteur']), // Nom d'affichage
            'code_option' => $option['code_option'],
            'libelle' => $option['libelle'],
            'montant' => formatPrice($option['montant']),
            'unite' => $option['unite'],
            'actif' => (bool)$option['actif'],
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Statistiques
    $stats = [
        'total' => count($formattedOptions),
        'active' => count(array_filter($formattedOptions, fn($o) => $o['actif'])),
        'inactive' => count(array_filter($formattedOptions, fn($o) => !$o['actif'])),
        'by_carrier' => []
    ];
    
    foreach (['heppner', 'xpo', 'kn'] as $c) {
        $stats['by_carrier'][$c] = count(array_filter($formattedOptions, fn($o) => $o['transporteur'] === $c));
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'options' => $formattedOptions,
            'stats' => $stats
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
        'id' => $option['id'],
        'transporteur' => $option['transporteur'],
        'code_option' => $option['code_option'],
        'libelle' => $option['libelle'],
        'montant' => $option['montant'],
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
        throw new Exception('Données JSON invalides');
    }
    
    // Validation
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
        throw new Exception('Cette option existe déjà pour ce transporteur');
    }
    
    // Insérer (utiliser directement le code transporteur)
    $sql = "INSERT INTO gul_options_supplementaires (transporteur, code_option, libelle, montant, unite, actif) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $data['transporteur'], // Garder le code (xpo, heppner, kn)
        $data['code_option'],
        $data['libelle'],
        (float)$data['montant'],
        $data['unite'],
        isset($data['actif']) ? (int)$data['actif'] : 1
    ]);
    
    $newId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'data' => ['id' => $newId],
        'message' => 'Option créée avec succès'
    ]);
}

/**
 * Met à jour une option
 */
function handleUpdateOption($db) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('Données invalides ou ID manquant');
    }
    
    $id = $data['id'];
    
    // Vérifier que l'option existe
    $stmt = $db->prepare("SELECT id FROM gul_options_supplementaires WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Option non trouvée');
    }
    
    // Construire la requête de mise à jour
    $updateFields = [];
    $params = [];
    
    $updatableFields = ['libelle', 'montant', 'unite', 'actif'];
    
    foreach ($updatableFields as $field) {
        if (isset($data[$field])) {
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
    
    echo json_encode([
        'success' => true,
        'message' => 'Option mise à jour avec succès'
    ]);
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
        throw new Exception('Option non trouvée');
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
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        throw new Exception('Option non trouvée');
    }
    
    $newStatus = $result['actif'] ? 0 : 1;
    
    // Mettre à jour
    $stmt = $db->prepare("UPDATE gul_options_supplementaires SET actif = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    
    echo json_encode([
        'success' => true,
        'data' => ['actif' => (bool)$newStatus],
        'message' => $newStatus ? 'Option activée' : 'Option désactivée'
    ]);
}

/**
 * Fonctions utilitaires
 */
function formatPrice($price) {
    if ($price === null || $price === '') {
        return '0.00';
    }
    return number_format((float)$price, 2, '.', '');
}

/**
 * Convertit le code transporteur en nom d'affichage
 */
function getCarrierDisplayName($carrierCode) {
    $mapping = [
        'heppner' => 'Heppner',
        'xpo' => 'XPO',
        'kn' => 'Kuehne + Nagel'
    ];
    return $mapping[$carrierCode] ?? ucfirst($carrierCode);
}
?>

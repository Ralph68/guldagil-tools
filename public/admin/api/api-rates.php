<?php
// public/admin/api-rates.php - Version simple et fonctionnelle
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

error_log("API Call: Method=$method, Action=$action, GET=" . json_encode($_GET));

try {
    switch ($action) {
        case 'list':
            handleListRates($db);
            break;
            
        case 'carriers':
            handleGetCarriers($db);
            break;
            
        case 'departments':
            handleGetDepartments($db);
            break;
            
        case 'get':
            handleGetSingleRate($db);
            break;
            
        case 'delete':
            handleDeleteRate($db);
            break;
            
        default:
            if ($method === 'PUT') {
                handleUpdateRate($db);
            } else {
                handleListRates($db);
            }
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
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
 * Liste les tarifs
 */
function handleListRates($db) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    
    $filters = [
        'carrier' => $_GET['carrier'] ?? '',
        'department' => $_GET['department'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    $allRates = [];
    $totalCount = 0;
    
    $carriers = [
        'heppner' => ['table' => 'gul_heppner_rates', 'name' => 'Heppner'],
        'xpo' => ['table' => 'gul_xpo_rates', 'name' => 'XPO'],
        'kn' => ['table' => 'gul_kn_rates', 'name' => 'Kuehne + Nagel']
    ];
    
    foreach ($carriers as $carrierCode => $carrierInfo) {
        if ($filters['carrier'] && $filters['carrier'] !== $carrierCode) {
            continue;
        }
        
        try {
            $rates = getCarrierRates($db, $carrierCode, $carrierInfo, $filters);
            $allRates = array_merge($allRates, $rates);
            $totalCount += count($rates);
        } catch (Exception $e) {
            error_log("Erreur transporteur $carrierCode: " . $e->getMessage());
        }
    }
    
    usort($allRates, function($a, $b) {
        return strcmp($a['department_num'], $b['department_num']);
    });
    
    $paginatedRates = array_slice($allRates, $offset, $limit);
    $totalPages = ceil($totalCount / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'rates' => $paginatedRates,
            'pagination' => [
                'page' => $page,
                'pages' => $totalPages,
                'total' => $totalCount,
                'limit' => $limit
            ],
            'filters' => $filters
        ]
    ]);
}

/**
 * Récupère les tarifs d'un transporteur
 */
function getCarrierRates($db, $carrierCode, $carrierInfo, $filters) {
    $table = $carrierInfo['table'];
    $carrierName = $carrierInfo['name'];
    
    $sql = "SELECT * FROM `$table` WHERE 1=1";
    $params = [];
    
    if ($filters['department']) {
        $sql .= " AND (num_departement LIKE ? OR departement LIKE ?)";
        $params[] = '%' . $filters['department'] . '%';
        $params[] = '%' . $filters['department'] . '%';
    }
    
    if ($filters['search']) {
        $sql .= " AND (num_departement LIKE ? OR departement LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    
    $sql .= " ORDER BY CAST(num_departement AS UNSIGNED)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedRates = [];
    foreach ($rates as $rate) {
        $formattedRates[] = [
            'id' => $rate['id'],
            'carrier_code' => $carrierCode,
            'carrier_name' => $carrierName,
            'department_num' => $rate['num_departement'],
            'department_name' => $rate['departement'] ?: 'Non défini',
            'delay' => $rate['delais'] ?? '',
            'rates' => [
                'tarif_0_9' => formatPrice($rate['tarif_0_9'] ?? null),
                'tarif_10_19' => formatPrice($rate['tarif_10_19'] ?? null),
                'tarif_20_29' => formatPrice($rate['tarif_20_29'] ?? null),
                'tarif_30_39' => formatPrice($rate['tarif_30_39'] ?? null),
                'tarif_40_49' => formatPrice($rate['tarif_40_49'] ?? null),
                'tarif_50_59' => formatPrice($rate['tarif_50_59'] ?? null),
                'tarif_60_69' => formatPrice($rate['tarif_60_69'] ?? null),
                'tarif_70_79' => formatPrice($rate['tarif_70_79'] ?? null),
                'tarif_80_89' => formatPrice($rate['tarif_80_89'] ?? null),
                'tarif_90_99' => formatPrice($rate['tarif_90_99'] ?? null),
                'tarif_100_299' => formatPrice($rate['tarif_100_299'] ?? null),
                'tarif_300_499' => formatPrice($rate['tarif_300_499'] ?? null),
                'tarif_500_999' => formatPrice($rate['tarif_500_999'] ?? null),
                'tarif_1000_1999' => formatPrice($rate['tarif_1000_1999'] ?? null),
                'tarif_2000_2999' => formatPrice($rate['tarif_2000_2999'] ?? null)
            ],
            'status' => determineStatus($rate)
        ];
    }
    
    return $formattedRates;
}

/**
 * Récupère un tarif spécifique
 */
function handleGetSingleRate($db) {
    $id = (int)($_GET['id'] ?? 0);
    $carrier = $_GET['carrier'] ?? '';
    
    if (!$id || !$carrier) {
        throw new Exception('ID et transporteur requis');
    }
    
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    if (!isset($tables[$carrier])) {
        throw new Exception('Transporteur non valide');
    }
    
    $table = $tables[$carrier];
    
    $stmt = $db->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    $rate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rate) {
        throw new Exception('Tarif non trouvé');
    }
    
    $formattedRate = [
        'id' => $rate['id'],
        'carrier_code' => $carrier,
        'carrier_name' => getCarrierName($carrier),
        'department_num' => $rate['num_departement'],
        'department_name' => $rate['departement'] ?: 'Non défini',
        'delay' => $rate['delais'] ?? '',
        'rates' => [
            'tarif_0_9' => formatPrice($rate['tarif_0_9'] ?? null),
            'tarif_10_19' => formatPrice($rate['tarif_10_19'] ?? null),
            'tarif_20_29' => formatPrice($rate['tarif_20_29'] ?? null),
            'tarif_30_39' => formatPrice($rate['tarif_30_39'] ?? null),
            'tarif_40_49' => formatPrice($rate['tarif_40_49'] ?? null),
            'tarif_50_59' => formatPrice($rate['tarif_50_59'] ?? null),
            'tarif_60_69' => formatPrice($rate['tarif_60_69'] ?? null),
            'tarif_70_79' => formatPrice($rate['tarif_70_79'] ?? null),
            'tarif_80_89' => formatPrice($rate['tarif_80_89'] ?? null),
            'tarif_90_99' => formatPrice($rate['tarif_90_99'] ?? null),
            'tarif_100_299' => formatPrice($rate['tarif_100_299'] ?? null),
            'tarif_300_499' => formatPrice($rate['tarif_300_499'] ?? null),
            'tarif_500_999' => formatPrice($rate['tarif_500_999'] ?? null),
            'tarif_1000_1999' => formatPrice($rate['tarif_1000_1999'] ?? null),
            'tarif_2000_2999' => formatPrice($rate['tarif_2000_2999'] ?? null)
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $formattedRate
    ]);
}

/**
 * Met à jour un tarif
 */
function handleUpdateRate($db) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Données JSON invalides');
    }
    
    $id = $data['id'] ?? null;
    $carrier = $data['carrier_code'] ?? '';
    
    if (!$id || !$carrier) {
        throw new Exception('ID et transporteur requis');
    }
    
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    if (!isset($tables[$carrier])) {
        throw new Exception('Transporteur non valide');
    }
    
    $table = $tables[$carrier];
    
    $updateFields = [];
    $params = [];
    
    if (isset($data['department_name'])) {
        $updateFields[] = 'departement = ?';
        $params[] = $data['department_name'];
    }
    
    if (isset($data['delay'])) {
        $updateFields[] = 'delais = ?';
        $params[] = $data['delay'];
    }
    
    if (isset($data['rates']) && is_array($data['rates'])) {
        $rateFields = [
            'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
            'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
            'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999'
        ];
        
        if ($carrier === 'xpo') {
            $rateFields[] = 'tarif_2000_2999';
        }
        
        foreach ($rateFields as $field) {
            if (array_key_exists($field, $data['rates'])) {
                $updateFields[] = "`$field` = ?";
                $value = $data['rates'][$field];
                $params[] = ($value !== null && $value !== '') ? (float)$value : null;
            }
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('Aucune donnée à mettre à jour');
    }
    
    $params[] = $id;
    $sql = "UPDATE `$table` SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Tarif mis à jour avec succès'
        ]);
    } else {
        throw new Exception('Aucun tarif trouvé avec cet ID');
    }
}

/**
 * Supprime un tarif
 */
function handleDeleteRate($db) {
    $id = (int)($_GET['id'] ?? 0);
    $carrier = $_GET['carrier'] ?? '';
    
    if (!$id || !$carrier) {
        throw new Exception('ID et transporteur requis');
    }
    
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    if (!isset($tables[$carrier])) {
        throw new Exception('Transporteur non valide');
    }
    
    $table = $tables[$carrier];
    
    $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Tarif supprimé avec succès'
        ]);
    } else {
        throw new Exception('Aucun tarif trouvé avec cet ID');
    }
}

/**
 * Récupère la liste des transporteurs
 */
function handleGetCarriers($db) {
    $carriers = [
        ['code' => 'heppner', 'name' => 'Heppner', 'table' => 'gul_heppner_rates'],
        ['code' => 'xpo', 'name' => 'XPO', 'table' => 'gul_xpo_rates'],
        ['code' => 'kn', 'name' => 'Kuehne + Nagel', 'table' => 'gul_kn_rates']
    ];
    
    foreach ($carriers as &$carrier) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM `{$carrier['table']}`");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $carrier['rates_count'] = $result['count'] ?? 0;
        } catch (Exception $e) {
            $carrier['rates_count'] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $carriers
    ]);
}

/**
 * Récupère la liste des départements
 */
function handleGetDepartments($db) {
    $departments = [];
    $tables = ['gul_heppner_rates', 'gul_xpo_rates', 'gul_kn_rates'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->prepare("SELECT DISTINCT num_departement, departement FROM `$table` WHERE num_departement IS NOT NULL AND num_departement != ''");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $key = $row['num_departement'];
                if (!isset($departments[$key])) {
                    $departments[$key] = [
                        'num' => $row['num_departement'],
                        'name' => $row['departement'] ?: 'Non défini'
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Erreur table $table: " . $e->getMessage());
        }
    }
    
    ksort($departments);
    
    echo json_encode([
        'success' => true,
        'data' => array_values($departments)
    ]);
}

/**
 * Fonctions utilitaires
 */
function formatPrice($price) {
    if ($price === null || $price === '' || $price <= 0) {
        return null;
    }
    return number_format((float)$price, 2, '.', '');
}

function determineStatus($rate) {
    $rateFields = ['tarif_0_9', 'tarif_10_19', 'tarif_90_99', 'tarif_100_299'];
    $filledRates = 0;
    
    foreach ($rateFields as $field) {
        if (isset($rate[$field]) && $rate[$field] !== null && $rate[$field] > 0) {
            $filledRates++;
        }
    }
    
    if ($filledRates >= 3) {
        return 'complet';
    } elseif ($filledRates >= 1) {
        return 'partiel';
    } else {
        return 'vide';
    }
}

function getCarrierName($carrier) {
    $names = [
        'heppner' => 'Heppner',
        'xpo' => 'XPO',
        'kn' => 'Kuehne + Nagel'
    ];
    return $names[$carrier] ?? $carrier;
}
?>

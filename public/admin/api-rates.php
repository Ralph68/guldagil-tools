<?php
// public/admin/api-rates.php - API pour la gestion des tarifs
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../lib/Transport.php';

header('Content-Type: application/json; charset=UTF-8');

// CORS pour les requêtes AJAX
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    $transport = new Transport($db);
    
    switch ($action) {
        case 'list':
            handleListRates($db, $transport);
            break;
            
        case 'get':
            handleGetRequest($db);
            break;
            
        case 'carriers':
            handleGetCarriers($db);
            break;
            
        case 'departments':
            handleGetDepartments($db);
            break;
            
        case 'delete':
            handleDeleteRate($db, $transport);
            break;
            
        case 'update':
            handleUpdateRate($db);
            break;
            
        case 'create':
            handleCreateRate($db);
            break;
            
        default:
            throw new Exception("Action non supportée: $action");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Gère les requêtes GET pour récupérer un tarif spécifique
 */
function handleGetRequest($db) {
    $id = (int)($_GET['id'] ?? 0);
    $carrier = $_GET['carrier'] ?? '';
    
    if (!$id || !$carrier) {
        throw new Exception('ID et transporteur requis');
    }
    
    $rate = getSingleRate($db, $carrier, $id);
    
    if ($rate) {
        echo json_encode([
            'success' => true,
            'data' => $rate
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Tarif non trouvé'
        ]);
    }
}

/**
 * Récupère un tarif spécifique par ID et transporteur
 */
function getSingleRate($db, $carrier, $id) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $carrierNames = [
        'heppner' => 'Heppner',
        'xpo' => 'XPO',
        'kn' => 'Kuehne + Nagel'
    ];
    
    if (!isset($tables[$carrier])) {
        throw new Exception('Transporteur non valide');
    }
    
    $table = $tables[$carrier];
    $carrierName = $carrierNames[$carrier];
    
    // Construire la requête selon le transporteur
    $query = "SELECT 
        id,
        '{$carrier}' as carrier_code,
        '{$carrierName}' as carrier_name,
        num_departement as department_num,
        departement as department_name,
        delais as delay,
        tarif_0_9,
        tarif_10_19,
        tarif_20_29,
        tarif_30_39,
        tarif_40_49,
        tarif_50_59,
        tarif_60_69,
        tarif_70_79,
        tarif_80_89,
        tarif_90_99,
        tarif_100_299,
        tarif_300_499,
        tarif_500_999,
        tarif_1000_1999";
        
    // Ajouter la colonne spécifique à XPO
    if ($carrier === 'xpo') {
        $query .= ", tarif_2000_2999";
    } else {
        $query .= ", NULL as tarif_2000_2999";
    }
    
    $query .= " FROM {$table} WHERE id = :id LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $rate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rate) {
        return null;
    }
    
    // Formater les données
    return [
        'id' => $rate['id'],
        'carrier_code' => $rate['carrier_code'],
        'carrier_name' => $rate['carrier_name'],
        'department_num' => $rate['department_num'],
        'department_name' => $rate['department_name'] ?: 'Non défini',
        'delay' => $rate['delay'],
        'rates' => [
            'tarif_0_9' => $rate['tarif_0_9'],
            'tarif_10_19' => $rate['tarif_10_19'],
            'tarif_20_29' => $rate['tarif_20_29'],
            'tarif_30_39' => $rate['tarif_30_39'],
            'tarif_40_49' => $rate['tarif_40_49'],
            'tarif_50_59' => $rate['tarif_50_59'],
            'tarif_60_69' => $rate['tarif_60_69'],
            'tarif_70_79' => $rate['tarif_70_79'],
            'tarif_80_89' => $rate['tarif_80_89'],
            'tarif_90_99' => $rate['tarif_90_99'],
            'tarif_100_299' => $rate['tarif_100_299'],
            'tarif_300_499' => $rate['tarif_300_499'],
            'tarif_500_999' => $rate['tarif_500_999'],
            'tarif_1000_1999' => $rate['tarif_1000_1999'],
            'tarif_2000_2999' => $rate['tarif_2000_2999']
        ]
    ];
}

/**
 * Met à jour un tarif existant
 */
function handleUpdateRate($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode POST requise');
    }
    
    $id = (int)($_POST['id'] ?? 0);
    $carrier = $_POST['carrier'] ?? '';
    
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
    
    // Préparer les données à mettre à jour
    $updateFields = [];
    $params = [':id' => $id];
    
    // Champs généraux
    if (isset($_POST['department_name'])) {
        $updateFields[] = 'departement = :department_name';
        $params[':department_name'] = $_POST['department_name'];
    }
    
    if (isset($_POST['delay'])) {
        $updateFields[] = 'delais = :delay';
        $params[':delay'] = $_POST['delay'];
    }
    
    // Tarifs
    $tariffFields = [
        'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
        'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
        'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999'
    ];
    
    // Ajouter tarif_2000_2999 pour XPO
    if ($carrier === 'xpo') {
        $tariffFields[] = 'tarif_2000_2999';
    }
    
    foreach ($tariffFields as $field) {
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            if ($value === '' || $value === null) {
                $updateFields[] = "{$field} = NULL";
            } else {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = (float)$value;
            }
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('Aucune donnée à mettre à jour');
    }
    
    $query = "UPDATE {$table} SET " . implode(', ', $updateFields) . " WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        // Récupérer le tarif mis à jour
        $updatedRate = getSingleRate($db, $carrier, $id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tarif mis à jour avec succès',
            'data' => $updatedRate
        ]);
    } else {
        throw new Exception('Aucune modification effectuée');
    }
}

/**
 * Crée un nouveau tarif
 */
function handleCreateRate($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode POST requise');
    }
    
    $carrier = $_POST['carrier'] ?? '';
    $departmentNum = $_POST['department_num'] ?? '';
    
    if (!$carrier || !$departmentNum) {
        throw new Exception('Transporteur et numéro de département requis');
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
    
    // Vérifier si le tarif existe déjà
    $checkStmt = $db->prepare("SELECT id FROM {$table} WHERE num_departement = :dept");
    $checkStmt->execute([':dept' => $departmentNum]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Un tarif existe déjà pour ce département et ce transporteur');
    }
    
    // Préparer les données à insérer
    $insertFields = ['num_departement'];
    $insertValues = [':num_departement'];
    $params = [':num_departement' => $departmentNum];
    
    // Champs optionnels
    if (isset($_POST['department_name']) && $_POST['department_name'] !== '') {
        $insertFields[] = 'departement';
        $insertValues[] = ':department_name';
        $params[':department_name'] = $_POST['department_name'];
    }
    
    if (isset($_POST['delay']) && $_POST['delay'] !== '') {
        $insertFields[] = 'delais';
        $insertValues[] = ':delay';
        $params[':delay'] = $_POST['delay'];
    }
    
    // Tarifs
    $tariffFields = [
        'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
        'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
        'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999'
    ];
    
    // Ajouter tarif_2000_2999 pour XPO
    if ($carrier === 'xpo') {
        $tariffFields[] = 'tarif_2000_2999';
    }
    
    foreach ($tariffFields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $insertFields[] = $field;
            $insertValues[] = ":{$field}";
            $params[":{$field}"] = (float)$_POST[$field];
        }
    }
    
    $query = "INSERT INTO {$table} (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertValues) . ")";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $newId = $db->lastInsertId();
    
    if ($newId) {
        // Récupérer le nouveau tarif
        $newRate = getSingleRate($db, $carrier, $newId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tarif créé avec succès',
            'data' => $newRate
        ]);
    } else {
        throw new Exception('Erreur lors de la création du tarif');
    }
}

/**
 * Liste les tarifs avec pagination et filtres
 */
function handleListRates($db, $transport) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    
    // Filtres
    $filters = [
        'carrier' => $_GET['carrier'] ?? '',
        'department' => $_GET['department'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    // Construction de la requête avec UNION pour tous les transporteurs
    $carriers = [
        'heppner' => ['table' => 'gul_heppner_rates', 'name' => 'Heppner'],
        'xpo' => ['table' => 'gul_xpo_rates', 'name' => 'XPO'],
        'kn' => ['table' => 'gul_kn_rates', 'name' => 'Kuehne + Nagel']
    ];
    
    $unionQueries = [];
    $params = [];
    
    foreach ($carriers as $code => $info) {
        $whereConditions = ["'{$code}' as carrier_code", "'{$info['name']}' as carrier_name"];
        
        // Filtre par transporteur
        if ($filters['carrier'] && $filters['carrier'] !== $code) {
            continue; // Skip ce transporteur
        }
        
        $query = "SELECT 
            id,
            {$whereConditions[0]},
            {$whereConditions[1]},
            num_departement as department_num,
            departement as department_name,
            delais as delay,
            tarif_0_9,
            tarif_10_19,
            tarif_20_29,
            tarif_30_39,
            tarif_40_49,
            tarif_50_59,
            tarif_60_69,
            tarif_70_79,
            tarif_80_89,
            tarif_90_99,
            tarif_100_299,
            tarif_300_499,
            tarif_500_999,
            tarif_1000_1999";
            
        // Ajouter les colonnes spécifiques à XPO
        if ($code === 'xpo') {
            $query .= ", tarif_2000_2999";
        } else {
            $query .= ", NULL as tarif_2000_2999";
        }
        
        $query .= " FROM {$info['table']} WHERE 1=1";
        
        // Filtre par département
        if ($filters['department']) {
            $query .= " AND num_departement = :department_{$code}";
            $params["department_{$code}"] = $filters['department'];
        }
        
        // Filtre par recherche
        if ($filters['search']) {
            $query .= " AND (num_departement LIKE :search_{$code} OR departement LIKE :search_{$code}_name)";
            $params["search_{$code}"] = '%' . $filters['search'] . '%';
            $params["search_{$code}_name"] = '%' . $filters['search'] . '%';
        }
        
        $unionQueries[] = $query;
    }
    
    if (empty($unionQueries)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'rates' => [],
                'pagination' => [
                    'page' => $page,
                    'pages' => 0,
                    'total' => 0
                ],
                'filters' => $filters
            ]
        ]);
        return;
    }
    
    // Compter le total
    $countQuery = "SELECT COUNT(*) as total FROM (" . implode(' UNION ALL ', $unionQueries) . ") as combined";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Récupérer les données avec pagination
    $dataQuery = "SELECT * FROM (" . implode(' UNION ALL ', $unionQueries) . ") as combined 
                  ORDER BY carrier_name, department_num 
                  LIMIT :limit OFFSET :offset";
    
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    $stmt = $db->prepare($dataQuery);
    $stmt->execute($params);
    $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données
    $formattedRates = [];
    foreach ($rates as $rate) {
        $formattedRates[] = [
            'id' => $rate['id'],
            'carrier_code' => $rate['carrier_code'],
            'carrier_name' => $rate['carrier_name'],
            'department_num' => $rate['department_num'],
            'department_name' => $rate['department_name'] ?: 'Non défini',
            'delay' => $rate['delay'],
            'rates' => [
                'tarif_0_9' => $rate['tarif_0_9'],
                'tarif_10_19' => $rate['tarif_10_19'],
                'tarif_20_29' => $rate['tarif_20_29'],
                'tarif_30_39' => $rate['tarif_30_39'],
                'tarif_40_49' => $rate['tarif_40_49'],
                'tarif_50_59' => $rate['tarif_50_59'],
                'tarif_60_69' => $rate['tarif_60_69'],
                'tarif_70_79' => $rate['tarif_70_79'],
                'tarif_80_89' => $rate['tarif_80_89'],
                'tarif_90_99' => $rate['tarif_90_99'],
                'tarif_100_299' => $rate['tarif_100_299'],
                'tarif_300_499' => $rate['tarif_300_499'],
                'tarif_500_999' => $rate['tarif_500_999'],
                'tarif_1000_1999' => $rate['tarif_1000_1999'],
                'tarif_2000_2999' => $rate['tarif_2000_2999']
            ]
        ];
    }
    
    $totalPages = $total > 0 ? ceil($total / $limit) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'rates' => $formattedRates,
            'pagination' => [
                'page' => $page,
                'pages' => $totalPages,
                'total' => $total,
                'limit' => $limit
            ],
            'filters' => $filters
        ]
    ]);
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
    
    // Ajouter le nombre de tarifs par transporteur
    foreach ($carriers as &$carrier) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM {$carrier['table']}");
            $carrier['rates_count'] = $stmt->fetch()['count'] ?? 0;
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
    try {
        // Union des départements de toutes les tables
        $query = "SELECT DISTINCT num_departement as num, departement as name 
                  FROM (
                      SELECT num_departement, departement FROM gul_heppner_rates WHERE num_departement IS NOT NULL
                      UNION 
                      SELECT num_departement, departement FROM gul_xpo_rates WHERE num_departement IS NOT NULL
                      UNION 
                      SELECT num_departement, departement FROM gul_kn_rates WHERE num_departement IS NOT NULL
                  ) as all_departments 
                  ORDER BY CAST(num_departement AS UNSIGNED)";
        
        $stmt = $db->query($query);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nettoyer les données
        $cleanDepartments = [];
        foreach ($departments as $dept) {
            if ($dept['num']) {
                $cleanDepartments[] = [
                    'num' => $dept['num'],
                    'name' => $dept['name'] ?: 'Non défini'
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $cleanDepartments
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la récupération des départements: ' . $e->getMessage()
        ]);
    }
}

/**
 * Supprime un tarif
 */
function handleDeleteRate($db, $transport) {
    $id = (int)($_GET['id'] ?? 0);
    $carrier = $_GET['carrier'] ?? '';
    
    if (!$id || !$carrier) {
        throw new Exception('ID et transporteur requis pour la suppression');
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
    $stmt = $db->prepare("DELETE FROM {$table} WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Tarif supprimé avec succès'
        ]);
    } else {
        throw new Exception('Aucun tarif trouvé avec cet ID');
    }
}
?>

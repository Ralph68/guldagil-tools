<?php
// public/admin/api-rates.php - API pour la gestion des tarifs
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/../../config.php';

// Gestion des erreurs
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGetRequest($db, $action);
            break;
        case 'POST':
            handlePostRequest($db, $action);
            break;
        case 'PUT':
            handlePutRequest($db, $action);
            break;
        case 'DELETE':
            handleDeleteRequest($db, $action);
            break;
        default:
            sendError('Méthode non autorisée', 405);
    }
    
} catch (Exception $e) {
    sendError('Erreur serveur: ' . $e->getMessage(), 500);
}

// =============================================================================
// GESTIONNAIRES DE REQUÊTES
// =============================================================================

function handleGetRequest($db, $action) {
    switch ($action) {
        case 'list':
            getRatesList($db);
            break;
        case 'carriers':
            getCarriers($db);
            break;
        case 'departments':
            getDepartments($db);
            break;
        default:
            getRatesList($db);
    }
}

function handlePostRequest($db, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            createRate($db, $data);
            break;
        default:
            sendError('Action non reconnue', 400);
    }
}

function handlePutRequest($db, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            updateRate($db, $data);
            break;
        default:
            sendError('Action non reconnue', 400);
    }
}

function handleDeleteRequest($db, $action) {
    switch ($action) {
        case 'delete':
            deleteRate($db);
            break;
        default:
            sendError('Action non reconnue', 400);
    }
}

// =============================================================================
// FONCTIONS MÉTIER
// =============================================================================

function getRatesList($db) {
    $carrier = $_GET['carrier'] ?? '';
    $department = $_GET['department'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = ($page - 1) * $limit;
    
    $carriers = [
        'heppner' => ['table' => 'gul_heppner_rates', 'name' => 'Heppner'],
        'xpo' => ['table' => 'gul_xpo_rates', 'name' => 'XPO'],
        'kn' => ['table' => 'gul_kn_rates', 'name' => 'Kuehne + Nagel']
    ];
    
    $results = [];
    $totalCount = 0;
    
    foreach ($carriers as $carrierCode => $carrierInfo) {
        // Filtrer par transporteur si spécifié
        if ($carrier && $carrier !== $carrierCode) {
            continue;
        }
        
        $table = $carrierInfo['table'];
        $carrierName = $carrierInfo['name'];
        
        // Construction de la requête
        $sql = "SELECT * FROM `$table` WHERE 1=1";
        $params = [];
        
        if ($department) {
            $sql .= " AND (num_departement LIKE :dept OR departement LIKE :dept_name)";
            $params[':dept'] = "%$department%";
            $params[':dept_name'] = "%$department%";
        }
        
        $sql .= " ORDER BY num_departement LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $db->prepare($sql);
        
        // Lier les paramètres
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        
        $stmt->execute();
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rates as $rate) {
            $results[] = [
                'id' => $rate['id'],
                'carrier_code' => $carrierCode,
                'carrier_name' => $carrierName,
                'department_num' => $rate['num_departement'],
                'department_name' => $rate['departement'] ?? '',
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
                'status' => determineStatus($rate),
                'last_updated' => date('Y-m-d H:i:s')
            ];
        }
        
        // Compter le total pour la pagination
        $countSql = "SELECT COUNT(*) FROM `$table` WHERE 1=1";
        if ($department) {
            $countSql .= " AND (num_departement LIKE '%$department%' OR departement LIKE '%$department%')";
        }
        $totalCount += $db->query($countSql)->fetchColumn();
    }
    
    // Trier les résultats par département
    usort($results, function($a, $b) {
        return strcmp($a['department_num'], $b['department_num']);
    });
    
    sendSuccess([
        'rates' => $results,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $limit)
        ],
        'filters' => [
            'carrier' => $carrier,
            'department' => $department
        ]
    ]);
}

function getCarriers($db) {
    $carriers = [
        ['code' => 'heppner', 'name' => 'Heppner', 'table' => 'gul_heppner_rates'],
        ['code' => 'xpo', 'name' => 'XPO', 'table' => 'gul_xpo_rates'],
        ['code' => 'kn', 'name' => 'Kuehne + Nagel', 'table' => 'gul_kn_rates']
    ];
    
    // Ajouter le nombre de tarifs par transporteur
    foreach ($carriers as &$carrier) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM `{$carrier['table']}`");
            $carrier['rates_count'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $carrier['rates_count'] = 0;
        }
    }
    
    sendSuccess($carriers);
}

function getDepartments($db) {
    $departments = [];
    
    $tables = ['gul_heppner_rates', 'gul_xpo_rates', 'gul_kn_rates'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT DISTINCT num_departement, departement FROM `$table` WHERE num_departement IS NOT NULL ORDER BY num_departement");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $key = $row['num_departement'];
                if (!isset($departments[$key])) {
                    $departments[$key] = [
                        'num' => $row['num_departement'],
                        'name' => $row['departement'] ?? ''
                    ];
                }
            }
        } catch (Exception $e) {
            // Ignorer les erreurs de table
        }
    }
    
    sendSuccess(array_values($departments));
}

function createRate($db, $data) {
    // Validation des données
    $required = ['carrier_code', 'department_num'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendError("Le champ '$field' est requis", 400);
            return;
        }
    }
    
    $carrier = $data['carrier_code'];
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    if (!isset($tables[$carrier])) {
        sendError('Transporteur non valide', 400);
        return;
    }
    
    $table = $tables[$carrier];
    
    // Vérifier si le tarif existe déjà
    $stmt = $db->prepare("SELECT id FROM `$table` WHERE num_departement = ?");
    $stmt->execute([$data['department_num']]);
    
    if ($stmt->fetch()) {
        sendError('Un tarif existe déjà pour ce département', 409);
        return;
    }
    
    // Préparer les données d'insertion
    $insertData = [
        'num_departement' => $data['department_num'],
        'departement' => $data['department_name'] ?? '',
        'delais' => $data['delay'] ?? ''
    ];
    
    // Ajouter les tarifs
    $rateFields = [
        'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
        'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
        'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999'
    ];
    
    // Pour XPO, ajouter tarif_2000_2999
    if ($carrier === 'xpo') {
        $rateFields[] = 'tarif_2000_2999';
    }
    
    foreach ($rateFields as $field) {
        $insertData[$field] = isset($data['rates'][$field]) && $data['rates'][$field] !== '' 
            ? (float)$data['rates'][$field] 
            : null;
    }
    
    // Construire la requête d'insertion
    $fields = array_keys($insertData);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($insertData));
        
        sendSuccess([
            'id' => $db->lastInsertId(),
            'message' => 'Tarif créé avec succès'
        ]);
        
    } catch (PDOException $e) {
        sendError('Erreur lors de la création: ' . $e->getMessage(), 500);
    }
}

function updateRate($db, $data) {
    // Validation des données
    if (!isset($data['id']) || !isset($data['carrier_code'])) {
        sendError('ID et transporteur requis', 400);
        return;
    }
    
    $carrier = $data['carrier_code'];
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    if (!isset($tables[$carrier])) {
        sendError('Transporteur non valide', 400);
        return;
    }
    
    $table = $tables[$carrier];
    
    // Préparer les données de mise à jour
    $updateData = [];
    $setParts = [];
    
    if (isset($data['department_name'])) {
        $updateData[] = $data['department_name'];
        $setParts[] = 'departement = ?';
    }
    
    if (isset($data['delay'])) {
        $updateData[] = $data['delay'];
        $setParts[] = 'delais = ?';
    }
    
    // Mettre à jour les tarifs
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
            if (isset($data['rates'][$field])) {
                $value = $data['rates'][$field] !== '' ? (float)$data['rates'][$field] : null;
                $updateData[] = $value;
                $setParts[] = "`$field` = ?";
            }
        }
    }
    
    if (empty($setParts)) {
        sendError('Aucune donnée à mettre à jour', 400);
        return;
    }
    
    $updateData[] = $data['id'];
    $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE id = ?";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($updateData);
        
        if ($stmt->rowCount() > 0) {
            sendSuccess(['message' => 'Tarif mis à jour avec succès']);
        } else {
            sendError('Aucun tarif trouvé avec cet ID', 404);
        }
        
    } catch (PDOException $e) {
        sendError('Erreur lors de la mise à jour: ' . $e->getMessage(), 500);
    }
}

function deleteRate($db) {
    $id = $_GET['id'] ?? '';
    $carrier = $_GET['carrier'] ?? '';
    
    if (!$id || !$carrier) {
        sendError('ID et transporteur requis', 400);
        return;
    }
    
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    if (!isset($tables[$carrier])) {
        sendError('Transporteur non valide', 400);
        return;
    }
    
    $table = $tables[$carrier];
    
    try {
        $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendSuccess(['message' => 'Tarif supprimé avec succès']);
        } else {
            sendError('Aucun tarif trouvé avec cet ID', 404);
        }
        
    } catch (PDOException $e) {
        sendError('Erreur lors de la suppression: ' . $e->getMessage(), 500);
    }
}

// =============================================================================
// FONCTIONS UTILITAIRES
// =============================================================================

function formatPrice($price) {
    if ($price === null || $price === '') {
        return null;
    }
    return number_format((float)$price, 2, '.', '');
}

function determineStatus($rate) {
    // Compter les tarifs non nuls
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

function sendSuccess($data) {
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

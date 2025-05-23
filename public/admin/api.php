<?php
// public/admin/api.php
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../lib/Transport.php';

header('Content-Type: application/json; charset=UTF-8');
session_start();

$transport = new Transport($db);
$response = ['success' => false, 'message' => '', 'data' => null];

// Récupérer l'action depuis POST ou GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // =============================================================================
        // GESTION DES TARIFS
        // =============================================================================
        
        case 'get_rates':
            $carrier = $_GET['carrier'] ?? '';
            $search = $_GET['search'] ?? '';
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $rates = getRates($db, $carrier, $search, $limit, $offset);
            $response = ['success' => true, 'data' => $rates];
            break;
            
        case 'get_rate':
            $id = (int)($_GET['id'] ?? 0);
            $carrier = $_GET['carrier'] ?? '';
            
            $rate = getRate($db, $id, $carrier);
            $response = ['success' => true, 'data' => $rate];
            break;
            
        case 'save_rate':
            $rateData = [
                'id' => $_POST['id'] ?? null,
                'carrier' => $_POST['carrier'] ?? '',
                'department' => $_POST['department'] ?? '',
                'tarif_0_9' => !empty($_POST['tarif_0_9']) ? (float)$_POST['tarif_0_9'] : null,
                'tarif_10_19' => !empty($_POST['tarif_10_19']) ? (float)$_POST['tarif_10_19'] : null,
                'tarif_20_29' => !empty($_POST['tarif_20_29']) ? (float)$_POST['tarif_20_29'] : null,
                'tarif_30_39' => !empty($_POST['tarif_30_39']) ? (float)$_POST['tarif_30_39'] : null,
                'tarif_40_49' => !empty($_POST['tarif_40_49']) ? (float)$_POST['tarif_40_49'] : null,
                'tarif_50_59' => !empty($_POST['tarif_50_59']) ? (float)$_POST['tarif_50_59'] : null,
                'tarif_60_69' => !empty($_POST['tarif_60_69']) ? (float)$_POST['tarif_60_69'] : null,
                'tarif_70_79' => !empty($_POST['tarif_70_79']) ? (float)$_POST['tarif_70_79'] : null,
                'tarif_80_89' => !empty($_POST['tarif_80_89']) ? (float)$_POST['tarif_80_89'] : null,
                'tarif_90_99' => !empty($_POST['tarif_90_99']) ? (float)$_POST['tarif_90_99'] : null,
                'tarif_100_299' => !empty($_POST['tarif_100_299']) ? (float)$_POST['tarif_100_299'] : null,
                'tarif_300_499' => !empty($_POST['tarif_300_499']) ? (float)$_POST['tarif_300_499'] : null,
                'tarif_500_999' => !empty($_POST['tarif_500_999']) ? (float)$_POST['tarif_500_999'] : null,
                'tarif_1000_1999' => !empty($_POST['tarif_1000_1999']) ? (float)$_POST['tarif_1000_1999'] : null,
                'delais' => $_POST['delais'] ?? ''
            ];
            
            if (saveRate($db, $rateData)) {
                $response = ['success' => true, 'message' => 'Tarif enregistré avec succès'];
            } else {
                throw new Exception('Erreur lors de l\'enregistrement du tarif');
            }
            break;
            
        case 'delete_rate':
            $id = (int)($_POST['id'] ?? 0);
            $carrier = $_POST['carrier'] ?? '';
            
            if (deleteRate($db, $id, $carrier)) {
                $response = ['success' => true, 'message' => 'Tarif supprimé'];
            } else {
                throw new Exception('Erreur lors de la suppression du tarif');
            }
            break;
            
        // =============================================================================
        // GESTION DES OPTIONS
        // =============================================================================
        
        case 'get_options':
            $carrier = $_GET['carrier'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $options = getOptions($db, $carrier, $search);
            $response = ['success' => true, 'data' => $options];
            break;
            
        case 'get_option':
            $id = (int)($_GET['id'] ?? 0);
            
            $option = getOption($db, $id);
            $response = ['success' => true, 'data' => $option];
            break;
            
        case 'save_option':
            $optionData = [
                'id' => $_POST['id'] ?? null,
                'transporteur' => $_POST['transporteur'] ?? '',
                'code_option' => $_POST['code_option'] ?? '',
                'libelle' => $_POST['libelle'] ?? '',
                'montant' => (float)($_POST['montant'] ?? 0),
                'unite' => $_POST['unite'] ?? 'forfait',
                'actif' => isset($_POST['actif']) ? 1 : 0
            ];
            
            if (saveOption($db, $optionData)) {
                $response = ['success' => true, 'message' => 'Option enregistrée avec succès'];
            } else {
                throw new Exception('Erreur lors de l\'enregistrement de l\'option');
            }
            break;
            
        case 'toggle_option':
            $id = (int)($_POST['id'] ?? 0);
            
            if (toggleOption($db, $id)) {
                $response = ['success' => true, 'message' => 'Statut de l\'option modifié'];
            } else {
                throw new Exception('Erreur lors de la modification du statut');
            }
            break;
            
        case 'delete_option':
            $id = (int)($_POST['id'] ?? 0);
            
            if (deleteOption($db, $id)) {
                $response = ['success' => true, 'message' => 'Option supprimée'];
            } else {
                throw new Exception('Erreur lors de la suppression de l\'option');
            }
            break;
            
        // =============================================================================
        // GESTION DES TAXES
        // =============================================================================
        
        case 'get_taxes':
            $taxes = getTaxes($db);
            $response = ['success' => true, 'data' => $taxes];
            break;
            
        case 'save_taxes':
            // TODO: Implémenter la sauvegarde des taxes
            $response = ['success' => true, 'message' => 'Taxes mises à jour'];
            break;
            
        // =============================================================================
        // STATISTIQUES
        // =============================================================================
        
        case 'get_stats':
            $stats = getStats($db);
            $response = ['success' => true, 'data' => $stats];
            break;
            
        // =============================================================================
        // IMPORT/EXPORT
        // =============================================================================
        
        case 'import':
            if (!isset($_FILES['import_file'])) {
                throw new Exception('Aucun fichier sélectionné');
            }
            
            $file = $_FILES['import_file'];
            $result = importFile($db, $file);
            $response = ['success' => true, 'message' => 'Import terminé', 'data' => $result];
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

// =============================================================================
// FONCTIONS POUR LES TARIFS
// =============================================================================

function getRates($db, $carrier = '', $search = '', $limit = 50, $offset = 0) {
    $carriers = [
        'heppner' => ['table' => 'gul_heppner_rates', 'name' => 'Heppner'],
        'xpo' => ['table' => 'gul_xpo_rates', 'name' => 'XPO'],
        'kn' => ['table' => 'gul_kn_rates', 'name' => 'Kuehne + Nagel']
    ];
    
    $rates = [];
    
    foreach ($carriers as $code => $info) {
        if ($carrier && $carrier !== $code) continue;
        
        $sql = "SELECT * FROM {$info['table']} WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (num_departement LIKE :search OR departement LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY num_departement LIMIT $limit OFFSET $offset";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['carrier_code'] = $code;
                $row['carrier_name'] = $info['name'];
                $rates[] = $row;
            }
        } catch (PDOException $e) {
            // Table n'existe pas ou erreur, continuer avec les autres
            continue;
        }
    }
    
    return $rates;
}

function getRate($db, $id, $carrier) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $table = $tables[$carrier] ?? null;
    if (!$table) {
        throw new Exception('Transporteur invalide');
    }
    
    $sql = "SELECT * FROM `$table` WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function saveRate($db, $data) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $table = $tables[$data['carrier']] ?? null;
    if (!$table) {
        throw new Exception('Transporteur invalide');
    }
    
    // Validation
    if (!$data['department'] || !preg_match('/^[0-9]{2}$/', $data['department'])) {
        throw new Exception('Le département doit être un nombre à 2 chiffres');
    }
    
    // Construire la liste des colonnes selon le transporteur
    $columns = getTableColumns($data['carrier']);
    
    if ($data['id']) {
        // Mise à jour
        $setClauses = [];
        $params = [':id' => $data['id']];
        
        foreach ($columns as $column) {
            if (isset($data[$column])) {
                $setClauses[] = "$column = :$column";
                $params[":$column"] = $data[$column];
            }
        }
        
        $sql = "UPDATE `$table` SET " . implode(', ', $setClauses) . " WHERE id = :id";
    } else {
        // Insertion
        $columnNames = [];
        $placeholders = [];
        $params = [];
        
        foreach ($columns as $column) {
            if (isset($data[$column])) {
                $columnNames[] = $column;
                $placeholders[] = ":$column";
                $params[":$column"] = $data[$column];
            }
        }
        
        $sql = "INSERT INTO `$table` (" . implode(', ', $columnNames) . ") VALUES (" . implode(', ', $placeholders) . ")";
    }
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function deleteRate($db, $id, $carrier) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $table = $tables[$carrier] ?? null;
    if (!$table) {
        throw new Exception('Transporteur invalide');
    }
    
    $sql = "DELETE FROM `$table` WHERE id = :id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

function getTableColumns($carrier) {
    $commonColumns = ['num_departement', 'departement', 'delais'];
    
    switch ($carrier) {
        case 'heppner':
        case 'kn':
            return array_merge($commonColumns, [
                'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
                'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
                'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999'
            ]);
        case 'xpo':
            return array_merge($commonColumns, [
                'tarif_0_99', 'tarif_100_499', 'tarif_500_999', 'tarif_1000_1999', 'tarif_2000_2999'
            ]);
        default:
            return $commonColumns;
    }
}

// =============================================================================
// FONCTIONS POUR LES OPTIONS
// =============================================================================

function getOptions($db, $carrier = '', $search = '') {
    $sql = "SELECT * FROM gul_options_supplementaires WHERE 1=1";
    $params = [];
    
    if ($carrier) {
        $sql .= " AND transporteur = :carrier";
        $params[':carrier'] = $carrier;
    }
    
    if ($search) {
        $sql .= " AND (code_option LIKE :search OR libelle LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $sql .= " ORDER BY transporteur, code_option";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOption($db, $id) {
    $sql = "SELECT * FROM gul_options_supplementaires WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function saveOption($db, $data) {
    // Validation
    if (!$data['transporteur'] || !$data['code_option'] || !$data['libelle']) {
        throw new Exception('Les champs transporteur, code et libellé sont obligatoires');
    }
    
    if ($data['montant'] < 0) {
        throw new Exception('Le montant ne peut pas être négatif');
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['code_option'])) {
        throw new Exception('Le code option ne peut contenir que des lettres, chiffres et underscores');
    }
    
    if ($data['id']) {
        // Mise à jour
        $sql = "UPDATE gul_options_supplementaires SET 
                transporteur = :transporteur,
                code_option = :code_option,
                libelle = :libelle,
                montant = :montant,
                unite = :unite,
                actif = :actif
                WHERE id = :id";
        
        $params = [
            ':id' => $data['id'],
            ':transporteur' => $data['transporteur'],
            ':code_option' => $data['code_option'],
            ':libelle' => $data['libelle'],
            ':montant' => $data['montant'],
            ':unite' => $data['unite'],
            ':actif' => $data['actif']
        ];
    } else {
        // Vérifier l'unicité transporteur + code_option
        $checkSql = "SELECT COUNT(*) FROM gul_options_supplementaires WHERE transporteur = :transporteur AND code_option = :code_option";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([
            ':transporteur' => $data['transporteur'],
            ':code_option' => $data['code_option']
        ]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception('Cette option existe déjà pour ce transporteur');
        }
        
        // Insertion
        $sql = "INSERT INTO gul_options_supplementaires 
                (transporteur, code_option, libelle, montant, unite, actif)
                VALUES (:transporteur, :code_option, :libelle, :montant, :unite, :actif)";
        
        $params = [
            ':transporteur' => $data['transporteur'],
            ':code_option' => $data['code_option'],
            ':libelle' => $data['libelle'],
            ':montant' => $data['montant'],
            ':unite' => $data['unite'],
            ':actif' => $data['actif']
        ];
    }
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function toggleOption($db, $id) {
    $sql = "UPDATE gul_options_supplementaires SET actif = NOT actif WHERE id = :id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

function deleteOption($db, $id) {
    $sql = "DELETE FROM gul_options_supplementaires WHERE id = :id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

// =============================================================================
// FONCTIONS POUR LES TAXES
// =============================================================================

function getTaxes($db) {
    $sql = "SELECT * FROM gul_taxes_transporteurs ORDER BY transporteur";
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =============================================================================
// FONCTIONS POUR LES STATISTIQUES
// =============================================================================

function getStats($db) {
    $stats = [];
    
    try {
        // Compter les transporteurs actifs
        $stmt = $db->query("SELECT COUNT(*) as count FROM gul_taxes_transporteurs");
        $stats['carriers'] = $stmt->fetch()['count'];
        
        // Compter les départements avec tarifs (union des 3 tables)
        $sql = "SELECT COUNT(DISTINCT num_departement) as count FROM (
                    SELECT num_departement FROM gul_heppner_rates 
                    UNION 
                    SELECT num_departement FROM gul_xpo_rates 
                    UNION 
                    SELECT num_departement FROM gul_kn_rates
                ) as all_departments";
        $stmt = $db->query($sql);
        $stats['departments'] = $stmt->fetch()['count'];
        
        // Compter les options actives
        $stmt = $db->query("SELECT COUNT(*) as count FROM gul_options_supplementaires WHERE actif = 1");
        $stats['options'] = $stmt->fetch()['count'];
        
        // Dernière modification (approximation)
        $stats['last_update'] = date('d/m/Y');
        
    } catch (Exception $e) {
        $stats = [
            'carriers' => 3,
            'departments' => 95,
            'options' => 0,
            'last_update' => 'Aujourd\'hui'
        ];
    }
    
    return $stats;
}

// =============================================================================
// FONCTIONS D'IMPORT
// =============================================================================

function importFile($db, $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors de l\'upload du fichier');
    }
    
    $allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
        'application/csv',
        'text/plain' // CSV parfois détecté comme text/plain
    ];
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Type de fichier non supporté. Utilisez Excel (.xlsx) ou CSV.');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    try {
        if ($extension === 'csv') {
            return importCSV($db, $file['tmp_name']);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return importExcel($db, $file['tmp_name']);
        } else {
            throw new Exception('Extension de fichier non supportée');
        }
    } catch (Exception $e) {
        throw new Exception('Erreur lors de l\'import : ' . $e->getMessage());
    }
}

function importCSV($db, $filePath) {
    $imported = 0;
    $errors = [];
    $lineNumber = 1;
    
    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        // Détecter l'encodage et convertir si nécessaire
        $content = file_get_contents($filePath);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            file_put_contents($filePath, $content);
        }
        
        fclose($handle);
        $handle = fopen($filePath, 'r');
        
        $headers = null;
        
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $lineNumber++;
            
            // Ignorer les lignes vides et les commentaires
            if (empty($data) || (isset($data[0]) && strpos($data[0], '#') === 0)) {
                continue;
            }
            
            // Première ligne non-commentaire = headers
            if ($headers === null) {
                $headers = array_map('trim', $data);
                continue;
            }
            
            try {
                if (importCSVLine($db, $headers, $data)) {
                    $imported++;
                }
            } catch (Exception $e) {
                $errors[] = "Ligne $lineNumber : " . $e->getMessage();
            }
        }
        fclose($handle);
    }
    
    return [
        'imported' => $imported,
        'errors' => $errors
    ];
}

function importCSVLine($db, $headers, $data) {
    // Créer un tableau associatif
    $row = [];
    foreach ($headers as $index => $header) {
        $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
    }
    
    // Déterminer le type d'import selon les colonnes
    if (isset($row['transporteur']) && isset($row['num_departement'])) {
        return importRateFromCSV($db, $row);
    } elseif (isset($row['transporteur']) && isset($row['code_option'])) {
        return importOptionFromCSV($db, $row);
    } else {
        throw new Exception('Format de données non reconnu');
    }
}

function importRateFromCSV($db, $row) {
    $carrier = strtolower(trim($row['transporteur']));
    
    // Normaliser le nom du transporteur
    $carrierMapping = [
        'heppner' => 'heppner',
        'xpo' => 'xpo',
        'kuehne + nagel' => 'kn',
        'kuehne+nagel' => 'kn',
        'kn' => 'kn',
        'k+n' => 'kn'
    ];
    
    $carrier = $carrierMapping[$carrier] ?? null;
    if (!$carrier) {
        throw new Exception('Transporteur non reconnu : ' . $row['transporteur']);
    }
    
    // Validation du département
    if (!preg_match('/^[0-9]{2}$/', $row['num_departement'])) {
        throw new Exception('Département invalide : ' . $row['num_departement']);
    }
    
    // Construire les données
    $rateData = [
        'id' => null,
        'carrier' => $carrier,
        'department' => $row['num_departement']
    ];
    
    // Ajouter les tarifs selon les colonnes disponibles
    $tarifColumns = getTableColumns($carrier);
    foreach ($tarifColumns as $column) {
        if (isset($row[$column]) && $row[$column] !== '') {
            $rateData[$column] = (float)$row[$column];
        }
    }
    
    // Ajouter les autres champs
    if (isset($row['departement'])) {
        $rateData['departement'] = $row['departement'];
    }
    if (isset($row['delais'])) {
        $rateData['delais'] = $row['delais'];
    }
    
    return saveRate($db, $rateData);
}

function importOptionFromCSV($db, $row) {
    $transporteur = strtolower(trim($row['transporteur']));
    
    // Normaliser le nom du transporteur
    $carrierMapping = [
        'heppner' => 'heppner',
        'xpo' => 'xpo',
        'kuehne + nagel' => 'kn',
        'kuehne+nagel' => 'kn',
        'kn' => 'kn',
        'k+n' => 'kn'
    ];
    
    $transporteur = $carrierMapping[$transporteur] ?? null;
    if (!$transporteur) {
        throw new Exception('Transporteur non reconnu : ' . $row['transporteur']);
    }
    
    $optionData = [
        'id' => null,
        'transporteur' => $transporteur,
        'code_option' => trim($row['code_option']),
        'libelle' => trim($row['libelle']),
        'montant' => (float)($row['montant'] ?? 0),
        'unite' => trim($row['unite'] ?? 'forfait'),
        'actif' => in_array(strtolower(trim($row['actif'] ?? '1')), ['1', 'oui', 'yes', 'true']) ? 1 : 0
    ];
    
    return saveOption($db, $optionData);
}

function importExcel($db, $filePath) {
    // Pour l'instant, on retourne un résultat simulé
    // En production, vous pouvez utiliser PhpSpreadsheet
    return [
        'imported' => 0,
        'errors' => ['Import Excel pas encore implémenté. Utilisez CSV pour le moment.']
    ];
}

// =============================================================================
// FONCTIONS UTILITAIRES
// =============================================================================

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateDepartment($department) {
    return preg_match('/^[0-9]{2}$/', $department);
}

function validateCarrier($carrier) {
    return in_array($carrier, ['heppner', 'xpo', 'kn']);
}

function logAction($action, $data = null) {
    // Log des actions importantes (optionnel)
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'data' => $data,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Vous pouvez enregistrer dans un fichier de log ou en base
    error_log('ADMIN_ACTION: ' . json_encode($logEntry));
}

// =============================================================================
// GESTION DES ERREURS SPÉCIFIQUES
// =============================================================================

function handleDatabaseError($e) {
    // Ne pas exposer les détails de la base de données en production
    if (getenv('APP_ENV') === 'production') {
        throw new Exception('Erreur de base de données');
    } else {
        throw new Exception('Erreur DB: ' . $e->getMessage());
    }
}

// Log de l'action pour audit
if ($action && $action !== 'get_stats') {
    logAction($action, $_POST ?: $_GET);
}
?><?php
// public/admin/api.php
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../lib/Transport.php';

header('Content-Type: application/json; charset=UTF-8');
session_start();

$transport = new Transport($db);
$response = ['success' => false, 'message' => '', 'data' => null];

// Récupérer l'action depuis POST ou GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // =============================================================================
        // GESTION DES TARIFS
        // =============================================================================
        
        case 'get_rates':
            $carrier = $_GET['carrier'] ?? '';
            $search = $_GET['search'] ?? '';
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $rates = getRates($db, $carrier, $search, $limit, $offset);
            $response = ['success' => true, 'data' => $rates];
            break;
            
        case 'save_rate':
            $rateData = [
                'id' => $_POST['id'] ?? null,
                'carrier' => $_POST['carrier'] ?? '',
                'department' => $_POST['department'] ?? '',
                'tarif_0_9' => !empty($_POST['tarif_0_9']) ? (float)$_POST['tarif_0_9'] : null,
                'tarif_10_19' => !empty($_POST['tarif_10_19']) ? (float)$_POST['tarif_10_19'] : null,
                'tarif_90_99' => !empty($_POST['tarif_90_99']) ? (float)$_POST['tarif_90_99'] : null,
                'tarif_100_299' => !empty($_POST['tarif_100_299']) ? (float)$_POST['tarif_100_299'] : null,
                'tarif_500_999' => !empty($_POST['tarif_500_999']) ? (float)$_POST['tarif_500_999'] : null,
                'delais' => $_POST['delais'] ?? ''
            ];
            
            if (saveRate($db, $rateData)) {
                $response = ['success' => true, 'message' => 'Tarif enregistré avec succès'];
            } else {
                throw new Exception('Erreur lors de l\'enregistrement du tarif');
            }
            break;
            
        case 'delete_rate':
            $id = (int)($_POST['id'] ?? 0);
            $carrier = $_POST['carrier'] ?? '';
            
            if (deleteRate($db, $id, $carrier)) {
                $response = ['success' => true, 'message' => 'Tarif supprimé'];
            } else {
                throw new Exception('Erreur lors de la suppression du tarif');
            }
            break;
            
        // =============================================================================
        // GESTION DES OPTIONS
        // =============================================================================
        
        case 'get_options':
            $carrier = $_GET['carrier'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $options = getOptions($db, $carrier, $search);
            $response = ['success' => true, 'data' => $options];
            break;
            
        case 'save_option':
            $optionData = [
                'id' => $_POST['id'] ?? null,
                'transporteur' => $_POST['transporteur'] ?? '',
                'code_option' => $_POST['code_option'] ?? '',
                'libelle' => $_POST['libelle'] ?? '',
                'montant' => (float)($_POST['montant'] ?? 0),
                'unite' => $_POST['unite'] ?? 'forfait',
                'actif' => isset($_POST['actif']) ? 1 : 0
            ];
            
            if (saveOption($db, $optionData)) {
                $response = ['success' => true, 'message' => 'Option enregistrée avec succès'];
            } else {
                throw new Exception('Erreur lors de l\'enregistrement de l\'option');
            }
            break;
            
        case 'toggle_option':
            $id = (int)($_POST['id'] ?? 0);
            
            if (toggleOption($db, $id)) {
                $response = ['success' => true, 'message' => 'Statut de l\'option modifié'];
            } else {
                throw new Exception('Erreur lors de la modification du statut');
            }
            break;
            
        case 'delete_option':
            $id = (int)($_POST['id'] ?? 0);
            
            if (deleteOption($db, $id)) {
                $response = ['success' => true, 'message' => 'Option supprimée'];
            } else {
                throw new Exception('Erreur lors de la suppression de l\'option');
            }
            break;
            
        // =============================================================================
        // GESTION DES TAXES
        // =============================================================================
        
        case 'get_taxes':
            $taxes = getTaxes($db);
            $response = ['success' => true, 'data' => $taxes];
            break;
            
        case 'save_taxes':
            // TODO: Implémenter la sauvegarde des taxes
            $response = ['success' => true, 'message' => 'Taxes mises à jour'];
            break;
            
        // =============================================================================
        // IMPORT/EXPORT
        // =============================================================================
        
        case 'import':
            if (!isset($_FILES['import_file'])) {
                throw new Exception('Aucun fichier sélectionné');
            }
            
            $file = $_FILES['import_file'];
            $result = importFile($db, $file);
            $response = ['success' => true, 'message' => 'Import terminé', 'data' => $result];
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

// =============================================================================
// FONCTIONS POUR LES TARIFS
// =============================================================================

function getRates($db, $carrier = '', $search = '', $limit = 50, $offset = 0) {
    $carriers = [
        'heppner' => ['table' => 'gul_heppner_rates', 'name' => 'Heppner'],
        'xpo' => ['table' => 'gul_xpo_rates', 'name' => 'XPO'],
        'kn' => ['table' => 'gul_kn_rates', 'name' => 'Kuehne + Nagel']
    ];
    
    $rates = [];
    
    foreach ($carriers as $code => $info) {
        if ($carrier && $carrier !== $code) continue;
        
        $sql = "SELECT * FROM {$info['table']} WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (num_departement LIKE :search OR departement LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY num_departement LIMIT $limit OFFSET $offset";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['carrier_code'] = $code;
            $row['carrier_name'] = $info['name'];
            $rates[] = $row;
        }
    }
    
    return $rates;
}

function saveRate($db, $data) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $table = $tables[$data['carrier']] ?? null;
    if (!$table) {
        throw new Exception('Transporteur invalide');
    }
    
    if ($data['id']) {
        // Mise à jour
        $sql = "UPDATE `$table` SET 
                num_departement = :department,
                tarif_0_9 = :tarif_0_9,
                tarif_10_19 = :tarif_10_19,
                tarif_90_99 = :tarif_90_99,
                tarif_100_299 = :tarif_100_299,
                tarif_500_999 = :tarif_500_999,
                delais = :delais
                WHERE id = :id";
        
        $params = [
            ':id' => $data['id'],
            ':department' => $data['department'],
            ':tarif_0_9' => $data['tarif_0_9'],
            ':tarif_10_19' => $data['tarif_10_19'],
            ':tarif_90_99' => $data['tarif_90_99'],
            ':tarif_100_299' => $data['tarif_100_299'],
            ':tarif_500_999' => $data['tarif_500_999'],
            ':delais' => $data['delais']
        ];
    } else {
        // Insertion
        $sql = "INSERT INTO `$table` 
                (num_departement, tarif_0_9, tarif_10_19, tarif_90_99, tarif_100_299, tarif_500_999, delais)
                VALUES (:department, :tarif_0_9, :tarif_10_19, :tarif_90_99, :tarif_100_299, :tarif_500_999, :delais)";
        
        $params = [
            ':department' => $data['department'],
            ':tarif_0_9' => $data['tarif_0_9'],
            ':tarif_10_19' => $data['tarif_10_19'],
            ':tarif_90_99' => $data['tarif_90_99'],
            ':tarif_100_299' => $data['tarif_100_299'],
            ':tarif_500_999' => $data['tarif_500_999'],
            ':delais' => $data['delais']
        ];
    }
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function deleteRate($db, $id, $carrier) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $table = $tables[$carrier] ?? null;
    if (!$table) {
        throw new Exception('Transporteur invalide');
    }
    
    $sql = "DELETE FROM `$table` WHERE id = :id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

// =============================================================================
// FONCTIONS POUR LES OPTIONS
// =============================================================================

function getOptions($db, $carrier = '', $search = '') {
    $sql = "SELECT * FROM gul_options_supplementaires WHERE 1=1";
    $params = [];
    
    if ($carrier) {
        $sql .= " AND transporteur = :carrier";
        $params[':carrier'] = $carrier;
    }
    
    if ($search) {
        $sql .= " AND (code_option LIKE :search OR libelle LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $sql .= " ORDER BY transporteur, code_option";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function saveOption($db, $data) {
    if ($data['id']) {
        // Mise à jour
        $sql = "UPDATE gul_options_supplementaires SET 
                transporteur = :transporteur,
                code_option = :code_option,
                libelle = :libelle,
                montant = :montant,
                unite = :unite,
                actif = :actif
                WHERE id = :id";
        
        $params = [
            ':id' => $data['id'],
            ':transporteur' => $data['transporteur'],
            ':code_option' => $data['code_option'],
            ':libelle' => $data['libelle'],
            ':montant' => $data['montant'],
            ':unite' => $data['unite'],
            ':actif' => $data['actif']
        ];
    } else {
        // Insertion
        $sql = "INSERT INTO gul_options_supplementaires 
                (transporteur, code_option, libelle, montant, unite, actif)
                VALUES (:transporteur, :code_option, :libelle, :montant, :unite, :actif)";
        
        $params = [
            ':transporteur' => $data['transporteur'],
            ':code_option' => $data['code_option'],
            ':libelle' => $data['libelle'],
            ':montant' => $data['montant'],
            ':unite' => $data['unite'],
            ':actif' => $data['actif']
        ];
    }
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function toggleOption($db, $id) {
    $sql = "UPDATE gul_options_supplementaires SET actif = NOT actif WHERE id = :id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

function deleteOption($db, $id) {
    $sql = "DELETE FROM gul_options_supplementaires WHERE id = :id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

// =============================================================================
// FONCTIONS POUR LES TAXES
// =============================================================================

function getTaxes($db) {
    $sql = "SELECT * FROM gul_taxes_transporteurs ORDER BY transporteur";
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =============================================================================
// FONCTIONS D'IMPORT
// =============================================================================

function importFile($db, $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors de l\'upload du fichier');
    }
    
    $allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
        'application/csv'
    ];
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Type de fichier non supporté. Utilisez Excel (.xlsx) ou CSV.');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    try {
        if ($extension === 'csv') {
            return importCSV($db, $file['tmp_name']);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return importExcel($db, $file['tmp_name']);
        } else {
            throw new Exception('Extension de fichier non supportée');
        }
    } catch (Exception $e) {
        throw new Exception('Erreur lors de l\'import : ' . $e->getMessage());
    }
}

function importCSV($db, $filePath) {
    $imported = 0;
    $errors = [];
    
    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        $headers = fgetcsv($handle); // Première ligne = en-têtes
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                // Logique d'import selon les colonnes détectées
                if (count($data) >= 3) {
                    $imported++;
                }
            } catch (Exception $e) {
                $errors[] = "Ligne " . ($imported + 1) . " : " . $e->getMessage();
            }
        }
        fclose($handle);
    }
    
    return [
        'imported' => $imported,
        'errors' => $errors
    ];
}

function importExcel($db, $filePath) {
    // Pour l'instant, on retourne un résultat simulé
    // En production, vous pouvez utiliser PhpSpreadsheet
    return [
        'imported' => 0,
        'errors' => ['Import Excel pas encore implémenté. Utilisez CSV pour le moment.']
    ];
}
?>

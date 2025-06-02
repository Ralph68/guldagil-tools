<?php
// public/admin/templates/import.php - Import CSV sécurisé et robuste
require __DIR__ . '/../../../config.php';
require __DIR__ . '/../auth.php';

// Vérification des permissions
checkAdminPermission('import');

header('Content-Type: application/json; charset=UTF-8');

// Validation de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Validation du token CSRF
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
    exit;
}

$type = $_POST['type'] ?? '';
$mode = $_POST['mode'] ?? 'validate'; // validate, preview, import

// Types d'import autorisés
$allowedTypes = ['rates', 'options'];
if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Type d\'import non supporté']);
    exit;
}

// Vérification du fichier uploadé
if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    $error = 'Erreur lors de l\'upload du fichier';
    if (isset($_FILES['import_file']['error'])) {
        $error .= ' (Code: ' . $_FILES['import_file']['error'] . ')';
    }
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

$file = $_FILES['import_file'];

// Validation du fichier
$validationResult = validateUploadedFile($file);
if (!$validationResult['valid']) {
    echo json_encode(['success' => false, 'error' => $validationResult['error']]);
    exit;
}

try {
    // Traitement selon le mode
    switch ($mode) {
        case 'validate':
            $result = validateImportFile($file, $type);
            break;
        case 'preview':
            $result = previewImportData($file, $type);
            break;
        case 'import':
            $result = performImport($file, $type, $db);
            break;
        default:
            throw new Exception('Mode d\'import invalide');
    }
    
    // Log de l'action
    logAdminAction('import_' . $mode, [
        'type' => $type,
        'file' => $file['name'],
        'size' => $file['size'],
        'success' => $result['success']
    ]);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur import $type/$mode: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du traitement : ' . $e->getMessage()
    ]);
}

/**
 * Valide le fichier uploadé
 */
function validateUploadedFile($file) {
    // Taille maximum : 5MB
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'Fichier trop volumineux (max 5MB)'];
    }
    
    // Extensions autorisées
    $allowedExtensions = ['csv', 'xlsx', 'xls'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Extension de fichier non autorisée (.csv, .xlsx, .xls uniquement)'];
    }
    
    // Type MIME
    $allowedMimes = [
        'text/csv',
        'text/plain',
        'application/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        return ['valid' => false, 'error' => 'Type de fichier non autorisé'];
    }
    
    return ['valid' => true];
}

/**
 * Validation initiale du fichier d'import
 */
function validateImportFile($file, $type) {
    $data = parseCSVFile($file);
    
    if (!$data['success']) {
        return $data;
    }
    
    $rows = $data['data'];
    $stats = [
        'total_rows' => count($rows),
        'valid_rows' => 0,
        'invalid_rows' => 0,
        'warnings' => [],
        'errors' => [],
        'sample_data' => array_slice($rows, 0, 5) // Aperçu des 5 premières lignes
    ];
    
    // Validation selon le type
    switch ($type) {
        case 'rates':
            $validation = validateRatesData($rows);
            break;
        case 'options':
            $validation = validateOptionsData($rows);
            break;
        default:
            return ['success' => false, 'error' => 'Type de validation non supporté'];
    }
    
    $stats = array_merge($stats, $validation);
    
    return [
        'success' => true,
        'mode' => 'validation',
        'type' => $type,
        'stats' => $stats,
        'can_import' => $stats['invalid_rows'] === 0
    ];
}

/**
 * Aperçu des données d'import
 */
function previewImportData($file, $type) {
    $data = parseCSVFile($file);
    
    if (!$data['success']) {
        return $data;
    }
    
    $rows = $data['data'];
    $preview = [];
    $conflicts = [];
    
    // Analyser les 20 premières lignes pour l'aperçu
    $previewRows = array_slice($rows, 0, 20);
    
    foreach ($previewRows as $index => $row) {
        $analysis = analyzeRowForPreview($row, $type, $index + 1);
        $preview[] = $analysis;
        
        if ($analysis['has_conflict']) {
            $conflicts[] = $analysis;
        }
    }
    
    return [
        'success' => true,
        'mode' => 'preview',
        'type' => $type,
        'preview' => $preview,
        'conflicts' => $conflicts,
        'total_rows' => count($rows),
        'preview_rows' => count($previewRows)
    ];
}

/**
 * Exécution de l'import
 */
function performImport($file, $type, $db) {
    $data = parseCSVFile($file);
    
    if (!$data['success']) {
        return $data;
    }
    
    $rows = $data['data'];
    $results = [
        'total_rows' => count($rows),
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'details' => []
    ];
    
    // Transaction pour assurer la cohérence
    $db->beginTransaction();
    
    try {
        foreach ($rows as $index => $row) {
            $lineNumber = $index + 1;
            
            switch ($type) {
                case 'rates':
                    $result = importRateRow($db, $row, $lineNumber);
                    break;
                case 'options':
                    $result = importOptionRow($db, $row, $lineNumber);
                    break;
                default:
                    throw new Exception('Type d\'import non supporté');
            }
            
            // Comptabiliser les résultats
            if ($result['success']) {
                if ($result['action'] === 'insert') {
                    $results['imported']++;
                } elseif ($result['action'] === 'update') {
                    $results['updated']++;
                }
            } else {
                $results['skipped']++;
                $results['errors'][] = "Ligne $lineNumber: " . $result['error'];
            }
            
            $results['details'][] = array_merge($result, ['line' => $lineNumber]);
        }
        
        // Valider la transaction si pas trop d'erreurs
        $errorRate = count($results['errors']) / $results['total_rows'];
        if ($errorRate > 0.5) {
            throw new Exception('Trop d\'erreurs dans l\'import (' . round($errorRate * 100) . '%)');
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'mode' => 'import',
            'type' => $type,
            'results' => $results
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Parse un fichier CSV
 */
function parseCSVFile($file) {
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'Impossible d\'ouvrir le fichier'];
    }
    
    $data = [];
    $headers = null;
    $lineNumber = 0;
    
    // Détecter l'encodage
    $content = file_get_contents($file['tmp_name']);
    if (strpos($content, "\xEF\xBB\xBF") === 0) {
        // Enlever le BOM UTF-8
        $content = substr($content, 3);
        file_put_contents($file['tmp_name'], $content);
    }
    
    rewind($handle);
    
    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        $lineNumber++;
        
        // Ignorer les lignes de commentaire
        if (isset($row[0]) && (strpos($row[0], '#') === 0 || trim($row[0]) === '')) {
            continue;
        }
        
        // La première ligne non-commentaire devient l'en-tête
        if ($headers === null) {
            $headers = array_map('trim', $row);
            continue;
        }
        
        // Associer les données aux en-têtes
        $rowData = [];
        foreach ($headers as $index => $header) {
            $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : '';
        }
        
        $data[] = $rowData;
    }
    
    fclose($handle);
    
    if (empty($data)) {
        return ['success' => false, 'error' => 'Fichier vide ou format invalide'];
    }
    
    return ['success' => true, 'data' => $data, 'headers' => $headers];
}

/**
 * Validation des données de tarifs
 */
function validateRatesData($rows) {
    $stats = ['valid_rows' => 0, 'invalid_rows' => 0, 'warnings' => [], 'errors' => []];
    $requiredFields = ['transporteur', 'num_departement'];
    $validCarriers = ['heppner', 'xpo', 'kn'];
    
    foreach ($rows as $index => $row) {
        $lineNumber = $index + 1;
        $isValid = true;
        
        // Vérifier les champs obligatoires
        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                $stats['errors'][] = "Ligne $lineNumber: Champ '$field' manquant";
                $isValid = false;
            }
        }
        
        // Validation transporteur
        if (!empty($row['transporteur']) && !in_array(strtolower($row['transporteur']), $validCarriers)) {
            $stats['errors'][] = "Ligne $lineNumber: Transporteur invalide (autorisés: " . implode(', ', $validCarriers) . ")";
            $isValid = false;
        }
        
        // Validation département
        if (!empty($row['num_departement']) && !preg_match('/^[0-9]{2,3}$/', $row['num_departement'])) {
            $stats['errors'][] = "Ligne $lineNumber: Numéro de département invalide";
            $isValid = false;
        }
        
        // Validation des tarifs (au moins un tarif doit être renseigné)
        $hasTarif = false;
        $tarifFields = array_filter(array_keys($row), function($key) {
            return strpos($key, 'tarif_') === 0;
        });
        
        foreach ($tarifFields as $field) {
            if (!empty($row[$field])) {
                if (!is_numeric($row[$field]) || $row[$field] < 0) {
                    $stats['errors'][] = "Ligne $lineNumber: Tarif '$field' invalide";
                    $isValid = false;
                } else {
                    $hasTarif = true;
                }
            }
        }
        
        if (!$hasTarif) {
            $stats['warnings'][] = "Ligne $lineNumber: Aucun tarif renseigné";
        }
        
        if ($isValid) {
            $stats['valid_rows']++;
        } else {
            $stats['invalid_rows']++;
        }
    }
    
    return $stats;
}

/**
 * Validation des données d'options
 */
function validateOptionsData($rows) {
    $stats = ['valid_rows' => 0, 'invalid_rows' => 0, 'warnings' => [], 'errors' => []];
    $requiredFields = ['transporteur', 'code_option', 'libelle', 'montant', 'unite'];
    $validCarriers = ['heppner', 'xpo', 'kn'];
    $validUnits = ['forfait', 'palette', 'pourcentage'];
    
    foreach ($rows as $index => $row) {
        $lineNumber = $index + 1;
        $isValid = true;
        
        // Vérifier les champs obligatoires
        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                $stats['errors'][] = "Ligne $lineNumber: Champ '$field' manquant";
                $isValid = false;
            }
        }
        
        // Validation transporteur
        if (!empty($row['transporteur']) && !in_array(strtolower($row['transporteur']), $validCarriers)) {
            $stats['errors'][] = "Ligne $lineNumber: Transporteur invalide";
            $isValid = false;
        }
        
        // Validation montant
        if (!empty($row['montant']) && (!is_numeric($row['montant']) || $row['montant'] < 0)) {
            $stats['errors'][] = "Ligne $lineNumber: Montant invalide";
            $isValid = false;
        }
        
        // Validation unité
        if (!empty($row['unite']) && !in_array(strtolower($row['unite']), $validUnits)) {
            $stats['errors'][] = "Ligne $lineNumber: Unité invalide (autorisées: " . implode(', ', $validUnits) . ")";
            $isValid = false;
        }
        
        // Validation statut actif
        if (isset($row['actif']) && !in_array($row['actif'], ['0', '1', 'true', 'false'])) {
            $stats['warnings'][] = "Ligne $lineNumber: Statut actif non reconnu, 1 sera utilisé par défaut";
        }
        
        if ($isValid) {
            $stats['valid_rows']++;
        } else {
            $stats['invalid_rows']++;
        }
    }
    
    return $stats;
}

/**
 * Analyse une ligne pour l'aperçu
 */
function analyzeRowForPreview($row, $type, $lineNumber) {
    $analysis = [
        'line_number' => $lineNumber,
        'data' => $row,
        'action' => 'insert',
        'has_conflict' => false,
        'warnings' => [],
        'status' => 'valid'
    ];
    
    // TODO: Vérifier si l'enregistrement existe déjà
    // Pour l'instant, on considère tout comme nouveau
    
    return $analysis;
}

/**
 * Import d'une ligne de tarif
 */
function importRateRow($db, $row, $lineNumber) {
    try {
        $carrier = strtolower(trim($row['transporteur']));
        $department = trim($row['num_departement']);
        
        // Déterminer la table
        $tables = [
            'heppner' => 'gul_heppner_rates',
            'xpo' => 'gul_xpo_rates',
            'kn' => 'gul_kn_rates'
        ];
        
        if (!isset($tables[$carrier])) {
            return ['success' => false, 'error' => 'Transporteur invalide'];
        }
        
        $table = $tables[$carrier];
        
        // Vérifier si l'enregistrement existe
        $stmt = $db->prepare("SELECT id FROM `$table` WHERE num_departement = ?");
        $stmt->execute([$department]);
        $existing = $stmt->fetch();
        
        // Préparer les données
        $data = [
            'num_departement' => $department,
            'departement' => $row['departement'] ?? '',
            'delais' => $row['delais'] ?? ''
        ];
        
        // Ajouter les tarifs selon le transporteur
        $tarifFields = getTarifFieldsForCarrier($carrier);
        foreach ($tarifFields as $field) {
            if (isset($row[$field]) && $row[$field] !== '') {
                $data[$field] = (float)$row[$field];
            }
        }
        
        if ($existing) {
            // Mise à jour
            $setParts = [];
            $params = [];
            foreach ($data as $field => $value) {
                if ($field !== 'num_departement') {
                    $setParts[] = "`$field` = ?";
                    $params[] = $value;
                }
            }
            $params[] = $department;
            
            $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE num_departement = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'action' => 'update'];
        } else {
            // Insertion
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO `$table` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($data));
            
            return ['success' => true, 'action' => 'insert'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Import d'une ligne d'option
 */
function importOptionRow($db, $row, $lineNumber) {
    try {
        $transporteur = strtolower(trim($row['transporteur']));
        $codeOption = trim($row['code_option']);
        
        // Vérifier si l'option existe
        $stmt = $db->prepare("SELECT id FROM gul_options_supplementaires WHERE transporteur = ? AND code_option = ?");
        $stmt->execute([$transporteur, $codeOption]);
        $existing = $stmt->fetch();
        
        // Préparer les données
        $data = [
            'transporteur' => $transporteur,
            'code_option' => $codeOption,
            'libelle' => $row['libelle'],
            'montant' => (float)$row['montant'],
            'unite' => strtolower($row['unite']),
            'actif' => in_array(strtolower($row['actif'] ?? '1'), ['1', 'true']) ? 1 : 0
        ];
        
        if ($existing) {
            // Mise à jour
            $sql = "UPDATE gul_options_supplementaires SET libelle = ?, montant = ?, unite = ?, actif = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['libelle'],
                $data['montant'],
                $data['unite'],
                $data['actif'],
                $existing['id']
            ]);
            
            return ['success' => true, 'action' => 'update'];
        } else {
            // Insertion
            $sql = "INSERT INTO gul_options_supplementaires (transporteur, code_option, libelle, montant, unite, actif) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['transporteur'],
                $data['code_option'],
                $data['libelle'],
                $data['montant'],
                $data['unite'],
                $data['actif']
            ]);
            
            return ['success' => true, 'action' => 'insert'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Récupère les champs de tarif pour un transporteur
 */
function getTarifFieldsForCarrier($carrier) {
    $commonFields = [
        'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
        'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
        'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999'
    ];
    
    if ($carrier === 'xpo') {
        return array_merge($commonFields, ['tarif_0_99', 'tarif_100_499', 'tarif_2000_2999']);
    }
    
    return $commonFields;
}
?>

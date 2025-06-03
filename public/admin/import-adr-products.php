<?php
// public/admin/import-adr-products.php - Import CSV produits ADR
require __DIR__ . '/../../config.php';
require __DIR__ . '/auth.php';

checkAdminPermission('import_adr');
logAdminAction('import_adr_products', ['file' => $_FILES['csv_file']['name'] ?? 'unknown']);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Validation du fichier
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors de l\'upload du fichier');
    }
    
    $file = $_FILES['csv_file'];
    
    // Validation taille (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Fichier trop volumineux (maximum 10MB)');
    }
    
    // Validation extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['csv', 'txt'])) {
        throw new Exception('Format de fichier non supporté (.csv uniquement)');
    }
    
    $mode = $_POST['mode'] ?? 'preview';
    
    switch ($mode) {
        case 'preview':
            $result = previewADRData($file, $db);
            break;
        case 'import':
            $result = importADRData($file, $db);
            break;
        default:
            throw new Exception('Mode non supporté');
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur import ADR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Aperçu des données ADR avant import
 */
function previewADRData($file, $db) {
    $data = parseADRCSV($file);
    
    if (!$data['success']) {
        return $data;
    }
    
    $rows = $data['rows'];
    $stats = [
        'total_rows' => count($rows),
        'adr_products' => 0,
        'non_adr_products' => 0,
        'conflicts' => [],
        'warnings' => [],
        'preview' => []
    ];
    
    // Analyser les 20 premières lignes
    $previewRows = array_slice($rows, 0, 20);
    
    foreach ($previewRows as $index => $row) {
        $analysis = analyzeADRRow($row, $db, $index + 1);
        $stats['preview'][] = $analysis;
        
        if ($analysis['is_adr']) {
            $stats['adr_products']++;
        } else {
            $stats['non_adr_products']++;
        }
        
        if (!empty($analysis['conflicts'])) {
            $stats['conflicts'] = array_merge($stats['conflicts'], $analysis['conflicts']);
        }
        
        if (!empty($analysis['warnings'])) {
            $stats['warnings'] = array_merge($stats['warnings'], $analysis['warnings']);
        }
    }
    
    return [
        'success' => true,
        'mode' => 'preview',
        'stats' => $stats,
        'can_import' => count($stats['conflicts']) === 0
    ];
}

/**
 * Import définitif des données ADR
 */
function importADRData($file, $db) {
    $data = parseADRCSV($file);
    
    if (!$data['success']) {
        return $data;
    }
    
    $rows = $data['rows'];
    $results = [
        'total_rows' => count($rows),
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'adr_count' => 0,
        'non_adr_count' => 0
    ];
    
    // Transaction pour cohérence
    $db->beginTransaction();
    
    try {
        foreach ($rows as $index => $row) {
            $lineNumber = $index + 1;
            $result = importADRRow($db, $row, $lineNumber);
            
            if ($result['success']) {
                if ($result['action'] === 'insert') {
                    $results['imported']++;
                } else {
                    $results['updated']++;
                }
                
                if ($result['is_adr']) {
                    $results['adr_count']++;
                } else {
                    $results['non_adr_count']++;
                }
            } else {
                $results['skipped']++;
                $results['errors'][] = "Ligne $lineNumber: " . $result['error'];
            }
        }
        
        // Valider si taux d'erreur acceptable
        $errorRate = count($results['errors']) / $results['total_rows'];
        if ($errorRate > 0.3) {
            throw new Exception("Trop d'erreurs dans l'import (" . round($errorRate * 100) . "%)");
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'mode' => 'import',
            'results' => $results
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Parse le CSV ADR avec gestion spécifique du format Guldagil
 */
function parseADRCSV($file) {
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'Impossible d\'ouvrir le fichier'];
    }
    
    // Détecter l'encodage et BOM
    $content = file_get_contents($file['tmp_name']);
    
    // Enlever BOM UTF-8 si présent
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
        file_put_contents($file['tmp_name'], $content);
    }
    
    // Convertir de Windows-1252 vers UTF-8 si nécessaire
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        file_put_contents($file['tmp_name'], $content);
    }
    
    rewind($handle);
    
    $rows = [];
    $headers = null;
    $lineNumber = 0;
    
    while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
        $lineNumber++;
        
        // Ignorer les lignes vides
        if (empty(array_filter($row))) {
            continue;
        }
        
        // La première ligne = en-têtes
        if ($headers === null) {
            $headers = array_map('trim', $row);
            continue;
        }
        
        // Associer les données aux en-têtes
        $rowData = [];
        foreach ($headers as $index => $header) {
            $value = isset($row[$index]) ? trim($row[$index]) : '';
            
            // Nettoyer les valeurs spéciales Excel
            if (in_array($value, ['#N/A', '#REF!', ''])) {
                $value = null;
            }
            
            $rowData[$header] = $value;
        }
        
        $rows[] = $rowData;
    }
    
    fclose($handle);
    
    if (empty($rows)) {
        return ['success' => false, 'error' => 'Aucune donnée trouvée dans le fichier'];
    }
    
    return ['success' => true, 'rows' => $rows, 'headers' => $headers];
}

/**
 * Analyse une ligne ADR
 */
function analyzeADRRow($row, $db, $lineNumber) {
    $codeProduit = $row['Code produit'] ?? '';
    $numeroUN = $row['UN'] ?? '';
    
    $analysis = [
        'line_number' => $lineNumber,
        'code_produit' => $codeProduit,
        'is_adr' => !empty($numeroUN) && $numeroUN !== '#N/A',
        'conflicts' => [],
        'warnings' => [],
        'action' => 'insert',
        'status' => 'valid'
    ];
    
    // Vérifier si le produit existe déjà
    if (!empty($codeProduit)) {
        $stmt = $db->prepare("SELECT id FROM gul_adr_products WHERE code_produit = ?");
        $stmt->execute([$codeProduit]);
        if ($stmt->fetch()) {
            $analysis['action'] = 'update';
            $analysis['warnings'][] = "Produit existant - sera mis à jour";
        }
    } else {
        $analysis['conflicts'][] = "Code produit manquant";
        $analysis['status'] = 'invalid';
    }
    
    // Validation numéro UN
    if ($analysis['is_adr']) {
        if (!preg_match('/^\d{4}$/', $numeroUN)) {
            $analysis['conflicts'][] = "Numéro UN invalide: $numeroUN";
            $analysis['status'] = 'invalid';
        }
    }
    
    // Validation catégorie transport
    $categorieTransport = $row['CAT TRANS'] ?? '';
    if (!empty($categorieTransport) && !in_array($categorieTransport, ['0', '1', '2', '3', '4'])) {
        $analysis['warnings'][] = "Catégorie transport non standard: $categorieTransport";
    }
    
    return $analysis;
}

/**
 * Importe une ligne ADR
 */
function importADRRow($db, $row, $lineNumber) {
    try {
        $codeProduit = $row['Code produit'] ?? '';
        
        if (empty($codeProduit)) {
            return ['success' => false, 'error' => 'Code produit manquant'];
        }
        
        // Vérifier si existe
        $stmt = $db->prepare("SELECT id FROM gul_adr_products WHERE code_produit = ?");
        $stmt->execute([$codeProduit]);
        $existing = $stmt->fetch();
        
        // Préparer les données
        $data = [
            'code_produit' => $codeProduit,
            'corde_article_ferme' => ($row['Corde article fermé'] === 'x') ? 'x' : '',
            'nom_produit' => $row['Nom Produit'] ?: null,
            'poids_contenant' => $row['POIDS / CONT'] ?: null,
            'type_contenant' => $row['CONTENANT'] ?: null,
            'numero_un' => (!empty($row['UN']) && $row['UN'] !== '#N/A') ? $row['UN'] : null,
            'nom_description_un' => $row['NOM ET DESCRIPTION'] ?: null,
            'nom_technique' => $row['NOM TECHNIQUE'] ?: null,
            'groupe_emballage' => $row['GR EMBAL'] ?: null,
            'numero_etiquette' => $row['N° D\'ETIQT'] ?: null,
            'categorie_transport' => $row['CAT TRANS'] ?: null,
            'code_tunnel' => $row['CODE TUNNEL'] ?: null,
            'danger_environnement' => in_array($row['DANGER ENV'], ['OUI', 'NON']) ? $row['DANGER ENV'] : '',
            'colonne_3' => $row['3'] ?: null
        ];
        
        $isADR = !empty($data['numero_un']);
        
        if ($existing) {
            // Mise à jour
            $setParts = [];
            $params = [];
            foreach ($data as $field => $value) {
                if ($field !== 'code_produit') {
                    $setParts[] = "`$field` = ?";
                    $params[] = $value;
                }
            }
            $params[] = $codeProduit;
            
            $sql = "UPDATE gul_adr_products SET " . implode(', ', $setParts) . " WHERE code_produit = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'action' => 'update', 'is_adr' => $isADR];
        } else {
            // Insertion
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO gul_adr_products (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($data));
            
            return ['success' => true, 'action' => 'insert', 'is_adr' => $isADR];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>

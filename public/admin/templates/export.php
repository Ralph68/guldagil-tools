<?php
// public/admin/templates/export.php - Version optimisée et corrigée
require __DIR__ . '/../../../config.php';
require __DIR__ . '/../auth.php';

// Vérification des permissions
checkAdminPermission('export');
logAdminAction('export_data', ['type' => $_GET['type'] ?? 'all', 'format' => $_GET['format'] ?? 'csv']);

$type = $_GET['type'] ?? 'all';
$format = $_GET['format'] ?? 'csv';

// Validation des paramètres
$allowedTypes = ['all', 'rates', 'options', 'taxes'];
$allowedFormats = ['csv', 'json', 'excel'];

if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    die('Type d\'export non supporté');
}

if (!in_array($format, $allowedFormats)) {
    http_response_code(400);
    die('Format d\'export non supporté');
}

try {
    switch ($format) {
        case 'csv':
            exportCSV($db, $type);
            break;
        case 'json':
            exportJSON($db, $type);
            break;
        case 'excel':
            exportExcel($db, $type);
            break;
    }
} catch (Exception $e) {
    error_log("Erreur export: " . $e->getMessage());
    http_response_code(500);
    echo 'Erreur lors de l\'export : ' . htmlspecialchars($e->getMessage());
}

/**
 * Export au format CSV
 */
function exportCSV($db, $type) {
    $filename = generateFilename($type, 'csv');
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    
    $output = fopen('php://output', 'w');
    
    // BOM UTF-8 pour Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    switch ($type) {
        case 'rates':
            exportRatesCSV($db, $output);
            break;
        case 'options':
            exportOptionsCSV($db, $output);
            break;
        case 'taxes':
            exportTaxesCSV($db, $output);
            break;
        case 'all':
        default:
            exportAllCSV($db, $output);
            break;
    }
    
    fclose($output);
}

/**
 * Export tarifs au format CSV
 */
function exportRatesCSV($db, $output) {
    // En-têtes avec colonnes harmonisées
    fputcsv($output, [
        'Transporteur',
        'Code transporteur',
        'Département',
        'Nom département',
        'Délai',
        'Tarif 0-9kg',
        'Tarif 10-19kg',
        'Tarif 20-29kg',
        'Tarif 30-39kg',
        'Tarif 40-49kg',
        'Tarif 50-59kg',
        'Tarif 60-69kg',
        'Tarif 70-79kg',
        'Tarif 80-89kg',
        'Tarif 90-99kg',
        'Tarif 100-299kg',
        'Tarif 300-499kg',
        'Tarif 500-999kg',
        'Tarif 1000-1999kg',
        'Tarif 2000-2999kg'
    ]);
    
    // Données Heppner
    exportCarrierRates($db, $output, 'heppner', 'gul_heppner_rates', 'Heppner');
    
    // Données XPO  
    exportCarrierRates($db, $output, 'xpo', 'gul_xpo_rates', 'XPO');
    
    // Données K+N
    exportCarrierRates($db, $output, 'kn', 'gul_kn_rates', 'Kuehne + Nagel');
}

/**
 * Export des tarifs d'un transporteur spécifique
 */
function exportCarrierRates($db, $output, $carrierCode, $table, $carrierName) {
    try {
        $stmt = $db->prepare("SELECT * FROM `$table` ORDER BY CAST(num_departement AS UNSIGNED)");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Harmonisation des colonnes selon le transporteur
            $data = [
                $carrierName,
                $carrierCode,
                $row['num_departement'],
                $row['departement'] ?? '',
                $row['delais'] ?? ''
            ];
            
            // Ajout des tarifs selon la structure du transporteur
            if ($carrierCode === 'xpo') {
                // XPO a une structure différente
                $data = array_merge($data, [
                    $row['tarif_0_99'] ?? '',       // 0-9kg (utilise 0-99)
                    '',                              // 10-19kg (n'existe pas)
                    '',                              // 20-29kg (n'existe pas)
                    '',                              // 30-39kg (n'existe pas)
                    '',                              // 40-49kg (n'existe pas)
                    '',                              // 50-59kg (n'existe pas)
                    '',                              // 60-69kg (n'existe pas)
                    '',                              // 70-79kg (n'existe pas)
                    '',                              // 80-89kg (n'existe pas)
                    '',                              // 90-99kg (n'existe pas)
                    $row['tarif_100_499'] ?? '',     // 100-299kg (utilise 100-499)
                    '',                              // 300-499kg (n'existe pas)
                    $row['tarif_500_999'] ?? '',     // 500-999kg
                    $row['tarif_1000_1999'] ?? '',   // 1000-1999kg
                    $row['tarif_2000_2999'] ?? ''    // 2000-2999kg
                ]);
            } else {
                // Heppner et K+N ont la même structure
                $data = array_merge($data, [
                    $row['tarif_0_9'] ?? '',
                    $row['tarif_10_19'] ?? '',
                    $row['tarif_20_29'] ?? '',
                    $row['tarif_30_39'] ?? '',
                    $row['tarif_40_49'] ?? '',
                    $row['tarif_50_59'] ?? '',
                    $row['tarif_60_69'] ?? '',
                    $row['tarif_70_79'] ?? '',
                    $row['tarif_80_89'] ?? '',
                    $row['tarif_90_99'] ?? '',
                    $row['tarif_100_299'] ?? '',
                    $row['tarif_300_499'] ?? '',
                    $row['tarif_500_999'] ?? '',
                    $row['tarif_1000_1999'] ?? '',
                    ''  // 2000-2999kg (n'existe pas pour Heppner/K+N)
                ]);
            }
            
            fputcsv($output, $data);
        }
    } catch (Exception $e) {
        error_log("Erreur export transporteur $carrierCode: " . $e->getMessage());
        fputcsv($output, ["Erreur lors de l'export de $carrierName"]);
    }
}

/**
 * Export options au format CSV
 */
function exportOptionsCSV($db, $output) {
    // En-têtes
    fputcsv($output, [
        'ID',
        'Transporteur',
        'Code option',
        'Libellé',
        'Montant',
        'Unité',
        'Actif',
        'Date création'
    ]);
    
    try {
        $stmt = $db->query("
            SELECT *, 
                   CASE WHEN actif = 1 THEN 'Oui' ELSE 'Non' END as actif_libelle
            FROM gul_options_supplementaires 
            ORDER BY transporteur, code_option
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['transporteur'],
                $row['code_option'],
                $row['libelle'],
                number_format($row['montant'], 2, ',', ' '),
                $row['unite'],
                $row['actif_libelle'],
                date('d/m/Y H:i', strtotime($row['date_creation'] ?? 'now'))
            ]);
        }
    } catch (Exception $e) {
        error_log("Erreur export options: " . $e->getMessage());
        fputcsv($output, ["Erreur lors de l'export des options"]);
    }
}

/**
 * Export taxes au format CSV
 */
function exportTaxesCSV($db, $output) {
    // En-têtes
    fputcsv($output, [
        'ID',
        'Transporteur',
        'Type tarification',
        'Poids maximum (kg)',
        'Unité poids',
        'Majoration ADR',
        'Majoration saisonnière applicable',
        'Majoration saisonnière taux (%)',
        'Majoration IDF type',
        'Majoration IDF valeur',
        'Majoration IDF départements',
        'Participation transition énergétique (€)',
        'Contribution sanitaire (€)',
        'Sûreté (€)',
        'Surcharge gasoil (%)',
        'Date modification'
    ]);
    
    try {
        $stmt = $db->query("
            SELECT *,
                   CASE WHEN majoration_saisonniere_applicable = 1 THEN 'Oui' ELSE 'Non' END as saison_libelle
            FROM gul_taxes_transporteurs 
            ORDER BY transporteur
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['transporteur'],
                $row['type_tarification'],
                number_format($row['poids_maximum'], 2, ',', ' '),
                $row['unite_poids'] ?? 'Kg',
                $row['majoration_adr'],
                $row['saison_libelle'],
                $row['majoration_saisonniere_taux'] ? number_format($row['majoration_saisonniere_taux'], 2, ',', ' ') : '',
                $row['majoration_idf_type'] ?? '',
                $row['majoration_idf_valeur'] ? number_format($row['majoration_idf_valeur'], 2, ',', ' ') : '',
                $row['majoration_idf_departements'] ?? '',
                $row['participation_transition_energetique'] ? number_format($row['participation_transition_energetique'], 2, ',', ' ') : '',
                $row['contribution_sanitaire'] ? number_format($row['contribution_sanitaire'], 2, ',', ' ') : '',
                $row['surete'] ? number_format($row['surete'], 2, ',', ' ') : '',
                $row['surcharge_gasoil'] ? number_format($row['surcharge_gasoil'] * 100, 4, ',', ' ') : '',
                date('d/m/Y H:i', strtotime($row['date_modification'] ?? $row['date_creation'] ?? 'now'))
            ]);
        }
    } catch (Exception $e) {
        error_log("Erreur export taxes: " . $e->getMessage());
        fputcsv($output, ["Erreur lors de l'export des taxes"]);
    }
}

/**
 * Export complet au format CSV
 */
function exportAllCSV($db, $output) {
    // Informations d'export
    fputcsv($output, ['=== EXPORT COMPLET GULDAGIL ===']);
    fputcsv($output, ['Date d\'export: ' . date('d/m/Y à H:i:s')]);
    fputcsv($output, ['Utilisateur: ' . ($_SESSION['admin_username'] ?? 'Inconnu')]);
    fputcsv($output, []);
    
    // Tarifs transporteurs
    fputcsv($output, ['=== TARIFS TRANSPORTEURS ===']);
    fputcsv($output, []);
    exportRatesCSV($db, $output);
    
    fputcsv($output, []);
    fputcsv($output, []);
    
    // Options supplémentaires
    fputcsv($output, ['=== OPTIONS SUPPLEMENTAIRES ===']);
    fputcsv($output, []);
    exportOptionsCSV($db, $output);
    
    fputcsv($output, []);
    fputcsv($output, []);
    
    // Taxes et majorations
    fputcsv($output, ['=== TAXES ET MAJORATIONS ===']);
    fputcsv($output, []);
    exportTaxesCSV($db, $output);
}

/**
 * Export au format JSON
 */
function exportJSON($db, $type) {
    $filename = generateFilename($type, 'json');
    
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    
    $data = [
        'export_info' => [
            'date' => date('Y-m-d H:i:s'),
            'user' => $_SESSION['admin_username'] ?? 'Inconnu',
            'type' => $type,
            'version' => '1.2.0'
        ]
    ];
    
    try {
        switch ($type) {
            case 'rates':
                $data['rates'] = getAllRates($db);
                break;
            case 'options':
                $data['options'] = getAllOptions($db);
                break;
            case 'taxes':
                $data['taxes'] = getAllTaxes($db);
                break;
            case 'all':
            default:
                $data['rates'] = getAllRates($db);
                $data['options'] = getAllOptions($db);
                $data['taxes'] = getAllTaxes($db);
                break;
        }
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        
    } catch (Exception $e) {
        error_log("Erreur export JSON: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Erreur lors de l\'export',
            'message' => $e->getMessage()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Export au format Excel (CSV pour l'instant)
 */
function exportExcel($db, $type) {
    // Pour l'instant, on génère un CSV avec extension .xlsx
    // En production, utilisez PhpSpreadsheet pour un vrai Excel
    $filename = generateFilename($type, 'xlsx');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    
    // Rediriger vers l'export CSV pour l'instant
    $_GET['format'] = 'csv';
    exportCSV($db, $type);
}

/**
 * Récupère tous les tarifs
 */
function getAllRates($db) {
    $rates = [];
    
    $tables = [
        'heppner' => ['table' => 'gul_heppner_rates', 'name' => 'Heppner'],
        'xpo' => ['table' => 'gul_xpo_rates', 'name' => 'XPO'],
        'kn' => ['table' => 'gul_kn_rates', 'name' => 'Kuehne + Nagel']
    ];
    
    foreach ($tables as $code => $info) {
        try {
            $stmt = $db->prepare("SELECT * FROM `{$info['table']}` ORDER BY CAST(num_departement AS UNSIGNED)");
            $stmt->execute();
            $rates[$code] = [
                'name' => $info['name'],
                'table' => $info['table'],
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            error_log("Erreur récupération tarifs $code: " . $e->getMessage());
            $rates[$code] = [
                'name' => $info['name'],
                'table' => $info['table'],
                'data' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $rates;
}

/**
 * Récupère toutes les options
 */
function getAllOptions($db) {
    try {
        $stmt = $db->query("SELECT * FROM gul_options_supplementaires ORDER BY transporteur, code_option");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur récupération options: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Récupère toutes les taxes
 */
function getAllTaxes($db) {
    try {
        $stmt = $db->query("SELECT * FROM gul_taxes_transporteurs ORDER BY transporteur");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur récupération taxes: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Génère un nom de fichier cohérent
 */
function generateFilename($type, $extension) {
    $prefix = 'guldagil_export';
    $date = date('Y-m-d_H-i-s');
    $user = preg_replace('/[^a-zA-Z0-9]/', '', $_SESSION['admin_username'] ?? 'admin');
    
    return "{$prefix}_{$type}_{$date}_{$user}.{$extension}";
}
?>

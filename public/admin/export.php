<?php
// public/admin/export.php
require __DIR__ . '/../../config.php';

$type = $_GET['type'] ?? 'all';
$format = $_GET['format'] ?? 'csv';

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
        default:
            throw new Exception('Format non supporté');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo 'Erreur lors de l\'export : ' . $e->getMessage();
}

function exportCSV($db, $type) {
    $filename = "guldagil_export_" . $type . "_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
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

function exportRatesCSV($db, $output) {
    // En-têtes
    fputcsv($output, [
        'Transporteur',
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
        'Tarif 1000-1999kg'
    ]);
    
    // Données Heppner
    $stmt = $db->query("SELECT * FROM gul_heppner_rates ORDER BY num_departement");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            'Heppner',
            $row['num_departement'],
            $row['departement'],
            $row['delais'],
            $row['tarif_0_9'],
            $row['tarif_10_19'],
            $row['tarif_20_29'],
            $row['tarif_30_39'],
            $row['tarif_40_49'],
            $row['tarif_50_59'],
            $row['tarif_60_69'],
            $row['tarif_70_79'],
            $row['tarif_80_89'],
            $row['tarif_90_99'],
            $row['tarif_100_299'],
            $row['tarif_300_499'],
            $row['tarif_500_999'],
            $row['tarif_1000_1999']
        ]);
    }
    
    // Données XPO
    $stmt = $db->query("SELECT * FROM gul_xpo_rates ORDER BY num_departement");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            'XPO',
            $row['num_departement'],
            $row['departement'],
            $row['delais'],
            $row['tarif_0_99'],
            '', '', '', '', '', '', '', '', '', // XPO n'a pas ces tranches
            $row['tarif_100_499'],
            '',
            $row['tarif_500_999'],
            $row['tarif_1000_1999']
        ]);
    }
    
    // Données K+N
    $stmt = $db->query("SELECT * FROM gul_kn_rates ORDER BY num_departement");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            'Kuehne + Nagel',
            $row['num_departement'],
            $row['departement'],
            $row['delais'],
            $row['tarif_0_9'],
            $row['tarif_10_19'],
            $row['tarif_20_29'],
            $row['tarif_30_39'],
            $row['tarif_40_49'],
            $row['tarif_50_59'],
            $row['tarif_60_69'],
            $row['tarif_70_79'],
            $row['tarif_80_89'],
            $row['tarif_90_99'],
            $row['tarif_100_299'],
            $row['tarif_300_499'],
            $row['tarif_500_999'],
            $row['tarif_1000_1999']
        ]);
    }
}

function exportOptionsCSV($db, $output) {
    // En-têtes
    fputcsv($output, [
        'Transporteur',
        'Code option',
        'Libellé',
        'Montant',
        'Unité',
        'Actif'
    ]);
    
    // Données
    $stmt = $db->query("SELECT * FROM gul_options_supplementaires ORDER BY transporteur, code_option");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['transporteur'],
            $row['code_option'],
            $row['libelle'],
            $row['montant'],
            $row['unite'],
            $row['actif'] ? 'Oui' : 'Non'
        ]);
    }
}

function exportTaxesCSV($db, $output) {
    // En-têtes
    fputcsv($output, [
        'Transporteur',
        'Type tarification',
        'Poids maximum',
        'Majoration ADR',
        'Majoration saisonnière applicable',
        'Majoration saisonnière taux',
        'Majoration IDF type',
        'Majoration IDF valeur',
        'Majoration IDF départements',
        'Participation transition énergétique',
        'Contribution sanitaire',
        'Sûreté',
        'Surcharge gasoil'
    ]);
    
    // Données
    $stmt = $db->query("SELECT * FROM gul_taxes_transporteurs ORDER BY transporteur");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['transporteur'],
            $row['type_tarification'],
            $row['poids_maximum'],
            $row['majoration_adr'],
            $row['majoration_saisonniere_applicable'] ? 'Oui' : 'Non',
            $row['majoration_saisonniere_taux'],
            $row['majoration_idf_type'],
            $row['majoration_idf_valeur'],
            $row['majoration_idf_departements'],
            $row['participation_transition_energetique'],
            $row['contribution_sanitaire'],
            $row['surete'],
            $row['surcharge_gasoil']
        ]);
    }
}

function exportAllCSV($db, $output) {
    // Export complet avec séparateurs
    
    fputcsv($output, ['=== TARIFS TRANSPORTEURS ===']);
    fputcsv($output, []);
    exportRatesCSV($db, $output);
    
    fputcsv($output, []);
    fputcsv($output, ['=== OPTIONS SUPPLEMENTAIRES ===']);
    fputcsv($output, []);
    exportOptionsCSV($db, $output);
    
    fputcsv($output, []);
    fputcsv($output, ['=== TAXES ET MAJORATIONS ===']);
    fputcsv($output, []);
    exportTaxesCSV($db, $output);
}

function exportJSON($db, $type) {
    $filename = "guldagil_export_" . $type . "_" . date('Y-m-d') . ".json";
    
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $data = [];
    
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
            $data = [
                'export_date' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'rates' => getAllRates($db),
                'options' => getAllOptions($db),
                'taxes' => getAllTaxes($db)
            ];
            break;
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function exportExcel($db, $type) {
    // Pour l'instant, on fait un CSV mais avec extension .xlsx
    // En production, vous pouvez utiliser PhpSpreadsheet
    $filename = "guldagil_export_" . $type . "_" . date('Y-m-d') . ".xlsx";
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Pour l'instant, on utilise le format CSV
    // TODO: Implémenter PhpSpreadsheet pour un vrai Excel
    exportCSV($db, $type);
}

function getAllRates($db) {
    $rates = [];
    
    // Heppner
    $stmt = $db->query("SELECT *, 'heppner' as transporteur FROM gul_heppner_rates ORDER BY num_departement");
    $rates['heppner'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // XPO
    $stmt = $db->query("SELECT *, 'xpo' as transporteur FROM gul_xpo_rates ORDER BY num_departement");
    $rates['xpo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // K+N
    $stmt = $db->query("SELECT *, 'kn' as transporteur FROM gul_kn_rates ORDER BY num_departement");
    $rates['kn'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $rates;
}

function getAllOptions($db) {
    $stmt = $db->query("SELECT * FROM gul_options_supplementaires ORDER BY transporteur, code_option");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAll

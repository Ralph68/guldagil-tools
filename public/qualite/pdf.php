<?php
/**
 * Titre: Génération PDF rapport contrôle qualité
 * Chemin: /public/qualite/pdf.php
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

require_once ROOT_PATH . '/config/config.php';

$control_id = (int)($_GET['id'] ?? 0);
if (!$control_id) {
    http_response_code(404);
    exit('Contrôle introuvable');
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Récupération contrôle complet
    $stmt = $pdo->prepare("
        SELECT qc.*, et.type_name, em.model_name, em.manufacturer, a.agency_name
        FROM cq_quality_controls qc
        JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
        LEFT JOIN cq_equipment_models em ON qc.equipment_model_id = em.id
        LEFT JOIN cq_agencies a ON qc.agency_code = a.agency_code
        WHERE qc.id = ?
    ");
    $stmt->execute([$control_id]);
    $control = $stmt->fetch();

    if (!$control) {
        http_response_code(404);
        exit('Contrôle introuvable');
    }

    $technical_data = json_decode($control['technical_data'], true) ?? [];
    $quality_checks = $technical_data['quality_checks'] ?? [];

    // Configuration PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Controle_' . $control['control_number'] . '.pdf"');

    // Génération PDF simple (à remplacer par TCPDF/FPDF)
    generateSimplePDF($control, $technical_data, $quality_checks);

} catch (Exception $e) {
    error_log("Erreur PDF: " . $e->getMessage());
    http_response_code(500);
    exit('Erreur génération PDF');
}

function generateSimplePDF($control, $technical_data, $quality_checks) {
    // PDF basique en texte (pour démo - remplacer par vraie lib PDF)
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="Controle_' . $control['control_number'] . '.txt"');
    
    echo "════════════════════════════════════════════════════════════════\n";
    echo "                    RAPPORT DE CONTRÔLE QUALITÉ\n";
    echo "                           GULDAGIL\n";
    echo "════════════════════════════════════════════════════════════════\n\n";
    
    echo "N° CONTRÔLE: " . $control['control_number'] . "\n";
    echo "DATE: " . date('d/m/Y', strtotime($control['created_at'])) . "\n";
    echo "STATUT: " . strtoupper($control['status']) . "\n\n";
    
    echo "────────────────────────────────────────────────────────────────\n";
    echo "ÉQUIPEMENT\n";
    echo "────────────────────────────────────────────────────────────────\n";
    echo "Type: " . ($control['type_name'] ?? 'N/A') . "\n";
    echo "Modèle: " . ($control['model_name'] ?? 'N/A') . "\n";
    echo "Fabricant: " . ($control['manufacturer'] ?? 'N/A') . "\n";
    echo "N° Série: " . ($control['serial_number'] ?? 'N/A') . "\n\n";
    
    echo "────────────────────────────────────────────────────────────────\n";
    echo "INSTALLATION\n";
    echo "────────────────────────────────────────────────────────────────\n";
    echo "Agence: " . ($control['agency_name'] ?? 'N/A') . "\n";
    echo "Installation: " . ($control['installation_name'] ?? 'N/A') . "\n";
    echo "N° Dossier: " . ($control['dossier_number'] ?? 'N/A') . "\n";
    echo "N° ARC: " . ($control['arc_number'] ?? 'N/A') . "\n\n";
    
    if (!empty($technical_data)) {
        echo "────────────────────────────────────────────────────────────────\n";
        echo "DONNÉES TECHNIQUES\n";
        echo "────────────────────────────────────────────────────────────────\n";
        foreach ($technical_data as $key => $value) {
            if ($key !== 'quality_checks' && !is_array($value)) {
                echo formatLabel($key) . ": " . $value . "\n";
            }
        }
        echo "\n";
    }
    
    if (!empty($quality_checks)) {
        echo "────────────────────────────────────────────────────────────────\n";
        echo "CONTRÔLES QUALITÉ\n";
        echo "────────────────────────────────────────────────────────────────\n";
        foreach ($quality_checks as $check => $data) {
            if ($check !== '_comments' && is_array($data)) {
                $status = $data['checked'] ? '[✓]' : '[✗]';
                echo $status . " " . formatLabel($check) . "\n";
            }
        }
        if (!empty($quality_checks['_comments'])) {
            echo "\nCommentaires techniques:\n" . $quality_checks['_comments'] . "\n";
        }
        echo "\n";
    }
    
    if ($control['observations']) {
        echo "────────────────────────────────────────────────────────────────\n";
        echo "OBSERVATIONS\n";
        echo "────────────────────────────────────────────────────────────────\n";
        echo wordwrap($control['observations'], 60) . "\n\n";
    }
    
    echo "────────────────────────────────────────────────────────────────\n";
    echo "VALIDATION\n";
    echo "────────────────────────────────────────────────────────────────\n";
    echo "Contrôlé par: " . $control['prepared_by'] . "\n";
    echo "Date: " . date('d/m/Y', strtotime($control['prepared_date'])) . "\n";
    
    if ($control['validated_by']) {
        echo "Validé par: " . $control['validated_by'] . "\n";
        echo "Date validation: " . date('d/m/Y', strtotime($control['validated_date'])) . "\n";
    }
    
    echo "\n────────────────────────────────────────────────────────────────\n";
    echo "Document généré automatiquement le " . date('d/m/Y à H:i') . "\n";
    echo "Guldagil - Solutions de traitement de l'eau\n";
    echo "────────────────────────────────────────────────────────────────\n";
}

function formatLabel($key) {
    $labels = [
        'debit_nominal_lh' => 'Débit nominal (L/h)',
        'pression_service_bar' => 'Pression service (bar)',
        'test_etancheite' => 'Test étanchéité',
        'test_debit_precision' => 'Test précision débit',
        'raw_water_hardness' => 'TH eau brute (°f)',
        'target_hardness' => 'TH à obtenir (°f)'
    ];
    return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
}
?>

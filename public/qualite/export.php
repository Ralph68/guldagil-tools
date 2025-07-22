<?php
/**
 * Titre: Export données contrôles qualité
 * Chemin: /public/qualite/export.php
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

require_once ROOT_PATH . '/config/config.php';

$control_id = (int)($_GET['id'] ?? 0);
$format = $_GET['format'] ?? 'json';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if ($control_id) {
        // Export contrôle unique
        $stmt = $pdo->prepare("SELECT * FROM v_recent_controls_detailed WHERE id = ?");
        $stmt->execute([$control_id]);
        $data = $stmt->fetch();
        $filename = "controle_{$control_id}";
    } else {
        // Export liste complète
        $stmt = $pdo->query("SELECT * FROM v_recent_controls_detailed ORDER BY created_at DESC");
        $data = $stmt->fetchAll();
        $filename = "controles_qualite_" . date('Y-m-d');
    }

    switch ($format) {
        case 'json':
            exportJSON($data, $filename);
            break;
        case 'csv':
            exportCSV($data, $filename);
            break;
        case 'xml':
            exportXML($data, $filename);
            break;
        default:
            exportJSON($data, $filename);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function exportJSON($data, $filename) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function exportCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    
    if (is_array($data) && !empty($data)) {
        $first_row = is_array($data[0]) ? $data[0] : $data;
        
        // En-têtes
        $headers = array_keys($first_row);
        fputcsv($output, $headers, ';');
        
        // Données
        if (isset($data[0])) {
            foreach ($data as $row) {
                fputcsv($output, array_values($row), ';');
            }
        } else {
            fputcsv($output, array_values($data), ';');
        }
    }
    
    fclose($output);
}

function exportXML($data, $filename) {
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xml"');
    
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><controles_qualite/>');
    
    if (isset($data[0])) {
        foreach ($data as $control) {
            $node = $xml->addChild('controle');
            foreach ($control as $key => $value) {
                $node->addChild($key, htmlspecialchars($value));
            }
        }
    } else {
        $node = $xml->addChild('controle');
        foreach ($data as $key => $value) {
            $node->addChild($key, htmlspecialchars($value));
        }
    }
    
    echo $xml->asXML();
}
?>

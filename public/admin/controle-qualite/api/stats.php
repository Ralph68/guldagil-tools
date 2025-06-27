<?php
// /public/admin/controle-qualite/api/stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../../config/config.php';

try {
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM gul_controles")->fetchColumn(),
        'aujourd_hui' => $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE DATE(date_controle) = CURDATE()")->fetchColumn(),
        'cette_semaine' => $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE WEEK(date_controle) = WEEK(NOW())")->fetchColumn(),
        'ce_mois' => $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE MONTH(date_controle) = MONTH(NOW())")->fetchColumn(),
        'en_cours' => $pdo->query("SELECT COUNT(*) FROM gul_controles WHERE statut = 'en_cours'")->fetchColumn(),
        'timestamp' => time()
    ];
    
    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}

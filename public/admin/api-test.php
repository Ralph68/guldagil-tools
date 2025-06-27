<?php
// public/admin/api-test.php - Test simple de l'API
header('Content-Type: application/json; charset=UTF-8');

try {
    // Test de la configuration
    require __DIR__ . '/../../config.php';
    
    // Test de la base de données
    $stmt = $db->query("SELECT COUNT(*) as count FROM gul_taxes_transporteurs");
    $result = $stmt->fetch();
    
    // Test des tables
    $tables = ['gul_heppner_rates', 'gul_xpo_rates', 'gul_kn_rates', 'gul_options_supplementaires'];
    $tableStatus = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            $tableStatus[$table] = ['exists' => true, 'count' => $count];
        } catch (Exception $e) {
            $tableStatus[$table] = ['exists' => false, 'error' => $e->getMessage()];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'API et base de données fonctionnelles',
        'data' => [
            'transporteurs_count' => $result['count'],
            'tables_status' => $tableStatus,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>

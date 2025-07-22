<?php
/**
 * Titre: API Résolution groupée non-conformités
 * Chemin: /public/qualite/api/bulk-resolve.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT_PATH . '/config/config.php';
session_start();

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $data = json_decode(file_get_contents('php://input'), true);
    $ids = $data['ids'] ?? [];
    $new_status = $data['status'] ?? 'completed';
    $comments = $data['comments'] ?? 'Résolution groupée';
    
    if (empty($ids) || !is_array($ids)) {
        throw new Exception('Liste IDs manquante');
    }

    $user_name = $_SESSION['user']['name'] ?? 'System';
    $resolved_count = 0;
    
    $pdo->beginTransaction();

    foreach ($ids as $id) {
        $control_id = (int)$id;
        
        // Vérifier statut
        $check_sql = "SELECT status, control_number FROM cq_quality_controls WHERE id = ? AND status = 'in_progress'";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute([$control_id]);
        $control = $stmt->fetch();
        
        if ($control) {
            // Résoudre
            $update_sql = "UPDATE cq_quality_controls SET 
                          status = ?, 
                          updated_at = NOW()
                          WHERE id = ?";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([$new_status, $control_id]);
            
            // Historique
            $history_sql = "INSERT INTO cq_control_history (control_id, action, old_value, new_value, user_name) 
                           VALUES (?, 'modified', 'in_progress', ?, ?)";
            $pdo->prepare($history_sql)->execute([$control_id, $new_status, $user_name]);
            
            $resolved_count++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "{$resolved_count} non-conformité(s) résolue(s)",
        'resolved' => $resolved_count,
        'total' => count($ids)
    ]);

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    error_log("Erreur bulk resolve: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

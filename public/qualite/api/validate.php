<?php
/**
 * Titre: API Validation des contrôles qualité
 * Chemin: /public/qualite/api/validate.php
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

// Auth rapide
$current_user = ['id' => 1, 'name' => 'Validateur', 'role' => 'resp_materiel'];
if (!in_array($current_user['role'], ['admin', 'resp_materiel'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Droits insuffisants']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $data = json_decode(file_get_contents('php://input'), true);
    $control_id = (int)($data['id'] ?? 0);

    if (!$control_id) {
        throw new Exception('ID contrôle manquant');
    }

    // Vérifier que le contrôle existe et peut être validé
    $check_sql = "SELECT status, control_number FROM cq_quality_controls WHERE id = ?";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute([$control_id]);
    $control = $stmt->fetch();

    if (!$control) {
        throw new Exception('Contrôle introuvable');
    }

    if ($control['status'] === 'validated') {
        throw new Exception('Contrôle déjà validé');
    }

    $pdo->beginTransaction();

    // Valider le contrôle
    $update_sql = "UPDATE cq_quality_controls SET 
                   status = 'validated',
                   validated_by = ?,
                   validated_date = NOW(),
                   updated_at = NOW()
                   WHERE id = ?";
    
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute([$current_user['name'], $control_id]);

    // Historique
    $history_sql = "INSERT INTO cq_control_history (control_id, action, user_name) VALUES (?, 'validated', ?)";
    $pdo->prepare($history_sql)->execute([$control_id, $current_user['name']]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Contrôle validé avec succès',
        'control_number' => $control['control_number']
    ]);

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    error_log("Erreur validation: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

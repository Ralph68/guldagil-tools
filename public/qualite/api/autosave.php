<?php
/**
 * Titre: API Sauvegarde automatique brouillon
 * Chemin: /public/qualite/api/autosave.php
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
$current_user = ['id' => 1, 'name' => 'TestUser'];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $control_number = $_POST['control_number'] ?? '';
    if (empty($control_number)) {
        throw new Exception('Numéro de contrôle manquant');
    }

    // Données formulaire
    $draft_data = [
        'control_number' => $control_number,
        'equipment_type' => $_POST['equipment_type'] ?? '',
        'equipment_model' => $_POST['equipment_model'] ?? '',
        'agency_code' => $_POST['agency_code'] ?? '',
        'dossier_number' => $_POST['dossier_number'] ?? '',
        'arc_number' => $_POST['arc_number'] ?? '',
        'installation_name' => $_POST['installation_name'] ?? '',
        'serial_number' => $_POST['serial_number'] ?? '',
        'technical' => $_POST['technical'] ?? [],
        'settings' => $_POST['settings'] ?? [],
        'quality_checks' => $_POST['quality_checks'] ?? [],
        'control_results' => $_POST['control_results'] ?? '',
        'observations' => $_POST['observations'] ?? '',
        'prepared_by' => $_POST['prepared_by'] ?? '',
        'prepared_date' => $_POST['prepared_date'] ?? '',
        'final_status' => $_POST['final_status'] ?? ''
    ];

    // Table brouillons (création si n'existe pas)
    $pdo->exec("CREATE TABLE IF NOT EXISTS cq_drafts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        control_number VARCHAR(20) UNIQUE,
        user_id INT,
        draft_data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Sauvegarde ou mise à jour
    $sql = "INSERT INTO cq_drafts (control_number, user_id, draft_data) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            draft_data = VALUES(draft_data), updated_at = CURRENT_TIMESTAMP";
    
    $pdo->prepare($sql)->execute([
        $control_number,
        $current_user['id'],
        json_encode($draft_data)
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Brouillon sauvegardé',
        'timestamp' => date('H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Erreur autosave: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde']);
}
?>

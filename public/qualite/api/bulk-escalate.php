<?php
/**
 * Titre: API Escalade groupée non-conformités
 * Chemin: /public/qualite/api/bulk-escalate.php
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
    
    if (empty($ids) || !is_array($ids)) {
        throw new Exception('Liste IDs manquante');
    }

    $user_name = $_SESSION['user']['name'] ?? 'System';
    $escalated_count = 0;
    $escalation_details = [];
    
    foreach ($ids as $id) {
        $control_id = (int)$id;
        
        // Récupérer infos contrôle
        $sql = "SELECT qc.control_number, qc.status, qc.observations, et.type_name, a.agency_name
                FROM cq_quality_controls qc
                JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
                LEFT JOIN cq_agencies a ON qc.agency_code = a.agency_code
                WHERE qc.id = ? AND qc.status = 'in_progress'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$control_id]);
        $control = $stmt->fetch();
        
        if ($control) {
            // Créer notification escalade
            $escalation_data = [
                'control_id' => $control_id,
                'control_number' => $control['control_number'],
                'type' => $control['type_name'],
                'agency' => $control['agency_name'],
                'observations' => $control['observations'],
                'escalated_by' => $user_name,
                'escalated_at' => date('Y-m-d H:i:s')
            ];
            
            // Log escalade
            $history_sql = "INSERT INTO cq_control_history (control_id, action, new_value, user_name) 
                           VALUES (?, 'escalated', ?, ?)";
            $pdo->prepare($history_sql)->execute([
                $control_id, 
                "Escaladé vers responsable qualité", 
                $user_name
            ]);
            
            // Envoyer notification (email ou autre)
            sendEscalationNotification($escalation_data);
            
            $escalation_details[] = $escalation_data;
            $escalated_count++;
        }
    }

    // Générer rapport escalade
    if ($escalated_count > 0) {
        generateEscalationReport($escalation_details);
    }

    echo json_encode([
        'success' => true,
        'message' => "{$escalated_count} non-conformité(s) escaladée(s)",
        'escalated' => $escalated_count,
        'total' => count($ids)
    ]);

} catch (Exception $e) {
    error_log("Erreur bulk escalate: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function sendEscalationNotification($data) {
    $to = "responsable.qualite@guldagil.fr";
    $subject = "ESCALADE - Non-conformité {$data['control_number']}";
    
    $message = "
    <h3>Escalade Non-Conformité</h3>
    <p><strong>Contrôle :</strong> {$data['control_number']}</p>
    <p><strong>Type :</strong> {$data['type']}</p>
    <p><strong>Agence :</strong> {$data['agency']}</p>
    <p><strong>Escaladé par :</strong> {$data['escalated_by']}</p>
    <p><strong>Date :</strong> {$data['escalated_at']}</p>
    
    <h4>Observations :</h4>
    <p>" . nl2br(htmlspecialchars($data['observations'])) . "</p>
    
    <p><strong>Action requise :</strong> Intervention responsable qualité nécessaire.</p>
    ";
    
    $headers = [
        "From: qualite@guldagil.fr",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "X-Priority: 1"
    ];
    
    // Simulation envoi
    error_log("ESCALATION EMAIL - To: {$to}, Control: {$data['control_number']}");
    return true;
}

function generateEscalationReport($escalations) {
    $report_content = "RAPPORT D'ESCALADE - " . date('d/m/Y H:i') . "\n\n";
    $report_content .= "Nombre de non-conformités escaladées : " . count($escalations) . "\n\n";
    
    foreach ($escalations as $esc) {
        $report_content .= "Contrôle : {$esc['control_number']}\n";
        $report_content .= "Type : {$esc['type']}\n";
        $report_content .= "Agence : {$esc['agency']}\n";
        $report_content .= "Escaladé par : {$esc['escalated_by']}\n";
        $report_content .= "Observations : " . substr($esc['observations'], 0, 200) . "...\n";
        $report_content .= "---\n\n";
    }
    
    // Sauvegarder rapport
    $reports_dir = ROOT_PATH . '/storage/reports/escalations/';
    if (!is_dir($reports_dir)) {
        mkdir($reports_dir, 0755, true);
    }
    
    $filename = "escalation_" . date('Y-m-d_H-i-s') . ".txt";
    file_put_contents($reports_dir . $filename, $report_content);
    
    return $filename;
}
?>

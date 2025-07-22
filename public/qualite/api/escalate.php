<?php
/**
 * Titre: API Escalade individuelle non-conformité
 * Chemin: /public/qualite/api/escalate.php
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
    $control_id = (int)($data['id'] ?? 0);
    $reason = $data['reason'] ?? 'Escalade demandée';
    
    if (!$control_id) {
        throw new Exception('ID contrôle manquant');
    }

    // Récupérer infos contrôle
    $sql = "SELECT qc.*, et.type_name, em.model_name, a.agency_name, a.email
            FROM cq_quality_controls qc
            JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
            LEFT JOIN cq_equipment_models em ON qc.equipment_model_id = em.id
            LEFT JOIN cq_agencies a ON qc.agency_code = a.agency_code
            WHERE qc.id = ? AND qc.status = 'in_progress'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$control_id]);
    $control = $stmt->fetch();

    if (!$control) {
        throw new Exception('Contrôle introuvable ou déjà résolu');
    }

    $user_name = $_SESSION['user']['name'] ?? 'System';
    
    // Calculer priorité escalade
    $days_open = (strtotime('now') - strtotime($control['created_at'])) / (24 * 3600);
    $priority = $days_open > 7 ? 'URGENT' : ($days_open > 3 ? 'IMPORTANTE' : 'NORMALE');

    // Enregistrer escalade
    $history_sql = "INSERT INTO cq_control_history (control_id, action, new_value, user_name) 
                   VALUES (?, 'escalated', ?, ?)";
    $pdo->prepare($history_sql)->execute([
        $control_id, 
        "Priorité: {$priority} - Raison: {$reason}", 
        $user_name
    ]);

    // Préparer données escalade
    $escalation_data = [
        'control_number' => $control['control_number'],
        'type_name' => $control['type_name'],
        'model_name' => $control['model_name'],
        'agency_name' => $control['agency_name'],
        'prepared_by' => $control['prepared_by'],
        'observations' => $control['observations'],
        'days_open' => round($days_open, 1),
        'priority' => $priority,
        'reason' => $reason,
        'escalated_by' => $user_name,
        'escalated_at' => date('Y-m-d H:i:s')
    ];

    // Envoyer notification
    $notification_sent = sendEscalationEmail($escalation_data);

    echo json_encode([
        'success' => true,
        'message' => 'Non-conformité escaladée avec succès',
        'priority' => $priority,
        'days_open' => $escalation_data['days_open'],
        'notification_sent' => $notification_sent
    ]);

} catch (Exception $e) {
    error_log("Erreur escalade: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function sendEscalationEmail($data) {
    $to = "responsable.qualite@guldagil.fr";
    $cc = "direction@guldagil.fr";
    
    $subject = "[{$data['priority']}] Escalade NC - {$data['control_number']}";
    
    $priority_color = [
        'URGENT' => '#ef4444',
        'IMPORTANTE' => '#f59e0b', 
        'NORMALE' => '#10b981'
    ][$data['priority']];

    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .header { background: {$priority_color}; color: white; padding: 15px; text-align: center; }
            .priority { font-size: 18px; font-weight: bold; }
            .content { padding: 20px; }
            .section { margin: 15px 0; padding: 15px; background: #f9fafb; border-radius: 5px; }
            .urgent { border-left: 5px solid #ef4444; }
            .importante { border-left: 5px solid #f59e0b; }
            .normale { border-left: 5px solid #10b981; }
            .footer { background: #374151; color: white; padding: 10px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='priority'>ESCALADE NON-CONFORMITÉ</div>
            <div>Priorité : {$data['priority']}</div>
        </div>
        
        <div class='content'>
            <div class='section " . strtolower($data['priority']) . "'>
                <h3>Informations du contrôle</h3>
                <p><strong>N° Contrôle :</strong> {$data['control_number']}</p>
                <p><strong>Équipement :</strong> {$data['type_name']} - {$data['model_name']}</p>
                <p><strong>Agence :</strong> {$data['agency_name']}</p>
                <p><strong>Technicien :</strong> {$data['prepared_by']}</p>
                <p><strong>Jours ouverts :</strong> {$data['days_open']} jour(s)</p>

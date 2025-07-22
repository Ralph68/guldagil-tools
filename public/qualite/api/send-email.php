<?php
/**
 * Titre: API Envoi emails aux agences
 * Chemin: /public/qualite/api/send-email.php
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

    if (!$control_id) {
        throw new Exception('ID contrôle manquant');
    }

    // Récupérer informations contrôle et agence
    $sql = "SELECT qc.*, et.type_name, em.model_name, a.agency_name, a.email, a.contact_person
            FROM cq_quality_controls qc
            JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
            LEFT JOIN cq_equipment_models em ON qc.equipment_model_id = em.id
            LEFT JOIN cq_agencies a ON qc.agency_code = a.agency_code
            WHERE qc.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$control_id]);
    $control = $stmt->fetch();

    if (!$control) {
        throw new Exception('Contrôle introuvable');
    }

    if (!$control['email']) {
        throw new Exception('Email agence non configuré');
    }

    // Générer contenu email
    $subject = "Contrôle Qualité - {$control['control_number']}";
    
    $status_label = [
        'validated' => 'VALIDÉ - Matériel conforme',
        'completed' => 'TERMINÉ - En attente validation',
        'in_progress' => 'NON-CONFORME - Action corrective nécessaire'
    ][$control['status']] ?? 'Statut inconnu';

    $message = generateEmailContent($control, $status_label);

    // Simulation envoi email (remplacer par vraie fonction mail)
    $email_sent = sendEmail($control['email'], $subject, $message, $control);

    if ($email_sent) {
        // Marquer comme envoyé
        $update_sql = "UPDATE cq_quality_controls SET status = 'sent', updated_at = NOW() WHERE id = ?";
        $pdo->prepare($update_sql)->execute([$control_id]);

        // Historique
        $history_sql = "INSERT INTO cq_control_history (control_id, action, new_value, user_name) 
                       VALUES (?, 'sent', ?, ?)";
        $pdo->prepare($history_sql)->execute([
            $control_id, 
            $control['email'], 
            $_SESSION['user']['name'] ?? 'System'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Email envoyé avec succès',
            'recipient' => $control['email']
        ]);
    } else {
        throw new Exception('Échec envoi email');
    }

} catch (Exception $e) {
    error_log("Erreur envoi email: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateEmailContent($control, $status_label) {
    $html = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #10b981; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .status { padding: 10px; border-radius: 5px; margin: 15px 0; }
            .status.validated { background: #d1fae5; border-left: 4px solid #10b981; }
            .status.completed { background: #dbeafe; border-left: 4px solid #3b82f6; }
            .status.in_progress { background: #fee2e2; border-left: 4px solid #ef4444; }
            .details { background: #f9fafb; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .footer { background: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #6b7280; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>Contrôle Qualité - Guldagil</h2>
        </div>
        
        <div class='content'>
            <h3>Rapport de contrôle : {$control['control_number']}</h3>
            
            <div class='status {$control['status']}'>
                <strong>Statut : {$status_label}</strong>
            </div>
            
            <div class='details'>
                <p><strong>Type équipement :</strong> {$control['type_name']}</p>
                <p><strong>Modèle :</strong> " . ($control['model_name'] ?? 'Non spécifié') . "</p>
                <p><strong>Installation :</strong> " . ($control['installation_name'] ?? 'Non spécifiée') . "</p>
                <p><strong>N° Série :</strong> " . ($control['serial_number'] ?? 'N/A') . "</p>
                <p><strong>Contrôlé par :</strong> {$control['prepared_by']}</p>
                <p><strong>Date :</strong> " . date('d/m/Y', strtotime($control['prepared_date'])) . "</p>
            </div>";

    if ($control['observations']) {
        $html .= "<div class='details'>
                    <p><strong>Observations :</strong></p>
                    <p>" . nl2br(htmlspecialchars($control['observations'])) . "</p>
                  </div>";
    }

    $html .= "
            <p>Ce rapport automatique confirme la réalisation du contrôle qualité sur votre équipement.</p>
            
            " . ($control['status'] === 'validated' ? 
                "<p><strong>✅ Votre équipement est conforme et prêt à être mis en service.</strong></p>" :
                "<p><strong>⚠️ Veuillez prendre contact avec notre service technique.</strong></p>") . "
        </div>
        
        <div class='footer'>
            <p>Guldagil - Solutions de traitement de l'eau<br>
            Email généré automatiquement le " . date('d/m/Y à H:i') . "</p>
        </div>
    </body>
    </html>";

    return $html;
}

function sendEmail($to, $subject, $message, $control) {
    // Configuration email
    $from = "qualite@guldagil.fr";
    $from_name = "Guldagil - Contrôle Qualité";
    
    $headers = [
        "From: {$from_name} <{$from}>",
        "Reply-To: {$from}",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "X-Mailer: PHP/" . phpversion()
    ];

    // Simulation d'envoi (remplacer par PHPMailer ou service SMTP)
    if (function_exists('mail')) {
        return mail($to, $subject, $message, implode("\r\n", $headers));
    } else {
        // Log pour développement
        error_log("EMAIL SIMULÉ - To: {$to}, Subject: {$subject}");
        return true; // Simulation réussie
    }
}
?>

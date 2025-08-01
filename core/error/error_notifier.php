<?php
/**
 * Titre: Système de notifications d'erreurs critiques
 * Chemin: /core/error/error_notifier.php
 * Version: 0.5 beta + build auto
 */

class ErrorNotifier 
{
    private $config;
    private $alert_cooldown = 300; // 5 minutes entre alertes similaires
    private $cooldown_cache = [];
    
    public function __construct() {
        $this->config = [
            'admin_email' => 'runser.jean.thomas@guldagil.com', // TODO: Config dans config.php
            'enable_email' => true,
            'enable_dashboard' => true
        ];
    }
    
    /**
     * Vérifie si une alerte similaire est en cooldown
     */
    private function isInCooldown($error_data) {
        $cache_key = md5($error_data['type'] . '|' . $error_data['message'] . '|' . $error_data['module']);
        
        if (isset($this->cooldown_cache[$cache_key])) {
            $last_alert = $this->cooldown_cache[$cache_key];
            return (time() - $last_alert) < $this->alert_cooldown;
        }
        
        return false;
    }
    
    /**
     * Enregistre une alerte dans le cache cooldown
     */
    private function setCooldown($error_data) {
        $cache_key = md5($error_data['type'] . '|' . $error_data['message'] . '|' . $error_data['module']);
        $this->cooldown_cache[$cache_key] = time();
        
        // Nettoyage du cache (garder seulement les 50 dernières)
        if (count($this->cooldown_cache) > 50) {
            $this->cooldown_cache = array_slice($this->cooldown_cache, -50, null, true);
        }
    }
    
    /**
     * Formate le corps de l'email d'alerte
     */
    private function formatEmailBody($error_data) {
        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Alerte Critique - Portail Guldagil</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .header { background: #dc2626; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .footer { background: #f3f4f6; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; }
        .error-details { background: white; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .label { font-weight: bold; color: #374151; }
        .value { color: #6b7280; }
        .critical { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class='header'>
        <h2>🚨 ALERTE CRITIQUE - Portail Guldagil</h2>
        <p>Une erreur critique nécessite votre attention immédiate</p>
    </div>
    
    <div class='content'>
        <div class='error-details'>
            <p><span class='label'>Type d'erreur:</span> <span class='critical'>" . strtoupper($error_data['type']) . "</span></p>
            <p><span class='label'>Message:</span> <span class='value'>" . htmlspecialchars($error_data['message']) . "</span></p>
            <p><span class='label'>Module:</span> <span class='value'>" . htmlspecialchars($error_data['module']) . "</span></p>
            <p><span class='label'>Niveau:</span> <span class='critical'>" . strtoupper($error_data['level']) . "</span></p>
            <p><span class='label'>Horodatage:</span> <span class='value'>" . $error_data['timestamp'] . "</span></p>";
        
        if ($error_data['user_id']) {
            $html .= "<p><span class='label'>Utilisateur:</span> <span class='value'>ID " . $error_data['user_id'] . "</span></p>";
        }
        
        if (!empty($error_data['context']['file'])) {
            $html .= "<p><span class='label'>Fichier:</span> <span class='value'>" . htmlspecialchars($error_data['context']['file']) . "</span></p>";
        }
        
        if (!empty($error_data['context']['line'])) {
            $html .= "<p><span class='label'>Ligne:</span> <span class='value'>" . $error_data['context']['line'] . "</span></p>";
        }
        
        $html .= "</div>";
        
        // Contexte additionnel si disponible
        if (!empty($error_data['context']) && is_array($error_data['context'])) {
            $html .= "<div class='error-details'>
                <p class='label'>Contexte technique:</p>
                <pre style='background: #f8fafc; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px;'>" . 
                htmlspecialchars(json_encode($error_data['context'], JSON_PRETTY_PRINT)) . 
                "</pre>
            </div>";
        }
        
        $html .= "</div>
    
    <div class='footer'>
        <p><small>Alerte générée depuis le portail Guldagil - " . date('d/m/Y H:i:s') . "</small></p>
        <p><small>Consultez les logs complets dans l'interface d'administration</small></p>
    </div>
</body>
</html>";
        
        return $html;
    }
    
    public function sendAlert($error_data) {
        // Vérifier cooldown pour éviter spam
        if ($this->isInCooldown($error_data)) {
            return false;
        }
        
        // Email aux admins
        if ($this->config['enable_email']) {
            $this->sendEmailAlert($error_data);
        }
        
        // Dashboard admin (session flash)
        if ($this->config['enable_dashboard']) {
            $this->setDashboardAlert($error_data);
        }
        
        $this->setCooldown($error_data);
        return true;
    }
    
    private function sendEmailAlert($error_data) {
        $subject = "[CRITIQUE] Erreur Portail Guldagil - " . $error_data['type'];
        $body = $this->formatEmailBody($error_data);
        
        // Headers email
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: noreply@guldagil.com',
            'Reply-To: noreply@guldagil.com',
            'X-Priority: 1',
            'X-MSMail-Priority: High',
            'Importance: high'
        ];
        
        // Tentative d'envoi
        $success = mail(
            $this->config['admin_email'],
            $subject,
            $body,
            implode("\r\n", $headers)
        );
        
        // Log de la tentative
        if ($success) {
            error_log("EMAIL ALERT SENT: {$subject} to {$this->config['admin_email']}");
        } else {
            error_log("EMAIL ALERT FAILED: {$subject}");
        }
        
        return $success;
    }
    
    private function setDashboardAlert($error_data) {
        // Stockage en session pour affichage dans admin
        if (!isset($_SESSION['admin_alerts'])) {
            $_SESSION['admin_alerts'] = [];
        }
        
        $_SESSION['admin_alerts'][] = [
            'type' => 'error_critical',
            'message' => $error_data['message'],
            'module' => $error_data['module'],
            'timestamp' => $error_data['timestamp'],
            'level' => $error_data['level']
        ];
        
        // Limiter à 10 alertes max
        $_SESSION['admin_alerts'] = array_slice($_SESSION['admin_alerts'], -10);
    }
    
    /**
     * 🔔 Notifications Avancées
     * Intégration Slack pour équipe dev
     * TODO: Implémenter pour v1.0
     */
    public function configureSlack($webhook) {
        // Intégration Slack pour équipe dev
        // TODO: Stocker le webhook et envoyer les alertes critiques
        $this->config['slack_webhook'] = $webhook;
    }

    /**
     * Seuils personnalisés par module
     * TODO: Implémenter pour v1.0
     */
    public function setThresholds($module, $limits) {
        // Seuils personnalisés par module
        // Ex: max 5 erreurs/heure pour "port"
        // TODO: Stocker et appliquer les limites pour chaque module
        if (!isset($this->config['thresholds'])) {
            $this->config['thresholds'] = [];
        }
        $this->config['thresholds'][$module] = $limits;
    }
    
    /**
     * Configuration email depuis config.php
     */
    public function setEmailConfig($email, $enabled = true) {
        $this->config['admin_email'] = $email;
        $this->config['enable_email'] = $enabled;
    }
    
    /**
     * Nettoyage du cache cooldown (appelé périodiquement)
     */
    public function cleanupCooldownCache() {
        $current_time = time();
        foreach ($this->cooldown_cache as $key => $timestamp) {
            if (($current_time - $timestamp) > $this->alert_cooldown) {
                unset($this->cooldown_cache[$key]);
            }
        }
    }
}
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
    
    public function __construct() {
        $this->config = [
            'admin_email' => 'admin@guldagil.com', // TODO: Config dans config.php
            'enable_email' => true,
            'enable_dashboard' => true
        ];
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
        
        // TODO: Implémentation email (mail() ou PHPMailer)
        // Pour l'instant, log de la tentative
        error_log("EMAIL ALERT: $subject");
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
            'timestamp' => $error_data['timestamp']
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
    }

    /**
     * Seuils personnalisés par module
     * TODO: Implémenter pour v1.0
     */
    public function setThresholds($module, $limits) {
        // Seuils personnalisés par module
        // Ex: max 5 erreurs/heure pour "port"
        // TODO: Stocker et appliquer les limites pour chaque module
    }
    
    // TODO: Intégration avec dashboard admin existant
    // TODO: Support Slack/Teams webhooks
}
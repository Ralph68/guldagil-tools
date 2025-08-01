<?php
/**
 * Titre: Gestionnaire central des erreurs
 * Chemin: /core/error/error_manager.php
 * Version: 0.5 beta + build auto
 */

class ErrorManager 
{
    private static $instance = null;
    private $logger;
    private $notifier;
    private $handlers = [];
    
    // Types d'erreurs supportés
    const CRITICAL = 'critical';
    const DATABASE = 'database';
    const AUTH = 'auth';
    const MODULE = 'module';
    const VALIDATION = 'validation';
    const TRANSPORT = 'transport';
    
    private function __construct() {
        $this->logger = new ErrorLogger();
        $this->notifier = new ErrorNotifier();
        $this->initializeHandlers();
        $this->registerGlobalHandlers();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Point d'entrée principal pour logger une erreur
     */
    public function handleError($type, $message, $context = [], $level = 'error') {
        $error_data = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'module' => $context['module'] ?? 'unknown',
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
        
        // Logging
        $this->logger->log($error_data);
        
        // Notifications pour erreurs critiques
        if ($level === 'critical' || $type === self::CRITICAL) {
            $this->notifier->sendAlert($error_data);
        }
        
        // Handlers spécialisés
        if (isset($this->handlers[$type])) {
            $this->handlers[$type]->handle($error_data);
        }
        
        return $error_data;
    }
    
    /**
     * Raccourcis pour types d'erreurs courants
     */
    public function logCritical($message, $context = []) {
        return $this->handleError(self::CRITICAL, $message, $context, 'critical');
    }
    
    public function logDatabase($message, $context = []) {
        return $this->handleError(self::DATABASE, $message, $context, 'error');
    }
    
    public function logAuth($message, $context = []) {
        return $this->handleError(self::AUTH, $message, $context, 'warning');
    }
    
    public function logModule($message, $context = []) {
        return $this->handleError(self::MODULE, $message, $context, 'error');
    }
    
    /**
     * Récupération erreurs récentes pour dashboard
     */
    public function getRecentErrors($hours = 24) {
        return $this->logger->getRecentErrors($hours);
    }
    
    /**
     * Statistiques pour le scanner
     */
    public function getErrorStats() {
        return $this->logger->getStats();
    }

    /**
     * 📈 Analytics Avancées
     * Tendances d'erreurs sur X jours (pour graphiques dashboard)
     * TODO: Implémenter pour v1.0
     */
    public function getErrorTrends($days = 7) {
        // Tendances d'erreurs sur X jours
        // Graphiques pour dashboard
        // TODO: Récupérer et agréger les erreurs par jour
        return [];
    }

    /**
     * Score de santé par module (alertes préventives)
     * TODO: Implémenter pour v1.0
     */
    public function getModuleHealth() {
        // Score de santé par module
        // Alertes préventives
        // TODO: Calculer un score basé sur les erreurs récentes par module
        return [];
    }
}
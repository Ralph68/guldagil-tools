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
        $this->notifier = $this->createNotifier();
        $this->initializeHandlers();
        $this->registerGlobalHandlers();
    }
    
    /**
     * Crée le notifier selon disponibilité
     */
    private function createNotifier() {
        if (class_exists('ErrorNotifier')) {
            return new ErrorNotifier();
        }
        // Fallback basique si ErrorNotifier n'existe pas
        return new class {
            public function sendAlert($error_data) {
                // TODO: Implémenter notifications basiques
                error_log("ALERT: " . $error_data['type'] . " - " . $error_data['message']);
            }
        };
    }
    
    /**
     * Initialise les handlers spécialisés par type d'erreur
     */
    private function initializeHandlers() {
        // Handler pour erreurs critiques
        $this->handlers[self::CRITICAL] = new class {
            public function handle($error_data) {
                // Actions spéciales pour erreurs critiques
                error_log("CRITICAL ERROR: " . json_encode($error_data));
                
                // TODO: Notifications d'urgence
                // TODO: Création ticket automatique
                // TODO: Arrêt de fonctionnalités si nécessaire
            }
        };
        
        // Handler pour erreurs base de données
        $this->handlers[self::DATABASE] = new class {
            public function handle($error_data) {
                // Logging spécialisé BDD
                error_log("DB ERROR: " . $error_data['message']);
                
                // TODO: Tentatives de reconnexion
                // TODO: Mode dégradé
                // TODO: Stats de santé BDD
            }
        };
        
        // Handler pour erreurs d'authentification
        $this->handlers[self::AUTH] = new class {
            public function handle($error_data) {
                // Sécurité renforcée
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                error_log("AUTH ERROR from {$ip}: " . $error_data['message']);
                
                // TODO: Détection tentatives d'intrusion
                // TODO: Blocage IP si répétitif
                // TODO: Alertes sécurité
            }
        };
        
        // Handler pour erreurs de modules
        $this->handlers[self::MODULE] = new class {
            public function handle($error_data) {
                $module = $error_data['module'] ?? 'unknown';
                error_log("MODULE ERROR in {$module}: " . $error_data['message']);
                
                // TODO: Désactivation module si critique
                // TODO: Stats de fiabilité par module
            }
        };
        
        // Handler pour erreurs de validation
        $this->handlers[self::VALIDATION] = new class {
            public function handle($error_data) {
                // Tracking des erreurs de validation
                error_log("VALIDATION ERROR: " . $error_data['message']);
                
                // TODO: Analytics des erreurs utilisateur
                // TODO: Amélioration UX basée sur erreurs fréquentes
            }
        };
        
        // Handler pour erreurs de transport/réseau
        $this->handlers[self::TRANSPORT] = new class {
            public function handle($error_data) {
                // Problèmes de connectivité
                error_log("TRANSPORT ERROR: " . $error_data['message']);
                
                // TODO: Retry automatique
                // TODO: Monitoring réseau
                // TODO: Fallback sur services alternatifs
            }
        };
    }
    
    /**
     * Enregistre les handlers globaux PHP
     */
    private function registerGlobalHandlers() {
        // Gestionnaire d'erreurs PHP global
        set_error_handler([$this, 'handlePhpError']);
        
        // Gestionnaire d'exceptions non catchées
        set_exception_handler([$this, 'handleUncaughtException']);
        
        // Gestionnaire d'erreurs fatales
        register_shutdown_function([$this, 'handleFatalError']);
    }
    
    /**
     * Handler pour erreurs PHP natives
     */
    public function handlePhpError($severity, $message, $file, $line) {
        // Mapping des niveaux PHP vers nos types
        $error_types = [
            E_ERROR => self::CRITICAL,
            E_WARNING => self::MODULE,
            E_NOTICE => self::VALIDATION,
            E_USER_ERROR => self::CRITICAL,
            E_USER_WARNING => self::MODULE,
            E_USER_NOTICE => self::VALIDATION
        ];
        
        $type = $error_types[$severity] ?? self::MODULE;
        $level = ($severity & (E_ERROR | E_USER_ERROR)) ? 'critical' : 'error';
        
        $this->handleError($type, $message, [
            'file' => $file,
            'line' => $line,
            'severity' => $severity,
            'module' => 'php'
        ], $level);
        
        // Continuer l'exécution pour erreurs non critiques
        return !($severity & (E_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR));
    }
    
    /**
     * Handler pour exceptions non catchées
     */
    public function handleUncaughtException($exception) {
        $this->handleError(self::CRITICAL, $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'module' => 'uncaught_exception'
        ], 'critical');
    }
    
    /**
     * Handler pour erreurs fatales
     */
    public function handleFatalError() {
        $error = error_get_last();
        if ($error && ($error['type'] & (E_ERROR | E_PARSE | E_COMPILE_ERROR))) {
            $this->handleError(self::CRITICAL, $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type'],
                'module' => 'fatal_error'
            ], 'critical');
        }
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
<?php
/**
 * Titre: Configuration DEBUG sécurisée - PRODUCTION READY
 * Chemin: /config/debug.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    exit('Accès direct interdit');
}

// ⚠️ VÉRIFICATION AVANT DÉFINITION
if (defined('DEBUG')) {
    return; // DEBUG déjà défini, ne pas redéfinir
}

// =====================================
// SYSTÈME DE TOGGLE ADMIN
// =====================================

/**
 * Vérifie si le debug est autorisé via toggle admin
 */
function isDebugEnabledByAdmin(): bool {
    $debug_file = ROOT_PATH . '/storage/cache/debug_mode.json';
    
    if (!file_exists($debug_file)) {
        return false;
    }
    
    $content = @file_get_contents($debug_file);
    if (!$content) {
        return false;
    }
    
    $data = @json_decode($content, true);
    if (!$data) {
        return false;
    }
    
    // Vérifier expiration (max 24h)
    if (isset($data['expires']) && time() > $data['expires']) {
        @unlink($debug_file);
        return false;
    }
    
    return ($data['enabled'] ?? false) === true;
}

/**
 * Active/désactive le mode debug temporairement
 */
function setDebugMode(bool $enabled, int $duration_hours = 24): bool {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        return false;
    }
    
    $debug_file = ROOT_PATH . '/storage/cache/debug_mode.json';
    $cache_dir = dirname($debug_file);
    
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }
    
    if ($enabled) {
        $data = [
            'enabled' => true,
            'enabled_by' => $_SESSION['username'] ?? 'admin',
            'enabled_at' => time(),
            'expires' => time() + ($duration_hours * 3600),
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $result = @file_put_contents($debug_file, json_encode($data, JSON_PRETTY_PRINT));
        
        // Log activation
        error_log("DEBUG MODE ACTIVÉ par " . ($data['enabled_by']) . " IP: " . $data['user_ip']);
        
    } else {
        $result = @unlink($debug_file);
        
        // Log désactivation
        error_log("DEBUG MODE DÉSACTIVÉ par " . ($_SESSION['username'] ?? 'admin'));
    }
    
    return $result !== false;
}

// =====================================
// DÉTECTION ENVIRONNEMENT
// =====================================

// Environnement depuis variable ou config
$environment = getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production');
$is_development = in_array($environment, ['development', 'dev']);

// IPs autorisées UNIQUEMENT en développement
$debug_allowed_ips = [
    '127.0.0.1',
    '::1',
    '192.168.1.100'  // Ajouter IPs spécifiques si nécessaire
];

// IP actuelle
$current_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

// Détection locale
$is_local = in_array($current_ip, ['127.0.0.1', '::1']) || 
            strpos($current_ip, '192.168.') === 0 || 
            strpos($current_ip, '10.') === 0;

// =====================================
// LOGIQUE DEBUG SÉCURISÉE
// =====================================

$debug_enabled = false;

// 1. JAMAIS en production (sauf toggle admin temporaire)
if ($environment === 'production') {
    $debug_enabled = isDebugEnabledByAdmin();
    
    if ($debug_enabled) {
        error_log("SECURITY ALERT: Debug mode temporairement activé en production");
    }
    
} else {
    // 2. En développement : IP autorisée OU toggle admin
    $debug_enabled = (
        $is_development && 
        (in_array($current_ip, $debug_allowed_ips) || $is_local)
    ) || isDebugEnabledByAdmin();
}

// ✅ DÉFINITION FINALE SÉCURISÉE
define('DEBUG', $debug_enabled);

// =====================================
// CONFIGURATION ERREURS
// =====================================

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');
    
    // Log activation avec contexte
    error_log(sprintf(
        "DEBUG MODE ACTIF - Env: %s, IP: %s, User: %s",
        $environment,
        $current_ip,
        $_SESSION['username'] ?? 'unknown'
    ));
    
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

// =====================================
// FONCTIONS UTILITAIRES SÉCURISÉES
// =====================================

/**
 * Affichage debug sécurisé
 */
function debugInfo($data, $label = 'DEBUG') {
    if (!DEBUG) return;
    
    echo '<div style="background:#1f2937;color:#f9fafb;padding:1rem;margin:1rem 0;border-radius:6px;font-family:monospace;font-size:0.875rem;border:2px solid #ef4444;">';
    echo '<strong style="color:#ef4444;">🔧 DEBUG MODE</strong> - ';
    echo '<strong style="color:#fbbf24;">' . htmlspecialchars($label) . ':</strong><br>';
    echo '<pre style="margin:0.5rem 0;color:#d1d5db;">' . htmlspecialchars(print_r($data, true)) . '</pre>';
    echo '<div style="font-size:0.75rem;color:#9ca3af;margin-top:0.5rem;">';
    echo 'IP: ' . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' | ';
    echo 'User: ' . htmlspecialchars($_SESSION['username'] ?? 'guest') . ' | ';
    echo 'Time: ' . date('H:i:s');
    echo '</div>';
    echo '</div>';
}

/**
 * Log debug sécurisé
 */
function debugLog($message, $context = []) {
    if (!DEBUG) return;
    
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => 'DEBUG',
        'message' => $message,
        'context' => $context,
        'user' => $_SESSION['username'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    $log_file = ROOT_PATH . '/storage/logs/debug.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $log_line = json_encode($log_data, JSON_UNESCAPED_UNICODE) . "\n";
    @file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}

/**
 * Barre debug visible en mode debug
 */
function showDebugBar($user_data = null) {
    if (!DEBUG) return '';
    
    $user_name = $user_data['username'] ?? $_SESSION['username'] ?? 'guest';
    $user_role = $user_data['role'] ?? $_SESSION['user_role'] ?? 'visitor';
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $session_id = session_id() ? substr(session_id(), 0, 8) . '...' : 'none';
    $environment = defined('APP_ENV') ? APP_ENV : 'unknown';
    
    return '<div style="background:#dc2626;color:white;padding:0.5rem;text-align:center;font-family:monospace;font-size:0.875rem;border-bottom:3px solid #991b1b;position:sticky;top:0;z-index:9999;">' .
           '🔧 DEBUG MODE ACTIF - ⚠️ ENVIRONNEMENT: ' . strtoupper($environment) . ' | ' .
           'User: ' . htmlspecialchars($user_name) . ' (' . htmlspecialchars($user_role) . ') | ' .
           date('H:i:s') . ' | IP: ' . htmlspecialchars($current_ip) . ' | Session: ' . htmlspecialchars($session_id) .
           '</div>';
}

// =====================================
// VARIABLES GLOBALES
// =====================================

$GLOBALS['DEBUG_MODE'] = DEBUG;
$GLOBALS['ENVIRONMENT'] = $environment;
$GLOBALS['DEBUG_CONTEXT'] = [
    'enabled_at' => time(),
    'environment' => $environment,
    'ip' => $current_ip,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

// =====================================
// ALERTES SÉCURITÉ
// =====================================

// Alerte critique si debug en production
if (DEBUG && $environment === 'production') {
    error_log('SECURITY CRITICAL: DEBUG mode is enabled in PRODUCTION environment!');
    
    // Notification admin si possible
    if (function_exists('mail') && defined('APP_ADMIN_EMAIL')) {
        $subject = 'ALERT: Debug mode enabled in production';
        $message = sprintf(
            "Debug mode has been enabled in production environment.\n\n" .
            "Time: %s\n" .
            "IP: %s\n" .
            "User: %s\n" .
            "User-Agent: %s\n\n" .
            "This should be disabled immediately.",
            date('Y-m-d H:i:s'),
            $current_ip,
            $_SESSION['username'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        @mail(APP_ADMIN_EMAIL, $subject, $message);
    }
}

// Log activation initiale
if (DEBUG) {
    debugLog('Mode debug activé', [
        'environment' => $environment,
        'method' => isDebugEnabledByAdmin() ? 'admin_toggle' : 'auto_detection',
        'ip' => $current_ip,
        'is_local' => $is_local,
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
}

?>

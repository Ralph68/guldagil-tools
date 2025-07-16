<?php
/**
 * Titre: Configuration du mode debug - CORRIGÉE
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

// Détection automatique de l'environnement
$environment = getenv('APP_ENV') ?: 'production';
$is_development = ($environment === 'development' || $environment === 'dev');

// IPs autorisées pour le debug
$debug_allowed_ips = [
    '127.0.0.1',
    '::1',
    '192.168.1.100'
];

// IP actuelle
$current_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

// Détection locale
$is_local = in_array($current_ip, ['127.0.0.1', '::1']) || 
            strpos($current_ip, '192.168.') === 0 || 
            strpos($current_ip, '10.') === 0;

// Mode debug activé si conditions OK
$debug_enabled = (
    $is_development && 
    (in_array($current_ip, $debug_allowed_ips) || $is_local)
);

// Forcer désactivation en production
if ($environment === 'production') {
    $debug_enabled = false;
}

// ✅ DÉFINITION SÉCURISÉE DE DEBUG
define('DEBUG', $debug_enabled);

// Configuration erreurs
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

// Fonctions debug
function debugInfo($data, $label = 'DEBUG') {
    if (!DEBUG) return;
    
    echo '<div style="background:#1f2937;color:#f9fafb;padding:1rem;margin:1rem 0;border-radius:6px;font-family:monospace;font-size:0.875rem;">';
    echo '<strong style="color:#fbbf24;">' . htmlspecialchars($label) . ':</strong><br>';
    echo '<pre style="margin:0.5rem 0;color:#d1d5db;">' . htmlspecialchars(print_r($data, true)) . '</pre>';
    echo '</div>';
}

function debugLog($message, $context = []) {
    if (!DEBUG) return;
    
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $log_file = ROOT_PATH . '/storage/logs/debug.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);
}

function showDebugBar($user_data = null) {
    if (!DEBUG) return '';
    
    $user_name = $user_data['username'] ?? 'guest';
    $user_role = $user_data['role'] ?? 'visitor';
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $session_id = session_id() ? substr(session_id(), 0, 8) . '...' : 'none';
    
    return '<div style="background:#dc2626;color:white;padding:0.5rem;text-align:center;font-family:monospace;font-size:0.875rem;border-bottom:2px solid #991b1b;">' .
           '🔧 DEBUG MODE - ' . htmlspecialchars($user_name) . ' (' . htmlspecialchars($user_role) . ') | ' .
           date('H:i:s') . ' | IP: ' . htmlspecialchars($current_ip) . ' | Session: ' . htmlspecialchars($session_id) .
           '</div>';
}

// Variables globales
$GLOBALS['DEBUG_MODE'] = DEBUG;
$GLOBALS['ENVIRONMENT'] = $environment;

// Logger activation si debug
if (DEBUG) {
    debugLog('Mode debug activé', [
        'environment' => $environment,
        'ip' => $current_ip,
        'is_local' => $is_local,
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
}

// Avertissement sécurité
if (DEBUG && $environment === 'production') {
    error_log('SECURITY WARNING: DEBUG mode enabled in production!');
}
?>

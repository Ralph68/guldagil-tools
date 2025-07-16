<?php
/**
 * Titre: Configuration du mode debug
 * Chemin: /config/debug.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    exit('Accès direct interdit');
}

// =====================================
// 🔧 CONFIGURATION MODE DEBUG
// =====================================

// Détection automatique de l'environnement
$environment = getenv('APP_ENV') ?: 'production';
$is_development = ($environment === 'development' || $environment === 'dev');

// IPs autorisées pour le debug (développeurs)
$debug_allowed_ips = [
    '127.0.0.1',        // Localhost IPv4
    '::1',              // Localhost IPv6
    '192.168.1.100',    // IP locale développeur (à adapter)
    // Ajoutez vos IPs de développement ici
];

// IP actuelle du visiteur
$current_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? 'unknown';

// Détection si on est en développement local
$is_local = in_array($current_ip, ['127.0.0.1', '::1']) || 
            strpos($current_ip, '192.168.') === 0 || 
            strpos($current_ip, '10.') === 0;

// =====================================
// 🎯 DÉFINITION DU MODE DEBUG
// =====================================

// Mode debug activé UNIQUEMENT si :
// 1. Variable d'environnement le permet ET
// 2. IP autorisée OU environnement de développement
$debug_enabled = (
    $is_development && 
    (in_array($current_ip, $debug_allowed_ips) || $is_local)
);

// Forcer désactivation en production
if ($environment === 'production') {
    $debug_enabled = false;
}

// Définir la constante DEBUG
define('DEBUG', $debug_enabled);

// =====================================
// 📝 CONFIGURATION ERREURS PHP
// =====================================

if (DEBUG) {
    // Mode développement : afficher toutes les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');
} else {
    // Mode production : masquer les erreurs, les logger
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

// =====================================
// 🔐 FONCTIONS DEBUG SÉCURISÉES
// =====================================

/**
 * Fonction pour afficher des informations de debug de manière sécurisée
 */
function debugInfo($data, $label = 'DEBUG') {
    if (!DEBUG) return;
    
    echo '<div style="background:#1f2937;color:#f9fafb;padding:1rem;margin:1rem 0;border-radius:6px;font-family:monospace;font-size:0.875rem;">';
    echo '<strong style="color:#fbbf24;">' . htmlspecialchars($label) . ':</strong><br>';
    echo '<pre style="margin:0.5rem 0;color:#d1d5db;">' . htmlspecialchars(print_r($data, true)) . '</pre>';
    echo '</div>';
}

/**
 * Fonction pour logger en mode debug
 */
function debugLog($message, $context = []) {
    if (!DEBUG) return;
    
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100)
    ];
    
    $log_file = ROOT_PATH . '/storage/logs/debug.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Afficher la barre de debug seulement si autorisé
 */
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

// =====================================
// 📊 INFORMATIONS DEBUG
// =====================================

if (DEBUG) {
    // Logger l'activation du mode debug
    debugLog('Mode debug activé', [
        'environment' => $environment,
        'ip' => $current_ip,
        'is_local' => $is_local,
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
}

// =====================================
// 🚨 SÉCURITÉ ET AVERTISSEMENTS
// =====================================

// Avertissement si debug activé en production (ne devrait jamais arriver)
if (DEBUG && $environment === 'production') {
    error_log('SECURITY WARNING: DEBUG mode enabled in production environment!');
    
    // Optionnel : envoyer une alerte email en cas de debug en production
    if (defined('ADMIN_EMAIL')) {
        $subject = 'ALERTE SÉCURITÉ: Debug activé en production';
        $message = 'Le mode debug est activé sur le serveur de production. Désactivez-le immédiatement.';
        mail(ADMIN_EMAIL, $subject, $message);
    }
}

// Variables globales pour templates
$GLOBALS['DEBUG_MODE'] = DEBUG;
$GLOBALS['ENVIRONMENT'] = $environment;

/**
 * UTILISATION DANS LES TEMPLATES :
 * 
 * Dans header.php, remplacer la barre de debug par :
 * <?= showDebugBar($current_user) ?>
 * 
 * Pour afficher des infos debug :
 * debugInfo($_SESSION, 'Session Data');
 * debugInfo($current_user, 'User Info');
 * 
 * Pour logger des événements :
 * debugLog('Utilisateur connecté', ['user' => $username]);
 */

// Message de confirmation
if (DEBUG) {
    // En mode debug, confirmer le chargement
    debugLog('Configuration debug chargée', [
        'file' => __FILE__,
        'debug_enabled' => DEBUG,
        'environment' => $environment
    ]);
}
?>

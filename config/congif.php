<?php
/**
 * Titre: Configuration principale COMPLÈTE
 * Chemin: /config/config.php
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
    define('CONFIG_PATH', ROOT_PATH . '/config');
}

// Démarrage session sécurisé si pas déjà fait
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Timezone
date_default_timezone_set('Europe/Paris');

// Chemins de base
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// URL de base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . ($scriptPath !== '/' ? $scriptPath : ''));

// Chargement des variables d'environnement
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile, false, INI_SCANNER_TYPED);
    if ($env !== false) {
        foreach ($env as $key => $value) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }
    }
}

// Configuration base de données
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'guldagil_portal');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Détection environnement
$isProduction = (getenv('APP_ENV') === 'production');
$isDebug = !$isProduction && (isset($_GET['debug']) || getenv('DEBUG') === 'true');

// Configuration des erreurs
if ($isDebug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    define('DEBUG', true);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    define('DEBUG', false);
}

// Chargement des configurations séparées
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/auth_database.php';
require_once CONFIG_PATH . '/functions.php';

// Configuration des modules - DEPUIS VOTRE BACKUP
const MODULES = [
    'calculateur' => [
        'name' => 'Calculateur Frais de Port',
        'description' => 'Calcul automatique des frais de transport',
        'version' => '0.5.1',
        'status' => 'active',
        'public' => true,
        'auth_required' => false,
        'color' => '#3498db'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses',
        'version' => '0.5.0',
        'status' => 'development',
        'public' => false,
        'auth_required' => true,
        'color' => '#e74c3c'
    ],
    'controle-qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Suivi qualité des marchandises',
        'version' => '0.5.0',
        'status' => 'active',
        'public' => true,
        'auth_required' => false,
        'color' => '#2ecc71'
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Gestion et configuration du portail',
        'version' => '0.5.0',
        'status' => 'active',
        'public' => false,
        'auth_required' => true,
        'color' => '#9b59b6'
    ]
];

// Configuration cache
const CACHE_CONFIG = [
    'enabled' => true,
    'default_ttl' => 3600,
    'path' => STORAGE_PATH . '/cache'
];

// Configuration logs
const LOG_CONFIG = [
    'enabled' => true,
    'level' => DEBUG ? 'debug' : 'error',
    'max_file_size' => 10 * 1024 * 1024,
    'max_files' => 5,
    'channels' => [
        'app' => STORAGE_PATH . '/logs/app.log',
        'error' => STORAGE_PATH . '/logs/error.log',
        'access' => STORAGE_PATH . '/logs/access.log'
    ]
];

// Fonctions utilitaires
function isModuleEnabled($module): bool {
    return isset(MODULES[$module]) && MODULES[$module]['status'] === 'active';
}

function getModuleInfo($module): ?array {
    return MODULES[$module] ?? null;
}

function hasModuleAccess($module, $user = null): bool {
    if (!isModuleEnabled($module)) {
        return false;
    }
    
    $moduleConfig = MODULES[$module];
    
    if ($moduleConfig['public']) {
        return true;
    }
    
    if (DEBUG) {
        return true;
    }
    
    if (isset($moduleConfig['auth_required']) && $moduleConfig['auth_required']) {
        return true; // Temporaire pour le développement
    }
    
    return false;
}

// Initialisation du système
try {
    if (!is_dir(CACHE_CONFIG['path'])) {
        mkdir(CACHE_CONFIG['path'], 0755, true);
    }
    
    foreach (LOG_CONFIG['channels'] as $channel => $logFile) {
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
} catch (Exception $e) {
    if (DEBUG) {
        error_log("Erreur d'initialisation: " . $e->getMessage());
    }
}

// Chargement automatique du fichier version
if (file_exists(CONFIG_PATH . '/version.php')) {
    require_once CONFIG_PATH . '/version.php';
}

// Log du démarrage
if (function_exists('logMessage')) {
    logMessage('info', 'Configuration chargée - Version: ' . (defined('APP_VERSION') ? APP_VERSION : 'inconnue'));
}
?>

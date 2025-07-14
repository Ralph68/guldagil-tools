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

// Démarrage session sécurisé si pas déjà fait
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Chargement des configurations séparées
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/auth_database.php';
require_once CONFIG_PATH . '/functions.php';

// Configuration des modules
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
        'name' => 'Module ADR',
        'description' => 'Gestion des matières dangereuses',
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
        'description' => 'Gestion et configuration',
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

function logMessage($level, $message, $channel = 'app'): void {
    if (!LOG_CONFIG['enabled']) return;
    
    $logFile = LOG_CONFIG['channels'][$channel] ?? LOG_CONFIG['channels']['app'];
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    if (file_exists($logFile) && filesize($logFile) > LOG_CONFIG['max_file_size']) {
        $backupFile = $logFile . '.' . date('Y-m-d-H-i-s');
        rename($logFile, $backupFile);
        cleanOldLogFiles($logDir, LOG_CONFIG['max_files']);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function cleanOldLogFiles($directory, $maxFiles): void {
    $files = glob($directory . '/*.log.*');
    if (count($files) > $maxFiles) {
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $filesToDelete = array_slice($files, 0, count($files) - $maxFiles);
        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }
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

function getFromCache($key, $default = null) {
    if (!CACHE_CONFIG['enabled']) {
        return $default;
    }
    
    $cacheFile = STORAGE_PATH . '/cache/' . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return $default;
    }
    
    $data = unserialize(file_get_contents($cacheFile));
    
    if ($data['expires'] < time()) {
        unlink($cacheFile);
        return $default;
    }
    
    return $data['value'];
}

function putInCache($key, $value, $ttl = null): bool {
    if (!CACHE_CONFIG['enabled']) {
        return false;
    }
    
    $ttl = $ttl ?? CACHE_CONFIG['default_ttl'];
    $cacheDir = STORAGE_PATH . '/cache';
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
    $data = [
        'value' => $value,
        'expires' => time() + $ttl,
        'created' => time()
    ];
    
    return file_put_contents($cacheFile, serialize($data), LOCK_EX) !== false;
}

// Configuration cache avancée
const CACHE_PREFIX = 'guldagil_';
const CACHE_DEFAULT_TTL = 3600;

function getCacheKey($key) {
    return CACHE_PREFIX . md5($key);
}

function cacheResult($key, $ttl = CACHE_DEFAULT_TTL) {
    return function($func) use ($key, $ttl) {
        $cacheKey = getCacheKey($key);
        $cached = getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        $result = $func();
        putInCache($cacheKey, $result, $ttl);
        return $result;
    };
}

// Initialisation du système
try {
    // Initialisation du cache
    if (!is_dir(CACHE_CONFIG['path'])) {
        mkdir(CACHE_CONFIG['path'], 0755, true);
    }
    
    // Initialisation des logs
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

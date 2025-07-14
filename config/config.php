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

// Configuration base de données (définie ici pour éviter doublons)
if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'guldagil_portal');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
}

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

// Démarrage session sécurisé
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Chargement des configurations séparées avec vérification
$config_files = [
    CONFIG_PATH . '/database.php',
    CONFIG_PATH . '/auth_database.php', 
    CONFIG_PATH . '/functions.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Configuration des modules (compatible avec structure existante)
define('MODULES', [
    'calculateur' => [
        'enabled' => true,
        'public' => true,
        'name' => 'Calculateur Frais de Port',
        'description' => 'Calcul automatique des frais de transport',
        'version' => '0.5.1',
        'status' => 'active',
        'auth_required' => false,
        'color' => '#3498db'
    ],
    'port' => [
        'enabled' => true,
        'public' => true,
        'name' => 'Calculateur Port',
        'description' => 'Module de calcul des frais de port',
        'version' => '0.5.0',
        'status' => 'active',
        'auth_required' => false,
        'color' => '#2ecc71'
    ],
    'adr' => [
        'enabled' => true,
        'public' => false,
        'name' => 'Module ADR',
        'description' => 'Gestion des matières dangereuses',
        'version' => '0.5.0',
        'status' => 'development',
        'auth_required' => true,
        'color' => '#e74c3c'
    ],
    'admin' => [
        'enabled' => true,
        'public' => false,
        'name' => 'Administration',
        'description' => 'Gestion et configuration',
        'version' => '0.5.0',
        'status' => 'active',
        'auth_required' => true,
        'color' => '#9b59b6'
    ]
]);

// Configuration cache
define('CACHE_CONFIG', [
    'enabled' => !DEBUG,
    'default_ttl' => 3600,
    'path' => STORAGE_PATH . '/cache'
]);

// Configuration logs
define('LOG_CONFIG', [
    'enabled' => true,
    'level' => DEBUG ? 'debug' : 'error',
    'max_file_size' => 10 * 1024 * 1024,
    'max_files' => 5,
    'channels' => [
        'app' => STORAGE_PATH . '/logs/app.log',
        'error' => STORAGE_PATH . '/logs/error.log',
        'access' => STORAGE_PATH . '/logs/access.log'
    ]
]);

// Fonctions utilitaires compatibles
function isModuleEnabled($module) {
    $modules = MODULES;
    return isset($modules[$module]) && $modules[$module]['enabled'];
}

function getModuleInfo($module) {
    $modules = MODULES;
    return $modules[$module] ?? null;
}

function hasModuleAccess($module, $user = null) {
    if (!isModuleEnabled($module)) {
        return false;
    }
    
    $modules = MODULES;
    $moduleConfig = $modules[$module];
    
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

// Fonction getDB() pour compatibilité
if (!function_exists('getDB')) {
    function getDB() {
        global $db;
        if (!isset($db) || !($db instanceof PDO)) {
            throw new Exception('Connexion base de données non initialisée');
        }
        return $db;
    }
}

// Fonctions cache compatibles
function getFromCache($key, $default = null) {
    $config = CACHE_CONFIG;
    if (!$config['enabled']) {
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

function putInCache($key, $value, $ttl = null) {
    $config = CACHE_CONFIG;
    if (!$config['enabled']) {
        return false;
    }
    
    $ttl = $ttl ?? $config['default_ttl'];
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

// Fonction de log compatible
if (!function_exists('logMessage')) {
    function logMessage($level, $message, $channel = 'app') {
        $config = LOG_CONFIG;
        if (!$config['enabled']) return;
        
        $logFile = $config['channels'][$channel] ?? $config['channels']['app'];
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        if (file_exists($logFile) && filesize($logFile) > $config['max_file_size']) {
            $backupFile = $logFile . '.' . date('Y-m-d-H-i-s');
            rename($logFile, $backupFile);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Chargement version
if (file_exists(__DIR__ . '/version.php')) {
    require_once __DIR__ . '/version.php';
}

// Log du démarrage
logMessage('info', 'Configuration chargée');
?>

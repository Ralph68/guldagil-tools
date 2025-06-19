<?php
/**
 * config/config.php - Configuration principale
 */

// Protection directe
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '2.0.0');
}

// Démarrage session sécurisé si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// Timezone
date_default_timezone_set('Europe/Paris');

// Chemins de base
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// URL de base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . ($scriptPath !== '/' ? $scriptPath : ''));

// Inclusion des fichiers de configuration
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/modules.php';

// Autoloader simple
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Fonctions utilitaires globales
 */

function isModuleEnabled($module) {
    return MODULES[$module]['enabled'] ?? false;
}

function isModulePublic($module) {
    return MODULES[$module]['public'] ?? false;
}

function getModulePath($module) {
    return MODULES[$module]['path'] ?? '/';
}

function dd($var) {
    if (DEBUG) {
        echo "<pre style='background:#000;color:#0f0;padding:15px;margin:10px;border-radius:5px;'>";
        var_dump($var);
        echo "</pre>";
        die();
    }
}

function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message";
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    $logMessage .= PHP_EOL;
    
    $logFile = STORAGE_PATH . '/logs/error.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// ===================================================

/**
 * config/database.php - Configuration base de données
 */

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

// Options PDO
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

// Connexion PDO globale
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $db = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
    
    // Test de la connexion
    $db->query("SELECT 1");
    
} catch (PDOException $e) {
    $errorMessage = DEBUG ? 
        'Erreur de connexion BDD : ' . $e->getMessage() : 
        'Erreur de connexion à la base de données';
    
    logError('Database connection failed', [
        'error' => $e->getMessage(),
        'host' => DB_HOST,
        'database' => DB_NAME
    ]);
    
    // Si requête AJAX, retourner JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $errorMessage]);
        exit;
    }
    
    die('<div style="padding:20px;background:#ffebee;border:1px solid #f44336;border-radius:5px;color:#c62828;font-family:monospace;">' . 
        '<strong>Erreur de connexion:</strong><br>' . htmlspecialchars($errorMessage) . '</div>');
}

// ===================================================

/**
 * config/modules.php - Configuration des modules
 */

// Modules disponibles
define('MODULES', [
    'calculateur' => [
        'enabled' => true,
        'public' => true,
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'path' => '/',
        'icon' => '🧮',
        'version' => '2.0.0'
    ],
    'adr' => [
        'enabled' => true,
        'public' => false,  // Accès restreint
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses',
        'path' => '/adr/',
        'icon' => '⚠️',
        'version' => '1.5.0',
        'auth_required' => true
    ],
    'tracking' => [
        'enabled' => true,
        'public' => true,   // Liens publics
        'name' => 'Suivi expéditions',
        'description' => 'Suivi des colis et accès transporteurs',
        'path' => '/#suivi',
        'icon' => '📦',
        'version' => '1.0.0'
    ],
    'admin' => [
        'enabled' => true,
        'public' => false,  // Admin seulement
        'name' => 'Administration',
        'description' => 'Gestion du système et configuration',
        'path' => '/admin/',
        'icon' => '⚙️',
        'version' => '2.0.0',
        'auth_required' => true,
        'min_role' => 'admin'
    ]
]);

// Configuration ADR spécifique
define('ADR_CONFIG', [
    'session_timeout' => 3600, // 1 heure
    'max_declarations_per_day' => 50,
    'pdf_output_path' => STORAGE_PATH . '/uploads/pdfs/',
    'temp_path' => STORAGE_PATH . '/uploads/temp/',
    'allowed_file_types' => ['xlsx', 'csv', 'pdf'],
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'auto_save_interval' => 30, // secondes
    'backup_retention_days' => 30
]);

// Liens externes transporteurs
define('TRACKING_LINKS', [
    'heppner' => [
        'name' => 'Heppner MyPortal',
        'url' => 'https://myportal.heppner-group.com/home',
        'logo' => '/assets/images/logos/heppner-logo.png',
        'active' => true
    ],
    'xpo' => [
        'name' => 'XPO Connect',
        'url' => 'https://xpoconnecteu.xpo.com/customer/orders/list',
        'logo' => '/assets/images/logos/xpo-logo.png',
        'active' => true
    ],
    'kuehne_nagel' => [
        'name' => 'Kuehne+Nagel Portal',
        'url' => 'https://myportal.kuehne-nagel.com',
        'logo' => '/assets/images/logos/kn-logo.png',
        'active' => true
    ],
    'geodis' => [
        'name' => 'Geodis Portal',
        'url' => 'https://portal.geodis.com',
        'logo' => '/assets/images/logos/geodis-logo.png',
        'active' => false // Désactivé temporairement
    ]
]);

// Configuration sécurité
define('SECURITY_CONFIG', [
    'csrf_protection' => true,
    'session_regenerate_interval' => 1800, // 30 minutes
    'max_login_attempts' => 5,
    'lockout_duration' => 900, // 15 minutes
    'password_min_length' => 8,
    'require_https' => !DEBUG
]);

// ===================================================

/**
 * config/constants.php - Constantes globales
 */

// Versions et informations système
define('SYSTEM_NAME', 'Guldagil Portal');
define('SYSTEM_DESCRIPTION', 'Solution complète de gestion des expéditions et calcul des frais de transport');
define('COMPANY_NAME', 'Guldagil');
define('SUPPORT_EMAIL', 'support@guldagil.fr');

// Configuration cache
define('CACHE_CONFIG', [
    'enabled' => !DEBUG,
    'default_ttl' => 3600, // 1 heure
    'rates_ttl' => 1800,   // 30 minutes pour les tarifs
    'static_ttl' => 86400  // 24 heures pour les données statiques
]);

// Configuration uploads
define('UPLOAD_CONFIG', [
    'max_size' => 10 * 1024 * 1024, // 10MB
    'allowed_types' => [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
        'archives' => ['zip', 'rar']
    ],
    'scan_uploads' => true, // Scanner antivirus si disponible
    'quarantine_suspicious' => true
]);

// Configuration emails
define('EMAIL_CONFIG', [
    'enabled' => !DEBUG,
    'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
    'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
    'smtp_user' => $_ENV['SMTP_USER'] ?? '',
    'smtp_pass' => $_ENV['SMTP_PASS'] ?? '',
    'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@guldagil.fr',
    'from_name' => $_ENV['FROM_NAME'] ?? 'Guldagil Portal'
]);

// Messages d'erreur standards
define('ERROR_MESSAGES', [
    'general' => 'Une erreur est survenue. Veuillez réessayer.',
    'database' => 'Erreur de base de données. Contactez le support.',
    'permission' => 'Vous n\'avez pas les permissions nécessaires.',
    'not_found' => 'Ressource non trouvée.',
    'validation' => 'Données invalides. Vérifiez votre saisie.',
    'maintenance' => 'Site en maintenance. Réessayez plus tard.',
    'rate_limit' => 'Trop de requêtes. Patientez avant de réessayer.'
]);

// Configuration API
define('API_CONFIG', [
    'rate_limit' => 100, // requêtes par minute
    'rate_limit_window' => 60, // secondes
    'max_batch_size' => 50,
    'timeout' => 30,
    'compression' => true,
    'cors_origins' => [
        'https://guldagil.fr',
        'https://portal.guldagil.fr'
    ]
]);

// Configuration logging
define('LOG_CONFIG', [
    'enabled' => true,
    'level' => DEBUG ? 'debug' : 'warning',
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'max_files' => 10,
    'channels' => [
        'app' => STORAGE_PATH . '/logs/app.log',
        'error' => STORAGE_PATH . '/logs/error.log',
        'security' => STORAGE_PATH . '/logs/security.log',
        'adr' => STORAGE_PATH . '/logs/adr.log',
        'api' => STORAGE_PATH . '/logs/api.log'
    ]
]);

// ===================================================

/**
 * includes/functions/helpers.php - Fonctions utilitaires
 */

/**
 * Génère un token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize une chaîne pour l'affichage HTML
 */
function clean($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Formatage des montants
 */
function formatPrice($amount, $currency = '€') {
    return number_format((float)$amount, 2, ',', ' ') . ' ' . $currency;
}

/**
 * Formatage des dates
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (is_string($date)) {
        $date = strtotime($date);
    }
    return date($format, $date);
}

/**
 * Génère un ID unique
 */
function generateUniqueId($prefix = '') {
    return $prefix . uniqid() . bin2hex(random_bytes(4));
}

/**
 * Redirection sécurisée
 */
function redirect($url, $code = 302) {
    if (!headers_sent()) {
        header("Location: $url", true, $code);
        exit;
    }
}

/**
 * Réponse JSON
 */
function jsonResponse($data, $code = 200) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Validation email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validation numérique
 */
function isValidNumber($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    $num = (float)$value;
    if ($min !== null && $num < $min) {
        return false;
    }
    if ($max !== null && $num > $max) {
        return false;
    }
    return true;
}

/**
 * Log sécurisé
 */
function secureLog($message, $level = 'info', $channel = 'app') {
    if (!LOG_CONFIG['enabled']) {
        return;
    }
    
    $logFile = LOG_CONFIG['channels'][$channel] ?? LOG_CONFIG['channels']['app'];
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Créer le dossier si nécessaire
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Rotation des logs si trop volumineux
    if (file_exists($logFile) && filesize($logFile) > LOG_CONFIG['max_file_size']) {
        $backupFile = $logFile . '.' . date('Y-m-d-H-i-s');
        rename($logFile, $backupFile);
        
        // Nettoyer les anciens backups
        cleanOldLogFiles($logDir, LOG_CONFIG['max_files']);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Nettoyage des anciens fichiers de log
 */
function cleanOldLogFiles($directory, $maxFiles) {
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

/**
 * Vérification des permissions de module
 */
function hasModuleAccess($module, $user = null) {
    if (!isModuleEnabled($module)) {
        return false;
    }
    
    $moduleConfig = MODULES[$module];
    
    // Module public
    if ($moduleConfig['public']) {
        return true;
    }
    
    // En mode debug, accès libre
    if (DEBUG) {
        return true;
    }
    
    // Vérification authentification
    if (isset($moduleConfig['auth_required']) && $moduleConfig['auth_required']) {
        // TODO: Implémenter la vérification d'authentification
        // return isUserAuthenticated($user);
        return true; // Temporaire pour le développement
    }
    
    return false;
}

/**
 * Cache simple en fichiers
 */
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

/**
 * Mise en cache
 */
function putInCache($key, $value, $ttl = null) {
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

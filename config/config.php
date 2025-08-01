<?php
/**
 * Titre: Configuration principale COMPLÈTE - Mode Debug
 * Chemin: /config/config.php
 */

$is_development = (getenv('APP_ENV') === 'development');
define('DEBUG', $is_development);
// ACTIVATION DEBUG FORCÉ
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

//echo "=== DEBUG CONFIG.PHP ===\n";

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
    define('CONFIG_PATH', ROOT_PATH . '/config');
    //echo "✅ ROOT_PATH défini: " . ROOT_PATH . "\n";
} else {
    //echo "ℹ️ ROOT_PATH déjà défini: " . ROOT_PATH . "\n";
}

//require_once __DIR__ . '/error_handler_simple.php';

// Timezone
date_default_timezone_set('Europe/Paris');
//echo "✅ Timezone: Europe/Paris\n";

// Chemins de base
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
//echo "✅ Chemins définis\n";

// URL de base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . ($scriptPath !== '/' ? $scriptPath : ''));
//echo "✅ BASE_URL: " . BASE_URL . "\n";

// Chargement des variables d'environnement avec debug
$envFile = ROOT_PATH . '/.env';
//echo "🔍 Recherche .env: $envFile\n";
if (file_exists($envFile)) {
    //echo "✅ Fichier .env trouvé\n";
    $env = parse_ini_file($envFile, false, INI_SCANNER_TYPED);
    if ($env !== false) {
        //echo "✅ Lecture .env réussie (" . count($env) . " variables)\n";
        foreach ($env as $key => $value) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }
    } else {
       // echo "❌ Erreur lecture .env\n";
    }
} else {
    //echo "⚠️ Fichier .env non trouvé\n";
}

// Configuration base de données avec debug
if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'guldagil_portal');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
    
    //echo "✅ Config DB définie:\n";
    //echo "   Host: " . DB_HOST . "\n";
    //echo "   Base: " . DB_NAME . "\n";
    //echo "   User: " . DB_USER . "\n";
    //echo "   Pass: " . (empty(DB_PASS) ? 'vide' : '***') . "\n";
} else {
    //echo "ℹ️ Config DB déjà définie\n";
}

// Test connexion DB
//echo "🔍 Test connexion DB...\n";
try {
    $testConn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    //echo "✅ Connexion DB réussie\n";
    $testConn = null;
} catch(PDOException $e) {
    //echo "❌ Erreur DB: " . $e->getMessage() . "\n";
}

// Configuration des erreurs
if (!defined('DEBUG')) {
    define('DEBUG', false);
}
//echo "✅ DEBUG activé\n";

// Démarrage session
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
    //echo "✅ Session démarrée\n";
} else {
    //echo "ℹ️ Session déjà active\n";
}

// Vérification/création répertoires
$dirs = ['cache', 'logs'];
//echo "🔍 Vérification répertoires...\n";
foreach ($dirs as $dir) {
    $path = STORAGE_PATH . '/' . $dir;
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            //echo "✅ Répertoire créé: $path\n";
        } else {
            //echo "❌ Impossible de créer: $path\n";
        }
    } else {
        //echo "✅ Répertoire existe: $path\n";
    }
}

// Chargement fichiers config
$config_files = [
    ROOT_PATH . '/config/database.php',
    ROOT_PATH . '/config/auth_database.php', 
    ROOT_PATH . '/config/functions.php'
];

//echo "🔍 Chargement fichiers config...\n";
foreach ($config_files as $file) {
    if (file_exists($file)) {
        //echo "✅ Chargement: " . basename($file) . "\n";
        try {
            require_once $file;
            //echo "   ✅ Succès\n";
        } catch (Exception $e) {
            //echo "   ❌ Erreur: " . $e->getMessage() . "\n";
        }
    } else {
        //echo "⚠️ Manquant: " . basename($file) . "\n";
    }
}

// Configuration modules simplifiée
//echo "🔍 Configuration modules...\n";
define('MODULES', [
    'port' => [
        'enabled' => true,
        'public' => true,
        'name' => 'Calculateur Port'
    ]
]);
//echo "✅ MODULES défini\n";

// Configuration cache/logs
define('CACHE_CONFIG', [
    'enabled' => false, // Désactivé en debug
    'default_ttl' => 3600,
    'path' => STORAGE_PATH . '/cache'
]);

define('LOG_CONFIG', [
    'enabled' => true,
    'channels' => [
        'app' => STORAGE_PATH . '/logs/app.log'
    ]
]);
//echo "✅ Cache/Log configurés\n";

// Fonctions de base
function isModuleEnabled($module) {
    $modules = MODULES;
    return isset($modules[$module]) && $modules[$module]['enabled'];
}

function logMessage($level, $message, $channel = 'app') {
   // echo "[LOG $level] $message\n";
}

//echo "✅ Fonctions définies\n";

// Version si disponible
if (file_exists(__DIR__ . '/version.php')) {
    require_once __DIR__ . '/version.php';
    //echo "✅ Version chargée\n";
} else {
    //echo "⚠️ version.php manquant\n";
}
 //require_once __DIR__ . '/debug.php';

//echo "=== FIN DEBUG CONFIG.PHP ===\n";
//echo "Configuration terminée avec succès!\n";

// =====================================
// SÉCURITÉ GÉOLOCALISATION IP FRANÇAISE
// =====================================

// Configuration de la sécurité IP
define('IP_GEOLOCATION_ENABLED', true);
define('IP_GEOLOCATION_BLOCK_MODE', true); // true = bloquer, false = logger seulement
define('IP_GEOLOCATION_ALLOWED_COUNTRIES', ['FR']);
define('IP_GEOLOCATION_BLOCK_METHOD', 'maintenance'); // 'maintenance', 'blank', 'timeout'

// IPs en whitelist (développement, serveurs spécifiques)
define('IP_GEOLOCATION_WHITELIST', [
    '127.0.0.1',        // Localhost
    '::1',              // Localhost IPv6
    // Ajoutez vos IPs de développement ici
    // '192.168.1.100',  // IP locale exemple
]);

// Chargement de la classe de sécurité
if (IP_GEOLOCATION_ENABLED) {
    require_once ROOT_PATH . '/core/security/ip_geolocation.php';
    
    // Méthodes avancées optionnelles
    if (file_exists(ROOT_PATH . '/core/security/stealth_methods.php')) {
        require_once ROOT_PATH . '/core/security/stealth_methods.php';
    }
    
    // Initialisation automatique de la vérification
    try {
        $ip_security = initIpGeolocationSecurity();
        
        // Configuration selon les constantes
        $ip_security->setAllowedCountries(IP_GEOLOCATION_ALLOWED_COUNTRIES);
        
        foreach (IP_GEOLOCATION_WHITELIST as $whitelisted_ip) {
            $ip_security->addWhitelistIp($whitelisted_ip);
        }
        
        // Vérification avec méthode de blocage intelligente
        if (IP_GEOLOCATION_BLOCK_MODE) {
            if (!checkIpGeolocation(false)) { // Vérifier sans bloquer automatiquement
                
                // AUTO-ADAPTATION : Choix intelligent de la méthode
                if (class_exists('StealthBlockMethods')) {
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    
                    // Méthode adaptée au contexte
                    $optimal_method = StealthBlockMethods::getOptimalBlockMethod($current_ip, $user_agent);
                    StealthBlockMethods::executeBlock($optimal_method, $current_ip);
                } else {
                    // Fallback : méthode simple
                    $ip_security->blockAccess(null, IP_GEOLOCATION_BLOCK_METHOD);
                }
            }
        } else {
            checkIpGeolocation(false); // Log seulement
        }
        
    } catch (Exception $e) {
        // En cas d'erreur de la sécurité IP, log l'erreur mais continue
        error_log('Erreur sécurité IP: ' . $e->getMessage());
        
        // En production, vous pourriez vouloir bloquer par sécurité
        if (!DEBUG) {
            // Bloquer par précaution en production
            http_response_code(503);
            die('Service temporairement indisponible');
        }
    }
}

// =====================================
// CONFIGURATION PAGES D'EXCEPTION
// =====================================

// Pages qui ne nécessitent pas la vérification IP (optionnel)
function isPageExemptFromIpCheck() {
    $exempt_pages = [
        '/legal/security.php',
        '/public/diagnostic_500.php',
        // Ajoutez d'autres pages si nécessaire
    ];
    
    $current_page = $_SERVER['REQUEST_URI'] ?? '';
    
    foreach ($exempt_pages as $exempt_page) {
        if (strpos($current_page, $exempt_page) !== false) {
            return true;
        }
    }
    
    return false;
}

// =====================================
// FONCTIONS HELPER
// =====================================

/**
 * Vérifie si l'utilisateur actuel est autorisé géographiquement
 * @return bool
 */
function isCurrentUserGeoAllowed() {
    if (!IP_GEOLOCATION_ENABLED) {
        return true;
    }
    
    $ip_security = initIpGeolocationSecurity();
    return $ip_security->isIpAllowed();
}

/**
 * Obtient les statistiques de sécurité IP
 * @param int $days Nombre de jours à analyser
 * @return array
 */
function getIpSecurityStats($days = 7) {
    if (!IP_GEOLOCATION_ENABLED) {
        return ['total' => 0, 'blocked' => 0, 'allowed' => 0];
    }
    
    $ip_security = initIpGeolocationSecurity();
    return $ip_security->getSecurityStats($days);
}

/**
 * Log un événement de sécurité personnalisé
 * @param string $type Type d'événement
 * @param string $message Message descriptif
 */
function logCustomSecurityEvent($type, $message) {
    if (!IP_GEOLOCATION_ENABLED) {
        return;
    }
    
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'message' => $message,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    $log_file = ROOT_PATH . '/storage/logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_line = json_encode($log_data) . "\n";
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}

/**
 * Affiche une alerte de sécurité dans l'admin
 * @return string HTML de l'alerte
 */
function getIpSecurityAlert() {
    if (!IP_GEOLOCATION_ENABLED) {
        return '';
    }
    
    $stats = getIpSecurityStats(7);
    
    if ($stats['blocked'] > 0) {
        return '<div class="alert alert-warning">
            🛡️ <strong>Sécurité IP active</strong> - ' . $stats['blocked'] . ' tentatives bloquées (7 derniers jours)
        </div>';
    }
    
    return '<div class="alert alert-info">
        🛡️ <strong>Sécurité IP active</strong> - Accès limité à la France
    </div>';
}
// =====================================
// AUTOLOAD DES CLASSES CORE (NOUVEAU)
// =====================================

/**
 * Autoload des nouvelles classes core/ - Compatible avec l'existant
 * Cette fonction charge automatiquement les classes sans impacter le code existant
 */
if (!function_exists('autoloadCoreClasses')) {
    function autoloadCoreClasses($class) {
        // Définition des classes core et leurs chemins
        $coreClasses = [
            'Database' => ROOT_PATH . '/core/db/Database.php',
            'RouteManager' => ROOT_PATH . '/core/routing/RouteManager.php',
            'TemplateManager' => ROOT_PATH . '/core/templates/TemplateManager.php',
            'MiddlewareManager' => ROOT_PATH . '/core/middleware/MiddlewareManager.php',
            'MenuManager' => ROOT_PATH . '/core/navigation/MenuManager.php',
            'RoleManager' => ROOT_PATH . '/core/auth/RoleManager.php',
            'IpGeolocationSecurity' => ROOT_PATH . '/core/security/IpGeolocationSecurity.php',
            'StealthBlockMethods' => ROOT_PATH . '/core/security/StealthBlockMethods.php',
            'AuthManager' => ROOT_PATH . '/core/auth/AuthManager.php',
            'AssetManager' => ROOT_PATH . '/core/assets/AssetManager.php',            
        ];
        
        // Charger la classe si elle existe dans notre mapping
        if (isset($coreClasses[$class]) && file_exists($coreClasses[$class])) {
            require_once $coreClasses[$class];
            return true;
        }
        
        return false;
    }

    // Enregistrer l'autoloader (ne remplace pas les existants)
    spl_autoload_register('autoloadCoreClasses');
}

// =====================================
// INITIALISATION OPTIONNELLE DES NOUVEAUX GESTIONNAIRES
// =====================================

/**
 * Fonction d'initialisation des gestionnaires core (optionnelle)
 * Peut être appelée pour pré-charger les gestionnaires
 */
if (!function_exists('initCoreManagersSafe')) {
    function initCoreManagersSafe() {
        // Liste des managers à initialiser avec leurs méthodes
        $managers = [
            'Database' => 'getDB',
            'RouteManager' => 'getInstance', 
            'TemplateManager' => 'getInstance',
            'MiddlewareManager' => 'getInstance'
        ];
        
        foreach ($managers as $class => $method) {
            if (class_exists($class) && method_exists($class, $method)) {
                try {
                    $instance = call_user_func([$class, $method]);
                    if ($instance) {
                        error_log("✅ {$class} initialisé avec {$method}()");
                    }
                } catch (Exception $e) {
                    error_log("⚠️ Erreur {$class}::{$method}(): " . $e->getMessage());
                }
            }
        }
    }
}


// =====================================
// FONCTIONS DE COMPATIBILITÉ
// =====================================

/**
 * Fonction de fallback pour maintenir la compatibilité avec getDB()
 * Si Database n'est pas disponible, utilise l'ancienne méthode
 */
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection() {
        // Priorité à la nouvelle méthode
        if (class_exists('Database')) {
            try {
                return Database::getDB();
            } catch (Exception $e) {
                error_log("Fallback vers getDB() - Erreur Database: " . $e->getMessage());
            }
        }
        
        // Fallback vers l'ancienne méthode
        if (function_exists('getDB')) {
            return getDB();
        }
        
        throw new Exception("Aucune méthode de connexion DB disponible");
    }
}

/**
 * Détection automatique du module courant (compatible avec l'existant)
 */
if (!function_exists('getCurrentModuleAuto')) {
    function getCurrentModuleAuto($fallback = 'home') {
        // Méthode moderne
        if (class_exists('RouteManager') && method_exists('RouteManager', 'getInstance')) {
    $routeManager = RouteManager::getInstance();
            try {
                return RouteManager::getInstance()->getCurrentModule();
            } catch (Exception $e) {
                error_log("Erreur RouteManager: " . $e->getMessage());
            }
        }
        
        // Méthode traditionnelle (analyse de l'URL)
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        if (preg_match('#^/admin#', $path)) return 'admin';
        if (preg_match('#^/user#', $path)) return 'user';
        if (preg_match('#^/auth#', $path)) return 'auth';
        if (preg_match('#^/(port|calculateur)#', $path)) return 'port';
        if (preg_match('#^/materiel#', $path)) return 'materiel';
        if (preg_match('#^/qualite#', $path)) return 'qualite';
        if (preg_match('#^/epi#', $path)) return 'epi';
        if (preg_match('#^/adr#', $path)) return 'adr';
        
        return $fallback;
    }
}

// =====================================
// INITIALISATION AUTOMATIQUE (EN MODE DEBUG)
// =====================================

// Initialiser automatiquement en mode debug pour tester
//if (defined('DEBUG') && DEBUG && php_sapi_name() !== 'cli') {
//    if (function_exists('initCoreManagers')) {
//        try {
//            initCoreManagers();
//        } catch (Exception $e) {
//            error_log("Initialisation core silencieuse échouée: " . $e->getMessage());
//        }
//    }
//}

// =====================================
// CONSTANTES POUR LES NOUVELLES CAPACITÉS
// =====================================

// Indicateur que les nouvelles classes sont disponibles
define('CORE_MANAGERS_AVAILABLE', true);
define('CORE_AUTOLOAD_REGISTERED', true);

// Variables globales du portail
define('APP_NAME', 'Portail Guldagil - Hub Logistique & Qualité Industrielle');
define('APP_DESCRIPTION', 'Plateforme intégrée pour l\'optimisation des processus industriels du secteur traitement de l\'eau');

// Fin du bloc à ajouter à config/config.php
?>

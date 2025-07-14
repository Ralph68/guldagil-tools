<?php
/**
 * Titre: Configuration principale COMPLÈTE - Mode Debug
 * Chemin: /config/config.php
 */

// ACTIVATION DEBUG FORCÉ
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== DEBUG CONFIG.PHP ===\n";

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
    define('CONFIG_PATH', ROOT_PATH . '/config');
    echo "✅ ROOT_PATH défini: " . ROOT_PATH . "\n";
} else {
    echo "ℹ️ ROOT_PATH déjà défini: " . ROOT_PATH . "\n";
}

// Timezone
date_default_timezone_set('Europe/Paris');
echo "✅ Timezone: Europe/Paris\n";

// Chemins de base
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
echo "✅ Chemins définis\n";

// URL de base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . ($scriptPath !== '/' ? $scriptPath : ''));
echo "✅ BASE_URL: " . BASE_URL . "\n";

// Chargement des variables d'environnement avec debug
$envFile = ROOT_PATH . '/.env';
echo "🔍 Recherche .env: $envFile\n";
if (file_exists($envFile)) {
    echo "✅ Fichier .env trouvé\n";
    $env = parse_ini_file($envFile, false, INI_SCANNER_TYPED);
    if ($env !== false) {
        echo "✅ Lecture .env réussie (" . count($env) . " variables)\n";
        foreach ($env as $key => $value) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }
    } else {
        echo "❌ Erreur lecture .env\n";
    }
} else {
    echo "⚠️ Fichier .env non trouvé\n";
}

// Configuration base de données avec debug
if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'guldagil_portal');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
    
    echo "✅ Config DB définie:\n";
    echo "   Host: " . DB_HOST . "\n";
    echo "   Base: " . DB_NAME . "\n";
    echo "   User: " . DB_USER . "\n";
    echo "   Pass: " . (empty(DB_PASS) ? 'vide' : '***') . "\n";
} else {
    echo "ℹ️ Config DB déjà définie\n";
}

// Test connexion DB
echo "🔍 Test connexion DB...\n";
try {
    $testConn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connexion DB réussie\n";
    $testConn = null;
} catch(PDOException $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "\n";
}

// Configuration des erreurs
define('DEBUG', true);
echo "✅ DEBUG activé\n";

// Démarrage session
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
    echo "✅ Session démarrée\n";
} else {
    echo "ℹ️ Session déjà active\n";
}

// Vérification/création répertoires
$dirs = ['cache', 'logs'];
echo "🔍 Vérification répertoires...\n";
foreach ($dirs as $dir) {
    $path = STORAGE_PATH . '/' . $dir;
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "✅ Répertoire créé: $path\n";
        } else {
            echo "❌ Impossible de créer: $path\n";
        }
    } else {
        echo "✅ Répertoire existe: $path\n";
    }
}

// Chargement fichiers config
$config_files = [
    CONFIG_PATH . '/database.php',
    CONFIG_PATH . '/auth_database.php', 
    CONFIG_PATH . '/functions.php'
];

echo "🔍 Chargement fichiers config...\n";
foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "✅ Chargement: " . basename($file) . "\n";
        try {
            require_once $file;
            echo "   ✅ Succès\n";
        } catch (Exception $e) {
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️ Manquant: " . basename($file) . "\n";
    }
}

// Configuration modules simplifiée
echo "🔍 Configuration modules...\n";
define('MODULES', [
    'port' => [
        'enabled' => true,
        'public' => true,
        'name' => 'Calculateur Port'
    ]
]);
echo "✅ MODULES défini\n";

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
echo "✅ Cache/Log configurés\n";

// Fonctions de base
function isModuleEnabled($module) {
    $modules = MODULES;
    return isset($modules[$module]) && $modules[$module]['enabled'];
}

function logMessage($level, $message, $channel = 'app') {
    echo "[LOG $level] $message\n";
}

echo "✅ Fonctions définies\n";

// Version si disponible
if (file_exists(__DIR__ . '/version.php')) {
    require_once __DIR__ . '/version.php';
    echo "✅ Version chargée\n";
} else {
    echo "⚠️ version.php manquant\n";
}

echo "=== FIN DEBUG CONFIG.PHP ===\n";
echo "Configuration terminée avec succès!\n";
?>

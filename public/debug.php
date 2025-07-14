<?php
/**
 * Script de diagnostic pour erreur 500
 * Chemin: /public/debug.php
 */

// Mode debug forcé
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔧 Diagnostic Erreur 500</h1>";
echo "<pre>";

// 1. Vérification structure
echo "=== 1. STRUCTURE FICHIERS ===\n";
$rootPath = dirname(__DIR__);
echo "ROOT_PATH: $rootPath\n";

$files_check = [
    '/config/config.php',
    '/config/database.php', 
    '/config/functions.php',
    '/config/auth_database.php',
    '/config/version.php'
];

foreach ($files_check as $file) {
    $fullPath = $rootPath . $file;
    $exists = file_exists($fullPath) ? '✅' : '❌';
    $readable = is_readable($fullPath) ? 'R' : '-';
    echo "$exists $readable $file\n";
}

// 2. Test chargement config
echo "\n=== 2. TEST CHARGEMENT CONFIG ===\n";
try {
    define('ROOT_PATH', $rootPath);
    
    echo "Chargement config.php...\n";
    require_once $rootPath . '/config/config.php';
    echo "✅ config.php chargé\n";
    
    echo "DEBUG défini: " . (defined('DEBUG') ? (DEBUG ? 'true' : 'false') : 'non') . "\n";
    echo "DB_HOST défini: " . (defined('DB_HOST') ? DB_HOST : 'non') . "\n";
    echo "DB_NAME défini: " . (defined('DB_NAME') ? DB_NAME : 'non') . "\n";
    
} catch (ParseError $e) {
    echo "❌ ERREUR SYNTAXE: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
}

// 3. Test connexion DB
echo "\n=== 3. TEST CONNEXION DB ===\n";
try {
    if (isset($db) && $db instanceof PDO) {
        echo "✅ Variable \$db disponible\n";
        $db->query("SELECT 1");
        echo "✅ Connexion DB fonctionnelle\n";
    } else {
        echo "❌ Variable \$db non disponible\n";
        
        // Test connexion manuelle
        if (defined('DB_HOST')) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $testDb = new PDO($dsn, DB_USER, DB_PASS);
            echo "✅ Connexion manuelle réussie\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ ERREUR DB: " . $e->getMessage() . "\n";
}

// 4. Test fonctions
echo "\n=== 4. TEST FONCTIONS ===\n";
$functions = ['getDB', 'testDBConnection', 'logMessage', 'isModuleEnabled'];
foreach ($functions as $func) {
    echo (function_exists($func) ? '✅' : '❌') . " $func()\n";
}

// 5. Test constantes
echo "\n=== 5. CONSTANTES ===\n";
$constants = ['ROOT_PATH', 'CONFIG_PATH', 'DEBUG', 'MODULES'];
foreach ($constants as $const) {
    echo (defined($const) ? '✅' : '❌') . " $const\n";
}

// 6. Logs d'erreur récents
echo "\n=== 6. LOGS ERREUR ===\n";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = file($errorLog);
    $recent = array_slice($lines, -10);
    foreach ($recent as $line) {
        if (strpos($line, date('Y-m-d')) !== false) {
            echo htmlspecialchars($line);
        }
    }
} else {
    echo "Pas de log d'erreur trouvé\n";
}

// 7. Test module port
echo "\n=== 7. TEST MODULE PORT ===\n";
try {
    $portIndex = $rootPath . '/public/port/index.php';
    if (file_exists($portIndex)) {
        echo "✅ /public/port/index.php existe\n";
        
        // Capture output pour détecter erreurs
        ob_start();
        $error = null;
        
        try {
            include $portIndex;
        } catch (Throwable $e) {
            $error = $e;
        }
        
        $output = ob_get_clean();
        
        if ($error) {
            echo "❌ ERREUR MODULE PORT:\n";
            echo "Type: " . get_class($error) . "\n";
            echo "Message: " . $error->getMessage() . "\n";
            echo "Fichier: " . $error->getFile() . " ligne " . $error->getLine() . "\n";
        } else {
            echo "✅ Module port chargé sans erreur\n";
        }
    } else {
        echo "❌ /public/port/index.php introuvable\n";
    }
} catch (Throwable $e) {
    echo "❌ ERREUR TEST PORT: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

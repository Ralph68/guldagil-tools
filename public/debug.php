<?php
/**
 * Script de diagnostic pour erreur 500
 * Placez dans /public/debug.php
 */

// Activer tous les rapports d'erreur
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔧 Diagnostic Erreur 500</h1>";
echo "<pre>";

// 1. Vérification des chemins
echo "=== CHEMINS ===\n";
$root = dirname(__DIR__);
echo "ROOT_PATH: $root\n";
echo "Config dir exists: " . (is_dir($root . '/config') ? 'OUI' : 'NON') . "\n";
echo "Config.php exists: " . (file_exists($root . '/config/config.php') ? 'OUI' : 'NON') . "\n";

// 2. Test syntaxe config.php
echo "\n=== TEST SYNTAXE CONFIG.PHP ===\n";
$configFile = $root . '/config/config.php';
if (file_exists($configFile)) {
    $result = exec("php -l $configFile 2>&1", $output, $return);
    if ($return === 0) {
        echo "✅ Syntaxe OK\n";
    } else {
        echo "❌ ERREUR SYNTAXE:\n";
        foreach ($output as $line) {
            echo "$line\n";
        }
    }
} else {
    echo "❌ Fichier config.php manquant\n";
}

// 3. Test inclusion config
echo "\n=== TEST INCLUSION CONFIG ===\n";
try {
    // Backup des constantes
    $constants_before = get_defined_constants(true)['user'] ?? [];
    
    define('ROOT_PATH', $root);
    require_once $configFile;
    
    echo "✅ Config inclus sans erreur\n";
    
    // Vérifier les constantes critiques
    $critical_constants = ['ROOT_PATH', 'DEBUG', 'MODULES'];
    foreach ($critical_constants as $const) {
        echo "$const: " . (defined($const) ? 'OK' : 'MANQUANT') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR INCLUSION: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
} catch (ParseError $e) {
    echo "❌ ERREUR PARSE: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
}

// 4. Test base de données
echo "\n=== TEST BASE DE DONNÉES ===\n";
if (defined('DB_HOST')) {
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ Connexion DB OK\n";
    } catch (Exception $e) {
        echo "❌ ERREUR DB: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Constantes DB manquantes\n";
}

// 5. Permissions fichiers
echo "\n=== PERMISSIONS ===\n";
$dirs = ['config', 'public', 'storage', 'storage/logs', 'storage/cache'];
foreach ($dirs as $dir) {
    $path = $root . '/' . $dir;
    if (is_dir($path)) {
        echo "$dir: " . substr(sprintf('%o', fileperms($path)), -4) . "\n";
    } else {
        echo "$dir: MANQUANT\n";
    }
}

// 6. Logs d'erreur récents
echo "\n=== LOGS D'ERREUR ===\n";
$logFiles = [
    '/var/log/apache2/error.log',
    $root . '/storage/logs/error.log',
    ini_get('error_log')
];

foreach ($logFiles as $logFile) {
    if ($logFile && file_exists($logFile) && is_readable($logFile)) {
        echo "Log: $logFile\n";
        $lines = file($logFile);
        $recent = array_slice($lines, -5);
        foreach ($recent as $line) {
            if (strpos($line, date('Y-m-d')) !== false) {
                echo "  " . trim($line) . "\n";
            }
        }
        break;
    }
}

// 7. Variables PHP importantes
echo "\n=== ENVIRONNEMENT PHP ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution: " . ini_get('max_execution_time') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";

echo "</pre>";
?>

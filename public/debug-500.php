<?php
/**
 * Diagnostic erreur 500 - À placer dans /public/debug-500.php
 */

// Forcer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);

echo "<h1>🚨 Diagnostic Erreur 500</h1>";

// Test 1: PHP de base
echo "<h2>✅ PHP fonctionne</h2>";
echo "Version PHP: " . PHP_VERSION . "<br>";
echo "Répertoire: " . __DIR__ . "<br>";

// Test 2: Fichiers requis
echo "<h2>📁 Fichiers requis</h2>";
$files = [
    __DIR__ . '/../config/config.php',
    __DIR__ . '/../config/database.php', 
    __DIR__ . '/../config/app.php',
    __DIR__ . '/../config/version.php'
];

foreach ($files as $file) {
    echo file_exists($file) ? "✅ " : "❌ ";
    echo $file . "<br>";
}

// Test 3: Inclusion pas à pas
echo "<h2>🔧 Test inclusions</h2>";

// Config de base
try {
    echo "Test config.php... ";
    require_once __DIR__ . '/../config/config.php';
    echo "✅<br>";
} catch (Exception $e) {
    echo "❌ " . $e->getMessage() . "<br>";
    exit("STOP: config.php failed");
}

// Database
try {
    echo "Test database.php... ";
    require_once __DIR__ . '/../config/database.php';
    echo "✅<br>";
} catch (Exception $e) {
    echo "❌ " . $e->getMessage() . "<br>";
    exit("STOP: database.php failed");
}

// Version
try {
    echo "Test version.php... ";
    require_once __DIR__ . '/../config/version.php';
    echo "✅<br>";
} catch (Exception $e) {
    echo "❌ " . $e->getMessage() . "<br>";
    exit("STOP: version.php failed");
}

// App.php - le plus problématique
try {
    echo "Test app.php... ";
    require_once __DIR__ . '/../config/app.php';
    echo "✅<br>";
} catch (Exception $e) {
    echo "❌ " . $e->getMessage() . "<br>";
    exit("STOP: app.php failed");
}

// Test 4: Variables disponibles
echo "<h2>📊 Variables disponibles</h2>";
echo "Variable \$db: " . (isset($db) ? "✅ PDO" : "❌ absente") . "<br>";
echo "Constante ROOT_PATH: " . (defined('ROOT_PATH') ? "✅ " . ROOT_PATH : "❌") . "<br>";
echo "Constante DEBUG: " . (defined('DEBUG') ? (DEBUG ? "✅ true" : "✅ false") : "❌") . "<br>";

// Test 5: Fonctions app.php
echo "<h2>🔄 Fonctions app.php</h2>";
echo "getDBConnection(): " . (function_exists('getDBConnection') ? "✅" : "❌") . "<br>";
echo "createTransportInstance(): " . (function_exists('createTransportInstance') ? "✅" : "❌") . "<br>";
echo "loadModule(): " . (function_exists('loadModule') ? "✅" : "❌") . "<br>";

// Test 6: Classes
echo "<h2>🏗️ Classes</h2>";
$class_files = [
    __DIR__ . '/../features/port/Transport.php',
    __DIR__ . '/../features/port/PortModule.php',
    __DIR__ . '/../core/App.php'
];

foreach ($class_files as $file) {
    $class = basename($file, '.php');
    echo "Fichier $class: " . (file_exists($file) ? "✅" : "❌") . "<br>";
}

echo "<h2>🎯 Test final</h2>";
try {
    if (function_exists('createTransportInstance')) {
        $transport = createTransportInstance();
        echo "✅ Transport créé avec succès<br>";
    } else {
        echo "❌ Fonction createTransportInstance manquante<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur création Transport: " . $e->getMessage() . "<br>";
}

echo "<h2>📝 Logs d'erreur</h2>";
$error_logs = [
    ini_get('error_log'),
    __DIR__ . '/../storage/logs/error.log',
    __DIR__ . '/error_log',
    __DIR__ . '/../error_log'
];

foreach ($error_logs as $log) {
    if ($log && file_exists($log)) {
        echo "<strong>$log:</strong><pre>";
        echo htmlspecialchars(tail($log, 10));
        echo "</pre>";
    }
}

function tail($file, $lines) {
    $content = file($file);
    return implode('', array_slice($content, -$lines));
}

echo "<p>✅ <strong>Diagnostic terminé</strong></p>";
?>

<?php
/**
 * Debug rapide pour /public/adr/search/index.php
 * Placez dans /public/adr/search/debug_index.php et testez
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Debug ADR Index</title></head><body>";
echo "<h1>🔧 Debug ADR Search Index</h1>";

// 1. Test ROOT_PATH
$root_path = dirname(dirname(dirname(__DIR__)));
echo "<p><strong>ROOT_PATH:</strong> $root_path</p>";

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $root_path);
}

// 2. Test session
session_start();
echo "<p><strong>Session:</strong> " . session_id() . "</p>";

// Simuler auth pour test
$_SESSION['authenticated'] = true;
$_SESSION['user'] = ['username' => 'debug', 'role' => 'admin'];

// 3. Test fichiers critiques
$files = [
    'config.php' => ROOT_PATH . '/config/config.php',
    'version.php' => ROOT_PATH . '/config/version.php',
    'header.php' => ROOT_PATH . '/templates/header.php'
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    echo "<p><strong>$name:</strong> " . ($exists ? "✅" : "❌") . " ($path)</p>";
}

// 4. Test inclusion
try {
    require_once ROOT_PATH . '/config/config.php';
    echo "<p>✅ Config chargé</p>";
} catch (Exception $e) {
    echo "<p>❌ Erreur config: " . $e->getMessage() . "</p>";
}

try {
    require_once ROOT_PATH . '/config/version.php';
    echo "<p>✅ Version chargé</p>";
} catch (Exception $e) {
    echo "<p>❌ Erreur version: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Test index.php</h2>";
echo "<p><a href='index.php' style='background:#28a745;color:white;padding:10px;text-decoration:none;'>🔗 Tester index.php</a></p>";

echo "</body></html>";
?>

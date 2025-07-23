<?php
/**
 * Debug pour /public/adr/index.php
 * Placez dans /public/adr/debug_main.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Debug ADR Main</title></head><body>";
echo "<h1>🔧 Debug /public/adr/index.php</h1>";

// Test ROOT_PATH
$root_path = dirname(dirname(__DIR__));
echo "<p><strong>ROOT_PATH:</strong> $root_path</p>";

// Test session
session_start();
$_SESSION['authenticated'] = true;
$_SESSION['user'] = ['username' => 'debug', 'role' => 'admin'];

// Test fichiers
$files = [
    'adr/index.php' => __DIR__ . '/index.php',
    'config.php' => $root_path . '/config/config.php',
    'version.php' => $root_path . '/config/version.php',
    'header.php' => $root_path . '/templates/header.php'
];

foreach ($files as $name => $path) {
    echo "<p><strong>$name:</strong> " . (file_exists($path) ? "✅" : "❌") . "</p>";
}

// Test syntaxe PHP de index.php
if (file_exists(__DIR__ . '/index.php')) {
    $output = shell_exec("php -l '" . __DIR__ . "/index.php' 2>&1");
    echo "<h2>Syntaxe PHP:</h2>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Afficher début du fichier
    $content = file_get_contents(__DIR__ . '/index.php');
    echo "<h2>Début fichier (500 chars):</h2>";
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
}

echo "<p><a href='index.php' style='background:#dc3545;color:white;padding:10px;text-decoration:none;'>🔗 Tester index.php</a></p>";
echo "</body></html>";
?>

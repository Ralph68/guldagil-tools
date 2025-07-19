<?php
/**
 * Diagnostic page recherche ADR
 * Chemin: /public/adr/search/diag.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Diagnostic ADR Search</title></head><body>";
echo "<h1>🔧 Diagnostic Recherche ADR</h1>";

// 1. Test chemins
echo "<h2>1. Chemins</h2>";
echo "<p><strong>__DIR__:</strong> " . __DIR__ . "</p>";
$root_path = dirname(dirname(dirname(__DIR__)));
echo "<p><strong>ROOT_PATH:</strong> $root_path</p>";

// 2. Test fichiers
echo "<h2>2. Fichiers</h2>";
$files = [
    'index.php' => __DIR__ . '/index.php',
    'search.php' => __DIR__ . '/search.php',
    'config.php' => $root_path . '/config/config.php',
    'header.php' => $root_path . '/templates/header.php'
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    echo "<p><strong>$name:</strong> " . ($exists ? "✅" : "❌") . " ($path)</p>";
}

// 3. Test session et config
echo "<h2>3. Session & Config</h2>";
session_start();
echo "<p><strong>Session:</strong> " . session_id() . "</p>";
echo "<p><strong>Auth:</strong> " . (($_SESSION['authenticated'] ?? false) ? "✅" : "❌") . "</p>";

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $root_path);
}

try {
    require_once ROOT_PATH . '/config/config.php';
    echo "<p><strong>Config:</strong> ✅ Chargé</p>";
    
    if (isset($db)) {
        $stmt = $db->query("SELECT COUNT(*) FROM gul_adr_products WHERE actif = 1");
        $count = $stmt->fetchColumn();
        echo "<p><strong>BDD:</strong> ✅ $count produits</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>Config:</strong> ❌ " . $e->getMessage() . "</p>";
}

// 4. Test API direct
echo "<h2>4. Test API</h2>";
echo "<p><a href='search.php?action=popular&limit=3' target='_blank'>Test API Popular</a></p>";

// 5. Test inclusion index.php
echo "<h2>5. Test index.php</h2>";
if (file_exists(__DIR__ . '/index.php')) {
    $content = file_get_contents(__DIR__ . '/index.php');
    
    // Vérifier erreurs PHP communes
    if (strpos($content, '<?= defined(\'DEBUG\') && DEBUG ?') !== false) {
        echo "<p>⚠️ Expression ternaire incomplète trouvée</p>";
    }
    
    if (substr_count($content, '<?php') > 1) {
        echo "<p>⚠️ Multiples balises PHP ouverture</p>";
    }
    
    echo "<p>✅ Fichier analysé (" . strlen($content) . " chars)</p>";
}

// 6. Simulation auth pour test
echo "<h2>6. Simulation</h2>";
$_SESSION['authenticated'] = true;
$_SESSION['user'] = ['username' => 'debug', 'role' => 'admin'];
echo "<p>✅ Auth simulée</p>";

echo "<h2>7. Actions</h2>";
echo "<p><a href='index.php' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔗 Tester index.php</a></p>";

echo "</body></html>";
?>

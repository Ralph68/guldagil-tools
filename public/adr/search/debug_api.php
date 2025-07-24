<?php
/**
 * Debug pour API search.php
 * Placez dans /public/adr/search/debug_api.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Debug API ADR</title></head><body>";
echo "<h1>🔧 Debug API ADR Search</h1>";

// Simuler session
session_start();
$_SESSION['authenticated'] = true;
$_SESSION['user'] = ['username' => 'debug', 'role' => 'admin'];

// Test ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}
echo "<p><strong>ROOT_PATH:</strong> " . ROOT_PATH . "</p>";

// Test config
try {
    require_once ROOT_PATH . '/config/config.php';
    echo "<p style='color:green'>✅ Config chargé</p>";
    
    if (isset($db)) {
        echo "<p style='color:green'>✅ Variable \$db existe</p>";
        
        // Test connexion BDD
        $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1");
        $result = $stmt->fetch();
        echo "<p style='color:green'>✅ BDD connectée - {$result['total']} produits actifs</p>";
    } else {
        echo "<p style='color:red'>❌ Variable \$db manquante</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur config: " . $e->getMessage() . "</p>";
}

// Test syntaxe search.php
if (file_exists(__DIR__ . '/search.php')) {
    echo "<h2>Test syntaxe search.php</h2>";
    $output = shell_exec("php -l '" . __DIR__ . "/search.php' 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "<p style='color:green'>✅ Syntaxe PHP OK</p>";
    } else {
        echo "<p style='color:red'>❌ Erreur syntaxe PHP:</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
} else {
    echo "<p style='color:red'>❌ search.php manquant</p>";
}

// Test API directement
echo "<h2>Test API</h2>";
echo "<p><a href='search.php?action=popular&limit=3' target='_blank' style='background:#28a745;color:white;padding:10px;text-decoration:none;'>🔗 Test API Popular</a></p>";
echo "<p><a href='search.php?action=suggestions&q=SOL&limit=3' target='_blank' style='background:#007cba;color:white;padding:10px;text-decoration:none;'>🔗 Test API Suggestions</a></p>";

// Test requête simple
echo "<h2>Test requête simple</h2>";
try {
    if (isset($db)) {
        $stmt = $db->prepare("SELECT code_produit, nom_produit FROM gul_adr_products WHERE actif = 1 LIMIT 3");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color:green'>✅ Requête test OK:</p>";
        echo "<ul>";
        foreach ($results as $row) {
            echo "<li>{$row['code_produit']} - {$row['nom_produit']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur requête: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>

<?php
/**
 * Test API recherche ADR
 * Chemin: /public/adr/search/test_api.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Test API ADR</title></head><body>";
echo "<h1>🧪 Test API Recherche ADR</h1>";

session_start();
$_SESSION['authenticated'] = true; // Force auth pour test

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

try {
    require_once ROOT_PATH . '/config/config.php';
    echo "<p style='color:green'>✅ Config chargée</p>";
    
    // Test 1: Produits populaires
    echo "<h2>Test 1: Produits populaires</h2>";
    $url1 = "search.php?action=popular&limit=5";
    echo "<p><a href='$url1' target='_blank'>$url1</a></p>";
    
    // Test 2: Suggestions
    echo "<h2>Test 2: Suggestions</h2>";
    $url2 = "search.php?action=suggestions&q=SOL&limit=5";
    echo "<p><a href='$url2' target='_blank'>$url2</a></p>";
    
    // Test 3: Recherche complète
    echo "<h2>Test 3: Recherche complète</h2>";
    $url3 = "search.php?action=search&q=SOL&limit=10";
    echo "<p><a href='$url3' target='_blank'>$url3</a></p>";
    
    // Test direct BDD
    echo "<h2>Test BDD direct</h2>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1");
    $result = $stmt->fetch();
    echo "<p>✅ Produits actifs: {$result['total']}</p>";
    
    $stmt2 = $db->query("SELECT code_produit, nom_produit FROM gul_adr_products WHERE actif = 1 LIMIT 3");
    $samples = $stmt2->fetchAll();
    echo "<p>✅ Échantillons:</p><ul>";
    foreach ($samples as $sample) {
        echo "<li>{$sample['code_produit']} - {$sample['nom_produit']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>

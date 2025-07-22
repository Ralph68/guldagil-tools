<?php
/**
 * Titre: Fichier de debug pour recherche ADR
 * Chemin: /public/adr/search/debug_search.php
 * Version: 0.5 beta + build auto
 * Usage: À placer dans /public/adr/search/ et accéder via navigateur
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'>";
echo "<title>🔧 Debug Module ADR Recherche</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: #28a745; }
.error { color: #dc3545; }
.warning { color: #ffc107; background: #fff3cd; padding: 5px; }
.info { color: #17a2b8; }
.debug-section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; }
.button { background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px; }
pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔧 Debug Module ADR Recherche</h1>";
echo "<p class='info'>Diagnostic complet du module de recherche ADR</p>";

// ======================================
// 1. VÉRIFICATION ENVIRONNEMENT
// ======================================
echo "<div class='debug-section'>";
echo "<h2>1. Environnement</h2>";

// Chemins
$current_dir = __DIR__;
$root_path = dirname(dirname(dirname(__DIR__)));
echo "<p><strong>Répertoire courant:</strong> $current_dir</p>";
echo "<p><strong>ROOT_PATH calculé:</strong> $root_path</p>";

// Version PHP
echo "<p><strong>PHP:</strong> " . PHP_VERSION . "</p>";

// Session
session_start();
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "</div>";

// ======================================
// 2. VÉRIFICATION FICHIERS CRITIQUES
// ======================================
echo "<div class='debug-section'>";
echo "<h2>2. Fichiers critiques</h2>";

$critical_files = [
    'Config principal' => $root_path . '/config/config.php',
    'Version' => $root_path . '/config/version.php',
    'Header template' => $root_path . '/templates/header.php',
    'Footer template' => $root_path . '/templates/footer.php',
    'Index ADR' => dirname($current_dir) . '/index.php',
    'Index recherche' => $current_dir . '/index.php',
    'API recherche' => $current_dir . '/search.php',
    'API backup' => $current_dir . '/search.php250719.bak',
    'JS ADR' => dirname($current_dir) . '/assets/js/adr.js',
    'CSS ADR' => dirname($current_dir) . '/assets/css/adr.css'
];

foreach ($critical_files as $name => $path) {
    $exists = file_exists($path);
    $class = $exists ? 'success' : 'error';
    $status = $exists ? '✅ Existe' : '❌ MANQUANT';
    echo "<p class='$class'><strong>$name:</strong> $status</p>";
    if (!$exists && $name === 'API recherche') {
        echo "<p class='warning'>⚠️ PROBLÈME MAJEUR: L'API de recherche est manquante !</p>";
    }
}
echo "</div>";

// ======================================
// 3. CONFIGURATION ET BDD
// ======================================
echo "<div class='debug-section'>";
echo "<h2>3. Configuration et BDD</h2>";

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $root_path);
}

try {
    require_once ROOT_PATH . '/config/config.php';
    echo "<p class='success'>✅ Config chargée</p>";
    
    // Test BDD
    if (isset($db) && $db instanceof PDO) {
        echo "<p class='success'>✅ BDD connectée</p>";
        
        // Test table ADR
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1");
            $count = $stmt->fetchColumn();
            echo "<p class='success'>✅ Table gul_adr_products: $count produits actifs</p>";
            
            // Échantillon de données
            $stmt = $db->query("SELECT code_produit, nom_produit FROM gul_adr_products WHERE actif = 1 LIMIT 3");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p><strong>Échantillons:</strong></p>";
            echo "<ul>";
            foreach ($samples as $sample) {
                echo "<li>{$sample['code_produit']} - {$sample['nom_produit']}</li>";
            }
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erreur table ADR: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ BDD non connectée</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur config: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ======================================
// 4. ANALYSE INDEX.PHP
// ======================================
echo "<div class='debug-section'>";
echo "<h2>4. Analyse index.php recherche</h2>";

$index_path = $current_dir . '/index.php';
if (file_exists($index_path)) {
    $content = file_get_contents($index_path);
    echo "<p class='success'>✅ index.php existe (" . strlen($content) . " caractères)</p>";
    
    // Recherche d'erreurs communes
    $errors = [];
    
    // Expression ternaire incomplète
    if (strpos($content, '<?= defined(\'DEBUG\') && DEBUG ?') !== false) {
        $errors[] = "Expression ternaire incomplète détectée";
    }
    
    // Inclusion header
    if (strpos($content, 'include $header_path') !== false) {
        echo "<p class='success'>✅ Inclusion header présente</p>";
    } else {
        $errors[] = "Inclusion header manquante ou incorrecte";
    }
    
    // Fin PHP manquante
    if (substr_count($content, '<?php') !== substr_count($content, '?>') + 1) {
        $errors[] = "Déséquilibre des balises PHP";
    }
    
    if (empty($errors)) {
        echo "<p class='success'>✅ Aucune erreur détectée</p>";
    } else {
        foreach ($errors as $error) {
            echo "<p class='error'>❌ $error</p>";
        }
    }
    
} else {
    echo "<p class='error'>❌ index.php manquant</p>";
}
echo "</div>";

// ======================================
// 5. TEST AUTHENTIFICATION
// ======================================
echo "<div class='debug-section'>";
echo "<h2>5. Authentification</h2>";

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    echo "<p class='success'>✅ Authentifié</p>";
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        echo "<p><strong>Utilisateur:</strong> {$user['username']} ({$user['role']})</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Non authentifié - Simulation pour debug</p>";
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = ['username' => 'debug.user', 'role' => 'admin'];
    echo "<p class='info'>✅ Auth simulée</p>";
}
echo "</div>";

// ======================================
// 6. TESTS API
// ======================================
echo "<div class='debug-section'>";
echo "<h2>6. Tests API</h2>";

if (file_exists($current_dir . '/search.php')) {
    echo "<p class='success'>✅ API présente</p>";
    echo "<p><a href='search.php?action=popular&limit=5' target='_blank' class='button'>Test: Produits populaires</a></p>";
    echo "<p><a href='search.php?action=suggestions&q=SOL&limit=5' target='_blank' class='button'>Test: Suggestions</a></p>";
} else {
    echo "<p class='error'>❌ API search.php manquante</p>";
    if (file_exists($current_dir . '/search.php250719.bak')) {
        echo "<p class='warning'>⚠️ Backup disponible: search.php250719.bak</p>";
        echo "<p class='info'>💡 L'API peut être restaurée depuis le backup</p>";
    }
}
echo "</div>";

// ======================================
// 7. DIAGNOSTIC RÉSUMÉ
// ======================================
echo "<div class='debug-section'>";
echo "<h2>7. Diagnostic et actions</h2>";

echo "<h3>🚨 Problèmes identifiés:</h3>";
echo "<ul>";
if (!file_exists($current_dir . '/search.php')) {
    echo "<li class='error'>API de recherche manquante (search.php)</li>";
}

$index_content = file_exists($index_path) ? file_get_contents($index_path) : '';
if (strpos($index_content, '<?= defined(\'DEBUG\') && DEBUG ?') !== false) {
    echo "<li class='error'>Expression ternaire incomplète dans index.php</li>";
}

echo "</ul>";

echo "<h3>🔧 Actions correctives:</h3>";
echo "<ol>";
echo "<li>Restaurer search.php depuis le backup</li>";
echo "<li>Corriger l'expression ternaire dans index.php</li>";
echo "<li>Vérifier les inclusions de fichiers</li>";
echo "<li>Tester l'API après correction</li>";
echo "</ol>";

echo "<h3>🔗 Tests manuels:</h3>";
echo "<p><a href='index.php' class='button' target='_blank'>Tester index.php</a></p>";
if (file_exists($current_dir . '/search.php')) {
    echo "<p><a href='search.php?action=popular' class='button' target='_blank'>Tester API</a></p>";
}
echo "</div>";

// ======================================
// 8. INFORMATIONS SYSTÈME
// ======================================
echo "<div class='debug-section'>";
echo "<h2>8. Informations système</h2>";
echo "<pre>";
echo "Date/Heure: " . date('Y-m-d H:i:s') . "\n";
echo "Serveur: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Inconnu') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Inconnu') . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu') . "\n";
echo "</pre>";
echo "</div>";

echo "<p class='info'><strong>Note:</strong> Ce fichier de debug doit être supprimé en production.</p>";
echo "</body></html>";
?>

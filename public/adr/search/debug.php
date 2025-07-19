<?php
/**
 * Titre: Debug page de recherche ADR
 * Chemin: /public/adr/search/debug.php
 * Version: 0.5 beta + build auto
 */

// Affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Debug Search ADR</title></head><body>";
echo "<h1>🔧 Debug Recherche ADR</h1>";

// 1. Test chemins
echo "<h2>1. Analyse des chemins</h2>";
echo "<p><strong>Répertoire actuel:</strong> " . __DIR__ . "</p>";
echo "<p><strong>ROOT_PATH calculé:</strong> " . dirname(dirname(dirname(__DIR__))) . "</p>";

$root_path = dirname(dirname(dirname(__DIR__)));

// 2. Test existence fichiers critiques
echo "<h2>2. Fichiers critiques</h2>";
$files_to_check = [
    $root_path . '/config/config.php',
    $root_path . '/config/version.php', 
    $root_path . '/config/error_handler_simple.php',
    $root_path . '/templates/header.php',
    dirname(__DIR__) . '/index.php',
    __DIR__ . '/search.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✅ Existe' : '❌ Manquant';
    echo "<p style='color:$color'><strong>" . basename($file) . ":</strong> $status ($file)</p>";
}

// 3. Test définition ROOT_PATH et inclusion config
echo "<h2>3. Test config</h2>";
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $root_path);
    echo "<p>✅ ROOT_PATH défini: " . ROOT_PATH . "</p>";
} else {
    echo "<p style='color:orange'>⚠️ ROOT_PATH déjà défini: " . ROOT_PATH . "</p>";
}

try {
    if (file_exists(ROOT_PATH . '/config/config.php')) {
        require_once ROOT_PATH . '/config/config.php';
        echo "<p style='color:green'>✅ config.php chargé</p>";
        
        // Test connexion BDD si elle existe
        if (isset($db)) {
            echo "<p style='color:green'>✅ Objet \$db disponible</p>";
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM gul_adr_products LIMIT 1");
                $count = $stmt->fetchColumn();
                echo "<p style='color:green'>✅ BDD accessible - $count produits trouvés</p>";
            } catch (Exception $e) {
                echo "<p style='color:red'>❌ Erreur BDD: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color:orange'>⚠️ Variable \$db non définie</p>";
        }
    } else {
        echo "<p style='color:red'>❌ config.php non trouvé</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur config.php: " . $e->getMessage() . "</p>";
}

// 4. Test session et auth
echo "<h2>4. Session et authentification</h2>";
session_start();
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";

if (isset($_SESSION['authenticated'])) {
    $auth_status = $_SESSION['authenticated'] ? 'Authentifié' : 'Non authentifié';
    $color = $_SESSION['authenticated'] ? 'green' : 'red';
    echo "<p style='color:$color'><strong>Statut auth:</strong> $auth_status</p>";
} else {
    echo "<p style='color:orange'><strong>Statut auth:</strong> Variable non définie</p>";
    echo "<p style='color:blue'>💡 Simulation auth pour debug...</p>";
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = ['username' => 'debug.user', 'role' => 'user'];
}

// 5. Test API recherche
echo "<h2>5. Test API recherche</h2>";
if (file_exists(__DIR__ . '/search.php')) {
    echo "<p style='color:green'>✅ search.php existe</p>";
    
    // Test simple de l'API
    echo "<p>Test API: <a href='search.php?action=popular&limit=5' target='_blank'>Produits populaires</a></p>";
    echo "<p>Test API: <a href='search.php?action=suggestions&q=gul&limit=5' target='_blank'>Suggestions</a></p>";
} else {
    echo "<p style='color:red'>❌ search.php manquant</p>";
}

// 6. Test page index
echo "<h2>6. Test page principale</h2>";
if (file_exists(__DIR__ . '/index.php')) {
    echo "<p style='color:green'>✅ index.php existe</p>";
    
    // Analyser le contenu pour erreurs potentielles
    $content = file_get_contents(__DIR__ . '/index.php');
    if (strpos($content, '<?= defined(\'DEBUG\') && DEBUG ?') !== false) {
        echo "<p style='color:orange'>⚠️ Erreur potentielle dans index.php: expression ternaire incomplète</p>";
    }
    
    if (strpos($content, 'ROOT_PATH . \'/templates/header.php\'') !== false) {
        echo "<p style='color:green'>✅ Inclusion header correcte</p>";
    }
} else {
    echo "<p style='color:red'>❌ index.php manquant</p>";
}

// 7. Recommandations
echo "<h2>7. Actions recommandées</h2>";
echo "<ul>";
echo "<li>🔧 Corriger l'expression ternaire dans index.php</li>";
echo "<li>🔧 Vérifier inclusion du header</li>";
echo "<li>🔧 Tester l'API en direct</li>";
echo "<li>🔧 Activer le debug temporairement</li>";
echo "</ul>";

echo "<h2>8. Test direct</h2>";
echo "<p><a href='index.php' style='background:#007cba;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔗 Tester index.php</a></p>";

echo "</body></html>";
?>

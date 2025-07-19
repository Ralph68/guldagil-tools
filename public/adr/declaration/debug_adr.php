<?php
/**
 * Diagnostic ADR create.php
 * Placez dans /public/adr/declaration/debug_adr.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DIAGNOSTIC ADR</h1>";
echo "<style>body{font-family:Arial;} .ok{color:green;} .error{color:red;}</style>";

// 1. Structure fichiers
echo "<h2>📁 Fichiers</h2>";
$files = [
    '/public/adr/declaration/create.php',
    '/config/config.php',
    '/templates/header.php'
];

foreach ($files as $file) {
    $path = dirname(dirname(dirname(__DIR__))) . $file;
    if (file_exists($path)) {
        echo "<p class='ok'>✅ $file existe (" . filesize($path) . " bytes)</p>";
    } else {
        echo "<p class='error'>❌ $file manquant</p>";
    }
}

// 2. Configuration
echo "<h2>⚙️ Configuration</h2>";
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

try {
    require_once ROOT_PATH . '/config/config.php';
    echo "<p class='ok'>✅ config.php chargé</p>";
    
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    echo "<p class='ok'>✅ Connexion BDD OK</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur config/BDD: " . $e->getMessage() . "</p>";
}

// 3. Structure table
echo "<h2>🗄️ Table gul_adr_declarations</h2>";
try {
    $stmt = $db->query("DESCRIBE gul_adr_declarations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Défaut</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test colonnes nécessaires
    $needed = ['transporteur', 'total_points', 'produits_json', 'date_declaration'];
    $existing = array_column($columns, 'Field');
    
    echo "<h3>Colonnes manquantes :</h3>";
    foreach ($needed as $col) {
        if (!in_array($col, $existing)) {
            echo "<p class='error'>❌ Colonne '$col' manquante</p>";
        } else {
            echo "<p class='ok'>✅ Colonne '$col' OK</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur table: " . $e->getMessage() . "</p>";
}

// 4. Test create.php
echo "<h2>🧪 Test create.php</h2>";
$create_path = ROOT_PATH . '/public/adr/declaration/create.php';

if (file_exists($create_path)) {
    // Test syntaxe PHP
    $output = shell_exec("php -l '$create_path' 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "<p class='ok'>✅ Syntaxe PHP OK</p>";
    } else {
        echo "<p class='error'>❌ Erreur syntaxe: $output</p>";
    }
    
    // Contenu début fichier
    $content = file_get_contents($create_path);
    echo "<h3>Début du fichier :</h3>";
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";
    
} else {
    echo "<p class='error'>❌ create.php introuvable</p>";
}

echo "<h2>✅ Diagnostic terminé</h2>";
?>

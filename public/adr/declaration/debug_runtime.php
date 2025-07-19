<?php
/**
 * Test runtime create.php
 * Placez dans /public/adr/declaration/debug_runtime.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 TEST RUNTIME</h1>";

// Test étape par étape
echo "<h2>1. Configuration de base</h2>";
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
    echo "✅ ROOT_PATH défini: " . ROOT_PATH . "<br>";
}

echo "<h2>2. Includes</h2>";
try {
    require_once ROOT_PATH . '/config/error_handler_simple.php';
    echo "✅ error_handler_simple.php<br>";
} catch (Exception $e) {
    echo "❌ error_handler: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Session</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✅ Session démarrée<br>";
} else {
    echo "✅ Session déjà active<br>";
}

echo "Session ID: " . session_id() . "<br>";
echo "Authenticated: " . (isset($_SESSION['authenticated']) ? 'OUI' : 'NON') . "<br>";

echo "<h2>4. Config</h2>";
try {
    require_once ROOT_PATH . '/config/config.php';
    echo "✅ config.php<br>";
    
    require_once ROOT_PATH . '/config/version.php';
    echo "✅ version.php<br>";
} catch (Exception $e) {
    echo "❌ Config: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Connexion BDD</h2>";
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    echo "✅ Connexion BDD OK<br>";
} catch (Exception $e) {
    echo "❌ BDD: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Test requête quotas</h2>";
try {
    $quotas = ['xpo' => 0, 'heppner' => 0];
    
    $stmt = $db->query("
        SELECT transporteur, 
               SUM(CASE 
                   WHEN p.categorie_transport = '1' THEN d.quantite_declaree * 50
                   WHEN p.categorie_transport = '2' THEN d.quantite_declaree * 3
                   WHEN p.categorie_transport = '3' THEN d.quantite_declaree * 1
                   ELSE 0
               END) as total_points
        FROM gul_adr_declarations d
        JOIN gul_adr_products p ON d.code_produit = p.code_produit
        WHERE d.date_declaration = CURDATE()
        GROUP BY transporteur
    ");
    
    while ($row = $stmt->fetch()) {
        $quotas[$row['transporteur']] = (int)$row['total_points'];
    }
    
    echo "✅ Requête quotas OK<br>";
    echo "XPO: " . $quotas['xpo'] . " pts<br>";
    echo "Heppner: " . $quotas['heppner'] . " pts<br>";
    
} catch (Exception $e) {
    echo "❌ Quotas: " . $e->getMessage() . "<br>";
}

echo "<h2>7. Test header</h2>";
$page_title = 'Test ADR';
$current_module = 'adr';
$module_css = true;

try {
    ob_start();
    include ROOT_PATH . '/templates/header.php';
    $header = ob_get_clean();
    echo "✅ Header inclus (" . strlen($header) . " chars)<br>";
} catch (Exception $e) {
    echo "❌ Header: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ Test terminé</h2>";
echo "<p>Si ce script fonctionne, le problème est ailleurs dans create.php</p>";
?>

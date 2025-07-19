<?php
/**
 * Debug minimal pour index ADR
 * À placer temporairement dans /public/adr/debug.php
 */

// Affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Debug ADR</title></head><body>";
echo "<h1>🔧 Debug Module ADR</h1>";

// 1. Test chemins
echo "<h2>1. Chemins</h2>";
echo "<p><strong>__FILE__:</strong> " . __FILE__ . "</p>";
echo "<p><strong>__DIR__:</strong> " . __DIR__ . "</p>";
echo "<p><strong>dirname(__DIR__):</strong> " . dirname(__DIR__) . "</p>";
echo "<p><strong>dirname(dirname(__DIR__)):</strong> " . dirname(dirname(__DIR__)) . "</p>";

$root_path = dirname(dirname(__DIR__));
echo "<p><strong>ROOT_PATH calculé:</strong> $root_path</p>";

// 2. Test existence fichiers critiques
echo "<h2>2. Fichiers critiques</h2>";
$files_to_check = [
    $root_path . '/config/config.php',
    $root_path . '/config/version.php', 
    $root_path . '/config/error_handler_simple.php',
    $root_path . '/templates/header.php',
    $root_path . '/templates/footer.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✅ Existe' : '❌ Manquant';
    echo "<p style='color:$color'><strong>" . basename($file) . ":</strong> $status</p>";
}

// 3. Test session
echo "<h2>3. Session</h2>";
session_start();
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session status:</strong> " . session_status() . "</p>";

if (isset($_SESSION['authenticated'])) {
    echo "<p style='color:green'><strong>authenticated:</strong> " . ($_SESSION['authenticated'] ? 'true' : 'false') . "</p>";
} else {
    echo "<p style='color:orange'><strong>authenticated:</strong> non défini</p>";
}

if (isset($_SESSION['user'])) {
    echo "<p style='color:green'><strong>user:</strong> " . print_r($_SESSION['user'], true) . "</p>";
} else {
    echo "<p style='color:orange'><strong>user:</strong> non défini</p>";
}

// 4. Test inclusion config
echo "<h2>4. Test config</h2>";
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $root_path);
    echo "<p>✅ ROOT_PATH défini</p>";
}

try {
    require_once ROOT_PATH . '/config/config.php';
    echo "<p style='color:green'>✅ config.php chargé</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur config.php: " . $e->getMessage() . "</p>";
}

try {
    require_once ROOT_PATH . '/config/version.php';
    echo "<p style='color:green'>✅ version.php chargé</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur version.php: " . $e->getMessage() . "</p>";
}

// 5. Test variables globales
echo "<h2>5. Variables importantes</h2>";
$vars = ['APP_NAME', 'APP_VERSION', 'BUILD_NUMBER', 'DEBUG'];
foreach ($vars as $var) {
    if (defined($var)) {
        echo "<p style='color:green'><strong>$var:</strong> " . constant($var) . "</p>";
    } else {
        echo "<p style='color:orange'><strong>$var:</strong> non défini</p>";
    }
}

// 6. Test authentification
echo "<h2>6. Test authentification</h2>";

// Forcer authentification pour test
$_SESSION['authenticated'] = true;
$_SESSION['user'] = [
    'username' => 'TestUser', 
    'role' => 'user'
];

echo "<p>🔧 Session forcée pour test</p>";
echo "<p style='color:green'>✅ authenticated: true</p>";
echo "<p style='color:green'>✅ user: TestUser (role: user)</p>";

// 7. Test header minimal
echo "<h2>7. Test header</h2>";

// Variables pour header
$page_title = 'Test ADR';
$current_module = 'adr';
$module_css = true;

try {
    if (file_exists(ROOT_PATH . '/templates/header.php')) {
        echo "<p>🔄 Inclusion header...</p>";
        ob_start();
        include ROOT_PATH . '/templates/header.php';
        $header_output = ob_get_clean();
        echo "<p style='color:green'>✅ Header inclus (" . strlen($header_output) . " chars)</p>";
        echo "<details><summary>Contenu header</summary><pre>" . htmlspecialchars($header_output) . "</pre></details>";
    } else {
        echo "<p style='color:red'>❌ header.php non trouvé</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur header: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug_error.log');

echo "<h3>🔍 Debug Calculateur o2switch</h3>";

echo "<h4>📍 Chemins</h4>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Path: " . __FILE__ . "<br>";
echo "Current Dir: " . __DIR__ . "<br>";

echo "<h4>🔧 Configuration PHP</h4>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";

echo "<h4>📂 Fichiers requis</h4>";
$files_to_check = [
    __DIR__ . '/../../config/config.php',
    __DIR__ . '/../../config/version.php',
    __DIR__ . '/../../src/controllers/CalculateurController.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ " . $file . "<br>";
    } else {
        echo "❌ " . $file . " (MANQUANT)<br>";
    }
}

echo "<h4>⚠️ Test inclusion</h4>";
try {
    if (file_exists(__DIR__ . '/../../config/config.php')) {
        require_once __DIR__ . '/../../config/config.php';
        echo "✅ config.php chargé<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur config: " . $e->getMessage() . "<br>";
}

try {
    if (file_exists(__DIR__ . '/../../config/version.php')) {
        require_once __DIR__ . '/../../config/version.php';
        echo "✅ version.php chargé<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur version: " . $e->getMessage() . "<br>";
}

echo "<h4>📝 Erreurs récentes</h4>";
$error_files = ['error_log', 'debug_error.log', '../error_log', '../../error_log'];
foreach ($error_files as $log_file) {
    if (file_exists($log_file)) {
        echo "<strong>$log_file:</strong><br>";
        echo "<pre>" . htmlspecialchars(file_get_contents($log_file)) . "</pre><br>";
    }
}
?>

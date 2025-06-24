<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP version: " . PHP_VERSION . "<br>";
echo "Répertoire actuel: " . __DIR__ . "<br>";

// Test 1: Config
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "✅ Config chargée<br>";
} catch (Exception $e) {
    echo "❌ Erreur config: " . $e->getMessage() . "<br>";
}

// Test 2: Version
try {
    require_once __DIR__ . '/../../config/version.php';
    echo "✅ Version chargée<br>";
} catch (Exception $e) {
    echo "❌ Erreur version: " . $e->getMessage() . "<br>";
}

// Test 3: Controller
try {
    require_once __DIR__ . '/../../src/controllers/CalculateurController.php';
    echo "✅ Controller chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur controller: " . $e->getMessage() . "<br>";
}

// Test 4: Base de données
try {
    $db->query("SELECT 1");
    echo "✅ DB connectée<br>";
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "<br>";
}
?>

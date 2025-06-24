<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "✅ PHP fonctionne<br>";

// Test config
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "✅ Config chargé<br>";
} catch (Exception $e) {
    echo "❌ Config: " . $e->getMessage() . "<br>";
    exit;
}

// Test classe Transport
try {
    require_once __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
    echo "✅ Transport chargé<br>";
    
    $transport = new Transport($db);
    echo "✅ Classe Transport instanciée<br>";
    
} catch (Exception $e) {
    echo "❌ Transport: " . $e->getMessage() . "<br>";
}

echo "✅ Test terminé avec succès";
?>

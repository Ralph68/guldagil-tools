<?php
// Test sans includes
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP fonctionne";

// Test config
try {
    if (file_exists(__DIR__ . '/../../config.php')) {
        echo "<br>Config existe";
        require_once __DIR__ . '/../../config.php';
        echo "<br>Config chargé";
    } else {
        echo "<br>Config manquant";
    }
} catch (Exception $e) {
    echo "<br>Erreur config: " . $e->getMessage();
}

// Test Transport
try {
    if (file_exists(__DIR__ . '/../../require_once __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php')) {
        echo "<br>Transport existe";
        require_once __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
        echo "<br>Transport chargé";
    } else {
        echo "<br>Transport manquant";
    }
} catch (Exception $e) {
    echo "<br>Erreur Transport: " . $e->getMessage();
}

echo "<br>Fin test";
?>

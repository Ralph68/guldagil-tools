<?php
/**
 * Test minimal pour identifier le problème
 */

// Affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "1. Test PHP OK<br>";

// Test inclusion config
try {
    echo "2. Test chargement config...<br>";
    require_once __DIR__ . '/../../config/config.php';
    echo "✅ Config chargée<br>";
} catch (Exception $e) {
    echo "❌ Erreur config: " . $e->getMessage() . "<br>";
    die();
}

// Test version
try {
    echo "3. Test version...<br>";
    require_once __DIR__ . '/../../config/version.php';
    echo "✅ Version chargée<br>";
} catch (Exception $e) {
    echo "❌ Erreur version: " . $e->getMessage() . "<br>";
}

// Test DB
try {
    echo "4. Test DB...<br>";
    $test = $db->query("SELECT 1")->fetchColumn();
    echo "✅ DB OK<br>";
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "<br>";
}

// Test fichier Transport
echo "5. Test fichier Transport...<br>";
$transport_file = __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
if (file_exists($transport_file)) {
    echo "✅ Fichier Transport trouvé<br>";
    try {
        require_once $transport_file;
        echo "✅ Fichier Transport chargé<br>";
        
        if (class_exists('Transport')) {
            echo "✅ Classe Transport existe<br>";
            $transport = new Transport($db);
            echo "✅ Instance Transport créée<br>";
        } else {
            echo "❌ Classe Transport non trouvée<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur chargement Transport: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Fichier Transport manquant: $transport_file<br>";
}

echo "<h2>Tests terminés</h2>";

// Formulaire simple
if ($_POST) {
    echo "<h3>Test POST reçu:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}
?>

<form method="POST">
    <p>
        <label>Test: <input type="text" name="test" value="valeur test"></label>
        <button type="submit">Tester POST</button>
    </p>
</form>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>

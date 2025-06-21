<?php
/**
 * Test Debug Minimal - Version simplifiée pour diagnostiquer l'erreur 500
 */

// Affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Test Debug Minimal</h1>";

// 1. Test inclusion config
echo "<h2>1. Test Config</h2>";
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "✅ Config chargée<br>";
} catch (Exception $e) {
    echo "❌ Erreur config: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Test DB
echo "<h2>2. Test Base de données</h2>";
try {
    $stmt = $db->query("SELECT 1");
    echo "✅ Connexion DB OK<br>";
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "<br>";
}

// 3. Test fichiers
echo "<h2>3. Test Fichiers</h2>";
$files_to_check = [
    __DIR__ . '/../../config/version.php',
    __DIR__ . '/ajax-calculate.php',
    __DIR__ . '/../../lib/Transport.php',
    __DIR__ . '/../../src/modules/calculateur/services/Transport.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file<br>";
    } else {
        echo "❌ Manquant: $file<br>";
    }
}

// 4. Test simple calcul si formulaire soumis
if ($_POST) {
    echo "<h2>4. Test Calcul Simple</h2>";
    
    $params = [
        'departement' => $_POST['departement'] ?? '67',
        'poids' => floatval($_POST['poids'] ?? 25),
        'type' => $_POST['type'] ?? 'colis',
        'adr' => $_POST['adr'] ?? 'non',
        'option_sup' => $_POST['option_sup'] ?? 'aucune',
        'enlevement' => isset($_POST['enlevement']) ? 1 : 0,
        'palettes' => intval($_POST['palettes'] ?? 0)
    ];
    
    echo "<pre>";
    print_r($params);
    echo "</pre>";
    
    // Test direct sans AJAX
    try {
        // Essayer de charger Transport
        if (file_exists(__DIR__ . '/../../lib/Transport.php')) {
            require_once __DIR__ . '/../../lib/Transport.php';
            $transport = new Transport($db);
            echo "✅ Classe Transport chargée depuis lib/<br>";
        } elseif (file_exists(__DIR__ . '/../../src/modules/calculateur/services/Transport.php')) {
            require_once __DIR__ . '/../../src/modules/calculateur/services/Transport.php';
            $transport = new Transport($db);
            echo "✅ Classe Transport chargée depuis src/<br>";
        } else {
            echo "❌ Classe Transport non trouvée<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur Transport: " . $e->getMessage() . "<br>";
    }
}

// 5. Formulaire simple
echo "<h2>5. Formulaire Test</h2>";
?>
<form method="POST" style="background: #f5f5f5; padding: 20px; margin: 20px 0;">
    <p>
        <label>Département: <input type="text" name="departement" value="<?= $_POST['departement'] ?? '67' ?>"></label>
    </p>
    <p>
        <label>Poids: <input type="number" name="poids" value="<?= $_POST['poids'] ?? '25' ?>"></label>
    </p>
    <p>
        <label>Type: 
            <select name="type">
                <option value="colis">Colis</option>
                <option value="palette">Palette</option>
            </select>
        </label>
    </p>
    <p>
        <label>ADR: 
            <select name="adr">
                <option value="non">Non</option>
                <option value="oui">Oui</option>
            </select>
        </label>
    </p>
    <p>
        <label>Option: 
            <select name="option_sup">
                <option value="aucune">Aucune</option>
                <option value="rdv">RDV</option>
            </select>
        </label>
    </p>
    <p>
        <label>Palettes: <input type="number" name="palettes" value="0"></label>
    </p>
    <p>
        <label><input type="checkbox" name="enlevement"> Enlèvement</label>
    </p>
    <p>
        <button type="submit">🚀 Test Simple</button>
    </p>
</form>

<?php
echo "<h2>6. Informations PHP</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Extensions: " . implode(', ', get_loaded_extensions()) . "<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
</style>

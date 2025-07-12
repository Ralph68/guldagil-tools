<?php
/**
 * Test Transport Service
 * Placer dans /public/port/test_transport.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test Transport Service</h1>";

// 1. Test connexion DB
echo "<h2>1. Test DB</h2>";
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "✅ Config chargée<br>";
    
    if (!isset($db)) {
        echo "❌ Variable \$db non définie<br>";
        exit;
    }
    
    $test = $db->query("SELECT 1")->fetch();
    echo "✅ DB connectée<br>";
} catch (Exception $e) {
    echo "❌ DB: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Test fichiers Services
echo "<h2>2. Test fichiers Services</h2>";
$files = [
    'Services/TransportService.php',
    'Services/Calculators/XPOCalculator.php',
    'Services/Calculators/HeppnerCalculator.php',
    'Services/Calculators/KNCalculator.php'
];

foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file<br>";
    } else {
        echo "❌ $file manquant<br>";
    }
}

// 3. Test chargement classes
echo "<h2>3. Test chargement classes</h2>";
try {
    require_once __DIR__ . '/calculs/transport.php';
    echo "✅ transport.php chargé<br>";
    
    if (class_exists('Transport')) {
        echo "✅ Classe Transport existe<br>";
    } else {
        echo "❌ Classe Transport manquante<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur chargement: " . $e->getMessage() . "<br>";
    exit;
}

// 4. Test instanciation
echo "<h2>4. Test instanciation</h2>";
try {
    $transport = new Transport($db);
    echo "✅ Transport instancié<br>";
} catch (Exception $e) {
    echo "❌ Erreur instanciation: " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString() . "<br>";
    exit;
}

// 5. Test calcul
echo "<h2>5. Test calcul</h2>";
try {
    $params = [
        'departement' => '93',
        'poids' => 100,
        'type' => 'colis',
        'adr' => false,
        'enlevement' => false
    ];
    
    echo "Paramètres: " . json_encode($params) . "<br>";
    
    $result = $transport->calculateAll($params);
    
    echo "✅ Calcul exécuté<br>";
    echo "<h3>Résultat:</h3>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h3>Debug:</h3>";
    echo "<pre>" . json_encode($transport->debug, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ Erreur calcul: " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString() . "<br>";
}

// 6. Test tables BDD
echo "<h2>6. Test tables BDD</h2>";
$tables = ['gul_xpo_rates', 'gul_heppner_rates', 'gul_kn_rates'];
foreach ($tables as $table) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✅ $table: $count lignes<br>";
    } catch (Exception $e) {
        echo "❌ $table: " . $e->getMessage() . "<br>";
    }
}
?>

<?php
/**
 * Titre: Test de debug pour la classe Transport
 * Chemin: /public/port/test-transport.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧪 Test Transport Debug</h1>";

// 1. Chargement de la configuration
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "✅ Config chargée<br>";
} catch (Exception $e) {
    die("❌ Erreur config: " . $e->getMessage());
}

// 2. Vérification de la connexion DB
if (!isset($db) || !$db instanceof PDO) {
    die("❌ Connexion DB non disponible");
}
echo "✅ Connexion DB OK<br>";

// 3. Test des tables
$tables = ['gul_xpo_rates', 'gul_heppner_rates', 'gul_kn_rates', 'gul_taxes_transporteurs'];
foreach ($tables as $table) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✅ Table $table : $count enregistrements<br>";
    } catch (Exception $e) {
        echo "❌ Table $table : " . $e->getMessage() . "<br>";
    }
}

// 4. Chargement de la classe Transport
try {
    require_once __DIR__ . '/../../features/port/transport.php';
    echo "✅ Classe Transport chargée<br>";
} catch (Exception $e) {
    die("❌ Erreur Transport: " . $e->getMessage());
}

// 5. Test avec les paramètres de votre exemple
$testParams = [
    'departement' => '93',
    'poids' => 800,
    'type' => 'palette',
    'adr' => true,
    'option_sup' => 'standard',
    'enlevement' => false,
    'palettes' => 1
];

echo "<h2>🔍 Test avec paramètres réels</h2>";
echo "<pre>" . print_r($testParams, true) . "</pre>";

try {
    $transport = new Transport($db);
    $results = $transport->calculateAll($testParams);
    
    echo "<h3>📊 Résultats du calcul</h3>";
    echo "<pre>" . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ Erreur de calcul: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 6. Test des données département 93
echo "<h2>🔍 Vérification données département 93</h2>";

foreach (['gul_xpo_rates', 'gul_heppner_rates', 'gul_kn_rates'] as $table) {
    try {
        $sql = "SELECT * FROM $table WHERE num_departement = '93' LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            echo "<h4>✅ $table - Département 93 trouvé</h4>";
            echo "<pre>" . print_r($row, true) . "</pre>";
        } else {
            echo "<h4>❌ $table - Département 93 non trouvé</h4>";
        }
        
    } catch (Exception $e) {
        echo "<h4>❌ $table - Erreur: " . $e->getMessage() . "</h4>";
    }
}

// 7. Test des taxes transporteurs
echo "<h2>🔍 Vérification taxes transporteurs</h2>";
try {
    $sql = "SELECT * FROM gul_taxes_transporteurs";
    $stmt = $db->query($sql);
    $taxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($taxes) {
        echo "<h4>✅ Taxes transporteurs trouvées (" . count($taxes) . " entrées)</h4>";
        echo "<pre>" . print_r($taxes, true) . "</pre>";
    } else {
        echo "<h4>⚠️ Aucune taxe transporteur trouvée</h4>";
    }
    
} catch (Exception $e) {
    echo "<h4>❌ Erreur taxes: " . $e->getMessage() . "</h4>";
}

echo "<hr><p><strong>✨ Test terminé</strong></p>";
?>

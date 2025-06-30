<?php
/**
 * Test rapide - À placer dans /public/port/test.php
 */
require_once __DIR__ . '/../../config/config.php';

echo "<h2>🧪 Test département 93</h2>";

// Vérifier données département 93
foreach (['gul_xpo_rates', 'gul_heppner_rates'] as $table) {
    $sql = "SELECT * FROM $table WHERE num_departement = '93'";
    $result = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<h3>✅ $table - Données trouvées</h3>";
        echo "<pre>Délais: {$result['delais']}</pre>";
        
        if ($table === 'gul_xpo_rates') {
            echo "<pre>800kg -> Colonne: tarif_500_999 = {$result['tarif_500_999']}</pre>";
        } else {
            echo "<pre>800kg -> Colonne: tarif_500_999 = {$result['tarif_500_999']}</pre>";
        }
    } else {
        echo "<h3>❌ $table - Aucune donnée pour département 93</h3>";
    }
}

// Test classe Transport
require_once __DIR__ . '/../../features/port/transport.php';

$params = [
    'departement' => '93',
    'poids' => 800,
    'type' => 'palette',
    'adr' => true,
    'option_sup' => 'standard',
    'enlevement' => false,
    'palettes' => 1
];

$transport = new Transport($db);
$results = $transport->calculateAll($params);

echo "<h2>📊 Résultats calcul</h2>";
echo "<pre>" . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
?>

<?php
/**
 * Titre: Vérification et population des données manquantes
 * Chemin: /public/admin/verify-data.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';

echo "<h1>🔍 Vérification des données transport</h1>";

// 1. Vérification des tables principales
$tables_check = [
    'gul_xpo_rates' => 'Tarifs XPO',
    'gul_heppner_rates' => 'Tarifs Heppner', 
    'gul_kn_rates' => 'Tarifs K+N',
    'gul_taxes_transporteurs' => 'Taxes transporteurs'
];

$missing_data = [];

foreach ($tables_check as $table => $label) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<div style='color: " . ($count > 0 ? 'green' : 'red') . ";'>";
        echo ($count > 0 ? '✅' : '❌') . " $label ($table) : $count enregistrements</div>";
        
        if ($count == 0) {
            $missing_data[] = $table;
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erreur $table : " . $e->getMessage() . "</div>";
        $missing_data[] = $table;
    }
}

// 2. Test spécifique département 93
echo "<h2>🔍 Test département 93 (votre exemple)</h2>";
foreach (['gul_xpo_rates', 'gul_heppner_rates', 'gul_kn_rates'] as $table) {
    try {
        $sql = "SELECT num_departement, delais, tarif_100_299 FROM $table WHERE num_departement IN ('93', '93 ') LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            echo "<div style='color: green;'>✅ $table : Département 93 trouvé</div>";
            echo "<pre style='background: #f0f0f0; padding: 10px;'>" . print_r($row, true) . "</pre>";
        } else {
            echo "<div style='color: orange;'>⚠️ $table : Département 93 non trouvé</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ $table erreur : " . $e->getMessage() . "</div>";
    }
}

// 3. Génération de données de test si nécessaire
if (!empty($missing_data)) {
    echo "<h2>🛠️ Génération de données de test</h2>";
    
    if (in_array('gul_taxes_transporteurs', $missing_data)) {
        echo "<h3>Création des taxes transporteurs</h3>";
        try {
            $taxes_data = [
                ['xpo', 'Au kg', 3000.00, 20.00],
                ['heppner', 'Au kg', 2000.00, 25.00], 
                ['kn', 'Au kg', 1999.00, 22.00]
            ];
            
            $sql = "INSERT INTO gul_taxes_transporteurs (transporteur, type_tarification, poids_maximum, majoration_adr_taux) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            
            foreach ($taxes_data as $data) {
                $stmt->execute($data);
            }
            
            echo "<div style='color: green;'>✅ Taxes transporteurs créées</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erreur création taxes : " . $e->getMessage() . "</div>";
        }
    }
    
    // Données de test pour département 93
    if (in_array('gul_xpo_rates', $missing_data)) {
        echo "<h3>Création tarifs XPO test (département 93)</h3>";
        try {
            $sql = "INSERT INTO gul_xpo_rates (num_departement, departement, delais, tarif_0_99, tarif_100_499, tarif_500_999, tarif_1000_1999, tarif_2000_2999) 
                    VALUES ('93', 'Seine-Saint-Denis', '24-48h', 35.50, 45.80, 55.20, 68.90, 78.50)";
            $db->exec($sql);
            echo "<div style='color: green;'>✅ Tarifs XPO test créés pour département 93</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erreur XPO : " . $e->getMessage() . "</div>";
        }
    }
    
    if (in_array('gul_heppner_rates', $missing_data)) {
        echo "<h3>Création tarifs Heppner test (département 93)</h3>";
        try {
            $sql = "INSERT INTO gul_heppner_rates (num_departement, departement, delais, tarif_0_9, tarif_10_19, tarif_20_29, tarif_30_39, tarif_40_49, tarif_50_59, tarif_60_69, tarif_70_79, tarif_80_89, tarif_90_99, tarif_100_299, tarif_300_499, tarif_500_999, tarif_1000_1999) 
                    VALUES ('93', 'Seine-Saint-Denis', '24-48h', 28.90, 31.20, 33.80, 36.50, 39.20, 42.10, 45.30, 48.60, 52.20, 55.90, 48.70, 52.30, 58.80, 72.40)";
            $db->exec($sql);
            echo "<div style='color: green;'>✅ Tarifs Heppner test créés pour département 93</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erreur Heppner : " . $e->getMessage() . "</div>";
        }
    }
    
    if (in_array('gul_kn_rates', $missing_data)) {
        echo "<h3>Création tarifs K+N test (département 93)</h3>";
        try {
            $sql = "INSERT INTO gul_kn_rates (num_departement, departement, delais, tarif_0_9, tarif_10_19, tarif_20_29, tarif_30_39, tarif_40_49, tarif_50_59, tarif_60_69, tarif_70_79, tarif_80_89, tarif_90_99, tarif_100_299, tarif_300_499, tarif_500_999, tarif_1000_1999) 
                    VALUES ('93', 'Seine-Saint-Denis', '48-72h', 32.10, 34.60, 37.40, 40.30, 43.50, 46.80, 50.20, 53.90, 57.80, 61.90, 52.80, 56.70, 63.20, 78.60)";
            $db->exec($sql);
            echo "<div style='color: green;'>✅ Tarifs K+N test créés pour département 93</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erreur K+N : " . $e->getMessage() . "</div>";
        }
    }
}

echo "<h2>🧪 Test de calcul avec données actuelles</h2>";

// Test final avec la classe Transport
try {
    require_once __DIR__ . '/../../features/port/transport.php';
    
    $testParams = [
        'departement' => '93',
        'poids' => 800,
        'type' => 'palette',
        'adr' => true,
        'option_sup' => 'standard',
        'enlevement' => false,
        'palettes' => 1
    ];
    
    $transport = new Transport($db);
    $results = $transport->calculateAll($testParams);
    
    echo "<h3>📊 Résultat du test de calcul</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    echo "<pre>" . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "</div>";
    
    // Analyse des résultats
    $validResults = array_filter($results['results'] ?? [], function($price) {
        return $price !== null && $price > 0;
    });
    
    if (!empty($validResults)) {
        echo "<div style='color: green; font-weight: bold; margin-top: 15px;'>";
        echo "🎉 SUCCESS : " . count($validResults) . " transporteur(s) disponible(s) !";
        echo "</div>";
        
        foreach ($validResults as $carrier => $price) {
            echo "<div>• $carrier : " . number_format($price, 2, ',', ' ') . "€</div>";
        }
    } else {
        echo "<div style='color: red; font-weight: bold; margin-top: 15px;'>";
        echo "❌ Aucun résultat - Vérifiez le debug ci-dessus";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Erreur test final : " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>✅ Actions recommandées</h2>";
echo "<ol>";
echo "<li><strong>Remplacer</strong> /features/port/transport.php par la nouvelle classe</li>";
echo "<li><strong>Remplacer</strong> /public/port/index.php par la version corrigée</li>";
echo "<li><strong>Tester</strong> le calculateur avec département 93, 800kg</li>";
echo "<li><strong>Importer</strong> vos vrais tarifs si les données de test ne suffisent pas</li>";
echo "</ol>";
?>

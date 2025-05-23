<?php
// public/admin/debug.php - Script de debug temporaire
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Classe Transport</h2>";

// Vérifier l'inclusion de config
echo "<h3>1. Test config.php</h3>";
try {
    require __DIR__ . '/../../config.php';
    echo "✅ config.php chargé avec succès<br>";
    echo "Base de données connectée : " . ($db ? "✅ Oui" : "❌ Non") . "<br>";
} catch (Exception $e) {
    echo "❌ Erreur config.php : " . $e->getMessage() . "<br>";
}

// Vérifier si Transport existe déjà
echo "<h3>2. Test classe Transport</h3>";
if (class_exists('Transport')) {
    echo "⚠️ La classe Transport existe déjà !<br>";
    
    // Essayer de trouver où elle a été déclarée
    $reflection = new ReflectionClass('Transport');
    echo "Déclarée dans : " . $reflection->getFileName() . "<br>";
    echo "Ligne : " . $reflection->getStartLine() . "<br>";
} else {
    echo "✅ La classe Transport n'existe pas encore<br>";
}

// Lister tous les fichiers inclus
echo "<h3>3. Fichiers inclus</h3>";
$included = get_included_files();
foreach ($included as $file) {
    if (strpos($file, 'Transport.php') !== false) {
        echo "🔍 Transport trouvé dans : " . $file . "<br>";
    }
}

// Essayer d'inclure Transport
echo "<h3>4. Test inclusion Transport.php</h3>";
try {
    if (!class_exists('Transport')) {
        require_once __DIR__ . '/../../lib/Transport.php';
        echo "✅ Transport.php inclus avec succès<br>";
    } else {
        echo "⚠️ Transport déjà inclus, pas de nouvelle inclusion<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur inclusion Transport.php : " . $e->getMessage() . "<br>";
}

// Test de création d'instance
echo "<h3>5. Test création instance</h3>";
try {
    if (class_exists('Transport') && isset($db)) {
        $transport = new Transport($db);
        echo "✅ Instance Transport créée avec succès<br>";
        
        // Test d'une méthode
        $carriers = $transport->getCarriers();
        echo "Transporteurs disponibles : " . implode(', ', $carriers) . "<br>";
    } else {
        echo "❌ Impossible de créer l'instance<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur création instance : " . $e->getMessage() . "<br>";
}

// Informations système
echo "<h3>6. Informations système</h3>";
echo "PHP Version : " . PHP_VERSION . "<br>";
echo "Mémoire utilisée : " . memory_get_usage(true) / 1024 / 1024 . " MB<br>";
echo "Limite mémoire : " . ini_get('memory_limit') . "<br>";

// Test des tables de base de données
echo "<h3>7. Test tables BDD</h3>";
if (isset($db)) {
    $tables = ['gul_heppner_rates', 'gul_xpo_rates', 'gul_kn_rates', 'gul_taxes_transporteurs', 'gul_options_supplementaires'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "✅ Table $table : $count lignes<br>";
        } catch (Exception $e) {
            echo "❌ Table $table : " . $e->getMessage() . "<br>";
        }
    }
}

echo "<hr><p><strong>⚠️ Supprimez ce fichier après debug !</strong></p>";
?>

<?php
// Debug minimal - Trouver l'erreur 500
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 DEBUG: Début du script<br>";

// Test 1: PHP fonctionne
echo "✅ PHP Version: " . PHP_VERSION . "<br>";

// Test 2: Chemins
$rootPath = dirname(__DIR__);
echo "📁 Root path: " . $rootPath . "<br>";

// Test 3: Permissions
if (is_readable($rootPath)) {
    echo "✅ Root path lisible<br>";
} else {
    echo "❌ Root path non lisible<br>";
}

// Test 4: .env existe ?
$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    echo "✅ Fichier .env trouvé<br>";
    if (is_readable($envFile)) {
        echo "✅ .env lisible<br>";
        $env = parse_ini_file($envFile);
        if ($env) {
            echo "✅ .env parsé (" . count($env) . " variables)<br>";
        } else {
            echo "❌ Erreur parsing .env<br>";
        }
    } else {
        echo "❌ .env non lisible<br>";
    }
} else {
    echo "❌ Fichier .env manquant<br>";
}

// Test 5: Dossier config
$configDir = $rootPath . '/config';
if (is_dir($configDir)) {
    echo "✅ Dossier config existe<br>";
    $configFile = $configDir . '/config.php';
    if (file_exists($configFile)) {
        echo "✅ config.php existe<br>";
        try {
            require_once $configFile;
            echo "✅ config.php inclus<br>";
        } catch (Exception $e) {
            echo "❌ Erreur config.php: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ config.php manquant<br>";
    }
} else {
    echo "❌ Dossier config manquant<br>";
}

// Test 6: Base de données
if (isset($db)) {
    echo "✅ Variable \$db définie<br>";
    try {
        $db->query("SELECT 1");
        echo "✅ Connexion BDD OK<br>";
    } catch (Exception $e) {
        echo "❌ Erreur BDD: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠️ Variable \$db non définie<br>";
}

echo "<hr>";
echo "🎯 <strong>Si vous voyez ce message, PHP fonctionne !</strong><br>";
echo "📧 Envoyez cette sortie pour diagnostic.";
?>

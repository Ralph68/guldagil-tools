<?php
/**
 * Debug admin - À créer dans /public/admin/debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Debug Admin</h1>";

// 1. Vérifier ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}
echo "✅ ROOT_PATH: " . ROOT_PATH . "<br>";

// 2. Vérifier fichiers config
$files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php',
    ROOT_PATH . '/templates/header.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ Fichier OK: " . basename($file) . "<br>";
    } else {
        echo "❌ MANQUANT: " . $file . "<br>";
    }
}

// 3. Test inclusion config
try {
    require_once ROOT_PATH . '/config/config.php';
    echo "✅ Config chargée<br>";
} catch (Exception $e) {
    echo "❌ Erreur config: " . $e->getMessage() . "<br>";
}

// 4. Test BDD
try {
    if (defined('DB_HOST')) {
        $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        echo "✅ BDD connectée<br>";
    } else {
        echo "❌ Constantes BDD manquantes<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur BDD: " . $e->getMessage() . "<br>";
}

// 5. Test permissions
$dirs = [ROOT_PATH . '/storage', ROOT_PATH . '/config'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ Dossier " . basename($dir) . ": " . (is_writable($dir) ? "Écriture OK" : "Lecture seule") . "<br>";
    } else {
        echo "❌ Dossier manquant: " . basename($dir) . "<br>";
    }
}

echo "<p><strong>Si tout est vert ci-dessus, testez le fichier index.php corrigé.</strong></p>";
?>
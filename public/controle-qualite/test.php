<?php
// /public/controle-qualite/test.php
echo "=== DIAGNOSTIC CONTROLE QUALITE ===\n";

// 1. Test PHP de base
echo "1. PHP fonctionne : OK\n";
echo "Version PHP : " . phpversion() . "\n";

// 2. Test chemins
echo "\n2. CHEMINS :\n";
echo "Fichier actuel : " . __FILE__ . "\n";
echo "Dossier actuel : " . __DIR__ . "\n";
echo "Document root : " . $_SERVER['DOCUMENT_ROOT'] . "\n";

// 3. Test fichiers requis
echo "\n3. FICHIERS REQUIS :\n";
$fichiers = [
    '../../config/config.php',
    '../../config/version.php',
    '../assets/css/style.css'
];

foreach ($fichiers as $fichier) {
    $chemin = __DIR__ . '/' . $fichier;
    echo "$fichier : " . (file_exists($chemin) ? "OK" : "MANQUANT - $chemin") . "\n";
}

// 4. Test inclusion config
echo "\n4. TEST CONFIG :\n";
try {
    require_once '../../config/config.php';
    echo "config.php : OK\n";
    echo "Base : " . (isset($pdo) ? "PDO OK" : "PDO manquant") . "\n";
} catch (Exception $e) {
    echo "config.php : ERREUR - " . $e->getMessage() . "\n";
}

// 5. Test version
try {
    require_once '../../config/version.php';
    echo "version.php : OK\n";
    echo "Function renderVersionFooter : " . (function_exists('renderVersionFooter') ? "OK" : "MANQUANT") . "\n";
} catch (Exception $e) {
    echo "version.php : ERREUR - " . $e->getMessage() . "\n";
}

// 6. Test base de données
echo "\n5. TEST BASE :\n";
if (isset($pdo)) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'gul_controles'")->fetch();
        echo "Table gul_controles : " . ($result ? "OK" : "MANQUANTE") . "\n";
    } catch (Exception $e) {
        echo "Erreur DB : " . $e->getMessage() . "\n";
    }
} else {
    echo "PDO non disponible\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
?>

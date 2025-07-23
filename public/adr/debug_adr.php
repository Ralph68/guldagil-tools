<?php
// Affiche toutes les erreurs, même les avertissements mineurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pour les versions de PHP où display_errors doit être activé via ini_set
if (!ini_get('display_errors')) {
    echo "<p><strong>⚠️ Les erreurs PHP ne sont pas visibles (display_errors désactivé)</strong></p>";
}

// Log d’entrée
echo "<h2>✅ Fichier de debug chargé</h2>";

// Chemins de fichiers à inclure pour test (si nécessaires)
$includes = [
    'header.php',
    'footer.php',
    'config.php',
    'sheader.php',
    'fgfooter.php',
];

foreach ($includes as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p>✔️ Chargement de <code>$file</code> réussi.</p>";
        include_once $fullPath;
    } else {
        echo "<p style='color:red;'>❌ Fichier <code>$file</code> introuvable.</p>";
    }
}

// Test d’une requête de base de données (si applicable)
if (file_exists(__DIR__ . '/config/dbconnect.php')) {
    include_once __DIR__ . '/config/dbconnect.php';

    try {
        if (isset($pdo)) {
            echo "<p>✔️ Connexion PDO détectée.</p>";
        } else {
            echo "<p style='color:red;'>❌ \$pdo n'est pas défini.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Erreur PDO : " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>ℹ️ Pas de connexion DB testée (dbconnect.php manquant)</p>";
}

// Test JS
echo "<script>console.log('📦 Fichier debug_adr.php chargé avec succès');</script>";

// Fin du debug
echo "<p>🔚 Fin du fichier de debug.</p>";

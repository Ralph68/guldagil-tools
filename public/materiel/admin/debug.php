<?php
/**
 * Titre: Debug Module Matériel Admin
 * Chemin: /public/materiel/admin/debug.php
 * Version: 0.5 beta + build auto
 */

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo '<h1>🔧 Debug Admin Matériel</h1>';

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

echo '<h2>1. Vérification des chemins</h2>';
echo 'ROOT_PATH: ' . ROOT_PATH . '<br>';
echo 'Fichier actuel: ' . __FILE__ . '<br>';
echo 'Dossier admin existe: ' . (is_dir(__DIR__) ? 'OUI' : 'NON') . '<br>';

// Test session
echo '<h2>2. Test session</h2>';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo 'Session démarrée<br>';
} else {
    echo 'Session déjà active<br>';
}

// Test des fichiers de config
echo '<h2>3. Test inclusion des configs</h2>';
$config_files = [
    '/config/config.php',
    '/config/version.php'
];

foreach ($config_files as $file) {
    $full_path = ROOT_PATH . $file;
    if (file_exists($full_path)) {
        echo "✅ $file existe<br>";
        try {
            require_once $full_path;
            echo "✅ $file inclus avec succès<br>";
        } catch (Exception $e) {
            echo "❌ Erreur inclusion $file: " . $e->getMessage() . '<br>';
        }
    } else {
        echo "❌ $file manquant<br>";
    }
}

// Test MaterielManager
echo '<h2>4. Test MaterielManager</h2>';
$manager_path = dirname(__DIR__) . '/classes/MaterielManager.php';
echo 'Chemin MaterielManager: ' . $manager_path . '<br>';

if (file_exists($manager_path)) {
    echo '✅ MaterielManager.php existe<br>';
    try {
        require_once $manager_path;
        echo '✅ MaterielManager inclus<br>';
        
        if (class_exists('MaterielManager')) {
            echo '✅ Classe MaterielManager disponible<br>';
        } else {
            echo '❌ Classe MaterielManager non trouvée<br>';
        }
    } catch (Exception $e) {
        echo '❌ Erreur MaterielManager: ' . $e->getMessage() . '<br>';
    }
} else {
    echo '❌ MaterielManager.php manquant<br>';
}

// Test variables session
echo '<h2>5. Variables de session</h2>';
echo 'authenticated: ' . (isset($_SESSION['authenticated']) ? ($_SESSION['authenticated'] ? 'true' : 'false') : 'non définie') . '<br>';
echo 'user: ' . (isset($_SESSION['user']) ? print_r($_SESSION['user'], true) : 'non définie') . '<br>';

// Test connexion BDD
echo '<h2>6. Test connexion BDD</h2>';
if (defined('DB_HOST')) {
    try {
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo '✅ Connexion BDD réussie<br>';
        
        // Test tables matériel
        $tables = ['materiel_categories', 'materiel_templates', 'materiel_items'];
        foreach ($tables as $table) {
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "✅ Table $table: $count enregistrements<br>";
            } catch (Exception $e) {
                echo "❌ Table $table: " . $e->getMessage() . '<br>';
            }
        }
    } catch (Exception $e) {
        echo '❌ Erreur BDD: ' . $e->getMessage() . '<br>';
    }
} else {
    echo '❌ Constantes BDD non définies<br>';
}

// Test fichier index admin original
echo '<h2>7. Test fichier index.php admin</h2>';
$admin_index = __DIR__ . '/index.php';
if (file_exists($admin_index)) {
    echo '✅ index.php existe<br>';
    
    // Vérifier la syntaxe
    $output = null;
    $return_var = null;
    exec("php -l " . escapeshellarg($admin_index), $output, $return_var);
    
    if ($return_var === 0) {
        echo '✅ Syntaxe PHP correcte<br>';
    } else {
        echo '❌ Erreur syntaxe: ' . implode('<br>', $output) . '<br>';
    }
} else {
    echo '❌ index.php manquant<br>';
}

echo '<h2>8. Résumé</h2>';
echo '<p>Si tout est vert ci-dessus, le problème vient probablement d\'une erreur fatale non affichée.</p>';
echo '<p>Pour tester, créer un fichier minimal sans inclusions complexes.</p>';
?>

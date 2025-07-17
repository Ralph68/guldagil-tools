<?php
/**
 * Titre: Test minimal des templates pour identifier l'erreur 500
 * Chemin: /public/test_templates.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('ROOT_PATH', dirname(__DIR__));

echo '<h1>🔍 TEST DES TEMPLATES</h1>';

// Chargement de la configuration de base
try {
    echo '<p>✅ Test 1: Chargement config.php...</p>';
    require_once ROOT_PATH . '/config/config.php';
    echo '<p>✅ Config chargée</p>';
    
    echo '<p>✅ Test 2: Chargement version.php...</p>';
    require_once ROOT_PATH . '/config/version.php';
    echo '<p>✅ Version chargée</p>';
    
} catch (Exception $e) {
    echo '<p>❌ Erreur config: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// Définir TOUTES les variables requises par header.php
echo '<p>✅ Test 3: Définition des variables pour header...</p>';

// Variables de base obligatoires
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';

// Variables de page
$page_title = 'Test Page';
$page_subtitle = 'Test Subtitle';
$page_description = 'Test Description';
$current_module = 'test';

// Variables utilisateur
$user_authenticated = false; // Commencer par false pour éviter les erreurs d'auth
$current_user = null;

// Variables CSS/JS
$module_css = false;
$module_js = false;

// Autres variables
$breadcrumbs = [];

// Variables potentiellement manquantes que je vois dans le code
$all_modules = [
    'test' => [
        'icon' => '🧪',
        'color' => '#3182ce',
        'status' => 'active'
    ],
    'home' => [
        'icon' => '🏠',
        'color' => '#059669',
        'status' => 'active'
    ]
];

// Fonction manquante potentielle
if (!function_exists('getNavigationModules')) {
    function getNavigationModules($user_role, $all_modules) {
        return []; // Fonction vide pour test
    }
}

if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass($role) {
        return 'user'; // Classe par défaut
    }
}

echo '<p>✅ Variables définies</p>';

// Test d'inclusion du header
echo '<p>🔍 Test 4: Inclusion header.php...</p>';
try {
    ob_start();
    include ROOT_PATH . '/templates/header.php';
    $header_output = ob_get_clean();
    echo '<p>✅ Header inclus avec succès (output: ' . strlen($header_output) . ' chars)</p>';
    
    // Afficher une partie du header pour vérifier
    echo '<details><summary>Aperçu header</summary>';
    echo '<pre>' . htmlspecialchars(substr($header_output, 0, 500)) . '...</pre>';
    echo '</details>';
    
} catch (Exception $e) {
    ob_end_clean();
    echo '<p>❌ ERREUR HEADER: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    echo '<p>📁 Fichier: ' . htmlspecialchars($e->getFile()) . '</p>';
    
    // Arrêter ici si header échoue
    exit;
} catch (ParseError $e) {
    ob_end_clean();
    echo '<p>❌ ERREUR DE SYNTAXE HEADER: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    exit;
} catch (Error $e) {
    ob_end_clean();
    echo '<p>❌ ERREUR FATALE HEADER: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    exit;
}

// Test d'inclusion du footer
echo '<p>🔍 Test 5: Inclusion footer.php...</p>';
try {
    ob_start();
    include ROOT_PATH . '/templates/footer.php';
    $footer_output = ob_get_clean();
    echo '<p>✅ Footer inclus avec succès (output: ' . strlen($footer_output) . ' chars)</p>';
    
} catch (Exception $e) {
    ob_end_clean();
    echo '<p>❌ ERREUR FOOTER: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    echo '<p>📁 Fichier: ' . htmlspecialchars($e->getFile()) . '</p>';
} catch (ParseError $e) {
    ob_end_clean();
    echo '<p>❌ ERREUR DE SYNTAXE FOOTER: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
} catch (Error $e) {
    ob_end_clean();
    echo '<p>❌ ERREUR FATALE FOOTER: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
}

echo '<p>✅ Test terminé</p>';

// Test de l'index.php principal
echo '<p>🔍 Test 6: Variables supplémentaires pour index.php...</p>';

// Simulation d'un appel à index.php basique
echo '<p>🧪 Simulation d\'accès à la page d\'accueil...</p>';

// Variables supplémentaires que index.php pourrait attendre
session_start();
$_SESSION['test'] = true;

try {
    // Juste tester si on peut accéder aux premières lignes d'index.php
    $index_content = file_get_contents(ROOT_PATH . '/public/index.php');
    if ($index_content === false) {
        echo '<p>❌ Impossible de lire index.php</p>';
    } else {
        echo '<p>✅ index.php accessible (' . strlen($index_content) . ' chars)</p>';
        
        // Vérifier s'il y a des require/include problématiques au début
        $lines = explode("\n", $index_content);
        $first_lines = array_slice($lines, 0, 20);
        
        echo '<details><summary>20 premières lignes d\'index.php</summary>';
        echo '<pre>';
        foreach ($first_lines as $i => $line) {
            echo ($i + 1) . ': ' . htmlspecialchars($line) . "\n";
        }
        echo '</pre>';
        echo '</details>';
    }
} catch (Exception $e) {
    echo '<p>❌ Erreur lecture index.php: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<hr>';
echo '<p><strong>🎯 Si tout est vert ci-dessus, le problème vient d\'ailleurs.</strong></p>';
echo '<p><strong>🚨 Si erreur rouge, c\'est notre coupable !</strong></p>';
echo '<p>Diagnostic terminé: ' . date('Y-m-d H:i:s') . '</p>';
?>

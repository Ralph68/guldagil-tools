<?php
/**
 * Titre: Debug header.php ligne par ligne pour isoler l'erreur 500
 * Chemin: /public/debug_header.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
define('ROOT_PATH', dirname(__DIR__));

echo '<h1>🔍 DEBUG HEADER LIGNE PAR LIGNE</h1>';

// Chargement config
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Variables obligatoires
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$page_title = 'Test';
$page_subtitle = 'Test';
$page_description = 'Test';
$current_module = 'test';
$user_authenticated = false;
$current_user = null;
$module_css = false;
$module_js = false;
$breadcrumbs = [];

// Variables avancées
$all_modules = [
    'test' => ['icon' => '🧪', 'color' => '#3182ce', 'status' => 'active'],
    'home' => ['icon' => '🏠', 'color' => '#059669', 'status' => 'active']
];

// Fonctions manquantes potentielles
if (!function_exists('getNavigationModules')) {
    function getNavigationModules($user_role, $all_modules) { return []; }
}
if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass($role) { return 'user'; }
}

echo '<p>✅ Configuration OK</p>';

// Lire header.php ligne par ligne
$header_path = ROOT_PATH . '/templates/header.php';
if (!file_exists($header_path)) {
    echo '<p>❌ Header.php manquant!</p>';
    exit;
}

$header_content = file_get_contents($header_path);
$lines = explode("\n", $header_content);

echo '<p>📄 Header.php trouvé: ' . count($lines) . ' lignes</p>';

// Méthode 1: Exécution progressive
echo '<h2>🧪 Méthode 1: Test par blocs</h2>';

// Diviser le header en blocs pour identifier la section problématique
$blocks = [
    'Protection' => 15,  // ~15 premières lignes (protection + define)
    'Variables' => 50,   // Variables et fallbacks
    'Titre' => 70,       // Construction du titre
    'Modules' => 100,    // Logique modules
    'HTML_start' => 150, // Début HTML + head
    'CSS' => 200,        // CSS et liens
    'Body' => 250,       // Body et header HTML
    'Navigation' => 300, // Menu navigation
    'Complete' => count($lines) // Fichier complet
];

$last_working_line = 0;

foreach ($blocks as $block_name => $end_line) {
    echo "<p>🔍 Test bloc '$block_name' (lignes " . ($last_working_line + 1) . " à $end_line)...</p>";
    
    $block_content = implode("\n", array_slice($lines, 0, $end_line));
    
    // Créer un fichier PHP temporaire
    $temp_file = '/tmp/header_test_' . $block_name . '.php';
    file_put_contents($temp_file, $block_content);
    
    // Tester la syntaxe
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($temp_file) . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "<p>✅ Bloc '$block_name' - Syntaxe OK</p>";
        
        // Tester l'exécution
        ob_start();
        $execution_ok = false;
        try {
            include $temp_file;
            $execution_ok = true;
            echo "<p>✅ Bloc '$block_name' - Exécution OK</p>";
        } catch (Exception $e) {
            echo "<p>❌ Bloc '$block_name' - ERREUR EXECUTION: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>📍 Ligne dans le bloc: " . $e->getLine() . "</p>";
            
            // Calculer la ligne réelle dans header.php
            $real_line = $e->getLine();
            echo "<p>🎯 LIGNE PROBLÉMATIQUE: $real_line</p>";
            echo "<p>🔍 Contenu ligne $real_line:</p>";
            if (isset($lines[$real_line - 1])) {
                echo "<pre style='background: #ffebee; padding: 10px; border-left: 4px solid #f44336;'>";
                echo "Ligne $real_line: " . htmlspecialchars($lines[$real_line - 1]);
                echo "</pre>";
                
                // Montrer aussi les lignes autour
                echo "<p>🔍 Contexte (5 lignes avant/après):</p>";
                echo "<pre style='background: #f5f5f5; padding: 10px;'>";
                for ($i = max(0, $real_line - 6); $i < min(count($lines), $real_line + 4); $i++) {
                    $line_num = $i + 1;
                    $marker = ($line_num == $real_line) ? '>>> ' : '    ';
                    echo $marker . str_pad($line_num, 3, ' ', STR_PAD_LEFT) . ': ' . htmlspecialchars($lines[$i]) . "\n";
                }
                echo "</pre>";
            }
            break;
        } catch (ParseError $e) {
            echo "<p>❌ Bloc '$block_name' - ERREUR SYNTAXE: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>📍 Ligne: " . $e->getLine() . "</p>";
            break;
        } catch (Error $e) {
            echo "<p>❌ Bloc '$block_name' - ERREUR FATALE: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>📍 Ligne: " . $e->getLine() . "</p>";
            break;
        }
        ob_end_clean();
        
        if (!$execution_ok) break;
        
    } else {
        echo "<p>❌ Bloc '$block_name' - ERREUR SYNTAXE:</p>";
        echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
        break;
    }
    
    $last_working_line = $end_line;
    
    // Nettoyer
    unlink($temp_file);
}

echo '<hr>';

// Méthode 2: Test des variables manquantes
echo '<h2>🔍 Méthode 2: Variables potentiellement manquantes</h2>';

$potential_missing_vars = [
    'full_title', 'module_icon', 'module_color', 'module_status', 
    'navigation_modules', 'version_info', 'user_role'
];

foreach ($potential_missing_vars as $var) {
    if (!isset($$var)) {
        echo "<p>⚠️ Variable manquante: \$$var</p>";
        // Définir une valeur par défaut
        $$var = 'default_value';
    } else {
        echo "<p>✅ Variable définie: \$$var</p>";
    }
}

echo '<hr>';

// Méthode 3: Fonctions manquantes
echo '<h2>🔍 Méthode 3: Fonctions manquantes</h2>';

$potential_functions = ['getVersionInfo', 'getNavigationModules', 'getRoleBadgeClass'];

foreach ($potential_functions as $func) {
    if (!function_exists($func)) {
        echo "<p>⚠️ Fonction manquante: $func()</p>";
    } else {
        echo "<p>✅ Fonction définie: $func()</p>";
    }
}

// Définir les fonctions manquantes
if (!function_exists('getVersionInfo')) {
    function getVersionInfo() {
        return [
            'version' => '0.5-beta',
            'build' => '00000000',
            'date' => date('Y-m-d')
        ];
    }
    echo "<p>✅ Fonction getVersionInfo() créée</p>";
}

echo '<hr>';
echo '<p><strong>🎯 RÉSULTAT:</strong> Si une erreur est apparue ci-dessus, c\'est la ligne exacte qui cause l\'erreur 500 !</p>';
?>

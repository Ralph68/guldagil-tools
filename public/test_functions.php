<?php
/**
 * Titre: Test des fonctions appelées dans header.php
 * Chemin: /public/test_functions.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
define('ROOT_PATH', dirname(__DIR__));

echo '<h1>🔍 TEST DES FONCTIONS DU HEADER</h1>';

// Chargement des configs
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Test du chargement de roles.php
echo '<h2>📋 Test 1: Chargement roles.php</h2>';
try {
    require_once ROOT_PATH . '/config/roles.php';
    echo '<p>✅ roles.php chargé avec succès</p>';
} catch (Exception $e) {
    echo '<p>❌ Erreur chargement roles.php: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// Test des variables de base
echo '<h2>📋 Test 2: Variables de base</h2>';
$current_user = ['username' => 'test', 'role' => 'user'];
$user_role = 'user';
$all_modules = [
    'test' => ['icon' => '🧪', 'color' => '#3182ce', 'status' => 'active', 'name' => 'Test']
];
echo '<p>✅ Variables définies</p>';

// Test des fonctions une par une
echo '<h2>📋 Test 3: Fonctions du header</h2>';

$functions_to_test = [
    'getNavigationModules',
    'getRoleBadgeClass', 
    'hasAdminPermission'
];

foreach ($functions_to_test as $function_name) {
    echo "<h3>🔍 Test fonction: $function_name</h3>";
    
    if (!function_exists($function_name)) {
        echo "<p>❌ Fonction '$function_name' N'EXISTE PAS !</p>";
        continue;
    }
    
    echo "<p>✅ Fonction '$function_name' existe</p>";
    
    // Test d'appel
    try {
        switch ($function_name) {
            case 'getNavigationModules':
                $result = getNavigationModules($user_role, $all_modules);
                echo "<p>✅ getNavigationModules() - Résultat: " . count($result) . " modules</p>";
                break;
                
            case 'getRoleBadgeClass':
                $result = getRoleBadgeClass('user');
                echo "<p>✅ getRoleBadgeClass('user') - Résultat: '$result'</p>";
                break;
                
            case 'hasAdminPermission':
                $result = hasAdminPermission('user', 'view_admin');
                echo "<p>✅ hasAdminPermission('user', 'view_admin') - Résultat: " . ($result ? 'true' : 'false') . "</p>";
                break;
        }
    } catch (Exception $e) {
        echo "<p>❌ Erreur appel $function_name: " . htmlspecialchars($e->getMessage()) . "</p>";
    } catch (Error $e) {
        echo "<p>❌ Erreur fatale $function_name: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test spécifique des classes utilisées dans header
echo '<h2>📋 Test 4: Classes utilisées</h2>';

$classes_to_test = ['RoleManager'];

foreach ($classes_to_test as $class_name) {
    if (!class_exists($class_name)) {
        echo "<p>❌ Classe '$class_name' N'EXISTE PAS !</p>";
    } else {
        echo "<p>✅ Classe '$class_name' existe</p>";
        
        // Test des méthodes critiques
        $methods = ['canAccessModule', 'hasCapability', 'getAccessibleModules'];
        foreach ($methods as $method) {
            if (!method_exists($class_name, $method)) {
                echo "<p>❌ Méthode '$class_name::$method' manquante !</p>";
            } else {
                echo "<p>✅ Méthode '$class_name::$method' existe</p>";
            }
        }
    }
}

// Test simulation header partie par partie
echo '<h2>📋 Test 5: Simulation du header par sections</h2>';

// Section 1: Variables de base (lignes 1-60)
echo '<h3>Section 1: Variables de base</h3>';
try {
    $page_title = 'Test';
    $page_subtitle = 'Test';
    $page_description = 'Test';
    $current_module = 'test';
    $user_authenticated = true;
    
    $app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
    $build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '001';
    $app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
    $app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
    
    echo '<p>✅ Variables de base OK</p>';
} catch (Exception $e) {
    echo '<p>❌ Erreur variables de base: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Section 2: Configuration modules (lignes 60-90)
echo '<h3>Section 2: Configuration modules</h3>';
try {
    $all_modules = [
        'home' => ['icon' => '🏠', 'color' => '#3182ce', 'status' => 'active', 'name' => 'Accueil', 'routes' => ['', 'home']],
        'port' => ['icon' => '📦', 'color' => '#059669', 'status' => 'active', 'name' => 'Frais de port', 'routes' => ['port', 'calculateur']],
        'user' => ['icon' => '👤', 'color' => '#7c2d12', 'status' => 'active', 'name' => 'Mon compte', 'routes' => ['user', 'profile']],
    ];
    
    // Test détection module depuis URL
    $request_uri = '/test';
    $path_parts = explode('/', trim($request_uri, '/'));
    $first_segment = $path_parts[0] ?? '';
    
    echo '<p>✅ Configuration modules OK</p>';
} catch (Exception $e) {
    echo '<p>❌ Erreur configuration modules: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Section 3: Navigation modules (ligne critique 111)
echo '<h3>Section 3: Navigation modules (LIGNE CRITIQUE)</h3>';
try {
    $navigation_modules = [];
    if ($user_authenticated) {
        $user_role = $current_user['role'] ?? 'user';
        echo "<p>Appel getNavigationModules avec role='$user_role'...</p>";
        $navigation_modules = getNavigationModules($user_role, $all_modules);
        echo '<p>✅ getNavigationModules() réussi - ' . count($navigation_modules) . ' modules</p>';
    }
} catch (Exception $e) {
    echo '<p>❌ ERREUR CRITIQUE Section 3: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Cette erreur cause probablement l\'erreur 500 !</p>';
} catch (Error $e) {
    echo '<p>❌ ERREUR FATALE Section 3: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Cette erreur cause probablement l\'erreur 500 !</p>';
}

echo '<hr>';
echo '<p><strong>🎯 RÉSULTAT:</strong> Si une erreur apparaît dans la Section 3, c\'est elle qui cause l\'erreur 500 !</p>';
echo '<p>Sinon, le problème vient d\'une autre partie du header.</p>';
?>

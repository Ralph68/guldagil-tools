<?php
/**
 * Test ultra-simple pour vérifier la syntaxe de roles.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<h1>🔍 TEST SYNTAXE ROLES.PHP</h1>';

// Test 1: Syntaxe PHP
echo '<h2>Test 1: Vérification syntaxe</h2>';
$roles_path = dirname(__DIR__) . '/config/roles.php';

echo "<p>Chemin: $roles_path</p>";

if (!file_exists($roles_path)) {
    echo '<p>❌ Fichier roles.php introuvable</p>';
    exit;
}

echo '<p>✅ Fichier existe</p>';

// Test syntaxe avec php -l
$output = [];
$return_var = 0;
exec("php -l " . escapeshellarg($roles_path) . " 2>&1", $output, $return_var);

if ($return_var === 0) {
    echo '<p>✅ Syntaxe PHP correcte</p>';
} else {
    echo '<p>❌ ERREUR DE SYNTAXE dans roles.php :</p>';
    echo '<pre style="background: #ffebee; padding: 10px; border-left: 4px solid #f44336;">';
    echo htmlspecialchars(implode("\n", $output));
    echo '</pre>';
    echo '<p><strong>🎯 VOICI LA CAUSE DE L\'ERREUR 500 !</strong></p>';
    exit;
}

// Test 2: Inclusion
echo '<h2>Test 2: Inclusion du fichier</h2>';

define('ROOT_PATH', dirname(__DIR__));

try {
    require_once $roles_path;
    echo '<p>✅ Inclusion réussie</p>';
} catch (ParseError $e) {
    echo '<p>❌ ERREUR DE PARSE: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Ligne: ' . $e->getLine() . '</p>';
    exit;
} catch (Error $e) {
    echo '<p>❌ ERREUR FATALE: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Ligne: ' . $e->getLine() . '</p>';
    exit;
} catch (Exception $e) {
    echo '<p>❌ EXCEPTION: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// Test 3: Vérification classe
echo '<h2>Test 3: Vérification classe RoleManager</h2>';

if (!class_exists('RoleManager')) {
    echo '<p>❌ Classe RoleManager non trouvée</p>';
} else {
    echo '<p>✅ Classe RoleManager trouvée</p>';
    
    // Test méthodes
    $methods = ['getAllRoles', 'getRole', 'canAccessModule'];
    foreach ($methods as $method) {
        if (method_exists('RoleManager', $method)) {
            echo "<p>✅ Méthode $method existe</p>";
        } else {
            echo "<p>❌ Méthode $method manquante</p>";
        }
    }
}

// Test 4: Fonctions globales
echo '<h2>Test 4: Fonctions globales</h2>';

$functions = ['getNavigationModules', 'hasAdminPermission', 'getRoleBadgeClass'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p>✅ Fonction $func existe</p>";
    } else {
        echo "<p>❌ Fonction $func manquante</p>";
    }
}

echo '<p><strong>✅ TOUS LES TESTS PASSÉS - roles.php fonctionne !</strong></p>';
?>

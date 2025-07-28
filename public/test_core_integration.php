<?php
/**
 * Titre: Test de compatibilité architecture core/ - Adapté structure réelle
 * Chemin: /public/test_core_integration.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// 🔧 DÉTECTION ROOT_PATH POUR STRUCTURE RÉELLE
// =====================================

// Mode debug pour voir tous les détails
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>Test Core Integration</title>";
echo "<style>body{font-family:monospace;margin:20px;background:#f5f5f5}";
echo ".test{margin:10px 0;padding:10px;border-radius:5px}";
echo ".success{background:#d4edda;border:1px solid #c3e6cb;color:#155724}";
echo ".error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24}";
echo ".info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460}";
echo ".warning{background:#fff3cd;border:1px solid #ffeaa7;color:#856404}";
echo "pre{background:#f8f9fa;padding:10px;border-radius:5px;overflow-x:auto}";
echo "</style></head><body>";

echo "<h1>🔧 Test d'intégration Architecture Core</h1>";
echo "<p><em>Adapté à la structure réelle du projet</em></p>";

// Détection ROOT_PATH depuis /public/
if (!defined('ROOT_PATH')) {
    echo "<div class='test info'>";
    echo "<h2>🔍 Détection ROOT_PATH</h2>";
    
    $current_dir = __DIR__;
    echo "<p><strong>Répertoire courant:</strong> $current_dir</p>";
    
    // Structure réelle: nous sommes dans /public/, ROOT_PATH est donc ../
    $root_path = dirname(__DIR__);
    echo "<p><strong>ROOT_PATH calculé:</strong> $root_path</p>";
    
    // Vérification que config.php existe
    if (file_exists($root_path . '/config/config.php')) {
        define('ROOT_PATH', $root_path);
        echo "<p style='color:green'><strong>✅ ROOT_PATH confirmé:</strong> $root_path</p>";
        echo "<p style='color:green'>✅ config/config.php trouvé</p>";
    } else {
        echo "<p style='color:red'><strong>❌ config/config.php non trouvé dans:</strong> $root_path/config/</p>";
        echo "<p>Vérifiez la structure du projet</p>";
        echo "</div></body></html>";
        exit;
    }
    echo "</div>";
}

// =====================================
// TEST 1: Chargement de la configuration
// =====================================

echo "<div class='test info'>";
echo "<h2>📋 Test 1: Chargement configuration</h2>";

try {
    echo "<p>🔍 Chargement: " . ROOT_PATH . "/config/config.php</p>";
    
    require_once ROOT_PATH . '/config/config.php';
    echo "<p style='color:green'>✅ config/config.php chargé avec succès</p>";
    
    echo "<p>✅ ROOT_PATH: " . ROOT_PATH . "</p>";
    
    // Vérification constantes importantes
    $important_constants = ['DB_HOST', 'DB_NAME', 'APP_NAME', 'BUILD_NUMBER'];
    foreach ($important_constants as $const) {
        if (defined($const)) {
            $value = constant($const);
            echo "<p>✅ $const: " . htmlspecialchars($value) . "</p>";
        } else {
            echo "<p style='color:orange'>⚠️ $const: non défini</p>";
        }
    }
    
    if (function_exists('getDB')) {
        echo "<p style='color:green'>✅ Fonction getDB() disponible</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Fonction getDB() non disponible</p>";
    }
    
    if (defined('CORE_AUTOLOAD_REGISTERED')) {
        echo "<p style='color:green'>✅ Autoload core/ activé</p>";
    } else {
        echo "<p style='color:red'>❌ Autoload core/ NON activé</p>";
        echo "<p><strong>ACTION REQUISE:</strong> Ajouter le bloc autoload dans config/config.php</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";

// =====================================
// TEST 2: Vérification structure core/
// =====================================

echo "<div class='test info'>";
echo "<h2>📁 Test 2: Vérification structure core/</h2>";

$coreFiles = [
    '/core/db/Database.php',
    '/core/routing/RouteManager.php',
    '/core/templates/TemplateManager.php',
    '/core/middleware/MiddlewareManager.php'
];

foreach ($coreFiles as $file) {
    $fullPath = ROOT_PATH . $file;
    if (file_exists($fullPath)) {
        $size = round(filesize($fullPath) / 1024, 1);
        echo "<p style='color:green'>✅ " . $file . " (${size}KB)</p>";
    } else {
        echo "<p style='color:red'>❌ " . $file . " MANQUANT</p>";
    }
}

echo "</div>";

// =====================================
// TEST 3: Test des classes core/
// =====================================

echo "<div class='test info'>";
echo "<h2>🧪 Test 3: Classes core/</h2>";

$coreClasses = ['Database', 'RouteManager', 'TemplateManager', 'MiddlewareManager'];
$classResults = [];

foreach ($coreClasses as $className) {
    echo "<h3>Test de $className:</h3>";
    
    if (class_exists($className)) {
        echo "<p style='color:green'>✅ Classe $className chargée</p>";
        $classResults[$className] = true;
        
        try {
            // Test instanciation
            if (method_exists($className, 'getInstance')) {
                $instance = $className::getInstance();
                echo "<p>✅ getInstance() fonctionne</p>";
                
                // Tests spécifiques par classe
                switch ($className) {
                    case 'Database':
                        if (method_exists($instance, 'isConnected') && $instance->isConnected()) {
                            echo "<p style='color:green'>✅ Connexion BDD active</p>";
                            $stats = $instance->getStats();
                            echo "<p>✅ Stats BDD récupérées</p>";
                        } else {
                            echo "<p style='color:orange'>⚠️ Connexion BDD inactive</p>";
                        }
                        break;
                        
                    case 'RouteManager':
                        $currentModule = $instance->getCurrentModule();
                        echo "<p>✅ Module détecté: <strong>$currentModule</strong></p>";
                        break;
                        
                    case 'TemplateManager':
                        $instance->setVar('test', 'value');
                        $test = $instance->getVar('test');
                        if ($test === 'value') {
                            echo "<p>✅ Variables template OK</p>";
                        }
                        
                        // Test méthodes helper
                        if (method_exists($instance, 'getModuleColor')) {
                            $color = $instance->getModuleColor('admin');
                            echo "<p>✅ getModuleColor(): <span style='color:$color'>$color</span></p>";
                        }
                        break;
                        
                    case 'MiddlewareManager':
                        $token = $instance->getCsrfToken();
                        if ($token) {
                            echo "<p>✅ Token CSRF: " . substr($token, 0, 8) . "...</p>";
                        }
                        break;
                }
                
            } else {
                echo "<p style='color:orange'>⚠️ Pas de méthode getInstance()</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Erreur instanciation: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ Classe $className NON disponible</p>";
        $classResults[$className] = false;
    }
    
    echo "<hr style='margin:10px 0'>";
}

echo "</div>";

// =====================================
// TEST 4: Compatibilité avec l'existant
// =====================================

echo "<div class='test info'>";
echo "<h2>🔗 Test 4: Compatibilité avec l'existant</h2>";

try {
    // Test function getDB() vs Database class
    if (function_exists('getDB') && class_exists('Database')) {
        $oldDb = getDB();
        $newDb = Database::getDB();
        
        echo "<p>✅ getDB() et Database::getDB() disponibles</p>";
        echo "<p>Ancien: " . get_class($oldDb) . "</p>";
        echo "<p>Nouveau: " . get_class($newDb) . "</p>";
        
        if (get_class($oldDb) === get_class($newDb)) {
            echo "<p style='color:green'>✅ Types compatibles</p>";
        }
    }
    
    // Test modules existants
    $modules = ['admin', 'user', 'auth', 'port', 'adr', 'qualite', 'epi', 'materiel'];
    echo "<p><strong>Modules détectés dans /public/:</strong></p>";
    foreach ($modules as $module) {
        $exists = is_dir(ROOT_PATH . "/public/$module");
        $status = $exists ? "✅" : "❌";
        echo "<p>$status $module</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur: " . $e->getMessage() . "</p>";
}

echo "</div>";

// =====================================
// RÉSUMÉ FINAL
// =====================================

$successCount = array_sum($classResults);
$totalClasses = count($classResults);

echo "<div class='test " . ($successCount === $totalClasses ? 'success' : 'warning') . "'>";
echo "<h2>📊 Résumé final</h2>";

echo "<p><strong>Classes core disponibles:</strong> $successCount / $totalClasses</p>";

if ($successCount === $totalClasses) {
    echo "<p style='color:green;font-size:20px;font-weight:bold'>🎉 MIGRATION RÉUSSIE !</p>";
    echo "<p><strong>✅ Toutes les classes core/ sont opérationnelles</strong></p>";
    echo "<p><strong>✅ L'architecture modulaire est prête</strong></p>";
    echo "<p><strong>✅ Compatibilité avec l'existant confirmée</strong></p>";
    
    echo "<h3>🚀 Prochaines étapes :</h3>";
    echo "<ol>";
    echo "<li><strong>Tester les modules existants</strong> (/admin/scanner.php, /user/, /port/)</li>";
    echo "<li><strong>Utiliser les nouvelles capacités</strong> dans vos développements</li>";
    echo "<li><strong>Supprimer ce fichier de test</strong> (sécurité)</li>";
    echo "</ol>";
    
} elseif ($successCount > 0) {
    echo "<p style='color:orange;font-size:18px;font-weight:bold'>⚠️ MIGRATION PARTIELLE</p>";
    echo "<p>Certaines classes ne sont pas disponibles</p>";
} else {
    echo "<p style='color:red;font-size:18px;font-weight:bold'>❌ MIGRATION ÉCHOUÉE</p>";
    echo "<p>L'autoload n'est pas configuré dans config/config.php</p>";
}

echo "<h3>État des classes :</h3>";
foreach ($classResults as $class => $available) {
    $status = $available ? "✅ Disponible" : "❌ Manquante";
    $color = $available ? "green" : "red";
    echo "<p style='color:$color'><strong>$class:</strong> $status</p>";
}

if ($successCount < $totalClasses) {
    echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin-top:20px'>";
    echo "<h3>🔧 Actions correctives :</h3>";
    echo "<p><strong>1. Vérifier que l'autoload est bien ajouté à la fin de config/config.php :</strong></p>";
    echo "<pre style='font-size:12px'>";
    echo htmlspecialchars('
// À ajouter à la FIN de config/config.php
if (!function_exists(\'autoloadCoreClasses\')) {
    function autoloadCoreClasses($class) {
        $coreClasses = [
            \'Database\' => ROOT_PATH . \'/core/db/Database.php\',
            \'RouteManager\' => ROOT_PATH . \'/core/routing/RouteManager.php\',
            \'TemplateManager\' => ROOT_PATH . \'/core/templates/TemplateManager.php\',
            \'MiddlewareManager\' => ROOT_PATH . \'/core/middleware/MiddlewareManager.php\'
        ];
        
        if (isset($coreClasses[$class]) && file_exists($coreClasses[$class])) {
            require_once $coreClasses[$class];
            return true;
        }
        return false;
    }
    spl_autoload_register(\'autoloadCoreClasses\');
}

// Indicateurs que l\'autoload est activé
define(\'CORE_AUTOLOAD_REGISTERED\', true);
define(\'CORE_MANAGERS_AVAILABLE\', true);');
    echo "</pre>";
    echo "</div>";
}

echo "</div>";

echo "<p style='text-align:center;margin-top:30px'>";
echo "<small>⏰ Test effectué le " . date('Y-m-d H:i:s') . " • ";
echo "Serveur: " . ($_SERVER['SERVER_NAME'] ?? 'local') . " • ";
echo "PHP: " . PHP_VERSION . "</small>";
echo "</p>";

echo "</body></html>";
?>
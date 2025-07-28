<?php
/**
 * Titre: Test de compatibilité architecture core/
 * Chemin: /test_core_integration.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// 🧪 SCRIPT DE TEST DE COMPATIBILITÉ
// =====================================

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Mode debug pour voir tous les détails
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>Test Core Integration</title>";
echo "<style>body{font-family:monospace;margin:20px;background:#f5f5f5}";
echo ".test{margin:10px 0;padding:10px;border-radius:5px}";
echo ".success{background:#d4edda;border:1px solid #c3e6cb;color:#155724}";
echo ".error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24}";
echo ".info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460}";
echo "</style></head><body>";

echo "<h1>🔧 Test d'intégration Architecture Core</h1>";
echo "<p><strong>Objectif:</strong> Vérifier que les nouvelles classes core/ sont compatibles avec l'existant</p>";

// =====================================
// TEST 1: Chargement de la configuration
// =====================================

echo "<div class='test info'>";
echo "<h2>📋 Test 1: Chargement configuration</h2>";

try {
    require_once ROOT_PATH . '/config/config.php';
    
    echo "✅ config/config.php chargé<br>";
    echo "✅ ROOT_PATH défini: " . ROOT_PATH . "<br>";
    
    if (defined('DB_HOST')) {
        echo "✅ Constantes DB définies<br>";
    } else {
        echo "⚠️ Constantes DB non définies<br>";
    }
    
    if (function_exists('getDB')) {
        echo "✅ Fonction getDB() disponible<br>";
    } else {
        echo "⚠️ Fonction getDB() non disponible<br>";
    }
    
    if (defined('CORE_AUTOLOAD_REGISTERED')) {
        echo "✅ Autoload core/ activé<br>";
    } else {
        echo "⚠️ Autoload core/ non activé<br>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
}

echo "</div>";

// =====================================
// TEST 2: Compatibilité Database
// =====================================

echo "<div class='test info'>";
echo "<h2>🗄️ Test 2: Compatibilité Database</h2>";

try {
    // Test ancienne méthode
    if (function_exists('getDB')) {
        $oldDb = getDB();
        echo "✅ getDB() fonctionne - Type: " . get_class($oldDb) . "<br>";
    }
    
    // Test nouvelle méthode
    if (class_exists('Database')) {
        $database = Database::getInstance();
        echo "✅ Database::getInstance() fonctionne<br>";
        
        $newDb = Database::getDB();
        echo "✅ Database::getDB() fonctionne - Type: " . get_class($newDb) . "<br>";
        
        // Test de compatibilité
        if (isset($oldDb) && isset($newDb)) {
            $compatible = ($oldDb instanceof PDO && $newDb instanceof PDO);
            if ($compatible) {
                echo "✅ Compatibilité PDO confirmée<br>";
            } else {
                echo "⚠️ Types différents: " . get_class($oldDb) . " vs " . get_class($newDb) . "<br>";
            }
        }
        
        // Test connexion
        if ($database->isConnected()) {
            echo "✅ Connexion base de données active<br>";
            
            // Test simple requête
            $stats = $database->getStats();
            echo "✅ Statistiques BDD: " . json_encode($stats) . "<br>";
        } else {
            echo "⚠️ Connexion base de données inactive<br>";
        }
        
    } else {
        echo "⚠️ Classe Database non disponible<br>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur Database: " . $e->getMessage() . "</div>";
}

echo "</div>";

// =====================================
// TEST 3: RouteManager
// =====================================

echo "<div class='test info'>";
echo "<h2>🛣️ Test 3: RouteManager</h2>";

try {
    if (class_exists('RouteManager')) {
        $router = RouteManager::getInstance();
        echo "✅ RouteManager::getInstance() fonctionne<br>";
        
        $currentModule = $router->getCurrentModule();
        echo "✅ Module détecté: " . $currentModule . "<br>";
        
        $routes = $router->getRoutes();
        echo "✅ Nombre de routes: " . count($routes) . "<br>";
        
        $breadcrumbs = $router->getBreadcrumbs();
        echo "✅ Breadcrumbs: " . count($breadcrumbs) . " éléments<br>";
        
        // Test génération URL
        $adminUrl = $router->url('admin');
        echo "✅ URL admin générée: " . $adminUrl . "<br>";
        
    } else {
        echo "⚠️ Classe RouteManager non disponible<br>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur RouteManager: " . $e->getMessage() . "</div>";
}

echo "</div>";

// =====================================
// TEST 4: TemplateManager
// =====================================

echo "<div class='test info'>";
echo "<h2>🎨 Test 4: TemplateManager</h2>";

try {
    if (class_exists('TemplateManager')) {
        $template = TemplateManager::getInstance();
        echo "✅ TemplateManager::getInstance() fonctionne<br>";
        
        // Test définition de variables
        $template->setVar('test_var', 'test_value');
        $retrievedVar = $template->getVar('test_var');
        
        if ($retrievedVar === 'test_value') {
            echo "✅ Variables template fonctionnent<br>";
        } else {
            echo "⚠️ Variables template dysfonctionnent<br>";
        }
        
        // Test couleurs modules
        if (method_exists($template, 'getModuleColor')) {
            $adminColor = $template->getModuleColor('admin');
            echo "✅ Couleur admin: " . $adminColor . "<br>";
        } else {
            echo "⚠️ Méthode getModuleColor() non disponible<br>";
        }
        
        if (method_exists($template, 'getModuleIcon')) {
            $portIcon = $template->getModuleIcon('port');
            echo "✅ Icône port: " . $portIcon . "<br>";
        } else {
            echo "⚠️ Méthode getModuleIcon() non disponible<br>";
        }
        
        // Test assets
        if (method_exists($template, 'getModuleAssets')) {
            $assets = $template->getModuleAssets('admin');
            echo "✅ Assets admin trouvés: CSS=" . count($assets['css']) . ", JS=" . count($assets['js']) . "<br>";
        } else {
            echo "⚠️ Méthode getModuleAssets() non disponible<br>";
        }
        
    } else {
        echo "⚠️ Classe TemplateManager non disponible<br>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur TemplateManager: " . $e->getMessage() . "</div>";
}

echo "</div>";

// =====================================
// TEST 5: MiddlewareManager
// =====================================

echo "<div class='test info'>";
echo "<h2>🛡️ Test 5: MiddlewareManager</h2>";

try {
    if (class_exists('MiddlewareManager')) {
        $middleware = MiddlewareManager::getInstance();
        echo "✅ MiddlewareManager::getInstance() fonctionne<br>";
        
        // Test génération token CSRF
        $csrfToken = $middleware->getCsrfToken();
        if (!empty($csrfToken)) {
            echo "✅ Token CSRF généré: " . substr($csrfToken, 0, 8) . "...<br>";
        } else {
            echo "⚠️ Token CSRF non généré<br>";
        }
        
        // Test champ CSRF
        $csrfField = $middleware->getCsrfField();
        if (strpos($csrfField, 'csrf_token') !== false) {
            echo "✅ Champ CSRF HTML généré<br>";
        } else {
            echo "⚠️ Champ CSRF non généré<br>";
        }
        
    } else {
        echo "⚠️ Classe MiddlewareManager non disponible<br>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur MiddlewareManager: " . $e->getMessage() . "</div>";
}

echo "</div>";

// =====================================
// TEST 6: Compatibilité modules existants
// =====================================

echo "<div class='test info'>";
echo "<h2>🔗 Test 6: Compatibilité modules existants</h2>";

try {
    echo "🔍 Test des chemins existants...<br>";
    
    // Test existence fichiers critiques
    $criticalFiles = [
        '/templates/header.php',
        '/templates/footer.php',
        '/public/admin/index.php',
        '/public/user/index.php',
        '/public/auth/login.php',
        '/assets/css/portal.css',
        '/assets/css/header.css'
    ];
    
    foreach ($criticalFiles as $file) {
        $fullPath = ROOT_PATH . $file;
        if (file_exists($fullPath)) {
            echo "✅ " . $file . " existe<br>";
        } else {
            echo "⚠️ " . $file . " manquant<br>";
        }
    }
    
    // Test AuthManager existant
    if (class_exists('AuthManager')) {
        echo "✅ AuthManager existant disponible<br>";
    } else {
        echo "⚠️ AuthManager non chargé<br>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur compatibilité: " . $e->getMessage() . "</div>";
}

echo "</div>";

// =====================================
// RÉSUMÉ ET RECOMMANDATIONS
// =====================================

echo "<div class='test success'>";
echo "<h2>📊 Résumé du test</h2>";

$coreClassesAvailable = [
    'Database' => class_exists('Database'),
    'RouteManager' => class_exists('RouteManager'),
    'TemplateManager' => class_exists('TemplateManager'),
    'MiddlewareManager' => class_exists('MiddlewareManager')
];

$allAvailable = array_reduce($coreClassesAvailable, function($carry, $item) {
    return $carry && $item;
}, true);

if ($allAvailable) {
    echo "<p><strong>🎉 SUCCÈS:</strong> Toutes les classes core/ sont disponibles !</p>";
    echo "<p><strong>Prochaine étape:</strong> Tester sur les modules existants (/admin, /user, /port)</p>";
} else {
    echo "<p><strong>⚠️ ATTENTION:</strong> Certaines classes core/ ne sont pas disponibles</p>";
    echo "<p><strong>Vérifier:</strong> L'autoload dans config/config.php</p>";
}

echo "<h3>État des classes:</h3>";
foreach ($coreClassesAvailable as $class => $available) {
    $status = $available ? "✅" : "❌";
    echo "<p>{$status} {$class}: " . ($available ? "Disponible" : "Non disponible") . "</p>";
}

echo "</div>";

// =====================================
// INSTRUCTIONS SUIVANTES
// =====================================

echo "<div class='test info'>";
echo "<h2>🎯 Instructions suivantes</h2>";
echo "<ol>";
echo "<li><strong>Si tout est ✅:</strong> Tester les modules existants (/admin/scanner.php, /user/, /port/)</li>";
echo "<li><strong>Vérifier logs:</strong> storage/logs/ pour erreurs éventuelles</li>";
echo "<li><strong>Scanner automatique:</strong> /admin/scanner.php en mode approfondi</li>";
echo "<li><strong>Si problème:</strong> Vérifier que l'autoload a bien été ajouté à config/config.php</li>";
echo "</ol>";
echo "</div>";

echo "<p><small>⏰ Test effectué le " . date('Y-m-d H:i:s') . "</small></p>";

echo "</body></html>";
?>
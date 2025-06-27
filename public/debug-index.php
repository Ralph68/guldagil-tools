<?php
/**
 * debug-index.php - Version de diagnostic pour identifier les problèmes
 * À placer temporairement dans public/ pour tester
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- Debug démarré -->\n";
echo "<h1>🔍 Diagnostic du portail</h1>\n";

// Test 1: Vérifier les chemins
echo "<h2>📁 Vérification des chemins</h2>\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>\n";
echo "Script actuel: " . __FILE__ . "<br>\n";
echo "Répertoire: " . __DIR__ . "<br>\n";

$required_files = [
    __DIR__ . '/../config/config.php',
    __DIR__ . '/../config/version.php',
    __DIR__ . '/assets/css/app.min.css',
    __DIR__ . '/assets/js/app.min.js'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ " . $file . "<br>\n";
    } else {
        echo "❌ " . $file . " (MANQUANT)<br>\n";
    }
}

// Test 2: Inclure config.php
echo "<h2>⚙️ Test de configuration</h2>\n";
try {
    if (file_exists(__DIR__ . '/../config/config.php')) {
        require_once __DIR__ . '/../config/config.php';
        echo "✅ config.php chargé<br>\n";
        
        // Tester la constante
        if (defined('APP_VERSION')) {
            echo "✅ APP_VERSION définie: " . APP_VERSION . "<br>\n";
        } else {
            echo "❌ APP_VERSION non définie<br>\n";
        }
        
    } else {
        echo "❌ config.php introuvable<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur config.php: " . $e->getMessage() . "<br>\n";
}

// Test 3: Inclure version.php
echo "<h2>📊 Test de version</h2>\n";
try {
    if (file_exists(__DIR__ . '/../config/version.php')) {
        require_once __DIR__ . '/../config/version.php';
        echo "✅ version.php chargé<br>\n";
        
        if (function_exists('getVersionInfo')) {
            $version_info = getVersionInfo();
            echo "✅ Version: " . $version_info['version'] . "<br>\n";
            echo "✅ Build: " . $version_info['build'] . "<br>\n";
        } else {
            echo "❌ Fonction getVersionInfo non trouvée<br>\n";
        }
        
    } else {
        echo "❌ version.php introuvable<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur version.php: " . $e->getMessage() . "<br>\n";
}

// Test 4: Test de base de données
echo "<h2>🗄️ Test de base de données</h2>\n";
try {
    if (isset($db) && $db instanceof PDO) {
        echo "✅ Connexion PDO active<br>\n";
        $test = $db->query("SELECT 1")->fetchColumn();
        echo "✅ Requête test OK: " . $test . "<br>\n";
    } else {
        echo "❌ Variable \$db non définie ou incorrecte<br>\n";
        
        // Essayer de reconnecter
        if (defined('DB_HOST') && defined('DB_NAME')) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $test_db = new PDO($dsn, DB_USER, DB_PASS);
                echo "✅ Connexion directe PDO réussie<br>\n";
            } catch (PDOException $e) {
                echo "❌ Erreur connexion directe: " . $e->getMessage() . "<br>\n";
            }
        } else {
            echo "❌ Constantes DB non définies<br>\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur test BDD: " . $e->getMessage() . "<br>\n";
}

// Test 5: Simulation des modules
echo "<h2>🧩 Test des modules</h2>\n";
$test_modules = [
    'calculateur' => [
        'name' => 'Calculateur frais de port',
        'path' => 'calculateur/',
        'enabled' => true
    ],
    'adr' => [
        'name' => 'Gestion ADR', 
        'path' => 'adr/',
        'enabled' => true
    ],
    'controle-qualite' => [
        'name' => 'Contrôle qualité',
        'path' => 'controle-qualite/',
        'enabled' => true
    ]
];

foreach ($test_modules as $key => $module) {
    echo "📦 Module {$key}: {$module['name']} ";
    if (is_dir(__DIR__ . '/' . $module['path'])) {
        echo "✅ Répertoire existe<br>\n";
    } else {
        echo "❌ Répertoire manquant: " . __DIR__ . '/' . $module['path'] . "<br>\n";
    }
}

// Test 6: Session
echo "<h2>🔐 Test de session</h2>\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✅ Session démarrée<br>\n";
} else {
    echo "✅ Session déjà active<br>\n";
}
echo "Session ID: " . session_id() . "<br>\n";

// Test 7: Fonction simple d'affichage
echo "<h2>🎨 Test d'affichage HTML</h2>\n";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Portail Guldagil</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .module { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .ok { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>🏠 Test Portail Guldagil</h1>
    
    <div class="module">
        <h3>📊 Statistiques de test</h3>
        <p>Calculs aujourd'hui: <strong><?= rand(20, 100) ?></strong></p>
        <p>Modules actifs: <strong>3</strong></p>
    </div>
    
    <div class="module">
        <h3>🧮 Module Calculateur</h3>
        <p>Status: <span class="ok">✅ Opérationnel</span></p>
        <a href="calculateur/">Accéder au calculateur</a>
    </div>
    
    <div class="module">
        <h3>⚠️ Module ADR</h3>
        <p>Status: <span class="ok">✅ Opérationnel</span></p>
        <a href="adr/">Accéder à ADR</a>
    </div>
    
    <div class="module">
        <h3>🔍 Module Contrôle Qualité</h3>
        <p>Status: <span class="ok">✅ Nouveau module intégré</span></p>
        <a href="controle-qualite/">Accéder au contrôle qualité</a>
    </div>
    
    <div class="module">
        <h3>⚙️ Administration</h3>
        <p>Status: <span class="ok">✅ Opérationnel</span></p>
        <a href="admin/">Accéder à l'administration</a>
    </div>
    
    <?php if (function_exists('getVersionInfo') && function_exists('renderVersionFooter')): ?>
    <footer style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc;">
        <p><?= renderVersionFooter() ?></p>
    </footer>
    <?php else: ?>
    <footer style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc;">
        <p>Version: Test • Build: Debug</p>
    </footer>
    <?php endif; ?>
    
    <script>
        console.log('🏠 Page de test chargée avec succès');
        console.log('📍 Chemin:', window.location.pathname);
        
        // Test des liens
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                console.log('🔗 Clic sur:', href);
                
                // Vérifier si le lien existe
                fetch(href, {method: 'HEAD'})
                    .then(response => {
                        if (!response.ok) {
                            e.preventDefault();
                            alert('❌ Module non accessible: ' + href);
                        }
                    })
                    .catch(error => {
                        console.warn('⚠️ Erreur test lien:', error);
                    });
            });
        });
    </script>
</body>
</html>

<?php
echo "\n<!-- Debug terminé -->\n";
?>

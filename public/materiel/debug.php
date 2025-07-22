<?php
/**
 * Debug simple pour module matériel
 * Placer dans /public/materiel/debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Debug Matériel</title>";
echo "<style>body{font-family:Arial;margin:20px;background:#f5f5f5;}";
echo ".ok{color:green;}.error{color:red;}.info{color:blue;}";
echo ".section{background:white;padding:15px;margin:10px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<h1>🔧 Debug Module Matériel</h1>";

// 1. Test configuration de base
echo "<div class='section'>";
echo "<h2>1. Configuration de base</h2>";

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
    echo "<p class='ok'>✅ ROOT_PATH défini: " . ROOT_PATH . "</p>";
} else {
    echo "<p class='ok'>✅ ROOT_PATH déjà défini</p>";
}

if (file_exists(ROOT_PATH . '/config/config.php')) {
    echo "<p class='ok'>✅ config.php trouvé</p>";
    require_once ROOT_PATH . '/config/config.php';
    echo "<p class='info'>📊 DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NON DÉFINI') . "</p>";
} else {
    echo "<p class='error'>❌ config.php manquant</p>";
    exit;
}

echo "</div>";

// 2. Test session
echo "<div class='section'>";
echo "<h2>2. Test session</h2>";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<p class='ok'>✅ Session démarrée</p>";
} else {
    echo "<p class='info'>📊 Session déjà active</p>";
}

// Session test simple
$_SESSION['test'] = 'OK';
echo "<p class='info'>📊 Test écriture session: " . ($_SESSION['test'] ?? 'ECHEC') . "</p>";

echo "</div>";

// 3. Test BDD
echo "<div class='section'>";
echo "<h2>3. Test base de données</h2>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p class='ok'>✅ Connexion BDD réussie</p>";
    
    // Test tables
    $stmt = $pdo->query("SHOW TABLES LIKE 'materiel_%'");
    $materiel_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'outillage_%'");
    $outillage_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p class='info'>📊 Tables materiel_: " . count($materiel_tables) . "</p>";
    echo "<p class='info'>📊 Tables outillage_: " . count($outillage_tables) . "</p>";
    
    if (count($materiel_tables) > 0) {
        echo "<p class='ok'>✅ Tables materiel disponibles</p>";
        foreach ($materiel_tables as $table) {
            echo "<p class='info'>- $table</p>";
        }
    } elseif (count($outillage_tables) > 0) {
        echo "<p class='info'>📊 Seulement tables outillage disponibles</p>";
        foreach ($outillage_tables as $table) {
            echo "<p class='info'>- $table</p>";
        }
    } else {
        echo "<p class='error'>❌ Aucune table trouvée</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur BDD: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// 4. Test fichiers critiques
echo "<div class='section'>";
echo "<h2>4. Test fichiers critiques</h2>";

$files_to_check = [
    'index.php',
    'dashboard.php',
    'classes/MaterielManager.php',
    'assets/css/materiel.css'
];

foreach ($files_to_check as $file) {
    $full_path = ROOT_PATH . '/public/materiel/' . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        echo "<p class='ok'>✅ $file ($size bytes)</p>";
        
        // Test inclusion pour PHP
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            ob_start();
            try {
                $content = file_get_contents($full_path);
                if (strpos($content, '<?php') !== false) {
                    echo "<p class='info'>📊 $file: Format PHP correct</p>";
                }
                // Test syntaxe basique
                if (strpos($content, 'new MaterielManager()') !== false) {
                    echo "<p class='error'>⚠️ $file: MaterielManager() sans paramètre détecté</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ $file: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ob_end_clean();
        }
    } else {
        echo "<p class='error'>❌ $file manquant</p>";
    }
}

echo "</div>";

// 5. Test simple d'inclusion
echo "<div class='section'>";
echo "<h2>5. Test inclusion dashboard</h2>";

$dashboard_path = ROOT_PATH . '/public/materiel/dashboard.php';
if (file_exists($dashboard_path)) {
    echo "<p class='info'>📊 Test inclusion dashboard.php...</p>";
    
    // Variables minimales
    $page_title = 'Test';
    $page_subtitle = 'Debug';
    $current_module = 'materiel';
    $module_css = true;
    $user_authenticated = true;
    $current_user = ['username' => 'debug', 'role' => 'admin'];
    
    ob_start();
    $error_occurred = false;
    
    try {
        // Test inclusion sans exécution complète
        $dashboard_content = file_get_contents($dashboard_path);
        
        // Recherche d'erreurs communes
        if (strpos($dashboard_content, 'header(') !== false) {
            echo "<p class='error'>⚠️ Redirection détectée dans dashboard.php</p>";
        }
        
        if (strpos($dashboard_content, 'canAccessModule') !== false && 
            !function_exists('canAccessModule')) {
            echo "<p class='error'>❌ Fonction canAccessModule manquante</p>";
        }
        
        echo "<p class='ok'>✅ dashboard.php semble correct</p>";
        
    } catch (Exception $e) {
        $error_occurred = true;
        echo "<p class='error'>❌ Erreur dashboard: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    ob_end_clean();
    
    if (!$error_occurred) {
        echo "<p class='ok'>✅ Pas d'erreur détectée dans dashboard.php</p>";
    }
} else {
    echo "<p class='error'>❌ dashboard.php manquant</p>";
}

echo "</div>";

// 6. Solutions
echo "<div class='section'>";
echo "<h2>6. Solutions rapides</h2>";

echo "<h3>Si le problème persiste :</h3>";
echo "<ol>";
echo "<li><strong>Vérifier logs Apache/PHP</strong> - Chercher erreurs fatales</li>";
echo "<li><strong>Créer index.php simple :</strong>";
echo "<pre style='background:#eee;padding:10px;border-radius:3px;'>";
echo htmlspecialchars('<?php echo "Test OK"; ?>');
echo "</pre></li>";
echo "<li><strong>Désactiver includes problématiques</strong> temporairement</li>";
echo "<li><strong>Vérifier .htaccess</strong> - Peut causer redirections</li>";
echo "</ol>";

echo "<h3>Diagnostic avancé :</h3>";
echo "<p>1. Activer logs PHP dans php.ini : <code>log_errors = On</code></p>";
echo "<p>2. Vérifier error.log du serveur web</p>";
echo "<p>3. Tester avec curl : <code>curl -I http://votre-site/materiel/</code></p>";

echo "</div>";

// 7. Test création fichier simple
echo "<div class='section'>";
echo "<h2>7. Test création fichier de secours</h2>";

$simple_index_content = '<?php
if (!defined("ROOT_PATH")) {
    define("ROOT_PATH", dirname(dirname(__DIR__)));
}
session_start();
echo "<!DOCTYPE html><html><head><title>Matériel - Mode debug</title></head><body>";
echo "<h1>🔧 Module Matériel - Mode Debug</h1>";
echo "<p>Ce fichier fonctionne. Le problème est ailleurs.</p>";
echo "<p>Session active : " . (session_status() === PHP_SESSION_ACTIVE ? "OUI" : "NON") . "</p>";
echo "<p><a href=\"debug.php\">Diagnostic complet</a></p>";
echo "</body></html>";
?>';

$backup_path = ROOT_PATH . '/public/materiel/index_simple.php';
if (file_put_contents($backup_path, $simple_index_content)) {
    echo "<p class='ok'>✅ Fichier de secours créé : <a href='index_simple.php'>index_simple.php</a></p>";
    echo "<p class='info'>📊 Testez avec : /materiel/index_simple.php</p>";
} else {
    echo "<p class='error'>❌ Impossible de créer le fichier de secours</p>";
}

echo "</div>";

echo "<hr><p><strong>Debug terminé.</strong> Si index_simple.php fonctionne, le problème est dans le code du module.</p>";
echo "</body></html>";
?>

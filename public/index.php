<?php
/**
 * GULDAGIL PORTAL - Point d'entrée principal (VERSION CORRIGÉE)
 */

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chemins de base
define('ROOT_PATH', dirname(__DIR__));

// Configuration minimale si config pas encore disponible
if (!file_exists(ROOT_PATH . '/config/config.php')) {
    // Configuration d'urgence
    define('APP_VERSION', '2.0.0');
    define('DEBUG', true);
    
    // Créer le fichier .env temporaire si n'existe pas
    if (!file_exists(ROOT_PATH . '/.env')) {
        $envContent = "APP_ENV=development
DEBUG=true
DB_HOST=localhost
DB_NAME=guldagil_portal
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4";
        file_put_contents(ROOT_PATH . '/.env', $envContent);
    }
    
    // Créer les dossiers nécessaires
    $dirs = ['storage/logs', 'storage/cache', 'config', 'includes/functions'];
    foreach ($dirs as $dir) {
        if (!is_dir(ROOT_PATH . '/' . $dir)) {
            mkdir(ROOT_PATH . '/' . $dir, 0755, true);
        }
    }
    
    // Créer les fichiers de config minimaux
    createMinimalConfig();
}

// Inclusion de la config
try {
    require_once ROOT_PATH . '/config/config.php';
} catch (Exception $e) {
    die("Erreur config: " . $e->getMessage());
}

// Fonctions utilitaires si pas définies
if (!function_exists('clean')) {
    function clean($string) {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice($amount, $currency = '€') {
        return number_format((float)$amount, 2, ',', ' ') . ' ' . $currency;
    }
}

// Version simple pour éviter l'erreur
define('BUILD_NUMBER', date('Ymd') . '001');
define('BUILD_DATE', date('Y-m-d H:i:s'));

$request = $_SERVER['REQUEST_URI'] ?? '/';
$request = strtok($request, '?');
$request = rtrim($request, '/');
if (empty($request)) $request = '/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guldagil Portal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #3b82f6; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .nav { display: flex; gap: 10px; margin-bottom: 20px; }
        .nav a { padding: 8px 15px; background: #f0f0f0; text-decoration: none; border-radius: 4px; color: #333; }
        .nav a:hover { background: #e0e0e0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧮 Guldagil Portal</h1>
            <p>Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?></p>
        </div>
        
        <nav class="nav">
            <a href="/">🏠 Accueil</a>
            <a href="/admin/">⚙️ Admin</a>
            <a href="/adr/">⚠️ ADR</a>
        </nav>
        
        <div class="content">
            <?php
            // Routage simple
            switch ($request) {
                case '/':
                    echo "<h2>🧮 Calculateur de frais de port</h2>";
                    echo "<p>Module calculateur en cours de développement...</p>";
                    
                    // Test base de données
                    if (isset($db)) {
                        try {
                            $stmt = $db->query("SHOW TABLES");
                            $tables = $stmt->fetchAll();
                            echo "<div style='background:#e8f5e8;padding:10px;border-radius:4px;margin:10px 0;'>";
                            echo "✅ Base de données connectée (" . count($tables) . " tables trouvées)";
                            echo "</div>";
                        } catch (Exception $e) {
                            echo "<div style='background:#ffeaea;padding:10px;border-radius:4px;margin:10px 0;'>";
                            echo "❌ Erreur BDD: " . $e->getMessage();
                            echo "</div>";
                        }
                    } else {
                        echo "<div style='background:#fff3cd;padding:10px;border-radius:4px;margin:10px 0;'>";
                        echo "⚠️ Base de données non configurée";
                        echo "</div>";
                    }
                    break;
                    
                case '/admin':
                case '/admin/':
                    echo "<h2>⚙️ Administration</h2>";
                    echo "<p>Interface d'administration en cours de développement...</p>";
                    break;
                    
                case '/adr':
                case '/adr/':
                    echo "<h2>⚠️ Gestion ADR</h2>";
                    echo "<p>Module ADR en cours de développement...</p>";
                    break;
                    
                default:
                    http_response_code(404);
                    echo "<h2>❌ Erreur 404</h2>";
                    echo "<p>Page non trouvée: " . htmlspecialchars($request) . "</p>";
                    break;
            }
            ?>
        </div>
        
        <div class="footer">
            <p>© <?= date('Y') ?> Guldagil Portal - Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?></p>
            <p>Dernière mise à jour: <?= BUILD_DATE ?></p>
        </div>
    </div>
</body>
</html>

<?php
/**
 * Créer les fichiers de configuration minimaux
 */
function createMinimalConfig() {
    $configDir = ROOT_PATH . '/config';
    
    // config.php minimal
    $configContent = '<?php
define("APP_VERSION", "2.0.0");
define("DEBUG", true);
date_default_timezone_set("Europe/Paris");

$envFile = ROOT_PATH . "/.env";
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile, false, INI_SCANNER_TYPED);
    if ($env !== false) {
        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
}

try {
    $dsn = "mysql:host=" . ($_ENV["DB_HOST"] ?? "localhost") . ";dbname=" . ($_ENV["DB_NAME"] ?? "guldagil_portal") . ";charset=utf8mb4";
    $db = new PDO($dsn, $_ENV["DB_USER"] ?? "root", $_ENV["DB_PASS"] ?? "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // Connexion BDD échoue = pas grave en développement
    $db = null;
}
';
    
    file_put_contents($configDir . '/config.php', $configContent);
    
    // modules.php minimal
    $modulesContent = '<?php
define("MODULES", [
    "calculateur" => ["enabled" => true, "public" => true],
    "admin" => ["enabled" => true, "public" => false],
    "adr" => ["enabled" => true, "public" => false]
]);
';
    
    file_put_contents($configDir . '/modules.php', $modulesContent);
    
    // helpers.php minimal
    $helpersDir = ROOT_PATH . '/includes/functions';
    if (!is_dir($helpersDir)) {
        mkdir($helpersDir, 0755, true);
    }
    
    $helpersContent = '<?php
function clean($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, "UTF-8");
}

function formatPrice($amount, $currency = "€") {
    return number_format((float)$amount, 2, ",", " ") . " " . $currency;
}
';
    
    file_put_contents($helpersDir . '/helpers.php', $helpersContent);
}
?>

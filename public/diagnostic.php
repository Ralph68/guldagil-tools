<?php
// Diagnostic complet - Placez dans /public/diagnostic.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Diagnostic Complet Projet</h1><pre>";

$root = dirname(__DIR__);
echo "=== RACINE: $root ===\n\n";

// 1. Structure fichiers critiques
echo "=== FICHIERS CRITIQUES ===\n";
$critical = [
    '/.htaccess' => 'Redirection Apache',
    '/public/.htaccess' => 'Config public',
    '/public/index.php' => 'Page accueil',
    '/config/config.php' => 'Config principale',
    '/config/database.php' => 'Config BDD',
    '/config/version.php' => 'Version info'
];

foreach ($critical as $file => $desc) {
    $path = $root . $file;
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    echo "$desc: " . ($exists ? ($readable ? '✅' : '❌ LECTURE') : '❌ MANQUANT') . "\n";
    
    if ($exists && $readable) {
        $size = filesize($path);
        echo "  Taille: {$size}o\n";
        
        // Vérif syntaxe PHP
        if (str_ends_with($file, '.php')) {
            $result = exec("php -l \"$path\" 2>&1", $output, $return);
            echo "  Syntaxe: " . ($return === 0 ? '✅' : '❌ ' . implode(' ', $output)) . "\n";
        }
    }
}

// 2. Test inclusion config
echo "\n=== TEST CONFIG ===\n";
try {
    define('ROOT_PATH', $root);
    ob_start();
    include $root . '/config/config.php';
    $config_output = ob_get_clean();
    
    echo "✅ Config inclus\n";
    if ($config_output) echo "Output: " . trim($config_output) . "\n";
    
    // Test constantes
    $constants = ['DEBUG', 'MODULES', 'DB_HOST'];
    foreach ($constants as $const) {
        echo "$const: " . (defined($const) ? '✅' : '❌') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . " (ligne " . $e->getLine() . ")\n";
} catch (Error $e) {
    echo "❌ FATAL: " . $e->getMessage() . " (ligne " . $e->getLine() . ")\n";
}

// 3. Test BDD
echo "\n=== TEST BASE DE DONNÉES ===\n";
if (defined('DB_HOST')) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ Connexion BDD OK\n";
        
        // Test table
        $stmt = $pdo->query("SHOW TABLES LIKE 'auth_users'");
        echo "Table auth_users: " . ($stmt->rowCount() > 0 ? '✅' : '❌') . "\n";
        
    } catch (Exception $e) {
        echo "❌ BDD: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Constantes DB manquantes\n";
}

// 4. Permissions
echo "\n=== PERMISSIONS ===\n";
$dirs = ['', '/config', '/public', '/storage', '/storage/logs'];
foreach ($dirs as $dir) {
    $path = $root . $dir;
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        echo ($dir ?: '/') . ": $perms " . ($writable ? '✅' : '❌') . "\n";
    }
}

// 5. .htaccess content
echo "\n=== HTACCESS ===\n";
$htaccess = $root . '/.htaccess';
if (file_exists($htaccess)) {
    echo "Contenu:\n" . file_get_contents($htaccess) . "\n";
} else {
    echo "❌ Pas de .htaccess racine\n";
}

$pub_htaccess = $root . '/public/.htaccess';
if (file_exists($pub_htaccess)) {
    echo "Public .htaccess:\n" . file_get_contents($pub_htaccess) . "\n";
} else {
    echo "❌ Pas de .htaccess public\n";
}

// 6. Logs Apache récents
echo "\n=== LOGS APACHE ===\n";
$log_files = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    $root . '/storage/logs/error.log'
];

foreach ($log_files as $log) {
    if (file_exists($log) && is_readable($log)) {
        echo "Log: $log\n";
        $lines = file($log);
        $recent = array_filter(array_slice($lines, -10), function($line) {
            return strpos($line, date('Y-m-d')) !== false;
        });
        
        foreach ($recent as $line) {
            if (stripos($line, 'gul') !== false || stripos($line, '500') !== false) {
                echo "  " . trim($line) . "\n";
            }
        }
        break;
    }
}

// 7. Variables serveur
echo "\n=== SERVEUR ===\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "Apache: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "\n";

echo "</pre>";
?>

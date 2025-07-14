<?php
/**
 * Titre: Endpoint de santé pour CI/CD
 * Chemin: /public/health.php
 * Version: 0.5 beta + build auto
 */

// Pas de session ni authentification pour ce endpoint
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$response = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'checks' => []
];

$overall_status = true;

try {
    // Test 1: Configuration de base
    define('ROOT_PATH', dirname(__DIR__));
    
    $config_files = [
        'config.php' => ROOT_PATH . '/config/config.php',
        'version.php' => ROOT_PATH . '/config/version.php'
    ];
    
    foreach ($config_files as $name => $path) {
        $exists = file_exists($path);
        $response['checks'][$name] = $exists ? 'ok' : 'missing';
        if (!$exists) $overall_status = false;
    }
    
    // Test 2: Chargement configuration si possible
    if ($response['checks']['config.php'] === 'ok') {
        require_once $config_files['config.php'];
        $response['checks']['config_loaded'] = 'ok';
    } else {
        $response['checks']['config_loaded'] = 'failed';
        $overall_status = false;
    }
    
    // Test 3: Version info
    if ($response['checks']['version.php'] === 'ok') {
        require_once $config_files['version.php'];
        $response['version'] = [
            'app' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
            'build' => defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'unknown'
        ];
        $response['checks']['version_loaded'] = 'ok';
    } else {
        $response['checks']['version_loaded'] = 'failed';
    }
    
    // Test 4: Base de données (optionnel)
    if (function_exists('getDBConnection') || (defined('DB_HOST') && defined('DB_NAME'))) {
        try {
            if (function_exists('getDBConnection')) {
                $db = getDBConnection();
            } else {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $db = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
            }
            
            $db->query("SELECT 1")->fetch();
            $response['checks']['database'] = 'ok';
        } catch (Exception $e) {
            $response['checks']['database'] = 'error';
            $response['database_error'] = $e->getMessage();
            // BDD en erreur ne bloque pas le health check global
        }
    } else {
        $response['checks']['database'] = 'not_configured';
    }
    
    // Test 5: Permissions critiques
    $critical_dirs = [
        ROOT_PATH . '/storage/logs',
        ROOT_PATH . '/storage/cache'
    ];
    
    $permissions_ok = true;
    foreach ($critical_dirs as $dir) {
        if (!is_dir($dir) || !is_writable($dir)) {
            $permissions_ok = false;
            break;
        }
    }
    
    $response['checks']['permissions'] = $permissions_ok ? 'ok' : 'warning';
    
    // Test 6: Modules critiques
    $auth_manager = ROOT_PATH . '/core/auth/AuthManager.php';
    $response['checks']['auth_manager'] = file_exists($auth_manager) ? 'ok' : 'missing';
    
} catch (Exception $e) {
    $overall_status = false;
    $response['error'] = $e->getMessage();
    $response['checks']['exception'] = 'error';
}

// Status global
$response['status'] = $overall_status ? 'ok' : 'error';

// Code HTTP approprié
http_response_code($overall_status ? 200 : 503);

// Retour JSON
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;

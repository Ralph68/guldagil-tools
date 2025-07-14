<?php
/**
 * Titre: Health check pour monitoring
 * Chemin: /public/health.php
 * Version: 0.5 beta + build auto
 */

// Headers pour empêcher le cache
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json');

// Statut par défaut
$status = 'ok';
$checks = [];
$timestamp = date('c');

try {
    // Check 1: Vérification fichiers critiques
    $required_files = [
        '/config/config.php',
        '/config/version.php'
    ];
    
    $missing_files = [];
    foreach ($required_files as $file) {
        $path = dirname(__DIR__) . $file;
        if (!file_exists($path)) {
            $missing_files[] = $file;
        }
    }
    
    $checks['files'] = [
        'status' => empty($missing_files) ? 'ok' : 'error',
        'missing' => $missing_files
    ];
    
    if (!empty($missing_files)) {
        $status = 'error';
    }
    
    // Check 2: Base de données (si config disponible)
    $db_status = 'skip';
    $config_path = dirname(__DIR__) . '/config/config.php';
    
    if (file_exists($config_path)) {
        try {
            require_once $config_path;
            
            if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                    DB_USER,
                    defined('DB_PASS') ? DB_PASS : '',
                    [
                        PDO::ATTR_TIMEOUT => 3,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]
                );
                $db_status = 'ok';
            }
        } catch (PDOException $e) {
            $db_status = 'error';
            $checks['database']['error'] = $e->getMessage();
            $status = 'warning'; // DB down mais app peut fonctionner
        }
    }
    
    $checks['database'] = ['status' => $db_status];
    
    // Check 3: Permissions critiques
    $dirs_to_check = [
        '/storage/logs',
        '/storage/cache'
    ];
    
    $permission_issues = [];
    foreach ($dirs_to_check as $dir) {
        $path = dirname(__DIR__) . $dir;
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        if (!is_writable($path)) {
            $permission_issues[] = $dir;
        }
    }
    
    $checks['permissions'] = [
        'status' => empty($permission_issues) ? 'ok' : 'warning',
        'issues' => $permission_issues
    ];
    
    // Check 4: Espace disque
    $free_space = disk_free_space('.');
    $free_gb = round($free_space / 1024 / 1024 / 1024, 2);
    
    $checks['disk_space'] = [
        'status' => $free_gb > 0.1 ? 'ok' : 'warning',
        'free_gb' => $free_gb
    ];
    
    // Check 5: Version et build
    if (file_exists(dirname(__DIR__) . '/config/version.php')) {
        require_once dirname(__DIR__) . '/config/version.php';
        $checks['version'] = [
            'app_version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
            'build_number' => defined('BUILD_NUMBER') ? BUILD_NUMBER : 'unknown'
        ];
    }
    
} catch (Exception $e) {
    $status = 'error';
    $checks['error'] = $e->getMessage();
}

// Réponse finale
$response = [
    'status' => $status,
    'timestamp' => $timestamp,
    'uptime' => time() - filectime(__FILE__),
    'checks' => $checks,
    'server' => [
        'php_version' => PHP_VERSION,
        'memory_used' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB',
        'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB'
    ]
];

// Status HTTP approprié
switch ($status) {
    case 'ok':
        http_response_code(200);
        break;
    case 'warning':
        http_response_code(200); // Toujours OK pour le monitoring
        break;
    case 'error':
        http_response_code(503);
        break;
    default:
        http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>

<?php
/**
 * Titre: Visualiseur de logs - Administration
 * Chemin: /public/admin/logs.php
 * Version: 0.5 beta + build auto
 */

// Sécurité et configuration
session_start();
define('ROOT_PATH', dirname(__DIR__, 3));

// Authentification admin obligatoire
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$current_user = $_SESSION['user'] ?? ['role' => 'user'];
if (!in_array($current_user['role'], ['admin', 'dev'])) {
    http_response_code(403);
    die('<h1>❌ Accès refusé</h1><p>Droits administrateur requis</p>');
}

// Configuration des logs
$log_configs = [
    'apache_error' => [
        'name' => 'Apache Error Log',
        'icon' => '🔴',
        'paths' => [
            '/var/log/apache2/error.log',
            '/var/log/httpd/error_log',
            '/usr/local/apache/logs/error_log'
        ],
        'color' => '#dc2626',
        'sensitive' => true  // Nouveau paramètre pour marquer les logs sensibles
    ],
    'apache_access' => [
        'name' => 'Apache Access Log',
        'icon' => '📝',
        'paths' => [
            '/var/log/apache2/access.log',
            '/var/log/httpd/access_log',
            '/usr/local/apache/logs/access_log'
        ],
        'color' => '#059669',
        'sensitive' => true
    ],
    'php_error' => [
        'name' => 'PHP Error Log',
        'icon' => '🐘',
        'paths' => [
            ini_get('error_log'),
            '/var/log/php_errors.log',
            ROOT_PATH . '/storage/logs/php_errors.log'
        ],
        'color' => '#7c3aed',
        'sensitive' => true
    ],
    'app_error' => [
        'name' => 'App Error Log',
        'icon' => '🚨',
        'paths' => [
            ROOT_PATH . '/storage/logs/error.log',
            ROOT_PATH . '/storage/logs/app.log'
        ],
        'color' => '#ea580c',
        'sensitive' => true
    ]
];

// Paramètres
$selected_log = $_GET['log'] ?? 'apache_error';
$lines_count = (int)($_GET['lines'] ?? 100);
$lines_count = max(10, min(1000, $lines_count)); // Limite 10-1000
$search_term = trim($_GET['search'] ?? '');
$auto_refresh = isset($_GET['auto_refresh']);

// Variables pour template
$page_title = 'Visualiseur de Logs';
$page_subtitle = 'Surveillance système';
$current_module = 'admin';
$user_authenticated = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚙️', 'text' => 'Administration', 'url' => '/admin/', 'active' => false],
    ['icon' => '📊', 'text' => 'Logs', 'url' => '/admin/logs.php', 'active' => true]
];

/**
 * Trouve le premier fichier de log existant et lisible
 */
function findLogFile($paths) {
    foreach ($paths as $path) {
        if ($path && file_exists($path) && is_readable($path)) {
            return $path;
        }
    }
    return null;
}

/**
 * Lit les dernières lignes d'un fichier
 */
function readLogLines($file_path, $lines_count, $search_term = '') {
    if (!file_exists($file_path) || !is_readable($file_path)) {
        return ['error' => 'Fichier non accessible'];
    }
    
    try {
        $file_size = filesize($file_path);
        if ($file_size === 0) {
            return ['lines' => [], 'total_size' => 0];
        }
        
        // Lecture efficace des dernières lignes
        $lines = [];
        $file = new SplFileObject($file_path);
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();
        
        $start_line = max(0, $total_lines - $lines_count);
        $file->seek($start_line);
        
        while (!$file->eof()) {
            $line = $file->current();
            if ($line !== null) {
                $line = rtrim($line);
                
                // Filtrage par terme de recherche
                if (empty($search_term) || stripos($line, $search_term) !== false) {
                    $lines[] = [
                        'number' => $file->key() + 1,
                        'content' => $line,
                        'timestamp' => extractTimestamp($line),
                        'level' => extractLogLevel($line)
                    ];
                }
            }
            $file->next();
        }
        
        return [
            'lines' => array_reverse($lines), // Plus récent en premier
            'total_size' => $file_size,
            'total_lines' => $total_lines,
            'file_modified' => filemtime($file_path)
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Extrait timestamp d'une ligne de log
 */
function extractTimestamp($line) {
    // Pattern pour différents formats de date
    $patterns = [
        '/\[([^\]]+)\]/',  // [date]
        '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',  // YYYY-MM-DD HH:MM:SS
        '/^(\w{3} \w{3} \d{2} \d{2}:\d{2}:\d{2} \d{4})/'  // Apache format
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $line, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Extrait le niveau de log
 */
function extractLogLevel($line) {
    $levels = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];
    foreach ($levels as $level) {
        if (stripos($line, $level) !== false) {
            return strtolower($level);
        }
    }
    return 'info';
}

// Récupération des logs
$current_log_config = $log_configs[$selected_log] ?? $log_configs['apache_error'];
$log_file_path = findLogFile($current_log_config['paths']);
$log_data = $log_file_path ? readLogLines($log_file_path, $lines_count, $search_term) : ['error' => 'Aucun fichier de log trouvé'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Administration</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <style>
        :root {
            --primary: #2563eb;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #0891b2;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
        }

        .header {
            background: var(--primary);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .breadcrumbs {
            display: flex;
            gap: 0.5rem;
            font-size: 0.9rem;
            margin: 1rem 0;
        }

        .breadcrumb-item {
            color: #6b7280;
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--primary);
            font-weight: 500;
        }

        .controls {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .form-group label {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--gray-800);
        }

        .form-control {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-secondary { background: #6b7280; color: white; }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .log-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            overflow-x: auto;
        }

        .log-tab {
            padding: 0.75rem 1rem;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray-800);
            white-space: nowrap;
            transition: all 0.2s;
        }

        .log-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .log-tab:hover:not(.active) {
            background: var(--gray-100);
        }

        .log-container {
            background: #1f2937;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .log-header {
            background: #374151;
            padding: 1rem;
            border-bottom: 1px solid #4b5563;
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .log-info {
            color: #d1d5db;
            font-size: 0.9rem;
        }

        .log-content {
            max-height: 70vh;
            overflow-y: auto;
            padding: 1rem;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .log-line {
            margin-bottom: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            word-wrap: break-word;
            border-left: 3px solid transparent;
        }

        .log-line:hover {
            background: rgba(255,255,255,0.05);
        }

        .log-line.error { 
            border-left-color: var(--danger);
            background: rgba(220, 38, 38, 0.1);
        }

        .log-line.warning { 
            border-left-color: var(--warning);
            background: rgba(217, 119, 6, 0.1);
        }

        .log-line.info { 
            border-left-color: var(--info);
        }

        .log-line-number {
            color: #6b7280;
            margin-right: 1rem;
            font-size: 0.8rem;
        }

        .log-line-timestamp {
            color: #9ca3af;
            margin-right: 1rem;
        }

        .log-line-content {
            color: #f3f4f6;
        }

        .highlight {
            background: yellow;
            color: black;
            padding: 0 2px;
            border-radius: 2px;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray-800);
            margin-top: 0.25rem;
        }

        .auto-refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            z-index: 1000;
        }

        @media (max-width: 768px) {
            .controls-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Auto-refresh indicator -->
    <?php if ($auto_refresh): ?>
    <div class="auto-refresh-indicator">
        🔄 Actualisation automatique (30s)
    </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1><?= htmlspecialchars($page_title) ?></h1>
                    <p><?= htmlspecialchars($page_subtitle) ?></p>
                </div>
                <div>
                    <span>👤 <?= htmlspecialchars($current_user['username'] ?? 'Admin') ?></span>
                    <a href="/admin/" class="btn btn-secondary" style="margin-left: 1rem;">← Retour Admin</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <?php if ($i > 0):

<?php
/**
 * Titre: Scanner d'erreurs - Diagnostic portail complet
 * Chemin: /public/admin/scanner.php
 * Version: 0.5 beta + build auto
 */

// Configuration initiale
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Déterminer ROOT_PATH
if (!defined('ROOT_PATH')) {
    $root_path = dirname(dirname(__DIR__));
    if (!file_exists($root_path . '/config/config.php')) {
        $root_path = dirname($root_path);
    }
    define('ROOT_PATH', $root_path);
}

// Charger configuration si possible
$config_loaded = false;
try {
    require_once ROOT_PATH . '/config/config.php';
    $config_loaded = true;
} catch (Exception $e) {
    $config_error = $e->getMessage();
}

// Variables template
$page_title = 'Scanner d\'erreurs';
$page_subtitle = 'Diagnostic complet du portail';
$current_module = 'admin';
$user_authenticated = true;

// Actions
$action = $_POST['action'] ?? $_GET['action'] ?? 'scan';
$scan_deep = isset($_POST['deep_scan']) || isset($_GET['deep']);

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚙️', 'text' => 'Administration', 'url' => '/admin/', 'active' => false],
    ['icon' => '🔍', 'text' => 'Scanner', 'url' => '/admin/scanner.php', 'active' => true]
];

/**
 * Fonction principale de scan
 */
function scanPortal($deep_scan = false) {
    $results = [
        'structure' => scanStructure(),
        'files' => scanCriticalFiles(),
        'syntax' => scanSyntaxErrors(),
        'permissions' => scanPermissions(),
        'config' => scanConfiguration(),
        'css_js' => scanAssets(),
        'database' => scanDatabase()
    ];
    
    if ($deep_scan) {
        $results['modules'] = scanModules();
        $results['logs'] = scanLogs();
    }
    
    return $results;
}

/**
 * Scan structure dossiers
 */
function scanStructure() {
    $required_dirs = [
        'config' => 'Configuration globale',
        'core' => 'Classes système',
        'public' => 'Document root',
        'public/assets' => 'Assets globaux',
        'public/assets/css' => 'CSS globaux',
        'public/assets/js' => 'JS globaux',
        'templates' => 'Templates communs',
        'storage' => 'Stockage données',
        'storage/logs' => 'Logs système',
        'public/admin' => 'Module admin',
        'public/auth' => 'Module auth',
        'public/user' => 'Module user'
    ];
    
    $results = [];
    foreach ($required_dirs as $dir => $desc) {
        $path = ROOT_PATH . '/' . $dir;
        $exists = is_dir($path);
        $writable = $exists ? is_writable($path) : false;
        $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : null;
        
        $results[$dir] = [
            'path' => $path,
            'description' => $desc,
            'exists' => $exists,
            'writable' => $writable,
            'permissions' => $perms,
            'status' => $exists ? 'ok' : 'error'
        ];
    }
    
    return $results;
}

/**
 * Scan fichiers critiques
 */
function scanCriticalFiles() {
    $critical_files = [
        'config/config.php' => 'Configuration principale',
        'config/version.php' => 'Gestion version',
        'config/modules.php' => 'Configuration modules',
        'core/auth/AuthManager.php' => 'Gestionnaire auth',
        'templates/header.php' => 'Header global',
        'templates/footer.php' => 'Footer global',
        'public/index.php' => 'Point entrée principal',
        'public/.htaccess' => 'Réécriture URLs',
        'public/assets/css/portal.css' => 'CSS principal',
        'public/admin/index.php' => 'Dashboard admin',
        'public/auth/login.php' => 'Page connexion'
    ];
    
    $results = [];
    foreach ($critical_files as $file => $desc) {
        $path = ROOT_PATH . '/' . $file;
        $exists = file_exists($path);
        $readable = $exists ? is_readable($path) : false;
        $size = $exists ? filesize($path) : 0;
        $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : null;
        
        $status = 'error';
        if ($exists && $readable && $size > 0) {
            $status = 'ok';
        } elseif ($exists) {
            $status = 'warning';
        }
        
        $results[$file] = [
            'path' => $path,
            'description' => $desc,
            'exists' => $exists,
            'readable' => $readable,
            'size' => $size,
            'permissions' => $perms,
            'status' => $status
        ];
    }
    
    return $results;
}

/**
 * Scan erreurs syntaxe
 */
function scanSyntaxErrors() {
    $php_files = [];
    $results = [];
    
    // Scan récursif fichiers PHP
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(ROOT_PATH . '/public'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $php_files[] = $file->getPathname();
        }
    }
    
    // Vérifier syntaxe
    foreach (array_slice($php_files, 0, 20) as $file) { // Limite pour performance
        $relative_path = str_replace(ROOT_PATH . '/', '', $file);
        
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        $has_error = strpos($output, 'Parse error') !== false || strpos($output, 'Fatal error') !== false;
        
        if ($has_error || strpos($output, 'No syntax errors') === false) {
            $results[$relative_path] = [
                'file' => $relative_path,
                'error' => trim($output),
                'status' => 'error'
            ];
        } else {
            $results[$relative_path] = [
                'file' => $relative_path,
                'status' => 'ok'
            ];
        }
    }
    
    return $results;
}

/**
 * Scan permissions
 */
function scanPermissions() {
    $check_paths = [
        'public/assets',
        'storage',
        'storage/logs',
        'config'
    ];
    
    $results = [];
    foreach ($check_paths as $path) {
        $full_path = ROOT_PATH . '/' . $path;
        if (file_exists($full_path)) {
            $perms = substr(sprintf('%o', fileperms($full_path)), -4);
            $owner_writable = is_writable($full_path);
            
            $status = 'ok';
            if (!$owner_writable) {
                $status = 'warning';
            }
            if ($perms === '0777' && strpos($path, 'config') !== false) {
                $status = 'error'; // Config trop permissif
            }
            
            $results[$path] = [
                'path' => $full_path,
                'permissions' => $perms,
                'writable' => $owner_writable,
                'status' => $status
            ];
        }
    }
    
    return $results;
}

/**
 * Scan configuration
 */
function scanConfiguration() {
    $results = [];
    
    // Test connexion BDD
    try {
        $db_config = [
            'host' => DB_HOST ?? 'localhost',
            'name' => DB_NAME ?? '',
            'user' => DB_USER ?? '',
            'charset' => DB_CHARSET ?? 'utf8mb4'
        ];
        
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['name']};charset={$db_config['charset']}",
            $db_config['user'],
            DB_PASS ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $results['database'] = [
            'status' => 'ok',
            'message' => 'Connexion BDD réussie',
            'config' => $db_config
        ];
        
    } catch (Exception $e) {
        $results['database'] = [
            'status' => 'error',
            'message' => 'Erreur BDD: ' . $e->getMessage()
        ];
    }
    
    // Vérifier constantes
    $required_constants = ['ROOT_PATH', 'BASE_URL', 'DB_HOST', 'DB_NAME'];
    foreach ($required_constants as $const) {
        $results['constants'][$const] = [
            'defined' => defined($const),
            'value' => defined($const) ? constant($const) : null,
            'status' => defined($const) ? 'ok' : 'error'
        ];
    }
    
    return $results;
}

/**
 * Scan assets CSS/JS
 */
function scanAssets() {
    $assets = [
        'public/assets/css/portal.css' => 'CSS principal',
        'public/assets/css/components.css' => 'Composants CSS',
        'templates/assets/css/header.css' => 'CSS header',
        'templates/assets/css/footer.css' => 'CSS footer',
        'public/admin/assets/css/admin.css' => 'CSS admin'
    ];
    
    $results = [];
    foreach ($assets as $file => $desc) {
        $path = ROOT_PATH . '/' . $file;
        $exists = file_exists($path);
        $size = $exists ? filesize($path) : 0;
        
        // Test basique de validité CSS
        $valid_css = true;
        if ($exists && $size > 0) {
            $content = file_get_contents($path);
            $valid_css = (substr_count($content, '{') === substr_count($content, '}'));
        }
        
        $status = 'error';
        if ($exists && $size > 0 && $valid_css) {
            $status = 'ok';
        } elseif ($exists) {
            $status = 'warning';
        }
        
        $results[$file] = [
            'description' => $desc,
            'exists' => $exists,
            'size' => $size,
            'valid' => $valid_css,
            'status' => $status
        ];
    }
    
    return $results;
}

/**
 * Scan BDD
 */
function scanDatabase() {
    $results = [];
    
    try {
        $pdo = new PDO(
            "mysql:host=" . (DB_HOST ?? 'localhost') . ";dbname=" . (DB_NAME ?? ''),
            DB_USER ?? '',
            DB_PASS ?? ''
        );
        
        // Vérifier tables existantes
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $required_tables = ['auth_users', 'auth_sessions'];
        foreach ($required_tables as $table) {
            $exists = in_array($table, $tables);
            $count = 0;
            
            if ($exists) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                $count = $stmt->fetchColumn();
            }
            
            $results['tables'][$table] = [
                'exists' => $exists,
                'count' => $count,
                'status' => $exists ? 'ok' : 'error'
            ];
        }
        
        $results['connection'] = ['status' => 'ok'];
        
    } catch (Exception $e) {
        $results['connection'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }
    
    return $results;
}

/**
 * Scan modules (mode deep)
 */
function scanModules() {
    $modules = ['admin', 'auth', 'user', 'port'];
    $results = [];
    
    foreach ($modules as $module) {
        $module_path = ROOT_PATH . "/public/$module";
        $index_file = "$module_path/index.php";
        
        $results[$module] = [
            'path' => $module_path,
            'exists' => is_dir($module_path),
            'index_exists' => file_exists($index_file),
            'has_assets' => is_dir("$module_path/assets"),
            'status' => (is_dir($module_path) && file_exists($index_file)) ? 'ok' : 'warning'
        ];
    }
    
    return $results;
}

/**
 * Scan logs récents
 */
function scanLogs() {
    $log_files = [
        ROOT_PATH . '/storage/logs/error.log',
        '/var/log/apache2/error.log',
        '/var/log/php_errors.log'
    ];
    
    $results = [];
    foreach ($log_files as $log_file) {
        if (file_exists($log_file) && is_readable($log_file)) {
            $size = filesize($log_file);
            $recent_errors = 0;
            
            if ($size > 0) {
                $lines = file($log_file);
                $recent_lines = array_slice($lines, -50); // 50 dernières lignes
                
                foreach ($recent_lines as $line) {
                    if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                        $recent_errors++;
                    }
                }
            }
            
            $results[basename($log_file)] = [
                'path' => $log_file,
                'size' => $size,
                'recent_errors' => $recent_errors,
                'status' => $recent_errors > 5 ? 'warning' : 'ok'
            ];
        }
    }
    
    return $results;
}

// Exécuter le scan
$scan_results = [];
if ($action === 'scan') {
    $start_time = microtime(true);
    $scan_results = scanPortal($scan_deep);
    $scan_duration = round((microtime(true) - $start_time) * 1000, 2);
}

// Charger header/footer
include ROOT_PATH . '/templates/header.php';
?>

<style>
.scan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.scan-header h2 {
    margin: 0;
    color: #2c3e50;
}

.btn-help {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background-color 0.3s;
}

.btn-help:hover {
    background: #7f8c8d;
}

.scan-info {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 5px;
    border-left: 4px solid #3498db;
}

.scan-info small {
    color: #555;
    line-height: 1.4;
}

.help-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 10000;
    overflow-y: auto;
}

.help-content {
    background: white;
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    border-radius: 8px;
    position: relative;
}

.help-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #e74c3c;
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2rem;
}

.scanner-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.scan-controls {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.scan-controls h2 {
    margin: 0 0 1rem 0;
    color: #2c3e50;
}

.scan-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.btn-scan {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s;
}

.btn-scan:hover {
    background: #2980b9;
}

.btn-scan.deep {
    background: #e74c3c;
}

.btn-scan.deep:hover {
    background: #c0392b;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.results-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.result-section {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.result-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.result-header h3 {
    margin: 0;
    color: #2c3e50;
}

.result-content {
    padding: 1.5rem;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 5px;
    border-left: 4px solid #bdc3c7;
}

.status-item.ok {
    background: #d5f4e6;
    border-left-color: #27ae60;
}

.status-item.warning {
    background: #fef5e7;
    border-left-color: #f39c12;
}

.status-item.error {
    background: #fadbd8;
    border-left-color: #e74c3c;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.ok {
    background: #27ae60;
    color: white;
}

.status-badge.warning {
    background: #f39c12;
    color: white;
}

.status-badge.error {
    background: #e74c3c;
    color: white;
}

.scan-summary {
    background: #34495e;
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
}

.summary-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    display: block;
}

.error-details {
    background: #ecf0f1;
    padding: 0.5rem;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.8rem;
    margin-top: 0.5rem;
    white-space: pre-wrap;
}

.file-path {
    font-family: monospace;
    font-size: 0.85rem;
    color: #7f8c8d;
}

@media (max-width: 768px) {
    .scanner-container {
        padding: 1rem;
    }
    
    .results-container {
        grid-template-columns: 1fr;
    }
    
    .scan-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .summary-stats {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<div class="scanner-container">
    <div class="scan-controls">
        <div class="scan-header">
            <h2>🔍 Scanner d'erreurs du portail</h2>
            <button type="button" class="btn-help" onclick="showHelp()">
                📖 Guide d'utilisation
            </button>
        </div>
        
        <form method="post" class="scan-form">
            <input type="hidden" name="action" value="scan">
            
            <div class="checkbox-group">
                <input type="checkbox" id="deep_scan" name="deep_scan" <?= $scan_deep ? 'checked' : '' ?>>
                <label for="deep_scan">Scan approfondi (modules + logs)</label>
            </div>
            
            <button type="submit" class="btn-scan <?= $scan_deep ? 'deep' : '' ?>">
                <?= $scan_deep ? '🔬 Scanner en profondeur' : '⚡ Scanner rapide' ?>
            </button>
        </form>
        
        <div class="scan-info">
            <small>
                <strong>⚡ Rapide :</strong> Structure, fichiers, config (~2-5s) • 
                <strong>🔬 Approfondi :</strong> + Modules, logs, syntaxe (~10-30s)
            </small>
        </div>
    </div>

    <?php if (!empty($scan_results)): ?>
        <?php
        // Calculer statistiques globales
        $total_checks = 0;
        $total_errors = 0;
        $total_warnings = 0;
        $total_ok = 0;
        
        foreach ($scan_results as $section => $results) {
            if (is_array($results)) {
                foreach ($results as $item) {
                    if (isset($item['status'])) {
                        $total_checks++;
                        switch ($item['status']) {
                            case 'error': $total_errors++; break;
                            case 'warning': $total_warnings++; break;
                            case 'ok': $total_ok++; break;
                        }
                    }
                }
            }
        }
        ?>
        
        <div class="scan-summary">
            <h2>📊 Résultats du scan</h2>
            <p>Scan terminé en <?= $scan_duration ?>ms</p>
            <div class="summary-stats">
                <div class="stat-item">
                    <span class="stat-number" style="color: #27ae60;"><?= $total_ok ?></span>
                    <span>OK</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" style="color: #f39c12;"><?= $total_warnings ?></span>
                    <span>Avertissements</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" style="color: #e74c3c;"><?= $total_errors ?></span>
                    <span>Erreurs</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $total_checks ?></span>
                    <span>Total vérifications</span>
                </div>
            </div>
        </div>

        <div class="results-container">
            <!-- Structure -->
            <div class="result-section">
                <div class="result-header">
                    <h3>📁 Structure des dossiers</h3>
                </div>
                <div class="result-content">
                    <?php foreach ($scan_results['structure'] as $dir => $info): ?>
                        <div class="status-item <?= $info['status'] ?>">
                            <div>
                                <strong><?= htmlspecialchars($dir) ?></strong>
                                <div class="file-path"><?= htmlspecialchars($info['description']) ?></div>
                                <?php if ($info['permissions']): ?>
                                    <small>Permissions: <?= $info['permissions'] ?></small>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['exists'] ? '✅' : '❌' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fichiers critiques -->
            <div class="result-section">
                <div class="result-header">
                    <h3>📄 Fichiers critiques</h3>
                </div>
                <div class="result-content">
                    <?php foreach ($scan_results['files'] as $file => $info): ?>
                        <div class="status-item <?= $info['status'] ?>">
                            <div>
                                <strong><?= basename($file) ?></strong>
                                <div class="file-path"><?= htmlspecialchars($info['description']) ?></div>
                                <?php if ($info['exists']): ?>
                                    <small><?= number_format($info['size']) ?> bytes | <?= $info['permissions'] ?></small>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['exists'] ? ($info['readable'] ? '✅' : '⚠️') : '❌' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Erreurs syntaxe -->
            <div class="result-section">
                <div class="result-header">
                    <h3>🐛 Erreurs de syntaxe</h3>
                </div>
                <div class="result-content">
                    <?php 
                    $syntax_errors = array_filter($scan_results['syntax'], function($item) {
                        return $item['status'] === 'error';
                    });
                    ?>
                    
                    <?php if (empty($syntax_errors)): ?>
                        <div class="status-item ok">
                            <div><strong>Aucune erreur de syntaxe détectée</strong></div>
                            <span class="status-badge ok">✅</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($syntax_errors as $file => $info): ?>
                            <div class="status-item error">
                                <div>
                                    <strong><?= htmlspecialchars($file) ?></strong>
                                    <div class="error-details"><?= htmlspecialchars($info['error']) ?></div>
                                </div>
                                <span class="status-badge error">❌</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Configuration -->
            <div class="result-section">
                <div class="result-header">
                    <h3>⚙️ Configuration</h3>
                </div>
                <div class="result-content">
                    <!-- Connexion BDD -->
                    <div class="status-item <?= $scan_results['config']['database']['status'] ?>">
                        <div>
                            <strong>Base de données</strong>
                            <div class="file-path"><?= htmlspecialchars($scan_results['config']['database']['message']) ?></div>
                        </div>
                        <span class="status-badge <?= $scan_results['config']['database']['status'] ?>">
                            <?= $scan_results['config']['database']['status'] === 'ok' ? '✅' : '❌' ?>
                        </span>
                    </div>
                    
                    <!-- Constantes -->
                    <?php foreach ($scan_results['config']['constants'] as $const => $info): ?>
                        <div class="status-item <?= $info['status'] ?>">
                            <div>
                                <strong><?= $const ?></strong>
                                <?php if ($info['defined']): ?>
                                    <div class="file-path"><?= htmlspecialchars($info['value']) ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['defined'] ? '✅' : '❌' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Assets CSS/JS -->
            <div class="result-section">
                <div class="result-header">
                    <h3>🎨 Assets CSS/JS</h3>
                </div>
                <div class="result-content">
                    <?php foreach ($scan_results['css_js'] as $file => $info): ?>
                        <div class="status-item <?= $info['status'] ?>">
                            <div>
                                <strong><?= basename($file) ?></strong>
                                <div class="file-path"><?= htmlspecialchars($info['description']) ?></div>
                                <?php if ($info['exists']): ?>
                                    <small><?= number_format($info['size']) ?> bytes | <?= $info['valid'] ? 'Valide' : 'Erreurs détectées' ?></small>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['exists'] ? ($info['valid'] ? '✅' : '⚠️') : '❌' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Base de données -->
            <div class="result-section">
                <div class="result-header">
                    <h3>🗄️ Base de données</h3>
                </div>
                <div class="result-content">
                    <?php if (isset($scan_results['database']['tables'])): ?>
                        <?php foreach ($scan_results['database']['tables'] as $table => $info): ?>
                            <div class="status-item <?= $info['status'] ?>">
                                <div>
                                    <strong><?= $table ?></strong>
                                    <?php if ($info['exists']): ?>
                                        <div class="file-path"><?= $info['count'] ?> enregistrements</div>
                                    <?php endif; ?>
                                </div>
                                <span class="status-badge <?= $info['status'] ?>">
                                    <?= $info['exists'] ? '✅' : '❌' ?>
                                </span>
                            </div>

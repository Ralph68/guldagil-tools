<?php
/**
 * Titre: Scanner d'erreurs - Diagnostic portail complet
 * Chemin: /public/admin/scanner.php
 * Version: 0.5 beta + build auto
 */

// Configuration initiale - Mode debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Déterminer ROOT_PATH
if (!defined('ROOT_PATH')) {
    $root_path = dirname(dirname(__DIR__));
    if (!file_exists($root_path . '/config/config.php')) {
        $root_path = dirname($root_path);
    }
    define('ROOT_PATH', $root_path);
}

// Variables template
$page_title = 'Scanner d\'erreurs';
$current_module = 'admin';
$user_authenticated = true;

// Actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
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
    $results = [];
    
    try {
        $results['structure'] = scanStructure();
        $results['files'] = scanCriticalFiles();
        $results['config'] = scanConfiguration();
        $results['assets'] = scanAssets();
        $results['permissions'] = scanPermissions();
        
        if ($deep_scan) {
            $results['syntax'] = scanSyntaxErrors();
            $results['modules'] = scanModules();
            $results['database'] = scanDatabase();
        }
    } catch (Exception $e) {
        $results['error'] = 'Erreur scan: ' . $e->getMessage();
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
        'public/admin' => 'Module admin',
        'templates' => 'Templates communs',
        'storage' => 'Stockage données'
    ];
    
    $results = [];
    foreach ($required_dirs as $dir => $desc) {
        $path = ROOT_PATH . '/' . $dir;
        $exists = is_dir($path);
        $writable = $exists ? is_writable($path) : false;
        
        $status = 'error';
        if ($exists && $writable) {
            $status = 'ok';
        } elseif ($exists) {
            $status = 'warning';
        }
        
        $results[$dir] = [
            'path' => $path,
            'description' => $desc,
            'exists' => $exists,
            'writable' => $writable,
            'status' => $status
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
        'templates/header.php' => 'Header global',
        'templates/footer.php' => 'Footer global',
        'public/index.php' => 'Point entrée',
        'public/.htaccess' => 'Réécriture URLs',
        'public/admin/index.php' => 'Dashboard admin'
    ];
    
    $results = [];
    foreach ($critical_files as $file => $desc) {
        $path = ROOT_PATH . '/' . $file;
        $exists = file_exists($path);
        $readable = $exists ? is_readable($path) : false;
        $size = $exists ? filesize($path) : 0;
        
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
            'status' => $status
        ];
    }
    
    return $results;
}

/**
 * Scan configuration
 */
function scanConfiguration() {
    $results = [];
    
    // Test chargement config
    try {
        require_once ROOT_PATH . '/config/config.php';
        $results['config_loaded'] = [
            'status' => 'ok',
            'message' => 'Configuration chargée'
        ];
    } catch (Exception $e) {
        $results['config_loaded'] = [
            'status' => 'error',
            'message' => 'Erreur config: ' . $e->getMessage()
        ];
        return $results;
    }
    
    // Test constantes
    $required_constants = ['ROOT_PATH', 'DB_HOST', 'DB_NAME', 'DB_USER'];
    foreach ($required_constants as $const) {
        $results['constants'][$const] = [
            'defined' => defined($const),
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
        'templates/assets/css/header.css' => 'CSS header',
        'public/admin/assets/css/admin.css' => 'CSS admin'
    ];
    
    $results = [];
    foreach ($assets as $file => $desc) {
        $path = ROOT_PATH . '/' . $file;
        $exists = file_exists($path);
        $size = $exists ? filesize($path) : 0;
        
        $status = 'error';
        if ($exists && $size > 0) {
            $status = 'ok';
        } elseif ($exists) {
            $status = 'warning';
        }
        
        $results[$file] = [
            'description' => $desc,
            'exists' => $exists,
            'size' => $size,
            'status' => $status
        ];
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
        'config'
    ];
    
    $results = [];
    foreach ($check_paths as $path) {
        $full_path = ROOT_PATH . '/' . $path;
        if (file_exists($full_path)) {
            $writable = is_writable($full_path);
            
            $results[$path] = [
                'path' => $full_path,
                'writable' => $writable,
                'status' => $writable ? 'ok' : 'warning'
            ];
        }
    }
    
    return $results;
}

/**
 * Scan erreurs syntaxe (mode deep)
 */
function scanSyntaxErrors() {
    $results = [];
    
    // Test quelques fichiers PHP critiques
    $test_files = [
        ROOT_PATH . '/public/index.php',
        ROOT_PATH . '/public/admin/index.php',
        ROOT_PATH . '/config/config.php'
    ];
    
    foreach ($test_files as $file) {
        if (file_exists($file)) {
            $relative = str_replace(ROOT_PATH . '/', '', $file);
            
            // Test syntaxe basique
            $output = '';
            $return_var = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
            
            $results[$relative] = [
                'file' => $relative,
                'status' => $return_var === 0 ? 'ok' : 'error',
                'error' => $return_var !== 0 ? implode(' ', $output) : null
            ];
        }
    }
    
    return $results;
}

/**
 * Scan modules (mode deep)
 */
function scanModules() {
    $modules = ['admin', 'auth', 'user'];
    $results = [];
    
    foreach ($modules as $module) {
        $module_path = ROOT_PATH . "/public/$module";
        $index_file = "$module_path/index.php";
        
        $results[$module] = [
            'exists' => is_dir($module_path),
            'index_exists' => file_exists($index_file),
            'status' => (is_dir($module_path) && file_exists($index_file)) ? 'ok' : 'warning'
        ];
    }
    
    return $results;
}

/**
 * Scan BDD (mode deep)
 */
function scanDatabase() {
    $results = [];
    
    try {
        if (!defined('DB_HOST')) {
            $results['connection'] = [
                'status' => 'error',
                'error' => 'Configuration BDD manquante'
            ];
            return $results;
        }
        
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $results['connection'] = ['status' => 'ok'];
        
        // Test tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $required_tables = ['auth_users', 'auth_sessions'];
        
        foreach ($required_tables as $table) {
            $exists = in_array($table, $tables);
            $results['tables'][$table] = [
                'exists' => $exists,
                'status' => $exists ? 'ok' : 'error'
            ];
        }
        
    } catch (Exception $e) {
        $results['connection'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }
    
    return $results;
}

// Exécuter le scan
$scan_results = [];
$scan_duration = 0;
if ($action === 'scan') {
    $start_time = microtime(true);
    $scan_results = scanPortal($scan_deep);
    $scan_duration = round((microtime(true) - $start_time) * 1000, 2);
}

// Calculer statistiques
$total_checks = 0;
$total_errors = 0;
$total_warnings = 0;
$total_ok = 0;

if (!empty($scan_results)) {
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
                } elseif (is_array($item)) {
                    // Pour les sous-sections comme 'constants' ou 'tables'
                    foreach ($item as $subitem) {
                        if (isset($subitem['status'])) {
                            $total_checks++;
                            switch ($subitem['status']) {
                                case 'error': $total_errors++; break;
                                case 'warning': $total_warnings++; break;
                                case 'ok': $total_ok++; break;
                            }
                        }
                    }
                }
            }
        }
    }
}

// Charger header si possible
$header_loaded = false;
try {
    if (file_exists(ROOT_PATH . '/templates/header.php')) {
        include ROOT_PATH . '/templates/header.php';
        $header_loaded = true;
    }
} catch (Exception $e) {
    // Header minimal de secours
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">';
    echo '<title>Scanner d\'erreurs - Admin</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body>';
    echo '<header style="background:#2c3e50;color:white;padding:1rem;margin-bottom:2rem;">';
    echo '<h1>🔍 Scanner d\'erreurs</h1></header>';
}
?>

<style>
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
}

.btn-help:hover {
    background: #7f8c8d;
}

.scan-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-scan {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
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

.scan-info {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 5px;
    border-left: 4px solid #3498db;
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

.file-path {
    font-family: monospace;
    font-size: 0.85rem;
    color: #7f8c8d;
}

.error-details {
    background: #ecf0f1;
    padding: 0.5rem;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.8rem;
    margin-top: 0.5rem;
}

.quick-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.quick-actions .btn-scan {
    background: #95a5a6;
    font-size: 0.9rem;
    padding: 0.6rem 1.2rem;
}

.quick-actions .btn-scan:hover {
    background: #7f8c8d;
}

@media (max-width: 768px) {
    .scanner-container {
        padding: 1rem;
    }
    
    .results-container {
        grid-template-columns: 1fr;
    }
    
    .scan-form, .summary-stats, .quick-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="scanner-container">
    <div class="scan-controls">
        <div class="scan-header">
            <h2>🔍 Scanner d'erreurs du portail</h2>
            <button type="button" class="btn-help" onclick="showHelp()">
                📖 Guide
            </button>
        </div>
        
        <form method="post" class="scan-form">
            <input type="hidden" name="action" value="scan">
            
            <div class="checkbox-group">
                <input type="checkbox" id="deep_scan" name="deep_scan" <?= $scan_deep ? 'checked' : '' ?>>
                <label for="deep_scan">Scan approfondi</label>
            </div>
            
            <button type="submit" class="btn-scan <?= $scan_deep ? 'deep' : '' ?>">
                <?= $scan_deep ? '🔬 Scanner en profondeur' : '⚡ Scanner rapide' ?>
            </button>
        </form>
        
        <div class="scan-info">
            <small>
                <strong>⚡ Rapide :</strong> Structure, fichiers, config (~2-5s) • 
                <strong>🔬 Approfondi :</strong> + Syntaxe, modules, BDD (~10-30s)
            </small>
        </div>
    </div>

    <?php if (!empty($scan_results)): ?>
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
                    <span>Total</span>
                </div>
            </div>
        </div>

        <div class="results-container">
            <!-- Structure -->
            <?php if (isset($scan_results['structure'])): ?>
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
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['exists'] ? '✅' : '❌' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Fichiers critiques -->
            <?php if (isset($scan_results['files'])): ?>
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
                                    <small><?= number_format($info['size']) ?> bytes</small>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['exists'] ? ($info['readable'] ? '✅' : '⚠️') : '❌' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Configuration -->
            <?php if (isset($scan_results['config'])): ?>
            <div class="result-section">
                <div class="result-header">
                    <h3>⚙️ Configuration</h3>
                </div>
                <div class="result-content">
                    <!-- Config loaded -->
                    <?php if (isset($scan_results['config']['config_loaded'])): ?>
                        <div class="status-item <?= $scan_results['config']['config_loaded']['status'] ?>">
                            <div>
                                <strong>Configuration</strong>
                                <div class="file-path"><?= htmlspecialchars($scan_results['config']['config_loaded']['message']) ?></div>
                            </div>
                            <span class="status-badge <?= $scan_results['config']['config_loaded']['status'] ?>">
                                <?= $scan_results['config']['config_loaded']['status'] === 'ok' ? '✅' : '❌' ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Constantes -->
                    <?php if (isset($scan_results['config']['constants'])): ?>
                        <?php foreach ($scan_results['config']['constants'] as $const => $info): ?>
                            <div class="status-item <?= $info['status'] ?>">
                                <div>
                                    <strong><?= $const ?></strong>
                                </div>
                                <span class="status-badge <?= $info['status'] ?>">
                                    <?= $info['defined'] ? '✅' : '❌' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Assets -->
            <?php if (isset($scan_results['assets'])): ?>
            <div class="result-section">
                <div class="result-header">
                    <h3>🎨 Assets CSS/JS</h3>
                </div>
                <div class="result-content">
                    <?php foreach ($scan_results['assets'] as $file => $info): ?>
                        <div class="status-item <?= $info['status'] ?>">
                            <div>
                                <strong><?= basename($file) ?></strong>
                                <div class="file-path"><?= htmlspecialchars($info['description']) ?></div>
                                <?php if ($info['exists']): ?>
                                    <small><?= number_format($info['size']) ?> bytes</small>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['exists'] ? '✅' : '❌' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Syntaxe (deep scan) -->
            <?php if (isset($scan_results['syntax'])): ?>
            <div class="result-section">
                <div class="result-header">
                    <h3>🐛 Erreurs de syntaxe</h3>
                </div>
                <div class="result-content">
                    <?php foreach ($scan_results['syntax'] as $file => $info): ?>
                        <div class="status-item <?= $info['status'] ?>">
                            <div>
                                <strong><?= htmlspecialchars($file) ?></strong>
                                <?php if ($info['error']): ?>
                                    <div class="error-details"><?= htmlspecialchars($info['error']) ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['status'] === 'ok' ? '✅' : '❌' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Modules (deep scan) -->
            <?php if (isset($scan_results['modules'])): ?>
            <div class="result-section">
                <div class="result-header">
                    <h3>🔧 Modules</h3>
                </div>
                <div class="result-content">
                    <?php foreach ($scan_results['modules'] as $module => $info): ?>
                        <div class="status-item <?= $info['status'] ?>">
                            <div>
                                <strong><?= $module ?></strong>
                                <div class="file-path">
                                    Dossier: <?= $info['exists'] ? 'Oui' : 'Non' ?> |
                                    Index: <?= $info['index_exists'] ? 'Oui' : 'Non' ?>
                                </div>
                            </div>
                            <span class="status-badge <?= $info['status'] ?>">
                                <?= $info['status'] === 'ok' ? '✅' : '⚠️' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Base de données (deep scan) -->
            <?php if (isset($scan_results['database'])): ?>
            <div class="result-section">
                <div class="result-header">
                    <h3>🗄️ Base de données</h3>
                </div>
                <div class="result-content">
                    <!-- Connexion -->
                    <div class="status-item <?= $scan_results['database']['connection']['status'] ?>">
                        <div>
                            <strong>Connexion</strong>
                            <?php if (isset($scan_results['database']['connection']['error'])): ?>
                                <div class="error-details"><?= htmlspecialchars($scan_results['database']['connection']['error']) ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="status-badge <?= $scan_results['database']['connection']['status'] ?>">
                            <?= $scan_results['database']['connection']['status'] === 'ok' ? '✅' : '❌' ?>
                        </span>
                    </div>
                    
                    <!-- Tables -->
                    <?php if (isset($scan_results['database']['tables'])): ?>
                        <?php foreach ($scan_results['database']['tables'] as $table => $info): ?>
                            <div class="status-item <?= $info['status'] ?>">
                                <div>
                                    <strong><?= $table ?></strong>
                                </div>
                                <span class="status-badge <?= $info['status'] ?>">
                                    <?= $info['exists'] ? '✅' : '❌' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions rapides -->
        <div class="result-section" style="grid-column: 1 / -1; margin-top: 2rem;">
            <div class="result-header">
                <h3>⚡ Actions rapides</h3>
            </div>
            <div class="result-content">
                <div class="quick-actions">
                    <button type="button" class="btn-scan" onclick="window.location.reload()">
                        🔄 Relancer le scan
                    </button>
                    
                    <button type="button" class="btn-scan" onclick="downloadReport()">
                        📥 Télécharger rapport
                    </button>
                    
                    <button type="button" class="btn-scan" onclick="window.open('/admin/logs.php', '_blank')">
                        📊 Voir les logs
                    </button>
                    
                    <button type="button" class="btn-scan" onclick="clearCache()">
                        🗑️ Vider le cache
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="result-section">
            <div class="result-header">
                <h3>ℹ️ Information</h3>
            </div>
            <div class="result-content">
                <p>Cliquez sur "Scanner" pour démarrer l'analyse du portail.</p>
                <p><strong>Scanner rapide :</strong> Vérifie les éléments essentiels (structure, fichiers critiques, configuration)</p>
                <p><strong>Scanner approfondi :</strong> Inclut l'analyse de la syntaxe, modules et base de données</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function showHelp() {
    const helpContent = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; overflow-y: auto;" onclick="closeHelp()">
            <div style="background: white; max-width: 800px; margin: 2rem auto; padding: 2rem; border-radius: 8px; position: relative;" onclick="event.stopPropagation()">
                <button onclick="closeHelp()" style="position: absolute; top: 1rem; right: 1rem; background: #e74c3c; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1.2rem;">×</button>
                
                <h2>🔍 Guide du Scanner d'erreurs</h2>
                
                <h3>🎯 Types de scan</h3>
                <ul>
                    <li><strong>⚡ Rapide (2-5s)</strong> : Structure, fichiers critiques, configuration</li>
                    <li><strong>🔬 Approfondi (10-30s)</strong> : + Syntaxe PHP, modules, base de données</li>
                </ul>
                
                <h3>📊 Sections analysées</h3>
                <ul>
                    <li><strong>📁 Structure</strong> : Dossiers requis et permissions</li>
                    <li><strong>📄 Fichiers critiques</strong> : config.php, header.php, .htaccess...</li>
                    <li><strong>⚙️ Configuration</strong> : Constantes et chargement config</li>
                    <li><strong>🎨 Assets CSS/JS</strong> : Existence et taille des fichiers</li>
                    <li><strong>🐛 Syntaxe PHP</strong> : Erreurs de parsing (mode approfondi)</li>
                    <li><strong>🔧 Modules</strong> : État des modules installés (mode approfondi)</li>
                    <li><strong>🗄️ Base de données</strong> : Connexion et tables (mode approfondi)</li>
                </ul>
                
                <h3>🎨 Codes couleur</h3>
                <ul>
                    <li><span style="color: #27ae60;">🟢 Vert</span> : Tout fonctionne correctement</li>
                    <li><span style="color: #f39c12;">🟡 Orange</span> : Avertissement, à surveiller</li>
                    <li><span style="color: #e74c3c;">🔴 Rouge</span> : Erreur, action requise</li>
                </ul>
                
                <h3>🛠️ Actions rapides</h3>
                <ul>
                    <li><strong>📥 Télécharger rapport</strong> : Export JSON complet</li>
                    <li><strong>📊 Voir logs</strong> : Accès aux logs système</li>
                    <li><strong>🗑️ Vider cache</strong> : Nettoyage cache modules</li>
                </ul>
                
                <h3>🚨 Problèmes courants</h3>
                <ul>
                    <li><strong>Config manquant</strong> : Vérifier ROOT_PATH et /config/config.php</li>
                    <li><strong>Fichiers manquants</strong> : Créer les fichiers critiques indiqués</li>
                    <li><strong>Permissions</strong> : chmod 755 (dossiers), 644 (fichiers)</li>
                    <li><strong>BDD inaccessible</strong> : Contrôler DB_HOST, DB_USER, DB_PASS</li>
                </ul>
                
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-top: 1rem;">
                    <strong>💡 Astuce :</strong> Lancez toujours un scan rapide après une modification,
                    et un scan approfondi avant mise en production !
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', helpContent);
    
    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeHelp();
    });
}

function closeHelp() {
    const modal = document.querySelector('[onclick="closeHelp()"]').closest('div');
    if (modal) {
        modal.remove();
    }
}

function downloadReport() {
    const report = {
        timestamp: new Date().toISOString(),
        scan_type: <?= json_encode($scan_deep ? 'deep' : 'quick') ?>,
        duration: <?= json_encode($scan_duration) ?>,
        summary: {
            total_checks: <?= json_encode($total_checks) ?>,
            errors: <?= json_encode($total_errors) ?>,
            warnings: <?= json_encode($total_warnings) ?>,
            ok: <?= json_encode($total_ok) ?>
        },
        results: <?= json_encode($scan_results) ?>
    };
    
    const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `portail-scan-report-${new Date().toISOString().slice(0, 10)}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function clearCache() {
    if (confirm('Êtes-vous sûr de vouloir vider le cache ?')) {
        // Simulation - à adapter selon votre système de cache
        alert('Fonction de cache à implémenter selon votre système');
    }
}
</script>

<?php
// Charger footer si possible
try {
    if ($header_loaded && file_exists(ROOT_PATH . '/templates/footer.php')) {
        include ROOT_PATH . '/templates/footer.php';
    } else {
        echo '</body></html>';
    }
} catch (Exception $e) {
    echo '</body></html>';
}
?>

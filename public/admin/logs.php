<?php
/**
 * Titre: Visualiseur de logs - Administration O2switch
 * Chemin: /public/admin/logs.php
 * Version: 0.5 beta + build auto
 */

// Sécurité et configuration
session_start();
define('ROOT_PATH', dirname(__DIR__, 2));

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

// Configuration spécifique O2switch
$log_configs = [
    'php_error' => [
        'name' => 'PHP Error Log',
        'icon' => '🐘',
        'paths' => [
            // Ordre de priorité pour O2switch
            ROOT_PATH . '/storage/logs/php_errors.log',
            ROOT_PATH . '/logs/php_errors.log',
            ini_get('error_log'),
            '/tmp/php_errors.log'
        ],
        'color' => '#dc2626',
        'priority' => 1
    ],
    'app_error' => [
        'name' => 'App Error Log',
        'icon' => '🚨',
        'paths' => [
            ROOT_PATH . '/storage/logs/error.log',
            ROOT_PATH . '/storage/logs/app.log',
            ROOT_PATH . '/logs/error.log'
        ],
        'color' => '#ea580c',
        'priority' => 2
    ],
    'access_log' => [
        'name' => 'Access Log',
        'icon' => '📝',
        'paths' => [
            ROOT_PATH . '/storage/logs/access.log',
            ROOT_PATH . '/logs/access.log'
        ],
        'color' => '#059669',
        'priority' => 3
    ],
    'system_log' => [
        'name' => 'System Log',
        'icon' => '⚙️',
        'paths' => [
            ROOT_PATH . '/storage/logs/system.log',
            ROOT_PATH . '/logs/system.log'
        ],
        'color' => '#7c3aed',
        'priority' => 4
    ]
];

// Paramètres
$selected_log = $_GET['log'] ?? 'php_error';
$lines_count = (int)($_GET['lines'] ?? 100);
$lines_count = max(10, min(500, $lines_count)); // Limite adaptée O2switch
$search_term = trim($_GET['search'] ?? '');
$auto_refresh = isset($_GET['auto_refresh']);

// Variables pour template
$page_title = 'Visualiseur de Logs';
$page_subtitle = 'Surveillance système O2switch';
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
        if ($path && file_exists($path) && is_readable($path) && filesize($path) > 0) {
            return $path;
        }
    }
    return null;
}

/**
 * Lit les dernières lignes d'un fichier avec optimisation O2switch
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
        
        // Lecture optimisée pour O2switch (limite de ressources)
        $max_file_size = 10 * 1024 * 1024; // 10MB max
        if ($file_size > $max_file_size) {
            // Lire seulement la fin du fichier pour les gros logs
            $handle = fopen($file_path, 'r');
            fseek($handle, -$max_file_size, SEEK_END);
            $content = fread($handle, $max_file_size);
            fclose($handle);
            $lines_array = explode("\n", $content);
        } else {
            $lines_array = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (empty($lines_array)) {
            return ['lines' => [], 'total_size' => $file_size];
        }
        
        // Prendre les dernières lignes
        $last_lines = array_slice($lines_array, -$lines_count);
        $processed_lines = [];
        
        foreach (array_reverse($last_lines) as $index => $line) {
            if (empty($search_term) || stripos($line, $search_term) !== false) {
                $processed_lines[] = [
                    'number' => count($last_lines) - $index,
                    'content' => $line,
                    'timestamp' => extractTimestamp($line),
                    'level' => extractLogLevel($line)
                ];
            }
        }
        
        return [
            'lines' => $processed_lines,
            'total_size' => $file_size,
            'total_lines' => count($lines_array)
        ];
        
    } catch (Exception $e) {
        return ['error' => 'Erreur lecture: ' . $e->getMessage()];
    }
}

/**
 * Extrait le timestamp d'une ligne de log
 */
function extractTimestamp($line) {
    // Formats communs de timestamp
    $patterns = [
        '/\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2})/',  // [18-Jul-2025 10:30:45]
        '/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',   // 2025-07-18 10:30:45
        '/\[(\d{2}\/\w{3}\/\d{4}:\d{2}:\d{2}:\d{2})/', // [18/Jul/2025:10:30:45]
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $line, $matches)) {
            return $matches[1];
        }
    }
    
    return 'N/A';
}

/**
 * Détermine le niveau de log
 */
function extractLogLevel($line) {
    $line_lower = strtolower($line);
    
    if (strpos($line_lower, 'fatal') !== false || strpos($line_lower, 'emergency') !== false) {
        return 'fatal';
    } elseif (strpos($line_lower, 'error') !== false) {
        return 'error';
    } elseif (strpos($line_lower, 'warning') !== false || strpos($line_lower, 'warn') !== false) {
        return 'warning';
    } elseif (strpos($line_lower, 'info') !== false) {
        return 'info';
    } elseif (strpos($line_lower, 'debug') !== false) {
        return 'debug';
    }
    
    return 'unknown';
}

// Traitement des données
$selected_config = $log_configs[$selected_log] ?? $log_configs['php_error'];
$log_file_path = findLogFile($selected_config['paths']);
$log_data = null;
$available_logs = [];

// Vérification des logs disponibles
foreach ($log_configs as $key => $config) {
    $file_path = findLogFile($config['paths']);
    if ($file_path) {
        $available_logs[$key] = [
            'config' => $config,
            'file_path' => $file_path,
            'size' => filesize($file_path),
            'modified' => filemtime($file_path)
        ];
    }
}

// Lecture du log sélectionné
if ($log_file_path) {
    $log_data = readLogLines($log_file_path, $lines_count, $search_term);
}

// Chargement header avec template
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Header minimal si template manquant
    echo '<!DOCTYPE html><html><head><title>Logs Admin</title>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5}</style></head><body>';
}
?>

<style>
.logs-container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.logs-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
}

.logs-controls {
    background: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
}

.control-group {
    display: inline-block;
    margin-right: 20px;
    margin-bottom: 10px;
}

.control-group label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-warning { background: #ffc107; color: #212529; }

.log-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    padding: 15px;
    background: #e9ecef;
    border-bottom: 1px solid #dee2e6;
}

.info-item {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.log-viewer {
    height: 600px;
    overflow-y: auto;
    background: #1e1e1e;
    color: #f8f8f2;
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.log-line {
    padding: 8px 15px;
    border-bottom: 1px solid #333;
    white-space: pre-wrap;
    word-break: break-word;
}

.log-line:hover {
    background: #2d2d2d;
}

.log-line.error { border-left: 4px solid #dc3545; }
.log-line.warning { border-left: 4px solid #ffc107; }
.log-line.info { border-left: 4px solid #17a2b8; }
.log-line.debug { border-left: 4px solid #6c757d; }
.log-line.fatal { border-left: 4px solid #8b0000; background: #2d1b1b; }

.line-number {
    color: #6c757d;
    margin-right: 10px;
    min-width: 50px;
    display: inline-block;
}

.timestamp {
    color: #28a745;
    margin-right: 10px;
}

.no-logs {
    text-align: center;
    padding: 50px;
    color: #666;
}

.available-logs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.log-card {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.log-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
}

.log-card.active {
    border-color: #007bff;
    background: #f8f9ff;
}

.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}

.status-available { background: #28a745; }
.status-unavailable { background: #dc3545; }

@media (max-width: 768px) {
    .logs-controls {
        text-align: center;
    }
    
    .control-group {
        display: block;
        margin-bottom: 15px;
    }
    
    .log-info {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="logs-container">
    <div class="logs-header">
        <h1><?= $selected_config['icon'] ?> <?= htmlspecialchars($selected_config['name']) ?></h1>
        <p>Surveillance des logs système - Hébergement O2switch</p>
    </div>

    <!-- Logs disponibles -->
    <div style="padding: 20px;">
        <h3>📋 Logs disponibles</h3>
        <div class="available-logs">
            <?php foreach ($log_configs as $key => $config): ?>
                <?php 
                $is_available = isset($available_logs[$key]);
                $is_selected = ($key === $selected_log);
                ?>
                <div class="log-card <?= $is_selected ? 'active' : '' ?>" 
                     onclick="window.location.href='?log=<?= $key ?>&lines=<?= $lines_count ?>&search=<?= urlencode($search_term) ?>'">
                    <div>
                        <span class="status-indicator <?= $is_available ? 'status-available' : 'status-unavailable' ?>"></span>
                        <?= $config['icon'] ?> <?= htmlspecialchars($config['name']) ?>
                    </div>
                    <?php if ($is_available): ?>
                        <small><?= number_format($available_logs[$key]['size'] / 1024, 1) ?> KB</small>
                    <?php else: ?>
                        <small style="color: #999;">Non trouvé</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Contrôles -->
    <form method="GET" class="logs-controls">
        <input type="hidden" name="log" value="<?= htmlspecialchars($selected_log) ?>">
        
        <div class="control-group">
            <label>Nombre de lignes</label>
            <select name="lines" class="form-control">
                <option value="50" <?= $lines_count == 50 ? 'selected' : '' ?>>50 lignes</option>
                <option value="100" <?= $lines_count == 100 ? 'selected' : '' ?>>100 lignes</option>
                <option value="200" <?= $lines_count == 200 ? 'selected' : '' ?>>200 lignes</option>
                <option value="500" <?= $lines_count == 500 ? 'selected' : '' ?>>500 lignes</option>
            </select>
        </div>

        <div class="control-group">
            <label>Rechercher</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>" 
                   placeholder="Terme à rechercher..." class="form-control">
        </div>

        <div class="control-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
        </div>

        <div class="control-group">
            <label>&nbsp;</label>
            <a href="?log=<?= $selected_log ?>&lines=<?= $lines_count ?>" class="btn btn-warning">🔄 Actualiser</a>
        </div>
    </form>

    <?php if ($log_file_path && $log_data && !isset($log_data['error'])): ?>
        <!-- Informations du fichier -->
        <div class="log-info">
            <div class="info-item">
                <span>Fichier:</span>
                <span><?= htmlspecialchars($log_file_path) ?></span>
            </div>
            <div class="info-item">
                <span>Taille:</span>
                <span><?= number_format($log_data['total_size'] / 1024, 2) ?> KB</span>
            </div>
            <div class="info-item">
                <span>Lignes totales:</span>
                <span><?= number_format($log_data['total_lines'] ?? 0) ?></span>
            </div>
            <div class="info-item">
                <span>Affichées:</span>
                <span><?= count($log_data['lines']) ?> lignes</span>
            </div>
            <div class="info-item">
                <span>Modifié:</span>
                <span><?= date('d/m/Y H:i:s', filemtime($log_file_path)) ?></span>
            </div>
            <?php if ($search_term): ?>
            <div class="info-item">
                <span>Recherche:</span>
                <span>"<?= htmlspecialchars($search_term) ?>"</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Visualiseur de logs -->
        <div class="log-viewer" id="logViewer">
            <?php if (empty($log_data['lines'])): ?>
                <div class="no-logs">
                    <?php if ($search_term): ?>
                        <p>🔍 Aucune ligne contenant "<?= htmlspecialchars($search_term) ?>" trouvée</p>
                        <a href="?log=<?= $selected_log ?>&lines=<?= $lines_count ?>" class="btn btn-primary">Voir tous les logs</a>
                    <?php else: ?>
                        <p>📝 Aucune ligne de log disponible</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($log_data['lines'] as $line): ?>
                    <div class="log-line <?= $line['level'] ?>">
                        <span class="line-number"><?= $line['number'] ?></span>
                        <?php if ($line['timestamp'] !== 'N/A'): ?>
                            <span class="timestamp">[<?= htmlspecialchars($line['timestamp']) ?>]</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($line['content']) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif (isset($log_data['error'])): ?>
        <div class="no-logs">
            <p>❌ <?= htmlspecialchars($log_data['error']) ?></p>
            <p><strong>Fichier recherché :</strong> <?= htmlspecialchars($log_file_path ?? 'Aucun') ?></p>
        </div>

    <?php else: ?>
        <div class="no-logs">
            <p>📂 Aucun fichier de log trouvé pour "<?= htmlspecialchars($selected_config['name']) ?>"</p>
            <p><strong>Chemins recherchés :</strong></p>
            <ul style="text-align: left; display: inline-block;">
                <?php foreach ($selected_config['paths'] as $path): ?>
                    <li><code><?= htmlspecialchars($path) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php if ($auto_refresh): ?>
<script>
setTimeout(function() {
    window.location.reload();
}, 30000); // Actualisation toutes les 30 secondes
</script>
<?php endif; ?>

<script>
// Auto-scroll vers le bas
document.addEventListener('DOMContentLoaded', function() {
    const logViewer = document.getElementById('logViewer');
    if (logViewer) {
        logViewer.scrollTop = logViewer.scrollHeight;
    }
});

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        window.location.reload();
    }
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.querySelector('input[name="search"]').focus();
    }
});
</script>

<?php
// Chargement footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    echo '</body></html>';
}
?>

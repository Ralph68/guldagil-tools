<?php
/**
 * Titre: Module Sécurité Administration - PRODUCTION READY
 * Chemin: /public/admin/security.php
 * Version: 0.5 beta + build auto
 */

// Configuration sécurisée
define('ROOT_PATH', dirname(dirname(__DIR__)));
session_start();

// Chargement obligatoire
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Fonction de toggle debug sécurisée
function setDebugMode(bool $enabled, int $duration_hours = 24): bool {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        return false;
    }
    
    $debug_file = ROOT_PATH . '/storage/cache/debug_mode.json';
    $cache_dir = dirname($debug_file);
    
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }
    
    if ($enabled) {
        $data = [
            'enabled' => true,
            'enabled_by' => $_SESSION['username'] ?? 'admin',
            'enabled_at' => time(),
            'expires' => time() + ($duration_hours * 3600),
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        file_put_contents($debug_file, json_encode($data, JSON_PRETTY_PRINT));
        error_log("DEBUG MODE ACTIVÉ par " . $data['enabled_by'] . " IP: " . $data['user_ip']);
        
    } else {
        @unlink($debug_file);
        error_log("DEBUG MODE DÉSACTIVÉ par " . ($_SESSION['username'] ?? 'admin'));
    }
    
    return true;
}

// Authentification stricte admin
$user_authenticated = false;
$current_user = null;

try {
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = new AuthManager();
        
        if ($auth->isAuthenticated()) {
            $current_user = $auth->getCurrentUser();
            $user_authenticated = $current_user['role'] === 'admin';
        }
    }
} catch (Exception $e) {
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
        $user_authenticated = true;
        $current_user = $_SESSION['user'];
    }
}

if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Variables pour template
$page_title = 'Sécurité Système';
$current_module = 'admin';
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'toggle_debug':
            $enable = isset($_POST['enable_debug']);
            $duration = (int)($_POST['debug_duration'] ?? 24);
            
            if (setDebugMode($enable, $duration)) {
                $message = $enable ? 
                    "Mode debug activé pour {$duration}h" : 
                    "Mode debug désactivé";
            } else {
                $error = "Erreur lors du changement de mode debug";
            }
            break;
            
        case 'security_scan':
            // Lancer scan sécurité complet
            header('Location: scanner.php?type=security');
            exit;
            break;
    }
}

// État actuel du debug
$debug_file = ROOT_PATH . '/storage/cache/debug_mode.json';
$debug_status = [
    'enabled' => false,
    'enabled_by' => null,
    'enabled_at' => null,
    'expires' => null
];

if (file_exists($debug_file)) {
    $debug_data = json_decode(file_get_contents($debug_file), true);
    if ($debug_data && time() < ($debug_data['expires'] ?? 0)) {
        $debug_status = $debug_data;
        $debug_status['enabled'] = true;
    } else {
        @unlink($debug_file);
    }
}

// Audit sécurité
function performSecurityAudit(): array {
    $results = [];
    
    // 1. Fichiers sensibles
    $sensitive_files = [
        '/config/config.php' => 'Configuration principale',
        '/config/database.php' => 'Configuration BDD',
        '/.env' => 'Variables environnement',
        '/.git' => 'Dépôt Git',
        '/composer.json' => 'Dépendances',
        '/storage/logs' => 'Logs système'
    ];
    
    foreach ($sensitive_files as $file => $desc) {
        $full_path = ROOT_PATH . $file;
        if (file_exists($full_path)) {
            $perms = substr(sprintf('%o', fileperms($full_path)), -4);
            $readable_web = (fileperms($full_path) & 0004);
            
            $results['files'][] = [
                'file' => $file,
                'description' => $desc,
                'permissions' => $perms,
                'web_readable' => $readable_web,
                'status' => $readable_web ? 'danger' : 'ok'
            ];
        }
    }
    
    // 2. Configuration PHP
    $php_config = [
        'display_errors' => ini_get('display_errors'),
        'expose_php' => ini_get('expose_php'),
        'allow_url_fopen' => ini_get('allow_url_fopen'),
        'allow_url_include' => ini_get('allow_url_include'),
        'session.cookie_secure' => ini_get('session.cookie_secure'),
        'session.cookie_httponly' => ini_get('session.cookie_httponly')
    ];
    
    foreach ($php_config as $setting => $value) {
        $is_secure = match($setting) {
            'display_errors' => !$value,
            'expose_php' => !$value,
            'allow_url_include' => !$value,
            'session.cookie_secure' => $value,
            'session.cookie_httponly' => $value,
            default => true
        };
        
        $results['php'][] = [
            'setting' => $setting,
            'value' => $value ? 'ON' : 'OFF',
            'status' => $is_secure ? 'ok' : 'warning'
        ];
    }
    
    // 3. Base de données
    try {
        if (defined('DB_DSN')) {
            $db = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            // Vérifier tables auth
            $stmt = $db->query("SHOW TABLES LIKE 'auth_%'");
            $auth_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $results['database'] = [
                'connection' => 'ok',
                'auth_tables' => count($auth_tables),
                'tables_found' => $auth_tables
            ];
            
            // Comptes admin
            if (in_array('auth_users', $auth_tables)) {
                $stmt = $db->query("SELECT COUNT(*) FROM auth_users WHERE role = 'admin'");
                $admin_count = $stmt->fetchColumn();
                
                $results['database']['admin_accounts'] = $admin_count;
                $results['database']['admin_status'] = $admin_count > 0 ? 'ok' : 'warning';
            }
        }
    } catch (Exception $e) {
        $results['database'] = [
            'connection' => 'error',
            'error' => $e->getMessage()
        ];
    }
    
    // 4. Logs sécurité
    $log_files = [
        '/storage/logs/auth.log',
        '/storage/logs/error.log',
        '/storage/logs/debug.log'
    ];
    
    foreach ($log_files as $log) {
        $full_path = ROOT_PATH . $log;
        if (file_exists($full_path)) {
            $size = filesize($full_path);
            $results['logs'][] = [
                'file' => basename($log),
                'size' => $size,
                'size_human' => formatBytes($size),
                'writable' => is_writable($full_path)
            ];
        }
    }
    
    return $results;
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

$security_audit = performSecurityAudit();

// Header inclus
include ROOT_PATH . '/templates/header.php';
?>

<link rel="stylesheet" href="assets/css/admin.css">

<main class="admin-security-container">
    <div class="admin-section">
        <h1>🔒 Sécurité Système</h1>
        <p>Gestion sécurisée du système et audit complet</p>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Toggle Debug Mode -->
    <div class="security-section">
        <h2>🔧 Mode Debug</h2>
        <div class="debug-controls">
            <?php if ($debug_status['enabled']): ?>
                <div class="debug-active">
                    <h3>⚠️ Mode Debug ACTIF</h3>
                    <div class="debug-info">
                        <strong>Activé par:</strong> <?= htmlspecialchars($debug_status['enabled_by']) ?><br>
                        <strong>Activé le:</strong> <?= date('d/m/Y H:i:s', $debug_status['enabled_at']) ?><br>
                        <strong>Expire le:</strong> <?= date('d/m/Y H:i:s', $debug_status['expires']) ?><br>
                        <strong>Reste:</strong> 
                        <?php
                        $remaining = $debug_status['expires'] - time();
                        echo floor($remaining / 3600) . 'h ' . floor(($remaining % 3600) / 60) . 'min';
                        ?>
                    </div>
                    <form method="post" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="toggle_debug">
                        <button type="submit" class="btn btn-danger">🔴 Désactiver le Debug</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="debug-controls-form">
                    <h3>Debug Mode désactivé</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="toggle_debug">
                        <input type="hidden" name="enable_debug" value="1">
                        
                        <div class="form-group">
                            <label for="debug_duration">Durée d'activation (heures):</label>
                            <select name="debug_duration" id="debug_duration">
                                <option value="1">1 heure</option>
                                <option value="4">4 heures</option>
                                <option value="8">8 heures</option>
                                <option value="24" selected>24 heures</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">⚠️ Activer Debug Temporaire</button>
                    </form>
                    <p class="warning-text">
                        ⚠️ Le mode debug expose des informations sensibles. 
                        À utiliser uniquement pour le développement.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audit Sécurité -->
    <div class="security-section">
        <h2>🛡️ Audit Sécurité</h2>
        
        <!-- Fichiers sensibles -->
        <div class="audit-category">
            <h3>📁 Fichiers Sensibles</h3>
            <div class="audit-grid">
                <?php foreach ($security_audit['files'] ?? [] as $file): ?>
                    <div class="audit-item status-<?= $file['status'] ?>">
                        <div class="audit-header">
                            <strong><?= htmlspecialchars($file['file']) ?></strong>
                            <span class="status-badge"><?= $file['status'] === 'ok' ? '✅' : '⚠️' ?></span>
                        </div>
                        <div class="audit-details">
                            <small><?= htmlspecialchars($file['description']) ?></small><br>
                            <code>Permissions: <?= $file['permissions'] ?></code><br>
                            <span class="<?= $file['web_readable'] ? 'text-danger' : 'text-success' ?>">
                                <?= $file['web_readable'] ? 'Lisible par le web' : 'Protégé' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Configuration PHP -->
        <div class="audit-category">
            <h3>🐘 Configuration PHP</h3>
            <div class="audit-table">
                <table>
                    <thead>
                        <tr>
                            <th>Paramètre</th>
                            <th>Valeur</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($security_audit['php'] ?? [] as $setting): ?>
                            <tr class="status-<?= $setting['status'] ?>">
                                <td><code><?= htmlspecialchars($setting['setting']) ?></code></td>
                                <td><?= htmlspecialchars($setting['value']) ?></td>
                                <td><?= $setting['status'] === 'ok' ? '✅ OK' : '⚠️ Attention' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Base de données -->
        <div class="audit-category">
            <h3>🗄️ Base de Données</h3>
            <div class="audit-item status-<?= $security_audit['database']['connection'] === 'ok' ? 'ok' : 'error' ?>">
                <?php if ($security_audit['database']['connection'] === 'ok'): ?>
                    <div class="audit-header">
                        <strong>Connexion BDD</strong>
                        <span class="status-badge">✅</span>
                    </div>
                    <div class="audit-details">
                        <strong>Tables auth:</strong> <?= $security_audit['database']['auth_tables'] ?><br>
                        <strong>Comptes admin:</strong> 
                        <span class="status-<?= $security_audit['database']['admin_status'] ?? 'ok' ?>">
                            <?= $security_audit['database']['admin_accounts'] ?? 0 ?>
                        </span><br>
                        <strong>Tables trouvées:</strong> <?= implode(', ', $security_audit['database']['tables_found']) ?>
                    </div>
                <?php else: ?>
                    <div class="audit-header">
                        <strong>Erreur BDD</strong>
                        <span class="status-badge">❌</span>
                    </div>
                    <div class="audit-details">
                        <code><?= htmlspecialchars($security_audit['database']['error']) ?></code>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Logs système -->
        <div class="audit-category">
            <h3>📋 Logs Système</h3>
            <div class="logs-grid">
                <?php foreach ($security_audit['logs'] ?? [] as $log): ?>
                    <div class="log-item">
                        <strong><?= htmlspecialchars($log['file']) ?></strong><br>
                        <span>Taille: <?= $log['size_human'] ?></span><br>
                        <span class="<?= $log['writable'] ? 'text-success' : 'text-warning' ?>">
                            <?= $log['writable'] ? '✅ Accessible' : '⚠️ Non accessible' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="security-section">
        <h2>⚡ Actions Rapides</h2>
        <div class="quick-actions">
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="security_scan">
                <button type="submit" class="btn btn-primary">🔍 Scanner Complet</button>
            </form>
            
            <a href="logs.php" class="btn btn-secondary">📋 Voir les Logs</a>
            <a href="config.php" class="btn btn-secondary">⚙

<?php
/**
 * Titre: Dashboard principal d'administration - Version Production
 * Chemin: /public/admin/index.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// CONFIGURATION ET SÉCURITÉ
// =====================================
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY'); 
header('X-XSS-Protection: 1; mode=block');

// Session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Chargement config avec gestion d'erreurs
try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
    $db_connected = isset($db) && $db instanceof PDO;
} catch (Exception $e) {
    $db_connected = false;
    $config_error = $e->getMessage();
}

// Variables globales pour templates
$page_title = "Administration du Portail";
$current_module = 'admin';
$module_css = true;

// =====================================
// AUTHENTIFICATION OBLIGATOIRE
// =====================================
$user_authenticated = false;
$current_user = null;

// Essayer AuthManager en priorité
if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
    try {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = new AuthManager();
        
        if ($auth->isAuthenticated()) {
            $current_user = $auth->getCurrentUser();
            $user_authenticated = in_array($current_user['role'], ['admin', 'dev']);
        }
    } catch (Exception $e) {
        error_log("Erreur AuthManager admin: " . $e->getMessage());
    }
}

// Fallback authentification manuelle
if (!$user_authenticated && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $session_user = $_SESSION['user'] ?? null;
    if ($session_user && in_array($session_user['role'] ?? '', ['admin', 'dev'])) {
        $user_authenticated = true;
        $current_user = $session_user;
    }
}

// Redirection si non autorisé
if (!$user_authenticated) {
    $_SESSION['error'] = 'Accès refusé - Administrateurs uniquement';
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// =====================================
// FONCTIONS ADMIN
// =====================================

/**
 * Scanner dynamique des fichiers admin
 */
function scanAdminFiles() {
    $admin_path = ROOT_PATH . '/public/admin';
    $files = ['pages' => [], 'folders' => []];
    
    if (!is_dir($admin_path)) return $files;
    
    foreach (scandir($admin_path) as $file) {
        if ($file === '.' || $file === '..' || $file === 'index.php') continue;
        
        $full_path = $admin_path . '/' . $file;
        $file_info = pathinfo($file);
        
        if ($file_info['extension'] === 'php') {
            $files['pages'][] = [
                'name' => $file_info['filename'],
                'file' => $file,
                'path' => '/admin/' . $file,
                'title' => ucfirst(str_replace(['-', '_'], ' ', $file_info['filename'])),
                'icon' => getFileIcon($file_info['filename']),
                'description' => getFileDescription($file_info['filename'])
            ];
        } elseif (is_dir($full_path)) {
            $php_files = array_filter(scandir($full_path), function($f) {
                return pathinfo($f, PATHINFO_EXTENSION) === 'php';
            });
            
            if (!empty($php_files)) {
                $files['folders'][$file] = array_map(function($f) use ($file) {
                    $name = pathinfo($f, PATHINFO_FILENAME);
                    return [
                        'name' => $name,
                        'file' => $f,
                        'path' => "/admin/{$file}/{$f}",
                        'title' => ucfirst(str_replace(['-', '_'], ' ', $name)),
                        'icon' => getFileIcon($name),
                        'description' => getFileDescription($name)
                    ];
                }, $php_files);
            }
        }
    }
    
    return $files;
}

/**
 * Icônes par type de fichier
 */
function getFileIcon($filename) {
    $icons = [
        'scanner' => '🔍', 'audit' => '📊', 'logs' => '📝', 'config' => '⚙️',
        'users' => '👥', 'database' => '🗄️', 'modules' => '🧩', 'system' => '💻',
        'backup' => '💾', 'security' => '🔐', 'monitoring' => '📈', 'reports' => '📋'
    ];
    
    foreach ($icons as $key => $icon) {
        if (stripos($filename, $key) !== false) return $icon;
    }
    return '📄';
}

/**
 * Descriptions par type de fichier
 */
function getFileDescription($filename) {
    $descriptions = [
        'scanner' => 'Diagnostic et scan des erreurs du portail',
        'audit' => 'Audit complet du système et sécurité',
        'logs' => 'Visualisation et gestion des logs système',
        'config' => 'Configuration avancée du portail',
        'users' => 'Gestion complète des utilisateurs',
        'database' => 'Administration de la base de données'
    ];
    
    foreach ($descriptions as $key => $desc) {
        if (stripos($filename, $key) !== false) return $desc;
    }
    return 'Outil d\'administration';
}

/**
 * Statistiques du portail
 */
function getPortalStats() {
    global $db, $db_connected;
    
    $stats = ['tables' => 0, 'records' => 0, 'users' => 0, 'modules' => 0];
    
    if (!$db_connected) return $stats;
    
    try {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $stats['tables'] = count($tables);
        
        $total_records = 0;
        foreach ($tables as $table) {
            $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $total_records += $count;
        }
        $stats['records'] = $total_records;
        
        if (in_array('auth_users', $tables)) {
            $stats['users'] = $db->query("SELECT COUNT(*) FROM auth_users")->fetchColumn();
        }
        
        $modules_dir = ROOT_PATH . '/public';
        if (is_dir($modules_dir)) {
            $dirs = array_filter(scandir($modules_dir), function($d) use ($modules_dir) {
                return is_dir($modules_dir . '/' . $d) && !in_array($d, ['.', '..', 'assets']);
            });
            $stats['modules'] = count($dirs);
        }
    } catch (Exception $e) {
        // Stats par défaut en cas d'erreur
    }
    
    return $stats;
}

// Charger les données
$admin_files = scanAdminFiles();
$portal_stats = getPortalStats();

// =====================================
// INCLUSION HEADER
// =====================================
include ROOT_PATH . '/templates/header.php';
?>

<div class="admin-container">
    <!-- En-tête admin -->
    <div class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <h1>⚙️ Administration du Portail</h1>
                <p class="admin-subtitle">Gestion et monitoring - Utilisateur: <?= htmlspecialchars($current_user['username']) ?></p>
            </div>
            <div class="admin-stats">
                <div class="stat-badge">
                    <span class="stat-number"><?= $portal_stats['tables'] ?></span>
                    <span class="stat-label">Tables</span>
                </div>
                <div class="stat-badge">
                    <span class="stat-number"><?= number_format($portal_stats['records']) ?></span>
                    <span class="stat-label">Enregistrements</span>
                </div>
                <div class="stat-badge">
                    <span class="stat-number"><?= $portal_stats['users'] ?></span>
                    <span class="stat-label">Utilisateurs</span>
                </div>
                <div class="stat-badge">
                    <span class="stat-number"><?= $portal_stats['modules'] ?></span>
                    <span class="stat-label">Modules</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions">
        <h2>⚡ Actions rapides</h2>
        <div class="quick-actions-grid">
            <?php
            $quick_actions = [
                ['file' => 'scanner.php', 'icon' => '🔍', 'title' => 'Scanner', 'desc' => 'Diagnostic erreurs', 'class' => 'scanner'],
                ['file' => 'audit.php', 'icon' => '📊', 'title' => 'Audit', 'desc' => 'Sécurité & performance', 'class' => 'audit'],
                ['file' => 'logs.php', 'icon' => '📝', 'title' => 'Logs', 'desc' => 'Historique système', 'class' => 'logs'],
                ['file' => 'config.php', 'icon' => '⚙️', 'title' => 'Configuration', 'desc' => 'Paramètres portail', 'class' => 'config']
            ];
            
            foreach ($quick_actions as $action):
                $exists = file_exists(ROOT_PATH . '/public/admin/' . $action['file']);
            ?>
            <a href="/admin/<?= $action['file'] ?>" class="quick-action <?= $action['class'] ?>" <?= !$exists ? 'style="opacity:0.5;pointer-events:none;"' : '' ?>>
                <div class="action-icon"><?= $action['icon'] ?></div>
                <div class="action-content">
                    <h3><?= $action['title'] ?></h3>
                    <p><?= $action['desc'] ?></p>
                </div>
                <div class="action-status <?= $exists ? 'ok' : 'error' ?>"><?= $exists ? 'Disponible' : 'Manquant' ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Outils d'administration -->
    <?php if (!empty($admin_files['pages']) || !empty($admin_files['folders'])): ?>
    <div class="admin-tools">
        <h2>🛠️ Outils d'administration</h2>
        
        <?php if (!empty($admin_files['pages'])): ?>
        <div class="tools-section">
            <h3>📄 Pages disponibles</h3>
            <div class="tools-grid">
                <?php foreach ($admin_files['pages'] as $page): ?>
                <a href="<?= htmlspecialchars($page['path']) ?>" class="tool-card">
                    <div class="tool-icon"><?= $page['icon'] ?></div>
                    <div class="tool-content">
                        <h4><?= htmlspecialchars($page['title']) ?></h4>
                        <p><?= htmlspecialchars($page['description']) ?></p>
                        <span class="tool-path"><?= htmlspecialchars($page['file']) ?></span>
                    </div>
                    <div class="tool-arrow">→</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php foreach ($admin_files['folders'] as $folder_name => $folder_files): ?>
        <div class="tools-section">
            <h3>📁 <?= ucfirst(str_replace(['-', '_'], ' ', $folder_name)) ?></h3>
            <div class="tools-grid">
                <?php foreach ($folder_files as $file): ?>
                <a href="<?= htmlspecialchars($file['path']) ?>" class="tool-card">
                    <div class="tool-icon"><?= $file['icon'] ?></div>
                    <div class="tool-content">
                        <h4><?= htmlspecialchars($file['title']) ?></h4>
                        <p><?= htmlspecialchars($file['description']) ?></p>
                        <span class="tool-path"><?= htmlspecialchars($folder_name . '/' . $file['file']) ?></span>
                    </div>
                    <div class="tool-arrow">→</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Modules du portail -->
    <div class="portal-modules">
        <h2>🧩 Modules du portail</h2>
        <div class="modules-overview">
            <?php
            $modules = [
                ['id' => 'port', 'name' => 'Calculateur Frais de Port', 'icon' => '📦', 'desc' => 'Calcul automatisé des frais de livraison'],
                ['id' => 'auth', 'name' => 'Authentification', 'icon' => '🔐', 'desc' => 'Système de connexion et gestion des comptes'],
                ['id' => 'user', 'name' => 'Espace Utilisateur', 'icon' => '👤', 'desc' => 'Profils et paramètres utilisateurs'],
                ['id' => 'admin', 'name' => 'Administration', 'icon' => '⚙️', 'desc' => 'Interface d\'administration système']
            ];
            
            foreach ($modules as $module):
                $module_exists = is_dir(ROOT_PATH . '/public/' . $module['id']);
            ?>
            <div class="module-card <?= $module['id'] ?>">
                <div class="module-header">
                    <span class="module-icon"><?= $module['icon'] ?></span>
                    <h3><?= htmlspecialchars($module['name']) ?></h3>
                    <span class="module-status <?= $module_exists ? 'active' : 'inactive' ?>"><?= $module_exists ? 'Actif' : 'Inactif' ?></span>
                </div>
                <p><?= htmlspecialchars($module['desc']) ?></p>
                <div class="module-actions">
                    <?php if ($module_exists): ?>
                        <?php if ($module['id'] === 'admin'): ?>
                        <span class="btn btn-current">Actuel</span>
                        <?php else: ?>
                        <a href="/<?= $module['id'] ?>/" class="btn btn-primary">Ouvrir</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="btn btn-disabled">Non installé</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- État système -->
    <div class="system-status">
        <h2>💻 État du système</h2>
        <div class="status-grid">
            <div class="status-item <?= $db_connected ? 'ok' : 'error' ?>">
                <span class="status-icon"><?= $db_connected ? '✅' : '❌' ?></span>
                <div class="status-content">
                    <h4>Base de données</h4>
                    <p><?= $db_connected ? 'Connectée' : 'Erreur de connexion' ?></p>
                </div>
            </div>
            
            <div class="status-item <?= file_exists(ROOT_PATH . '/config/config.php') ? 'ok' : 'error' ?>">
                <span class="status-icon"><?= file_exists(ROOT_PATH . '/config/config.php') ? '✅' : '❌' ?></span>
                <div class="status-content">
                    <h4>Configuration</h4>
                    <p><?= file_exists(ROOT_PATH . '/config/config.php') ? 'Chargée' : 'Manquante' ?></p>
                </div>
            </div>
            
            <div class="status-item <?= is_writable(ROOT_PATH . '/storage') ? 'ok' : 'warning' ?>">
                <span class="status-icon"><?= is_writable(ROOT_PATH . '/storage') ? '✅' : '⚠️' ?></span>
                <div class="status-content">
                    <h4>Permissions</h4>
                    <p><?= is_writable(ROOT_PATH . '/storage') ? 'Correctes' : 'À vérifier' ?></p>
                </div>
            </div>
            
            <div class="status-item info">
                <span class="status-icon">📊</span>
                <div class="status-content">
                    <h4>Version</h4>
                    <p><?= defined('APP_VERSION') ? APP_VERSION : '0.5' ?> - Build <?= defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Liens utiles -->
    <div class="useful-links">
        <h2>🔗 Accès rapide</h2>
        <div class="links-grid">
            <a href="/" class="link-card home" target="_blank">
                <span class="link-icon">🏠</span>
                <div class="link-content">
                    <h4>Portail Principal</h4>
                    <p>Retour à l'accueil</p>
                </div>
            </a>
            
            <a href="/admin/scanner.php?deep=1" class="link-card scanner">
                <span class="link-icon">🔬</span>
                <div class="link-content">
                    <h4>Scan Approfondi</h4>
                    <p>Diagnostic complet</p>
                </div>
            </a>
            
            <a href="/diagnostic_500.php" class="link-card emergency" target="_blank">
                <span class="link-icon">🚨</span>
                <div class="link-content">
                    <h4>Diagnostic d'Urgence</h4>
                    <p>En cas de problème</p>
                </div>
            </a>
            
            <a href="/auth/logout.php" class="link-card logout">
                <span class="link-icon">🚪</span>
                <div class="link-content">
                    <h4>Déconnexion</h4>
                    <p>Fin de session admin</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/templates/footer.php'; ?>

<?php
/**
 * Titre: Dashboard principal d'administration - Version réécrite
 * Chemin: /public/admin/index.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// CONFIGURATION ET SÉCURITÉ
// =====================================
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY'); 
header('X-XSS-Protection: 1; mode=block');

// Session unique
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ajouter après session_start()
if (ENHANCED_SECURITY_ENABLED) {
    enhancedSecurityCheck('admin');
}

// Chargement config avec gestion d'erreurs
$db_connected = false;
$config_error = null;

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
    
    if (isset($db) && $db instanceof PDO) {
        $db->query('SELECT 1');
        $db_connected = true;
    }
} catch (Exception $e) {
    $db_connected = false;
    $config_error = $e->getMessage();
    error_log("Erreur config admin: " . $e->getMessage());
}

// Variables pour templates
$page_title = "Administration du Portail";
$page_subtitle = "Tableau de bord système";
$current_module = 'admin';

// L'authentification est gérée exclusivement par le header du portail
// Aucun contrôle d'authentification requis dans cette page

// =====================================
// FONCTIONS UTILITAIRES
// =====================================

/**
 * Récupère les statistiques système
 */
function getSystemStats($db) {
    $stats = [
        'tables' => 0,
        'users' => 0,
        'sessions' => 0,
        'modules' => 0
    ];
    
    if (!$db) return $stats;
    
    try {
        // Compter les tables
        $stmt = $db->query("SHOW TABLES");
        $stats['tables'] = $stmt->rowCount();
        
        // Compter les utilisateurs
        $stmt = $db->query("SELECT COUNT(*) FROM auth_users");
        $stats['users'] = $stmt->fetchColumn();
        
        // Compter les sessions actives
        $stmt = $db->query("SELECT COUNT(*) FROM auth_sessions WHERE expires_at > NOW()");
        $stats['sessions'] = $stmt->fetchColumn();
        
        // Modules détectés
        $modules_path = ROOT_PATH . '/public';
        if (is_dir($modules_path)) {
            $modules = array_filter(scandir($modules_path), function($item) use ($modules_path) {
                return is_dir($modules_path . '/' . $item) && 
                       !in_array($item, ['.', '..', 'assets']) &&
                       file_exists($modules_path . '/' . $item . '/index.php');
            });
            $stats['modules'] = count($modules);
        }
        
    } catch (Exception $e) {
        error_log("Erreur stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Actions rapides disponibles
 */
function getQuickActions() {
    return [
        'scanner' => [
            'title' => 'Scanner Système',
            'desc' => 'Diagnostic complet du portail',
            'icon' => '🔍',
            'url' => '/admin/scanner.php',
            'class' => 'scanner'
        ],
        'users' => [
            'title' => 'Gestion Utilisateurs',
            'desc' => 'CRUD utilisateurs et permissions',
            'icon' => '👥',
            'url' => '/admin/pages/users.php',
            'class' => 'users'
        ],
        'database' => [
            'title' => 'Base de Données',
            'desc' => 'Tables, données et requêtes',
            'icon' => '🗄️',
            'url' => '/admin/pages/database.php',
            'class' => 'database'
        ],
        'logs' => [
            'title' => 'Logs Système',
            'desc' => 'Erreurs, accès et monitoring',
            'icon' => '📊',
            'url' => '/admin/pages/logs.php',
            'class' => 'logs'
        ],
        'config' => [
            'title' => 'Configuration',
            'desc' => 'Paramètres système et modules',
            'icon' => '⚙️',
            'url' => '/admin/pages/config.php',
            'class' => 'config'
        ],
        'cache' => [
            'title' => 'Cache & Performance',
            'desc' => 'Nettoyage et optimisation',
            'icon' => '🚀',
            'url' => '/admin/pages/performance.php',
            'class' => 'performance'
        ]
    ];
}

/**
 * Scan des modules disponibles
 */
function scanAvailableModules() {
    $modules = [];
    $modules_path = ROOT_PATH . '/public';
    
    if (!is_dir($modules_path)) return $modules;
    
    foreach (scandir($modules_path) as $item) {
        if ($item === '.' || $item === '..' || $item === 'assets') continue;
        
        $module_path = $modules_path . '/' . $item;
        if (!is_dir($module_path)) continue;
        
        $index_file = $module_path . '/index.php';
        if (!file_exists($index_file)) continue;
        
        $modules[] = [
            'name' => $item,
            'title' => ucfirst($item),
            'path' => '/' . $item,
            'status' => file_exists($index_file) ? 'active' : 'inactive',
            'size' => formatFileSize(getDirectorySize($module_path))
        ];
    }
    
    return $modules;
}

/**
 * Formatage taille fichier
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Taille d'un répertoire
 */
function getDirectorySize($directory) {
    $size = 0;
    if (is_dir($directory)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
    }
    return $size;
}

// =====================================
// DONNÉES POUR L'AFFICHAGE
// =====================================
$system_stats = getSystemStats($db_connected ? $db : null);
$quick_actions = getQuickActions();
$available_modules = scanAvailableModules();

// Header inclusions
require_once ROOT_PATH . '/templates/header.php';
?>

<div class="admin-container">
    <!-- En-tête admin -->
    <div class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <h1>⚡ Administration</h1>
                <p class="admin-subtitle">Tableau de bord système - Version <?= htmlspecialchars(APP_VERSION) ?></p>
            </div>
            
            <div class="admin-stats">
                <div class="stat-badge">
                    <span class="stat-number"><?= $system_stats['tables'] ?></span>
                    <span class="stat-label">Tables</span>
                </div>
                <div class="stat-badge">
                    <span class="stat-number"><?= $system_stats['users'] ?></span>
                    <span class="stat-label">Utilisateurs</span>
                </div>
                <div class="stat-badge">
                    <span class="stat-number"><?= $system_stats['sessions'] ?></span>
                    <span class="stat-label">Sessions</span>
                </div>
                <div class="stat-badge">
                    <span class="stat-number"><?= $system_stats['modules'] ?></span>
                    <span class="stat-label">Modules</span>
                </div>
            </div>

            <!-- Widget sécurité admin -->
            <?php if (function_exists('getSecurityWidget')): ?>
                <?= getSecurityWidget(7) ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- État système -->
    <div class="system-status">
        <h2>🔧 État du Système</h2>
        <div class="status-grid">
            <div class="status-card">
                <div class="status-icon <?= $db_connected ? 'ok' : 'error' ?>">🗄️</div>
                <div class="status-content">
                    <h3>Base de Données</h3>
                    <p><?= $db_connected ? 'Connectée et fonctionnelle' : 'Erreur de connexion' ?></p>
                    <?php if ($config_error): ?>
                        <small class="error-detail"><?= htmlspecialchars($config_error) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="status-card">
                <div class="status-icon info">🔐</div>
                <div class="status-content">
                    <h3>Authentification</h3>
                    <p>Gérée par le header du portail</p>
                    <small>Accès admin autorisé</small>
                </div>
            </div>
            
            <div class="status-card">
                <div class="status-icon <?= is_writable(ROOT_PATH . '/storage') ? 'ok' : 'warning' ?>">📝</div>
                <div class="status-content">
                    <h3>Permissions</h3>
                    <p><?= is_writable(ROOT_PATH . '/storage') ? 'Écriture autorisée' : 'Vérifier les permissions' ?></p>
                    <small>Storage: <?= is_dir(ROOT_PATH . '/storage') ? 'Présent' : 'Manquant' ?></small>
                </div>
            </div>
            
            <div class="status-card">
                <div class="status-icon info">⚡</div>
                <div class="status-content">
                    <h3>Performance</h3>
                    <p>Mémoire: <?= round(memory_get_usage() / 1024 / 1024, 2) ?> MB</p>
                    <small>Temps: <?= round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000) ?>ms</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions">
        <h2>🚀 Actions Rapides</h2>
        <div class="quick-actions-grid">
            <?php foreach ($quick_actions as $key => $action): ?>
                <a href="<?= htmlspecialchars($action['url']) ?>" class="quick-action <?= $action['class'] ?>">
                    <div class="action-icon"><?= $action['icon'] ?></div>
                    <div class="action-content">
                        <h3><?= htmlspecialchars($action['title']) ?></h3>
                        <p><?= htmlspecialchars($action['desc']) ?></p>
                    </div>
                    <div class="action-status <?= file_exists(ROOT_PATH . '/public' . $action['url']) ? 'ok' : 'warning' ?>">
                        <?= file_exists(ROOT_PATH . '/public' . $action['url']) ? 'Disponible' : 'À créer' ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Modules disponibles -->
    <?php if (!empty($available_modules)): ?>
    <div class="modules-overview">
        <h2>🔧 Modules Installés</h2>
        <div class="modules-grid">
            <?php foreach ($available_modules as $module): ?>
                <div class="module-card">
                    <div class="module-header">
                        <h3><?= htmlspecialchars($module['title']) ?></h3>
                        <span class="module-status <?= $module['status'] ?>"><?= ucfirst($module['status']) ?></span>
                    </div>
                    <div class="module-content">
                        <p><strong>Chemin:</strong> <?= htmlspecialchars($module['path']) ?></p>
                        <p><strong>Taille:</strong> <?= htmlspecialchars($module['size']) ?></p>
                    </div>
                    <div class="module-actions">
                        <a href="<?= htmlspecialchars($module['path']) ?>" class="btn-primary">Accéder</a>
                        <button class="btn-secondary" onclick="analyzeModule('<?= htmlspecialchars($module['name']) ?>')">Analyser</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Logs récents -->
    <div class="recent-activity">
        <h2>📊 Activité Récente</h2>
        <div class="activity-feed">
            <div class="activity-item">
                <div class="activity-icon info">ℹ️</div>
                <div class="activity-content">
                    <p><strong>Dashboard chargé</strong></p>
                    <small><?= date('d/m/Y H:i:s') ?></small>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon ok">✅</div>
                <div class="activity-content">
                    <p><strong>Base de données</strong> : <?= $db_connected ? 'Connexion établie' : 'Erreur de connexion' ?></p>
                    <small><?= date('d/m/Y H:i:s') ?></small>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon info">🔐</div>
                <div class="activity-content">
                    <p><strong>Authentification</strong> : Gérée par le header du portail</p>
                    <small>Accès administrateur validé</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript admin -->
<script>
// TODO: Déplacer dans /public/admin/assets/js/admin.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin dashboard loaded');
    
    // Animation des cartes au chargement
    const cards = document.querySelectorAll('.quick-action, .status-card, .module-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Rafraîchissement automatique des stats (optionnel)
    // setInterval(refreshStats, 30000);
});

function analyzeModule(moduleName) {
    // TODO: Implémenter analyse détaillée des modules
    alert('Analyse du module "' + moduleName + '" - Fonctionnalité à développer');
}

function refreshStats() {
    // TODO: Implémenter rafraîchissement AJAX des statistiques
    console.log('Refresh stats...');
}
</script>

<?php
// Footer
require_once ROOT_PATH . '/templates/footer.php';
?>
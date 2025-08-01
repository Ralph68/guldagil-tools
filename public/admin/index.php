<?php
/**
 * Titre: Dashboard principal d'administration - Version Production
 * Chemin: /public/admin/index.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// CONFIGURATION ET SÉCURITÉ
// =====================================
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Headers de sécurité renforcés
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY'); 
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Session sécurisée (éviter les doublons)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Chargement config avec gestion d'erreurs robuste
$db_connected = false;
$config_error = null;

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
    
    // Test connexion BDD
    if (isset($db) && $db instanceof PDO) {
        $db->query('SELECT 1');
        $db_connected = true;
    }
} catch (Exception $e) {
    $db_connected = false;
    $config_error = $e->getMessage();
    error_log("Erreur config admin: " . $e->getMessage());
}

// Variables globales pour templates
$page_title = "Administration du Portail";
$page_subtitle = "Tableau de bord système";
$page_description = "Interface complète d'administration et monitoring";
$current_module = 'admin';
$module_css = true;

// =====================================
// AUTHENTIFICATION OBLIGATOIRE
// =====================================
$user_authenticated = false;
$current_user = null;

// TODO: Intégrer système de permissions granulaires
// TODO: Ajouter audit trail des actions admin
// TODO: Implémenter rate limiting pour sécurité

// Essayer AuthManager en priorité
if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
    try {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = new AuthManager();
        
        if ($auth->isAuthenticated()) {
            $current_user = $auth->getCurrentUser();
            $user_authenticated = in_array($current_user['role'], ['admin', 'dev', 'superadmin']);
        }
    } catch (Exception $e) {
        error_log("Erreur AuthManager admin: " . $e->getMessage());
    }
}

if (!$user_authenticated) {
    $_SESSION['error'] = 'Accès refusé - Administrateurs uniquement';
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
// Redirection si non autorisé
if (!$user_authenticated) {
    $_SESSION['error'] = 'Accès refusé - Administrateurs uniquement';
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// =====================================
// FONCTIONS ADMIN AMÉLIORÉES
// =====================================

/**
 * Scanner dynamique amélioré des fichiers admin
 * TODO: Ajouter cache pour performance
 * TODO: Scanner récursif des sous-modules
 */
function scanAdminFiles() {
    $admin_path = ROOT_PATH . '/public/admin';
    $files = ['pages' => [], 'folders' => [], 'stats' => ['total' => 0, 'folders' => 0, 'pages' => 0]];
    
    if (!is_dir($admin_path)) {
        return $files;
    }
    
    try {
        foreach (scandir($admin_path) as $file) {
            if ($file === '.' || $file === '..' || $file === 'index.php') continue;
            
            $full_path = $admin_path . '/' . $file;
            $file_info = pathinfo($file);
            
            if (isset($file_info['extension']) && $file_info['extension'] === 'php') {
                $files['pages'][] = [
                    'name' => $file_info['filename'],
                    'file' => $file,
                    'path' => '/admin/' . $file,
                    'title' => ucfirst(str_replace(['-', '_'], ' ', $file_info['filename'])),
                    'icon' => getFileIcon($file_info['filename']),
                    'description' => getFileDescription($file_info['filename']),
                    'size' => formatFileSize(filesize($full_path)),
                    'modified' => date('d/m/Y H:i', filemtime($full_path))
                ];
                $files['stats']['pages']++;
            } elseif (is_dir($full_path)) {
                $php_files = array_filter(scandir($full_path), function($f) {
                    return pathinfo($f, PATHINFO_EXTENSION) === 'php';
                });
                
                if (!empty($php_files)) {
                    $files['folders'][$file] = array_map(function($f) use ($file, $full_path) {
                        $name = pathinfo($f, PATHINFO_FILENAME);
                        $subfile_path = $full_path . '/' . $f;
                        return [
                            'name' => $name,
                            'file' => $f,
                            'path' => "/admin/{$file}/{$f}",
                            'title' => ucfirst(str_replace(['-', '_'], ' ', $name)),
                            'icon' => getFileIcon($name),
                            'description' => getFileDescription($name),
                            'size' => formatFileSize(filesize($subfile_path)),
                            'modified' => date('d/m/Y H:i', filemtime($subfile_path))
                        ];
                    }, $php_files);
                    $files['stats']['folders']++;
                }
            }
            $files['stats']['total']++;
        }
    } catch (Exception $e) {
        error_log("Erreur scan admin files: " . $e->getMessage());
    }
    
    return $files;
}

/**
 * Icônes par type de fichier - version étendue
 */
function getFileIcon($filename) {
    $icons = [
        'scanner' => '🔍', 'audit' => '📊', 'logs' => '📝', 'config' => '⚙️',
        'users' => '👥', 'database' => '🗄️', 'modules' => '🧩', 'system' => '💻',
        'backup' => '💾', 'security' => '🔐', 'monitoring' => '📈', 'reports' => '📋',
        'analytics' => '📊', 'maintenance' => '🔧', 'cache' => '⚡', 'error' => '❌',
        'debug' => '🐛', 'test' => '🧪', 'performance' => '⚡', 'stats' => '📈'
    ];
    
    foreach ($icons as $key => $icon) {
        if (stripos($filename, $key) !== false) return $icon;
    }
    return '📄';
}

/**
 * Descriptions améliorées par type de fichier
 */
function getFileDescription($filename) {
    $descriptions = [
        'scanner' => 'Diagnostic automatisé et scan des erreurs du portail',
        'audit' => 'Audit complet sécurité et conformité système',
        'logs' => 'Visualisation et analyse avancée des logs',
        'config' => 'Configuration système et paramètres avancés',
        'users' => 'Gestion complète utilisateurs et permissions',
        'database' => 'Administration base de données et maintenance',
        'modules' => 'Gestion des modules et dépendances',
        'system' => 'Informations système et monitoring',
        'backup' => 'Sauvegarde et restauration automatisée',
        'security' => 'Sécurité, authentification et contrôles d\'accès',
        'monitoring' => 'Surveillance temps réel et alertes',
        'reports' => 'Génération de rapports et analytics',
        'analytics' => 'Analyse des performances et statistiques',
        'maintenance' => 'Maintenance préventive et optimisation',
        'cache' => 'Gestion du cache et performance',
        'debug' => 'Outils de debug et développement',
        'test' => 'Tests automatisés et validation',
        'performance' => 'Optimisation des performances'
    ];
    
    foreach ($descriptions as $key => $desc) {
        if (stripos($filename, $key) !== false) return $desc;
    }
    return 'Outil d\'administration système';
}

/**
 * Formatage de taille de fichier
 * TODO: Ajouter localisation française
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log(1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

/**
 * Statistiques système améliorées
 * TODO: Intégrer métriques de performance
 * TODO: Ajouter alertes système automatiques
 */
function getSystemStats() {
    global $db_connected;
    
    $stats = [
        'system' => [
            'php_version' => PHP_VERSION,
            'memory_usage' => formatFileSize(memory_get_usage(true)),
            'peak_memory' => formatFileSize(memory_get_peak_usage(true)),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu',
            'disk_space' => formatFileSize(disk_free_space(ROOT_PATH))
        ],
        'database' => [
            'connected' => $db_connected,
            'tables_count' => 0,
            'size' => 'N/A'
        ],
        'files' => [
            'total_files' => 0,
            'total_size' => 0
        ]
    ];
    
    // Stats BDD si connectée
    if ($db_connected) {
        try {
            global $db;
            $stmt = $db->query("SHOW TABLES");
            $stats['database']['tables_count'] = $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Erreur stats BDD: " . $e->getMessage());
        }
    }
    
    // TODO: Ajouter stats fichiers si performance acceptable
    
    return $stats;
}

// =====================================
// DONNÉES POUR INTERFACE
// =====================================

// Scan des fichiers admin
$admin_files = scanAdminFiles();

// Statistiques système
$system_stats = getSystemStats();

// Actions rapides améliorées
$quick_actions = [
    [
    'file' => 'system/errors.php',
    'icon' => '🚨',
    'title' => 'Gestion Erreurs',
    'desc' => 'Monitoring et diagnostic système',
    'class' => 'system'
    ],
    [
        'title' => 'Scanner système',
        'desc' => 'Diagnostic complet du portail',
        'icon' => '🔍',
        'file' => 'scanner.php',
        'class' => 'scanner',
        'priority' => 1
    ],
    [
        'title' => 'Analytics & maintenance',
        'desc' => 'Monitoring et optimisation',
        'icon' => '📊',
        'file' => 'analytics_maintenance.php',
        'class' => 'analytics',
        'priority' => 2
    ],
    [
        'title' => 'Gestion utilisateurs',
        'desc' => 'Administration des comptes',
        'icon' => '👥',
        'file' => 'users.php',
        'class' => 'users',
        'priority' => 3
    ],
    [
        'title' => 'Configuration',
        'desc' => 'Paramètres système',
        'icon' => '⚙️',
        'file' => 'config.php', 
        'class' => 'config',
        'priority' => 4
    ]
];

// Tri par priorité
usort($quick_actions, function($a, $b) {
    return $a['priority'] - $b['priority'];
});

// =====================================
// INCLUSION TEMPLATES
// =====================================

// Header avec gestion d'erreur
$header_path = ROOT_PATH . '/templates/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    // TODO: Créer header d'urgence minimal
    echo "<!DOCTYPE html><html><head><title>Admin - Erreur Template</title></head><body>";
    echo "<div style='color:red;padding:20px;'>Erreur: Template header introuvable</div>";
}
?>

<!-- Dashboard admin principal -->
<main class="admin-dashboard">
    <!-- Alerte configuration si erreur -->
    <?php if ($config_error): ?>
    <div class="alert alert-error">
        <span class="alert-icon">⚠️</span>
        <div class="alert-content">
            <h4>Problème de configuration détecté</h4>
            <p><?= htmlspecialchars($config_error) ?></p>
            <small>Vérifiez la configuration dans <code>/config/config.php</code></small>
        </div>
    </div>
    <?php endif; ?>

    <!-- En-tête dashboard -->
    <div class="dashboard-header">
        <div class="header-info">
            <h1>🛠️ Administration du portail</h1>
            <p class="subtitle">Bienvenue <strong><?= htmlspecialchars($current_user['username'] ?? 'Admin') ?></strong> • Rôle: <span class="badge badge-<?= htmlspecialchars($current_user['role'] ?? 'admin') ?>"><?= htmlspecialchars($current_user['role'] ?? 'admin') ?></span></p>
        </div>
        <div class="header-stats">
            <div class="stat-item">
                <span class="stat-icon">📊</span>
                <div class="stat-content">
                    <div class="stat-value"><?= $admin_files['stats']['total'] ?></div>
                    <div class="stat-label">Outils disponibles</div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon <?= $db_connected ? '✅' : '❌' ?>"></span>
                <div class="stat-content">
                    <div class="stat-value"><?= $db_connected ? 'Connectée' : 'Erreur' ?></div>
                    <div class="stat-label">Base de données</div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon">🚨</span>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php
                        $error_count = 0;
                        if (class_exists('ErrorManager')) {
                            $stats = ErrorManager::getInstance()->getErrorStats();
                            $error_count = $stats['critical'] ?? 0;
                        }
                        echo $error_count;
                        ?>
                    </div>
                    <div class="stat-label">Erreurs critiques</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides prioritaires -->
    <div class="quick-actions-section">
        <h2>⚡ Actions rapides</h2>
        <div class="quick-actions-grid">
            <?php foreach ($quick_actions as $action): 
                $exists = file_exists(ROOT_PATH . '/public/admin/' . $action['file']);
            ?>
            <a href="/admin/<?= htmlspecialchars($action['file']) ?>" 
               class="quick-action <?= htmlspecialchars($action['class']) ?>" 
               <?= !$exists ? 'style="opacity:0.5;pointer-events:none;" title="Fichier manquant"' : '' ?>>
                <div class="action-icon"><?= $action['icon'] ?></div>
                <div class="action-content">
                    <h3><?= htmlspecialchars($action['title']) ?></h3>
                    <p><?= htmlspecialchars($action['desc']) ?></p>
                </div>
                <div class="action-status <?= $exists ? 'ok' : 'error' ?>">
                    <?= $exists ? 'Disponible' : 'Manquant' ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Outils d'administration détaillés -->
    <?php if (!empty($admin_files['pages']) || !empty($admin_files['folders'])): ?>
    <div class="admin-tools">
        <h2>🛠️ Outils d'administration</h2>
        
        <!-- Pages directes -->
        <?php if (!empty($admin_files['pages'])): ?>
        <div class="tools-section">
            <h3>📄 Pages disponibles (<?= count($admin_files['pages']) ?>)</h3>
            <div class="tools-grid">
                <?php foreach ($admin_files['pages'] as $page): ?>
                <a href="<?= htmlspecialchars($page['path']) ?>" class="tool-card">
                    <div class="tool-icon"><?= $page['icon'] ?></div>
                    <div class="tool-content">
                        <h4><?= htmlspecialchars($page['title']) ?></h4>
                        <p><?= htmlspecialchars($page['description']) ?></p>
                        <div class="tool-meta">
                            <span class="tool-path"><?= htmlspecialchars($page['file']) ?></span>
                            <span class="tool-size"><?= htmlspecialchars($page['size']) ?></span>
                            <span class="tool-date"><?= htmlspecialchars($page['modified']) ?></span>
                        </div>
                    </div>
                    <div class="tool-arrow">→</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Dossiers organisés -->
        <?php foreach ($admin_files['folders'] as $folder_name => $folder_files): ?>
        <div class="tools-section">
            <h3>📁 <?= ucfirst(str_replace(['-', '_'], ' ', htmlspecialchars($folder_name))) ?> (<?= count($folder_files) ?>)</h3>
            <div class="tools-grid">
                <?php foreach ($folder_files as $file): ?>
                <a href="<?= htmlspecialchars($file['path']) ?>" class="tool-card">
                    <div class="tool-icon"><?= $file['icon'] ?></div>
                    <div class="tool-content">
                        <h4><?= htmlspecialchars($file['title']) ?></h4>
                        <p><?= htmlspecialchars($file['description']) ?></p>
                        <div class="tool-meta">
                            <span class="tool-path"><?= htmlspecialchars($folder_name . '/' . $file['file']) ?></span>
                            <span class="tool-size"><?= htmlspecialchars($file['size']) ?></span>
                            <span class="tool-date"><?= htmlspecialchars($file['modified']) ?></span>
                        </div>
                    </div>
                    <div class="tool-arrow">→</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <h3>Aucun outil d'administration trouvé</h3>
        <p>Les outils d'administration seront disponibles une fois installés.</p>
        <!-- TODO: Ajouter bouton installation/génération outils manquants -->
    </div>
    <?php endif; ?>

    <!-- Modules du portail -->
    <div class="portal-modules">
        <h2>🧩 Modules du portail</h2>
        <div class="modules-overview">
            <?php
            // TODO: Scanner automatique des modules installés
            $modules = [
                ['id' => 'port', 'name' => 'Calculateur Frais de Port', 'icon' => '📦', 'desc' => 'Calcul automatisé des frais de livraison'],
                ['id' => 'auth', 'name' => 'Authentification', 'icon' => '🔐', 'desc' => 'Système de connexion et gestion des comptes'],
                ['id' => 'user', 'name' => 'Espace Utilisateur', 'icon' => '👤', 'desc' => 'Profils et paramètres utilisateurs'],
                ['id' => 'admin', 'name' => 'Administration', 'icon' => '⚙️', 'desc' => 'Interface d\'administration système'],
                ['id' => 'adr', 'name' => 'Module ADR', 'icon' => '⚠️', 'desc' => 'Gestion marchandises dangereuses']
            ];
            
            foreach ($modules as $module):
                $module_exists = is_dir(ROOT_PATH . '/public/' . $module['id']);
                $index_exists = file_exists(ROOT_PATH . '/public/' . $module['id'] . '/index.php');
            ?>
            <div class="module-card <?= htmlspecialchars($module['id']) ?>">
                <div class="module-header">
                    <span class="module-icon"><?= $module['icon'] ?></span>
                    <h3><?= htmlspecialchars($module['name']) ?></h3>
                    <span class="module-status <?= $module_exists && $index_exists ? 'active' : ($module_exists ? 'incomplete' : 'inactive') ?>">
                        <?= $module_exists && $index_exists ? 'Actif' : ($module_exists ? 'Incomplet' : 'Inactif') ?>
                    </span>
                </div>
                <p><?= htmlspecialchars($module['desc']) ?></p>
                <div class="module-actions">
                    <?php if ($module['id'] === 'admin'): ?>
                        <span class="btn btn-current">Actuel</span>
                    <?php elseif ($module_exists && $index_exists): ?>
                        <a href="/<?= htmlspecialchars($module['id']) ?>/" class="btn btn-primary">Ouvrir</a>
                    <?php elseif ($module_exists): ?>
                        <span class="btn btn-warning">Configuration requise</span>
                    <?php else: ?>
                        <span class="btn btn-disabled">Non installé</span>
                        <!-- TODO: Ajouter bouton installation module -->
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- État système détaillé -->
    <div class="system-status">
        <h2>💻 État du système</h2>
        <div class="status-grid">
            <!-- Base de données -->
            <div class="status-item <?= $db_connected ? 'ok' : 'error' ?>">
                <span class="status-icon"><?= $db_connected ? '✅' : '❌' ?></span>
                <div class="status-content">
                    <h4>Base de données</h4>
                    <p><?= $db_connected ? 'Connectée' : 'Erreur de connexion' ?></p>
                    <?php if ($db_connected): ?>
                    <small><?= $system_stats['database']['tables_count'] ?> tables disponibles</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Configuration -->
            <div class="status-item <?= file_exists(ROOT_PATH . '/config/config.php') ? 'ok' : 'error' ?>">
                <span class="status-icon"><?= file_exists(ROOT_PATH . '/config/config.php') ? '✅' : '❌' ?></span>
                <div class="status-content">
                    <h4>Configuration</h4>
                    <p><?= file_exists(ROOT_PATH . '/config/config.php') ? 'Configuré' : 'Manquant' ?></p>
                    <small>PHP <?= $system_stats['system']['php_version'] ?></small>
                </div>
            </div>
            
            <!-- Mémoire -->
            <div class="status-item <?= memory_get_usage(true) < 100*1024*1024 ? 'ok' : 'warning' ?>">
                <span class="status-icon"><?= memory_get_usage(true) < 100*1024*1024 ? '✅' : '⚠️' ?></span>
                <div class="status-content">
                    <h4>Mémoire PHP</h4>
                    <p><?= $system_stats['system']['memory_usage'] ?> utilisés</p>
                    <small>Pic: <?= $system_stats['system']['peak_memory'] ?></small>
                </div>
            </div>
            
            <!-- Espace disque -->
            <div class="status-item <?= disk_free_space(ROOT_PATH) > 1*1024*1024*1024 ? 'ok' : 'warning' ?>">
                <span class="status-icon"><?= disk_free_space(ROOT_PATH) > 1*1024*1024*1024 ? '✅' : '⚠️' ?></span>
                <div class="status-content">
                    <h4>Espace disque</h4>
                    <p><?= $system_stats['system']['disk_space'] ?> libres</p>
                    <small><?= htmlspecialchars($system_stats['system']['server_software']) ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions système rapides -->
    <div class="system-actions">
        <h2>🔧 Actions système</h2>
        <div class="actions-grid">
            <button onclick="clearCache()" class="action-btn cache">
                <span class="btn-icon">🗑️</span>
                <span class="btn-text">Vider le cache</span>
            </button>
            <button onclick="viewLogs()" class="action-btn logs">
                <span class="btn-icon">📝</span>
                <span class="btn-text">Voir les logs</span>
            </button>
            <button onclick="runDiagnostic()" class="action-btn diagnostic">
                <span class="btn-icon">🔍</span>
                <span class="btn-text">Diagnostic complet</span>
            </button>
            <button onclick="exportConfig()" class="action-btn export">
                <span class="btn-icon">📤</span>
                <span class="btn-text">Exporter config</span>
            </button>
        </div>
    </div>
</main>

<!-- Scripts spécifiques admin -->
<script>
// TODO: Migrer vers fichier externe /public/admin/assets/js/admin.js
// TODO: Ajouter notifications temps réel
// TODO: Intégrer WebSocket pour monitoring live

// Actions système
function clearCache() {
    if (!confirm('Vider le cache système ?')) return;
    
    fetch('/admin/api/system.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'clear_cache'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('Cache vidé avec succès', 'success');
        } else {
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(e => showNotification('Erreur réseau', 'error'));
}

function viewLogs() {
    window.open('/admin/logs.php', '_blank');
}

function runDiagnostic() {
    window.location.href = '/admin/scanner.php';
}

function exportConfig() {
    window.location.href = '/admin/api/system.php?action=export_config';
}

// Système de notifications simple
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
        <span class="notification-text">${message}</span>
        <button onclick="this.parentElement.remove()" class="notification-close">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-suppression après 5 secondes
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Auto-refresh des stats système toutes les 30 secondes
// TODO: Implémenter refresh intelligent avec WebSocket
setInterval(() => {
    fetch('/admin/api/system.php?action=stats')
        .then(r => r.json())
        .then(data => {
            // Mise à jour des éléments de status
            // TODO: Implémenter mise à jour UI dynamique
        })
        .catch(e => console.warn('Erreur refresh stats:', e));
}, 30000);

// Raccourcis clavier admin
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 's':
                e.preventDefault();
                runDiagnostic();
                break;
            case 'l':
                e.preventDefault();
                viewLogs();
                break;
        }
    }
});
</script>

<?php
// Footer avec gestion d'erreur
$footer_path = ROOT_PATH . '/templates/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    // TODO: Créer footer d'urgence minimal
    echo "<footer style='padding:20px;text-align:center;border-top:1px solid #ddd;margin-top:40px;'>";
    echo "<p>&copy; " . date('Y') . " Portail Guldagil • Version " . htmlspecialchars($version ?? '0.5 beta') . " • Build " . htmlspecialchars($build_number ?? 'dev') . "</p>";
    echo "</footer></body></html>";
}
?>
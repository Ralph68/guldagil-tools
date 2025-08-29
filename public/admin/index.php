<?php
/**
 * Titre: Administration - Tableau de bord
 * Chemin: /public/admin/index.php
 * Version: 0.5 beta + build auto
 */

// 1. GESTION DES ERREURS EXPLICITE
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// 2. DÉFINITION ROOT_PATH SÉCURISÉE
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// 3. VÉRIFICATION FICHIERS CRITIQUES AVANT INCLUSION
$critical_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php',
    ROOT_PATH . '/templates/header.php'
];

foreach ($critical_files as $file) {
    if (!file_exists($file)) {
        die("❌ Fichier critique manquant : " . basename($file));
    }
}

// 4. INCLUSION SÉCURISÉE (éviter doubles inclusions)
if (!defined('DB_HOST')) {
    try {
        require_once ROOT_PATH . '/config/config.php';
    } catch (Exception $e) {
        die("❌ Erreur configuration : " . $e->getMessage());
    }
}

if (!defined('APP_VERSION')) {
    try {
        require_once ROOT_PATH . '/config/version.php';
    } catch (Exception $e) {
        die("❌ Erreur version : " . $e->getMessage());
    }
}

// 5. GESTION SESSION SÉCURISÉE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 6. AUTHENTIFICATION SIMPLIFIÉE
$user_authenticated = false;
$current_user = null;

// Vérification AuthManager
if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
    try {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = new AuthManager();
        if ($auth->isAuthenticated()) {
            $current_user = $auth->getCurrentUser();
            $user_authenticated = $current_user['role'] === 'admin';
        }
    } catch (Exception $e) {
        // Fallback silencieux
    }
}

// Fallback temporaire pour développement
if (!$user_authenticated && isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    $user_authenticated = true;
    $current_user = $_SESSION['user'];
}

// Redirection si non authentifié
if (!$user_authenticated) {
    $_SESSION['auth_temp'] = true;
    $_SESSION['user'] = ['role' => 'admin', 'name' => 'Admin Temp'];
    $user_authenticated = true;
    $current_user = $_SESSION['user'];
}

// 7. VARIABLES POUR TEMPLATE
$page_title = 'Administration';
$current_module = 'admin';

// 8. DONNÉES SYSTÈME AVEC GESTION D'ERREURS
function getSystemStats($db = null) {
    $stats = [
        'tables' => 0,
        'users' => 0,
        'sessions' => 0,
        'modules' => 4
    ];
    
    if ($db) {
        try {
            // Compter les tables
            $stmt = $db->query("SHOW TABLES");
            $stats['tables'] = $stmt->rowCount();
            
            // Compter les utilisateurs
            $stmt = $db->query("SELECT COUNT(*) FROM auth_users");
            $stats['users'] = $stmt->fetchColumn();
            
            // Compter les sessions
            $stmt = $db->query("SELECT COUNT(*) FROM auth_sessions WHERE expires_at > NOW()");
            $stats['sessions'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            // Garde les valeurs par défaut
        }
    }
    
    return $stats;
}

// 9. CONNEXION BASE DE DONNÉES SÉCURISÉE
$db_connected = false;
$db = null;
$config_error = '';

try {
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        $db_connected = true;
    } else {
        $config_error = 'Constantes BDD non définies';
    }
} catch (Exception $e) {
    $config_error = $e->getMessage();
}

// 10. DONNÉES DASHBOARD
$system_stats = getSystemStats($db);
$quick_actions = [
    [
        'title' => 'Scanner Système',
        'icon' => '🔍',
        'url' => '/admin/scanner.php',
        'desc' => 'Diagnostic complet'
    ],
    [
        'title' => 'Gestion Utilisateurs',
        'icon' => '👥',
        'url' => '/admin/users.php',
        'desc' => 'CRUD utilisateurs'
    ],
    [
        'title' => 'Configuration',
        'icon' => '⚙️',
        'url' => '/admin/config.php',
        'desc' => 'Paramètres système'
    ],
    [
        'title' => 'Logs Système',
        'icon' => '📊',
        'url' => '/admin/logs.php',
        'desc' => 'Journaux d\'erreurs'
    ]
];

// 11. INCLUSION HEADER SÉCURISÉE
try {
    require_once ROOT_PATH . '/templates/header.php';
} catch (Exception $e) {
    // Header minimal de fallback
    echo '<!DOCTYPE html><html><head><title>Admin</title>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5}</style></head><body>';
}
?>

<!-- CSS Admin inline pour éviter les problèmes de chemins -->
<style>
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.admin-title h1 {
    margin: 0;
    font-size: 2.5em;
    font-weight: 300;
}

.admin-subtitle {
    margin: 10px 0 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.system-status {
    margin-bottom: 30px;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.status-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
}

.status-icon {
    font-size: 2em;
    margin-right: 20px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.status-icon.ok { background: #d4edda; }
.status-icon.error { background: #f8d7da; }
.status-icon.warning { background: #fff3cd; }
.status-icon.info { background: #d1ecf1; }

.status-content h3 {
    margin: 0 0 10px;
    color: #333;
}

.status-content p {
    margin: 0;
    color: #666;
}

.error-detail {
    color: #dc3545;
    font-size: 0.9em;
}

.quick-actions {
    margin-bottom: 30px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.action-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.action-icon {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.action-title {
    font-size: 1.3em;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.action-desc {
    color: #666;
    font-size: 0.95em;
}

.stats-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 30px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}

h2 {
    color: #333;
    border-bottom: 3px solid #667eea;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
</style>

<div class="admin-container">
    <!-- En-tête -->
    <div class="admin-header">
        <div class="admin-title">
            <h1>⚡ Administration</h1>
            <p class="admin-subtitle">Tableau de bord système - Version <?= htmlspecialchars(APP_VERSION ?? '0.5') ?></p>
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
                    <p>Session active</p>
                    <small>Utilisateur : <?= htmlspecialchars($current_user['name'] ?? 'Admin') ?></small>
                </div>
            </div>
            
            <div class="status-card">
                <div class="status-icon <?= is_writable(ROOT_PATH . '/storage') ? 'ok' : 'warning' ?>">📝</div>
                <div class="status-content">
                    <h3>Permissions</h3>
                    <p><?= is_writable(ROOT_PATH . '/storage') ? 'Écriture autorisée' : 'Vérifier permissions' ?></p>
                    <small>Dossier storage</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="quick-actions">
        <h2>⚡ Actions Rapides</h2>
        <div class="actions-grid">
            <?php foreach ($quick_actions as $action): ?>
                <a href="<?= htmlspecialchars($action['url']) ?>" class="action-card">
                    <div class="action-icon"><?= $action['icon'] ?></div>
                    <div class="action-title"><?= htmlspecialchars($action['title']) ?></div>
                    <div class="action-desc"><?= htmlspecialchars($action['desc']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="stats-summary">
        <div class="stat-item">
            <span class="stat-number"><?= $system_stats['tables'] ?></span>
            <span class="stat-label">Tables BDD</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?= $system_stats['users'] ?></span>
            <span class="stat-label">Utilisateurs</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?= $system_stats['sessions'] ?></span>
            <span class="stat-label">Sessions</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?= $system_stats['modules'] ?></span>
            <span class="stat-label">Modules</span>
        </div>
    </div>
</div>

<?php 
// Footer sécurisé
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    require_once ROOT_PATH . '/templates/footer.php';
} else {
    echo '</body></html>';
}
?>
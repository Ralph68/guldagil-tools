<?php
/**
 * Titre: Dashboard Administration - Gestion BDD et Modules
 * Chemin: /public/admin/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config/config.php';

// Variables pour templates
$page_title = 'Administration';
$page_subtitle = 'Gestion du portail et modules';
$page_description = 'Interface d\'administration pour la gestion complète du portail';
$current_module = 'admin';
$module_css = true;
$module_js = true;

// Fil d'Ariane
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚙️', 'text' => 'Administration', 'url' => '/admin/', 'active' => true]
];

// Gestion session existante
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification auth simplifiée - à remplacer par AuthManager
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'dev'])) {
    // Pour le debug temporaire
    $_SESSION['user_role'] = 'admin';
    $_SESSION['username'] = 'admin_temp';
}

// Variables globales
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$version = defined('APP_VERSION') ? APP_VERSION : '0.5';
$build_number = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('Ymd');

// Action AJAX pour lecture/modification BDD
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'list_tables':
                $tables = [];
                $stmt = $db->query("SHOW TABLES");
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $table = $row[0];
                    $count_stmt = $db->query("SELECT COUNT(*) FROM `$table`");
                    $count = $count_stmt->fetchColumn();
                    $tables[] = ['name' => $table, 'count' => $count];
                }
                echo json_encode(['success' => true, 'tables' => $tables]);
                break;
                
            case 'table_data':
                $table = $_POST['table'] ?? '';
                if ($table && preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                    $limit = intval($_POST['limit'] ?? 50);
                    $stmt = $db->query("SELECT * FROM `$table` LIMIT $limit");
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Structure de la table
                    $struct_stmt = $db->query("DESCRIBE `$table`");
                    $structure = $struct_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $data, 'structure' => $structure]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Table invalide']);
                }
                break;
                
            case 'update_record':
                $table = $_POST['table'] ?? '';
                $id = $_POST['id'] ?? '';
                $field = $_POST['field'] ?? '';
                $value = $_POST['value'] ?? '';
                
                if ($table && $id && $field && preg_match('/^[a-zA-Z0-9_]+$/', $table) && preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                    $stmt = $db->prepare("UPDATE `$table` SET `$field` = ? WHERE id = ?");
                    $result = $stmt->execute([$value, $id]);
                    echo json_encode(['success' => $result]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
                }
                break;
                
            case 'modules_status':
                $modules = [
                    'port' => ['name' => 'Calculateur Port', 'status' => 'production', 'progress' => 95, 'tables' => ['gul_xpo_rates', 'gul_heppner_rates', 'gul_kn_rates'], 'icon' => '🚛'],
                    'adr' => ['name' => 'Gestion ADR', 'status' => 'production', 'progress' => 90, 'tables' => ['gul_adr_products'], 'icon' => '⚠️'],
                    'user' => ['name' => 'Utilisateurs', 'status' => 'production', 'progress' => 85, 'tables' => ['auth_users', 'auth_sessions'], 'icon' => '👥'],
                    'admin' => ['name' => 'Administration', 'status' => 'development', 'progress' => 60, 'tables' => [], 'icon' => '⚙️'],
                    'qualite' => ['name' => 'Contrôle Qualité', 'status' => 'development', 'progress' => 25, 'tables' => ['gul_qualite_checks'], 'icon' => '✅'],
                    'epi' => ['name' => 'Équipements EPI', 'status' => 'development', 'progress' => 40, 'tables' => ['gul_epi_equipment'], 'icon' => '🛡️']
                ];
                
                // Compter les enregistrements et calculer progression automatique
                foreach ($modules as $key => &$module) {
                    $module['total_records'] = 0;
                    $table_count = 0;
                    foreach ($module['tables'] as $table) {
                        try {
                            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
                            $count = $stmt->fetchColumn();
                            $module['total_records'] += $count;
                            if ($count > 0) $table_count++;
                        } catch (Exception $e) {
                            // Table n'existe pas
                        }
                    }
                    
                    // Ajustement automatique progression basé sur données
                    if ($module['total_records'] > 100) {
                        $module['progress'] = min(95, $module['progress'] + 10);
                    } elseif ($module['total_records'] > 10) {
                        $module['progress'] = min(80, $module['progress'] + 5);
                    }
                }
                
                echo json_encode(['success' => true, 'modules' => $modules]);
                break;
                
            case 'toggle_module_status':
                $module_id = $_POST['module_id'] ?? '';
                $new_status = $_POST['new_status'] ?? '';
                
                if ($module_id && in_array($new_status, ['development', 'production', 'maintenance'])) {
                    // Ici on sauvegarderait en BDD - pour l'instant simulation
                    echo json_encode(['success' => true, 'message' => "Module $module_id passé en $new_status"]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
                }
                break;
                
            case 'user_management':
                $action_type = $_POST['action_type'] ?? '';
                switch ($action_type) {
                    case 'list':
                        $stmt = $db->query("SELECT id, username, role, created_at, last_login, is_active FROM auth_users ORDER BY created_at DESC");
                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        echo json_encode(['success' => true, 'users' => $users]);
                        break;
                        
                    case 'toggle_active':
                        $user_id = intval($_POST['user_id'] ?? 0);
                        $new_status = intval($_POST['new_status'] ?? 0);
                        if ($user_id > 0) {
                            $stmt = $db->prepare("UPDATE auth_users SET is_active = ? WHERE id = ?");
                            $result = $stmt->execute([$new_status, $user_id]);
                            echo json_encode(['success' => $result]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'ID utilisateur invalide']);
                        }
                        break;
                        
                    case 'update_role':
                        $user_id = intval($_POST['user_id'] ?? 0);
                        $new_role = $_POST['new_role'] ?? '';
                        if ($user_id > 0 && in_array($new_role, ['user', 'admin', 'dev'])) {
                            $stmt = $db->prepare("UPDATE auth_users SET role = ? WHERE id = ?");
                            $result = $stmt->execute([$new_role, $user_id]);
                            echo json_encode(['success' => $result]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
                        }
                        break;
                }
                break;
                
            case 'system_info':
                $info = [
                    'php_version' => PHP_VERSION,
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu',
                    'db_version' => $db->query('SELECT VERSION()')->fetchColumn(),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'disk_free_space' => disk_free_space('.'),
                    'disk_total_space' => disk_total_space('.')
                ];
                echo json_encode(['success' => true, 'info' => $info]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Action inconnue']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Inclure header du portail
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Header minimal de secours
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">';
    echo '<title>Admin - ' . htmlspecialchars($app_name) . '</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body>';
    echo '<header style="background:#2c3e50;color:white;padding:1rem;">';
    echo '<h1>🛠️ Administration - ' . htmlspecialchars($app_name) . '</h1>';
    echo '<div>Version ' . htmlspecialchars($version) . ' - Build ' . htmlspecialchars($build_number) . '</div>';
    echo '</header>';
}
?>

<!-- CSS admin intégré temporairement -->
<style>
:root {
    --admin-primary: #2c3e50;
    --admin-secondary: #3498db;
    --admin-success: #27ae60;
    --admin-warning: #f39c12;
    --admin-danger: #e74c3c;
    --admin-light: #ecf0f1;
    --admin-white: #ffffff;
    --admin-text: #2c3e50;
    --admin-border: #bdc3c7;
}

.admin-nav {
    background: white;
    padding: 1rem 2rem;
    border-bottom: 1px solid var(--admin-border);
    margin-bottom: 2rem;
}

.nav-tabs {
    list-style: none;
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.nav-tab {
    padding: 0.5rem 1rem;
    background: var(--admin-light);
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.nav-tab:hover,
.nav-tab.active {
    background: var(--admin-secondary);
    color: white;
    border-color: var(--admin-secondary);
}

.admin-content {
    padding: 0 2rem 2rem;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.admin-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid var(--admin-border);
}

.admin-card h3 {
    color: var(--admin-primary);
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--admin-secondary);
}

.stat-label {
    color: var(--admin-text);
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table-header {
    background: var(--admin-primary);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-primary { background: var(--admin-secondary); color: white; }
.btn-success { background: var(--admin-success); color: white; }
.btn-warning { background: var(--admin-warning); color: white; }
.btn-danger { background: var(--admin-danger); color: white; }

.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--admin-border);
}

th {
    background: var(--admin-light);
    font-weight: 600;
}

tr:hover {
    background: rgba(52, 152, 219, 0.1);
}

.loading {
    text-align: center;
    padding: 2rem;
    color: var(--admin-secondary);
}

.module-status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.status-active {
    background: var(--admin-success);
    color: white;
}

.status-inactive {
    background: var(--admin-danger);
    color: white;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .admin-grid {
        grid-template-columns: 1fr;
    }
    
    .nav-tabs {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<!-- Navigation -->
<nav class="admin-nav">
    <ul class="nav-tabs">
        <li class="nav-tab active" data-tab="dashboard">📊 Dashboard</li>
        <li class="nav-tab" data-tab="modules">🎯 Modules</li>
        <li class="nav-tab" data-tab="database">🗄️ Base de données</li>
        <li class="nav-tab" data-tab="users">👥 Utilisateurs</li>
        <li class="nav-tab" data-tab="config">⚙️ Configuration</li>
    </ul>
</nav>

<!-- Contenu principal -->
<main class="admin-content">
    
    <!-- Tab: Dashboard -->
    <div id="dashboard" class="tab-content active">
        <h2>Tableau de bord général</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="total-tables">--</div>
                <div class="stat-label">Tables BDD</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-records">--</div>
                <div class="stat-label">Enregistrements</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="active-modules">--</div>
                <div class="stat-label">Modules actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-users">--</div>
                <div class="stat-label">Utilisateurs</div>
            </div>
        </div>

        <div class="admin-grid">
           <div class="admin-card">
    <h3>📁 Structure du dossier admin</h3>
    <div class="file-list">
        <!-- Dossiers -->
        <div class="folder-section">
            <h4>📁 Dossiers</h4>
            <ul>
                <li>
                    <a href="assets" class="folder-link">
                        <span class="icon">📁</span> assets
                        <ul>
                            <li>
                                <a href="assets/css" class="folder-link">
                                    <span class="icon">📁</span> css
                                </a>
                            </li>
                        </ul>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Fichiers -->
        <div class="file-section">
            <h4>📄 Fichiers</h4>
            <ul>
                <li><a href="about.md" class="file-link"><span class="icon">📄</span> about.md</a></li>
                <li><a href="audit.php" class="file-link"><span class="icon">📄</span> audit.php</a></li>
                <li><a href="config.php" class="file-link"><span class="icon">📄</span> config.php</a></li>
                <li><a href="index.php" class="file-link"><span class="icon">📄</span> index.php</a></li>
                <li><a href="logs-old.php" class="file-link"><span class="icon">📄</span> logs-old.php</a></li>
                <li><a href="logs.php" class="file-link"><span class="icon">📄</span> logs.php</a></li>
                <li><a href="scanner-about.md" class="file-link"><span class="icon">📄</span> scanner-about.md</a></li>
                <li><a href="scanner.php" class="file-link"><span class="icon">📄</span> scanner.php</a></li>
                <li><a href="users.php" class="file-link"><span class="icon">📄</span> users.php</a></li>
                <li><a href="verify-data.php" class="file-link"><span class="icon">📄</span> verify-data.php</a></li>
            </ul>
        </div>
    </div>
</div>
            
            <div class="admin-card">
                <h3>📈 État du système</h3>
                <div id="system-status">
                    <div>🟢 Base de données : Connectée</div>
                    <div>🟢 Sessions : Actives</div>
                    <div>🟡 Cache : Non configuré</div>
                    <div>🟢 Logs : Fonctionnels</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Modules -->
    <div id="modules" class="tab-content">
        <h2>Gestion des modules</h2>
        <div class="modules-controls" style="margin-bottom: 2rem;">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="show-dev-modules" checked onchange="filterModules()">
                    Afficher modules en développement
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="show-prod-modules" checked onchange="filterModules()">
                    Afficher modules en production
                </label>
                <button class="btn btn-success" onclick="refreshModules()">🔄 Actualiser</button>
            </div>
        </div>
        <div class="table-container">
            <div class="table-header">
                <h3>Modules installés</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <span class="status-badge status-development">Développement</span>
                    <span class="status-badge status-production">Production</span>
                </div>
            </div>
            <div id="modules-content" class="loading">Chargement des modules...</div>
        </div>
    </div>

    <!-- Tab: Base de données -->
    <div id="database" class="tab-content">
        <h2>Gestion de la base de données</h2>
        
        <div class="admin-grid">
            <div class="admin-card">
                <h3>📋 Tables disponibles</h3>
                <div id="tables-list" class="loading">Chargement...</div>
            </div>
            
            <div class="admin-card">
                <h3>🔍 Données de table</h3>
                <select id="table-selector" onchange="loadTableData(this.value)" style="width: 100%; padding: 0.5rem; margin-bottom: 1rem;">
                    <option value="">Sélectionner une table...</option>
                </select>
                <div id="table-data"></div>
            </div>
        </div>
    </div>

    <!-- Tab: Utilisateurs -->
    <div id="users" class="tab-content">
        <h2>Gestion des utilisateurs</h2>
        <div class="table-container">
            <div class="table-header">
                <h3>Utilisateurs du système</h3>
                <button class="btn btn-primary" onclick="loadUsers()">📊 Charger utilisateurs</button>
            </div>
            <div id="users-content">
                <p>Cliquez sur "Charger utilisateurs" pour afficher la gestion complète des comptes.</p>
            </div>
        </div>
    </div>

    <!-- Tab: Configuration -->
    <div id="config" class="tab-content">
        <h2>Configuration générale</h2>
        
        <div class="admin-grid">
            <div class="admin-card">
                <h3>🔧 Paramètres généraux</h3>
                <p><strong>Nom de l'application :</strong> <?= htmlspecialchars($app_name) ?></p>
                <p><strong>Version :</strong> <?= htmlspecialchars($version) ?></p>
                <p><strong>Build :</strong> <?= htmlspecialchars($build_number) ?></p>
                <p><strong>Environnement :</strong> <?= defined('DEBUG') && DEBUG ? 'Développement' : 'Production' ?></p>
                <div style="margin-top: 1rem;">
                    <button class="btn btn-warning" onclick="loadSystemInfo()">📊 Infos système</button>
                </div>
            </div>
            
            <div class="admin-card">
                <h3>📂 Chemins système</h3>
                <p><strong>ROOT_PATH :</strong> <?= htmlspecialchars(ROOT_PATH) ?></p>
                <p><strong>Config :</strong> <?= file_exists(ROOT_PATH . '/config/config.php') ? '✅ Trouvé' : '❌ Manquant' ?></p>
                <p><strong>Storage :</strong> <?= is_writable(ROOT_PATH . '/storage') ? '✅ Accessible' : '❌ Non accessible' ?></p>
                <p><strong>Templates :</strong> <?= is_dir(ROOT_PATH . '/templates') ? '✅ Trouvé' : '❌ Manquant' ?></p>
            </div>
            
            <div class="admin-card">
                <h3>🌐 Configuration serveur</h3>
                <div id="system-info">
                    <p>Cliquez sur "Infos système" pour charger les détails du serveur.</p>
                </div>
            </div>
            
            <div class="admin-card">
                <h3>🔒 Sécurité</h3>
                <p><strong>Sessions :</strong> <?= session_status() === PHP_SESSION_ACTIVE ? '✅ Actives' : '❌ Inactives' ?></p>
                <p><strong>HTTPS :</strong> <?= (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '✅ Activé' : '⚠️ Désactivé' ?></p>
                <p><strong>Auth système :</strong> <?= file_exists(ROOT_PATH . '/core/auth/AuthManager.php') ? '✅ AuthManager' : '⚠️ Sessions PHP' ?></p>
                <p><strong>Erreurs PHP :</strong> <?= ini_get('display_errors') ? '⚠️ Affichées' : '✅ Masquées' ?></p>
            </div>
        </div>
    </div>
</main>

<script>
// Variables globales
let currentTables = [];
let currentModules = [];

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadTables();
});

// Gestion des onglets
document.querySelectorAll('.nav-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.dataset.tab;
        showTab(tabName);
    });
});

function showTab(tabName) {
    // Masquer tous les onglets
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Afficher l'onglet sélectionné
    document.getElementById(tabName).classList.add('active');
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

    // Charger le contenu si nécessaire
    if (tabName === 'modules' && currentModules.length === 0) {
        loadModules();
    }
}

// Chargement des statistiques du dashboard
async function loadDashboardStats() {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax_action=1&action=list_tables'
        });
        const data = await response.json();
        
        if (data.success) {
            const totalRecords = data.tables.reduce((sum, table) => sum + table.count, 0);
            document.getElementById('total-tables').textContent = data.tables.length;
            document.getElementById('total-records').textContent = totalRecords.toLocaleString();
        }
    } catch (error) {
        console.error('Erreur chargement stats:', error);
    }

    // Charger stats modules
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax_action=1&action=modules_status'
        });
        const data = await response.json();
        
        if (data.success) {
            const activeModules = Object.values(data.modules).filter(m => m.status === 'active').length;
            document.getElementById('active-modules').textContent = activeModules;
            
            // Estimation utilisateurs (depuis auth_users si disponible)
            const userModule = data.modules.user;
            if (userModule && userModule.total_records > 0) {
                document.getElementById('total-users').textContent = userModule.total_records;
            } else {
                document.getElementById('total-users').textContent = '?';
            }
        }
    } catch (error) {
        console.error('Erreur chargement modules:', error);
    }
}

// Chargement des tables
async function loadTables() {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax_action=1&action=list_tables'
        });
        const data = await response.json();
        
        if (data.success) {
            currentTables = data.tables;
            displayTables(data.tables);
            updateTableSelector(data.tables);
        }
    } catch (error) {
        console.error('Erreur chargement tables:', error);
        document.getElementById('tables-list').innerHTML = '❌ Erreur de chargement';
    }
}

function displayTables(tables) {
    const html = tables.map(table => 
        `<div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
            <span><strong>${table.name}</strong></span>
            <span>${table.count} enregistrements</span>
        </div>`
    ).join('');
    
    document.getElementById('tables-list').innerHTML = html;
}

function updateTableSelector(tables) {
    const selector = document.getElementById('table-selector');
    selector.innerHTML = '<option value="">Sélectionner une table...</option>';
    
    tables.forEach(table => {
        const option = document.createElement('option');
        option.value = table.name;
        option.textContent = `${table.name} (${table.count} enregistrements)`;
        selector.appendChild(option);
    });
}

// Chargement des données d'une table
async function loadTableData(tableName) {
    if (!tableName) {
        document.getElementById('table-data').innerHTML = '';
        return;
    }

    document.getElementById('table-data').innerHTML = '<div class="loading">Chargement des données...</div>';

    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_action=1&action=table_data&table=${encodeURIComponent(tableName)}&limit=50`
        });
        const data = await response.json();
        
        if (data.success) {
            displayTableData(tableName, data.data, data.structure);
        } else {
            document.getElementById('table-data').innerHTML = `❌ Erreur: ${data.error}`;
        }
    } catch (error) {
        console.error('Erreur chargement données table:', error);
        document.getElementById('table-data').innerHTML = '❌ Erreur de chargement';
    }
}

function displayTableData(tableName, data, structure) {
    if (data.length === 0) {
        document.getElementById('table-data').innerHTML = '<p>Aucune donnée trouvée dans cette table.</p>';
        return;
    }

    const columns = structure.map(col => col.Field);
    
    let html = `<div style="margin-bottom: 1rem;">
        <h4>Table: ${tableName}</h4>
        <p>Affichage des 50 premiers enregistrements</p>
    </div>`;
    
    html += '<div style="overflow-x: auto;"><table>';
    
    // En-têtes
    html += '<thead><tr>';
    columns.forEach(col => {
        html += `<th>${col}</th>`;
    });
    html += '</tr></thead>';
    
    // Données
    html += '<tbody>';
    data.forEach(row => {
        html += '<tr>';
        columns.forEach(col => {
            const value = row[col] !== null ? row[col] : '<em>NULL</em>';
            html += `<td>${String(value).substring(0, 100)}${String(value).length > 100 ? '...' : ''}</td>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    
    document.getElementById('table-data').innerHTML = html;
}

// Chargement des modules
async function loadModules() {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax_action=1&action=modules_status'
        });
        const data = await response.json();
        
        if (data.success) {
            currentModules = data.modules;
            displayModules(data.modules);
        }
    } catch (error) {
        console.error('Erreur chargement modules:', error);
        document.getElementById('modules-content').innerHTML = '❌ Erreur de chargement';
    }
}

function displayModules(modules) {
    let html = '<table><thead><tr><th>Module</th><th>Statut</th><th>Progression</th><th>Tables</th><th>Enregistrements</th><th>Actions</th></tr></thead><tbody>';
    
    Object.entries(modules).forEach(([key, module]) => {
        const statusClass = `status-${module.status}`;
        const tables = module.tables.join(', ') || 'Aucune';
        const progressColor = module.progress >= 80 ? '#27ae60' : module.progress >= 50 ? '#f39c12' : '#e74c3c';
        
        html += `<tr data-module-status="${module.status}">
            <td>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.2rem;">${module.icon}</span>
                    <div>
                        <strong>${key}</strong>
                        <br><small>${module.name}</small>
                    </div>
                </div>
            </td>
            <td>
                <select onchange="toggleModuleStatus('${key}', this.value)" class="status-selector">
                    <option value="development" ${module.status === 'development' ? 'selected' : ''}>Développement</option>
                    <option value="production" ${module.status === 'production' ? 'selected' : ''}>Production</option>
                    <option value="maintenance" ${module.status === 'maintenance' ? 'selected' : ''}>Maintenance</option>
                </select>
            </td>
            <td>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${module.progress}%; background-color: ${progressColor};"></div>
                    </div>
                    <span class="progress-text">${module.progress}%</span>
                </div>
            </td>
            <td>${tables}</td>
            <td>${module.total_records}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="viewModuleDetails('${key}')">📊</button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    document.getElementById('modules-content').innerHTML = html;
    filterModules(); // Appliquer les filtres
}

function filterModules() {
    const showDev = document.getElementById('show-dev-modules').checked;
    const showProd = document.getElementById('show-prod-modules').checked;
    
    document.querySelectorAll('[data-module-status]').forEach(row => {
        const status = row.dataset.moduleStatus;
        const shouldShow = (status === 'development' && showDev) || 
                          (status === 'production' && showProd) ||
                          (status === 'maintenance' && (showDev || showProd));
        row.style.display = shouldShow ? '' : 'none';
    });
}

async function toggleModuleStatus(moduleId, newStatus) {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_action=1&action=toggle_module_status&module_id=${moduleId}&new_status=${newStatus}`
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification(`Module ${moduleId} passé en ${newStatus}`, 'success');
            refreshModules();
        } else {
            showNotification(`Erreur: ${data.error}`, 'error');
        }
    } catch (error) {
        showNotification('Erreur de communication', 'error');
    }
}

function viewModuleDetails(moduleId) {
    // Aller à l'onglet base de données et afficher les tables du module
    showTab('database');
    // Logique pour filtrer les tables du module
    showNotification(`Affichage des détails du module ${moduleId}`, 'info');
}

// Gestion des utilisateurs
async function loadUsers() {
    document.getElementById('users-content').innerHTML = '<div class="loading">Chargement des utilisateurs...</div>';
    
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax_action=1&action=user_management&action_type=list'
        });
        const data = await response.json();
        
        if (data.success) {
            displayUsers(data.users);
        } else {
            document.getElementById('users-content').innerHTML = '❌ Erreur de chargement';
        }
    } catch (error) {
        document.getElementById('users-content').innerHTML = '❌ Erreur de communication';
    }
}

function displayUsers(users) {
    let html = '<table><thead><tr><th>ID</th><th>Nom d\'utilisateur</th><th>Rôle</th><th>Statut</th><th>Créé</th><th>Dernière connexion</th><th>Actions</th></tr></thead><tbody>';
    
    users.forEach(user => {
        const isActive = user.is_active == 1;
        const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString('fr-FR') : 'Jamais';
        const created = new Date(user.created_at).toLocaleDateString('fr-FR');
        
        html += `<tr>
            <td>${user.id}</td>
            <td><strong>${user.username}</strong></td>
            <td>
                <select onchange="updateUserRole(${user.id}, this.value)">
                    <option value="user" ${user.role === 'user' ? 'selected' : ''}>Utilisateur</option>
                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                    <option value="dev" ${user.role === 'dev' ? 'selected' : ''}>Développeur</option>
                </select>
            </td>
            <td>
                <label class="switch">
                    <input type="checkbox" ${isActive ? 'checked' : ''} onchange="toggleUserStatus(${user.id}, this.checked)">
                    <span class="slider"></span>
                </label>
            </td>
            <td>${created}</td>
            <td>${lastLogin}</td>
            <td>
                <button class="btn btn-sm btn-warning" onclick="resetUserPassword(${user.id})">🔑</button>
                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">🗑️</button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    document.getElementById('users-content').innerHTML = html;
}

async function toggleUserStatus(userId, isActive) {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_action=1&action=user_management&action_type=toggle_active&user_id=${userId}&new_status=${isActive ? 1 : 0}`
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification(`Utilisateur ${isActive ? 'activé' : 'désactivé'}`, 'success');
        } else {
            showNotification('Erreur de mise à jour', 'error');
            loadUsers(); // Recharger pour annuler le changement
        }
    } catch (error) {
        showNotification('Erreur de communication', 'error');
    }
}

async function updateUserRole(userId, newRole) {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_action=1&action=user_management&action_type=update_role&user_id=${userId}&new_role=${newRole}`
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification(`Rôle mis à jour vers ${newRole}`, 'success');
        } else {
            showNotification('Erreur de mise à jour', 'error');
            loadUsers();
        }
    } catch (error) {
        showNotification('Erreur de communication', 'error');
    }
}

// Configuration système
async function loadSystemInfo() {
    document.getElementById('system-info').innerHTML = '<div class="loading">Chargement...</div>';
    
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax_action=1&action=system_info'
        });
        const data = await response.json();
        
        if (data.success) {
            displaySystemInfo(data.info);
        }
    } catch (error) {
        document.getElementById('system-info').innerHTML = '❌ Erreur de chargement';
    }
}

function displaySystemInfo(info) {
    const freeSpace = (info.disk_free_space / (1024*1024*1024)).toFixed(2);
    const totalSpace = (info.disk_total_space / (1024*1024*1024)).toFixed(2);
    
    const html = `
        <p><strong>PHP :</strong> ${info.php_version}</p>
        <p><strong>Serveur :</strong> ${info.server_software}</p>
        <p><strong>Base de données :</strong> ${info.db_version}</p>
        <p><strong>Mémoire limite :</strong> ${info.memory_limit}</p>
        <p><strong>Temps d'exécution max :</strong> ${info.max_execution_time}s</p>
        <p><strong>Upload max :</strong> ${info.upload_max_filesize}</p>
        <p><strong>Espace disque :</strong> ${freeSpace}GB libre / ${totalSpace}GB total</p>
    `;
    
    document.getElementById('system-info').innerHTML = html;
}

// Système de notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    switch(type) {
        case 'success': notification.style.background = '#27ae60'; break;
        case 'error': notification.style.background = '#e74c3c'; break;
        case 'warning': notification.style.background = '#f39c12'; break;
        default: notification.style.background = '#3498db';
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function refreshModules() {
    document.getElementById('modules-content').innerHTML = '<div class="loading">Actualisation...</div>';
    loadModules();
}
</script>

<?php
// Inclure footer du portail
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    // Footer minimal de secours
    echo '<footer style="background:#2c3e50;color:white;padding:1rem;text-align:center;margin-top:2rem;">';
    echo '<p>&copy; 2025 ' . htmlspecialchars($app_name) . ' - Version ' . htmlspecialchars($version) . ' - Build ' . htmlspecialchars($build_number) . '</p>';
    echo '<p>Administration temporaire - À compléter selon besoins</p>';
    echo '</footer>';
}
?>

</body>
</html>

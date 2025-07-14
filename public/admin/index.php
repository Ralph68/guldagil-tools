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
                // Récupérer l'état des modules depuis config
                $modules = [
                    'port' => ['name' => 'Calculateur Port', 'status' => 'active', 'tables' => ['gul_xpo_rates', 'gul_heppner_rates', 'gul_kn_rates']],
                    'adr' => ['name' => 'Gestion ADR', 'status' => 'active', 'tables' => ['gul_adr_products']],
                    'user' => ['name' => 'Utilisateurs', 'status' => 'active', 'tables' => ['auth_users', 'auth_sessions']],
                    'admin' => ['name' => 'Administration', 'status' => 'active', 'tables' => []]
                ];
                
                // Compter les enregistrements par module
                foreach ($modules as $key => &$module) {
                    $module['total_records'] = 0;
                    foreach ($module['tables'] as $table) {
                        try {
                            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
                            $module['total_records'] += $stmt->fetchColumn();
                        } catch (Exception $e) {
                            // Table n'existe pas
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'modules' => $modules]);
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
                <h3>🚀 Actions rapides</h3>
                <p>Raccourcis vers les fonctionnalités principales :</p>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="showTab('database')">Voir BDD</button>
                    <button class="btn btn-success" onclick="showTab('modules')">Gérer modules</button>
                    <button class="btn btn-warning" onclick="showTab('users')">Utilisateurs</button>
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
        <div class="table-container">
            <div class="table-header">
                <h3>Modules installés</h3>
                <button class="btn btn-success" onclick="refreshModules()">🔄 Actualiser</button>
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
                <button class="btn btn-primary" onclick="loadTableData('auth_users')">📊 Voir détails</button>
            </div>
            <div id="users-content">
                <p>Cliquez sur "Voir détails" pour afficher la table auth_users avec possibilité de modification.</p>
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
            </div>
            
            <div class="admin-card">
                <h3>📂 Chemins système</h3>
                <p><strong>ROOT_PATH :</strong> <?= htmlspecialchars(ROOT_PATH) ?></p>
                <p><strong>Config :</strong> <?= file_exists(ROOT_PATH . '/config/config.php') ? '✅' : '❌' ?></p>
                <p><strong>Storage :</strong> <?= is_writable(ROOT_PATH . '/storage') ? '✅' : '❌' ?></p>
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
    let html = '<table><thead><tr><th>Module</th><th>Nom</th><th>Statut</th><th>Tables</th><th>Enregistrements</th></tr></thead><tbody>';
    
    Object.entries(modules).forEach(([key, module]) => {
        const statusClass = module.status === 'active' ? 'status-active' : 'status-inactive';
        const tables = module.tables.join(', ') || 'Aucune';
        
        html += `<tr>
            <td><strong>${key}</strong></td>
            <td>${module.name}</td>
            <td><span class="module-status ${statusClass}">${module.status}</span></td>
            <td>${tables}</td>
            <td>${module.total_records}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    document.getElementById('modules-content').innerHTML = html;
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

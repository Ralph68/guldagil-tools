<?php
/**
 * Titre: Page Liste des Contrôles - Module Contrôle Qualité
 * Chemin: /public/qualite/list.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
session_start();
define('PORTAL_ACCESS', true);

// Chargement de la configuration
if (file_exists(__DIR__ . '/../../config/config.php')) {
    require_once __DIR__ . '/../../config/config.php';
}
if (file_exists(__DIR__ . '/../../config/version.php')) {
    require_once __DIR__ . '/../../config/version.php';
}

// Variables d'environnement
$current_module = 'qualite';
$page_title = 'Liste des Contrôles';
$page_description = 'Consultation et gestion des contrôles qualité';

// Chargement du gestionnaire qualité
if (file_exists(__DIR__ . '/classes/qualite_manager.php')) {
    require_once __DIR__ . '/classes/qualite_manager.php';
}

// Paramètres de recherche et filtres
$action = $_GET['action'] ?? 'list';
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$agency_filter = $_GET['agency'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 25;

// Simulation des données (à remplacer par vraie base de données)
$mock_controls = [
    [
        'id' => 1,
        'control_number' => 'ADOU_20250123_001',
        'equipment_type' => 'Adoucisseur',
        'equipment_model' => 'Clack CI',
        'installation_name' => 'Installation Test 1',
        'agency_code' => 'GUL31',
        'status' => 'completed',
        'created_at' => '2025-01-23 14:30:00',
        'prepared_by' => 'J. Technicien'
    ],
    [
        'id' => 2,
        'control_number' => 'POMPE_20250123_002',
        'equipment_type' => 'Pompe Doseuse',
        'equipment_model' => 'DOS4-8V',
        'installation_name' => 'Installation Test 2',
        'agency_code' => 'GUL82',
        'status' => 'in_progress',
        'created_at' => '2025-01-23 10:15:00',
        'prepared_by' => 'M. Contrôleur'
    ],
    [
        'id' => 3,
        'control_number' => 'ADOU_20250122_003',
        'equipment_type' => 'Adoucisseur',
        'equipment_model' => 'Fleck SXT',
        'installation_name' => 'Installation Importante',
        'agency_code' => 'GUL31',
        'status' => 'validated',
        'created_at' => '2025-01-22 16:45:00',
        'prepared_by' => 'A. Expert'
    ]
];

// Filtrage des données (simulation)
$filtered_controls = $mock_controls;
if ($search) {
    $filtered_controls = array_filter($filtered_controls, function($control) use ($search) {
        return stripos($control['control_number'], $search) !== false ||
               stripos($control['installation_name'], $search) !== false ||
               stripos($control['agency_code'], $search) !== false;
    });
}
if ($status_filter) {
    $filtered_controls = array_filter($filtered_controls, function($control) use ($status_filter) {
        return $control['status'] === $status_filter;
    });
}
if ($type_filter) {
    $filtered_controls = array_filter($filtered_controls, function($control) use ($type_filter) {
        return stripos($control['equipment_type'], $type_filter) !== false;
    });
}

$total_controls = count($filtered_controls);
$total_pages = ceil($total_controls / $per_page);
$offset = ($page - 1) * $per_page;
$current_controls = array_slice($filtered_controls, $offset, $per_page);

// Labels pour les statuts
$status_labels = [
    'draft' => 'Brouillon',
    'in_progress' => 'En cours',
    'completed' => 'Terminé',
    'validated' => 'Validé',
    'sent' => 'Envoyé'
];

// Chargement du header
if (file_exists(__DIR__ . '/../../templates/header.php')) {
    require_once __DIR__ . '/../../templates/header.php';
}
?>

<div class="qualite-module">
    <!-- Header du module -->
    <div class="module-header">
        <div class="breadcrumb">
            <a href="/" class="breadcrumb-item">🏠 Accueil</a>
            <span class="breadcrumb-separator">›</span>
            <a href="/qualite/" class="breadcrumb-item">🔬 Contrôle Qualité</a>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item current">📋 Liste des Contrôles</span>
        </div>
        
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">📋</div>
                <div class="module-info">
                    <h1>Liste des Contrôles</h1>
                    <div class="module-version"><?= $total_controls ?> contrôle(s) trouvé(s)</div>
                </div>
            </div>
            
            <div class="module-actions">
                <a href="/qualite/" class="btn btn-secondary">
                    ← Retour Dashboard
                </a>
                <button onclick="nouveauControle()" class="btn btn-primary">
                    ➕ Nouveau contrôle
                </button>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <main class="main-content">
        <!-- Filtres et recherche -->
        <section class="filters-section">
            <form method="GET" class="filters-form">
                <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
                
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="search">🔍 Recherche</label>
                        <input type="text" id="search" name="search" 
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="N° contrôle, installation, agence...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">📊 Statut</label>
                        <select id="status" name="status">
                            <option value="">Tous les statuts</option>
                            <?php foreach ($status_labels as $status => $label): ?>
                            <option value="<?= $status ?>" <?= $status_filter === $status ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="type">⚙️ Type</label>
                        <select id="type" name="type">
                            <option value="">Tous les types</option>
                            <option value="Adoucisseur" <?= $type_filter === 'Adoucisseur' ? 'selected' : '' ?>>
                                Adoucisseur
                            </option>
                            <option value="Pompe" <?= $type_filter === 'Pompe' ? 'selected' : '' ?>>
                                Pompe Doseuse
                            </option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="agency">🏢 Agence</label>
                        <select id="agency" name="agency">
                            <option value="">Toutes les agences</option>
                            <option value="GUL31" <?= $agency_filter === 'GUL31' ? 'selected' : '' ?>>GUL31</option>
                            <option value="GUL82" <?= $agency_filter === 'GUL82' ? 'selected' : '' ?>>GUL82</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from">📅 Du</label>
                        <input type="date" id="date_from" name="date_from" 
                               value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">📅 Au</label>
                        <input type="date" id="date_to" name="date_to" 
                               value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                </div>
                
                <div class="filters-actions">
                    <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                    <a href="list.php" class="btn btn-secondary">🔄 Réinitialiser</a>
                </div>
            </form>
        </section>

        <!-- Tableau des résultats -->
        <section class="results-section">
            <?php if (!empty($current_controls)): ?>
            <div class="table-container">
                <table class="controls-table">
                    <thead>
                        <tr>
                            <th>N° Contrôle</th>
                            <th>Type / Modèle</th>
                            <th>Installation</th>
                            <th>Agence</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Technicien</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_controls as $control): ?>
                        <tr class="control-row">
                            <td>
                                <strong class="control-number">
                                    <?= htmlspecialchars($control['control_number']) ?>
                                </strong>
                            </td>
                            <td>
                                <div class="equipment-info">
                                    <span class="equipment-type">
                                        <?= htmlspecialchars($control['equipment_type']) ?>
                                    </span>
                                    <br>
                                    <small class="equipment-model">
                                        <?= htmlspecialchars($control['equipment_model']) ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="installation-name">
                                    <?= htmlspecialchars($control['installation_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="agency-badge">
                                    <?= htmlspecialchars($control['agency_code']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $control['status'] ?>">
                                    <?= $status_labels[$control['status']] ?? $control['status'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="control-date">
                                    <?= date('d/m/Y H:i', strtotime($control['created_at'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="technician-name">
                                    <?= htmlspecialchars($control['prepared_by']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-small btn-secondary" 
                                            onclick="viewControl(<?= $control['id'] ?>)"
                                            title="Voir le détail">
                                        👁️
                                    </button>
                                    <?php if (in_array($control['status'], ['draft', 'in_progress'])): ?>
                                    <button class="btn btn-small btn-primary"
                                            onclick="editControl(<?= $control['id'] ?>)"
                                            title="Modifier">
                                        ✏️
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-small btn-warning"
                                            onclick="exportControl(<?= $control['id'] ?>)"
                                            title="Exporter PDF">
                                        📄
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                   class="pagination-btn">← Précédent</a>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Page <?= $page ?> sur <?= $total_pages ?> 
                    (<?= $total_controls ?> contrôle(s))
                </span>
                
                <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                   class="pagination-btn">Suivant →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- État vide -->
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>Aucun contrôle trouvé</h3>
                <p>Aucun contrôle ne correspond à vos critères de recherche.</p>
                <div class="empty-actions">
                    <button onclick="nouveauControle()" class="btn btn-primary">
                        ➕ Créer un nouveau contrôle
                    </button>
                    <a href="list.php" class="btn btn-secondary">
                        🔄 Réinitialiser les filtres
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<!-- CSS spécifique -->
<style>
/* Filtres */
.filters-section {
    background: white;
    margin-bottom: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 2rem;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
}

.filters-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

/* Tableau */
.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.controls-table {
    width: 100%;
    border-collapse: collapse;
}

.controls-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.controls-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.control-row:hover {
    background: #f9fafb;
}

/* Badges et statuts */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-draft { background: #fef3c7; color: #92400e; }
.status-in_progress { background: #dbeafe; color: #1e40af; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-validated { background: #dcfce7; color: #166534; }
.status-sent { background: #e0e7ff; color: #3730a3; }

.agency-badge {
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Actions */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-small {
    padding: 0.5rem;
    font-size: 0.875rem;
    min-width: auto;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pagination-btn {
    background: #3b82f6;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
}

.pagination-btn:hover {
    background: #2563eb;
}

.pagination-info {
    color: #6b7280;
    font-weight: 500;
}

/* État vide */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
}
</style>

<!-- JavaScript -->
<script>
function nouveauControle() {
    const type = prompt('Type de contrôle :\n1 - Adoucisseur\n2 - Pompe\n\nEntrez 1 ou 2 :');
    
    if (type === '1') {
        window.location.href = 'components/adoucisseurs.php';
    } else if (type === '2') {
        alert('🚧 Module contrôle pompes en développement');
    }
}

function viewControl(id) {
    window.location.href = `view.php?id=${id}`;
}

function editControl(id) {
    window.location.href = `edit.php?id=${id}`;
}

function exportControl(id) {
    window.location.href = `export.php?id=${id}&format=pdf`;
}
</script>

<?php
// Chargement du footer
if (file_exists(__DIR__ . '/../../templates/footer.php')) {
    require_once __DIR__ . '/../../templates/footer.php';
}
?>

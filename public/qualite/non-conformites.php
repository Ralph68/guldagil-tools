/**
 * Titre: Gestion des non-conformités et actions correctives
 * Chemin: /public/qualite/non-conformites.php
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

$page_title = 'Non-Conformités';
$page_subtitle = 'Gestion et suivi des actions correctives';
$current_module = 'qualite';
$module_css = true;

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '✅', 'text' => 'Contrôle Qualité', 'url' => '/qualite/', 'active' => false],
    ['icon' => '🚨', 'text' => 'Non-Conformités', 'url' => '/qualite/non-conformites.php', 'active' => true]
];

// Auth temporaire
$user_authenticated = true;
$current_user = ['id' => 1, 'role' => 'logistique', 'name' => 'Utilisateur'];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Filtres
    $status_filter = $_GET['status'] ?? '';
    $priority_filter = $_GET['priority'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $responsible_filter = $_GET['responsible'] ?? '';
    
    // Actions
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status' && !empty($_POST['nc_id'])) {
        $nc_id = (int)$_POST['nc_id'];
        $new_status = $_POST['new_status'];
        $comments = $_POST['comments'] ?? '';
        
        $update_sql = "UPDATE cq_quality_controls SET 
                       status = ?, 
                       updated_at = NOW()
                       WHERE id = ? AND status = 'in_progress'";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([$new_status, $nc_id]);
        
        // Historique
        if ($stmt->rowCount() > 0) {
            $history_sql = "INSERT INTO cq_control_history (control_id, action, old_value, new_value, user_name) 
                           VALUES (?, 'modified', 'in_progress', ?, ?)";
            $pdo->prepare($history_sql)->execute([$nc_id, $new_status, $current_user['name']]);
        }
    }

    // Construction requête non-conformités
    $where_conditions = ["qc.status = 'in_progress'"];
    $params = [];

    if ($type_filter) {
        $where_conditions[] = "et.type_code = ?";
        $params[] = $type_filter;
    }
    
    if ($responsible_filter) {
        $where_conditions[] = "qc.prepared_by LIKE ?";
        $params[] = "%{$responsible_filter}%";
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Récupération non-conformités
    $nc_sql = "
        SELECT qc.*, et.type_name, et.type_code, em.model_name, 
               a.agency_name, a.email as agency_email,
               TIMESTAMPDIFF(DAY, qc.created_at, NOW()) as days_open,
               CASE 
                   WHEN TIMESTAMPDIFF(DAY, qc.created_at, NOW()) > 7 THEN 'high'
                   WHEN TIMESTAMPDIFF(DAY, qc.created_at, NOW()) > 3 THEN 'medium'
                   ELSE 'low'
               END as priority
        FROM cq_quality_controls qc
        JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
        LEFT JOIN cq_equipment_models em ON qc.equipment_model_id = em.id
        LEFT JOIN cq_agencies a ON qc.agency_code = a.agency_code
        WHERE $where_clause
        ORDER BY qc.created_at DESC
    ";
    
    $stmt = $pdo->prepare($nc_sql);
    $stmt->execute($params);
    $non_conformites = $stmt->fetchAll();

    // Statistiques NC
    $stats_sql = "
        SELECT 
            COUNT(*) as total_nc,
            COUNT(CASE WHEN TIMESTAMPDIFF(DAY, created_at, NOW()) > 7 THEN 1 END) as critical,
            COUNT(CASE WHEN TIMESTAMPDIFF(DAY, created_at, NOW()) BETWEEN 4 AND 7 THEN 1 END) as warning,
            COUNT(CASE WHEN TIMESTAMPDIFF(DAY, created_at, NOW()) <= 3 THEN 1 END) as recent,
            AVG(TIMESTAMPDIFF(DAY, created_at, NOW())) as avg_days_open
        FROM cq_quality_controls 
        WHERE status = 'in_progress'
    ";
    $nc_stats = $pdo->query($stats_sql)->fetch();

    // Causes fréquentes (analyse des observations)
    $causes_sql = "
        SELECT 
            et.type_name,
            COUNT(*) as count,
            GROUP_CONCAT(DISTINCT LEFT(qc.observations, 50) SEPARATOR '; ') as sample_issues
        FROM cq_quality_controls qc
        JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
        WHERE qc.status = 'in_progress'
        AND qc.observations IS NOT NULL
        AND qc.observations != ''
        GROUP BY et.id, et.type_name
        ORDER BY count DESC
        LIMIT 5
    ";
    $frequent_causes = $pdo->query($causes_sql)->fetchAll();

    // Types d'équipements pour filtre
    $types = $pdo->query("SELECT type_code, type_name FROM cq_equipment_types WHERE active = 1")->fetchAll();

} catch (Exception $e) {
    error_log("Erreur non-conformités: " . $e->getMessage());
    $non_conformites = [];
    $nc_stats = ['total_nc' => 0, 'critical' => 0, 'warning' => 0, 'recent' => 0, 'avg_days_open' => 0];
    $frequent_causes = [];
}

require_once ROOT_PATH . '/templates/header.php';
?>

<div class="qualite-module">
    <!-- Header -->
    <div class="module-header">
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">🚨</div>
                <div class="module-info">
                    <h1>Non-Conformités</h1>
                    <p class="module-version"><?= count($non_conformites) ?> non-conformité(s) active(s)</p>
                </div>
            </div>
            <div class="module-actions">
                <button class="btn btn-outline" onclick="toggleFilters()">
                    <span class="icon">🔍</span>
                    Filtres
                </button>
                <button class="btn btn-outline" onclick="exportNC()">
                    <span class="icon">📥</span>
                    Exporter
                </button>
                <button class="btn btn-primary" onclick="showBulkActions()">
                    <span class="icon">⚡</span>
                    Actions groupées
                </button>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- Filtres -->
        <div class="filters-panel" id="filters-panel" style="display: none;">
            <form method="GET" class="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="type">Type équipement</label>
                        <select name="type" id="type">
                            <option value="">Tous les types</option>
                            <?php foreach ($types as $type): ?>
                            <option value="<?= $type['type_code'] ?>" <?= $type_filter === $type['type_code'] ? 'selected' : '' ?>>
                                <?= $type['type_name'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="responsible">Responsable</label>
                        <input type="text" name="responsible" id="responsible" value="<?= htmlspecialchars($responsible_filter) ?>" 
                               placeholder="Nom du technicien...">
                    </div>
                </div>
                
                <div class="filters-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="/qualite/non-conformites.php" class="btn btn-outline">Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- Statistiques NC -->
        <div class="nc-stats-section">
            <div class="nc-stats-grid">
                <div class="stat-card critical">
                    <div class="stat-icon">🔴</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $nc_stats['critical'] ?></div>
                        <div class="stat-label">Critiques (>7j)</div>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">🟡</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $nc_stats['warning'] ?></div>
                        <div class="stat-label">Attention (4-7j)</div>
                    </div>
                </div>
                
                <div class="stat-card recent">
                    <div class="stat-icon">🟢</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $nc_stats['recent'] ?></div>
                        <div class="stat-label">Récentes (≤3j)</div>
                    </div>
                </div>
                
                <div class="stat-card average">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= round($nc_stats['avg_days_open'], 1) ?>j</div>
                        <div class="stat-label">Durée moyenne</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($non_conformites)): ?>
        <div class="empty-state">
            <div class="empty-icon">🎉</div>
            <h3>Aucune non-conformité active</h3>
            <p>Excellente nouvelle ! Aucune non-conformité en cours.</p>
            <a href="/qualite/list.php" class="btn btn-primary">Voir tous les contrôles</a>
        </div>
        <?php else: ?>

        <!-- Liste des non-conformités -->
        <div class="nc-list-section">
            <div class="section-header">
                <h2>Non-conformités actives</h2>
                <div class="bulk-actions" id="bulk-actions" style="display: none;">
                    <button class="btn btn-outline" onclick="bulkResolve()">✅ Résoudre sélectionnées</button>
                    <button class="btn btn-outline" onclick="bulkEscalate()">⬆️ Escalader</button>
                </div>
            </div>
            
            <div class="nc-cards">
                <?php foreach ($non_conformites as $nc): ?>
                <div class="nc-card priority-<?= $nc['priority'] ?>" data-id="<?= $nc['id'] ?>">
                    <div class="nc-header">
                        <div class="nc-info">
                            <input type="checkbox" class="nc-select" value="<?= $nc['id'] ?>" onchange="updateBulkActions()">
                            <div class="nc-title">
                                <strong><?= htmlspecialchars($nc['control_number']) ?></strong>
                                <span class="nc-type"><?= htmlspecialchars($nc['type_name']) ?></span>
                            </div>
                        </div>
                        <div class="nc-priority">
                            <span class="priority-badge priority-<?= $nc['priority'] ?>">
                                <?= getPriorityLabel($nc['priority']) ?>
                            </span>
                            <span class="days-open"><?= $nc['days_open'] ?>j ouvert</span>
                        </div>
                    </div>
                    
                    <div class="nc-content">
                        <div class="nc-details">
                            <div class="detail-item">
                                <label>Équipement:</label>
                                <value><?= htmlspecialchars($nc['model_name'] ?? 'Non spécifié') ?></value>
                            </div>
                            <div class="detail-item">
                                <label>Agence:</label>
                                <value>
                                    <span class="agency-badge"><?= htmlspecialchars($nc['agency_code']) ?></span>
                                    <?= htmlspecialchars($nc['agency_name'] ?? '') ?>
                                </value>
                            </div>
                            <div class="detail-item">
                                <label>Responsable:</label>
                                <value><?= htmlspecialchars($nc['prepared_by']) ?></value>
                            </div>
                            <div class="detail-item">
                                <label>Ouvert le:</label>
                                <value><?= date('d/m/Y à H:i', strtotime($nc['created_at'])) ?></value>
                            </div>
                        </div>
                        
                        <?php if (!empty($nc['observations'])): ?>
                        <div class="nc-observations">
                            <label>Observations:</label>
                            <p><?= nl2br(htmlspecialchars($nc['observations'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Analyse technique -->
                        <?php 
                        $technical_data = json_decode($nc['technical_data'], true) ?? [];
                        $quality_checks = $technical_data['quality_checks'] ?? [];
                        $failed_checks = array_filter($quality_checks, function($check) {
                            return is_array($check) && !($check['checked'] ?? false);
                        });
                        ?>
                        
                        <?php if (!empty($failed_checks)): ?>
                        <div class="failed-checks">
                            <label>Contrôles échoués:</label>
                            <div class="checks-list">
                                <?php foreach ($failed_checks as $check_name => $check): ?>
                                <span class="failed-check">❌ <?= formatCheckName($check_name) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="nc-actions">
                        <div class="action-buttons">
                            <a href="/qualite/view.php?id=<?= $nc['id'] ?>" class="btn-action view">
                                👁️ Voir détails
                            </a>
                            <button class="btn-action resolve" onclick="resolveNC(<?= $nc['id'] ?>)">
                                ✅ Résoudre
                            </button>
                            <button class="btn-action escalate" onclick="escalateNC(<?= $nc['id'] ?>)">
                                ⬆️ Escalader
                            </button>
                            <?php if ($nc['agency_email']): ?>
                            <button class="btn-action contact" onclick="contactAgency('<?= $nc['agency_email'] ?>', '<?= $nc['control_number'] ?>')">
                                📧 Contacter agence
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Analyse des causes -->
        <?php if (!empty($frequent_causes)): ?>
        <div class="causes-section">
            <h2>Analyse des causes fréquentes</h2>
            <div class="causes-grid">
                <?php foreach ($frequent_causes as $cause): ?>
                <div class="cause-card">
                    <div class="cause-header">
                        <strong><?= htmlspecialchars($cause['type_name']) ?></strong>
                        <span class="cause-count"><?= $cause['count'] ?> cas</span>
                    </div>
                    <div class="cause-samples">
                        <?= htmlspecialchars(substr($cause['sample_issues'], 0, 150)) ?>...
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal résolution NC -->
<div class="modal" id="resolve-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Résoudre non-conformité</h3>
            <button class="modal-close" onclick="closeModal('resolve-modal')">×</button>
        </div>
        <form id="resolve-form" method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="nc_id" id="resolve-nc-id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="new_status">Nouveau statut</label>
                    <select name="new_status" id="new_status" required>
                        <option value="completed">Terminé (action corrective effectuée)</option>
                        <option value="validated">Validé (conformité vérifiée)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="comments">Commentaires sur la résolution</label>
                    <textarea name="comments" id="comments" rows="4" 
                              placeholder="Décrivez l'action corrective mise en place..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('resolve-modal')">Annuler</button>
                <button type="submit" class="btn btn-primary">Confirmer résolution</button>
            </div>
        </form>
    </div>
</div>

<script>
const NCConfig = {
    baseUrl: '/qualite/',
    selectedIds: new Set()
};

// Filtres
function toggleFilters() {
    const panel = document.getElementById('filters-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

// Sélection multiple
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.nc-select:checked');
    const bulkActions = document.getElementById('bulk-actions');
    
    NCConfig.selectedIds.clear();
    checkboxes.forEach(cb => NCConfig.selectedIds.add(cb.value));
    
    bulkActions.style.display = NCConfig.selectedIds.size > 0 ? 'flex' : 'none';
}

// Actions individuelles
function resolveNC(ncId) {
    document.getElementById('resolve-nc-id').value = ncId;
    document.getElementById('resolve-modal').style.display = 'flex';
}

function escalateNC(ncId) {
    if (confirm('Escalader cette non-conformité vers le responsable qualité ?')) {
        fetch(`${NCConfig.baseUrl}api/escalate.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: ncId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification('Non-conformité escaladée', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        });
    }
}

function contactAgency(email, controlNumber) {
    const subject = encodeURIComponent(`Non-conformité ${controlNumber}`);
    const body = encodeURIComponent(`Bonjour,\n\nNous souhaitons vous informer d'une non-conformité détectée sur le contrôle ${controlNumber}.\n\nCordialement`);
    window.open(`mailto:${email}?subject=${subject}&body=${body}`);
}

// Actions groupées
function bulkResolve() {
    const ids = Array.from(NCConfig.selectedIds);
    if (ids.length === 0) return;
    
    if (confirm(`Résoudre ${ids.length} non-conformité(s) ?`)) {
        fetch(`${NCConfig.baseUrl}api/bulk-resolve.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ids})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification(`${data.resolved} non-conformité(s) résolue(s)`, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        });
    }
}

function bulkEscalate() {
    const ids = Array.from(NCConfig.selectedIds);
    if (ids.length === 0) return;
    
    if (confirm(`Escalader ${ids.length} non-conformité(s) ?`)) {
        fetch(`${NCConfig.baseUrl}api/bulk-escalate.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ids})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification(`${data.escalated} non-conformité(s) escaladée(s)`, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        });
    }
}

// Export
function exportNC() {
    window.open(`${NCConfig.baseUrl}export-nc.php`, '_blank');
}

// Utilitaires
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="notification-text">${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 100);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

console.log('🚨 Page non-conformités initialisée');
</script>

<style>
/* Styles non-conformités */
.nc-stats-section {
    margin-bottom: 2rem;
}

.nc-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    border-left: 4px solid;
}

.stat-card.critical { border-color: #ef4444; }
.stat-card.warning { border-color: #f59e0b; }
.stat-card.recent { border-color: #10b981; }
.stat-card.average { border-color: #6b7280; }

.stat-icon {
    font-size: 2rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-card.critical .stat-value { color: #ef4444; }
.stat-card.warning .stat-value { color: #f59e0b; }
.stat-card.recent .stat-value { color: #10b981; }
.stat-card.average .stat-value { color: #6b7280; }

.stat-label {
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 500;
}

.nc-cards {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.nc-card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid;
    overflow: hidden;
    transition: all 0.3s ease;
}

.nc-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.nc-card.priority-high { border-color: #ef4444; }
.nc-card.priority-medium { border-color: #f59e0b; }
.nc-card.priority-low { border-color: #10b981; }

.nc-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: var(--gray-50);
}

.nc-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nc-title {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.nc-type {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.nc-priority {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-high {
    background: #fee2e2;
    color: #991b1b;
}

.priority-medium {
    background: #fef3c7;
    color: #92400e;
}

.priority-low {
    background: #dcfce7;
    color: #166534;
}

.days-open {
    font-size: 0.75rem;
    color: var(--gray-600);
}

.nc-content {
    padding: 1.5rem;
}

.nc-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-item label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-600);
}

.detail-item value {
    color: var(--gray-900);
}

.nc-observations {
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
}

.nc-observations label {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
    display: block;
}

.nc-observations p {
    margin: 0;
    color: var(--gray-800);
    line-height: 1.5;
}

.failed-checks {
    margin-bottom: 1rem;
}

.failed-checks label {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
    display: block;
}

.checks-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.failed-check {
    padding: 0.25rem 0.75rem;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 0.5rem;
    font-size: 0.875rem;
}

.nc-actions {
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

.action-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn-action {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-action.view {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-action.resolve {
    background: #dcfce7;
    color: #166534;
}

.btn-action.escalate {
    background: #fef3c7;
    color: #92400e;
}

.btn-action.contact {
    background: #dbeafe;
    color: #1e40af;
}

.btn-action:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

.bulk-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.causes-section {
    margin-top: 3rem;
    background: white;
    padding: 2rem;
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.causes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.cause-card {
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200);
}

.cause-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.cause-count {
    background: var(--qualite-primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.cause-samples {
    font-size: 0.875rem;
    color: var(--gray-600);
    line-height: 1.4;
}

.empty-state {
    text-align: center;
    padding: 4rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.empty-state p {
    margin-bottom: 2rem;
    color: var(--gray-600);
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.modal-header h3 {
    margin: 0;
    color: var(--gray-800);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-400);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid var(--gray-200);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--gray-300);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: border-color 0.3s ease;
}

.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--qualite-primary);
}

/* Notifications */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 1rem;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    z-index: 1001;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-color: #22c55e;
}

.notification-error {
    border-color: #ef4444;
}

.notification-close {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.25rem;
    color: var(--gray-400);
    margin-left: auto;
}

/* Responsive */
@media (max-width: 1024px) {
    .nc-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .causes-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .module-header-content {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .nc-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .nc-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .nc-details {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-action {
        width: 100%;
        justify-content: center;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .bulk-actions {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 1rem;
    }
    
    .nc-card {
        margin: 0 -0.5rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 1rem;
    }
}
</style>

<?php
// Fonctions utilitaires
function getPriorityLabel($priority) {
    $labels = [
        'high' => 'Critique',
        'medium' => 'Attention',
        'low' => 'Normale'
    ];
    return $labels[$priority] ?? 'Inconnue';
}

function formatCheckName($checkName) {
    $names = [
        'test_etancheite' => 'Test d\'étanchéité',
        'test_debit_precision' => 'Test précision débit',
        'test_signal_4_20ma' => 'Test signal 4-20mA',
        'test_impulsions' => 'Test entrée impulsions',
        'test_amorcage' => 'Test amorçage manuel',
        'verification_kit' => 'Vérification kit installation',
        'pressure_test' => 'Test de pression',
        'flow_test' => 'Test de débit',
        'regeneration_test' => 'Test régénération',
        'th_output_test' => 'Test TH sortie',
        'programming_check' => 'Vérification programmation'
    ];
    return $names[$checkName] ?? ucfirst(str_replace('_', ' ', $checkName));
}

require_once ROOT_PATH . '/templates/footer.php';
?><?php
/**
 * Titre: Gestion des non-conformités et actions correctives
 * Chemin: /public/qualite/non-conformites.php
 * Version: 0.5 beta + build auto

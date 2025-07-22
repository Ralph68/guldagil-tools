<?php
/**
 * Titre: Visualisation détaillée d'un contrôle qualité
 * Chemin: /public/qualite/view.php
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

// Récupération ID contrôle
$control_id = (int)($_GET['id'] ?? 0);
if (!$control_id) {
    header('Location: /qualite/list.php');
    exit;
}

$current_module = 'qualite';
$module_css = true;

// Auth temporaire
$user_authenticated = true;
$current_user = ['id' => 1, 'role' => 'logistique', 'name' => 'Utilisateur'];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Récupération contrôle complet
    $stmt = $pdo->prepare("
        SELECT qc.*, et.type_name, et.type_code, em.model_name, em.manufacturer,
               a.agency_name, a.email as agency_email, a.contact_person
        FROM cq_quality_controls qc
        JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
        LEFT JOIN cq_equipment_models em ON qc.equipment_model_id = em.id
        LEFT JOIN cq_agencies a ON qc.agency_code = a.agency_code
        WHERE qc.id = ?
    ");
    $stmt->execute([$control_id]);
    $control = $stmt->fetch();

    if (!$control) {
        header('Location: /qualite/list.php?error=not_found');
        exit;
    }

    // Décoder données JSON
    $technical_data = json_decode($control['technical_data'], true) ?? [];
    $settings_data = json_decode($control['settings_data'], true) ?? [];
    $quality_checks = $technical_data['quality_checks'] ?? [];

    // Historique du contrôle
    $history_stmt = $pdo->prepare("
        SELECT * FROM cq_control_history 
        WHERE control_id = ? 
        ORDER BY action_date DESC
    ");
    $history_stmt->execute([$control_id]);
    $history = $history_stmt->fetchAll();

    // Titre page
    $page_title = "Contrôle {$control['control_number']}";
    $page_subtitle = $control['type_name'] . ' - ' . ($control['model_name'] ?? 'Modèle non spécifié');

    $breadcrumbs = [
        ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
        ['icon' => '✅', 'text' => 'Contrôle Qualité', 'url' => '/qualite/', 'active' => false],
        ['icon' => '📋', 'text' => 'Consultation', 'url' => '/qualite/list.php', 'active' => false],
        ['icon' => '👁️', 'text' => $control['control_number'], 'url' => '', 'active' => true]
    ];

} catch (Exception $e) {
    error_log("Erreur view contrôle: " . $e->getMessage());
    header('Location: /qualite/list.php?error=db_error');
    exit;
}

require_once ROOT_PATH . '/templates/header.php';
?>

<div class="qualite-module">
    <!-- Header -->
    <div class="module-header">
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">👁️</div>
                <div class="module-info">
                    <h1><?= htmlspecialchars($control['control_number']) ?></h1>
                    <p class="module-version"><?= htmlspecialchars($control['type_name']) ?> - <?= htmlspecialchars($control['model_name'] ?? 'Modèle non spécifié') ?></p>
                </div>
            </div>
            <div class="module-actions">
                <?php if ($control['status'] !== 'validated'): ?>
                <a href="/qualite/edit.php?id=<?= $control_id ?>" class="btn btn-primary">
                    <span class="icon">✏️</span>
                    Modifier
                </a>
                <?php endif; ?>
                <button class="btn btn-outline" onclick="generatePDF()">
                    <span class="icon">📄</span>
                    Générer PDF
                </button>
                <button class="btn btn-outline" onclick="duplicateControl()">
                    <span class="icon">📋</span>
                    Dupliquer
                </button>
                <a href="/qualite/list.php" class="btn btn-outline">
                    <span class="icon">←</span>
                    Retour liste
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- Status banner -->
        <div class="status-banner status-<?= $control['status'] ?>">
            <div class="status-content">
                <span class="status-icon"><?= getStatusIcon($control['status']) ?></span>
                <div class="status-text">
                    <strong><?= getStatusLabel($control['status']) ?></strong>
                    <span><?= getStatusDescription($control['status']) ?></span>
                </div>
                <?php if ($control['status'] === 'validated'): ?>
                <div class="status-details">
                    Validé par <?= htmlspecialchars($control['validated_by']) ?> 
                    le <?= date('d/m/Y à H:i', strtotime($control['validated_date'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="view-layout">
            <!-- Informations principales -->
            <div class="main-panel">
                <!-- Informations générales -->
                <div class="info-section">
                    <h2>📋 Informations générales</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>N° Contrôle</label>
                            <value><?= htmlspecialchars($control['control_number']) ?></value>
                        </div>
                        <div class="info-item">
                            <label>Type équipement</label>
                            <value>
                                <span class="type-badge"><?= htmlspecialchars($control['type_name']) ?></span>
                            </value>
                        </div>
                        <div class="info-item">
                            <label>Modèle</label>
                            <value><?= htmlspecialchars($control['model_name'] ?? 'Non spécifié') ?></value>
                        </div>
                        <div class="info-item">
                            <label>Fabricant</label>
                            <value><?= htmlspecialchars($control['manufacturer'] ?? 'Non spécifié') ?></value>
                        </div>
                        <div class="info-item">
                            <label>Agence</label>
                            <value>
                                <span class="agency-badge"><?= htmlspecialchars($control['agency_code']) ?></span>
                                <?= htmlspecialchars($control['agency_name'] ?? '') ?>
                            </value>
                        </div>
                        <div class="info-item">
                            <label>N° Dossier</label>
                            <value><?= htmlspecialchars($control['dossier_number'] ?? 'N/A') ?></value>
                        </div>
                        <div class="info-item">
                            <label>N° ARC</label>
                            <value><?= htmlspecialchars($control['arc_number'] ?? 'N/A') ?></value>
                        </div>
                        <div class="info-item">
                            <label>N° Série</label>
                            <value><?= htmlspecialchars($control['serial_number'] ?? 'N/A') ?></value>
                        </div>
                        <div class="info-item full-width">
                            <label>Installation</label>
                            <value><?= htmlspecialchars($control['installation_name'] ?? 'Non spécifiée') ?></value>
                        </div>
                    </div>
                </div>

                <!-- Données techniques -->
                <?php if (!empty($technical_data)): ?>
                <div class="info-section">
                    <h2>⚙️ Données techniques</h2>
                    <div class="technical-data">
                        <?php foreach ($technical_data as $key => $value): ?>
                            <?php if ($key !== 'quality_checks'): ?>
                            <div class="data-item">
                                <label><?= formatTechnicalLabel($key) ?></label>
                                <value><?= formatTechnicalValue($key, $value) ?></value>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contrôles qualité -->
                <?php if (!empty($quality_checks)): ?>
                <div class="info-section">
                    <h2>🔬 Contrôles qualité</h2>
                    <div class="quality-checks">
                        <?php foreach ($quality_checks as $check => $data): ?>
                            <?php if ($check !== '_comments' && is_array($data)): ?>
                            <div class="check-item <?= $data['checked'] ? 'checked' : 'unchecked' ?>">
                                <span class="check-icon"><?= $data['checked'] ? '✅' : '❌' ?></span>
                                <div class="check-content">
                                    <strong><?= formatCheckLabel($check) ?></strong>
                                    <?php if (isset($data['timestamp'])): ?>
                                    <small>Vérifié le <?= date('d/m/Y à H:i', strtotime($data['timestamp'])) ?></small>
                                    <?php endif; ?>
                                    <?php if (isset($data['verified_by'])): ?>
                                    <small>par <?= htmlspecialchars($data['verified_by']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if (!empty($quality_checks['_comments'])): ?>
                        <div class="check-comments">
                            <h4>Commentaires techniques :</h4>
                            <p><?= nl2br(htmlspecialchars($quality_checks['_comments'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Observations -->
                <?php if (!empty($control['observations'])): ?>
                <div class="info-section">
                    <h2>📝 Observations</h2>
                    <div class="observations">
                        <?= nl2br(htmlspecialchars($control['observations'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Panel latéral -->
            <div class="side-panel">
                <!-- Statut et dates -->
                <div class="side-section">
                    <h3>📅 Suivi</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <span class="timeline-icon">📝</span>
                            <div class="timeline-content">
                                <strong>Création</strong>
                                <small><?= date('d/m/Y à H:i', strtotime($control['created_at'])) ?></small>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <span class="timeline-icon">🔧</span>
                            <div class="timeline-content">
                                <strong>Préparé par</strong>
                                <small><?= htmlspecialchars($control['prepared_by']) ?></small>
                                <small><?= date('d/m/Y', strtotime($control['prepared_date'])) ?></small>
                            </div>
                        </div>
                        
                        <?php if ($control['validated_by']): ?>
                        <div class="timeline-item">
                            <span class="timeline-icon">✅</span>
                            <div class="timeline-content">
                                <strong>Validé par</strong>
                                <small><?= htmlspecialchars($control['validated_by']) ?></small>
                                <small><?= date('d/m/Y à H:i', strtotime($control['validated_date'])) ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($control['updated_at'] !== $control['created_at']): ?>
                        <div class="timeline-item">
                            <span class="timeline-icon">🔄</span>
                            <div class="timeline-content">
                                <strong>Dernière modif</strong>
                                <small><?= date('d/m/Y à H:i', strtotime($control['updated_at'])) ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="side-section">
                    <h3>⚡ Actions rapides</h3>
                    <div class="quick-actions">
                        <?php if ($control['status'] !== 'validated' && in_array($current_user['role'], ['admin', 'resp_materiel'])): ?>
                        <button class="action-btn" onclick="validateControl()">
                            <span class="icon">✅</span>
                            Valider contrôle
                        </button>
                        <?php endif; ?>
                        
                        <button class="action-btn" onclick="sendToAgency()">
                            <span class="icon">📧</span>
                            Envoyer à l'agence
                        </button>
                        
                        <button class="action-btn" onclick="printControl()">
                            <span class="icon">🖨️</span>
                            Imprimer
                        </button>
                        
                        <button class="action-btn" onclick="exportData()">
                            <span class="icon">💾</span>
                            Exporter données
                        </button>
                    </div>
                </div>

                <!-- Contact agence -->
                <?php if ($control['agency_email'] || $control['contact_person']): ?>
                <div class="side-section">
                    <h3>📞 Contact agence</h3>
                    <div class="contact-info">
                        <?php if ($control['contact_person']): ?>
                        <div class="contact-item">
                            <strong>Contact :</strong>
                            <?= htmlspecialchars($control['contact_person']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($control['agency_email']): ?>
                        <div class="contact-item">
                            <strong>Email :</strong>
                            <a href="mailto:<?= htmlspecialchars($control['agency_email']) ?>">
                                <?= htmlspecialchars($control['agency_email']) ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Fichiers joints -->
                <div class="side-section">
                    <h3>📎 Documents</h3>
                    <div class="documents">
                        <?php if ($control['pdf_generated']): ?>
                        <a href="/qualite/pdf.php?id=<?= $control_id ?>" class="doc-link" target="_blank">
                            <span class="doc-icon">📄</span>
                            Rapport PDF
                        </a>
                        <?php endif; ?>
                        
                        <button class="doc-link" onclick="generatePDF()">
                            <span class="doc-icon">📄</span>
                            Générer nouveau PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique -->
        <?php if (!empty($history)): ?>
        <div class="history-section">
            <h2>📚 Historique des modifications</h2>
            <div class="history-timeline">
                <?php foreach ($history as $entry): ?>
                <div class="history-item">
                    <div class="history-icon"><?= getActionIcon($entry['action']) ?></div>
                    <div class="history-content">
                        <div class="history-action">
                            <strong><?= getActionLabel($entry['action']) ?></strong>
                            <?php if ($entry['field_name']): ?>
                            <span class="field-name"><?= htmlspecialchars($entry['field_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($entry['old_value'] || $entry['new_value']): ?>
                        <div class="history-changes">
                            <?php if ($entry['old_value']): ?>
                            <span class="old-value">Ancien : <?= htmlspecialchars($entry['old_value']) ?></span>
                            <?php endif; ?>
                            <?php if ($entry['new_value']): ?>
                            <span class="new-value">Nouveau : <?= htmlspecialchars($entry['new_value']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="history-meta">
                            <?= htmlspecialchars($entry['user_name']) ?> • 
                            <?= date('d/m/Y à H:i', strtotime($entry['action_date'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const ViewConfig = {
    controlId: <?= $control_id ?>,
    baseUrl: '/qualite/',
    canEdit: <?= json_encode($control['status'] !== 'validated') ?>,
    currentStatus: '<?= $control['status'] ?>'
};

function generatePDF() {
    window.open(`${ViewConfig.baseUrl}pdf.php?id=${ViewConfig.controlId}`, '_blank');
}

function duplicateControl() {
    if (confirm('Dupliquer ce contrôle vers un nouveau ?')) {
        window.location.href = `${ViewConfig.baseUrl}create.php?duplicate=${ViewConfig.controlId}`;
    }
}

function validateControl() {
    if (!confirm('Valider définitivement ce contrôle ? Cette action est irréversible.')) return;
    
    fetch(`${ViewConfig.baseUrl}api/validate.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: ViewConfig.controlId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('Contrôle validé avec succès', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Erreur : ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Erreur de connexion', 'error');
    });
}

function sendToAgency() {
    if (!confirm('Envoyer ce contrôle par email à l\'agence ?')) return;
    
    fetch(`${ViewConfig.baseUrl}api/send-email.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: ViewConfig.controlId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('Email envoyé avec succès', 'success');
        } else {
            showNotification('Erreur envoi : ' + data.message, 'error');
        }
    });
}

function printControl() {
    window.print();
}

function exportData() {
    window.location.href = `${ViewConfig.baseUrl}export.php?id=${ViewConfig.controlId}&format=json`;
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

console.log('👁️ Page visualisation contrôle initialisée');
</script>

<style>
/* Styles spécifiques page view */
.status-banner {
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-radius: 0.75rem;
    border-left: 4px solid;
}

.status-draft { background: #f8fafc; border-color: #64748b; }
.status-in_progress { background: #fef3c7; border-color: #f59e0b; }
.status-completed { background: #dbeafe; border-color: #3b82f6; }
.status-validated { background: #d1fae5; border-color: #10b981; }
.status-sent { background: #e0e7ff; border-color: #6366f1; }

.status-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-icon {
    font-size: 1.5rem;
}

.view-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
}

.info-section {
    background: white;
    padding: 2rem;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.info-section h2 {
    margin: 0 0 1.5rem 0;
    color: var(--gray-800);
    font-size: 1.25rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-item label {
    font-weight: 600;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.info-item value {
    color: var(--gray-900);
    font-weight: 500;
}

.technical-data {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.data-item {
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quality-checks {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.check-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 2px solid;
}

.check-item.checked {
    background: #f0fdf4;
    border-color: #22c55e;
}

.check-item.unchecked {
    background: #fef2f2;
    border-color: #ef4444;
}

.check-comments {
    margin-top: 1.5rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
}

.side-panel {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.side-section {
    background: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.side-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    color: var(--gray-800);
}

.timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timeline-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.timeline-icon {
    font-size: 1.25rem;
}

.timeline-content small {
    display: block;
    color: var(--gray-600);
    font-size: 0.75rem;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: var(--gray-700);
}

.action-btn:hover {
    background: var(--qualite-primary);
    color: white;
    transform: translateY(-1px);
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.contact-item {
    font-size: 0.875rem;
}

.documents {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.doc-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    text-decoration: none;
    color: var(--gray-700);
    transition: all 0.3s ease;
}

.doc-link:hover {
    background: var(--gray-100);
}

.history-section {
    background: white;
    padding: 2rem;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-top: 2rem;
}

.history-timeline {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.history-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
}

.history-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.history-content {
    flex: 1;
}

.history-action {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.field-name {
    background: var(--gray-200);
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
}

.history-changes {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.old-value {
    color: #dc2626;
}

.new-value {
    color: #059669;
    margin-left: 1rem;
}

.history-meta {
    font-size: 0.75rem;
    color: var(--gray-600);
}

.notifications {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
}

.notification {
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
    margin-bottom: 1rem;
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
}

@media (max-width: 768px) {
    .view-layout {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .technical-data {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Fonctions utilitaires
function getStatusIcon($status) {
    $icons = [
        'draft' => '📝',
        'in_progress' => '⏳',
        'completed' => '✅',
        'validated' => '🎯',
        'sent' => '📧'
    ];
    return $icons[$status] ?? '❓';
}

function getStatusLabel($status) {
    $labels = [
        'draft' => 'Brouillon',
        'in_progress' => 'En cours',
        'completed' => 'Terminé',
        'validated' => 'Validé',
        'sent' => 'Envoyé'
    ];
    return $labels[$status] ?? 'Inconnu';
}

function getStatusDescription($status) {
    $descriptions = [
        'draft' => 'Contrôle en cours de rédaction',
        'in_progress' => 'Contrôle en cours, actions correctives nécessaires',
        'completed' => 'Contrôle terminé, en attente de validation',
        'validated' => 'Contrôle validé et approuvé',
        'sent' => 'Contrôle envoyé à l\'agence'
    ];
    return $descriptions[$status] ?? '';
}

function formatTechnicalLabel($key) {
    $labels = [
        'debit_nominal_lh' => 'Débit nominal (L/h)',
        'pression_service_bar' => 'Pression service (bar)',
        'hauteur_aspiration_m' => 'Hauteur aspiration (m)',
        'product_type' => 'Type produit',
        'concentration_percent' => 'Concentration (%)',
        'signal_control_type' => 'Type signal contrôle',
        'potentiometre_position' => 'Position potentiomètre (%)',
        'consommation_w' => 'Consommation (W)',
        'raw_water_hardness' => 'TH eau brute (°f)',
        'target_hardness' => 'TH à obtenir (°f)',
        'resin_volume' => 'Volume résine (L)',
        'flow_rate' => 'Débit (m³/h)',
        'salt_consumption' => 'Consommation sel (kg)'
    ];
    return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
}

function formatTechnicalValue($key, $value) {
    if (is_bool($value)) {
        return $value ? 'Oui' : 'Non';
    }
    if (is_numeric($value)) {
        return number_format($value, 2, ',', ' ');
    }
    return htmlspecialchars($value);
}

function formatCheckLabel($check) {
    $labels = [
        'test_etancheite' => 'Test d\'étanchéité',
        'test_debit_precision' => 'Test précision débit',
        'test_signal_4_20ma' => 'Test signal 4-20mA',
        'test_impulsions' => 'Test entrée impulsions',
        'test_amorcage' => 'Test amorçage manuel',
        'verification_kit' => 'Vérification kit installation',
        'pressure_test' => 'Test de pression',
        'flow_test' => 'Test de débit',
        'regeneration_test' => 'Test régénération',
        'th_output_test' => 'Test TH sortie'
    ];
    return $labels[$check] ?? ucfirst(str_replace('_', ' ', $check));
}

function getActionIcon($action) {
    $icons = [
        'created' => '📝',
        'modified' => '✏️',
        'validated' => '✅',
        'pdf_generated' => '📄',
        'sent' => '📧'
    ];
    return $icons[$action] ?? '🔄';
}

function getActionLabel($action) {
    $labels = [
        'created' => 'Création',
        'modified' => 'Modification',
        'validated' => 'Validation',
        'pdf_generated' => 'PDF généré',
        'sent' => 'Envoi'
    ];
    return $labels[$action] ?? ucfirst($action);
}

require_once ROOT_PATH . '/templates/footer.php';
?>

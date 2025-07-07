<?php
/**
 * Titre: Dashboard - Module Contrôle Qualité
 * Chemin: /features/qualite/components/dashboard.php
 * Version: 0.5 beta + build auto
 */

// Récupérer les statistiques
$stats = $qualiteManager->getStats();
$equipmentTypes = $qualiteManager->getEquipmentTypes();
$recentControls = $qualiteManager->getQualityControls(['limit' => 10]);

// Statistiques par type d'équipement
$statsByType = $qualiteManager->getStatsByEquipmentType();

?>

<!-- Dashboard Header -->
<section class="dashboard-header">
    <div class="page-title">
        <h2>🏠 Dashboard Contrôle Qualité</h2>
        <p>Vue d'ensemble des contrôles et validations</p>
    </div>
    
    <div class="dashboard-actions">
        <button class="btn btn-primary" onclick="showNewControlModal()">
            ➕ Nouveau contrôle
        </button>
        <button class="btn btn-secondary" onclick="exportDashboardData()">
            📊 Export données
        </button>
    </div>
</section>

<!-- Statistiques principales -->
<section class="dashboard-stats">
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">🔍</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['total_controls'] ?? 0 ?></div>
                <div class="stat-label">Contrôles total</div>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['validated_count'] ?? 0 ?></div>
                <div class="stat-label">Validés</div>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['in_progress_count'] ?? 0 ?></div>
                <div class="stat-label">En cours</div>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['today_count'] ?? 0 ?></div>
                <div class="stat-label">Aujourd'hui</div>
            </div>
        </div>
    </div>
</section>

<!-- Types d'équipements disponibles -->
<section class="equipment-types-section">
    <h3>📋 Types d'équipements disponibles</h3>
    
    <div class="equipment-grid">
        <?php foreach ($equipmentTypes as $type): ?>
        <div class="equipment-card equipment-<?= strtolower($type['category']) ?>">
            <div class="equipment-header">
                <div class="equipment-icon">
                    <?php if ($type['category'] === 'adoucisseur'): ?>
                        💧
                    <?php elseif ($type['category'] === 'pompe_doseuse'): ?>
                        ⚙️
                    <?php else: ?>
                        🔧
                    <?php endif; ?>
                </div>
                <div class="equipment-info">
                    <h4><?= htmlspecialchars($type['type_name']) ?></h4>
                    <p><?= htmlspecialchars($type['description']) ?></p>
                </div>
            </div>
            
            <div class="equipment-stats">
                <?php 
                $typeStats = array_filter($statsByType, fn($s) => $s['type_name'] === $type['type_name']);
                $typeStats = reset($typeStats);
                ?>
                <div class="equipment-stat">
                    <span class="stat-value"><?= $typeStats['count'] ?? 0 ?></span>
                    <span class="stat-label">Contrôles</span>
                </div>
                <div class="equipment-stat">
                    <span class="stat-value"><?= $typeStats['validated_count'] ?? 0 ?></span>
                    <span class="stat-label">Validés</span>
                </div>
            </div>
            
            <div class="equipment-actions">
                <?php if ($type['category'] === 'adoucisseur'): ?>
                <button class="btn btn-small btn-primary" onclick="startAdoucisseurControl('<?= $type['type_code'] ?>')">
                    💧 Nouveau contrôle
                </button>
                <?php elseif ($type['category'] === 'pompe_doseuse'): ?>
                <button class="btn btn-small btn-primary" onclick="startPompeControl('<?= $type['type_code'] ?>')">
                    ⚙️ Nouveau contrôle
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Contrôles récents -->
<section class="recent-controls-section">
    <div class="section-header">
        <h3>🕒 Contrôles récents</h3>
        <a href="?action=controles" class="btn btn-secondary btn-small">Voir tous</a>
    </div>
    
    <?php if (!empty($recentControls)): ?>
    <div class="controls-table">
        <table>
            <thead>
                <tr>
                    <th>N° Contrôle</th>
                    <th>Type équipement</th>
                    <th>Installation</th>
                    <th>Agence</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentControls as $control): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($control['control_number']) ?></strong>
                    </td>
                    <td>
                        <span class="equipment-badge equipment-<?= strtolower($control['equipment_type']) ?>">
                            <?= htmlspecialchars($control['equipment_type']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($control['installation_name']) ?></td>
                    <td>
                        <span class="agency-badge"><?= htmlspecialchars($control['agency_code']) ?></span>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $control['status'] ?>">
                            <?php
                            $statusLabels = [
                                'draft' => 'Brouillon',
                                'in_progress' => 'En cours',
                                'completed' => 'Terminé',
                                'validated' => 'Validé',
                                'sent' => 'Envoyé'
                            ];
                            echo $statusLabels[$control['status']] ?? $control['status'];
                            ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($control['created_at'])) ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-small btn-secondary" onclick="viewControl(<?= $control['id'] ?>)">
                                👁️ Voir
                            </button>
                            <?php if (in_array($control['status'], ['draft', 'in_progress'])): ?>
                            <button class="btn btn-small btn-primary" onclick="editControl(<?= $control['id'] ?>)">
                                ✏️ Modifier
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h4>Aucun contrôle récent</h4>
        <p>Commencez par créer votre premier contrôle qualité</p>
        <button class="btn btn-primary" onclick="showNewControlModal()">
            ➕ Nouveau contrôle
        </button>
    </div>
    <?php endif; ?>
</section>

<!-- Actions rapides -->
<section class="quick-actions-section">
    <h3>⚡ Actions rapides</h3>
    
    <div class="actions-grid">
        <div class="action-card">
            <div class="action-icon">💧</div>
            <div class="action-content">
                <h4>Contrôle Adoucisseur</h4>
                <p>Démarrer un nouveau contrôle pour adoucisseur</p>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="startAdoucisseurControl('ADOU_CLACK_CI')">
                        Clack CI
                    </button>
                    <button class="btn btn-primary" onclick="startAdoucisseurControl('ADOU_FLECK_SXT')">
                        Fleck SXT
                    </button>
                </div>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">⚙️</div>
            <div class="action-content">
                <h4>Contrôle Pompe Doseuse</h4>
                <p>Démarrer un nouveau contrôle pour pompe doseuse</p>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="startPompeControl('POMPE_DOS4_8V')">
                        DOS4-8V
                    </button>
                    <button class="btn btn-primary" onclick="startPompeControl('POMPE_DOS6_DDE')">
                        DOS6 DDE
                    </button>
                </div>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">📊</div>
            <div class="action-content">
                <h4>Rapports</h4>
                <p>Générer et consulter les rapports de conformité</p>
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="generateWeeklyReport()">
                        Rapport hebdo
                    </button>
                    <button class="btn btn-secondary" onclick="generateMonthlyReport()">
                        Rapport mensuel
                    </button>
                </div>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">📧</div>
            <div class="action-content">
                <h4>Envois en attente</h4>
                <p>Gérer les contrôles validés en attente d'envoi</p>
                <div class="action-buttons">
                    <button class="btn btn-warning" onclick="showPendingSends()">
                        <?= $stats['validated_count'] ?? 0 ?> en attente
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal nouveau contrôle -->
<div id="newControlModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ Nouveau contrôle qualité</h3>
            <button class="modal-close" onclick="closeModal('newControlModal')">&times;</button>
        </div>
        
        <div class="modal-body">
            <p>Sélectionnez le type d'équipement à contrôler :</p>
            
            <div class="equipment-selection">
                <div class="equipment-category">
                    <h4>💧 Adoucisseurs</h4>
                    <div class="equipment-options">
                        <button class="equipment-option" onclick="startAdoucisseurControl('ADOU_CLACK_CI')">
                            <strong>Clack CI</strong><br>
                            <small>Vanne Clack CI standard</small>
                        </button>
                        <button class="equipment-option" onclick="startAdoucisseurControl('ADOU_CLACK_CIM')">
                            <strong>Clack CIM</strong><br>
                            <small>Vanne Clack CIM</small>
                        </button>
                        <button class="equipment-option" onclick="startAdoucisseurControl('ADOU_CLACK_CIP')">
                            <strong>Clack CI P</strong><br>
                            <small>Vanne Clack CI Plastique</small>
                        </button>
                        <button class="equipment-option" onclick="startAdoucisseurControl('ADOU_FLECK_SXT')">
                            <strong>Fleck SXT</strong><br>
                            <small>Ex: 26 SXT</small>
                        </button>
                    </div>
                </div>
                
                <div class="equipment-category">
                    <h4>⚙️ Pompes Doseuses</h4>
                    <div class="equipment-options">
                        <button class="equipment-option" onclick="startPompeControl('POMPE_DOS4_8V')">
                            <strong>DOS4-8V</strong><br>
                            <small>TEKNA DOS4-8V</small>
                        </button>
                        <button class="equipment-option" onclick="startPompeControl('POMPE_DOS4_8V2')">
                            <strong>DOS4-8V2</strong><br>
                            <small>TEKNA DOS4-8V2</small>
                        </button>
                        <button class="equipment-option" onclick="startPompeControl('POMPE_DOS6_DDE')">
                            <strong>DOS6 DDE</strong><br>
                            <small>GRUNDFOS DOS6 DDE</small>
                        </button>
                        <button class="equipment-option" onclick="startPompeControl('POMPE_DOS3_4')">
                            <strong>DOS3.4</strong><br>
                            <small>TEKNA DOS3.4</small>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques au dashboard */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.equipment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.equipment-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #22c55e;
}

.equipment-card.equipment-adoucisseur {
    border-left-color: #3b82f6;
}

.equipment-card.equipment-pompe_doseuse {
    border-left-color: #f59e0b;
}

.equipment-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.equipment-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 50%;
}

.equipment-stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.equipment-stat {
    text-align: center;
}

.equipment-stat .stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #22c55e;
}

.equipment-stat .stat-label {
    font-size: 0.8rem;
    color: #6b7280;
}

.controls-table table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.controls-table th,
.controls-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
}

.controls-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge.status-draft { background: #f3f4f6; color: #6b7280; }
.status-badge.status-in_progress { background: #dbeafe; color: #1d4ed8; }
.status-badge.status-completed { background: #fef3c7; color: #d97706; }
.status-badge.status-validated { background: #dcfce7; color: #16a34a; }
.status-badge.status-sent { background: #e0e7ff; color: #7c3aed; }

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.action-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.action-card .action-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.equipment-selection {
    display: grid;
    gap: 2rem;
}

.equipment-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.equipment-option {
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
}

.equipment-option:hover {
    border-color: #22c55e;
    background: #f0fdf4;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}
</style>

<script>
function showNewControlModal() {
    document.getElementById('newControlModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function startAdoucisseurControl(typeCode) {
    window.location.href = `forms/adoucisseur.php?type=${typeCode}`;
}

function startPompeControl(typeCode) {
    window.location.href = `forms/pompe.php?type=${typeCode}`;
}

function viewControl(controlId) {
    window.location.href = `?action=controles&view=${controlId}`;
}

function editControl(controlId) {
    // Déterminer le type d'équipement pour rediriger vers le bon formulaire
    // TODO: Implémenter la logique de redirection
    alert(`Modification du contrôle ${controlId} - À implémenter`);
}

function generateWeeklyReport() {
    window.location.href = `?action=rapports&type=weekly`;
}

function generateMonthlyReport() {
    window.location.href = `?action=rapports&type=monthly`;
}

function showPendingSends() {
    window.location.href = `?action=controles&status=validated`;
}

function exportDashboardData() {
    window.location.href = `export/dashboard.php`;
}
</script>

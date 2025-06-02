<?php
// tabs/import-tab.php - Import et Export des données
?>
<div id="tab-import" class="tab-content">
    <!-- Actions rapides d'export -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>📤 Export rapide</h2>
            <button class="btn btn-secondary" onclick="refreshExportStats()">
                <span>🔄</span>
                Actualiser
            </button>
        </div>
        <div class="admin-card-body">
            <div class="export-grid">
                <div class="export-card" onclick="exportData('rates', 'csv')">
                    <div class="export-icon">💰</div>
                    <div class="export-content">
                        <h4>Tarifs transporteurs</h4>
                        <p>Tous les tarifs par département</p>
                        <div class="export-info">
                            <?php
                            try {
                                $stmt = $db->query("SELECT 
                                    (SELECT COUNT(*) FROM gul_heppner_rates) + 
                                    (SELECT COUNT(*) FROM gul_xpo_rates) + 
                                    (SELECT COUNT(*) FROM gul_kn_rates) as total");
                                $totalRates = $stmt->fetch()['total'] ?? 0;
                                echo "<span class=\"data-count\">{$totalRates} lignes</span>";
                            } catch (Exception $e) {
                                echo "<span class=\"data-count\">- lignes</span>";
                            }
                            ?>
                            <span class="file-format">CSV</span>
                        </div>
                    </div>
                    <div class="export-action">📥</div>
                </div>

                <div class="export-card" onclick="exportData('options', 'csv')">
                    <div class="export-icon">⚙️</div>
                    <div class="export-content">
                        <h4>Options supplémentaires</h4>
                        <p>Configuration des options</p>
                        <div class="export-info">
                            <?php
                            try {
                                $stmt = $db->query("SELECT COUNT(*) as total FROM gul_options_supplementaires");
                                $totalOptions = $stmt->fetch()['total'] ?? 0;
                                echo "<span class=\"data-count\">{$totalOptions} options</span>";
                            } catch (Exception $e) {
                                echo "<span class=\"data-count\">- options</span>";
                            }
                            ?>
                            <span class="file-format">CSV</span>
                        </div>
                    </div>
                    <div class="export-action">📥</div>
                </div>

                <div class="export-card" onclick="exportData('taxes', 'csv')">
                    <div class="export-icon">📋</div>
                    <div class="export-content">
                        <h4>Taxes & majorations</h4>
                        <p>Configuration des taxes</p>
                        <div class="export-info">
                            <?php
                            try {
                                $stmt = $db->query("SELECT COUNT(*) as total FROM gul_taxes_transporteurs");
                                $totalTaxes = $stmt->fetch()['total'] ?? 0;
                                echo "<span class=\"data-count\">{$totalTaxes} transporteurs</span>";
                            } catch (Exception $e) {
                                echo "<span class=\"data-count\">- transporteurs</span>";
                            }
                            ?>
                            <span class="file-format">CSV</span>
                        </div>
                    </div>
                    <div class="export-action">📥</div>
                </div>

                <div class="export-card complete" onclick="exportData('all', 'json')">
                    <div class="export-icon">💾</div>
                    <div class="export-content">
                        <h4>Sauvegarde complète</h4>
                        <p>Toutes les données du système</p>
                        <div class="export-info">
                            <span class="data-count">Backup complet</span>
                            <span class="file-format">JSON</span>
                        </div>
                    </div>
                    <div class="export-action">💾</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Import et Export avancé -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Import de fichiers -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>📥 Import de données</h3>
            </div>
            <div class="admin-card-body">
                <div class="import-section">
                    <div class="import-type-selector">
                        <label>Type de données à importer :</label>
                        <select id="import-type" class="form-control" onchange="updateImportInstructions()">
                            <option value="">Choisir le type</option>
                            <option value="rates">Tarifs transporteurs</option>
                            <option value="options">Options supplémentaires</option>
                            <option value="taxes">Taxes et majorations</option>
                        </select>
                    </div>

                    <div class="file-upload-zone" id="file-upload-zone">
                        <div class="upload-placeholder">
                            <div class="upload-icon">📁</div>
                            <div class="upload-text">
                                <p><strong>Glissez-déposez un fichier CSV ici</strong></p>
                                <p>ou cliquez pour sélectionner</p>
                            </div>
                            <input type="file" id="import-file" accept=".csv,.xlsx,.xls" onchange="handleFileSelect(event)">
                        </div>
                        <div class="upload-info">
                            <small>Formats acceptés : CSV, Excel (.xlsx, .xls) • Taille max : 10 MB</small>
                        </div>
                    </div>

                    <div class="import-instructions" id="import-instructions">
                        <h5>💡 Instructions d'import</h5>
                        <p>Sélectionnez d'abord le type de données à importer pour voir les instructions spécifiques.</p>
                    </div>

                    <div class="import-templates">
                        <h5>📋 Modèles de fichiers</h5>
                        <div class="template-buttons">
                            <button class="btn btn-secondary btn-sm" onclick="downloadTemplate('rates')">
                                📄 Modèle tarifs
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="downloadTemplate('options')">
                                📄 Modèle options
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="downloadTemplate('taxes')">
                                📄 Modèle taxes
                            </button>
                        </div>
                    </div>

                    <div class="import-actions" style="margin-top: 1.5rem;">
                        <button class="btn btn-primary" onclick="processImport()" disabled id="import-btn">
                            <span>⬆️</span>
                            Importer le fichier
                        </button>
                        <button class="btn btn-secondary" onclick="validateImport()" disabled id="validate-btn">
                            <span>✅</span>
                            Valider d'abord
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export personnalisé -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>⚙️ Export personnalisé</h3>
            </div>
            <div class="admin-card-body">
                <div class="export-customizer">
                    <div class="form-group">
                        <label>Type de données :</label>
                        <select id="custom-export-type" class="form-control" onchange="updateExportOptions()">
                            <option value="rates">Tarifs transporteurs</option>
                            <option value="options">Options supplémentaires</option>
                            <option value="taxes">Taxes et majorations</option>
                            <option value="all">Toutes les données</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Format de fichier :</label>
                        <div class="format-options">
                            <label class="format-option">
                                <input type="radio" name="export-format" value="csv" checked>
                                <span>📄 CSV</span>
                            </label>
                            <label class="format-option">
                                <input type="radio" name="export-format" value="excel">
                                <span>📊 Excel</span>
                            </label>
                            <label class="format-option">
                                <input type="radio" name="export-format" value="json">
                                <span>💾 JSON</span>
                            </label>
                        </div>
                    </div>

                    <div class="export-filters" id="export-filters">
                        <!-- Filtres dynamiques selon le type -->
                    </div>

                    <div class="export-preview" id="export-preview">
                        <h5>📊 Aperçu</h5>
                        <div class="preview-stats">
                            <div class="stat-item">
                                <span>Lignes estimées :</span>
                                <span id="preview-rows">-</span>
                            </div>
                            <div class="stat-item">
                                <span>Taille estimée :</span>
                                <span id="preview-size">-</span>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary" onclick="executeCustomExport()">
                        <span>📤</span>
                        Exporter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique des imports/exports -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>📜 Historique des opérations</h3>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-secondary btn-sm" onclick="refreshHistory()">
                    <span>🔄</span>
                    Actualiser
                </button>
                <button class="btn btn-secondary btn-sm" onclick="clearHistory()">
                    <span>🗑️</span>
                    Effacer
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="operations-history">
                <?php
                // Simuler un historique (en production, récupérer depuis une table de logs)
                $operations = [
                    [
                        'id' => 1,
                        'type' => 'export',
                        'data_type' => 'rates',
                        'format' => 'csv',
                        'file_name' => 'tarifs_transporteurs_2025-01-15.csv',
                        'status' => 'success',
                        'rows' => 285,
                        'size' => '45.2 KB',
                        'user' => 'admin',
                        'date' => '2025-01-15 14:30:00',
                        'download_count' => 3
                    ],
                    [
                        'id' => 2,
                        'type' => 'import',
                        'data_type' => 'options',
                        'format' => 'csv',
                        'file_name' => 'nouvelles_options.csv',
                        'status' => 'success',
                        'rows' => 12,
                        'size' => '2.1 KB',
                        'user' => 'runser',
                        'date' => '2025-01-14 10:15:00',
                        'processed_rows' => 12,
                        'errors' => 0
                    ],
                    [
                        'id' => 3,
                        'type' => 'export',
                        'data_type' => 'all',
                        'format' => 'json',
                        'file_name' => 'backup_complet_2025-01-13.json',
                        'status' => 'success',
                        'rows' => 312,
                        'size' => '156.8 KB',
                        'user' => 'admin',
                        'date' => '2025-01-13 18:45:00',
                        'download_count' => 1
                    ],
                    [
                        'id' => 4,
                        'type' => 'import',
                        'data_type' => 'rates',
                        'format' => 'excel',
                        'file_name' => 'tarifs_xpo_update.xlsx',
                        'status' => 'error',
                        'rows' => 0,
                        'size' => '12.3 KB',
                        'user' => 'admin',
                        'date' => '2025-01-12 16:20:00',
                        'error_message' => 'Format de département invalide ligne 15'
                    ]
                ];
                ?>

                <?php if (empty($operations)): ?>
                    <div class="no-operations">
                        <div style="text-align: center; font-size: 2rem; color: #9ca3af;">📭</div>
                        <p style="text-align: center; color: #6b7280;">Aucune opération récente</p>
                    </div>
                <?php else: ?>
                    <div class="operations-table">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Données</th>
                                        <th>Fichier</th>
                                        <th>Statut</th>
                                        <th>Détails</th>
                                        <th>Utilisateur</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($operations as $op): ?>
                                    <tr>
                                        <td>
                                            <div class="operation-type <?= $op['type'] ?>">
                                                <?= $op['type'] === 'export' ? '📤' : '📥' ?>
                                                <?= ucfirst($op['type']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="data-type">
                                                <?= ucfirst($op['data_type']) ?>
                                            </div>
                                            <small class="format-badge"><?= strtoupper($op['format']) ?></small>
                                        </td>
                                        <td>
                                            <div class="file-info">
                                                <div class="file-name"><?= htmlspecialchars($op['file_name']) ?></div>
                                                <small class="file-size"><?= $op['size'] ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $op['status'] ?>">
                                                <?= $op['status'] === 'success' ? '✅' : '❌' ?>
                                                <?= ucfirst($op['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="operation-details">
                                                <?php if ($op['type'] === 'export'): ?>
                                                    <div><?= $op['rows'] ?> lignes</div>
                                                    <?php if (isset($op['download_count'])): ?>
                                                        <small><?= $op['download_count'] ?> téléchargement(s)</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($op['status'] === 'success'): ?>
                                                        <div><?= $op['processed_rows'] ?> lignes traitées</div>
                                                        <small><?= $op['errors'] ?> erreur(s)</small>
                                                    <?php else: ?>
                                                        <div class="error-message"><?= htmlspecialchars($op['error_message']) ?></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                👤 <?= htmlspecialchars($op['user']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="operation-date">
                                                <?= date('d/m/Y', strtotime($op['date'])) ?>
                                                <br>
                                                <small><?= date('H:i', strtotime($op['date'])) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="operation-actions">
                                                <?php if ($op['type'] === 'export' && $op['status'] === 'success'): ?>
                                                    <button class="btn btn-sm btn-secondary" 
                                                            onclick="redownloadFile('<?= $op['file_name'] ?>')" 
                                                            title="Télécharger à nouveau">
                                                        📥
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-secondary" 
                                                        onclick="showOperationDetails(<?= $op['id'] ?>)" 
                                                        title="Voir détails">
                                                    👁️
                                                </button>
                                                
                                                <?php if ($op['status'] === 'error'): ?>
                                                    <button class="btn btn-sm btn-warning" 
                                                            onclick="retryOperation(<?= $op['id'] ?>)" 
                                                            title="Réessayer">
                                                        🔄
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteOperation(<?= $op['id'] ?>)" 
                                                        title="Supprimer">
                                                    🗑️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de détails d'opération -->
    <div id="operation-details-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📊 Détails de l'opération</h3>
                <button class="modal-close" onclick="closeOperationModal()">&times;</button>
            </div>
            <div class="modal-body" id="operation-details-content">
                <!-- Contenu dynamique -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeOperationModal()">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques à l'import/export */
.export-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.export-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.export-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(0, 122, 204, 0.15);
    transform: translateY(-2px);
}

.export-card.complete {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-color: var(--primary-color);
}

.export-icon {
    font-size: 2rem;
    color: var(--primary-color);
}

.export-content {
    flex: 1;
}

.export-content h4 {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-size: 1.1rem;
}

.export-content p {
    margin: 0 0 0.75rem 0;
    color: #6b7280;
    font-size: 0.9rem;
}

.export-info {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.data-count {
    font-size: 0.8rem;
    color: #374151;
    font-weight: 500;
}

.file-format {
    background: #f3f4f6;
    color: #374151;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}

.export-action {
    font-size: 1.5rem;
    opacity: 0.6;
    transition: opacity 0.3s ease;
}

.export-card:hover .export-action {
    opacity: 1;
}

.import-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.file-upload-zone {
    position: relative;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
}

.file-upload-zone:hover {
    border-color: var(--primary-color);
    background: #f8fafc;
}

.file-upload-zone.dragover {
    border-color: var(--primary-color);
    background: #f0f9ff;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.upload-icon {
    font-size: 3rem;
    color: #9ca3af;
}

.upload-text p {
    margin: 0.25rem 0;
}

.upload-text strong {
    color: var(--primary-color);
}

#import-file {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.upload-info {
    margin-top: 1rem;
    color: #6b7280;
    font-size: 0.8rem;
}

.import-instructions {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
    border-left: 4px solid var(--primary-color);
}

.import-instructions h5 {
    margin: 0 0 0.5rem 0;
    color: var(--primary-color);
}

.template-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.export-customizer {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.format-options {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.format-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.format-option:hover {
    border-color: var(--primary-color);
}

.format-option input[type="radio"] {
    margin: 0;
}

.format-option input[type="radio"]:checked + span {
    color: var(--primary-color);
    font-weight: 600;
}

.export-preview {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
}

.preview-stats {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.operations-table {
    overflow-x: auto;
}

.operation-type {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.operation-type.export {
    background: #e0f2fe;
    color: #0277bd;
}

.operation-type.import {
    background: #e8f5e8;
    color: #2e7d32;
}

.data-type {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.format-badge {
    background: #f3f4f6;
    color: #374151;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 600;
}

.file-info {
    max-width: 200px;
}

.file-name {
    font-weight: 500;
    word-break: break-all;
    font-size: 0.9rem;
}

.file-size {
    color: #6b7280;
    font-size: 0.8rem;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge.success {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.error {
    background: #fee2e2;
    color: #991b1b;
}

.operation-details {
    font-size: 0.85rem;
}

.error-message {
    color: #dc2626;
    font-style: italic;
    max-width: 200px;
    word-wrap: break-word;
}

.user-info {
    font-size: 0.85rem;
    color: #6b7280;
}

.operation-date {
    font-size: 0.85rem;
    text-align: center;
}

.operation-actions {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.no-operations {
    text-align: center;
    padding: 2rem;
}

@media (max-width: 768px) {
    .export-grid {
        grid-template-columns: 1fr;
    }
    
    .export-card {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .format-options {
        flex-direction: column;
    }
    
    .template-buttons {
        justify-content: center;
    }
    
    .operation-actions {
        justify-content: center;
    }
}
</style>

<script>
// Variables globales pour l'import/export
let selectedFile = null;
let currentImportType = '';

// Fonctions d'export rapide
function exportData(type, format) {
    showAlert('info', 'Préparation de l\'export...');
    
    const link = document.createElement('a');
    link.href = `export.php?type=${type}&format=${format}`;
    link.download = `guldagil_${type}_${new Date().toISOString().split('T')[0]}.${format}`;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('success', `Export ${type} démarré`);
    
    // Ajouter à l'historique (simulation)
    setTimeout(() => {
        addToHistory({
            type: 'export',
            data_type: type,
            format: format,
            status: 'success'
        });
    }, 2000);
}

function refreshExportStats() {
    showAlert('info', 'Actualisation des statistiques...');
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Gestion de l'import
function updateImportInstructions() {
    const type = document.getElementById('import-type').value;
    const instructionsDiv = document.getElementById('import-instructions');
    currentImportType = type;
    
    const instructions = {
        'rates': {
            title: '💰 Import de tarifs',
            content: `
                <ul>
                    <li><strong>Colonnes requises :</strong> transporteur, num_departement, departement, delais, tarif_0_9, tarif_10_19, etc.</li>
                    <li><strong>Transporteurs acceptés :</strong> heppner, xpo, kn</li>
                    <li><strong>Départements :</strong> Format 2 chiffres (01-95)</li>
                    <li><strong>Tarifs :</strong> Nombres décimaux (ex: 12.50)</li>
                    <li><strong>Lignes vides :</strong> Seront ignorées</li>
                </ul>
            `
        },
        'options': {
            title: '⚙️ Import d\'options',
            content: `
                <ul>
                    <li><strong>Colonnes requises :</strong> transporteur, code_option, libelle, montant, unite, actif</li>
                    <li><strong>Codes option :</strong> rdv, premium13, premium18, datefixe, enlevement, etc.</li>
                    <li><strong>Unités :</strong> forfait, palette, pourcentage</li>
                    <li><strong>Actif :</strong> 1 pour actif, 0 pour inactif</li>
                </ul>
            `
        },
        'taxes': {
            title: '📋 Import de taxes',
            content: `
                <ul>
                    <li><strong>Attention :</strong> Les taxes sont critiques pour les calculs</li>
                    <li><strong>Sauvegarde recommandée</strong> avant import</li>
                    <li><strong>Validation obligatoire</strong> avant application</li>
                    <li><strong>Rollback possible</strong> en cas d\'erreur</li>
                </ul>
            `
        }
    };
    
    if (type && instructions[type]) {
        instructionsDiv.innerHTML = `
            <h5>${instructions[type].title}</h5>
            ${instructions[type].content}
        `;
    } else {
        instructionsDiv.innerHTML = `
            <h5>💡 Instructions d'import</h5>
            <p>Sélectionnez d'abord le type de données à importer pour voir les instructions spécifiques.</p>
        `;
    }
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    selectedFile = file;
    
    // Vérifications de base
    const maxSize = 10 * 1024 * 1024; // 10 MB
    if (file.size > maxSize) {
        showAlert('error', 'Le fichier est trop volumineux (max 10 MB)');
        return;
    }
    
    const allowedTypes = ['.csv', '.xlsx', '.xls'];
    const fileExt = '.' + file.name.split('.').pop().toLowerCase();
    if (!allowedTypes.includes(fileExt)) {
        showAlert('error', 'Format de fichier non supporté');
        return;
    }
    
    // Mettre à jour l'interface
    const uploadZone = document.getElementById('file-upload-zone');
    uploadZone.innerHTML = `
        <div class="file-selected">
            <div class="file-icon">📄</div>
            <div class="file-details">
                <div class="file-name">${file.name}</div>
                <div class="file-info">${(file.size / 1024).toFixed(1)} KB • ${fileExt.toUpperCase()}</div>
            </div>
            <button class="btn btn-sm btn-secondary" onclick="clearFileSelection()">❌ Supprimer</button>
        </div>
    `;
    
    // Activer les boutons
    document.getElementById('import-btn').disabled = false;
    document.getElementById('validate-btn').disabled = false;
    
    showAlert('success', 'Fichier sélectionné avec succès');
}

function clearFileSelection() {
    selectedFile = null;
    const uploadZone = document.getElementById('file-upload-zone');
    uploadZone.innerHTML = `
        <div class="upload-placeholder">
            <div class="upload-icon">📁</div>
            <div class="upload-text">
                <p><strong>Glissez-déposez un fichier CSV ici</strong></p>
                <p>ou cliquez pour sélectionner</p>
            </div>
            <input type="file" id="import-file" accept=".csv,.xlsx,.xls" onchange="handleFileSelect(event)">
        </div>
        <div class="upload-info">
            <small>Formats acceptés : CSV, Excel (.xlsx, .xls) • Taille max : 10 MB</small>
        </div>
    `;
    
    document.getElementById('import-btn').disabled = true;
    document.getElementById('validate-btn').disabled = true;
}

function validateImport() {
    if (!selectedFile || !currentImportType) {
        showAlert('error', 'Veuillez sélectionner un fichier et un type de données');
        return;
    }
    
    showAlert('info', 'Validation du fichier en cours...');
    
    // Simulation de validation
    setTimeout(() => {
        const validationResults = {
            total_rows: Math.floor(Math.random() * 100) + 10,
            valid_rows: Math.floor(Math.random() * 90) + 5,
            errors: Math.floor(Math.random() * 5),
            warnings: Math.floor(Math.random() * 3)
        };
        
        let message = `Validation terminée: ${validationResults.valid_rows} lignes valides`;
        if (validationResults.errors > 0) {
            message += `, ${validationResults.errors} erreur(s)`;
        }
        if (validationResults.warnings > 0) {
            message += `, ${validationResults.warnings} avertissement(s)`;
        }
        
        const alertType = validationResults.errors > 0 ? 'warning' : 'success';
        showAlert(alertType, message);
    }, 2000);
}

function processImport() {
    if (!selectedFile || !currentImportType) {
        showAlert('error', 'Veuillez sélectionner un fichier et un type de données');
        return;
    }
    
    if (!confirm('Êtes-vous sûr de vouloir importer ces données ? Cette action peut modifier les tarifs existants.')) {
        return;
    }
    
    showAlert('info', 'Import en cours...');
    
    // Simulation d'import
    setTimeout(() => {
        const success = Math.random() > 0.2; // 80% de réussite
        
        if (success) {
            showAlert('success', 'Import terminé avec succès');
            addToHistory({
                type: 'import',
                data_type: currentImportType,
                format: selectedFile.name.split('.').pop(),
                status: 'success',
                file_name: selectedFile.name
            });
            clearFileSelection();
        } else {
            showAlert('error', 'Erreur lors de l\'import. Vérifiez le format du fichier.');
            addToHistory({
                type: 'import',
                data_type: currentImportType,
                format: selectedFile.name.split('.').pop(),
                status: 'error',
                file_name: selectedFile.name
            });
        }
    }, 3000);
}

function downloadTemplate(type) {
    const link = document.createElement('a');
    link.href = `template.php?type=${type}`;
    link.download = `guldagil_template_${type}.csv`;
    link.click();
    
    showAlert('success', `Modèle ${type} téléchargé`);
}

// Export personnalisé
function updateExportOptions() {
    const type = document.getElementById('custom-export-type').value;
    const filtersDiv = document.getElementById('export-filters');
    
    let filtersHTML = '';
    
    switch (type) {
        case 'rates':
            filtersHTML = `
                <div class="form-group">
                    <label>Transporteur :</label>
                    <select class="form-control" onchange="updateExportPreview()">
                        <option value="">Tous les transporteurs</option>
                        <option value="heppner">Heppner</option>
                        <option value="xpo">XPO</option>
                        <option value="kn">Kuehne + Nagel</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" onchange="updateExportPreview()">
                        Inclure uniquement les tarifs configurés
                    </label>
                </div>
            `;
            break;
        case 'options':
            filtersHTML = `
                <div class="form-group">
                    <label>
                        <input type="checkbox" onchange="updateExportPreview()">
                        Inclure uniquement les options actives
                    </label>
                </div>
            `;
            break;
        case 'taxes':
            filtersHTML = `
                <div class="form-group">
                    <label>
                        <input type="checkbox" onchange="updateExportPreview()">
                        Inclure l'historique des modifications
                    </label>
                </div>
            `;
            break;
    }
    
    filtersDiv.innerHTML = filtersHTML;
    updateExportPreview();
}

function updateExportPreview() {
    const type = document.getElementById('custom-export-type').value;
    
    // Estimation simulée
    const estimates = {
        'rates': { rows: 285, size: '45.2 KB' },
        'options': { rows: 15, size: '3.1 KB' },
        'taxes': { rows: 4, size: '2.8 KB' },
        'all': { rows: 304, size: '51.1 KB' }
    };
    
    const estimate = estimates[type] || { rows: 0, size: '0 KB' };
    
    document.getElementById('preview-rows').textContent = estimate.rows;
    document.getElementById('preview-size').textContent = estimate.size;
}

function executeCustomExport() {
    const type = document.getElementById('custom-export-type').value;
    const format = document.querySelector('input[name="export-format"]:checked').value;
    
    showAlert('info', 'Préparation de l\'export personnalisé...');
    
    // Construire l'URL avec les filtres
    const params = new URLSearchParams({
        type: type,
        format: format
    });
    
    const link = document.createElement('a');
    link.href = `export.php?${params.toString()}`;
    link.download = `guldagil_custom_${type}_${new Date().toISOString().split('T')[0]}.${format}`;
    link.target = '_blank';
    link.click();
    
    showAlert('success', 'Export personnalisé démarré');
}

// Gestion de l'historique
function addToHistory(operation) {
    // En production, faire un appel API pour enregistrer en base
    console.log('Nouvelle opération:', operation);
}

function refreshHistory() {
    showAlert('info', 'Actualisation de l\'historique...');
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function clearHistory() {
    if (confirm('Êtes-vous sûr de vouloir effacer tout l\'historique ?')) {
        showAlert('success', 'Historique effacé');
        // En production, appeler l'API pour effacer
    }
}

function redownloadFile(fileName) {
    showAlert('info', `Téléchargement de ${fileName}...`);
    // En production, télécharger depuis le stockage
}

function showOperationDetails(operationId) {
    // Simuler les détails d'une opération
    const details = {
        id: operationId,
        summary: 'Détails de l\'opération',
        logs: [
            '14:30:00 - Début de l\'opération',
            '14:30:05 - Validation du fichier',
            '14:30:10 - Traitement des données',
            '14:30:15 - Opération terminée'
        ],
        statistics: {
            'Temps d\'exécution': '15 secondes',
            'Mémoire utilisée': '2.3 MB',
            'Lignes traitées': '285'
        }
    };
    
    const content = `
        <div class="operation-summary">
            <h4>Résumé</h4>
            <p>Opération #${details.id} exécutée avec succès</p>
        </div>
        
        <div class="operation-logs">
            <h4>Journal d'exécution</h4>
            <div class="logs-container">
                ${details.logs.map(log => `<div class="log-entry">${log}</div>`).join('')}
            </div>
        </div>
        
        <div class="operation-stats">
            <h4>Statistiques</h4>
            ${Object.entries(details.statistics).map(([key, value]) => 
                `<div class="stat-row"><span>${key}:</span><span>${value}</span></div>`
            ).join('')}
        </div>
    `;
    
    document.getElementById('operation-details-content').innerHTML = content;
    document.getElementById('operation-details-modal').style.display = 'flex';
}

function closeOperationModal() {
    document.getElementById('operation-details-modal').style.display = 'none';
}

function retryOperation(operationId) {
    if (confirm('Relancer cette opération ?')) {
        showAlert('info', `Relance de l'opération #${operationId}...`);
        // En production, relancer l'opération
    }
}

function deleteOperation(operationId) {
    if (confirm('Supprimer cette entrée de l\'historique ?')) {
        showAlert('success', 'Entrée supprimée');
        // En production, supprimer de la base
    }
}

// Drag & Drop
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.getElementById('file-upload-zone');
    
    if (uploadZone) {
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('import-file').files = files;
                handleFileSelect({ target: { files: files } });
            }
        });
    }
    
    // Initialiser les options d'export
    updateExportOptions();
});

// Styles additionnels pour les détails d'opération
const additionalStyles = `
.file-selected {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f0f9ff;
    border-radius: 6px;
}

.file-icon {
    font-size: 2rem;
    color: var(--primary-color);
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 600;
    color: #374151;
}

.file-info {
    font-size: 0.8rem;
    color: #6b7280;
}

.operation-summary,
.operation-logs,
.operation-stats {
    margin-bottom: 1.5rem;
}

.logs-container {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 1rem;
    max-height: 200px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 0.85rem;
}

.log-entry {
    padding: 0.25rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.log-entry:last-child {
    border-bottom: none;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.stat-row:last-child {
    border-bottom: none;
}
`;

if (!document.getElementById('import-additional-styles')) {
    const style = document.createElement('style');
    style.id = 'import-additional-styles';
    style.textContent = additionalStyles;
    document.head.appendChild(style);
}
</script>

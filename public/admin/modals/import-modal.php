<?php
// public/admin/modals/import-modal.php - Modal d'import modulaire
?>
<!-- Modal d'import de données -->
<div id="import-modal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>📁 Import de données</h3>
            <button class="modal-close" onclick="closeImportModal()" title="Fermer">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Étapes du processus -->
            <div class="import-steps">
                <div class="step step-active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-label">Sélection fichier</span>
                </div>
                <div class="step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-label">Validation</span>
                </div>
                <div class="step" data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-label">Aperçu</span>
                </div>
                <div class="step" data-step="4">
                    <span class="step-number">4</span>
                    <span class="step-label">Import</span>
                </div>
            </div>

            <!-- Section 1: Sélection du fichier -->
            <div id="import-step-1" class="import-section">
                <div class="section-header">
                    <h4>📂 Sélection du fichier</h4>
                    <p>Choisissez le fichier à importer (CSV, Excel)</p>
                </div>

                <!-- Zone de drop -->
                <div id="file-drop-zone" class="drop-zone">
                    <div class="drop-zone-content">
                        <div class="drop-icon">📁</div>
                        <h5>Glissez votre fichier ici</h5>
                        <p>ou cliquez pour sélectionner</p>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('import-file-input').click()">
                            📎 Parcourir les fichiers
                        </button>
                        <input type="file" id="import-file-input" accept=".csv,.xlsx,.xls" style="display: none;">
                    </div>
                    <div class="drop-zone-help">
                        <small>
                            <strong>Formats acceptés :</strong> CSV, Excel (.xlsx, .xls)<br>
                            <strong>Taille maximum :</strong> 5MB<br>
                            <strong>Encodage :</strong> UTF-8 recommandé
                        </small>
                    </div>
                </div>

                <!-- Informations du fichier sélectionné -->
                <div id="file-info-container" style="display: none;">
                    <!-- Sera rempli dynamiquement -->
                </div>

                <!-- Sélection du type d'import -->
                <div class="import-type-selection">
                    <label for="import-type-select">Type d'import :</label>
                    <select id="import-type-select" class="form-control">
                        <option value="">Sélectionner le type de données...</option>
                        <option value="rates">💰 Tarifs transporteurs</option>
                        <option value="options">⚙️ Options supplémentaires</option>
                    </select>
                    <div class="import-type-help">
                        <div class="help-item" data-type="rates">
                            <strong>Tarifs :</strong> Import des tarifs par transporteur et département
                        </div>
                        <div class="help-item" data-type="options">
                            <strong>Options :</strong> Import des options de transport (RDV, Premium, etc.)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Validation -->
            <div id="import-step-2" class="import-section" style="display: none;">
                <div class="section-header">
                    <h4>✅ Validation des données</h4>
                    <p>Vérification de la structure et du contenu du fichier</p>
                </div>

                <!-- Loading de validation -->
                <div id="validation-loading" class="import-loading" style="display: none;">
                    <div class="loading-content">
                        <div class="spinner"></div>
                        <div class="loading-text">Validation en cours...</div>
                        <div class="loading-detail">Analyse de la structure et des données</div>
                    </div>
                </div>

                <!-- Résultats de validation -->
                <div id="validation-results" style="display: none;">
                    <!-- Sera rempli par JavaScript -->
                </div>
            </div>

            <!-- Section 3: Aperçu -->
            <div id="import-step-3" class="import-section" style="display: none;">
                <div class="section-header">
                    <h4>👁️ Aperçu des modifications</h4>
                    <p>Prévisualisation des données qui seront importées</p>
                </div>

                <!-- Loading d'aperçu -->
                <div id="preview-loading" class="import-loading" style="display: none;">
                    <div class="loading-content">
                        <div class="spinner"></div>
                        <div class="loading-text">Génération de l'aperçu...</div>
                        <div class="loading-detail">Analyse des conflits et préparation des modifications</div>
                    </div>
                </div>

                <!-- Résultats d'aperçu -->
                <div id="preview-results" style="display: none;">
                    <!-- Sera rempli par JavaScript -->
                </div>
            </div>

            <!-- Section 4: Import final -->
            <div id="import-step-4" class="import-section" style="display: none;">
                <div class="section-header">
                    <h4>🚀 Import en cours</h4>
                    <p>Exécution de l'import des données</p>
                </div>

                <!-- Loading d'import -->
                <div id="import-loading" class="import-loading" style="display: none;">
                    <div class="loading-content">
                        <div class="spinner"></div>
                        <div class="loading-text">Import en cours...</div>
                        <div class="loading-detail">Traitement et sauvegarde des données</div>
                        <div class="loading-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 0%"></div>
                            </div>
                            <div class="progress-text">0%</div>
                        </div>
                    </div>
                </div>

                <!-- Résultats d'import -->
                <div id="import-results" style="display: none;">
                    <!-- Sera rempli par JavaScript -->
                </div>
            </div>

            <!-- Messages d'erreur globaux -->
            <div id="import-error-container" class="error-container" style="display: none;"></div>
        </div>
        
        <div class="modal-footer">
            <div class="modal-actions-left">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">
                    ❌ Annuler
                </button>
                <button type="button" id="import-back-btn" class="btn btn-secondary" style="display: none;" onclick="goToPreviousImportStep()">
                    ⬅️ Retour
                </button>
            </div>
            
            <div class="modal-actions-right">
                <button type="button" id="validate-import-btn" class="btn btn-primary import-action-btn" disabled onclick="processImport('validate')">
                    ✅ Valider les données
                </button>
                <button type="button" id="preview-import-btn" class="btn btn-warning import-action-btn" style="display: none;" onclick="processImport('preview')">
                    👁️ Aperçu des modifications
                </button>
                <button type="button" id="execute-import-btn" class="btn btn-success import-action-btn" style="display: none;" onclick="confirmAndExecuteImport()">
                    🚀 Exécuter l'import
                </button>
                <button type="button" id="finish-import-btn" class="btn btn-success" style="display: none;" onclick="finishImport()">
                    ✅ Terminer
                </button>
                <button type="button" id="new-import-btn" class="btn btn-primary" style="display: none;" onclick="startNewImport()">
                    🔄 Nouvel import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template pour les informations de fichier -->
<template id="file-info-template">
    <div class="file-info-card">
        <div class="file-info-header">
            <span class="file-icon">📄</span>
            <div class="file-details">
                <div class="file-name">[FILENAME]</div>
                <div class="file-meta">
                    <span class="file-size">Taille: [SIZE]</span>
                    <span class="file-modified">Modifié: [MODIFIED]</span>
                    <span class="file-type">Type: [TYPE]</span>
                </div>
            </div>
            <div class="file-status">
                <span class="status-badge status-ready">✅ Prêt</span>
            </div>
            <div class="file-actions">
                <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelectedFile()">
                    🗑️ Supprimer
                </button>
            </div>
        </div>
    </div>
</template>

<!-- Template pour les statistiques de validation -->
<template id="validation-stats-template">
    <div class="validation-summary">
        <h5>📊 Résultats de validation</h5>
        <div class="stats-grid">
            <div class="stat-card [TOTAL_CLASS]">
                <div class="stat-value">[TOTAL_ROWS]</div>
                <div class="stat-label">Lignes total</div>
            </div>
            <div class="stat-card [VALID_CLASS]">
                <div class="stat-value">[VALID_ROWS]</div>
                <div class="stat-label">Lignes valides</div>
            </div>
            <div class="stat-card [INVALID_CLASS]">
                <div class="stat-value">[INVALID_ROWS]</div>
                <div class="stat-label">Lignes invalides</div>
            </div>
            <div class="stat-card [WARNINGS_CLASS]">
                <div class="stat-value">[WARNINGS_COUNT]</div>
                <div class="stat-label">Avertissements</div>
            </div>
        </div>
    </div>
</template>

<!-- Template pour les résultats d'import -->
<template id="import-results-template">
    <div class="import-summary success">
        <div class="summary-header">
            <span class="summary-icon">🎉</span>
            <h5>Import terminé avec succès</h5>
        </div>
        <div class="results-grid">
            <div class="result-card result-total">
                <div class="result-value">[TOTAL_ROWS]</div>
                <div class="result-label">Lignes traitées</div>
            </div>
            <div class="result-card result-success">
                <div class="result-value">[IMPORTED]</div>
                <div class="result-label">Nouvelles entrées</div>
            </div>
            <div class="result-card result-warning">
                <div class="result-value">[UPDATED]</div>
                <div class="result-label">Mises à jour</div>
            </div>
            <div class="result-card result-danger">
                <div class="result-value">[SKIPPED]</div>
                <div class="result-label">Ignorées</div>
            </div>
        </div>
        <div class="success-rate">
            <div class="rate-label">Taux de réussite</div>
            <div class="rate-value">[SUCCESS_RATE]%</div>
        </div>
    </div>
</template>

<!-- Styles spécifiques à la modal (peut être déplacé dans admin-style.css) -->
<style>
/* Modal d'import */
.modal-large {
    max-width: 900px;
    width: 95%;
}

/* Étapes du processus */
.import-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    position: relative;
}

.step::after {
    content: '';
    position: absolute;
    top: 15px;
    left: 60%;
    width: 80%;
    height: 2px;
    background: #ddd;
    z-index: 0;
}

.step:last-child::after {
    display: none;
}

.step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #ddd;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    position: relative;
    z-index: 1;
}

.step.step-active .step-number {
    background: #007acc;
}

.step.step-completed .step-number {
    background: #4CAF50;
}

.step-label {
    font-size: 0.85rem;
    color: #666;
    text-align: center;
}

.step.step-active .step-label {
    color: #007acc;
    font-weight: 600;
}

/* Sections d'import */
.import-section {
    margin-bottom: 1.5rem;
}

.section-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.section-header h4 {
    margin: 0 0 0.5rem 0;
    color: #007acc;
}

.section-header p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

/* Zone de drop */
.drop-zone {
    border: 2px dashed #007acc;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    background: linear-gradient(135deg, #f8f9ff 0%, #eef6ff 100%);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    position: relative;
}

.drop-zone.drag-over {
    border-color: #4CAF50;
    background: linear-gradient(135deg, #f0fff0 0%, #e8f5e8 100%);
    transform: scale(1.02);
}

.drop-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.drop-zone h5 {
    margin: 0 0 0.5rem 0;
    color: #007acc;
}

.drop-zone p {
    margin: 0 0 1rem 0;
    color: #666;
}

.drop-zone-help {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
    font-size: 0.85rem;
    color: #666;
}

/* Informations fichier */
.file-info-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.file-info-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.file-icon {
    font-size: 2rem;
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.file-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: #666;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-ready {
    background: #d4edda;
    color: #155724;
}

/* Sélection type d'import */
.import-type-selection {
    margin-bottom: 1.5rem;
}

.import-type-selection label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.import-type-help {
    margin-top: 0.5rem;
}

.help-item {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    display: none;
}

.help-item.active {
    display: block;
}

/* Loading states */
.import-loading {
    text-align: center;
    padding: 2rem;
}

.loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007acc;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.loading-text {
    font-weight: 600;
    color: #007acc;
}

.loading-detail {
    font-size: 0.9rem;
    color: #666;
}

.loading-progress {
    width: 100%;
    max-width: 300px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #007acc, #4CAF50);
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.85rem;
    color: #666;
}

/* Statistiques et résultats */
.stats-grid, .results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
}

.stat-card, .result-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}

.stat-value, .result-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-label, .result-label {
    font-size: 0.85rem;
    color: #666;
}

.stat-success, .result-success { border-left: 4px solid #4CAF50; }
.stat-warning, .result-warning { border-left: 4px solid #ff9800; }
.stat-danger, .result-danger { border-left: 4px solid #f44336; }
.stat-info, .result-total { border-left: 4px solid #007acc; }

/* Actions de footer */
.modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.modal-actions-left, .modal-actions-right {
    display: flex;
    gap: 0.5rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .modal-large {
        width: 98%;
        margin: 1%;
    }
    
    .import-steps {
        flex-direction: column;
        gap: 1rem;
    }
    
    .step::after {
        display: none;
    }
    
    .file-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .modal-footer {
        flex-direction: column;
        gap: 1rem;
    }
    
    .modal-actions-left, .modal-actions-right {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php
// public/admin/modals/export-modal.php - Modal d'export avancée
?>
<!-- Modal d'export de données -->
<div id="export-modal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>📤 Export de données</h3>
            <button class="modal-close" onclick="closeExportModal()" title="Fermer">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Onglets d'export -->
            <div class="export-tabs">
                <button class="export-tab-btn active" data-tab="quick">
                    ⚡ Export rapide
                </button>
                <button class="export-tab-btn" data-tab="advanced">
                    🔧 Export avancé
                </button>
                <button class="export-tab-btn" data-tab="scheduled">
                    ⏰ Export programmé
                </button>
            </div>

            <!-- Onglet Export rapide -->
            <div id="export-tab-quick" class="export-tab-content active">
                <div class="section-header">
                    <h4>⚡ Export rapide</h4>
                    <p>Téléchargement immédiat des données principales</p>
                </div>

                <div class="quick-export-grid">
                    <!-- Export complet -->
                    <div class="export-card featured">
                        <div class="export-card-header">
                            <span class="export-icon">📦</span>
                            <h5>Export complet</h5>
                        </div>
                        <div class="export-card-body">
                            <p>Toutes les données (tarifs, options, taxes)</p>
                            <div class="export-stats">
                                <span class="stat">~95 départements</span>
                                <span class="stat">3 transporteurs</span>
                                <span class="stat">Format CSV/Excel</span>
                            </div>
                        </div>
                        <div class="export-card-actions">
                            <button class="btn btn-primary btn-sm" onclick="quickExport('all', 'csv')">
                                📄 CSV
                            </button>
                            <button class="btn btn-success btn-sm" onclick="quickExport('all', 'xlsx')">
                                📊 Excel
                            </button>
                            <button class="btn btn-info btn-sm" onclick="quickExport('all', 'json')">
                                📋 JSON
                            </button>
                        </div>
                    </div>

                    <!-- Export tarifs -->
                    <div class="export-card">
                        <div class="export-card-header">
                            <span class="export-icon">💰</span>
                            <h5>Tarifs transporteurs</h5>
                        </div>
                        <div class="export-card-body">
                            <p>Grilles tarifaires par département</p>
                            <div class="export-preview">
                                <small>Heppner, XPO, Kuehne+Nagel</small>
                            </div>
                        </div>
                        <div class="export-card-actions">
                            <button class="btn btn-primary btn-sm" onclick="quickExport('rates', 'csv')">
                                📄 CSV
                            </button>
                            <button class="btn btn-success btn-sm" onclick="quickExport('rates', 'xlsx')">
                                📊 Excel
                            </button>
                        </div>
                    </div>

                    <!-- Export options -->
                    <div class="export-card">
                        <div class="export-card-header">
                            <span class="export-icon">⚙️</span>
                            <h5>Options supplémentaires</h5>
                        </div>
                        <div class="export-card-body">
                            <p>Services et options de transport</p>
                            <div class="export-preview">
                                <small>RDV, Premium, Date fixe...</small>
                            </div>
                        </div>
                        <div class="export-card-actions">
                            <button class="btn btn-primary btn-sm" onclick="quickExport('options', 'csv')">
                                📄 CSV
                            </button>
                            <button class="btn btn-success btn-sm" onclick="quickExport('options', 'xlsx')">
                                📊 Excel
                            </button>
                        </div>
                    </div>

                    <!-- Export taxes -->
                    <div class="export-card">
                        <div class="export-card-header">
                            <span class="export-icon">📋</span>
                            <h5>Taxes et majorations</h5>
                        </div>
                        <div class="export-card-body">
                            <p>Configuration des taxes par transporteur</p>
                            <div class="export-preview">
                                <small>ADR, IDF, Saisonnière...</small>
                            </div>
                        </div>
                        <div class="export-card-actions">
                            <button class="btn btn-primary btn-sm" onclick="quickExport('taxes', 'csv')">
                                📄 CSV
                            </button>
                            <button class="btn btn-success btn-sm" onclick="quickExport('taxes', 'xlsx')">
                                📊 Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Export avancé -->
            <div id="export-tab-advanced" class="export-tab-content">
                <div class="section-header">
                    <h4>🔧 Export personnalisé</h4>
                    <p>Configuration avancée avec filtres et options</p>
                </div>

                <form id="advanced-export-form" class="advanced-export-form">
                    <!-- Sélection des données -->
                    <div class="form-section">
                        <h5>📊 Données à exporter</h5>
                        <div class="data-selection">
                            <label class="checkbox-option">
                                <input type="checkbox" name="export_data[]" value="rates" checked>
                                <span class="checkbox-custom"></span>
                                <div class="option-content">
                                    <strong>💰 Tarifs transporteurs</strong>
                                    <small>Grilles tarifaires complètes</small>
                                </div>
                            </label>
                            <label class="checkbox-option">
                                <input type="checkbox" name="export_data[]" value="options">
                                <span class="checkbox-custom"></span>
                                <div class="option-content">
                                    <strong>⚙️ Options supplémentaires</strong>
                                    <small>Services additionnels</small>
                                </div>
                            </label>
                            <label class="checkbox-option">
                                <input type="checkbox" name="export_data[]" value="taxes">
                                <span class="checkbox-custom"></span>
                                <div class="option-content">
                                    <strong>📋 Taxes et majorations</strong>
                                    <small>Configuration fiscale</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="form-section">
                        <h5>🔍 Filtres</h5>
                        <div class="filters-grid">
                            <!-- Filtre transporteurs -->
                            <div class="filter-group">
                                <label>Transporteurs</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-mini">
                                        <input type="checkbox" name="carriers[]" value="heppner" checked>
                                        <span class="checkbox-custom-mini"></span>
                                        Heppner
                                    </label>
                                    <label class="checkbox-mini">
                                        <input type="checkbox" name="carriers[]" value="xpo" checked>
                                        <span class="checkbox-custom-mini"></span>
                                        XPO
                                    </label>
                                    <label class="checkbox-mini">
                                        <input type="checkbox" name="carriers[]" value="kn" checked>
                                        <span class="checkbox-custom-mini"></span>
                                        Kuehne+Nagel
                                    </label>
                                </div>
                            </div>

                            <!-- Filtre départements -->
                            <div class="filter-group">
                                <label>Départements</label>
                                <select name="departments_filter" class="form-control">
                                    <option value="all">Tous les départements</option>
                                    <option value="idf">Île-de-France uniquement</option>
                                    <option value="est">Grand Est uniquement</option>
                                    <option value="custom">Sélection personnalisée</option>
                                </select>
                                <input type="text" name="custom_departments" placeholder="Ex: 67,68,75" 
                                       class="form-control" style="display: none;">
                            </div>

                            <!-- Filtre statut -->
                            <div class="filter-group">
                                <label>Statut des données</label>
                                <select name="status_filter" class="form-control">
                                    <option value="all">Toutes les données</option>
                                    <option value="complete">Données complètes uniquement</option>
                                    <option value="active">Actives uniquement</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Format et options -->
                    <div class="form-section">
                        <h5>⚙️ Format et options</h5>
                        <div class="format-options">
                            <!-- Format de fichier -->
                            <div class="format-selection">
                                <label>Format de sortie</label>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="export_format" value="csv" checked>
                                        <span class="radio-custom"></span>
                                        <div class="option-content">
                                            <strong>📄 CSV</strong>
                                            <small>Compatible Excel, UTF-8</small>
                                        </div>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="export_format" value="xlsx">
                                        <span class="radio-custom"></span>
                                        <div class="option-content">
                                            <strong>📊 Excel</strong>
                                            <small>Format natif Excel</small>
                                        </div>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="export_format" value="json">
                                        <span class="radio-custom"></span>
                                        <div class="option-content">
                                            <strong>📋 JSON</strong>
                                            <small>Format technique</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Options avancées -->
                            <div class="advanced-options">
                                <label>Options d'export</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-mini">
                                        <input type="checkbox" name="include_metadata" checked>
                                        <span class="checkbox-custom-mini"></span>
                                        Inclure les métadonnées
                                    </label>
                                    <label class="checkbox-mini">
                                        <input type="checkbox" name="include_timestamps" checked>
                                        <span class="checkbox-custom-mini"></span>
                                        Horodatage
                                    </label>
                                    <label class="checkbox-mini">
                                        <input type="checkbox" name="compress_output">
                                        <span class="checkbox-custom-mini"></span>
                                        Compresser (ZIP)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aperçu des paramètres -->
                    <div class="form-section">
                        <h5>👁️ Aperçu de l'export</h5>
                        <div id="export-preview" class="export-preview-box">
                            <div class="preview-loading">
                                <span class="preview-icon">📋</span>
                                <span>Sélectionnez vos paramètres pour voir l'aperçu</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Onglet Export programmé -->
            <div id="export-tab-scheduled" class="export-tab-content">
                <div class="section-header">
                    <h4>⏰ Export programmé</h4>
                    <p>Planification d'exports automatiques</p>
                </div>

                <div class="scheduled-exports">
                    <!-- Configuration planification -->
                    <div class="form-section">
                        <h5>📅 Nouvelle planification</h5>
                        <div class="schedule-config">
                            <div class="schedule-row">
                                <div class="form-group">
                                    <label>Type d'export</label>
                                    <select name="schedule_type" class="form-control">
                                        <option value="all">Export complet</option>
                                        <option value="rates">Tarifs uniquement</option>
                                        <option value="options">Options uniquement</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Fréquence</label>
                                    <select name="schedule_frequency" class="form-control">
                                        <option value="daily">Quotidien</option>
                                        <option value="weekly">Hebdomadaire</option>
                                        <option value="monthly">Mensuel</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Heure</label>
                                    <input type="time" name="schedule_time" value="08:00" class="form-control">
                                </div>
                            </div>
                            <div class="schedule-row">
                                <div class="form-group">
                                    <label>Email de notification</label>
                                    <input type="email" name="notification_email" 
                                           placeholder="admin@guldagil.com" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Format</label>
                                    <select name="schedule_format" class="form-control">
                                        <option value="csv">CSV</option>
                                        <option value="xlsx">Excel</option>
                                    </select>
                                </div>
                                <div class="form-group form-actions">
                                    <button type="button" class="btn btn-primary" onclick="createScheduledExport()">
                                        ➕ Créer la planification
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Exports programmés existants -->
                    <div class="form-section">
                        <h5>📋 Planifications actives</h5>
                        <div id="scheduled-exports-list" class="scheduled-list">
                            <!-- Sera rempli dynamiquement -->
                            <div class="no-schedules">
                                <span class="no-schedules-icon">📭</span>
                                <p>Aucune planification configurée</p>
                                <small>Créez votre première planification ci-dessus</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone de statut des exports -->
            <div id="export-status" class="export-status" style="display: none;">
                <!-- Sera rempli dynamiquement -->
            </div>
        </div>
        
        <div class="modal-footer">
            <div class="modal-actions-left">
                <button type="button" class="btn btn-secondary" onclick="closeExportModal()">
                    ❌ Fermer
                </button>
                <button type="button" class="btn btn-info" onclick="previewAdvancedExport()">
                    👁️ Aperçu
                </button>
            </div>
            
            <div class="modal-actions-right">
                <button type="button" id="export-advanced-btn" class="btn btn-primary" onclick="executeAdvancedExport()">
                    📤 Exporter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template pour export en cours -->
<template id="export-progress-template">
    <div class="export-progress">
        <div class="progress-header">
            <span class="progress-icon">📤</span>
            <div class="progress-info">
                <div class="progress-title">[EXPORT_TYPE]</div>
                <div class="progress-detail">[EXPORT_DETAIL]</div>
            </div>
            <div class="progress-status">[STATUS]</div>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: [PROGRESS]%"></div>
        </div>
        <div class="progress-footer">
            <span class="progress-time">Temps estimé: [TIME]</span>
            <span class="progress-size">Taille: [SIZE]</span>
        </div>
    </div>
</template>

<!-- Template pour planification -->
<template id="scheduled-export-template">
    <div class="scheduled-item">
        <div class="scheduled-header">
            <span class="scheduled-icon">⏰</span>
            <div class="scheduled-info">
                <div class="scheduled-title">[TITLE]</div>
                <div class="scheduled-detail">[DETAIL]</div>
            </div>
            <div class="scheduled-status">
                <span class="status-badge [STATUS_CLASS]">[STATUS]</span>
            </div>
            <div class="scheduled-actions">
                <button class="btn btn-sm btn-secondary" onclick="editScheduledExport('[ID]')">✏️</button>
                <button class="btn btn-sm btn-danger" onclick="deleteScheduledExport('[ID]')">🗑️</button>
            </div>
        </div>
        <div class="scheduled-meta">
            <span>Prochaine exécution: [NEXT_RUN]</span>
            <span>Dernière exécution: [LAST_RUN]</span>
        </div>
    </div>
</template>

<!-- Styles spécifiques à la modal d'export -->
<style>
/* Onglets d'export */
.export-tabs {
    display: flex;
    border-bottom: 2px solid #eee;
    margin-bottom: 2rem;
}

.export-tab-btn {
    padding: 1rem 2rem;
    border: none;
    background: transparent;
    cursor: pointer;
    color: #666;
    font-weight: 600;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.export-tab-btn.active {
    color: #007acc;
    border-bottom-color: #007acc;
    background: #f8f9ff;
}

.export-tab-btn:hover:not(.active) {
    color: #007acc;
    background: #f8f9fa;
}

.export-tab-content {
    display: none;
}

.export-tab-content.active {
    display: block;
}

/* Export rapide */
.quick-export-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.export-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.export-card:hover {
    border-color: #007acc;
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 122, 204, 0.1);
}

.export-card.featured {
    border-color: #4CAF50;
    background: linear-gradient(135deg, #f8fff8 0%, #f0fff0 100%);
}

.export-card-header {
    padding: 1.5rem 1.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.export-icon {
    font-size: 2rem;
}

.export-card-header h5 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
}

.export-card-body {
    padding: 0 1.5rem 1rem;
}

.export-card-body p {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
}

.export-stats {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.export-stats .stat {
    font-size: 0.75rem;
    background: #f0f0f0;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: #666;
}

.export-preview {
    font-style: italic;
}

.export-card-actions {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

/* Export avancé */
.advanced-export-form {
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h5 {
    margin: 0 0 1rem 0;
    color: #007acc;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Sélection des données */
.data-selection {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.checkbox-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.checkbox-option:hover {
    border-color: #007acc;
    background: #f8f9ff;
}

.checkbox-option input:checked + .checkbox-custom + .option-content {
    color: #007acc;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 4px;
    background: white;
    position: relative;
}

.checkbox-option input {
    display: none;
}

.checkbox-option input:checked + .checkbox-custom {
    background: #007acc;
    border-color: #007acc;
}

.checkbox-option input:checked + .checkbox-custom::after {
    content: '✓';
    position: absolute;
    top: -2px;
    left: 3px;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.option-content strong {
    display: block;
    margin-bottom: 0.25rem;
}

.option-content small {
    color: #666;
    font-size: 0.85rem;
}

/* Filtres */
.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.checkbox-mini {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
}

.checkbox-custom-mini {
    width: 16px;
    height: 16px;
    border: 2px solid #ddd;
    border-radius: 3px;
    background: white;
    position: relative;
}

.checkbox-mini input {
    display: none;
}

.checkbox-mini input:checked + .checkbox-custom-mini {
    background: #007acc;
    border-color: #007acc;
}

.checkbox-mini input:checked + .checkbox-custom-mini::after {
    content: '✓';
    position: absolute;
    top: -2px;
    left: 2px;
    color: white;
    font-weight: bold;
    font-size: 12px;
}

/* Format et options */
.format-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.radio-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.radio-option:hover {
    border-color: #007acc;
    background: #f8f9ff;
}

.radio-custom {
    width: 18px;
    height: 18px;
    border: 2px solid #ddd;
    border-radius: 50%;
    background: white;
    position: relative;
}

.radio-option input {
    display: none;
}

.radio-option input:checked + .radio-custom {
    border-color: #007acc;
}

.radio-option input:checked + .radio-custom::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #007acc;
}

/* Aperçu export */
.export-preview-box {
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: #666;
}

.preview-icon {
    font-size: 2rem;
}

/* Export programmé */
.schedule-config {
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
}

.schedule-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.schedule-row:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.form-actions {
    display: flex;
    align-items: end;
}

.scheduled-list {
    max-height: 300px;
    overflow-y: auto;
}

.no-schedules {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.no-schedules-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
}

.scheduled-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.scheduled-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.scheduled-icon {
    font-size: 1.5rem;
}

.scheduled-info {
    flex: 1;
}

.scheduled-title {
    font-weight: 600;
    color: #333;
}

.scheduled-detail {
    font-size: 0.85rem;
    color: #666;
}

.scheduled-actions {
    display: flex;
    gap: 0.5rem;
}

.scheduled-meta {
    font-size: 0.8rem;
    color: #888;
    display: flex;
    gap: 1rem;
}

/* Statut export */
.export-progress {
    background: #f8f9ff;
    border: 1px solid #007acc;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.progress-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.progress-icon {
    font-size: 1.5rem;
}

.progress-info {
    flex: 1;
}

.progress-title {
    font-weight: 600;
    color: #007acc;
}

.progress-detail {
    font-size: 0.85rem;
    color: #666;
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
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progress-footer {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .quick-export-grid {
        grid-template-columns: 1fr;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .format-options {
        grid-template-columns: 1fr;
    }
    
    .schedule-row {
        grid-template-columns: 1fr;
    }
    
    .export-tabs {
        flex-direction: column;
    }
    
    .export-tab-btn {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #eee;
        border-right: none;
    }
    
    .export-tab-btn.active {
        border-bottom: 1px solid #007acc;
        border-right: none;
    }
}
</style>

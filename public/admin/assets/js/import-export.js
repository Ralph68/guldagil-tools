// public/admin/assets/js/import-export.js - Interface import/export avancée
console.log('📤 Chargement du module import/export...');

// Variables globales
let currentImportData = null;
let currentImportType = null;
let uploadedFileName = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Module import/export initialisé');
    initializeImportExportInterface();
});

function initializeImportExportInterface() {
    console.log('🔧 Initialisation interface import/export');
    
    // Gestion du drag & drop
    setupDragAndDrop();
    
    // Event listeners
    setupEventListeners();
    
    // Initialiser les templates disponibles
    loadAvailableTemplates();
}

function setupEventListeners() {
    // Upload de fichier
    const fileInput = document.getElementById('import-file-input');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelect);
    }
    
    // Boutons de template
    document.querySelectorAll('.template-download-btn').forEach(btn => {
        btn.addEventListener('click', handleTemplateDownload);
    });
    
    // Boutons d'export
    document.querySelectorAll('.export-btn').forEach(btn => {
        btn.addEventListener('click', handleExport);
    });
    
    // Modal d'import
    const importBtn = document.getElementById('start-import-btn');
    if (importBtn) {
        importBtn.addEventListener('click', showImportModal);
    }
    
    // Boutons de navigation import
    const validateBtn = document.getElementById('validate-import-btn');
    const previewBtn = document.getElementById('preview-import-btn');
    const executeBtn = document.getElementById('execute-import-btn');
    
    if (validateBtn) validateBtn.addEventListener('click', () => processImport('validate'));
    if (previewBtn) previewBtn.addEventListener('click', () => processImport('preview'));
    if (executeBtn) executeBtn.addEventListener('click', () => processImport('import'));
}

/**
 * Configuration du drag & drop
 */
function setupDragAndDrop() {
    const dropZone = document.getElementById('file-drop-zone');
    if (!dropZone) return;
    
    // Prévenir les comportements par défaut
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    // Highlights
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    // Gestion du drop
    dropZone.addEventListener('drop', handleDrop, false);
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight() {
        dropZone.classList.add('drag-over');
    }
    
    function unhighlight() {
        dropZone.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        const files = Array.from(e.dataTransfer.files);
        if (files.length > 0) {
            handleFileSelect({ target: { files: files } });
        }
    }
}

/**
 * Gestion de la sélection de fichier
 */
function handleFileSelect(event) {
    const files = event.target.files;
    if (!files || files.length === 0) return;
    
    const file = files[0];
    
    // Validation côté client
    if (!validateFile(file)) {
        return;
    }
    
    // Afficher les informations du fichier
    displayFileInfo(file);
    
    // Activer les boutons d'import
    enableImportButtons(true);
    
    uploadedFileName = file.name;
    showSuccess(`Fichier "${file.name}" prêt pour l'import`);
}

/**
 * Validation du fichier côté client
 */
function validateFile(file) {
    // Taille (5MB max)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        showError('Fichier trop volumineux (maximum 5MB)');
        return false;
    }
    
    // Extension
    const allowedExtensions = ['csv', 'xlsx', 'xls'];
    const extension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(extension)) {
        showError('Format de fichier non supporté (.csv, .xlsx, .xls uniquement)');
        return false;
    }
    
    // Type MIME (basique)
    const allowedTypes = [
        'text/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if (file.type && !allowedTypes.includes(file.type)) {
        showWarning('Type de fichier non reconnu, mais l\'extension semble correcte');
    }
    
    return true;
}

/**
 * Affiche les informations du fichier
 */
function displayFileInfo(file) {
    const container = document.getElementById('file-info-container');
    if (!container) return;
    
    const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
    const lastModified = new Date(file.lastModified).toLocaleString('fr-FR');
    
    container.innerHTML = `
        <div class="file-info-card">
            <div class="file-info-header">
                <span class="file-icon">📄</span>
                <div class="file-details">
                    <div class="file-name">${file.name}</div>
                    <div class="file-meta">
                        <span>Taille: ${sizeInMB} MB</span>
                        <span>Modifié: ${lastModified}</span>
                    </div>
                </div>
                <div class="file-status">
                    <span class="status-badge status-ready">✅ Prêt</span>
                </div>
            </div>
        </div>
    `;
    
    container.style.display = 'block';
}

/**
 * Active/désactive les boutons d'import
 */
function enableImportButtons(enable) {
    const buttons = ['validate-import-btn', 'preview-import-btn'];
    buttons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.disabled = !enable;
            if (enable) {
                btn.classList.remove('btn-disabled');
            } else {
                btn.classList.add('btn-disabled');
            }
        }
    });
}

/**
 * Traitement de l'import selon le mode
 */
function processImport(mode) {
    const fileInput = document.getElementById('import-file-input');
    const typeSelect = document.getElementById('import-type-select');
    
    if (!fileInput?.files[0]) {
        showError('Aucun fichier sélectionné');
        return;
    }
    
    if (!typeSelect?.value) {
        showError('Veuillez sélectionner un type d\'import');
        return;
    }
    
    const file = fileInput.files[0];
    const type = typeSelect.value;
    currentImportType = type;
    
    // Afficher le loading selon le mode
    showImportLoading(mode);
    
    // Préparer le FormData
    const formData = new FormData();
    formData.append('import_file', file);
    formData.append('type', type);
    formData.append('mode', mode);
    formData.append('csrf_token', getCSRFToken());
    
    console.log(`🔄 Traitement import - Mode: ${mode}, Type: ${type}, Fichier: ${file.name}`);
    
    // Envoyer la requête
    fetch('templates/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log(`✅ Réponse import ${mode}:`, data);
        
        if (data.success) {
            handleImportSuccess(data, mode);
        } else {
            throw new Error(data.error || 'Erreur inconnue');
        }
    })
    .catch(error => {
        console.error(`❌ Erreur import ${mode}:`, error);
        showError(`Erreur lors du ${mode}: ${error.message}`);
    })
    .finally(() => {
        hideImportLoading();
    });
}

/**
 * Gestion du succès d'import
 */
function handleImportSuccess(data, mode) {
    currentImportData = data;
    
    switch (mode) {
        case 'validate':
            displayValidationResults(data);
            break;
        case 'preview':
            displayPreviewResults(data);
            break;
        case 'import':
            displayImportResults(data);
            break;
    }
}

/**
 * Affiche les résultats de validation
 */
function displayValidationResults(data) {
    const container = document.getElementById('validation-results');
    if (!container) return;
    
    const stats = data.stats;
    const canImport = data.can_import;
    
    let html = `
        <div class="validation-summary">
            <h4>📊 Résultats de validation</h4>
            <div class="stats-grid">
                <div class="stat-card ${stats.total_rows > 0 ? 'stat-info' : 'stat-warning'}">
                    <div class="stat-value">${stats.total_rows}</div>
                    <div class="stat-label">Lignes total</div>
                </div>
                <div class="stat-card ${stats.valid_rows > 0 ? 'stat-success' : 'stat-warning'}">
                    <div class="stat-value">${stats.valid_rows}</div>
                    <div class="stat-label">Lignes valides</div>
                </div>
                <div class="stat-card ${stats.invalid_rows === 0 ? 'stat-success' : 'stat-danger'}">
                    <div class="stat-value">${stats.invalid_rows}</div>
                    <div class="stat-label">Lignes invalides</div>
                </div>
            </div>
        </div>
    `;
    
    // Erreurs
    if (stats.errors && stats.errors.length > 0) {
        html += `
            <div class="validation-errors">
                <h5>❌ Erreurs détectées</h5>
                <ul class="error-list">
                    ${stats.errors.map(error => `<li>${error}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    
    // Avertissements
    if (stats.warnings && stats.warnings.length > 0) {
        html += `
            <div class="validation-warnings">
                <h5>⚠️ Avertissements</h5>
                <ul class="warning-list">
                    ${stats.warnings.map(warning => `<li>${warning}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    
    // Échantillon de données
    if (stats.sample_data && stats.sample_data.length > 0) {
        html += `
            <div class="sample-data">
                <h5>👁️ Aperçu des données (5 premières lignes)</h5>
                <div class="table-container">
                    ${generateDataTable(stats.sample_data)}
                </div>
            </div>
        `;
    }
    
    // Actions suivantes
    html += `
        <div class="next-actions">
            ${canImport ? 
                `<button class="btn btn-primary" onclick="processImport('preview')">
                    👁️ Aperçu des modifications
                </button>` :
                `<div class="alert alert-danger">
                    ❌ Import impossible - Corrigez les erreurs dans votre fichier
                </div>`
            }
        </div>
    `;
    
    container.innerHTML = html;
    container.style.display = 'block';
    
    // Message de statut
    if (canImport) {
        showSuccess(`Validation réussie - ${stats.valid_rows} lignes prêtes pour l'import`);
    } else {
        showError(`Validation échouée - ${stats.invalid_rows} erreurs à corriger`);
    }
}

/**
 * Affiche les résultats d'aperçu
 */
function displayPreviewResults(data) {
    const container = document.getElementById('preview-results');
    if (!container) return;
    
    const preview = data.preview;
    const conflicts = data.conflicts;
    
    let html = `
        <div class="preview-summary">
            <h4>👁️ Aperçu des modifications</h4>
            <p>Aperçu des ${data.preview_rows} première(s) ligne(s) sur ${data.total_rows} total.</p>
        </div>
    `;
    
    // Conflits détectés
    if (conflicts && conflicts.length > 0) {
        html += `
            <div class="conflicts-section">
                <h5>⚠️ Conflits détectés</h5>
                <div class="conflicts-list">
                    ${conflicts.map(conflict => `
                        <div class="conflict-item">
                            <strong>Ligne ${conflict.line_number}:</strong> ${conflict.warnings.join(', ')}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // Tableau d'aperçu
    if (preview && preview.length > 0) {
        html += `
            <div class="preview-table">
                <h5>📋 Aperçu des données</h5>
                <div class="table-container">
                    ${generatePreviewTable(preview)}
                </div>
            </div>
        `;
    }
    
    // Actions
    html += `
        <div class="preview-actions">
            <button class="btn btn-secondary" onclick="processImport('validate')">
                ⬅️ Retour à la validation
            </button>
            <button class="btn btn-primary" onclick="confirmImport()">
                🚀 Confirmer l'import
            </button>
        </div>
    `;
    
    container.innerHTML = html;
    container.style.display = 'block';
    
    showSuccess(`Aperçu généré - ${preview.length} lignes analysées`);
}

/**
 * Affiche les résultats d'import final
 */
function displayImportResults(data) {
    const container = document.getElementById('import-results');
    if (!container) return;
    
    const results = data.results;
    
    let html = `
        <div class="import-summary">
            <h4>🎉 Import terminé</h4>
            <div class="results-grid">
                <div class="stat-card stat-info">
                    <div class="stat-value">${results.total_rows}</div>
                    <div class="stat-label">Lignes traitées</div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-value">${results.imported}</div>
                    <div class="stat-label">Nouvelles entrées</div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-value">${results.updated}</div>
                    <div class="stat-label">Mises à jour</div>
                </div>
                <div class="stat-card ${results.skipped > 0 ? 'stat-danger' : 'stat-success'}">
                    <div class="stat-value">${results.skipped}</div>
                    <div class="stat-label">Ignorées</div>
                </div>
            </div>
        </div>
    `;
    
    // Erreurs d'import
    if (results.errors && results.errors.length > 0) {
        html += `
            <div class="import-errors">
                <h5>❌ Erreurs d'import</h5>
                <ul class="error-list">
                    ${results.errors.map(error => `<li>${error}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    
    // Actions finales
    html += `
        <div class="final-actions">
            <button class="btn btn-success" onclick="closeImportModal()">
                ✅ Terminer
            </button>
            <button class="btn btn-secondary" onclick="startNewImport()">
                🔄 Nouvel import
            </button>
        </div>
    `;
    
    container.innerHTML = html;
    container.style.display = 'block';
    
    const successRate = ((results.imported + results.updated) / results.total_rows * 100).toFixed(1);
    showSuccess(`Import terminé avec succès (${successRate}% de réussite)`);
    
    // Recharger les données si on est sur l'onglet correspondant
    setTimeout(() => {
        refreshCurrentTabData();
    }, 1000);
}

/**
 * Génère un tableau de données
 */
function generateDataTable(data) {
    if (!data || data.length === 0) return '<p>Aucune donnée</p>';
    
    const headers = Object.keys(data[0]);
    
    let html = `
        <table class="data-preview-table">
            <thead>
                <tr>
                    ${headers.map(header => `<th>${header}</th>`).join('')}
                </tr>
            </thead>
            <tbody>
    `;
    
    data.forEach(row => {
        html += '<tr>';
        headers.forEach(header => {
            const value = row[header] || '';
            html += `<td>${value}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    return html;
}

/**
 * Génère un tableau d'aperçu avec statuts
 */
function generatePreviewTable(preview) {
    if (!preview || preview.length === 0) return '<p>Aucun aperçu disponible</p>';
    
    let html = `
        <table class="preview-table">
            <thead>
                <tr>
                    <th>Ligne</th>
                    <th>Action</th>
                    <th>Statut</th>
                    <th>Données</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    preview.forEach(item => {
        const actionIcon = item.action === 'insert' ? '➕' : '🔄';
        const statusClass = item.status === 'valid' ? 'status-success' : 'status-warning';
        
        html += `
            <tr>
                <td>${item.line_number}</td>
                <td><span class="action-badge">${actionIcon} ${item.action}</span></td>
                <td><span class="status-badge ${statusClass}">${item.status}</span></td>
                <td class="data-preview">${formatPreviewData(item.data)}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    return html;
}

/**
 * Formate les données pour l'aperçu
 */
function formatPreviewData(data) {
    const important = ['transporteur', 'num_departement', 'code_option', 'libelle'];
    let result = [];
    
    important.forEach(key => {
        if (data[key]) {
            result.push(`<strong>${key}:</strong> ${data[key]}`);
        }
    });
    
    return result.join(' | ') || 'Données complètes...';
}

/**
 * Affiche le loading d'import
 */
function showImportLoading(mode) {
    const modeLabels = {
        'validate': 'Validation en cours...',
        'preview': 'Génération de l\'aperçu...',
        'import': 'Import en cours...'
    };
    
    const container = document.getElementById('import-loading');
    if (container) {
        container.innerHTML = `
            <div class="loading-content">
                <div class="spinner"></div>
                <div class="loading-text">${modeLabels[mode] || 'Traitement...'}</div>
            </div>
        `;
        container.style.display = 'block';
    }
    
    // Désactiver les boutons pendant le traitement
    document.querySelectorAll('.import-action-btn').forEach(btn => {
        btn.disabled = true;
    });
}

/**
 * Masque le loading d'import
 */
function hideImportLoading() {
    const container = document.getElementById('import-loading');
    if (container) {
        container.style.display = 'none';
    }
    
    // Réactiver les boutons
    document.querySelectorAll('.import-action-btn').forEach(btn => {
        btn.disabled = false;
    });
}

/**
 * Confirmation d'import
 */
function confirmImport() {
    const message = `Êtes-vous sûr de vouloir importer les données ?
    
Type: ${currentImportType}
Fichier: ${uploadedFileName}

Cette action ne peut pas être annulée.`;
    
    if (confirm(message)) {
        processImport('import');
    }
}

/**
 * Gestion des téléchargements de templates
 */
function handleTemplateDownload(event) {
    const button = event.target.closest('.template-download-btn');
    const type = button.dataset.type;
    const format = button.dataset.format || 'csv';
    
    console.log(`📥 Téléchargement template: ${type}.${format}`);
    
    // Créer le lien de téléchargement
    const url = `templates/template.php?type=${type}&format=${format}`;
    
    // Télécharger
    const link = document.createElement('a');
    link.href = url;
    link.download = `guldagil_template_${type}_${new Date().toISOString().split('T')[0]}.${format}`;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showSuccess(`Template ${type} téléchargé`);
}

/**
 * Gestion des exports
 */
function handleExport(event) {
    const button = event.target.closest('.export-btn');
    const type = button.dataset.type;
    const format = button.dataset.format || 'csv';
    
    console.log(`📤 Export: ${type}.${format}`);
    
    // Créer le lien d'export
    const url = `templates/export.php?type=${type}&format=${format}`;
    
    // Télécharger
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showSuccess(`Export ${type} démarré`);
}

/**
 * Charge les templates disponibles
 */
function loadAvailableTemplates() {
    const container = document.getElementById('templates-list');
    if (!container) return;
    
    const templates = [
        { type: 'rates', name: 'Tarifs transporteurs', icon: '💰', description: 'Import des tarifs par transporteur et département' },
        { type: 'options', name: 'Options supplémentaires', icon: '⚙️', description: 'Import des options de transport' },
        { type: 'departments', name: 'Référentiel départements', icon: '🗺️', description: 'Liste de référence des départements français' }
    ];
    
    let html = '';
    templates.forEach(template => {
        html += `
            <div class="template-card">
                <div class="template-header">
                    <span class="template-icon">${template.icon}</span>
                    <h4>${template.name}</h4>
                </div>
                <p class="template-description">${template.description}</p>
                <div class="template-actions">
                    <button class="btn btn-secondary btn-sm template-download-btn" 
                            data-type="${template.type}" data-format="csv">
                        📄 CSV
                    </button>
                    <button class="btn btn-secondary btn-sm template-download-btn" 
                            data-type="${template.type}" data-format="xlsx">
                        📊 Excel
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Réattacher les event listeners
    container.querySelectorAll('.template-download-btn').forEach(btn => {
        btn.addEventListener('click', handleTemplateDownload);
    });
}

/**
 * Affiche la modal d'import
 */
function showImportModal() {
    const modal = document.getElementById('import-modal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
        
        // Reset de la modal
        resetImportModal();
    }
}

/**
 * Ferme la modal d'import
 */
function closeImportModal() {
    const modal = document.getElementById('import-modal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
}

/**
 * Reset de la modal d'import
 */
function resetImportModal() {
    // Cacher tous les résultats
    ['validation-results', 'preview-results', 'import-results', 'import-loading'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
    
    // Reset du formulaire
    const fileInput = document.getElementById('import-file-input');
    if (fileInput) fileInput.value = '';
    
    const fileInfo = document.getElementById('file-info-container');
    if (fileInfo) fileInfo.style.display = 'none';
    
    // Désactiver les boutons
    enableImportButtons(false);
    
    // Reset des variables
    currentImportData = null;
    currentImportType = null;
    uploadedFileName = null;
}

/**
 * Démarre un nouvel import
 */
function startNewImport() {
    resetImportModal();
    showSuccess('Prêt pour un nouvel import');
}

/**
 * Rafraîchit les données de l'onglet actuel
 */
function refreshCurrentTabData() {
    // Détecter l'onglet actuel et recharger ses données
    const activeTab = document.querySelector('.tab-button.active');
    if (!activeTab) return;
    
    const tabName = activeTab.getAttribute('data-tab');
    
    switch (tabName) {
        case 'rates':
            if (typeof window.loadRates === 'function') {
                window.loadRates(true);
            }
            break;
        case 'options':
            if (typeof window.loadOptions === 'function') {
                window.loadOptions(true);
            }
            break;
    }
}

/**
 * Récupère le token CSRF
 */
function getCSRFToken() {
    // Récupérer depuis un meta tag ou input hidden
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) return metaToken.getAttribute('content');
    
    const inputToken = document.querySelector('input[name="csrf_token"]');
    if (inputToken) return inputToken.value;
    
    // Générer un token temporaire (à améliorer en production)
    return 'temp_token_' + Date.now();
}

/**
 * Messages d'interface
 */
function showSuccess(message) {
    if (typeof showAlert === 'function') {
        showAlert('success', message);
    } else {
        console.log('✅ Succès:', message);
    }
}

function showError(message) {
    if (typeof showAlert === 'function') {
        showAlert('error', message);
    } else {
        console.error('❌ Erreur:', message);
    }
}

function showWarning(message) {
    if (typeof showAlert === 'function') {
        showAlert('warning', message);
    } else {
        console.warn('⚠️ Avertissement:', message);
    }
}

// Exposer les fonctions globalement
window.showImportModal = showImportModal;
window.closeImportModal = closeImportModal;
window.processImport = processImport;
window.confirmImport = confirmImport;
window.startNewImport = startNewImport;
window.handleTemplateDownload = handleTemplateDownload;
window.handleExport = handleExport;

// Gestion des clics extérieurs pour fermer les modals
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal') && e.target.id === 'import-modal') {
        closeImportModal();
    }
});

// Gestion des raccourcis clavier
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('import-modal');
        if (modal && modal.style.display === 'flex') {
            closeImportModal();
        }
    }
});

console.log('✅ Module import/export chargé avec succès');

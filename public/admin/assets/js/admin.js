// public/assets/js/admin.js - JavaScript complet pour l'administration

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Interface d\'administration Guldagil chargée');
    
    // Initialiser l'interface
    initializeAdmin();
    
    // Charger les données par défaut
    loadRates();
    
    // Configurer les événements
    setupEventListeners();
    
    showNotification('Interface d\'administration prête', 'success');
});

// =============================================================================
// INITIALISATION
// =============================================================================

function initializeAdmin() {
    // Gestion des clics extérieurs pour fermer les modaux
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Configuration du drag & drop pour l'upload
    setupDragAndDrop();
    
    // Configuration des filtres de recherche
    setupSearchFilters();
}

function setupEventListeners() {
    // Recherche en temps réel pour les tarifs
    const searchRates = document.getElementById('search-rates');
    if (searchRates) {
        searchRates.addEventListener('input', debounce(function() {
            loadRates(this.value);
        }, 300));
    }
    
    // Filtre par transporteur pour les tarifs
    const filterCarrier = document.getElementById('filter-carrier');
    if (filterCarrier) {
        filterCarrier.addEventListener('change', function() {
            loadRates(document.getElementById('search-rates').value, this.value);
        });
    }
    
    // Recherche pour les options
    const searchOptions = document.getElementById('search-options');
    if (searchOptions) {
        searchOptions.addEventListener('input', debounce(function() {
            loadOptions(this.value);
        }, 300));
    }
    
    // Filtre par transporteur pour les options
    const filterOptionCarrier = document.getElementById('filter-option-carrier');
    if (filterOptionCarrier) {
        filterOptionCarrier.addEventListener('change', function() {
            loadOptions(document.getElementById('search-options').value, this.value);
        });
    }
}

function setupDragAndDrop() {
    const uploadZone = document.querySelector('.upload-zone');
    if (!uploadZone) return;
    
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('file-input').files = files;
            handleFileUpload(document.getElementById('file-input'));
        }
    });
}

function setupSearchFilters() {
    // Les filtres sont configurés dans setupEventListeners()
}

// =============================================================================
// GESTION DES ONGLETS
// =============================================================================

function showTab(tabName) {
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Désactiver tous les boutons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    document.getElementById(`tab-${tabName}`).classList.add('active');
    event.target.classList.add('active');
    
    // Charger les données selon l'onglet
    switch(tabName) {
        case 'rates':
            loadRates();
            break;
        case 'options':
            loadOptions();
            break;
        case 'taxes':
            loadTaxes();
            break;
        case 'import':
            // Pas de chargement spécifique
            break;
    }
}

// =============================================================================
// GESTION DES TARIFS
// =============================================================================

async function loadRates(search = '', carrier = '') {
    const tbody = document.getElementById('rates-tbody');
    
    // Afficher le loading
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="loading-spinner">Chargement des tarifs...</div></td></tr>';
    
    try {
        const params = new URLSearchParams({
            action: 'get_rates',
            search: search,
            carrier: carrier
        });
        
        const response = await fetch(`api.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayRates(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des tarifs:', error);
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Erreur lors du chargement</td></tr>';
        showNotification('Erreur lors du chargement des tarifs', 'danger');
    }
}

function displayRates(rates) {
    const tbody = document.getElementById('rates-tbody');
    
    if (rates.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Aucun tarif trouvé</td></tr>';
        return;
    }
    
    tbody.innerHTML = rates.map(rate => {
        const carrierColors = {
            'heppner': 'var(--primary-color)',
            'xpo': 'var(--warning-color)',
            'kn': 'var(--error-color)'
        };
        
        return `
            <tr>
                <td><strong style="color: ${carrierColors[rate.carrier_code] || '#333'}">${rate.carrier_name}</strong></td>
                <td>${rate.num_departement} - ${rate.departement || ''}</td>
                <td>${formatPrice(rate.tarif_0_9)}</td>
                <td>${formatPrice(rate.tarif_10_19)}</td>
                <td>${formatPrice(rate.tarif_90_99)}</td>
                <td>${formatPrice(rate.tarif_100_299)}</td>
                <td>${formatPrice(rate.tarif_500_999)}</td>
                <td><span style="color: var(--success-color);">${rate.delais || '-'}</span></td>
                <td class="actions">
                    <button class="btn btn-sm btn-warning" onclick="editRate(${rate.id}, '${rate.carrier_code}')">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteRate(${rate.id}, '${rate.carrier_code}')">🗑️</button>
                </td>
            </tr>
        `;
    }).join('');
}

function addRate() {
    // Réinitialiser le formulaire
    document.getElementById('rate-form').reset();
    document.getElementById('rate-id').value = '';
    showModal('edit-rate-modal');
}

async function editRate(id, carrier) {
    // Charger les données du tarif pour pré-remplir le formulaire
    try {
        const response = await fetch(`api.php?action=get_rate&id=${id}&carrier=${carrier}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const rate = data.data;
            document.getElementById('rate-id').value = rate.id;
            document.getElementById('rate-carrier').value = carrier;
            document.getElementById('rate-department').value = rate.num_departement;
            document.getElementById('rate-0-9').value = rate.tarif_0_9 || '';
            document.getElementById('rate-10-19').value = rate.tarif_10_19 || '';
            document.getElementById('rate-90-99').value = rate.tarif_90_99 || '';
            document.getElementById('rate-100-299').value = rate.tarif_100_299 || '';
            document.getElementById('rate-500-999').value = rate.tarif_500_999 || '';
            document.getElementById('rate-delay').value = rate.delais || '';
        }
    } catch (error) {
        console.error('Erreur lors du chargement du tarif:', error);
    }
    
    showModal('edit-rate-modal');
}

async function saveRate() {
    const form = document.getElementById('rate-form');
    const formData = new FormData(form);
    formData.append('action', 'save_rate');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('edit-rate-modal');
            loadRates();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error);
        showNotification('Erreur lors de la sauvegarde', 'danger');
    }
}

async function deleteRate(id, carrier) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce tarif ?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_rate');
        formData.append('id', id);
        formData.append('carrier', carrier);
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            loadRates();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showNotification('Erreur lors de la suppression', 'danger');
    }
}

// =============================================================================
// GESTION DES OPTIONS
// =============================================================================

async function loadOptions(search = '', carrier = '') {
    const tbody = document.getElementById('options-tbody');
    
    if (!tbody) return; // L'onglet options n'est pas encore affiché
    
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="loading-spinner">Chargement des options...</div></td></tr>';
    
    try {
        const params = new URLSearchParams({
            action: 'get_options',
            search: search,
            carrier: carrier
        });
        
        const response = await fetch(`api.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayOptions(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des options:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Erreur lors du chargement</td></tr>';
        showNotification('Erreur lors du chargement des options', 'danger');
    }
}

function displayOptions(options) {
    const tbody = document.getElementById('options-tbody');
    
    if (options.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Aucune option trouvée</td></tr>';
        return;
    }
    
    tbody.innerHTML = options.map(option => {
        const carrierColors = {
            'heppner': 'var(--primary-color)',
            'xpo': 'var(--warning-color)',
            'kn': 'var(--error-color)'
        };
        
        const statusIcon = option.actif ? '✅ Actif' : '⏸️ Inactif';
        const statusColor = option.actif ? 'var(--success-color)' : 'var(--text-muted)';
        const toggleButton = option.actif ? 
            '<button class="btn btn-sm btn-secondary" onclick="toggleOption(' + option.id + ')">⏸️</button>' :
            '<button class="btn btn-sm btn-success" onclick="toggleOption(' + option.id + ')">▶️</button>';
        
        return `
            <tr>
                <td><strong style="color: ${carrierColors[option.transporteur] || '#333'}">${getCarrierName(option.transporteur)}</strong></td>
                <td><code>${option.code_option}</code></td>
                <td>${option.libelle}</td>
                <td>${formatPrice(option.montant)}</td>
                <td>${option.unite}</td>
                <td><span style="color: ${statusColor};">${statusIcon}</span></td>
                <td class="actions">
                    <button class="btn btn-sm btn-warning" onclick="editOption(${option.id})">✏️</button>
                    ${toggleButton}
                    <button class="btn btn-sm btn-danger" onclick="deleteOption(${option.id})">🗑️</button>
                </td>
            </tr>
        `;
    }).join('');
}

function addOption() {
    document.getElementById('option-form').reset();
    document.getElementById('option-id').value = '';
    showModal('edit-option-modal');
}

async function editOption(id) {
    // Charger les données de l'option pour pré-remplir le formulaire
    try {
        const response = await fetch(`api.php?action=get_option&id=${id}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const option = data.data;
            document.getElementById('option-id').value = option.id;
            document.getElementById('option-carrier').value = option.transporteur;
            document.getElementById('option-code').value = option.code_option;
            document.getElementById('option-label').value = option.libelle;
            document.getElementById('option-amount').value = option.montant;
            document.getElementById('option-unit').value = option.unite;
            document.getElementById('option-active').checked = option.actif == 1;
        }
    } catch (error) {
        console.error('Erreur lors du chargement de l\'option:', error);
    }
    
    showModal('edit-option-modal');
}

async function saveOption() {
    const form = document.getElementById('option-form');
    const formData = new FormData(form);
    formData.append('action', 'save_option');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('edit-option-modal');
            loadOptions();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error);
        showNotification('Erreur lors de la sauvegarde', 'danger');
    }
}

async function toggleOption(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_option');
        formData.append('id', id);
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            loadOptions();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors de la modification:', error);
        showNotification('Erreur lors de la modification', 'danger');
    }
}

async function deleteOption(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette option ?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_option');
        formData.append('id', id);
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            loadOptions();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showNotification('Erreur lors de la suppression', 'danger');
    }
}

// =============================================================================
// GESTION DES TAXES
// =============================================================================

async function loadTaxes() {
    const content = document.getElementById('taxes-content');
    
    if (!content) return;
    
    content.innerHTML = '<div class="loading-spinner">Chargement des taxes...</div>';
    
    try {
        const response = await fetch('api.php?action=get_taxes');
        const data = await response.json();
        
        if (data.success) {
            displayTaxes(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des taxes:', error);
        content.innerHTML = '<div class="text-center text-muted">Erreur lors du chargement</div>';
        showNotification('Erreur lors du chargement des taxes', 'danger');
    }
}

function displayTaxes(taxes) {
    const content = document.getElementById('taxes-content');
    
    const taxesGrid = taxes.map(tax => {
        return `
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>🚛 ${tax.transporteur}</h3>
                </div>
                <div class="admin-card-body">
                    <div class="form-row">
                        <div>
                            <label>Poids maximum</label>
                            <div style="font-weight: bold; color: var(--primary-color);">${tax.poids_maximum} kg</div>
                        </div>
                        <div>
                            <label>Majoration ADR</label>
                            <div>${tax.majoration_adr || 'Non applicable'}</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Participation énergétique</label>
                            <div>${formatPrice(tax.participation_transition_energetique)}</div>
                        </div>
                        <div>
                            <label>Contribution sanitaire</label>
                            <div>${formatPrice(tax.contribution_sanitaire)}</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Sûreté</label>
                            <div>${formatPrice(tax.surete)}</div>
                        </div>
                        <div>
                            <label>Surcharge gasoil</label>
                            <div>${tax.surcharge_gasoil ? (tax.surcharge_gasoil * 100).toFixed(2) + '%' : '-'}</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Majoration IDF</label>
                            <div>${tax.majoration_idf_type || 'Aucune'} 
                                 ${tax.majoration_idf_valeur ? '(' + tax.majoration_idf_valeur + (tax.majoration_idf_type === 'Pourcentage' ? '%' : '€') + ')' : ''}
                            </div>
                        </div>
                        <div>
                            <label>Départements IDF</label>
                            <div style="font-size: 0.8rem;">${tax.majoration_idf_departements || '-'}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    content.innerHTML = `<div class="form-row">${taxesGrid}</div>`;
}

function editTaxes() {
    showNotification('Fonctionnalité en développement. Contactez l\'administrateur pour modifier les taxes.', 'info');
}

// =============================================================================
// IMPORT/EXPORT
// =============================================================================

function handleFileUpload(input) {
    const file = input.files[0];
    if (file) {
        console.log('Fichier sélectionné:', file.name);
        
        // Vérifier la taille (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            showNotification('Fichier trop volumineux (max 10MB)', 'warning');
            input.value = '';
            return;
        }
        
        // Vérifier le type
        const allowedTypes = [
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        if (!allowedTypes.includes(file.type)) {
            showNotification('Type de fichier non supporté. Utilisez CSV ou Excel.', 'warning');
            input.value = '';
            return;
        }
        
        showNotification(`Fichier "${file.name}" sélectionné`, 'success');
    }
}

async function importData() {
    const fileInput = document.getElementById('file-input');
    if (!fileInput.files[0]) {
        showNotification('Veuillez sélectionner un fichier', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'import');
    formData.append('import_file', fileInput.files[0]);
    
    try {
        showNotification('Import en cours...', 'info');
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const result = data.data;
            let message = `Import terminé : ${result.imported} lignes importées`;
            
            if (result.errors && result.errors.length > 0) {
                message += ` (${result.errors.length} erreurs)`;
                console.warn('Erreurs d\'import:', result.errors);
                
                // Afficher les erreurs dans une notification
                setTimeout(() => {
                    showNotification(`Erreurs détectées : ${result.errors.slice(0, 3).join(', ')}${result.errors.length > 3 ? '...' : ''}`, 'warning');
                }, 1000);
            }
            
            showNotification(message, 'success');
            loadRates(); // Recharger les données
            loadOptions();
            
            // Réinitialiser le champ fichier
            fileInput.value = '';
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erreur lors de l\'import:', error);
        showNotification('Erreur lors de l\'import : ' + error.message, 'danger');
    }
}

async function exportData() {
    const type = document.getElementById('export-type').value;
    const format = document.getElementById('export-format').value;
    
    try {
        showNotification('Export en cours...', 'info');
        
        // Créer un lien de téléchargement
        const params = new URLSearchParams({
            type: type,
            format: format
        });
        
        // Créer un lien temporaire pour le téléchargement
        const link = document.createElement('a');
        link.href = `export.php?${params}`;
        link.download = `guldagil_export_${type}_${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xlsx' : format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        setTimeout(() => {
            showNotification('Export terminé', 'success');
        }, 1000);
    } catch (error) {
        console.error('Erreur lors de l\'export:', error);
        showNotification('Erreur lors de l\'export', 'danger');
    }
}

function downloadTemplate() {
    const type = 'rates'; // Par défaut, modèle de tarifs
    
    try {
        const link = document.createElement('a');
        link.href = `template.php?type=${type}`;
        link.download = `guldagil_template_${type}_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Téléchargement du modèle...', 'info');
    } catch (error) {
        console.error('Erreur lors du téléchargement:', error);
        showNotification('Erreur lors du téléchargement', 'danger');
    }
}

// =============================================================================
// UTILITAIRES MODAUX
// =============================================================================

function showModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    // Focus sur le premier champ du formulaire
    setTimeout(() => {
        const firstInput = document.querySelector(`#${modalId} input:not([type="hidden"]), #${modalId} select`);
        if (firstInput) firstInput.focus();
    }, 100);
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function showHelp() {
    const helpText = `
🎯 AIDE - Interface d'Administration Guldagil

📊 GESTION DES TARIFS
• Ajouter : Bouton "➕ Ajouter un tarif"
• Modifier : Bouton "✏️" sur chaque ligne
• Supprimer : Bouton "🗑️" (irréversible)
• Rechercher : Barre de recherche par département

⚙️ OPTIONS SUPPLÉMENTAIRES
• Codes : rdv, premium13, premium18, datefixe, enlevement
• Unités : forfait, palette, pourcentage
• Toggle : Bouton "⏸️/▶️" pour activer/désactiver

📤 IMPORT/EXPORT
• Formats : CSV, JSON, Excel
• Modèles : Téléchargement via "📋 Télécharger le modèle"
• Import : Glisser-déposer ou sélection de fichier

📞 SUPPORT
• Technique : runser.jean.thomas@guldagil.com
• Fonctionnel : achats@guldagil.com
• Urgences : 03 89 63 42 42
    `;
    
    alert(helpText);
}

// =============================================================================
// NOTIFICATIONS
// =============================================================================

function showNotification(message, type = 'info') {
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
        animation: slideInRight 0.3s ease;
        box-shadow: var(--shadow-hover);
    `;
    
    // Ajouter l'icône selon le type
    const icons = {
        success: '✅',
        danger: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    notification.innerHTML = `${icons[type] || 'ℹ️'} ${message}`;
    
    // Ajouter au DOM
    document.body.appendChild(notification);
    
    // Supprimer après 4 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
    
    // Permettre de cliquer pour fermer
    notification.addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    });
}

// =============================================================================
// UTILITAIRES
// =============================================================================

function formatPrice(price) {
    if (price === null || price === undefined || price === '' || price === 0) {
        return '<span class="text-muted">-</span>';
    }
    return parseFloat(price).toFixed(2) + ' €';
}

function getCarrierName(code) {
    const names = {
        'heppner': 'Heppner',
        'xpo': 'XPO',
        'kn': 'Kuehne + Nagel'
    };
    return names[code] || code;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// =============================================================================
// VALIDATION DES FORMULAIRES
// =============================================================================

function validateRateForm() {
    const form = document.getElementById('rate-form');
    const department = document.getElementById('rate-department').value;
    const carrier = document.getElementById('rate-carrier').value;
    
    // Validation du département
    if (!/^[0-9]{2}$/.test(department)) {
        showNotification('Le département doit être un nombre à 2 chiffres', 'warning');
        return false;
    }
    
    // Validation transporteur
    if (!carrier) {
        showNotification('Veuillez sélectionner un transporteur', 'warning');
        return false;
    }
    
    // Au moins un tarif doit être renseigné
    const tarifs = ['rate-0-9', 'rate-10-19', 'rate-90-99', 'rate-100-299', 'rate-500-999'];
    const hasAtLeastOneTarif = tarifs.some(id => {
        const value = document.getElementById(id).value;
        return value && parseFloat(value) > 0;
    });
    
    if (!hasAtLeastOneTarif) {
        showNotification('Veuillez renseigner au moins un tarif', 'warning');
        return false;
    }
    
    return true;
}

function validateOptionForm() {
    const transporteur = document.getElementById('option-carrier').value;
    const code = document.getElementById('option-code').value;
    const libelle = document.getElementById('option-label').value;
    const montant = document.getElementById('option-amount').value;
    const unite = document.getElementById('option-unit').value;
    
    if (!transporteur || !code || !libelle || !montant || !unite) {
        showNotification('Tous les champs marqués * sont obligatoires', 'warning');
        return false;
    }
    
    if (parseFloat(montant) < 0) {
        showNotification('Le montant ne peut pas être négatif', 'warning');
        return false;
    }
    
    // Validation du code option (alphanumerique + underscore)
    if (!/^[a-zA-Z0-9_]+$/.test(code)) {
        showNotification('Le code option ne peut contenir que des lettres, chiffres et underscores', 'warning');
        return false;
    }
    
    return true;
}

// =============================================================================
// GESTION DES ERREURS GLOBALES
// =============================================================================

window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
    showNotification('Une erreur inattendue s\'est produite', 'danger');
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Promise rejetée:', e.reason);
    showNotification('Erreur de communication avec le serveur', 'danger');
});

// =============================================================================
// RACCOURCIS CLAVIER
// =============================================================================

document.addEventListener('keydown', function(e) {
    // Échapper pour fermer les modaux
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
    
    // Ctrl+S pour sauvegarder (si modal ouvert)
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        
        if (document.getElementById('edit-rate-modal').classList.contains('active')) {
            if (validateRateForm()) {
                saveRate();
            }
        } else if (document.getElementById('edit-option-modal').classList.contains('active')) {
            if (validateOptionForm()) {
                saveOption();
            }
        }
    }
    
    // Ctrl+N pour nouveau (selon l'onglet actif)
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        
        const activeTab = document.querySelector('.tab-button.active');
        if (activeTab) {
            const tabName = activeTab.textContent.toLowerCase();
            if (tabName.includes('tarifs')) {
                addRate();
            } else if (tabName.includes('options')) {
                addOption();
            }
        }
    }
});

// =============================================================================
// MISE À JOUR AUTOMATIQUE DES STATISTIQUES
// =============================================================================

async function updateStats() {
    try {
        const response = await fetch('api.php?action=get_stats');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            
            document.getElementById('total-carriers').textContent = stats.carriers || 3;
            document.getElementById('total-departments').textContent = stats.departments || 95;
            document.getElementById('total-options').textContent = stats.options || 0;
            
            // Mettre à jour la date de dernière modification
            const lastUpdate = document.querySelector('.stat-value:last-child');
            if (lastUpdate && stats.last_update) {
                lastUpdate.textContent = stats.last_update;
            }
        }
    } catch (error) {
        console.error('Erreur lors de la mise à jour des statistiques:', error);
    }
}

// Mettre à jour les stats toutes les 30 secondes
setInterval(updateStats, 30000);

// =============================================================================
// INITIALISATION FINALE
// =============================================================================

// Ajouter les styles d'animation
if (!document.getElementById('admin-animations')) {
    const style = document.createElement('style');
    style.id = 'admin-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .loading-spinner::before {
            content: "";
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .admin-card:hover {
            transform: translateY(-2px);
        }
        
        .data-table tr:hover {
            transform: scale(1.01);
        }
    `;
    document.head.appendChild(style);
}

// =============================================================================
// FONCTIONS UTILITAIRES SUPPLÉMENTAIRES
// =============================================================================

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copié dans le presse-papiers', 'success');
    }).catch(err => {
        console.error('Erreur lors de la copie:', err);
        showNotification('Erreur lors de la copie', 'danger');
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function generateCSVFromTable(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = Array.from(table.querySelectorAll('tr'));
    
    const csv = rows.map(row => {
        const cells = Array.from(row.querySelectorAll('th, td'));
        return cells.map(cell => {
            const text = cell.textContent.trim();
            return `"${text.replace(/"/g, '""')}"`;
        }).join(',');
    }).join('\n');
    
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// =============================================================================
// GESTIONNAIRE DE PERFORMANCE
// =============================================================================

class PerformanceMonitor {
    constructor() {
        this.metrics = {
            loadTimes: [],
            apiCalls: 0,
            errors: 0
        };
    }
    
    recordLoadTime(operation, startTime) {
        const endTime = performance.now();
        const duration = endTime - startTime;
        this.metrics.loadTimes.push({
            operation,
            duration,
            timestamp: new Date()
        });
        
        // Log si l'opération prend plus de 2 secondes
        if (duration > 2000) {
            console.warn(`Opération lente détectée: ${operation} (${duration.toFixed(0)}ms)`);
        }
    }
    
    recordApiCall() {
        this.metrics.apiCalls++;
    }
    
    recordError() {
        this.metrics.errors++;
    }
    
    getReport() {
        const avgLoadTime = this.metrics.loadTimes.length > 0 
            ? this.metrics.loadTimes.reduce((sum, metric) => sum + metric.duration, 0) / this.metrics.loadTimes.length
            : 0;
            
        return {
            averageLoadTime: avgLoadTime.toFixed(0) + 'ms',
            totalApiCalls: this.metrics.apiCalls,
            totalErrors: this.metrics.errors,
            slowOperations: this.metrics.loadTimes.filter(m => m.duration > 1000).length
        };
    }
}

// Instance globale du monitor de performance
window.performanceMonitor = new PerformanceMonitor();

// Wrapper pour les fonctions de chargement avec monitoring
const originalLoadRates = loadRates;
loadRates = async function(...args) {
    const startTime = performance.now();
    window.performanceMonitor.recordApiCall();
    try {
        await originalLoadRates.apply(this, args);
        window.performanceMonitor.recordLoadTime('loadRates', startTime);
    } catch (error) {
        window.performanceMonitor.recordError();
        throw error;
    }
};

console.log('✅ Interface d\'administration Guldagil entièrement chargée et prête');

// Activer le mode debug si paramètre URL présent
if (new URLSearchParams(window.location.search).has('debug')) {
    console.log('🔍 Mode debug activé');
    
    // Afficher les métriques de performance toutes les 10 secondes
    setInterval(() => {
        console.log('📊 Métriques de performance:', window.performanceMonitor.getReport());
    }, 10000);
    
    // Ajouter un bouton de debug dans le header
    const debugBtn = document.createElement('a');
    debugBtn.href = '#';
    debugBtn.innerHTML = '🔍 Debug';
    debugBtn.onclick = () => {
        const report = window.performanceMonitor.getReport();
        alert(`Rapport de performance:\n\nTemps de chargement moyen: ${report.averageLoadTime}\nAppels API: ${report.totalApiCalls}\nErreurs: ${report.totalErrors}\nOpérations lentes: ${report.slowOperations}`);
        return false;
    };
    document.querySelector('.admin-nav').appendChild(debugBtn);
}

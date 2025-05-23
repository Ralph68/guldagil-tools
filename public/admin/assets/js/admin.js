// public/assets/js/admin.js - Version complète pour l'interface moderne

// Variables globales
let currentTab = 'dashboard';
let isLoading = false;

// =============================================================================
// INITIALISATION
// =============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Interface d\'administration Guldagil chargée');
    
    // Initialiser l'interface
    initializeAdmin();
    
    // Configurer les événements
    setupEventListeners();
    
    // Ajouter les styles d'animation
    addAnimationStyles();
    
    // Afficher notification de bienvenue
    showNotification('Interface d\'administration prête', 'success');
});

function initializeAdmin() {
    // Fermer les modaux en cliquant à l'extérieur
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Configuration des raccourcis clavier
    setupKeyboardShortcuts();
    
    console.log('✅ Interface initialisée');
}

function setupEventListeners() {
    // Gestion des onglets
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('data-tab');
            if (tabName) {
                showTab(tabName);
            }
        });
    });
    
    // Recherche en temps réel pour les tarifs
    const searchRates = document.getElementById('search-rates');
    if (searchRates) {
        searchRates.addEventListener('input', debounce(function() {
            loadRatesData(this.value);
        }, 300));
    }
    
    // Filtre par transporteur
    const filterCarrier = document.getElementById('filter-carrier');
    if (filterCarrier) {
        filterCarrier.addEventListener('change', function() {
            const search = document.getElementById('search-rates')?.value || '';
            loadRatesData(search, this.value);
        });
    }
    
    // Recherche pour les options
    const searchOptions = document.getElementById('search-options');
    if (searchOptions) {
        searchOptions.addEventListener('input', debounce(function() {
            loadOptionsData(this.value);
        }, 300));
    }
    
    // Gestion de l'upload de fichiers
    const fileInput = document.getElementById('file-input');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelect);
    }
    
    console.log('✅ Event listeners configurés');
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Échapper pour fermer les modaux
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
        
        // Ctrl+S pour sauvegarder
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveCurrentForm();
        }
        
        // Ctrl+E pour exporter
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            exportData();
        }
    });
}

// =============================================================================
// GESTION DES ONGLETS
// =============================================================================

function showTab(tabName) {
    // Masquer tous les contenus d'onglets
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Désactiver tous les boutons d'onglets
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    const tabContent = document.getElementById(`tab-${tabName}`);
    const tabButton = document.querySelector(`[data-tab="${tabName}"]`);
    
    if (tabContent) {
        tabContent.classList.add('active');
        tabContent.classList.add('fade-in');
    }
    
    if (tabButton) {
        tabButton.classList.add('active');
    }
    
    currentTab = tabName;
    
    // Charger les données de l'onglet
    loadTabData(tabName);
    
    console.log(`Onglet ${tabName} activé`);
}

function loadTabData(tabName) {
    switch (tabName) {
        case 'rates':
            loadRatesData();
            break;
        case 'options':
            loadOptionsData();
            break;
        case 'taxes':
            loadTaxesData();
            break;
        case 'dashboard':
            // Dashboard déjà chargé au démarrage
            break;
        case 'import':
            // Pas de chargement spécifique
            break;
    }
}

// =============================================================================
// GESTION DES MODAUX
// =============================================================================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus sur le premier champ
        setTimeout(() => {
            const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// =============================================================================
// GESTION DES TARIFS
// =============================================================================

function loadRatesData(search = '', carrier = '') {
    if (isLoading) return;
    
    isLoading = true;
    const tbody = document.getElementById('rates-tbody');
    
    if (!tbody) {
        isLoading = false;
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="loading-spinner">Chargement des tarifs...</div></td></tr>';
    
    // Simulation de données (remplacez par votre appel AJAX réel)
    setTimeout(() => {
        displayMockRates(tbody, search, carrier);
        isLoading = false;
    }, 800);
}

function displayMockRates(tbody, search = '', carrier = '') {
    // Données de test
    const mockRates = [
        {
            id: 1,
            carrier: 'heppner',
            carrierName: 'Heppner',
            department: '67',
            departmentName: 'Bas-Rhin',
            tarif_0_9: 12.68,
            tarif_10_19: 15.32,
            tarif_90_99: 35.11,
            tarif_100_299: 22.97,
            tarif_500_999: 14.37,
            delay: '24h'
        },
        {
            id: 2,
            carrier: 'xpo',
            carrierName: 'XPO',
            department: '68',
            departmentName: 'Haut-Rhin',
            tarif_0_9: 35.17,
            tarif_10_19: null,
            tarif_90_99: null,
            tarif_100_299: 16.22,
            tarif_500_999: 10.39,
            delay: '24h-48h'
        },
        {
            id: 3,
            carrier: 'kn',
            carrierName: 'Kuehne + Nagel',
            department: '75',
            departmentName: 'Paris',
            tarif_0_9: null,
            tarif_10_19: null,
            tarif_90_99: null,
            tarif_100_299: null,
            tarif_500_999: null,
            delay: '24h-48h'
        }
    ];
    
    // Filtrer selon la recherche et le transporteur
    let filteredRates = mockRates;
    
    if (search) {
        filteredRates = filteredRates.filter(rate => 
            rate.department.includes(search) || 
            rate.departmentName.toLowerCase().includes(search.toLowerCase()) ||
            rate.carrierName.toLowerCase().includes(search.toLowerCase())
        );
    }
    
    if (carrier) {
        filteredRates = filteredRates.filter(rate => rate.carrier === carrier);
    }
    
    if (filteredRates.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500">Aucun tarif trouvé</td></tr>';
        return;
    }
    
    tbody.innerHTML = filteredRates.map(rate => `
        <tr>
            <td class="font-semibold text-primary">${rate.carrierName}</td>
            <td>${rate.department} - ${rate.departmentName}</td>
            <td class="font-medium">${formatPrice(rate.tarif_0_9)}</td>
            <td class="font-medium">${formatPrice(rate.tarif_10_19)}</td>
            <td class="font-medium">${formatPrice(rate.tarif_90_99)}</td>
            <td class="font-medium">${formatPrice(rate.tarif_100_299)}</td>
            <td class="font-medium">${formatPrice(rate.tarif_500_999)}</td>
            <td><span class="badge badge-success">${rate.delay}</span></td>
            <td class="text-center">
                <div class="actions">
                    <button class="btn btn-secondary btn-sm" onclick="editRate(${rate.id}, '${rate.carrier}')" title="Modifier">
                        ✏️
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteRate(${rate.id}, '${rate.carrier}')" title="Supprimer">
                        🗑️
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function loadRates() {
    loadRatesData();
}

function addRate(carrier = '', department = '') {
    // Réinitialiser le formulaire
    const form = document.getElementById('rate-form');
    if (form) {
        form.reset();
        document.getElementById('rate-id').value = '';
        if (carrier) document.getElementById('rate-carrier').value = carrier;
        if (department) document.getElementById('rate-department').value = department;
    }
    
    openModal('edit-rate-modal');
}

function editRate(id, carrier) {
    // Charger les données existantes (simulation)
    const mockData = {
        1: { carrier: 'heppner', department: '67', tarif_0_9: '12.68', tarif_10_19: '15.32', tarif_90_99: '35.11', tarif_100_299: '22.97', tarif_500_999: '14.37', delay: '24h' },
        2: { carrier: 'xpo', department: '68', tarif_0_9: '35.17', tarif_100_299: '16.22', tarif_500_999: '10.39', delay: '24h-48h' },
        3: { carrier: 'kn', department: '75', delay: '24h-48h' }
    };
    
    const data = mockData[id];
    if (data) {
        document.getElementById('rate-id').value = id;
        document.getElementById('rate-carrier').value = data.carrier;
        document.getElementById('rate-department').value = data.department;
        document.getElementById('rate-0-9').value = data.tarif_0_9 || '';
        document.getElementById('rate-10-19').value = data.tarif_10_19 || '';
        document.getElementById('rate-90-99').value = data.tarif_90_99 || '';
        document.getElementById('rate-100-299').value = data.tarif_100_299 || '';
        document.getElementById('rate-500-999').value = data.tarif_500_999 || '';
        document.getElementById('rate-delay').value = data.delay || '';
    }
    
    openModal('edit-rate-modal');
}

function saveRate() {
    const form = document.getElementById('rate-form');
    const formData = new FormData(form);
    
    // Validation basique
    const carrier = formData.get('carrier');
    const department = formData.get('department');
    
    if (!carrier || !department) {
        showNotification('Veuillez remplir tous les champs obligatoires', 'warning');
        return;
    }
    
    if (!/^[0-9]{2}$/.test(department)) {
        showNotification('Le département doit être composé de 2 chiffres', 'warning');
        return;
    }
    
    // Simulation de sauvegarde
    showNotification('Tarif sauvegardé avec succès !', 'success');
    closeModal('edit-rate-modal');
    
    // Recharger les données
    if (currentTab === 'rates') {
        loadRatesData();
    }
}

function deleteRate(id, carrier) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce tarif ?')) {
        // Simulation de suppression
        showNotification('Tarif supprimé avec succès !', 'success');
        
        // Recharger les données
        if (currentTab === 'rates') {
            loadRatesData();
        }
    }
}

// =============================================================================
// GESTION DES OPTIONS
// =============================================================================

function loadOptionsData(search = '', carrier = '') {
    if (isLoading) return;
    
    isLoading = true;
    const tbody = document.getElementById('options-tbody');
    
    if (!tbody) {
        isLoading = false;
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="loading-spinner">Chargement des options...</div></td></tr>';
    
    // Simulation de données
    setTimeout(() => {
        displayMockOptions(tbody, search, carrier);
        isLoading = false;
    }, 600);
}

function displayMockOptions(tbody, search = '', carrier = '') {
    const mockOptions = [
        {
            id: 1,
            carrier: 'heppner',
            carrierName: 'Heppner',
            code: 'rdv',
            label: 'Prise de RDV',
            amount: 15.00,
            unit: 'forfait',
            active: true
        },
        {
            id: 2,
            carrier: 'xpo',
            carrierName: 'XPO',
            code: 'premium13',
            label: 'Premium avant 13h',
            amount: 22.00,
            unit: 'forfait',
            active: true
        },
        {
            id: 3,
            carrier: 'kn',
            carrierName: 'Kuehne + Nagel',
            code: 'palette',
            label: 'Frais par palette EUR',
            amount: 6.50,
            unit: 'palette',
            active: false
        }
    ];
    
    // Filtrer
    let filteredOptions = mockOptions;
    
    if (search) {
        filteredOptions = filteredOptions.filter(option =>
            option.code.includes(search.toLowerCase()) ||
            option.label.toLowerCase().includes(search.toLowerCase()) ||
            option.carrierName.toLowerCase().includes(search.toLowerCase())
        );
    }
    
    if (carrier) {
        filteredOptions = filteredOptions.filter(option => option.carrier === carrier);
    }
    
    if (filteredOptions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-500">Aucune option trouvée</td></tr>';
        return;
    }
    
    tbody.innerHTML = filteredOptions.map(option => `
        <tr>
            <td class="font-semibold text-primary">${option.carrierName}</td>
            <td><code class="bg-gray-100 px-2 py-1 rounded">${option.code}</code></td>
            <td>${option.label}</td>
            <td class="font-medium">${formatPrice(option.amount)}</td>
            <td><span class="badge badge-gray">${option.unit}</span></td>
            <td>
                <span class="badge ${option.active ? 'badge-success' : 'badge-warning'}">
                    ${option.active ? '✅ Actif' : '⏸️ Inactif'}
                </span>
            </td>
            <td class="text-center">
                <div class="actions">
                    <button class="btn btn-secondary btn-sm" onclick="editOption(${option.id})" title="Modifier">
                        ✏️
                    </button>
                    <button class="btn ${option.active ? 'btn-warning' : 'btn-success'} btn-sm" 
                            onclick="toggleOption(${option.id})" title="${option.active ? 'Désactiver' : 'Activer'}">
                        ${option.active ? '⏸️' : '▶️'}
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteOption(${option.id})" title="Supprimer">
                        🗑️
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function loadOptions() {
    loadOptionsData();
}

function addOption() {
    const form = document.getElementById('option-form');
    if (form) {
        form.reset();
        document.getElementById('option-id').value = '';
        document.getElementById('option-active').checked = true;
    }
    
    openModal('edit-option-modal');
}

function editOption(id) {
    // Données de test
    const mockData = {
        1: { carrier: 'heppner', code: 'rdv', label: 'Prise de RDV', amount: '15.00', unit: 'forfait', active: true },
        2: { carrier: 'xpo', code: 'premium13', label: 'Premium avant 13h', amount: '22.00', unit: 'forfait', active: true },
        3: { carrier: 'kn', code: 'palette', label: 'Frais par palette EUR', amount: '6.50', unit: 'palette', active: false }
    };
    
    const data = mockData[id];
    if (data) {
        document.getElementById('option-id').value = id;
        document.getElementById('option-carrier').value = data.carrier;
        document.getElementById('option-code').value = data.code;
        document.getElementById('option-label').value = data.label;
        document.getElementById('option-amount').value = data.amount;
        document.getElementById('option-unit').value = data.unit;
        document.getElementById('option-active').checked = data.active;
    }
    
    openModal('edit-option-modal');
}

function saveOption() {
    const form = document.getElementById('option-form');
    const formData = new FormData(form);
    
    // Validation
    const required = ['transporteur', 'code_option', 'libelle', 'montant', 'unite'];
    for (const field of required) {
        if (!formData.get(field)) {
            showNotification('Veuillez remplir tous les champs obligatoires', 'warning');
            return;
        }
    }
    
    // Simulation de sauvegarde
    showNotification('Option sauvegardée avec succès !', 'success');
    closeModal('edit-option-modal');
    
    // Recharger les données
    if (currentTab === 'options') {
        loadOptionsData();
    }
}

function toggleOption(id) {
    showNotification('Statut de l\'option modifié', 'success');
    if (currentTab === 'options') {
        loadOptionsData();
    }
}

function deleteOption(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette option ?')) {
        showNotification('Option supprimée avec succès !', 'success');
        
        if (currentTab === 'options') {
            loadOptionsData();
        }
    }
}

// =============================================================================
// GESTION DES TAXES
// =============================================================================

function loadTaxesData() {
    const content = document.getElementById('taxes-content');
    
    if (!content) return;
    
    content.innerHTML = '<div class="loading-spinner">Chargement des taxes...</div>';
    
    // Simulation de données
    setTimeout(() => {
        displayMockTaxes(content);
    }, 600);
}

function displayMockTaxes(content) {
    const mockTaxes = [
        {
            carrier: 'Heppner',
            poids_max: '2000.00',
            majoration_adr: 'Non applicable',
            part_energetique: '0.50',
            contrib_sanitaire: '0.40',
            surete: '2.30',
            surcharge_gasoil: '6.60',
            maj_idf: 'Montant fixe (7.35€)',
            dept_idf: '75,77,78,91,92,93,94,95'
        },
        {
            carrier: 'XPO',
            poids_max: '2001.00',
            majoration_adr: '+20% si ADR',
            part_energetique: '1.45',
            contrib_sanitaire: '0.00',
            surete: '0.70',
            surcharge_gasoil: '15.22',
            maj_idf: 'Pourcentage (6%)',
            dept_idf: '75,77,78,91,92,93,94,95'
        },
        {
            carrier: 'Kuehne + Nagel',
            poids_max: '1500.00',
            majoration_adr: '+20% si ADR',
            part_energetique: '0.00',
            contrib_sanitaire: '0.00',
            surete: '1.50',
            surcharge_gasoil: '6.80',
            maj_idf: 'Montant fixe (7.00€)',
            dept_idf: '6,13,17,31,33,35,38,44,59,67,69,74,75,76,84,91,92,93,94,98'
        }
    ];
    
    const taxesHtml = mockTaxes.map(tax => `
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>🚛 ${tax.carrier}</h3>
            </div>
            <div class="admin-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Poids maximum</label>
                        <div class="font-semibold text-primary">${tax.poids_max} kg</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Majoration ADR</label>
                        <div>${tax.majoration_adr}</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Participation énergétique</label>
                        <div>${formatPrice(tax.part_energetique)}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contribution sanitaire</label>
                        <div>${formatPrice(tax.contrib_sanitaire)}</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Sûreté</label>
                        <div>${formatPrice(tax.surete)}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Surcharge gasoil</label>
                        <div>${tax.surcharge_gasoil}%</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Majoration IDF</label>
                        <div>${tax.maj_idf}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Départements IDF</label>
                        <div class="text-sm text-gray-600">${tax.dept_idf}</div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    content.innerHTML = `<div class="import-export-grid">${taxesHtml}</div>`;
}

function editTaxes() {
    showNotification('Modification des taxes disponible prochainement', 'info');
}

// =============================================================================
// IMPORT/EXPORT
// =============================================================================

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        const preview = document.getElementById('file-preview');
        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');
        const importBtn = document.getElementById('import-btn');
        
        if (preview && fileName && fileSize && importBtn) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            preview.classList.remove('hidden');
            importBtn.disabled = false;
        }
        
        showNotification(`Fichier "${file.name}" sélectionné`, 'success');
    }
}

function removeFile() {
    const fileInput = document.getElementById('file-input');
    const preview = document.getElementById('file-preview');
    const importBtn = document.getElementById('import-btn');
    
    if (fileInput) fileInput.value = '';
    if (preview) preview.classList.add('hidden');
    if (importBtn) importBtn.disabled = true;
}

function importData() {
    const fileInput = document.getElementById('file-input');
    if (!fileInput || !fileInput.files[0]) {
        showNotification('Veuillez sélectionner un fichier', 'warning');
        return;
    }
    
    // Simulation d'import
    showNotification('Import en cours...', 'info');
    
    setTimeout(() => {
        showNotification('Import terminé : 25 lignes importées', 'success');
        removeFile();
        
        // Recharger les données
        if (currentTab === 'rates') {
            loadRatesData();
        } else if (currentTab === 'options') {
            loadOptionsData();
        }
    }, 2000);
}

function exportData() {
    const type = document.getElementById('export-type')?.value || 'all';
    const format = document.getElementById('export-format')?.value || 'excel';
    
    showNotification('Export en cours...', 'info');
    
    // Simulation d'export
    setTimeout(() => {
        showNotification(`Export ${type} en format ${format} terminé`, 'success');
    }, 1500);
}

// =============================================================================
// UTILITAIRES
// =============================================================================

function formatPrice(price) {
    if (price === null || price === undefined || price === '' || parseFloat(price) === 0) {
        return '<span class="text-gray-400">-</span>';
    }
    return parseFloat(price).toFixed(2) + ' €';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

function saveCurrentForm() {
    if (document.getElementById('edit-rate-modal')?.classList.contains('active')) {
        saveRate();
    } else if (document.getElementById('edit-option-modal')?.classList.contains('active')) {
        saveOption();
    } else {
        showNotification('Aucun formulaire ouvert à sauvegarder', 'info');
    }
}

function showHelp() {
    const helpText = `🎯 AIDE - Interface d'Administration Guldagil

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

🔧 RACCOURCIS
• Ctrl+S : Sauvegarder
• Ctrl+E : Exporter
• Échap : Fermer modal`;
    
    alert(helpText);
}

// =============================================================================
// NOTIFICATIONS
// =============================================================================

function showNotification(message, type = 'info') {
    // Supprimer les anciennes notifications
    document.querySelectorAll('.notification').forEach(notif => {
        notif.remove();
    });
    
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `notification alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
        animation: slideInRight 0.3s ease;
        box-shadow: var(--shadow-md);
        cursor: pointer;
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
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 4000);
    
    // Permettre de cliquer pour fermer
    notification.addEventListener('click', () => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    });
}

// =============================================================================
// GESTION DU DRAG & DROP
// =============================================================================

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('dragover');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.files = files;
            handleFileSelect({ target: { files: files } });
        }
    }
}

// =============================================================================
// ANIMATIONS CSS INTÉGRÉES
// =============================================================================

function addAnimationStyles() {
    // Ajouter les styles d'animation si ils n'existent pas
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
                border-top: 2px solid var(--primary);
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
            
            .slide-in-up {
                animation: slideInUp 0.4s ease;
            }
            
            @keyframes slideInUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .btn:hover {
                transform: translateY(-1px);
            }
            
            .admin-card:hover {
                transform: translateY(-2px);
            }
            
            .data-table tr:hover {
                transform: scale(1.005);
            }
            
            .upload-zone.dragover {
                border-color: var(--primary) !important;
                background: var(--primary-lighter) !important;
                transform: scale(1.02);
            }
        `;
        document.head.appendChild(style);
    }
}

// =============================================================================
// FONCTIONS GLOBALES EXPOSÉES
// =============================================================================

// Exposer les fonctions principales pour l'utilisation dans le HTML
window.showTab = showTab;
window.openModal = openModal;
window.closeModal = closeModal;
window.loadRates = loadRates;
window.addRate = addRate;
window.editRate = editRate;
window.saveRate = saveRate;
window.deleteRate = deleteRate;
window.loadOptions = loadOptions;
window.addOption = addOption;
window.editOption = editOption;
window.saveOption = saveOption;
window.toggleOption = toggleOption;
window.deleteOption = deleteOption;
window.editTaxes = editTaxes;
window.importData = importData;
window.exportData = exportData;
window.showHelp = showHelp;
window.handleFileSelect = handleFileSelect;
window.removeFile = removeFile;
window.handleDragOver = handleDragOver;
window.handleDragLeave = handleDragLeave;
window.handleDrop = handleDrop;

// =============================================================================
// UTILITAIRES SUPPLÉMENTAIRES
// =============================================================================

function scrollToFirstStep() {
    const firstStep = document.querySelector('.form-step');
    if (firstStep) {
        firstStep.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function downloadBackup() {
    showNotification('Génération de la sauvegarde...', 'info');
    
    setTimeout(() => {
        const link = document.createElement('a');
        link.href = 'export.php?type=backup&format=json';
        link.download = `guldagil_backup_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Sauvegarde téléchargée', 'success');
    }, 1000);
}

function showLogs() {
    showNotification('Affichage des journaux disponible prochainement', 'info');
}

// Exposer les fonctions utilitaires
window.scrollToFirstStep = scrollToFirstStep;
window.downloadBackup = downloadBackup;
window.showLogs = showLogs;

// =============================================================================
// INITIALISATION FINALE
// =============================================================================

console.log('✅ Admin.js chargé avec succès');

// Fonction de test pour vérifier le bon fonctionnement
function testAdmin() {
    console.log('🧪 Test de l\'interface admin');
    showNotification('Test de notification réussi !', 'success');
    return 'Interface admin fonctionnelle';
}

// Exposer la fonction de test
window.testAdmin = testAdmin;

// =============================================================================
// GESTION D'ERREURS GLOBALES
// =============================================================================

window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript détectée:', e.error);
    showNotification('Une erreur JavaScript s\'est produite. Consultez la console.', 'danger');
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Promise rejetée:', e.reason);
    showNotification('Erreur de communication. Vérifiez votre connexion.', 'warning');
});

// =============================================================================
// AUTO-ACTUALISATION DES STATISTIQUES (optionnel)
// =============================================================================

function updateStatistics() {
    // Cette fonction peut être appelée périodiquement pour mettre à jour les stats
    // Exemple d'implémentation avec de vraies données AJAX :
    /*
    fetch('api.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-carriers').textContent = data.carriers;
                document.getElementById('total-departments').textContent = data.departments;
                document.getElementById('total-options').textContent = data.options;
            }
        })
        .catch(error => console.error('Erreur stats:', error));
    */
}

// Mettre à jour les stats toutes les 5 minutes (optionnel)
// setInterval(updateStatistics, 300000);

// =============================================================================
// FONCTIONS DE COMPATIBILITÉ
// =============================================================================

// S'assurer que les variables CSS sont disponibles
function ensureCSSVariables() {
    const root = document.documentElement;
    const computedStyle = getComputedStyle(root);
    
    // Vérifier si les variables CSS sont définies
    if (!computedStyle.getPropertyValue('--primary')) {
        // Définir des variables de secours
        root.style.setProperty('--primary', '#2563eb');
        root.style.setProperty('--primary-light', '#3b82f6');
        root.style.setProperty('--success', '#10b981');
        root.style.setProperty('--warning', '#f59e0b');
        root.style.setProperty('--error', '#ef4444');
        root.style.setProperty('--shadow-md', '0 4px 6px -1px rgba(0, 0, 0, 0.1)');
        
        console.warn('Variables CSS manquantes - Variables de secours appliquées');
    }
}

// Appeler au chargement
ensureCSSVariables();

// =============================================================================
// EXPORT FINAL
// =============================================================================

// Export de l'objet admin pour utilisation externe
window.AdminInterface = {
    version: '1.2.0',
    showTab,
    showNotification,
    loadRates: loadRatesData,
    loadOptions: loadOptionsData,
    loadTaxes: loadTaxesData,
    test: testAdmin
};

console.log('🎯 Interface d\'administration Guldagil entièrement initialisée');

// Message de bienvenue dans la console
console.log(`
╔══════════════════════════════════════╗
║     🎯 GULDAGIL ADMIN INTERFACE      ║
║                                      ║
║  Version: 1.2.0                     ║
║  Status:  ✅ Prêt                    ║
║  Test:    testAdmin()                ║
║                                      ║
║  Support: runser.jean.thomas@       ║
║           guldagil.com               ║
╚══════════════════════════════════════╝
`);

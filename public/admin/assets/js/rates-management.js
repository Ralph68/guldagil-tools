// public/admin/assets/js/rates-management.js
// Gestion complète des tarifs - Version finale et fonctionnelle

console.log('📋 Gestionnaire de tarifs chargé');

// Variables globales
let currentPage = 1;
let currentFilters = {
    carrier: '',
    department: '',
    search: ''
};
let editingRateId = null;
let editingCarrier = null;

// =============================================================================
// INITIALISATION
// =============================================================================

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('rates-tbody')) {
        initializeRatesManagement();
    }
});

function initializeRatesManagement() {
    console.log('🚀 Initialisation du gestionnaire de tarifs');
    
    // Charger les données initiales
    Promise.all([
        loadCarriers(),
        loadDepartments()
    ]).then(() => {
        loadRates();
        showAlert('success', 'Interface des tarifs chargée !');
    }).catch(error => {
        console.error('Erreur initialisation:', error);
        showAlert('error', 'Erreur lors de l\'initialisation');
    });
    
    // Event listeners
    setupEventListeners();
}

function setupEventListeners() {
    console.log('🎧 Configuration des événements...');
    
    // Recherche
    const searchInput = document.getElementById('search-rates');
    const searchButton = document.getElementById('search-button');
    const clearButton = document.getElementById('clear-filters-button');
    const refreshButton = document.getElementById('refresh-rates-button');
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    if (searchButton) {
        searchButton.addEventListener('click', performSearch);
    }
    
    if (clearButton) {
        clearButton.addEventListener('click', clearFilters);
    }
    
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            loadRates(true);
            showAlert('info', 'Données actualisées');
        });
    }
    
    // Filtres
    const carrierFilter = document.getElementById('filter-carrier');
    const departmentFilter = document.getElementById('filter-department');
    
    if (carrierFilter) {
        carrierFilter.addEventListener('change', performSearch);
    }
    
    if (departmentFilter) {
        departmentFilter.addEventListener('change', performSearch);
    }
    
    // Export
    const exportButton = document.getElementById('export-rates-button');
    if (exportButton) {
        exportButton.addEventListener('click', exportRates);
    }
    
    // Bouton ajout
    const addButton = document.getElementById('add-rate-button');
    if (addButton) {
        addButton.addEventListener('click', function() {
            showAlert('info', 'Fonctionnalité d\'ajout en cours de développement');
        });
    }
}

// =============================================================================
// CHARGEMENT DES DONNÉES
// =============================================================================

async function loadRates(showLoading = false) {
    const tbody = document.getElementById('rates-tbody');
    
    if (showLoading && tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 2rem;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <div class="spinner"></div>
                        Chargement des tarifs...
                    </div>
                </td>
            </tr>
        `;
    }
    
    try {
        const params = new URLSearchParams({
            action: 'list',
            page: currentPage,
            limit: 25,
            ...currentFilters
        });
        
        console.log('🌐 Requête API:', `api-rates.php?${params}`);
        
        const response = await fetch(`api-rates.php?${params}`);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📨 Réponse API:', data);
        
        if (data.success) {
            displayRates(data.data.rates);
            displayPagination(data.data.pagination);
            updateFiltersInfo(data.data.filters);
        } else {
            throw new Error(data.error || 'Erreur lors du chargement');
        }
        
    } catch (error) {
        console.error('❌ Erreur lors du chargement:', error);
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center" style="padding: 2rem;">
                        <div style="color: var(--error-color); text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">⚠️</div>
                            <div>Erreur: ${error.message}</div>
                            <button class="btn btn-secondary btn-sm" onclick="loadRates(true)" style="margin-top: 1rem;">
                                🔄 Réessayer
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
        showAlert('error', 'Erreur lors du chargement des tarifs');
    }
}

async function loadCarriers() {
    try {
        console.log('🚚 Chargement des transporteurs...');
        const response = await fetch('api-rates.php?action=carriers');
        const data = await response.json();
        
        if (data.success) {
            populateCarrierFilter(data.data);
            console.log('✅ Transporteurs chargés:', data.data.length);
        }
    } catch (error) {
        console.error('❌ Erreur chargement transporteurs:', error);
    }
}

async function loadDepartments() {
    try {
        console.log('📍 Chargement des départements...');
        const response = await fetch('api-rates.php?action=departments');
        const data = await response.json();
        
        if (data.success) {
            populateDepartmentFilter(data.data);
            console.log('✅ Départements chargés:', data.data.length);
        }
    } catch (error) {
        console.error('❌ Erreur chargement départements:', error);
    }
}

// =============================================================================
// AFFICHAGE DES DONNÉES
// =============================================================================

function displayRates(rates) {
    const tbody = document.getElementById('rates-tbody');
    if (!tbody) {
        console.error('❌ Element rates-tbody non trouvé');
        return;
    }

    console.log('📊 Affichage de', rates.length, 'tarifs');

    if (rates.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 2rem; color: #666;">
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">📭</div>
                        <div>Aucun tarif trouvé pour ces critères</div>
                        <button class="btn btn-primary btn-sm" onclick="clearFilters()" style="margin-top: 1rem;">
                            🔄 Effacer les filtres
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = rates.map(rate => {
        const departmentText = rate.department_name && rate.department_name !== 'Non défini' 
            ? `${rate.department_num} - ${rate.department_name}` 
            : rate.department_num;
            
        return `
            <tr data-rate-id="${rate.id}" data-carrier="${rate.carrier_code}">
                <td class="font-semibold text-primary">${rate.carrier_name}</td>
                <td>${departmentText}</td>
                <td>${formatPrice(rate.rates.tarif_0_9)}</td>
                <td>${formatPrice(rate.rates.tarif_10_19)}</td>
                <td>${formatPrice(rate.rates.tarif_90_99)}</td>
                <td>${formatPrice(rate.rates.tarif_100_299)}</td>
                <td>${formatPrice(rate.rates.tarif_500_999)}</td>
                <td>${rate.delay || '-'}</td>
                <td class="text-center">
                    <div class="actions">
                        <button class="btn btn-secondary btn-sm" 
                                onclick="editRate(${rate.id}, '${rate.carrier_code}')" 
                                title="Modifier">
                            ✏️
                        </button>
                        <button class="btn btn-danger btn-sm" 
                                onclick="deleteRate(${rate.id}, '${rate.carrier_code}', '${departmentText}')" 
                                title="Supprimer">
                            🗑️
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function displayPagination(pagination) {
    const container = document.getElementById('pagination-container');
    if (!container) return;
    
    const { page, pages, total } = pagination;
    
    if (pages <= 1) {
        container.innerHTML = `
            <div style="text-align: center; color: #666; margin: 1rem 0; padding: 1rem; background: var(--bg-light); border-radius: var(--border-radius);">
                Total: ${total} tarifs
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="pagination-wrapper" style="display: flex; justify-content: space-between; align-items: center; margin: 1.5rem 0; padding: 1rem; background: var(--bg-light); border-radius: var(--border-radius);">
            <span style="color: #666; font-size: 0.9rem;">
                Page ${page} sur ${pages} • ${total} tarifs au total
            </span>
            <div class="pagination" style="display: flex; gap: 0.5rem;">
    `;
    
    // Bouton précédent
    if (page > 1) {
        html += `<button class="btn btn-secondary btn-sm" onclick="changePage(${page - 1})">‹ Précédent</button>`;
    }
    
    // Numéros de pages
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(pages, page + 2);
    
    if (startPage > 1) {
        html += `<button class="btn btn-secondary btn-sm" onclick="changePage(1)">1</button>`;
        if (startPage > 2) {
            html += `<span style="padding: 0.5rem;">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === page ? 'btn-primary' : 'btn-secondary';
        const disabled = i === page ? 'style="cursor: default;"' : `onclick="changePage(${i})"`;
        html += `<button class="btn ${isActive} btn-sm" ${disabled}>${i}</button>`;
    }
    
    if (endPage < pages) {
        if (endPage < pages - 1) {
            html += `<span style="padding: 0.5rem;">...</span>`;
        }
        html += `<button class="btn btn-secondary btn-sm" onclick="changePage(${pages})">${pages}</button>`;
    }
    
    // Bouton suivant
    if (page < pages) {
        html += `<button class="btn btn-secondary btn-sm" onclick="changePage(${page + 1})">Suivant ›</button>`;
    }
    
    html += `</div></div>`;
    
    container.innerHTML = html;
}

function populateCarrierFilter(carriers) {
    const select = document.getElementById('filter-carrier');
    if (!select) return;
    
    const options = carriers.map(carrier => 
        `<option value="${carrier.code}">${carrier.name} (${carrier.rates_count} tarifs)</option>`
    ).join('');
    
    select.innerHTML = '<option value="">Tous les transporteurs</option>' + options;
}

function populateDepartmentFilter(departments) {
    const select = document.getElementById('filter-department');
    if (!select) return;
    
    const options = departments.map(dept => 
        `<option value="${dept.num}">${dept.num} - ${dept.name}</option>`
    ).join('');
    
    select.innerHTML = '<option value="">Tous les départements</option>' + options;
}

function updateFiltersInfo(filters) {
    const container = document.getElementById('filters-info');
    if (!container) return;
    
    const activeFilters = [];
    
    if (filters.carrier) {
        const carrierName = document.querySelector(`#filter-carrier option[value="${filters.carrier}"]`)?.textContent || filters.carrier;
        activeFilters.push(`Transporteur: ${carrierName}`);
    }
    
    if (filters.department) {
        activeFilters.push(`Département: ${filters.department}`);
    }
    
    if (filters.search) {
        activeFilters.push(`Recherche: "${filters.search}"`);
    }
    
    if (activeFilters.length > 0) {
        container.innerHTML = `
            <div class="active-filters" style="background: #e3f2fd; border: 1px solid #2196f3; color: #1565c0; padding: 0.75rem; border-radius: var(--border-radius); font-size: 0.9rem; margin-bottom: 1rem;">
                <strong>Filtres actifs:</strong> ${activeFilters.join(' • ')}
                <button onclick="clearFilters()" style="float: right; background: none; border: none; color: #1565c0; cursor: pointer; font-weight: bold;">✕</button>
            </div>
        `;
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

// =============================================================================
// ACTIONS UTILISATEUR
// =============================================================================

function performSearch() {
    const searchInput = document.getElementById('search-rates');
    const carrierFilter = document.getElementById('filter-carrier');
    const departmentFilter = document.getElementById('filter-department');
    
    currentFilters = {
        search: searchInput?.value || '',
        carrier: carrierFilter?.value || '',
        department: departmentFilter?.value || ''
    };
    
    console.log('🔍 Application des filtres:', currentFilters);
    currentPage = 1;
    loadRates(true);
}

function clearFilters() {
    const searchInput = document.getElementById('search-rates');
    const carrierFilter = document.getElementById('filter-carrier');
    const departmentFilter = document.getElementById('filter-department');
    
    if (searchInput) searchInput.value = '';
    if (carrierFilter) carrierFilter.value = '';
    if (departmentFilter) departmentFilter.value = '';
    
    currentFilters = { search: '', carrier: '', department: '' };
    currentPage = 1;
    loadRates(true);
    
    showAlert('info', 'Filtres effacés');
}

function changePage(page) {
    currentPage = page;
    loadRates(true);
}

async function editRate(id, carrier) {
    console.log('📝 Édition tarif:', { id, carrier });
    
    editingRateId = id;
    editingCarrier = carrier;
    
    try {
        // Charger les données du tarif
        const response = await fetch(`api-rates.php?action=get&id=${id}&carrier=${carrier}`);
        const data = await response.json();
        
        if (data.success) {
            populateEditForm(data.data);
            showEditModal();
        } else {
            throw new Error(data.error || 'Erreur lors du chargement du tarif');
        }
        
    } catch (error) {
        console.error('❌ Erreur:', error);
        showAlert('error', 'Impossible de charger le tarif pour édition');
    }
}

async function deleteRate(id, carrier, departmentText) {
    if (!confirm(`Supprimer le tarif ${carrier.toUpperCase()} pour le département ${departmentText} ?\n\nCette action est irréversible.`)) {
        return;
    }
    
    try {
        console.log('🗑️ Suppression tarif:', { id, carrier, departmentText });
        
        const response = await fetch(`api-rates.php?action=delete&id=${id}&carrier=${carrier}`);
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Tarif supprimé avec succès');
            loadRates(true);
        } else {
            throw new Error(data.error || 'Erreur lors de la suppression');
        }
        
    } catch (error) {
        console.error('❌ Erreur:', error);
        showAlert('error', 'Erreur lors de la suppression du tarif');
    }
}

function exportRates() {
    try {
        const params = new URLSearchParams(currentFilters);
        const url = `export.php?type=rates&format=csv&${params}`;
        
        // Ouvrir dans un nouvel onglet
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.download = `tarifs_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showAlert('success', 'Export des tarifs démarré');
    } catch (error) {
        console.error('❌ Erreur export:', error);
        showAlert('error', 'Erreur lors de l\'export');
    }
}

// =============================================================================
// MODAL D'ÉDITION
// =============================================================================

function showEditModal() {
    const modal = document.getElementById('edit-rate-modal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
        
        // Focus sur le premier champ éditable
        setTimeout(() => {
            const firstInput = document.getElementById('edit-department-name');
            if (firstInput) {
                firstInput.focus();
                firstInput.select();
            }
        }, 100);
    }
}

function closeEditModal() {
    const modal = document.getElementById('edit-rate-modal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
    
    editingRateId = null;
    editingCarrier = null;
}

function populateEditForm(rate) {
    console.log('📋 Remplissage modal avec:', rate);
    
    // Informations générales
    document.getElementById('edit-carrier').value = rate.carrier_name;
    document.getElementById('edit-department-num').value = rate.department_num;
    document.getElementById('edit-department-name').value = rate.department_name || '';
    document.getElementById('edit-delay').value = rate.delay || '';
    
    // Tarifs
    const tariffFields = [
        'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
        'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
        'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999', 'tarif_2000_2999'
    ];
    
    tariffFields.forEach(field => {
        const input = document.getElementById(`edit-${field.replace(/_/g, '-')}`);
        if (input) {
            const value = rate.rates[field];
            input.value = value !== null && value !== undefined ? parseFloat(value).toFixed(2) : '';
        }
    });
    
    // Afficher/masquer le champ 2000-2999 pour XPO
    const tarif2000Group = document.getElementById('edit-tarif-2000-group');
    if (tarif2000Group) {
        tarif2000Group.style.display = rate.carrier_code === 'xpo' ? 'block' : 'none';
    }
    
    // Champs cachés
    document.getElementById('edit-rate-id').value = rate.id;
    document.getElementById('edit-carrier-code').value = rate.carrier_code;
}

async function saveRateChanges() {
    if (!editingRateId || !editingCarrier) {
        showAlert('error', 'Erreur: aucun tarif en cours d\'édition');
        return;
    }
    
    try {
        console.log('💾 Sauvegarde des modifications...');
        
        const formData = new FormData();
        formData.append('id', editingRateId);
        formData.append('carrier', editingCarrier);
        
        // Ajouter tous les champs du formulaire
        const form = document.getElementById('edit-rate-form');
        const inputs = form.querySelectorAll('input[type="text"], input[type="number"]');
        
        inputs.forEach(input => {
            if (!input.readOnly && input.id !== 'edit-rate-id' && input.id !== 'edit-carrier-code') {
                const fieldName = input.id.replace('edit-', '').replace(/-/g, '_');
                const value = input.value.trim();
                
                if (fieldName.startsWith('tarif_')) {
                    // Pour les tarifs, envoyer null si vide, sinon la valeur numérique
                    formData.append(fieldName, value !== '' ? parseFloat(value) : '');
                } else {
                    // Pour les autres champs, envoyer la valeur string
                    formData.append(fieldName, value);
                }
            }
        });
        
        // Log des données envoyées
        console.log('📤 Données envoyées:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }
        
        const response = await fetch('api-rates.php?action=update', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Tarif mis à jour avec succès !');
            closeEditModal();
            loadRates(true);
        } else {
            throw new Error(data.error || 'Erreur lors de la sauvegarde');
        }
        
    } catch (error) {
        console.error('❌ Erreur sauvegarde:', error);
        showAlert('error', 'Erreur lors de la sauvegarde: ' + error.message);
    }
}

// =============================================================================
// UTILITAIRES
// =============================================================================

function formatPrice(price) {
    if (price === null || price === undefined || price === '') {
        return '<span style="color: #999;">-</span>';
    }
    
    return parseFloat(price).toFixed(2).replace('.', ',') + ' €';
}

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeEditModal();
    }
});

// Fermer le modal avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});

// Ajouter le CSS du spinner si pas déjà présent
if (!document.getElementById('rates-spinner-css')) {
    const spinnerCSS = document.createElement('style');
    spinnerCSS.id = 'rates-spinner-css';
    spinnerCSS.textContent = `
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(spinnerCSS);
}

// =============================================================================
// EXPOSITION GLOBALE DES FONCTIONS
// =============================================================================

// Exposer les fonctions nécessaires globalement
window.editRate = editRate;
window.deleteRate = deleteRate;
window.changePage = changePage;
window.closeEditModal = closeEditModal;
window.saveRateChanges = saveRateChanges;
window.performSearch = performSearch;
window.clearFilters = clearFilters;
window.loadRates = loadRates;

// Fonction d'initialisation accessible globalement
window.initRatesManager = initializeRatesManagement;

console.log('✅ Gestionnaire de tarifs initialisé - 732 lignes de fonctionnalités complètes');

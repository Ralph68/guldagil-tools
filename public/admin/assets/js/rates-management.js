// public/admin/assets/js/rates-management.js - Version corrigée
console.log('📊 Chargement du module de gestion des tarifs...');

// Variables globales
let currentPage = 1;
let currentFilters = {
    carrier: '',
    department: '',
    search: ''
};
let ratesData = [];

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Module tarifs initialisé');
    initializeRatesInterface();
});

function initializeRatesInterface() {
    console.log('🔧 Initialisation interface tarifs');
    
    // Event listeners pour les boutons et filtres
    setupEventListeners();
    
    // Charger les données initiales
    loadCarriers();
    loadDepartments();
    
    // Charger les tarifs si on est sur l'onglet tarifs
    if (document.getElementById('tab-rates')?.classList.contains('active')) {
        loadRates();
    }
}

function setupEventListeners() {
    // Bouton de recherche
    const searchButton = document.getElementById('search-button');
    if (searchButton) {
        searchButton.addEventListener('click', handleSearch);
    }
    
    // Bouton effacer filtres
    const clearButton = document.getElementById('clear-filters-button');
    if (clearButton) {
        clearButton.addEventListener('click', clearFilters);
    }
    
    // Bouton actualiser
    const refreshButton = document.getElementById('refresh-rates-button');
    if (refreshButton) {
        refreshButton.addEventListener('click', () => loadRates(true));
    }
    
    // Bouton export
    const exportButton = document.getElementById('export-rates-button');
    if (exportButton) {
        exportButton.addEventListener('click', exportRates);
    }
    
    // Recherche en temps réel
    const searchInput = document.getElementById('search-rates');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(handleSearch, 500);
        });
    }
    
    // Filtres
    const carrierFilter = document.getElementById('filter-carrier');
    const departmentFilter = document.getElementById('filter-department');
    
    if (carrierFilter) {
        carrierFilter.addEventListener('change', handleSearch);
    }
    
    if (departmentFilter) {
        departmentFilter.addEventListener('change', handleSearch);
    }
}

/**
 * Charge la liste des transporteurs
 */
function loadCarriers() {
    console.log('📦 Chargement des transporteurs...');
    
    fetch('api-rates.php?action=carriers')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateCarrierFilter(data.data);
                console.log('✅ Transporteurs chargés:', data.data.length);
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('❌ Erreur chargement transporteurs:', error);
            showError('Erreur lors du chargement des transporteurs: ' + error.message);
        });
}

/**
 * Charge la liste des départements
 */
function loadDepartments() {
    console.log('🗺️ Chargement des départements...');
    
    fetch('api-rates.php?action=departments')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateDepartmentFilter(data.data);
                console.log('✅ Départements chargés:', data.data.length);
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('❌ Erreur chargement départements:', error);
            showError('Erreur lors du chargement des départements: ' + error.message);
        });
}

/**
 * Charge les tarifs avec les filtres actuels
 */
function loadRates(force = false) {
    console.log('💰 Chargement des tarifs...', { page: currentPage, filters: currentFilters, force });
    
    // Afficher le loading
    showLoading(true);
    
    // Construire l'URL avec les paramètres
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        limit: 25,
        carrier: currentFilters.carrier,
        department: currentFilters.department,
        search: currentFilters.search
    });
    
    const url = `api-rates.php?${params.toString()}`;
    console.log('📡 Requête API:', url);
    
    fetch(url)
        .then(response => {
            console.log('📥 Réponse API:', response.status, response.statusText);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('❌ Erreur API (texte):', text);
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 Données reçues:', data);
            if (data.success) {
                ratesData = data.data.rates;
                displayRates(data.data.rates);
                displayPagination(data.data.pagination);
                updateFiltersInfo(data.data.filters);
                console.log('✅ Tarifs affichés:', data.data.rates.length);
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('❌ Erreur chargement tarifs:', error);
            showError('Erreur lors du chargement des tarifs: ' + error.message);
            displayRates([]); // Afficher un tableau vide
        })
        .finally(() => {
            showLoading(false);
        });
}

/**
 * Affiche le loading
 */
function showLoading(show) {
    const tbody = document.getElementById('rates-tbody');
    if (!tbody) return;
    
    if (show) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 2rem;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 1rem;">
                        <div class="spinner" style="width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #007acc; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        Chargement des tarifs...
                    </div>
                </td>
            </tr>
        `;
    }
}

/**
 * Affiche les tarifs dans le tableau
 */
function displayRates(rates) {
    const tbody = document.getElementById('rates-tbody');
    if (!tbody) return;
    
    if (!rates || rates.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 2rem; color: #666;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                        <div style="font-size: 2rem;">📭</div>
                        <div>Aucun tarif trouvé</div>
                        <button class="btn btn-primary btn-sm" onclick="clearFilters()">
                            🔄 Effacer les filtres
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    rates.forEach(rate => {
        const statusBadge = getStatusBadge(rate.status);
        
        html += `
            <tr style="transition: background-color 0.2s ease;">
                <td>
                    <div style="font-weight: 600; color: var(--primary-color);">${rate.carrier_name}</div>
                    <div style="font-size: 0.8rem; color: #666;">${rate.carrier_code}</div>
                </td>
                <td>
                    <div style="font-weight: 500;">${rate.department_num} - ${rate.department_name}</div>
                </td>
                <td>${formatDisplayPrice(rate.rates.tarif_0_9)}</td>
                <td>${formatDisplayPrice(rate.rates.tarif_10_19)}</td>
                <td>${formatDisplayPrice(rate.rates.tarif_90_99)}</td>
                <td style="font-weight: 600;">${formatDisplayPrice(rate.rates.tarif_100_299)}</td>
                <td>${formatDisplayPrice(rate.rates.tarif_500_999)}</td>
                <td>
                    <span class="badge badge-info">${rate.delay || 'Non défini'}</span>
                </td>
                <td class="text-center">
                    <div class="actions" style="display: flex; gap: 0.5rem; justify-content: center;">
                        <button class="btn btn-secondary btn-sm" 
                                onclick="editRateModal('${rate.carrier_code}', '${rate.department_num}', ${rate.id})" 
                                title="Modifier">
                            ✏️
                        </button>
                        <button class="btn btn-danger btn-sm" 
                                onclick="confirmDeleteRate('${rate.carrier_code}', '${rate.department_num}', ${rate.id})" 
                                title="Supprimer">
                            🗑️
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

/**
 * Formate le prix pour l'affichage
 */
function formatDisplayPrice(price) {
    if (price === null || price === undefined || price === '') {
        return '<span style="color: #999;">-</span>';
    }
    return `<span style="font-weight: 500;">${parseFloat(price).toFixed(2)} €</span>`;
}

/**
 * Retourne un badge de statut
 */
function getStatusBadge(status) {
    const badges = {
        'complet': '<span class="badge badge-success">Complet</span>',
        'partiel': '<span class="badge badge-warning">Partiel</span>',
        'vide': '<span class="badge badge-danger">Vide</span>'
    };
    return badges[status] || '<span class="badge badge-info">Inconnu</span>';
}

/**
 * Affiche la pagination
 */
function displayPagination(pagination) {
    const container = document.getElementById('pagination-container');
    if (!container || !pagination) return;
    
    if (pagination.pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 0.9rem; color: #666;">
                Page ${pagination.page} sur ${pagination.pages} 
                (${pagination.total} résultats)
            </div>
            <div style="display: flex; gap: 0.5rem;">
    `;
    
    // Bouton précédent
    if (pagination.page > 1) {
        html += `<button class="btn btn-secondary btn-sm" onclick="goToPage(${pagination.page - 1})">« Précédent</button>`;
    }
    
    // Numéros de pages
    const startPage = Math.max(1, pagination.page - 2);
    const endPage = Math.min(pagination.pages, pagination.page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === pagination.page;
        html += `<button class="btn ${isActive ? 'btn-primary' : 'btn-secondary'} btn-sm" onclick="goToPage(${i})">${i}</button>`;
    }
    
    // Bouton suivant
    if (pagination.page < pagination.pages) {
        html += `<button class="btn btn-secondary btn-sm" onclick="goToPage(${pagination.page + 1})">Suivant »</button>`;
    }
    
    html += `
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

/**
 * Va à une page spécifique
 */
function goToPage(page) {
    currentPage = page;
    loadRates();
}

/**
 * Gère la recherche
 */
function handleSearch() {
    console.log('🔍 Recherche déclenchée');
    
    // Récupérer les valeurs des filtres
    currentFilters.carrier = document.getElementById('filter-carrier')?.value || '';
    currentFilters.department = document.getElementById('filter-department')?.value || '';
    currentFilters.search = document.getElementById('search-rates')?.value || '';
    
    // Remettre à la page 1
    currentPage = 1;
    
    // Charger les tarifs
    loadRates();
}

/**
 * Efface tous les filtres
 */
function clearFilters() {
    console.log('🔄 Effacement des filtres');
    
    // Réinitialiser les champs
    const searchInput = document.getElementById('search-rates');
    const carrierFilter = document.getElementById('filter-carrier');
    const departmentFilter = document.getElementById('filter-department');
    
    if (searchInput) searchInput.value = '';
    if (carrierFilter) carrierFilter.value = '';
    if (departmentFilter) departmentFilter.value = '';
    
    // Réinitialiser les filtres
    currentFilters = { carrier: '', department: '', search: '' };
    currentPage = 1;
    
    // Recharger
    loadRates();
}

/**
 * Met à jour les informations de filtres
 */
function updateFiltersInfo(filters) {
    const container = document.getElementById('filters-info');
    if (!container) return;
    
    const activeFilters = [];
    if (filters.carrier) activeFilters.push(`Transporteur: ${filters.carrier}`);
    if (filters.department) activeFilters.push(`Département: ${filters.department}`);
    if (filters.search) activeFilters.push(`Recherche: "${filters.search}"`);
    
    if (activeFilters.length > 0) {
        container.innerHTML = `
            <div style="background: #e3f2fd; border: 1px solid #1976d2; padding: 0.75rem; border-radius: 6px; font-size: 0.9rem;">
                <strong>Filtres actifs:</strong> ${activeFilters.join(', ')}
                <button onclick="clearFilters()" style="margin-left: 1rem; padding: 0.25rem 0.5rem; background: #1976d2; color: white; border: none; border-radius: 4px; font-size: 0.8rem;">Effacer</button>
            </div>
        `;
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

/**
 * Remplit le filtre des transporteurs
 */
function populateCarrierFilter(carriers) {
    const filter = document.getElementById('filter-carrier');
    if (!filter) return;
    
    // Garder l'option "Tous"
    let html = '<option value="">Tous les transporteurs</option>';
    
    carriers.forEach(carrier => {
        html += `<option value="${carrier.code}">${carrier.name} (${carrier.rates_count} tarifs)</option>`;
    });
    
    filter.innerHTML = html;
}

/**
 * Remplit le filtre des départements
 */
function populateDepartmentFilter(departments) {
    const filter = document.getElementById('filter-department');
    if (!filter) return;
    
    // Garder l'option "Tous"
    let html = '<option value="">Tous les départements</option>';
    
    departments.forEach(dept => {
        html += `<option value="${dept.num}">${dept.num} - ${dept.name}</option>`;
    });
    
    filter.innerHTML = html;
}

/**
 * Confirme la suppression d'un tarif
 */
function confirmDeleteRate(carrier, department, id) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le tarif ${carrier.toUpperCase()} pour le département ${department} ?`)) {
        deleteRate(carrier, id);
    }
}

/**
 * Supprime un tarif
 */
function deleteRate(carrier, id) {
    console.log('
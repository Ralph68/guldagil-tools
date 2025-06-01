// assets/js/rates-management.js - Gestion des tarifs côté client

class RatesManager {
    constructor() {
        this.apiUrl = 'api-rates.php';
        this.currentPage = 1;
        this.currentFilters = {};
        this.isLoading = false;
    }

    // =============================================================================
    // CHARGEMENT DES DONNÉES
    // =============================================================================

    async loadRates(page = 1, filters = {}) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.currentPage = page;
        this.currentFilters = filters;
        
        try {
            this.showLoading('rates-tbody');
            
            const params = new URLSearchParams({
                action: 'list',
                page: page,
                limit: 25,
                ...filters
            });
            
            const response = await fetch(`${this.apiUrl}?${params}`);
            const result = await response.json();
            
            if (result.success) {
                this.displayRates(result.data.rates);
                this.displayPagination(result.data.pagination);
                this.updateFiltersInfo(result.data.filters);
            } else {
                throw new Error(result.error);
            }
            
        } catch (error) {
            console.error('Erreur lors du chargement des tarifs:', error);
            this.showError('Erreur lors du chargement des tarifs: ' + error.message);
        } finally {
            this.isLoading = false;
        }
    }

    async loadCarriers() {
        try {
            const response = await fetch(`${this.apiUrl}?action=carriers`);
            const result = await response.json();
            
            if (result.success) {
                this.populateCarrierFilter(result.data);
                return result.data;
            }
        } catch (error) {
            console.error('Erreur lors du chargement des transporteurs:', error);
        }
        return [];
    }

    async loadDepartments() {
        try {
            const response = await fetch(`${this.apiUrl}?action=departments`);
            const result = await response.json();
            
            if (result.success) {
                this.populateDepartmentFilter(result.data);
                return result.data;
            }
        } catch (error) {
            console.error('Erreur lors du chargement des départements:', error);
        }
        return [];
    }

    // =============================================================================
    // AFFICHAGE DES DONNÉES
    // =============================================================================

    displayRates(rates) {
        const tbody = document.getElementById('rates-tbody');
        if (!tbody) return;

        if (rates.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center" style="padding: 2rem; color: #666;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem;">📭</div>
                            <div>Aucun tarif trouvé pour ces critères</div>
                            <button class="btn btn-primary btn-sm" onclick="ratesManager.clearFilters()">
                                Effacer les filtres
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rates.map(rate => `
            <tr data-rate-id="${rate.id}" data-carrier="${rate.carrier_code}">
                <td class="font-semibold text-primary">${rate.carrier_name}</td>
                <td>${rate.department_num} - ${rate.department_name}</td>
                <td>${this.formatPriceDisplay(rate.rates.tarif_0_9)}</td>
                <td>${this.formatPriceDisplay(rate.rates.tarif_10_19)}</td>
                <td>${this.formatPriceDisplay(rate.rates.tarif_90_99)}</td>
                <td>${this.formatPriceDisplay(rate.rates.tarif_100_299)}</td>
                <td>${this.formatPriceDisplay(rate.rates.tarif_500_999)}</td>
                <td>${rate.delay || '-'}</td>
                <td class="text-center">
                    <div class="actions">
                        <button class="btn btn-secondary btn-sm" 
                                onclick="ratesManager.editRate(${rate.id}, '${rate.carrier_code}')" 
                                title="Modifier">
                            ✏️
                        </button>
                        <button class="btn btn-danger btn-sm" 
                                onclick="ratesManager.deleteRate(${rate.id}, '${rate.carrier_code}', '${rate.department_num}')" 
                                title="Supprimer">
                            🗑️
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    displayPagination(pagination) {
        const container = document.getElementById('pagination-container');
        if (!container) return;

        const { page, pages, total } = pagination;
        
        if (pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = `
            <div class="pagination-info" style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                <span style="color: #666; font-size: 0.9rem;">
                    Page ${page} sur ${pages} (${total} tarifs au total)
                </span>
                <div class="pagination-buttons" style="display: flex; gap: 0.5rem;">
        `;

        // Bouton Précédent
        if (page > 1) {
            html += `<button class="btn btn-secondary btn-sm" onclick="ratesManager.loadRates(${page - 1}, ratesManager.currentFilters)">‹ Précédent</button>`;
        }

        // Numéros de pages
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(pages, page + 2);

        if (startPage > 1) {
            html += `<button class="btn btn-secondary btn-sm" onclick="ratesManager.loadRates(1, ratesManager.currentFilters)">1</button>`;
            if (startPage > 2) {
                html += `<span style="padding: 0.5rem;">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === page;
            html += `<button class="btn ${isActive ? 'btn-primary' : 'btn-secondary'} btn-sm" 
                     ${!isActive ? `onclick="ratesManager.loadRates(${i}, ratesManager.currentFilters)"` : ''}>
                     ${i}
                     </button>`;
        }

        if (endPage < pages) {
            if (endPage < pages - 1) {
                html += `<span style="padding: 0.5rem;">...</span>`;
            }
            html += `<button class="btn btn-secondary btn-sm" onclick="ratesManager.loadRates(${pages}, ratesManager.currentFilters)">${pages}</button>`;
        }

        // Bouton Suivant
        if (page < pages) {
            html += `<button class="btn btn-secondary btn-sm" onclick="ratesManager.loadRates(${page + 1}, ratesManager.currentFilters)">Suivant ›</button>`;
        }

        html += `
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    // =============================================================================
    // GESTION DES FILTRES
    // =============================================================================

    populateCarrierFilter(carriers) {
        const select = document.getElementById('filter-carrier');
        if (!select) return;

        select.innerHTML = '<option value="">Tous les transporteurs</option>' +
            carriers.map(carrier => 
                `<option value="${carrier.code}">${carrier.name} (${carrier.rates_count} tarifs)</option>`
            ).join('');
    }

    populateDepartmentFilter(departments) {
        const select = document.getElementById('filter-department');
        if (!select) return;

        select.innerHTML = '<option value="">Tous les départements</option>' +
            departments.map(dept => 
                `<option value="${dept.num}">${dept.num} - ${dept.name}</option>`
            ).join('');
    }

    applyFilters() {
        const filters = {};
        
        const carrierFilter = document.getElementById('filter-carrier');
        if (carrierFilter && carrierFilter.value) {
            filters.carrier = carrierFilter.value;
        }

        const departmentFilter = document.getElementById('filter-department');
        if (departmentFilter && departmentFilter.value) {
            filters.department = departmentFilter.value;
        }

        const searchInput = document.getElementById('search-rates');
        if (searchInput && searchInput.value.trim()) {
            filters.search = searchInput.value.trim();
        }

        this.loadRates(1, filters);
    }

    clearFilters() {
        const carrierFilter = document.getElementById('filter-carrier');
        const departmentFilter = document.getElementById('filter-department');
        const searchInput = document.getElementById('search-rates');

        if (carrierFilter) carrierFilter.value = '';
        if (departmentFilter) departmentFilter.value = '';
        if (searchInput) searchInput.value = '';

        this.loadRates(1, {});
    }

    updateFiltersInfo(filters) {
        const info = document.getElementById('filters-info');
        if (!info) return;

        const activeFilters = [];
        if (filters.carrier) activeFilters.push(`Transporteur: ${filters.carrier}`);
        if (filters.department) activeFilters.push(`Département: ${filters.department}`);
        if (filters.search) activeFilters.push(`Recherche: ${filters.search}`);

        if (activeFilters.length > 0) {
            info.innerHTML = `<small style="color: #666;">Filtres actifs: ${activeFilters.join(', ')}</small>`;
            info.style.display = 'block';
        } else {
            info.style.display = 'none';
        }
    }

    // =============================================================================
    // ACTIONS CRUD
    // =============================================================================

    async editRate(id, carrier) {
        try {
            // Pour l'instant, afficher une modal simple
            showAlert('info', `Édition du tarif ID: ${id} pour ${carrier} (fonctionnalité en développement)`);
            
            // TODO: Implémenter la modal d'édition
            // this.openEditModal(id, carrier);
            
        } catch (error) {
            console.error('Erreur lors de l\'édition:', error);
            showAlert('error', 'Erreur lors de l\'édition du tarif');
        }
    }

    async deleteRate(id, carrier, department) {
        if (!confirm(`Êtes-vous sûr de vouloir supprimer le tarif pour le département ${department} (${carrier}) ?`)) {
            return;
        }

        try {
            const response = await fetch(`${this.apiUrl}?action=delete&id=${id}&carrier=${carrier}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Tarif supprimé avec succès');
                this.loadRates(this.currentPage, this.currentFilters);
            } else {
                throw new Error(result.error);
            }

        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            showAlert('error', 'Erreur lors de la suppression: ' + error.message);
        }
    }

    async createRate() {
        showAlert('info', 'Création de nouveau tarif (fonctionnalité en développement)');
        
        // TODO: Implémenter la modal de création
        // this.openCreateModal();
    }

    // =============================================================================
    // UTILITAIRES
    // =============================================================================

    formatPriceDisplay(price) {
        if (price === null || price === undefined || price === '') {
            return '<span style="color: #999;">-</span>';
        }
        return `${parseFloat(price).toFixed(2)} €`;
    }

    showLoading(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 2rem;">
                    <div class="loading-spinner">Chargement des tarifs...</div>
                </td>
            </tr>
        `;
    }

    showError(message) {
        const container = document.getElementById('rates-tbody');
        if (!container) return;

        container.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 2rem;">
                    <div style="color: var(--error-color);">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">⚠️</div>
                        <div>${message}</div>
                        <button class="btn btn-secondary btn-sm" onclick="ratesManager.loadRates()" style="margin-top: 1rem;">
                            Réessayer
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    // =============================================================================
    // INITIALISATION
    // =============================================================================

    async init() {
        console.log('🚀 Initialisation du gestionnaire de tarifs');
        
        // Charger les données initiales
        await this.loadCarriers();
        await this.loadDepartments();
        await this.loadRates();

        // Configurer les écouteurs d'événements
        this.setupEventListeners();
        
        showAlert('success', 'Gestionnaire de tarifs initialisé');
    }

    setupEventListeners() {
        // Filtres
        const carrierFilter = document.getElementById('filter-carrier');
        const departmentFilter = document.getElementById('filter-department');
        const searchInput = document.getElementById('search-rates');
        const searchButton = document.getElementById('search-button');
        const clearButton = document.getElementById('clear-filters-button');

        if (carrierFilter) {
            carrierFilter.addEventListener('change', () => this.applyFilters());
        }

        if (departmentFilter) {
            departmentFilter.addEventListener('change', () => this.applyFilters());
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.applyFilters();
                }
            });
        }

        if (searchButton) {
            searchButton.addEventListener('click', () => this.applyFilters());
        }

        if (clearButton) {
            clearButton.addEventListener('click', () => this.clearFilters());
        }

        // Bouton d'ajout
        const addButton = document.getElementById('add-rate-button');
        if (addButton) {
            addButton.addEventListener('click', () => this.createRate());
        }

        // Bouton d'actualisation
        const refreshButton = document.getElementById('refresh-rates-button');
        if (refreshButton) {
            refreshButton.addEventListener('click', () => this.loadRates(this.currentPage, this.currentFilters));
        }

        // Bouton d'export
        const exportButton = document.getElementById('export-rates-button');
        if (exportButton) {
            exportButton.addEventListener('click', () => this.exportRates());
        }
    }

    async exportRates() {
        try {
            showAlert('info', 'Export en cours...');
            
            const params = new URLSearchParams({
                action: 'export',
                format: 'excel',
                ...this.currentFilters
            });

            // Pour l'instant, simuler l'export
            setTimeout(() => {
                showAlert('success', 'Export terminé (fonctionnalité simulée)');
            }, 2000);

            // TODO: Implémenter le vrai export
            // window.open(`${this.apiUrl}?${params}`, '_blank');

        } catch (error) {
            console.error('Erreur lors de l\'export:', error);
            showAlert('error', 'Erreur lors de l\'export');
        }
    }

    // =============================================================================
    // MÉTHODES PUBLIQUES POUR L'INTERFACE
    // =============================================================================

    refresh() {
        this.loadRates(this.currentPage, this.currentFilters);
    }

    search(query) {
        const searchInput = document.getElementById('search-rates');
        if (searchInput) {
            searchInput.value = query;
        }
        this.applyFilters();
    }

    filterByCarrier(carrier) {
        const carrierFilter = document.getElementById('filter-carrier');
        if (carrierFilter) {
            carrierFilter.value = carrier;
        }
        this.applyFilters();
    }

    filterByDepartment(department) {
        const departmentFilter = document.getElementById('filter-department');
        if (departmentFilter) {
            departmentFilter.value = department;
        }
        this.applyFilters();
    }
}

// Instance globale
let ratesManager = null;

// Fonction d'initialisation à appeler depuis l'interface admin
function initRatesManager() {
    if (!ratesManager) {
        ratesManager = new RatesManager();
    }
    return ratesManager.init();
}

// Exposer les fonctions nécessaires globalement
window.ratesManager = ratesManager;
window.initRatesManager = initRatesManager;

console.log('📦 Module de gestion des tarifs chargé');

// assets/js/rates-management.js - Gestion complète des tarifs avec édition

console.log('📦 Chargement du gestionnaire de tarifs...');

// Gestionnaire de tarifs complet et fonctionnel
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
        
        console.log('🔄 Chargement des tarifs, page:', page, 'filtres:', filters);
        this.isLoading = true;
        this.currentPage = page;
        this.currentFilters = filters;
        
        try {
            this.showLoading();
            
            const params = new URLSearchParams({
                action: 'list',
                page: page,
                limit: 25,
                ...filters
            });
            
            console.log('🌐 Requête API:', `${this.apiUrl}?${params}`);
            
            const response = await fetch(`${this.apiUrl}?${params}`);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('📨 Réponse API:', result);
            
            if (result.success) {
                this.displayRates(result.data.rates);
                this.displayPagination(result.data.pagination);
                this.updateFiltersInfo(result.data.filters);
                this.showSuccess(`${result.data.rates.length} tarifs chargés`);
            } else {
                throw new Error(result.error || 'Erreur inconnue');
            }
            
        } catch (error) {
            console.error('❌ Erreur lors du chargement:', error);
            this.showError('Erreur: ' + error.message);
        } finally {
            this.isLoading = false;
        }
    }

    async loadCarriers() {
        try {
            console.log('🚚 Chargement des transporteurs...');
            const response = await fetch(`${this.apiUrl}?action=carriers`);
            const result = await response.json();
            
            if (result.success) {
                this.populateCarrierFilter(result.data);
                console.log('✅ Transporteurs chargés:', result.data);
                return result.data;
            }
        } catch (error) {
            console.error('❌ Erreur transporteurs:', error);
        }
        return [];
    }

    async loadDepartments() {
        try {
            console.log('📍 Chargement des départements...');
            const response = await fetch(`${this.apiUrl}?action=departments`);
            const result = await response.json();
            
            if (result.success) {
                this.populateDepartmentFilter(result.data);
                console.log('✅ Départements chargés:', result.data);
                return result.data;
            }
        } catch (error) {
            console.error('❌ Erreur départements:', error);
        }
        return [];
    }

    // =============================================================================
    // AFFICHAGE DES DONNÉES
    // =============================================================================

    displayRates(rates) {
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
                            <button class="btn btn-primary btn-sm" onclick="window.ratesManager.clearFilters()" style="margin-top: 1rem;">
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
                <td style="font-weight: 600; color: var(--primary-color);">${rate.carrier_name}</td>
                <td>${rate.department_num} - ${rate.department_name}</td>
                <td>${this.formatPrice(rate.rates.tarif_0_9)}</td>
                <td>${this.formatPrice(rate.rates.tarif_10_19)}</td>
                <td>${this.formatPrice(rate.rates.tarif_90_99)}</td>
                <td>${this.formatPrice(rate.rates.tarif_100_299)}</td>
                <td>${this.formatPrice(rate.rates.tarif_500_999)}</td>
                <td>${rate.delay || '-'}</td>
                <td class="text-center">
                    <div style="display: flex; gap: 0.5rem; justify-content: center;">
                        <button class="btn btn-secondary btn-sm" 
                                onclick="window.ratesManager.editRate(${rate.id}, '${rate.carrier_code}')" 
                                title="Modifier">
                            ✏️
                        </button>
                        <button class="btn btn-danger btn-sm" 
                                onclick="window.ratesManager.deleteRate(${rate.id}, '${rate.carrier_code}', '${rate.department_num}')" 
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
            container.innerHTML = `<div style="text-align: center; color: #666; margin: 1rem 0;">Total: ${total} tarifs</div>`;
            return;
        }

        let html = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0; padding: 1rem; background: var(--bg-light); border-radius: var(--border-radius);">
                <span style="color: #666;">Page ${page} sur ${pages} (${total} tarifs)</span>
                <div style="display: flex; gap: 0.5rem;">
        `;

        // Bouton Précédent
        if (page > 1) {
            html += `<button class="btn btn-secondary btn-sm" onclick="window.ratesManager.loadRates(${page - 1}, window.ratesManager.currentFilters)">‹ Précédent</button>`;
        }

        // Numéros de pages (simplifié)
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(pages, page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === page;
            html += `<button class="btn ${isActive ? 'btn-primary' : 'btn-secondary'} btn-sm" 
                     ${!isActive ? `onclick="window.ratesManager.loadRates(${i}, window.ratesManager.currentFilters)"` : ''}
                     style="${isActive ? 'cursor: default;' : ''}">
                     ${i}
                     </button>`;
        }

        // Bouton Suivant
        if (page < pages) {
            html += `<button class="btn btn-secondary btn-sm" onclick="window.ratesManager.loadRates(${page + 1}, window.ratesManager.currentFilters)">Suivant ›</button>`;
        }

        html += `</div></div>`;
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

        console.log('🔍 Application des filtres:', filters);
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
            console.log('📝 Édition tarif:', { id, carrier });
            
            // 1. Récupérer les données du tarif via l'API
            const response = await fetch(`${this.apiUrl}?action=get&id=${id}&carrier=${carrier}`);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Erreur lors de la récupération du tarif');
            }
            
            // 2. Remplir la modal avec les données
            this.populateEditModal(result.data);
            
            // 3. Afficher la modal
            this.showEditModal();
            
        } catch (error) {
            console.error('❌ Erreur lors de l\'édition:', error);
            this.showError('Erreur lors de l\'édition du tarif: ' + error.message);
        }
    }

    populateEditModal(rateData) {
        console.log('📋 Remplissage modal avec:', rateData);
        
        // Informations générales
        document.getElementById('edit-rate-id').value = rateData.id;
        document.getElementById('edit-carrier-code').value = rateData.carrier_code;
        document.getElementById('edit-carrier').value = rateData.carrier_name;
        document.getElementById('edit-department-num').value = rateData.department_num;
        document.getElementById('edit-department-name').value = rateData.department_name || '';
        document.getElementById('edit-delay').value = rateData.delay || '';
        
        // Tarifs
        const tariffFields = [
            'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
            'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
            'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999'
        ];
        
        tariffFields.forEach(field => {
            const input = document.getElementById(`edit-${field.replace(/_/g, '-')}`);
            if (input) {
                const value = rateData.rates[field];
                input.value = value !== null ? parseFloat(value).toFixed(2) : '';
            }
        });
        
        // Tarif spécial XPO (2000-2999 kg)
        if (rateData.carrier_code === 'xpo') {
            document.getElementById('edit-tarif-2000-group').style.display = 'block';
            const xpoInput = document.getElementById('edit-tarif-2000-2999');
            if (xpoInput) {
                const value = rateData.rates.tarif_2000_2999;
                xpoInput.value = value !== null ? parseFloat(value).toFixed(2) : '';
            }
        } else {
            document.getElementById('edit-tarif-2000-group').style.display = 'none';
        }
    }

    showEditModal() {
        const modal = document.getElementById('edit-rate-modal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('active');
            
            // Focus sur le premier champ éditable
            setTimeout(() => {
                const firstInput = document.getElementById('edit-department-name');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        }
    }

    closeEditModal() {
        const modal = document.getElementById('edit-rate-modal');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('active');
        }
    }

    async saveRateChanges() {
        try {
            console.log('💾 Sauvegarde des modifications...');
            
            // Récupérer les données du formulaire
            const formData = this.getEditFormData();
            
            // Validation
            if (!this.validateEditForm(formData)) {
                return;
            }
            
            // Envoyer les modifications via l'API
            const response = await fetch(`${this.apiUrl}?action=update`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Tarif mis à jour avec succès !');
                this.closeEditModal();
                
                // Recharger les données
                this.loadRates(this.currentPage, this.currentFilters);
            } else {
                throw new Error(result.error || 'Erreur lors de la sauvegarde');
            }
            
        } catch (error) {
            console.error('❌ Erreur sauvegarde:', error);
            this.showError('Erreur lors de la sauvegarde: ' + error.message);
        }
    }

    getEditFormData() {
        const formData = {
            id: document.getElementById('edit-rate-id').value,
            carrier_code: document.getElementById('edit-carrier-code').value,
            department_name: document.getElementById('edit-department-name').value,
            delay: document.getElementById('edit-delay').value,
            rates: {}
        };
        
        // Récupérer tous les tarifs
        const tariffFields = [
            'tarif_0_9', 'tarif_10_19', 'tarif_20_29', 'tarif_30_39', 'tarif_40_49',
            'tarif_50_59', 'tarif_60_69', 'tarif_70_79', 'tarif_80_89', 'tarif_90_99',
            'tarif_100_299', 'tarif_300_499', 'tarif_500_999', 'tarif_1000_1999'
        ];
        
        tariffFields.forEach(field => {
            const input = document.getElementById(`edit-${field.replace(/_/g, '-')}`);
            if (input) {
                const value = input.value.trim();
                formData.rates[field] = value !== '' ? parseFloat(value) : null;
            }
        });
        
        // Tarif spécial XPO
        if (formData.carrier_code === 'xpo') {
            const xpoInput = document.getElementById('edit-tarif-2000-2999');
            if (xpoInput) {
                const value = xpoInput.value.trim();
                formData.rates.tarif_2000_2999 = value !== '' ? parseFloat(value) : null;
            }
        }
        
        return formData;
    }

    validateEditForm(formData) {
        // Validation basique
        if (!formData.id || !formData.carrier_code) {
            this.showError('Données manquantes pour la sauvegarde');
            return false;
        }
        
        // Vérifier qu'au moins un tarif est renseigné
        const hasAnyRate = Object.values(formData.rates).some(rate => rate !== null && rate > 0);
        if (!hasAnyRate) {
            this.showError('Veuillez renseigner au moins un tarif');
            return false;
        }
        
        return true;
    }

    async deleteRate(id, carrier, department) {
        if (!confirm(`Êtes-vous sûr de vouloir supprimer le tarif pour le département ${department} (${carrier}) ?`)) {
            return;
        }

        try {
            console.log('🗑️ Suppression tarif:', { id, carrier, department });
            
            const response = await fetch(`${this.apiUrl}?action=delete&id=${id}&carrier=${carrier}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess('Tarif supprimé avec succès');
                this.loadRates(this.currentPage, this.currentFilters);
            } else {
                throw new Error(result.error);
            }

        } catch (error) {
            console.error('❌ Erreur lors de la suppression:', error);
            this.showError('Erreur lors de la suppression: ' + error.message);
        }
    }

    async createRate() {
        this.showInfo('Création de nouveau tarif (fonctionnalité en développement)');
        
        // TODO: Implémenter la modal de création
        console.log('➕ Création nouveau tarif');
    }

    // =============================================================================
    // IMPORT/EXPORT
    // =============================================================================

    async exportRates() {
        try {
            this.showInfo('Export en cours...');
            
            // TODO: Implémenter le vrai export
            setTimeout(() => {
                this.showSuccess('Export terminé (fonctionnalité simulée)');
            }, 2000);

        } catch (error) {
            console.error('❌ Erreur lors de l\'export:', error);
            this.showError('Erreur lors de l\'export');
        }
    }

    // =============================================================================
    // UTILITAIRES
    // =============================================================================

    formatPrice(price) {
        if (price === null || price === undefined || price === '') {
            return '<span style="color: #999;">-</span>';
        }
        return `${parseFloat(price).toFixed(2)} €`;
    }

    showLoading() {
        const tbody = document.getElementById('rates-tbody');
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 2rem;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <div style="width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        Chargement des tarifs...
                    </div>
                </td>
            </tr>
        `;
    }

    showError(message) {
        const tbody = document.getElementById('rates-tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center" style="padding: 2rem;">
                        <div style="color: var(--error-color); text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">⚠️</div>
                            <div>${message}</div>
                            <button class="btn btn-secondary btn-sm" onclick="window.ratesManager.loadRates()" style="margin-top: 1rem;">
                                Réessayer
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }

        if (window.showAlert) {
            window.showAlert('error', message);
        } else {
            console.error('❌', message);
        }
    }

    showSuccess(message) {
        if (window.showAlert) {
            window.showAlert('success', message);
        } else {
            console.log('✅', message);
        }
    }

    showInfo(message) {
        if (window.showAlert) {
            window.showAlert('info', message);
        } else {
            console.log('ℹ️', message);
        }
    }

    // =============================================================================
    // ÉVÉNEMENTS
    // =============================================================================

    setupEventListeners() {
        console.log('🎧 Configuration des événements...');
        
        // Filtres
        const carrierFilter = document.getElementById('filter-carrier');
        const departmentFilter = document.getElementById('filter-department');
        const searchInput = document.getElementById('search-rates');
        const searchButton = document.getElementById('search-button');
        const clearButton = document.getElementById('clear-filters-button');

        if (carrierFilter) {
            carrierFilter.addEventListener('change', () => this.applyFilters());
            console.log('✅ Event listener ajouté pour filter-carrier');
        }

        if (departmentFilter) {
            departmentFilter.addEventListener('change', () => this.applyFilters());
            console.log('✅ Event listener ajouté pour filter-department');
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.applyFilters();
                }
            });
            console.log('✅ Event listener ajouté pour search-rates');
        }

        if (searchButton) {
            searchButton.addEventListener('click', () => this.applyFilters());
            console.log('✅ Event listener ajouté pour search-button');
        }

        if (clearButton) {
            clearButton.addEventListener('click', () => this.clearFilters());
            console.log('✅ Event listener ajouté pour clear-filters-button');
        }

        // Boutons d'actions
        const addButton = document.getElementById('add-rate-button');
        if (addButton) {
            addButton.addEventListener('click', () => this.createRate());
            console.log('✅ Event listener ajouté pour add-rate-button');
        }

        const refreshButton = document.getElementById('refresh-rates-button');
        if (refreshButton) {
            refreshButton.addEventListener('click', () => this.loadRates(this.currentPage, this.currentFilters));
            console.log('✅ Event listener ajouté pour refresh-rates-button');
        }

        const exportButton = document.getElementById('export-rates-button');
        if (exportButton) {
            exportButton.addEventListener('click', () => this.exportRates());
            console.log('✅ Event listener ajouté pour export-rates-button');
        }
    }

    // =============================================================================
    // INITIALISATION
    // =============================================================================

    async init() {
        console.log('🚀 Initialisation du gestionnaire de tarifs');
        
        try {
            // Charger les données initiales
            await this.loadCarriers();
            await this.loadDepartments();
            await this.loadRates();

            // Configurer les événements
            this.setupEventListeners();
            
            console.log('✅ Gestionnaire de tarifs initialisé avec succès');
            this.showSuccess('Interface des tarifs chargée !');
            
        } catch (error) {
            console.error('❌ Erreur lors de l\'initialisation:', error);
            this.showError('Erreur d\'initialisation: ' + error.message);
        }
    }

    // =============================================================================
    // MÉTHODES PUBLIQUES
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

// =============================================================================
// INITIALISATION GLOBALE
// =============================================================================

// Fonction d'initialisation globale
async function initRatesManager() {
    console.log('🔧 Initialisation du gestionnaire de tarifs...');
    
    if (!window.ratesManager) {
        window.ratesManager = new RatesManager();
    }
    
    return window.ratesManager.init();
}

// Exposer les fonctions globalement
window.initRatesManager = initRatesManager;
window.RatesManager = RatesManager;

// Fonctions globales pour la modal d'édition
window.closeEditModal = function() {
    if (window.ratesManager) {
        window.ratesManager.closeEditModal();
    }
};

window.saveRateChanges = function() {
    if (window.ratesManager) {
        window.ratesManager.saveRateChanges();
    }
};

console.log('✅ Module de gestion des tarifs chargé complètement');

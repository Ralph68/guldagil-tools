/**
 * Titre: Module JavaScript calculateur de frais de port
 * Chemin: /public/assets/js/calculateur.js
 * Version: 0.5 beta + build auto
 */

const CalculateurModule = {
    // Configuration
    config: {
        apiUrl: '/features/port/api/calculate.php',
        debounceDelay: 300,
        maxRetries: 3
    },

    // État du module
    state: {
        isCalculating: false,
        currentData: null,
        history: [],
        validationErrors: {}
    },

    // Cache DOM
    dom: {},

    /**
     * Initialisation du module
     */
    init() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadHistory();
        this.setupValidation();
        console.log('🧮 Calculateur module initialisé');
    },

    /**
     * Cache des éléments DOM
     */
    cacheDOMElements() {
        this.dom = {
            form: document.getElementById('calculatorForm'),
            departement: document.getElementById('departement'),
            poids: document.getElementById('poids'),
            type: document.getElementById('type'),
            palettes: document.getElementById('palettes'),
            adr: document.getElementById('adr'),
            enlevement: document.getElementById('enlevement'),
            optionSup: document.getElementById('option_sup'),
            calculateBtn: document.getElementById('calculateBtn'),
            resultsContent: document.getElementById('resultsContent')
        };
    },

    /**
     * Configuration des événements
     */
    setupEventListeners() {
        // Soumission formulaire
        this.dom.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleCalculate();
        });

        // Validation temps réel
        this.dom.departement.addEventListener('input', 
            this.debounce(() => this.validateDepartement(), this.config.debounceDelay)
        );

        this.dom.poids.addEventListener('input', 
            this.debounce(() => this.validatePoids(), this.config.debounceDelay)
        );

        // Auto-calcul si tous les champs requis sont valides
        ['departement', 'poids'].forEach(field => {
            this.dom[field].addEventListener('input', 
                this.debounce(() => this.autoCalculateIfValid(), this.config.debounceDelay)
            );
        });

        // Gestion des palettes selon le type
        this.dom.type.addEventListener('change', () => {
            this.handleTypeChange();
        });
    },

    /**
     * Validation département
     */
    validateDepartement() {
        const value = this.dom.departement.value.trim();
        const isValid = /^(0[1-9]|[1-8][0-9]|9[0-5])$/.test(value);
        
        this.setFieldValidation('departement', isValid);
        
        if (!isValid && value.length >= 2) {
            this.showFieldError('departement', 'Département invalide (01-95)');
        } else {
            this.clearFieldError('departement');
        }
        
        return isValid;
    },

    /**
     * Validation poids
     */
    validatePoids() {
        const value = parseFloat(this.dom.poids.value);
        const isValid = value > 0 && value <= 10000;
        
        this.setFieldValidation('poids', isValid);
        
        if (!isValid && this.dom.poids.value) {
            this.showFieldError('poids', 'Poids doit être entre 0.1 et 10000 kg');
        } else {
            this.clearFieldError('poids');
        }
        
        return isValid;
    },

    /**
     * Gestion changement de type
     */
    handleTypeChange() {
        const isPalette = this.dom.type.value === 'palette';
        this.dom.palettes.style.display = isPalette ? 'block' : 'none';
        
        if (!isPalette) {
            this.dom.palettes.value = '0';
        }
    },

    /**
     * Auto-calcul si formulaire valide
     */
    autoCalculateIfValid() {
        if (this.isFormValid() && !this.state.isCalculating) {
            setTimeout(() => this.handleCalculate(), 500);
        }
    },

    /**
     * Vérification validité formulaire
     */
    isFormValid() {
        return this.validateDepartement() && this.validatePoids();
    },

    /**
     * Gestion du calcul
     */
    async handleCalculate() {
        if (this.state.isCalculating) return;
        
        const formData = this.getFormData();
        
        if (!this.isFormValid()) {
            this.showError('Veuillez corriger les erreurs du formulaire');
            return;
        }

        this.state.isCalculating = true;
        this.showLoading();
        this.disableForm();

        try {
            const results = await this.callAPI(formData);
            this.displayResults(results, formData);
            this.saveToHistory(formData, results);
        } catch (error) {
            console.error('Erreur calcul:', error);
            this.showError('Erreur lors du calcul. Veuillez réessayer.');
        } finally {
            this.state.isCalculating = false;
            this.enableForm();
        }
    },

    /**
     * Récupération données formulaire
     */
    getFormData() {
        return {
            departement: this.dom.departement.value.trim().padStart(2, '0'),
            poids: parseFloat(this.dom.poids.value) || 0,
            type: this.dom.type.value,
            palettes: parseInt(this.dom.palettes.value) || 0,
            adr: this.dom.adr.checked ? 'oui' : 'non',
            enlevement: this.dom.enlevement.checked,
            option_sup: this.dom.optionSup.value
        };
    },

    /**
     * Appel API avec retry
     */
    async callAPI(data, retryCount = 0) {
        try {
            const response = await fetch(this.config.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Erreur de calcul');
            }

            return result;
        } catch (error) {
            if (retryCount < this.config.maxRetries) {
                console.warn(`Tentative ${retryCount + 1}/${this.config.maxRetries} échouée, retry...`);
                await this.delay(1000 * (retryCount + 1));
                return this.callAPI(data, retryCount + 1);
            }
            throw error;
        }
    },

    /**
     * Affichage des résultats
     */
    displayResults(results, formData) {
        if (!results.carriers || results.carriers.length === 0) {
            this.showNoResults();
            return;
        }

        // Trier par prix
        const sortedCarriers = results.carriers.sort((a, b) => a.price - b.price);
        const bestPrice = sortedCarriers[0].price;

        let html = '<div class="carrier-results fade-in">';
        
        sortedCarriers.forEach((carrier, index) => {
            const isBest = carrier.price === bestPrice;
            html += this.renderCarrierResult(carrier, isBest, formData);
        });
        
        html += '</div>';
        
        // Résumé
        html += this.renderSummary(results, formData);
        
        this.dom.resultsContent.innerHTML = html;
    },

    /**
     * Rendu résultat transporteur
     */
    renderCarrierResult(carrier, isBest, formData) {
        const savings = isBest ? '' : this.calculateSavings(carrier.price, formData);
        
        return `
            <div class="carrier-result ${isBest ? 'best' : ''}">
                <div class="carrier-header">
                    <div class="carrier-name">${this.escapeHtml(carrier.carrier_name)}</div>
                </div>
                <div class="carrier-price">${carrier.price_display}</div>
                <div class="carrier-details">
                    <div class="detail-item">
                        <span class="detail-label">Délai</span>
                        <span class="detail-value">${carrier.delay || '24-48h'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Service</span>
                        <span class="detail-value">${this.getServiceLabel(formData.option_sup)}</span>
                    </div>
                    ${formData.adr === 'oui' ? `
                    <div class="detail-item">
                        <span class="detail-label">ADR</span>
                        <span class="detail-value">✅ Inclus</span>
                    </div>` : ''}
                </div>
                ${savings}
            </div>
        `;
    },

    /**
     * Rendu résumé
     */
    renderSummary(results, formData) {
        const totalCarriers = results.carriers.length;
        const bestPrice = Math.min(...results.carriers.map(c => c.price));
        
        return `
            <div class="calculation-summary">
                <h4>📋 Résumé du calcul</h4>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Destination:</span>
                        <span class="summary-value">Département ${formData.departement}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Poids:</span>
                        <span class="summary-value">${formData.poids} kg</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Transporteurs:</span>
                        <span class="summary-value">${totalCarriers} disponibles</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Meilleur tarif:</span>
                        <span class="summary-value">${this.formatPrice(bestPrice)}</span>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Affichage loading
     */
    showLoading() {
        this.dom.resultsContent.innerHTML = `
            <div class="loading">
                <div class="loading-spinner"></div>
                <span>Calcul en cours...</span>
            </div>
        `;
        this.dom.calculateBtn.innerHTML = '⏳ Calcul en cours...';
        this.dom.calculateBtn.disabled = true;
    },

    /**
     * Affichage erreur
     */
    showError(message) {
        this.dom.resultsContent.innerHTML = `
            <div class="error-message">
                ❌ ${this.escapeHtml(message)}
            </div>
        `;
    },

    /**
     * Aucun résultat
     */
    showNoResults() {
        this.dom.resultsContent.innerHTML = `
            <div class="results-placeholder">
                <div class="results-placeholder-icon">😔</div>
                <p>Aucun transporteur disponible pour cette destination</p>
            </div>
        `;
    },

    /**
     * Gestion des champs
     */
    setFieldValidation(fieldName, isValid) {
        const field = this.dom[fieldName];
        field.classList.toggle('valid', isValid);
        field.classList.toggle('invalid', !isValid);
    },

    showFieldError(fieldName, message) {
        this.clearFieldError(fieldName);
        const field = this.dom[fieldName];
        const error = document.createElement('div');
        error.className = 'field-error';
        error.textContent = message;
        field.parentNode.appendChild(error);
    },

    clearFieldError(fieldName) {
        const field = this.dom[fieldName];
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    },

    /**
     * État formulaire
     */
    disableForm() {
        Object.values(this.dom).forEach(element => {
            if (element && element.disabled !== undefined) {
                element.disabled = true;
            }
        });
    },

    enableForm() {
        Object.values(this.dom).forEach(element => {
            if (element && element.disabled !== undefined) {
                element.disabled = false;
            }
        });
        this.dom.calculateBtn.innerHTML = '🧮 Calculer les tarifs';
    },

    /**
     * Historique
     */
    saveToHistory(formData, results) {
        const entry = {
            timestamp: Date.now(),
            data: formData,
            results: results,
            bestPrice: Math.min(...results.carriers.map(c => c.price))
        };
        
        this.state.history.unshift(entry);
        this.state.history = this.state.history.slice(0, 10); // Limite à 10
        
        localStorage.setItem('calculateur_history', JSON.stringify(this.state.history));
    },

    loadHistory() {
        try {
            const saved = localStorage.getItem('calculateur_history');
            this.state.history = saved ? JSON.parse(saved) : [];
        } catch (error) {
            console.warn('Erreur chargement historique:', error);
            this.state.history = [];
        }
    },

    /**
     * Utilitaires
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    formatPrice(price) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    },

    getServiceLabel(service) {
        const labels = {
            'standard': 'Standard',
            'premium13': 'Premium 13h',
            'rdv': 'Sur RDV'
        };
        return labels[service] || 'Standard';
    },

    calculateSavings(currentPrice, formData) {
        // Logique calcul économies (si applicable)
        return '';
    },

    setupValidation() {
        // Configuration validation avancée
        this.state.validationErrors = {};
    }
};

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CalculateurModule;
}

// Auto-initialisation si chargé directement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => CalculateurModule.init());
} else {
    CalculateurModule.init();
}

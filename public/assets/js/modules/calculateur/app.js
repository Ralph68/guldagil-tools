/**
 * JavaScript pour calcul dynamique et détail Excel
 * Chemin: /public/assets/js/modules/calculateur/app.js - Version améliorée
 */

class CalculateurApp {
    constructor() {
        this.modules = new Map();
        this.initialized = false;
        this.calcTimeout = null;
        this.lastCalcData = null;
    }

    async init(config) {
        if (this.initialized) return;

        try {
            console.log('🚀 Initialisation Calculateur v' + config.version);

            this.config = config;
            window.CalculateurConfig = config;
            window.calculateurApp = this; // Exposition globale

            await this.initModules();
            this.setupEventListeners();
            this.setupDynamicCalculation();
            
            this.initialized = true;
            console.log('✅ Calculateur v2.0 opérationnel');

        } catch (error) {
            console.error('❌ Erreur initialisation:', error);
            this.fallbackMode();
        }
    }

    async initModules() {
        this.stateManager = new StateManager();
        this.modules.set('state', this.stateManager);

        this.apiService = new ApiService(this.config.urls);
        this.modules.set('api', this.apiService);

        this.formController = new FormController(this.stateManager);
        this.modules.set('form', this.formController);

        this.resultsController = new ResultsController();
        this.modules.set('results', this.resultsController);

        console.log('📦 Modules initialisés');
    }

    setupEventListeners() {
        const form = document.getElementById('calc-form');
        if (form) {
            form.addEventListener('submit', this.handleCalculation.bind(this));
        }

        const resetBtn = document.getElementById('reset-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', this.handleReset.bind(this));
        }

        console.log('🔗 Event listeners configurés');
    }

    setupDynamicCalculation() {
        if (!this.config.auto_calc.enabled) return;

        const autoCalcElements = document.querySelectorAll('.auto-calc');
        
        autoCalcElements.forEach(element => {
            // Input events pour champs texte/number
            if (element.type === 'text' || element.type === 'number') {
                element.addEventListener('input', this.handleInputChange.bind(this));
            }
            
            // Change events pour select/radio/checkbox
            element.addEventListener('change', this.handleFieldChange.bind(this));
        });

        console.log('⚡ Calcul dynamique activé');
    }

    handleInputChange(event) {
        this.clearCalcTimeout();
        
        if (this.isFormReady()) {
            this.showCalculatingState();
            this.calcTimeout = setTimeout(() => {
                this.triggerAutoCalculation('input');
            }, this.config.auto_calc.delay);
        }
    }

    handleFieldChange(event) {
        // Gestion spéciale pour le type (palette field)
        if (event.target.name === 'type') {
            this.togglePalettesField(event.target.value);
        }

        this.clearCalcTimeout();
        
        if (this.isFormReady()) {
            this.showCalculatingState();
            this.calcTimeout = setTimeout(() => {
                this.triggerAutoCalculation('change');
            }, 300); // Plus rapide pour les changements
        }
    }

    async handleCalculation(event) {
        event.preventDefault();

        try {
            if (!this.formController.validate()) {
                return;
            }

            this.resultsController.showLoading();
            this.updateCalcButton('loading');

            const formData = this.formController.getData();
            
            // Éviter les calculs redondants
            if (this.isDuplicateCalculation(formData)) {
                console.log('🔄 Calcul identique, utilisation cache');
                this.updateCalcButton('ready');
                return;
            }

            this.lastCalcData = formData;
            const results = await this.apiService.calculate(formData);

            this.resultsController.showResults(results);
            this.updateCalcButton('auto');
            
            // Notification succès
            this.showToast('Calcul effectué avec succès!', 'success');

        } catch (error) {
            console.error('Erreur calcul:', error);
            this.resultsController.showError(error.message);
            this.updateCalcButton('ready');
            this.showToast('Erreur lors du calcul', 'error');
        }
    }

    async triggerAutoCalculation(source = 'auto') {
        if (!this.isFormReady()) return;

        try {
            const formData = this.formController.getData();
            
            if (this.isDuplicateCalculation(formData)) {
                this.hideCalculatingState();
                return;
            }

            console.log(`🔄 Calcul auto (${source}):`, formData);
            
            this.lastCalcData = formData;
            const results = await this.apiService.calculate(formData);

            this.resultsController.showResults(results);
            this.updateCalcButton('auto');
            this.hideCalculatingState();

        } catch (error) {
            console.error('Erreur calcul auto:', error);
            this.hideCalculatingState();
            // Pas de toast pour erreur auto (moins intrusif)
        }
    }

    handleReset() {
        this.formController.reset();
        this.resultsController.showWaiting();
        this.stateManager.reset();
        this.lastCalcData = null;
        this.updateCalcButton('ready');
        this.togglePalettesField('colis'); // Reset à colis par défaut
    }

    // Utilitaires
    isFormReady() {
        const dept = document.getElementById('departement')?.value;
        const poids = document.getElementById('poids')?.value;
        const type = document.querySelector('input[name="type"]:checked')?.value;
        
        return dept && poids && parseFloat(poids) > 0 && type;
    }

    isDuplicateCalculation(newData) {
        if (!this.lastCalcData) return false;
        
        const keys = ['departement', 'poids', 'type', 'adr', 'enlevement'];
        return keys.every(key => this.lastCalcData[key] === newData[key]);
    }

    clearCalcTimeout() {
        if (this.calcTimeout) {
            clearTimeout(this.calcTimeout);
            this.calcTimeout = null;
        }
    }

    showCalculatingState() {
        this.updateCalcButton('loading');
        
        // Micro-feedback visuel
        const form = document.getElementById('calc-form');
        if (form) {
            form.style.opacity = '0.8';
        }
    }

    hideCalculatingState() {
        const form = document.getElementById('calc-form');
        if (form) {
            form.style.opacity = '1';
        }
    }

    updateCalcButton(state) {
        const btn = document.getElementById('calc-btn');
        const text = document.getElementById('calc-btn-text');
        
        if (!btn || !text) return;

        switch(state) {
            case 'loading':
                btn.disabled = true;
                btn.classList.add('loading');
                text.textContent = 'Calcul...';
                break;
            case 'ready':
                btn.disabled = false;
                btn.classList.remove('loading');
                text.textContent = 'Calculer';
                break;
            case 'auto':
                btn.disabled = false;
                btn.classList.remove('loading');
                text.textContent = 'Recalculer';
                break;
        }
    }

    togglePalettesField(type) {
        const field = document.getElementById('field-palettes');
        if (field) {
            field.style.display = type === 'palette' ? 'block' : 'none';
            
            // Reset valeur si changement vers colis
            if (type === 'colis') {
                const palettesInput = document.getElementById('palettes');
                if (palettesInput) palettesInput.value = '';
            }
        }
    }

    showToast(message, type = 'success') {
        // Création toast notification
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animation d'apparition
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Suppression automatique
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }

    fallbackMode() {
        console.log('🔄 Mode fallback activé');
        
        const form = document.getElementById('calc-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.showToast('Service temporairement indisponible', 'error');
            });
        }
    }
}

// =====================================================================
// ResultsController amélioré avec détail Excel
// =====================================================================
class ResultsController {
    constructor() {
        this.container = document.querySelector('.results-content');
        this.states = {
            waiting: document.getElementById('results-waiting'),
            loading: document.getElementById('results-loading'),
            display: document.getElementById('results-display'),
            error: document.getElementById('results-error')
        };
    }

    showWaiting() {
        this.hideAllStates();
        if (this.states.waiting) {
            this.states.waiting.classList.add('active');
        }
    }

    showLoading() {
        this.hideAllStates();
        if (this.states.loading) {
            this.states.loading.classList.add('active');
            this.animateLoadingProgress();
        }
    }

    showResults(data) {
        this.hideAllStates();
        
        if (this.states.display) {
            this.states.display.innerHTML = this.renderResults(data);
            this.states.display.classList.add('active');
            this.setupResultsInteractions();
        }
    }

    showError(message) {
        this.hideAllStates();
        
        if (this.states.error) {
            const errorText = this.states.error.querySelector('#error-message');
            if (errorText) {
                errorText.textContent = message;
            }
            this.states.error.classList.add('active');
        }
    }

    hideAllStates() {
        Object.values(this.states).forEach(state => {
            if (state) {
                state.classList.remove('active');
            }
        });
    }

    renderResults(data) {
        if (!data.carriers) {
            return '<p>Aucun résultat disponible</p>';
        }

        let html = '<div class="results-display">';

        // Meilleur tarif en évidence
        if (data.best_rate) {
            html += this.renderBestRate(data.best_rate, data.carriers);
        }

        // Comparaison des transporteurs
        html += '<div class="carriers-comparison">';
        Object.entries(data.carriers).forEach(([carrier, info]) => {
            html += this.renderCarrierResult(carrier, info, data.best_rate?.carrier === carrier);
        });
        html += '</div>';

        // Détail du calcul Excel si disponible
        if (data.debug) {
            html += this.renderCalculationDetail(data.debug);
        }

        // Statistiques
        if (data.stats) {
            html += this.renderStats(data.stats);
        }

        html += '</div>';
        return html;
    }

    renderBestRate(bestRate, carriers) {
        const savings = this.calculateTotalSavings(bestRate, carriers);
        
        return `
            <div class="best-rate-card">
                <div class="best-rate-carrier">${bestRate.carrier_name}</div>
                <div class="best-rate-amount">${bestRate.formatted}</div>
                ${savings > 0 ? `<div class="savings-info">💰 Économie de ${savings.toFixed(2)}€ vs autres transporteurs</div>` : ''}
            </div>
        `;
    }

    renderCarrierResult(carrier, info, isBest) {
        const statusClass = info.available ? (isBest ? 'best-option' : '') : 'unavailable';
        
        return `
            <div class="carrier-result ${statusClass}">
                <div class="carrier-info">
                    <div class="carrier-logo">${this.getCarrierIcon(carrier)}</div>
                    <div class="carrier-details">
                        <h4>${info.name}</h4>
                        <p>${info.available ? 'Service disponible' : 'Non disponible'}</p>
                    </div>
                </div>
                <div class="carrier-price">
                    <div class="price-amount ${info.available ? '' : 'price-unavailable'}">
                        ${info.formatted}
                    </div>
                    ${info.available ? '<div class="price-detail">TTC, délai standard</div>' : ''}
                </div>
            </div>
        `;
    }

    renderCalculationDetail(debug) {
        let html = '<div class="calculation-detail">';
        
        Object.entries(debug).forEach(([carrier, details]) => {
            if (details.error) return; // Skip erreurs
            
            html += `
                <div class="detail-section">
                    <div class="detail-header" onclick="toggleDetail('${carrier}')">
                        <div class="detail-title">
                            🧮 Détail calcul ${carrier.toUpperCase()}
                        </div>
                        <button class="detail-toggle" type="button">Voir détail</button>
                    </div>
                    <div class="detail-content" id="detail-${carrier}">
                        ${this.renderCarrierCalculationDetail(details)}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }

    renderCarrierCalculationDetail(details) {
        if (!details.detail_calcul) return '<p>Détail non disponible</p>';
        
        const calc = details.detail_calcul;
        
        return `
            <div class="calculation-breakdown">
                <div class="calc-line">
                    <span class="calc-label">💼 Tarif de base</span>
                    <span class="calc-value">${this.formatPrice(calc.tarif_base)}</span>
                </div>
                ${calc.surcharge_gasoil > 0 ? `
                <div class="calc-line">
                    <span class="calc-label">⛽ Surcharge gasoil</span>
                    <span class="calc-value">+${this.formatPrice(calc.surcharge_gasoil)}</span>
                </div>
                ` : ''}
                ${calc.haute_saison > 0 ? `
                <div class="calc-line">
                    <span class="calc-label">🌞 Haute saison</span>
                    <span class="calc-value">+${this.formatPrice(calc.haute_saison)}</span>
                </div>
                ` : ''}
                ${calc.option > 0 ? `
                <div class="calc-line">
                    <span class="calc-label">⚙️ Options</span>
                    <span class="calc-value">+${this.formatPrice(calc.option)}</span>
                </div>
                ` : ''}
                ${calc.enlevement > 0 ? `
                <div class="calc-line">
                    <span class="calc-label">🚚 Enlèvement</span>
                    <span class="calc-value">+${this.formatPrice(calc.enlevement)}</span>
                </div>
                ` : ''}
                ${calc.taxes_fixes > 0 ? `
                <div class="calc-line">
                    <span class="calc-label">🏛️ Taxes fixes</span>
                    <span class="calc-value">+${this.formatPrice(calc.taxes_fixes)}</span>
                </div>
                ` : ''}
                <div class="calc-line total">
                    <span class="calc-label">💰 Total TTC</span>
                    <span class="calc-value">${this.formatPrice(calc.total)}</span>
                </div>
            </div>
        `;
    }

    renderStats(stats) {
        return `
            <div class="calc-stats">
                <div class="stat-info">
                    ⚡ Calculé en ${stats.calculation_time}ms
                    • ${stats.carriers_available} transporteur(s) disponible(s)
                </div>
            </div>
        `;
    }

    // Utilitaires
    getCarrierIcon(carrier) {
        const icons = {
            'xpo': 'XPO',
            'heppner': 'HPR',
            'kn': 'K+N'
        };
        return icons[carrier] || carrier.substring(0, 3).toUpperCase();
    }

    formatPrice(price) {
        return typeof price === 'number' ? 
            new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price) :
            price;
    }

    calculateTotalSavings(bestRate, carriers) {
        let totalSavings = 0;
        Object.values(carriers).forEach(carrier => {
            if (carrier.available && carrier.price > bestRate.price) {
                totalSavings += (carrier.price - bestRate.price);
            }
        });
        return totalSavings;
    }

    animateLoadingProgress() {
        const progressElement = document.getElementById('loading-progress');
        if (!progressElement) return;

        const messages = [
            'Récupération des tarifs...',
            'Calcul des options...',
            'Comparaison des transporteurs...',
            'Finalisation...'
        ];

        let currentMsg = 0;
        const interval = setInterval(() => {
            if (currentMsg < messages.length) {
                progressElement.textContent = messages[currentMsg];
                currentMsg++;
            } else {
                clearInterval(interval);
            }
        }, 200);
    }

    setupResultsInteractions() {
        // Les interactions sont gérées par les fonctions globales
        // définies dans le HTML (toggleDetail, etc.)
    }
}

// =====================================================================
// Fonctions globales pour interactions
// =====================================================================
window.toggleDetail = function(carrier) {
    const content = document.getElementById(`detail-${carrier}`);
    const button = content?.previousElementSibling.querySelector('.detail-toggle');
    
    if (content && button) {
        if (content.classList.contains('show')) {
            content.classList.remove('show');
            button.textContent = 'Voir détail';
        } else {
            content.classList.add('show');
            button.textContent = 'Masquer';
        }
    }
};

window.retryCalculation = function() {
    if (window.calculateurApp) {
        window.calculateurApp.triggerAutoCalculation('retry');
    }
};

window.contactSupport = function() {
    window.open('mailto:support@guldagil.com?subject=Erreur Calculateur&body=Veuillez décrire le problème rencontré...', '_blank');
};

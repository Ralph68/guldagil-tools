/**
 * Titre: Module JavaScript calculateur de frais de port - Version modernisée
 * Chemin: /public/port/assets/js/port.js
 * Version: 0.5 beta + build auto
 */

const CalculateurModule = {
    // Configuration
    config: {
        apiUrl: '?ajax=calculate',
        debounceDelay: 300,
        maxRetries: 3
    },

    // État du module
    state: {
        isCalculating: false,
        currentData: null,
        history: [],
        validationErrors: {},
        currentStep: 1,
        adrSelected: false
    },

    // Cache DOM avec nouvelles classes CSS
    dom: {},

    /**
     * Initialisation du module
     */
    init() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadHistory();
        this.setupValidation();
        this.setupSteps();
        console.log('🧮 Calculateur module initialisé');
    },

    /**
     * Cache des éléments DOM avec classes CSS modernisées
     */
    cacheDOMElements() {
        this.dom = {
            form: document.getElementById('calculatorForm'),
            departement: document.getElementById('departement'),
            poids: document.getElementById('poids'),
            type: document.getElementById('type'),
            palettes: document.getElementById('palettes'),
            paletteEur: document.getElementById('palette_eur'),
            adr: document.getElementById('adr'),
            enlevement: document.getElementById('enlevement'),
            optionSup: document.getElementById('option_sup'),
            calculateBtn: document.getElementById('calculateBtn'),
            resultsContent: document.getElementById('resultsContent'),
            calcStatus: document.getElementById('calcStatus'),
            palettesGroup: document.getElementById('palettesGroup'),
            paletteEurGroup: document.getElementById('paletteEurGroup'),
            
            // Nouveaux éléments pour les étapes
            stepBtns: document.querySelectorAll('.calc-step-btn'),
            stepContents: document.querySelectorAll('.calc-step-content'),
            toggleBtns: document.querySelectorAll('.calc-toggle-btn')
        };
    },

    /**
     * Configuration des étapes
     */
    setupSteps() {
        // Gestion des boutons d'étapes
        this.dom.stepBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const step = parseInt(e.target.dataset.step);
                this.activateStep(step);
            });
        });

        // Gestion des toggles (ADR, enlèvement)
        this.dom.toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleToggle(e.target);
            });
        });

        // Gestion type palette/colis
        this.dom.type.addEventListener('change', () => {
            this.handleTypeChange();
        });
    },

    /**
     * Activer une étape
     */
    activateStep(stepNumber) {
        this.state.currentStep = stepNumber;
        
        // Mettre à jour les boutons
        this.dom.stepBtns.forEach(btn => {
            btn.classList.remove('active');
            if (parseInt(btn.dataset.step) === stepNumber) {
                btn.classList.add('active');
            }
        });

        // Mettre à jour le contenu
        this.dom.stepContents.forEach(content => {
            content.classList.remove('active');
            if (parseInt(content.dataset.step) === stepNumber) {
                content.classList.add('active');
            }
        });
    },

    /**
     * Gestion des toggles
     */
    handleToggle(button) {
        const group = button.parentElement;
        const hiddenInput = group.nextElementSibling;
        
        // Désactiver tous les boutons du groupe
        group.querySelectorAll('.calc-toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Activer le bouton cliqué
        button.classList.add('active');
        
        // Mettre à jour le champ caché
        const value = button.dataset.adr || button.dataset.enlevement;
        if (hiddenInput && hiddenInput.type === 'hidden') {
            hiddenInput.value = value;
        }

        // Gestion spéciale ADR
        if (button.dataset.adr) {
            this.state.adrSelected = value === 'oui';
        }
    },

    /**
     * Gestion du changement de type
     */
    handleTypeChange() {
        const isLot = this.dom.type.value === 'palette';
        
        if (this.dom.palettesGroup) {
            this.dom.palettesGroup.style.display = isLot ? 'block' : 'none';
        }
        
        if (this.dom.paletteEurGroup) {
            this.dom.paletteEurGroup.style.display = isLot ? 'block' : 'none';
        }
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

        // Validation temps réel avec debounce
        this.dom.departement.addEventListener('input', 
            this.debounce(() => this.validateDepartement(), this.config.debounceDelay)
        );

        this.dom.poids.addEventListener('input', 
            this.debounce(() => this.validatePoids(), this.config.debounceDelay)
        );

        // Auto-progression et calcul intelligent
        ['departement', 'poids'].forEach(field => {
            this.dom[field].addEventListener('input', 
                this.debounce(() => this.autoCalculateIfValid(), this.config.debounceDelay)
            );
            
            // Progression manuelle avec Enter
            this.dom[field].addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (field === 'departement' && this.validateDepartement()) {
                        this.activateStep(2);
                        this.dom.poids.focus();
                    } else if (field === 'poids' && this.validatePoids()) {
                        this.activateStep(3);
                        document.querySelector('[data-adr="non"]').focus();
                    }
                }
            });
        });

        // Navigation étapes avec flèches
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey) {
                if (e.key === 'ArrowLeft' && this.state.currentStep > 1) {
                    this.activateStep(this.state.currentStep - 1);
                    e.preventDefault();
                } else if (e.key === 'ArrowRight' && this.state.currentStep < 3) {
                    this.activateStep(this.state.currentStep + 1);
                    e.preventDefault();
                }
            }
        });
    },

    /**
     * Validation département
     */
    validateDepartement() {
        const value = this.dom.departement.value.trim();
        const isValid = /^[0-9]{2,3}$/.test(value);
        
        this.updateFieldValidation('departement', isValid, 
            isValid ? '' : 'Format invalide (ex: 75, 69, 13)');
        
        return isValid;
    },

    /**
     * Validation poids
     */
    validatePoids() {
        const value = parseFloat(this.dom.poids.value);
        const isValid = value >= 1 && value <= 3000 && Number.isInteger(value);
        
        this.updateFieldValidation('poids', isValid, 
            isValid ? '' : 'Poids entre 1 et 3000 kg (entier)');
        
        return isValid;
    },

    /**
     * Mise à jour validation champ
     */
    updateFieldValidation(fieldName, isValid, errorMessage) {
        const field = this.dom[fieldName];
        const errorElement = document.getElementById(fieldName + 'Error');
        
        if (isValid) {
            field.classList.remove('error');
            field.classList.add('valid');
            if (errorElement) errorElement.textContent = '';
        } else {
            field.classList.add('error');
            field.classList.remove('valid');
            if (errorElement) errorElement.textContent = errorMessage;
        }
        
        this.state.validationErrors[fieldName] = !isValid;
    },

    /**
     * Auto-calcul si formulaire valide
     */
    autoCalculateIfValid() {
        const deptValid = this.validateDepartement();
        const poidsValid = this.validatePoids();
        
        // Progression automatique des étapes
        if (deptValid && this.state.currentStep === 1) {
            // Passer automatiquement à l'étape 2
            setTimeout(() => this.activateStep(2), 500);
        } else if (deptValid && poidsValid && this.state.currentStep === 2) {
            // Passer automatiquement à l'étape 3
            setTimeout(() => this.activateStep(3), 500);
        } else if (deptValid && poidsValid && this.state.currentStep >= 3 && !this.state.isCalculating) {
            // Lancer le calcul automatiquement
            setTimeout(() => this.handleCalculate(), 800);
        }
    },

    /**
     * Configuration validation
     */
    setupValidation() {
        // Validation en temps réel avec indicateurs visuels
        const requiredFields = ['departement', 'poids'];
        
        requiredFields.forEach(fieldName => {
            const field = this.dom[fieldName];
            if (field) {
                field.addEventListener('blur', () => {
                    this[`validate${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`]();
                });
            }
        });
    },

    /**
     * Chargement historique
     */
    loadHistory() {
        try {
            const saved = localStorage.getItem('calc_history');
            this.state.history = saved ? JSON.parse(saved) : [];
        } catch (e) {
            console.warn('Erreur chargement historique:', e);
            this.state.history = [];
        }
    },

    /**
     * Sauvegarde dans l'historique
     */
    saveToHistory(params, results) {
        const entry = {
            timestamp: Date.now(),
            params: { ...params },
            results: { ...results },
            id: 'calc_' + Date.now()
        };
        
        this.state.history.unshift(entry);
        this.state.history = this.state.history.slice(0, 10); // Garder 10 max
        
        try {
            localStorage.setItem('calc_history', JSON.stringify(this.state.history));
        } catch (e) {
            console.warn('Erreur sauvegarde historique:', e);
        }
        
        this.updateHistoryDisplay();
    },

    /**
     * Mise à jour affichage historique
     */
    updateHistoryDisplay() {
        const historySection = document.getElementById('historySection');
        const historyContent = document.getElementById('historyContent');
        
        if (!historySection || !historyContent) return;
        
        if (this.state.history.length > 0) {
            historySection.style.display = 'block';
            
            let html = '<div class="calc-history-list">';
            this.state.history.forEach(entry => {
                const date = new Date(entry.timestamp).toLocaleString();
                html += `
                    <div class="calc-history-item" onclick="CalculateurModule.replayCalculation('${entry.id}')">
                        <div class="calc-history-header">
                            <span class="calc-history-date">${date}</span>
                            <span class="calc-history-dept">${entry.params.departement}</span>
                        </div>
                        <div class="calc-history-details">
                            ${entry.params.poids}kg - ${entry.params.type}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            historyContent.innerHTML = html;
        }
    },

    /**
     * Rejouer un calcul
     */
    replayCalculation(entryId) {
        const entry = this.state.history.find(h => h.id === entryId);
        if (!entry) return;
        
        // Remplir le formulaire
        Object.entries(entry.params).forEach(([key, value]) => {
            const field = this.dom[key];
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = value;
                } else {
                    field.value = value;
                }
            }
        });
        
        // Mettre à jour l'affichage
        this.handleTypeChange();
        this.displayResults(entry.results);
    },

    /**
     * Gestion du calcul
     */
    async handleCalculate() {
        if (this.state.isCalculating) return;
        
        // Validation finale
        if (!this.validateDepartement() || !this.validatePoids()) {
            this.showError('Veuillez corriger les erreurs dans le formulaire');
            return;
        }
        
        this.state.isCalculating = true;
        this.dom.form.classList.add('loading');
        this.dom.calcStatus.textContent = '⏳ Calcul en cours...';
        this.dom.calculateBtn.disabled = true;
        
        try {
            const formData = this.getFormData();
            const data = await this.callAPI(formData);
            this.displayResults(data);
            this.saveToHistory(formData, data);
        } catch (error) {
            console.error('Erreur calcul:', error);
            this.showError('Erreur de calcul: ' + error.message);
        } finally {
            this.state.isCalculating = false;
            this.dom.form.classList.remove('loading');
            this.dom.calculateBtn.disabled = false;
        }
    },

    /**
     * Récupération données formulaire
     */
    getFormData() {
        const formData = new FormData(this.dom.form);
        const params = Object.fromEntries(formData.entries());
        
        // Ajouter palette_eur si visible
        if (this.dom.paletteEurGroup && this.dom.paletteEurGroup.style.display !== 'none') {
            params.palette_eur = parseInt(this.dom.paletteEur.value) || 0;
        }
        
        return params;
    },

    /**
     * Appel API
     */
    async callAPI(params) {
        const response = await fetch(this.config.apiUrl, {
            method: 'POST',
            body: new URLSearchParams(params),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erreur inconnue');
        }
        
        return data;
    },

    /**
     * Affichage des résultats avec nouvelles classes CSS
     */
    displayResults(data) {
        this.state.currentData = data;
        this.dom.calcStatus.textContent = `✅ Calculé en ${data.time_ms || 0}ms`;
        
        // Tri des transporteurs par prix
        const carriers = Object.entries(data.carriers || {})
            .filter(([name, result]) => result && result.prix_ttc)
            .sort((a, b) => a[1].prix_ttc - b[1].prix_ttc);
        
        if (carriers.length === 0) {
            this.showError('Aucun transporteur disponible pour cette destination');
            return;
        }
        
        let html = '<div class="calc-carrier-list">';
        
        carriers.forEach(([carrierName, result], index) => {
            const isBest = index === 0;
            const cardClass = isBest ? 'calc-carrier-card calc-carrier-best' : 'calc-carrier-card';
            
            html += `
                <div class="${cardClass}">
                    ${isBest ? '<div class="calc-best-badge">🏆 Meilleur tarif</div>' : ''}
                    <div class="calc-carrier-header">
                        <div class="calc-carrier-name">${this.formatCarrierName(carrierName)}</div>
                        <div class="calc-carrier-price">${this.formatPrice(result.prix_ttc)} €</div>
                    </div>
                    <div class="calc-carrier-details">
                        <div class="calc-detail-item">
                            <span class="calc-detail-label">Prix HT</span>
                            <span class="calc-detail-value">${this.formatPrice(result.prix_ht)} €</span>
                        </div>
                        <div class="calc-detail-item">
                            <span class="calc-detail-label">Délai</span>
                            <span class="calc-detail-value">${result.delai || 'N/A'}</span>
                        </div>
                        ${result.service ? `
                        <div class="calc-detail-item">
                            <span class="calc-detail-label">Service</span>
                            <span class="calc-detail-value">${result.service}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        this.dom.resultsContent.innerHTML = html;
        
        // Afficher debug si disponible
        if (data.debug && data.debug.length > 0) {
            this.showDebugInfo(data.debug);
        }
    },

    /**
     * Formatage nom transporteur
     */
    formatCarrierName(name) {
        const names = {
            'xpo': 'XPO Logistics',
            'heppner': 'Heppner',
            'kn': 'Kuehne+Nagel',
            'geodis': 'Geodis'
        };
        return names[name.toLowerCase()] || name.toUpperCase();
    },

    /**
     * Formatage prix
     */
    formatPrice(price) {
        return parseFloat(price).toFixed(2);
    },

    /**
     * Affichage erreur
     */
    showError(message) {
        this.dom.calcStatus.textContent = '❌ Erreur';
        this.dom.resultsContent.innerHTML = `
            <div class="calc-error">
                <div class="calc-error-icon">❌</div>
                <div class="calc-error-content">
                    <h3>Erreur de calcul</h3>
                    <p>${message}</p>
                </div>
            </div>
        `;
    },

    /**
     * Affichage debug
     */
    showDebugInfo(debugData) {
        const debugContainer = document.getElementById('debugContainer');
        const debugContent = document.getElementById('debugContent');
        
        if (debugContainer && debugContent) {
            debugContainer.style.display = 'block';
            debugContent.innerHTML = `
                <pre class="calc-debug-pre">${JSON.stringify(debugData, null, 2)}</pre>
            `;
        }
    },

    /**
     * Debounce utility
     */
    debounce(func, wait) {
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
};

// Fonctions globales pour les boutons onclick
window.contactExpress = function() {
    const subject = 'Demande Express Dédié - Livraison 12h';
    const body = `Bonjour,

Je souhaite obtenir un devis pour un transport express dédié :

- Type : Express 12h (chargé après-midi → livré lendemain 8h)
- Poids approximatif : [à compléter] kg
- Département destination : [à compléter]
- Date souhaitée : [à compléter]
- Détails urgence : [à compléter]

Merci de me communiquer le tarif et les modalités.

Cordialement`;

    const mailtoLink = `mailto:contact@guldagil.com?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    window.location.href = mailtoLink;
};

window.resetForm = function() {
    const form = document.getElementById('calculatorForm');
    if (form) {
        form.reset();
        
        // Reset état
        CalculateurModule.state.currentStep = 1;
        CalculateurModule.state.adrSelected = false;
        CalculateurModule.activateStep(1);
        
        // Reset affichage
        CalculateurModule.handleTypeChange();
        
        // Reset résultats
        document.getElementById('resultsContent').innerHTML = `
            <div class="calc-empty-state">
                <div class="calc-empty-icon">🧮</div>
                <p class="calc-empty-text">Complétez le formulaire pour voir les tarifs</p>
            </div>
        `;
        document.getElementById('calcStatus').textContent = '⏳ En attente...';
        
        // Reset validation
        document.querySelectorAll('.calc-form-input').forEach(input => {
            input.classList.remove('error', 'valid');
        });
        document.querySelectorAll('.calc-error-message').forEach(error => {
            error.textContent = '';
        });
    }
};

window.toggleHistory = function() {
    const content = document.getElementById('historyContent');
    const toggle = document.getElementById('historyToggle');
    
    if (content && toggle) {
        if (content.style.display === 'block') {
            content.style.display = 'none';
            toggle.textContent = '▼';
        } else {
            content.style.display = 'block';
            toggle.textContent = '▲';
        }
    }
};

window.toggleDebug = function() {
    const content = document.getElementById('debugContent');
    const toggle = document.getElementById('debugToggle');
    
    if (content && toggle) {
        if (content.style.display === 'block') {
            content.style.display = 'none';
            toggle.textContent = '▼';
        } else {
            content.style.display = 'block';
            toggle.textContent = '▲';
        }
    }
};

// Initialisation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => CalculateurModule.init());
} else {
    CalculateurModule.init();
}

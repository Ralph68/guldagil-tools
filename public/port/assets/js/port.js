/**
 * Titre: Module JavaScript calculateur de frais de port - Version corrigée ADR obligatoire
 * Chemin: /public/port/assets/js/port.js
 * Version: 0.5 beta + build auto
 */

const CalculateurModule = {
    config: {
        apiUrl: '?ajax=calculate',
        debounceDelay: 300,
        maxRetries: 3
    },

    state: {
        isCalculating: false,
        currentData: null,
        history: [],
        validationErrors: {},
        currentStep: 1,
        adrSelected: null
    },

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
     * Mise en cache des éléments DOM
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
            stepContents: document.querySelectorAll('.calc-form-step'),
            toggleBtns: document.querySelectorAll('.calc-toggle-btn')
        };
    },

    /**
     * Setup des listeners
     */
    setupEventListeners() {
        // Validation temps réel
        if (this.dom.departement) {
            this.dom.departement.addEventListener('input', 
                this.debounce(() => {
                    this.validateDepartement();
                    this.autoProgressIfValid();
                }, this.config.debounceDelay)
            );
            this.dom.departement.addEventListener('blur', () => {
                this.validateDepartement();
                this.autoProgressIfValid();
            });
        }

        if (this.dom.poids) {
            this.dom.poids.addEventListener('input', 
                this.debounce(() => {
                    this.validatePoids();
                    this.autoProgressIfValid();
                }, this.config.debounceDelay)
            );
            this.dom.poids.addEventListener('blur', () => {
                this.validatePoids();
                this.autoProgressIfValid();
            });
        }

        // Soumission formulaire
        if (this.dom.form) {
            this.dom.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCalculate();
            });
        }

        // Bouton calcul manuel (si présent)
        if (this.dom.calculateBtn) {
            this.dom.calculateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleCalculate();
            });
        }

        // Navigation clavier
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                this.handleEnterKey(e);
            }
        });
    },

    /**
     * Gestion des étapes
     */
    setupSteps() {
        // Étapes du haut
        this.dom.stepBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const step = parseInt(e.currentTarget.dataset.step);
                this.activateStep(step);
            });
        });

        // Toggles (ADR, enlèvement)
        this.dom.toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleToggle(e.currentTarget);
            });
        });

        // Type palette/colis
        if (this.dom.type) {
            this.dom.type.addEventListener('change', () => {
                this.handleTypeChange();
                this.autoProgressIfValid();
            });
        }
    },

    /**
     * Auto progression entre étapes
     */
    autoProgressIfValid() {
        const deptValid = this.validateDepartement();
        const poidsValid = this.validatePoids();
        const adrValid = this.isADRSelected();

        // Étape 1 → 2
        if (deptValid && this.state.currentStep === 1) {
            setTimeout(() => {
                this.activateStep(2);
                if (this.dom.poids) this.dom.poids.focus();
            }, 400);
        }
        // Étape 2 → 3
        else if (deptValid && poidsValid && this.state.currentStep === 2) {
            setTimeout(() => {
                this.activateStep(3);
                if (this.dom.type) this.dom.type.focus();
            }, 400);
        }
        // Étape 3 : calcul automatique si tout validé
        else if (deptValid && poidsValid && adrValid && this.state.currentStep >= 3 && !this.state.isCalculating) {
            setTimeout(() => this.handleCalculate(), 800);
        }
    },

    /**
     * Gestion de la touche Entrée pour progression rapide
     */
    handleEnterKey(e) {
        const activeElement = document.activeElement;

        if (activeElement === this.dom.departement && this.validateDepartement()) {
            e.preventDefault();
            this.activateStep(2);
            setTimeout(() => this.dom.poids.focus(), 100);
        }
        else if (activeElement === this.dom.poids && this.validatePoids()) {
            e.preventDefault();
            this.activateStep(3);
            setTimeout(() => this.dom.type.focus(), 100);
        }
        else if (this.state.currentStep >= 3 && this.isFormValid()) {
            e.preventDefault();
            this.handleCalculate();
        }
    },

    /**
     * Activation d'une étape
     */
    activateStep(stepNumber) {
        const previousStep = this.state.currentStep;
        this.state.currentStep = stepNumber;

        // Boutons étapes (visuels)
        this.dom.stepBtns.forEach(btn => {
            const btnStep = parseInt(btn.dataset.step);
            btn.classList.remove('active', 'completed');
            if (btnStep === stepNumber) btn.classList.add('active');
            else if (btnStep < stepNumber) btn.classList.add('completed');
        });

        // Contenus étapes
        this.dom.stepContents.forEach(content => {
            const contentStep = parseInt(content.dataset.step);
            if (contentStep === stepNumber) {
                content.style.display = 'block';
                content.classList.add('active');
            } else {
                content.classList.remove('active');
                content.style.display = 'none';
            }
        });

        // Focus sur le premier champ utile
        this.focusFirstFieldInStep(stepNumber);

        this.showStepNotification(stepNumber, previousStep);
    },

    focusFirstFieldInStep(stepNumber) {
        setTimeout(() => {
            switch(stepNumber) {
                case 1: if (this.dom.departement) this.dom.departement.focus(); break;
                case 2: if (this.dom.poids) this.dom.poids.focus(); break;
                case 3: if (this.dom.type) this.dom.type.focus(); break;
            }
        }, 200);
    },

    showStepNotification(newStep, previousStep) {
        const messages = {
            1: '📍 Saisissez le département de destination',
            2: '⚖️ Indiquez le poids de votre envoi',
            3: '⚙️ Choisissez vos options d\'expédition'
        };
        if (newStep > previousStep) {
            this.showMessage(messages[newStep], 'success', 1600);
        }
    },

    /**
     * Validation du département (2 ou 3 chiffres, 2A/2B)
     */
    validateDepartement() {
        if (!this.dom.departement) return false;
        const value = this.dom.departement.value.trim().toUpperCase();
        const isValid = /^(0[1-9]|[1-8][0-9]|9[0-5]|2[AB])$/.test(value) || /^[0-9]{2,3}$/.test(value);
        this.updateFieldValidation('departement', isValid, 
            isValid ? '' : 'Département invalide (ex: 75, 69, 13, 2A)');
        return isValid;
    },

    /**
     * Validation du poids
     */
    validatePoids() {
        if (!this.dom.poids) return false;
        const value = parseFloat(this.dom.poids.value);
        const isValid = value >= 1 && value <= 3000 && !isNaN(value);
        this.updateFieldValidation('poids', isValid, 
            isValid ? '' : 'Poids entre 1 et 3000 kg');
        return isValid;
    },

    /**
     * Vérification que ADR a été sélectionné (Oui ou Non)
     */
    isADRSelected() {
        const adrValue = this.dom.adr ? this.dom.adr.value : '';
        return adrValue === 'oui' || adrValue === 'non';
    },

    /**
     * Validation complète du formulaire (ADR obligatoire)
     */
    isFormValid() {
        const adrValid = this.isADRSelected();
        if (!adrValid) {
            this.showMessage('⚠️ Veuillez indiquer si votre transport est ADR (Oui ou Non)', 'error');
        }
        return this.validateDepartement() && this.validatePoids() && adrValid;
    },

    /**
     * Mise à jour visuelle de la validation d'un champ
     */
    updateFieldValidation(fieldName, isValid, errorMessage) {
        const field = this.dom[fieldName];
        const errorElement = document.getElementById(fieldName + 'Error');
        if (!field) return;
        field.classList.remove('error', 'valid');
        if (isValid) field.classList.add('valid');
        else if (field.value.trim() !== '') field.classList.add('error');
        if (errorElement) errorElement.textContent = errorMessage;
        this.state.validationErrors[fieldName] = !isValid;
    },

    /**
     * Gestion des toggles (ADR, enlèvement)
     */
    handleToggle(clickedBtn) {
        const group = clickedBtn.closest('.calc-toggle-group');
        if (!group) return;
        // Retirer active de tous les boutons du groupe
        group.querySelectorAll('.calc-toggle-btn').forEach(btn => btn.classList.remove('active'));
        // Activer le bouton cliqué
        clickedBtn.classList.add('active');
        // Si c'est ADR, synchroniser le hidden input
        if (clickedBtn.dataset.adr !== undefined && this.dom.adr) {
            this.dom.adr.value = clickedBtn.dataset.adr;
            this.state.adrSelected = clickedBtn.dataset.adr === 'oui';
        }
        // Idem pour enlèvement si tu veux
        if (clickedBtn.dataset.enlevement !== undefined && this.dom.enlevement) {
            this.dom.enlevement.value = clickedBtn.dataset.enlevement;
        }
        this.autoProgressIfValid();
    },

    /**
     * Changement de type (palette/colis)
     */
    handleTypeChange() {
        if (!this.dom.type) return;
        const isPalette = this.dom.type.value === 'palette';
        if (this.dom.palettesGroup) this.dom.palettesGroup.style.display = isPalette ? 'block' : 'none';
        if (this.dom.paletteEurGroup) this.dom.paletteEurGroup.style.display = isPalette ? 'block' : 'none';
        if (!isPalette && this.dom.palettes) this.dom.palettes.value = '1';
        if (!isPalette && this.dom.paletteEur) this.dom.paletteEur.value = '0';
    },

    /**
     * Affiche une notification utilisateur
     */
    showMessage(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `calc-notification calc-notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed; top: 20px; right: 20px;
            padding: 12px 20px; border-radius: 6px;
            color: white; font-weight: 500; z-index: 9999;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            background: ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#3182ce'};
        `;
        document.body.appendChild(notification);
        setTimeout(() => { notification.style.transform = 'translateX(0)'; }, 100);
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => { if (notification.parentNode) notification.parentNode.removeChild(notification); }, 300);
        }, duration);
    },

    /**
     * Gestion du calcul
     */
    async handleCalculate() {
        if (this.state.isCalculating) return;

        // Validation ADR en plus avant calcul
        if (!this.isADRSelected()) {
            this.showMessage('⚠️ Veuillez sélectionner Oui ou Non pour ADR.', 'error');
            if (this.dom.adr) this.dom.adr.scrollIntoView({behavior: "smooth"});
            return;
        }

        if (!this.isFormValid()) {
            this.showMessage('⚠️ Veuillez corriger les erreurs du formulaire', 'error');
            return;
        }

        const formData = this.getFormData();

        this.state.isCalculating = true;
        this.showLoading();
        this.disableForm();

        try {
            console.log('🧮 Lancement calcul avec:', formData);
            const results = await this.callAPI(formData);
            this.displayResults(results, formData);
            this.saveToHistory(formData, results);
            this.showMessage('✅ Calcul terminé avec succès', 'success');
        } catch (error) {
            console.error('❌ Erreur calcul:', error);
            this.showMessage('❌ Erreur lors du calcul. Veuillez réessayer.', 'error');
        } finally {
            this.state.isCalculating = false;
            this.enableForm();
        }
    },

    /**
     * Récupération des données formulaire
     */
    getFormData() {
        return {
            departement: this.dom.departement?.value.trim().padStart(2, '0') || '',
            poids: parseFloat(this.dom.poids?.value) || 0,
            type: this.dom.type?.value || 'colis',
            palettes: parseInt(this.dom.palettes?.value) || 1,
            palette_eur: parseInt(this.dom.paletteEur?.value) || 0,
            adr: this.dom.adr?.value || 'non',
            enlevement: this.dom.enlevement?.value || 'non',
            option_sup: this.dom.optionSup?.value || 'standard'
        };
    },

    /**
     * Appel API AJAX
     */
    async callAPI(formData) {
        const params = new URLSearchParams(formData);
        const response = await fetch(this.config.apiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        });
        const text = await response.text();
        console.log('[DEBUG] Réponse brute du serveur :', text);
        try {
            const result = JSON.parse(text);
            if (!result.success) throw new Error(result.error || 'Erreur inconnue');
            return result;
        } catch (err) {
            throw new Error('Réponse non-JSON reçue: ' + text.substring(0, 100));
        }
    },

    /**
     * Affichage résultats
     */
    displayResults(results, formData) {
        if (!this.dom.resultsContent) return;

        let html = '<div class="calc-results">';
        if (results.carriers) {
            Object.entries(results.carriers).forEach(([carrier, data]) => {
                html += `
                    <div class="calc-result-card">
                        <h4>${data.name || carrier.toUpperCase()}</h4>
                        <div class="calc-result-price">
                            <span class="calc-price-ht">${data.price ? data.formatted : '—'}</span>
                        </div>
                        <div class="calc-result-delay">${data.available ? 'Disponible' : 'Non disponible'}</div>
                    </div>
                `;
            });
        } else if (results.error) {
            html += `<div class="calc-result-error">${results.error}</div>`;
        }
        html += '</div>';
        this.dom.resultsContent.innerHTML = html;

        // Afficher section résultats si masquée
        const resultsSection = document.getElementById('resultsPanel');
        if (resultsSection) resultsSection.style.display = 'block';
    },

    showLoading() {
        if (this.dom.calcStatus) {
            this.dom.calcStatus.innerHTML = '🧮 Calcul en cours...';
            this.dom.calcStatus.style.display = 'block';
        }
    },

    disableForm() {
        const fields = ['departement', 'poids', 'type', 'palettes', 'paletteEur'];
        fields.forEach(field => { if (this.dom[field]) this.dom[field].disabled = true; });
        if (this.dom.calculateBtn) this.dom.calculateBtn.disabled = true;
    },

    enableForm() {
        const fields = ['departement', 'poids', 'type', 'palettes', 'paletteEur'];
        fields.forEach(field => { if (this.dom[field]) this.dom[field].disabled = false; });
        if (this.dom.calculateBtn) this.dom.calculateBtn.disabled = false;
        if (this.dom.calcStatus) this.dom.calcStatus.style.display = 'none';
    },

    saveToHistory(params, results) {
        const entry = {
            timestamp: Date.now(),
            params: { ...params },
            results: { ...results },
            id: 'calc_' + Date.now()
        };
        this.state.history.unshift(entry);
        this.state.history = this.state.history.slice(0, 10);
        try { localStorage.setItem('calc_history', JSON.stringify(this.state.history)); }
        catch (e) { console.warn('Erreur sauvegarde historique:', e); }
    },

    loadHistory() {
        try {
            const saved = localStorage.getItem('calc_history');
            this.state.history = saved ? JSON.parse(saved) : [];
        } catch (e) {
            console.warn('Erreur chargement historique:', e);
            this.state.history = [];
        }
    },

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
    },

    /**
     * Validation instantanée à la saisie
     */
    setupValidation() {
        const requiredFields = ['departement', 'poids'];
        requiredFields.forEach(fieldName => {
            const field = this.dom[fieldName];
            if (field) {
                field.addEventListener('blur', () => {
                    this[`validate${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`]();
                });
                field.addEventListener('focus', () => {
                    field.classList.remove('error');
                    const errorElement = document.getElementById(fieldName + 'Error');
                    if (errorElement) errorElement.textContent = '';
                });
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    CalculateurModule.init();
});
window.CalculateurModule = CalculateurModule;

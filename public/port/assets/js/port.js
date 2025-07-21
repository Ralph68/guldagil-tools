/**
 * Titre: Module JavaScript calculateur de frais de port - VERSION CORRIGÉE
 * Chemin: /public/port/assets/js/port.js
 * Version: 0.5 beta + build auto
 * Description: Gestion complète du formulaire avec corrections des erreurs
 */

// ===============================================
// 🔧 MODULE PRINCIPAL PORTMODULE
// ===============================================

const PortModule = {
    // Configuration
    config: {
        apiUrl: window.location.pathname + '?ajax=calculate',
        debounceDelay: 300,
        maxRetries: 3,
        autoProgressDelay: 800,
        validationDelay: 500
    },

    // État du module
    state: {
        currentStep: 1,
        totalSteps: 3,
        isCalculating: false,
        currentData: null,
        history: [],
        validationErrors: {},
        formData: {}
    },

    // Cache DOM
    dom: {},

    /**
     * Initialisation du module
     */
    init() {
        console.log('🚛 Initialisation module Port...');
        
        // Appliquer les correctifs immédiatement
        this.applyFormFixes();
        
        // Cache des éléments DOM
        this.cacheDOMElements();
        
        // Configuration des événements
        this.setupEventListeners();
        
        // Configuration validation
        this.setupValidation();
        
        // Chargement historique
        this.loadHistory();
        
        // Affichage de la première étape
        this.showStep(1);
        
        console.log('✅ Module Port initialisé avec succès');
    },

    /**
     * CORRECTION: Appliquer les correctifs pour éviter les erreurs
     */
    applyFormFixes() {
        // Correction 1: Problème "invalid form control"
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('calculatorForm');
            if (form) {
                // Désactiver la validation HTML5 native qui pose problème
                form.setAttribute('novalidate', 'novalidate');
                console.log('✅ Validation HTML5 native désactivée');
            }
            
            // Correction spécifique pour le champ poids
            const poidsField = document.getElementById('poids');
            if (poidsField) {
                // Retirer required temporairement
                poidsField.removeAttribute('required');
                
                // Validation JavaScript personnalisée
                poidsField.addEventListener('input', (e) => {
                    this.handlePoidsValidation(e.target);
                });
                
                console.log('✅ Validation poids corrigée');
            }
        });
    },

    /**
     * Validation personnalisée du champ poids
     */
    handlePoidsValidation(field) {
        const value = parseFloat(field.value);
        const isValid = !isNaN(value) && value >= 1 && value <= 3000 && Number.isInteger(value);
        
        // Nettoyer les erreurs précédentes
        this.clearFieldError(field);
        
        if (field.value && !isValid) {
            this.showFieldError(field, 'Poids requis: nombre entier entre 1 et 3000 kg');
            field.classList.add('error');
        } else if (isValid) {
            field.classList.remove('error');
            field.classList.add('valid');
            // Re-ajouter required si valide
            field.setAttribute('required', 'required');
        }
        
        this.state.validationErrors.poids = !isValid;
        return isValid;
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
            palettesGroup: document.getElementById('palettesGroup'),
            optionSup: document.getElementById('option_sup'),
            
            // Boutons navigation
            prevBtn: document.getElementById('prevBtn'),
            nextBtn: document.getElementById('nextBtn'),
            calculateBtn: document.getElementById('calculateBtn'),
            
            // Zones d'affichage
            resultsContent: document.getElementById('resultsContent'),
            loadingState: document.getElementById('loadingState'),
            
            // Étapes
            stepContents: document.querySelectorAll('.calc-step-content'),
            
            // Toggles
            adrToggles: document.querySelectorAll('[data-adr]'),
            enlevementToggles: document.querySelectorAll('[data-enlevement]')
        };
        
        console.log('📋 Éléments DOM mis en cache');
    },

    /**
     * Configuration des événements
     */
    setupEventListeners() {
        // Soumission formulaire
        if (this.dom.form) {
            this.dom.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCalculate();
            });
        }

        // Navigation étapes
        if (this.dom.nextBtn) {
            this.dom.nextBtn.addEventListener('click', () => this.nextStep());
        }
        
        if (this.dom.prevBtn) {
            this.dom.prevBtn.addEventListener('click', () => this.prevStep());
        }

        // Validation temps réel avec debounce
        if (this.dom.departement) {
            this.dom.departement.addEventListener('input', 
                this.debounce(() => this.validateDepartement(), this.config.debounceDelay)
            );
        }

        if (this.dom.poids) {
            this.dom.poids.addEventListener('input', 
                this.debounce(() => this.validatePoids(), this.config.debounceDelay)
            );
        }

        // Gestion du type (colis/palette)
        if (this.dom.type) {
            this.dom.type.addEventListener('change', () => this.handleTypeChange());
        }

        // Gestion des toggles ADR
        this.dom.adrToggles.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleToggle(e, 'adr'));
        });

        // Gestion des toggles enlèvement
        this.dom.enlevementToggles.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleToggle(e, 'enlevement'));
        });

        // Auto-progression et auto-calcul
        ['departement', 'poids'].forEach(fieldName => {
            const field = this.dom[fieldName];
            if (field) {
                field.addEventListener('input', 
                    this.debounce(() => this.autoProgressIfValid(), this.config.autoProgressDelay)
                );
            }
        });
        
        console.log('🎯 Événements configurés');
    },

    /**
     * Gestion des toggles
     */
    handleToggle(event, type) {
        event.preventDefault();
        
        const clickedBtn = event.target;
        const group = clickedBtn.closest('.calc-toggle-group');
        
        // Retirer active de tous les boutons du groupe
        group.querySelectorAll('.calc-toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Ajouter active au bouton cliqué
        clickedBtn.classList.add('active');
        
        // Sauvegarder la valeur
        this.state.formData[type] = clickedBtn.dataset[type];
        
        console.log(`🔄 Toggle ${type}: ${clickedBtn.dataset[type]}`);
    },

    /**
     * Gestion changement de type (colis/palette)
     */
    handleTypeChange() {
        const isPalette = this.dom.type.value === 'palette';
        
        if (this.dom.palettesGroup) {
            this.dom.palettesGroup.style.display = isPalette ? 'block' : 'none';
            
            if (!isPalette && this.dom.palettes) {
                this.dom.palettes.value = '1';
            }
        }
        
        console.log(`📦 Type changé: ${this.dom.type.value}`);
    },

    /**
     * Navigation vers l'étape suivante
     */
    nextStep() {
        if (this.canProgressToStep(this.state.currentStep + 1)) {
            this.showStep(this.state.currentStep + 1);
        }
    },

    /**
     * Navigation vers l'étape précédente
     */
    prevStep() {
        if (this.state.currentStep > 1) {
            this.showStep(this.state.currentStep - 1);
        }
    },

    /**
     * Affichage d'une étape spécifique
     */
    showStep(step) {
        // Masquer toutes les étapes
        this.dom.stepContents.forEach(content => {
            content.style.display = 'none';
        });
        
        // Afficher l'étape demandée
        const stepContent = document.querySelector(`[data-step="${step}"]`);
        if (stepContent) {
            stepContent.style.display = 'block';
        }
        
        // Mettre à jour les boutons
        this.updateNavigationButtons(step);
        
        // Mettre à jour l'état
        this.state.currentStep = step;
        
        console.log(`📍 Étape ${step} affichée`);
    },

    /**
     * Mise à jour des boutons de navigation
     */
    updateNavigationButtons(step) {
        if (this.dom.prevBtn) {
            this.dom.prevBtn.style.display = step > 1 ? 'inline-block' : 'none';
        }
        
        if (this.dom.nextBtn) {
            this.dom.nextBtn.style.display = step < this.state.totalSteps ? 'inline-block' : 'none';
        }
        
        if (this.dom.calculateBtn) {
            this.dom.calculateBtn.style.display = step === this.state.totalSteps ? 'inline-block' : 'none';
        }
    },

    /**
     * Vérifier si on peut progresser vers une étape
     */
    canProgressToStep(step) {
        switch (step) {
            case 2:
                return this.validateDepartement();
            case 3:
                return this.validateDepartement() && this.validatePoids();
            default:
                return true;
        }
    },

    /**
     * Auto-progression si formulaire valide
     */
    autoProgressIfValid() {
        const deptValid = this.validateDepartement();
        const poidsValid = this.validatePoids();
        
        console.log(`🔄 Auto-progression: dept=${deptValid}, poids=${poidsValid}, step=${this.state.currentStep}`);
        
        // Progression automatique des étapes
        if (deptValid && this.state.currentStep === 1) {
            console.log('🚀 Progression automatique étape 1 → 2');
            setTimeout(() => this.showStep(2), 500);
        } else if (deptValid && poidsValid && this.state.currentStep === 2) {
            console.log('🚀 Progression automatique étape 2 → 3');
            setTimeout(() => this.showStep(3), 500);
        } else if (deptValid && poidsValid && this.state.currentStep >= 3 && !this.state.isCalculating) {
            console.log('🚀 Lancement calcul automatique');
            setTimeout(() => this.handleCalculate(), 800);
        }
    },

    /**
     * Validation département
     */
    validateDepartement() {
        if (!this.dom.departement) return false;
        
        const value = this.dom.departement.value.trim();
        const isValid = /^(0[1-9]|[1-8][0-9]|9[0-5]|97[1-8]|98[0-8])$/.test(value);
        
        console.log(`✓ Validation département: ${value} → ${isValid}`);
        
        this.updateFieldValidation('departement', isValid, 
            isValid ? '' : 'Format invalide (ex: 75, 69, 13)');
        
        return isValid;
    },

    /**
     * Validation poids avec correction
     */
    validatePoids() {
        if (!this.dom.poids) return false;
        
        const value = parseFloat(this.dom.poids.value);
        const isValid = !isNaN(value) && value >= 1 && value <= 3000 && Number.isInteger(value);
        
        console.log(`✓ Validation poids: ${value}, isValid: ${isValid}, currentStep: ${this.state.currentStep}`);
        
        this.updateFieldValidation('poids', isValid, 
            isValid ? '' : 'Poids entre 1 et 3000 kg (entier)');
        
        return isValid;
    },

    /**
     * Mise à jour validation champ
     */
    updateFieldValidation(fieldName, isValid, errorMessage) {
        const field = this.dom[fieldName];
        if (!field) return;
        
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
     * Configuration validation globale
     */
    setupValidation() {
        const requiredFields = ['departement', 'poids'];
        
        requiredFields.forEach(fieldName => {
            const field = this.dom[fieldName];
            if (field) {
                field.addEventListener('blur', () => {
                    this[`validate${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`]();
                });
            }
        });
        
        console.log('✅ Validation configurée');
    },

    /**
     * Gestion du calcul des tarifs
     */
    async handleCalculate() {
        if (this.state.isCalculating) {
            console.log('⏳ Calcul déjà en cours...');
            return;
        }
        
        console.log('🧮 Démarrage calcul des tarifs...');
        
        const formData = this.collectFormData();
        
        // Validation finale
        if (!this.validateFormData(formData)) {
            console.log('❌ Validation formulaire échouée');
            return;
        }

        this.state.isCalculating = true;
        this.showLoading();
        this.disableForm();

        try {
            const results = await this.callAPI(formData);
            this.displayResults(results, formData);
            this.saveToHistory(formData, results);
            console.log('✅ Calcul terminé avec succès');
        } catch (error) {
            console.error('❌ Erreur calcul:', error);
            this.showError('Erreur lors du calcul. Veuillez réessayer.');
        } finally {
            this.state.isCalculating = false;
            this.enableForm();
            this.hideLoading();
        }
    },

    /**
     * Collecte des données du formulaire
     */
    collectFormData() {
        const adrActive = document.querySelector('[data-adr].active');
        const enlevementActive = document.querySelector('[data-enlevement].active');
        
        return {
            departement: this.dom.departement?.value?.trim() || '',
            poids: parseFloat(this.dom.poids?.value) || 0,
            type: this.dom.type?.value || 'colis',
            palettes: parseInt(this.dom.palettes?.value) || 1,
            adr: adrActive?.dataset?.adr || 'non',
            enlevement: enlevementActive?.dataset?.enlevement || 'non',
            option_sup: this.dom.optionSup?.value || 'standard'
        };
    },

    /**
     * Validation des données du formulaire
     */
    validateFormData(data) {
        const errors = [];
        
        if (!data.departement || !/^[0-9]{2,3}$/.test(data.departement)) {
            errors.push('Département invalide');
        }
        
        if (!data.poids || data.poids < 1 || data.poids > 3000) {
            errors.push('Poids invalide (1-3000 kg)');
        }
        
        if (errors.length > 0) {
            this.showError('Erreurs: ' + errors.join(', '));
            return false;
        }
        
        return true;
    },

    /**
     * Appel API pour le calcul
     */
    async callAPI(formData) {
        const response = await fetch(this.config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(formData)
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erreur inconnue');
        }

        return data;
    },

    /**
     * Affichage des résultats
     */
    displayResults(data, formData) {
        if (!data.carriers || data.carriers.length === 0) {
            this.showEmptyResults();
            return;
        }

        const resultsHTML = this.buildResultsHTML(data.carriers);
        this.dom.resultsContent.innerHTML = resultsHTML;
        
        // Animation d'entrée
        this.dom.resultsContent.style.opacity = '0';
        setTimeout(() => {
            this.dom.resultsContent.style.opacity = '1';
        }, 100);
        
        console.log(`📊 ${data.carriers.length} résultats affichés`);
    },

    /**
     * Construction HTML des résultats
     */
    buildResultsHTML(carriers) {
        let html = '<div class="calc-results-grid">';
        
        carriers.forEach((carrier, index) => {
            const isBest = index === 0;
            html += `
                <div class="calc-carrier-card ${isBest ? 'calc-carrier-best' : ''}">
                    ${isBest ? '<div class="calc-best-badge">🏆 Meilleur tarif</div>' : ''}
                    <div class="calc-carrier-header">
                        <h3 class="calc-carrier-name">${this.escapeHtml(carrier.name || 'Transporteur')}</h3>
                        <div class="calc-carrier-price">${carrier.total || 'N/C'}€ HT</div>
                    </div>
                    <div class="calc-carrier-details">
                        ${carrier.details ? carrier.details.map(d => `<div>• ${this.escapeHtml(d)}</div>`).join('') : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    },

    /**
     * Affichage état vide
     */
    showEmptyResults() {
        this.dom.resultsContent.innerHTML = `
            <div class="calc-empty-state">
                <div class="calc-empty-icon">❌</div>
                <p class="calc-empty-text">Aucun tarif disponible pour ces critères</p>
                <div class="calc-status">Essayez d'autres paramètres</div>
            </div>
        `;
    },

    /**
     * Affichage des erreurs
     */
    showError(message) {
        this.dom.resultsContent.innerHTML = `
            <div class="calc-empty-state">
                <div class="calc-empty-icon">❌</div>
                <p class="calc-empty-text">Erreur: ${this.escapeHtml(message)}</p>
                <div class="calc-status">Veuillez réessayer</div>
            </div>
        `;
    },

    /**
     * Affichage loading
     */
    showLoading() {
        if (this.dom.loadingState) {
            this.dom.loadingState.style.display = 'flex';
        }
        if (this.dom.resultsContent) {
            this.dom.resultsContent.style.display = 'none';
        }
    },

    /**
     * Masquer loading
     */
    hideLoading() {
        if (this.dom.loadingState) {
            this.dom.loadingState.style.display = 'none';
        }
        if (this.dom.resultsContent) {
            this.dom.resultsContent.style.display = 'block';
        }
    },

    /**
     * Désactiver formulaire
     */
    /**
     * Désactiver formulaire
     */
    disableForm() {
        const inputs = this.dom.form?.querySelectorAll('input, select, button');
        inputs?.forEach(input => {
            input.disabled = true;
        });
    },

    /**
     * Réactiver formulaire
     */
    enableForm() {
        const inputs = this.dom.form?.querySelectorAll('input, select, button');
        inputs?.forEach(input => {
            input.disabled = false;
        });
    },

    /**
     * Sauvegarde dans l'historique
     */
    saveToHistory(formData, results) {
        const historyEntry = {
            timestamp: new Date().toISOString(),
            formData: { ...formData },
            results: results,
            bestPrice: results.carriers?.[0]?.total || null
        };
        
        this.state.history.unshift(historyEntry);
        
        // Limiter à 10 entrées
        if (this.state.history.length > 10) {
            this.state.history = this.state.history.slice(0, 10);
        }
        
        // Sauvegarder en localStorage si disponible
        try {
            localStorage.setItem('port_calc_history', JSON.stringify(this.state.history));
        } catch (e) {
            console.warn('⚠️ Impossible de sauvegarder l\'historique:', e);
        }
    },

    /**
     * Chargement de l'historique
     */
    loadHistory() {
        try {
            const saved = localStorage.getItem('port_calc_history');
            this.state.history = saved ? JSON.parse(saved) : [];
            console.log(`📚 Historique chargé: ${this.state.history.length} entrées`);
        } catch (e) {
            console.warn('⚠️ Erreur chargement historique:', e);
            this.state.history = [];
        }
    },

    /**
     * Affichage erreur de champ
     */
    showFieldError(field, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'calc-error-message';
        errorDiv.textContent = message;
        
        // Retirer erreur existante
        this.clearFieldError(field);
        
        // Ajouter nouvelle erreur
        field.parentNode.appendChild(errorDiv);
    },

    /**
     * Nettoyage erreur de champ
     */
    clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.calc-error-message');
        if (existingError) {
            existingError.remove();
        }
    },

    /**
     * Fonction debounce pour limiter les appels
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
    },

    /**
     * Échappement HTML pour sécurité
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, (m) => map[m]);
    },

    /**
     * Réinitialisation du formulaire
     */
    resetForm() {
        if (this.dom.form) {
            this.dom.form.reset();
        }
        
        // Retour à l'étape 1
        this.showStep(1);
        
        // Nettoyage des erreurs
        this.state.validationErrors = {};
        
        // Nettoyage des classes CSS
        document.querySelectorAll('.calc-form-input').forEach(input => {
            input.classList.remove('error', 'valid');
        });
        
        // Affichage état initial
        this.showInitialState();
        
        console.log('🔄 Formulaire réinitialisé');
    },

    /**
     * Affichage état initial
     */
    showInitialState() {
        this.dom.resultsContent.innerHTML = `
            <div class="calc-empty-state">
                <div class="calc-empty-icon">⏳</div>
                <p class="calc-empty-text">Remplissez le formulaire pour voir les tarifs</p>
                <div class="calc-status">Prêt pour calcul</div>
            </div>
        `;
    }
};

// ===============================================
// 🛡️ CORRECTIFS COOKIES (Anti-boucle infinie)
// ===============================================

const CookieConfigFix = {
    applied: false,
    
    /**
     * Arrêter la boucle infinie des cookies
     */
    stopCookieLoop() {
        if (this.applied) return;
        
        console.log('🛑 Application correctif boucle cookies...');
        
        // Désactiver les rechargements automatiques
        if (typeof initAdvancedCookieConfig !== 'undefined') {
            // Redéfinir la fonction problématique
            window.initAdvancedCookieConfig = function() {
                console.log('🍪 Cookie config désactivé pour éviter boucle');
                return false;
            };
        }
        
        // Créer gestionnaire minimal si nécessaire
        if (typeof window.cookieBanner === 'undefined') {
            this.createMinimalBanner();
        }
        
        this.applied = true;
        console.log('✅ Correctif cookies appliqué');
    },
    
    /**
     * Créer un gestionnaire minimal fonctionnel
     */
    createMinimalBanner() {
        window.cookieBanner = {
            acceptAll: function() {
                document.cookie = 'guldagil_cookie_consent=accepted; path=/; max-age=31536000';
                this.hideBanners();
                console.log('✅ Cookies acceptés (version simplifiée)');
            },
            
            acceptMinimal: function() {
                document.cookie = 'guldagil_cookie_consent=minimal; path=/; max-age=31536000';
                this.hideBanners();
                console.log('⚙️ Cookies techniques (version simplifiée)');
            },
            
            hideBanners: function() {
                document.querySelectorAll('#cookie-banner, .cookie-banner').forEach(el => {
                    el.style.display = 'none';
                });
            },
            
            showDetails: function() {
                console.log('ℹ️ Détails cookies (version simplifiée)');
            }
        };
        
        console.log('🍪 Gestionnaire cookies minimal créé');
    }
};

// ===============================================
// 🚀 INITIALISATION AUTOMATIQUE
// ===============================================

/**
 * Fonction d'initialisation principale
 */
function initPortModule() {
    console.log('🚀 Démarrage initialisation module Port...');
    
    // 1. Appliquer les correctifs en premier
    CookieConfigFix.stopCookieLoop();
    
    // 2. Attendre que le DOM soit prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => PortModule.init(), 100);
        });
    } else {
        setTimeout(() => PortModule.init(), 100);
    }
    
    // 3. Exposer le module globalement
    window.PortModule = PortModule;
    window.CookieConfigFix = CookieConfigFix;
}

// ===============================================
// 🎯 GESTION DES ERREURS GLOBALES
// ===============================================

/**
 * Gestionnaire d'erreurs global pour le module
 */
window.addEventListener('error', function(event) {
    // Filtrer les erreurs liées aux cookies pour éviter le spam
    if (event.message && (
        event.message.includes('cookie') || 
        event.message.includes('initAdvancedCookieConfig')
    )) {
        console.warn('⚠️ Erreur cookies interceptée et ignorée:', event.message);
        event.preventDefault();
        return;
    }
    
    // Logger les autres erreurs importantes
    if (event.message && event.message.includes('Port')) {
        console.error('❌ Erreur module Port:', event.message);
    }
});

/**
 * Gestionnaire pour les promesses rejetées
 */
window.addEventListener('unhandledrejection', function(event) {
    if (event.reason && event.reason.toString().includes('cookie')) {
        console.warn('⚠️ Promesse cookie rejetée ignorée:', event.reason);
        event.preventDefault();
        return;
    }
    
    console.error('❌ Promesse rejetée:', event.reason);
});

// ===============================================
// 🔧 UTILITAIRES DE DEBUG
// ===============================================

/**
 * Utilitaires de debug pour le développement
 */
window.PortDebug = {
    /**
     * Afficher l'état actuel du module
     */
    getState() {
        return {
            module: PortModule.state,
            dom: Object.keys(PortModule.dom),
            cookieFixed: CookieConfigFix.applied,
            timestamp: new Date().toISOString()
        };
    },
    
    /**
     * Forcer un calcul de test
     */
    testCalculation() {
        PortModule.state.formData = {
            departement: '75',
            poids: 25,
            type: 'colis',
            adr: 'non',
            enlevement: 'non'
        };
        PortModule.handleCalculate();
    },
    
    /**
     * Réinitialiser complètement le module
     */
    reset() {
        PortModule.resetForm();
        PortModule.state.history = [];
        localStorage.removeItem('port_calc_history');
        console.log('🔄 Module Port complètement réinitialisé');
    }
};

// ===============================================
// 🎬 LANCEMENT DE L'INITIALISATION
// ===============================================

// Démarrer immédiatement l'initialisation
initPortModule();

// Re-essayer après un délai si nécessaire
setTimeout(() => {
    if (!window.PortModule || !PortModule.dom.form) {
        console.warn('⚠️ Re-tentative initialisation module Port...');
        initPortModule();
    }
}, 1000);

console.log('📦 Module Port chargé - Version corrigée anti-erreurs');

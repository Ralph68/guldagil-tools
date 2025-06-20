/**
 * Titre: Module Calculateur - Fichier principal (Interface pas-à-pas)
 * Chemin: /public/assets/js/modules/calculateur/calculateur.js
 * Version: 0.5 beta + build
 * 
 * Orchestrateur principal du module calculateur - Interface pas-à-pas
 * Calcul dynamique automatique sans bouton calculer
 */

// ========================================
// NAMESPACE ET CONFIGURATION
// ========================================

window.Calculateur = window.Calculateur || {};

const CONFIG = {
    AUTO_CALC_DELAY: 800, // Plus long pour interface pas-à-pas
    MAX_POIDS: 3500,
    MIN_POIDS: 0.1,
    PALETTE_THRESHOLD: 60,
    API_ENDPOINT: 'ajax-calculate.php',
    VERSION: '0.5 beta',
    DEBUG: window.location.search.includes('debug=1')
};

// ========================================
// ÉTAT GLOBAL DU CALCULATEUR
// ========================================

const CalculateurState = {
    isCalculating: false,
    currentResults: null,
    formData: {},
    calculationTimeout: null,
    lastValidCalculation: null,
    
    // Méthodes d'état
    setCalculating(state) {
        this.isCalculating = state;
        if (Calculateur.UI) {
            Calculateur.UI.updateCalculatingState(state);
        }
    },
    
    setResults(results) {
        this.currentResults = results;
        this.lastValidCalculation = Date.now();
    },
    
    updateFormData() {
        if (Calculateur.Form) {
            this.formData = Calculateur.Form.getFormData();
        }
    },
    
    isFormValid() {
        if (!Calculateur.Form) return false;
        return Calculateur.Form.validateForm().isValid;
    },
    
    reset() {
        this.isCalculating = false;
        this.currentResults = null;
        this.formData = {};
        this.lastValidCalculation = null;
        if (this.calculationTimeout) {
            clearTimeout(this.calculationTimeout);
            this.calculationTimeout = null;
        }
    }
};

// ========================================
// ÉLÉMENTS DOM CACHÉS GLOBALEMENT
// ========================================

const Elements = {
    // Formulaire pas-à-pas
    form: null,
    departement: null,
    poids: null,
    typeRadios: null,
    adrRadios: null,
    serviceLivraison: null,
    enlevement: null,
    palettes: null,
    stepPalettes: null,
    
    // Actions
    btnReset: null,
    btnNouveauCalcul: null,
    
    // Résultats (toujours visibles)
    resultsSection: null,
    resultsStatus: null,
    resultsContent: null,
    
    // Feedback des champs
    deptFeedback: null,
    poidsFeedback: null,
    
    // Cache des éléments
    init() {
        this.form = document.getElementById('calculator-form');
        this.departement = document.getElementById('departement');
        this.poids = document.getElementById('poids');
        this.typeRadios = document.querySelectorAll('input[name="type"]');
        this.adrRadios = document.querySelectorAll('input[name="adr"]');
        this.serviceLivraison = document.getElementById('service_livraison');
        this.enlevement = document.getElementById('enlevement');
        this.palettes = document.getElementById('palettes');
        this.stepPalettes = document.getElementById('step-palettes');
        
        this.btnReset = document.getElementById('btn-reset');
        this.btnNouveauCalcul = document.getElementById('btn-nouveau-calcul');
        
        this.resultsSection = document.querySelector('.results-section');
        this.resultsStatus = document.getElementById('results-status');
        this.resultsContent = document.getElementById('results-content');
        
        this.deptFeedback = document.getElementById('dept-feedback');
        this.poidsFeedback = document.getElementById('poids-feedback');
        
        return this.form !== null;
    }
};

// ========================================
// MODULE PRINCIPAL CALCULATEUR
// ========================================

Calculateur.Core = {
    /**
     * Initialisation du module calculateur
     */
    init() {
        if (CONFIG.DEBUG) {
            console.log('🚚 Initialisation Calculateur pas-à-pas v' + CONFIG.VERSION);
        }
        
        // Cache des éléments DOM
        if (!Elements.init()) {
            console.error('❌ Éléments DOM calculateur non trouvés');
            return false;
        }
        
        // Initialisation des sous-modules
        this.initSubModules();
        
        // Configuration initiale
        this.setupInitialState();
        
        // Événements globaux
        this.bindGlobalEvents();
        
        // Démarrage de l'interface pas-à-pas
        this.startStepwiseInterface();
        
        if (CONFIG.DEBUG) {
            console.log('✅ Module Calculateur pas-à-pas initialisé');
        }
        
        return true;
    },
    
    /**
     * Initialisation des sous-modules
     */
    initSubModules() {
        if (Calculateur.Utils) Calculateur.Utils.init();
        if (Calculateur.UI) Calculateur.UI.init();
        if (Calculateur.Form) Calculateur.Form.init();
        if (Calculateur.Calculs) Calculateur.Calculs.init();
        if (Calculateur.Resultats) Calculateur.Resultats.init();
    },
    
    /**
     * Configuration initiale
     */
    setupInitialState() {
        CalculateurState.reset();
        CalculateurState.updateFormData();
        
        // Focus automatique sur le premier champ
        setTimeout(() => {
            if (Elements.departement) {
                Elements.departement.focus();
            }
        }, 200);
    },
    
    /**
     * Événements globaux
     */
    bindGlobalEvents() {
        // Empêcher la soumission classique
        if (Elements.form) {
            Elements.form.addEventListener('submit', (e) => {
                e.preventDefault();
                return false;
            });
        }
        
        // Bouton reset
        if (Elements.btnReset) {
            Elements.btnReset.addEventListener('click', (e) => {
                e.preventDefault();
                this.resetCalculator();
            });
        }
        
        // Bouton nouveau calcul (header)
        if (Elements.btnNouveauCalcul) {
            Elements.btnNouveauCalcul.addEventListener('click', (e) => {
                e.preventDefault();
                this.resetCalculator();
            });
        }
    },
    
    /**
     * Démarrage interface pas-à-pas
     */
    startStepwiseInterface() {
        // Configuration des événements pour calcul dynamique
        this.setupDynamicCalculation();
        
        // Gestion affichage étapes conditionnelles
        this.setupConditionalSteps();
        
        // État initial des résultats
        this.updateResultsDisplay();
    },
    
    /**
     * Configuration calcul dynamique
     */
    setupDynamicCalculation() {
        const triggerElements = [
            Elements.departement,
            Elements.poids,
            Elements.serviceLivraison
        ].filter(Boolean);
        
        // Événements sur champs texte/select
        triggerElements.forEach(element => {
            element.addEventListener('input', () => {
                this.triggerDynamicCalculation();
            });
            element.addEventListener('change', () => {
                this.triggerDynamicCalculation();
            });
        });
        
        // Événements sur radios
        [...Elements.typeRadios, ...Elements.adrRadios].forEach(radio => {
            radio.addEventListener('change', () => {
                this.handleRadioChange(radio);
                this.triggerDynamicCalculation();
            });
        });
        
        // Événement sur checkbox
        if (Elements.enlevement) {
            Elements.enlevement.addEventListener('change', () => {
                this.handleEnlevementChange();
                this.triggerDynamicCalculation();
            });
        }
        
        // Événement sur palettes
        if (Elements.palettes) {
            Elements.palettes.addEventListener('input', () => {
                this.triggerDynamicCalculation();
            });
        }
    },
    
    /**
     * Gestion changement radio
     */
    handleRadioChange(radio) {
        if (radio.name === 'type') {
            this.handleTypeChange(radio.value);
        } else if (radio.name === 'adr') {
            this.handleAdrChange(radio.value);
        }
    },
    
    /**
     * Gestion changement type
     */
    handleTypeChange(type) {
        if (type === 'palette') {
            this.showPaletteStep();
        } else {
            this.hidePaletteStep();
        }
    },
    
    /**
     * Gestion changement ADR
     */
    handleAdrChange(adr) {
        if (adr === 'oui') {
            this.disableIncompatibleOptions();
        } else {
            this.enableAllOptions();
        }
    },
    
    /**
     * Gestion changement enlèvement
     */
    handleEnlevementChange() {
        const isEnlevement = Elements.enlevement.checked;
        
        if (isEnlevement) {
            // Enlèvement désactive les options de livraison
            if (Elements.serviceLivraison) {
                Elements.serviceLivraison.value = 'standard';
                Elements.serviceLivraison.disabled = true;
            }
        } else {
            // Réactiver les options de livraison
            if (Elements.serviceLivraison) {
                Elements.serviceLivraison.disabled = false;
            }
        }
    },
    
    /**
     * Afficher étape palettes
     */
    showPaletteStep() {
        if (Elements.stepPalettes) {
            Elements.stepPalettes.style.display = 'block';
            Elements.stepPalettes.classList.add('fade-in');
        }
    },
    
    /**
     * Masquer étape palettes
     */
    hidePaletteStep() {
        if (Elements.stepPalettes) {
            Elements.stepPalettes.style.display = 'none';
            if (Elements.palettes) {
                Elements.palettes.value = '0';
            }
        }
    },
    
    /**
     * Configuration étapes conditionnelles
     */
    setupConditionalSteps() {
        // Étape palettes masquée par défaut
        this.hidePaletteStep();
    },
    
    /**
     * Désactiver options incompatibles avec ADR
     */
    disableIncompatibleOptions() {
        if (!Elements.serviceLivraison) return;
        
        const options = Elements.serviceLivraison.querySelectorAll('option');
        options.forEach(option => {
            // Désactiver les options premium avec ADR
            if (option.value.includes('premium')) {
                option.disabled = true;
                option.textContent = option.textContent.replace(' (Non compatible ADR)', '') + ' (Non compatible ADR)';
            }
        });
        
        // Reset à standard si option incompatible sélectionnée
        if (Elements.serviceLivraison.value.includes('premium')) {
            Elements.serviceLivraison.value = 'standard';
        }
    },
    
    /**
     * Réactiver toutes les options
     */
    enableAllOptions() {
        if (!Elements.serviceLivraison) return;
        
        const options = Elements.serviceLivraison.querySelectorAll('option');
        options.forEach(option => {
            option.disabled = false;
            option.textContent = option.textContent.replace(' (Non compatible ADR)', '');
        });
    },
    
    /**
     * Déclencher calcul dynamique
     */
    triggerDynamicCalculation() {
        // Annuler calcul précédent
        if (CalculateurState.calculationTimeout) {
            clearTimeout(CalculateurState.calculationTimeout);
        }
        
        // Mettre à jour les données du formulaire
        CalculateurState.updateFormData();
        
        // Validation temps réel
        this.performRealtimeValidation();
        
        // Programmer nouveau calcul si formulaire valide
        if (CalculateurState.isFormValid()) {
            CalculateurState.calculationTimeout = setTimeout(() => {
                this.performCalculation();
            }, CONFIG.AUTO_CALC_DELAY);
        } else {
            // Reset des résultats si formulaire invalide
            this.updateResultsDisplay();
        }
    },
    
    /**
     * Validation en temps réel
     */
    performRealtimeValidation() {
        if (!Calculateur.Form) return;
        
        // Validation département
        if (Elements.departement && Elements.deptFeedback) {
            const deptValid = Calculateur.Form.validateDepartement(Elements.departement.value);
            this.updateFieldFeedback(Elements.departement, Elements.deptFeedback, deptValid);
        }
        
        // Validation poids
        if (Elements.poids && Elements.poidsFeedback) {
            const poidsValid = Calculateur.Form.validatePoids(Elements.poids.value);
            this.updateFieldFeedback(Elements.poids, Elements.poidsFeedback, poidsValid);
        }
    },
    
    /**
     * Mise à jour feedback champ
     */
    updateFieldFeedback(field, feedback, validation) {
        // Mise à jour classe du champ
        field.classList.remove('valid', 'invalid');
        if (validation.isValid) {
            field.classList.add('valid');
        } else if (field.value.trim() !== '') {
            field.classList.add('invalid');
        }
        
        // Mise à jour message feedback
        feedback.className = 'field-feedback';
        if (validation.isValid && field.value.trim() !== '') {
            feedback.className += ' valid';
            feedback.textContent = validation.message || '✓ Valide';
        } else if (!validation.isValid && field.value.trim() !== '') {
            feedback.className += ' invalid';
            feedback.textContent = validation.message || '';
        } else {
            feedback.textContent = '';
        }
    },
    
    /**
     * Exécution du calcul principal
     */
    async performCalculation() {
        if (CalculateurState.isCalculating || !CalculateurState.isFormValid()) {
            return;
        }
        
        CalculateurState.setCalculating(true);
        
        try {
            const results = await Calculateur.Calculs.performCalculation(CalculateurState.formData);
            this.handleCalculationSuccess(results);
        } catch (error) {
            this.handleCalculationError(error);
        } finally {
            CalculateurState.setCalculating(false);
        }
    },
    
    /**
     * Gestion succès de calcul
     */
    handleCalculationSuccess(results) {
        CalculateurState.setResults(results);
        this.updateResultsDisplay(results);
        
        if (CONFIG.DEBUG) {
            console.log('✅ Calcul dynamique terminé', results);
        }
    },
    
    /**
     * Gestion erreur de calcul
     */
    handleCalculationError(error) {
        console.error('❌ Erreur calcul dynamique:', error);
        this.updateResultsDisplay(null, error.message);
    },
    
    /**
     * Mise à jour affichage résultats
     */
    updateResultsDisplay(results = null, errorMessage = null) {
        if (!Elements.resultsContent || !Elements.resultsStatus) return;
        
        if (CalculateurState.isCalculating) {
            // État de chargement
            Elements.resultsStatus.textContent = 'Calcul en cours...';
            Elements.resultsContent.innerHTML = `
                <div class="results-loading">
                    <div class="loading-spinner"></div>
                </div>
            `;
        } else if (errorMessage) {
            // État d'erreur
            Elements.resultsStatus.textContent = 'Erreur';
            Elements.resultsContent.innerHTML = `
                <div class="results-error">
                    <div class="error-icon">❌</div>
                    <h4>Erreur de calcul</h4>
                    <p>${errorMessage}</p>
                </div>
            `;
        } else if (results && results.success) {
            // Résultats valides
            if (Calculateur.Resultats) {
                Calculateur.Resultats.displayResults(results);
            }
        } else if (!CalculateurState.isFormValid()) {
            // Formulaire incomplet
            Elements.resultsStatus.textContent = 'En attente';
            Elements.resultsContent.innerHTML = `
                <div class="results-placeholder">
                    <div class="placeholder-icon">🚀</div>
                    <h4>Prêt à calculer</h4>
                    <p>Renseignez le formulaire pour voir les tarifs de nos transporteurs partenaires</p>
                </div>
            `;
        } else {
            ///**
 * Titre: Module Calculateur - Fichier principal
 * Chemin: /public/assets/js/modules/calculateur/calculateur.js
 * Version: 0.5 beta + build
 * 
 * Orchestrateur principal du module calculateur de frais de port
 * Architecture modulaire pour faciliter la maintenance
 */

// ========================================
// NAMESPACE ET CONFIGURATION
// ========================================

window.Calculateur = window.Calculateur || {};

const CONFIG = {
    AUTO_CALC_DELAY: 300,
    MAX_POIDS: 3500,
    MIN_POIDS: 0.1,
    PALETTE_THRESHOLD: 60,
    API_ENDPOINT: 'ajax-calculate.php',
    VERSION: '0.5 beta',
    DEBUG: window.location.search.includes('debug=1')
};

// ========================================
// ÉTAT GLOBAL DU CALCULATEUR
// ========================================

const CalculateurState = {
    isCalculating: false,
    currentResults: null,
    formData: {},
    calculationTimeout: null,
    
    // Méthodes d'état
    setCalculating(state) {
        this.isCalculating = state;
        Calculateur.UI.updateCalculatingState(state);
    },
    
    setResults(results) {
        this.currentResults = results;
    },
    
    updateFormData() {
        this.formData = Calculateur.Form.getFormData();
    },
    
    isFormValid() {
        return Calculateur.Form.validateForm().isValid;
    },
    
    reset() {
        this.isCalculating = false;
        this.currentResults = null;
        this.formData = {};
        if (this.calculationTimeout) {
            clearTimeout(this.calculationTimeout);
            this.calculationTimeout = null;
        }
    }
};

// ========================================
// ÉLÉMENTS DOM CACHÉS GLOBALEMENT
// ========================================

const Elements = {
    // Formulaire
    form: null,
    departement: null,
    poids: null,
    typeInputs: null,
    adrInputs: null,
    optionSup: null,
    enlevement: null,
    palettes: null,
    paletteOptions: null,
    
    // Actions
    btnCalculate: null,
    
    // Résultats
    loadingZone: null,
    resultMain: null,
    resultStatus: null,
    resultContent: null,
    alertsZone: null,
    comparisonZone: null,
    quickActions: null,
    
    // Cache des éléments
    init() {
        this.form = document.getElementById('calculator-form');
        this.departement = document.getElementById('departement');
        this.poids = document.getElementById('poids');
        this.typeInputs = document.querySelectorAll('input[name="type"]');
        this.adrInputs = document.querySelectorAll('input[name="adr"]');
        this.optionSup = document.getElementById('option_sup');
        this.enlevement = document.getElementById('enlevement');
        this.palettes = document.getElementById('palettes');
        this.paletteOptions = document.getElementById('palette-options');
        
        this.btnCalculate = document.getElementById('btn-calculate');
        
        this.loadingZone = document.getElementById('loading-zone');
        this.resultMain = document.getElementById('result-main');
        this.resultStatus = document.getElementById('result-status');
        this.resultContent = document.getElementById('result-content');
        this.alertsZone = document.getElementById('alerts-zone');
        this.comparisonZone = document.getElementById('comparison-zone');
        this.quickActions = document.getElementById('quick-actions');
        
        return this.form !== null; // Vérification éléments critiques
    }
};

// ========================================
// MODULE PRINCIPAL CALCULATEUR
// ========================================

Calculateur.Core = {
    /**
     * Initialisation du module calculateur
     */
    init() {
        if (CONFIG.DEBUG) {
            console.log('🚚 Initialisation Module Calculateur v' + CONFIG.VERSION);
        }
        
        // Cache des éléments DOM
        if (!Elements.init()) {
            console.error('❌ Éléments DOM calculateur non trouvés');
            return false;
        }
        
        // Initialisation des sous-modules
        this.initSubModules();
        
        // Configuration initiale
        this.setupInitialState();
        
        // Événements globaux
        this.bindGlobalEvents();
        
        if (CONFIG.DEBUG) {
            console.log('✅ Module Calculateur initialisé avec succès');
        }
        
        return true;
    },
    
    /**
     * Initialisation des sous-modules
     */
    initSubModules() {
        // Initialiser dans l'ordre de dépendance
        if (Calculateur.Utils) Calculateur.Utils.init();
        if (Calculateur.Form) Calculateur.Form.init();
        if (Calculateur.Calculs) Calculateur.Calculs.init();
        if (Calculateur.Resultats) Calculateur.Resultats.init();
        if (Calculateur.UI) Calculateur.UI.init();
    },
    
    /**
     * Configuration initiale
     */
    setupInitialState() {
        CalculateurState.reset();
        CalculateurState.updateFormData();
        
        // Focus automatique sur le premier champ
        setTimeout(() => {
            if (Elements.departement) {
                Elements.departement.focus();
            }
        }, 100);
    },
    
    /**
     * Événements globaux
     */
    bindGlobalEvents() {
        // Soumission du formulaire
        if (Elements.form) {
            Elements.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performCalculation();
            });
            
            Elements.form.addEventListener('reset', () => {
                this.resetCalculator();
            });
        }
        
        // Bouton de calcul
        if (Elements.btnCalculate) {
            Elements.btnCalculate.addEventListener('click', (e) => {
                e.preventDefault();
                this.performCalculation();
            });
        }
    },
    
    /**
     * Exécution du calcul principal
     */
    async performCalculation() {
        if (CalculateurState.isCalculating) {
            if (CONFIG.DEBUG) console.log('⏳ Calcul déjà en cours...');
            return;
        }
        
        // Validation
        const validation = Calculateur.Form.validateForm();
        if (!validation.isValid) {
            Calculateur.UI.showValidationErrors(validation.errors);
            return;
        }
        
        // Lancement du calcul
        CalculateurState.setCalculating(true);
        CalculateurState.updateFormData();
        
        try {
            const results = await Calculateur.Calculs.performCalculation(CalculateurState.formData);
            this.handleCalculationSuccess(results);
        } catch (error) {
            this.handleCalculationError(error);
        } finally {
            CalculateurState.setCalculating(false);
        }
    },
    
    /**
     * Gestion succès de calcul
     */
    handleCalculationSuccess(results) {
        CalculateurState.setResults(results);
        Calculateur.Resultats.displayResults(results);
        
        // Sauvegarde historique si disponible
        if (Calculateur.Form.saveToHistory) {
            Calculateur.Form.saveToHistory(CalculateurState.formData, results);
        }
        
        if (CONFIG.DEBUG) {
            console.log('✅ Calcul terminé avec succès', results);
        }
    },
    
    /**
     * Gestion erreur de calcul
     */
    handleCalculationError(error) {
        console.error('❌ Erreur calcul:', error);
        Calculateur.UI.showError('Erreur lors du calcul. Veuillez réessayer.');
    },
    
    /**
     * Reset complet du calculateur
     */
    resetCalculator() {
        CalculateurState.reset();
        
        if (Calculateur.Form.reset) Calculateur.Form.reset();
        if (Calculateur.Resultats.clear) Calculateur.Resultats.clear();
        if (Calculateur.UI.reset) Calculateur.UI.reset();
        
        setTimeout(() => {
            if (Elements.departement) Elements.departement.focus();
        }, 50);
    },
    
    /**
     * Calcul automatique avec délai
     */
    triggerAutoCalculation() {
        if (CalculateurState.calculationTimeout) {
            clearTimeout(CalculateurState.calculationTimeout);
        }
        
        if (CalculateurState.isFormValid()) {
            CalculateurState.calculationTimeout = setTimeout(() => {
                this.performCalculation();
            }, CONFIG.AUTO_CALC_DELAY);
        }
    }
};

// ========================================
// EXPOSER LES RÉFÉRENCES GLOBALES
// ========================================

// Pour les autres modules
Calculateur.State = CalculateurState;
Calculateur.Elements = Elements;
Calculateur.Config = CONFIG;

// Pour debug et tests
if (CONFIG.DEBUG) {
    window.CalculateurDebug = {
        state: CalculateurState,
        elements: Elements,
        config: CONFIG,
        core: Calculateur.Core
    };
}

// ========================================
// AUTO-INITIALISATION
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si on est sur la page du calculateur
    if (document.querySelector('#calculator-form')) {
        Calculateur.Core.init();
        
        // Exposer globalement pour compatibilité
        window.calculateur = Calculateur.Core;
    }
});

// ========================================
// EXPORT MODULE (pour tests unitaires)
// ========================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = Calculateur;
}

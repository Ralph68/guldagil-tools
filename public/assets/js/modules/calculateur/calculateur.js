/**
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

/**
 * Titre: Gestionnaire d'état centralisé
 * Chemin: /public/assets/js/modules/calculateur/core/state-manager.js
 * Version: 0.5 beta + build
 * 
 * State manager avec observables pour interface progressive
 */

class StateManager {
    constructor() {
        this.state = {
            // Données du formulaire
            formData: {
                departement: '',
                poids: '',
                type: '',
                adr: false,
                service_livraison: 'standard',
                enlevement: false,
                palettes: 0
            },
            
            // État de l'interface
            ui: {
                currentStep: 0,
                stepStates: CalculateurConfig.UI.PROGRESS_STEPS.map(() => 'pending'),
                isCalculating: false,
                showResults: false,
                showDetails: false
            },
            
            // Résultats
            results: {
                carriers: {},
                bestRate: null,
                suggestions: [],
                debug: null,
                timestamp: null
            },
            
            // Validation
            validation: {
                errors: {},
                isValid: false,
                touchedFields: new Set()
            }
        };
        
        this.observers = new Map();
        this.history = [];
        
        // Initialisation
        this.initializeState();
    }
    
    /**
     * Initialisation de l'état
     */
    initializeState() {
        // Marquer la première étape comme courante
        this.state.ui.stepStates[0] = 'current';
        
        // Charger des données depuis l'URL si présentes
        this.loadFromURL();
        
        // Debug
        CalculateurConfig.log('debug', 'État initial:', this.state);
    }
    
    /**
     * Chargement des données depuis l'URL
     */
    loadFromURL() {
        const params = new URLSearchParams(window.location.search);
        
        if (params.has('dept')) {
            this.updateFormData('departement', params.get('dept'));
        }
        if (params.has('poids')) {
            this.updateFormData('poids', parseFloat(params.get('poids')));
        }
        if (params.has('type')) {
            this.updateFormData('type', params.get('type'));
        }
        if (params.has('adr')) {
            this.updateFormData('adr', params.get('adr') === 'oui');
        }
    }
    
    /**
     * Observer un changement d'état
     * @param {string} path - Chemin dans l'état (ex: 'formData.departement')
     * @param {function} callback - Callback appelé lors du changement
     * @returns {function} Fonction de désabonnement
     */
    observe(path, callback) {
        if (!this.observers.has(path)) {
            this.observers.set(path, new Set());
        }
        
        this.observers.get(path).add(callback);
        
        // Retourner fonction de cleanup
        return () => {
            this.observers.get(path)?.delete(callback);
        };
    }
    
    /**
     * Notifier les observateurs
     * @param {string} path - Chemin modifié
     * @param {*} newValue - Nouvelle valeur
     * @param {*} oldValue - Ancienne valeur
     */
    notify(path, newValue, oldValue) {
        // Notifier les observateurs spécifiques
        this.observers.get(path)?.forEach(callback => {
            try {
                callback(newValue, oldValue, path);
            } catch (error) {
                CalculateurConfig.log('error', 'Erreur dans observer:', error);
            }
        });
        
        // Notifier les observateurs génériques
        this.observers.get('*')?.forEach(callback => {
            try {
                callback(this.state, path, newValue, oldValue);
            } catch (error) {
                CalculateurConfig.log('error', 'Erreur dans observer global:', error);
            }
        });
    }
    
    /**
     * Récupérer une valeur de l'état
     * @param {string} path - Chemin vers la valeur
     * @returns {*}
     */
    get(path) {
        return path.split('.').reduce((obj, key) => obj?.[key], this.state);
    }
    
    /**
     * Mettre à jour l'état
     * @param {string} path - Chemin vers la valeur
     * @param {*} value - Nouvelle valeur
     */
    set(path, value) {
        const keys = path.split('.');
        const lastKey = keys.pop();
        const target = keys.reduce((obj, key) => obj[key], this.state);
        
        const oldValue = target[lastKey];
        
        if (oldValue !== value) {
            target[lastKey] = value;
            this.saveToHistory();
            this.notify(path, value, oldValue);
            
            CalculateurConfig.log('debug', `État mis à jour: ${path}`, { oldValue, newValue: value });
        }
    }
    
    /**
     * Mettre à jour les données du formulaire
     * @param {string} field - Nom du champ
     * @param {*} value - Valeur
     */
    updateFormData(field, value) {
        this.set(`formData.${field}`, value);
        this.markFieldTouched(field);
        this.validateField(field);
    }
    
    /**
     * Marquer un champ comme touché
     * @param {string} field - Nom du champ
     */
    markFieldTouched(field) {
        this.state.validation.touchedFields.add(field);
    }
    
    /**
     * Valider un champ
     * @param {string} field - Nom du champ
     */
    validateField(field) {
        const value = this.get(`formData.${field}`);
        const errors = {};
        
        switch (field) {
            case 'departement':
                if (value && !CalculateurConfig.VALIDATION.DEPT_PATTERN.test(value)) {
                    errors[field] = CalculateurConfig.VALIDATION.MESSAGES.DEPT_INVALID;
                }
                break;
                
            case 'poids':
                if (value) {
                    if (value < CalculateurConfig.VALIDATION.MIN_POIDS) {
                        errors[field] = CalculateurConfig.VALIDATION.MESSAGES.POIDS_TOO_LOW;
                    } else if (value > CalculateurConfig.VALIDATION.MAX_POIDS) {
                        errors[field] = CalculateurConfig.VALIDATION.MESSAGES.POIDS_TOO_HIGH;
                    }
                }
                break;
                
            case 'type':
                if (!value) {
                    errors[field] = CalculateurConfig.VALIDATION.MESSAGES.TYPE_REQUIRED;
                }
                break;
                
            case 'palettes':
                if (value > CalculateurConfig.VALIDATION.MAX_PALETTES) {
                    errors[field] = CalculateurConfig.VALIDATION.MESSAGES.PALETTES_TOO_HIGH;
                }
                break;
        }
        
        // Mettre à jour les erreurs
        if (Object.keys(errors).length > 0) {
            this.set(`validation.errors.${field}`, errors[field]);
        } else {
            this.clearFieldError(field);
        }
        
        // Recalculer la validité globale
        this.updateGlobalValidation();
    }
    
    /**
     * Effacer l'erreur d'un champ
     * @param {string} field - Nom du champ
     */
    clearFieldError(field) {
        const errors = { ...this.state.validation.errors };
        delete errors[field];
        this.set('validation.errors', errors);
    }
    
    /**
     * Mettre à jour la validation globale
     */
    updateGlobalValidation() {
        const formData = this.state.formData;
        const errors = this.state.validation.errors;
        
        // Vérifier les champs requis
        const isValid = formData.departement && 
                       formData.poids && 
                       formData.type &&
                       Object.keys(errors).length === 0;
        
        this.set('validation.isValid', isValid);
    }
    
    /**
     * Avancer à l'étape suivante
     */
    nextStep() {
        const currentStep = this.state.ui.currentStep;
        const maxStep = CalculateurConfig.UI.PROGRESS_STEPS.length - 1;
        
        if (currentStep < maxStep) {
            // Marquer l'étape actuelle comme terminée
            this.setStepState(currentStep, 'completed');
            
            // Passer à l'étape suivante
            const nextStep = currentStep + 1;
            this.set('ui.currentStep', nextStep);
            this.setStepState(nextStep, 'current');
            
            CalculateurConfig.log('info', `Passage à l'étape ${nextStep + 1}`);
        }
    }
    
    /**
     * Revenir à l'étape précédente
     */
    previousStep() {
        const currentStep = this.state.ui.currentStep;
        
        if (currentStep > 0) {
            // Marquer l'étape actuelle comme en attente
            this.setStepState(currentStep, 'pending');
            
            // Revenir à l'étape précédente
            const prevStep = currentStep - 1;
            this.set('ui.currentStep', prevStep);
            this.setStepState(prevStep, 'current');
            
            CalculateurConfig.log('info', `Retour à l'étape ${prevStep + 1}`);
        }
    }
    
    /**
     * Aller à une étape spécifique
     * @param {number} stepIndex - Index de l'étape
     */
    goToStep(stepIndex) {
        const maxStep = CalculateurConfig.UI.PROGRESS_STEPS.length - 1;
        
        if (stepIndex >= 0 && stepIndex <= maxStep) {
            const currentStep = this.state.ui.currentStep;
            
            // Marquer l'étape actuelle
            this.setStepState(currentStep, stepIndex > currentStep ? 'completed' : 'pending');
            
            // Aller à la nouvelle étape
            this.set('ui.currentStep', stepIndex);
            this.setStepState(stepIndex, 'current');
            
            CalculateurConfig.log('info', `Navigation vers étape ${stepIndex + 1}`);
        }
    }
    
    /**
     * Définir l'état d'une étape
     * @param {number} stepIndex - Index de l'étape
     * @param {string} state - État ('pending', 'current', 'completed', 'error')
     */
    setStepState(stepIndex, state) {
        const stepStates = [...this.state.ui.stepStates];
        stepStates[stepIndex] = state;
        this.set('ui.stepStates', stepStates);
    }
    
    /**
     * Vérifier si on peut avancer à l'étape suivante
     * @returns {boolean}
     */
    canAdvanceStep() {
        const currentStep = this.state.ui.currentStep;
        const formData = this.state.formData;
        
        switch (currentStep) {
            case 0: // Destination & poids
                return formData.departement && formData.poids;
            case 1: // Type d'envoi
                return formData.type;
            case 2: // Options
                return true; // Toujours possible
            default:
                return false;
        }
    }
    
    /**
     * Définir l'état de calcul
     * @param {boolean} isCalculating - En cours de calcul
     */
    setCalculating(isCalculating) {
        this.set('ui.isCalculating', isCalculating);
    }
    
    /**
     * Mettre à jour les résultats
     * @param {object} results - Résultats du calcul
     */
    updateResults(results) {
        this.set('results', {
            ...results,
            timestamp: new Date().toISOString()
        });
        this.set('ui.showResults', true);
    }
    
    /**
     * Effacer les résultats
     */
    clearResults() {
        this.set('results', {
            carriers: {},
            bestRate: null,
            suggestions: [],
            debug: null,
            timestamp: null
        });
        this.set('ui.showResults', false);
    }
    
    /**
     * Basculer l'affichage des détails
     */
    toggleDetails() {
        this.set('ui.showDetails', !this.state.ui.showDetails);
    }
    
    /**
     * Réinitialiser le formulaire
     */
    reset() {
        // Sauvegarder l'état actuel
        this.saveToHistory();
        
        // Réinitialiser les données
        this.state.formData = {
            departement: '',
            poids: '',
            type: '',
            adr: false,
            service_livraison: 'standard',
            enlevement: false,
            palettes: 0
        };
        
        this.state.ui = {
            currentStep: 0,
            stepStates: CalculateurConfig.UI.PROGRESS_STEPS.map(() => 'pending'),
            isCalculating: false,
            showResults: false,
            showDetails: false
        };
        
        this.state.validation = {
            errors: {},
            isValid: false,
            touchedFields: new Set()
        };
        
        // Marquer la première étape comme courante
        this.state.ui.stepStates[0] = 'current';
        
        // Notifier tous les observateurs
        this.notify('*', this.state, null);
        
        CalculateurConfig.log('info', 'Formulaire réinitialisé');
    }
    
    /**
     * Sauvegarder l'état dans l'historique
     */
    saveToHistory() {
        this.history.push(JSON.parse(JSON.stringify(this.state)));
        
        // Limiter l'historique à 10 entrées
        if (this.history.length > 10) {
            this.history.shift();
        }
    }
    
    /**
     * Restaurer un état depuis l'historique
     * @param {number} index - Index dans l'historique (-1 = dernier)
     */
    restoreFromHistory(index = -1) {
        const targetIndex = index < 0 ? this.history.length + index : index;
        
        if (this.history[targetIndex]) {
            this.state = JSON.parse(JSON.stringify(this.history[targetIndex]));
            this.notify('*', this.state, null);
            
            CalculateurConfig.log('info', 'État restauré depuis historique');
        }
    }
    
    /**
     * Exporter l'état actuel
     * @returns {object}
     */
    export() {
        return JSON.parse(JSON.stringify(this.state));
    }
    
    /**
     * Importer un état
     * @param {object} state - État à importer
     */
    import(state) {
        this.saveToHistory();
        this.state = JSON.parse(JSON.stringify(state));
        this.notify('*', this.state, null);
        
        CalculateurConfig.log('info', 'État importé');
    }
    
    /**
     * Obtenir un résumé de l'état pour debug
     * @returns {object}
     */
    getDebugSummary() {
        return {
            currentStep: this.state.ui.currentStep + 1,
            stepStates: this.state.ui.stepStates,
            formValid: this.state.validation.isValid,
            hasResults: this.state.ui.showResults,
            formData: this.state.formData,
            errors: this.state.validation.errors
        };
    }
}

// =========================================================================
// INSTANCE GLOBALE ET EXPORT
// =========================================================================

// Créer instance globale
window.calculateurState = new StateManager();

// Debug helpers
if (CalculateurConfig.DEBUG.ENABLED) {
    // Exposer des helpers debug
    window.debugCalculateur = {
        state: () => window.calculateurState.state,
        summary: () => window.calculateurState.getDebugSummary(),
        reset: () => window.calculateurState.reset(),
        export: () => window.calculateurState.export(),
        import: (state) => window.calculateurState.import(state),
        history: () => window.calculateurState.history
    };
    
    CalculateurConfig.log('info', 'State manager initialisé avec debug helpers');
}

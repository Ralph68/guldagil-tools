// =============================================================================
// FICHIER 6: /public/assets/js/modules/calculateur/controllers/calc-controller.js
// =============================================================================

/**
 * Contrôleur des calculs
 */
class CalcController {
    constructor() {
        this.currentRequest = null;
        this.bindMethods();
    }
    
    bindMethods() {
        this.startCalculation = this.startCalculation.bind(this);
        this.handleCalculationResult = this.handleCalculationResult.bind(this);
        this.handleCalculationError = this.handleCalculationError.bind(this);
    }
    
    /**
     * Initialisation
     */
    init() {
        this.setupStateSubscriptions();
        
        if (CalculateurConfig.DEBUG) {
            console.log('✅ CalcController initialisé');
        }
    }
    
    /**
     * Abonnements aux changements d'état
     */
    setupStateSubscriptions() {
        // Réaction aux résultats de calcul
        calculateurState.subscribe('calculation.results', (results) => {
            if (results) {
                window.resultsView?.displayResults(results);
            }
        });
        
        // Réaction aux erreurs
        calculateurState.subscribe('calculation.error', (error) => {
            if (error) {
                window.resultsView?.displayError(error);
            }
        });
        
        // Réaction aux changements de loading
        calculateurState.subscribe('calculation.isLoading', (isLoading) => {
            window.resultsView?.setLoadingState(isLoading);
        });
    }
    
    /**
     * Démarrage d'un calcul
     */
    async startCalculation(formData) {
        // Annuler le calcul précédent si en cours
        if (this.currentRequest) {
            this.currentRequest.abort();
        }
        
        // Démarrer le nouveau calcul
        calculateurState.dispatch({
            type: 'CALCULATION_START'
        });
        
        try {
            const result = await apiService.calculateRates(formData);
            this.handleCalculationResult(result);
            
        } catch (error) {
            this.handleCalculationError(error);
        }
    }
    
    /**
     * Gestion du succès du calcul
     */
    handleCalculationResult(result) {
        calculateurState.dispatch({
            type: 'CALCULATION_SUCCESS',
            payload: result
        });
        
        if (CalculateurConfig.DEBUG) {
            console.log('✅ Calcul réussi:', result);
        }
    }
    
    /**
     * Gestion des erreurs de calcul
     */
    handleCalculationError(error) {
        calculateurState.dispatch({
            type: 'CALCULATION_ERROR',
            payload: error.message
        });
        
        if (CalculateurConfig.DEBUG) {
            console.error('❌ Erreur calcul:', error);
        }
    }
    
    /**
     * Annuler le calcul en cours
     */
    cancelCalculation() {
        if (this.currentRequest) {
            this.currentRequest.abort();
            this.currentRequest = null;
        }
    }
}

window.calcController = new CalcController();

// =============================================================================
// FICHIER 2: /public/assets/js/modules/calculateur/core/state.js
// =============================================================================

/**
 * Gestionnaire d'état centralisé - Pattern Redux simplifié
 */
class CalculateurState {
    constructor() {
        this.state = {
            // États du formulaire
            form: {
                data: {},
                isValid: false,
                errors: {},
                touched: {}
            },
            
            // États des calculs
            calculation: {
                isLoading: false,
                results: null,
                error: null,
                history: []
            },
            
            // États UI
            ui: {
                currentStep: 1,
                showDebug: CalculateurConfig.DEBUG,
                showComparison: false
            }
        };
        
        this.listeners = new Map();
        this.actionHistory = [];
    }
    
    /**
     * Abonnement aux changements d'état
     */
    subscribe(path, callback) {
        if (!this.listeners.has(path)) {
            this.listeners.set(path, new Set());
        }
        this.listeners.get(path).add(callback);
        
        // Retourner fonction de désabonnement
        return () => {
            this.listeners.get(path)?.delete(callback);
        };
    }
    
    /**
     * Dispatch d'une action
     */
    dispatch(action) {
        if (CalculateurConfig.DEBUG) {
            console.log('🔄 Action:', action.type, action.payload);
        }
        
        const prevState = structuredClone(this.state);
        this.state = this.reducer(this.state, action);
        
        // Historique des actions
        this.actionHistory.push({
            action,
            timestamp: Date.now(),
            prevState,
            newState: structuredClone(this.state)
        });
        
        // Notifier les listeners
        this.notifyListeners(prevState, this.state);
    }
    
    /**
     * Reducer principal
     */
    reducer(state, action) {
        switch (action.type) {
            case 'FORM_UPDATE':
                return {
                    ...state,
                    form: {
                        ...state.form,
                        data: { ...state.form.data, ...action.payload }
                    }
                };
                
            case 'FORM_VALIDATE':
                return {
                    ...state,
                    form: {
                        ...state.form,
                        isValid: action.payload.isValid,
                        errors: action.payload.errors
                    }
                };
                
            case 'CALCULATION_START':
                return {
                    ...state,
                    calculation: {
                        ...state.calculation,
                        isLoading: true,
                        error: null
                    }
                };
                
            case 'CALCULATION_SUCCESS':
                return {
                    ...state,
                    calculation: {
                        ...state.calculation,
                        isLoading: false,
                        results: action.payload,
                        error: null,
                        history: [...state.calculation.history, {
                            timestamp: Date.now(),
                            params: state.form.data,
                            results: action.payload
                        }].slice(-10) // Garder les 10 derniers
                    }
                };
                
            case 'CALCULATION_ERROR':
                return {
                    ...state,
                    calculation: {
                        ...state.calculation,
                        isLoading: false,
                        error: action.payload
                    }
                };
                
            case 'UI_SET_STEP':
                return {
                    ...state,
                    ui: {
                        ...state.ui,
                        currentStep: action.payload
                    }
                };
                
            default:
                return state;
        }
    }
    
    /**
     * Notification des listeners
     */
    notifyListeners(prevState, newState) {
        this.listeners.forEach((callbacks, path) => {
            const prevValue = this.getNestedValue(prevState, path);
            const newValue = this.getNestedValue(newState, path);
            
            if (prevValue !== newValue) {
                callbacks.forEach(callback => {
                    try {
                        callback(newValue, prevValue, path);
                    } catch (error) {
                        console.error('Erreur listener:', error);
                    }
                });
            }
        });
    }
    
    /**
     * Obtenir une valeur imbriquée par chemin
     */
    getNestedValue(obj, path) {
        return path.split('.').reduce((current, key) => current?.[key], obj);
    }
    
    /**
     * Getter pour l'état actuel
     */
    getState() {
        return structuredClone(this.state);
    }
}

// Instance singleton
window.calculateurState = new CalculateurState();

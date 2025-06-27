/**
 * Titre: Point d'entrée principal - Orchestration modulaire
 * Chemin: /public/assets/js/modules/calculateur/main.js
 * Version: 0.5 beta + build
 */

class CalculateurApp {
    constructor() {
        this.initialized = false;
        this.modules = new Map();
        this.initOrder = [
            'config',
            'state',
            'api',
            'models',
            'controllers',
            'views'
        ];
    }

    async init(serverConfig = {}) {
        if (this.initialized) return;

        try {
            CalculateurConfig.log('info', `🚀 Initialisation Calculateur v${CalculateurConfig.META.VERSION}`);
            
            this.validateDependencies();
            this.mergeServerConfig(serverConfig);
            await this.initializeModules();
            this.setupGlobalHandlers();
            
            this.initialized = true;
            CalculateurConfig.log('info', '✅ Calculateur opérationnel');
            
            if (CalculateurConfig.DEBUG.ENABLED) {
                this.exposeDebugAPI();
            }

        } catch (error) {
            this.handleInitError(error);
        }
    }

    validateDependencies() {
        const required = [
            'CalculateurConfig',
            'calculateurState', 
            'apiService',
            'formDataModel',
            'validationModel',
            'formController',
            'calcController', 
            'uiController',
            'progressiveFormView',
            'resultsDisplayView'
        ];

        const missing = required.filter(dep => !window[dep]);
        if (missing.length > 0) {
            throw new Error(`Modules manquants: ${missing.join(', ')}`);
        }
    }

    mergeServerConfig(serverConfig) {
        if (serverConfig.presetData) {
            this.loadPresetData(serverConfig.presetData);
        }
        
        if (serverConfig.optionsService) {
            CalculateurConfig.CARRIERS.SERVICES = {
                ...CalculateurConfig.CARRIERS.SERVICES,
                ...this.parseServerOptions(serverConfig.optionsService)
            };
        }
    }

    async initializeModules() {
        // Core services
        await this.initCore();
        
        // Models
        await this.initModels();
        
        // Controllers
        await this.initControllers();
        
        // Views
        await this.initViews();
        
        // Cross-module connections
        this.connectModules();
    }

    async initCore() {
        // State manager déjà initialisé globalement
        this.modules.set('state', window.calculateurState);
        
        // API service
        window.apiService.init();
        this.modules.set('api', window.apiService);
        
        CalculateurConfig.log('debug', 'Core initialisé');
    }

    async initModels() {
        // Models sont des singletons, pas d'init spéciale
        this.modules.set('formData', window.formDataModel);
        this.modules.set('validation', window.validationModel);
        
        CalculateurConfig.log('debug', 'Models initialisés');
    }

    async initControllers() {
        // Form controller
        window.formController.init();
        this.modules.set('formController', window.formController);
        
        // Calculation controller
        window.calcController.init();
        this.modules.set('calcController', window.calcController);
        
        // UI controller
        window.uiController.init();
        this.modules.set('uiController', window.uiController);
        
        CalculateurConfig.log('debug', 'Controllers initialisés');
    }

    async initViews() {
        // Progressive form view
        window.progressiveFormView.init();
        this.modules.set('progressiveForm', window.progressiveFormView);
        
        // Results display view
        window.resultsDisplayView.init();
        this.modules.set('resultsDisplay', window.resultsDisplayView);
        
        CalculateurConfig.log('debug', 'Views initialisées');
    }

    connectModules() {
        // Connecter validation aux contrôleurs
        this.setupValidationPipeline();
        
        // Connecter calculs automatiques
        this.setupAutoCalculation();
        
        // Connecter animations
        this.setupAnimationPipeline();
    }

    setupValidationPipeline() {
        window.calculateurState.observe('formData', (newData, oldData) => {
            const validation = window.validationModel.validateAll(newData);
            window.calculateurState.set('validation', {
                isValid: validation.valid,
                errors: this.extractFieldErrors(validation.fields),
                touchedFields: window.calculateurState.get('validation.touchedFields') || new Set()
            });
        });
    }

    setupAutoCalculation() {
        window.calculateurState.observe('validation.isValid', (isValid) => {
            if (isValid && CalculateurConfig.isFeatureEnabled('REAL_TIME_CALC')) {
                const formData = window.calculateurState.get('formData');
                if (window.formDataModel.isComplete(formData)) {
                    window.calcController.scheduleCalculation();
                }
            }
        });
    }

    setupAnimationPipeline() {
        // Animation lors des transitions d'étapes
        window.calculateurState.observe('ui.currentStep', (step, oldStep) => {
            if (oldStep !== undefined) {
                this.triggerStepAnimation(oldStep, step);
            }
        });
        
        // Animation lors des résultats
        window.calculateurState.observe('ui.showResults', (show) => {
            if (show) {
                this.triggerResultsAnimation();
            }
        });
    }

    setupGlobalHandlers() {
        // Gestion erreurs globales
        window.addEventListener('error', this.handleGlobalError.bind(this));
        window.addEventListener('unhandledrejection', this.handleGlobalError.bind(this));
        
        // Gestion fermeture page
        window.addEventListener('beforeunload', this.cleanup.bind(this));
        
        // Gestion raccourcis clavier
        document.addEventListener('keydown', this.handleGlobalKeyboard.bind(this));
        
        // Performance monitoring
        this.setupPerformanceMonitoring();
    }

    handleGlobalKeyboard(event) {
        // Ctrl/Cmd + R : Reset
        if ((event.ctrlKey || event.metaKey) && event.key === 'r') {
            event.preventDefault();
            this.reset();
        }
        
        // Escape : Actions d'annulation
        if (event.key === 'Escape') {
            window.calcController.cancelCalculation();
        }
    }

    setupPerformanceMonitoring() {
        if (!CalculateurConfig.DEBUG.ENABLED) return;
        
        let calcCount = 0;
        window.calculateurState.observe('ui.isCalculating', (calculating) => {
            if (!calculating) {
                calcCount++;
                if (calcCount % 10 === 0) {
                    const stats = window.apiService.getStats();
                    CalculateurConfig.log('info', `Performance après ${calcCount} calculs:`, stats);
                }
            }
        });
    }

    loadPresetData(presetData) {
        Object.entries(presetData).forEach(([key, value]) => {
            if (value) {
                const normalized = window.formDataModel.normalize({ [key]: value });
                window.calculateurState.updateFormData(key, normalized[key]);
            }
        });
    }

    parseServerOptions(options) {
        const parsed = {};
        options.forEach(option => {
            parsed[option.code_option] = {
                label: option.libelle,
                carrier: option.transporteur,
                cost: parseFloat(option.montant) || 0
            };
        });
        return parsed;
    }

    extractFieldErrors(validationFields) {
        const errors = {};
        Object.entries(validationFields).forEach(([field, result]) => {
            if (!result.valid && result.errors.length > 0) {
                errors[field] = result.errors[0]; // Premier erreur seulement
            }
        });
        return errors;
    }

    triggerStepAnimation(oldStep, newStep) {
        const direction = newStep > oldStep ? 'forward' : 'backward';
        document.body.setAttribute('data-step-transition', direction);
        
        setTimeout(() => {
            document.body.removeAttribute('data-step-transition');
        }, 500);
    }

    triggerResultsAnimation() {
        const resultsPanel = document.querySelector('.results-panel');
        if (resultsPanel) {
            resultsPanel.classList.add('results-appear');
        }
    }

    handleInitError(error) {
        CalculateurConfig.log('error', 'Erreur initialisation:', error);
        
        this.showCriticalError(
            'Erreur d\'initialisation du calculateur',
            error.message,
            () => window.location.reload()
        );
    }

    handleGlobalError(event) {
        const error = event.error || event.reason;
        CalculateurConfig.log('error', 'Erreur globale:', error);
        
        // Ne pas afficher les erreurs de réseau en modal
        if (error?.message?.includes('fetch')) {
            return;
        }
        
        window.uiController?.showToast(
            'Une erreur inattendue s\'est produite',
            'error'
        );
    }

    showCriticalError(title, message, retryCallback) {
        const modal = document.createElement('div');
        modal.className = 'error-modal';
        modal.innerHTML = `
            <div class="error-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="error-actions">
                    <button onclick="window.location.reload()">Recharger</button>
                    <button onclick="window.history.back()">Retour</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const style = document.createElement('style');
        style.textContent = `
            .error-modal {
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.8); display: flex; align-items: center;
                justify-content: center; z-index: 10000;
            }
            .error-content {
                background: white; padding: 2rem; border-radius: 8px;
                max-width: 400px; text-align: center;
            }
            .error-actions { margin-top: 1rem; display: flex; gap: 1rem; }
            .error-actions button {
                flex: 1; padding: 0.5rem; border: 1px solid #ccc;
                background: white; cursor: pointer; border-radius: 4px;
            }
        `;
        document.head.appendChild(style);
    }

    reset() {
        CalculateurConfig.log('info', 'Reset complet de l\'application');
        
        window.calculateurState.reset();
        window.formController.reset();
        window.resultsDisplayView.reset();
        window.calcController.cancelCalculation();
    }

    cleanup() {
        CalculateurConfig.log('info', 'Nettoyage avant fermeture');
        
        this.modules.forEach(module => {
            if (module.cleanup) {
                module.cleanup();
            }
        });
        
        window.calcController.cancelCalculation();
    }

    exposeDebugAPI() {
        window.calculateurDebug = {
            app: this,
            state: () => window.calculateurState.getDebugSummary(),
            config: CalculateurConfig,
            modules: this.modules,
            stats: () => window.apiService.getStats(),
            reset: () => this.reset(),
            simulate: {
                calculation: () => window.calcController.calculate(true),
                error: () => { throw new Error('Test error'); },
                step: (n) => window.calculateurState.goToStep(n)
            }
        };
        
        CalculateurConfig.log('info', 'API Debug exposée: window.calculateurDebug');
    }

    getStatus() {
        return {
            initialized: this.initialized,
            modules: Array.from(this.modules.keys()),
            state: window.calculateurState?.getDebugSummary(),
            version: CalculateurConfig.META.VERSION
        };
    }
}

// Instance globale
window.calculateurApp = new CalculateurApp();

// Auto-initialisation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.calculateurApp.init(window.CALCULATEUR_CONFIG);
    });
} else {
    window.calculateurApp.init(window.CALCULATEUR_CONFIG);
}

/**
 * Titre: JavaScript modulaire - App Controller
 * Chemin: /public/assets/js/modules/calculateur/app.js
 */

// app.js - Orchestrateur principal
class CalculateurApp {
    constructor() {
        this.modules = new Map();
        this.initialized = false;
    }

    async init(config) {
        if (this.initialized) return;

        try {
            console.log('🚀 Initialisation Calculateur v' + config.version);

            // Configuration globale
            this.config = config;
            window.CalculateurConfig = config;

            // Initialisation des modules
            await this.initModules();
            this.setupEventListeners();
            
            this.initialized = true;
            console.log('✅ Calculateur opérationnel');

        } catch (error) {
            console.error('❌ Erreur initialisation:', error);
            this.fallbackMode();
        }
    }

    async initModules() {
        // State Manager
        this.stateManager = new StateManager();
        this.modules.set('state', this.stateManager);

        // API Service
        this.apiService = new ApiService(this.config.urls);
        this.modules.set('api', this.apiService);

        // Form Controller
        this.formController = new FormController(this.stateManager);
        this.modules.set('form', this.formController);

        // Results Controller
        this.resultsController = new ResultsController();
        this.modules.set('results', this.resultsController);

        console.log('📦 Modules initialisés');
    }

    setupEventListeners() {
        // Form submission
        const form = document.getElementById('calc-form');
        if (form) {
            form.addEventListener('submit', this.handleCalculation.bind(this));
        }

        // Reset button
        const resetBtn = document.getElementById('reset-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', this.handleReset.bind(this));
        }

        // Type change (palette field)
        const typeInputs = document.querySelectorAll('input[name="type"]');
        typeInputs.forEach(input => {
            input.addEventListener('change', this.handleTypeChange.bind(this));
        });

        console.log('🔗 Event listeners configurés');
    }

    async handleCalculation(event) {
        event.preventDefault();

        try {
            // Validation
            if (!this.formController.validate()) {
                return;
            }

            // Affichage loading
            this.resultsController.showLoading();

            // Collecte des données
            const formData = this.formController.getData();

            // Appel API
            const results = await this.apiService.calculate(formData);

            // Affichage résultats
            this.resultsController.showResults(results);

        } catch (error) {
            console.error('Erreur calcul:', error);
            this.resultsController.showError(error.message);
        }
    }

    handleReset() {
        this.formController.reset();
        this.resultsController.showWaiting();
        this.stateManager.reset();
    }

    handleTypeChange(event) {
        const palettesField = document.getElementById('field-palettes');
        if (palettesField) {
            palettesField.style.display = event.target.value === 'palette' ? 'block' : 'none';
        }
    }

    fallbackMode() {
        console.log('🔄 Mode fallback activé');
        
        // Gestion basique sans modules
        const form = document.getElementById('calc-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                alert('Service temporairement indisponible');
            });
        }
    }
}

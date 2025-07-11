// calculateur.js
import { validationModule } from './modules/validation.js';
import { calculationModule } from './modules/calcul.js';
import { stateModule } from './modules/etat.js';

const CalculateurModule = {
    modules: {
        validation: validationModule,
        calculation: calculationModule,
        state: stateModule
    },

    // Initialisation
    init() {
        console.log('Initialisation du calculateur...');
        this.setupEventListeners();
    },

    // Configuration des écouteurs
    setupEventListeners() {
        // Vérification automatique
        ['departement', 'poids'].forEach(field => {
            document.getElementById(field).addEventListener('input', 
                () => this.validateField(field));
        });
    },

    // Validation des champs
    validateField(field) {
        const value = document.getElementById(field).value;
        const isValid = this.modules.validation[field === 'departement' ? 'validateDepartement' : 'validatePoids'](value);
        
        if (isValid) {
            this.checkAutoCalculateConditions();
        }
    },

    // Vérification des conditions de calcul automatique
    checkAutoCalculateConditions() {
        const conditions = {
            departement: this.modules.validation.validateDepartement(
                document.getElementById('departement').value.trim()
            ),
            poids: this.modules.validation.validatePoids(
                parseFloat(document.getElementById('poids').value)
            )
        };

        if (conditions.departement && conditions.poids) {
            this.handleCalculate();
        }
    }
};

// Démarrage
CalculateurModule.init();

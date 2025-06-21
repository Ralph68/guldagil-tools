// =============================================================================
// FICHIER 4: /public/assets/js/modules/calculateur/models/form-data.js
// =============================================================================

/**
 * Modèle des données du formulaire
 */
class FormDataModel {
    constructor() {
        this.defaultData = {
            departement: '',
            poids: null,
            type: 'colis',
            adr: 'non',
            service_livraison: 'standard',
            enlevement: false,
            palettes: 0
        };
    }
    
    /**
     * Créer un objet de données vide
     */
    create() {
        return { ...this.defaultData };
    }
    
    /**
     * Normaliser les données
     */
    normalize(rawData) {
        const normalized = { ...this.defaultData };
        
        // Département avec padding
        if (rawData.departement) {
            normalized.departement = String(rawData.departement).padStart(2, '0');
        }
        
        // Poids en float
        if (rawData.poids) {
            normalized.poids = parseFloat(rawData.poids);
        }
        
        // Type en lowercase
        if (rawData.type) {
            normalized.type = rawData.type.toLowerCase();
        }
        
        // ADR booléen vers string
        if (typeof rawData.adr === 'boolean') {
            normalized.adr = rawData.adr ? 'oui' : 'non';
        } else if (rawData.adr) {
            normalized.adr = rawData.adr;
        }
        
        // Service de livraison
        if (rawData.service_livraison) {
            normalized.service_livraison = rawData.service_livraison;
        }
        
        // Enlèvement booléen
        normalized.enlevement = Boolean(rawData.enlevement);
        
        // Palettes en entier
        if (rawData.palettes) {
            normalized.palettes = Math.max(0, parseInt(rawData.palettes));
        }
        
        return normalized;
    }
    
    /**
     * Comparer deux jeux de données
     */
    equals(data1, data2) {
        return JSON.stringify(data1) === JSON.stringify(data2);
    }
    
    /**
     * Vérifier si les données sont complètes
     */
    isComplete(data) {
        return Boolean(
            data.departement &&
            data.poids &&
            data.type
        );
    }
}

window.formDataModel = new FormDataModel();

// =============================================================================
// FICHIER 5: /public/assets/js/modules/calculateur/controllers/form-controller.js
// =============================================================================

/**
 * Contrôleur du formulaire
 */
class FormController {
    constructor() {
        this.debounceTimeout = null;
        this.elements = {};
        this.bindMethods();
    }
    
    /**
     * Bind des méthodes pour conserver le contexte
     */
    bindMethods() {
        this.handleFormInput = this.handleFormInput.bind(this);
        this.handleFormSubmit = this.handleFormSubmit.bind(this);
        this.debouncedValidation = this.debouncedValidation.bind(this);
    }
    
    /**
     * Initialisation
     */
    init() {
        this.cacheElements();
        this.bindEvents();
        this.setupStateSubscriptions();
        
        // Charger les données initiales
        this.loadInitialData();
        
        if (CalculateurConfig.DEBUG) {
            console.log('✅ FormController initialisé');
        }
    }
    
    /**
     * Cache des éléments DOM
     */
    cacheElements() {
        this.elements = {
            form: document.getElementById('calculator-form'),
            departement: document.getElementById('departement'),
            poids: document.getElementById('poids'),
            typeRadios: document.querySelectorAll('input[name="type"]'),
            adrRadios: document.querySelectorAll('input[name="adr"]'),
            serviceLivraison: document.getElementById('service_livraison'),
            enlevement: document.getElementById('enlevement'),
            palettes: document.getElementById('palettes')
        };
    }
    
    /**
     * Liaison des événements
     */
    bindEvents() {
        // Événements formulaire
        if (this.elements.form) {
            this.elements.form.addEventListener('submit', this.handleFormSubmit);
            this.elements.form.addEventListener('input', this.handleFormInput);
            this.elements.form.addEventListener('change', this.handleFormInput);
        }
        
        // Événements spécifiques
        if (this.elements.departement) {
            this.elements.departement.addEventListener('input', this.debouncedValidation);
        }
        
        if (this.elements.poids) {
            this.elements.poids.addEventListener('input', this.debouncedValidation);
        }
    }
    
    /**
     * Abonnements aux changements d'état
     */
    setupStateSubscriptions() {
        // Réaction aux erreurs de validation
        calculateurState.subscribe('form.errors', (errors) => {
            this.displayValidationErrors(errors);
        });
        
        // Réaction aux changements de données
        calculateurState.subscribe('form.data', (data) => {
            this.syncFormWithState(data);
        });
    }
    
    /**
     * Gestion des entrées formulaire
     */
    handleFormInput(event) {
        const formData = this.collectFormData();
        
        // Mise à jour de l'état
        calculateurState.dispatch({
            type: 'FORM_UPDATE',
            payload: formData
        });
        
        // Validation différée
        this.debouncedValidation();
    }
    
    /**
     * Gestion de la soumission
     */
    handleFormSubmit(event) {
        event.preventDefault();
        
        const formData = this.collectFormData();
        
        // Validation immédiate
        const validation = apiService.validateClient(formData);
        
        calculateurState.dispatch({
            type: 'FORM_VALIDATE',
            payload: validation
        });
        
        if (validation.isValid) {
            // Déclencher le calcul via le contrôleur de calcul
            window.calcController?.startCalculation(formData);
        }
    }
    
    /**
     * Validation avec debouncing
     */
    debouncedValidation() {
        clearTimeout(this.debounceTimeout);
        
        this.debounceTimeout = setTimeout(() => {
            const formData = this.collectFormData();
            const validation = apiService.validateClient(formData);
            
            calculateurState.dispatch({
                type: 'FORM_VALIDATE',
                payload: validation
            });
            
            // Auto-calcul si valide et complet
            if (validation.isValid && formDataModel.isComplete(formData)) {
                window.calcController?.startCalculation(formData);
            }
            
        }, CalculateurConfig.TIMING.DEBOUNCE_DELAY);
    }
    
    /**
     * Collecte des données du formulaire
     */
    collectFormData() {
        const rawData = {};
        
        // Champs texte
        if (this.elements.departement) rawData.departement = this.elements.departement.value.trim();
        if (this.elements.poids) rawData.poids = this.elements.poids.value;
        if (this.elements.serviceLivraison) rawData.service_livraison = this.elements.serviceLivraison.value;
        if (this.elements.palettes) rawData.palettes = this.elements.palettes.value;
        
        // Radio buttons
        const selectedType = document.querySelector('input[name="type"]:checked');
        if (selectedType) rawData.type = selectedType.value;
        
        const selectedAdr = document.querySelector('input[name="adr"]:checked');
        if (selectedAdr) rawData.adr = selectedAdr.value;
        
        // Checkbox
        if (this.elements.enlevement) rawData.enlevement = this.elements.enlevement.checked;
        
        return formDataModel.normalize(rawData);
    }
    
    /**
     * Synchronisation formulaire avec l'état
     */
    syncFormWithState(data) {
        // Éviter les boucles infinies
        if (this.isSyncInProgress) return;
        this.isSyncInProgress = true;
        
        try {
            // Mise à jour des champs
            if (this.elements.departement && data.departement !== undefined) {
                this.elements.departement.value = data.departement;
            }
            
            if (this.elements.poids && data.poids !== undefined) {
                this.elements.poids.value = data.poids || '';
            }
            
            // Types
            if (data.type) {
                const typeRadio = document.querySelector(`input[name="type"][value="${data.type}"]`);
                if (typeRadio) typeRadio.checked = true;
            }
            
            // ADR
            if (data.adr) {
                const adrRadio = document.querySelector(`input[name="adr"][value="${data.adr}"]`);
                if (adrRadio) adrRadio.checked = true;
            }
            
            // Autres champs...
            
        } finally {
            this.isSyncInProgress = false;
        }
    }
    
    /**
     * Affichage des erreurs de validation
     */
    displayValidationErrors(errors) {
        // Nettoyer les erreurs précédentes
        document.querySelectorAll('.field-error').forEach(el => el.remove());
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        
        // Afficher les nouvelles erreurs
        Object.entries(errors).forEach(([field, message]) => {
            const element = this.elements[field];
            if (element) {
                element.classList.add('error');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.textContent = message;
                
                element.parentNode.appendChild(errorDiv);
            }
        });
    }
    
    /**
     * Chargement des données initiales
     */
    loadInitialData() {
        const initialData = this.collectFormData();
        
        calculateurState.dispatch({
            type: 'FORM_UPDATE',
            payload: initialData
        });
    }
    
    /**
     * Reset du formulaire
     */
    reset() {
        const defaultData = formDataModel.create();
        
        calculateurState.dispatch({
            type: 'FORM_UPDATE',
            payload: defaultData
        });
        
        if (this.elements.form) {
            this.elements.form.reset();
        }
    }
}

window.formController = new FormController();

// =============================================================================
// FICHIER: /public/assets/js/modules/calculateur/controllers/form-controller.js
// =============================================================================

/**
 * Contrôleur du formulaire - Version corrigée
 */
class FormController {
    constructor() {
        this.debounceTimeout = null;
        this.elements = {};
        this.isSyncInProgress = false;
        this.bindMethods();
    }
    
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
        if (this.elements.form) {
            this.elements.form.addEventListener('submit', this.handleFormSubmit);
            this.elements.form.addEventListener('input', this.handleFormInput);
            this.elements.form.addEventListener('change', this.handleFormInput);
        }
        
        // Validation en temps réel pour champs critiques
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
        calculateurState.subscribe('form.errors', (errors) => {
            this.displayValidationErrors(errors);
        });
        
        calculateurState.subscribe('form.data', (data) => {
            this.syncFormWithState(data);
        });
    }
    
    /**
     * Gestion des entrées formulaire
     */
    handleFormInput(event) {
        const formData = this.collectFormData();
        
        calculateurState.dispatch({
            type: 'FORM_UPDATE',
            payload: formData
        });
        
        this.debouncedValidation();
    }
    
    /**
     * Gestion de la soumission
     */
    handleFormSubmit(event) {
        event.preventDefault();
        
        const formData = this.collectFormData();
        const validation = apiService.validateClient(formData);
        
        calculateurState.dispatch({
            type: 'FORM_VALIDATE',
            payload: validation
        });
        
        if (validation.isValid) {
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
        if (this.isSyncInProgress) return;
        this.isSyncInProgress = true;
        
        try {
            if (this.elements.departement && data.departement !== undefined) {
                this.elements.departement.value = data.departement;
            }
            
            if (this.elements.poids && data.poids !== undefined) {
                this.elements.poids.value = data.poids || '';
            }
            
            if (data.type) {
                const typeRadio = document.querySelector(`input[name="type"][value="${data.type}"]`);
                if (typeRadio) typeRadio.checked = true;
            }
            
            if (data.adr) {
                const adrRadio = document.querySelector(`input[name="adr"][value="${data.adr}"]`);
                if (adrRadio) adrRadio.checked = true;
            }
            
            if (this.elements.serviceLivraison && data.service_livraison) {
                this.elements.serviceLivraison.value = data.service_livraison;
            }
            
            if (this.elements.enlevement && data.enlevement !== undefined) {
                this.elements.enlevement.checked = data.enlevement;
            }
            
            if (this.elements.palettes && data.palettes !== undefined) {
                this.elements.palettes.value = data.palettes;
            }
            
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
        
        // Nettoyer les erreurs
        this.displayValidationErrors({});
    }
    
    /**
     * Nettoyage
     */
    cleanup() {
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }
    }
}

window.formController = new FormController();

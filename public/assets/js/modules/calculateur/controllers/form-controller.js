console.log("Chargement de FormController...");

/**
 * Titre: Contrôleur formulaire progressif
 * Chemin: /public/assets/js/modules/calculateur/controllers/form-controller.js
 * Version: 0.5 beta + build
 */

class FormController {
    constructor() {
        this.elements = {};
        this.debounceTimers = new Map();
        this.validationRules = this.initValidationRules();
        this.bindMethods();
    }

    bindMethods() {
        this.handleInput = this.handleInput.bind(this);
        this.handleFieldChange = this.handleFieldChange.bind(this);
        this.validateField = this.validateField.bind(this);
        this.advanceStep = this.advanceStep.bind(this);
    }

    init() {
        this.cacheElements();
        this.bindEvents();
        this.initializeForm();
        this.loadPresetData();
        
        CalculateurConfig.log('info', 'Form controller initialisé');
    }

    cacheElements() {
        this.elements = {
            form: document.getElementById('calculator-form'),
            steps: document.querySelectorAll('.form-step'),
            progressSteps: document.getElementById('progress-steps'),
            progressFill: document.getElementById('progress-fill'),
            
            // Champs
            departement: document.getElementById('departement'),
            poids: document.getElementById('poids'),
            typeRadios: document.querySelectorAll('input[name="type"]'),
            palettes: document.getElementById('palettes'),
            adr: document.getElementById('adr'),
            serviceLivraison: document.getElementById('service_livraison'),
            enlevement: document.getElementById('enlevement'),
            
            // Conteneurs conditionnels
            fieldPalettes: document.getElementById('field-palettes'),
            
            // Résumés
            summaries: document.querySelectorAll('[id^="summary-step-"]')
        };
    }

    bindEvents() {
        // Événements de saisie
        this.elements.departement?.addEventListener('input', this.handleInput);
        this.elements.poids?.addEventListener('input', this.handleInput);
        
        // Événements de changement
        this.elements.typeRadios.forEach(radio => {
            radio.addEventListener('change', this.handleFieldChange);
        });
        
        this.elements.palettes?.addEventListener('input', this.handleInput);
        this.elements.adr?.addEventListener('change', this.handleFieldChange);
        this.elements.serviceLivraison?.addEventListener('change', this.handleFieldChange);
        this.elements.enlevement?.addEventListener('change', this.handleFieldChange);

        // Navigation clavier
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey) {
                this.handleEnterKey(e);
            }
        });

        // Observer l'état pour mise à jour UI
        window.calculateurState?.observe('ui.currentStep', (step) => {
            this.updateStepDisplay(step);
        });

        window.calculateurState?.observe('validation.errors', (errors) => {
            this.updateFieldErrors(errors);
        });
    }

    initializeForm() {
        this.createProgressSteps();
        this.updateProgress(0);
    }

    createProgressSteps() {
        if (!this.elements.progressSteps) return;

        const steps = CalculateurConfig.UI.PROGRESS_STEPS;
        this.elements.progressSteps.innerHTML = steps.map((step, index) => `
            <div class="progress-step ${index === 0 ? 'current' : 'pending'}" data-step="${index}">
                <div class="step-circle">${step.icon}</div>
                <div class="step-label">${step.label}</div>
            </div>
        `).join('');

        // Événements de navigation
        this.elements.progressSteps.querySelectorAll('.progress-step').forEach((step, index) => {
            step.addEventListener('click', () => this.goToStep(index));
        });
    }

    loadPresetData() {
        const presetData = window.CALCULATEUR_CONFIG?.presetData;
        if (!presetData) return;

        Object.entries(presetData).forEach(([key, value]) => {
            if (value) {
                this.setFieldValue(key, value);
            }
        });
    }

    setFieldValue(fieldName, value) {
        switch (fieldName) {
            case 'departement':
                if (this.elements.departement) {
                    this.elements.departement.value = value;
                    this.handleInput({ target: this.elements.departement });
                }
                break;
            case 'poids':
                if (this.elements.poids) {
                    this.elements.poids.value = value;
                    this.handleInput({ target: this.elements.poids });
                }
                break;
            case 'type':
                const radio = document.querySelector(`input[name="type"][value="${value}"]`);
                if (radio) {
                    radio.checked = true;
                    this.handleFieldChange({ target: radio });
                }
                break;
            case 'adr':
                if (this.elements.adr) {
                    this.elements.adr.value = value;
                    this.handleFieldChange({ target: this.elements.adr });
                }
                break;
        }
    }

    handleInput(event) {
        const field = event.target;
        const fieldName = field.name || field.id;
        const value = field.value;

        // Formatage spécifique
        if (fieldName === 'departement') {
            field.value = this.formatDepartement(value);
        } else if (fieldName === 'poids') {
            field.value = this.formatPoids(value);
        }

        // Mise à jour state avec debounce
        this.debouncedUpdate(fieldName, field.value);
    }

    handleFieldChange(event) {
        const field = event.target;
        const fieldName = field.name || field.id;
        let value;

        if (field.type === 'radio') {
            value = field.value;
            this.handleTypeChange(value);
        } else if (field.type === 'checkbox') {
            value = field.checked;
        } else {
            value = field.value;
        }

        // Mise à jour immédiate
        window.calculateurState?.updateFormData(fieldName, value);
        this.validateField(fieldName, value);
        this.updateStepCompletion();
    }

    handleTypeChange(type) {
        // Afficher/masquer champ palettes
        if (this.elements.fieldPalettes) {
            this.elements.fieldPalettes.style.display = type === 'palette' ? 'block' : 'none';
        }

        // Réinitialiser palettes si colis
        if (type === 'colis' && this.elements.palettes) {
            this.elements.palettes.value = '0';
            window.calculateurState?.updateFormData('palettes', 0);
        }
    }

    debouncedUpdate(fieldName, value) {
        clearTimeout(this.debounceTimers.get(fieldName));
        
        this.debounceTimers.set(fieldName, setTimeout(() => {
            window.calculateurState?.updateFormData(fieldName, value);
            this.validateField(fieldName, value);
            this.updateStepCompletion();
        }, CalculateurConfig.TIMING.DEBOUNCE_DELAY));
    }

    validateField(fieldName, value) {
        const rule = this.validationRules[fieldName];
        if (!rule) return true;

        const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
        if (!field) return true;

        const errors = rule.validate(value);
        
        // Mise à jour UI
        if (errors.length > 0) {
            this.showFieldError(field, errors[0]);
            return false;
        } else {
            this.clearFieldError(field);
            return true;
        }
    }

    initValidationRules() {
        return {
            departement: {
                validate: (value) => {
                    const errors = [];
                    if (!value) {
                        errors.push('Département requis');
                    } else if (!CalculateurConfig.VALIDATION.DEPT_PATTERN.test(value)) {
                        errors.push(CalculateurConfig.VALIDATION.MESSAGES.DEPT_INVALID);
                    }
                    return errors;
                }
            },
            poids: {
                validate: (value) => {
                    const errors = [];
                    const numValue = parseFloat(value);
                    if (!value || isNaN(numValue)) {
                        errors.push('Poids requis');
                    } else if (numValue < CalculateurConfig.VALIDATION.MIN_POIDS) {
                        errors.push(CalculateurConfig.VALIDATION.MESSAGES.POIDS_TOO_LOW);
                    } else if (numValue > CalculateurConfig.VALIDATION.MAX_POIDS) {
                        errors.push(CalculateurConfig.VALIDATION.MESSAGES.POIDS_TOO_HIGH);
                    }
                    return errors;
                }
            },
            type: {
                validate: (value) => {
                    return value ? [] : ['Type d\'envoi requis'];
                }
            },
            palettes: {
                validate: (value) => {
                    const errors = [];
                    const numValue = parseInt(value);
                    if (numValue > CalculateurConfig.VALIDATION.MAX_PALETTES) {
                        errors.push(CalculateurConfig.VALIDATION.MESSAGES.PALETTES_TOO_HIGH);
                    }
                    return errors;
                }
            }
        };
    }

    formatDepartement(value) {
        // Supprimer caractères non numériques
        const cleaned = value.replace(/[^0-9]/g, '');
        // Limiter à 3 caractères
        const limited = cleaned.slice(0, 3);
        // Ajouter 0 devant si 1 seul chiffre > 0
        if (limited.length === 1 && parseInt(limited) > 0) {
            return '0' + limited;
        }
        return limited;
    }

    formatPoids(value) {
        // Permettre décimales avec point ou virgule
        return value.replace(',', '.');
    }

    showFieldError(field, message) {
        field.classList.add('error');
        field.classList.remove('valid');
        
        const errorElement = document.getElementById(`error-${field.id || field.name}`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    clearFieldError(field) {
        field.classList.remove('error');
        field.classList.add('valid');
        
        const errorElement = document.getElementById(`error-${field.id || field.name}`);
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    updateFieldErrors(errors) {
        // Effacer toutes les erreurs
        document.querySelectorAll('.form-input, .form-select').forEach(field => {
            this.clearFieldError(field);
        });

        // Afficher nouvelles erreurs
        Object.entries(errors).forEach(([fieldName, message]) => {
            const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                this.showFieldError(field, message);
            }
        });
    }

    updateStepCompletion() {
        const currentStep = window.calculateurState?.get('ui.currentStep') || 0;
        
        if (this.isStepComplete(currentStep)) {
            this.markStepComplete(currentStep);
            this.updateStepSummary(currentStep);
            
            // Auto-advance après délai
            if (CalculateurConfig.isFeatureEnabled('AUTO_ADVANCE')) {
                setTimeout(() => {
                    this.autoAdvanceStep();
                }, CalculateurConfig.TIMING.STEP_AUTO_ADVANCE);
            }
        }
    }

    isStepComplete(stepIndex) {
        const formData = window.calculateurState?.get('formData');
        if (!formData) return false;

        switch (stepIndex) {
            case 0: // Destination & poids
                return formData.departement && formData.poids;
            case 1: // Type
                return formData.type;
            case 2: // Options
                return true; // Toujours valide (optionnel)
            default:
                return false;
        }
    }

    markStepComplete(stepIndex) {
        const step = document.querySelector(`[data-step="${stepIndex}"]`);
        if (step) {
            step.classList.add('completed');
        }
    }

    updateStepSummary(stepIndex) {
        const summaryElement = document.getElementById(`summary-step-${stepIndex + 1}`);
        const contentElement = document.getElementById(`summary-content-${stepIndex + 1}`);
        
        if (!summaryElement || !contentElement) return;

        const formData = window.calculateurState?.get('formData');
        let summaryText = '';

        switch (stepIndex) {
            case 0:
                summaryText = `Département ${formData.departement} • ${formData.poids} kg`;
                break;
            case 1:
                summaryText = `${formData.type === 'colis' ? 'Colis' : 'Palette(s)'}`;
                if (formData.type === 'palette' && formData.palettes > 0) {
                    summaryText += ` • ${formData.palettes} palette(s)`;
                }
                break;
            case 2:
                const options = [];
                if (formData.adr === 'oui') options.push('ADR');
                if (formData.service_livraison !== 'standard') {
                    options.push(this.getServiceLabel(formData.service_livraison));
                }
                if (formData.enlevement) options.push('Enlèvement');
                summaryText = options.length > 0 ? options.join(' • ') : 'Aucune option';
                break;
        }

        contentElement.textContent = summaryText;
        summaryElement.style.display = 'block';
    }

    getServiceLabel(service) {
        const services = CalculateurConfig.CARRIERS.SERVICES;
        return services[service]?.label || service;
    }

    autoAdvanceStep() {
        const currentStep = window.calculateurState?.get('ui.currentStep') || 0;
        const maxStep = CalculateurConfig.UI.PROGRESS_STEPS.length - 1;
        
        if (currentStep < maxStep && this.isStepComplete(currentStep)) {
            window.calculateurState?.nextStep();
        }
    }

    goToStep(stepIndex) {
        const maxStep = CalculateurConfig.UI.PROGRESS_STEPS.length - 1;
        if (stepIndex < 0 || stepIndex > maxStep) return;

        // Vérifier si on peut aller à cette étape
        if (stepIndex > 0 && !this.canGoToStep(stepIndex)) {
            this.showStepValidationMessage(stepIndex);
            return;
        }

        window.calculateurState?.goToStep(stepIndex);
    }

    canGoToStep(stepIndex) {
        // Vérifier que toutes les étapes précédentes sont complètes
        for (let i = 0; i < stepIndex; i++) {
            if (!this.isStepComplete(i)) {
                return false;
            }
        }
        return true;
    }

    showStepValidationMessage(stepIndex) {
        const stepName = CalculateurConfig.UI.PROGRESS_STEPS[stepIndex]?.label;
        window.showFooterToast?.(`Complétez les étapes précédentes pour accéder à "${stepName}"`, 'warning');
    }

    updateStepDisplay(stepIndex) {
        // Mettre à jour les étapes visibles
        this.elements.steps.forEach((step, index) => {
            step.classList.toggle('active', index === stepIndex);
            step.classList.toggle('previous', index < stepIndex);
        });

        // Mettre à jour la progression
        this.updateProgress(stepIndex);
        this.updateProgressSteps(stepIndex);
    }

    updateProgress(stepIndex) {
        if (!this.elements.progressFill) return;
        
        const progress = ((stepIndex + 1) / CalculateurConfig.UI.PROGRESS_STEPS.length) * 100;
        this.elements.progressFill.style.width = `${progress}%`;
    }

    updateProgressSteps(stepIndex) {
        if (!this.elements.progressSteps) return;

        const steps = this.elements.progressSteps.querySelectorAll('.progress-step');
        steps.forEach((step, index) => {
            step.classList.remove('pending', 'current', 'completed');
            
            if (index < stepIndex) {
                step.classList.add('completed');
            } else if (index === stepIndex) {
                step.classList.add('current');
            } else {
                step.classList.add('pending');
            }
        });
    }

    handleEnterKey(event) {
        const activeElement = document.activeElement;
        
        // Si dans un champ de saisie, valider et avancer
        if (activeElement && activeElement.matches('.form-input, .form-select')) {
            event.preventDefault();
            
            const fieldName = activeElement.name || activeElement.id;
            if (this.validateField(fieldName, activeElement.value)) {
                this.focusNextField(activeElement);
            }
        }
    }

    focusNextField(currentField) {
        const formFields = Array.from(this.elements.form.querySelectorAll('.form-input, .form-select'));
        const currentIndex = formFields.indexOf(currentField);
        
        if (currentIndex < formFields.length - 1) {
            formFields[currentIndex + 1].focus();
        }
    }

    reset() {
        // Réinitialiser le formulaire
        this.elements.form?.reset();
        
        // Effacer les erreurs
        document.querySelectorAll('.form-input, .form-select').forEach(field => {
            this.clearFieldError(field);
        });

        // Masquer résumés
        this.elements.summaries.forEach(summary => {
            summary.style.display = 'none';
        });

        // Réinitialiser progression
        this.updateStepDisplay(0);
        
        CalculateurConfig.log('info', 'Formulaire réinitialisé');
    }

    getFormData() {
        return window.calculateurState?.get('formData') || {};
    }

    isFormValid() {
        return window.calculateurState?.get('validation.isValid') || false;
    }
}

// Export global
window.formController = new FormController();

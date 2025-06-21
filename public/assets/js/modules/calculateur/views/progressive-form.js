/**
 * Titre: Vue formulaire progressif dynamique
 * Chemin: /public/assets/js/modules/calculateur/views/progressive-form.js
 * Version: 0.5 beta + build
 */

class ProgressiveFormView {
    constructor() {
        this.currentStep = 0;
        this.elements = {};
        this.animationQueue = [];
    }

    init() {
        this.cacheElements();
        this.setupObservers();
        this.initializeSteps();
        CalculateurConfig.log('info', 'Progressive form view initialisée');
    }

    cacheElements() {
        this.elements = {
            steps: document.querySelectorAll('.form-step'),
            progressBar: document.getElementById('progress-fill'),
            progressSteps: document.querySelectorAll('.progress-step'),
            summaries: document.querySelectorAll('[id^="summary-"]')
        };
    }

    setupObservers() {
        window.calculateurState?.observe('ui.currentStep', (step) => {
            this.animateToStep(step);
        });

        window.calculateurState?.observe('formData', () => {
            this.updateSummaries();
        });
    }

    initializeSteps() {
        this.elements.steps.forEach((step, index) => {
            step.style.display = index === 0 ? 'block' : 'none';
            step.style.opacity = index === 0 ? '1' : '0';
        });
    }

    animateToStep(newStep) {
        if (newStep === this.currentStep) return;

        const oldStep = this.currentStep;
        this.currentStep = newStep;

        this.animateStepTransition(oldStep, newStep);
        this.updateProgressBar(newStep);
        this.updateProgressSteps(newStep);
    }

    animateStepTransition(from, to) {
        const fromEl = this.elements.steps[from];
        const toEl = this.elements.steps[to];
        
        if (!fromEl || !toEl) return;

        // Animation sortie
        fromEl.style.transform = to > from ? 'translateX(-100%)' : 'translateX(100%)';
        fromEl.style.opacity = '0';
        
        setTimeout(() => {
            fromEl.style.display = 'none';
            fromEl.style.transform = '';
            
            // Animation entrée
            toEl.style.display = 'block';
            toEl.style.transform = to > from ? 'translateX(100%)' : 'translateX(-100%)';
            toEl.style.opacity = '0';
            
            requestAnimationFrame(() => {
                toEl.style.transform = 'translateX(0)';
                toEl.style.opacity = '1';
            });
        }, 200);
    }

    updateProgressBar(step) {
        const progress = ((step + 1) / this.elements.steps.length) * 100;
        if (this.elements.progressBar) {
            this.elements.progressBar.style.width = `${progress}%`;
        }
    }

    updateProgressSteps(currentStep) {
        this.elements.progressSteps.forEach((step, index) => {
            step.classList.remove('pending', 'current', 'completed');
            
            if (index < currentStep) {
                step.classList.add('completed');
            } else if (index === currentStep) {
                step.classList.add('current');
            } else {
                step.classList.add('pending');
            }
        });
    }

    updateSummaries() {
        const formData = window.calculateurState?.get('formData');
        if (!formData) return;

        // Étape 1: Destination & poids
        this.updateSummary(1, this.formatStep1Summary(formData));
        
        // Étape 2: Type
        this.updateSummary(2, this.formatStep2Summary(formData));
        
        // Étape 3: Options
        this.updateSummary(3, this.formatStep3Summary(formData));
    }

    updateSummary(stepNumber, content) {
        const summaryEl = document.getElementById(`summary-step-${stepNumber}`);
        const contentEl = document.getElementById(`summary-content-${stepNumber}`);
        
        if (summaryEl && contentEl && content) {
            contentEl.textContent = content;
            summaryEl.style.display = 'block';
            summaryEl.classList.add('fade-in');
        }
    }

    formatStep1Summary(data) {
        if (!data.departement || !data.poids) return null;
        return `Département ${data.departement} • ${data.poids} kg`;
    }

    formatStep2Summary(data) {
        if (!data.type) return null;
        let summary = data.type === 'colis' ? 'Colis' : 'Palette(s)';
        if (data.type === 'palette' && data.palettes > 0) {
            summary += ` • ${data.palettes} palette(s)`;
        }
        return summary;
    }

    formatStep3Summary(data) {
        const options = [];
        if (data.adr === 'oui') options.push('ADR');
        if (data.service_livraison !== 'standard') {
            const services = CalculateurConfig.CARRIERS.SERVICES;
            options.push(services[data.service_livraison]?.label || data.service_livraison);
        }
        if (data.enlevement) options.push('Enlèvement');
        return options.length > 0 ? options.join(' • ') : 'Aucune option';
    }

    showFieldValidation(fieldName, isValid, message = '') {
        const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        field.classList.toggle('valid', isValid);
        field.classList.toggle('error', !isValid);

        const errorEl = document.getElementById(`error-${fieldName}`);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = isValid ? 'none' : 'block';
        }
    }

    highlightRequiredFields(stepIndex) {
        const step = this.elements.steps[stepIndex];
        if (!step) return;

        const requiredFields = step.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value) {
                field.classList.add('highlight-required');
                setTimeout(() => field.classList.remove('highlight-required'), 2000);
            }
        });
    }

    focusFirstField(stepIndex) {
        const step = this.elements.steps[stepIndex];
        if (!step) return;

        setTimeout(() => {
            const firstInput = step.querySelector('.form-input, .form-select');
            firstInput?.focus();
        }, 300);
    }
}

window.progressiveFormView = new ProgressiveFormView();

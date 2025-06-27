// =====================================================================
// form-controller.js - Contrôleur de formulaire
// =====================================================================
class FormController {
    constructor(stateManager) {
        this.stateManager = stateManager;
        this.form = document.getElementById('calc-form');
        this.validators = new Map();
        this.setupValidators();
    }

    setupValidators() {
        this.validators.set('departement', (value) => {
            if (!value || value === '') {
                return 'Département requis';
            }
            return null;
        });

        this.validators.set('poids', (value) => {
            if (!value || value <= 0) {
                return 'Poids requis et supérieur à 0';
            }
            if (value > 32000) {
                return 'Poids maximum 32000 kg';
            }
            return null;
        });

        this.validators.set('type', (value) => {
            if (!value) {
                return 'Type d\'envoi requis';
            }
            return null;
        });
    }

    validate() {
        const formData = this.getData();
        const errors = [];

        this.validators.forEach((validator, field) => {
            const error = validator(formData[field]);
            if (error) {
                errors.push({ field, message: error });
            }
        });

        if (errors.length > 0) {
            this.showValidationErrors(errors);
            return false;
        }

        this.clearValidationErrors();
        return true;
    }

    getData() {
        const formData = new FormData(this.form);
        const data = {};

        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        return data;
    }

    reset() {
        this.form.reset();
        this.clearValidationErrors();
        
        // Reset palette field visibility
        const palettesField = document.getElementById('field-palettes');
        if (palettesField) {
            palettesField.style.display = 'none';
        }
    }

    showValidationErrors(errors) {
        this.clearValidationErrors();
        
        errors.forEach(error => {
            const field = document.getElementById(error.field);
            if (field) {
                field.classList.add('error');
                const errorMsg = document.createElement('div');
                errorMsg.className = 'field-error';
                errorMsg.textContent = error.message;
                field.parentNode.appendChild(errorMsg);
            }
        });
    }

    clearValidationErrors() {
        // Remove error classes
        document.querySelectorAll('.form-control.error').forEach(el => {
            el.classList.remove('error');
        });

        // Remove error messages
        document.querySelectorAll('.field-error').forEach(el => {
            el.remove();
        });
    }
}

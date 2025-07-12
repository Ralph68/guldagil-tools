/**
 * Titre: JavaScript Login - Version modulaire
 * Chemin: /public/auth/assets/js/login.js
 * Version: 0.5 beta + build auto
 */

class LoginManager {
    constructor() {
        this.form = document.querySelector('.login-form');
        this.submitBtn = document.querySelector('.login-btn');
        this.usernameField = document.getElementById('username');
        this.passwordField = document.getElementById('password');
        this.alertContainer = document.querySelector('.alert');
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupFormValidation();
        this.autoFocusFirstField();
        this.handleURLParams();
    }

    bindEvents() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        // Enter key sur username -> focus password
        if (this.usernameField && this.passwordField) {
            this.usernameField.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.passwordField.focus();
                }
            });
        }

        // Validation en temps réel
        [this.usernameField, this.passwordField].forEach(field => {
            if (field) {
                field.addEventListener('input', () => this.clearFieldError(field));
            }
        });
    }

    setupFormValidation() {
        if (!this.form) return;

        this.form.setAttribute('novalidate', true);
        
        // Validation native HTML5 mais avec style custom
        const inputs = this.form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('invalid', (e) => {
                e.preventDefault();
                this.showFieldError(input, this.getValidationMessage(input));
            });
        });
    }

    getValidationMessage(input) {
        const field = input.name;
        const value = input.value.trim();

        if (!value) {
            return field === 'username' ? 
                'Veuillez entrer votre nom d\'utilisateur' : 
                'Veuillez entrer votre mot de passe';
        }

        if (field === 'username' && value.length < 2) {
            return 'Le nom d\'utilisateur doit contenir au moins 2 caractères';
        }

        if (field === 'password' && value.length < 3) {
            return 'Le mot de passe doit contenir au moins 3 caractères';
        }

        return '';
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('field-error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error-message';
        errorDiv.textContent = message;
        errorDiv.style.cssText = `
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            animation: fadeIn 0.2s ease-in;
        `;
        
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('field-error');
        const errorMsg = field.parentNode.querySelector('.field-error-message');
        if (errorMsg) {
            errorMsg.remove();
        }
    }

    handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }

        this.setLoading(true);
        
        // Simulation délai réseau
        setTimeout(() => {
            this.form.submit();
        }, 300);
    }

    validateForm() {
        let isValid = true;
        
        const username = this.usernameField?.value.trim();
        const password = this.passwordField?.value.trim();

        if (!username) {
            this.showFieldError(this.usernameField, 'Nom d\'utilisateur requis');
            isValid = false;
        } else if (username.length < 2) {
            this.showFieldError(this.usernameField, 'Nom d\'utilisateur trop court');
            isValid = false;
        }

        if (!password) {
            this.showFieldError(this.passwordField, 'Mot de passe requis');
            isValid = false;
        } else if (password.length < 3) {
            this.showFieldError(this.passwordField, 'Mot de passe trop court');
            isValid = false;
        }

        return isValid;
    }

    setLoading(loading) {
        if (!this.submitBtn) return;

        if (loading) {
            this.submitBtn.disabled = true;
            this.submitBtn.innerHTML = `
                <span style="display: inline-block; animation: spin 1s linear infinite;">⟳</span>
                Connexion...
            `;
        } else {
            this.submitBtn.disabled = false;
            this.submitBtn.innerHTML = 'Se connecter';
        }
    }

    autoFocusFirstField() {
        // Focus automatique sur le premier champ vide
        if (this.usernameField && !this.usernameField.value) {
            setTimeout(() => this.usernameField.focus(), 100);
        } else if (this.passwordField && !this.passwordField.value) {
            setTimeout(() => this.passwordField.focus(), 100);
        }
    }

    handleURLParams() {
        const params = new URLSearchParams(window.location.search);
        const msg = params.get('msg');
        
        if (msg === 'disconnected') {
            this.showMessage('Vous avez été déconnecté avec succès', 'success');
        } else if (msg === 'expired') {
            this.showMessage('Votre session a expiré, veuillez vous reconnecter', 'warning');
        } else if (msg === 'unauthorized') {
            this.showMessage('Accès non autorisé, veuillez vous connecter', 'warning');
        }
    }

    showMessage(text, type = 'info') {
        // Supprimer message existant
        const existing = document.querySelector('.dynamic-alert');
        if (existing) existing.remove();

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} dynamic-alert`;
        
        const icon = type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
        alertDiv.innerHTML = `${icon} ${text}`;
        
        const form = document.querySelector('.login-form');
        if (form) {
            form.parentNode.insertBefore(alertDiv, form);
        }

        // Auto-masquer après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, 5000);
    }
}

// Styles CSS dynamiques
const styles = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .field-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    
    .login-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .dynamic-alert {
        animation: fadeIn 0.3s ease-out;
        transition: opacity 0.3s ease-out;
    }
`;

// Injection des styles
const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    new LoginManager();
});

// public/assets/js/globals.js - Utilitaires globaux corrigés

/**
 * Namespace global Guldagil
 */
window.Guldagil = window.Guldagil || {
    version: 'beta 0.5',
    debug: false,
    modules: {},
    utils: {}
};

/**
 * Système de notifications unifié
 */
Guldagil.notifications = {
    container: null,
    
    init() {
        if (!this.container) {
            this.createContainer();
        }
    },
    
    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notifications-container';
        this.container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        `;
        document.body.appendChild(this.container);
    },
    
    show(type, message, duration = 5000) {
        this.init();
        
        const notification = document.createElement('div');
        notification.style.cssText = `
            background: var(--bg-primary, white);
            border: 1px solid var(--border-light, #e2e8f0);
            border-radius: var(--radius-md, 0.5rem);
            box-shadow: var(--shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1));
            padding: 1rem;
            margin-bottom: 0.5rem;
            min-width: 300px;
            pointer-events: auto;
            animation: slideIn 0.3s ease;
            border-left: 4px solid ${this.getTypeColor(type)};
        `;
        
        const icon = this.getIcon(type);
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.2rem;">${icon}</span>
                <span style="flex: 1; color: var(--text-primary, #0f172a);">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; cursor: pointer; font-size: 1.2rem; color: var(--text-secondary, #64748b);">×</button>
            </div>
        `;
        
        this.container.appendChild(notification);
        
        // Auto-suppression
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, duration);
    },
    
    getIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    },
    
    getTypeColor(type) {
        const colors = {
            success: '#22c55e',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        return colors[type] || colors.info;
    }
};

/**
 * Validation simple
 */
Guldagil.validation = {
    required: (value) => value !== null && value !== undefined && value.toString().trim() !== '',
    
    email: (value) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    },
    
    numeric: (value) => !isNaN(value) && !isNaN(parseFloat(value)),
    
    minLength: (value, min) => value && value.length >= min,
    
    maxLength: (value, max) => !value || value.length <= max
};

/**
 * Utilitaires HTTP simplifiés
 */
Guldagil.http = {
    async request(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaultOptions, ...options };
        
        if (config.data && config.method !== 'GET') {
            if (config.data instanceof FormData) {
                delete config.headers['Content-Type'];
                config.body = config.data;
            } else {
                config.body = JSON.stringify(config.data);
            }
        }
        
        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            console.error('Erreur HTTP:', error);
            throw error;
        }
    },
    
    get(url, params = {}) {
        const urlParams = new URLSearchParams(params).toString();
        const fullUrl = urlParams ? `${url}?${urlParams}` : url;
        return this.request(fullUrl);
    },
    
    post(url, data) {
        return this.request(url, { method: 'POST', data });
    }
};

/**
 * Gestionnaire de formulaires
 */
Guldagil.forms = {
    validate(form) {
        const errors = {};
        const fields = form.querySelectorAll('[data-validate]');
        
        fields.forEach(field => {
            const rules = field.dataset.validate.split('|');
            const value = field.value;
            const name = field.name || field.id;
            
            rules.forEach(rule => {
                const [ruleName, ruleValue] = rule.split(':');
                
                switch (ruleName) {
                    case 'required':
                        if (!Guldagil.validation.required(value)) {
                            errors[name] = errors[name] || [];
                            errors[name].push('Ce champ est requis');
                        }
                        break;
                    case 'email':
                        if (value && !Guldagil.validation.email(value)) {
                            errors[name] = errors[name] || [];
                            errors[name].push('Format email invalide');
                        }
                        break;
                    case 'min':
                        if (!Guldagil.validation.minLength(value, parseInt(ruleValue))) {
                            errors[name] = errors[name] || [];
                            errors[name].push(`Minimum ${ruleValue} caractères`);
                        }
                        break;
                    case 'numeric':
                        if (value && !Guldagil.validation.numeric(value)) {
                            errors[name] = errors[name] || [];
                            errors[name].push('Valeur numérique requise');
                        }
                        break;
                }
            });
        });
        
        return {
            isValid: Object.keys(errors).length === 0,
            errors: errors
        };
    },
    
    showErrors(form, errors) {
        // Nettoyer les erreurs précédentes
        form.querySelectorAll('.form-error').forEach(error => error.remove());
        form.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
        
        // Afficher les nouvelles erreurs
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"], #${fieldName}`);
            if (field) {
                field.classList.add('error');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.textContent = errors[fieldName][0];
                errorDiv.style.cssText = `
                    color: var(--error-border, #ef4444);
                    font-size: 0.8rem;
                    margin-top: 0.25rem;
                `;
                
                field.parentNode.appendChild(errorDiv);
            }
        });
    }
};

/**
 * Utilitaires de stockage
 */
Guldagil.storage = {
    _memoryStorage: {},
    
    set(key, value) {
        try {
            localStorage.setItem(`guldagil_${key}`, JSON.stringify(value));
            return true;
        } catch (e) {
            console.warn('LocalStorage non disponible, utilisation mémoire');
            this._memoryStorage[key] = value;
            return false;
        }
    },
    
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(`guldagil_${key}`);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            return this._memoryStorage[key] || defaultValue;
        }
    },
    
    remove(key) {
        try {
            localStorage.removeItem(`guldagil_${key}`);
        } catch (e) {
            delete this._memoryStorage[key];
        }
    }
};

/**
 * Utilitaires divers
 */
Guldagil.utils = {
    debounce(func, wait, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    },
    
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    },
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

/**
 * Gestionnaire d'événements simple
 */
Guldagil.events = {
    listeners: {},
    
    on(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);
    },
    
    off(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
        }
    },
    
    emit(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Erreur dans listener ${event}:`, error);
                }
            });
        }
    }
};

/**
 * Animation CSS pour les notifications
 */
if (!document.querySelector('#guldagil-animations')) {
    const style = document.createElement('style');
    style.id = 'guldagil-animations';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .form-error {
            color: var(--error-border, #ef4444) !important;
            font-size: 0.8rem !important;
            margin-top: 0.25rem !important;
        }
        
        .error {
            border-color: var(--error-border, #ef4444) !important;
        }
    `;
    document.head.appendChild(style);
}

// Fonction globale pour compatibilité
window.showNotification = function(message, type = 'info') {
    Guldagil.notifications.show(type, message);
};

// Log d'initialisation
console.log('✅ Globals Guldagil chargés - version', Guldagil.version);

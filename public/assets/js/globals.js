// public/assets/js/globals.js - Utilitaires globaux et fonctions communes

/**
 * Namespace global Guldagil
 */
window.Guldagil = window.Guldagil || {
    version: '2.0.0',
    debug: true,
    modules: {},
    components: {},
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
        this.container.className = 'notifications-container';
        document.body.appendChild(this.container);
        
        // Injecter les styles
        this.injectStyles();
    },
    
    show(type, message, title = null, duration = 5000) {
        this.init();
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} animate-slide-in`;
        
        const icon = this.getIcon(type);
        const id = 'notif-' + Date.now();
        notification.id = id;
        
        notification.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div class="notification-content">
                ${title ? `<div class="notification-title">${title}</div>` : ''}
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close" onclick="Guldagil.notifications.hide('${id}')">&times;</button>
        `;
        
        this.container.appendChild(notification);
        
        // Auto-hide
        if (duration > 0) {
            setTimeout(() => this.hide(id), duration);
        }
        
        return id;
    },
    
    hide(id) {
        const notification = document.getElementById(id);
        if (notification) {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    },
    
    success(message, title = 'Succès') {
        return this.show('success', message, title);
    },
    
    error(message, title = 'Erreur') {
        return this.show('error', message, title, 8000);
    },
    
    warning(message, title = 'Attention') {
        return this.show('warning', message, title, 6000);
    },
    
    info(message, title = null) {
        return this.show('info', message, title);
    },
    
    getIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || 'ℹ️';
    },
    
    injectStyles() {
        if (document.getElementById('notifications-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'notifications-styles';
        style.textContent = `
            .notifications-container {
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: var(--z-toast);
                display: flex;
                flex-direction: column;
                gap: var(--space-sm);
                max-width: 400px;
                pointer-events: none;
            }
            
            .notification {
                background: var(--bg-primary);
                border: 1px solid var(--border-medium);
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-xl);
                padding: var(--space-md);
                display: flex;
                align-items: flex-start;
                gap: var(--space-sm);
                pointer-events: auto;
                transition: var(--transition-normal);
            }
            
            .notification-success {
                border-left: 4px solid var(--success-border);
            }
            
            .notification-error {
                border-left: 4px solid var(--error-border);
            }
            
            .notification-warning {
                border-left: 4px solid var(--warning-border);
            }
            
            .notification-info {
                border-left: 4px solid var(--info-border);
            }
            
            .notification-icon {
                font-size: 1.25rem;
                flex-shrink: 0;
                margin-top: 0.125rem;
            }
            
            .notification-content {
                flex: 1;
                min-width: 0;
            }
            
            .notification-title {
                font-weight: 600;
                margin-bottom: var(--space-xs);
                color: var(--text-primary);
            }
            
            .notification-message {
                font-size: 0.875rem;
                color: var(--text-secondary);
                line-height: 1.4;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: var(--text-muted);
                cursor: pointer;
                font-size: 1.25rem;
                padding: 0;
                width: 1.5rem;
                height: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-sm);
                transition: var(--transition-fast);
                flex-shrink: 0;
            }
            
            .notification-close:hover {
                background: var(--bg-tertiary);
                color: var(--text-primary);
            }
            
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
            
            @media (max-width: 768px) {
                .notifications-container {
                    left: 1rem;
                    right: 1rem;
                    max-width: none;
                }
            }
        `;
        document.head.appendChild(style);
    }
};

/**
 * Gestionnaire de modales unifié
 */
Guldagil.modals = {
    activeModal: null,
    
    open(modalId, options = {}) {
        this.closeAll();
        
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`Modal ${modalId} non trouvée`);
            return;
        }
        
        modal.classList.add('active');
        this.activeModal = modalId;
        
        // Bloquer le scroll du body
        document.body.style.overflow = 'hidden';
        
        // Focus sur le premier élément focusable
        setTimeout(() => {
            const firstFocusable = modal.querySelector('input, button, textarea, select, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) firstFocusable.focus();
        }, 100);
        
        // Callback d'ouverture
        if (options.onOpen) options.onOpen(modal);
        
        // Event personnalisé
        window.dispatchEvent(new CustomEvent('modalOpened', { detail: { modalId } }));
    },
    
    close(modalId = null) {
        const targetModal = modalId || this.activeModal;
        if (!targetModal) return;
        
        const modal = document.getElementById(targetModal);
        if (modal) {
            modal.classList.remove('active');
            
            // Restaurer le scroll
            document.body.style.overflow = '';
            
            if (this.activeModal === targetModal) {
                this.activeModal = null;
            }
            
            // Event personnalisé
            window.dispatchEvent(new CustomEvent('modalClosed', { detail: { modalId: targetModal } }));
        }
    },
    
    closeAll() {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
        this.activeModal = null;
    },
    
    // Fermeture avec Escape
    init() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModal) {
                this.close();
            }
        });
        
        // Fermeture par clic sur backdrop
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
                this.close(e.target.id);
            }
        });
    }
};

/**
 * Utilitaires de validation
 */
Guldagil.validation = {
    email(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    phone(phone) {
        const re = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
        return re.test(phone.replace(/\s/g, ''));
    },
    
    required(value) {
        return value !== null && value !== undefined && value.toString().trim() !== '';
    },
    
    minLength(value, min) {
        return value && value.length >= min;
    },
    
    maxLength(value, max) {
        return !value || value.length <= max;
    },
    
    numeric(value) {
        return !isNaN(value) && !isNaN(parseFloat(value));
    },
    
    postal(code) {
        const re = /^[0-9]{5}$/;
        return re.test(code);
    }
};

/**
 * Utilitaires de formatage
 */
Guldagil.format = {
    currency(amount, currency = 'EUR') {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },
    
    number(num, decimals = 2) {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
    },
    
    date(date, options = {}) {
        const defaultOptions = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return new Intl.DateTimeFormat('fr-FR', { ...defaultOptions, ...options }).format(new Date(date));
    },
    
    fileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
};

/**
 * Utilitaires AJAX
 */
Guldagil.api = {
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
            config.body = JSON.stringify(config.data);
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
            console.error('API Error:', error);
            throw error;
        }
    },
    
    get(url, params = {}) {
        const urlParams = new URLSearchParams(params);
        const fullUrl = urlParams.toString() ? `${url}?${urlParams}` : url;
        return this.request(fullUrl);
    },
    
    post(url, data) {
        return this.request(url, {
            method: 'POST',
            data: data
        });
    },
    
    put(url, data) {
        return this.request(url, {
            method: 'PUT',
            data: data
        });
    },
    
    delete(url) {
        return this.request(url, {
            method: 'DELETE'
        });
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
        form.querySelectorAll('.form-field.error').forEach(field => {
            field.classList.remove('error');
        });
        
        // Afficher les nouvelles erreurs
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"], #${fieldName}`);
            if (field) {
                const formField = field.closest('.form-field');
                if (formField) {
                    formField.classList.add('error');
                    
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'form-error';
                    errorDiv.textContent = errors[fieldName][0]; // Première erreur
                    formField.appendChild(errorDiv);
                }
            }
        });
    },
    
    serialize(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }
        
        return data;
    }
};

/**
 * Utilitaires de stockage local (avec fallback)
 */
Guldagil.storage = {
    set(key, value) {
        try {
            localStorage.setItem(`guldagil_${key}`, JSON.stringify(value));
            return true;
        } catch (e) {
            console.warn('LocalStorage non disponible, utilisation mémoire');
            this._memoryStorage = this._memoryStorage || {};
            this._memoryStorage[key] = value;
            return false;
        }
    },
    
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(`guldagil_${key}`);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            return (this._memoryStorage && this._memoryStorage[key]) || defaultValue;
        }
    },
    
    remove(key) {
        try {
            localStorage.removeItem(`guldagil_${key}`);
        } catch (e) {
            if (this._memoryStorage) {
                delete this._memoryStorage[key];
            }
        }
    },
    
    clear() {
        try {
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith('guldagil_')) {
                    localStorage.removeItem(key);
                }
            });
        } catch (e) {
            this._memoryStorage = {};
        }
    }
};

/**
 * Utilitaires de débounce/throttle
 */
Guldagil.utils.debounce = function(func, wait, immediate = false) {
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
};

Guldagil.utils.throttle = function(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
};

/**
 * Gestionnaire d'événements globaux
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
 * Gestionnaire de loading states
 */
Guldagil.loading = {
    show(target, message = 'Chargement...') {
        const element = typeof target === 'string' ? document.getElementById(target) : target;
        if (!element) return;
        
        const existing = element.querySelector('.loading-overlay');
        if (existing) return;
        
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loader loader-lg"></div>
                <div class="loading-text">${message}</div>
            </div>
        `;
        
        element.style.position = 'relative';
        element.appendChild(overlay);
        
        // Styles inline pour éviter les dépendances
        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            backdrop-filter: blur(2px);
        `;
        
        overlay.querySelector('.loading-content').style.cssText = `
            text-align: center;
            color: var(--text-secondary);
        `;
        
        overlay.querySelector('.loading-text').style.cssText = `
            margin-top: 1rem;
            font-size: 0.875rem;
        `;
    },
    
    hide(target) {
        const element = typeof target === 'string' ? document.getElementById(target) : target;
        if (!element) return;
        
        const overlay = element.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
};

/**
 * Utilitaires de navigation
 */
Guldagil.navigation = {
    goTo(url, newTab = false) {
        if (newTab) {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    },
    
    reload() {
        window.location.reload();
    },
    
    back() {
        window.history.back();
    },
    
    confirm(message, callback) {
        if (window.confirm(message)) {
            callback();
        }
    }
};

/**
 * Initialisation globale
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les sous-systèmes
    Guldagil.modals.init();
    
    // Logger le chargement
    if (Guldagil.debug) {
        console.log('🚀 Guldagil Global Utils chargés', {
            version: Guldagil.version,
            modules: Object.keys(Guldagil.modules),
            timestamp: new Date().toISOString()
        });
    }
    
    // Gestion globale des erreurs JavaScript
    window.addEventListener('error', function(e) {
        if (Guldagil.debug) {
            console.error('Erreur JavaScript globale:', e.error);
        }
    });
    
    // Event global pour les changements de thème
    window.addEventListener('themeChanged', function(e) {
        if (Guldagil.debug) {
            console.log('🎨 Thème changé:', e.detail.theme);
        }
    });
    
    // Raccourcis clavier globaux
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K : Recherche rapide (si implémentée)
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            Guldagil.events.emit('searchShortcut');
        }
        
        // Ctrl/Cmd + D : Toggle mode sombre
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            if (window.ThemeSwitcher) {
                window.ThemeSwitcher.toggleTheme();
            }
        }
    });
});

// Exposer les utilitaires globalement pour compatibilité
window.showAlert = (type, message, title) => Guldagil.notifications.show(type, message, title);
window.showSuccess = (message) => Guldagil.notifications.success(message);
window.showError = (message) => Guldagil.notifications.error(message);
window.showWarning = (message) => Guldagil.notifications.warning(message);
window.showInfo = (message) => Guldagil.notifications.info(message);

// Export pour usage modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Guldagil;
}

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
    
    post(url, data)

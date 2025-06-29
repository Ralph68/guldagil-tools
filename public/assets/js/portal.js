/**
 * Titre: Gestionnaire JavaScript du portail Guldagil
 * Chemin: /public/assets/js/portal.js
 * Version: 0.5 beta + build auto
 */

class PortalManager {
    constructor() {
        this.version = '0.5-beta';
        this.modules = new Map();
        this.initialized = false;
        this.debug = false;
    }

    /**
     * Initialisation principale du portail
     */
    init() {
        if (this.initialized) return;

        this.log('info', `🚀 Initialisation Portail Guldagil v${this.version}`);
        
        try {
            this.detectEnvironment();
            this.initializeModules();
            this.setupEventListeners();
            this.setupAccessibility();
            this.setupAnimations();
            this.checkModuleStatus();
            
            this.initialized = true;
            this.log('success', '✅ Portail initialisé avec succès');
            
            // Exposer l'API en mode debug
            if (this.debug) {
                window.PortalAPI = this.getDebugAPI();
            }
            
        } catch (error) {
            this.log('error', 'Erreur lors de l\'initialisation', error);
        }
    }

    /**
     * Détection de l'environnement
     */
    detectEnvironment() {
        this.debug = document.querySelector('meta[name="cache-control"]')?.content === 'no-cache';
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth <= 1024 && window.innerWidth > 768;
        this.isDesktop = window.innerWidth > 1024;
        
        // Ajouter classes CSS selon l'environnement
        document.body.classList.add(
            this.isMobile ? 'is-mobile' : 
            this.isTablet ? 'is-tablet' : 'is-desktop'
        );
        
        if (this.debug) {
            document.body.classList.add('debug-mode');
        }
    }

    /**
     * Initialisation des modules
     */
    initializeModules() {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach(card => {
            const moduleId = card.dataset.module;
            const moduleData = this.extractModuleData(card);
            
            this.modules.set(moduleId, {
                ...moduleData,
                element: card,
                initialized: false
            });
            
            this.initializeModuleCard(card, moduleData);
        });
        
        this.log('info', `📦 ${this.modules.size} modules détectés`);
    }

    /**
     * Extraction des données d'un module
     */
    extractModuleData(card) {
        const header = card.querySelector('.module-header');
        const icon = header?.querySelector('.module-icon .icon')?.textContent;
        const name = header?.querySelector('.module-name')?.textContent;
        const status = header?.querySelector('.module-status')?.textContent;
        const description = card.querySelector('.module-description')?.textContent;
        const button = card.querySelector('.module-button');
        const href = button?.href;
        
        return {
            icon,
            name,
            status,
            description,
            href,
            isActive: card.classList.contains('module-active'),
            isDevelopment: card.classList.contains('module-development')
        };
    }

    /**
     * Initialisation d'une carte module
     */
    initializeModuleCard(card, data) {
        const moduleId = card.dataset.module;
        
        // Gestionnaire de clic sur la carte entière
        card.addEventListener('click', (e) => {
            if (e.target.closest('.module-button')) return; // Ne pas déclencher si clic sur bouton
            
            const button = card.querySelector('.module-button[href]');
            if (button && !button.classList.contains('btn-disabled')) {
                this.navigateToModule(moduleId, button.href);
            }
        });
        
        // Gestionnaire du bouton
        const button = card.querySelector('.module-button[href]');
        if (button) {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigateToModule(moduleId, button.href);
            });
        }
        
        // Effet hover amélioré
        card.addEventListener('mouseenter', () => {
            if (data.isActive) {
                card.style.transform = 'translateY(-8px)';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    }

    /**
     * Navigation vers un module
     */
    navigateToModule(moduleId, href) {
        this.log('info', `📍 Navigation vers ${moduleId}`);
        
        const module = this.modules.get(moduleId);
        if (!module) {
            this.log('error', `Module ${moduleId} non trouvé`);
            return;
        }
        
        // Vérifier si le module est disponible
        if (module.isDevelopment) {
            this.showModuleNotAvailable(moduleId);
            return;
        }
        
        // Animation de chargement
        this.showLoadingState(moduleId);
        
        // Navigation différée pour l'animation
        setTimeout(() => {
            window.location.href = href;
        }, 300);
    }

    /**
     * Affichage état de chargement
     */
    showLoadingState(moduleId) {
        const card = this.modules.get(moduleId)?.element;
        if (!card) return;
        
        card.classList.add('loading');
        
        const button = card.querySelector('.module-button');
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="btn-text">Chargement...</span>';
            
            // Restaurer si navigation échoue
            setTimeout(() => {
                if (card.classList.contains('loading')) {
                    card.classList.remove('loading');
                    button.innerHTML = originalText;
                }
            }, 5000);
        }
    }

    /**
     * Module non disponible
     */
    showModuleNotAvailable(moduleId) {
        const module = this.modules.get(moduleId);
        const toast = this.createToast(
            'info',
            `${module.name} en développement`,
            'Ce module sera bientôt disponible. Merci de votre patience.',
            3000
        );
        this.showToast(toast);
    }

    /**
     * Configuration des event listeners
     */
    setupEventListeners() {
        // Responsive
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));
        
        // Navigation clavier
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });
        
        // Liens admin
        document.querySelectorAll('.admin-link, .admin-btn').forEach(link => {
            link.addEventListener('click', (e) => {
                if (link.href) {
                    this.handleAdminNavigation(e, link);
                }
            });
        });
        
        // Gestion des erreurs globales
        window.addEventListener('error', (e) => {
            this.log('error', 'Erreur JavaScript', e.error);
        });
    }

    /**
     * Gestion du redimensionnement
     */
    handleResize() {
        const wasMobile = this.isMobile;
        const wasTablet = this.isTablet;
        
        this.detectEnvironment();
        
        if (wasMobile !== this.isMobile || wasTablet !== this.isTablet) {
            this.log('info', '📱 Changement de format détecté');
            this.refreshLayout();
        }
    }

    /**
     * Navigation clavier
     */
    handleKeyboardNavigation(e) {
        // Échap pour fermer les toasts
        if (e.key === 'Escape') {
            this.closeAllToasts();
        }
        
        // Raccourcis clavier
        if (e.altKey) {
            switch (e.key) {
                case '1':
                    e.preventDefault();
                    this.navigateToModule('calculateur', 'calculateur/');
                    break;
                case '2':
                    e.preventDefault();
                    this.navigateToModule('adr', 'adr/');
                    break;
                case 'a':
                    e.preventDefault();
                    window.location.href = 'admin/';
                    break;
            }
        }
    }

    /**
     * Navigation admin
     */
    handleAdminNavigation(e, link) {
        // Confirmation pour certaines actions critiques
        if (link.href.includes('maintenance')) {
            e.preventDefault();
            this.confirmAdminAction(
                'Accéder à la maintenance',
                'Vous accédez aux outils de maintenance système. Continuer ?',
                () => window.location.href = link.href
            );
        }
    }

    /**
     * Configuration de l'accessibilité
     */
    setupAccessibility() {
        // ARIA labels dynamiques
        this.modules.forEach((module, moduleId) => {
            const card = module.element;
            card.setAttribute('role', 'article');
            card.setAttribute('aria-label', `Module ${module.name} - ${module.status}`);
            
            const button = card.querySelector('.module-button');
            if (button && !button.hasAttribute('aria-label')) {
                button.setAttribute('aria-label', `Accéder au module ${module.name}`);
            }
        });
        
        // Focus visible amélioré
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    }

    /**
     * Configuration des animations
     */
    setupAnimations() {
        // Intersection Observer pour les animations au scroll
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });
            
            // Observer les cartes modules
            document.querySelectorAll('.module-card').forEach(card => {
                observer.observe(card);
            });
        }
        
        // Préférences utilisateur pour les animations
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('reduced-motion');
        }
    }

    /**
     * Vérification du statut des modules
     */
    async checkModuleStatus() {
        if (!this.debug) return;
        
        try {
            // Vérification simple de disponibilité (ping)
            for (const [moduleId, module] of this.modules) {
                if (module.isActive && module.href) {
                    this.pingModule(moduleId, module.href);
                }
            }
        } catch (error) {
            this.log('error', 'Erreur vérification modules', error);
        }
    }

    /**
     * Ping d'un module
     */
    async pingModule(moduleId, href) {
        try {
            const response = await fetch(href, { 
                method: 'HEAD',
                cache: 'no-cache'
            });
            
            const module = this.modules.get(moduleId);
            if (module && module.element) {
                if (response.ok) {
                    module.element.classList.add('module-online');
                } else {
                    module.element.classList.add('module-offline');
                    this.log('warning', `Module ${moduleId} inaccessible (${response.status})`);
                }
            }
        } catch (error) {
            this.log('warning', `Module ${moduleId} ping failed`, error);
        }
    }

    /**
     * Rafraîchissement du layout
     */
    refreshLayout() {
        // Force reflow pour les animations CSS
        document.body.offsetHeight;
        
        // Réinitialiser les styles inline des cartes
        document.querySelectorAll('.module-card').forEach(card => {
            card.style.transform = '';
        });
    }

    /**
     * Système de toast notifications
     */
    createToast(type, title, message, duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-header">
                <span class="toast-icon">${this.getToastIcon(type)}</span>
                <span class="toast-title">${title}</span>
                <button class="toast-close" aria-label="Fermer">&times;</button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        // Gestionnaire de fermeture
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this.closeToast(toast);
        });
        
        // Auto-fermeture
        if (duration > 0) {
            setTimeout(() => this.closeToast(toast), duration);
        }
        
        return toast;
    }

    /**
     * Affichage d'un toast
     */
    showToast(toast) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Animation d'entrée
        setTimeout(() => toast.classList.add('show'), 10);
    }

    /**
     * Fermeture d'un toast
     */
    closeToast(toast) {
        toast.classList.add('hide');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    /**
     * Fermeture de tous les toasts
     */
    closeAllToasts() {
        document.querySelectorAll('.toast').forEach(toast => {
            this.closeToast(toast);
        });
    }

    /**
     * Icônes des toasts
     */
    getToastIcon(type) {
        const icons = {
            info: 'ℹ️',
            success: '✅',
            warning: '⚠️',
            error: '❌'
        };
        return icons[type] || icons.info;
    }

    /**
     * Confirmation d'action admin
     */
    confirmAdminAction(title, message, callback) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3>${title}</h3>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary modal-cancel">Annuler</button>
                    <button class="btn btn-primary modal-confirm">Continuer</button>
                </div>
            </div>
        `;
        
        // Gestionnaires
        modal.querySelector('.modal-cancel').addEventListener('click', () => {
            this.closeModal(modal);
        });
        
        modal.querySelector('.modal-confirm').addEventListener('click', () => {
            this.closeModal(modal);
            callback();
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal(modal);
            }
        });
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);
    }

    /**
     * Fermeture de modal
     */
    closeModal(modal) {
        modal.classList.add('hide');
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    }

    /**
     * Système de logging
     */
    log(level, message, data = null) {
        if (!this.debug && level === 'info') return;
        
        const timestamp = new Date().toLocaleTimeString();
        const prefix = `[${timestamp}] Portal:`;
        
        switch (level) {
            case 'error':
                console.error(prefix, message, data);
                break;
            case 'warning':
                console.warn(prefix, message, data);
                break;
            case 'success':
                console.log(`%c${prefix} ${message}`, 'color: green; font-weight: bold', data);
                break;
            default:
                console.log(prefix, message, data);
        }
    }

    /**
     * Utilitaire debounce
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * API de debug
     */
    getDebugAPI() {
        return {
            version: this.version,
            modules: this.modules,
            showToast: (type, title, message) => {
                const toast = this.createToast(type, title, message);
                this.showToast(toast);
            },
            pingAllModules: () => this.checkModuleStatus(),
            refreshLayout: () => this.refreshLayout(),
            getStats: () => ({
                modulesCount: this.modules.size,
                activeModules: Array.from(this.modules.values()).filter(m => m.isActive).length,
                environment: {
                    isMobile: this.isMobile,
                    isTablet: this.isTablet,
                    isDesktop: this.isDesktop,
                    debug: this.debug
                }
            })
        };
    }
}

// Initialisation automatique et exposition globale
const PortalManager = new PortalManager();
window.PortalManager = PortalManager;

// Auto-initialisation au chargement DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => PortalManager.init());
} else {
    PortalManager.init();
}

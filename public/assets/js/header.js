/**
 * Titre: JavaScript Header - Interactions modernes et optimisées
 * Chemin: /assets/js/header.js
 * Version: 0.5 beta + build auto
 * 
 * Features:
 * - Menu utilisateur dropdown
 * - Navigation mobile responsive
 * - Raccourcis clavier
 * - Accessibilité ARIA
 * - Performance optimisée
 * - Event delegation
 */

(function() {
    'use strict';

    // === CONFIGURATION ===
    const CONFIG = {
        selectors: {
            userMenuTrigger: '#userMenuTrigger',
            userDropdown: '#userDropdown',
            mobileMenuToggle: '#mobileMenuToggle',
            modulesNav: '.modules-nav',
            debugBanner: '.debug-banner'
        },
        classes: {
            show: 'show',
            open: 'open',
            mobileOpen: 'mobile-open',
            hidden: 'hidden'
        },
        keyCodes: {
            ESCAPE: 'Escape',
            ENTER: 'Enter',
            SPACE: ' ',
            ARROW_DOWN: 'ArrowDown',
            ARROW_UP: 'ArrowUp',
            TAB: 'Tab'
        },
        delays: {
            debounce: 250,
            hideDelay: 150
        }
    };

    // === UTILITAIRES ===
    
    /**
     * Debounce function pour optimiser les performances
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function pour les événements fréquents
     */
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Sélecteur sécurisé avec vérification
     */
    function safeQuerySelector(selector) {
        try {
            return document.querySelector(selector);
        } catch (error) {
            console.warn(`Sélecteur invalide: ${selector}`, error);
            return null;
        }
    }

    /**
     * Gestion des focus trap pour accessibilité
     */
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        element.addEventListener('keydown', (e) => {
            if (e.key === CONFIG.keyCodes.TAB) {
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        lastElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        firstElement.focus();
                        e.preventDefault();
                    }
                }
            }
        });

        return {
            activate: () => firstElement?.focus(),
            deactivate: () => element.blur()
        };
    }

    // === CLASSE MENU UTILISATEUR ===
    class UserMenu {
        constructor() {
            this.trigger = safeQuerySelector(CONFIG.selectors.userMenuTrigger);
            this.dropdown = safeQuerySelector(CONFIG.selectors.userDropdown);
            this.isOpen = false;
            this.hideTimeout = null;
            this.focusTrap = null;

            if (this.trigger && this.dropdown) {
                this.init();
            }
        }

        init() {
            this.bindEvents();
            this.setupAccessibility();
            this.focusTrap = trapFocus(this.dropdown);
        }

        bindEvents() {
            // Click sur le trigger
            this.trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            });

            // Gestion clavier sur le trigger
            this.trigger.addEventListener('keydown', (e) => {
                if (e.key === CONFIG.keyCodes.ENTER || e.key === CONFIG.keyCodes.SPACE) {
                    e.preventDefault();
                    this.toggle();
                } else if (e.key === CONFIG.keyCodes.ARROW_DOWN) {
                    e.preventDefault();
                    this.show();
                    this.focusFirstItem();
                }
            });

            // Fermeture avec échap
            document.addEventListener('keydown', (e) => {
                if (e.key === CONFIG.keyCodes.ESCAPE && this.isOpen) {
                    this.hide();
                    this.trigger.focus();
                }
            });

            // Clic extérieur
            document.addEventListener('click', (e) => {
                if (this.isOpen && !this.dropdown.contains(e.target) && !this.trigger.contains(e.target)) {
                    this.hide();
                }
            });

            // Navigation clavier dans le dropdown
            this.dropdown.addEventListener('keydown', (e) => this.handleDropdownKeydown(e));

            // Hover pour améliorer l'UX
            this.setupHoverBehavior();
        }

        setupAccessibility() {
            // ARIA attributes
            this.trigger.setAttribute('aria-haspopup', 'true');
            this.trigger.setAttribute('aria-expanded', 'false');
            this.dropdown.setAttribute('role', 'menu');
            this.dropdown.setAttribute('aria-hidden', 'true');

            // ID unique pour aria-controls
            if (!this.dropdown.id) {
                this.dropdown.id = 'user-dropdown-' + Math.random().toString(36).substr(2, 9);
            }
            this.trigger.setAttribute('aria-controls', this.dropdown.id);

            // Role pour les items
            const items = this.dropdown.querySelectorAll('.dropdown-item');
            items.forEach(item => {
                item.setAttribute('role', 'menuitem');
                item.setAttribute('tabindex', '-1');
            });
        }

        setupHoverBehavior() {
            // Hover pour prévisualiser
            this.trigger.addEventListener('mouseenter', () => {
                if (!this.isOpen) {
                    clearTimeout(this.hideTimeout);
                    this.previewShow();
                }
            });

            this.trigger.addEventListener('mouseleave', () => {
                if (!this.isOpen) {
                    this.hideTimeout = setTimeout(() => this.previewHide(), CONFIG.delays.hideDelay);
                }
            });

            this.dropdown.addEventListener('mouseenter', () => {
                clearTimeout(this.hideTimeout);
            });

            this.dropdown.addEventListener('mouseleave', () => {
                if (!this.isOpen) {
                    this.hideTimeout = setTimeout(() => this.previewHide(), CONFIG.delays.hideDelay);
                }
            });
        }

        previewShow() {
            this.dropdown.style.opacity = '0.7';
            this.dropdown.style.visibility = 'visible';
            this.dropdown.style.transform = 'translateY(0) scale(1)';
            this.dropdown.style.pointerEvents = 'none';
        }

        previewHide() {
            if (!this.isOpen) {
                this.dropdown.style.opacity = '0';
                this.dropdown.style.visibility = 'hidden';
                this.dropdown.style.transform = 'translateY(-10px) scale(0.95)';
            }
        }

        toggle() {
            this.isOpen ? this.hide() : this.show();
        }

        show() {
            this.isOpen = true;
            clearTimeout(this.hideTimeout);
            
            // Mise à jour ARIA
            this.trigger.setAttribute('aria-expanded', 'true');
            this.dropdown.setAttribute('aria-hidden', 'false');
            
            // Animation
            this.dropdown.classList.add(CONFIG.classes.show);
            this.dropdown.style.opacity = '1';
            this.dropdown.style.visibility = 'visible';
            this.dropdown.style.transform = 'translateY(0) scale(1)';
            this.dropdown.style.pointerEvents = 'auto';

            // Focus management
            this.focusTrap.activate();

            // Event personnalisé
            this.trigger.dispatchEvent(new CustomEvent('userMenuOpen', {
                bubbles: true,
                detail: { menu: this }
            }));
        }

        hide() {
            this.isOpen = false;
            
            // Mise à jour ARIA
            this.trigger.setAttribute('aria-expanded', 'false');
            this.dropdown.setAttribute('aria-hidden', 'true');
            
            // Animation
            this.dropdown.classList.remove(CONFIG.classes.show);
            this.dropdown.style.opacity = '0';
            this.dropdown.style.visibility = 'hidden';
            this.dropdown.style.transform = 'translateY(-10px) scale(0.95)';

            // Désactiver focus trap
            this.focusTrap.deactivate();

            // Event personnalisé
            this.trigger.dispatchEvent(new CustomEvent('userMenuClose', {
                bubbles: true,
                detail: { menu: this }
            }));
        }

        handleDropdownKeydown(e) {
            const items = Array.from(this.dropdown.querySelectorAll('.dropdown-item'));
            const currentIndex = items.indexOf(document.activeElement);

            switch (e.key) {
                case CONFIG.keyCodes.ARROW_DOWN:
                    e.preventDefault();
                    const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                    items[nextIndex].focus();
                    break;

                case CONFIG.keyCodes.ARROW_UP:
                    e.preventDefault();
                    const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                    items[prevIndex].focus();
                    break;

                case CONFIG.keyCodes.ENTER:
                case CONFIG.keyCodes.SPACE:
                    e.preventDefault();
                    if (document.activeElement.href) {
                        document.activeElement.click();
                    }
                    break;
            }
        }

        focusFirstItem() {
            const firstItem = this.dropdown.querySelector('.dropdown-item');
            if (firstItem) {
                firstItem.focus();
            }
        }
    }

    // === CLASSE MENU MOBILE ===
    class MobileMenu {
        constructor() {
            this.toggle = safeQuerySelector(CONFIG.selectors.mobileMenuToggle);
            this.nav = safeQuerySelector(CONFIG.selectors.modulesNav);
            this.isOpen = false;

            if (this.toggle && this.nav) {
                this.init();
            }
        }

        init() {
            this.bindEvents();
            this.setupAccessibility();
        }

        bindEvents() {
            this.toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleMenu();
            });

            // Fermeture avec échap
            document.addEventListener('keydown', (e) => {
                if (e.key === CONFIG.keyCodes.ESCAPE && this.isOpen) {
                    this.closeMenu();
                }
            });

            // Fermeture sur resize si desktop
            window.addEventListener('resize', debounce(() => {
                if (window.innerWidth > 768 && this.isOpen) {
                    this.closeMenu();
                }
            }, CONFIG.delays.debounce));

            // Clic extérieur sur mobile
            document.addEventListener('click', (e) => {
                if (this.isOpen && 
                    !this.nav.contains(e.target) && 
                    !this.toggle.contains(e.target)) {
                    this.closeMenu();
                }
            });
        }

        setupAccessibility() {
            this.toggle.setAttribute('aria-expanded', 'false');
            this.toggle.setAttribute('aria-controls', 'modules-nav-items');
            this.toggle.setAttribute('aria-label', 'Menu modules');
        }

        toggleMenu() {
            this.isOpen ? this.closeMenu() : this.openMenu();
        }

        openMenu() {
            this.isOpen = true;
            this.nav.classList.add(CONFIG.classes.mobileOpen);
            this.toggle.classList.add(CONFIG.classes.open);
            this.toggle.setAttribute('aria-expanded', 'true');

            // Empêcher le scroll du body
            document.body.style.overflow = 'hidden';

            // Animation fluide
            requestAnimationFrame(() => {
                const items = this.nav.querySelector('.modules-nav-items');
                if (items) {
                    items.style.transform = 'translateY(0)';
                    items.style.opacity = '1';
                }
            });

            // Event personnalisé
            this.toggle.dispatchEvent(new CustomEvent('mobileMenuOpen', {
                bubbles: true,
                detail: { menu: this }
            }));
        }

        closeMenu() {
            this.isOpen = false;
            this.nav.classList.remove(CONFIG.classes.mobileOpen);
            this.toggle.classList.remove(CONFIG.classes.open);
            this.toggle.setAttribute('aria-expanded', 'false');

            // Restaurer le scroll
            document.body.style.overflow = '';

            // Event personnalisé
            this.toggle.dispatchEvent(new CustomEvent('mobileMenuClose', {
                bubbles: true,
                detail: { menu: this }
            }));
        }
    }

    // === GESTIONNAIRE RACCOURCIS CLAVIER ===
    class KeyboardShortcuts {
        constructor() {
            this.shortcuts = new Map([
                ['Alt+m', () => this.toggleMobileMenu()],
                ['Alt+u', () => this.toggleUserMenu()],
                ['Alt+h', () => this.goHome()],
                ['Alt+a', () => this.goToAdmin()],
                ['Escape', () => this.closeAllMenus()]
            ]);

            this.init();
        }

        init() {
            document.addEventListener('keydown', (e) => this.handleKeydown(e));
        }

        handleKeydown(e) {
            // Ignorer si on est dans un input
            if (e.target.matches('input, textarea, select, [contenteditable]')) {
                return;
            }

            const combo = this.getKeyCombo(e);
            const action = this.shortcuts.get(combo);

            if (action) {
                e.preventDefault();
                action();
            }
        }

        getKeyCombo(e) {
            const parts = [];
            if (e.altKey) parts.push('Alt');
            if (e.ctrlKey) parts.push('Ctrl');
            if (e.shiftKey) parts.push('Shift');
            if (e.metaKey) parts.push('Meta');
            
            if (e.key !== 'Alt' && e.key !== 'Control' && e.key !== 'Shift' && e.key !== 'Meta') {
                parts.push(e.key);
            }

            return parts.join('+');
        }

        toggleMobileMenu() {
            const mobileMenu = window.HeaderApp?.mobileMenu;
            if (mobileMenu) {
                mobileMenu.toggleMenu();
            }
        }

        toggleUserMenu() {
            const userMenu = window.HeaderApp?.userMenu;
            if (userMenu) {
                userMenu.toggle();
            }
        }

        goHome() {
            window.location.href = '/';
        }

        goToAdmin() {
            const adminLink = document.querySelector('a[href*="/admin"]');
            if (adminLink) {
                window.location.href = adminLink.href;
            }
        }

        closeAllMenus() {
            const userMenu = window.HeaderApp?.userMenu;
            const mobileMenu = window.HeaderApp?.mobileMenu;
            
            if (userMenu?.isOpen) {
                userMenu.hide();
            }
            if (mobileMenu?.isOpen) {
                mobileMenu.closeMenu();
            }
        }
    }

    // === GESTIONNAIRE NOTIFICATIONS ===
    class NotificationManager {
        constructor() {
            this.container = this.createContainer();
            this.notifications = new Map();
            this.init();
        }

        createContainer() {
            const container = document.createElement('div');
            container.className = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                pointer-events: none;
            `;
            document.body.appendChild(container);
            return container;
        }

        init() {
            // Écouter les événements personnalisés
            document.addEventListener('userMenuOpen', () => {
                this.show('Menu utilisateur ouvert', 'info', 2000);
            });

            document.addEventListener('mobileMenuOpen', () => {
                this.show('Menu mobile ouvert', 'info', 2000);
            });
        }

        show(message, type = 'info', duration = 5000) {
            const id = Date.now().toString();
            const notification = this.createElement(message, type, id);
            
            this.container.appendChild(notification);
            this.notifications.set(id, notification);

            // Animation d'entrée
            requestAnimationFrame(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            });

            // Auto-suppression
            if (duration > 0) {
                setTimeout(() => this.hide(id), duration);
            }

            return id;
        }

        createElement(message, type, id) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.dataset.id = id;
            notification.style.cssText = `
                background: white;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                margin-bottom: 8px;
                transform: translateX(100%);
                opacity: 0;
                transition: all 0.3s ease;
                pointer-events: auto;
                border-left: 4px solid ${this.getTypeColor(type)};
                font-size: 14px;
                max-width: 300px;
            `;

            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 16px;">${this.getTypeIcon(type)}</span>
                    <span>${message}</span>
                    <button onclick="window.HeaderApp.notifications.hide('${id}')" 
                            style="background: none; border: none; font-size: 18px; cursor: pointer; margin-left: auto;">×</button>
                </div>
            `;

            return notification;
        }

        getTypeColor(type) {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            return colors[type] || colors.info;
        }

        getTypeIcon(type) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            return icons[type] || icons.info;
        }

        hide(id) {
            const notification = this.notifications.get(id);
            if (notification) {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                    this.notifications.delete(id);
                }, 300);
            }
        }
    }

    // === GESTIONNAIRE PERFORMANCE ===
    class PerformanceMonitor {
        constructor() {
            this.metrics = {
                loadTime: 0,
                interactionTime: 0,
                memoryUsage: 0
            };
            this.init();
        }

        init() {
            this.measureLoadTime();
            this.setupInteractionTracking();
            this.setupMemoryMonitoring();
        }

        measureLoadTime() {
            if (performance.mark) {
                performance.mark('header-init-start');
                
                window.addEventListener('load', () => {
                    performance.mark('header-init-end');
                    performance.measure('header-load', 'header-init-start', 'header-init-end');
                    
                    const measure = performance.getEntriesByName('header-load')[0];
                    this.metrics.loadTime = measure.duration;
                    
                    if (this.metrics.loadTime > 100) {
                        console.warn(`Header load time: ${this.metrics.loadTime.toFixed(2)}ms (slow)`);
                    }
                });
            }
        }

        setupInteractionTracking() {
            const startTime = performance.now();
            
            document.addEventListener('click', throttle(() => {
                this.metrics.interactionTime = performance.now() - startTime;
            }, 1000), { once: true });
        }

        setupMemoryMonitoring() {
            if (performance.memory) {
                setInterval(() => {
                    this.metrics.memoryUsage = performance.memory.usedJSHeapSize;
                    
                    // Alerter si utilisation mémoire excessive
                    if (this.metrics.memoryUsage > 50 * 1024 * 1024) { // 50MB
                        console.warn('Header: Utilisation mémoire élevée détectée');
                    }
                }, 30000); // Vérifier toutes les 30 secondes
            }
        }

        getMetrics() {
            return { ...this.metrics };
        }
    }

    // === CLASSE PRINCIPALE HEADER APP ===
    class HeaderApp {
        constructor() {
            this.userMenu = null;
            this.mobileMenu = null;
            this.shortcuts = null;
            this.notifications = null;
            this.performance = null;
            this.initialized = false;
        }

        async init() {
            if (this.initialized) return;

            try {
                // Attendre que le DOM soit prêt
                if (document.readyState === 'loading') {
                    await new Promise(resolve => {
                        document.addEventListener('DOMContentLoaded', resolve, { once: true });
                    });
                }

                // Initialiser les composants
                this.performance = new PerformanceMonitor();
                this.userMenu = new UserMenu();
                this.mobileMenu = new MobileMenu();
                this.shortcuts = new KeyboardShortcuts();
                this.notifications = new NotificationManager();

                // Setup des événements globaux
                this.setupGlobalEvents();
                
                // Masquer la bannière debug après 5 secondes
                this.setupDebugBanner();

                this.initialized = true;
                
                // Event personnalisé pour signaler l'initialisation
                document.dispatchEvent(new CustomEvent('headerReady', {
                    detail: { app: this }
                }));

                console.log('🚀 Header App initialisé avec succès');

            } catch (error) {
                console.error('❌ Erreur lors de l\'initialisation du Header:', error);
                this.notifications?.show('Erreur d\'initialisation du header', 'error');
            }
        }

        setupGlobalEvents() {
            // Gestion du scroll pour header sticky
            let lastScrollY = window.scrollY;
            
            window.addEventListener('scroll', throttle(() => {
                const currentScrollY = window.scrollY;
                const header = document.querySelector('.portal-header');
                
                if (header) {
                    if (currentScrollY > lastScrollY && currentScrollY > 100) {
                        // Scroll vers le bas - masquer header
                        header.style.transform = 'translateY(-100%)';
                    } else {
                        // Scroll vers le haut - afficher header
                        header.style.transform = 'translateY(0)';
                    }
                }
                
                lastScrollY = currentScrollY;
            }, 16)); // ~60fps

            // Gestion des focus pour accessibilité
            document.addEventListener('focusin', (e) => {
                if (e.target.matches('.module-nav-item, .dropdown-item')) {
                    e.target.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'nearest' 
                    });
                }
            });

            // Gestion des erreurs JavaScript
            window.addEventListener('error', (e) => {
                if (e.filename?.includes('header')) {
                    this.notifications?.show('Erreur dans le header détectée', 'error');
                    console.error('Header Error:', e.error);
                }
            });
        }

        setupDebugBanner() {
            const debugBanner = safeQuerySelector(CONFIG.selectors.debugBanner);
            if (debugBanner) {
                // Auto-masquage après 5 secondes
                setTimeout(() => {
                    debugBanner.style.transform = 'translateY(-100%)';
                    setTimeout(() => {
                        debugBanner.style.display = 'none';
                    }, 300);
                }, 5000);

                // Clic pour masquer immédiatement
                debugBanner.addEventListener('click', () => {
                    debugBanner.style.transform = 'translateY(-100%)';
                    setTimeout(() => {
                        debugBanner.style.display = 'none';
                    }, 300);
                });
            }
        }

        // API publique
        showNotification(message, type = 'info', duration = 5000) {
            return this.notifications?.show(message, type, duration);
        }

        hideNotification(id) {
            this.notifications?.hide(id);
        }

        getPerformanceMetrics() {
            return this.performance?.getMetrics() || {};
        }

        toggleUserMenu() {
            this.userMenu?.toggle();
        }

        toggleMobileMenu() {
            this.mobileMenu?.toggleMenu();
        }

        destroy() {
            // Nettoyer les event listeners et références
            this.userMenu = null;
            this.mobileMenu = null;
            this.shortcuts = null;
            this.notifications = null;
            this.performance = null;
            this.initialized = false;
        }
    }

    // === FONCTIONS UTILITAIRES GLOBALES ===
    
    /**
     * Fonction d'aide pour l'accessibilité
     */
    window.showHelp = function() {
        const module = document.body.dataset.module || 'unknown';
        const version = document.querySelector('meta[name="version"]')?.content || 'unknown';
        const build = document.querySelector('meta[name="build"]')?.content || 'unknown';
        
        const helpMessage = `Aide contextuelle\n\nModule: ${module}\nVersion: ${version}\nBuild: ${build}\n\nRaccourcis clavier:\n• Alt+M: Menu mobile\n• Alt+U: Menu utilisateur\n• Alt+H: Accueil\n• Alt+A: Administration\n• Echap: Fermer menus`;
        
        if (window.HeaderApp?.notifications) {
            window.HeaderApp.showNotification('Aide affichée en console', 'info');
            console.info(helpMessage);
        } else {
            alert(helpMessage);
        }
    };

    // === INITIALISATION ===
    
    // Créer l'instance globale
    window.HeaderApp = new HeaderApp();
    
    // Auto-initialisation
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.HeaderApp.init();
        });
    } else {
        window.HeaderApp.init();
    }

    // Nettoyage lors du déchargement
    window.addEventListener('beforeunload', () => {
        window.HeaderApp?.destroy();
    });

    // Export pour usage en module (si nécessaire)
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { HeaderApp, UserMenu, MobileMenu, NotificationManager };
    }

})();
/**
 * Gestionnaire JavaScript pour breadcrumb sticky optimisé
 * Maintient le fil d'ariane visible même quand le header se cache
 */

class BreadcrumbStickyManager {
    constructor() {
        this.breadcrumbNav = document.querySelector('.breadcrumb-nav.sticky');
        this.lastScrollY = window.scrollY;
        this.headerHeight = 120; // Hauteur approximative du header
        this.isHeaderHidden = false;
        
        if (this.breadcrumbNav) {
            this.init();
        }
    }

    init() {
        this.setupScrollListener();
        this.updateStickyPosition();
    }

    setupScrollListener() {
        let scrollTimeout;
        
        window.addEventListener('scroll', () => {
            if (!scrollTimeout) {
                scrollTimeout = setTimeout(() => {
                    this.handleScroll();
                    scrollTimeout = null;
                }, 16); // ~60fps
            }
        });
    }

    handleScroll() {
        const currentScrollY = window.scrollY;
        const header = document.querySelector('.portal-header');
        
        // Détection si le header est masqué
        if (header) {
            const headerRect = header.getBoundingClientRect();
            this.isHeaderHidden = headerRect.bottom <= 0;
        }
        
        // Mise à jour de la position sticky du breadcrumb
        this.updateStickyPosition();
        
        // Ajout de la classe 'scrolled' pour les styles
        if (currentScrollY > 50) {
            this.breadcrumbNav.classList.add('scrolled');
        } else {
            this.breadcrumbNav.classList.remove('scrolled');
        }
        
        this.lastScrollY = currentScrollY;
    }

    updateStickyPosition() {
        if (!this.breadcrumbNav) return;
        
        // Si le header est masqué, le breadcrumb colle en haut
        if (this.isHeaderHidden) {
            this.breadcrumbNav.style.top = '0px';
        } else {
            // Sinon, il se positionne sous le header modules (56px)
            this.breadcrumbNav.style.top = '56px';
        }
    }
}

// Extension de la classe HeaderManager existante
if (typeof HeaderManager !== 'undefined') {
    const originalSetupScrollBehavior = HeaderManager.prototype.setupScrollBehavior;
    
    HeaderManager.prototype.setupScrollBehavior = function() {
        // Appel de la méthode originale
        if (originalSetupScrollBehavior) {
            originalSetupScrollBehavior.call(this);
        }
        
        // Ajout du gestionnaire breadcrumb
        this.breadcrumbManager = new BreadcrumbStickyManager();
    };
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    // Si HeaderManager n'existe pas encore, initialiser directement
    if (typeof HeaderManager === 'undefined') {
        new BreadcrumbStickyManager();
    }
});

// Export pour réutilisation
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BreadcrumbStickyManager };
}

/**
 * Titre: JavaScript Header + Menu modules - Version fusionnée exhaustive
 * Chemin: /assets/js/header.js
 * Version: 0.5 beta + build auto
 */

// ================== HEADER UTILISATEUR ==================
class HeaderManager {
    constructor() {
        this.userMenuTrigger = document.querySelector('.user-menu-trigger');
        this.userDropdown = document.querySelector('.user-dropdown');
        this.isDropdownOpen = false;
        this.init();
    }
    init() {
        this.bindEvents();
        this.setupAccessibility();
    }
    bindEvents() {
        // Toggle menu utilisateur
        if (this.userMenuTrigger) {
            this.userMenuTrigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleUserMenu();
            });
        }
        // Fermer menu en cliquant ailleurs
        document.addEventListener('click', (e) => {
            if (this.isDropdownOpen && !this.userMenuTrigger?.contains(e.target) && !this.userDropdown?.contains(e.target)) {
                this.closeUserMenu();
            }
        });
        // Raccourcis clavier
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isDropdownOpen) {
                this.closeUserMenu();
            }
        });
    }
    setupAccessibility() {
        if (this.userMenuTrigger) {
            this.userMenuTrigger.setAttribute('aria-haspopup', 'true');
            this.userMenuTrigger.setAttribute('aria-expanded', 'false');
        }
        if (this.userDropdown) {
            this.userDropdown.setAttribute('role', 'menu');
            this.userDropdown.setAttribute('aria-hidden', 'true');
        }
    }
    toggleUserMenu() {
        if (this.isDropdownOpen) {
            this.closeUserMenu();
        } else {
            this.openUserMenu();
        }
    }
    openUserMenu() {
        if (!this.userDropdown) return;
        this.isDropdownOpen = true;
        this.userDropdown.classList.add('active');
        if (this.userMenuTrigger) {
            this.userMenuTrigger.setAttribute('aria-expanded', 'true');
        }
        this.userDropdown.setAttribute('aria-hidden', 'false');
        // Focus premier élément du menu
        const firstMenuItem = this.userDropdown.querySelector('.dropdown-item');
        if (firstMenuItem) {
            setTimeout(() => firstMenuItem.focus(), 100);
        }
    }
    closeUserMenu() {
        if (!this.userDropdown) return;
        this.isDropdownOpen = false;
        this.userDropdown.classList.remove('active');
        if (this.userMenuTrigger) {
            this.userMenuTrigger.setAttribute('aria-expanded', 'false');
        }
        this.userDropdown.setAttribute('aria-hidden', 'true');
    }
}

// ================== BREADCRUMB MANAGER ==================
class BreadcrumbManager {
    constructor() {
        this.breadcrumbNav = document.querySelector('.breadcrumb-nav');
        this.init();
    }
    init() {
        this.addBreadcrumbInteractions();
    }
    addBreadcrumbInteractions() {
        const breadcrumbItems = document.querySelectorAll('.breadcrumb-item');
        breadcrumbItems.forEach(item => {
            item.addEventListener('mouseenter', () => {
                if (!item.classList.contains('active')) {
                    item.style.transform = 'translateX(2px)';
                }
            });
            item.addEventListener('mouseleave', () => {
                item.style.transform = '';
            });
        });
    }
}

// ================== HEADER NOTIFICATIONS ==================
class HeaderNotifications {
    constructor() {
        this.container = null;
        this.createContainer();
    }
    createContainer() {
        this.container = document.createElement('div');
        this.container.className = 'header-notifications';
        this.container.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1050;
            max-width: 350px;
        `;
        document.body.appendChild(this.container);
    }
    show(message, type = 'info', duration = 4000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3182ce'
        };
        notification.style.cssText = `
            background: white;
            border-left: 4px solid ${colors[type]};
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            margin-bottom: 0.5rem;
            transform: translateX(100%);
            transition: all 0.3s ease-out;
            opacity: 0;
        `;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.25rem;">${icons[type]}</span>
                <span style="flex: 1; color: #374151; font-size: 0.875rem;">${message}</span>
                <button style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
        `;
        // Bouton fermer
        const closeBtn = notification.querySelector('button');
        closeBtn.addEventListener('click', () => this.remove(notification));
        this.container.appendChild(notification);
        // Animation d'entrée
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        });
        // Auto-suppression
        if (duration > 0) {
            setTimeout(() => this.remove(notification), duration);
        }
        return notification;
    }
    remove(notification) {
        if (!notification || !notification.parentNode) return;
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
    success(message, duration) { return this.show(message, 'success', duration);}
    error(message, duration) { return this.show(message, 'error', duration);}
    warning(message, duration) { return this.show(message, 'warning', duration);}
    info(message, duration) { return this.show(message, 'info', duration);}
}

// ================== MENU MODULES NAVIGATION + MOBILE ==================
document.addEventListener('DOMContentLoaded', function() {
    // ---- HEADER CLASSES ----
    window.headerManager = new HeaderManager();
    window.breadcrumbManager = new BreadcrumbManager();
    window.headerNotifications = new HeaderNotifications();

    // ---- MENU BURGER MOBILE ----
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const modulesList = document.querySelector('.modules-list');
    if (mobileToggle && modulesList) {
        mobileToggle.addEventListener('click', function() {
            const isOpen = modulesList.classList.contains('open');
            if (isOpen) {
                modulesList.classList.remove('open');
                mobileToggle.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
            } else {
                modulesList.classList.add('open');
                mobileToggle.classList.add('active');
                mobileToggle.setAttribute('aria-expanded', 'true');
            }
        });
        // Fermer le menu si clic ailleurs
        document.addEventListener('click', function(e) {
            if (!mobileToggle.contains(e.target) && !modulesList.contains(e.target)) {
                modulesList.classList.remove('open');
                mobileToggle.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ---- RESPONSIVE : reset menu si resize ----
    function handleResize() {
        if (window.innerWidth > 768) {
            modulesList?.classList.remove('open');
            mobileToggle?.classList.remove('active');
            mobileToggle?.setAttribute('aria-expanded', 'false');
        }
    }
    window.addEventListener('resize', handleResize);

    // ---- RACCOURCI CLAVIER ESC pour menu mobile ----
    document.addEventListener('keydown', function(e) {
        // Menu user
        if (e.key === 'Escape' && window.headerManager?.isDropdownOpen) {
            window.headerManager.closeUserMenu();
        }
        // Menu modules
        if (e.key === 'Escape' && modulesList?.classList.contains('open')) {
            modulesList.classList.remove('open');
            mobileToggle?.classList.remove('active');
            mobileToggle?.setAttribute('aria-expanded', 'false');
        }
    });

    // ---- INDICATEUR MODULE ACTIF ----
    const currentModule = window.PortalConfig?.currentModule;
    if (currentModule) {
        const activeItem = document.querySelector(`[data-module="${currentModule}"]`);
        if (activeItem && !activeItem.classList.contains('active')) {
            activeItem.classList.add('active');
        }
    }

    // ---- EFFET VISUEL sur click module ----
    const moduleItems = document.querySelectorAll('.module-nav-item');
    moduleItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Feedback visuel
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // ---- BADGES ANIMATION ----
    const devBadges = document.querySelectorAll('.status-badge.dev');
    devBadges.forEach(badge => {
        badge.style.animation = 'pulse 2s infinite';
    });

    // ---- STICKY HEADER MANAGEMENT ----
    let lastScrollY = window.scrollY;
    const modulesNav = document.querySelector('.modules-nav');
    const breadcrumbNav = document.querySelector('.breadcrumb-nav.sticky');
    function handleScroll() {
        const currentScrollY = window.scrollY;
        // Masquer/afficher le menu modules au scroll sur mobile
        if (window.innerWidth <= 768) {
            if (currentScrollY > lastScrollY && currentScrollY > 100) {
                modulesNav?.style.setProperty('transform', 'translateY(-100%)');
            } else {
                modulesNav?.style.setProperty('transform', 'translateY(0)');
            }
        }
        lastScrollY = currentScrollY;
    }
    // Throttle scroll event
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        if (!scrollTimeout) {
            scrollTimeout = setTimeout(function() {
                handleScroll();
                scrollTimeout = null;
            }, 10);
        }
    });

    // ---- CSS animations via JavaScript ----
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .modules-nav {
            transition: transform 0.3s ease;
        }
    `;
    document.head.appendChild(style);

    // Log (pour debug)
    console.log('Header + modules-nav JS initialisé');
});

// ================== UTILITAIRES ==================
// Changer de module programmatiquement
window.navigateToModule = function(moduleKey) {
    const moduleItem = document.querySelector(`[data-module="${moduleKey}"]`);
    if (moduleItem) {
        window.location.href = moduleItem.href;
    }
};

// Export Node.js/require (pour tests éventuels)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { HeaderManager, BreadcrumbManager, HeaderNotifications };
}

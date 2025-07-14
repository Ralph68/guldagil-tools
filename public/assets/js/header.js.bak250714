/**
 * Titre: JavaScript Header - Version modulaire  
 * Chemin: /templates/assets/js/header.js
 * Version: 0.5 beta + build auto
 */

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

    success(message, duration) {
        return this.show(message, 'success', duration);
    }

    error(message, duration) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration) {
        return this.show(message, 'info', duration);
    }
}

// Instances globales
let headerManager;
let breadcrumbManager;
let headerNotifications;

// Initialisation du module header
document.addEventListener('DOMContentLoaded', function() {
    headerManager = new HeaderManager();
    breadcrumbManager = new BreadcrumbManager();
    headerNotifications = new HeaderNotifications();
    
    // Exposer les notifications globalement
    window.headerNotifications = headerNotifications;
    
    console.log('Header module initialisé');
});

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { HeaderManager, BreadcrumbManager, HeaderNotifications };
}

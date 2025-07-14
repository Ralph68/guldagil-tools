/**
 * Titre: JavaScript Header & Navigation Combinés
 * Version: 0.5 beta + build auto
 */

// Classe principale pour la gestion de l'en-tête et de la navigation
class HeaderNavigation {
    constructor() {
        // Initialisation des gestionnaires
        this.headerManager = new HeaderManager();
        this.breadcrumbManager = new BreadcrumbManager();
        
        // Configuration mobile
        this.mobileToggle = document.querySelector('.mobile-menu-toggle');
        this.modulesList = document.querySelector('.modules-list');
        
        // Éléments navigation
        this.modulesNav = document.querySelector('.modules-nav');
        this.breadcrumbNav = document.querySelector('.breadcrumb-nav.sticky');
        
        // Initialisation
        this.init();
    }

    // Méthode d'initialisation
    init() {
        // Initialisations existantes
        this.setupMobileMenu();
        this.handleResize();
        this.setupSmoothScroll();
        this.setupStatusBadges();
        this.setupStickyHeader();
        
        // Ajout des écouteurs d'événements globaux
        window.addEventListener('resize', () => this.handleResize());
        window.addEventListener('scroll', () => this.throttleScroll(() => this.handleScroll(), 10));
    }

    // Configuration du menu mobile
    setupMobileMenu() {
        if (this.mobileToggle && this.modulesList) {
            this.mobileToggle.addEventListener('click', () => {
                const isOpen = this.modulesList.classList.contains('open');
                
                if (isOpen) {
                    this.closeMobileMenu();
                } else {
                    this.openMobileMenu();
                }
            });

            // Fermeture automatique
            document.addEventListener('click', (e) => {
                if (!this.mobileToggle.contains(e.target) && !this.modulesList.contains(e.target)) {
                    this.closeMobileMenu();
                }
            });
        }
    }

    // Ouvrir le menu mobile
    openMobileMenu() {
        this.modulesList.classList.add('open');
        this.mobileToggle.classList.add('active');
        this.mobileToggle.setAttribute('aria-expanded', 'true');
    }

    // Fermer le menu mobile
    closeMobileMenu() {
        this.modulesList.classList.remove('open');
        this.mobileToggle.classList.remove('active');
        this.mobileToggle.setAttribute('aria-expanded', 'false');
    }

    // Gestion du redimensionnement
    handleResize() {
        if (window.innerWidth > 768) {
            this.modulesList?.classList.remove('open');
            this.mobileToggle?.classList.remove('active');
            this.mobileToggle?.setAttribute('aria-expanded', 'false');
        }
    }

    // Configuration du scroll fluide
    setupSmoothScroll() {
        const moduleItems = document.querySelectorAll('.module-nav-item');
        moduleItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.currentTarget.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    e.currentTarget.style.transform = '';
                }, 150);
            });
        });
    }

    // Configuration des badges de statut
    setupStatusBadges() {
        const devBadges = document.querySelectorAll('.status-badge.dev');
        const betaBadges = document.querySelectorAll('.status-badge.beta');
        
        devBadges.forEach(badge => {
            badge.style.animation = 'pulse 2s infinite';
        });
    }

    // Configuration du header sticky
    setupStickyHeader() {
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            
            if (window.innerWidth <= 768) {
                if (currentScrollY > this.lastScrollY && currentScrollY > 100) {
                    this.modulesNav?.style.setProperty('transform', 'translateY(-100%)');
                } else {
                    this.modulesNav?.style.setProperty('transform', 'translateY(0)');
                }
            }
            
            this.lastScrollY = currentScrollY;
        });
    }

    // Fonction de limitation de débit pour le scroll
    throttleScroll(callback, limit) {
        let wait = false;
        return () => {
            if (!wait) {
                callback.call();
                wait = true;
                setTimeout(() => {
                    wait = false;
                }, limit);
            }
        };
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    const headerNavigation = new HeaderNavigation();
    
    console.log('Module de navigation initialisé');
});

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { HeaderNavigation };
}

// Fonction globale pour navigation entre modules
window.navigateToModule = function(moduleKey) {
    const moduleItem = document.querySelector(`[data-module="${moduleKey}"]`);
    if (moduleItem) {
        window.location.href = moduleItem.href;
    }
};

// Styles CSS pour animations
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

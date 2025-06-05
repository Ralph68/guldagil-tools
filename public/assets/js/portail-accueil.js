// public/assets/js/portail-accueil.js - JavaScript pour accueil épuré

console.log('🏠 Chargement Portail Accueil V2...');

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Accueil Portail V2 initialisé');
    
    setupCardInteractions();
    setupNavigationEffects();
    setupExternalLinks();
    initializeAccessibilityFeatures();
});

// ========== INTERACTIONS CARTES ==========
function setupCardInteractions() {
    const toolCards = document.querySelectorAll('.tool-card');
    
    toolCards.forEach(card => {
        // Effet hover amélioré
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-6px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
        
        // Clic avec feedback visuel
        card.addEventListener('click', function(e) {
            // Animation de clic
            this.style.transform = 'translateY(-2px) scale(0.98)';
            
            setTimeout(() => {
                this.style.transform = 'translateY(-6px) scale(1)';
            }, 150);
            
            // Log analytics
            const toolName = this.querySelector('h2').textContent;
            console.log(`🎯 Accès outil: ${toolName}`);
            
            // Redirection avec petit délai pour l'animation
            setTimeout(() => {
                const href = this.getAttribute('onclick').match(/'([^']+)'/)[1];
                window.location.href = href;
            }, 200);
            
            // Empêcher la redirection immédiate
            e.preventDefault();
        });
        
        // Support clavier (accessibilité)
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
        
        // Rendre focusable
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
    });
}

// ========== NAVIGATION EFFECTS ==========
function setupNavigationEffects() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Si c'est un lien interne (commence par une lettre, pas http)
            if (!this.href.startsWith('http')) {
                // Animation de transition
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
                
                // Log navigation
                const toolName = this.textContent.trim();
                console.log(`🧭 Navigation: ${toolName}`);
            }
        });
    });
    
    // Effet survol amélioré pour la navigation
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// ========== LIENS EXTERNES ==========
function setupExternalLinks() {
    const externalLinks = document.querySelectorAll('a[target="_blank"]');
    
    externalLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Confirmation pour liens externes (optionnel)
            const shouldConfirm = this.href.includes('heppner') || this.href.includes('xpo');
            
            if (shouldConfirm) {
                const siteName = this.href.includes('heppner') ? 'Portal Heppner' : 'XPO Connect';
                
                // Animation avant ouverture
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 100);
                
                // Log analytics
                console.log(`🔗 Redirection externe: ${siteName}`);
                console.log(`📊 URL: ${this.href}`);
            }
        });
        
        // Effet hover pour liens transporteurs
        link.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.external-icon');
            if (icon) {
                icon.style.transform = 'translate(4px, -4px) rotate(15deg)';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.external-icon');
            if (icon) {
                icon.style.transform = 'translate(0, 0) rotate(0deg)';
            }
        });
    });
}

// ========== ACCESSIBILITÉ ==========
function initializeAccessibilityFeatures() {
    // Focus visible amélioré
    const focusableElements = document.querySelectorAll('a, button, .tool-card');
    
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.style.outline = '2px solid var(--gul-blue-primary)';
            this.style.outlineOffset = '2px';
        });
        
        element.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });
    
    // Support clavier pour les cartes
    document.addEventListener('keydown', function(e) {
        // Échapper pour fermer des éléments (préparation future)
        if (e.key === 'Escape') {
            console.log('⌨️ Touche Échap pressée');
        }
        
        // Raccourcis clavier (optionnel)
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case '1':
                    e.preventDefault();
                    location.href = 'calculateur/';
                    break;
                case '2':
                    e.preventDefault();
                    location.href = 'adr/';
                    break;
                case '3':
                    e.preventDefault();
                    location.href = 'admin/';
                    break;
            }
        }
    });
}

// ========== ANIMATIONS SCROLL ==========
function setupScrollAnimations() {
    // Observer pour les animations au scroll (si nécessaire)
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observer les cartes pour animation d'apparition
    const cards = document.querySelectorAll('.tool-card');
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

// ========== GESTION RESPONSIVE ==========
function handleResponsiveFeatures() {
    // Ajuster les interactions selon la taille d'écran
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Désactiver certains effets hover sur mobile
        document.body.classList.add('mobile-device');
    } else {
        document.body.classList.remove('mobile-device');
        
        // Activer animations scroll sur desktop
        setupScrollAnimations();
    }
}

// ========== EVENT LISTENERS ==========
window.addEventListener('resize', debounce(handleResponsiveFeatures, 300));
window.addEventListener('load', handleResponsiveFeatures);

// ========== PRÉCHARGEMENT ==========
function preloadCriticalResources() {
    // Précharger les pages importantes
    const criticalPages = ['calculateur/', 'adr/'];
    
    criticalPages.forEach(page => {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = page;
        document.head.appendChild(link);
    });
    
    console.log('🚀 Ressources critiques préchargées');
}

// ========== ANALYTICS & MONITORING ==========
function trackUserInteraction(action, target) {
    // Simulation d'analytics (remplacer par votre solution)
    const analyticsData = {
        timestamp: new Date().toISOString(),
        action: action,
        target: target,
        userAgent: navigator.userAgent,
        screenSize: `${window.innerWidth}x${window.innerHeight}`,
        page: 'accueil'
    };
    
    console.log('📊 Analytics:', analyticsData);
    
    // Ici vous pouvez envoyer vers votre système d'analytics
    // fetch('/analytics', { method: 'POST', body: JSON.stringify(analyticsData) });
}

// ========== ÉTAT DE L'APPLICATION ==========
const AppState = {
    isLoaded: false,
    currentFocus: null,
    
    init() {
        this.isLoaded = true;
        trackUserInteraction('page_load', 'accueil');
    },
    
    updateFocus(element) {
        this.currentFocus = element;
    }
};

// ========== UTILITAIRES ==========
function debounce(func, wait) {
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

function showNotification(message, type = 'info') {
    // Système de notification simple (pour usage futur)
    console.log(`📢 ${type.toUpperCase()}: ${message}`);
    
    // Ici vous pouvez ajouter un toast/notification visuel
}

// ========== GESTION DES ERREURS ==========
window.addEventListener('error', function(e) {
    console.error('❌ Erreur JavaScript:', e.error);
    
    // Log de l'erreur pour monitoring
    trackUserInteraction('javascript_error', {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno
    });
});

// ========== INITIALISATION FINALE ==========
document.addEventListener('DOMContentLoaded', function() {
    AppState.init();
    preloadCriticalResources();
    
    // Message de bienvenue (en dev seulement)
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
        console.log('🎉 Portail Guldagil V2 - Mode Développement');
        console.log('⌨️ Raccourcis: Ctrl+1 (Calculateur), Ctrl+2 (ADR), Ctrl+3 (Admin)');
    }
});

// ========== EXPORT POUR TESTS ==========
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        AppState,
        trackUserInteraction,
        debounce
    };
}

console.log('✅ Portail Accueil V2 chargé avec succès');
console.log('🎯 Fonctionnalités: navigation améliorée, analytics, accessibilité');

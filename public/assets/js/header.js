/**
 * JavaScript pour le header du portail - Version sticky
 * Chemin: /public/assets/js/header.js
 * Version: 0.5 beta + build auto
 */

document.addEventListener('DOMContentLoaded', function() {
    // === ÉLÉMENTS DOM ===
    const userMenuTrigger = document.querySelector('.user-menu-trigger');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    const modulesNav = document.querySelector('.modules-nav');
    const breadcrumbNav = document.querySelector('.breadcrumb-nav');
    
    // === GESTION MENU UTILISATEUR ===
    if (userMenuTrigger && userDropdownMenu) {
        // Toggle menu au clic
        userMenuTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isExpanded = userMenuTrigger.getAttribute('aria-expanded') === 'true';
            toggleUserMenu(!isExpanded);
        });
        
        // Fermer menu si clic ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header-user-nav')) {
                toggleUserMenu(false);
            }
        });
        
        // Gestion clavier (ESC)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                toggleUserMenu(false);
                userMenuTrigger.focus();
            }
        });
        
        // Fonction toggle
        function toggleUserMenu(show) {
            userMenuTrigger.setAttribute('aria-expanded', show);
            userDropdownMenu.setAttribute('aria-hidden', !show);
            userDropdownMenu.style.display = show ? 'block' : 'none';
        }
    }
    
    // === SCROLL BEHAVIOR INTELLIGENT ===
    let lastScrollTop = 0;
    let scrollTimeout;
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollingDown = scrollTop > lastScrollTop;
        const scrollThreshold = 100;
        
        // Gestion classe scrolled sur body
        if (scrollTop > scrollThreshold) {
            document.body.classList.add('scrolled');
        } else {
            document.body.classList.remove('scrolled');
        }
        
        // Navigation modules : cacher au scroll down, montrer au scroll up
        if (modulesNav && scrollTop > scrollThreshold) {
            if (scrollingDown) {
                modulesNav.style.transform = 'translateY(-100%)';
                modulesNav.style.opacity = '0';
            } else {
                modulesNav.style.transform = 'translateY(0)';
                modulesNav.style.opacity = '1';
            }
        } else if (modulesNav) {
            modulesNav.style.transform = 'translateY(0)';
            modulesNav.style.opacity = '1';
        }
        
        // Breadcrumb sticky amélioré
        if (breadcrumbNav && scrollTop > scrollThreshold) {
            breadcrumbNav.style.top = 'var(--header-height)';
            breadcrumbNav.style.background = 'rgba(248, 250, 252, 0.95)';
            breadcrumbNav.style.backdropFilter = 'blur(8px)';
            breadcrumbNav.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        } else if (breadcrumbNav) {
            breadcrumbNav.style.top = 'calc(var(--header-height) + var(--nav-height))';
            breadcrumbNav.style.background = '#f9fafb';
            breadcrumbNav.style.backdropFilter = 'none';
            breadcrumbNav.style.boxShadow = 'none';
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }
    
    // Optimisation scroll avec throttle
    function throttledScroll() {
        if (scrollTimeout) return;
        
        scrollTimeout = setTimeout(() => {
            handleScroll();
            scrollTimeout = null;
        }, 16); // ~60fps
    }
    
    window.addEventListener('scroll', throttledScroll, { passive: true });
    
    // Initialiser au chargement
    handleScroll();
    
    // === RACCOURCIS CLAVIER ===
    document.addEventListener('keydown', function(e) {
        // Alt + H : Accueil
        if (e.altKey && e.key === 'h') {
            e.preventDefault();
            window.location.href = '/';
        }
        
        // Alt + A : Administration (si accessible)
        if (e.altKey && e.key === 'a') {
            const adminLink = document.querySelector('a[href="/admin/"]');
            if (adminLink) {
                e.preventDefault();
                window.location.href = '/admin/';
            }
        }
        
        // Alt + U : Menu utilisateur
        if (e.altKey && e.key === 'u') {
            e.preventDefault();
            if (userMenuTrigger) {
                userMenuTrigger.click();
            }
        }
    });
    
    // === GESTION RESPONSIVE ===
    function handleResize() {
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile && userDropdownMenu) {
            userDropdownMenu.style.minWidth = '200px';
        } else if (userDropdownMenu) {
            userDropdownMenu.style.minWidth = '250px';
        }
    }
    
    window.addEventListener('resize', handleResize);
    handleResize();
    
    // === PERFORMANCE OPTIMISATIONS ===
    // Lazy load des avatars si présents
    const avatarImages = document.querySelectorAll('.user-avatar img');
    if (avatarImages.length > 0 && 'IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                }
            });
        });
        
        avatarImages.forEach(img => imageObserver.observe(img));
    }
    
    // === DEBUG MODE ===
    if (document.querySelector('.debug-banner')) {
        console.log('🔧 Header JS chargé - Mode debug actif');
        console.log('📊 Éléments détectés:', {
            userMenu: !!userMenuTrigger,
            modulesNav: !!modulesNav,
            breadcrumb: !!breadcrumbNav
        });
    }
});

// === FONCTIONS GLOBALES ===

/**
 * Fonction d'aide contextuelle
 */
function showHelp() {
    const module = document.body.dataset.module || 'inconnu';
    const version = document.querySelector('meta[name="generator"]')?.content || 'inconnu';
    
    const helpText = `Aide contextuelle - Module: ${module}
Version: 0.5 beta
Build: ${new Date().toISOString().slice(0,10).replace(/-/g,'')}

Raccourcis clavier:
• Alt+H: Accueil
• Alt+A: Administration
• Alt+U: Menu utilisateur
• Échap: Fermer les menus

Support: contact@guldagil.fr`;
    
    // Notification moderne ou fallback alert
    if (window.Notification && Notification.permission === 'granted') {
        new Notification('Aide Portail Guldagil', {
            body: `Module ${module} - Raccourcis disponibles`,
            icon: '/assets/img/favicon.png'
        });
    } else {
        alert(helpText);
    }
}

/**
 * Navigation rapide vers module
 */
function navigateToModule(moduleKey) {
    if (moduleKey && typeof moduleKey === 'string') {
        window.location.href = `/${moduleKey}/`;
    }
}

/**
 * API Header pour modules externes
 */
window.HeaderAPI = {
    toggleUserMenu: function(show) {
        const trigger = document.querySelector('.user-menu-trigger');
        const menu = document.getElementById('userDropdownMenu');
        
        if (trigger && menu) {
            trigger.setAttribute('aria-expanded', show);
            menu.setAttribute('aria-hidden', !show);
            menu.style.display = show ? 'block' : 'none';
        }
    },
    
    showNotification: function(message, type = 'info') {
        console.log(`[${type.toUpperCase()}] ${message}`);
        
        // Toast notification simple
        const toast = document.createElement('div');
        toast.className = `notification notification-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'error' ? '#dc2626' : type === 'warning' ? '#d97706' : '#059669'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            font-size: 14px;
            font-weight: 500;
            max-width: 300px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        // Animation d'entrée
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto-suppression
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    },
    
    getCurrentModule: function() {
        return document.body.dataset.module || 'home';
    },
    
    isUserAuthenticated: function() {
        return document.body.classList.contains('authenticated');
    },
    
    refreshBreadcrumb: function(breadcrumbs) {
        const container = document.querySelector('.breadcrumb-container');
        if (!container || !Array.isArray(breadcrumbs)) return;
        
        container.innerHTML = '';
        
        breadcrumbs.forEach((item, index) => {
            if (index > 0) {
                const separator = document.createElement('span');
                separator.className = 'breadcrumb-separator';
                separator.textContent = '›';
                container.appendChild(separator);
            }
            
            const element = document.createElement(item.url && !item.active ? 'a' : 'span');
            element.className = `breadcrumb-item${item.active ? ' active' : ''}`;
            
            if (item.icon) {
                const icon = document.createElement('span');
                icon.className = 'breadcrumb-icon';
                icon.textContent = item.icon;
                element.appendChild(icon);
            }
            
            const text = document.createElement('span');
            text.className = 'breadcrumb-text';
            text.textContent = item.text;
            element.appendChild(text);
            
            if (item.url && !item.active) {
                element.href = item.url;
            }
            
            container.appendChild(element);
        });
    }
};

// === INITIALISATION FINALE ===
document.addEventListener('DOMContentLoaded', function() {
    // Marquer le header comme initialisé
    document.documentElement.setAttribute('data-header-loaded', 'true');
    
    // Log de démarrage
    console.log('🎯 Header Guldagil v0.5 - Chargé avec succès');
    
    // Ajouter classe no-breadcrumb si pas de fil d'ariane
    if (!document.querySelector('.breadcrumb-nav')) {
        document.body.classList.add('no-breadcrumb');
    }
    
    // Performance monitoring
    if (window.performance && window.performance.now) {
        const loadTime = window.performance.now();
        console.log(`⚡ Header initialisé en ${Math.round(loadTime)}ms`);
    }
});

// === EXPORT POUR MODULES ===
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { HeaderAPI: window.HeaderAPI, showHelp, navigateToModule };
}
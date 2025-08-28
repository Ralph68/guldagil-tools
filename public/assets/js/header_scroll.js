/**
 * Titre: JavaScript pour gestion scroll intelligent et breadcrumb adaptatif
 * Chemin: /assets/js/header_scroll.js
 * Version: 0.5 beta + build auto
 * Description: Gestion du comportement scroll avec logique conditionnelle selon présence breadcrumb
 */

(function() {
    'use strict';
    
    // === CONFIGURATION ===
    const CONFIG = {
        SCROLL_THRESHOLD: 100,
        SCROLL_DELTA_MIN: 5,
        ANIMATION_DURATION: 300,
    DEBUG: window.location.search.includes('debug=true') || (document.body && document.body.dataset && document.body.dataset.debug === 'true')
    };
    
    // === VARIABLES D'ÉTAT ===
    let lastScrollY = window.scrollY;
    let isScrollingDown = false;
    let ticking = false;
    let hasBreadcrumb = false;
    let isInitialized = false;
    
    // === ÉLÉMENTS DOM ===
    let elements = {};
    
    /**
     * Initialisation des éléments DOM
     */
    function initElements() {
        elements = {
            body: document.body,
            header: document.querySelector('.portal-header'),
            modulesNav: document.querySelector('.modules-nav'),
            breadcrumbNav: document.querySelector('.breadcrumb-nav'),
            portalMain: document.querySelector('.portal-main')
        };
        
        // Détecter présence du breadcrumb
    hasBreadcrumb = (elements.body && elements.body.dataset && elements.body.dataset.hasBreadcrumb === 'true') || 
               elements.breadcrumbNav !== null;
        
        if (CONFIG.DEBUG) {
            console.log('🔧 Header Scroll Debug - Éléments initialisés:', {
                hasBreadcrumb,
                modulesNav: !!elements.modulesNav,
                breadcrumbNav: !!elements.breadcrumbNav,
                bodyClass: elements.body.className
            });
        }
    }
    
    /**
     * Gestion intelligente du scroll
     */
    function handleIntelligentScroll() {
        const currentScrollY = window.scrollY;
        const scrollDelta = currentScrollY - lastScrollY;
        
        // Seuil minimum pour éviter les micro-mouvements
        if (Math.abs(scrollDelta) > CONFIG.SCROLL_DELTA_MIN) {
            isScrollingDown = scrollDelta > 0;
            lastScrollY = currentScrollY;
        }
        
        // Logique conditionnelle selon présence du breadcrumb
        if (hasBreadcrumb) {
            handleScrollWithBreadcrumb(currentScrollY);
        } else {
            handleScrollWithoutBreadcrumb(currentScrollY);
        }
        
        ticking = false;
    }
    
    /**
     * Comportement avec breadcrumb : menu se cache, breadcrumb se colle
     */
    function handleScrollWithBreadcrumb(currentScrollY) {
        const { modulesNav, breadcrumbNav, body } = elements;
        
        if (!modulesNav || !breadcrumbNav) return;
        
        if (currentScrollY > CONFIG.SCROLL_THRESHOLD && isScrollingDown) {
            // Masquer le menu modules
            modulesNav.style.transform = 'translateY(-100%)';
            modulesNav.style.opacity = '0';
            modulesNav.style.pointerEvents = 'none';
            
            // Breadcrumb se colle sous le header
            breadcrumbNav.style.position = 'fixed';
            breadcrumbNav.style.top = 'var(--header-height)';
            breadcrumbNav.style.background = 'rgba(241, 245, 249, 0.95)';
            breadcrumbNav.style.backdropFilter = 'blur(12px)';
            breadcrumbNav.style.webkitBackdropFilter = 'blur(12px)';
            breadcrumbNav.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.1)';
            breadcrumbNav.style.zIndex = '999';
            breadcrumbNav.style.borderBottom = '1px solid rgba(226, 232, 240, 0.8)';
            
            body.classList.add('scrolled');
            
            if (CONFIG.DEBUG) {
                console.log('📱 Scroll DOWN avec breadcrumb - Menu masqué, breadcrumb collé');
            }
            
        } else if (!isScrollingDown || currentScrollY <= 50) {
            // Restaurer le menu modules
            modulesNav.style.transform = 'translateY(0)';
            modulesNav.style.opacity = '1';
            modulesNav.style.pointerEvents = 'auto';
            
            // Breadcrumb retour position normale
            breadcrumbNav.style.position = 'static';
            breadcrumbNav.style.background = '';
            breadcrumbNav.style.backdropFilter = '';
            breadcrumbNav.style.webkitBackdropFilter = '';
            breadcrumbNav.style.boxShadow = '';
            breadcrumbNav.style.borderBottom = '';
            
            body.classList.remove('scrolled');
            
            if (CONFIG.DEBUG) {
                console.log('📱 Scroll UP avec breadcrumb - Menu visible, breadcrumb normal');
            }
        }
    }
    
    /**
     * Comportement sans breadcrumb : menu reste toujours visible
     */
    function handleScrollWithoutBreadcrumb(currentScrollY) {
        const { modulesNav, body } = elements;
        
        if (!modulesNav) return;
        
        // Toujours garder le menu visible
        modulesNav.style.transform = 'translateY(0)';
        modulesNav.style.opacity = '1';
        modulesNav.style.pointerEvents = 'auto';
        
        // Retirer les classes de scroll
        body.classList.remove('scrolled');
        
        if (CONFIG.DEBUG && currentScrollY > CONFIG.SCROLL_THRESHOLD) {
            console.log('📱 Scroll détecté SANS breadcrumb - Menu conservé visible');
        }
    }
    
    /**
     * Optimisation du scroll avec requestAnimationFrame
     */
    function requestScrollUpdate() {
        if (!ticking) {
            requestAnimationFrame(handleIntelligentScroll);
            ticking = true;
        }
    }
    
    /**
     * Gestion responsive
     */
    function handleResize() {
        const isMobile = window.innerWidth <= 768;
        const { modulesNav } = elements;
        
        if (modulesNav) {
            const navItems = modulesNav.querySelector('.modules-nav-items');
            if (navItems) {
                navItems.style.justifyContent = isMobile ? 'flex-start' : 'center';
            }
            
            // Sur mobile, ajustements spécifiques
            if (isMobile) {
                navItems?.style.setProperty('overflow-x', 'auto');
                navItems?.style.setProperty('padding-bottom', '0.25rem');
            } else {
                navItems?.style.removeProperty('overflow-x');
                navItems?.style.removeProperty('padding-bottom');
            }
        }
        
        if (CONFIG.DEBUG) {
            console.log('📱 Resize détecté:', {
                width: window.innerWidth,
                isMobile,
                hasBreadcrumb
            });
        }
    }
    
    /**
     * Optimisation performances avec debounce
     */
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
    
    /**
     * Amélioration accessibilité - Focus management
     */
    function initAccessibility() {
        // Gestion focus pour navigation clavier
        document.addEventListener('keydown', function(e) {
            // Navigation rapide modules avec Alt + numéro
            if (e.altKey && /^[1-9]$/.test(e.key)) {
                e.preventDefault();
                const moduleItems = document.querySelectorAll('.module-nav-item');
                const index = parseInt(e.key) - 1;
                if (moduleItems[index]) {
                    moduleItems[index].focus();
                    moduleItems[index].click();
                }
            }
            
            // Navigation breadcrumb avec Ctrl + flèches
            if (e.ctrlKey && ['ArrowLeft', 'ArrowRight'].includes(e.key)) {
                e.preventDefault();
                const breadcrumbItems = document.querySelectorAll('.breadcrumb-item');
                const currentIndex = Array.from(breadcrumbItems).findIndex(item => 
                    item === document.activeElement
                );
                
                let newIndex;
                if (e.key === 'ArrowLeft' && currentIndex > 0) {
                    newIndex = currentIndex - 1;
                } else if (e.key === 'ArrowRight' && currentIndex < breadcrumbItems.length - 1) {
                    newIndex = currentIndex + 1;
                }
                
                if (newIndex !== undefined && breadcrumbItems[newIndex]) {
                    breadcrumbItems[newIndex].focus();
                }
            }
        });
    }
    
    /**
     * Détection des préférences utilisateur
     */
    function detectUserPreferences() {
        // Respect des préférences de mouvement réduit
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            CONFIG.ANIMATION_DURATION = 50;
            document.documentElement.style.setProperty('--transition-fast', 'all 0.05s ease');
            document.documentElement.style.setProperty('--transition-normal', 'all 0.1s ease');
            
            if (CONFIG.DEBUG) {
                console.log('♿ Mouvement réduit détecté - Animations accélérées');
            }
        }
        
        // Détection contraste élevé
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            document.body.classList.add('high-contrast');
            
            if (CONFIG.DEBUG) {
                console.log('♿ Contraste élevé détecté - Styles adaptés');
            }
        }
    }
    
    /**
     * Initialisation complète
     */
    function init() {
        if (isInitialized) return;
        
        try {
            initElements();
            detectUserPreferences();
            initAccessibility();
            
            // Event listeners avec options de performance
            window.addEventListener('scroll', requestScrollUpdate, { 
                passive: true, 
                capture: false 
            });
            
            window.addEventListener('resize', debounce(handleResize, 250), { 
                passive: true 
            });
            
            // Initialisation responsive
            handleResize();
            
            // État initial selon scroll actuel
            if (window.scrollY > 0) {
                requestScrollUpdate();
            }
            
            isInitialized = true;
            
            if (CONFIG.DEBUG) {
                console.log('✅ Header Scroll Management initialisé avec succès', {
                    hasBreadcrumb,
                    scrollY: window.scrollY,
                    elements: Object.keys(elements).filter(key => elements[key])
                });
            }
            
        } catch (error) {
            console.error('❌ Erreur initialisation Header Scroll:', error);
        }
    }
    
    /**
     * API publique pour debug et contrôle externe
     */
    window.HeaderScrollManager = {
        init,
        getState: () => ({
            hasBreadcrumb,
            isScrollingDown,
            lastScrollY,
            isInitialized
        }),
        forceUpdate: () => requestScrollUpdate(),
        toggleDebug: () => {
            CONFIG.DEBUG = !CONFIG.DEBUG;
            console.log(`🔧 Debug mode: ${CONFIG.DEBUG ? 'ON' : 'OFF'}`);
        }
    };
    
    // Auto-initialisation au chargement DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Initialisation de secours au chargement complet
    window.addEventListener('load', () => {
        if (!isInitialized) {
            setTimeout(init, 100);
        }
    });
    
})();
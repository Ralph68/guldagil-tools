/**
 * Titre: JavaScript pour le header du portail - Version complète
 * Chemin: /assets/js/header.js
 * Version: 0.5 beta + build auto
 * Description: Gestion complète des interactions header, navigation modules et menu utilisateur
 */

document.addEventListener('DOMContentLoaded', function() {
    // === ÉLÉMENTS DOM ===
    const userMenuTrigger = document.getElementById('userMenuTrigger');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const modulesNav = document.querySelector('.modules-nav');
    const breadcrumbNav = document.querySelector('.breadcrumb-nav');
    const portalHeader = document.querySelector('.portal-header');
    
    // Variables d'état
    let lastScrollY = window.scrollY;
    let isScrollingDown = false;
    let userMenuOpen = false;
    let mobileMenuOpen = false;
    
    // === GESTION MENU UTILISATEUR ===
    if (userMenuTrigger && userDropdownMenu) {
        // Toggle menu au clic
        userMenuTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            userMenuOpen = !userMenuOpen;
            toggleUserMenu(userMenuOpen);
        });
        
        // Fermer menu si clic ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header-user-nav') && userMenuOpen) {
                userMenuOpen = false;
                toggleUserMenu(false);
            }
        });
        
        // Gestion clavier
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && userMenuOpen) {
                userMenuOpen = false;
                toggleUserMenu(false);
                userMenuTrigger.focus();
            }
        });
        
        // Fonction toggle menu utilisateur
        function toggleUserMenu(show) {
            userMenuTrigger.setAttribute('aria-expanded', show);
            userDropdownMenu.setAttribute('aria-hidden', !show);
            userDropdownMenu.style.display = show ? 'block' : 'none';
            
            // Animation d'entrée/sortie
            if (show) {
                userDropdownMenu.style.opacity = '0';
                userDropdownMenu.style.transform = 'translateY(-10px) scale(0.95)';
                requestAnimationFrame(() => {
                    userDropdownMenu.style.opacity = '1';
                    userDropdownMenu.style.transform = 'translateY(0) scale(1)';
                });
            }
        }
    }
    
    // === GESTION MENU MOBILE ===
    if (mobileMenuToggle && modulesNav) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            mobileMenuOpen = !mobileMenuOpen;
            toggleMobileMenu(mobileMenuOpen);
        });
        
        // Fermer menu mobile si clic ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.modules-nav') && mobileMenuOpen) {
                mobileMenuOpen = false;
                toggleMobileMenu(false);
            }
        });
        
        // Fonction toggle menu mobile
        function toggleMobileMenu(show) {
            modulesNav.classList.toggle('mobile-open', show);
            mobileMenuToggle.classList.toggle('open', show);
            mobileMenuToggle.setAttribute('aria-expanded', show);
        }
    }
    
    // === GESTION SCROLL INTELLIGENT ===
    let ticking = false;
    
    function handleScroll() {
        const currentScrollY = window.scrollY;
        const scrollDelta = currentScrollY - lastScrollY;
        
        // Déterminer direction du scroll
        if (Math.abs(scrollDelta) > 5) { // Seuil minimum pour éviter les micro-mouvements
            isScrollingDown = scrollDelta > 0;
            lastScrollY = currentScrollY;
        }
        
        // Gestion affichage navigation modules selon scroll
        if (modulesNav) {
            if (currentScrollY > 100 && isScrollingDown) {
                modulesNav.classList.add('hide-modules-nav');
            } else if (!isScrollingDown || currentScrollY <= 50) {
                modulesNav.classList.remove('hide-modules-nav');
            }
        }
        
        // Adaptation header compact pour mobile
        if (window.innerWidth <= 768) {
            if (currentScrollY > 80) {
                portalHeader?.classList.add('header-compact');
                document.body.classList.add('header-compact');
            } else {
                portalHeader?.classList.remove('header-compact');
                document.body.classList.remove('header-compact');
            }
        }
        
        ticking = false;
    }
    
    // Optimisation scroll avec requestAnimationFrame
    function requestScrollUpdate() {
        if (!ticking) {
            requestAnimationFrame(handleScroll);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', requestScrollUpdate, { passive: true });
    
    // === GESTION RESPONSIVE ===
    function handleResize() {
        const isMobile = window.innerWidth <= 768;
        
        // Fermer menu mobile si passage en desktop
        if (!isMobile && mobileMenuOpen) {
            mobileMenuOpen = false;
            toggleMobileMenu(false);
        }
        
        // Ajuster navigation modules selon taille écran
        if (modulesNav) {
            const navItems = modulesNav.querySelector('.modules-nav-items');
            if (navItems) {
                if (isMobile) {
                    navItems.style.justifyContent = 'flex-start';
                } else {
                    navItems.style.justifyContent = 'center';
                }
            }
        }
    }
    
    window.addEventListener('resize', handleResize, { passive: true });
    
    // === AMÉLIORATION ACCESSIBILITÉ ===
    
    // Focus management pour navigation clavier
    function trapFocusInDropdown(dropdown) {
        const focusableElements = dropdown.querySelectorAll(
            'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        dropdown.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    // Shift + Tab
                    if (document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    // Tab
                    if (document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        });
    }
    
    // Appliquer trap focus au menu utilisateur
    if (userDropdownMenu) {
        trapFocusInDropdown(userDropdownMenu);
    }
    
    // === INDICATEURS VISUELS MODULES ACTIFS ===
    function highlightActiveModule() {
        const currentPath = window.location.pathname;
        const moduleItems = document.querySelectorAll('.module-nav-item');
        
        moduleItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentPath.startsWith(href) && href !== '/') {
                item.classList.add('active');
                
                // Mise à jour couleur module
                const moduleColor = item.style.getPropertyValue('--module-color');
                if (moduleColor) {
                    document.documentElement.style.setProperty('--current-module-color', moduleColor);
                }
            } else {
                item.classList.remove('active');
            }
        });
    }
    
    // === ANIMATIONS AVANCÉES ===
    function addHoverEffects() {
        // Animation hover pour modules nav
        const moduleNavItems = document.querySelectorAll('.module-nav-item');
        moduleNavItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateY(-2px)';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });
        
        // Animation hover pour dropdown items
        const dropdownItems = document.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.paddingLeft = 'calc(var(--spacing-lg) + 4px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.paddingLeft = 'var(--spacing-lg)';
            });
        });
    }
    
    // === LAZY LOADING AVATAR ===
    function initAvatarLazyLoad() {
        const avatarImages = document.querySelectorAll('.user-avatar img');
        avatarImages.forEach(img => {
            img.addEventListener('error', function() {
                // Fallback en cas d'erreur de chargement avatar
                const parent = this.parentElement;
                parent.innerHTML = parent.dataset.fallback || 'U';
                parent.style.backgroundColor = 'white';
                parent.style.color = 'var(--primary-blue)';
            });
        });
    }
    
    // === NOTIFICATIONS SYSTÈME ===
    function checkSystemNotifications() {
        // TODO: Implémentation future pour notifications temps réel
        // Placeholder pour système de notifications
        const userMenu = document.querySelector('.user-menu-trigger');
        if (userMenu) {
            // Ajouter badge notification si nécessaire
            // Implementation future avec WebSocket ou polling
        }
    }
    
    // === SAUVEGARDE PRÉFÉRENCES UTILISATEUR ===
    function saveUserPreferences() {
        const preferences = {
            menuState: userMenuOpen ? 'open' : 'closed',
            lastModule: document.body.dataset.module,
            timestamp: Date.now()
        };
        
        try {
            localStorage.setItem('portal_user_prefs', JSON.stringify(preferences));
        } catch (e) {
            console.warn('Impossible de sauvegarder les préférences utilisateur');
        }
    }
    
    function loadUserPreferences() {
        try {
            const prefs = JSON.parse(localStorage.getItem('portal_user_prefs') || '{}');
            
            // Restaurer état menu si récent (< 1 heure)
            if (prefs.timestamp && (Date.now() - prefs.timestamp) < 3600000) {
                // Logique de restauration si nécessaire
            }
        } catch (e) {
            console.warn('Impossible de charger les préférences utilisateur');
        }
    }
    
    // === GESTION ERREURS ET FALLBACKS ===
    function initErrorHandling() {
        // Gestion erreurs JavaScript globales
        window.addEventListener('error', function(e) {
            console.error('Erreur JavaScript dans header:', e.error);
            
            // Fallback : s'assurer que navigation reste fonctionnelle
            if (e.filename && e.filename.includes('header')) {
                // Réinitialiser navigation en mode dégradé
                const navItems = document.querySelectorAll('.module-nav-item');
                navItems.forEach(item => {
                    item.style.transform = '';
                    item.style.transition = 'none';
                });
            }
        });
        
        // Détection capacités navigateur
        if (!('CSS' in window && CSS.supports && CSS.supports('color', 'color-mix(in srgb, red 50%, blue)'))) {
            // Fallback pour navigateurs plus anciens
            document.documentElement.classList.add('no-color-mix');
        }
    }
    
    // === OPTIMISATIONS PERFORMANCE ===
    function initPerformanceOptimizations() {
        // Préchargement images hover states
        const moduleIcons = document.querySelectorAll('.module-nav-icon');
        moduleIcons.forEach(icon => {
            // Précharger assets si nécessaire
        });
        
        // Intersection Observer pour animations lazy
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, { threshold: 0.1 });
            
            // Observer éléments navigation
            document.querySelectorAll('.module-nav-item').forEach(item => {
                observer.observe(item);
            });
        }
    }
    
    // === INITIALISATION FINALE ===
    function initialize() {
        // Vérifications initiales
        console.log('🚀 Initialisation header portail v0.5');
        
        // Charger préférences utilisateur
        loadUserPreferences();
        
        // Activer effets visuels
        addHoverEffects();
        
        // Initialiser gestion erreurs
        initErrorHandling();
        
        // Initialiser optimisations
        initPerformanceOptimizations();
        
        // Initialiser lazy loading avatars
        initAvatarLazyLoad();
        
        // Mettre en évidence module actif
        highlightActiveModule();
        
        // Vérifier notifications
        checkSystemNotifications();
        
        // Ajustement initial responsive
        handleResize();
        
        console.log('✅ Header portail initialisé avec succès');
    }
    
    // === NETTOYAGE AU DÉCHARGEMENT ===
    window.addEventListener('beforeunload', function() {
        saveUserPreferences();
    });
    
    // === DÉMARRAGE ===
    initialize();
    
    // === EXPOSITION GLOBALE POUR DEBUG ===
    if (window.DEBUG) {
        window.PortalHeader = {
            toggleUserMenu,
            toggleMobileMenu,
            highlightActiveModule,
            saveUserPreferences,
            loadUserPreferences
        };
    }
});

// === FONCTIONS UTILITAIRES GLOBALES ===

/**
 * Fonction d'aide globale
 */
function showHelp() {
    const moduleInfo = document.body.dataset.module || 'unknown';
    const version = document.querySelector('meta[name="version"]')?.content || '0.5-beta';
    
    const helpContent = `
🔧 Aide Portail Guldagil

📍 Module actuel: ${moduleInfo}
🏷️ Version: ${version}
🕒 Dernière build: ${new Date().toLocaleString('fr-FR')}

🎯 Navigation:
• Utilisez les onglets modules pour naviguer
• Le fil d'Ariane montre votre position
• Menu utilisateur en haut à droite

⚙️ Raccourcis clavier:
• ESC: Fermer menus ouverts
• Tab: Navigation clavier dans menus

📞 Support:
• Contact: support@guldagil.com
• Documentation: /docs/
    `;
    
    // Utiliser modal si disponible, sinon alert
    if (typeof showModal === 'function') {
        showModal('Aide', helpContent);
    } else {
        alert(helpContent);
    }
}

/**
 * Fonction de debug pour le développement
 */
function debugHeader() {
    if (!window.DEBUG) {
        console.warn('Mode debug non activé');
        return;
    }
    
    const debugInfo = {
        userAuthenticated: document.body.classList.contains('authenticated'),
        currentModule: document.body.dataset.module,
        moduleStatus: document.body.dataset.moduleStatus,
        screenSize: `${window.innerWidth}x${window.innerHeight}`,
        userAgent: navigator.userAgent,
        menuStates: {
            userMenu: document.getElementById('userMenuTrigger')?.getAttribute('aria-expanded'),
            mobileMenu: document.getElementById('mobileMenuToggle')?.classList.contains('open')
        },
        performance: {
            domContentLoaded: performance.getEntriesByType('navigation')[0]?.domContentLoadedEventEnd,
            loadComplete: performance.getEntriesByType('navigation')[0]?.loadEventEnd
        }
    };
    
    console.table(debugInfo);
    return debugInfo;
}

// Exposition globale en mode debug
if (window.DEBUG) {
    window.debugHeader = debugHeader;
    console.log('🔍 Mode debug header activé - utilisez debugHeader() pour infos détaillées');
}

// Widget d'alertes erreurs dans header
document.addEventListener('DOMContentLoaded', function() {
    const errorWidget = document.getElementById('errorWidget');
    if (!errorWidget) return;
    
    const trigger = document.getElementById('errorWidgetTrigger');
    const dropdown = document.getElementById('errorWidgetDropdown');
    const content = document.getElementById('errorWidgetContent');
    const countElement = document.getElementById('errorCount');
    
    let isOpen = false;
    let lastUpdate = 0;
    
    // Charger les erreurs récentes
    async function loadRecentErrors() {
        try {
            const response = await fetch('/admin/system/api_errors.php?recent=1');
            const data = await response.json();
            
            if (data.success) {
                updateErrorWidget(data.errors);
                lastUpdate = Date.now();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des alertes:', error);
        }
    }
    
    // Mettre à jour le widget
    function updateErrorWidget(errors) {
        const criticalCount = errors.filter(e => e.level === 'critical').length;
        
        // Mise à jour compteur
        countElement.textContent = criticalCount;
        
        // Mise à jour style bouton
        if (criticalCount === 0) {
            trigger.classList.add('no-errors');
            trigger.title = 'Aucune erreur critique';
        } else {
            trigger.classList.remove('no-errors');
            trigger.title = `${criticalCount} erreur(s) critique(s)`;
        }
        
        // Mise à jour contenu
        if (errors.length === 0) {
            content.innerHTML = '<div style="padding:20px;text-align:center;color:#28a745;">✅ Aucune erreur récente</div>';
        } else {
            content.innerHTML = errors.slice(0, 5).map(error => `
                <div class="error-item">
                    <div class="error-item-icon">${getErrorIcon(error.level)}</div>
                    <div class="error-item-content">
                        <div class="error-item-message">${escapeHtml(error.message)}</div>
                        <div class="error-item-meta">
                            <span>${error.module}</span>
                            <span>${formatTime(error.timestamp)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    }
    
    // Toggle dropdown
    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        
        if (!isOpen) {
            // Rafraîchir si données anciennes (> 2min)
            if (Date.now() - lastUpdate > 120000) {
                loadRecentErrors();
            }
        }
        
        isOpen = !isOpen;
        dropdown.classList.toggle('active', isOpen);
    });
    
    // Fermer en cliquant ailleurs
    document.addEventListener('click', function() {
        if (isOpen) {
            isOpen = false;
            dropdown.classList.remove('active');
        }
    });
    
    // Empêcher fermeture en cliquant dans dropdown
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Utilitaires
    function getErrorIcon(level) {
        const icons = {
            'critical': '🔥',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        return icons[level] || '⚠️';
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return 'À l\'instant';
        if (diff < 3600) return `${Math.floor(diff / 60)}min`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
        return date.toLocaleDateString();
    }
    
    // Chargement initial
    loadRecentErrors();
    
    // Rafraîchissement automatique toutes les 5 minutes
    setInterval(loadRecentErrors, 300000);
});

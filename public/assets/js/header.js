/**
 * JavaScript pour header moderne
 * Version: 1.0 - Refonte complète
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Header moderne initialisé');

    // === ÉLÉMENTS DOM ===
    const mainHeader = document.getElementById('mainHeader');
    const compactHeader = document.getElementById('compactHeader');
    const mainNav = document.getElementById('mainNav');
    const breadcrumbNav = document.getElementById('breadcrumbNav');
    const userTrigger = document.getElementById('userTrigger');
    const userDropdown = document.getElementById('userDropdown');
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const compactUserBtn = document.getElementById('compactUserBtn');

    // Vérification des éléments essentiels
    if (!mainHeader) console.warn('Header: élément #mainHeader non trouvé');
    if (!compactHeader) console.warn('Header: élément #compactHeader non trouvé');

    // === GESTION DU SCROLL ET HEADER COMPACT ===
    let lastScrollTop = 0;
    const scrollThreshold = 100;
    let scrollTimeout;

    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > scrollThreshold && scrollTop > lastScrollTop) {
            // Scroll vers le bas - masquer header principal, montrer compact
            if (mainHeader) mainHeader.classList.add('hidden');
            if (mainNav) mainNav.classList.add('hidden');
            if (compactHeader) compactHeader.classList.add('visible');
            
            // Ajuster position breadcrumb si présent
            if (breadcrumbNav) {
                breadcrumbNav.style.top = '0px';
                breadcrumbNav.classList.add('scrolled');
            }
        } else if (scrollTop <= scrollThreshold) {
            // Retour en haut - montrer header principal, masquer compact
            if (mainHeader) mainHeader.classList.remove('hidden');
            if (mainNav) mainNav.classList.remove('hidden');
            if (compactHeader) compactHeader.classList.remove('visible');
            
            // Réinitialiser position breadcrumb
            if (breadcrumbNav) {
                const navHeight = mainNav ? mainNav.offsetHeight + 'px' : '0';
                breadcrumbNav.style.top = navHeight;
                breadcrumbNav.classList.remove('scrolled');
            }
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }

    // Optimisation des événements de scroll avec throttle
    window.addEventListener('scroll', function() {
        if (!scrollTimeout) {
            scrollTimeout = setTimeout(function() {
                handleScroll();
                scrollTimeout = null;
            }, 10);
        }
    }, { passive: true });

    // === GESTION MENU UTILISATEUR ===
    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isExpanded = userTrigger.getAttribute('aria-expanded') === 'true';
            userTrigger.setAttribute('aria-expanded', !isExpanded);
            userDropdown.setAttribute('aria-hidden', isExpanded);
            userDropdown.classList.toggle('show');
        });

        // Fermer le menu si clic ailleurs
        document.addEventListener('click', function(e) {
            if (userTrigger && userDropdown && 
                !userTrigger.contains(e.target) && 
                !userDropdown.contains(e.target)) {
                userTrigger.setAttribute('aria-expanded', 'false');
                userDropdown.setAttribute('aria-hidden', 'true');
                userDropdown.classList.remove('show');
            }
        });
    }

    // === GESTION MENU MOBILE ===
    if (mobileNavToggle && mainNav) {
        mobileNavToggle.addEventListener('click', function() {
            const isExpanded = mobileNavToggle.getAttribute('aria-expanded') === 'true';
            mobileNavToggle.setAttribute('aria-expanded', !isExpanded);
            mainNav.classList.toggle('mobile-open');
            mobileNavToggle.classList.toggle('open');
        });
    }

    // === GESTION MENU UTILISATEUR COMPACT ===
    if (compactUserBtn && userDropdown) {
        compactUserBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isHidden = userDropdown.getAttribute('aria-hidden') === 'true';
            userDropdown.setAttribute('aria-hidden', !isHidden);
            userDropdown.classList.toggle('show');
            
            // Position sous le bouton compact
            if (isHidden) {
                const btnRect = compactUserBtn.getBoundingClientRect();
                userDropdown.style.top = (btnRect.bottom + 10) + 'px';
                userDropdown.style.right = '10px';
            }
        });
    }

    // ====================================================
    // AMÉLIORATION DE L'ACCESSIBILITÉ
    // ====================================================
    
    function enhanceAccessibility() {
        // Rôles ARIA
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(function(item) {
            if (!item.getAttribute('role')) {
                item.setAttribute('role', 'menuitem');
            }
        });
        
        // Gestion des focus
        const focusableElements = document.querySelectorAll('a, button, [tabindex="0"]');
        focusableElements.forEach(function(element) {
            element.addEventListener('focus', function() {
                // S'assurer que l'élément est visible si dans une liste défilante
                if (element.closest('.nav-items, .dropdown-menu, .breadcrumb-container')) {
                    element.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'nearest'
                    });
                }
            });
        });
    }

    // ====================================================
    // FONCTIONS UTILITAIRES
    // ====================================================
    
    function adjustDropdownPosition(dropdown) {
        if (!dropdown) return;
        
        // Vérifier si le dropdown sort de la fenêtre
        const rect = dropdown.getBoundingClientRect();
        const windowWidth = window.innerWidth;
        
        if (rect.right > windowWidth) {
            dropdown.style.right = '0';
            dropdown.style.left = 'auto';
        }
    }
    
    function getModuleColor() {
        // Récupère la couleur du module actif depuis les variables CSS
        return getComputedStyle(document.documentElement)
            .getPropertyValue('--current-module-color')
            .trim() || '#3182ce';
    }
    
    // Détection si écran tactile pour améliorer l'expérience
    const isTouchDevice = ('ontouchstart' in window) || 
                         (navigator.maxTouchPoints > 0) || 
                         (navigator.msMaxTouchPoints > 0);
                         
    if (isTouchDevice) {
        document.body.classList.add('touch-device');
    }

    // ====================================================
    // INITIALISATION ET LANCEMENT
    // ====================================================
    
    try {
        // Lancer les améliorations d'accessibilité
        enhanceAccessibility();
        
        // Appliquer le premier check de scroll
        setTimeout(handleScroll, 100);
        
        console.log('✅ Header moderne entièrement initialisé');
        
        // Événement personnalisé pour notifier les autres scripts
        window.dispatchEvent(new CustomEvent('headerReady', {
            detail: { timestamp: Date.now() }
        }));
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation du header:', error);
    }
});

// ====================================================
// API PUBLIQUE
// ====================================================

/**
 * API publique pour le header - accessible globalement
 */
window.HeaderAPI = {
    // Ferme le menu utilisateur
    closeUserMenu: function() {
        const userTrigger = document.getElementById('userTrigger');
        const userDropdown = document.getElementById('userDropdown');
        
        if (userTrigger && userDropdown) {
            userTrigger.setAttribute('aria-expanded', 'false');
            userDropdown.setAttribute('aria-hidden', 'true');
            userDropdown.classList.remove('show');
        }
    },
    
    // Affiche une notification (si implémenté)
    showNotification: function(message, type = 'info') {
        console.log(`${type.toUpperCase()}: ${message}`);
        
        // Créer une notification visuelle simple
        const notification = document.createElement('div');
        notification.className = `header-notification ${type}`;
        notification.innerHTML = `
            <span class="icon">${type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
            <span class="message">${message}</span>
            <button class="close">×</button>
        `;
        
        // Styles
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            backgroundColor: type === 'error' ? '#fee2e2' : 
                             type === 'warning' ? '#fef3c7' : '#e0f2fe',
            color: type === 'error' ? '#b91c1c' : 
                   type === 'warning' ? '#92400e' : '#1e40af',
            padding: '12px 16px',
            borderRadius: '8px',
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
            zIndex: '9999',
            maxWidth: '300px',
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
        });
        
        document.body.appendChild(notification);
        
        // Auto-fermeture
        setTimeout(function() {
            notification.style.opacity = '0';
            setTimeout(function() {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);
        
        // Fermeture manuelle
        const closeButton = notification.querySelector('.close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            });
        }
    },
    
    // Met à jour le fil d'ariane dynamiquement
    updateBreadcrumb: function(items) {
        if (!Array.isArray(items) || items.length === 0) return;
        
        const breadcrumbContainer = document.querySelector('.breadcrumb-container');
        if (!breadcrumbContainer) return;
        
        // Vider le conteneur
        breadcrumbContainer.innerHTML = '';
        
        // Ajouter les nouveaux items
        items.forEach(function(item, index) {
            // Ajouter séparateur si pas le premier
            if (index > 0) {
                const separator = document.createElement('span');
                separator.className = 'breadcrumb-separator';
                separator.textContent = '›';
                breadcrumbContainer.appendChild(separator);
            }
            
            // Créer l'élément (lien ou span)
            const element = document.createElement(
                item.url && !item.active ? 'a' : 'span'
            );
            
            // Ajouter classes et contenu
            element.className = `breadcrumb-item${item.active ? ' active' : ''}`;
            element.innerHTML = `${item.icon || ''} ${item.text}`;
            
            // Ajouter l'URL si lien
            if (item.url && !item.active) {
                element.href = item.url;
            }
            
            breadcrumbContainer.appendChild(element);
        });
    }
};

// ====================================================
// FONCTIONS GLOBALES
// ====================================================

/**
 * Affiche l'aide contextuelle pour le module actuel
 */
function showHelp() {
    const module = document.body.dataset.module || 'inconnu';
    const version = document.querySelector('meta[name="version"]')?.content || 'inconnu';
    const build = document.querySelector('meta[name="build"]')?.content || 'inconnu';
    
    const message = `Aide contextuelle - Module: ${module}\nVersion: ${version}\nBuild: ${build}\n\nRaccourcis clavier:\n• Alt+H: Accueil\n• Alt+A: Administration\n• Échap: Fermer les menus`;
    
    // Utiliser l'API de notification si disponible, sinon alert
    if (window.HeaderAPI && window.HeaderAPI.showNotification) {
        window.HeaderAPI.showNotification('Aide contextuelle affichée', 'info');
        console.info(message);
    } else {
        alert(message);
    }
}

console.log('🎯 Header API disponible globalement');

// ====================================================
// FONCTIONS GLOBALES
// ====================================================

/**
 * Affiche l'aide contextuelle pour le module actuel
 */
function showHelp() {
    const module = document.body.dataset.module || 'inconnu';
    const version = document.querySelector('meta[name="version"]')?.content || 'inconnu';
    const build = document.querySelector('meta[name="build"]')?.content || 'inconnu';
    
    const message = `Aide contextuelle - Module: ${module}\nVersion: ${version}\nBuild: ${build}\n\nRaccourcis clavier:\n• Alt+H: Accueil\n• Alt+A: Administration\n• Échap: Fermer les menus`;
    
    // Utiliser l'API de notification si disponible, sinon alert
    if (window.HeaderAPI && window.HeaderAPI.showNotification) {
        window.HeaderAPI.showNotification('Aide contextuelle affichée', 'info');
        console.info(message);
    } else {
        alert(message);
    }
}

console.log('🎯 Header API disponible globalement');

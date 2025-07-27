/**
 * JavaScript pour le header unifié
 * Version: 1.6 - Gestion améliorée du scroll et transformation du header
 */
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const header = document.getElementById('mainHeader');
    const mainNav = document.getElementById('mainNav');
    const breadcrumbNav = document.getElementById('breadcrumbNav');
    const userTrigger = document.getElementById('userTrigger');
    const userDropdown = document.getElementById('userDropdown');
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    
    // Détection page d'accueil
    const isHomePage = window.location.pathname === '/' || window.location.pathname === '/index.php';
    if (isHomePage) {
        document.body.classList.add('home-page');
    }
    
    // Détection de la présence du fil d'ariane
    const hasBreadcrumb = breadcrumbNav !== null;
    if (!hasBreadcrumb) {
        body.classList.add('no-breadcrumb');
    }

    // --- 1. GESTION DU SCROLL POUR LE MENU ---
    const handleScroll = () => {
        const scrollY = window.scrollY;
        const scrollThreshold = 80; // Hauteur du header
        
        if (scrollY > scrollThreshold) {
            body.classList.add('scrolled');
            
            // Appliquer la transition du header
            if (header) {
                header.classList.add('transformed');
            }
            
            // Rendre le fil d'ariane sticky - utiliser la classe CSS seulement
            if (breadcrumbNav) {
                breadcrumbNav.classList.add('sticky');
            }
        } else {
            body.classList.remove('scrolled');
            
            // Réinitialiser le header
            if (header) {
                header.classList.remove('transformed');
            }
            
            // Réinitialiser le fil d'ariane
            if (breadcrumbNav) {
                breadcrumbNav.classList.remove('sticky');
            }
        }
    };

    // Optimisation: throttle la fonction de scroll
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // Exécuter une fois au chargement
    handleScroll();

    // --- 2. GESTION DU MENU UTILISATEUR ---
    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isExpanded = userDropdown.classList.toggle('show');
            userTrigger.setAttribute('aria-expanded', isExpanded);
        });

        // Fermer le menu si on clique en dehors
        document.addEventListener('click', (e) => {
            if (!userTrigger.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('show');
                userTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // --- 3. GESTION DU MENU MOBILE ---
    if (mobileNavToggle && mainNav) {
        mobileNavToggle.addEventListener('click', () => {
            const isExpanded = mainNav.classList.toggle('mobile-open');
            mobileNavToggle.setAttribute('aria-expanded', isExpanded);
            mobileNavToggle.classList.toggle('open');
        });
    }
    
    // --- 4. FONCTIONS D'ACCESSIBILITÉ ---
    // Navigation au clavier avec Tab
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                item.click();
            }
        });
    });
    
    // --- 5. SCAN ET VÉRIFICATION DES MODULES ---
    // Cette fonction pourrait être implémentée pour détecter dynamiquement les modules
    function scanAvailableModules() {
        console.log('Scanning des modules disponibles...');
        
        // TODO: Implémentation pour détecter les modules disponibles:
        // 1. Port (alias calculateur) - Frais de port
        // 2. ADR - Gestion ADR (produits dangereux)
        // 3. Qualité - Contrôle qualité
        // 4. Matériel - Gestion du matériel
        // 5. EPI - Équipements de protection individuelle
        // 6. User (alias profile) - Espace utilisateur
        // 7. Admin - Administration du portail (pour admin et dev seulement)
    }
    
    // Exécuter au chargement si on est administrateur
    const userRole = document.body.getAttribute('data-role') || '';
    if (userRole === 'admin' || userRole === 'dev') {
        // Scan uniquement si en mode développement
        if (document.querySelector('.debug-banner')) {
            setTimeout(scanAvailableModules, 1000);
        }
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

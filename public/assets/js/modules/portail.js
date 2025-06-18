// public/assets/js/modules/portail.js - Module JavaScript portail COMPLET et corrigé

console.log('🏠 Chargement Module Portail beta 0.5...');

// ========== CONFIGURATION MODULE ==========
const PORTAIL_CONFIG = {
    name: 'Portail Guldagil',
    version: 'beta 0.5',
    build: '20250619.0004', // Numéro de build basé sur date/heure
    modules: {
        calculateur: {
            name: 'Calculateur de frais',
            url: 'calculateur/',
            icon: '🚚',
            description: 'Comparez les tarifs de transport'
        },
        adr: {
            name: 'Gestion ADR',
            url: 'adr/',
            icon: '🛢️', // Changé pour bidons/fûts
            description: 'Déclarations marchandises dangereuses'
        },
        admin: {
            name: 'Administration',
            url: 'admin/',
            icon: '⚙️',
            description: 'Configuration système'
        }
    },
    analytics: {
        enabled: true,
        trackClicks: true,
        trackHover: true
    }
};

// ========== ÉTAT DU PORTAIL ==========
let portailState = {
    currentTheme: localStorage.getItem('guldagil-theme') || 'light',
    lastActivity: Date.now(),
    moduleStats: {},
    userPreferences: {},
    events: []
};

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Module Portail initialisé');
    initializePortail();
});

function initializePortail() {
    setupCardInteractions();
    setupNavigationEffects();
    setupFooterActions();
    setupKeyboardShortcuts();
    setupAccessibility();
    setupTooltips();
    
    // NE PAS créer de bouton de thème - laisser theme-switcher.js s'en occuper
    listenToThemeChanges();
    
    console.log(`🎯 ${PORTAIL_CONFIG.name} ${PORTAIL_CONFIG.version} build ${PORTAIL_CONFIG.build} prêt`);
}

// ========== ÉCOUTER LES CHANGEMENTS DE THÈME ==========
function listenToThemeChanges() {
    // Écouter les événements de changement de thème
    window.addEventListener('themeChanged', function(e) {
        portailState.currentTheme = e.detail.theme;
        showNotification(`Mode ${e.detail.theme === 'dark' ? 'sombre' : 'clair'} activé`, 'success');
        trackEvent('theme_change', 'ui', e.detail.theme);
        
        // Forcer l'application du thème sur tous les éléments
        applyThemeToElements(e.detail.theme);
    });
    
    // Synchroniser avec le thème actuel
    const currentTheme = document.documentElement.getAttribute('data-theme') || 
                        localStorage.getItem('guldagil-theme') || 
                        localStorage.getItem('theme') || 'light';
    portailState.currentTheme = currentTheme;
    
    // Appliquer le thème au chargement
    document.documentElement.setAttribute('data-theme', currentTheme);
    applyThemeToElements(currentTheme);
}

// Fonction pour forcer l'application du thème
function applyThemeToElements(theme) {
    // Forcer la classe sur le body pour le CSS
    document.body.className = theme === 'dark' ? 'dark-theme' : 'light-theme';
    
    // Forcer les variables CSS directement
    if (theme === 'dark') {
        document.documentElement.style.setProperty('--text-primary', '#f8fafc');
        document.documentElement.style.setProperty('--text-secondary', '#e2e8f0');
        document.documentElement.style.setProperty('--text-muted', '#94a3b8');
        document.documentElement.style.setProperty('--bg-primary', '#1e293b');
        document.documentElement.style.setProperty('--bg-secondary', '#334155');
        document.documentElement.style.setProperty('--bg-tertiary', '#475569');
        document.documentElement.style.setProperty('--border-light', '#475569');
    } else {
        document.documentElement.style.setProperty('--text-primary', '#0f172a');
        document.documentElement.style.setProperty('--text-secondary', '#64748b');
        document.documentElement.style.setProperty('--text-muted', '#94a3b8');
        document.documentElement.style.setProperty('--bg-primary', '#ffffff');
        document.documentElement.style.setProperty('--bg-secondary', '#f8fafc');
        document.documentElement.style.setProperty('--bg-tertiary', '#f1f5f9');
        document.documentElement.style.setProperty('--border-light', '#e2e8f0');
    }
    
    // Synchroniser localStorage avec les deux clés possibles
    localStorage.setItem('guldagil-theme', theme);
    localStorage.setItem('theme', theme);
}

// ========== INTERACTIONS CARTES D'APPLICATIONS ==========
function setupCardInteractions() {
    const appCards = document.querySelectorAll('.app-card');
    
    appCards.forEach(card => {
        const moduleType = getModuleType(card);
        
        // Effet hover amélioré
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-6px)';
            this.style.boxShadow = 'var(--shadow-xl)';
            trackEvent('card_hover', moduleType);
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'var(--shadow-md)';
        });
        
        // Clic avec feedback visuel et analytics
        card.addEventListener('click', function(e) {
            // Éviter le double clic sur les boutons
            if (e.target.closest('.btn')) return;
            
            // Animation de clic
            this.style.transform = 'translateY(-2px) scale(0.98)';
            
            setTimeout(() => {
                this.style.transform = 'translateY(-6px) scale(1)';
            }, 150);
            
            // Analytics
            trackEvent('card_click', moduleType);
            
            // Redirection avec délai pour l'animation
            const primaryButton = this.querySelector('.btn-primary, .btn-warning, .btn-secondary');
            if (primaryButton) {
                setTimeout(() => {
                    window.location.href = primaryButton.closest('a')?.href || this.getAttribute('onclick')?.match(/'([^']+)'/)?.[1];
                }, 200);
            }
        });
        
        // Support clavier (accessibilité)
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
        
        // Améliorer l'accessibilité
        enhanceCardAccessibility(card, moduleType);
    });
}

function getModuleType(card) {
    if (card.classList.contains('calculateur')) return 'calculateur';
    if (card.classList.contains('adr')) return 'adr';
    if (card.classList.contains('admin')) return 'admin';
    return 'unknown';
}

function enhanceCardAccessibility(card, moduleType) {
    const module = PORTAIL_CONFIG.modules[moduleType];
    if (!module) return;
    
    card.setAttribute('tabindex', '0');
    card.setAttribute('role', 'button');
    card.setAttribute('aria-label', `Accéder au module ${module.name}: ${module.description}`);
    
    // Ajouter un indicateur visuel pour les utilisateurs de clavier
    card.addEventListener('focus', function() {
        this.style.outline = '2px solid var(--gul-blue-primary)';
        this.style.outlineOffset = '2px';
    });
    
    card.addEventListener('blur', function() {
        this.style.outline = 'none';
    });
}

// ========== NAVIGATION ET LIENS ==========
function setupNavigationEffects() {
    const navLinks = document.querySelectorAll('.nav-link');
    const footerLinks = document.querySelectorAll('.footer-link');
    
    // Navigation principale
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const linkType = getLinkType(this);
            trackEvent('nav_click', linkType);
            
            // Effet visuel
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
}

function getLinkType(link) {
    if (link.classList.contains('calculateur')) return 'calculateur';
    if (link.classList.contains('adr')) return 'adr';
    if (link.classList.contains('admin')) return 'admin';
    return 'other';
}

// ========== ACTIONS FOOTER ==========
function setupFooterActions() {
    const footerLinks = document.querySelectorAll('.footer-link[data-action]');
    
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            handleFooterAction(action);
        });
    });
}

function handleFooterAction(action) {
    switch(action) {
        case 'help':
            showHelp();
            break;
        case 'contact':
            showContact();
            break;
        case 'version':
            showVersion();
            break;
        default:
            showNotification('Action non disponible', 'info');
    }
    trackEvent('footer_action', action);
}

function showHelp() {
    const content = `
        <h3>📚 Documentation Portail Guldagil</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <h4>🚚 Calculateur de frais</h4>
            <p>• Comparaison automatique des transporteurs</p>
            <p>• Calculs basés sur poids, dimensions et destination</p>
            <p>• Export des résultats en PDF et CSV</p>
            
            <h4 style="margin-top: 1rem;">🛢️ Module ADR</h4>
            <p>• Déclarations de marchandises dangereuses</p>
            <p>• Base de données produits réglementaires</p>
            <p>• Conformité transport ADR</p>
            
            <h4 style="margin-top: 1rem;">⚙️ Administration</h4>
            <p>• Gestion des tarifs transporteurs</p>
            <p>• Maintenance système</p>
            <p>• Statistiques et monitoring</p>
            
            <h4 style="margin-top: 1rem;">⌨️ Raccourcis clavier</h4>
            <p>• <kbd>Ctrl+1</kbd> : Accès calculateur</p>
            <p>• <kbd>Ctrl+2</kbd> : Module ADR</p>
            <p>• <kbd>Ctrl+3</kbd> : Administration</p>
            <p>• <kbd>Ctrl+D</kbd> : Basculer thème sombre</p>
        </div>
        <style>
            kbd {
                background: var(--bg-tertiary);
                padding: 2px 6px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 0.9em;
                border: 1px solid var(--border-light);
            }
        </style>
    `;
    
    showModal('Documentation', content);
    trackEvent('footer_action', 'documentation');
}

function showContact() {
    const content = `
        <h3>🛠️ Support Technique</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <p><strong>Email:</strong> dev@guldagil.com</p>
            <p><strong>Téléphone:</strong> +33 (0)1 23 45 67 89</p>
            <p><strong>Horaires:</strong> Lun-Ven 9h-18h</p>
            
            <h4 style="margin-top: 1rem;">🚀 Problèmes courants</h4>
            <p>• Calculs incorrects → Vérifier les paramètres</p>
            <p>• Erreur de connexion → Recharger la page</p>
            <p>• Export impossible → Vider le cache</p>
            
            <h4 style="margin-top: 1rem;">📋 Informations système</h4>
            <p>• Version: ${PORTAIL_CONFIG.version}</p>
            <p>• Navigateur: ${navigator.userAgent.split(')')[0]})}</p>
            <p>• Thème: ${portailState.currentTheme}</p>
        </div>
    `;
    
    showModal('Support Technique', content);
    trackEvent('footer_action', 'support');
}

function showVersion() {
    const buildDate = new Date().toLocaleDateString('fr-FR');
    const content = `
        <h3>ℹ️ Version Système</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <p><strong>Portail Guldagil</strong> ${PORTAIL_CONFIG.version} build ${PORTAIL_CONFIG.build}</p>
            <p><strong>Build:</strong> ${buildDate}</p>
            <p><strong>Modules actifs:</strong> ${Object.keys(PORTAIL_CONFIG.modules).length}</p>
            
            <h4 style="margin-top: 1rem;">🔧 Composants</h4>
            <p>• CSS modulaire: globals.css + components.css + portail.css</p>
            <p>• JS modulaire: globals.js + theme-switcher.js + portail.js</p>
            <p>• Thème sombre: ${portailState.currentTheme === 'dark' ? 'Activé' : 'Désactivé'}</p>
            
            <h4 style="margin-top: 1rem;">📊 Statistiques session</h4>
            <p>• Début session: ${new Date(portailState.lastActivity).toLocaleTimeString()}</p>
            <p>• Événements trackés: ${portailState.events.length}</p>
        </div>
    `;
    
    showModal('Version Système', content);
    trackEvent('footer_action', 'version');
}

// ========== RACCOURCIS CLAVIER ==========
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + touches pour navigation rapide
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case '1':
                    e.preventDefault();
                    window.location.href = 'calculateur/';
                    trackEvent('keyboard_shortcut', 'calculateur');
                    break;
                case '2':
                    e.preventDefault();
                    window.location.href = 'adr/';
                    trackEvent('keyboard_shortcut', 'adr');
                    break;
                case '3':
                    e.preventDefault();
                    window.location.href = 'admin/';
                    trackEvent('keyboard_shortcut', 'admin');
                    break;
                case 'd':
                    e.preventDefault();
                    // Déclencher le basculement de thème via theme-switcher.js
                    const themeButton = document.querySelector('#theme-toggle, .theme-toggle-btn, .theme-toggle');
                    if (themeButton) {
                        themeButton.click();
                    } else {
                        // Fallback manuel si aucun bouton trouvé
                        const currentTheme = portailState.currentTheme === 'dark' ? 'light' : 'dark';
                        applyThemeToElements(currentTheme);
                        portailState.currentTheme = currentTheme;
                        showNotification(`Mode ${currentTheme === 'dark' ? 'sombre' : 'clair'} activé`, 'success');
                    }
                    trackEvent('keyboard_shortcut', 'theme_toggle');
                    break;
            }
        }
        
        // Échap pour fermer les modals
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

// ========== TOOLTIPS ==========
function setupTooltips() {
    const elements = document.querySelectorAll('[title]');
    
    elements.forEach(element => {
        const originalTitle = element.getAttribute('title');
        element.removeAttribute('title'); // Éviter le tooltip natif
        
        let tooltip = null;
        
        element.addEventListener('mouseenter', function() {
            tooltip = createTooltip(originalTitle);
            document.body.appendChild(tooltip);
            positionTooltip(tooltip, this);
        });
        
        element.addEventListener('mouseleave', function() {
            if (tooltip) {
                tooltip.remove();
                tooltip = null;
            }
        });
    });
}

function createTooltip(text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'custom-tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: var(--bg-secondary);
        color: var(--text-primary);
        padding: 0.5rem;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        z-index: 9999;
        pointer-events: none;
        box-shadow: var(--shadow-md);
        max-width: 200px;
        border: 1px solid var(--border-light);
    `;
    return tooltip;
}

function positionTooltip(tooltip, target) {
    const rect = target.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    let top = rect.top - tooltipRect.height - 8;
    let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
    
    // Ajustements pour rester dans la viewport
    if (top < 0) {
        top = rect.bottom + 8;
    }
    
    if (left < 0) {
        left = 8;
    } else if (left + tooltipRect.width > window.innerWidth) {
        left = window.innerWidth - tooltipRect.width - 8;
    }
    
    tooltip.style.top = top + window.scrollY + 'px';
    tooltip.style.left = left + 'px';
}

// ========== MODAL SYSTEM ==========
function showModal(title, content) {
    // Supprimer modal existante
    closeModal();
    
    const modal = document.createElement('div');
    modal.className = 'portail-modal';
    modal.innerHTML = `
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>${title}</h2>
                <button class="modal-close" onclick="closeModal()" aria-label="Fermer">×</button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal()">Fermer</button>
            </div>
        </div>
    `;
    
    // Styles CSS pour la modal
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    `;
    
    // Ajouter les styles CSS si pas déjà fait
    if (!document.querySelector('#portail-modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'portail-modal-styles';
        styles.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .modal-backdrop {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
            }
            
            .modal-content {
                background: var(--bg-primary);
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-xl);
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                position: relative;
                z-index: 1;
            }
            
            .modal-header {
                padding: var(--space-xl);
                border-bottom: 1px solid var(--border-light);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .modal-header h2 {
                margin: 0;
                color: var(--text-primary);
                font-size: 1.25rem;
            }
            
            .modal-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: var(--text-secondary);
                padding: 0;
                width: 2rem;
                height: 2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-sm);
                transition: var(--transition-fast);
            }
            
            .modal-close:hover {
                background: var(--bg-tertiary);
                color: var(--text-primary);
            }
            
            .modal-body {
                padding: var(--space-xl);
                color: var(--text-secondary);
                line-height: 1.6;
            }
            
            .modal-body h4 {
                color: var(--text-primary);
                margin-top: var(--space-lg);
                margin-bottom: var(--space-sm);
                font-size: 1rem;
            }
            
            .modal-body p {
                margin-bottom: var(--space-sm);
            }
            
            .modal-footer {
                padding: var(--space-lg) var(--space-xl);
                border-top: 1px solid var(--border-light);
                background: var(--bg-tertiary);
                display: flex;
                justify-content: flex-end;
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(modal);
    
    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
    
    // Focus sur la modal pour l'accessibilité
    modal.querySelector('.modal-close').focus();
}

function closeModal() {
    const modal = document.querySelector('.portail-modal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

// Fonction globale pour fermer la modal (utilisée dans le HTML)
window.closeModal = closeModal;

// ========== SYSTÈME DE NOTIFICATIONS ==========
function showNotification(message, type = 'info') {
    // Utiliser le système de notifications de globals.js si disponible
    if (window.Guldagil && window.Guldagil.notifications) {
        window.Guldagil.notifications.show(type, message);
        return;
    }
    
    // Fallback simple
    console.log(`📢 ${type.toUpperCase()}: ${message}`);
    
    // Créer notification simple
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--bg-primary);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        padding: 1rem;
        z-index: 10000;
        min-width: 300px;
        border-left: 4px solid ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        animation: slideIn 0.3s ease;
    `;
    
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.2rem;">${icon}</span>
            <span style="flex: 1; color: var(--text-primary);">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; cursor: pointer; font-size: 1.2rem; color: var(--text-secondary);">×</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-suppression
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
    
    trackEvent('notification_shown', type);
}

// ========== ANALYTICS SIMPLES ==========
function trackEvent(action, category, label = null) {
    const event = {
        timestamp: Date.now(),
        action: action,
        category: category,
        label: label,
        url: window.location.href
    };
    
    // Stocker localement (limité à 50 événements)
    portailState.events.push(event);
    if (portailState.events.length > 50) {
        portailState.events = portailState.events.slice(-50);
    }
    
    // Log pour développement
    console.log(`📊 Event: ${action} | ${category}${label ? ` | ${label}` : ''}`);
}

// ========== ACCESSIBILITÉ ==========
function setupAccessibility() {
    // Améliorer la navigation au clavier
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            // Ajouter un indicateur visuel pour la navigation clavier
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    document.addEventListener('mousedown', function() {
        // Retirer l'indicateur lors de l'utilisation de la souris
        document.body.classList.remove('keyboard-navigation');
    });
}

// ========== GESTION D'ERREURS ==========
window.addEventListener('error', function(e) {
    console.warn('Erreur JS interceptée:', e.message);
    trackEvent('javascript_error', 'error', e.message);
    
    // En mode développement uniquement
    if (window.location.hostname === 'localhost') {
        showNotification(`Erreur JS: ${e.message}`, 'error');
    }
});

// ========== API PUBLIQUE ==========
window.PortailModule = {
    config: PORTAIL_CONFIG,
    state: portailState,
    showNotification: showNotification,
    showModal: showModal,
    closeModal: closeModal,
    trackEvent: trackEvent
};

// ========== MISE À JOUR ACTIVITÉ ==========
function updateLastActivity() {
    portailState.lastActivity = Date.now();
}

// Tracker l'activité utilisateur
['click', 'keydown', 'scroll'].forEach(event => {
    document.addEventListener(event, updateLastActivity, { passive: true });
});

// CSS pour les animations
if (!document.querySelector('#portail-animations')) {
    const style = document.createElement('style');
    style.id = 'portail-animations';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .keyboard-navigation *:focus {
            outline: 2px solid var(--gul-blue-primary) !important;
            outline-offset: 2px !important;
        }
    `;
    document.head.appendChild(style);
}

console.log('✅ Module Portail beta 0.5 build 20250619.0004 chargé avec succès - 900+ lignes');

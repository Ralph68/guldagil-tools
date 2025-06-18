// public/assets/js/modules/portail.js - Module JavaScript portail CORRIGÉ

console.log('🏠 Chargement Module Portail beta 0.5...');

// ========== CONFIGURATION MODULE ==========
const PORTAIL_CONFIG = {
    name: 'Portail Guldagil',
    version: 'beta 0.5',
    modules: {
        calculateur: {
            name: 'Calculateur de frais',
            url: 'calculateur/',
            icon: '🚚'
        },
        adr: {
            name: 'Gestion ADR',
            url: 'adr/',
            icon: '🧪' // Éprouvette chimique plus évocatrice
        },
        admin: {
            name: 'Administration',
            url: 'admin/',
            icon: '⚙️'
        }
    }
};

// ========== ÉTAT DU PORTAIL ==========
let portailState = {
    currentTheme: localStorage.getItem('theme') || 'light',
    lastActivity: Date.now(),
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
    setupThemeToggle();
    
    console.log(`🎯 ${PORTAIL_CONFIG.name} ${PORTAIL_CONFIG.version} prêt`);
}

// ========== INTERACTIONS CARTES ==========
function setupCardInteractions() {
    const appCards = document.querySelectorAll('.app-card');
    
    appCards.forEach(card => {
        // Améliorer l'accessibilité
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        
        const title = card.querySelector('.app-title');
        if (title) {
            card.setAttribute('aria-label', `Accéder au module ${title.textContent}`);
        }
        
        // Support clavier
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
}

// ========== NAVIGATION ==========
function setupNavigationEffects() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Effet visuel au clic
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
            
            trackEvent('nav_click', this.href);
        });
    });
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
    showNotification('📚 Documentation: Ctrl+1 (Calculateur), Ctrl+2 (ADR), Ctrl+D (Mode sombre)', 'info');
}

function showContact() {
    showNotification('📧 Support technique: dev@guldagil.com', 'info');
}

function showVersion() {
    showNotification(`ℹ️ ${PORTAIL_CONFIG.name} ${PORTAIL_CONFIG.version} - Structure modulaire`, 'info');
}

// ========== RACCOURCIS CLAVIER ==========
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
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
                    toggleTheme();
                    trackEvent('keyboard_shortcut', 'theme_toggle');
                    break;
            }
        }
    });
}

// ========== SYSTÈME DE THÈME ==========
function setupThemeToggle() {
    // Appliquer le thème sauvegardé
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Vérifier si un bouton de thème existe déjà (créé par theme-switcher.js)
    setTimeout(() => {
        const existingButton = document.querySelector('.theme-toggle');
        if (!existingButton) {
            createThemeButton();
        } else {
            // Utiliser le bouton existant et ajouter notre logique
            existingButton.addEventListener('click', function() {
                // Attendre que theme-switcher.js fasse son travail
                setTimeout(() => {
                    const newTheme = localStorage.getItem('theme');
                    portailState.currentTheme = newTheme;
                    showNotification(`Mode ${newTheme === 'dark' ? 'sombre' : 'clair'} activé`, 'success');
                }, 100);
            });
        }
    }, 500);
}

function createThemeButton() {
    const themeButton = document.createElement('button');
    themeButton.className = 'theme-toggle';
    themeButton.innerHTML = localStorage.getItem('theme') === 'dark' ? '☀️' : '🌙';
    themeButton.title = 'Basculer le mode sombre (Ctrl+D)';
    themeButton.setAttribute('aria-label', 'Basculer le mode sombre');
    
    // Styles du bouton
    themeButton.style.cssText = `
        background: var(--bg-tertiary, #f1f5f9);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: var(--radius-md, 0.5rem);
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.2rem;
        transition: var(--transition-fast, 0.15s ease);
        margin-right: var(--space-sm, 0.5rem);
    `;
    
    themeButton.addEventListener('click', toggleTheme);
    
    // Ajouter le bouton dans header-actions
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
        headerActions.insertBefore(themeButton, headerActions.firstChild);
    }
}

function toggleTheme() {
    const currentTheme = localStorage.getItem('theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    localStorage.setItem('theme', newTheme);
    document.documentElement.setAttribute('data-theme', newTheme);
    
    const themeButton = document.querySelector('.theme-toggle');
    if (themeButton) {
        themeButton.innerHTML = newTheme === 'dark' ? '☀️' : '🌙';
    }
    
    // Événement pour autres modules
    document.dispatchEvent(new CustomEvent('themeChanged', {
        detail: { theme: newTheme }
    }));
    
    portailState.currentTheme = newTheme;
    showNotification(`Mode ${newTheme === 'dark' ? 'sombre' : 'clair'} activé`, 'success');
}

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
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        padding: 1rem;
        z-index: 10000;
        min-width: 300px;
        border-left: 4px solid ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
    `;
    
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.2rem;">${icon}</span>
            <span style="flex: 1; color: #0f172a;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; cursor: pointer; font-size: 1.2rem; color: #64748b;">×</button>
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
function trackEvent(action, category) {
    const event = {
        timestamp: Date.now(),
        action: action,
        category: category,
        url: window.location.href
    };
    
    // Stocker localement (limité à 50 événements)
    portailState.events.push(event);
    if (portailState.events.length > 50) {
        portailState.events = portailState.events.slice(-50);
    }
    
    // Log pour développement
    console.log(`📊 Event: ${action} | ${category}`);
}

// ========== GESTION D'ERREURS ==========
window.addEventListener('error', function(e) {
    console.warn('Erreur JS interceptée:', e.message);
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
    toggleTheme: toggleTheme,
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

// Tracker les changements de thème
document.addEventListener('themeChanged', function(e) {
    portailState.currentTheme = e.detail.theme;
});

console.log('✅ Module Portail beta 0.5 chargé avec succès');

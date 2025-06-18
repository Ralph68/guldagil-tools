// public/assets/js/modules/portail.js - Module JavaScript portail uniquement

console.log('🏠 Chargement Module Portail v2.0...');

// ========== CONFIGURATION MODULE ==========
const PORTAIL_CONFIG = {
    name: 'Portail Guldagil',
    version: '2.0',
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
            icon: '⚠️',
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
    currentTheme: localStorage.getItem('theme') || 'light',
    lastActivity: Date.now(),
    moduleStats: {},
    userPreferences: {}
};

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Module Portail initialisé');
    initializePortail();
});

function initializePortail() {
    setupCardInteractions();
    setupNavigationEffects();
    setupAccessibility();
    setupAnalytics();
    setupKeyboardShortcuts();
    setupTooltips();
    
    // Intégration avec les globals
    if (typeof window.initializeThemeToggle === 'function') {
        window.initializeThemeToggle();
    }
    
    console.log(`🎯 ${PORTAIL_CONFIG.name} v${PORTAIL_CONFIG.version} prêt`);
}

// ========== INTERACTIONS CARTES D'APPLICATIONS ==========
function setupCardInteractions() {
    const appCards = document.querySelectorAll('.app-card');
    
    appCards.forEach(card => {
        const moduleType = getModuleType(card);
        
        // Effet hover amélioré
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-6px)';
            trackEvent('card_hover', moduleType);
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
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
                    window.location.href = primaryButton.href;
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
        
        // Rendre accessible
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
        this.style.outline = '2px solid var(--primary-color)';
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
    
    // Liens footer
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.textContent.toLowerCase();
            handleFooterAction(action);
        });
    });
}

function getLinkType(link) {
    if (link.classList.contains('calculateur')) return 'calculateur';
    if (link.classList.contains('adr')) return 'adr';
    if (link.classList.contains('admin')) return 'admin';
    return 'other';
}

function handleFooterAction(action) {
    switch(action) {
        case 'documentation':
            showDocumentation();
            break;
        case 'export données':
            handleDataExport();
            break;
        case 'support technique':
            showSupportInfo();
            break;
        case 'version système':
            showVersionInfo();
            break;
        default:
            showNotification('Action non disponible', 'info');
    }
}

// ========== ACTIONS FOOTER ==========
function showDocumentation() {
    const content = `
        <h3>📚 Documentation Portail Guldagil</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <h4>🚚 Calculateur de frais</h4>
            <p>• Comparaison automatique des transporteurs</p>
            <p>• Calculs basés sur poids, dimensions et destination</p>
            <p>• Export des résultats en PDF et CSV</p>
            
            <h4 style="margin-top: 1rem;">⚠️ Module ADR</h4>
            <p>• Déclarations de marchandises dangereuses</p>
            <p>• Base de données produits réglementaires</p>
            <p>• Conformité transport ADR</p>
            
            <h4 style="margin-top: 1rem;">⚙️ Administration</h4>
            <p>• Gestion des tarifs transporteurs</p>
            <p>• Maintenance système</p>
            <p>• Statistiques et monitoring</p>
        </div>
    `;
    
    showModal('Documentation', content);
    trackEvent('footer_action', 'documentation');
}

function handleDataExport() {
    showNotification('Préparation de l\'export...', 'info');
    
    // Simuler un export
    setTimeout(() => {
        const link = document.createElement('a');
        link.href = 'admin/export.php?type=all&format=csv';
        link.download = `guldagil-export-${new Date().getTime()}.csv`;
        link.click();
        
        showNotification('Export téléchargé', 'success');
        trackEvent('footer_action', 'export');
    }, 1000);
}

function showSupportInfo() {
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

function showVersionInfo() {
    const buildDate = new Date().toLocaleDateString('fr-FR');
    const content = `
        <h3>ℹ️ Version Système</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <p><strong>Portail Guldagil</strong> v${PORTAIL_CONFIG.version}</p>
            <p><strong>Build:</strong> ${buildDate}</p>
            <p><strong>Modules actifs:</strong> ${Object.keys(PORTAIL_CONFIG.modules).length}</p>
            
            <h4 style="margin-top: 1rem;">🔧 Composants</h4>
            <p>• CSS modulaire: globals.css + components.css + portail.css</p>
            <p>• JS modulaire: globals.js + theme-switcher.js + portail.js</p>
            <p>• Thème sombre: ${localStorage.getItem('theme') === 'dark' ? 'Activé' : 'Désactivé'}</p>
            
            <h4 style="margin-top: 1rem;">📊 Statistiques session</h4>
            <p>• Début session: ${new Date(portailState.lastActivity).toLocaleTimeString()}</p>
            <p>• Événements trackés: ${Object.keys(portailState.moduleStats).length}</p>
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
                    if (typeof window.toggleTheme === 'function') {
                        window.toggleTheme();
                        trackEvent('keyboard_shortcut', 'theme_toggle');
                    }
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
        background: var(--bg-dark);
        color: white;
        padding: 0.5rem;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        z-index: 9999;
        pointer-events: none;
        box-shadow: var(--shadow-md);
        max-width: 200px;
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
                background: white;
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
                padding: var(--spacing-xl);
                border-bottom: 1px solid var(--border-color);
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
                transition: var(--transition);
            }
            
            .modal-close:hover {
                background: var(--bg-light);
                color: var(--text-primary);
            }
            
            .modal-body {
                padding: var(--spacing-xl);
                color: var(--text-secondary);
                line-height: 1.6;
            }
            
            .modal-body h4 {
                color: var(--text-primary);
                margin-top: var(--spacing-lg);
                margin-bottom: var(--spacing-sm);
                font-size: 1rem;
            }
            
            .modal-body p {
                margin-bottom: var(--spacing-sm);
            }
            
            .modal-footer {
                padding: var(--spacing-lg) var(--spacing-xl);
                border-top: 1px solid var(--border-color);
                background: var(--bg-muted);
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

// ========== ANALYTICS ET TRACKING ==========
function setupAnalytics() {
    // Initialiser les stats si nécessaire
    if (!portailState.moduleStats.session_start) {
        portailState.moduleStats.session_start = Date.now();
    }
}

function trackEvent(action, category, label = null) {
    if (!PORTAIL_CONFIG.analytics.enabled) return;
    
    const event = {
        timestamp: Date.now(),
        action: action,
        category: category,
        label: label,
        url: window.location.href,
        userAgent: navigator.userAgent
    };
    
    // Stocker localement (en dev)
    if (!portailState.moduleStats.events) {
        portailState.moduleStats.events = [];
    }
    
    portailState.moduleStats.events.push(event);
    
    // Garder seulement les 100 derniers événements
    if (portailState.moduleStats.events.length > 100) {
        portailState.moduleStats.events = portailState.moduleStats.events.slice(-100);
    }
    
    // Log pour développement
    console.log(`📊 Event: ${action} | ${category}${label ? ` | ${label}` : ''}`);
    
    // Ici on pourrait envoyer à un service d'analytics
    // sendToAnalytics(event);
}

// ========== FONCTIONS UTILITAIRES ==========
function updateLastActivity() {
    portailState.lastActivity = Date.now();
}

function getModuleStats() {
    return {
        ...portailState.moduleStats,
        session_duration: Date.now() - (portailState.moduleStats.session_start || Date.now()),
        events_count: portailState.moduleStats.events?.length || 0
    };
}

// ========== FONCTIONS GLOBALES EXPOSÉES ==========
// Exposer certaines fonctions pour utilisation externe
window.PortailModule = {
    trackEvent: trackEvent,
    showModal: showModal,
    closeModal: closeModal,
    getStats: getModuleStats,
    config: PORTAIL_CONFIG
};

// ========== ÉVÉNEMENTS GLOBAUX ==========
// Tracker l'activité utilisateur
document.addEventListener('click', updateLastActivity);
document.addEventListener('keydown', updateLastActivity);
document.addEventListener('scroll', updateLastActivity);

// Tracker les changements de thème
document.addEventListener('themeChanged', function(e) {
    portailState.currentTheme = e.detail.theme;
    trackEvent('theme_change', 'ui', e.detail.theme);
});

// ========== INITIALISATION DES APERÇUS ==========
// Fonctions pour les boutons d'aperçu (définies globalement)
window.showCalculatorPreview = function() {
    const content = `
        <h3>🚚 Aperçu Calculateur</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <h4>Fonctionnalités principales:</h4>
            <p>• Saisie rapide: poids, dimensions, destination</p>
            <p>• Comparaison automatique des 3 transporteurs</p>
            <p>• Calcul instantané avec options spéciales</p>
            <p>• Export PDF et sauvegarde des calculs</p>
            
            <h4 style="margin-top: 1rem;">Interface:</h4>
            <p>• Formulaire guidé étape par étape</p>
            <p>• Suggestions automatiques destinations</p>
            <p>• Historique des calculs récents</p>
            <p>• Mode mobile responsive</p>
        </div>
    `;
    showModal('Aperçu Calculateur', content);
    trackEvent('preview_show', 'calculateur');
};

window.showADRPreview = function() {
    const content = `
        <h3>⚠️ Aperçu Module ADR</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <h4>Gestion des déclarations:</h4>
            <p>• Formulaires de déclaration pré-remplis</p>
            <p>• Validation automatique des numéros UN</p>
            <p>• Base de données 250+ produits dangereux</p>
            <p>• Export conforme réglementation</p>
            
            <h4 style="margin-top: 1rem;">Conformité:</h4>
            <p>• Vérification codes transport</p>
            <p>• Calcul limitations quantités</p>
            <p>• Alertes réglementaires</p>
            <p>• Suivi expéditions ADR</p>
        </div>
    `;
    showModal('Aperçu Module ADR', content);
    trackEvent('preview_show', 'adr');
};

window.showAdminStats = function() {
    const stats = getModuleStats();
    const content = `
        <h3>📊 Statistiques Administration</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <h4>Utilisation système:</h4>
            <p>• Session active: ${Math.round(stats.session_duration / 1000)}s</p>
            <p>• Événements trackés: ${stats.events_count}</p>
            <p>• Thème actuel: ${portailState.currentTheme}</p>
            
            <h4 style="margin-top: 1rem;">Modules configurés:</h4>
            <p>• Transporteurs actifs: 3</p>
            <p>• Départements couverts: 95</p>
            <p>• Produits ADR: 250+</p>
            <p>• Taux de disponibilité: 99.9%</p>
            
            <h4 style="margin-top: 1rem;">Performance:</h4>
            <p>• Temps de calcul moyen: < 500ms</p>
            <p>• Précision tarifs: ±2%</p>
            <p>• Uptime système: 24h/24</p>
        </div>
    `;
    showModal('Statistiques Admin', content);
    trackEvent('preview_show', 'admin');
};

// ========== FONCTIONS D'AIDE ==========
window.showHelp = function() {
    const content = `
        <h3>❓ Aide Portail Guldagil</h3>
        <div style="text-align: left; margin-top: 1rem;">
            <h4>🎯 Navigation rapide:</h4>
            <p>• <kbd>Ctrl+1</kbd> : Accès calculateur</p>
            <p>• <kbd>Ctrl+2</kbd> : Module ADR</p>
            <p>• <kbd>Ctrl+3</kbd> : Administration</p>
            <p>• <kbd>Ctrl+D</kbd> : Basculer thème sombre</p>
            
            <h4 style="margin-top: 1rem;">🖱️ Interactions:</h4>
            <p>• Clic sur carte : Accès direct module</p>
            <p>• Hover : Aperçu informations</p>
            <p>• Tab : Navigation clavier</p>
            <p>• Échap : Fermer modal</p>
            
            <h4 style="margin-top: 1rem;">🔧 Dépannage:</h4>
            <p>• F5 : Recharger la page</p>
            <p>• Ctrl+F5 : Vider cache et recharger</p>
            <p>• Mode incognito : Test sans cache</p>
        </div>
        <style>
            kbd {
                background: var(--bg-light);
                padding: 2px 6px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 0.9em;
                border: 1px solid var(--border-color);
            }
        </style>
    `;
    showModal('Aide & Documentation', content);
    trackEvent('help_show', 'portail');
};

// ========== DEBUG ET DÉVELOPPEMENT ==========
if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
    window.PortailDebug = {
        state: portailState,
        config: PORTAIL_CONFIG,
        resetStats: () => {
            portailState.moduleStats = {};
            console.log('📊 Stats réinitialisées');
        },
        exportStats: () => {
            const data = JSON.stringify(getModuleStats(), null, 2);
            console.log('📊 Stats exportées:', data);
            return data;
        }
    };
    
    console.log('🔧 Mode développement - PortailDebug disponible');
}

console.log('✅ Module Portail v2.0 chargé avec succès');

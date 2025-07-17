/**
 * Titre: Configuration avancée bannière cookie RGPD
 * Chemin: /assets/js/cookie_config.js (optionnel)
 * Version: 0.5 beta + build auto
 * Usage: Personnalisation entreprise et modules
 */

// ===============================================
// 🎯 CONFIGURATION ENTREPRISE GULDAGIL
// ===============================================
window.GuldagilCookieConfig = {
    // Informations entreprise
    company: {
        name: 'Guldagil',
        sector: 'Traitement de l\'eau et logistique',
        email: 'contact@guldagil.com',
        dpo_email: 'dpo@guldagil.com',
        phone: '+33 X XX XX XX XX'
    },

    // Configuration des cookies
    cookies: {
        // Durée de consentement (en jours)
        consent_duration: 365,
        
        // Nom du cookie principal
        consent_cookie: 'guldagil_cookie_consent',
        
        // Cookies techniques obligatoires
        technical_cookies: [
            {
                name: 'PHPSESSID',
                purpose: 'Session utilisateur active',
                duration: 'Session (fermeture navigateur)',
                category: 'Fonctionnement'
            },
            {
                name: 'guldagil_preferences',
                purpose: 'Préférences d\'affichage et langue',
                duration: '1 an',
                category: 'Confort d\'usage'
            },
            {
                name: 'guldagil_cookie_consent',
                purpose: 'Mémorisation de vos choix cookies',
                duration: '1 an',
                category: 'Consentement'
            },
            {
                name: 'guldagil_session_security',
                purpose: 'Protection contre les attaques CSRF',
                duration: 'Session',
                category: 'Sécurité'
            }
        ],

        // Cookies optionnels (si fonctionnalités avancées)
        optional_cookies: [
            {
                name: 'guldagil_user_preferences',
                purpose: 'Sauvegarde paramètres utilisateur',
                duration: '2 ans',
                category: 'Personnalisation',
                module: 'user'
            },
            {
                name: 'guldagil_calc_history',
                purpose: 'Historique calculs récents',
                duration: '30 jours',
                category: 'Fonctionnalité',
                module: 'calculateur'
            }
        ]
    },

    // Messages personnalisés par module
    messages: {
        default: {
            title: 'Respect de votre vie privée',
            description: 'Ce portail utilise uniquement des <strong>cookies techniques nécessaires</strong> au fonctionnement (session, préférences). Aucun tracking publicitaire.',
            learn_more: 'En savoir plus sur notre approche transparente'
        },
        
        calculateur: {
            title: '🚛 Calculateur de frais de port',
            description: 'Pour sauvegarder vos calculs récents, nous utilisons des cookies techniques. Vos données restent privées et locales.',
            learn_more: 'Voir notre politique de confidentialité transport'
        },
        
        adr: {
            title: '⚠️ Gestion des matières dangereuses',
            description: 'La sécurité des données ADR est notre priorité. Seuls des cookies essentiels sont utilisés.',
            learn_more: 'Notre engagement sécurité et confidentialité'
        },
        
        admin: {
            title: '🔧 Administration système',
            description: 'Interface sécurisée - cookies techniques uniquement pour votre session administrateur.',
            learn_more: 'Politique de sécurité administrateurs'
        }
    },

    // Liens légaux dynamiques
    legal_links: {
        privacy_policy: '/legal/privacy.php',
        terms_of_use: '/legal/terms.php',
        security_policy: '/legal/security.php',
        contact_dpo: 'mailto:dpo@guldagil.com?subject=RGPD%20-%20Demande%20utilisateur'
    },

    // Configuration d'affichage
    ui: {
        // Position de la bannière
        banner_position: 'bottom', // 'top' | 'bottom'
        
        // Thème couleur (hérite du module actuel)
        use_module_colors: true,
        
        // Couleurs de fallback
        colors: {
            primary: '#3182ce',
            success: '#059669',
            warning: '#f59e0b',
            danger: '#dc2626'
        },
        
        // Animation de la bannière
        animations: {
            enabled: true,
            duration: 300, // ms
            easing: 'ease-in-out'
        },
        
        // Bouton gestionnaire flottant
        floating_button: {
            enabled: true,
            position: 'bottom-right', // 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left'
            icon: '🍪'
        }
    },

    // Configuration par environnement
    environment: {
        // Mode debug (affiche les logs console)
        debug: false, // true en développement
        
        // Domaine pour les cookies
        domain: window.location.hostname,
        
        // HTTPS obligatoire pour les cookies sécurisés
        secure: window.location.protocol === 'https:',
        
        // Mode strict pour SameSite
        same_site: 'Strict'
    },

    // Callbacks personnalisés
    callbacks: {
        // Appelé après acceptation de tous les cookies
        onAcceptAll: function() {
            console.log('Guldagil: Tous les cookies acceptés');
            // Activer les fonctionnalités avancées
            if (typeof window.enableAdvancedFeatures === 'function') {
                window.enableAdvancedFeatures();
            }
        },
        
        // Appelé après acceptation minimale
        onAcceptMinimal: function() {
            console.log('Guldagil: Cookies techniques uniquement');
            // Assurer le fonctionnement de base
            if (typeof window.enableBasicFeatures === 'function') {
                window.enableBasicFeatures();
            }
        },
        
        // Appelé après suppression des préférences
        onReset: function() {
            console.log('Guldagil: Préférences cookies réinitialisées');
            // Nettoyage des données locales
            if (typeof window.clearUserData === 'function') {
                window.clearUserData();
            }
        },
        
        // Appelé au chargement de la page
        onLoad: function() {
            console.log('Guldagil: Gestionnaire cookies initialisé');
            // Vérifications de compatibilité
            if (typeof window.checkBrowserCompatibility === 'function') {
                window.checkBrowserCompatibility();
            }
        }
    },

    // Intégration avec les modules existants
    modules: {
        // Module calculateur de frais de port
        calculateur: {
            cookies_needed: ['guldagil_calc_history'],
            features_requiring_consent: ['sauvegarde_calculs', 'historique_recent'],
            fallback_message: 'Calculs possibles sans cookies, mais sans sauvegarde'
        },
        
        // Module gestion ADR
        adr: {
            cookies_needed: ['guldagil_adr_preferences'],
            features_requiring_consent: ['preferences_affichage'],
            fallback_message: 'Consultation ADR disponible sans cookies'
        },
        
        // Module utilisateur
        user: {
            cookies_needed: ['guldagil_user_preferences'],
            features_requiring_consent: ['theme_personnalise', 'raccourcis_clavier'],
            fallback_message: 'Profil de base disponible sans cookies optionnels'
        }
    }
};

// ===============================================
// 🔧 FONCTIONS D'INTÉGRATION AVANCÉES
// ===============================================

/**
 * Initialise la configuration avancée
 */
function initAdvancedCookieConfig() {
    if (typeof window.cookieBanner !== 'undefined') {
        // Fusionner la configuration personnalisée
        Object.assign(window.cookieBanner, {
            config: window.GuldagilCookieConfig
        });
        
        // Appliquer les messages personnalisés selon le module
        applyModuleSpecificMessages();
        
        // Configurer les callbacks
        setupCallbacks();
        
        console.log('Guldagil: Configuration avancée cookies appliquée');
    } else {
        console.warn('Guldagil: Gestionnaire cookies non trouvé, rechargement dans 1s...');
        setTimeout(initAdvancedCookieConfig, 1000);
    }
}

/**
 * Applique les messages spécifiques au module actuel
 */
function applyModuleSpecificMessages() {
    const currentModule = document.body.getAttribute('data-module') || 'default';
    const config = window.GuldagilCookieConfig;
    
    if (config.messages[currentModule]) {
        // Personnaliser la bannière selon le module
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            const messageEl = banner.querySelector('.cookie-message h3');
            const descEl = banner.querySelector('.cookie-message p');
            
            if (messageEl && config.messages[currentModule].title) {
                messageEl.textContent = config.messages[currentModule].title;
            }
            
            if (descEl && config.messages[currentModule].description) {
                descEl.innerHTML = config.messages[currentModule].description;
            }
        }
    }
}

/**
 * Configure les callbacks personnalisés
 */
function setupCallbacks() {
    const config = window.GuldagilCookieConfig;
    
    // Surcharger les méthodes du gestionnaire principal
    if (window.cookieBanner) {
        const originalAcceptAll = window.cookieBanner.acceptAll;
        const originalAcceptMinimal = window.cookieBanner.acceptMinimal;
        const originalReset = window.cookieBanner.resetConsent;
        
        window.cookieBanner.acceptAll = function() {
            originalAcceptAll.call(this);
            config.callbacks.onAcceptAll();
        };
        
        window.cookieBanner.acceptMinimal = function() {
            originalAcceptMinimal.call(this);
            config.callbacks.onAcceptMinimal();
        };
        
        window.cookieBanner.resetConsent = function() {
            originalReset.call(this);
            config.callbacks.onReset();
        };
    }
}

/**
 * Vérifie les cookies nécessaires pour un module
 */
function checkModuleCookieRequirements(moduleName) {
    const config = window.GuldagilCookieConfig;
    const moduleConfig = config.modules[moduleName];
    
    if (!moduleConfig) return true; // Module sans restrictions
    
    const hasConsent = window.hasCookieConsent('accepted');
    
    if (!hasConsent && moduleConfig.cookies_needed.length > 0) {
        // Afficher message d'information
        showModuleCookieInfo(moduleName, moduleConfig.fallback_message);
        return false;
    }
    
    return true;
}

/**
 * Affiche une information sur les cookies pour un module
 */
function showModuleCookieInfo(moduleName, message) {
    const notification = document.createElement('div');
    notification.className = 'module-cookie-info';
    notification.innerHTML = `
        <div class="module-cookie-content">
            <span class="module-icon">ℹ️</span>
            <span class="module-message">${message}</span>
            <button onclick="window.cookieBanner.showManageModal(); this.parentElement.parentElement.remove();" class="module-cookie-btn">
                Gérer les cookies
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-suppression après 5 secondes
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

/**
 * API publique pour les modules
 */
window.GuldagilCookies = {
    // Vérifier si un module peut utiliser ses cookies
    canUseModuleCookies: function(moduleName) {
        return checkModuleCookieRequirements(moduleName);
    },
    
    // Obtenir la configuration d'un module
    getModuleConfig: function(moduleName) {
        return window.GuldagilCookieConfig.modules[moduleName] || null;
    },
    
    // Forcer l'affichage du gestionnaire
    showManager: function() {
        if (window.cookieBanner) {
            window.cookieBanner.showManageModal();
        }
    },
    
    // Obtenir le statut actuel
    getConsentStatus: function() {
        return {
            hasMinimal: window.hasCookieConsent('minimal'),
            hasAll: window.hasCookieConsent('accepted'),
            timestamp: new Date().toISOString()
        };
    }
};

// ===============================================
// 🚀 INITIALISATION AUTOMATIQUE
// ===============================================

// Attendre que le DOM et le gestionnaire principal soient chargés
document.addEventListener('DOMContentLoaded', function() {
    // Délai pour laisser le gestionnaire principal s'initialiser
    setTimeout(initAdvancedCookieConfig, 100);
});

// Export pour usage en module ES6 (si nécessaire)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.GuldagilCookieConfig;
}

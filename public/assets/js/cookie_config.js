/**
 * Titre: Configuration avancée bannière cookie RGPD - VERSION CORRIGÉE
 * Chemin: /assets/js/cookie_config.js
 * Version: 0.5 beta + build auto
 * Usage: Personnalisation entreprise et modules - BOUCLE INFINIE CORRIGÉE
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
        
        port: {
            title: '🚛 Calculateur de frais de port',
            description: 'Pour sauvegarder vos calculs récents, nous utilisons des cookies techniques.',
            learn_more: 'Optimisez vos expéditions en toute confidentialité'
        },
        
        admin: {
            title: '⚙️ Administration système',
            description: 'Interface d\'administration sécurisée avec cookies de session obligatoires.',
            learn_more: 'Sécurité et traçabilité des actions admin'
        }
    },

    // Callbacks personnalisés
    callbacks: {
        onAcceptAll: function() {
            console.log('Guldagil: Tous les cookies acceptés');
            // Activer les fonctionnalités avancées
            if (window.analytics) window.analytics.enable();
            if (window.userPreferences) window.userPreferences.enable();
        },
        
        onAcceptMinimal: function() {
            console.log('Guldagil: Cookies techniques uniquement');
            // Désactiver les fonctionnalités optionnelles
            if (window.analytics) window.analytics.disable();
        },
        
        onReset: function() {
            console.log('Guldagil: Consentement réinitialisé');
            // Nettoyer les données optionnelles
            if (window.userPreferences) window.userPreferences.reset();
        }
    }
};

// ===============================================
// 🔧 FONCTIONS D'INTÉGRATION AVANCÉES - CORRIGÉES
// ===============================================

/**
 * Variable de contrôle pour éviter la boucle infinie
 */
let cookieConfigAttempts = 0;
const MAX_ATTEMPTS = 5;
let cookieConfigInitialized = false;

/**
 * Initialise la configuration avancée - VERSION CORRIGÉE
 */
function initAdvancedCookieConfig() {
    // CORRECTION: Empêcher la boucle infinie
    if (cookieConfigInitialized) {
        console.log('Guldagil: Configuration cookies déjà initialisée');
        return;
    }
    
    if (cookieConfigAttempts >= MAX_ATTEMPTS) {
        console.warn('Guldagil: Nombre maximum de tentatives atteint, arrêt des tentatives');
        createFallbackCookieManager();
        return;
    }
    
    cookieConfigAttempts++;
    
    if (typeof window.cookieBanner !== 'undefined') {
        // Fusionner la configuration personnalisée
        Object.assign(window.cookieBanner, {
            config: window.GuldagilCookieConfig
        });
        
        // Appliquer les messages personnalisés selon le module
        applyModuleSpecificMessages();
        
        // Configurer les callbacks
        setupCallbacks();
        
        cookieConfigInitialized = true;
        console.log('Guldagil: Configuration avancée cookies appliquée');
        
    } else {
        console.warn(`Guldagil: Gestionnaire cookies non trouvé, tentative ${cookieConfigAttempts}/${MAX_ATTEMPTS}`);
        
        // CORRECTION: Augmenter le délai et limiter les tentatives
        if (cookieConfigAttempts < MAX_ATTEMPTS) {
            setTimeout(initAdvancedCookieConfig, 2000); // 2 secondes au lieu de 1
        } else {
            console.error('Guldagil: Impossible de charger le gestionnaire de cookies, création d\'un gestionnaire de secours');
            createFallbackCookieManager();
        }
    }
}

/**
 * Crée un gestionnaire de cookies de secours si le principal échoue
 */
function createFallbackCookieManager() {
    if (typeof window.cookieBanner === 'undefined') {
        console.log('Guldagil: Création du gestionnaire de cookies de secours');
        
        window.cookieBanner = {
            acceptAll: function() {
                this.setCookie('guldagil_cookie_consent', 'accepted', 365);
                this.hideAllBanners();
                console.log('✅ Cookies acceptés (gestionnaire de secours)');
                if (window.GuldagilCookieConfig.callbacks.onAcceptAll) {
                    window.GuldagilCookieConfig.callbacks.onAcceptAll();
                }
            },
            
            acceptMinimal: function() {
                this.setCookie('guldagil_cookie_consent', 'minimal', 365);
                this.hideAllBanners();
                console.log('⚙️ Cookies techniques uniquement (gestionnaire de secours)');
                if (window.GuldagilCookieConfig.callbacks.onAcceptMinimal) {
                    window.GuldagilCookieConfig.callbacks.onAcceptMinimal();
                }
            },
            
            showDetails: function() {
                console.log('ℹ️ Détails cookies demandés (gestionnaire de secours)');
                alert('Gestionnaire de cookies simplifié activé. Fonctionnalités limitées.');
            },
            
            showManageModal: function() {
                console.log('⚙️ Gestion cookies demandée (gestionnaire de secours)');
                this.showDetails();
            },
            
            resetConsent: function() {
                this.setCookie('guldagil_cookie_consent', '', -1);
                console.log('🗑️ Consentement réinitialisé (gestionnaire de secours)');
                if (window.GuldagilCookieConfig.callbacks.onReset) {
                    window.GuldagilCookieConfig.callbacks.onReset();
                }
            },
            
            setCookie: function(name, value, days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                const expires = days > 0 ? `expires=${date.toUTCString()}` : 'expires=Thu, 01 Jan 1970 00:00:00 UTC';
                document.cookie = `${name}=${value};${expires};path=/;SameSite=Strict`;
            },
            
            hideAllBanners: function() {
                const banners = document.querySelectorAll('#cookie-banner, .cookie-banner, .module-cookie-info');
                banners.forEach(banner => {
                    banner.style.display = 'none';
                });
            },
            
            config: window.GuldagilCookieConfig
        };
        
        cookieConfigInitialized = true;
        console.log('✅ Gestionnaire de cookies de secours créé');
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
            if (originalAcceptAll) originalAcceptAll.call(this);
            config.callbacks.onAcceptAll();
        };
        
        window.cookieBanner.acceptMinimal = function() {
            if (originalAcceptMinimal) originalAcceptMinimal.call(this);
            config.callbacks.onAcceptMinimal();
        };
        
        window.cookieBanner.resetConsent = function() {
            if (originalReset) originalReset.call(this);
            config.callbacks.onReset();
        };
    }
}

/**
 * Vérifie les cookies nécessaires pour un module
 */
function checkModuleCookieRequirements(moduleName) {
    const config = window.GuldagilCookieConfig;
    const moduleConfig = config.modules && config.modules[moduleName];
    
    if (!moduleConfig) return true; // Module sans restrictions
    
    const hasConsent = window.hasCookieConsent && window.hasCookieConsent('accepted');
    
    if (!hasConsent && moduleConfig.cookies_needed && moduleConfig.cookies_needed.length > 0) {
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
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        z-index: 10000;
        max-width: 300px;
        font-size: 14px;
    `;
    
    notification.innerHTML = `
        <div class="module-cookie-content">
            <span class="module-icon">ℹ️</span>
            <span class="module-message">${message || 'Ce module nécessite des cookies optionnels'}</span>
            <button onclick="window.cookieBanner.showManageModal && window.cookieBanner.showManageModal(); this.parentElement.parentElement.remove();" 
                    style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 5px 10px; border-radius: 5px; margin-top: 10px; cursor: pointer;">
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
        return window.GuldagilCookieConfig.modules && window.GuldagilCookieConfig.modules[moduleName] || null;
    },
    
    // Forcer l'affichage du gestionnaire
    showManager: function() {
        if (window.cookieBanner && window.cookieBanner.showManageModal) {
            window.cookieBanner.showManageModal();
        } else {
            console.warn('Gestionnaire de cookies non disponible');
        }
    },
    
    // Obtenir le statut actuel
    getConsentStatus: function() {
        return {
            hasMinimal: window.hasCookieConsent ? window.hasCookieConsent('minimal') : false,
            hasAll: window.hasCookieConsent ? window.hasCookieConsent('accepted') : false,
            timestamp: new Date().toISOString(),
            attempts: cookieConfigAttempts,
            initialized: cookieConfigInitialized
        };
    }
};

// ===============================================
// 🚀 INITIALISATION AUTOMATIQUE - CORRIGÉE
// ===============================================

/**
 * Fonction de démarrage sécurisée
 */
function startCookieConfig() {
    // Réinitialiser les compteurs si nécessaire
    if (cookieConfigAttempts >= MAX_ATTEMPTS && !cookieConfigInitialized) {
        console.log('Guldagil: Réinitialisation des tentatives de configuration cookies');
        cookieConfigAttempts = 0;
    }
    
    // Lancer l'initialisation
    setTimeout(initAdvancedCookieConfig, 500); // Délai initial de 500ms
}

// Attendre que le DOM soit chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startCookieConfig);
} else {
    startCookieConfig();
}

// ===============================================
// 🛡️ PROTECTION CONTRE LES ERREURS
// ===============================================

/**
 * Gestionnaire d'erreurs pour les cookies
 */
window.addEventListener('error', function(event) {
    if (event.message && event.message.includes('cookie')) {
        console.warn('Guldagil: Erreur liée aux cookies interceptée:', event.message);
        // Ne pas laisser l'erreur se propager
        event.preventDefault();
        
        // Essayer de créer le gestionnaire de secours si pas déjà fait
        if (!cookieConfigInitialized) {
            createFallbackCookieManager();
        }
    }
});

/**
 * Nettoyage des timeouts en cas de problème
 */
window.addEventListener('beforeunload', function() {
    // Nettoyer les tentatives en cours
    cookieConfigAttempts = MAX_ATTEMPTS;
    console.log('Guldagil: Nettoyage configuration cookies avant déchargement page');
});

// Export pour usage en module ES6 (si nécessaire)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.GuldagilCookieConfig;
}

// Debug: afficher le statut dans la console
console.log('🍪 Guldagil Cookie Config: Module chargé (version corrigée anti-boucle)');

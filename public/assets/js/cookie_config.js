/**
 * Titre: Configuration globale cookies RGPD - Anti-conflit
 * Chemin: /assets/js/cookie_config.js
 * Version: 0.5 beta + build auto
 * Anti-duplication: Évite la re-déclaration de CookieBannerManager
 */

// Protection contre les chargements multiples
if (typeof window.GuldagilCookieConfigLoaded !== 'undefined') {
    console.log('🍪 Cookie Config déjà chargé, arrêt pour éviter les doublons');
    // Arrêter l'exécution du script
    if (typeof module !== 'undefined') {
        module.exports = {};
    }
} else {
    // Marquer comme chargé IMMÉDIATEMENT
    window.GuldagilCookieConfigLoaded = true;
    console.log('🍪 Guldagil Cookie Config: Module chargé (version corrigée anti-boucle)');
}

/**
 * Configuration centralisée des cookies pour tous les modules
 * NE PAS redéfinir CookieBannerManager ici - utilise celui de cookie_banner.js
 */
window.GuldagilCookieConfig = {
    // Configuration globale
    version: '1.2',
    debug: false,
    
    // Paramètres par défaut
    defaults: {
        consentExpiry: 730, // 2 ans
        cookieName: 'guldagil_cookie_consent',
        localStorageKey: 'guldagil_cookie_consent_permanent'
    },
    
    // Messages personnalisés par module
    messages: {
        default: {
            title: 'Respect de votre vie privée',
            description: 'Ce portail utilise uniquement des <strong>cookies techniques nécessaires</strong> au fonctionnement (session, préférences). Aucun tracking publicitaire.'
        },
        port: {
            title: 'Calcul de frais de port',
            description: 'Le module calcul frais de port utilise des cookies techniques pour sauvegarder vos préférences de calcul et historique.'
        },
        materiel: {
            title: 'Gestion matériel',
            description: 'Le module matériel utilise des cookies pour mémoriser vos filtres et préférences d\'affichage.'
        },
        adr: {
            title: 'Transport ADR',
            description: 'Le module ADR utilise des cookies pour mémoriser vos paramètres de sécurité et certifications.'
        },
        auth: {
            title: 'Authentification',
            description: 'La page de connexion utilise des cookies de session sécurisés nécessaires à l\'authentification.'
        }
    },
    
    // Configuration spécifique par module
    modules: {
        port: {
            cookies_needed: ['session', 'preferences'],
            fallback_message: 'Pour accéder au calcul de frais de port, veuillez accepter les cookies techniques.'
        },
        materiel: {
            cookies_needed: ['session', 'filters'],
            fallback_message: 'Pour gérer le matériel, veuillez accepter les cookies techniques.'
        },
        adr: {
            cookies_needed: ['session', 'certifications'],
            fallback_message: 'Pour accéder aux fonctions ADR, veuillez accepter les cookies techniques.'
        }
    },
    
    // Callbacks personnalisables
    callbacks: {
        onAcceptAll: function() {
            console.log('✅ Tous les cookies acceptés');
            // Ici on peut activer des fonctionnalités avancées
            window.dispatchEvent(new CustomEvent('cookiesAccepted', { detail: 'all' }));
        },
        
        onAcceptMinimal: function() {
            console.log('⚙️ Cookies techniques uniquement');
            // Juste les fonctionnalités de base
            window.dispatchEvent(new CustomEvent('cookiesAccepted', { detail: 'minimal' }));
        },
        
        onReset: function() {
            console.log('🗑️ Préférences cookies réinitialisées');
            window.dispatchEvent(new CustomEvent('cookiesReset'));
            // Rediriger vers page d'accueil après reset
            setTimeout(() => {
                window.location.href = '/';
            }, 1000);
        }
    }
};

/**
 * Gestionnaire de fallback UNIQUEMENT si le principal n'existe pas
 * Ne redéfinit PAS CookieBannerManager pour éviter les conflits
 */
function initFallbackCookieManager() {
    // Attendre que le DOM soit chargé ET que cookie_banner.js soit potentiellement chargé
    setTimeout(() => {
        if (typeof window.CookieBannerManager === 'undefined' && typeof window.cookieBanner === 'undefined') {
            console.log('⚠️ CookieBannerManager principal non trouvé, création du gestionnaire de secours');
            
            // Gestionnaire minimal sans redéfinir la classe
            window.cookieBanner = {
                acceptAll: function() {
                    this.setCookie('guldagil_cookie_consent', 'accepted', 730);
                    this.hideAllBanners();
                    console.log('✅ Cookies acceptés (gestionnaire de secours)');
                    if (window.GuldagilCookieConfig.callbacks.onAcceptAll) {
                        window.GuldagilCookieConfig.callbacks.onAcceptAll();
                    }
                },
                
                acceptMinimal: function() {
                    this.setCookie('guldagil_cookie_consent', 'minimal', 730);
                    this.hideAllBanners();
                    console.log('⚙️ Cookies techniques uniquement (gestionnaire de secours)');
                    if (window.GuldagilCookieConfig.callbacks.onAcceptMinimal) {
                        window.GuldagilCookieConfig.callbacks.onAcceptMinimal();
                    }
                },
                
                showDetails: function() {
                    alert('Fonctionnalités limitées en mode de secours.\n\nCookies techniques :\n- Session utilisateur\n- Préférences interface\n- Fonctionnement modules\n\nAucun tracking publicitaire.');
                },
                
                showManageModal: function() {
                    console.log('⚙️ Gestion cookies demandée (gestionnaire de secours)');
                    this.showDetails();
                },
                
                resetConsent: function() {
                    this.setCookie('guldagil_cookie_consent', '', -1);
                    try {
                        localStorage.removeItem('guldagil_cookie_consent_permanent');
                    } catch (e) {}
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
                    
                    // Aussi dans localStorage
                    try {
                        if (days > 0) {
                            localStorage.setItem('guldagil_cookie_consent_permanent', value);
                        } else {
                            localStorage.removeItem('guldagil_cookie_consent_permanent');
                        }
                    } catch (e) {}
                },
                
                hideAllBanners: function() {
                    const banners = document.querySelectorAll('#cookie-banner, .cookie-banner, .module-cookie-info');
                    banners.forEach(banner => {
                        banner.style.display = 'none';
                    });
                },
                
                config: window.GuldagilCookieConfig
            };
            
            console.log('✅ Gestionnaire de cookies de secours créé (sans conflit de classe)');
        } else {
            console.log('✅ CookieBannerManager principal détecté, pas de fallback nécessaire');
        }
    }, 100); // Petit délai pour laisser cookie_banner.js se charger
}

/**
 * Applique les messages spécifiques au module actuel
 */
function applyModuleSpecificMessages() {
    const currentModule = document.body.getAttribute('data-module') || 'default';
    const config = window.GuldagilCookieConfig;
    
    if (config.messages[currentModule]) {
        // Attendre que la bannière soit créée
        setTimeout(() => {
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
        }, 500);
    }
}

/**
 * Configure les callbacks personnalisés sur le gestionnaire existant
 */
function setupCallbacks() {
    setTimeout(() => {
        if (window.cookieBanner && typeof window.cookieBanner === 'object') {
            const config = window.GuldagilCookieConfig;
            
            // Surcharger les méthodes existantes pour ajouter nos callbacks
            const originalAcceptAll = window.cookieBanner.acceptAll;
            const originalAcceptMinimal = window.cookieBanner.acceptMinimal;
            const originalReset = window.cookieBanner.resetConsent;
            
            if (originalAcceptAll) {
                window.cookieBanner.acceptAll = function() {
                    originalAcceptAll.call(this);
                    config.callbacks.onAcceptAll();
                };
            }
            
            if (originalAcceptMinimal) {
                window.cookieBanner.acceptMinimal = function() {
                    originalAcceptMinimal.call(this);
                    config.callbacks.onAcceptMinimal();
                };
            }
            
            if (originalReset) {
                window.cookieBanner.resetConsent = function() {
                    originalReset.call(this);
                    config.callbacks.onReset();
                };
            }
            
            console.log('🔗 Callbacks cookies configurés sur le gestionnaire existant');
        }
    }, 200);
}

/**
 * Vérifie les cookies nécessaires pour un module
 */
function checkModuleCookieRequirements(moduleName) {
    const config = window.GuldagilCookieConfig;
    const moduleConfig = config.modules && config.modules[moduleName];
    
    if (!moduleConfig) return true; // Module sans restrictions
    
    // Fonction hasCookieConsent globale
    const hasConsent = window.hasCookieConsent && window.hasCookieConsent('accepted');
    
    if (!hasConsent && moduleConfig.cookies_needed && moduleConfig.cookies_needed.length > 0) {
        showModuleCookieInfo(moduleName, moduleConfig.fallback_message);
        return false;
    }
    
    return true;
}

/**
 * Affiche une information sur les cookies pour un module
 */
function showModuleCookieInfo(moduleName, message) {
    // Éviter les doublons
    if (document.querySelector('.module-cookie-info')) return;
    
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
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        z-index: 9998;
        max-width: 350px;
        font-size: 14px;
        line-height: 1.4;
    `;
    
    notification.innerHTML = `
        <div style="margin-bottom: 10px;">${message}</div>
        <button onclick="this.parentNode.remove(); if(window.cookieBanner) window.cookieBanner.acceptMinimal();" 
                style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-size: 12px;">
            Accepter les cookies techniques
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-suppression après 10 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 10000);
}

// Initialisation quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initFallbackCookieManager();
        applyModuleSpecificMessages();
        setupCallbacks();
    });
} else {
    // DOM déjà prêt
    initFallbackCookieManager();
    applyModuleSpecificMessages();
    setupCallbacks();
}

// Exposer les fonctions utiles globalement
window.checkModuleCookieRequirements = checkModuleCookieRequirements;
window.showModuleCookieInfo = showModuleCookieInfo;

console.log('✅ Guldagil: Configuration avancée cookies appliquée (sans conflit)');
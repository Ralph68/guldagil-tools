/**
 * Titre: Configuration centralisée du module calculateur
 * Chemin: /public/assets/js/modules/calculateur/core/config.js
 * Version: 0.5 beta + build
 * 
 * Configuration globale, validation et paramètres API
 */

const CalculateurConfig = {
    // =========================================================================
    // API ET ENDPOINTS
    // =========================================================================
    API: {
        ENDPOINT: 'ajax-calculate.php',
        TIMEOUT: 12000,
        RETRY_ATTEMPTS: 2,
        RETRY_DELAY: 1000,
        METHODS: {
            CALCULATE: 'POST',
            VALIDATE: 'POST'
        }
    },
    
    // =========================================================================
    // TIMING ET PERFORMANCE
    // =========================================================================
    TIMING: {
        // Délai avant calcul automatique (ms)
        AUTO_CALC_DELAY: 800,
        
        // Délai de debounce pour validation (ms)
        DEBOUNCE_DELAY: 300,
        
        // Durée minimale d'affichage loading (ms)
        LOADING_MIN_TIME: 500,
        
        // Durée des animations (ms)
        ANIMATION_DURATION: 250,
        STEP_TRANSITION: 400,
        
        // Progression automatique étapes (ms)
        STEP_AUTO_ADVANCE: 600,
        
        // Délai avant masquage des messages (ms)
        MESSAGE_HIDE_DELAY: 5000
    },
    
    // =========================================================================
    // VALIDATION ET LIMITES
    // =========================================================================
    VALIDATION: {
        // Poids
        MAX_POIDS: 3500,
        MIN_POIDS: 0.1,
        PALETTE_WEIGHT_THRESHOLD: 60,
        
        // Département
        DEPT_PATTERN: /^(0[1-9]|[1-8][0-9]|9[0-5])$/,
        DEPT_MIN: 1,
        DEPT_MAX: 95,
        
        // Palettes
        MAX_PALETTES: 26,
        MIN_PALETTES: 0,
        
        // Messages d'erreur
        MESSAGES: {
            DEPT_INVALID: 'Département invalide (01-95)',
            POIDS_TOO_LOW: 'Poids minimum : 0.1 kg',
            POIDS_TOO_HIGH: 'Poids maximum : 3500 kg',
            PALETTES_TOO_HIGH: 'Maximum 26 palettes',
            TYPE_REQUIRED: 'Type d\'envoi requis',
            NETWORK_ERROR: 'Erreur de connexion',
            CALCULATION_ERROR: 'Erreur de calcul'
        }
    },
    
    // =========================================================================
    // INTERFACE UTILISATEUR
    // =========================================================================
    UI: {
        // Progression
        PROGRESS_STEPS: [
            { id: 'destination', label: 'Destination & poids', icon: '📍' },
            { id: 'type', label: 'Type d\'envoi', icon: '📦' },
            { id: 'options', label: 'Options', icon: '⚡' },
            { id: 'results', label: 'Résultats', icon: '💰' }
        ],
        
        // États des étapes
        STEP_STATES: {
            PENDING: 'pending',
            CURRENT: 'current',
            COMPLETED: 'completed',
            ERROR: 'error'
        },
        
        // Messages toast
        TOAST: {
            DURATION: 4000,
            POSITIONS: {
                TOP_RIGHT: 'top-right',
                BOTTOM_RIGHT: 'bottom-right'
            }
        },
        
        // Thème couleurs (correspondant aux variables CSS)
        COLORS: {
            PRIMARY: '#1e40af',
            SECONDARY: '#0ea5e9',
            SUCCESS: '#10b981',
            WARNING: '#f59e0b',
            ERROR: '#ef4444',
            INFO: '#3b82f6'
        }
    },
    
    // =========================================================================
    // TRANSPORTEURS ET SERVICES
    // =========================================================================
    CARRIERS: {
        // Mapping des transporteurs
        NAMES: {
            'heppner': 'Heppner',
            'xpo': 'XPO Logistics',
            'kn': 'Kuehne + Nagel'
        },
        
        // Icônes des transporteurs
        ICONS: {
            'heppner': '🚛',
            'xpo': '🚚',
            'kn': '🚐'
        },
        
        // Couleurs des transporteurs
        COLORS: {
            'heppner': '#2563eb',
            'xpo': '#0ea5e9',
            'kn': '#10b981'
        },
        
        // Services disponibles
        SERVICES: {
            'standard': { label: 'Livraison standard', delay: '24-48h' },
            'rdv': { label: 'Prise de RDV', delay: '24-48h + RDV' },
            'star18': { label: 'Star 18h (Heppner)', delay: 'Avant 18h' },
            'star13': { label: 'Star 13h (Heppner)', delay: 'Avant 13h' },
            'datefixe18': { label: 'Date fixe 18h', delay: 'Date imposée' },
            'datefixe13': { label: 'Date fixe 13h', delay: 'Date imposée' },
            'premium18': { label: 'Premium 18h (XPO)', delay: 'Avant 18h' },
            'premium13': { label: 'Premium 13h (XPO)', delay: 'Avant 13h' }
        }
    },
    
    // =========================================================================
    // DONNÉES MÉTIER
    // =========================================================================
    BUSINESS: {
        // Types d'envoi
        TYPES: {
            'colis': { 
                label: 'Colis', 
                description: 'Jusqu\'à 60kg',
                maxWeight: 60,
                icon: '📦'
            },
            'palette': { 
                label: 'Palette(s)', 
                description: 'Palette EUR 80x120cm',
                maxWeight: 3500,
                icon: '🏗️'
            }
        },
        
        // Départements avec restrictions
        DEPT_RESTRICTIONS: {
            // Départements d'outre-mer
            DOM_TOM: ['971', '972', '973', '974', '975', '976'],
            
            // Départements avec surcoût saisonnier
            SEASONAL_SURCHARGE: ['04', '05', '06', '13', '83', '84']
        }
    },
    
    // =========================================================================
    // DEBUG ET DÉVELOPPEMENT
    // =========================================================================
    DEBUG: {
        // Activer le mode debug
        ENABLED: window.location.search.includes('debug=1') || 
                 window.location.hostname === 'localhost' ||
                 document.querySelector('meta[name="environment"]')?.content === 'development',
        
        // Niveau de verbosité
        LEVEL: 'info', // 'error', 'warn', 'info', 'debug'
        
        // Données de test
        TEST_DATA: {
            departement: '67',
            poids: 150,
            type: 'palette',
            adr: false,
            service_livraison: 'standard',
            enlevement: false,
            palettes: 1
        },
        
        // Mock responses pour tests
        MOCK_RESPONSES: {
            ENABLED: false,
            DELAY: 1000,
            SUCCESS_RATE: 0.9 // 90% de succès
        }
    },
    
    // =========================================================================
    // MÉTADONNÉES
    // =========================================================================
    META: {
        VERSION: '0.5.0',
        BUILD: '20250622001',
        NAME: 'Calculateur Guldagil',
        DESCRIPTION: 'Calculateur de frais de port - Interface progressive',
        AUTHOR: 'Guldagil',
        
        // Feature flags
        FEATURES: {
            PROGRESSIVE_FORM: true,
            REAL_TIME_CALC: true,
            AUTO_ADVANCE: true,
            SUGGESTIONS: true,
            COMPARISON_MODE: true,
            EXPORT_RESULTS: false // Désactivé pour v0.5
        }
    }
};

// =========================================================================
// FONCTIONS UTILITAIRES DE CONFIGURATION
// =========================================================================

/**
 * Récupère une valeur de configuration avec chemin en notation pointée
 * @param {string} path - Chemin vers la valeur (ex: 'API.TIMEOUT')
 * @param {*} defaultValue - Valeur par défaut
 * @returns {*}
 */
CalculateurConfig.get = function(path, defaultValue = null) {
    return path.split('.').reduce((obj, key) => obj?.[key], this) ?? defaultValue;
};

/**
 * Vérifie si une feature est activée
 * @param {string} feature - Nom de la feature
 * @returns {boolean}
 */
CalculateurConfig.isFeatureEnabled = function(feature) {
    return this.get(`META.FEATURES.${feature}`, false);
};

/**
 * Récupère les informations d'un transporteur
 * @param {string} carrier - Code transporteur
 * @returns {object}
 */
CalculateurConfig.getCarrierInfo = function(carrier) {
    return {
        name: this.get(`CARRIERS.NAMES.${carrier}`, carrier),
        icon: this.get(`CARRIERS.ICONS.${carrier}`, '🚛'),
        color: this.get(`CARRIERS.COLORS.${carrier}`, this.UI.COLORS.PRIMARY)
    };
};

/**
 * Logs de debug conditionnels
 * @param {string} level - Niveau de log
 * @param {string} message - Message
 * @param {*} data - Données additionnelles
 */
CalculateurConfig.log = function(level, message, data = null) {
    if (!this.DEBUG.ENABLED) return;
    
    const levels = ['error', 'warn', 'info', 'debug'];
    const currentLevel = levels.indexOf(this.DEBUG.LEVEL);
    const messageLevel = levels.indexOf(level);
    
    if (messageLevel <= currentLevel) {
        const prefix = `[Calculateur ${level.toUpperCase()}]`;
        if (data) {
            console[level](prefix, message, data);
        } else {
            console[level](prefix, message);
        }
    }
};

// Export global
window.CalculateurConfig = CalculateurConfig;

// Debug info
if (CalculateurConfig.DEBUG.ENABLED) {
    CalculateurConfig.log('info', `Configuration chargée v${CalculateurConfig.META.VERSION}`);
    CalculateurConfig.log('debug', 'Configuration complète:', CalculateurConfig);
}

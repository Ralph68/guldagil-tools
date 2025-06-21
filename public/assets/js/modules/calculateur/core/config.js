// =============================================================================
// FICHIER 1: /public/assets/js/modules/calculateur/core/config.js
// =============================================================================

/**
 * Configuration centralisée du module calculateur
 */
const CalculateurConfig = {
    // API
    API: {
        ENDPOINT: 'ajax-calculate.php',
        TIMEOUT: 10000,
        RETRY_ATTEMPTS: 3
    },
    
    // Timing
    TIMING: {
        AUTO_CALC_DELAY: 800,
        DEBOUNCE_DELAY: 300,
        ANIMATION_DURATION: 250
    },
    
    // Validation
    VALIDATION: {
        MAX_POIDS: 3500,
        MIN_POIDS: 0.1,
        DEPT_PATTERN: /^\d{2}$/,
        PALETTE_THRESHOLD: 60
    },
    
    // UI
    UI: {
        LOADING_MIN_TIME: 500,
        TOAST_DURATION: 4000,
        ERROR_DISPLAY_TIME: 8000
    },
    
    // Debug
    DEBUG: window.location.search.includes('debug=1'),
    VERSION: '0.5.0'
};

// Export pour les autres modules
window.CalculateurConfig = CalculateurConfig;

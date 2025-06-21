// =============================================================================
// FICHIER 8: /public/assets/js/modules/calculateur/main.js
// =============================================================================

/**
 * Point d'entrée principal du module calculateur
 */
class CalculateurApp {
    constructor() {
        this.initialized = false;
        this.modules = [];
    }
    
    /**
     * Initialisation de l'application
     */
    async init() {
        if (this.initialized) return;
        
        try {
            if (CalculateurConfig.DEBUG) {
                console.log('🚀 Initialisation Calculateur v' + CalculateurConfig.VERSION);
            }
            
            // Vérifier que tous les modules sont chargés
            if (!this.checkDependencies()) {
                throw new Error('Modules manquants');
            }
            
            // Initialiser les modules dans l'ordre
            await this.initializeModules();
            
            // Configuration globale
            this.setupGlobalEvents();
            
            // Interface initiale
            this.setupInitialUI();
            
            this.initialized = true;
            
            if (CalculateurConfig.DEBUG) {
                console.log('✅ Calculateur initialisé avec succès');
                this.exposeDebugAPI();
            }
            
        } catch (error) {
            console.error('❌ Erreur initialisation Calculateur:', error);
            this.showError('Erreur d\'initialisation du calculateur');
        }
    }
    
    /**
     * Vérification des dépendances
     */
    checkDependencies() {
        const requiredModules = [
            'CalculateurConfig',
            'calculateurState',
            'apiService',
            'formDataModel',
            'formController',
            'calcController',
            'resultsView'
        ];
        
        const missing = requiredModules.filter(module => !window[module]);
        
        if (missing.length > 0) {
            console.error('Modules manquants:', missing);
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialisation des modules
     */
    async initializeModules() {
        const modules = [
            window.formController,
            window.calcController,
            window.resultsView
        ];
        
        for (const module of modules) {
            if (module && typeof module.init === 'function') {
                await module.init();
                this.modules.push(module);
            }
        }
    }
    
    /**
     * Événements globaux
     */
    setupGlobalEvents() {
        // Gestion des erreurs globales
        window.addEventListener('error', (event) => {
            if (CalculateurConfig.DEBUG) {
                console.error('Erreur globale:', event.error);
            }
        });
        
        // Gestion du beforeunload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
        
        // Raccourcis clavier
        document.addEventListener('keydown', (event) => {
            // Échapper pour reset
            if (event.key === 'Escape') {
                window.formController?.reset();
            }
            
            // Ctrl+Enter pour calculer
            if (event.ctrlKey && event.key === 'Enter') {
                const form = document.getElementById('calculator-form');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });
    }
    
    /**
     * Interface utilisateur initiale
     */
    setupInitialUI() {
        // Afficher placeholder dans résultats
        window.resultsView?.showPlaceholder();
        
        // Focus sur premier champ
        setTimeout(() => {
            const firstField = document.getElementById('departement');
            if (firstField) {
                firstField.focus();
            }
        }, 100);
        
        // Charger données URL si présentes
        this.loadURLParams();
    }
    
    /**
     * Chargement des paramètres URL
     */
    loadURLParams() {
        const params = new URLSearchParams(window.location.search);
        const urlData = {};
        
        // Mapper les paramètres URL
        const urlMappings = {
            'dept': 'departement',
            'departement': 'departement',
            'poids': 'poids',
            'type': 'type',
            'adr': 'adr'
        };
        
        Object.entries(urlMappings).forEach(([urlParam, formField]) => {
            const value = params.get(urlParam);
            if (value) {
                urlData[formField] = value;
            }
        });
        
        // Appliquer les données URL
        if (Object.keys(urlData).length > 0) {
            calculateurState.dispatch({
                type: 'FORM_UPDATE',
                payload: urlData
            });
            
            // Auto-calcul si données complètes
            setTimeout(() => {
                const currentData = calculateurState.getState().form.data;
                if (formDataModel.isComplete(currentData)) {
                    window.calcController?.startCalculation(currentData);
                }
            }, 500);
        }
    }
    
    /**
     * API de debug
     */
    exposeDebugAPI() {
        window.CalculateurDebug = {
            getState: () => calculateurState.getState(),
            getConfig: () => CalculateurConfig,
            getHistory: () => calculateurState.actionHistory,
            reset: () => window.formController?.reset(),
            simulate: (data) => window.calcController?.startCalculation(data),
            modules: this.modules,
            version: CalculateurConfig.VERSION
        };
        
        console.log('🔧 API Debug disponible: window.CalculateurDebug');
    }
    
    /**
     * Affichage d'erreur critique
     */
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'calculateur-error';
        errorDiv.innerHTML = `
            <div class="error-content">
                <h3>❌ Erreur Calculateur</h3>
                <p>${message}</p>
                <button onclick="location.reload()">Recharger la page</button>
            </div>
        `;
        
        // Insérer au début du body
        document.body.insertBefore(errorDiv, document.body.firstChild);
    }
    
    /**
     * Nettoyage lors de la fermeture
     */
    cleanup() {
        // Annuler les calculs en cours
        window.calcController?.cancelCalculation();
        
        // Nettoyer les timeouts
        this.modules.forEach(module => {
            if (module.cleanup) {
                module.cleanup();
            }
        });
    }
    
    /**
     * Restart de l'application
     */
    restart() {
        this.cleanup();
        this.initialized = false;
        this.modules = [];
        setTimeout(() => this.init(), 100);
    }
}

// Instance globale
window.calculateurApp = new CalculateurApp();

// Auto-initialisation au chargement DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.calculateurApp.init();
    });
} else {
    // DOM déjà chargé
    window.calculateurApp.init();
}

// =============================================================================
// FICHIER 9: Styles CSS pour accompagner la nouvelle architecture
// =============================================================================

/* 
FICHIER: /public/assets/css/modules/calculateur/architecture.css

Styles pour supporter la nouvelle architecture JS
*/

/* États des champs de formulaire */
.form-input.error {
    border-color: var(--calculateur-error);
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.1);
}

.form-input.valid {
    border-color: var(--calculateur-success);
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1);
}

/* Messages d'erreur des champs */
.field-error {
    color: var(--calculateur-error);
    font-size: 12px;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.field-error::before {
    content: "⚠️";
    font-size: 10px;
}

/* États des résultats */
.results-status {
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    text-align: center;
    margin-bottom: 16px;
}

.results-status.success {
    background: var(--calculateur-success);
    color: white;
}

.results-status.error {
    background: var(--calculateur-error);
    color: white;
}

.results-status.warning {
    background: var(--calculateur-warning);
    color: white;
}

.results-status.loading {
    background: var(--calculateur-secondary);
    color: white;
}

.results-status.placeholder {
    background: var(--calculateur-gray-100);
    color: var(--calculateur-gray-600);
}

/* Spinner de chargement */
.loading-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid var(--calculateur-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* États de contenu */
.loading-state,
.error-message,
.placeholder-state,
.affretement-message {
    text-align: center;
    padding: 32px 16px;
}

.placeholder-icon,
.error-icon,
.affretement-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.7;
}

/* Meilleur tarif */
.best-rate {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    text-align: center;
}

.best-rate-header h3 {
    margin: 0 0 16px 0;
    font-size: 18px;
}

.carrier-name {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 8px;
}

.price {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.delivery-time {
    font-size: 14px;
    opacity: 0.9;
}

/* Comparaison transporteurs */
.comparison-section {
    margin-top: 24px;
}

.comparison-section h4 {
    margin: 0 0 16px 0;
    color: var(--calculateur-gray-700);
}

.carriers-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.carrier-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border: 1px solid var(--calculateur-gray-200);
    border-radius: 8px;
    background: white;
}

.carrier-item.best {
    border-color: var(--calculateur-success);
    background: rgba(16, 185, 129, 0.05);
}

.carrier-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.best-badge {
    background: var(--calculateur-success);
    color: white;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.carrier-price {
    font-weight: 600;
    color: var(--calculateur-primary);
}

/* Message d'affrètement */
.affretement-message {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 12px;
    padding: 32px;
}

.contact-info {
    margin-top: 16px;
    padding: 16px;
    background: rgba(245, 158, 11, 0.1);
    border-radius: 8px;
}

/* Bouton retry */
.retry-btn {
    background: var(--calculateur-primary);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 16px;
    transition: background-color 0.2s;
}

.retry-btn:hover {
    background: var(--calculateur-secondary);
}

/* Erreur critique */
.calculateur-error {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: var(--calculateur-error);
    color: white;
    padding: 16px;
    text-align: center;
    z-index: 9999;
}

.error-content button {
    background: white;
    color: var(--calculateur-error);
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .carrier-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .carrier-price {
        align-self: flex-end;
    }
}

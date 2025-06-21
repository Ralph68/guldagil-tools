// =============================================================================
// FICHIER: /public/assets/js/modules/calculateur/core/events.js
// =============================================================================

/**
 * Système d'événements centralisé
 */
class EventSystem {
    constructor() {
        this.listeners = new Map();
    }
    
    /**
     * Écouter un événement
     */
    on(eventName, callback) {
        if (!this.listeners.has(eventName)) {
            this.listeners.set(eventName, new Set());
        }
        this.listeners.get(eventName).add(callback);
        
        // Retourner fonction de désabonnement
        return () => this.off(eventName, callback);
    }
    
    /**
     * Arrêter d'écouter un événement
     */
    off(eventName, callback) {
        this.listeners.get(eventName)?.delete(callback);
    }
    
    /**
     * Émettre un événement
     */
    emit(eventName, data = null) {
        const callbacks = this.listeners.get(eventName);
        if (callbacks) {
            callbacks.forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error('Erreur listener événement:', error);
                }
            });
        }
        
        if (CalculateurConfig?.DEBUG) {
            console.log('📡 Événement émis:', eventName, data);
        }
    }
    
    /**
     * Écouter une seule fois
     */
    once(eventName, callback) {
        const unsubscribe = this.on(eventName, (data) => {
            callback(data);
            unsubscribe();
        });
        return unsubscribe;
    }
}

// Instance globale
window.calculateurEvents = new EventSystem();

/**
 * Titre: Analytics léger pour le portail Guldagil
 * Chemin: /public/assets/js/analytics.js
 * Version: 0.5 beta + build auto
 */

// Système d'analytics léger pour le portail
class PortalAnalytics {
    constructor() {
        this.config = window.portalAnalytics || {};
        this.sessionId = this._generateSessionId();
        this.pageLoadTime = Date.now();
        this.interactions = [];
        this.initialized = false;
        
        // Initialiser uniquement si activé dans la configuration
        if (this.config.enabled) {
            this._init();
        }
    }
    
    _init() {
        // Enregistrer visite page
        this._recordPageView();
        
        // Écouter les événements du portail
        this._setupEventListeners();
        
        // Initialiser tracking temps de visite
        this._trackTimeOnPage();
        
        // Marquer comme initialisé
        this.initialized = true;
        
        // Activer le debug si admin
        if (this.config.isAdmin) {
            console.log('🔍 Analytics Portal initialisé en mode admin');
        }
    }
    
    _generateSessionId() {
        // Générer un ID de session unique
        return 'sess_' + Math.random().toString(36).substring(2, 15);
    }
    
    _recordPageView() {
        const pageData = {
            type: 'pageview',
            url: window.location.pathname,
            title: document.title,
            referrer: document.referrer,
            module: this.config.module || 'unknown',
            timestamp: new Date().toISOString(),
            sessionId: this.sessionId,
            userAgent: navigator.userAgent,
            screenWidth: window.innerWidth,
            screenHeight: window.innerHeight
        };
        
        // Envoyer au serveur ou stocker
        this._sendToServer('pageview', pageData);
    }
    
    _setupEventListeners() {
        // Suivi des clics sur liens
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link) {
                this.trackEvent('link_click', {
                    text: link.textContent.trim(),
                    href: link.getAttribute('href'),
                    module: link.dataset.module || 'unknown'
                });
            }
        });
        
        // Suivi des formulaires
        document.addEventListener('submit', (e) => {
            if (e.target.tagName === 'FORM') {
                this.trackEvent('form_submit', {
                    formId: e.target.id || 'unknown',
                    formAction: e.target.action || 'unknown'
                });
            }
        });
        
        // Capture de la sortie
        window.addEventListener('beforeunload', () => {
            this._trackExitEvent();
        });
    }
    
    _trackTimeOnPage() {
        // Enregistrer le temps passé toutes les 30 secondes
        this.timeInterval = setInterval(() => {
            const timeSpent = Math.round((Date.now() - this.pageLoadTime) / 1000);
            
            // Enregistrer toutes les 30 secondes
            if (timeSpent % 30 === 0) {
                this._sendToServer('time_update', {
                    type: 'time_update',
                    seconds: timeSpent,
                    url: window.location.pathname,
                    module: this.config.module || 'unknown',
                    sessionId: this.sessionId
                });
            }
        }, 1000);
    }
    
    _trackExitEvent() {
        const timeSpent = Math.round((Date.now() - this.pageLoadTime) / 1000);
        
        // Enregistrer sortie
        this._sendToServer('exit', {
            type: 'exit',
            timeSpent: timeSpent,
            url: window.location.pathname,
            module: this.config.module || 'unknown',
            sessionId: this.sessionId,
            interactions: this.interactions.length
        }, true);
    }
    
    trackEvent(eventName, eventData = {}) {
        if (!this.initialized) return;
        
        const event = {
            type: 'event',
            name: eventName,
            data: eventData,
            timestamp: new Date().toISOString(),
            url: window.location.pathname,
            module: this.config.module || 'unknown',
            sessionId: this.sessionId
        };
        
        // Stocker localement
        this.interactions.push(event);
        
        // Envoyer au serveur
        this._sendToServer('event', event);
        
        // Log si mode admin
        if (this.config.isAdmin) {
            console.log(`📊 Event: ${eventName}`, eventData);
        }
    }
    
    _sendToServer(action, data, isSync = false) {
        // Uniquement si le serveur est configuré (défaut: log silencieux)
        if (!this.config.endpoint) {
            // En mode admin, afficher dans la console
            if (this.config.isAdmin) {
                console.log(`📊 Analytics [${action}]:`, data);
            }
            return;
        }
        
        // Préparer les données
        const payload = {
            action: action,
            data: data,
            sessionId: this.sessionId
        };
        
        // Méthode d'envoi (synchrone uniquement en cas de sortie)
        if (isSync && navigator.sendBeacon) {
            navigator.sendBeacon(this.config.endpoint, JSON.stringify(payload));
        } else {
            // Envoi asynchrone standard
            fetch(this.config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(() => {}); // Ignorer erreurs silencieusement
        }
    }
}

// Initialiser l'analytics au chargement
window.addEventListener('DOMContentLoaded', () => {
    window.Analytics = new PortalAnalytics();
});

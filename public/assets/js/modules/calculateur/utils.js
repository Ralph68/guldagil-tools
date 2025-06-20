/**
 * Titre: Fonctions utilitaires - Module calculateur
 * Chemin: /public/assets/js/modules/calculateur/utils.js
 * Version: 0.5 beta + build
 * 
 * Fonctions utilitaires et helpers partagés
 * Module de base - chargé en premier
 */

// ========================================
// MODULE UTILITAIRES
// ========================================

window.Calculateur = window.Calculateur || {};

Calculateur.Utils = {
    
    /**
     * Initialisation des utilitaires
     */
    init() {
        this.setupGlobalErrorHandler();
        this.setupConsoleOverrides();
        
        if (Calculateur.Config && Calculateur.Config.DEBUG) {
            console.log('🔧 Module Utils initialisé');
        }
    },
    
    /**
     * Gestionnaire d'erreurs global
     */
    setupGlobalErrorHandler() {
        window.addEventListener('error', (event) => {
            if (Calculateur.Config && Calculateur.Config.DEBUG) {
                console.error('🚨 Erreur JavaScript:', {
                    message: event.message,
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                    error: event.error
                });
            }
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            if (Calculateur.Config && Calculateur.Config.DEBUG) {
                console.error('🚨 Promise rejetée:', event.reason);
            }
        });
    },
    
    /**
     * Override console pour le debug
     */
    setupConsoleOverrides() {
        if (Calculateur.Config && !Calculateur.Config.DEBUG) {
            // En production, limiter les logs
            const originalConsole = {
                log: console.log,
                warn: console.warn,
                error: console.error
            };
            
            console.log = () => {}; // Désactiver logs normaux
            console.warn = originalConsole.warn; // Garder warnings
            console.error = originalConsole.error; // Garder erreurs
        }
    },
    
    /**
     * Formatage des prix
     */
    formatPrice(price) {
        if (price === null || price === undefined || isNaN(price)) {
            return 'N/A';
        }
        
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(parseFloat(price));
    },
    
    /**
     * Formatage des nombres
     */
    formatNumber(number, decimals = 0) {
        if (number === null || number === undefined || isNaN(number)) {
            return 'N/A';
        }
        
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(parseFloat(number));
    },
    
    /**
     * Formatage des dates
     */
    formatDate(date, options = {}) {
        if (!date) return 'N/A';
        
        const defaultOptions = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        const formatOptions = { ...defaultOptions, ...options };
        
        try {
            const dateObj = date instanceof Date ? date : new Date(date);
            return dateObj.toLocaleDateString('fr-FR', formatOptions);
        } catch (error) {
            return 'Date invalide';
        }
    },
    
    /**
     * Formatage durée
     */
    formatDuration(milliseconds) {
        if (!milliseconds || milliseconds < 0) return '0ms';
        
        if (milliseconds < 1000) {
            return `${Math.round(milliseconds)}ms`;
        } else if (milliseconds < 60000) {
            return `${(milliseconds / 1000).toFixed(1)}s`;
        } else {
            const minutes = Math.floor(milliseconds / 60000);
            const seconds = Math.floor((milliseconds % 60000) / 1000);
            return `${minutes}m ${seconds}s`;
        }
    },
    
    /**
     * Debounce pour limiter les appels
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Throttle pour limiter la fréquence
     */
    throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Validation email simple
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    /**
     * Validation département français
     */
    isValidDepartement(dept) {
        if (!dept || typeof dept !== 'string') return false;
        
        const deptNum = parseInt(dept);
        return /^\d{2}$/.test(dept) && deptNum >= 1 && deptNum <= 95;
    },
    
    /**
     * Validation poids
     */
    isValidPoids(poids, min = 0.1, max = 3500) {
        const weight = parseFloat(poids);
        return !isNaN(weight) && weight >= min && weight <= max;
    },
    
    /**
     * Sanitization HTML basique
     */
    sanitizeHtml(str) {
        if (!str || typeof str !== 'string') return '';
        
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },
    
    /**
     * Échappement des caractères pour RegExp
     */
    escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    },
    
    /**
     * Génération d'ID unique
     */
    generateId(prefix = 'calc') {
        return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    },
    
    /**
     * Copie profonde d'objet (simple)
     */
    deepClone(obj) {
        if (obj === null || typeof obj !== 'object') return obj;
        if (obj instanceof Date) return new Date(obj.getTime());
        if (obj instanceof Array) return obj.map(item => this.deepClone(item));
        if (typeof obj === 'object') {
            const cloned = {};
            Object.keys(obj).forEach(key => {
                cloned[key] = this.deepClone(obj[key]);
            });
            return cloned;
        }
    },
    
    /**
     * Vérification si objet est vide
     */
    isEmpty(obj) {
        if (!obj) return true;
        if (Array.isArray(obj)) return obj.length === 0;
        if (typeof obj === 'object') return Object.keys(obj).length === 0;
        if (typeof obj === 'string') return obj.trim().length === 0;
        return false;
    },
    
    /**
     * Fusion d'objets (shallow merge)
     */
    merge(target, ...sources) {
        if (!target) target = {};
        sources.forEach(source => {
            if (source) {
                Object.keys(source).forEach(key => {
                    target[key] = source[key];
                });
            }
        });
        return target;
    },
    
    /**
     * Helpers DOM
     */
    dom: {
        /**
         * Sélection sécurisée d'éléments
         */
        $(selector, context = document) {
            try {
                return context.querySelector(selector);
            } catch (e) {
                console.warn('Sélecteur invalide:', selector);
                return null;
            }
        },
        
        /**
         * Sélection multiple sécurisée
         */
        $$(selector, context = document) {
            try {
                return Array.from(context.querySelectorAll(selector));
            } catch (e) {
                console.warn('Sélecteur invalide:', selector);
                return [];
            }
        },
        
        /**
         * Création d'élément
         */
        create(tag, attributes = {}, content = '') {
            const element = document.createElement(tag);
            
            Object.keys(attributes).forEach(attr => {
                if (attr === 'class') {
                    element.className = attributes[attr];
                } else if (attr === 'style' && typeof attributes[attr] === 'object') {
                    Object.assign(element.style, attributes[attr]);
                } else {
                    element.setAttribute(attr, attributes[attr]);
                }
            });
            
            if (content) {
                element.innerHTML = content;
            }
            
            return element;
        },
        
        /**
         * Ajout de classe avec vérification
         */
        addClass(element, className) {
            if (element && className) {
                element.classList.add(className);
            }
        },
        
        /**
         * Suppression de classe avec vérification
         */
        removeClass(element, className) {
            if (element && className) {
                element.classList.remove(className);
            }
        },
        
        /**
         * Toggle de classe
         */
        toggleClass(element, className) {
            if (element && className) {
                element.classList.toggle(className);
            }
        },
        
        /**
         * Vérification si élément a une classe
         */
        hasClass(element, className) {
            return element && className && element.classList.contains(className);
        }
    },
    
    /**
     * Utilitaires de stockage
     */
    storage: {
        /**
         * Sauvegarde sécurisée en localStorage
         */
        set(key, value) {
            try {
                const data = JSON.stringify(value);
                localStorage.setItem(key, data);
                return true;
            } catch (e) {
                console.warn('Erreur sauvegarde localStorage:', e);
                return false;
            }
        },
        
        /**
         * Récupération sécurisée du localStorage
         */
        get(key, defaultValue = null) {
            try {
                const data = localStorage.getItem(key);
                return data ? JSON.parse(data) : defaultValue;
            } catch (e) {
                console.warn('Erreur lecture localStorage:', e);
                return defaultValue;
            }
        },
        
        /**
         * Suppression du localStorage
         */
        remove(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (e) {
                console.warn('Erreur suppression localStorage:', e);
                return false;
            }
        },
        
        /**
         * Sauvegarde sécurisée en sessionStorage
         */
        setSession(key, value) {
            try {
                const data = JSON.stringify(value);
                sessionStorage.setItem(key, data);
                return true;
            } catch (e) {
                console.warn('Erreur sauvegarde sessionStorage:', e);
                return false;
            }
        },
        
        /**
         * Récupération sécurisée du sessionStorage
         */
        getSession(key, defaultValue = null) {
            try {
                const data = sessionStorage.getItem(key);
                return data ? JSON.parse(data) : defaultValue;
            } catch (e) {
                console.warn('Erreur lecture sessionStorage:', e);
                return defaultValue;
            }
        }
    },
    
    /**
     * Utilitaires de performance
     */
    performance: {
        /**
         * Mesure de temps d'exécution
         */
        measure(name, func) {
            const startTime = performance.now();
            const result = func();
            const endTime = performance.now();
            
            if (Calculateur.Config && Calculateur.Config.DEBUG) {
                console.log(`⏱️ ${name}: ${Calculateur.Utils.formatDuration(endTime - startTime)}`);
            }
            
            return result;
        },
        
        /**
         * Mesure asynchrone
         */
        async measureAsync(name, asyncFunc) {
            const startTime = performance.now();
            const result = await asyncFunc();
            const endTime = performance.now();
            
            if (Calculateur.Config && Calculateur.Config.DEBUG) {
                console.log(`⏱️ ${name}: ${Calculateur.Utils.formatDuration(endTime - startTime)}`);
            }
            
            return result;
        }
    },
    
    /**
     * Gestionnaire d'événements personnalisés
     */
    events: {
        /**
         * Émission d'événement
         */
        emit(eventName, data = null) {
            const event = new CustomEvent(eventName, {
                detail: data,
                bubbles: true,
                cancelable: true
            });
            document.dispatchEvent(event);
        },
        
        /**
         * Écoute d'événement
         */
        on(eventName, callback) {
            document.addEventListener(eventName, callback);
        },
        
        /**
         * Suppression d'écoute
         */
        off(eventName, callback) {
            document.removeEventListener(eventName, callback);
        },
        
        /**
         * Écoute unique (une seule fois)
         */
        once(eventName, callback) {
            const onceCallback = (event) => {
                callback(event);
                this.off(eventName, onceCallback);
            };
            this.on(eventName, onceCallback);
        }
    },
    
    /**
     * Utilitaires de debug
     */
    debug: {
        /**
         * Affichage formaté d'objet
         */
        dump(obj, label = 'Debug') {
            if (Calculateur.Config && Calculateur.Config.DEBUG) {
                console.group(`🐛 ${label}`);
                console.log(obj);
                console.groupEnd();
            }
        },
        
        /**
         * Trace d'exécution
         */
        trace(message) {
            if (Calculateur.Config && Calculateur.Config.DEBUG) {
                console.trace(`🔍 ${message}`);
            }
        },
        
        /**
         * Assertion simple
         */
        assert(condition, message) {
            if (!condition) {
                console.error(`❌ Assertion échouée: ${message}`);
                if (Calculateur.Config && Calculateur.Config.DEBUG) {
                    debugger; // Point d'arrêt en mode debug
                }
            }
        }
    }
};

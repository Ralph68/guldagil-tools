/**
 * assets/js/portal.js - JavaScript professionnel pour le portail
 * Chemin: /public/assets/js/portal.js
 * Architecture: Module ES6 avec Design Patterns
 */

'use strict';

// =============================================================================
// PORTAL MAIN MODULE
// =============================================================================

const Portal = (() => {
    
    // Configuration
    const CONFIG = {
        animations: {
            duration: 300,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
        },
        metrics: {
            updateInterval: 30000, // 30 secondes
            animationDelay: 100
        },
        notifications: {
            autoHideDelay: 5000,
            slideOutDuration: 300
        }
    };
    
    // État du module
    const state = {
        initialized: false,
        metricsTimer: null,
        activeNotifications: new Set()
    };
    
    // Cache des éléments DOM
    const elements = {
        modules: null,
        notifications: null,
        metrics: null,
        quickActions: null
    };
    
    /**
     * Initialisation principale
     */
    function init() {
        if (state.initialized) return;
        
        console.log('🏠 Initialisation Portal Enterprise...');
        
        // Cache des éléments
        cacheElements();
        
        // Initialisation des composants
        initModules();
        initNotifications();
        initMetrics();
        initQuickActions();
        initKeyboardShortcuts();
        initAnimations();
        
        // Démarrage des services
        startServices();
        
        state.initialized = true;
        console.log('✅ Portal Enterprise initialisé');
        
        // Événement personnalisé
        document.dispatchEvent(new CustomEvent('portal:ready', {
            detail: { timestamp: Date.now() }
        }));
    }
    
    /**
     * Cache des éléments DOM pour optimiser les performances
     */
    function cacheElements() {
        elements.modules = document.querySelectorAll('.module-card');
        elements.notifications = document.querySelectorAll('.notification');
        elements.metrics = document.querySelectorAll('.metric-item');
        elements.quickActions = document.querySelectorAll('.quick-action');
    }
    
    /**
     * Initialisation des modules
     */
    function initModules() {
        if (!elements.modules) return;
        
        elements.modules.forEach((module, index) => {
            // Animation d'entrée échelonnée
            module.style.animationDelay = `${index * CONFIG.metrics.animationDelay}ms`;
            
            // Gestion des interactions
            initModuleInteractions(module);
            
            // Métriques par module
            initModuleMetrics(module);
        });
    }
    
    /**
     * Interactions avancées pour un module
     */
    function initModuleInteractions(module) {
        const iconWrapper = module.querySelector('.module-icon-wrapper');
        const icon = module.querySelector('.module-icon');
        
        // Effet de survol sophistiqué
        module.addEventListener('mouseenter', () => {
            module.style.transform = 'translateY(-4px) scale(1.02)';
            
            if (iconWrapper) {
                iconWrapper.style.transform = 'scale(1.1) rotate(5deg)';
            }
            
            // Animation de l'icône
            if (icon) {
                icon.style.transform = 'scale(1.1)';
            }
        });
        
        module.addEventListener('mouseleave', () => {
            module.style.transform = '';
            
            if (iconWrapper) {
                iconWrapper.style.transform = '';
            }
            
            if (icon) {
                icon.style.transform = '';
            }
        });
        
        // Clic sur la carte entière
        module.addEventListener('click', (e) => {
            // Éviter le double clic si on clique sur un bouton
            if (e.target.closest('.btn')) return;
            
            const primaryLink = module.querySelector('.btn--primary');
            if (primaryLink) {
                // Animation de feedback
                module.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    module.style.transform = '';
                    primaryLink.click();
                }, 150);
            }
        });
        
        // Accessibilité clavier
        module.setAttribute('tabindex', '0');
        module.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                module.click();
            }
        });
    }
    
    /**
     * Métriques par module
     */
    function initModuleMetrics(module) {
        const metricNumber = module.querySelector('.metric-number');
        if (!metricNumber) return;
        
        // Animation de comptage au chargement
        const finalValue = parseInt(metricNumber.textContent) || 0;
        animateCounter(metricNumber, 0, finalValue, 1500);
    }
    
    /**
     * Animation de compteur
     */
    function animateCounter(element, start, end, duration) {
        const startTime = performance.now();
        const range = end - start;
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Fonction d'easing
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const current = Math.floor(start + (range * easeOutQuart));
            
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = end.toLocaleString();
            }
        }
        
        requestAnimationFrame(updateCounter);
    }
    
    /**
     * Initialisation des notifications
     */
    function initNotifications() {
        if (!elements.notifications) return;
        
        elements.notifications.forEach(notification => {
            initNotificationBehavior(notification);
        });
    }
    
    /**
     * Comportement d'une notification
     */
    function initNotificationBehavior(notification) {
        const dismissBtn = notification.querySelector('.notification-dismiss');
        
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                dismissNotification(notification);
            });
        }
        
        // Auto-hide pour certains types
        if (notification.classList.contains('notification--info')) {
            setTimeout(() => {
                dismissNotification(notification);
            }, CONFIG.notifications.autoHideDelay);
        }
        
        // Animation d'entrée
        notification.style.transform = 'translateX(-100%)';
        notification.style.opacity = '0';
        
        setTimeout(() => {
            notification.style.transition = `all ${CONFIG.notifications.slideOutDuration}ms ${CONFIG.animations.easing}`;
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 100);
    }
    
    /**
     * Fermeture d'une notification
     */
    function dismissNotification(notification) {
        notification.style.transform = 'translateX(-100%)';
        notification.style.opacity = '0';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, CONFIG.notifications.slideOutDuration);
    }
    
    /**
     * Initialisation des métriques
     */
    function initMetrics() {
        if (!elements.metrics) return;
        
        // Animation d'entrée des métriques
        elements.metrics.forEach((metric, index) => {
            const value = metric.querySelector('.metric-value');
            if (!value) return;
            
            // Délai d'animation échelonné
            setTimeout(() => {
                const finalValue = parseInt(value.textContent) || 0;
                animateCounter(value, 0, finalValue, 2000);
            }, index * 200);
        });
    }
    
    /**
     * Initialisation des actions rapides
     */
    function initQuickActions() {
        if (!elements.quickActions) return;
        
        elements.quickActions.forEach(action => {
            // Effet de clic avec feedback tactile
            action.addEventListener('click', (e) => {
                const icon = action.querySelector('.action-icon');
                
                if (icon) {
                    icon.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        icon.style.transform = '';
                    }, 150);
                }
                
                // Analytics (si nécessaire)
                trackAction(action.dataset.action);
            });
            
            // Animation de survol
            action.addEventListener('mouseenter', () => {
                action.style.transform = 'translateY(-4px) scale(1.02)';
            });
            
            action.addEventListener('mouseleave', () => {
                action.style.transform = '';
            });
        });
    }
    
    /**
     * Raccourcis clavier professionnels
     */
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Éviter les conflits avec les champs de saisie
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            // Alt + chiffre pour accès direct aux modules
            if (e.altKey && e.key >= '1' && e.key <= '4') {
                e.preventDefault();
                const moduleIndex = parseInt(e.key) - 1;
                const module = elements.modules[moduleIndex];
                
                if (module) {
                    const link = module.querySelector('.btn--primary');
                    if (link) {
                        // Feedback visuel
                        module.style.outline = '2px solid var(--color-primary)';
                        setTimeout(() => {
                            module.style.outline = '';
                            link.click();
                        }, 200);
                    }
                }
                return;
            }
            
            // Ctrl/Cmd + K pour recherche (futur)
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                showSearchModal();
                return;
            }
            
            // Escape pour fermer les notifications
            if (e.key === 'Escape') {
                dismissAllNotifications();
            }
        });
    }
    
    /**
     * Initialisation des animations
     */
    function initAnimations() {
        // Observer d'intersection pour les animations au scroll
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
            
            // Observer les sections
            document.querySelectorAll('.modules-section, .quick-actions-section').forEach(section => {
                observer.observe(section);
            });
        }
        
        // Animation de chargement de la page
        document.body.classList.add('page-loaded');
    }
    
    /**
     * Démarrage des services
     */
    function startServices() {
        // Mise à jour périodique des métriques
        if (window.PortalConfig && !window.PortalConfig.debug) {
            state.metricsTimer = setInterval(updateMetrics, CONFIG.metrics.updateInterval);
        }
        
        // Vérification de santé périodique
        setTimeout(checkSystemHealth, 5000);
    }
    
    /**
     * Mise à jour des métriques
     */
    async function updateMetrics() {
        try {
            // Simulation d'un appel API
            const response = await fetch('/api/metrics', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                updateMetricsDisplay(data);
            }
        } catch (error) {
            console.warn('⚠️ Erreur mise à jour métriques:', error);
            // Fallback avec simulation
            simulateMetricsUpdate();
        }
    }
    
    /**
     * Mise à jour de l'affichage des métriques
     */
    function updateMetricsDisplay(data) {
        elements.metrics.forEach(metric => {
            const value = metric.querySelector('.metric-value');
            const label = metric.querySelector('.metric-label').textContent.toLowerCase();
            
            if (value && data[label]) {
                const currentValue = parseInt(value.textContent.replace(/,/g, '')) || 0;
                const newValue = data[label];
                
                if (newValue !== currentValue) {
                    animateMetricUpdate(value, currentValue, newValue);
                }
            }
        });
    }
    
    /**
     * Animation de mise à jour d'une métrique
     */
    function animateMetricUpdate(element, oldValue, newValue) {
        element.style.transform = 'scale(1.1)';
        element.style.color = newValue > oldValue ? 'var(--color-success)' : 'var(--color-warning)';
        
        setTimeout(() => {
            animateCounter(element, oldValue, newValue, 800);
            
            setTimeout(() => {
                element.style.transform = '';
                element.style.color = '';
            }, 800);
        }, 100);
    }
    
    /**
     * Simulation de mise à jour des métriques
     */
    function simulateMetricsUpdate() {
        elements.metrics.forEach(metric => {
            const value = metric.querySelector('.metric-value');
            if (!value) return;
            
            const currentValue = parseInt(value.textContent.replace(/,/g, '')) || 0;
            const variation = Math.floor(Math.random() * 5) - 2; // -2 à +2
            const newValue = Math.max(0, currentValue + variation);
            
            if (newValue !== currentValue) {
                animateMetricUpdate(value, currentValue, newValue);
            }
        });
    }
    
    /**
     * Vérification de santé système
     */
    async function checkSystemHealth() {
        try {
            const healthChecks = [
                checkConnectivity(),
                checkPerformance(),
                checkModulesStatus()
            ];
            
            const results = await Promise.allSettled(healthChecks);
            updateSystemStatus(results);
            
        } catch (error) {
            console.warn('⚠️ Erreur vérification santé:', error);
            updateSystemStatus([{ status: 'rejected' }]);
        }
    }
    
    /**
     * Vérification de connectivité
     */
    async function checkConnectivity() {
        if (!navigator.onLine) {
            throw new Error('Hors ligne');
        }
        
        // Test ping vers le serveur
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);
        
        try {
            const response = await fetch('/api/ping', {
                method: 'HEAD',
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            return response.ok;
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }
    
    /**
     * Vérification des performances
     */
    function checkPerformance() {
        if (!window.performance || !performance.navigation) {
            return { loadTime: 0 };
        }
        
        const navigation = performance.getEntriesByType('navigation')[0];
        const loadTime = navigation ? navigation.loadEventEnd - navigation.loadEventStart : 0;
        
        return {
            loadTime,
            memory: performance.memory ? {
                used: performance.memory.usedJSHeapSize,
                total: performance.memory.totalJSHeapSize
            } : null
        };
    }
    
    /**
     * Vérification du statut des modules
     */
    async function checkModulesStatus() {
        const modules = Array.from(elements.modules);
        const statusChecks = modules.map(async (module) => {
            const moduleId = module.dataset.module;
            const path = module.querySelector('.btn--primary')?.getAttribute('href');
            
            if (!path) return { module: moduleId, status: 'unknown' };
            
            try {
                const response = await fetch(path, { method: 'HEAD' });
                return {
                    module: moduleId,
                    status: response.ok ? 'active' : 'error'
                };
            } catch (error) {
                return { module: moduleId, status: 'error' };
            }
        });
        
        return Promise.allSettled(statusChecks);
    }
    
    /**
     * Mise à jour du statut système
     */
    function updateSystemStatus(healthResults) {
        const statusIndicator = document.querySelector('.system-status');
        const statusDot = document.querySelector('.status-dot');
        const statusText = document.querySelector('.status-text');
        
        if (!statusIndicator || !statusDot || !statusText) return;
        
        const hasErrors = healthResults.some(result => result.status === 'rejected');
        const newStatus = hasErrors ? 'degraded' : 'optimal';
        
        // Mise à jour visuelle
        statusIndicator.dataset.status = newStatus;
        statusText.textContent = newStatus === 'optimal' ? 'Système optimal' : 'Performance dégradée';
        
        // Animation du point de statut
        statusDot.style.animation = 'none';
        statusDot.style.background = newStatus === 'optimal' ? '#10b981' : '#f59e0b';
        
        setTimeout(() => {
            statusDot.style.animation = 'pulse 2s infinite';
        }, 100);
    }
    
    /**
     * Recherche modale (placeholder)
     */
    function showSearchModal() {
        console.log('🔍 Recherche - Fonctionnalité à implémenter');
        
        // Notification temporaire
        showNotification('Recherche en cours de développement', 'info');
    }
    
    /**
     * Fermeture de toutes les notifications
     */
    function dismissAllNotifications() {
        document.querySelectorAll('.notification').forEach(notification => {
            dismissNotification(notification);
        });
    }
    
    /**
     * Affichage d'une notification dynamique
     */
    function showNotification(message, type = 'info', duration = 4000) {
        const notificationsBar = document.querySelector('.notifications-bar');
        if (!notificationsBar) return;
        
        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
            </div>
            <button class="notification-dismiss" aria-label="Fermer">×</button>
        `;
        
        // Insertion
        const container = notificationsBar.querySelector('.notifications-container') || notificationsBar;
        container.appendChild(notification);
        
        // Initialisation du comportement
        initNotificationBehavior(notification);
        
        // Auto-hide
        if (duration > 0) {
            setTimeout(() => {
                dismissNotification(notification);
            }, duration);
        }
        
        return notification;
    }
    
    /**
     * Tracking des actions (Analytics)
     */
    function trackAction(action) {
        if (!action) return;
        
        console.log(`📊 Action: ${action}`);
        
        // Intégration future avec Google Analytics, Mixpanel, etc.
        if (window.gtag) {
            gtag('event', 'portal_action', {
                action_type: action,
                timestamp: Date.now()
            });
        }
    }
    
    /**
     * Nettoyage et destruction
     */
    function destroy() {
        if (state.metricsTimer) {
            clearInterval(state.metricsTimer);
            state.metricsTimer = null;
        }
        
        state.initialized = false;
        state.activeNotifications.clear();
        
        // Nettoyage des event listeners
        elements.modules?.forEach(module => {
            module.replaceWith(module.cloneNode(true));
        });
        
        console.log('🏠 Portal nettoyé');
    }
    
    /**
     * API publique
     */
    return {
        init,
        destroy,
        showNotification,
        updateMetrics,
        checkSystemHealth,
        
        // Getters pour l'état
        get isInitialized() { return state.initialized; },
        get config() { return { ...CONFIG }; }
    };
    
})();

// =============================================================================
// MODULE UTILITIES
// =============================================================================

const PortalUtils = {
    
    /**
     * Formatage des nombres
     */
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'k';
        }
        return num.toLocaleString();
    },
    
    /**
     * Debounce pour optimiser les performances
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
     * Throttle pour limiter les appels
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Vérification de support des fonctionnalités
     */
    hasSupport: {
        intersectionObserver: 'IntersectionObserver' in window,
        customElements: 'customElements' in window,
        webAnimations: 'animate' in document.createElement('div'),
        serviceWorker: 'serviceWorker' in navigator
    }
};

// =============================================================================
// COMPOSANT NOTIFICATIONS AVANCÉ
// =============================================================================

class NotificationManager {
    constructor() {
        this.container = null;
        this.notifications = new Map();
        this.init();
    }
    
    init() {
        this.createContainer();
    }
    
    createContainer() {
        if (document.querySelector('.notification-manager')) return;
        
        this.container = document.createElement('div');
        this.container.className = 'notification-manager';
        this.container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
            pointer-events: none;
        `;
        
        document.body.appendChild(this.container);
    }
    
    show(message, type = 'info', options = {}) {
        const id = `notif-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const notification = this.createElement(message, type, options);
        
        notification.id = id;
        this.notifications.set(id, notification);
        this.container.appendChild(notification);
        
        // Animation d'entrée
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        });
        
        // Auto-hide
        if (options.autoHide !== false) {
            setTimeout(() => {
                this.hide(id);
            }, options.duration || 4000);
        }
        
        return id;
    }
    
    createElement(message, type, options) {
        const element = document.createElement('div');
        element.className = `notification notification--${type}`;
        element.style.cssText = `
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-bottom: 12px;
            padding: 16px;
            transform: translateX(100%);
            opacity: 0;
            transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: auto;
            border-left: 4px solid var(--color-${type === 'success' ? 'success' : type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'info'});
        `;
        
        element.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="flex: 1; font-weight: 500;">${message}</span>
                <button style="background: none; border: none; font-size: 18px; cursor: pointer; padding: 4px;" onclick="notifications.hide('${element.id}')">×</button>
            </div>
        `;
        
        return element;
    }
    
    hide(id) {
        const notification = this.notifications.get(id);
        if (!notification) return;
        
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            this.notifications.delete(id);
        }, 300);
    }
    
    clear() {
        this.notifications.forEach((_, id) => this.hide(id));
    }
}

// =============================================================================
// INITIALISATION GLOBALE
// =============================================================================

// Instance globale du gestionnaire de notifications
window.notifications = new NotificationManager();

// Auto-initialisation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Portal.init();
    });
} else {
    Portal.init();
}

// Nettoyage avant déchargement
window.addEventListener('beforeunload', () => {
    Portal.destroy();
});

// Gestion des erreurs JavaScript
window.addEventListener('error', (event) => {
    console.error('Erreur JavaScript:', event.error);
    
    if (window.PortalConfig?.debug) {
        window.notifications?.show(
            `Erreur JavaScript: ${event.error.message}`,
            'error'
        );
    }
});

// Export pour utilisation en module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Portal, PortalUtils, NotificationManager };
}

// Exposition globale pour compatibilité
window.Portal = Portal;
window.PortalUtils = PortalUtils;

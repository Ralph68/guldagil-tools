/**
 * Titre: JavaScript EPI Module
 * Chemin: /features/epi/assets/js/epi.js
 * Version: 0.5 beta + build auto
 */

// Configuration globale
const EPI_CONFIG = {
    BASE_URL: '/features/epi',
    AJAX_TIMEOUT: 10000,
    ANIMATION_DURATION: 300
};

// Classe principale EPI
class EpiManager {
    constructor() {
        this.init();
    }

    init() {
        console.log('🛡️ Module EPI initialisé');
        this.setupEventListeners();
        this.animateOnLoad();
        this.updateLastActivity();
    }

    // Configuration des écouteurs d'événements
    setupEventListeners() {
        // Boutons d'action
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', this.handleActionClick.bind(this));
        });

        // Alertes cliquables
        document.querySelectorAll('.alert-item').forEach(alert => {
            alert.addEventListener('click', this.handleAlertClick.bind(this));
        });

        // Refresh automatique des données
        setInterval(() => {
            this.refreshDashboard();
        }, 300000); // 5 minutes
    }

    // Animation au chargement
    animateOnLoad() {
        const cards = document.querySelectorAll('.epi-card, .metric-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Gestion des clics sur les boutons d'action
    handleActionClick(event) {
        const btn = event.currentTarget;
        const href = btn.getAttribute('href');
        
        if (!href || href === '#') {
            event.preventDefault();
            this.showNotification('Fonctionnalité en cours de développement', 'warning');
            return;
        }

        // Animation de clic
        btn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            btn.style.transform = '';
        }, 150);
    }

    // Gestion des clics sur les alertes
    handleAlertClick(event) {
        const alert = event.currentTarget;
        const employeeName = alert.querySelector('div div').textContent;
        
        if (confirm(`Voulez-vous voir les détails pour ${employeeName} ?`)) {
            // Rediriger vers la page employé (à implémenter)
            this.showNotification('Redirection vers les détails...', 'info');
        }
    }

    // Mise à jour du tableau de bord
    async refreshDashboard() {
        try {
            const response = await this.apiCall('ajax/refresh_dashboard.php', 'GET');
            if (response.success) {
                this.updateMetrics(response.data.metrics);
                this.updateAlerts(response.data.alerts);
                this.updateLastActivity();
                this.showNotification('Données mises à jour', 'success', 2000);
            }
        } catch (error) {
            console.error('Erreur refresh dashboard:', error);
        }
    }

    // Mise à jour des métriques
    updateMetrics(metrics) {
        const elements = {
            'equipped_ratio': document.querySelector('.metric-card:nth-child(1) .metric-value'),
            'expired_count': document.querySelector('.metric-card:nth-child(2) .metric-value'),
            'urgent_count': document.querySelector('.metric-card:nth-child(3) .metric-value'),
            'available_count': document.querySelector('.metric-card:nth-child(4) .metric-value')
        };

        if (elements.equipped_ratio) {
            elements.equipped_ratio.textContent = `${metrics.equipped_employees}/${metrics.total_employees}`;
        }
        // Autres mises à jour...
    }

    // Mise à jour des alertes
    updateAlerts(alerts) {
        const alertsContainer = document.querySelector('.epi-card h3');
        if (alertsContainer && alertsContainer.textContent.includes('Alertes')) {
            const totalAlerts = (alerts.expired?.length || 0) + (alerts.urgent?.length || 0);
            const badge = totalAlerts > 0 ? ` (${totalAlerts})` : '';
            alertsContainer.textContent = `🚨 Alertes prioritaires${badge}`;
        }
    }

    // Mise à jour de la dernière activité
    updateLastActivity() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        // Ajouter ou mettre à jour l'indicateur
        let indicator = document.querySelector('.last-update-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'last-update-indicator';
            indicator.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--epi-primary);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 20px;
                font-size: 0.8rem;
                opacity: 0.8;
                z-index: 1000;
            `;
            document.body.appendChild(indicator);
        }
        
        indicator.textContent = `Dernière MAJ: ${timeString}`;
    }

    // Appel API générique
    async apiCall(endpoint, method = 'GET', data = null) {
        const url = `${EPI_CONFIG.BASE_URL}/${endpoint}`;
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), EPI_CONFIG.AJAX_TIMEOUT);

        try {
            options.signal = controller.signal;
            const response = await fetch(url, options);
            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Timeout: La requête a pris trop de temps');
            }
            throw error;
        }
    }

    // Système de notifications
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        `;

        // Couleurs selon le type
        const colors = {
            success: '#10B981',
            error: '#EF4444',
            warning: '#F59E0B',
            info: '#3B82F6'
        };
        notification.style.background = colors[type] || colors.info;

        notification.textContent = message;
        document.body.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto-suppression
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);

        // Clic pour fermer
        notification.addEventListener('click', () => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });
    }

    // Utilitaires de validation
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    formatDate(date) {
        return new Date(date).toLocaleDateString('fr-FR');
    }

    formatDateTime(date) {
        return new Date(date).toLocaleString('fr-FR');
    }

    // Gestion des modals (pour futures fonctionnalités)
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    // Debounce pour les recherches
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
    }

    // Export de données (CSV, etc.)
    exportToCsv(data, filename) {
        const csv = this.convertToCSV(data);
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    convertToCSV(objArray) {
        const array = typeof objArray !== 'object' ? JSON.parse(objArray) : objArray;
        let str = '';
        
        // En-têtes
        const headers = Object.keys(array[0]);
        str += headers.join(',') + '\r\n';
        
        // Données
        for (let i = 0; i < array.length; i++) {
            let line = '';
            for (let index in array[i]) {
                if (line !== '') line += ',';
                line += array[i][index];
            }
            str += line + '\r\n';
        }
        
        return str;
    }
}

// Fonctions utilitaires globales
window.EpiUtils = {
    // Confirmation avec style
    confirm: (message, callback) => {
        if (confirm(message)) {
            callback();
        }
    },

    // Loading indicator
    showLoading: (element) => {
        element.style.opacity = '0.6';
        element.style.pointerEvents = 'none';
    },

    hideLoading: (element) => {
        element.style.opacity = '1';
        element.style.pointerEvents = 'auto';
    },

    // Validation de formulaire simple
    validateForm: (formElement) => {
        const requiredFields = formElement.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#EF4444';
                isValid = false;
            } else {
                field.style.borderColor = '#D1D5DB';
            }
        });

        return isValid;
    }
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    // Instancier le gestionnaire EPI
    window.epiManager = new EpiManager();

    // Gestion globale des erreurs JavaScript
    window.addEventListener('error', (event) => {
        console.error('Erreur EPI Module:', event.error);
        
        // En mode debug, afficher l'erreur
        if (window.location.hostname === 'localhost' || window.location.search.includes('debug=1')) {
            if (window.epiManager) {
                window.epiManager.showNotification(`Erreur JS: ${event.error.message}`, 'error');
            }
        }
    });

    // Raccourcis clavier
    document.addEventListener('keydown', (event) => {
        // Ctrl+R : Refresh dashboard
        if (event.ctrlKey && event.key === 'r') {
            event.preventDefault();
            if (window.epiManager) {
                window.epiManager.refreshDashboard();
            }
        }
        
        // Échap : Fermer les modales
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'flex') {
                    window.epiManager?.closeModal(modal.id);
                }
            });
        }
    });
});

// Export pour utilisation dans d'autres scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { EpiManager, EpiUtils };
}

/**
 * Titre: Scripts module ADR complets
 * Chemin: /features/adr/assets/js/adr.js
 * Version: 0.5 beta + build auto
 */

// Namespace ADR
if (typeof window.ADR === 'undefined') {
    window.ADR = {};
}

// ========== MODULE DASHBOARD ==========
ADR.Dashboard = {
    config: {
        searchDelay: 300,
        minChars: 1,
        maxResults: 50,
        searchUrl: window.location.href
    },
    
    state: {
        searchTimeout: null,
        selectedIndex: -1,
        currentTab: 'recherche'
    },
    
    elements: {},
    
    init: function() {
        console.log('✅ Initialisation Dashboard ADR');
        
        this.cache_elements();
        this.bind_events();
        this.load_popular_products();
        
        console.log('🎯 Dashboard prêt');
    },
    
    cache_elements: function() {
        this.elements = {
            searchInput: document.getElementById('product-search'),
            suggestionsContainer: document.getElementById('search-suggestions'),
            resultsSection: document.getElementById('search-results'),
            resultsContent: document.getElementById('results-content'),
            resultsTitle: document.getElementById('results-title')
        };
        
        if (!this.elements.searchInput) {
            console.error('❌ Élément search-input non trouvé');
            return false;
        }
        
        return true;
    },
    
    bind_events: function() {
        const input = this.elements.searchInput;
        if (!input) return;
        
        input.addEventListener('input', (e) => {
            this.handle_search_input(e.target.value);
        });
        
        input.addEventListener('keydown', (e) => {
            this.handle_keyboard_navigation(e);
        });
        
        input.addEventListener('focus', () => {
            this.show_suggestions();
        });
        
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.hide_suggestions();
            }
        });
    },
    
    handle_search_input: function(value) {
        clearTimeout(this.state.searchTimeout);
        
        if (value.length < this.config.minChars) {
            this.hide_suggestions();
            return;
        }
        
        this.state.searchTimeout = setTimeout(() => {
            this.search_products(value);
        }, this.config.searchDelay);
    },
    
    search_products: function(query) {
        // Simulation recherche - remplacer par vraie API
        const mockResults = [
            {
                name: 'Acide sulfurique 96%',
                code: 'UN2796',
                badges: ['adr', 'env'],
                category: 'Classe 8'
            },
            {
                name: 'Hydrogène peroxyde',
                code: 'UN2015',
                badges: ['adr'],
                category: 'Classe 5.1'
            }
        ];
        
        this.display_suggestions(mockResults.filter(p => 
            p.name.toLowerCase().includes(query.toLowerCase()) ||
            p.code.toLowerCase().includes(query.toLowerCase())
        ));
    },
    
    display_suggestions: function(results) {
        const container = this.elements.suggestionsContainer;
        if (!container) return;
        
        if (results.length === 0) {
            this.hide_suggestions();
            return;
        }
        
        container.innerHTML = results.map((item, index) => `
            <div class="suggestion-item" data-index="${index}" onclick="ADR.Dashboard.select_product(${index})">
                <div class="suggestion-content">
                    <div class="suggestion-name">${item.name}</div>
                    <div class="suggestion-code">${item.code}</div>
                    <div class="suggestion-badges">
                        ${item.badges.map(badge => 
                            `<span class="badge badge-${badge}">${badge.toUpperCase()}</span>`
                        ).join('')}
                    </div>
                </div>
            </div>
        `).join('');
        
        container.style.display = 'block';
        this.state.selectedIndex = -1;
    },
    
    show_suggestions: function() {
        const container = this.elements.suggestionsContainer;
        if (container && container.children.length > 0) {
            container.style.display = 'block';
        }
    },
    
    hide_suggestions: function() {
        const container = this.elements.suggestionsContainer;
        if (container) {
            container.style.display = 'none';
        }
    },
    
    select_product: function(index) {
        console.log('Produit sélectionné:', index);
        this.hide_suggestions();
    },
    
    load_popular_products: function() {
        // Charger produits populaires
        console.log('📊 Chargement produits populaires...');
    }
};

// ========== MODULE DESTINATAIRE ==========
ADR.Destinataire = {
    current_destinataire: {},
    
    init: function() {
        console.log('📋 Initialisation module Destinataire');
        this.bind_events();
    },
    
    bind_events: function() {
        // Événements destinataire
    },
    
    select_destinataire: function(data) {
        this.current_destinataire = data;
        console.log('Destinataire sélectionné:', data);
    },
    
    show_create_destinataire_form: function() {
        console.log('💼 Affichage formulaire nouveau destinataire');
    },
    
    save_new_destinataire: function() {
        console.log('💾 Sauvegarde nouveau destinataire');
    },
    
    cancel_new_destinataire: function() {
        console.log('❌ Annulation nouveau destinataire');
    },
    
    change_destinataire: function() {
        console.log('🔄 Changement destinataire');
    },
    
    select_ville: function(ville) {
        console.log('🏙️ Ville sélectionnée:', ville);
    },
    
    validate_destinataire: function(show_errors = true) {
        const is_valid = this.current_destinataire && 
                        this.current_destinataire.nom && 
                        this.current_destinataire.adresse;
        
        if (!is_valid && show_errors) {
            this.show_notification('Veuillez compléter les informations destinataire', 'warning');
        }
        
        return is_valid;
    },
    
    clear_destinataire_form: function() {
        this.current_destinataire = {};
        console.log('🧹 Formulaire destinataire vidé');
    },
    
    get_destinataire_data: function() {
        return {
            isValid: this.validate_destinataire(false),
            data: this.current_destinataire
        };
    }
};

// ========== UTILITAIRES ==========
ADR.Utils = {
    show_notification: function(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `adr-notification adr-notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${this.get_notification_icon(type)}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${this.get_notification_color(type)};
            color: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, duration);
    },
    
    get_notification_color: function(type) {
        const colors = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        return colors[type] || colors.info;
    },
    
    get_notification_icon: function(type) {
        const icons = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        return icons[type] || icons.info;
    }
};

// ========== CONFIGURATION GLOBALE ==========
ADR.Config = {
    searchUrl: window.location.href,
    user: null,
    debug: true
};

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Chargement module ADR...');
    
    // Configuration depuis variables globales
    if (typeof window.ADR_CONFIG !== 'undefined') {
        Object.assign(ADR.Config, window.ADR_CONFIG);
    }
    
    // Initialisation modules
    if (ADR.Dashboard && typeof ADR.Dashboard.init === 'function') {
        ADR.Dashboard.init();
    }
    
    if (ADR.Destinataire && typeof ADR.Destinataire.init === 'function') {
        ADR.Destinataire.init();
    }
    
    console.log('✅ Module ADR chargé');
});

// ========== API PUBLIQUE ==========
// Exposer fonctions globalement pour compatibilité
window.select_destinataire = function(data) {
    return ADR.Destinataire.select_destinataire(data);
};

window.show_create_destinataire_form = function() {
    return ADR.Destinataire.show_create_destinataire_form();
};

window.save_new_destinataire = function() {
    return ADR.Destinataire.save_new_destinataire();
};

window.cancel_new_destinataire = function() {
    return ADR.Destinataire.cancel_new_destinataire();
};

window.change_destinataire = function() {
    return ADR.Destinataire.change_destinataire();
};

window.select_ville = function(ville) {
    return ADR.Destinataire.select_ville(ville);
};

window.get_destinataire_data = function() {
    return ADR.Destinataire.get_destinataire_data();
};

window.clear_destinataire_form = function() {
    return ADR.Destinataire.clear_destinataire_form();
};

window.validate_destinataire = function(show_errors = true) {
    return ADR.Destinataire.validate_destinataire(show_errors);
};

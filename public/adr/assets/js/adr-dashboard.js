// public/adr/assets/js/adr-dashboard.js - JavaScript Dashboard ADR
console.log('📊 Chargement module Dashboard ADR...');

// Namespace ADR si pas encore défini
if (typeof window.ADR === 'undefined') {
    window.ADR = {};
}

// Module Dashboard
ADR.Dashboard = {
    // Configuration
    config: {
        searchDelay: 300,
        minChars: 1,
        maxResults: 50,
        searchUrl: window.location.href
    },
    
    // Variables d'état
    state: {
        searchTimeout: null,
        selectedIndex: -1,
        currentTab: 'recherche'
    },
    
    // Éléments DOM
    elements: {},
    
    // Initialisation
    init: function() {
        console.log('✅ Initialisation Dashboard ADR');
        
        this.cacheElements();
        this.bindEvents();
        this.loadPopularProducts();
        
        console.log('🎯 Dashboard prêt');
    },
    
    // Cache des éléments DOM
    cacheElements: function() {
        this.elements = {
            searchInput: document.getElementById('product-search'),
            suggestionsContainer: document.getElementById('search-suggestions'),
            resultsSection: document.getElementById('search-results'),
            resultsContent: document.getElementById('results-content'),
            resultsTitle: document.getElementById('results-title')
        };
        
        // Vérifier éléments critiques
        if (!this.elements.searchInput) {
            console.error('❌ Élément search-input non trouvé');
            return false;
        }
        
        return true;
    },
    
    // Liaison des événements
    bindEvents: function() {
        const input = this.elements.searchInput;
        if (!input) return;
        
        input.addEventListener('input', (e) => this.handleSearchInput(e));
        input.addEventListener('keydown', (e) => this.handleKeyNavigation(e));
        input.addEventListener('focus', () => this.handleSearchFocus());
        input.addEventListener('blur', () => {
            setTimeout(() => this.hideSuggestions(), 150);
        });
    },
    
    // Gestion saisie recherche
    handleSearchInput: function(e) {
        const term = e.target.value.trim();
        this.state.selectedIndex = -1;
        
        console.log('🔍 Recherche:', term);
        
        if (term.length === 0) {
            this.hideSuggestions();
            this.hideResults();
            this.loadPopularProducts();
            return;
        }
        
        if (term.length < this.config.minChars) {
            this.hideSuggestions();
            return;
        }
        
        clearTimeout(this.state.searchTimeout);
        this.state.searchTimeout = setTimeout(() => {
            this.searchProducts(term, true);
        }, this.config.searchDelay);
    },
    
    // Navigation clavier
    handleKeyNavigation: function(e) {
        const suggestions = document.querySelectorAll('.suggestion-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.state.selectedIndex = Math.min(this.state.selectedIndex + 1, suggestions.length - 1);
                this.updateSelection(suggestions);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.state.selectedIndex = Math.max(this.state.selectedIndex - 1, -1);
                this.updateSelection(suggestions);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.state.selectedIndex >= 0 && suggestions[this.state.selectedIndex]) {
                    this.selectProduct(suggestions[this.state.selectedIndex].dataset.code);
                } else {
                    this.performFullSearch(this.elements.searchInput.value.trim());
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                this.elements.searchInput.blur();
                break;
        }
    },
    
    // Focus sur recherche
    handleSearchFocus: function() {
        const term = this.elements.searchInput.value.trim();
        if (term.length >= this.config.minChars) {
            this.searchProducts(term, true);
        } else if (term.length === 0) {
            this.loadPopularProducts();
        }
    },
    
    // Mise à jour sélection
    updateSelection: function(suggestions) {
        suggestions.forEach((item, index) => {
            if (index === this.state.selectedIndex) {
                item.style.background = 'var(--adr-primary)';
                item.style.color = 'white';
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.style.background = '';
                item.style.color = '';
            }
        });
    },
    
    // Recherche produits via AJAX
    searchProducts: function(term, suggestionsOnly = false) {
        console.log('🔍 Recherche API:', term, suggestionsOnly ? '(suggestions)' : '(complet)');
        
        const formData = new FormData();
        formData.append('action', suggestionsOnly ? 'suggestions' : 'search_products');
        formData.append('q', term);
        formData.append('ajax_action', '1');
        
        fetch(this.config.searchUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Réponse:', data);
            
            if (data.success) {
                if (suggestionsOnly) {
                    this.displaySuggestions(data);
                } else {
                    this.displayResults(data, term);
                }
            } else {
                console.error('❌ Erreur API:', data.error);
                if (!suggestionsOnly) {
                    this.showError('Aucun résultat trouvé');
                }
            }
        })
        .catch(error => {
            console.error('❌ Erreur réseau:', error);
            if (!suggestionsOnly) {
                this.showError('Erreur de connexion');
            }
        });
    },
    
    // Affichage suggestions
    displaySuggestions: function(data) {
        const suggestions = data.data || [];
        
        if (suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        let html = '';
        suggestions.forEach((product, index) => {
            const badges = this.generateBadges(product);
            
            html += `
                <div class="suggestion-item" data-code="${this.escapeHtml(product.code_produit)}" data-index="${index}">
                    <div class="suggestion-product">
                        <div class="suggestion-name">${this.escapeHtml(product.nom_produit)}</div>
                        <div class="suggestion-code">Code: ${product.code_produit}${product.numero_un ? ' | UN ' + product.numero_un : ''}</div>
                    </div>
                    <div class="suggestion-badges">${badges}</div>
                </div>
            `;
        });
        
        this.elements.suggestionsContainer.innerHTML = html;
        this.elements.suggestionsContainer.style.display = 'block';
        
        // Event listeners sur suggestions
        document.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this.selectProduct(item.dataset.code);
            });
            
            item.addEventListener('mouseenter', () => {
                this.state.selectedIndex = parseInt(item.dataset.index);
                this.updateSelection(document.querySelectorAll('.suggestion-item'));
            });
        });
    },
    
    // Sélection produit
    selectProduct: function(code) {
        console.log('📦 Sélection:', code);
        this.hideSuggestions();
        this.elements.searchInput.value = code;
        this.performFullSearch(code);
    },
    
    // Recherche complète
    performFullSearch: function(term) {
        console.log('🔍 Recherche complète:', term);
        
        if (this.elements.resultsContent) {
            this.elements.resultsContent.innerHTML = '<div class="loading"><div class="spinner"></div>Recherche en cours...</div>';
            this.showResults();
        }
        
        this.searchProducts(term, false);
    },
    
    // Affichage résultats
    displayResults: function(data, searchTerm) {
        if (!this.elements.resultsContent || !this.elements.resultsTitle) return;
        
        const results = data.data || [];
        
        this.elements.resultsTitle.textContent = `Résultats pour "${searchTerm}" (${results.length})`;
        
        if (results.length === 0) {
            this.elements.resultsContent.innerHTML = `
                <div style="text-align:center;padding:2rem;color:#666;">
                    <div style="font-size:2rem;margin-bottom:1rem;">📭</div>
                    <div>Aucun produit trouvé pour "${searchTerm}"</div>
                    <div style="margin-top:1rem;font-size:0.9rem;">
                        Vérifiez l'orthographe ou essayez avec moins de caractères
                    </div>
                </div>
            `;
            return;
        }
        
        let html = `
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Code</th>
                        <th>UN / Description</th>
                        <th>Catégorie</th>
                        <th>Contenant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        results.forEach(product => {
            const statusBadges = this.generateBadges(product);
            
            const unInfo = product.numero_un ? 
                `<strong>UN ${product.numero_un}</strong><br><small>${this.escapeHtml(product.nom_description_un || 'Description non disponible')}</small>` : 
                '<span style="color:#999;">Non-ADR</span>';
            
            html += `
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--adr-primary);">${this.escapeHtml(product.nom_produit)}</div>
                        ${product.nom_technique ? `<small style="color:#666;">${this.escapeHtml(product.nom_technique)}</small>` : ''}
                    </td>
                    <td>
                        <code>${this.escapeHtml(product.code_produit)}</code>
                    </td>
                    <td>${unInfo}</td>
                    <td style="text-align:center;">
                        ${product.categorie_transport && product.categorie_transport !== '0' ? 
                            `<span class="badge badge-cat">Cat. ${product.categorie_transport}</span>` : 
                            '<span style="color:#999;">-</span>'
                        }
                    </td>
                    <td>
                        ${product.type_contenant || '-'}<br>
                        <small style="color:#666;">${product.poids_contenant || ''}</small>
                    </td>
                    <td>${statusBadges}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        
        // Indication mode démo si applicable
        if (data.demo_mode) {
            html += '<div style="margin-top:1rem;padding:1rem;background:#fff3cd;border-radius:8px;color:#856404;"><strong>ℹ️ Mode démo</strong> - Données de test affichées car problème base de données</div>';
        }
        
        this.elements.resultsContent.innerHTML = html;
    },
    
    // Génération badges
    generateBadges: function(product) {
        const badges = [];
        
        if (product.numero_un) {
            badges.push(`<span class="badge badge-adr">UN ${product.numero_un}</span>`);
        }
        
        if (product.danger_environnement === 'OUI') {
            badges.push(`<span class="badge badge-env">ENV</span>`);
        }
        
        if (product.categorie_transport && product.categorie_transport !== '0') {
            badges.push(`<span class="badge badge-cat">Cat.${product.categorie_transport}</span>`);
        }
        
        if (product.corde_article_ferme === 'x') {
            badges.push(`<span class="badge badge-closed">FERMÉ</span>`);
        }
        
        return badges.join(' ');
    },
    
    // Chargement produits populaires
    loadPopularProducts: function() {
        console.log('💡 Chargement produits populaires');
        
        const formData = new FormData();
        formData.append('action', 'popular_products');
        formData.append('ajax_action', '1');
        
        fetch(this.config.searchUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                this.displayInitialSuggestions(data.data);
            }
        })
        .catch(error => {
            console.log('Info: Produits populaires non disponibles');
        });
    },
    
    // Suggestions initiales
    displayInitialSuggestions: function(products) {
        if (!this.elements.suggestionsContainer || !products || products.length === 0) return;
        
        let html = '<div style="padding:0.5rem 1rem;background:#f8f9fa;font-size:0.8rem;color:#666;border-bottom:1px solid #eee;">💡 Produits ADR fréquents :</div>';
        
        products.forEach((product, index) => {
            const badges = this.generateBadges(product);
            html += `
                <div class="suggestion-item" data-code="${product.code_produit}" data-index="${index}">
                    <div class="suggestion-product">
                        <div class="suggestion-name">${this.escapeHtml(product.nom_produit)}</div>
                        <div class="suggestion-code">Code: ${product.code_produit}</div>
                    </div>
                    <div class="suggestion-badges">${badges}</div>
                </div>
            `;
        });
        
        this.elements.suggestionsContainer.innerHTML = html;
        this.elements.suggestionsContainer.style.display = 'block';
        
        // Event listeners
        document.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this.selectProduct(item.dataset.code);
            });
        });
    },
    
    // Affichage/masquage
    showResults: function() {
        if (this.elements.resultsSection) {
            this.elements.resultsSection.style.display = 'block';
        }
    },
    
    hideResults: function() {
        if (this.elements.resultsSection) {
            this.elements.resultsSection.style.display = 'none';
        }
    },
    
    hideSuggestions: function() {
        if (this.elements.suggestionsContainer) {
            this.elements.suggestionsContainer.style.display = 'none';
        }
    },
    
    // Affichage erreur
    showError: function(message) {
        if (this.elements.resultsContent) {
            this.elements.resultsContent.innerHTML = `
                <div style="text-align:center;color:#666;padding:2rem;">
                    ❌ ${message}
                </div>
            `;
        }
        this.showResults();
    },
    
    // Nettoyage résultats
    clearResults: function() {
        this.hideResults();
        if (this.elements.searchInput) {
            this.elements.searchInput.value = '';
            this.elements.searchInput.focus();
        }
        this.state.selectedIndex = -1;
        this.loadPopularProducts();
    },
    
    // Utilitaire échappement HTML
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Fonctions globales pour compatibilité
ADR.showTab = function(tabName) {
    console.log('🔄 Changement onglet:', tabName);
    
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Désactiver tous les boutons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    const tab = document.getElementById('tab-' + tabName);
    const button = document.querySelector('[data-tab="' + tabName + '"]');
    
    if (tab) tab.classList.add('active');
    if (button) button.classList.add('active');
    
    // Actions spécifiques
    if (tabName === 'recherche' && ADR.Dashboard.elements.searchInput) {
        setTimeout(() => {
            ADR.Dashboard.elements.searchInput.focus();
            if (!ADR.Dashboard.elements.searchInput.value) {
                ADR.Dashboard.loadPopularProducts();
            }
        }, 100);
    }
    
    ADR.Dashboard.state.currentTab = tabName;
};

ADR.clearResults = function() {
    ADR.Dashboard.clearResults();
};

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl+K pour focus recherche
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        ADR.showTab('recherche');
        if (ADR.Dashboard.elements.searchInput) {
            ADR.Dashboard.elements.searchInput.focus();
            ADR.Dashboard.elements.searchInput.select();
        }
    }
    
    // Escape pour nettoyer
    if (e.key === 'Escape' && ADR.Dashboard.state.currentTab === 'recherche') {
        ADR.clearResults();
    }
    
    // Chiffres pour navigation onglets
    if (e.ctrlKey && ['1', '2', '3'].includes(e.key)) {
        e.preventDefault();
        const tabs = ['recherche', 'expeditions', 'statistiques'];
        const tabIndex = parseInt(e.key) - 1;
        if (tabs[tabIndex]) {
            ADR.showTab(tabs[tabIndex]);
        }
    }
});

console.log('✅ Module Dashboard ADR chargé');
console.log('💡 Raccourcis: Ctrl+K (recherche), Ctrl+1/2/3 (onglets), Escape (nettoyer)');

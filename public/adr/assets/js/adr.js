/**
 * Titre: JavaScript pour la recherche ADR
 * Chemin: /public/adr/assets/js/search.js
 * Version: 0.5 beta + build auto
 */

// Extension du namespace ADR pour la recherche
if (typeof window.ADR === 'undefined') {
    window.ADR = {};
}

// ========== MODULE RECHERCHE ==========
ADR.Search = {
    config: {
        apiEndpoint: '/adr/search/search.php',
        minChars: 1,
        maxResults: 50,
        searchDelay: 300,
        currentPage: 1,
        itemsPerPage: 20
    },
    
    state: {
        searchTimeout: null,
        selectedIndex: -1,
        lastQuery: '',
        currentResults: [],
        totalResults: 0,
        isSearching: false
    },
    
    elements: {},
    
    init: function() {
        console.log('🔍 Initialisation module recherche ADR');
        
        // Configuration depuis variables globales
        if (typeof window.ADR_SEARCH_CONFIG !== 'undefined') {
            Object.assign(this.config, window.ADR_SEARCH_CONFIG);
        }
        
        this.cacheElements();
        this.bindEvents();
        
        console.log('✅ Recherche ADR prête');
    },
    
    cacheElements: function() {
        this.elements = {
            searchInput: document.getElementById('product-search'),
            suggestionsContainer: document.getElementById('search-suggestions'),
            resultsSection: document.getElementById('search-results'),
            resultsContent: document.getElementById('results-content'),
            resultsTitle: document.getElementById('results-title'),
            popularSection: document.getElementById('popular-products'),
            categoryFilter: document.getElementById('category-filter'),
            transportFilter: document.getElementById('transport-filter'),
            adrOnlyFilter: document.getElementById('adr-only'),
            envDangerFilter: document.getElementById('env-danger')
        };
        
        return !!this.elements.searchInput;
    },
    
    bindEvents: function() {
        const input = this.elements.searchInput;
        if (!input) return;
        
        // Événements de saisie
        input.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        input.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });
        
        input.addEventListener('focus', () => {
            this.showSuggestions();
        });
        
        // Fermer suggestions au clic extérieur
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.hideSuggestions();
            }
        });
        
        // Gestionnaires filtres
        [this.elements.categoryFilter, this.elements.transportFilter, 
         this.elements.adrOnlyFilter, this.elements.envDangerFilter].forEach(element => {
            if (element) {
                element.addEventListener('change', () => {
                    if (this.elements.searchInput.value) {
                        this.performFullSearch(this.elements.searchInput.value);
                    }
                });
            }
        });
    },
    
    handleSearchInput: function(query) {
        clearTimeout(this.state.searchTimeout);
        
        if (query.length < this.config.minChars) {
            this.hideSuggestions();
            this.hideResults();
            this.showPopular();
            return;
        }
        
        this.state.searchTimeout = setTimeout(() => {
            this.fetchSuggestions(query);
        }, this.config.searchDelay);
    },
    
    handleKeyboardNavigation: function(e) {
        const suggestions = this.elements.suggestionsContainer;
        if (!suggestions || suggestions.style.display === 'none') return;
        
        const items = suggestions.querySelectorAll('.suggestion-item');
        if (items.length === 0) return;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.state.selectedIndex = Math.min(this.state.selectedIndex + 1, items.length - 1);
                this.highlightSuggestion(items);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.state.selectedIndex = Math.max(this.state.selectedIndex - 1, -1);
                this.highlightSuggestion(items);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.state.selectedIndex >= 0) {
                    this.selectSuggestion(items[this.state.selectedIndex]);
                } else {
                    this.performFullSearch(this.elements.searchInput.value);
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                this.elements.searchInput.blur();
                break;
        }
    },
    
    fetchSuggestions: function(query) {
        if (this.state.isSearching) return;
        
        const url = `${this.config.apiEndpoint}?action=suggestions&q=${encodeURIComponent(query)}&limit=10`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displaySuggestions(data.suggestions);
                } else {
                    console.error('Erreur suggestions:', data.error);
                }
            })
            .catch(error => {
                console.error('Erreur fetch suggestions:', error);
            });
    },
    
    displaySuggestions: function(suggestions) {
        const container = this.elements.suggestionsContainer;
        if (!container) return;
        
        if (!suggestions || suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        const html = suggestions.map((product, index) => `
            <div class="suggestion-item" data-index="${index}" onclick="ADR.Search.selectSuggestionByCode('${product.code_produit}')">
                <div class="suggestion-content">
                    <div class="suggestion-name">${this.escapeHtml(product.nom_produit || 'Produit sans nom')}</div>
                    <div class="suggestion-code">Code: ${product.code_produit}</div>
                    <div class="suggestion-badges">
                        ${product.numero_un ? `<span class="badge badge-adr">UN${product.numero_un}</span>` : ''}
                        ${product.danger_environnement === 'oui' ? '<span class="badge badge-env">ENV</span>' : ''}
                        ${product.categorie_transport ? `<span class="badge badge-cat">Cat. ${product.categorie_transport}</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
        this.showSuggestions();
        this.state.selectedIndex = -1;
    },
    
    selectSuggestionByCode: function(code) {
        this.elements.searchInput.value = code;
        this.hideSuggestions();
        this.performFullSearch(code);
    },
    
    selectSuggestion: function(element) {
        const code = element.querySelector('.suggestion-code').textContent.replace('Code: ', '');
        this.selectSuggestionByCode(code);
    },
    
    highlightSuggestion: function(items) {
        items.forEach((item, index) => {
            item.classList.toggle('highlighted', index === this.state.selectedIndex);
        });
    },
    
    performFullSearch: function(query) {
        if (this.state.isSearching) return;
        
        this.state.isSearching = true;
        this.state.lastQuery = query;
        this.hideSuggestions();
        this.hidePopular();
        
        // Construire URL avec filtres
        const params = new URLSearchParams({
            action: 'search',
            q: query,
            limit: this.config.maxResults
        });
        
        // Ajouter filtres
        if (this.elements.categoryFilter?.value) {
            params.append('category', this.elements.categoryFilter.value);
        }
        if (this.elements.transportFilter?.value) {
            params.append('transport', this.elements.transportFilter.value);
        }
        if (this.elements.adrOnlyFilter?.checked) {
            params.append('adr_only', 'true');
        }
        if (this.elements.envDangerFilter?.checked) {
            params.append('env_danger', 'true');
        }
        
        const url = `${this.config.apiEndpoint}?${params.toString()}`;
        
        // Afficher loading
        this.showLoading();
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                this.state.isSearching = false;
                
                if (data.success) {
                    this.displayResults(data.products, data.total, query);
                } else {
                    this.showError('Erreur de recherche: ' + data.error);
                }
            })
            .catch(error => {
                this.state.isSearching = false;
                console.error('Erreur recherche:', error);
                this.showError('Erreur de connexion');
            });
    },
    
    displayResults: function(products, total, query) {
        if (!this.elements.resultsContent) return;
        
        this.state.currentResults = products;
        this.state.totalResults = total;
        
        // Titre des résultats
        if (this.elements.resultsTitle) {
            const count = products.length;
            const totalText = total > count ? ` (${total} au total)` : '';
            this.elements.resultsTitle.textContent = 
                `${count} résultat${count > 1 ? 's' : ''} pour "${query}"${totalText}`;
        }
        
        if (products.length === 0) {
            this.elements.resultsContent.innerHTML = `
                <div class="no-results">
                    <p>Aucun produit trouvé pour "${this.escapeHtml(query)}"</p>
                    <p>Essayez avec d'autres termes de recherche ou vérifiez les filtres.</p>
                </div>
            `;
        } else {
            const html = products.map(product => this.renderProductCard(product)).join('');
            this.elements.resultsContent.innerHTML = html;
        }
        
        this.showResults();
    },
    
    renderProductCard: function(product) {
        return `
            <div class="result-item" onclick="ADR.Search.showProductDetail('${product.code_produit}')">
                <div class="result-header">
                    <span class="result-code">${product.code_produit}</span>
                    <div class="result-badges">
                        ${product.numero_un ? `<span class="badge badge-adr">UN${product.numero_un}</span>` : ''}
                        ${product.danger_environnement === 'oui' ? '<span class="badge badge-env">ENV</span>' : ''}
                        ${product.categorie_transport ? `<span class="badge badge-cat">Cat. ${product.categorie_transport}</span>` : ''}
                        ${product.statut_produit ? `<span class="badge badge-status">${product.statut_produit}</span>` : ''}
                    </div>
                </div>
                <div class="result-name">${this.escapeHtml(product.nom_produit || 'Produit sans nom')}</div>
                <div class="result-details">
                    ${product.nom_description_un ? `<span class="result-label">Description UN:</span><span>${this.escapeHtml(product.nom_description_un)}</span>` : ''}
                    ${product.type_contenant ? `<span class="result-label">Contenant:</span><span>${this.escapeHtml(product.type_contenant)}</span>` : ''}
                    ${product.poids_contenant ? `<span class="result-label">Poids:</span><span>${this.escapeHtml(product.poids_contenant)}</span>` : ''}
                    ${product.groupe_emballage ? `<span class="result-label">Groupe:</span><span>${product.groupe_emballage}</span>` : ''}
                    ${product.quota_max_vehicule ? `<span class="result-label">Quota véhicule:</span><span>${product.quota_max_vehicule} kg</span>` : ''}
                </div>
            </div>
        `;
    },
    
    showProductDetail: function(code) {
        // Afficher détail produit (modal ou page dédiée)
        const url = `${this.config.apiEndpoint}?action=detail&q=${encodeURIComponent(code)}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayProductModal(data.product, data.history);
                } else {
                    alert('Erreur lors du chargement du détail');
                }
            })
            .catch(error => {
                console.error('Erreur détail produit:', error);
                alert('Erreur de connexion');
            });
    },
    
    displayProductModal: function(product, history) {
        // Créer modal simple pour le détail
        const modal = document.createElement('div');
        modal.className = 'product-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${product.code_produit} - ${this.escapeHtml(product.nom_produit || 'Produit sans nom')}</h2>
                    <button class="modal-close" onclick="this.closest('.product-modal').remove()">×</button>
                </div>
                <div class="modal-body">
                    <div class="product-info">
                        ${product.numero_un ? `<p><strong>Numéro UN:</strong> UN${product.numero_un}</p>` : ''}
                        ${product.nom_description_un ? `<p><strong>Description UN:</strong> ${this.escapeHtml(product.nom_description_un)}</p>` : ''}
                        ${product.categorie_transport ? `<p><strong>Catégorie transport:</strong> ${product.categorie_transport}</p>` : ''}
                        ${product.type_contenant ? `<p><strong>Type contenant:</strong> ${this.escapeHtml(product.type_contenant)}</p>` : ''}
                        ${product.danger_environnement === 'oui' ? '<p><strong>⚠️ Dangereux pour l\'environnement</strong></p>' : ''}
                    </div>
                    ${history.length > 0 ? `
                        <div class="product-history">
                            <h3>Déclarations récentes</h3>
                            <ul>
                                ${history.slice(0, 5).map(h => `
                                    <li>${h.date_expedition} - ${h.transporteur} - ${h.quantite_declaree} ${h.unite_quantite} 
                                    ${h.destinataire_nom ? `vers ${h.destinataire_nom}` : ''}</li>
                                `).join('')}
                            </ul>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Style modal
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7); z-index: 1000;
            display: flex; align-items: center; justify-content: center;
        `;
        
        modal.querySelector('.modal-content').style.cssText = `
            background: white; border-radius: 8px;

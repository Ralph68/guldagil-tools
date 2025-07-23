/**
 * Titre: Recherche ADR optimisée avec suggestions dynamiques
 * Chemin: /public/adr/assets/js/search.js (remplacement)
 * Version: 0.5 beta + build auto
 */

// Extension du namespace ADR pour la recherche
if (typeof window.ADR === 'undefined') {
    window.ADR = {};
}

// ========== MODULE RECHERCHE OPTIMISÉ ==========
ADR.Search = {
    config: {
        apiEndpoint: '/adr/search/search.php',
        minChars: 2, // Réduit à 2 caractères pour plus de réactivité
        maxResults: 100,
        searchDelay: 200, // Réduit pour plus de réactivité
        currentPage: 1,
        itemsPerPage: 25
    },
    
    state: {
        searchTimeout: null,
        selectedIndex: -1,
        lastQuery: '',
        currentResults: [],
        totalResults: 0,
        isSearching: false,
        suggestionsVisible: false
    },
    
    elements: {},
    
    init: function() {
        console.log('🔍 Initialisation module recherche ADR optimisé');
        
        // Configuration depuis variables globales
        if (typeof window.ADR_SEARCH_CONFIG !== 'undefined') {
            Object.assign(this.config, window.ADR_SEARCH_CONFIG);
        }
        
        this.cacheElements();
        this.bindEvents();
        this.setupTable();
        
        console.log('✅ Recherche ADR optimisée prête');
    },
    
    cacheElements: function() {
        this.elements = {
            searchInput: document.getElementById('product-search'),
            suggestionsContainer: document.getElementById('search-suggestions'),
            resultsSection: document.getElementById('search-results'),
            resultsContent: document.getElementById('results-content'),
            resultsTitle: document.getElementById('results-title'),
            popularSection: document.getElementById('popular-products'),
            searchHint: document.getElementById('search-hint'),
            // Filtres
            categoryFilter: document.getElementById('category-filter'),
            transportFilter: document.getElementById('transport-filter'),
            adrOnlyFilter: document.getElementById('adr-only'),
            envDangerFilter: document.getElementById('env-danger')
        };
        
        return Object.values(this.elements).some(el => el !== null);
    },
    
    bindEvents: function() {
        if (!this.elements.searchInput) {
            console.warn('Element product-search introuvable');
            return;
        }
        
        // Recherche en temps réel avec suggestions
        this.elements.searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        // Navigation clavier dans les suggestions
        this.elements.searchInput.addEventListener('keydown', (e) => {
            this.handleKeyNavigation(e);
        });
        
        // Focus/blur
        this.elements.searchInput.addEventListener('focus', () => {
            if (this.elements.suggestionsContainer && this.elements.suggestionsContainer.innerHTML.trim()) {
                this.showSuggestions();
            }
        });
        
        this.elements.searchInput.addEventListener('blur', () => {
            setTimeout(() => this.hideSuggestions(), 150);
        });
        
        // Filtres en temps réel
        ['categoryFilter', 'transportFilter', 'adrOnlyFilter', 'envDangerFilter'].forEach(filterId => {
            const element = this.elements[filterId];
            if (element) {
                element.addEventListener('change', () => {
                    if (this.state.lastQuery.length >= this.config.minChars) {
                        this.performFullSearch();
                    }
                });
            }
        });
        
        // Clic en dehors pour fermer suggestions
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.hideSuggestions();
            }
        });
    },
    
    setupTable: function() {
        // Créer structure tableau si elle n'existe pas
        if (this.elements.resultsContent && !this.elements.resultsContent.querySelector('table')) {
            this.elements.resultsContent.innerHTML = `
                <div class="table-responsive">
                    <table class="adr-results-table" id="adr-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Nom produit</th>
                                <th>UN</th>
                                <th>Classe</th>
                                <th>Groupe</th>
                                <th>Cat.</th>
                                <th>ENV</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adr-table-body">
                            <!-- Résultats seront injectés ici -->
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination" id="table-pagination">
                    <!-- Pagination sera générée ici -->
                </div>
            `;
        }
    },
    
    handleSearchInput: function(query) {
        query = query.trim();
        this.state.lastQuery = query;
        
        if (this.state.searchTimeout) {
            clearTimeout(this.state.searchTimeout);
        }
        
        if (query.length === 0) {
            this.hideSuggestions();
            this.showSearchHint();
            this.clearResults();
            return;
        }
        
        if (query.length < this.config.minChars) {
            this.showSearchHint();
            this.hideSuggestions();
            return;
        }
        
        this.hideSearchHint();
        
        // Déclencher suggestions et recherche en parallèle
        this.state.searchTimeout = setTimeout(() => {
            this.fetchSuggestions(query);
            this.performFullSearch(query);
        }, this.config.searchDelay);
    },
    
    handleKeyNavigation: function(e) {
        if (!this.state.suggestionsVisible) return;
        
        const suggestions = document.querySelectorAll('.suggestion-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.state.selectedIndex = Math.min(this.state.selectedIndex + 1, suggestions.length - 1);
                this.highlightSuggestion();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.state.selectedIndex = Math.max(this.state.selectedIndex - 1, -1);
                this.highlightSuggestion();
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.state.selectedIndex >= 0 && suggestions[this.state.selectedIndex]) {
                    const code = suggestions[this.state.selectedIndex].dataset.code;
                    this.selectProduct(code);
                } else {
                    this.hideSuggestions();
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
        
        const url = `${this.config.apiEndpoint}?action=suggestions&q=${encodeURIComponent(query)}&limit=8`;
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.suggestions) {
                    this.displaySuggestions(data.suggestions, query);
                } else {
                    this.hideSuggestions();
                }
            })
            .catch(error => {
                console.error('Erreur suggestions:', error.message || 'Erreur serveur');
                this.hideSuggestions();
            });
    },
    
    displaySuggestions: function(suggestions, query) {
        const container = this.elements.suggestionsContainer;
        if (!container) return;
        
        if (!suggestions || suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        const html = suggestions.map((product, index) => {
            const highlightedName = this.highlightMatch(product.nom_produit || 'Produit sans nom', query);
            const highlightedCode = this.highlightMatch(product.code_produit, query);
            
            return `
                <div class="suggestion-item" 
                     data-index="${index}" 
                     data-code="${product.code_produit}"
                     onclick="ADR.Search.selectProduct('${product.code_produit}')">
                    <div class="suggestion-main">
                        <div class="suggestion-code">${highlightedCode}</div>
                        <div class="suggestion-name">${highlightedName}</div>
                    </div>
                    <div class="suggestion-meta">
                        ${product.numero_un ? `<span class="badge badge-un">UN${product.numero_un}</span>` : ''}
                        ${product.classe_adr ? `<span class="badge badge-classe">Cl.${product.classe_adr}</span>` : ''}
                        ${product.danger_environnement === 'OUI' ? '<span class="badge badge-env">ENV</span>' : ''}
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = html;
        this.showSuggestions();
        this.state.selectedIndex = -1;
    },
    
    highlightMatch: function(text, query) {
        if (!text || !query) return text;
        
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    },
    
    showSuggestions: function() {
        if (this.elements.suggestionsContainer) {
            this.elements.suggestionsContainer.style.display = 'block';
            this.state.suggestionsVisible = true;
        }
    },
    
    hideSuggestions: function() {
        if (this.elements.suggestionsContainer) {
            this.elements.suggestionsContainer.style.display = 'none';
            this.state.suggestionsVisible = false;
        }
        this.state.selectedIndex = -1;
    },
    
    highlightSuggestion: function() {
        document.querySelectorAll('.suggestion-item').forEach((item, index) => {
            item.classList.toggle('highlighted', index === this.state.selectedIndex);
        });
    },
    
    selectProduct: function(code) {
        this.elements.searchInput.value = code;
        this.hideSuggestions();
        this.performFullSearch(code);
        
        // Scroll vers le produit dans les résultats
        setTimeout(() => {
            const row = document.querySelector(`[data-product-code="${code}"]`);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('highlight-row');
                setTimeout(() => row.classList.remove('highlight-row'), 2000);
            }
        }, 500);
    },
    
    performFullSearch: function(query) {
        query = query || this.state.lastQuery || this.elements.searchInput.value.trim();
        
        if (!query || query.length < this.config.minChars) {
            this.showSearchHint();
            return;
        }
        
        this.state.isSearching = true;
        this.hideSuggestions();
        this.hideSearchHint();
        this.showLoadingTable();
        
        const filters = this.getFilters();
        const params = new URLSearchParams({
            action: 'search',
            q: query,
            limit: this.config.maxResults,
            ...filters
        });
        
        fetch(`${this.config.apiEndpoint}?${params}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.displayResults(data.products || data.results || [], query);
                } else {
                    this.showError('Erreur de recherche: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur recherche:', error);
                this.showError('Erreur de connexion au serveur');
            })
            .finally(() => {
                this.state.isSearching = false;
            });
    },
    
    getFilters: function() {
        const filters = {};
        
        if (this.elements.categoryFilter?.value) {
            filters.classe = this.elements.categoryFilter.value;
        }
        
        if (this.elements.transportFilter?.value) {
            filters.transport = this.elements.transportFilter.value;
        }
        
        if (this.elements.adrOnlyFilter?.checked) {
            filters.adr_status = 'adr_only';
        }
        
        if (this.elements.envDangerFilter?.checked) {
            filters.env_danger = 'true';
        }
        
        return filters;
    },
    
    displayResults: function(results, query) {
        if (!this.elements.resultsSection) return;
        
        this.state.currentResults = results || [];
        this.state.totalResults = this.state.currentResults.length;
        
        // Afficher section résultats
        this.elements.resultsSection.style.display = 'block';
        
        // Mettre à jour titre
        if (this.elements.resultsTitle) {
            this.elements.resultsTitle.textContent = 
                `${this.state.totalResults} résultat${this.state.totalResults > 1 ? 's' : ''} pour "${query}"`;
        }
        
        // Afficher résultats dans le tableau
        this.populateTable(this.state.currentResults);
        
        // Masquer produits populaires
        if (this.elements.popularSection) {
            this.elements.popularSection.style.display = 'none';
        }
    },
    
    populateTable: function(products) {
        const tbody = document.getElementById('adr-table-body');
        if (!tbody) return;
        
        if (products.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="no-results-cell">
                        <div class="no-results">
                            <h3>Aucun résultat trouvé</h3>
                            <p>Essayez avec d'autres mots-clés ou vérifiez l'orthographe.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = products.map(product => this.renderTableRow(product)).join('');
        
        // Ajouter pagination si nécessaire
        this.setupPagination(products.length);
    },
    
    renderTableRow: function(product) {
        const fdsUrl = `https://www.quickfds.com/fr/search/Guldagil/${encodeURIComponent(product.code_produit)}`;
        
        return `
            <tr data-product-code="${product.code_produit}" class="product-row">
                <td class="col-code">
                    <strong>${product.code_produit}</strong>
                    ${product.corde_article_ferme === 'x' ? '<span class="badge badge-closed">Fermé</span>' : ''}
                </td>
                <td class="col-name">
                    <div class="product-name">${product.nom_produit || 'Nom non disponible'}</div>
                    ${product.nom_technique ? `<div class="product-tech">${product.nom_technique}</div>` : ''}
                    ${product.poids_contenant ? `<small class="product-weight">${product.poids_contenant} - ${product.type_contenant || ''}</small>` : ''}
                </td>
                <td class="col-un">
                    ${product.numero_un ? `<span class="badge badge-un">UN${product.numero_un}</span>` : '-'}
                </td>
                <td class="col-classe">
                    ${product.classe_adr ? `<span class="badge badge-classe">${product.classe_adr}</span>` : '-'}
                </td>
                <td class="col-groupe">
                    ${product.groupe_emballage ? `<span class="badge badge-groupe">${product.groupe_emballage}</span>` : '-'}
                </td>
                <td class="col-cat">
                    ${product.categorie_transport ? `<span class="badge badge-cat">${product.categorie_transport}</span>` : '-'}
                </td>
                <td class="col-env">
                    ${product.danger_environnement === 'OUI' ? '<span class="badge badge-env">OUI</span>' : '-'}
                </td>
                <td class="col-actions">
                    <div class="action-buttons">
                        <a href="${fdsUrl}" target="_blank" class="btn-fds" title="Fiche de Données de Sécurité">
                            📄 FDS
                        </a>
                        ${product.numero_un ? `<button class="btn-detail" onclick="ADR.Search.showDetail('${product.code_produit}')" title="Détail produit">ℹ️</button>` : ''}
                    </div>
                </td>
            </tr>
        `;
    },
    
    setupPagination: function(totalItems) {
        const paginationContainer = document.getElementById('table-pagination');
        if (!paginationContainer || totalItems <= this.config.itemsPerPage) {
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }
        
        const totalPages = Math.ceil(totalItems / this.config.itemsPerPage);
        let paginationHTML = '<div class="pagination-controls">';
        
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === this.config.currentPage ? 'active' : '';
            paginationHTML += `<button class="page-btn ${activeClass}" onclick="ADR.Search.goToPage(${i})">${i}</button>`;
        }
        
        paginationHTML += '</div>';
        paginationContainer.innerHTML = paginationHTML;
    },
    
    goToPage: function(page) {
        this.config.currentPage = page;
        // Implémentation pagination si nécessaire
        console.log('Page:', page);
    },
    
    showLoadingTable: function() {
        const tbody = document.getElementById('adr-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="loading-cell">
                        <div class="loading-results">
                            <div class="spinner"></div>
                            <p>Recherche en cours...</p>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        if (this.elements.resultsSection) {
            this.elements.resultsSection.style.display = 'block';
        }
    },
    
    showError: function(message) {
        const tbody = document.getElementById('adr-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="error-cell">
                        <div class="error-results">
                            <h3>❌ Erreur</h3>
                            <p>${message}</p>
                            <button onclick="location.reload()" class="btn-retry">Réessayer</button>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        if (this.elements.resultsSection) {
            this.elements.resultsSection.style.display = 'block';
        }
    },
    
    showSearchHint: function() {
        if (this.elements.searchHint) {
            this.elements.searchHint.style.display = 'block';
        }
    },
    
    hideSearchHint: function() {
        if (this.elements.searchHint) {
            this.elements.searchHint.style.display = 'none';
        }
    },
    
    clearResults: function() {
        if (this.elements.resultsSection) {
            this.elements.resultsSection.style.display = 'none';
        }
        
        if (this.elements.popularSection) {
            this.elements.popularSection.style.display = 'block';
        }
        
        this.hideSuggestions();
        this.showSearchHint();
        this.state.currentResults = [];
        this.state.totalResults = 0;
        this.state.lastQuery = '';
    },
    
    showDetail: function(codeProduct) {
        // Placeholder pour affichage détail
        alert(`Détail du produit ${codeProduct}\n\nÀ implémenter : modal avec toutes les infos techniques`);
    },
    
    exportResults: function() {
        if (this.state.currentResults.length === 0) {
            alert('Aucun résultat à exporter');
            return;
        }
        
        try {
            const csvContent = this.convertToCSV(this.state.currentResults);
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            link.href = URL.createObjectURL(blob);
            link.download = `adr-recherche-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
            
            URL.revokeObjectURL(link.href);
        } catch (error) {
            console.error('Erreur export:', error);
            alert('Erreur lors de l\'export');
        }
    },
    
    convertToCSV: function(data) {
        const headers = ['Code', 'Nom', 'UN', 'Nom UN', 'Classe', 'Groupe', 'Catégorie', 'Environnement', 'Contenant', 'Poids'];
        const rows = data.map(item => [
            item.code_produit,
            item.nom_produit,
            item.numero_un || '',
            item.nom_description_un || '',
            item.classe_adr || '',
            item.groupe_emballage || '',
            item.categorie_transport || '',
            item.danger_environnement || '',
            item.type_contenant || '',
            item.poids_contenant || ''
        ]);
        
        return [headers, ...rows]
            .map(row => row.map(field => `"${(field || '').toString().replace(/"/g, '""')}"`).join(','))
            .join('\n');
    }
};

// Fonctions globales pour compatibilité
window.performSearch = function(query) {
    if (ADR.Search) {
        ADR.Search.performFullSearch(query);
    }
};

window.clearResults = function() {
    if (ADR.Search) {
        ADR.Search.clearResults();
    }
};

window.exportResults = function() {
    if (ADR.Search) {
        ADR.Search.exportResults();
    }
};

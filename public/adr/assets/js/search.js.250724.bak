/**
 * Titre: Recherche ADR complète avec modal et toutes améliorations
 * Chemin: /public/adr/assets/js/search.js (version finale)
 * Version: 0.5 beta + build auto
 */

// Extension du namespace ADR pour la recherche
if (typeof window.ADR === 'undefined') {
    window.ADR = {};
}

// ========== MODULE RECHERCHE COMPLET ==========
ADR.Search = {
    config: {
        apiEndpoint: '/adr/search/search.php',
        minChars: 2,
        maxResults: 100,
        searchDelay: 150,
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
        suggestionsVisible: false,
        modalOpen: false
    },
    
    elements: {},
    
    init: function() {
        console.log('🔍 Initialisation recherche ADR complète');
        
        if (typeof window.ADR_SEARCH_CONFIG !== 'undefined') {
            Object.assign(this.config, window.ADR_SEARCH_CONFIG);
        }
        
        this.cacheElements();
        this.bindEvents();
        this.setupTable();
        this.createModal();
        
        console.log('✅ Recherche ADR complète prête');
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
        
        // Recherche en temps réel
        this.elements.searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        // Navigation clavier
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
        
        // Filtres
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
        
        // Clic en dehors
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.hideSuggestions();
            }
            if (!e.target.closest('.modal-content') && this.state.modalOpen) {
                this.closeModal();
            }
        });
        
        // Escape pour fermer modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.state.modalOpen) {
                this.closeModal();
            }
        });
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
        
        // Délai pour éviter trop de requêtes
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
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
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
                console.error('Erreur suggestions:', error.message);
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
                        ${this.getClasseBadge(product.classe_adr)}
                        ${product.danger_environnement === 'OUI' ? '<span class="badge badge-env">ENV</span>' : ''}
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = html;
        this.showSuggestions();
        this.state.selectedIndex = -1;
    },
    
    getClasseBadge: function(classe) {
        if (!classe) return '';
        
        const classeColors = {
            '1': '#e91e63', // Rose - Explosifs
            '2': '#ff9800', // Orange - Gaz
            '3': '#f44336', // Rouge - Liquides inflammables
            '4': '#795548', // Marron - Solides inflammables
            '5': '#ff5722', // Rouge-orange - Comburants
            '6': '#4caf50', // Vert - Toxiques
            '7': '#9c27b0', // Violet - Radioactifs
            '8': '#2196f3', // Bleu - Corrosifs
            '9': '#607d8b'  // Gris - Divers
        };
        
        const color = classeColors[classe] || '#666';
        return `<span class="badge badge-classe" style="background: ${color}">Cl.${classe}</span>`;
    },
    
    getAcidBaseInfo: function(nomProduit, nomTechnique) {
        const text = (nomProduit + ' ' + (nomTechnique || '')).toLowerCase();
        
        if (text.includes('acide') || text.includes('acid')) {
            return { type: 'acide', color: '#f44336', icon: '🔴' };
        }
        if (text.includes('base') || text.includes('basique') || text.includes('soude') || text.includes('hydroxyde')) {
            return { type: 'base', color: '#2196f3', icon: '🔵' };
        }
        if (text.includes('neutre') || text.includes('neutral')) {
            return { type: 'neutre', color: '#4caf50', icon: '🟢' };
        }
        
        return null;
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
        
        // Scroll vers le produit
        setTimeout(() => {
            const row = document.querySelector(`[data-product-code="${code}"]`);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('highlight-row');
                setTimeout(() => row.classList.remove('highlight-row'), 3000);
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
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
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
    
    setupTable: function() {
        if (this.elements.resultsContent && !this.elements.resultsContent.querySelector('table')) {
            this.elements.resultsContent.innerHTML = `
                <div class="table-responsive">
                    <table class="adr-results-table" id="adr-table">
                        <thead>
                            <tr>
                                <th class="col-code">Code</th>
                                <th class="col-name">Produit</th>
                                <th class="col-un">UN</th>
                                <th class="col-classe">Classe</th>
                                <th class="col-groupe">Groupe</th>
                                <th class="col-cat">Cat.</th>
                                <th class="col-env">ENV</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adr-table-body"></tbody>
                    </table>
                </div>
                <div class="table-pagination" id="table-pagination"></div>
            `;
        }
    },
    
    displayResults: function(results, query) {
        if (!this.elements.resultsSection) return;
        
        this.state.currentResults = results || [];
        this.state.totalResults = this.state.currentResults.length;
        
        // Afficher section
        this.elements.resultsSection.style.display = 'block';
        
        // Titre
        if (this.elements.resultsTitle) {
            this.elements.resultsTitle.textContent = 
                `${this.state.totalResults} résultat${this.state.totalResults > 1 ? 's' : ''} pour "${query}"`;
        }
        
        // Remplir tableau
        this.populateTable(this.state.currentResults);
        
        // Masquer populaires
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
        
        tbody.innerHTML = products.map((product, index) => this.renderTableRow(product, index)).join('');
        this.setupPagination(products.length);
    },
    
    renderTableRow: function(product, index) {
        const fdsUrl = `https://www.quickfds.com/fr/search/Guldagil/${encodeURIComponent(product.code_produit)}`;
        const acidBase = this.getAcidBaseInfo(product.nom_produit, product.nom_technique);
        const isEvenRow = index % 2 === 0;
        const isClosed = product.corde_article_ferme === 'x';
        
        return `
            <tr data-product-code="${product.code_produit}" 
                class="product-row ${isEvenRow ? 'even' : 'odd'} ${isClosed ? 'closed-product' : ''}"
                onclick="ADR.Search.showModal('${product.code_produit}')">
                <td class="col-code">
                    <div class="code-container">
                        <strong class="${isClosed ? 'closed-code' : ''}">${product.code_produit}</strong>
                        ${isClosed ? '<span class="badge badge-closed">FERMÉ</span>' : ''}
                        ${acidBase ? `<span class="acid-base-indicator" style="color: ${acidBase.color}" title="${acidBase.type}">${acidBase.icon}</span>` : ''}
                    </div>
                </td>
                <td class="col-name">
                    <div class="product-name ${isClosed ? 'closed-text' : ''}">${product.nom_produit || 'Nom non disponible'}</div>
                    ${product.nom_technique ? `<div class="product-tech">${product.nom_technique}</div>` : ''}
                    ${product.poids_contenant ? `<small class="product-weight">${product.poids_contenant}${product.type_contenant ? ' - ' + product.type_contenant : ''}</small>` : ''}
                </td>
                <td class="col-un text-center">
                    ${product.numero_un ? `<span class="badge badge-un">UN${product.numero_un}</span>` : '-'}
                </td>
                <td class="col-classe text-center">
                    ${this.getClasseBadge(product.classe_adr) || '-'}
                </td>
                <td class="col-groupe text-center">
                    ${product.groupe_emballage ? `<span class="badge badge-groupe">${product.groupe_emballage}</span>` : '-'}
                </td>
                <td class="col-cat text-center">
                    ${product.categorie_transport ? `<span class="badge badge-cat">${product.categorie_transport}</span>` : '-'}
                </td>
                <td class="col-env text-center">
                    ${product.danger_environnement === 'OUI' ? '<span class="badge badge-env">OUI</span>' : '-'}
                </td>
                <td class="col-actions" onclick="event.stopPropagation()">
                    <div class="action-buttons">
                        <a href="${fdsUrl}" target="_blank" class="btn-fds" title="Fiche de Données de Sécurité">
                            📄 FDS
                        </a>
                        <button class="btn-detail" onclick="ADR.Search.showModal('${product.code_produit}')" title="Détail produit">
                            ℹ️
                        </button>
                    </div>
                </td>
            </tr>
        `;
    },
    
    setupPagination: function(totalItems) {
        const container = document.getElementById('table-pagination');
        if (!container || totalItems <= this.config.itemsPerPage) {
            if (container) container.innerHTML = '';
            return;
        }
        
        const totalPages = Math.ceil(totalItems / this.config.itemsPerPage);
        const currentPage = this.config.currentPage;
        
        let html = '<div class="pagination-controls">';
        
        // Bouton précédent
        if (currentPage > 1) {
            html += `<button class="page-btn prev" onclick="ADR.Search.goToPage(${currentPage - 1})">‹ Précédent</button>`;
        }
        
        // Numéros de pages
        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            const activeClass = i === currentPage ? 'active' : '';
            html += `<button class="page-btn ${activeClass}" onclick="ADR.Search.goToPage(${i})">${i}</button>`;
        }
        
        // Bouton suivant
        if (currentPage < totalPages) {
            html += `<button class="page-btn next" onclick="ADR.Search.goToPage(${currentPage + 1})">Suivant ›</button>`;
        }
        
        html += '</div>';
        html += `<div class="pagination-info">Page ${currentPage} sur ${totalPages} (${totalItems} résultats)</div>`;
        
        container.innerHTML = html;
    },
    
    goToPage: function(page) {
        this.config.currentPage = page;
        this.populateTable(this.state.currentResults);
    },
    
    createModal: function() {
        const modal = document.createElement('div');
        modal.id = 'product-modal';
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-title">Détail produit</h2>
                    <button class="modal-close" onclick="ADR.Search.closeModal()">&times;</button>
                </div>
                <div class="modal-body" id="modal-body">
                    <div class="loading-modal">
                        <div class="spinner"></div>
                        <p>Chargement...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" onclick="ADR.Search.closeModal()">Fermer</button>
                    <a id="modal-fds-link" href="#" target="_blank" class="btn-primary">📄 Ouvrir FDS</a>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    },
    
    showModal: function(codeProduct) {
        const modal = document.getElementById('product-modal');
        const modalBody = document.getElementById('modal-body');
        const modalTitle = document.getElementById('modal-title');
        const fdsLink = document.getElementById('modal-fds-link');
        
        if (!modal) return;
        
        // Afficher modal
        modal.style.display = 'flex';
        this.state.modalOpen = true;
        
        // Loading
        modalBody.innerHTML = `
            <div class="loading-modal">
                <div class="spinner"></div>
                <p>Chargement des détails...</p>
            </div>
        `;
        
        // Chercher le produit dans les résultats actuels
        const product = this.state.currentResults.find(p => p.code_produit === codeProduct);
        
        if (product) {
            this.displayProductModal(product);
        } else {
            // Requête API pour récupérer le détail
            fetch(`${this.config.apiEndpoint}?action=detail&code=${encodeURIComponent(codeProduct)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.product) {
                        this.displayProductModal(data.product);
                    } else {
                        this.showModalError('Produit introuvable');
                    }
                })
                .catch(error => {
                    console.error('Erreur détail produit:', error);
                    this.showModalError('Erreur de chargement');
                });
        }
    },
    
    displayProductModal: function(product) {
        const modalBody = document.getElementById('modal-body');
        const modalTitle = document.getElementById('modal-title');
        const fdsLink = document.getElementById('modal-fds-link');
        
        modalTitle.textContent = `${product.code_produit} - Détail produit`;
        fdsLink.href = `https://www.quickfds.com/fr/search/Guldagil/${encodeURIComponent(product.code_produit)}`;
        
        const acidBase = this.getAcidBaseInfo(product.nom_produit, product.nom_technique);
        const isClosed = product.corde_article_ferme === 'x';
        
        modalBody.innerHTML = `
            <div class="product-detail">
                <!-- En-tête produit -->
                <div class="detail-header">
                    <div class="detail-title">
                        <h3 class="${isClosed ? 'closed-text' : ''}">${product.nom_produit || 'Nom non disponible'}</h3>
                        <div class="detail-badges">
                            ${isClosed ? '<span class="badge badge-closed">ARTICLE FERMÉ</span>' : ''}
                            ${product.numero_un ? `<span class="badge badge-un">UN${product.numero_un}</span>` : ''}
                            ${this.getClasseBadge(product.classe_adr)}
                            ${product.danger_environnement === 'OUI' ? '<span class="badge badge-env">DANGEREUX ENV</span>' : ''}
                            ${acidBase ? `<span class="badge" style="background: ${acidBase.color}">${acidBase.icon} ${acidBase.type.toUpperCase()}</span>` : ''}
                        </div>
                    </div>
                </div>
                
                <!-- Informations principales -->
                <div class="detail-grid">
                    <div class="detail-section">
                        <h4>📋 Informations générales</h4>
                        <div class="detail-rows">
                            <div class="detail-row">
                                <span class="label">Code produit :</span>
                                <span class="value">${product.code_produit}</span>
                            </div>
                            ${product.nom_technique ? `
                            <div class="detail-row">
                                <span class="label">Nom technique :</span>
                                <span class="value">${product.nom_technique}</span>
                            </div>
                            ` : ''}
                            <div class="detail-row">
                                <span class="label">Statut :</span>
                                <span class="value ${isClosed ? 'closed-text' : ''}">${isClosed ? 'Article fermé' : 'Actif'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>📦 Conditionnement</h4>
                        <div class="detail-rows">
                            ${product.poids_contenant ? `
                            <div class="detail-row">
                                <span class="label">Poids/contenant :</span>
                                <span class="value">${product.poids_contenant}</span>
                            </div>
                            ` : ''}
                            ${product.type_contenant ? `
                            <div class="detail-row">
                                <span class="label">Type contenant :</span>
                                <span class="value">${product.type_contenant}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>⚠️ Classification ADR</h4>
                        <div class="detail-rows">
                            ${product.numero_un ? `
                            <div class="detail-row">
                                <span class="label">Numéro UN :</span>
                                <span class="value"><strong>UN${product.numero_un}</strong></span>
                            </div>
                            ` : ''}
                            ${product.nom_description_un ? `
                            <div class="detail-row">
                                <span class="label">Description UN :</span>
                                <span class="value">${product.nom_description_un}</span>
                            </div>
                            ` : ''}
                            ${product.classe_adr ? `
                            <div class="detail-row">
                                <span class="label">Classe de danger :</span>
                                <span class="value">${this.getClasseBadge(product.classe_adr)} Classe ${product.classe_adr}</span>
                            </div>
                            ` : ''}
                            ${product.groupe_emballage ? `
                            <div class="detail-row">
                                <span class="label">Groupe emballage :</span>
                                <span class="value"><span class="badge badge-groupe">${product.groupe_emballage}</span></span>
                            </div>
                            ` : ''}
                            ${product.numero_etiquette ? `
                            <div class="detail-row">
                                <span class="label">Numéro étiquette :</span>
                                <span class="value">${product.numero_etiquette}</span>
                            </div>
                            ` : ''}
                            ${product.categorie_transport ? `
                            <div class="detail-row">
                                <span class="label">Catégorie transport :</span>
                                <span class="value"><span class="badge badge-cat">${product.categorie_transport}</span></span>
                            </div>
                            ` : ''}
                            ${product.code_tunnel ? `
                            <div class="detail-row">
                                <span class="label">Code tunnel :</span>
                                <span class="value">${product.code_tunnel}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>🌍 Environnement & Sécurité</h4>
                        <div class="detail-rows">
                            <div class="detail-row">
                                <span class="label">Dangereux environnement :</span>
                                <span class="value">${product.danger_environnement === 'OUI' ? '<span class="badge badge-env">OUI</span>' : '<span class="badge badge-safe">NON</span>'}</span>
                            </div>
                            ${acidBase ? `
                            <div class="detail-row">
                                <span class="label">Type chimique :</span>
                                <span class="value"><span class="badge" style="background: ${acidBase.color}">${acidBase.icon} ${acidBase.type.toUpperCase()}</span></span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                
                <!-- Pictogrammes et symboles -->
                <div class="picto-section">
                    <h4>🚨 Pictogrammes de danger</h4>
                    <div class="picto-grid">
                        ${this.generatePictograms(product)}
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="quick-actions">
                    <h4>⚡ Actions rapides</h4>
                    <div class="action-grid">
                        <button class="action-btn" onclick="ADR.Search.copyToClipboard('${product.code_produit}')">
                            📋 Copier code
                        </button>
                        <button class="action-btn" onclick="ADR.Search.searchSimilar('${product.classe_adr || ''}')">
                            🔍 Produits similaires
                        </button>
                        <a href="https://www.quickfds.com/fr/search/Guldagil/${encodeURIComponent(product.code_produit)}" 
                           target="_blank" class="action-btn">
                            📄 Ouvrir FDS
                        </a>
                    </div>
                </div>
            </div>
        `;
    },
    
    generatePictograms: function(product) {
        const pictograms = [];
        
        // Basé sur la classe ADR
        switch (product.classe_adr) {
            case '1':
                pictograms.push({ symbol: '💥', name: 'Explosif', color: '#e91e63' });
                break;
            case '2':
                pictograms.push({ symbol: '🏺', name: 'Gaz', color: '#ff9800' });
                break;
            case '3':
                pictograms.push({ symbol: '🔥', name: 'Inflammable', color: '#f44336' });
                break;
            case '4':
                pictograms.push({ symbol: '🔥', name: 'Solide inflammable', color: '#795548' });
                break;
            case '5':
                pictograms.push({ symbol: '🔥', name: 'Comburant', color: '#ff5722' });
                break;
            case '6':
                pictograms.push({ symbol: '☠️', name: 'Toxique', color: '#4caf50' });
                break;
            case '7':
                pictograms.push({ symbol: '☢️', name: 'Radioactif', color: '#9c27b0' });
                break;
            case '8':
                pictograms.push({ symbol: '🧪', name: 'Corrosif', color: '#2196f3' });
                break;
            case '9':
                pictograms.push({ symbol: '⚠️', name: 'Divers', color: '#607d8b' });
                break;
        }
        
        // Danger environnement
        if (product.danger_environnement === 'OUI') {
            pictograms.push({ symbol: '🌍', name: 'Dangereux environnement', color: '#4caf50' });
        }
        
        // Acide/base
        const acidBase = this.getAcidBaseInfo(product.nom_produit, product.nom_technique);
        if (acidBase) {
            pictograms.push({ symbol: acidBase.icon, name: acidBase.type, color: acidBase.color });
        }
        
        if (pictograms.length === 0) {
            return '<p class="no-picto">Aucun pictogramme spécifique identifié</p>';
        }
        
        return pictograms.map(picto => `
            <div class="picto-item" style="border-color: ${picto.color}">
                <div class="picto-symbol" style="color: ${picto.color}">${picto.symbol}</div>
                <div class="picto-name">${picto.name}</div>
            </div>
        `).join('');
    },
    
    showModalError: function(message) {
        const modalBody = document.getElementById('modal-body');
        modalBody.innerHTML = `
            <div class="modal-error">
                <h3>❌ Erreur</h3>
                <p>${message}</p>
            </div>
        `;
    },
    
    closeModal: function() {
        const modal = document.getElementById('product-modal');
        if (modal) {
            modal.style.display = 'none';
            this.state.modalOpen = false;
        }
    },
    
    copyToClipboard: function(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showToast(`Code ${text} copié !`);
        }).catch(() => {
            // Fallback pour anciens navigateurs
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showToast(`Code ${text} copié !`);
        });
    },
    
    searchSimilar: function(classe) {
        if (!classe) return;
        
        this.closeModal();
        this.elements.searchInput.value = `classe:${classe}`;
        this.performFullSearch(`classe:${classe}`);
    },
    
    showToast: function(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
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
    
    exportResults: function() {
        if (this.state.currentResults.length === 0) {
            this.showToast('Aucun résultat à exporter');
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
            this.showToast('Export CSV réussi !');
        } catch (error) {
            console.error('Erreur export:', error);
            this.showToast('Erreur lors de l\'export');
        }
    },
    
    convertToCSV: function(data) {
        const headers = ['Code', 'Nom', 'UN', 'Nom UN', 'Classe', 'Groupe', 'Catégorie', 'Environnement', 'Contenant', 'Poids', 'Statut'];
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
            item.poids_contenant || '',
            item.corde_article_ferme === 'x' ? 'Fermé' : 'Actif'
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

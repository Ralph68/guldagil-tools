/**
 * Module JavaScript pour la fonctionnalité ADR
 * Version: 1.0
 */

// Configuration globale
window.ADR_SEARCH_CONFIG = {
    apiEndpoint: '/adr/search/search.php',
    minChars: 2,
    maxResults: 100,
    searchDelay: 150
};

// Initialiser avec une valeur vide, sera remplacée par la valeur PHP
window.ADR_CURRENT_QUERY = '';

// Variables pour la pagination
let currentPage = 1;
const itemsPerPage = 20;

/**
 * Fonction de recherche principale
 * @param {string} query - Requête de recherche optionnelle
 */
function performSearch(query) {
    const searchInput = document.getElementById('product-search');
    const searchQuery = query || searchInput.value.trim();
    
    if (searchQuery.length < 2) {
        showMessage('Veuillez saisir au moins 2 caractères', 'warning');
        return;
    }
    
    showLoader();
    
    fetch(`${window.ADR_SEARCH_CONFIG.apiEndpoint}?action=search&q=${encodeURIComponent(searchQuery)}`)
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success && Array.isArray(data.results)) {
                displayResults(data.results);
            } else {
                showMessage('Aucun résultat trouvé', 'info');
                displayResults([]);
            }
        })
        .catch(error => {
            hideLoader();
            showMessage('Erreur de recherche', 'error');
            console.error('Erreur:', error);
        });
}

/**
 * Affichage des résultats dans le tableau et la vue mobile
 * @param {Array} results - Résultats de la recherche
 */
function displayResults(results) {
    const resultsSection = document.getElementById('search-results');
    const resultsContent = document.getElementById('results-content');
    const mobileContent = document.getElementById('search-results-mobile');
    const resultsTitle = document.getElementById('results-title');
    
    if (results.length === 0) {
        resultsSection.style.display = 'none';
        return;
    }
    
    resultsTitle.textContent = `${results.length} résultat(s) trouvé(s)`;
    resultsSection.style.display = 'block';
    
    // Tableau desktop
    resultsContent.innerHTML = results.map(row => `
        <tr class="result-row">
            <td><strong>${row.code_produit || '-'}</strong></td>
            <td>${row.nom_produit || row.nom_technique || '-'}</td>
            <td>${row.numero_un ? `<span class="badge badge-un">UN${row.numero_un}</span>` : '-'}</td>
            <td>${row.categorie_transport ? `<span class="badge badge-cat">${row.categorie_transport}</span>` : '-'}</td>
            <td>${row.groupe_emballage ? `<span class="badge badge-group">${row.groupe_emballage}</span>` : '-'}</td>
            <td>${row.danger_environnement === '1' ? '<span class="badge badge-env">ENV</span>' : '-'}</td>
            <td class="actions-cell">
                <a href="${row.url_fds || '#'}" target="_blank" class="btn-fds" title="Fiche de données de sécurité">
                    📄 FDS
                </a>
            </td>
        </tr>
    `).join('');
    
    // Vue mobile avec tuiles
    mobileContent.innerHTML = results.map(row => `
        <div class="result-tile">
            <div class="tile-header">
                <strong>${row.code_produit || 'Code manquant'}</strong>
                ${row.numero_un ? `<span class="badge badge-un">UN${row.numero_un}</span>` : ''}
            </div>
            <div class="tile-content">
                <p class="product-name">${row.nom_produit || row.nom_technique || 'Nom non disponible'}</p>
                <div class="tile-badges">
                    ${row.categorie_transport ? `<span class="badge badge-cat">Classe ${row.categorie_transport}</span>` : ''}
                    ${row.groupe_emballage ? `<span class="badge badge-group">Groupe ${row.groupe_emballage}</span>` : ''}
                    ${row.danger_environnement === '1' ? '<span class="badge badge-env">ENV</span>' : ''}
                </div>
            </div>
            <div class="tile-actions">
                <a href="${row.url_fds || '#'}" target="_blank" class="btn-fds">📄 FDS</a>
            </div>
        </div>
    `).join('');
}

/**
 * Configuration du système de suggestions
 */
function setupSuggestions() {
    const searchInput = document.getElementById('product-search');
    const suggestionsContainer = document.getElementById('search-suggestions');
    let suggestionsTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(suggestionsTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        suggestionsTimeout = setTimeout(() => {
            fetch(`${window.ADR_SEARCH_CONFIG.apiEndpoint}?action=suggestions&q=${encodeURIComponent(query)}&limit=8`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.suggestions.length > 0) {
                        displaySuggestions(data.suggestions);
                    } else {
                        suggestionsContainer.style.display = 'none';
                    }
                })
                .catch(() => {
                    suggestionsContainer.style.display = 'none';
                });
        }, 300);
    });
    
    // Cacher suggestions au clic extérieur
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
}

/**
 * Affichage des suggestions de recherche
 * @param {Array} suggestions - Liste des suggestions
 */
function displaySuggestions(suggestions) {
    const container = document.getElementById('search-suggestions');
    container.innerHTML = suggestions.map(item => `
        <div class="suggestion-item" onclick="selectSuggestion('${item.code_produit}')">
            <div class="suggestion-code">${item.code_produit}</div>
            <div class="suggestion-name">${item.nom_produit || item.nom_technique || ''}</div>
            ${item.numero_un ? `<div class="suggestion-un">UN${item.numero_un}</div>` : ''}
        </div>
    `).join('');
    container.style.display = 'block';
}

/**
 * Sélection d'une suggestion
 * @param {string} code - Code produit sélectionné
 */
function selectSuggestion(code) {
    document.getElementById('product-search').value = code;
    document.getElementById('search-suggestions').style.display = 'none';
    performSearch(code);
}

/**
 * Chargement des produits populaires
 */
function loadPopularProducts() {
    fetch('/adr/search/search.php?action=popular&limit=6')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('popular-content');
            if (data.success && data.products) {
                container.innerHTML = data.products.map(product => `
                    <div class="popular-item" onclick="performSearch('${product.code_produit}')">
                        <strong>${product.code_produit}</strong>
                        <span class="search-count">${product.search_count || 0} recherches</span>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="no-data">Aucune donnée disponible</p>';
            }
        })
        .catch(() => {
            document.getElementById('popular-content').innerHTML = '<p class="error">Erreur de chargement</p>';
        });
}

/**
 * Chargement des mises à jour récentes
 */
function loadRecentUpdates() {
    fetch('/adr/search/search.php?action=recent&limit=6')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('recent-content');
            if (data.success && data.products) {
                container.innerHTML = data.products.map(product => `
                    <div class="recent-item" onclick="performSearch('${product.code_produit}')">
                        <strong>${product.code_produit}</strong>
                        <span class="update-date">${product.date_maj || 'Date inconnue'}</span>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="no-data">Aucune donnée disponible</p>';
            }
        })
        .catch(() => {
            document.getElementById('recent-content').innerHTML = '<p class="error">Erreur de chargement</p>';
        });
}

/**
 * Affiche un indicateur de chargement
 */
function showLoader() {
    let loader = document.getElementById('adr-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'adr-loader';
        loader.style.position = 'fixed';
        loader.style.top = '0';
        loader.style.left = '0';
        loader.style.width = '100vw';
        loader.style.height = '100vh';
        loader.style.background = 'rgba(255,255,255,0.7)';
        loader.style.display = 'flex';
        loader.style.alignItems = 'center';
        loader.style.justifyContent = 'center';
        loader.style.zIndex = '9999';
        loader.innerHTML = '<div class="loader-spinner" style="font-size:2rem;">⏳ Chargement...</div>';
        document.body.appendChild(loader);
    }
    loader.style.display = 'flex';
}

/**
 * Masque l'indicateur de chargement
 */
function hideLoader() {
    const loader = document.getElementById('adr-loader');
    if (loader) {
        loader.style.display = 'none';
    }
}

/**
 * Affichage de messages utilisateur
 * @param {string} message - Message à afficher
 * @param {string} type - Type de message (info, error, warning)
 */
function showMessage(message, type) {
    // Pour l'instant, simple log console
    console.log(`${type.toUpperCase()}: ${message}`);
    
    // À implémenter : système de notifications toast
}

/**
 * Réinitialisation de la recherche
 */
function clearResults() {
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('product-search').value = '';
    document.getElementById('product-search').focus();
}

/**
 * Export des résultats (à implémenter)
 */
function exportResults() {
    // Fonctionnalité à venir
    alert('Export en cours de développement');
}

/**
 * Initialisation au chargement de la page
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('adr-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
    
    setupSuggestions();
    loadPopularProducts();
    loadRecentUpdates();
    
    // Focus sur la barre de recherche
    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.focus();
    }
    
    // Recherche si query en URL
    if (window.ADR_CURRENT_QUERY && window.ADR_CURRENT_QUERY.length >= 2) {
        setTimeout(() => performSearch(window.ADR_CURRENT_QUERY), 500);
    }
});

// API publique du module ADR
window.ADR = {
    performSearch,
    displayResults,
    setupSuggestions,
    loadPopularProducts,
    loadRecentUpdates,
    clearResults,
    exportResults,
    showMessage
};
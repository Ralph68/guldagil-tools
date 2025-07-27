<?php
/**
 * Titre: Page principale recherche ADR - Version améliorée
 * Chemin: /public/adr/index.php
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit;
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

$page_title = 'Recherche produits ADR';
$page_subtitle = 'Centre de recherche et gestion des marchandises dangereuses';
$page_description = 'Module ADR - Recherche avancée avec suggestions temps réel et accès FDS';
$current_module = 'adr';
$module_css = true;
$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚠️', 'text' => 'Module ADR', 'url' => '/adr/', 'active' => true]
];

$query = $_GET['q'] ?? '';

$header_path = ROOT_PATH . '/templates/header.php';
if (file_exists($header_path)) {
    include $header_path;
}
?>

<main class="adr-main-container">
    <!-- Navigation modules ADR -->
    <nav class="adr-modules-nav">
        <h2>📋 Modules de gestion ADR</h2>
        <div class="modules-grid">
            <a href="/adr/declaration.php" class="module-card declaration">
                <div class="module-icon">➕</div>
                <div class="module-info">
                    <h3>Nouvelle déclaration</h3>
                    <p>Créer une déclaration ADR</p>
                </div>
            </a>
            <a href="/adr/historique.php" class="module-card history">
                <div class="module-icon">📜</div>
                <div class="module-info">
                    <h3>Historique</h3>
                    <p>Consulter les déclarations</p>
                </div>
            </a>
            <a href="/adr/recap.php" class="module-card recap">
                <div class="module-icon">📝</div>
                <div class="module-info">
                    <h3>Récapitulatif</h3>
                    <p>Synthèse journalière</p>
                </div>
            </a>
            <a href="/adr/dashboard.php" class="module-card dashboard">
                <div class="module-icon">📊</div>
                <div class="module-info">
                    <h3>Dashboard</h3>
                    <p>Statistiques & analyses</p>
                </div>
            </a>
        </div>
    </nav>

    <!-- Zone de recherche centrée et élargie -->
    <section class="search-section-main">
        <div class="search-header-centered">
            <h1>🔍 Recherche produits ADR</h1>
            <p>Recherche dynamique avec suggestions temps réel et accès aux FDS</p>
        </div>
        
        <div class="search-container-wide">
            <form id="adr-search-form" class="search-form-main">
                <div class="search-input-wrapper">
                    <input 
                        type="text" 
                        id="product-search" 
                        class="search-input-large"
                        placeholder="Rechercher un produit, code, nom commercial, numéro UN..."
                        value="<?= htmlspecialchars($query) ?>"
                        autocomplete="off"
                    >
                    <button type="button" class="search-btn-main" onclick="performSearch()">
                        <span class="search-icon">🔍</span>
                        <span class="search-text">Rechercher</span>
                    </button>
                </div>
                
                <!-- Dropdown suggestions -->
                <div id="search-suggestions" class="suggestions-dropdown"></div>
            </form>
        </div>
    </section>

    <!-- Résultats de recherche -->
    <section id="search-results" class="results-section" style="display: none;">
        <div class="results-header">
            <h2 id="results-title">Résultats de recherche</h2>
            <div class="results-actions">
                <button class="btn-secondary" onclick="exportResults()">📊 Exporter</button>
                <button class="btn-secondary" onclick="clearResults()">🗑️ Effacer</button>
            </div>
        </div>
        
        <!-- Tableau desktop -->
        <div class="results-table-container desktop-only">
            <table id="results-table" class="results-table">
                <thead>
                    <tr>
                        <th>Code produit</th>
                        <th>Nom produit</th>
                        <th>UN</th>
                        <th>Classe</th>
                        <th>Groupe</th>
                        <th>Env.</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="results-content"></tbody>
            </table>
        </div>
        
        <!-- Vue mobile avec tuiles -->
        <div class="results-mobile-container mobile-only" id="search-results-mobile">
        </div>
        
        <!-- Pagination -->
        <div class="pagination-container" id="pagination-container" style="display: none;">
            <button id="prev-page" class="pagination-btn">« Précédent</button>
            <span id="page-info" class="page-info">Page 1 sur 1</span>
            <button id="next-page" class="pagination-btn">Suivant »</button>
        </div>
    </section>

    <!-- Top recherchés et derniers mis à jour -->
    <div class="stats-grid">
        <!-- Produits populaires -->
        <section class="popular-section">
            <h2>🔥 Top des recherchés</h2>
            <div id="popular-content" class="popular-content">
                <div class="loading">Chargement...</div>
            </div>
        </section>

        <!-- Derniers mis à jour -->
        <section class="recent-section">
            <h2>🆕 Derniers mis à jour</h2>
            <div id="recent-content" class="recent-content">
                <div class="loading">Chargement...</div>
            </div>
        </section>
    </div>

    <!-- Légende des badges -->
    <section class="badges-legend">
        <h3>🏷️ Légende des codes</h3>
        <div class="legend-grid">
            <div class="legend-item">
                <span class="badge badge-un">UN1234</span> Numéro UN
            </div>
            <div class="legend-item">
                <span class="badge badge-cat">3</span> Classe de transport
            </div>
            <div class="legend-item">
                <span class="badge badge-group">II</span> Groupe d'emballage
            </div>
            <div class="legend-item">
                <span class="badge badge-env">ENV</span> Dangereux environnement
            </div>
            <div class="legend-item">
                <span class="badge badge-closed">Fermé</span> Article fermé
            </div>
        </div>
    </section>
</main>

<!-- Configuration JavaScript -->
<script>
window.ADR_SEARCH_CONFIG = {
    apiEndpoint: '/adr/search/search.php',
    minChars: 2,
    maxResults: 100,
    searchDelay: 150
};
window.ADR_CURRENT_QUERY = <?= json_encode($query) ?>;

let currentPage = 1;
const itemsPerPage = 20;

// Fonction de recherche principale
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

// Affichage des résultats
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

// Suggestions dropdown
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

function selectSuggestion(code) {
    document.getElementById('product-search').value = code;
    document.getElementById('search-suggestions').style.display = 'none';
    performSearch(code);
}

// Charger données initiales
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

// Utilitaires
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

function hideLoader() {
    const loader = document.getElementById('adr-loader');
    if (loader) {
        loader.style.display = 'none';
    }
}

function showMessage(message, type) {
    // TODO: Système de notifications toast
    console.log(`${type.toUpperCase()}: ${message}`);
}

function clearResults() {
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('product-search').value = '';
    document.getElementById('product-search').focus();
}

function exportResults() {
    // TODO: Export CSV/Excel des résultats
    alert('Export en cours de développement');
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    setupSuggestions();
    loadPopularProducts();
    loadRecentUpdates();
    
    // Focus sur la barre de recherche
    document.getElementById('product-search').focus();
    
    // Recherche si query en URL
    if (window.ADR_CURRENT_QUERY && window.ADR_CURRENT_QUERY.length >= 2) {
        setTimeout(() => performSearch(window.ADR_CURRENT_QUERY), 500);
    }
});

// Gestion du formulaire
document.getElementById('adr-search-form').addEventListener('submit', function(e) {
    e.preventDefault();
    performSearch();
});

// Remplacer `export` par une déclaration globale
window.ADR = {
    performSearch,
    displayResults,
    setupSuggestions,
    loadPopularProducts,
    loadRecentUpdates,
    // ...autres fonctions exportées...
};
</script>

<?php
$footer_path = ROOT_PATH . '/templates/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
}
?>
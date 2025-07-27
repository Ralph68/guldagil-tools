<?php
// Affichage des erreurs pour debug (à désactiver en prod)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Titre: Page de recherche ADR optimisée - Version finale
 * Chemin: /public/adr/index.php
 * Version: 0.5 beta + build auto
 */

// Définition du chemin racine si besoin
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Démarrage session si pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification authentification portail
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit;
}

// Chargement de la configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Variables pour templates
$page_title = 'Recherche produits ADR';
$page_subtitle = 'Recherche avancée avec suggestions et liens FDS';
$page_description = 'Module ADR - Recherche dynamique de produits et marchandises dangereuses avec accès aux fiches de données de sécurité';
$current_module = 'adr';
$module_css = true;
$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
$user_authenticated = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚠️', 'text' => 'Module ADR', 'url' => '/adr/', 'active' => false],
    ['icon' => '🔍', 'text' => 'Recherche', 'url' => '/adr/', 'active' => true]
];

// Paramètres de recherche
$query = $_GET['q'] ?? '';

// Inclusion du header (ne pas dupliquer le HTML)
$header_path = ROOT_PATH . '/templates/header.php';
if (file_exists($header_path)) {
    include $header_path;
}
?>

<main class="adr-container search-page">
    <!-- Liens d'accès rapide ADR -->
    <nav class="adr-nav-actions">
        <a href="/adr/declaration.php" class="adr-action-btn">➕ Nouvelle déclaration ADR</a>
        <a href="/adr/historique.php" class="adr-action-btn">📜 Historique des déclarations</a>
        <a href="/adr/recap.php" class="adr-action-btn">📝 Récapitulatif journalier</a>
        <a href="/adr/dashboard.php" class="adr-action-btn">📊 Dashboard & Statistiques</a>
    </nav>

    <!-- Section recherche centrale -->
    <section class="search-header">
        <div class="search-intro">
            <h1>🔍 Recherche produits ADR</h1>
            <p>Recherche dynamique avec suggestions temps réel et accès aux fiches FDS</p>
        </div>
        <div class="main-search-centered">
            <form id="adr-search-form" autocomplete="off" onsubmit="performSearch(); return false;">
                <div class="search-input-container">
                    <input 
                        type="text" 
                        id="product-search" 
                        class="search-input"
                        placeholder="Tapez un code produit, nom, ou numéro UN... (min. 2 caractères)"
                        value="<?= htmlspecialchars($query) ?>"
                        autocomplete="off"
                        spellcheck="false"
                        required
                    >
                    <button class="search-btn" type="submit">
                        🔍 Rechercher
                    </button>
                </div>
                <!-- Suggestions dynamiques -->
                <div id="search-suggestions" class="search-suggestions"></div>
            </form>
            <div id="search-hint" class="search-hint">
                💡 Saisissez au moins 2 caractères pour voir les suggestions et lancer la recherche
            </div>
        </div>
        <div class="search-stats">
            <span class="stat-item">📊 <strong id="total-products">-</strong> produits</span>
            <span class="stat-item">⚠️ <strong id="adr-products">-</strong> ADR</span>
            <span class="stat-item">🌍 <strong id="env-products">-</strong> ENV</span>
        </div>
    </section>

    <!-- Résultats de recherche -->
    <section id="search-results" class="results-section" style="display: none;">
        <div class="results-header">
            <h2 id="results-title">Résultats de recherche</h2>
            <div class="results-actions">
                <button class="btn-export" onclick="exportResults()">
                    📊 Exporter CSV
                </button>
                <button class="btn-clear" onclick="clearResults()">
                    🗑️ Nouvelle recherche
                </button>
            </div>
        </div>
        <!-- Tableau responsive, sans ascenseur horizontal -->
        <div id="results-content" class="results-content">
            <div class="adr-results-table-responsive">
                <table class="adr-results-table" id="adr-table">
                    <thead>
                        <tr>
                            <th>Code produit</th>
                            <th>Nom / Description</th>
                            <th>UN</th>
                            <th>Classe</th>
                            <th>Groupe</th>
                            <th>Cat.</th>
                            <th>ENV</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adr-table-body">
                        <!-- Résultats injectés dynamiquement -->
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" id="table-pagination">
                <!-- Pagination JS -->
            </div>
        </div>
    </section>

    <!-- Section mobile-friendly : affichage en blocs si petit écran -->
    <section id="search-results-mobile" class="results-section-mobile" style="display: none;">
        <div id="results-mobile-content" class="results-mobile-content">
            <!-- Résultats injectés dynamiquement en JS -->
        </div>
        <div class="table-pagination" id="table-pagination-mobile"></div>
    </section>

    <!-- TODO: Ajouter les filtres avancés si besoin (masqués par défaut sur mobile) -->

    <!-- Raccourcis et produits populaires -->
    <section id="popular-products" class="popular-section">
        <div class="section-header">
            <h2>🔥 Raccourcis de recherche</h2>
            <p>Produits fréquemment recherchés et raccourcis utiles</p>
        </div>
        <div class="shortcuts-grid">
            <div class="shortcut-category">
                <h3>⚡ Recherches rapides</h3>
                <div class="shortcut-buttons">
                    <button class="shortcut-btn" onclick="quickSearch('UN')">Tous les UN</button>
                    <button class="shortcut-btn" onclick="quickSearch('classe:8')">Classe 8 (Corrosifs)</button>
                    <button class="shortcut-btn" onclick="quickSearch('env:oui')">Dangereux ENV</button>
                    <button class="shortcut-btn" onclick="quickSearch('cat:1')">Catégorie 1</button>
                </div>
            </div>
            <div class="shortcut-category">
                <h3>📈 Produits populaires</h3>
                <div id="popular-content" class="popular-content">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Chargement des produits populaires...</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Aide et informations -->
    <section class="help-section">
        <div class="help-content">
            <h3>💡 Guide de recherche</h3>
            <div class="help-grid">
                <div class="help-item">
                    <h4>🔤 Codes produits</h4>
                    <p>Recherchez par code complet (SOL11) ou partiel (SOL). Les suggestions apparaissent dès 2 caractères.</p>
                    <small>Exemple : SOL, DETARTRANT, 1001KN</small>
                </div>
                <div class="help-item">
                    <h4>🚛 Numéros UN</h4>
                    <p>Tapez le numéro UN avec ou sans préfixe "UN".</p>
                    <small>Exemple : 1824, UN1824, 3412</small>
                </div>
                <div class="help-item">
                    <h4>📋 Noms de produits</h4>
                    <p>Recherche dans les noms commerciaux et descriptions techniques.</p>
                    <small>Exemple : "acide", "hypochlorite", "détartrant"</small>
                </div>
                <div class="help-item">
                    <h4>📄 Fiches FDS</h4>
                    <p>Cliquez sur le bouton "FDS" pour accéder à la fiche de données de sécurité.</p>
                    <small>Redirection vers QuickFDS avec le code produit</small>
                </div>
            </div>
            <div class="help-legend">
                <h4>🏷️ Légende des badges</h4>
                <div class="legend-items">
                    <span class="badge badge-un">UN1824</span> Numéro UN officiel
                    <span class="badge badge-classe">8</span> Classe de danger ADR
                    <span class="badge badge-groupe">II</span> Groupe d'emballage
                    <span class="badge badge-cat">2</span> Catégorie de transport
                    <span class="badge badge-env">ENV</span> Dangereux pour l'environnement
                    <span class="badge badge-closed">Fermé</span> Article fermé au catalogue
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Configuration JavaScript -->
<script>
// Configuration pour la recherche ADR optimisée
window.ADR_SEARCH_CONFIG = {
    apiEndpoint: '/adr/search/search.php',
    minChars: 2,
    maxResults: 100,
    searchDelay: 200
};
window.ADR_CURRENT_QUERY = <?= json_encode($query) ?>;

// Focus automatique sur la barre de recherche à l'affichage
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.focus();
        searchInput.select();
    }
});

// TODO: Déplacer la logique de suggestion dans le JS du module si besoin
// TODO: Gérer l'affichage mobile (affichage en blocs verticaux, voir CSS)
// TODO: Utiliser les vraies données de la BDD côté /adr/search/search.php

// Fonctions utilitaires
function quickSearch(query) {
    const searchInput = document.getElementById('product-search');
    if (searchInput && window.ADR && ADR.Search) {
        searchInput.value = query;
        ADR.Search.performFullSearch(query);
        searchInput.focus();
        searchInput.select();
    }
}
</script>

<!-- JavaScript du module recherche -->
<script src="/adr/assets/js/adr.js?v=<?= $build_number ?>"></script>
<script src="/adr/assets/js/search.js?v=<?= $build_number ?>"></script>

<script>
// Initialisation complète
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le module recherche
    if (typeof ADR !== 'undefined' && ADR.Search) {
        ADR.Search.init();

        // Charger données initiales
        if (typeof loadPopularProducts === 'function') loadPopularProducts();
        if (typeof loadStats === 'function') loadStats();

        // Lancer recherche si query dans URL
        if (window.ADR_CURRENT_QUERY && window.ADR_CURRENT_QUERY.length >= 2) {
            setTimeout(function() {
                ADR.Search.performFullSearch(window.ADR_CURRENT_QUERY);
            }, 500);
        }
    }
});

// Fonctions globales pour compatibilité
function performSearch(query) {
    if (ADR.Search) {
        ADR.Search.performFullSearch(query);
    }
}
function clearResults() {
    if (ADR.Search) {
        ADR.Search.clearResults();
    }
}
function exportResults() {
    if (ADR.Search) {
        ADR.Search.exportResults();
    }
}
</script>

<?php
// Inclusion du footer (ne pas dupliquer le HTML)
$footer_path = ROOT_PATH . '/templates/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
}
?>
<script>
// Configuration pour la recherche ADR optimisée
window.ADR_SEARCH_CONFIG = {
    apiEndpoint: '/adr/search/search.php',
    minChars: 2,
    maxResults: 100,
    searchDelay: 200
};

// Variables globales
window.ADR_CURRENT_QUERY = <?= json_encode($query) ?>;

// État des filtres
let filtersVisible = false;

// Fonctions utilitaires
function toggleFilters() {
    const content = document.getElementById('filters-content');
    const toggleText = document.getElementById('filter-toggle-text');
    
    filtersVisible = !filtersVisible;
    content.style.display = filtersVisible ? 'block' : 'none';
    toggleText.textContent = filtersVisible ? 'Masquer' : 'Afficher';
}

function clearFilters() {
    document.getElementById('category-filter').value = '';
    document.getElementById('transport-filter').value = '';
    document.getElementById('adr-only').checked = false;
    document.getElementById('env-danger').checked = false;
    
    // Relancer la recherche si une requête est active
    if (ADR.Search && ADR.Search.state.lastQuery.length >= 2) {
        ADR.Search.performFullSearch();
    }
}

function quickSearch(query) {
    const searchInput = document.getElementById('product-search');
    if (searchInput && ADR.Search) {
        searchInput.value = query;
        ADR.Search.performFullSearch(query);
    }
}

function loadPopularProducts() {
    fetch('/adr/search/search.php?action=popular&limit=8')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                displayPopularProducts(data.products);
            } else {
                document.getElementById('popular-content').innerHTML = 
                    '<p class="error">Erreur chargement produits populaires</p>';
            }
        })
        .catch(error => {
            console.error('Erreur produits populaires:', error);
            document.getElementById('popular-content').innerHTML = 
                '<p class="error">Erreur de connexion</p>';
        });
}

function displayPopularProducts(products) {
    const container = document.getElementById('popular-content');
    if (!products.length) {
        container.innerHTML = '<p>Aucun produit populaire disponible</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="popular-grid">
            ${products.map(product => `
                <div class="popular-item" onclick="quickSearch('${product.code_produit}')">
                    <div class="popular-header">
                        <strong>${product.code_produit}</strong>
                        ${product.numero_un ? `<span class="badge badge-un">UN${product.numero_un}</span>` : ''}
                    </div>
                    <p class="popular-name">${product.nom_produit || 'Nom non disponible'}</p>
                    <div class="popular-meta">
                        ${product.classe_adr ? `<span class="badge badge-classe">${product.classe_adr}</span>` : ''}
                        ${product.danger_environnement === 'OUI' ? '<span class="badge badge-env">ENV</span>' : ''}
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function loadStats() {
    fetch('/adr/search/search.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.stats) {
                document.getElementById('total-products').textContent = data.stats.total || '0';
                document.getElementById('adr-products').textContent = data.stats.adr || '0';
                document.getElementById('env-products').textContent = data.stats.env || '0';
            }
        })
        .catch(error => {
            console.error('Erreur statistiques:', error);
        });
}
</script>

<script>
// --- Suggestions dynamiques sous la barre de recherche ---
let suggestionSelected = -1;
let suggestions = [];

function showSuggestions(list) {
    const container = document.getElementById('search-suggestions');
    if (!container) return;
    container.innerHTML = '';
    if (!list.length) {
        container.classList.remove('active');
        return;
    }
    suggestions = list;
    suggestionSelected = -1;
    list.forEach((item, idx) => {
        const div = document.createElement('div');
        div.className = 'search-suggestion-item';
        div.textContent = item.label;
        div.tabIndex = 0;
        div.onclick = () => selectSuggestion(idx);
        div.onkeydown = (e) => {
            if (e.key === 'Enter') selectSuggestion(idx);
        };
        container.appendChild(div);
    });
    container.classList.add('active');
}

function hideSuggestions() {
    const container = document.getElementById('search-suggestions');
    if (container) {
        container.classList.remove('active');
        container.innerHTML = '';
    }
}

function selectSuggestion(idx) {
    if (suggestions[idx]) {
        document.getElementById('product-search').value = suggestions[idx].label;
        hideSuggestions();
        performSearch();
    }
}

// Gestion clavier pour suggestions
document.getElementById('product-search').addEventListener('keydown', function(e) {
    const container = document.getElementById('search-suggestions');
    const items = container ? container.querySelectorAll('.search-suggestion-item') : [];
    if (!items.length) return;
    if (e.key === 'ArrowDown') {
        suggestionSelected = (suggestionSelected + 1) % items.length;
        items.forEach((el, i) => el.classList.toggle('selected', i === suggestionSelected));
        items[suggestionSelected].focus();
        e.preventDefault();
    } else if (e.key === 'ArrowUp') {
        suggestionSelected = (suggestionSelected - 1 + items.length) % items.length;
        items.forEach((el, i) => el.classList.toggle('selected', i === suggestionSelected));
        items[suggestionSelected].focus();
        e.preventDefault();
    } else if (e.key === 'Escape') {
        hideSuggestions();
    }
});

// Appel AJAX pour suggestions
document.getElementById('product-search').addEventListener('input', function(e) {
    const val = this.value.trim();
    if (val.length < 2) {
        hideSuggestions();
        return;
    }
    fetch(window.ADR_SEARCH_CONFIG.apiEndpoint + '?action=suggest&q=' + encodeURIComponent(val))
        .then(r => r.json())
        .then(data => {
            if (data.success && Array.isArray(data.suggestions)) {
                showSuggestions(data.suggestions);
            } else {
                hideSuggestions();
            }
        })
        .catch(() => hideSuggestions());
});

// --- Injection des résultats (tableau ou blocs mobile) ---
function renderResults(results) {
    // Desktop/tableau
    const tableBody = document.getElementById('adr-table-body');
    // Mobile/blocs
    const mobileContent = document.getElementById('results-mobile-content');
    if (!Array.isArray(results)) return;

    // Table desktop
    tableBody.innerHTML = '';
    results.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${row.code_produit || ''}</td>
            <td>${row.nom_produit || ''}</td>
            <td>${row.numero_un ? 'UN' + row.numero_un : ''}</td>
            <td>${row.classe_adr || ''}</td>
            <td>${row.groupe_emballage || ''}</td>
            <td>${row.categorie_transport || ''}</td>
            <td>${row.danger_environnement === 'OUI' ? '<span class="badge badge-env">ENV</span>' : ''}</td>
            <td>
                <a href="${row.url_fds || '#'}" target="_blank" class="btn-fds">FDS</a>
            </td>
        `;
        tableBody.appendChild(tr);
    });

    // Mobile blocs
    mobileContent.innerHTML = '';
    results.forEach(row => {
        const block = document.createElement('div');
        block.className = 'adr-result-mobile-block';
        block.innerHTML = `
            <div class="block-row"><span class="block-label">Code :</span> <span class="block-value">${row.code_produit || ''}</span></div>
            <div class="block-row"><span class="block-label">Nom :</span> <span class="block-value">${row.nom_produit || ''}</span></div>
            <div class="block-row"><span class="block-label">UN :</span> <span class="block-value">${row.numero_un ? 'UN' + row.numero_un : ''}</span></div>
            <div class="block-row"><span class="block-label">Classe :</span> <span class="block-value">${row.classe_adr || ''}</span></div>
            <div class="block-row"><span class="block-label">Groupe :</span> <span class="block-value">${row.groupe_emballage || ''}</span></div>
            <div class="block-row"><span class="block-label">Cat. :</span> <span class="block-value">${row.categorie_transport || ''}</span></div>
            <div class="block-row"><span class="block-label">ENV :</span> <span class="block-value">${row.danger_environnement === 'OUI' ? 'Oui' : 'Non'}</span></div>
            <div class="block-actions">
                <a href="${row.url_fds || '#'}" target="_blank" class="btn-fds">FDS</a>
            </div>
        `;
        mobileContent.appendChild(block);
    });

    // Affichage des sections
    document.getElementById('search-results').style.display = results.length ? '' : 'none';
    document.getElementById('search-results-mobile').style.display = results.length ? '' : 'none';
}

// Surcharge la fonction ADR.Search.performFullSearch pour afficher les résultats correctement
if (window.ADR && ADR.Search) {
    ADR.Search._originalPerformFullSearch = ADR.Search.performFullSearch;
    ADR.Search.performFullSearch = function(query) {
        // TODO: Pagination, gestion erreurs, loader
        const val = query || document.getElementById('product-search').value.trim();
        if (val.length < 2) return;
        fetch(window.ADR_SEARCH_CONFIG.apiEndpoint + '?action=search&q=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                if (data.success && Array.isArray(data.results)) {
                    renderResults(data.results);
                } else {
                    renderResults([]);
                    // TODO: Afficher un message "Aucun résultat"
                }
            })
            .catch(() => {
                renderResults([]);
                // TODO: Afficher un message d'erreur réseau
            });
    };
}

// TODO: Vérifier que /adr/search/search.php retourne bien les données réelles (pas de démo)
// TODO: Ajouter gestion accessibilité (focus, navigation clavier, aria-live)
// TODO: Ajouter messages d'erreur ou de chargement si besoin
</script>

<!-- JavaScript du module recherche -->
<script src="/adr/assets/js/adr.js?v=<?= $build_number ?>"></script>
<script src="/adr/assets/js/search.js?v=<?= $build_number ?>"></script>

<script>
// Initialisation complète
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Initialisation page recherche ADR optimisée');
    
    // Initialiser le module recherche
    if (typeof ADR !== 'undefined' && ADR.Search) {
        ADR.Search.init();
        
        // Charger données initiales
        loadPopularProducts();
        loadStats();
        
        // Lancer recherche si query dans URL
        if (window.ADR_CURRENT_QUERY && window.ADR_CURRENT_QUERY.length >= 2) {
            setTimeout(function() {
                ADR.Search.performFullSearch(window.ADR_CURRENT_QUERY);
            }, 500);
        }
        
        console.log('✅ Recherche ADR optimisée initialisée');
    } else {
        console.error('❌ Module ADR.Search non disponible');
    }
});

// Fonctions globales pour compatibilité
function performSearch(query) {
    if (ADR.Search) {
        ADR.Search.performFullSearch(query);
    }
}

function clearResults() {
    if (ADR.Search) {
        ADR.Search.clearResults();
    }
}

function exportResults() {
    if (ADR.Search) {
        ADR.Search.exportResults();
    }
}
</script>

<?php
// Inclure footer
$footer_path = ROOT_PATH . '/templates/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    echo '</body></html>';
}
?>

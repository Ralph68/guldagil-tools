<?php
// Affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Titre: Page de recherche ADR optimisée - Version finale
 * Chemin: /public/adr/search/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
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

// Configuration
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
    ['icon' => '🔍', 'text' => 'Recherche', 'url' => '/adr/search/', 'active' => true]
];

$nav_info = 'Recherche de produits ADR avec suggestions et FDS';

// Paramètres de recherche
$query = $_GET['q'] ?? '';

// Inclure header
$header_path = ROOT_PATH . '/templates/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    echo '<!DOCTYPE html><html><head>';
    echo '<title>Recherche ADR</title>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '</head><body>';
}
?>

<main class="adr-container search-page">
    
    <!-- Header de recherche -->
    <section class="search-header">
        <div class="search-intro">
            <h1>🔍 Recherche produits ADR</h1>
            <p>Recherche dynamique avec suggestions temps réel et accès aux fiches FDS</p>
            <div class="search-stats">
                <span class="stat-item">📊 <strong id="total-products">-</strong> produits</span>
                <span class="stat-item">⚠️ <strong id="adr-products">-</strong> ADR</span>
                <span class="stat-item">🌍 <strong id="env-products">-</strong> ENV</span>
            </div>
        </div>
    </section>

    <!-- Zone de recherche principale -->
    <section class="search-section">
        <div class="search-container">
            <!-- Barre de recherche avec suggestions -->
            <div class="main-search">
                <div class="search-input-container">
                    <input 
                        type="text" 
                        id="product-search" 
                        class="search-input" 
                        placeholder="Tapez un code produit, nom, ou numéro UN... (min. 2 caractères)"
                        value="<?= htmlspecialchars($query) ?>"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <button class="search-btn" type="button" onclick="performSearch()">
                        🔍 Rechercher
                    </button>
                </div>
                
                <!-- Conteneur des suggestions -->
                <div id="search-suggestions" class="search-suggestions" style="display: none;"></div>
            </div>
            
            <!-- Message d'aide -->
            <div id="search-hint" class="search-hint">
                💡 Saisissez au moins 2 caractères pour voir les suggestions et lancer la recherche
            </div>

            <!-- Filtres avancés -->
            <div class="advanced-filters" id="advanced-filters">
                <div class="filters-header">
                    <h3>🎛️ Filtres avancés</h3>
                    <button class="btn-toggle-filters" onclick="toggleFilters()">
                        <span id="filter-toggle-text">Afficher</span>
                    </button>
                </div>
                
                <div class="filters-content" id="filters-content" style="display: none;">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="category-filter">Catégorie transport :</label>
                            <select id="category-filter" class="filter-select">
                                <option value="">Toutes les catégories</option>
                                <option value="0">Catégorie 0 - Pas de restrictions</option>
                                <option value="1">Catégorie 1 - Très dangereux</option>
                                <option value="2">Catégorie 2 - Dangereux</option>
                                <option value="3">Catégorie 3 - Moyennement dangereux</option>
                                <option value="4">Catégorie 4 - Peu dangereux</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="transport-filter">Type transport :</label>
                            <select id="transport-filter" class="filter-select">
                                <option value="">Tous les types</option>
                                <option value="route">Route</option>
                                <option value="mer">Mer</option>
                                <option value="air">Air</option>
                                <option value="rail">Rail</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filters-row">
                        <div class="filter-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="adr-only">
                                <span>🚚 Produits ADR uniquement</span>
                            </label>
                        </div>
                        
                        <div class="filter-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="env-danger">
                                <span>🌍 Dangereux pour l'environnement</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="filters-actions">
                        <button class="btn-clear-filters" onclick="clearFilters()">
                            🗑️ Effacer filtres
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Résultats de recherche en tableau -->
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
        
        <!-- Tableau des résultats -->
        <div id="results-content" class="results-content">
            <div class="table-responsive">
                <table class="adr-results-table" id="adr-table">
                    <thead>
                        <tr>
                            <th class="col-code">Code produit</th>
                            <th class="col-name">Nom et description</th>
                            <th class="col-un">UN</th>
                            <th class="col-classe">Classe</th>
                            <th class="col-groupe">Groupe</th>
                            <th class="col-cat">Cat.</th>
                            <th class="col-env">ENV</th>
                            <th class="col-actions">Actions</th>
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
        </div>
    </section>

    <!-- Raccourcis et produits populaires -->
    <section id="popular-products" class="popular-section">
        <div class="section-header">
            <h2>🔥 Raccourcis de recherche</h2>
            <p>Produits fréquemment recherchés et raccourcis utiles</p>
        </div>
        
        <div class="shortcuts-grid">
            <!-- Recherches rapides -->
            <div class="shortcut-category">
                <h3>⚡ Recherches rapides</h3>
                <div class="shortcut-buttons">
                    <button class="shortcut-btn" onclick="quickSearch('UN')">
                        Tous les UN
                    </button>
                    <button class="shortcut-btn" onclick="quickSearch('classe:8')">
                        Classe 8 (Corrosifs)
                    </button>
                    <button class="shortcut-btn" onclick="quickSearch('env:oui')">
                        Dangereux ENV
                    </button>
                    <button class="shortcut-btn" onclick="quickSearch('cat:1')">
                        Catégorie 1
                    </button>
                </div>
            </div>
            
            <!-- Produits populaires -->
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

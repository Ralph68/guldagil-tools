<?php
/**
 * Titre: Page de recherche ADR - CORRIGÉE
 * Chemin: /public/adr/search/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
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
$page_subtitle = 'Rechercher dans la base de données des produits ADR';
$page_description = 'Module ADR - Recherche avancée de produits et marchandises dangereuses';
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

$nav_info = 'Recherche de produits ADR';

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
            <p>Recherchez dans la base de données des produits et marchandises dangereuses</p>
        </div>
    </section>

    <!-- Zone de recherche -->
    <section class="search-section advanced">
        <div class="search-container">
            <!-- Barre de recherche principale -->
            <div class="main-search">
                <input 
                    type="text" 
                    id="product-search" 
                    class="search-input" 
                    placeholder="Rechercher un produit (code, nom, numéro UN...)"
                    value="<?= htmlspecialchars($query) ?>"
                    autocomplete="off"
                >
                <button class="search-btn" type="button" onclick="performSearch()">
                    🔍 Rechercher
                </button>
            </div>

            <!-- Suggestions -->
            <div id="search-suggestions" class="search-suggestions" style="display: none;"></div>
            
            <!-- Message d'aide pour 3 caractères -->
            <div id="search-hint" class="search-hint">
                💡 Saisissez au moins 3 caractères pour lancer la recherche
            </div>

            <!-- Filtres avancés -->
            <div class="advanced-filters" id="advanced-filters">
                <div class="filters-row">
                    <div class="filter-group">
                        <label for="category-filter">Catégorie transport :</label>
                        <select id="category-filter" class="filter-select">
                            <option value="">Toutes les catégories</option>
                            <option value="0">Catégorie 0</option>
                            <option value="1">Catégorie 1</option>
                            <option value="2">Catégorie 2</option>
                            <option value="3">Catégorie 3</option>
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
                            <span>Produits ADR uniquement</span>
                        </label>
                    </div>
                    
                    <div class="filter-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="env-danger">
                            <span>Dangereux pour l'environnement</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Résultats de recherche -->
    <section id="search-results" class="results-section" style="display: none;">
        <div class="results-header">
            <h2 id="results-title">Résultats de recherche</h2>
            <div class="results-actions">
                <button class="btn-export" onclick="exportResults()">📊 Exporter</button>
                <button class="btn-clear" onclick="clearResults()">🗑️ Effacer</button>
            </div>
        </div>
        <div id="results-content" class="results-content">
            <!-- Les résultats seront injectés ici -->
        </div>
        <div id="results-pagination" class="pagination">
            <!-- Pagination sera générée ici -->
        </div>
    </section>

    <!-- Produits populaires -->
    <section id="popular-products" class="popular-section">
        <div class="section-header">
            <h2>🔥 Produits populaires</h2>
            <p>Les produits les plus recherchés</p>
        </div>
        <div id="popular-content" class="popular-content">
            <!-- Chargement automatique des produits populaires -->
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Chargement des produits populaires...</p>
            </div>
        </div>
    </section>

    <!-- Statistiques rapides -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <h3 id="stat-total">-</h3>
                    <p>Produits total</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <h3 id="stat-adr">-</h3>
                    <p>Produits ADR</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🌍</div>
                <div class="stat-content">
                    <h3 id="stat-env">-</h3>
                    <p>Danger environnement</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Aide et documentation -->
    <section class="help-section">
        <div class="help-content">
            <h3>💡 Aide à la recherche</h3>
            <div class="help-grid">
                <div class="help-item">
                    <strong>Codes produits :</strong>
                    <p>Recherchez par code complet (ex: SOL11) ou partiel (ex: SOL)</p>
                </div>
                <div class="help-item">
                    <strong>Numéros UN :</strong>
                    <p>Tapez le numéro UN avec ou sans "UN" (ex: 1824 ou UN1824)</p>
                </div>
                <div class="help-item">
                    <strong>Noms de produits :</strong>
                    <p>Recherche dans les noms et descriptions techniques</p>
                </div>
                <div class="help-item">
                    <strong>Filtres avancés :</strong>
                    <p>Utilisez les filtres pour affiner vos résultats</p>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- Configuration JavaScript -->
<script>
// Configuration pour la recherche ADR
window.ADR_SEARCH_CONFIG = {
    apiEndpoint: '/adr/search/search.php',
    minChars: 3,
    maxResults: 50,
    searchDelay: 300
};

// Variables globales pour le module
window.ADR_CURRENT_QUERY = <?= json_encode($query) ?>;
</script>

<!-- JavaScript du module recherche -->
<script src="/adr/assets/js/adr.js?v=<?= $build_number ?>"></script>
<script src="/adr/assets/js/search.js?v=<?= $build_number ?>"></script>

<script>
// Initialisation page de recherche
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Initialisation page recherche ADR');
    
    // Initialiser le module recherche
    if (typeof ADR !== 'undefined' && ADR.Search) {
        ADR.Search.init();
        
        // Charger produits populaires
        loadPopularProducts();
        
        // Charger statistiques
        loadStats();
        
        // Si requête dans URL, lancer recherche
        if (window.ADR_CURRENT_QUERY && window.ADR_CURRENT_QUERY.length >= 3) {
            setTimeout(function() {
                performSearch(window.ADR_CURRENT_QUERY);
            }, 500);
        }
    } else {
        console.error('❌ Module ADR.Search non disponible');
    }
});

// Fonctions globales pour la recherche
function performSearch(query) {
    if (typeof ADR !== 'undefined' && ADR.Search) {
        ADR.Search.performFullSearch(query || document.getElementById('product-search').value);
    }
}

function clearResults() {
    if (typeof ADR !== 'undefined' && ADR.Search) {
        ADR.Search.clearResults();
    }
}

function exportResults() {
    if (typeof ADR !== 'undefined' && ADR.Search) {
        ADR.Search.exportResults();
    }
}

function loadPopularProducts() {
    fetch('/adr/search/search.php?action=popular&limit=6')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                displayPopularProducts(data.products);
            }
        })
        .catch(error => {
            console.error('Erreur chargement produits populaires:', error);
            document.getElementById('popular-content').innerHTML = '<p class="error">Erreur de chargement</p>';
        });
}

function loadStats() {
    fetch('/adr/search/search.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.stats) {
                document.getElementById('stat-total').textContent = data.stats.total || '0';
                document.getElementById('stat-adr').textContent = data.stats.adr || '0';
                document.getElementById('stat-env').textContent = data.stats.env || '0';
            }
        })
        .catch(error => {
            console.error('Erreur chargement statistiques:', error);
        });
}

function displayPopularProducts(products) {
    const container = document.getElementById('popular-content');
    if (!products.length) {
        container.innerHTML = '<p>Aucun produit populaire disponible</p>';
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="popular-item" onclick="searchProduct('${product.code_produit}')">
            <div class="popular-header">
                <strong>${product.code_produit}</strong>
                ${product.numero_un ? `<span class="un-badge">UN${product.numero_un}</span>` : ''}
            </div>
            <p class="popular-name">${product.nom_produit}</p>
            ${product.classe_adr ? `<span class="classe-badge">Classe ${product.classe_adr}</span>` : ''}
        </div>
    `).join('');
}

function searchProduct(code) {
    document.getElementById('product-search').value = code;
    performSearch(code);
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

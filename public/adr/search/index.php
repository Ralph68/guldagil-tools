<?php
/**
 * Titre: Page de recherche ADR
 * Chemin: /public/adr/search/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

// Gestion des erreurs
require_once ROOT_PATH . '/config/error_handler_simple.php';

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
$category = $_GET['category'] ?? '';
$transport_type = $_GET['transport'] ?? '';

// Configuration de recherche
$search_config = [
    'min_chars' => 1,
    'max_results' => 50,
    'api_endpoint' => '/adr/search/search.php'
];

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

<!-- Container principal -->
<main class="adr-container search-page">
    
    <!-- Header de recherche -->
    <section class="search-header">
        <div class="search-intro">
            <h1>🔍 Recherche produits ADR</h1>
            <p>Recherchez dans la base de données des produits et marchandises dangereuses</p>
        </div>
    </section>

    <!-- Zone de recherche avancée -->
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

            <!-- Suggestions en temps réel -->
            <div id="search-suggestions" class="search-suggestions" style="display: none;"></div>

            <!-- Filtres avancés -->
            <div class="search-filters">
                <details class="filter-group">
                    <summary>Filtres avancés</summary>
                    <div class="filters-content">
                        <div class="filter-row">
                            <label for="category-filter">Catégorie de transport :</label>
                            <select id="category-filter" name="category">
                                <option value="">Toutes les catégories</option>
                                <option value="1" <?= $category === '1' ? 'selected' : '' ?>>Catégorie 1</option>
                                <option value="2" <?= $category === '2' ? 'selected' : '' ?>>Catégorie 2</option>
                                <option value="3" <?= $category === '3' ? 'selected' : '' ?>>Catégorie 3</option>
                            </select>
                        </div>
                        
                        <div class="filter-row">
                            <label for="transport-filter">Type de transport :</label>
                            <select id="transport-filter" name="transport">
                                <option value="">Tous les transports</option>
                                <option value="heppner" <?= $transport_type === 'heppner' ? 'selected' : '' ?>>Heppner</option>
                                <option value="xpo" <?= $transport_type === 'xpo' ? 'selected' : '' ?>>XPO</option>
                                <option value="kn" <?= $transport_type === 'kn' ? 'selected' : '' ?>>Kuehne + Nagel</option>
                            </select>
                        </div>

                        <div class="filter-row">
                            <label class="checkbox-label">
                                <input type="checkbox" id="adr-only" name="adr_only">
                                Produits ADR uniquement
                            </label>
                        </div>

                        <div class="filter-row">
                            <label class="checkbox-label">
                                <input type="checkbox" id="env-danger" name="env_danger">
                                Dangereux pour l'environnement
                            </label>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </section>

    <!-- Zone de résultats -->
    <section id="search-results" class="results-section" style="display: none;">
        <div class="results-header">
            <h2 id="results-title">Résultats de recherche</h2>
            <div class="results-actions">
                <button class="btn-export" onclick="exportResults()">📋 Exporter</button>
                <button class="btn-clear" onclick="clearSearch()">🗑️ Effacer</button>
            </div>
        </div>
        
        <div id="results-content" class="results-content">
            <!-- Les résultats seront chargés ici -->
        </div>
        
        <div id="results-pagination" class="results-pagination" style="display: none;">
            <!-- Pagination sera générée ici -->
        </div>
    </section>

    <!-- Zone de produits populaires -->
    <section id="popular-products" class="popular-section">
        <h2>🔥 Produits populaires</h2>
        <div id="popular-content" class="popular-content">
            <div class="loading-spinner">Chargement des produits populaires...</div>
        </div>
    </section>

    <!-- Instructions -->
    <section class="help-section">
        <details>
            <summary>💡 Aide à la recherche</summary>
            <div class="help-content">
                <h3>Comment rechercher efficacement :</h3>
                <ul>
                    <li><strong>Code produit :</strong> Tapez le code exact (ex: GUL-001)</li>
                    <li><strong>Nom produit :</strong> Recherche partielle possible (ex: "chlore")</li>
                    <li><strong>Numéro UN :</strong> Format UN suivi de 4 chiffres (ex: UN1005)</li>
                    <li><strong>Mots-clés :</strong> Plusieurs mots séparés par des espaces</li>
                </ul>
                
                <h3>Filtres disponibles :</h3>
                <ul>
                    <li><strong>Catégorie :</strong> Filtre par catégorie de transport ADR</li>
                    <li><strong>Transport :</strong> Filtre par transporteur disponible</li>
                    <li><strong>ADR uniquement :</strong> Masque les produits non-ADR</li>
                    <li><strong>Environnement :</strong> Produits dangereux pour l'environnement</li>
                </ul>
            </div>
        </details>
    </section>

</main>

<!-- Configuration JavaScript -->
<script>
// Configuration pour le module de recherche
window.ADR_SEARCH_CONFIG = {
    apiEndpoint: '<?= $search_config['api_endpoint'] ?>',
    minChars: <?= $search_config['min_chars'] ?>,
    maxResults: <?= $search_config['max_results'] ?>,
    initialQuery: '<?= addslashes($query) ?>',
    debug: <?= defined('DEBUG') && DEBUG ? 'true' : 'false' ?>
};

// Fonctions globales
function performSearch() {
    const query = document.getElementById('product-search').value.trim();
    if (query.length >= window.ADR_SEARCH_CONFIG.minChars) {
        if (window.ADR && window.ADR.Search) {
            window.ADR.Search.performFullSearch(query);
        } else {
            console.error('Module de recherche ADR non disponible');
        }
    }
}

function clearSearch() {
    document.getElementById('product-search').value = '';
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('popular-products').style.display = 'block';
    
    // Réinitialiser les filtres
    document.getElementById('category-filter').value = '';
    document.getElementById('transport-filter').value = '';
    document.getElementById('adr-only').checked = false;
    document.getElementById('env-danger').checked = false;
}

function exportResults() {
    if (window.ADR && window.ADR.Search) {
        window.ADR.Search.exportResults();
    } else {
        alert('Fonction d\'export non disponible');
    }
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Page de recherche ADR chargée');
    
    // Charger les produits populaires
    loadPopularProducts();
    
    // Si une recherche est définie dans l'URL, l'exécuter
    if (window.ADR_SEARCH_CONFIG.initialQuery) {
        setTimeout(() => performSearch(), 500);
    }
    
    // Gestionnaire de touches pour la recherche
    document.getElementById('product-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    // Gestionnaires pour les filtres
    ['category-filter', 'transport-filter', 'adr-only', 'env-danger'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function() {
                if (document.getElementById('product-search').value) {
                    performSearch();
                }
            });
        }
    });
});

function loadPopularProducts() {
    const popularContent = document.getElementById('popular-content');
    
    fetch(window.ADR_SEARCH_CONFIG.apiEndpoint + '?action=popular&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                displayPopularProducts(data.products);
            } else {
                popularContent.innerHTML = '<p class="no-results">Aucun produit populaire disponible</p>';
            }
        })
        .catch(error => {
            console.error('Erreur chargement produits populaires:', error);
            popularContent.innerHTML = '<p class="error-message">Erreur de chargement</p>';
        });
}

function displayPopularProducts(products) {
    const popularContent = document.getElementById('popular-content');
    
    if (!products || products.length === 0) {
        popularContent.innerHTML = '<p class="no-results">Aucun produit populaire</p>';
        return;
    }
    
    const html = products.map(product => `
        <div class="popular-item" onclick="selectProduct('${product.code_produit}')">
            <div class="product-header">
                <span class="product-code">${product.code_produit}</span>
                ${product.numero_un ? `<span class="badge badge-adr">UN${product.numero_un}</span>` : ''}
                ${product.danger_environnement === 'oui' ? '<span class="badge badge-env">ENV</span>' : ''}
            </div>
            <div class="product-name">${product.nom_produit || 'Produit sans nom'}</div>
            <div class="product-details">
                ${product.categorie_transport ? `Cat. ${product.categorie_transport}` : ''} 
                ${product.type_contenant ? ` • ${product.type_contenant}` : ''}
            </div>
        </div>
    `).join('');
    
    popularContent.innerHTML = html;
}

function selectProduct(code) {
    document.getElementById('product-search').value = code;
    performSearch();
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

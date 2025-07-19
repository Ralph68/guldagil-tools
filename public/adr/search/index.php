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
            <div class="search-filters">
                <details class="filter-group">
                    <summary>Filtres avancés</summary>
                    <div class="filters-content">
                        <div class="filter-row">
                            <label for="classe-filter">Classe ADR :</label>
                            <select id="classe-filter" name="classe">
                                <option value="">Toutes les classes</option>
                                <option value="1">Classe 1 - Explosifs</option>
                                <option value="2">Classe 2 - Gaz</option>
                                <option value="3">Classe 3 - Liquides inflammables</option>
                                <option value="4">Classe 4 - Solides inflammables</option>
                                <option value="5">Classe 5 - Oxydants</option>
                                <option value="6">Classe 6 - Toxiques</option>
                                <option value="8">Classe 8 - Corrosifs</option>
                                <option value="9">Classe 9 - Divers</option>
                            </select>
                        </div>
                        
                        <div class="filter-row">
                            <label for="groupe-filter">Groupe d'emballage :</label>
                            <select id="groupe-filter" name="groupe">
                                <option value="">Tous les groupes</option>
                                <option value="I">Groupe I - Très dangereux</option>
                                <option value="II">Groupe II - Moyennement dangereux</option>
                                <option value="III">Groupe III - Faiblement dangereux</option>
                            </select>
                        </div>

                        <div class="filter-row">
                            <label for="adr-filter">Statut ADR :</label>
                            <select id="adr-filter" name="adr_status">
                                <option value="">Tous les produits</option>
                                <option value="adr_only">Uniquement ADR</option>
                                <option value="non_adr_only">Uniquement non-ADR</option>
                            </select>
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
            <!-- Résultats chargés ici -->
        </div>
    </section>

    <!-- Produits populaires -->
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
                    <li><strong>Code produit :</strong> Tapez le code exact (ex: G18) - affiche codes liés (SOL11 + SOL111)</li>
                    <li><strong>Nom produit :</strong> Recherche partielle possible (ex: "Corg 315")</li>
                    <li><strong>Numéro UN :</strong> Format UN suivi de 4 chiffres (ex: UN1719) → tous les produits avec cet UN</li>
                    <li><strong>Mots-clés :</strong> Plusieurs mots séparés par des espaces</li>
                </ul>
                
                <h3>Filtres disponibles :</h3>
                <ul>
                    <li><strong>Classe ADR :</strong> Filtre par classe de danger</li>
                    <li><strong>Groupe emballage :</strong> I, II, III</li>
                    <li><strong>Produits ADR :</strong> Uniquement ADR / Uniquement non-ADR / Tous</li>
                    <li><strong>Environnement :</strong> Dangereux pour l'environnement</li>
                </ul>
            </div>
        </details>
    </section>

</main>

<script>
// Configuration globale
window.ADR_SEARCH_CONFIG = {
    apiEndpoint: '/adr/search/search.php',
    minChars: 3,
    maxResults: 50,
    initialQuery: '<?= addslashes($query) ?>',
    debug: <?= defined('DEBUG') && DEBUG ? 'true' : 'false' ?>
};

// Variables globales
let searchTimeout = null;
let selectedIndex = -1;

// Fonctions principales
function performSearch() {
    const query = document.getElementById('product-search').value.trim();
    if (query.length >= window.ADR_SEARCH_CONFIG.minChars) {
        fullSearch(query);
    }
}

function clearSearch() {
    document.getElementById('product-search').value = '';
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('popular-products').style.display = 'block';
    
    // Réinitialiser filtres
    document.getElementById('classe-filter').value = '';
    document.getElementById('groupe-filter').value = '';
    document.getElementById('adr-filter').value = '';
    document.getElementById('env-danger').checked = false;
    
    hideSearchHint();
}

function exportResults() {
    alert('Fonction export à implémenter');
}

// Gestion de la saisie
function handleSearchInput(query) {
    clearTimeout(searchTimeout);
    
    if (query.length > 0 && query.length < window.ADR_SEARCH_CONFIG.minChars) {
        showSearchHint();
        hideSuggestions();
        return;
    } else {
        hideSearchHint();
    }
    
    if (query.length < window.ADR_SEARCH_CONFIG.minChars) {
        hideSuggestions();
        document.getElementById('search-results').style.display = 'none';
        document.getElementById('popular-products').style.display = 'block';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetchSuggestions(query);
    }, 300);
}

// API Suggestions
function fetchSuggestions(query) {
    const url = window.ADR_SEARCH_CONFIG.apiEndpoint + '?action=suggestions&q=' + encodeURIComponent(query) + '&limit=10';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySuggestions(data.suggestions);
            }
        })
        .catch(error => {
            console.error('Erreur suggestions:', error);
        });
}

// Affichage suggestions
function displaySuggestions(suggestions) {
    const container = document.getElementById('search-suggestions');
    if (!container) return;
    
    if (!suggestions || suggestions.length === 0) {
        hideSuggestions();
        return;
    }
    
    let html = '';
    suggestions.forEach((product, index) => {
        html += '<div class="suggestion-item" onclick="selectProduct(\'' + product.code_produit + '\')">';
        html += '<div class="suggestion-content">';
        html += '<div class="suggestion-name">' + escapeHtml(product.nom_produit || 'Produit sans nom') + '</div>';
        html += '<div class="suggestion-code">Code: ' + product.code_produit + '</div>';
        html += '<div class="suggestion-badges">';
        if (product.numero_un) {
            html += '<span class="badge badge-adr">UN' + product.numero_un + '</span>';
        }
        if (product.danger_environnement === 'oui') {
            html += '<span class="badge badge-env">ENV</span>';
        }
        if (product.classe_adr) {
            html += '<span class="badge badge-classe">Classe ' + product.classe_adr + '</span>';
        }
        html += '</div></div></div>';
    });
    
    container.innerHTML = html;
    showSuggestions();
}

// Recherche complète
function fullSearch(query) {
    document.getElementById('popular-products').style.display = 'none';
    
    // Construire URL avec filtres
    let url = window.ADR_SEARCH_CONFIG.apiEndpoint + '?action=search&q=' + encodeURIComponent(query) + '&limit=' + window.ADR_SEARCH_CONFIG.maxResults;
    
    const classe = document.getElementById('classe-filter').value;
    const groupe = document.getElementById('groupe-filter').value;
    const adrStatus = document.getElementById('adr-filter').value;
    const envDanger = document.getElementById('env-danger').checked;
    
    if (classe) url += '&classe=' + encodeURIComponent(classe);
    if (groupe) url += '&groupe=' + encodeURIComponent(groupe);
    if (adrStatus) url += '&adr_status=' + encodeURIComponent(adrStatus);
    if (envDanger) url += '&env_danger=true';
    
    // Afficher loading
    showLoading();
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.products, data.total, query);
            } else {
                showError('Erreur de recherche: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erreur recherche:', error);
            showError('Erreur de connexion');
        });
}

// Affichage résultats
function displayResults(products, total, query) {
    const resultsContent = document.getElementById('results-content');
    const resultsTitle = document.getElementById('results-title');
    
    if (resultsTitle) {
        const count = products.length;
        const totalText = total > count ? ' (' + total + ' au total)' : '';
        resultsTitle.textContent = count + ' résultat' + (count > 1 ? 's' : '') + ' pour "' + query + '"' + totalText;
    }
    
    if (products.length === 0) {
        resultsContent.innerHTML = '<div class="no-results"><p>Aucun produit trouvé pour "' + escapeHtml(query) + '"</p></div>';
    } else {
        let html = '';
        products.forEach(product => {
            html += '<div class="result-item">';
            html += '<div class="result-header">';
            html += '<span class="result-code">' + product.code_produit + '</span>';
            html += '<div class="result-badges">';
            if (product.numero_un) {
                html += '<span class="badge badge-adr">UN' + product.numero_un + '</span>';
            }
            if (product.danger_environnement === 'oui') {
                html += '<span class="badge badge-env">ENV</span>';
            }
            if (product.classe_adr) {
                html += '<span class="badge badge-classe">Classe ' + product.classe_adr + '</span>';
            }
            html += '</div></div>';
            html += '<div class="result-name">' + escapeHtml(product.nom_produit || 'Produit sans nom') + '</div>';
            html += '<div class="result-details">';
            if (product.nom_description_un) {
                html += '<span class="result-label">Description UN:</span><span>' + escapeHtml(product.nom_description_un) + '</span>';
            }
            if (product.type_contenant) {
                html += '<span class="result-label">Contenant:</span><span>' + escapeHtml(product.type_contenant) + '</span>';
            }
            html += '</div>';
            html += '<div class="result-actions">';
            html += '<a href="https://www.quickfds.com/fr/search/Guldagil/' + encodeURIComponent(product.code_produit) + '" target="_blank" class="fds-link">📄 Fiche de sécurité</a>';
            html += '</div></div>';
        });
        resultsContent.innerHTML = html;
    }
    
    showResults();
}

// Chargement produits populaires
function loadPopularProducts() {
    const popularContent = document.getElementById('popular-content');
    
    fetch(window.ADR_SEARCH_CONFIG.apiEndpoint + '?action=popular&limit=10')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.json();
        })
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
    
    let html = '';
    products.forEach(product => {
        html += '<div class="popular-item" onclick="selectProduct(\'' + product.code_produit + '\')">';
        html += '<div class="product-header">';
        html += '<span class="product-code">' + product.code_produit + '</span>';
        if (product.numero_un) {
            html += '<span class="badge badge-adr">UN' + product.numero_un + '</span>';
        }
        if (product.danger_environnement === 'oui') {
            html += '<span class="badge badge-env">ENV</span>';
        }
        html += '</div>';
        html += '<div class="product-name">' + escapeHtml(product.nom_produit || 'Produit sans nom') + '</div>';
        html += '<div class="product-details">';
        if (product.classe_adr) {
            html += 'Classe ' + product.classe_adr;
        }
        if (product.type_contenant) {
            html += ' • ' + escapeHtml(product.type_contenant);
        }
        html += '</div></div>';
    });
    
    popularContent.innerHTML = html;
}

function selectProduct(code) {
    document.getElementById('product-search').value = code;
    performSearch();
}

// Utilitaires affichage
function showSuggestions() {
    document.getElementById('search-suggestions').style.display = 'block';
}

function hideSuggestions() {
    document.getElementById('search-suggestions').style.display = 'none';
}

function showResults() {
    document.getElementById('search-results').style.display = 'block';
}

function showSearchHint() {
    document.getElementById('search-hint').classList.add('show');
}

function hideSearchHint() {
    document.getElementById('search-hint').classList.remove('show');
}

function showLoading() {
    const resultsContent = document.getElementById('results-content');
    resultsContent.innerHTML = '<div class="loading-spinner"><p>🔍 Recherche en cours...</p></div>';
    showResults();
}

function showError(message) {
    const resultsContent = document.getElementById('results-content');
    resultsContent.innerHTML = '<div class="error-message"><p>❌ ' + escapeHtml(message) + '</p></div>';
    showResults();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Page de recherche ADR chargée');
    
    // Charger produits populaires
    loadPopularProducts();
    
    // Gestionnaire recherche
    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            handleSearchInput(e.target.value);
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        searchInput.addEventListener('focus', function() {
            if (this.value.length >= window.ADR_SEARCH_CONFIG.minChars) {
                showSuggestions();
            }
        });
    }
    
    // Gestionnaires filtres
    ['classe-filter', 'groupe-filter', 'adr-filter', 'env-danger'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function() {
                const query = document.getElementById('product-search').value;
                if (query && query.length >= window.ADR_SEARCH_CONFIG.minChars) {
                    performSearch();
                }
            });
        }
    });
    
    // Fermer suggestions au clic extérieur
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            hideSuggestions();
        }
    });
    
    // Recherche initiale si query dans URL
    if (window.ADR_SEARCH_CONFIG.initialQuery) {
        setTimeout(() => performSearch(), 500);
    }
});
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

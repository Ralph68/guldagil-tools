<?php
/**
 * Titre: Page d'accueil module ADR
 * Chemin: /public/adr/index.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Vérification authentification portail
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit;
}

// Configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Variables pour templates
$page_title = 'Module ADR';
$page_subtitle = 'Gestion des marchandises dangereuses';
$page_description = 'Module ADR - Transport de marchandises dangereuses selon réglementation';
$current_module = 'adr';
$module_css = true;
$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
$user_authenticated = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚠️', 'text' => 'Module ADR', 'url' => '/adr/', 'active' => true]
];

$nav_info = 'Transport de marchandises dangereuses';

// Debug mode
$debug_mode = defined('DEBUG') && DEBUG;

// Simuler données quotas (uniquement XPO et Heppner)
$quotas_data = [
    'xpo' => ['used' => 750, 'limit' => 1000, 'percentage' => 75],
    'heppner' => ['used' => 320, 'limit' => 1000, 'percentage' => 32]
];

// Stats rapides (à connecter aux vraies données)
$quick_stats = [
    'declarations_today' => 12,
    'products_adr' => 180,
    'alerts_active' => 3,
    'last_declaration' => '14:32'
];

// Inclure header
if (file_exists(__DIR__ . '/../../templates/header.php')) {
    include __DIR__ . '/../../templates/header.php';
} else {
    // Header minimal si template non trouvé
    echo '<!DOCTYPE html><html><head><title>Module ADR</title><meta charset="utf-8"></head><body>';
}
?>

<!-- CSS spécifique ADR -->
    <link rel="stylesheet" href="assets/css/adr.css">
<!-- Container principal -->
<main class="adr-container">
    
    <!-- Debug panel si activé -->
    <?php if ($debug_mode): ?>
    <div class="debug-panel">
        🔧 DEBUG MODE | Session: <?= session_id() ?> | User: <?= $current_user['username'] ?> | Role: <?= $current_user['role'] ?? 'user' ?>
    </div>
    <?php endif; ?>

    <!-- Hero section avec quotas intégrés -->
    <section class="adr-hero">
        <div class="hero-content">
            <h1>
                <span>⚠️</span>
                <span>Module ADR</span>
            </h1>
            <p>Gestion des marchandises dangereuses selon la réglementation ADR</p>
        </div>
        
        <!-- Quotas en sidebar -->
        <div class="hero-quotas">
            <h3>⚖️ Quotas quotidiens (1000 pts/jour)</h3>
            <?php foreach ($quotas_data as $transporteur => $quota): ?>
            <div class="quota-mini">
                <div class="quota-mini-header">
                    <span class="quota-mini-name"><?= strtoupper($transporteur) ?></span>
                    <span class="quota-mini-value"><?= $quota['used'] ?>/<?= $quota['limit'] ?></span>
                </div>
                <div class="quota-mini-bar">
                    <div class="quota-mini-fill <?= $quota['percentage'] > 80 ? 'high' : ($quota['percentage'] > 50 ? 'medium' : 'low') ?>" 
                         style="width: <?= $quota['percentage'] ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Section recherche produit -->
    <section class="search-section">
        <h2 class="search-title">
            🔍 Recherche produit ADR
        </h2>
        <div class="search-form">
            <input 
                type="text" 
                class="search-input" 
                id="product-search"
                placeholder="Code produit, nom, numéro UN..." 
                autocomplete="off"
            >
            <div class="search-suggestions" id="search-suggestions"></div>
        </div>
    </section>

    <!-- Actions principales -->
    <section class="main-actions">
        <a href="declaration/create.php" class="action-card primary">
            <span class="action-icon">📝</span>
            <h3 class="action-title">Nouvelle déclaration</h3>
            <p class="action-desc">Créer une déclaration d'expédition de marchandises dangereuses</p>
            <span class="btn">Commencer</span>
        </a>

        <a href="recap_daily.php" class="action-card success">
            <span class="action-icon">📊</span>
            <h3 class="action-title">Récap journalier</h3>
            <p class="action-desc">Consulter les récapitulatifs quotidiens par transporteur</p>
            <span class="btn btn-outline">Consulter</span>
        </a>

        <a href="archives.php" class="action-card info">
            <span class="action-icon">📋</span>
            <h3 class="action-title">Archives</h3>
            <p class="action-desc">Consulter et réouvrir les déclarations passées</p>
            <span class="btn btn-outline">Voir archives</span>
        </a>
    </section>

    <!-- Supprimer cette section - quotas déplacés dans hero -->
    <!-- Section supprimée : quotas-section -->

    <!-- Stats rapides -->
    <section class="stats-section">
        <h2 class="stats-title">
            📈 Statistiques du jour
        </h2>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?= $quick_stats['declarations_today'] ?></div>
                <div class="stat-label">Déclarations aujourd'hui</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $quick_stats['products_adr'] ?></div>
                <div class="stat-label">Produits ADR actifs</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $quick_stats['alerts_active'] ?></div>
                <div class="stat-label">Alertes actives</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $quick_stats['last_declaration'] ?></div>
                <div class="stat-label">Dernière déclaration</div>
            </div>
        </div>
    </section>

</main>

<!-- Scripts -->
<script>
// Configuration
const ADR_CONFIG = {
    searchEndpoint: 'ajax/search.php',
    minChars: 1,
    searchDelay: 300
};

let searchTimeout;
let currentResults = [];

// Recherche en temps réel
document.getElementById('product-search').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length >= ADR_CONFIG.minChars) {
        searchTimeout = setTimeout(() => {
            searchProducts(query);
        }, ADR_CONFIG.searchDelay);
    } else {
        hideSuggestions();
    }
});

// Navigation clavier
document.getElementById('product-search').addEventListener('keydown', function(e) {
    const suggestions = document.getElementById('search-suggestions');
    const items = suggestions.querySelectorAll('.suggestion-item');
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        navigateSuggestions(items, 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        navigateSuggestions(items, -1);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const active = suggestions.querySelector('.suggestion-item.active');
        if (active) {
            selectProduct(active.dataset.code);
        }
    } else if (e.key === 'Escape') {
        hideSuggestions();
    }
});

function searchProducts(query) {
    const suggestions = document.getElementById('search-suggestions');
    suggestions.innerHTML = '<div class="suggestion-loading">🔍 Recherche...</div>';
    suggestions.style.display = 'block';

    fetch(`search/search.php?action=suggestions&q=${encodeURIComponent(query)}&limit=10`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.text(); // D'abord récupérer le texte
        })
        .then(text => {
            try {
                const data = JSON.parse(text); // Puis parser
                if (data.success && data.suggestions && data.suggestions.length > 0) {
                    displaySuggestions(data.suggestions);
                } else {
                    suggestions.innerHTML = '<div class="suggestion-empty">Aucun résultat trouvé</div>';
                }
            } catch (e) {
                console.error('JSON invalide:', text);
                suggestions.innerHTML = '<div class="suggestion-error">Erreur de format</div>';
            }
        })
        .catch(error => {
            console.error('Erreur recherche:', error);
            suggestions.innerHTML = '<div class="suggestion-error">Erreur de recherche</div>';
        });
}

function displaySuggestions(results) {
    const suggestions = document.getElementById('search-suggestions');
    currentResults = results;
    
    let html = '';
    results.forEach((product, index) => {
        const badges = [];
        if (product.un) badges.push(`<span class="badge adr">UN ${product.un}</span>`);
        if (product.env_danger) badges.push(`<span class="badge env">🌍</span>`);
        if (product.closed) badges.push(`<span class="badge closed">🔒</span>`);
        
        html += `
            <div class="suggestion-item ${index === 0 ? 'active' : ''}" 
                 data-code="${product.code}" 
                 onclick="selectProduct('${product.code}')">
                <div class="suggestion-main">
                    <strong>${product.code}</strong> - ${product.name}
                </div>
                <div class="suggestion-badges">${badges.join(' ')}</div>
            </div>
        `;
    });
    
    suggestions.innerHTML = html;
}

function navigateSuggestions(items, direction) {
    const currentActive = document.querySelector('.suggestion-item.active');
    let newIndex = 0;
    
    if (currentActive) {
        const currentIndex = Array.from(items).indexOf(currentActive);
        newIndex = Math.max(0, Math.min(items.length - 1, currentIndex + direction));
        currentActive.classList.remove('active');
    }
    
    if (items[newIndex]) {
        items[newIndex].classList.add('active');
    }
}

function selectProduct(code) {
    const product = currentResults.find(p => p.code === code);
    if (product) {
        document.getElementById('product-search').value = product.display;
        alert(`Produit sélectionné: ${product.name}\nCode: ${product.code}${product.un ? '\nUN: ' + product.un : ''}`);
    }
    hideSuggestions();
}

function hideSuggestions() {
    document.getElementById('search-suggestions').style.display = 'none';
}

// Fermer si clic ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-form')) {
        hideSuggestions();
    }
});

// Animation d'entrée
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.action-card, .quota-card, .stat-item');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

console.log('🔰 Module ADR initialisé');
<?php if ($debug_mode): ?>
console.log('🚨 Mode debug actif');
<?php endif; ?>
</script>

<?php
// Inclure footer
if (file_exists(__DIR__ . '/../../templates/footer.php')) {
    include __DIR__ . '/../../templates/footer.php';
} else {
    echo '</body></html>';
}
?>

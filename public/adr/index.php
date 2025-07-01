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
<style>
    :root {
        --adr-primary: #ff6b35;
        --adr-secondary: #f7931e;
        --adr-danger: #dc3545;
        --adr-success: #28a745;
        --adr-warning: #ffc107;
        --adr-info: #17a2b8;
    }

    .adr-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .adr-hero {
        background: linear-gradient(135deg, var(--adr-primary), var(--adr-secondary));
        color: white;
        padding: 3rem 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        text-align: center;
        box-shadow: 0 4px 16px rgba(255, 107, 53, 0.3);
    }

    .adr-hero h1 {
        font-size: 2.5rem;
        margin: 0 0 0.5rem 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }

    .adr-hero p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0;
    }

    /* Section recherche */
    .search-section {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.1);
    }

    .search-title {
        color: var(--adr-primary);
        margin: 0 0 1.5rem 0;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .search-form {
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1.5rem;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--adr-primary);
        box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
    }

    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e1e5e9;
        border-top: none;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: none;
        z-index: 100;
        max-height: 300px;
        overflow-y: auto;
    }

    /* Actions principales */
    .main-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .action-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        cursor: pointer;
        border-left: 4px solid;
        text-decoration: none;
        color: inherit;
    }

    .action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        text-decoration: none;
        color: inherit;
    }

    .action-card.primary { border-left-color: var(--adr-primary); }
    .action-card.success { border-left-color: var(--adr-success); }
    .action-card.info { border-left-color: var(--adr-info); }

    .action-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        display: block;
    }

    .action-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
    }

    .action-desc {
        color: #666;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: var(--adr-primary);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn:hover {
        background: #e55a2b;
        transform: translateY(-1px);
        text-decoration: none;
        color: white;
    }

    .btn-outline {
        background: transparent;
        color: var(--adr-primary);
        border: 2px solid var(--adr-primary);
    }

    .btn-outline:hover {
        background: var(--adr-primary);
        color: white;
    }

    /* Quotas section */
    .quotas-section {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.1);
    }

    .quotas-title {
        color: var(--adr-primary);
        margin: 0 0 1.5rem 0;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .quotas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .quota-card {
        border: 1px solid #e1e5e9;
        border-radius: 8px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .quota-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .quota-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .quota-name {
        font-weight: 600;
        color: #2c3e50;
        font-size: 1.1rem;
    }

    .quota-value {
        font-size: 0.9rem;
        color: #666;
    }

    .quota-bar {
        width: 100%;
        height: 24px;
        background: #f1f2f6;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }

    .quota-fill {
        height: 100%;
        transition: width 0.8s ease;
        border-radius: 12px;
    }

    .quota-fill.low { background: linear-gradient(90deg, var(--adr-success), #34d058); }
    .quota-fill.medium { background: linear-gradient(90deg, var(--adr-warning), #ffdf5d); }
    .quota-fill.high { background: linear-gradient(90deg, var(--adr-danger), #ff6b9d); }

    .quota-status {
        font-size: 0.9rem;
        text-align: center;
        font-weight: 500;
    }

    /* Stats section */
    .stats-section {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.1);
    }

    .stats-title {
        color: var(--adr-primary);
        margin: 0 0 1.5rem 0;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.5rem;
    }

    .stat-item {
        text-align: center;
        padding: 1.5rem 1rem;
        border: 1px solid #e1e5e9;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .stat-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .stat-value {
        font-size: 2.2rem;
        font-weight: bold;
        color: var(--adr-primary);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Debug panel */
    .debug-panel {
        background: #1a1a1a;
        color: #00ff00;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-family: monospace;
        font-size: 0.85rem;
    }

    /* Suggestions styles */
    .suggestion-item {
        padding: 0.75rem;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background 0.2s;
    }
    .suggestion-item:hover, .suggestion-item.active {
        background: #f8f9fa;
    }
    .suggestion-item:last-child {
        border-bottom: none;
    }
    .suggestion-main {
        margin-bottom: 0.25rem;
        font-weight: 500;
    }
    .suggestion-badges {
        display: flex;
        gap: 0.25rem;
    }
    .badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        color: white;
        font-weight: 500;
    }
    .badge.adr { background: #dc3545; }
    .badge.env { background: #ffc107; color: #333; }
    .badge.closed { background: #6c757d; }
    .suggestion-loading, .suggestion-empty, .suggestion-error {
        padding: 1rem;
        text-align: center;
        color: #666;
    }
    .suggestion-error {
        color: #dc3545;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .adr-container {
            padding: 1rem;
        }
        .adr-hero {
            padding: 2rem 1rem;
        }
        .adr-hero h1 {
            font-size: 2rem;
            flex-direction: column;
            gap: 0.5rem;
        }
        .main-actions {
            grid-template-columns: 1fr;
        }
        .quotas-grid {
            grid-template-columns: 1fr;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<!-- Container principal -->
<main class="adr-container">
    
    <!-- Debug panel si activé -->
    <?php if ($debug_mode): ?>
    <div class="debug-panel">
        🔧 DEBUG MODE | Session: <?= session_id() ?> | User: <?= $current_user['username'] ?> | Role: <?= $current_user['role'] ?? 'user' ?>
    </div>
    <?php endif; ?>

    <!-- Hero section -->
    <section class="adr-hero">
        <h1>
            <span>⚠️</span>
            <span>Module ADR</span>
        </h1>
        <p>Gestion des marchandises dangereuses selon la réglementation ADR</p>
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

    <!-- Quotas quotidiens (sans Kuehne+Nagel) -->
    <section class="quotas-section">
        <h2 class="quotas-title">
            ⚖️ Quotas quotidiens (1000 pts/jour/transporteur)
        </h2>
        <div class="quotas-grid">
            <?php foreach ($quotas_data as $transporteur => $quota): ?>
            <div class="quota-card">
                <div class="quota-header">
                    <span class="quota-name"><?= strtoupper($transporteur) ?></span>
                    <span class="quota-value"><?= $quota['used'] ?> / <?= $quota['limit'] ?> pts</span>
                </div>
                <div class="quota-bar">
                    <div class="quota-fill <?= $quota['percentage'] > 80 ? 'high' : ($quota['percentage'] > 50 ? 'medium' : 'low') ?>" 
                         style="width: <?= $quota['percentage'] ?>%"></div>
                </div>
                <div class="quota-status">
                    <?= $quota['percentage'] ?>% utilisé
                    <?php if ($quota['percentage'] > 90): ?>
                        ⚠️ Limite proche
                    <?php elseif ($quota['percentage'] > 80): ?>
                        🟡 Attention
                    <?php else: ?>
                        ✅ OK
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

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

    fetch(`${ADR_CONFIG.searchEndpoint}?q=${encodeURIComponent(query)}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.results.length > 0) {
                displaySuggestions(data.results);
            } else {
                suggestions.innerHTML = '<div class="suggestion-empty">Aucun résultat trouvé</div>';
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

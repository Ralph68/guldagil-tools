<?php
// public/adr/dashboard.php - Version COMPLÈTE avec recherche dynamique et onglets
session_start();

// Vérification authentification ADR
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    $_SESSION['adr_logged_in'] = true;
    $_SESSION['adr_user'] = 'demo.user';
    $_SESSION['adr_login_time'] = time();
}

require __DIR__ . '/../../config.php';

// Statistiques pour le dashboard
try {
    $stmt = $db->query("SELECT 
        COUNT(*) as total_produits,
        COUNT(CASE WHEN numero_un IS NOT NULL AND numero_un != '' THEN 1 END) as produits_adr,
        COUNT(CASE WHEN corde_article_ferme = 'x' THEN 1 END) as produits_fermes,
        COUNT(CASE WHEN danger_environnement = 'OUI' THEN 1 END) as produits_env_dangereux
        FROM gul_adr_products WHERE actif = 1");
    $stats = $stmt->fetch();
    
    $stmt = $db->query("SELECT 
        categorie_transport, 
        COUNT(*) as nombre,
        GROUP_CONCAT(DISTINCT type_contenant ORDER BY type_contenant) as contenants
        FROM gul_adr_products 
        WHERE actif = 1 AND categorie_transport IS NOT NULL 
        GROUP BY categorie_transport 
        ORDER BY categorie_transport");
    $categories = $stmt->fetchAll();
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as total_declarations FROM gul_adr_declarations");
        $declarations_count = $stmt->fetch()['total_declarations'];
    } catch (Exception $e) {
        $declarations_count = 0;
    }
    
} catch (Exception $e) {
    $stats = ['total_produits' => 0, 'produits_adr' => 0, 'produits_fermes' => 0, 'produits_env_dangereux' => 0];
    $categories = [];
    $declarations_count = 0;
    error_log("Erreur stats ADR: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ADR - Guldagil Portal</title>
    <style>
        :root {
            --adr-primary: #ff6b35;
            --adr-secondary: #f7931e;
            --adr-danger: #dc3545;
            --adr-warning: #ffc107;
            --adr-success: #28a745;
            --adr-dark: #343a40;
            --adr-light: #f8f9fa;
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 16px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Header ADR */
        .adr-header {
            background: linear-gradient(135deg, var(--adr-primary) 0%, var(--adr-secondary) 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .adr-logo {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
        }

        .btn-header {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-header:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        /* Container principal */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Onglets de navigation */
        .dashboard-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .tab-button {
            background: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            box-shadow: var(--shadow);
            color: var(--adr-dark);
            min-width: 150px;
            justify-content: center;
        }

        .tab-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .tab-button.active {
            background: var(--adr-primary);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Section recherche produits */
        .search-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--adr-primary);
        }

        .search-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-icon {
            width: 50px;
            height: 50px;
            background: var(--adr-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .search-container {
            position: relative;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            transition: var(--transition);
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>') no-repeat 12px center;
            background-size: 20px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--adr-primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        /* Suggestions de recherche */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            box-shadow: var(--shadow-hover);
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suggestion-item:hover,
        .suggestion-item.selected {
            background: var(--adr-light);
            transform: translateX(4px);
        }

        .suggestion-product {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .suggestion-name {
            font-weight: 600;
            color: var(--adr-primary);
        }

        .suggestion-code {
            font-size: 0.9rem;
            color: #666;
        }

        .suggestion-badges {
            display: flex;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-adr {
            background: var(--adr-danger);
            color: white;
        }

        .badge-env {
            background: var(--adr-warning);
            color: #333;
        }

        .badge-cat {
            background: var(--adr-dark);
            color: white;
        }

        /* Section résultats */
        .results-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: none;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .results-table th {
            background: var(--adr-light);
            font-weight: 600;
            color: var(--adr-dark);
        }

        .results-table tr:hover {
            background: var(--adr-light);
        }

        /* Statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card.primary { border-left-color: var(--adr-primary); }
        .stat-card.danger { border-left-color: var(--adr-danger); }
        .stat-card.warning { border-left-color: var(--adr-warning); }
        .stat-card.success { border-left-color: var(--adr-success); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--adr-primary);
            margin-bottom: 0.5rem;
        }

        .stat-detail {
            font-size: 0.85rem;
            color: #666;
        }

        /* Catégories ADR */
        .categories-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .category-card {
            background: var(--adr-light);
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--adr-primary);
            transition: var(--transition);
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .category-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--adr-primary);
        }

        .category-count {
            background: var(--adr-primary);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .category-contenants {
            font-size: 0.8rem;
            color: #666;
        }

        /* États vides */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--adr-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .header-container {
                padding: 0 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .search-section {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-tabs {
                flex-direction: column;
            }

            .tab-button {
                min-width: auto;
            }

            body {
                padding-top: 120px;
            }
        }
    </style>
</head>
<body>
    
    <header class="adr-header">
        <div class="header-container">
            <div class="header-title">
                <div class="adr-logo">⚠️</div>
                <div>
                    <h1>Dashboard ADR</h1>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Gestion des marchandises dangereuses</div>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="user-info">
                    <span>👤</span>
                    <span><?= htmlspecialchars($_SESSION['adr_user']) ?></span>
                </div>

                <a href="declaration/create.php" class="btn-header">
                    <span>➕</span>
                    Nouvelle déclaration
                </a>
                
                <a href="../" class="btn-header">
                    <span>🏠</span>
                    Portal
                </a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Onglets de navigation -->
        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="showTab('recherche')" data-tab="recherche">
                <span>🔍</span>
                Recherche produits
            </button>
            <button class="tab-button" onclick="showTab('expeditions')" data-tab="expeditions">
                <span>➕</span>
                Nouvelle expédition
            </button>
            <button class="tab-button" onclick="showTab('mes-expeditions')" data-tab="mes-expeditions">
                <span>📋</span>
                Mes expéditions
            </button>
            <button class="tab-button" onclick="showTab('recapitulatifs')" data-tab="recapitulatifs">
                <span>📊</span>
                Récapitulatifs
            </button>
            <button class="tab-button" onclick="showTab('statistiques')" data-tab="statistiques">
                <span>📈</span>
                Statistiques
            </button>
        </div>

        <!-- Contenu onglet Recherche produits -->
        <div id="tab-recherche" class="tab-content active">
            <section class="search-section">
                <div class="search-header">
                    <div class="search-icon">🔍</div>
                    <div>
                        <h2>Recherche produits ADR</h2>
                        <p>Tapez un code article ou nom de produit pour obtenir toutes les informations réglementaires</p>
                    </div>
                </div>
                
                <div class="search-container">
                    <input type="text" 
                           class="search-input" 
                           id="product-search" 
                           placeholder="Ex: Performax, GULTRAT, code article..."
                           autocomplete="off">
                    
                    <div class="search-suggestions" id="search-suggestions"></div>
                </div>
                
                <div style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                    <strong>💡 Astuces :</strong> 
                    • Recherche partielle acceptée (ex: "Perf" trouvera "Performax")
                    • Recherche par code UN (ex: "3412")
                    • Filtrage automatique par catégorie de danger
                </div>
            </section>

            <section class="results-section" id="search-results">
                <div class="results-header">
                    <h3 id="results-title">Résultats de recherche</h3>
                    <button class="btn-header" onclick="clearResults()">
                        <span>✖️</span>
                        Effacer
                    </button>
                </div>
                
                <div id="results-content"></div>
            </section>
        </div>

        <!-- Contenu onglet Nouvelle expédition -->
        <div id="tab-expeditions" class="tab-content">
            <div class="empty-state">
                <div class="empty-state-icon">➕</div>
                <h3>Nouvelle expédition ADR</h3>
                <p>Créez une nouvelle déclaration d'expédition de marchandises dangereuses</p>
                <br>
                <a href="declaration/create.php" class="btn-header" style="background: var(--adr-primary); color: white; padding: 1rem 2rem;">
                    Commencer une déclaration
                </a>
            </div>
        </div>

        <!-- Contenu onglet Mes expéditions -->
        <div id="tab-mes-expeditions" class="tab-content">
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>Mes expéditions</h3>
                <p>Consultez l'historique de vos déclarations ADR</p>
                <br>
                <a href="declaration/list.php" class="btn-header" style="background: var(--adr-primary); color: white; padding: 1rem 2rem;">
                    Voir la liste
                </a>
            </div>
        </div>

        <!-- Contenu onglet Récapitulatifs -->
        <div id="tab-recapitulatifs" class="tab-content">
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <h3>Récapitulatifs quotidiens</h3>
                <p>Générez les récapitulatifs par transporteur pour les expéditions du jour</p>
                <br>
                <a href="recap/daily.php" class="btn-header" style="background: var(--adr-primary); color: white; padding: 1rem 2rem;">
                    Accéder aux récapitulatifs
                </a>
            </div>
        </div>

        <!-- Contenu onglet Statistiques -->
        <div id="tab-statistiques" class="tab-content">
            <section class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-title">Total produits</div>
                        <div class="stat-icon">📦</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_produits']) ?></div>
                    <div class="stat-detail">Produits dans le catalogue</div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-header">
                        <div class="stat-title">Produits ADR</div>
                        <div class="stat-icon">⚠️</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['produits_adr']) ?></div>
                    <div class="stat-detail">Nécessitent déclaration ADR</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-title">Danger environnement</div>
                        <div class="stat-icon">🌍</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['produits_env_dangereux']) ?></div>
                    <div class="stat-detail">Produits polluants marins</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-title">Déclarations</div>
                        <div class="stat-icon">📋</div>
                    </div>
                    <div class="stat-value"><?= number_format($declarations_count) ?></div>
                    <div class="stat-detail">Total expéditions déclarées</div>
                </div>
            </section>

            <section class="categories-section">
                <h3>📊 Répartition par catégories de transport</h3>
                <div class="categories-grid">
                    <?php foreach ($categories as $cat): ?>
                    <div class="category-card">
                        <div class="category-header">
                            <div class="category-number">Cat. <?= htmlspecialchars($cat['categorie_transport']) ?></div>
                            <div class="category-count"><?= $cat['nombre'] ?></div>
                        </div>
                        <div class="category-contenants">
                            <?= htmlspecialchars(substr($cat['contenants'], 0, 50)) ?><?= strlen($cat['contenants']) > 50 ? '...' : '' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>

    <script>
        // ========== GESTION DES ONGLETS ==========
        function showTab(tabName) {
            console.log('🔄 Changement onglet:', tabName);
            
            // Masquer tous les contenus
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Désactiver tous les boutons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Activer l'onglet sélectionné
            document.getElementById(`tab-${tabName}`).classList.add('active');
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            
            // Focus sur la recherche si onglet recherche
            if (tabName === 'recherche') {
                setTimeout(() => {
                    const searchInput = document.getElementById('product-search');
                    if (searchInput) searchInput.focus();
                }, 100);
            }
        }

        // ========== RECHERCHE DYNAMIQUE ==========
        const searchConfig = {
            minChars: 3,
            delay: 150,
            maxResults: 20
        };

        const searchCache = {};
        let searchTimeout;
        let currentSearchTerm = '';
        let selectedIndex = -1;

        // Éléments DOM
        const searchInput = document.getElementById('product-search');
        const suggestionsContainer = document.getElementById('search-suggestions');
        const resultsSection = document.getElementById('search-results');
        const resultsContent = document.getElementById('results-content');
        const resultsTitle = document.getElementById('results-title');

        // Event listeners pour la recherche
        if (searchInput) {
            searchInput.addEventListener('input', handleSearchInput);
            searchInput.addEventListener('keydown', handleKeyNavigation);
            searchInput.addEventListener('blur', hideSuggestions);
            searchInput.addEventListener('focus', handleSearchFocus);
        }

        function handleSearchInput(e) {
            const term = e.target.value.trim();
            currentSearchTerm = term;
            selectedIndex = -1;

            if (term.length < searchConfig.minChars) {
                hideSuggestions();
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(term);
            }, searchConfig.delay);
        }

        function handleKeyNavigation(e) {
            const suggestions = document.querySelectorAll('.suggestion-item');
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                    updateSelectedSuggestion();
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelectedSuggestion();
                    break;
                    
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                        selectProduct(suggestions[selectedIndex].dataset.code);
                    } else if (currentSearchTerm.length >= searchConfig.minChars) {
                        performFullSearch(currentSearchTerm);
                    }
                    break;
                    
                case 'Escape':
                    hideSuggestions();
                    searchInput.blur();
                    break;
            }
        }

        function handleSearchFocus() {
            if (currentSearchTerm.length >= searchConfig.minChars) {
                searchProducts(currentSearchTerm);
            }
        }

        function searchProducts(term) {
            console.log('🔍 Recherche:', term);

            // Utiliser le cache si disponible
            if (searchCache[term]) {
                displaySuggestions(searchCache[term]);
                return;
            }

            // Recherche via API
            fetch(`search/api.php?q=${encodeURIComponent(term)}&limit=${searchConfig.maxResults}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        searchCache[term] = data.suggestions;
                        displaySuggestions(data.suggestions);
                    } else {
                        console.error('Erreur recherche:', data.error);
                        hideSuggestions();
                    }
                })
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    hideSuggestions();
                });
        }

        function displaySuggestions(suggestions) {
            if (!suggestions || suggestions.length === 0) {
                hideSuggestions();
                return;
            }

            let html = '';
            suggestions.forEach((product, index) => {
                const badges = [];
                
                if (product.numero_un) {
                    badges.push(`<span class="badge badge-adr">UN ${product.numero_un}</span>`);
                }
                
                if (product.danger_environnement === 'OUI') {
                    badges.push(`<span class="badge badge-env">ENV</span>`);
                }
                
                if (product.categorie_transport) {
                    badges.push(`<span class="badge badge-cat">Cat.${product.categorie_transport}</span>`);
                }

                html += `
                    <div class="suggestion-item" data-code="${product.code_produit}" data-index="${index}">
                        <div class="suggestion-product">
                            <div class="suggestion-name">${highlightMatch(product.nom_produit, currentSearchTerm)}</div>
                            <div class="suggestion-code">Code: ${product.code_produit}</div>
                        </div>
                        <div class="suggestion-badges">
                            ${badges.join('')}
                        </div>
                    </div>
                `;
            });

            suggestionsContainer.innerHTML = html;
            suggestionsContainer.style.display = 'block';

            // Event listeners pour les suggestions
            document.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault(); // Empêche le blur
                    selectProduct(item.dataset.code);
                });
                
                item.addEventListener('mouseenter', () => {
                    selectedIndex = parseInt(item.dataset.index);
                    updateSelectedSuggestion();
                });
            });
        }

        function updateSelectedSuggestion() {
            document.querySelectorAll('.suggestion-item').forEach((item, index) => {
                item.classList.toggle('selected', index === selectedIndex);
            });
        }

        function selectProduct(codeProduct) {
            console.log('📦 Sélection produit:', codeProduct);
            
            hideSuggestions();
            searchInput.value = codeProduct;
            performFullSearch(codeProduct, true);
        }

        function performFullSearch(term, singleProduct = false) {
            console.log('🔍 Recherche complète:', term);
            
            resultsContent.innerHTML = '<div class="loading"><div class="spinner"></div>Recherche en cours...</div>';
            resultsSection.style.display = 'block';
            
            const action = singleProduct ? 'detail' : 'search';
            
            fetch(`search/api.php?action=${action}&q=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayResults(data.results, term);
                    } else {
                        resultsContent.innerHTML = `<div style="text-align:center;color:#666;padding:2rem;">❌ ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Erreur recherche complète:', error);
                    resultsContent.innerHTML = '<div style="text-align:center;color:#666;padding:2rem;">❌ Erreur de connexion</div>';
                });
        }

        function displayResults(results, searchTerm) {
            if (!results || results.length === 0) {
                resultsContent.innerHTML = `
                    <div style="text-align:center;color:#666;padding:2rem;">
                        <div style="font-size:2rem;margin-bottom:1rem;">📭</div>
                        <div>Aucun produit trouvé pour "${searchTerm}"</div>
                        <div style="margin-top:1rem;font-size:0.9rem;">
                            Vérifiez l'orthographe ou essayez avec moins de caractères
                        </div>
                    </div>
                `;
                return;
            }

            resultsTitle.textContent = `Résultats pour "${searchTerm}" (${results.length})`;

            let html = `
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Code article</th>
                            <th>UN / Description</th>
                            <th>Catégorie</th>
                            <th>Contenant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            results.forEach(product => {
                const statusBadges = [];
                
                if (product.numero_un) {
                    statusBadges.push(`<span class="badge badge-adr">ADR</span>`);
                }
                
                if (product.danger_environnement === 'OUI') {
                    statusBadges.push(`<span class="badge badge-env">ENV</span>`);
                }
                
                if (product.corde_article_ferme === 'x') {
                    statusBadges.push(`<span class="badge" style="background:#dc3545;color:white;">FERMÉ</span>`);
                }

                const unInfo = product.numero_un ? 
                    `<strong>UN ${product.numero_un}</strong><br><small>${product.nom_description_un || 'Description non disponible'}</small>` : 
                    '<span style="color:#999;">Non-ADR</span>';

                html += `
                    <tr>
                        <td>
                            <div style="font-weight:600;color:var(--adr-primary);">${product.nom_produit}</div>
                            ${product.nom_technique ? `<small style="color:#666;">${product.nom_technique}</small>` : ''}
                        </td>
                        <td>
                            <code style="background:#f5f5f5;padding:0.2rem 0.4rem;border-radius:4px;">${product.code_produit}</code>
                        </td>
                        <td>${unInfo}</td>
                        <td class="text-center">
                            ${product.categorie_transport ? 
                                `<span class="badge badge-cat">Cat. ${product.categorie_transport}</span>` : 
                                '<span style="color:#999;">-</span>'
                            }
                        </td>
                        <td>
                            ${product.type_contenant || '-'}<br>
                            <small style="color:#666;">${product.poids_contenant || ''}</small>
                        </td>
                        <td>${statusBadges.join(' ')}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            resultsContent.innerHTML = html;
        }

        function hideSuggestions() {
            setTimeout(() => {
                if (suggestionsContainer) {
                    suggestionsContainer.style.display = 'none';
                }
            }, 150);
        }

        function clearResults() {
            if (resultsSection) resultsSection.style.display = 'none';
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
        }

        function highlightMatch(text, searchTerm) {
            if (!text || !searchTerm) return text;
            
            const safeTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\            // Recherche via API
            fetch(`search/api.php?q=${encodeURIComponent(term)}&limit=${searchConfig.maxResults}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        searchCache[term] = data.suggestions;
                        displaySugg');
            const regex = new RegExp(`(${safeTerm})`, 'gi');
            return text.replace(regex, '<mark style="background:yellow;padding:0.1rem;">$1</mark>');
        }

        // ========== RACCOURCIS CLAVIER ==========
        document.addEventListener('keydown', function(e) {
            // Ctrl+K ou Cmd+K pour focus sur la recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                showTab('recherche');
                setTimeout(() => {
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }, 100);
            }
            
            // Escape pour effacer la recherche
            if (e.key === 'Escape' && document.activeElement !== searchInput) {
                clearResults();
            }

            // Chiffres 1-5 pour naviguer entre onglets
            const numberKeys = ['1', '2', '3', '4', '5'];
            const tabNames = ['recherche', 'expeditions', 'mes-expeditions', 'recapitulatifs', 'statistiques'];
            
            if (e.ctrlKey && numberKeys.includes(e.key)) {
                e.preventDefault();
                const tabIndex = parseInt(e.key) - 1;
                if (tabNames[tabIndex]) {
                    showTab(tabNames[tabIndex]);
                }
            }
        });

        // ========== INITIALISATION ==========
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Dashboard ADR chargé - Version complète avec onglets');
            
            // Auto-focus sur la recherche
            if (searchInput) {
                searchInput.focus();
            }
            
            // Charger des suggestions populaires au focus initial (optionnel)
            setTimeout(() => {
                if (searchInput && !searchInput.value) {
                    loadPopularProducts();
                }
            }, 500);
        });

        // Chargement des produits populaires (suggestions initiales)
        function loadPopularProducts() {
            fetch('search/api.php?action=popular&limit=8')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.popular) {
                        displayInitialSuggestions(data.popular);
                    }
                })
                .catch(error => {
                    console.log('Info: Produits populaires non disponibles');
                });
        }

        // Affichage des suggestions initiales
        function displayInitialSuggestions(products) {
            if (!products || products.length === 0) return;

            let html = '<div style="padding:0.5rem 1rem;background:#f8f9fa;font-size:0.8rem;color:#666;border-bottom:1px solid #eee;">💡 Produits fréquemment recherchés :</div>';
            
            products.forEach((product, index) => {
                html += `
                    <div class="suggestion-item" data-code="${product.code_produit}" data-index="${index}">
                        <div class="suggestion-product">
                            <div class="suggestion-name">${product.nom_produit}</div>
                            <div class="suggestion-code">Code: ${product.code_produit}</div>
                        </div>
                        <div class="suggestion-badges">
                            ${product.numero_un ? `<span class="badge badge-adr">UN ${product.numero_un}</span>` : ''}
                        </div>
                    </div>
                `;
            });

            if (suggestionsContainer) {
                suggestionsContainer.innerHTML = html;
                
                // Event listeners pour les suggestions initiales
                document.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        selectProduct(item.dataset.code);
                    });
                });
            }
        }

        // Gestion responsive des suggestions
        function handleResize() {
            const suggestions = document.getElementById('search-suggestions');
            
            if (suggestions) {
                if (window.innerWidth <= 768) {
                    suggestions.style.position = 'fixed';
                    suggestions.style.left = '1rem';
                    suggestions.style.right = '1rem';
                    suggestions.style.maxHeight = '250px';
                } else {
                    suggestions.style.position = 'absolute';
                    suggestions.style.left = '0';
                    suggestions.style.right = '0';
                    suggestions.style.maxHeight = '300px';
                }
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // Appel initial

        // Analytics de recherche (optionnel)
        function trackSearch(term, resultCount) {
            console.log('📊 Analytics:', { term, resultCount, timestamp: new Date().toISOString() });
        }

        console.log('💡 Raccourcis disponibles:');
        console.log('  • Ctrl+K : Focus recherche');
        console.log('  • Ctrl+1-5 : Navigation onglets');
        console.log('  • Flèches : Navigation suggestions');
        console.log('  • Enter : Sélection');
        console.log('  • Escape : Fermer');
    </script>
</body>
</html>

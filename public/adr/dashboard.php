<?php
// public/adr/dashboard.php - Dashboard refactorisé avec modules
session_start();

// Vérification authentification ADR (temporaire)
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    $_SESSION['adr_logged_in'] = true;
    $_SESSION['adr_user'] = 'demo.user';
    $_SESSION['adr_login_time'] = time();
    $_SESSION['adr_permissions'] = ['read', 'write', 'admin', 'dev'];
}

require __DIR__ . '/../../config.php';

// Statistiques rapides pour le dashboard
try {
    // Compter les produits ADR
    $stmt = $db->query("SELECT 
        COUNT(*) as total_produits,
        COUNT(CASE WHEN numero_un IS NOT NULL AND numero_un != '' THEN 1 END) as produits_adr,
        COUNT(CASE WHEN corde_article_ferme = 'x' THEN 1 END) as produits_fermes,
        COUNT(CASE WHEN danger_environnement = 'OUI' THEN 1 END) as produits_env_dangereux
        FROM gul_adr_products WHERE actif = 1");
    $stats = $stmt->fetch();
    
    // Répartition par catégorie
    $stmt = $db->query("SELECT 
        categorie_transport, 
        COUNT(*) as nombre,
        GROUP_CONCAT(DISTINCT type_contenant ORDER BY type_contenant) as contenants
        FROM gul_adr_products 
        WHERE actif = 1 AND categorie_transport IS NOT NULL 
        GROUP BY categorie_transport 
        ORDER BY categorie_transport");
    $categories = $stmt->fetchAll();
    
    // Dernières déclarations (si la table existe)
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
    
    <!-- Styles CSS -->
    <link rel="stylesheet" href="../assets/css/adr.css">
    <style>
        /* Styles globaux pour le dashboard */
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

        /* Navigation du dashboard */
        .dashboard-nav {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav-item {
            padding: 0.75rem 1.5rem;
            background: var(--adr-light);
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--adr-dark);
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-item:hover {
            background: var(--adr-primary);
            color: white;
            transform: translateY(-1px);
        }

        .nav-item.active {
            background: var(--adr-primary);
            color: white;
        }

        /* Section recherche principale */
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

        /* Statistiques dashboard */
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

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            backdrop-filter: blur(4px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            max-width: 1200px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: var(--adr-primary);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.2);
        }

        .modal-body {
            padding: 20px;
            max-height: calc(90vh - 140px);
            overflow-y: auto;
        }

        .modal-footer {
            background: var(--adr-light);
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Styles pour les boutons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary { background: var(--adr-primary); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-warning { background: var(--adr-warning); color: #333; }
        .btn-danger { background: var(--adr-danger); color: white; }
        .btn-success { background: var(--adr-success); color: white; }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
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

            body {
                padding-top: 120px;
            }

            .dashboard-nav {
                flex-direction: column;
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
                
                <button class="btn-header" onclick="loadDevTools()">
                    🛠️ Outils Dev
                </button>
                
                <button class="btn-header" onclick="loadMaintenance()">
                    🧰 Maintenance
                </button>

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
        
        <!-- Navigation du dashboard -->
        <nav class="dashboard-nav">
            <a href="#recherche" class="nav-item active">
                <span>🔍</span>
                Recherche produits
            </a>
            <a href="declaration/create.php" class="nav-item">
                <span>➕</span>
                Nouvelle expédition
            </a>
            <a href="declaration/list.php" class="nav-item">
                <span>📋</span>
                Mes expéditions
            </a>
            <a href="recap/daily.php" class="nav-item">
                <span>📊</span>
                Récapitulatifs
            </a>
            <a href="#" class="nav-item" onclick="showQuickStats()">
                <span>📈</span>
                Statistiques
            </a>
        </nav>
        
        <!-- Section recherche principale -->
        <section class="search-section" id="recherche">
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

        <!-- Section résultats -->
        <section class="results-section" id="search-results" style="display: none;">
            <div class="results-header">
                <h3 id="results-title">Résultats de recherche</h3>
                <button class="btn-header" onclick="clearResults()">
                    <span>✖️</span>
                    Effacer
                </button>
            </div>
            
            <div id="results-content"></div>
        </section>

        <!-- Statistiques du dashboard -->
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

        <!-- Section catégories -->
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

    <!-- Modal Outils développement -->
    <div id="dev-tools-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🛠️ Outils de développement</h3>
                <button class="modal-close" onclick="closeModal('dev-tools-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <?php include 'modals/dev-tools.php'; ?>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('dev-tools-modal')">Fermer</button>
                <button class="btn btn-danger" onclick="clearAllTestData()">🗑️ Nettoyer données test</button>
            </div>
        </div>
    </div>

    <!-- Modal Maintenance -->
    <div id="maintenance-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🧰 Maintenance système</h3>
                <button class="modal-close" onclick="closeModal('maintenance-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Contenu dynamique des onglets de maintenance -->
                <div class="maintenance-tabs">
                    <button class="tab-btn active" onclick="loadMaintenanceTab('database')">🗄️ Base de données</button>
                    <button class="tab-btn" onclick="loadMaintenanceTab('cleanup')">🧹 Nettoyage</button>
                    <button class="tab-btn" onclick="loadMaintenanceTab('backup')">💾 Sauvegarde</button>
                    <button class="tab-btn" onclick="loadMaintenanceTab('monitoring')">📊 Monitoring</button>
                    <button class="tab-btn" onclick="loadMaintenanceTab('logs')">📝 Logs</button>
                </div>
                
                <div id="maintenance-content">
                    <!-- Le contenu sera chargé dynamiquement -->
                    <div class="loading-placeholder">
                        <div class="spinner"></div>
                        <p>Chargement de l'onglet...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="maintenance-status">
                    <span id="maintenance-mode-status">🟢 Mode normal</span>
                </div>
                <div>
                    <button class="btn btn-warning" onclick="toggleMaintenanceMode()">🔧 Basculer mode maintenance</button>
                    <button class="btn btn-secondary" onclick="closeModal('maintenance-modal')">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chargement des scripts -->
    <script src="../assets/js/adr.js"></script>
    
    <script>
        // Configuration du dashboard
        const dashboardConfig = {
            searchMinChars: 3,
            searchDelay: 150,
            maxResults: 20,
            cacheTimeout: 5 * 60 * 1000 // 5 minutes
        };

        // Cache pour optimiser les recherches
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

        // Initialisation du dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Dashboard ADR initialisé');
            initializeSearch();
            loadQuickStats();
        });

        // ========== FONCTIONS DE RECHERCHE ==========

        function initializeSearch() {
            if (!searchInput) return;

            searchInput.addEventListener('input', handleSearchInput);
            searchInput.addEventListener('keydown', handleKeyNavigation);
            searchInput.addEventListener('blur', hideSuggestions);
            searchInput.addEventListener('focus', handleSearchFocus);
            searchInput.focus();
        }

        function handleSearchInput(e) {
            const term = e.target.value.trim();
            currentSearchTerm = term;
            selectedIndex = -1;

            if (term.length < dashboardConfig.searchMinChars) {
                hideSuggestions();
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(term);
            }, dashboardConfig.searchDelay);
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
                    } else if (currentSearchTerm.length >= dashboardConfig.searchMinChars) {
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
            if (currentSearchTerm.length >= dashboardConfig.searchMinChars) {
                searchProducts(currentSearchTerm);
            }
        }

        function searchProducts(term) {
            console.log('🔍 Recherche:', term);

            // Vérifier le cache
            if (searchCache[term]) {
                displaySuggestions(searchCache[term]);
                return;
            }

            // Simuler un appel API (remplacer par le vrai endpoint)
            fetch(`search/api.php?q=${encodeURIComponent(term)}&limit=${dashboardConfig.maxResults}`)
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
                    // Données de démonstration en cas d'erreur
                    displayDemoSuggestions(term);
                });
        }

        function displayDemoSuggestions(term) {
            const demoData = [
                { code_produit: 'GUL-001', nom_produit: 'GULTRAT pH+', numero_un: '1823', categorie_transport: '8' },
                { code_produit: 'GUL-002', nom_produit: 'PERFORMAX', numero_un: '3265', categorie_transport: '3' },
                { code_produit: 'GUL-003', nom_produit: 'ALKADOSE', numero_un: '1824', categorie_transport: '8' }
            ].filter(p => p.nom_produit.toLowerCase().includes(term.toLowerCase()));
            
            displaySuggestions(demoData);
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
                    e.preventDefault();
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
            
            // Simuler une recherche complète
            setTimeout(() => {
                displayMockResults(term);
            }, 800);
        }

        function displayMockResults(searchTerm) {
            const mockResults = [
                {
                    code_produit: 'GUL-001',
                    nom_produit: 'GULTRAT pH+',
                    numero_un: '1823',
                    nom_description_un: 'Hydroxyde de sodium en solution',
                    categorie_transport: '8',
                    type_contenant: 'Bidon 25L',
                    danger_environnement: 'NON',
                    corde_article_ferme: ''
                }
            ];

            resultsTitle.textContent = `Résultats pour "${searchTerm}" (${mockResults.length})`;

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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            mockResults.forEach(product => {
                html += `
                    <tr>
                        <td>
                            <div style="font-weight:600;color:var(--adr-primary);">${product.nom_produit}</div>
                        </td>
                        <td>
                            <code style="background:#f5f5f5;padding:0.2rem 0.4rem;border-radius:4px;">${product.code_produit}</code>
                        </td>
                        <td>
                            <strong>UN ${product.numero_un}</strong><br>
                            <small>${product.nom_description_un}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-cat">Cat. ${product.categorie_transport}</span>
                        </td>
                        <td>${product.type_contenant}</td>
                        <td><span class="badge badge-adr">ADR</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary" onclick="showProductDetail('${product.code_produit}')">
                                📋 Détail
                            </button>
                        </td>
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
            resultsSection.style.display = 'none';
            searchInput.value = '';
            searchInput.focus();
        }

        function highlightMatch(text, searchTerm) {
            if (!text || !searchTerm) return text;
            
            const safeTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\    <!-- Modal Outils développement -->');
            const regex = new RegExp(`(${safeTerm})`, 'gi');
            return text.replace(regex, '<mark style="background:yellow;padding:0.1rem;">$1</mark>');
        }

        function showProductDetail(codeProduct) {
            alert(`Détail du produit ${codeProduct}\n\nCette fonctionnalité sera développée prochainement.`);
        }

        // ========== FONCTIONS MODALS ==========

        function loadDevTools() {
            document.getElementById('dev-tools-modal').classList.add('active');
        }

        function loadMaintenance() {
            document.getElementById('maintenance-modal').classList.add('active');
            loadMaintenanceTab('database'); // Charger l'onglet par défaut
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function loadMaintenanceTab(tabName) {
            console.log('📋 Chargement onglet maintenance:', tabName);
            
            // Mettre à jour les boutons d'onglets
            document.querySelectorAll('.maintenance-tabs .tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Charger le contenu de l'onglet
            const maintenanceContent = document.getElementById('maintenance-content');
            maintenanceContent.innerHTML = '<div class="loading-placeholder"><div class="spinner"></div><p>Chargement...</p></div>';
            
            // Simuler le chargement du contenu
            setTimeout(() => {
                loadMaintenanceTabContent(tabName);
            }, 500);
        }

        function loadMaintenanceTabContent(tabName) {
            const maintenanceContent = document.getElementById('maintenance-content');
            
            // Pour la démo, charger le contenu correspondant
            // En production, ceci ferait un appel AJAX pour charger le contenu
            switch(tabName) {
                case 'database':
                    // Inclure le contenu de database-tab.php
                    fetch('modals/tabs/database-tab.php')
                        .then(response => response.text())
                        .then(html => {
                            maintenanceContent.innerHTML = html;
                        })
                        .catch(() => {
                            maintenanceContent.innerHTML = getDatabaseTabDemo();
                        });
                    break;
                    
                case 'cleanup':
                    fetch('modals/tabs/cleanup-tab.php')
                        .then(response => response.text())
                        .then(html => {
                            maintenanceContent.innerHTML = html;
                        })
                        .catch(() => {
                            maintenanceContent.innerHTML = getCleanupTabDemo();
                        });
                    break;
                    
                case 'monitoring':
                    fetch('modals/tabs/monitoring-tab.php')
                        .then(response => response.text())
                        .then(html => {
                            maintenanceContent.innerHTML = html;
                        })
                        .catch(() => {
                            maintenanceContent.innerHTML = getMonitoringTabDemo();
                        });
                    break;
                    
                default:
                    maintenanceContent.innerHTML = `
                        <div style="text-align: center; padding: 3rem;">
                            <h4>🚧 Onglet ${tabName}</h4>
                            <p>Contenu en développement...</p>
                        </div>
                    `;
            }
        }

        // Fonctions de démonstration pour les onglets
        function getDatabaseTabDemo() {
            return `
                <div style="padding: 20px;">
                    <h4>🗄️ Gestion base de données</h4>
                    <p>Onglet de gestion de la base de données en cours de chargement...</p>
                    <button class="btn btn-primary" onclick="alert('Fonction de démonstration')">🩺 Vérifier santé BDD</button>
                </div>
            `;
        }

        function getCleanupTabDemo() {
            return `
                <div style="padding: 20px;">
                    <h4>🧹 Nettoyage système</h4>
                    <p>Onglet de nettoyage système en cours de chargement...</p>
                    <button class="btn btn-warning" onclick="alert('Fonction de démonstration')">🗑️ Nettoyer sessions</button>
                </div>
            `;
        }

        function getMonitoringTabDemo() {
            return `
                <div style="padding: 20px;">
                    <h4>📊 Monitoring système</h4>
                    <p>Onglet de monitoring en cours de chargement...</p>
                    <button class="btn btn-info" onclick="alert('Fonction de démonstration')">📈 Voir métriques</button>
                </div>
            `;
        }

        // ========== AUTRES FONCTIONS ==========

        function showQuickStats() {
            alert('📊 Statistiques rapides ADR\n\nCette fonctionnalité ouvrira un dashboard détaillé avec:\n• Évolution des expéditions\n• Top produits ADR\n• Performance quotas\n• Tendances transporteurs');
        }

        function loadQuickStats() {
            // Charger des statistiques rapides en arrière-plan
            console.log('📊 Chargement statistiques rapides...');
        }

        function toggleMaintenanceMode() {
            const statusElement = document.getElementById('maintenance-mode-status');
            const currentMode = statusElement.textContent.includes('normal');
            
            if (currentMode) {
                statusElement.innerHTML = '🔴 Mode maintenance actif';
                statusElement.style.color = '#dc3545';
                event.target.textContent = '🟢 Désactiver maintenance';
                event.target.className = 'btn btn-success';
            } else {
                statusElement.innerHTML = '🟢 Mode normal';
                statusElement.style.color = '#28a745';
                event.target.textContent = '🔧 Basculer mode maintenance';
                event.target.className = 'btn btn-warning';
            }
        }

        // Fermer les modals en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });

        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Ctrl+K pour focus recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            
            // Escape pour fermer modals
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });

        console.log('✅ Dashboard ADR avec onglets modulaires chargé');
        console.log('💡 Raccourcis: Ctrl+K (recherche), Escape (fermer modals)');
    </script>

    <!-- Styles pour les éléments dynamiques -->
    <style>
        /* Styles pour les suggestions de recherche */
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
            padding: 1rem;
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

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-name {
            font-weight: 600;
            color: var(--adr-primary);
            margin-bottom: 0.25rem;
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

        /* Styles pour les résultats */
        .results-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
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
            background: rgba(255, 107, 53, 0.05);
        }

        /* Categories */
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

        /* Loading et spinner */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .loading-placeholder {
            text-align: center;
            padding: 3rem;
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

        /* Onglets de maintenance */
        .maintenance-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 10px 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            border-radius: 6px 6px 0 0;
        }

        .tab-btn:hover {
            background: #e9ecef;
        }

        .tab-btn.active {
            background: var(--adr-primary);
            color: white;
            border-color: var(--adr-primary);
        }
    </style>
</body>
</html>

<?php
// public/adr/dashboard.php - Dashboard principal module ADR optimisé
session_start();

// Vérification authentification ADR (temporaire - à remplacer par le vrai système)
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    // Pour le développement, on simule une session active
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
    
    // Dernières déclarations (si la table existe et contient des données)
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/adr.css">
    <style>
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
            padding-top: 100px;
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

        /* Section principale */
        .main-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--adr-primary);
        }

        /* Recherche */
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

        /* Catégories ADR */
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

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 1000px;
        }

        .modal-header {
            background: #ff6b35;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
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
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
                padding-top: 120px;
            }

            .header-container {
                padding: 0 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .header-title {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header ADR -->
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
                
                <button class="btn-header" onclick="openDevToolsModal()">
                    🛠️ Outils Dev
                </button>
                
                <button class="btn-header" onclick="openMaintenanceModal()">
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
        <!-- Section recherche -->
        <section class="main-section">
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
            </div>
            
            <div style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                <strong>💡 Astuces :</strong> 
                • Recherche partielle acceptée (ex: "Perf" trouvera "Performax")
                • Recherche par code UN (ex: "3412")
                • Filtrage automatique par catégorie de danger
            </div>
        </section>

        <!-- Statistiques -->
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

        <!-- Catégories ADR -->
        <section class="main-section">
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

    <!-- Modal Outils Dev -->
    <div id="dev-tools-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🛠️ Outils de développement</h3>
                <button class="modal-close" onclick="closeModal('dev-tools-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <?php include __DIR__ . '/modals/dev-tools.php'; ?>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" onclick="clearAllTestData()">🗑️ Nettoyer données test</button>
                <button class="btn btn-secondary" onclick="closeModal('dev-tools-modal')">Fermer</button>
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
                <?php include __DIR__ . '/modals/maintenance.php'; ?>
            </div>
            <div class="modal-footer">
                <div class="maintenance-status">
                    <span id="maintenance-mode-status">🟢 Mode normal</span>
                    <button class="btn btn-warning" onclick="toggleMaintenanceMode()">🔧 Basculer mode maintenance</button>
                </div>
                <button class="btn btn-secondary" onclick="closeModal('maintenance-modal')">Fermer</button>
            </div>
        </div>
    </div>

    <script>
        // Configuration recherche
        const searchConfig = {
            minChars: 3,
            delay: 150,
            maxResults: 20
        };

        let searchTimeout;

        // Éléments DOM
        const searchInput = document.getElementById('product-search');

        // Event listeners
        searchInput.addEventListener('input', handleSearchInput);

        function handleSearchInput(e) {
            const term = e.target.value.trim();

            if (term.length < searchConfig.minChars) {
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(term);
            }, searchConfig.delay);
        }

        function searchProducts(term) {
            console.log('🔍 Recherche:', term);
            
            // Simulation d'une recherche
            // En production, ceci ferait un appel AJAX à search/api.php
            setTimeout(() => {
                console.log('📦 Résultats trouvés pour:', term);
            }, 500);
        }

        // Gestion des modals
        function openDevToolsModal() {
            document.getElementById('dev-tools-modal').classList.add('active');
        }

        function openMaintenanceModal() {
            document.getElementById('maintenance-modal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Fermer modals avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });

        // Fermer modals en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });

        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });

        // Auto-focus sur la recherche
        document.addEventListener('DOMContentLoaded', function() {
            searchInput.focus();
        });

        console.log('✅ Dashboard ADR initialisé');
        console.log('💡 Raccourcis: Ctrl+K (recherche), Escape (fermer modals)');
    </script>
</body>
</html>

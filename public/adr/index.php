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

// Variables utilisateur
$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];

// Debug si activé
$debug_mode = defined('DEBUG') && DEBUG;

// Simuler données quotas (à remplacer par vraies requêtes BDD)
$quotas_data = [
    'xpo' => ['used' => 750, 'limit' => 1000, 'percentage' => 75],
    'heppner' => ['used' => 320, 'limit' => 1000, 'percentage' => 32],
    'kuehne' => ['used' => 890, 'limit' => 1000, 'percentage' => 89]
];

// Stats rapides (à remplacer par vraies requêtes)
$quick_stats = [
    'declarations_today' => 12,
    'products_adr' => 180,
    'alerts_active' => 3,
    'last_declaration' => '14:32'
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module ADR - Dashboard</title>
    
    <!-- CSS globaux du portail -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        :root {
            --adr-primary: #ff6b35;
            --adr-secondary: #f7931e;
            --adr-danger: #dc3545;
            --adr-success: #28a745;
            --adr-warning: #ffc107;
            --adr-info: #17a2b8;
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        body {
            background: #f5f6fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Header ADR */
        .adr-header {
            background: linear-gradient(135deg, var(--adr-primary), var(--adr-secondary));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .adr-logo {
            font-size: 2.5rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .header-info h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .header-subtitle {
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
        }

        /* Container principal */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            margin-bottom: 3rem;
        }

        /* Section recherche */
        .search-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .search-title {
            color: var(--adr-primary);
            margin: 0 0 1.5rem 0;
            font-size: 1.3rem;
        }

        .search-form {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid #e1e5e9;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
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
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: var(--shadow);
            display: none;
            z-index: 100;
        }

        /* Actions rapides */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
            border-left: 4px solid;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .action-card.primary { border-left-color: var(--adr-primary); }
        .action-card.success { border-left-color: var(--adr-success); }
        .action-card.info { border-left-color: var(--adr-info); }

        .action-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .action-title {
            font-size: 1.2rem;
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
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #e55a2b;
            transform: translateY(-1px);
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
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .quotas-title {
            color: var(--adr-primary);
            margin: 0 0 1.5rem 0;
            font-size: 1.3rem;
        }

        .quotas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .quota-card {
            border: 1px solid #e1e5e9;
            border-radius: var(--border-radius);
            padding: 1.5rem;
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
        }

        .quota-value {
            font-size: 0.9rem;
            color: #666;
        }

        .quota-bar {
            width: 100%;
            height: 20px;
            background: #f1f2f6;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .quota-fill {
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 10px;
        }

        .quota-fill.low { background: var(--adr-success); }
        .quota-fill.medium { background: var(--adr-warning); }
        .quota-fill.high { background: var(--adr-danger); }

        .quota-status {
            font-size: 0.85rem;
            text-align: center;
        }

        /* Stats section */
        .stats-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .stats-title {
            color: var(--adr-primary);
            margin: 0 0 1.5rem 0;
            font-size: 1.3rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            border: 1px solid #e1e5e9;
            border-radius: var(--border-radius);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--adr-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Navigation retour */
        .back-nav {
            margin-bottom: 1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .back-link:hover {
            background: #f1f2f6;
            color: var(--adr-primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .dashboard-container {
                padding: 0 1rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .quotas-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Debug panel */
        .debug-panel {
            background: #1a1a1a;
            color: #00ff00;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            font-family: monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    
    <!-- Navigation retour -->
    <div class="dashboard-container">
        <div class="back-nav">
            <a href="../" class="back-link">
                ← Retour au portail
            </a>
        </div>
    </div>

    <!-- Header ADR -->
    <header class="adr-header">
        <div class="header-content">
            <div class="header-title">
                <div class="adr-logo">⚠️</div>
                <div class="header-info">
                    <h1>Module ADR</h1>
                    <div class="header-subtitle">Gestion des marchandises dangereuses</div>
                </div>
            </div>
            <div class="user-badge">
                👤 <?= htmlspecialchars($current_user['username']) ?>
            </div>
        </div>
    </header>

    <!-- Container principal -->
    <div class="dashboard-container">
        
        <!-- Debug panel si activé -->
        <?php if ($debug_mode): ?>
        <div class="debug-panel">
            🔧 DEBUG MODE | Session: <?= session_id() ?> | User: <?= $current_user['username'] ?> | Role: <?= $current_user['role'] ?>
        </div>
        <?php endif; ?>

        <!-- Section recherche produit -->
        <section class="search-section">
            <h2 class="search-title">🔍 Recherche produit ADR</h2>
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

        <!-- Actions rapides -->
        <section class="quick-actions">
            <div class="action-card primary" onclick="location.href='declaration/create.php'">
                <div class="action-icon">📝</div>
                <h3 class="action-title">Nouvelle déclaration</h3>
                <p class="action-desc">Créer une déclaration d'expédition de marchandises dangereuses</p>
                <button class="btn">Commencer</button>
            </div>

            <div class="action-card info" onclick="location.href='archives.php'">
                <div class="action-icon">📋</div>
                <h3 class="action-title">Archives</h3>
                <p class="action-desc">Consulter et réouvrir les déclarations passées</p>
                <button class="btn btn-outline">Consulter</button>
            </div>

            <div class="action-card success" onclick="location.href='reports.php'">
                <div class="action-icon">📊</div>
                <h3 class="action-title">Rapports</h3>
                <p class="action-desc">Statistiques et rapports de conformité</p>
                <button class="btn btn-outline">Voir rapports</button>
            </div>
        </section>

        <!-- Quotas quotidiens -->
        <section class="quotas-section">
            <h2 class="quotas-title">⚖️ Quotas quotidiens (1000 pts/jour/transporteur)</h2>
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
            <h2 class="stats-title">📈 Statistiques du jour</h2>
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

    </div>

    <!-- Scripts -->
    <script>
        // Configuration
        const ADR_CONFIG = {
            searchEndpoint: 'ajax/search.php',
            minChars: 1,
            searchDelay: 300
        };

        let searchTimeout;

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

        function searchProducts(query) {
            const suggestions = document.getElementById('search-suggestions');
            suggestions.innerHTML = '<div style="padding: 1rem; color: #666;">🔍 Recherche...</div>';
            suggestions.style.display = 'block';

            // Simulation - à remplacer par vraie requête AJAX
            setTimeout(() => {
                const mockResults = [
                    { code: 'ADR001', name: 'Acide sulfurique', un: '1830' },
                    { code: 'ADR002', name: 'Alcool éthylique', un: '1170' },
                    { code: 'ADR003', name: 'Ammoniaque', un: '1005' }
                ];

                let html = '';
                mockResults.forEach(product => {
                    html += `
                        <div style="padding: 0.75rem; border-bottom: 1px solid #eee; cursor: pointer;" 
                             onclick="selectProduct('${product.code}')">
                            <strong>${product.code}</strong> - ${product.name}
                            <small style="color: #666; display: block;">UN ${product.un}</small>
                        </div>
                    `;
                });

                suggestions.innerHTML = html;
            }, 500);
        }

        function selectProduct(code) {
            alert(`Produit sélectionné: ${code}`);
            hideSuggestions();
        }

        function hideSuggestions() {
            document.getElementById('search-suggestions').style.display = 'none';
        }

        // Fermer suggestions si clic ailleurs
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
</body>
</html>

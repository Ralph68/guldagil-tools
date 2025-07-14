<?php
/**
 * Titre: Dashboard Module Contrôle Qualité - Simple et Professionnel
 * Chemin: /features/qualite/index.php
 * Version: 0.5 beta + build auto
 */

require_once ROOT_PATH . '/config/error_handler_simple.php';
// Sécurité et configuration
session_start();
define('PORTAL_ACCESS', true);

// Chargement de la configuration si disponible
if (file_exists(__DIR__ . '/../../config/config.php')) {
    require_once __DIR__ . '/../../config/config.php';
}
if (file_exists(__DIR__ . '/../../config/version.php')) {
    require_once __DIR__ . '/../../config/version.php';
}


// Configuration du module qualité
$qualite_config = [
    'module_name' => 'Contrôle Qualité',
    'module_icon' => '🔬',
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5 beta'
];

// Action
$action = $_GET['action'] ?? 'dashboard';

// Statistiques simples (données simulées)
$stats = [
    'controles_mois' => 127,
    'controles_semaine' => 31,
    'controles_aujourd_hui' => 8,
    'taux_conformite' => 94.2
];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $qualite_config['module_name'] ?> - Portail Guldagil</title>
    
    <!-- CSS du portail -->
    <link rel="stylesheet" href="../../public/assets/css/portal.css">
    <style>
    /* CSS simple et professionnel */
    .qualite-module {
        min-height: 100vh;
        background: #f8fafc;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .module-header {
        background: white;
        padding: 2rem;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }

    .header-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .module-title h1 {
        margin: 0;
        font-size: 2rem;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .breadcrumb {
        color: #64748b;
        margin-bottom: 1rem;
    }

    .breadcrumb a {
        color: #3b82f6;
        text-decoration: none;
    }

    .main-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    /* Actions principales */
    .actions-section {
        margin-bottom: 3rem;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .action-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
    }

    .action-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .action-card.primary {
        border-left: 4px solid #22c55e;
    }

    .action-card.secondary {
        border-left: 4px solid #3b82f6;
    }

    .action-card.warning {
        border-left: 4px solid #f59e0b;
    }

    .action-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .action-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .action-description {
        color: #64748b;
        line-height: 1.6;
    }

    /* Statistiques */
    .stats-section {
        margin-bottom: 3rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #64748b;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card.success .stat-value { color: #22c55e; }
    .stat-card.primary .stat-value { color: #3b82f6; }
    .stat-card.warning .stat-value { color: #f59e0b; }

    /* Sections */
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .section-description {
        color: #64748b;
        margin-bottom: 1.5rem;
    }

    /* Boutons */
    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
    }

    .btn-outline {
        background: transparent;
        color: #3b82f6;
        border: 1px solid #3b82f6;
    }

    .btn-outline:hover {
        background: #3b82f6;
        color: white;
    }

    /* Footer */
    .module-footer {
        margin-top: 4rem;
        padding: 2rem;
        background: white;
        border-top: 1px solid #e2e8f0;
        text-align: center;
        color: #64748b;
        font-size: 0.875rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .actions-grid, .stats-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body class="qualite-module">

    <!-- Header -->
    <header class="module-header">
        <div class="header-content">
            <div class="module-title">
                <div class="breadcrumb">
                    <a href="../../public/index.php">🏠 Accueil</a> › Contrôle Qualité
                </div>
                <h1>
                    <span><?= $qualite_config['module_icon'] ?></span>
                    <?= $qualite_config['module_name'] ?>
                </h1>
            </div>
            <div class="header-actions">
                <span class="version">Version <?= $qualite_config['version'] ?></span>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="main-content">

        <!-- Actions principales -->
        <section class="actions-section">
            <h2 class="section-title">Actions</h2>
            <p class="section-description">Créer un nouveau contrôle ou consulter les archives</p>
            
            <div class="actions-grid">
                <div class="action-card primary" onclick="nouveauControle()">
                    <span class="action-icon">➕</span>
                    <h3 class="action-title">Nouveau contrôle</h3>
                    <p class="action-description">Créer un contrôle adoucisseur ou pompe</p>
                </div>
                
                <div class="action-card secondary" onclick="consulterControles()">
                    <span class="action-icon">🔍</span>
                    <h3 class="action-title">Consulter un contrôle</h3>
                    <p class="action-description">Rechercher dans les archives</p>
                </div>
                
                <div class="action-card warning" onclick="voirAnomalies()">
                    <span class="action-icon">⚠️</span>
                    <h3 class="action-title">Répertoire anomalies</h3>
                    <p class="action-description">Consulter les anomalies par catégorie</p>
                </div>
            </div>
        </section>

        <!-- Statistiques -->
        <section class="stats-section">
            <h2 class="section-title">Statistiques</h2>
            <p class="section-description">Vue d'ensemble de l'activité</p>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-value"><?= $stats['controles_aujourd_hui'] ?></div>
                    <div class="stat-label">Aujourd'hui</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['controles_semaine'] ?></div>
                    <div class="stat-label">Cette semaine</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['controles_mois'] ?></div>
                    <div class="stat-label">Ce mois</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-value"><?= $stats['taux_conformite'] ?>%</div>
                    <div class="stat-label">Conformité</div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="module-footer">
        <p>Module <?= $qualite_config['module_name'] ?> - <?= $qualite_config['version'] ?> • 
        © <?= date('Y') ?> Guldagil - Tous droits réservés</p>
    </footer>

    <!-- JavaScript -->
    <script>
        function nouveauControle() {
            // Simple sélection du type
            const type = prompt('Type de contrôle :\n1 - Adoucisseur\n2 - Pompe\n\nEntrez 1 ou 2 :');
            
            if (type === '1') {
                window.location.href = 'components/adoucisseurs.php';
            } else if (type === '2') {
                alert('🚧 Module contrôle pompes en développement');
            }
        }

        function consulterControles() {
            window.location.href = '?action=recherche';
        }

        function voirAnomalies() {
            alert('🚧 Répertoire anomalies en développement');
        }

        // Animation simple au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.action-card, .stat-card');
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
    </script>
</body>
</html>

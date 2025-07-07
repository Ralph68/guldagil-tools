<?php
/**
 * Titre: Module Contrôle Qualité - Page principale
 * Chemin: /features/qualite/index.php
 * Version: 0.5 beta + build auto
 */

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
    'module_color' => 'green',
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5 beta',
    'components' => [
        'adoucisseurs' => [
            'name' => 'Adoucisseurs',
            'icon' => '💧',
            'active' => true,
            'file' => 'components/adoucisseurs.php'
        ],
        'filtres' => [
            'name' => 'Systèmes de filtration',
            'icon' => '🔎',
            'active' => true,
            'file' => 'components/filtres.php'
        ],
        'analyses' => [
            'name' => 'Analyses laboratoire',
            'icon' => '🧪',
            'active' => true,
            'file' => 'components/analyses.php'
        ],
        'conformite' => [
            'name' => 'Rapports conformité',
            'icon' => '📋',
            'active' => true,
            'file' => 'components/conformite.php'
        ]
    ]
];

// Gestion des actions via paramètres GET
$action = $_GET['action'] ?? 'dashboard';
$component = $_GET['component'] ?? null;

// Validation de sécurité pour les actions
$allowed_actions = ['dashboard', 'component', 'export', 'maintenance'];
$action = in_array($action, $allowed_actions) ? $action : 'dashboard';

// Données de démonstration pour le dashboard
$dashboard_stats = [
    'equipements_total' => 15,
    'equipements_operationnels' => 12,
    'equipements_maintenance' => 2,
    'equipements_panne' => 1,
    'alertes_actives' => 4,
    'controles_jour' => 8,
    'conformite_rate' => 94.2,
    'derniere_analyse' => '2025-01-08 14:30:00'
];

// Alertes système
$alertes_qualite = [
    [
        'id' => 'ALT001',
        'type' => 'maintenance',
        'severity' => 'warning',
        'titre' => 'Maintenance ADC001 programmée',
        'description' => 'Adoucisseur principal - maintenance préventive dans 24h',
        'timestamp' => '2025-01-08 10:00:00',
        'component' => 'adoucisseurs'
    ],
    [
        'id' => 'ALT002',
        'type' => 'performance',
        'severity' => 'danger',
        'titre' => 'Performance dégradée FLT003',
        'description' => 'Système de filtration laboratoire - efficacité sous seuil',
        'timestamp' => '2025-01-08 09:15:00',
        'component' => 'filtres'
    ],
    [
        'id' => 'ALT003',
        'type' => 'analyse',
        'severity' => 'info',
        'titre' => 'Rapport analyse mensuel disponible',
        'description' => 'Résultats analyses décembre 2024 générés',
        'timestamp' => '2025-01-08 08:00:00',
        'component' => 'analyses'
    ]
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
    <link rel="stylesheet" href="assets/qualite.css">
    
    <!-- Favicon et meta -->
    <link rel="icon" type="image/x-icon" href="../../public/assets/img/favicon.ico">
    <meta name="description" content="Module de contrôle qualité - Gestion des équipements de traitement de l'eau">
</head>
<body class="qualite-module">

    <!-- Header du module -->
    <header class="module-header">
        <nav class="breadcrumb">
            <a href="../../public/index.php" class="breadcrumb-item">
                <span>🏠</span> Accueil
            </a>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item current">
                <span><?= $qualite_config['module_icon'] ?></span>
                <?= $qualite_config['module_name'] ?>
            </span>
        </nav>
        
        <div class="module-header-content">
            <div class="module-title">
                <span class="module-icon"><?= $qualite_config['module_icon'] ?></span>
                <div class="module-info">
                    <h1><?= $qualite_config['module_name'] ?></h1>
                    <span class="module-version">Version <?= $qualite_config['version'] ?></span>
                </div>
            </div>
            
            <div class="module-actions">
                <button class="btn btn-outline" onclick="exportModule()">
                    <span>📊</span> Export global
                </button>
                <button class="btn btn-primary" onclick="nouvelleAnalyse()">
                    <span>🧪</span> Nouvelle analyse
                </button>
            </div>
        </div>
    </header>

    <!-- Navigation des composants -->
    <nav class="component-nav">
        <div class="nav-container">
            <button class="nav-item <?= $action === 'dashboard' ? 'active' : '' ?>" 
                    onclick="navigateToComponent('dashboard')">
                <span class="nav-icon">📊</span>
                <span class="nav-label">Dashboard</span>
            </button>
            
            <?php foreach ($qualite_config['components'] as $comp_id => $comp): ?>
                <?php if ($comp['active']): ?>
                <button class="nav-item <?= ($action === 'component' && $component === $comp_id) ? 'active' : '' ?>" 
                        onclick="navigateToComponent('<?= $comp_id ?>')">
                    <span class="nav-icon"><?= $comp['icon'] ?></span>
                    <span class="nav-label"><?= $comp['name'] ?></span>
                </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="module-content">
        <?php if ($action === 'dashboard'): ?>
            <!-- Dashboard principal -->
            <div class="dashboard-section">
                <div class="dashboard-header">
                    <h2>Vue d'ensemble du contrôle qualité</h2>
                    <span class="last-update">
                        Dernière mise à jour: <?= date('d/m/Y H:i', strtotime($dashboard_stats['derniere_analyse'])) ?>
                    </span>
                </div>

                <!-- Statistiques principales -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">⚙️</div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $dashboard_stats['equipements_total'] ?></div>
                            <div class="stat-label">Équipements totaux</div>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $dashboard_stats['equipements_operationnels'] ?></div>
                            <div class="stat-label">Opérationnels</div>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">🔧</div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $dashboard_stats['equipements_maintenance'] ?></div>
                            <div class="stat-label">En maintenance</div>
                        </div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $dashboard_stats['alertes_actives'] ?></div>
                            <div class="stat-label">Alertes actives</div>
                        </div>
                    </div>
                </div>

                <!-- Métriques de performance -->
                <div class="performance-section">
                    <h3>Indicateurs de performance</h3>
                    <div class="performance-grid">
                        <div class="performance-card">
                            <div class="performance-header">
                                <span class="performance-title">Taux de conformité</span>
                                <span class="performance-value"><?= $dashboard_stats['conformite_rate'] ?>%</span>
                            </div>
                            <div class="performance-bar">
                                <div class="performance-fill" style="width: <?= $dashboard_stats['conformite_rate'] ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="performance-card">
                            <div class="performance-header">
                                <span class="performance-title">Contrôles aujourd'hui</span>
                                <span class="performance-value"><?= $dashboard_stats['controles_jour'] ?></span>
                            </div>
                            <div class="performance-description">
                                Objectif quotidien: 10 contrôles
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertes récentes -->
                <div class="alerts-section">
                    <h3>Alertes récentes</h3>
                    <div class="alerts-list">
                        <?php foreach ($alertes_qualite as $alerte): ?>
                        <div class="alert-item alert-<?= $alerte['severity'] ?>" onclick="navigateToComponent('<?= $alerte['component'] ?>')">
                            <div class="alert-content">
                                <div class="alert-header">
                                    <span class="alert-title"><?= htmlspecialchars($alerte['titre']) ?></span>
                                    <span class="alert-time"><?= date('H:i', strtotime($alerte['timestamp'])) ?></span>
                                </div>
                                <div class="alert-description"><?= htmlspecialchars($alerte['description']) ?></div>
                            </div>
                            <div class="alert-action">
                                <span class="alert-arrow">→</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Accès rapides aux composants -->
                <div class="quick-access-section">
                    <h3>Accès rapide</h3>
                    <div class="quick-access-grid">
                        <?php foreach ($qualite_config['components'] as $comp_id => $comp): ?>
                            <?php if ($comp['active']): ?>
                            <div class="quick-access-card" onclick="navigateToComponent('<?= $comp_id ?>')">
                                <div class="quick-access-icon"><?= $comp['icon'] ?></div>
                                <div class="quick-access-name"><?= $comp['name'] ?></div>
                                <div class="quick-access-arrow">→</div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'component' && $component): ?>
            <!-- Affichage d'un composant spécifique -->
            <div class="component-section">
                <?php
                if (isset($qualite_config['components'][$component]) && 
                    $qualite_config['components'][$component]['active'] &&
                    file_exists($qualite_config['components'][$component]['file'])):
                    
                    include $qualite_config['components'][$component]['file'];
                else:
                    echo '<div class="error-message">';
                    echo '<h3>🚫 Composant non trouvé</h3>';
                    echo '<p>Le composant demandé n\'existe pas ou n\'est pas encore implémenté.</p>';
                    echo '<button class="btn btn-primary" onclick="navigateToComponent(\'dashboard\')">Retour au dashboard</button>';
                    echo '</div>';
                endif;
                ?>
            </div>

        <?php else: ?>
            <!-- Page d'erreur -->
            <div class="error-section">
                <h2>🚫 Action non reconnue</h2>
                <p>L'action demandée n'est pas valide.</p>
                <button class="btn btn-primary" onclick="navigateToComponent('dashboard')">
                    Retour au dashboard
                </button>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer du module -->
    <footer class="module-footer">
        <div class="footer-content">
            <div class="footer-info">
                <span>Module <?= $qualite_config['module_name'] ?> - <?= $qualite_config['version'] ?></span>
                <?php if (defined('BUILD_NUMBER')): ?>
                <span class="build-info">Build <?= BUILD_NUMBER ?></span>
                <?php endif; ?>
            </div>
            <div class="footer-links">
                <a href="../../public/index.php">Retour portail</a>
                <a href="#" onclick="exportModule()">Export données</a>
                <a href="#" onclick="showHelp()">Aide</a>
            </div>
        </div>
        <div class="footer-copyright">
            © <?= defined('COPYRIGHT_YEAR') ? COPYRIGHT_YEAR : date('Y') ?> Guldagil - Tous droits réservés
        </div>
    </footer>

    <!-- JavaScript du module -->
    <script src="assets/qualite.js"></script>
    <script>
        // Navigation entre composants
        function navigateToComponent(component) {
            if (component === 'dashboard') {
                window.location.href = 'index.php';
            } else {
                window.location.href = `index.php?action=component&component=${component}`;
            }
        }

        // Actions principales
        function exportModule() {
            alert('📊 Export global du module Qualité\n\nGénérera un rapport complet de tous les équipements et analyses.');
        }

        function nouvelleAnalyse() {
            alert('🧪 Nouvelle analyse\n\nOuvrira l\'assistant de création d\'analyse.');
        }

        function showHelp() {
            alert('❓ Aide du module Qualité\n\nDocumentation et guide d\'utilisation.');
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔬 Module Qualité initialisé');
            
            // Animation d'entrée
            const elements = document.querySelectorAll('.stat-card, .alert-item, .quick-access-card');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    el.style.transition = 'all 0.5s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>

<?php
/**
 * public/index.php - Portail principal Guldagil
 * Chemin: /public/index.php
 * Version: 0.5 beta - Architecture MVC modulaire
 * Inclut: Module contrôle qualité intégré
 */

// Chargement de la configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/version.php';

// Configuration des modules disponibles
define('MODULES', [
    'calculateur' => [
        'name' => 'Calculateur frais de port',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'icon' => '🧮',
        'color' => 'primary',
        'path' => 'calculateur/',
        'enabled' => true,
        'features' => ['Comparaison transporteurs', 'Calcul instantané', 'Options avancées']
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Déclarations et suivi des marchandises dangereuses',
        'icon' => '⚠️',
        'color' => 'warning',
        'path' => 'adr/',
        'enabled' => true,
        'features' => ['Déclarations ADR', 'Base de données produits', 'Export PDF']
    ],
    'controle-qualite' => [
        'name' => 'Contrôle qualité',
        'description' => 'Contrôle et validation des équipements',
        'icon' => '🔍',
        'color' => 'success',
        'path' => 'controle-qualite/',
        'enabled' => true,
        'features' => ['Pompes doseuses', 'Rapports PDF', 'Checklist équipements']
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et gestion du système',
        'icon' => '⚙️',
        'color' => 'secondary',
        'path' => 'admin/',
        'enabled' => true,
        'features' => ['Gestion tarifs', 'Import/Export', 'Maintenance']
    ]
]);

// Authentification simplifiée (développement)
session_start();
$auth_enabled = false; // Désactivé pour le développement
$user_info = [
    'username' => 'Développeur',
    'role' => 'admin',
    'authenticated' => true
];

// Fonction pour logger les erreurs
function logError($message, $context = []) {
    if (DEBUG) {
        error_log("Portal Error: $message " . json_encode($context));
    }
}

// Récupération des statistiques
try {
    // Stats des calculs (table logs ou simulation)
    $calculations_today = 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM gul_adr_expeditions WHERE DATE(date_creation) = CURDATE()");
        $calculations_today = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $calculations_today = rand(45, 120); // Simulation pour la démo
    }
    
    // Stats contrôle qualité
    $controles_today = 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM gul_controles_qualite WHERE DATE(date_controle) = CURDATE()");
        $controles_today = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $controles_today = rand(5, 15); // Simulation pour la démo
    }
    
    // Stats globales
    $stats = [
        'calculations_today' => $calculations_today,
        'controles_today' => $controles_today,
        'modules_available' => count(array_filter(MODULES, fn($m) => $m['enabled'])),
        'system_status' => 'operational',
        'total_activity' => $calculations_today + $controles_today
    ];
} catch (Exception $e) {
    $stats = [
        'calculations_today' => 0,
        'controles_today' => 0,
        'modules_available' => 4,
        'system_status' => 'partial',
        'total_activity' => 0
    ];
    logError('Stats retrieval failed', ['error' => $e->getMessage()]);
}

// Récupération des alertes système
$system_alerts = [];
try {
    // Vérifier les tâches de maintenance
    if (date('H') >= 2 && date('H') <= 4) {
        $system_alerts[] = [
            'type' => 'info',
            'message' => 'Maintenance programmée en cours (02h-04h)'
        ];
    }
    
    // Vérifier l'espace disque (simulation)
    if (rand(1, 10) > 8) {
        $system_alerts[] = [
            'type' => 'warning',
            'message' => 'Espace disque faible sur le serveur de fichiers'
        ];
    }
} catch (Exception $e) {
    logError('Alerts check failed', ['error' => $e->getMessage()]);
}

// Version et build
$version_info = getVersionInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Guldagil - Calculateur et Gestion Transport</title>
    
    <!-- Preconnect optimisations -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS consolidé -->
    <link rel="stylesheet" href="assets/css/app.min.css">
    <link rel="stylesheet" href="assets/css/modules/calculateur/variables.css">
    
    <!-- Meta tags -->
    <meta name="description" content="Portail Guldagil - Calculateur de frais de port, gestion ADR, contrôle qualité et suivi des expéditions">
    <meta name="keywords" content="transport, frais de port, ADR, contrôle qualité, expédition, Guldagil">
    <meta name="author" content="<?= APP_AUTHOR ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body>
    <!-- Header principal -->
    <header class="portal-header">
        <div class="header-container">
            <div class="header-brand">
                <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="portal-logo">
                <div class="brand-info">
                    <h1 class="portal-title"><?= APP_NAME ?></h1>
                    <p class="portal-subtitle"><?= APP_DESCRIPTION ?></p>
                </div>
            </div>
            
            <div class="header-status">
                <div class="status-indicator <?= $stats['system_status'] ?>">
                    <span class="status-dot"></span>
                    <span class="status-text">
                        <?= $stats['system_status'] === 'operational' ? 'Système opérationnel' : 'Fonctionnement partiel' ?>
                    </span>
                </div>
                
                <?php if ($auth_enabled): ?>
                <div class="user-profile">
                    <span class="user-avatar">👤</span>
                    <span class="user-name"><?= htmlspecialchars($user_info['username']) ?></span>
                </div>
                <?php else: ?>
                <div class="dev-mode">
                    <span class="dev-indicator">👨‍💻</span>
                    <span class="dev-text">Mode développement</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Navigation principale -->
    <nav class="portal-navigation">
        <div class="nav-container">
            <div class="nav-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['total_activity'] ?></span>
                    <span class="stat-label">activités aujourd'hui</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['modules_available'] ?></span>
                    <span class="stat-label">modules actifs</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['controles_today'] ?></span>
                    <span class="stat-label">contrôles qualité</span>
                </div>
            </div>
            
            <!-- Recherche rapide -->
            <div class="nav-search">
                <form action="#" method="get" onsubmit="handleQuickSearch(event)">
                    <input type="text" id="quickSearchInput" placeholder="Recherche rapide..." autocomplete="off">
                    <button type="submit">🔍</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Alertes système -->
    <?php if (!empty($system_alerts)): ?>
    <div class="system-alerts">
        <div class="alerts-container">
            <?php foreach ($system_alerts as $alert): ?>
            <div class="alert alert-<?= $alert['type'] ?>">
                <span class="alert-icon">
                    <?= $alert['type'] === 'warning' ? '⚠️' : ($alert['type'] === 'error' ? '❌' : 'ℹ️') ?>
                </span>
                <span class="alert-message"><?= htmlspecialchars($alert['message']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contenu principal -->
    <main class="portal-main">
        <div class="portal-container">
            
            <!-- Section modules principaux -->
            <section class="modules-section">
                <h2 class="section-title">Modules disponibles</h2>
                
                <div class="modules-grid">
                    
                    <?php foreach (MODULES as $key => $module): ?>
                        <?php if ($module['enabled']): ?>
                        <div class="module-card <?= $module['color'] ?>-module">
                            <div class="module-header">
                                <div class="module-icon"><?= $module['icon'] ?></div>
                                <div class="module-info">
                                    <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                                    <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                                </div>
                            </div>
                            
                            <div class="module-features">
                                <?php foreach ($module['features'] as $feature): ?>
                                <span class="feature-tag">✓ <?= htmlspecialchars($feature) ?></span>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="module-actions">
                                <a href="<?= $module['path'] ?>" class="btn btn-<?= $module['color'] ?>">
                                    <span><?= $module['icon'] ?></span>
                                    Accéder au module
                                </a>
                                <?php if ($key === 'calculateur'): ?>
                                <a href="<?= $module['path'] ?>?demo=1" class="btn btn-secondary">
                                    <span>🎮</span>
                                    Mode démo
                                </a>
                                <?php elseif ($key === 'controle-qualite'): ?>
                                <a href="<?= $module['path'] ?>?controller=pompe-doseuse&action=nouveau" class="btn btn-secondary">
                                    <span>➕</span>
                                    Nouveau contrôle
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="module-stats">
                                <?php if ($key === 'calculateur'): ?>
                                <div class="stat">
                                    <span class="stat-number"><?= $stats['calculations_today'] ?></span>
                                    <span class="stat-text">calculs aujourd'hui</span>
                                </div>
                                <?php elseif ($key === 'controle-qualite'): ?>
                                <div class="stat">
                                    <span class="stat-number"><?= $stats['controles_today'] ?></span>
                                    <span class="stat-text">contrôles aujourd'hui</span>
                                </div>
                                <?php elseif ($key === 'adr'): ?>
                                <div class="stat">
                                    <span class="stat-number"><?= rand(3, 12) ?></span>
                                    <span class="stat-text">déclarations en cours</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                </div>
            </section>
            
            <!-- Section activité récente -->
            <section class="activity-section">
                <h2 class="section-title">Activité récente</h2>
                
                <div class="activity-grid">
                    
                    <!-- Activité calculateur -->
                    <div class="activity-card">
                        <div class="activity-header">
                            <h3>🧮 Calculs récents</h3>
                            <a href="calculateur/" class="activity-link">Voir tous</a>
                        </div>
                        <div class="activity-content">
                            <div class="activity-stat">
                                <span class="stat-big"><?= $stats['calculations_today'] ?></span>
                                <span class="stat-label">calculs aujourd'hui</span>
                            </div>
                            <div class="activity-details">
                                <p>Transporteurs les plus utilisés :</p>
                                <ul>
                                    <li>Heppner (<?= rand(40, 60) ?>%)</li>
                                    <li>XPO (<?= rand(25, 35) ?>%)</li>
                                    <li>Kuehne+Nagel (<?= rand(10, 20) ?>%)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activité contrôle qualité -->
                    <div class="activity-card">
                        <div class="activity-header">
                            <h3>🔍 Contrôles qualité</h3>
                            <a href="controle-qualite/" class="activity-link">Voir tous</a>
                        </div>
                        <div class="activity-content">
                            <div class="activity-stat">
                                <span class="stat-big"><?= $stats['controles_today'] ?></span>
                                <span class="stat-label">contrôles aujourd'hui</span>
                            </div>
                            <div class="activity-details">
                                <p>Types d'équipements :</p>
                                <ul>
                                    <li>Pompes doseuses (<?= rand(70, 90) ?>%)</li>
                                    <li>Compteurs (<?= rand(5, 15) ?>%)</li>
                                    <li>Autres équipements (<?= rand(5, 15) ?>%)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activité ADR -->
                    <div class="activity-card">
                        <div class="activity-header">
                            <h3>⚠️ Gestion ADR</h3>
                            <a href="adr/" class="activity-link">Voir tous</a>
                        </div>
                        <div class="activity-content">
                            <div class="activity-stat">
                                <span class="stat-big"><?= rand(15, 35) ?></span>
                                <span class="stat-label">expéditions ADR</span>
                            </div>
                            <div class="activity-details">
                                <p>Statut des déclarations :</p>
                                <ul>
                                    <li>Validées (<?= rand(80, 95) ?>%)</li>
                                    <li>En attente (<?= rand(3, 10) ?>%)</li>
                                    <li>À corriger (<?= rand(2, 7) ?>%)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </section>
            
            <!-- Section liens rapides -->
            <section class="quick-links-section">
                <h2 class="section-title">Accès rapides</h2>
                
                <div class="quick-links-grid">
                    <a href="calculateur/" class="quick-link">
                        <span class="quick-link-icon">🚀</span>
                        <span class="quick-link-text">Nouveau calcul</span>
                    </a>
                    <a href="controle-qualite/?controller=pompe-doseuse&action=nouveau" class="quick-link">
                        <span class="quick-link-icon">🔍</span>
                        <span class="quick-link-text">Nouveau contrôle</span>
                    </a>
                    <a href="adr/declaration/create.php" class="quick-link">
                        <span class="quick-link-icon">⚠️</span>
                        <span class="quick-link-text">Déclaration ADR</span>
                    </a>
                    <a href="admin/" class="quick-link">
                        <span class="quick-link-icon">⚙️</span>
                        <span class="quick-link-text">Administration</span>
                    </a>
                    <a href="admin/rates.php" class="quick-link">
                        <span class="quick-link-icon">💰</span>
                        <span class="quick-link-text">Gestion tarifs</span>
                    </a>
                    <a href="admin/import-export.php" class="quick-link">
                        <span class="quick-link-icon">📊</span>
                        <span class="quick-link-text">Import/Export</span>
                    </a>
                </div>
            </section>
            
        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
        <div class="footer-container">
            <div class="footer-info">
                <p>&copy; <?= COPYRIGHT_YEAR ?> Guldagil - Tous droits réservés</p>
                <p>Développé par <?= APP_AUTHOR ?></p>
            </div>
            
            <div class="footer-version">
                <?= renderVersionFooter() ?>
            </div>
            
            <div class="footer-links">
                <a href="admin/">Administration</a>
                <a href="admin/maintenance.php">Maintenance</a>
                <?php if (DEBUG): ?>
                <a href="?debug=1" style="color: #ff6b6b;">Mode Debug</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/app.min.js"></script>
    <script>
        // Configuration globale
        window.PortalConfig = {
            version: '<?= APP_VERSION ?>',
            build: '<?= BUILD_NUMBER ?>',
            debug: <?= DEBUG ? 'true' : 'false' ?>,
            modules: <?= json_encode(array_keys(array_filter(MODULES, fn($m) => $m['enabled']))) ?>
        };
        
        // Initialisation du portail
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🏠 Portail Guldagil v' + PortalConfig.version + ' initialisé');
            
            // Initialiser les modules du portail
            if (typeof Portal !== 'undefined') {
                Portal.init();
            }
            
            // Vérifier la santé des modules
            setTimeout(() => {
                if (typeof Portal !== 'undefined' && Portal.checkModulesHealth) {
                    Portal.checkModulesHealth();
                }
            }, 2000);
        });
        
        // Fonction de recherche rapide
        function handleQuickSearch(event) {
            if (event) event.preventDefault();
            
            const input = document.getElementById('quickSearchInput');
            const query = input.value.trim();
            
            if (query.length < 2) {
                alert('Veuillez saisir au moins 2 caractères');
                return false;
            }
            
            // Simulation de recherche
            console.log('🔍 Recherche:', query);
            alert(`Recherche pour "${query}" - Fonction en développement`);
            
            return false;
        }
        
        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Ctrl+K pour la recherche
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.getElementById('quickSearchInput').focus();
            }
            
            // Escape pour vider la recherche
            if (e.key === 'Escape') {
                const input = document.getElementById('quickSearchInput');
                if (input === document.activeElement) {
                    input.value = '';
                    input.blur();
                }
            }
        });
    </script>
</body>
</html>

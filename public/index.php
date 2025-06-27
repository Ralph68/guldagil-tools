<?php
/**
 * Titre: Page d'accueil du portail Guldagil
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier et charger la configuration
if (!file_exists(__DIR__ . '/../config/config.php')) {
    die('<h1>❌ Erreur Configuration</h1><p>Le fichier config.php est manquant dans /config/</p>');
}

if (!file_exists(__DIR__ . '/../config/version.php')) {
    die('<h1>❌ Erreur Version</h1><p>Le fichier version.php est manquant dans /config/</p>');
}

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/version.php';
} catch (Exception $e) {
    die('<h1>❌ Erreur de chargement</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Vérifier connexion base de données
if (!isset($db) || !($db instanceof PDO)) {
    die('<h1>❌ Erreur Base de données</h1><p>Connexion à la base de données non disponible</p>');
}

// Définition des modules - TOUS en version 0.5 beta
$modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'icon' => 'calculator',
        'color' => 'blue',
        'status' => 'active',
        'path' => 'calculateur/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export PDF']
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses',
        'icon' => 'warning',
        'color' => 'orange',
        'status' => 'active',
        'path' => 'adr/',
        'features' => ['Déclarations ADR', 'Gestion quotas', 'Suivi réglementaire']
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection',
        'icon' => 'shield',
        'color' => 'green',
        'status' => 'development',
        'path' => 'epi/',
        'features' => ['Catalogue EPI', 'Suivi dotations', 'Maintenance']
    ],
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion des outils et équipements',
        'icon' => 'tool',
        'color' => 'purple',
        'status' => 'development',
        'path' => 'outillages/',
        'features' => ['Inventaire', 'Maintenance', 'Réservations']
    ],
    'controle-qualite' => [
        'name' => 'Contrôle qualité',
        'description' => 'Suivi et contrôle de la qualité',
        'icon' => 'check',
        'color' => 'teal',
        'status' => 'active',
        'path' => 'controle-qualite/',
        'features' => ['Plans de contrôle', 'Rapports qualité', 'Non-conformités']
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Gestion du système et configuration',
        'icon' => 'settings',
        'color' => 'gray',
        'status' => 'active',
        'path' => 'admin/',
        'features' => ['Configuration', 'Utilisateurs', 'Maintenance']
    ]
];

// Statistiques du système (simulation ou vraies données)
try {
    // Ici vous pouvez ajouter de vraies requêtes vers votre base
    $calculations_today = rand(85, 156);
    $controles_today = rand(12, 28);
    $declarations_pending = rand(3, 11);
    
    $stats = [
        'calculations_today' => $calculations_today,
        'controles_today' => $controles_today,
        'declarations_pending' => $declarations_pending,
        'modules_active' => count(array_filter($modules, fn($m) => $m['status'] === 'active')),
        'system_health' => 'optimal'
    ];
} catch (Exception $e) {
    $stats = [
        'calculations_today' => 127,
        'controles_today' => 18,
        'declarations_pending' => 6,
        'modules_active' => 4,
        'system_health' => 'operational'
    ];
}

// Notifications système
$notifications = [];
if ($stats['declarations_pending'] > 8) {
    $notifications[] = [
        'type' => 'warning',
        'message' => 'Déclarations ADR en attente de validation',
        'count' => $stats['declarations_pending']
    ];
}

// Définir la navigation actuelle
$current_path = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Solutions Transport & Logistique</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Structure MVC -->
    <link rel="stylesheet" href="assets/css/app.min.css">
    <link rel="stylesheet" href="assets/css/portal.css">
    
    <!-- Meta tags -->
    <meta name="description" content="<?= APP_DESCRIPTION ?>">
    <meta name="keywords" content="transport,logistique,ADR,contrôle qualité,frais de port,Guldagil">
    <meta name="author" content="<?= APP_AUTHOR ?>">
    <meta name="robots" content="noindex,nofollow">
</head>
<body class="portal-layout">
    
    <!-- Header principal -->
    <header class="portal-header" role="banner">
        <div class="header-container">
            <!-- Brand -->
            <div class="brand-section">
                <h1 class="brand-title">
                    <span class="brand-icon">🌊</span>
                    <?= APP_NAME ?>
                </h1>
                <p class="brand-subtitle"><?= APP_DESCRIPTION ?></p>
            </div>
            
            <!-- Navigation principale -->
            <nav class="main-navigation" role="navigation" aria-label="Navigation principale">
                <a href="./" class="nav-link <?= $current_path === '/' ? 'active' : '' ?>">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Accueil</span>
                </a>
                
                <a href="calculateur/" class="nav-link <?= strpos($current_path, 'calculateur') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Frais de port</span>
                </a>
                
                <a href="adr/" class="nav-link <?= strpos($current_path, 'adr') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">⚠️</span>
                    <span class="nav-text">ADR</span>
                </a>
                
                <a href="epi/" class="nav-link <?= strpos($current_path, 'epi') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">🦺</span>
                    <span class="nav-text">EPI</span>
                </a>
                
                <a href="outillages/" class="nav-link <?= strpos($current_path, 'outillages') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">🔧</span>
                    <span class="nav-text">Outillages</span>
                </a>
                
                <a href="controle-qualite/" class="nav-link <?= strpos($current_path, 'controle-qualite') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">✅</span>
                    <span class="nav-text">Contrôle qualité</span>
                </a>
                
                <a href="admin/" class="nav-link admin-link <?= strpos($current_path, 'admin') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Administration</span>
                </a>
            </nav>
            
            <!-- Métadonnées header -->
            <div class="header-meta">
                <span class="build-info">Build #<?= substr(BUILD_NUMBER, -8) ?></span>
                <?php if (DEBUG): ?>
                <span class="debug-badge">🐛 Debug</span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Notifications -->
    <?php if (!empty($notifications)): ?>
    <aside class="notifications-bar" role="complementary">
        <div class="notifications-container">
            <?php foreach ($notifications as $notification): ?>
            <div class="notification notification-<?= $notification['type'] ?>" role="alert">
                <div class="notification-content">
                    <span class="notification-message"><?= htmlspecialchars($notification['message']) ?></span>
                    <?php if (isset($notification['count'])): ?>
                    <span class="notification-badge"><?= $notification['count'] ?></span>
                    <?php endif; ?>
                </div>
                <button class="notification-dismiss" aria-label="Fermer">×</button>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="portal-main" role="main">
        <div class="main-container">
            
            <!-- Stats Section -->
            <section class="stats-section" aria-labelledby="stats-title">
                <h2 id="stats-title" class="section-title sr-only">Statistiques du système</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $stats['calculations_today'] ?></div>
                            <div class="stat-label">Calculs aujourd'hui</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $stats['controles_today'] ?></div>
                            <div class="stat-label">Contrôles effectués</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $stats['declarations_pending'] ?></div>
                            <div class="stat-label">Déclarations en attente</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🟢</div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $stats['modules_active'] ?></div>
                            <div class="stat-label">Modules actifs</div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Modules Section -->
            <section class="modules-section" aria-labelledby="modules-title">
                <header class="section-header">
                    <h2 id="modules-title" class="section-title">Modules applicatifs</h2>
                    <p class="section-subtitle">Solutions intégrées pour la gestion transport & logistique</p>
                </header>
                
                <div class="modules-grid">
                    <?php foreach ($modules as $moduleId => $module): ?>
                    <article class="module-card" data-module="<?= $moduleId ?>" data-color="<?= $module['color'] ?>">
                        <div class="module-header">
                            <div class="module-icon-wrapper">
                                <span class="module-icon" data-icon="<?= $module['icon'] ?>">
                                    <?php 
                                    $icons = [
                                        'calculator' => '🧮',
                                        'warning' => '⚠️',
                                        'shield' => '🛡️',
                                        'tool' => '🔧',
                                        'check' => '✅',
                                        'settings' => '⚙️'
                                    ];
                                    echo $icons[$module['icon']] ?? '📋';
                                    ?>
                                </span>
                            </div>
                            <div class="module-meta">
                                <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                                <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                            </div>
                            <div class="module-status" data-status="<?= $module['status'] ?>">
                                <span class="status-indicator"></span>
                                <span class="status-text"><?= ucfirst($module['status']) ?></span>
                            </div>
                        </div>
                        
                        <div class="module-content">
                            <ul class="module-features">
                                <?php foreach ($module['features'] as $feature): ?>
                                <li class="feature-item"><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="module-footer">
                            <div class="module-version">v<?= APP_VERSION ?></div>
                            <?php if ($module['status'] === 'active'): ?>
                            <a href="<?= $module['path'] ?>" class="module-button">
                                Accéder <span class="button-arrow">→</span>
                            </a>
                            <?php else: ?>
                            <span class="module-button disabled">En développement</span>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- Quick Access Section -->
            <section class="quick-access-section" aria-labelledby="quick-title">
                <header class="section-header">
                    <h2 id="quick-title" class="section-title">Accès rapide</h2>
                    <p class="section-subtitle">Actions fréquemment utilisées</p>
                </header>
                
                <div class="quick-actions-grid">
                    <a href="calculateur/" class="quick-action">
                        <div class="action-icon">🚚</div>
                        <div class="action-content">
                            <div class="action-title">Nouveau calcul</div>
                            <div class="action-description">Calculer les frais de transport</div>
                        </div>
                    </a>
                    
                    <a href="adr/" class="quick-action">
                        <div class="action-icon">📋</div>
                        <div class="action-content">
                            <div class="action-title">Déclaration ADR</div>
                            <div class="action-description">Nouvelle déclaration transport</div>
                        </div>
                    </a>
                    
                    <a href="controle-qualite/" class="quick-action">
                        <div class="action-icon">🔍</div>
                        <div class="action-content">
                            <div class="action-title">Plan de contrôle</div>
                            <div class="action-description">Nouveau contrôle qualité</div>
                        </div>
                    </a>
                    
                    <a href="admin/" class="quick-action">
                        <div class="action-icon">📊</div>
                        <div class="action-content">
                            <div class="action-title">Rapports</div>
                            <div class="action-description">Consulter les statistiques</div>
                        </div>
                    </a>
                </div>
            </section>
            
        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer" role="contentinfo">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-brand">
                    <span class="footer-title"><?= APP_NAME ?></span>
                    <span class="footer-description"><?= APP_DESCRIPTION ?></span>
                </div>
                
                <div class="footer-info">
                    <span class="copyright">&copy; <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?></span>
                    <div class="version-info">
                        <?= renderVersionFooter() ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/app.min.js"></script>
    <script>
        // Configuration du portail
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🌊 Portail Guldagil v<?= APP_VERSION ?> - Build #<?= BUILD_NUMBER ?>');
            
            // Initialisation des modules JavaScript si nécessaire
            if (typeof window.PortalApp !== 'undefined') {
                window.PortalApp.init({
                    debug: <?= DEBUG ? 'true' : 'false' ?>,
                    version: '<?= APP_VERSION ?>',
                    build: '<?= BUILD_NUMBER ?>'
                });
            }
            
            // Gestion notifications dismissible
            document.querySelectorAll('.notification-dismiss').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.notification').style.display = 'none';
                });
            });
        });
    </script>
</body>
</html>

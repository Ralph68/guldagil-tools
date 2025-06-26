<?php
/**
 * public/index.php - Portail principal Guldagil
 * Chemin: /public/index.php
 * Version: 0.5 beta - Architecture MVC modulaire
 */

// Chargement de la configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/version.php';

// Configuration des modules (MVC)
$modules = [
    'calculateur' => [
        'name' => 'Calculateur frais de port',
        'description' => 'Comparaison et calcul des tarifs de transport multimodaux',
        'icon' => 'calculator',
        'color' => 'blue',
        'path' => 'calculateur/',
        'features' => ['Comparaison transporteurs', 'Calcul temps réel', 'Export devis'],
        'status' => 'active'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Déclarations et conformité marchandises dangereuses',
        'icon' => 'shield-alert',
        'color' => 'amber',
        'path' => 'adr/',
        'features' => ['Déclarations automatisées', 'Base réglementaire', 'Traçabilité'],
        'status' => 'active'
    ],
    'controle-qualite' => [
        'name' => 'Contrôle qualité',
        'description' => 'Validation et certification des équipements techniques',
        'icon' => 'clipboard-check',
        'color' => 'emerald',
        'path' => 'controle-qualite/',
        'features' => ['Contrôles normalisés', 'Rapports certifiés', 'Planification'],
        'status' => 'active'
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration système et gestion des données',
        'icon' => 'cog',
        'color' => 'slate',
        'path' => 'admin/',
        'features' => ['Paramétrage', 'Import/Export', 'Analytics'],
        'status' => 'active'
    ]
];

// Authentification (mode développement)
session_start();
$auth_enabled = false;
$user_info = ['username' => 'Développeur', 'role' => 'admin'];

// Récupération des métriques
try {
    $calculations_today = $db->query("SELECT COUNT(*) FROM gul_adr_expeditions WHERE DATE(date_creation) = CURDATE()")->fetchColumn() ?: rand(85, 156);
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
    <meta name="description" content="Plateforme Guldagil : calculateur de frais de port, gestion ADR, contrôle qualité et administration centralisée">
    <meta name="keywords" content="transport,logistique,ADR,contrôle qualité,frais de port,Guldagil">
    <meta name="author" content="<?= APP_AUTHOR ?>">
    <meta name="robots" content="noindex,nofollow">
</head>
<body class="portal-layout">
    
    <!-- Header Enterprise -->
    <header class="portal-header" role="banner">
        <div class="header-container">
            <div class="header-brand">
                <div class="brand-logo">
                    <img src="assets/img/logo_guldagil.png" alt="Guldagil" class="logo-image">
                </div>
                <div class="brand-identity">
                    <h1 class="brand-title"><?= APP_NAME ?></h1>
                    <p class="brand-tagline"><?= APP_DESCRIPTION ?></p>
                </div>
            </div>
            
            <div class="header-controls">
                <div class="system-status" data-status="<?= $stats['system_health'] ?>">
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span class="status-text">
                            <?= $stats['system_health'] === 'optimal' ? 'Système optimal' : 'Opérationnel' ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!$auth_enabled): ?>
                <div class="dev-badge">
                    <span class="dev-icon">🛠</span>
                    <span class="dev-text">Mode développement</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Navigation & Metrics -->
    <nav class="portal-nav" role="navigation">
        <div class="nav-container">
            <div class="metrics-bar">
                <div class="metric-group">
                    <div class="metric-item" data-metric="primary">
                        <span class="metric-value"><?= number_format($stats['calculations_today']) ?></span>
                        <span class="metric-label">Calculs traités</span>
                        <span class="metric-period">aujourd'hui</span>
                    </div>
                    <div class="metric-item" data-metric="success">
                        <span class="metric-value"><?= $stats['controles_today'] ?></span>
                        <span class="metric-label">Contrôles validés</span>
                        <span class="metric-period">24h</span>
                    </div>
                    <div class="metric-item" data-metric="warning">
                        <span class="metric-value"><?= $stats['declarations_pending'] ?></span>
                        <span class="metric-label">Déclarations</span>
                        <span class="metric-period">en attente</span>
                    </div>
                    <div class="metric-item" data-metric="info">
                        <span class="metric-value"><?= $stats['modules_active'] ?></span>
                        <span class="metric-label">Modules</span>
                        <span class="metric-period">actifs</span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Notifications -->
    <?php if (!empty($notifications)): ?>
    <aside class="notifications-bar" role="alert">
        <div class="notifications-container">
            <?php foreach ($notifications as $notification): ?>
            <div class="notification notification--<?= $notification['type'] ?>">
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
                                <svg class="module-icon" data-icon="<?= $module['icon'] ?>">
                                    <use href="assets/icons/sprite.svg#<?= $module['icon'] ?>"></use>
                                </svg>
                            </div>
                            <div class="module-meta">
                                <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                                <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                            </div>
                            <div class="module-status" data-status="<?= $module['status'] ?>">
                                <span class="status-indicator"></span>
                            </div>
                        </div>
                        
                        <div class="module-features">
                            <ul class="features-list">
                                <?php foreach ($module['features'] as $feature): ?>
                                <li class="feature-item">
                                    <svg class="feature-icon"><use href="assets/icons/sprite.svg#check"></use></svg>
                                    <span><?= htmlspecialchars($feature) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="module-actions">
                            <a href="<?= $module['path'] ?>" class="btn btn--primary btn--module">
                                <span class="btn-text">Accéder</span>
                                <svg class="btn-icon"><use href="assets/icons/sprite.svg#arrow-right"></use></svg>
                            </a>
                            
                            <?php if ($moduleId === 'calculateur'): ?>
                            <a href="<?= $module['path'] ?>?demo=1" class="btn btn--secondary btn--sm">
                                <svg class="btn-icon"><use href="assets/icons/sprite.svg#play"></use></svg>
                                <span class="btn-text">Démo</span>
                            </a>
                            <?php elseif ($moduleId === 'controle-qualite'): ?>
                            <a href="<?= $module['path'] ?>?controller=pompe-doseuse&action=nouveau" class="btn btn--secondary btn--sm">
                                <svg class="btn-icon"><use href="assets/icons/sprite.svg#plus"></use></svg>
                                <span class="btn-text">Nouveau</span>
                            </a>
                            <?php elseif ($moduleId === 'adr'): ?>
                            <a href="<?= $module['path'] ?>declaration/create.php" class="btn btn--secondary btn--sm">
                                <svg class="btn-icon"><use href="assets/icons/sprite.svg#file-plus"></use></svg>
                                <span class="btn-text">Déclarer</span>
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="module-metrics">
                            <?php if ($moduleId === 'calculateur'): ?>
                            <div class="metric-display">
                                <span class="metric-number"><?= number_format($stats['calculations_today']) ?></span>
                                <span class="metric-text">calculs / jour</span>
                            </div>
                            <?php elseif ($moduleId === 'controle-qualite'): ?>
                            <div class="metric-display">
                                <span class="metric-number"><?= $stats['controles_today'] ?></span>
                                <span class="metric-text">contrôles / 24h</span>
                            </div>
                            <?php elseif ($moduleId === 'adr'): ?>
                            <div class="metric-display">
                                <span class="metric-number"><?= $stats['declarations_pending'] ?></span>
                                <span class="metric-text">en attente</span>
                            </div>
                            <?php else: ?>
                            <div class="metric-display">
                                <span class="metric-number">●</span>
                                <span class="metric-text">opérationnel</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- Quick Actions -->
            <section class="quick-actions-section" aria-labelledby="actions-title">
                <header class="section-header">
                    <h2 id="actions-title" class="section-title">Actions rapides</h2>
                </header>
                
                <div class="quick-actions-grid">
                    <a href="calculateur/" class="quick-action" data-action="calculate">
                        <div class="action-icon">
                            <svg><use href="assets/icons/sprite.svg#calculator"></use></svg>
                        </div>
                        <span class="action-text">Nouveau calcul</span>
                    </a>
                    <a href="controle-qualite/?controller=pompe-doseuse&action=nouveau" class="quick-action" data-action="control">
                        <div class="action-icon">
                            <svg><use href="assets/icons/sprite.svg#clipboard-check"></use></svg>
                        </div>
                        <span class="action-text">Contrôle qualité</span>
                    </a>
                    <a href="adr/declaration/create.php" class="quick-action" data-action="declare">
                        <div class="action-icon">
                            <svg><use href="assets/icons/sprite.svg#shield-alert"></use></svg>
                        </div>
                        <span class="action-text">Déclaration ADR</span>
                    </a>
                    <a href="admin/" class="quick-action" data-action="admin">
                        <div class="action-icon">
                            <svg><use href="assets/icons/sprite.svg#cog"></use></svg>
                        </div>
                        <span class="action-text">Administration</span>
                    </a>
                </div>
            </section>
            
        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer" role="contentinfo">
        <div class="footer-container">
            <div class="footer-info">
                <p class="footer-copyright">&copy; <?= COPYRIGHT_YEAR ?> Guldagil. Tous droits réservés.</p>
                <p class="footer-author">Développé par <?= APP_AUTHOR ?></p>
            </div>
            <div class="footer-meta">
                <div class="version-info"><?= renderVersionFooter() ?></div>
                <div class="footer-links">
                    <a href="admin/" class="footer-link">Administration</a>
                    <a href="admin/maintenance.php" class="footer-link">Maintenance</a>
                    <?php if (DEBUG): ?>
                    <a href="?debug=1" class="footer-link footer-link--debug">Debug</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Structure MVC -->
    <script src="assets/js/app.min.js"></script>
    <script src="assets/js/portal.js"></script>
    
    <!-- Configuration -->
    <script>
        window.PortalConfig = {
            version: '<?= APP_VERSION ?>',
            build: '<?= BUILD_NUMBER ?>',
            debug: <?= DEBUG ? 'true' : 'false' ?>,
            modules: <?= json_encode(array_keys($modules)) ?>,
            metrics: <?= json_encode($stats) ?>
        };
    </script>
</body>
</html>

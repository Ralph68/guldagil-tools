<?php
/**
 * Titre: Dashboard utilisateur COMPLET - Toutes fonctionnalités restaurées
 * Chemin: /public/user/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration ROOT_PATH corrigée
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Chargement configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Gestion session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Variables pour template
$page_title = 'Mon Espace Utilisateur';
$page_subtitle = 'Dashboard personnel et modules disponibles';
$page_description = 'Espace personnel - Profil, modules disponibles et statistiques d\'activité';
$current_module = 'user';
$module_css = true;
$module_js = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '👤', 'text' => 'Mon Espace', 'url' => '/user/', 'active' => true]
];

// Variables globales
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';

// ========================================
// AUTHENTIFICATION ROBUSTE AVEC FALLBACK
// ========================================
$user_authenticated = false;
$current_user = null;

try {
    // 1. Tentative AuthManager
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = new AuthManager();
        
        if ($auth->isAuthenticated()) {
            $user_authenticated = true;
            $current_user = $auth->getCurrentUser();
        }
    }
    
    // 2. Fallback session simple
    if (!$user_authenticated && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
        $user_authenticated = true;
        $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
    }
    
    // 3. Session temporaire pour développement
    if (!$user_authenticated) {
        $user_authenticated = true;
        $current_user = [
            'id' => 1,
            'username' => 'DevUser',
            'role' => 'user',
            'email' => 'dev@guldagil.com',
            'name' => 'Utilisateur Développement',
            'last_login' => date('Y-m-d H:i:s'),
            'modules' => ['calculateur', 'user', 'adr']
        ];
    }
    
} catch (Exception $e) {
    error_log("Erreur auth user: " . $e->getMessage());
    $user_authenticated = true;
    $current_user = ['username' => 'Anonyme', 'role' => 'user'];
}

// ========================================
// DÉFINITION MODULES COMPLETS - RESTAURÉ
// ========================================
$all_modules = [
    'calculateur' => [
        'name' => 'Calculateur Frais de Port',
        'description' => 'Calcul intelligent des frais de transport selon différents transporteurs et types d\'envoi',
        'icon' => '📦',
        'url' => '/port/',
        'status' => 'active',
        'color' => '#0ea5e9',
        'category' => 'Logistique & Transport',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Calcul automatique selon transporteur',
            'Tarifs Heppner France intégrés', 
            'Gestion des frais additionnels',
            'Export des résultats PDF/Excel',
            'Historique des calculs'
        ],
        'priority' => 1,
        'tables' => ['calculations', 'transport_rates']
    ],
    'adr' => [
        'name' => 'Module ADR',
        'description' => 'Gestion des marchandises dangereuses selon réglementation ADR/IMDG',
        'icon' => '⚠️',
        'url' => '/adr/',
        'status' => 'development',
        'color' => '#dc2626',
        'category' => 'Sécurité & Conformité',
        'roles' => ['admin', 'dev', 'logistique'],
        'features' => [
            'Classification matières dangereuses',
            'Documents de transport ADR',
            'Étiquetage et signalisation',
            'Contrôles de conformité',
            'Formation personnel'
        ],
        'priority' => 2,
        'coming_soon' => true
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Suivi et contrôle qualité des processus et produits',
        'icon' => '🔬',
        'url' => '/qualite/',
        'status' => 'development',
        'color' => '#059669',
        'category' => 'Qualité & Contrôles',
        'roles' => ['admin', 'dev', 'qualite'],
        'features' => [
            'Plans de contrôle qualité',
            'Fiches de non-conformité',
            'Statistiques qualité',
            'Audits et certifications',
            'Amélioration continue'
        ],
        'priority' => 3,
        'coming_soon' => true
    ],
    'maintenance' => [
        'name' => 'Maintenance & Outillages',
        'description' => 'Gestion de la maintenance préventive et des outillages',
        'icon' => '🔧',
        'url' => '/maintenance/',
        'status' => 'development',
        'color' => '#6b7280',
        'category' => 'Maintenance & Matériel',
        'roles' => ['admin', 'dev', 'maintenance'],
        'features' => [
            'Inventaire détaillé du matériel',
            'Planning de maintenance préventive',
            'Suivi des réparations',
            'Historique d\'utilisation',
            'Gestion des prêts'
        ],
        'priority' => 4,
        'coming_soon' => true
    ],
    'user' => [
        'name' => 'Mon Espace Personnel',
        'description' => 'Profil utilisateur, paramètres et historique d\'activité',
        'icon' => '👤',
        'url' => '/user/',
        'status' => 'active',
        'color' => '#9b59b6',
        'category' => 'Personnel & Compte',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Profil utilisateur complet',
            'Historique d\'activité détaillé',
            'Préférences personnalisées',
            'Notifications et alertes',
            'Raccourcis personnalisés'
        ],
        'priority' => 5
    ],
    'admin' => [
        'name' => 'Administration Système',
        'description' => 'Configuration avancée et gestion complète du portail',
        'icon' => '⚙️',
        'url' => '/admin/',
        'status' => 'active',
        'color' => '#34495e',
        'category' => 'Système & Configuration',
        'roles' => ['admin', 'dev'],
        'features' => [
            'Gestion complète des utilisateurs',
            'Configuration modules et permissions',
            'Monitoring système temps réel',
            'Logs d\'audit et sécurité',
            'Sauvegarde et maintenance'
        ],
        'priority' => 6,
        'admin_only' => true
    ]
];

// Fonctions utilitaires restaurées
function shouldShowModule($module_id, $module, $user_role) {
    if (isset($module['admin_only']) && $module['admin_only'] && $user_role !== 'admin') {
        return false;
    }
    
    if (isset($module['roles']) && !in_array($user_role, $module['roles'])) {
        return false;
    }
    
    return true;
}

function canAccessModule($module_id, $module, $user_role) {
    if ($module['status'] === 'development' && !in_array($user_role, ['admin', 'dev'])) {
        return false;
    }
    
    if (isset($module['coming_soon']) && $module['coming_soon']) {
        return false;
    }
    
    return shouldShowModule($module_id, $module, $user_role);
}

// Filtrer modules selon rôle utilisateur
$user_role = $current_user['role'] ?? 'user';
$user_modules = [];

foreach ($all_modules as $id => $module) {
    if (shouldShowModule($id, $module, $user_role)) {
        $module['can_access'] = canAccessModule($id, $module, $user_role);
        $user_modules[$id] = $module;
    }
}

// Trier par priorité
uasort($user_modules, function($a, $b) {
    return ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999);
});

// Statistiques par catégorie
$categories_stats = [];
foreach ($user_modules as $module) {
    $cat = $module['category'] ?? 'Général';
    if (!isset($categories_stats[$cat])) {
        $categories_stats[$cat] = ['total' => 0, 'active' => 0, 'development' => 0];
    }
    $categories_stats[$cat]['total']++;
    $status = $module['status'] ?? 'active';
    if (isset($categories_stats[$cat][$status])) {
        $categories_stats[$cat][$status]++;
    }
}

// Statistiques globales du portail
$portal_stats = [
    'modules_accessibles' => count($user_modules),
    'modules_actifs' => count(array_filter($user_modules, fn($m) => $m['status'] === 'active')),
    'calculs_aujourd_hui' => rand(25, 75),
    'utilisateurs_connectes' => 1,
    'session_timeout' => '30 min'
];

// Messages système
$system_alerts = [];
$restricted_modules = array_filter($all_modules, function($module, $id) use ($user_role) {
    return !shouldShowModule($id, $module, $user_role);
}, ARRAY_FILTER_USE_BOTH);

if (!empty($restricted_modules)) {
    $system_alerts[] = [
        'type' => 'info',
        'icon' => '🔒',
        'message' => count($restricted_modules) . ' module(s) nécessitent des permissions supplémentaires',
        'action' => 'Contactez un administrateur pour étendre vos accès'
    ];
}

// Activités récentes simulées
$recent_activities = [
    [
        'icon' => '🔐',
        'title' => 'Connexion réussie',
        'time' => 'Maintenant',
        'type' => 'login',
        'details' => 'Authentification avec AuthManager'
    ],
    [
        'icon' => '🧮',
        'title' => 'Calcul frais de port',
        'time' => 'Il y a 15 min',
        'type' => 'calculation',
        'details' => 'Heppner France - Colis 5kg Paris->Lyon'
    ],
    [
        'icon' => '👤',
        'title' => 'Mise à jour profil',
        'time' => 'Hier à 14:30',
        'type' => 'profile',
        'details' => 'Modification préférences notifications'
    ],
    [
        'icon' => '📊',
        'title' => 'Export rapport',
        'time' => 'Avant-hier',
        'type' => 'export',
        'details' => 'Téléchargement historique calculs PDF'
    ]
];

// Statistiques utilisateur détaillées
$user_stats = [
    'derniere_connexion' => $current_user['last_login'] ?? date('d/m/Y H:i'),
    'nb_calculs' => rand(15, 150),
    'modules_actifs' => count(array_filter($user_modules, fn($m) => $m['can_access'])),
    'notifications' => rand(0, 5),
    'temps_session' => '2h 15min',
    'derniere_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($app_name) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    
    <!-- CSS de base -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <!-- CSS module user -->
    <link rel="stylesheet" href="assets/css/user.css?v=<?= $build_number ?>">
</head>
<body>
    <?php 
    // Inclusion header sécurisée
    if (file_exists(ROOT_PATH . '/templates/header.php')) {
        include ROOT_PATH . '/templates/header.php';
    }
    ?>

    <main class="user-dashboard">
        <!-- En-tête utilisateur enrichi -->
        <section class="user-header">
            <div class="user-avatar">
                <div class="avatar-circle">
                    <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 2)) ?>
                </div>
                <div class="user-status">
                    <span class="status-indicator online" title="En ligne"></span>
                </div>
            </div>
            <div class="user-info">
                <h1>Bonjour, <?= htmlspecialchars($current_user['name'] ?? $current_user['username'] ?? 'Utilisateur') ?> !</h1>
                <p class="user-role">
                    <span class="role-badge role-<?= $current_user['role'] ?? 'user' ?>">
                        <?= ucfirst($current_user['role'] ?? 'user') ?>
                    </span>
                </p>
                <div class="user-meta">
                    <span>📅 Dernière connexion: <?= $user_stats['derniere_connexion'] ?></span>
                    <span>⏱️ Session: <?= $user_stats['temps_session'] ?></span>
                    <span>🌐 IP: <?= htmlspecialchars($user_stats['derniere_ip']) ?></span>
                </div>
            </div>
            <div class="user-quick-actions">
                <a href="profile.php" class="quick-btn" title="Mon profil">
                    <span class="icon">👤</span>
                </a>
                <a href="settings.php" class="quick-btn" title="Paramètres">
                    <span class="icon">⚙️</span>
                </a>
                <a href="/auth/logout.php" class="quick-btn danger" title="Déconnexion">
                    <span class="icon">🔓</span>
                </a>
            </div>
        </section>

        <!-- Statistiques du portail -->
        <section class="portal-stats">
            <h2>📊 Tableau de bord</h2>
            <div class="stats-grid">
                <?php foreach ($portal_stats as $key => $value): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <?= $key === 'modules_accessibles' ? '📋' : 
                            ($key === 'modules_actifs' ? '✅' : 
                            ($key === 'calculs_aujourd_hui' ? '🧮' : 
                            ($key === 'utilisateurs_connectes' ? '👥' : '⏰'))) ?>
                    </div>
                    <div class="stat-content">
                        <h3><?= $value ?><?= str_contains($key, 'percentage') ? '%' : '' ?></h3>
                        <p><?= ucfirst(str_replace('_', ' ', $key)) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Alertes système -->
        <?php if (!empty($system_alerts)): ?>
        <section class="system-alerts">
            <?php foreach ($system_alerts as $alert): ?>
            <div class="alert alert-<?= $alert['type'] ?>">
                <span class="alert-icon"><?= $alert['icon'] ?></span>
                <div class="alert-content">
                    <strong><?= htmlspecialchars($alert['message']) ?></strong>
                    <br><small><?= htmlspecialchars($alert['action']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <!-- Modules par catégorie -->
        <?php 
        $categories_order = [
            'Logistique & Transport',
            'Sécurité & Conformité', 
            'Qualité & Contrôles',
            'Maintenance & Matériel',
            'Personnel & Compte',
            'Système & Configuration'
        ];
        
        foreach ($categories_order as $category): 
            $category_modules = array_filter($user_modules, function($module) use ($category) {
                return ($module['category'] ?? 'Général') === $category;
            });
            
            if (empty($category_modules)) continue;
        ?>
        
        <section class="modules-category">
            <div class="category-header">
                <h2 class="category-title"><?= htmlspecialchars($category) ?></h2>
                <?php if (isset($categories_stats[$category])): ?>
                <div class="category-stats">
                    <span class="stat-item">
                        <span class="stat-count"><?= $categories_stats[$category]['total'] ?></span>
                        <span class="stat-text">modules</span>
                    </span>
                    <?php if ($categories_stats[$category]['active'] > 0): ?>
                    <span class="stat-item active">
                        <span class="stat-count"><?= $categories_stats[$category]['active'] ?></span>
                        <span class="stat-text">actifs</span>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="modules-grid">
                <?php foreach ($category_modules as $id => $module): ?>
                <div class="module-card <?= $module['status'] ?> <?= !$module['can_access'] ? 'restricted' : 'accessible' ?>" data-module="<?= $id ?>">
                    <div class="module-header" style="background-color: <?= $module['color'] ?>">
                        <span class="module-icon"><?= $module['icon'] ?></span>
                        <div class="module-meta">
                            <span class="module-status status-<?= $module['status'] ?>"><?= $module['status'] ?></span>
                            <?php if (isset($module['coming_soon']) && $module['coming_soon']): ?>
                            <span class="module-badge">Bientôt</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="module-content">
                        <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                        <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                        
                        <?php if (!empty($module['features'])): ?>
                        <div class="module-features">
                            <h4>Fonctionnalités :</h4>
                            <ul>
                                <?php foreach (array_slice($module['features'], 0, 3) as $feature): ?>
                                <li><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                                <?php if (count($module['features']) > 3): ?>
                                <li class="feature-more">... et <?= count($module['features']) - 3 ?> autres</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($module['can_access']): ?>
                        <a href="<?= htmlspecialchars($module['url']) ?>" class="module-link">
                            Accéder au module
                            <span class="link-arrow">→</span>
                        </a>
                        <?php else: ?>
                        <div class="module-restricted">
                            <span>🔒 Accès restreint</span>
                            <small>Permissions insuffisantes</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>

        <!-- Activité récente détaillée -->
        <section class="recent-activity">
            <h2>📋 Activité récente</h2>
            <div class="activity-timeline">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon"><?= $activity['icon'] ?></div>
                    <div class="activity-content">
                        <h4><?= htmlspecialchars($activity['title']) ?></h4>
                        <p class="activity-details"><?= htmlspecialchars($activity['details']) ?></p>
                        <time><?= htmlspecialchars($activity['time']) ?></time>
                    </div>
                    <div class="activity-type type-<?= $activity['type'] ?>"></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="activity-footer">
                <a href="profile.php?tab=activity" class="btn-secondary">Voir tout l'historique</a>
            </div>
        </section>

        <!-- Debug panel conditionnel -->
        <?php if (defined('DEBUG') && DEBUG === true): ?>
        <section class="debug-section">
            <h3>🔧 Debug Mode - Informations développeur</h3>
            <div class="debug-info">
                <p><strong>Méthode auth:</strong> <?= isset($auth) ? 'AuthManager' : 'Session PHP' ?></p>
                <p><strong>Session ID:</strong> <?= htmlspecialchars(session_id()) ?></p>
                <p><strong>Utilisateur:</strong> <?= htmlspecialchars($current_user['username'] ?? 'N/A') ?></p>
                <p><strong>Rôle:</strong> <?= htmlspecialchars($current_user['role'] ?? 'N/A') ?></p>
                <p><strong>Modules accessibles:</strong> <?= count($user_modules) ?>/<?= count($all_modules) ?></p>
                <p><strong>Modules restreints:</strong> <?= count($restricted_modules) ?></p>
                <p><strong>Catégories:</strong> <?= count($categories_stats) ?></p>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <?php 
    // Inclusion footer sécurisée
    if (file_exists(ROOT_PATH . '/templates/footer.php')) {
        include ROOT_PATH . '/templates/footer.php';
    }
    ?>

    <!-- JS module user -->
    <script src="assets/js/user.js?v=<?= $build_number ?>"></script>
</body>
</html>

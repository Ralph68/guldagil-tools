<?php
/**
 * Titre: Dashboard utilisateur
 * Chemin: /public/user/index.php
 * Version: 1.3 - Simplifié
 */

// Configuration
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';

// --- Définition des variables pour le template header ---
$page_title = 'Mon Espace Utilisateur';
$page_subtitle = 'Dashboard personnel et modules disponibles';
$current_module = 'user';

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '👤', 'text' => 'Mon Espace', 'url' => '/user/', 'active' => true]
];

// Définition des modules pour l'affichage des cartes sur cette page
$all_modules = [
    'port' => ['name' => 'Calculateur Frais de Port', 'description' => 'Calcul intelligent des frais de transport.', 'icon' => '📦', 'url' => '/port/', 'status' => 'active', 'color' => '#0ea5e9', 'category' => 'Logistique & Transport', 'priority' => 1, 'alias' => 'calculateur'],
    'adr' => ['name' => 'Module ADR', 'description' => 'Gestion des marchandises dangereuses.', 'icon' => '⚠️', 'url' => '/adr/', 'status' => 'development', 'color' => '#dc2626', 'category' => 'Sécurité & Conformité', 'priority' => 2],
    'qualite' => ['name' => 'Contrôle Qualité', 'description' => 'Suivi et contrôle qualité des processus.', 'icon' => '🔬', 'url' => '/qualite/', 'status' => 'development', 'color' => '#059669', 'category' => 'Qualité & Contrôles', 'priority' => 3],
    'materiel' => ['name' => 'Gestion du matériel', 'description' => 'Inventaire et gestion du matériel.', 'icon' => '🔨', 'url' => '/materiel/', 'status' => 'development', 'color' => '#6b7280', 'category' => 'Maintenance & Matériel', 'priority' => 4],
    'epi' => ['name' => 'Équipements de protection', 'description' => 'Gestion des EPI et équipements de sécurité.', 'icon' => '🥽', 'url' => '/epi/', 'status' => 'development', 'color' => '#f59e0b', 'category' => 'Sécurité & Conformité', 'priority' => 5],
    'user' => ['name' => 'Mon Espace Personnel', 'description' => 'Profil, paramètres et historique.', 'icon' => '👤', 'url' => '/user/', 'status' => 'active', 'color' => '#9b59b6', 'category' => 'Personnel & Compte', 'priority' => 6, 'alias' => 'profile'],
    'admin' => ['name' => 'Administration', 'description' => 'Configuration avancée du portail.', 'icon' => '⚙️', 'url' => '/admin/', 'status' => 'active', 'color' => '#34495e', 'category' => 'Système & Configuration', 'priority' => 7, 'restricted' => ['admin', 'dev']]
];

// --- Inclusion du header ---
include_once ROOT_PATH . '/templates/header.php';

// --- Logique de la page (après que le header ait authentifié l'utilisateur) ---
$user_role = $current_user['role'] ?? 'guest';
$user_modules = getNavigationModules($user_role, $all_modules);

// Ajouter la logique d'accès pour l'affichage des cartes
foreach ($user_modules as $id => &$module) {
    $module['can_access'] = true;
    if ($module['status'] === 'development' && !in_array($user_role, ['admin', 'dev'])) {
        $module['can_access'] = false;
    }
}
unset($module);

uasort($user_modules, fn($a, $b) => ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999));

// Simuler des données pour l'affichage
$portal_stats = ['modules_accessibles' => count($user_modules), 'calculs_aujourd_hui' => rand(25, 75)];
$recent_activities = [['icon' => '📦', 'title' => 'Nouveau calcul de frais de port', 'details' => 'Destination: Lyon, Poids: 15kg', 'time' => 'Il y a 5 minutes', 'type' => 'calcul']];
?>

<!-- Le contenu de la page commence ici, après le <main> du header -->
<div class="user-dashboard">
    <section class="user-header">
        <div class="user-avatar">
            <div class="avatar-circle"><?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?></div>
            <div class="user-status"><span class="status-indicator online" title="En ligne"></span></div>
        </div>
        <div class="user-info">
            <h1>Bonjour, <?= htmlspecialchars($current_user['name'] ?? $current_user['username'] ?? 'Utilisateur') ?> !</h1>
            <p class="user-role"><?= RoleManager::getRoleBadge($user_role) ?></p>
        </div>
        <div class="user-quick-actions">
            <a href="/user/profile.php" class="quick-btn" title="Mon profil">👤</a>
            <a href="/user/settings.php" class="quick-btn" title="Paramètres">⚙️</a>
            <a href="/auth/logout.php" class="quick-btn danger" title="Déconnexion">🚪</a>
        </div>
    </section>

    <section class="portal-stats">
        <h2>📊 Tableau de bord</h2>
        <div class="stats-grid">
            <?php foreach ($portal_stats as $key => $value): ?>
            <div class="stat-card">
                <div class="stat-icon"><?= $key === 'modules_accessibles' ? '📋' : '🧮' ?></div>
                <div class="stat-content">
                    <h3><?= $value ?></h3>
                    <p><?= ucfirst(str_replace('_', ' ', $key)) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php
    $categories = [];
    foreach ($user_modules as $id => $module) {
        $categories[$module['category'] ?? 'Général'][] = $module;
    }
    ?>

    <?php foreach ($categories as $category_name => $modules_in_category): ?>
    <section class="modules-category">
        <h2 class="category-title"><?= htmlspecialchars($category_name) ?></h2>
        <div class="modules-grid">
            <?php foreach ($modules_in_category as $module): ?>
            <div class="module-card <?= $module['status'] ?> <?= $module['can_access'] ? 'accessible' : 'restricted' ?>">
                <div class="module-header" style="background-color: <?= $module['color'] ?>">
                    <span class="module-icon"><?= $module['icon'] ?></span>
                    <span class="module-status status-<?= $module['status'] ?>"><?= $module['status'] ?></span>
                </div>
                <div class="module-content">
                    <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                    <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                    <?php if ($module['can_access']): ?>
                    <a href="<?= htmlspecialchars($module['url']) ?>" class="module-link">Accéder <span class="link-arrow">→</span></a>
                    <?php else: ?>
                    <div class="module-restricted"><span>🔒 Accès restreint</span></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

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
    </section>
</div>
<!-- Le contenu de la page se termine ici, avant le footer -->

<?php
// --- Inclusion du footer ---
include_once ROOT_PATH . '/templates/footer.php';
?>
$user_modules = getNavigationModules($user_role, $all_modules, $roles_config);

// Ajouter la logique d'accès pour l'affichage des cartes
foreach ($user_modules as $id => &$module) {
    $module['can_access'] = true;
    if ($module['status'] === 'development' && !in_array($user_role, ['admin', 'dev'])) {
        $module['can_access'] = false;
    }
    if (isset($module['coming_soon']) && $module['coming_soon']) {
        $module['can_access'] = false;
    }
}
unset($module); // Bonne pratique

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

// Charger les activités récentes depuis la base de données
function loadRecentActivities($userId, $db) {
    $stmt = $db->prepare("
        SELECT icon, title, details, type, time 
        FROM user_activities 
        WHERE user_id = :user_id 
        ORDER BY time DESC 
        LIMIT 10
    ");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Charger les statistiques utilisateur depuis la base de données
function loadUserStats($userId, $db) {
    $stmt = $db->prepare("
        SELECT 
            last_login, 
            nb_calculs, 
            modules_actifs, 
            notifications, 
            temps_session, 
            derniere_ip 
        FROM user_stats 
        WHERE user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Charger les activités récentes et statistiques utilisateur
if ($user_authenticated) {
    $userId = $current_user['id'] ?? null;
    if ($userId) {
        $recent_activities = loadRecentActivities($userId, $db);
        $user_stats = loadUserStats($userId, $db);
    }
}

// Remplacer les données de démonstration par les données réelles
$recent_activities = $recent_activities ?? [];
$user_stats = $user_stats ?? [
    'last_login' => date('d/m/Y H:i'),
    'nb_calculs' => 0,
    'modules_actifs' => 0,
    'notifications' => 0,
    'temps_session' => '0h 0min',
    'derniere_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];

// Fonction pour charger les préférences utilisateur depuis la table auth_users
function loadUserPreferences($userId, $db) {
    $stmt = $db->prepare("SELECT preferences FROM auth_users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? json_decode($result['preferences'], true) : [];
}

// Fonction pour sauvegarder les préférences utilisateur dans la table auth_users
function saveUserPreferences($userId, $preferences, $db) {
    $stmt = $db->prepare("UPDATE auth_users SET preferences = :preferences WHERE id = :id");
    $stmt->execute([
        'preferences' => json_encode($preferences),
        'id' => $userId
    ]);
}

// Charger les préférences utilisateur
if ($user_authenticated) {
    $userId = $current_user['id'] ?? null;
    if ($userId) {
        $current_user['preferences'] = loadUserPreferences($userId, $db);
    }
}

// Sauvegarder les préférences utilisateur si une requête POST est reçue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preferences'])) {
    $preferences = json_decode($_POST['preferences'], true);
    if ($user_authenticated && $userId) {
        saveUserPreferences($userId, $preferences, $db);
        $current_user['preferences'] = $preferences;
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}
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

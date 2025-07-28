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
$module_js = true; // S'assurer que cette variable est définie

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '👤', 'text' => 'Mon Espace', 'url' => '/user/', 'active' => true]
];

// Définition des modules pour l'affichage des cartes sur cette page
// TODO: Vérifier que tous les modules ont leur CSS dans /assets/css/
// TODO: Créer les JS manquants dans /assets/js/ si nécessaire
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

// TODO: Ajouter une vérification pour les fichiers CSS/JS des modules avant inclusion
$module_css_path = "/user/assets/css/user.css";
$module_js_path = "/user/assets/js/user.js";

// TODO: Standardiser les fichiers JS pour tous les modules
// TODO: Créer /public/admin/assets/js/admin.js et /public/auth/assets/js/auth.js si manquants
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
<link rel="stylesheet" href="<?= $module_css_path ?>?v=<?= $build_number ?>">
<script src="<?= $module_js_path ?>?v=<?= $build_number ?>"></script>

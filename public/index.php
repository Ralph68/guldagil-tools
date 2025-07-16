<?php
/**
 * Titre: Page d'accueil principale du portail - TOUTES FONCTIONNALITÉS PRÉSERVÉES
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// 🔧 CONFIGURATION INITIALE - ANTI-WARNINGS
// =====================================

// Définir ROOT_PATH AVANT TOUT pour éviter warnings
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Démarrage session sécurisé - éviter doublon
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================
// 🗂️ CHARGEMENT CONFIGURATION ROBUSTE
// =====================================

// Chargement config avec vérification pour éviter warnings
$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// =====================================
// 📊 VARIABLES TEMPLATE OBLIGATOIRES
// =====================================

// Variables avec valeurs par défaut pour éviter tous les warnings/notices
$page_title = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$page_subtitle = 'Tableau de bord principal';
$page_description = 'Portail de gestion centralisé - Solutions professionnelles';
$current_module = 'home';
$module_css = false;
$nav_info = 'Tableau de bord principal';

// Breadcrumbs par défaut
$breadcrumbs = $breadcrumbs ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// =====================================
// 🔧 FONCTIONS UTILITAIRES PRÉSERVÉES
// =====================================

/**
 * Fonctions from original index.php - PRESERVED
 */
function shouldShowModule($module_id, $module, $user_role) {
    // Si admin_only et pas admin, masquer
    if (isset($module['admin_only']) && $module['admin_only'] && $user_role !== 'admin') {
        return false;
    }
    
    // Vérifier les rôles autorisés
    if (isset($module['roles']) && !in_array($user_role, $module['roles'])) {
        return false;
    }
    
    return true;
}

function canAccessModule($module_id, $module, $user_role) {
    // Module en développement = accès restreint
    if ($module['status'] === 'development' && !in_array($user_role, ['admin', 'dev'])) {
        return false;
    }
    
    // Coming soon = pas encore accessible
    if (isset($module['coming_soon']) && $module['coming_soon']) {
        return false;
    }
    
    return shouldShowModule($module_id, $module, $user_role);
}

// =====================================
// 📋 MODULES COMPLETS - PRÉSERVÉS DE L'ORIGINAL
// =====================================

$all_modules = [
    'port' => [
        'name' => 'Calculateur Frais de Port',
        'description' => 'Calcul intelligent des frais de transport selon différents transporteurs et types d\'envoi',
        'icon' => '📦',
        'url' => '/port/',
        'status' => 'active',
        'color' => '#3498db',
        'category' => 'Logistique & Transport',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Calcul automatique multi-transporteurs',
            'Gestion des tarifs Heppner',
            'Optimisation des coûts d\'expédition',
            'Historique des calculs',
            'Export des résultats'
        ],
        'priority' => 1
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport sécurisé de marchandises dangereuses selon réglementation ADR',
        'icon' => '⚠️',
        'url' => '/adr/',
        'status' => 'beta',
        'color' => '#e74c3c',
        'category' => 'Sécurité & Réglementation',
        'roles' => ['user', 'admin', 'dev', 'logistique', 'securite'],
        'features' => [
            'Classification automatique ADR',
            'Calcul des quotas transport',
            'Gestion des déclarations',
            'Suivi réglementaire',
            'Alertes de sécurité'
        ],
        'priority' => 2
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Système de gestion qualité et suivi des contrôles réglementaires',
        'icon' => '✅',
        'url' => '/qualite/',
        'status' => 'development',
        'color' => '#2ecc71',
        'category' => 'Qualité & Conformité',
        'roles' => ['admin', 'dev', 'qualite'],
        'features' => [
            'Planification des contrôles',
            'Suivi des non-conformités',
            'Reporting automatique',
            'Traçabilité complète',
            'Tableau de bord qualité'
        ],
        'priority' => 3
    ],
    'epi' => [
        'name' => 'Gestion EPI',
        'description' => 'Suivi et gestion des équipements de protection individuelle',
        'icon' => '🦺',
        'url' => '/epi/',
        'status' => 'development',
        'color' => '#f39c12',
        'category' => 'Sécurité & Personnel',
        'roles' => ['admin', 'dev', 'securite', 'rh'],
        'features' => [
            'Inventaire EPI complet',
            'Suivi des dates d\'expiration',
            'Attribution nominative',
            'Alertes de renouvellement',
            'Statistiques d\'utilisation'
        ],
        'priority' => 4
    ],
    'outillages' => [
        'name' => 'Gestion Outillages',
        'description' => 'Inventaire et maintenance du matériel et outillages industriels',
        'icon' => '🔧',
        'url' => '/outillages/',
        'status' => 'development',
        'color' => '#95a5a6',
        'category' => 'Maintenance & Matériel',
        'roles' => ['admin', 'dev', 'maintenance'],
        'features' => [
            'Inventaire centralisé',
            'Planning de maintenance',
            'Historique d\'utilisation',
            'Géolocalisation des équipements',
            'Calcul ROI et amortissement'
        ],
        'priority' => 5,
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
        'priority' => 6
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
        'priority' => 7,
        'admin_only' => true
    ]
];

// =====================================
// 🎨 INCLUSION TEMPLATE ET AUTHENTIFICATION
// =====================================

// Inclure header (qui gère automatiquement l'auth obligatoire) - PRÉSERVÉ
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Header minimal de secours
    echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($page_title) . '</title><meta charset="utf-8"></head><body>';
}

// À ce stade, $user_authenticated et $current_user sont disponibles via le header
// Si on arrive ici, l'utilisateur EST forcément authentifié (sinon redirection par header)

// =====================================
// 📊 DONNÉES ET STATISTIQUES - LOGIC PRÉSERVÉE
// =====================================

// Filtrer modules selon rôle utilisateur - LOGIQUE ORIGINALE PRÉSERVÉE
$user_role = $current_user['role'] ?? 'user';
$user_modules = [];

foreach ($all_modules as $id => $module) {
    if (shouldShowModule($id, $module, $user_role)) {
        $module['can_access'] = canAccessModule($id, $module, $user_role);
        $user_modules[$id] = $module;
    }
}

// Trier par priorité - ORIGINAL PRESERVED
uasort($user_modules, function($a, $b) {
    return ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999);
});

// Statistiques par catégorie - ORIGINAL PRESERVED
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

// Statistiques globales du portail - ORIGINAL PRESERVED
$portal_stats = [
    'modules_accessibles' => count($user_modules),
    'modules_actifs' => count(array_filter($user_modules, fn($m) => $m['status'] === 'active')),
    'calculs_aujourd_hui' => rand(25, 75),
    'utilisateurs_connectes' => 1,
    'session_timeout' => '30 min'
];

// Messages système - ORIGINAL PRESERVED
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
?>

<!-- Container principal du dashboard - STRUCTURE ORIGINALE PRÉSERVÉE -->
<div class="dashboard-container">
    
    <!-- Section de bienvenue - ENHANCED BUT PRESERVED -->
    <section class="welcome-section">
        <div class="welcome-content">
            <h1>👋 Bienvenue, <?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?> !</h1>
            <p class="welcome-subtitle">
                Accès au portail Guldagil - Solutions professionnelles pour le traitement de l'eau et la logistique
            </p>
            <div class="welcome-meta">
                <span>🔐 Session sécurisée</span>
                <span>👤 Rôle : <strong><?= htmlspecialchars(ucfirst($current_user['role'] ?? 'user')) ?></strong></span>
                <span>⏰ Connecté à : <?= date('H:i') ?></span>
                <span>🌐 IP : <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'inconnue') ?></span>
            </div>
        </div>
        
        <!-- Statistiques du portail - PRESERVED -->
        <div class="portal-stats">
            <?php foreach ($portal_stats as $key => $value): ?>
            <div class="stat-item">
                <div class="stat-value"><?= htmlspecialchars($value) ?></div>
                <div class="stat-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Alertes système - PRESERVED -->
    <?php if (!empty($system_alerts)): ?>
    <section class="system-alerts">
        <?php foreach ($system_alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?>">
            <span class="alert-icon"><?= $alert['icon'] ?></span>
            <div class="alert-content">
                <div class="alert-message"><?= htmlspecialchars($alert['message']) ?></div>
                <?php if (isset($alert['action'])): ?>
                <div class="alert-action"><?= htmlspecialchars($alert['action']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- Statistiques par catégorie - PRESERVED -->
    <?php if (!empty($categories_stats)): ?>
    <section class="categories-stats">
        <h2>📊 Modules par catégorie</h2>
        <div class="categories-grid">
            <?php foreach ($categories_stats as $category => $stats): ?>
            <div class="category-card">
                <h3><?= htmlspecialchars($category) ?></h3>
                <div class="category-stats">
                    <div class="category-stat">
                        <span class="stat-number"><?= $stats['total'] ?></span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="category-stat">
                        <span class="stat-number"><?= $stats['active'] ?? 0 ?></span>
                        <span class="stat-label">Actifs</span>
                    </div>
                    <?php if (($stats['development'] ?? 0) > 0): ?>
                    <div class="category-stat">
                        <span class="stat-number"><?= $stats['development'] ?></span>
                        <span class="stat-label">En dev</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Grille des modules - ENHANCED FROM ORIGINAL -->
    <section class="modules-section">
        <h2>🚀 Modules disponibles</h2>
        
        <div class="modules-grid">
            <?php foreach ($user_modules as $module_key => $module): ?>
            <article class="module-card <?= $module['can_access'] ? 'accessible' : 'restricted' ?>" data-module="<?= $module_key ?>">
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
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Debug panel conditionnel - PRESERVED -->
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

</div>

<?php
// =====================================
// 🎨 INCLUSION FOOTER - PRESERVED
// =====================================

if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    // Footer minimal de secours
    ?>
    <footer class="main-footer">
        <p>&copy; <?= date('Y') ?> - Portail Guldagil v<?= defined('APP_VERSION') ? APP_VERSION : '0.5-beta' ?></p>
        <p>Build: <?= defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000' ?></p>
    </footer>
    </body>
    </html>
    <?php
}
?>

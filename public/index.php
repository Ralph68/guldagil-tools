<?php
/**
 * Titre: Page d'accueil complète du portail Guldagil - AUTHENTIFICATION OBLIGATOIRE
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// ========================================
// 📋 CONFIGURATION DE BASE
// ========================================
define('ROOT_PATH', dirname(__DIR__));

// Variables pour template (OBLIGATOIRES pour header)
$page_title = 'Accueil du portail';
$page_subtitle = 'Solutions professionnelles';
$page_description = 'Portail Guldagil - Solutions pour le traitement de l\'eau et la logistique';
$current_module = 'home';
$module_css = true; // IMPORTANT : Activer le CSS spécifique au module home
$module_js = true;  // IMPORTANT : Activer le JS spécifique au module home

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// Chargement configuration
$config_paths = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($config_paths as $config_path) {
    if (file_exists($config_path)) {
        try {
            require_once $config_path;
        } catch (Exception $e) {
            error_log("Erreur config: " . $e->getMessage());
        }
    }
}

// Chargement configuration modules
require_once ROOT_PATH . '/config/modules.php';

// Variables avec fallbacks
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd');
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// ========================================
// 🎯 CONFIGURATION COMPLÈTE DES MODULES
// ========================================
$all_modules = [
    'port' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs XPO, Heppner, Kuehne+Nagel avec options ADR',
        'icon' => '🚛',
        'url' => '/port/',
        'status' => 'beta',
        'color' => '#3498db',
        'category' => 'Transport & Logistique',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Comparaison multi-transporteurs temps réel',
            'Calculs avec options ADR et enlèvement', 
            'Export PDF et Excel des devis',
            'Historique des calculs',
            'Gestion des palettes EUR'
        ],
        'priority' => 1,
        'business_critical' => true
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses selon réglementation européenne',
        'icon' => '⚠️',
        'url' => '/adr/',
        'status' => 'development',
        'color' => '#e74c3c',
        'category' => 'Sécurité & Réglementation',
        'roles' => ['admin', 'dev'],
        'features' => [
            'Base de données produits dangereux',
            'Génération déclarations ADR automatiques',
            'Suivi réglementaire en temps réel',
            'Alertes de conformité',
            'Formation du personnel'
        ],
        'priority' => 2,
        'requires_certification' => true
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Suivi qualité, audits et validation des équipements traitement eau',
        'icon' => '✅',
        'url' => '/qualite/',
        'status' => 'development',
        'color' => '#2ecc71',
        'category' => 'Qualité & Conformité',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Planification contrôles périodiques',
            'Rapports qualité automatisés',
            'Traçabilité complète des équipements',
            'Gestion des non-conformités',
            'Certifications ISO 9001'
        ],
        'priority' => 3,
        'coming_soon' => true
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion complète des équipements de protection individuelle',
        'icon' => '🦺',
        'url' => '/epi/',
        'status' => 'development',
        'color' => '#f39c12',
        'category' => 'Sécurité & Réglementation',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Inventaire EPI temps réel',
            'Alertes dates de validité',
            'Commandes automatiques',
            'Formation à l\'utilisation',
            'Rapports de conformité'
        ],
        'priority' => 4,
        'coming_soon' => true
    ],
    'outillages' => [
        'name' => 'Gestion Outillages',
        'description' => 'Inventaire et maintenance des outillages industriels',
        'icon' => '🔧',
        'url' => '/outillages/',
        'status' => 'development',
        'color' => '#95a5a6',
        'category' => 'Maintenance & Équipement',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Inventaire dynamique en temps réel',
            'Planification maintenance préventive',
            'Historique complet d\'utilisation',
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

// ========================================
// 🎨 INCLUSION TEMPLATE ET AUTHENTIFICATION
// ========================================

// Inclure header (qui gère automatiquement l'auth obligatoire)
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($page_title) . '</title><meta charset="utf-8"></head><body>';
}

// À ce stade, $user_authenticated et $current_user sont disponibles via le header
// Si on arrive ici, l'utilisateur EST forcément authentifié (sinon redirection par header)

// ========================================
// 📊 DONNÉES ET STATISTIQUES
// ========================================

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
    $cat = $module['category'];
    if (!isset($categories_stats[$cat])) {
        $categories_stats[$cat] = ['total' => 0, 'active' => 0, 'development' => 0];
    }
    $categories_stats[$cat]['total']++;
    $categories_stats[$cat][$module['status']]++;
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
?>

<!-- Container principal du dashboard -->
<div class="dashboard-container">
    
    <!-- Section de bienvenue -->
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
                <span>🌐 IP : <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A') ?></span>
            </div>
        </div>
    </section>
    
    <!-- Statistiques rapides -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $portal_stats['modules_accessibles'] ?></div>
            <div class="stat-label">Modules accessibles</div>
            <div class="stat-sublabel">Selon votre rôle : <?= ucfirst($user_role) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $portal_stats['modules_actifs'] ?></div>
            <div class="stat-label">Modules actifs</div>
            <div class="stat-sublabel">Prêts à utiliser</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $portal_stats['calculs_aujourd_hui'] ?></div>
            <div class="stat-label">Calculs aujourd'hui</div>
            <div class="stat-sublabel">Tous utilisateurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $app_version ?></div>
            <div class="stat-label">Version portail</div>
            <div class="stat-sublabel">Build <?= substr($build_number, 0, 8) ?></div>
        </div>
    </section>
    
    <!-- Modules par catégorie -->
    <section>
        <div class="section-header">
            <h2 class="section-title">📋 Modules disponibles</h2>
        </div>
        
        <!-- Informations sur le rôle -->
        <div class="role-info">
            <div>
                <strong>Votre rôle :</strong>
                <span class="role-badge role-<?= $user_role ?>"><?= ucfirst($user_role) ?></span>
            </div>
            <div class="role-description">
                <?php
                $role_descriptions = [
                    'user' => 'Accès aux modules actifs et consultation des données',
                    'admin' => 'Gestion système et accès modules actifs/beta',
                    'dev' => 'Accès développeur complet incluant modules en développement',
                    'logistique' => 'Accès spécialisé transport et logistique'
                ];
                echo $role_descriptions[$user_role] ?? 'Permissions standard';
                ?>
            </div>
        </div>
        
        <?php if (empty($user_modules)): ?>
        <div class="alert alert-info">
            <span class="alert-icon">ℹ️</span>
            <div>
                <strong>Aucun module accessible</strong><br>
                <small>Votre rôle actuel ne permet l'accès à aucun module. Contactez un administrateur.</small>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Grouper par catégorie -->
        <?php
        $modules_by_category = [];
        foreach ($user_modules as $id => $module) {
            $cat = $module['category'];
            if (!isset($modules_by_category[$cat])) {
                $modules_by_category[$cat] = [];
            }
            $modules_by_category[$cat][$id] = $module;
        }
        ?>
        
        <?php foreach ($modules_by_category as $category => $modules): ?>
        <div class="category-section">
            <h3 class="category-title">
                📂 <?= htmlspecialchars($category) ?>
                <span class="category-stats">
                    <?= count($modules) ?> module<?= count($modules) > 1 ? 's' : '' ?>
                    • <?= count(array_filter($modules, fn($m) => $m['status'] === 'active')) ?> actif<?= count(array_filter($modules, fn($m) => $m['status'] === 'active')) > 1 ? 's' : '' ?>
                </span>
            </h3>
            
            <div class="modules-grid">
                <?php foreach ($modules as $module_id => $module): ?>
                <div class="module-card <?= !$module['can_access'] ? 'no-access' : '' ?>"
                     style="--module-color: <?= $module['color'] ?>"
                     data-module="<?= $module_id ?>">
                    
                    <?php if ($module['can_access'] && $module['status'] === 'active'): ?>
                    <a href="<?= htmlspecialchars($module['url']) ?>" class="module-link">
                    <?php endif; ?>
                    
                        <div class="module-header">
                            <span class="module-icon"><?= $module['icon'] ?></span>
                            <div class="module-info">
                                <div class="module-name"><?= htmlspecialchars($module['name']) ?></div>
                                <div class="module-status-badge status-<?= $module['status'] ?>">
                                    <?php
                                    switch ($module['status']) {
                                        case 'active': echo 'Actif'; break;
                                        case 'beta': echo 'Bêta'; break;
                                        case 'development': echo 'En développement'; break;
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="module-description">
                            <?= htmlspecialchars($module['description']) ?>
                        </div>
                        
                        <div class="module-features">
                            <h4>Fonctionnalités</h4>
                            <ul class="features-list">
                                <?php foreach ($module['features'] as $feature): ?>
                                <li><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="module-footer">
                            <div class="access-status">
                                <?php if ($module['can_access']): ?>
                                    <span class="status-available">✅ Accès autorisé</span>
                                <?php else: ?>
                                    <span class="status-restricted">🔒 Accès restreint</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    <?php if ($module['can_access'] && $module['status'] === 'active'): ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (!empty($system_alerts)): ?>
        <div class="alert alert-info">
            <span class="alert-icon">ℹ️</span>
            <div>
                <strong><?= htmlspecialchars($system_alerts[0]['message']) ?></strong><br>
                <small><?= htmlspecialchars($system_alerts[0]['action']) ?></small>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </section>
</div>

<?php
// Inclure footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    echo '
    <!-- Footer simple si fichier manquant -->
    <footer class="portal-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="footer-title">' . htmlspecialchars($app_name) . '</div>
                <div class="footer-copyright">© ' . date('Y') . ' ' . htmlspecialchars($app_author) . '</div>
            </div>
            <div class="footer-info">
                <div class="version-info">Version ' . htmlspecialchars($app_version) . '</div>
                <div class="build-info">Build ' . htmlspecialchars($build_number) . '</div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript du portail -->
    <script src="/assets/js/portal.js?v=' . htmlspecialchars($build_number) . '"></script>
    <script src="/assets/js/home.js?v=' . htmlspecialchars($build_number) . '"></script>
    </body>
    </html>';
}
?>

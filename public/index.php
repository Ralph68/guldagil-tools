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
$module_css = false;

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
        'roles' => ['admin', 'dev'], // Accès restreint
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
        'roles' => ['admin', 'dev'], // Accès très restreint
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
// 🎯 FONCTIONS UTILITAIRES
// ========================================

// Utiliser les mêmes fonctions que dans le header pour cohérence
function canAccessModule($module_key, $module_data, $user_role) {
    if (!$user_role || $user_role === 'guest') {
        return false;
    }
    
    switch ($user_role) {
        case 'dev':
            return true;
            
        case 'admin':
            return ($module_key !== 'dev' && in_array($module_data['status'] ?? 'active', ['active', 'beta']));
            
        case 'logistique':
            if (in_array($module_key, ['port', 'adr', 'qualite', 'epi', 'outillages', 'user'])) {
                if ($module_key === 'port' && ($module_data['status'] ?? 'active') === 'beta') {
                    return true;
                }
                if (in_array($module_key, ['qualite', 'epi', 'outillages', 'user']) && ($module_data['status'] ?? 'active') === 'active') {
                    return true;
                }
                return false;
            }
            return false;
            
        case 'user':
            return (($module_data['status'] ?? 'active') === 'active');
            
        default:
            return false;
    }
}

function shouldShowModule($module_key, $module_data, $user_role) {
    if (!$user_role || $user_role === 'guest') {
        return false;
    }
    
    switch ($user_role) {
        case 'dev':
            return true;
            
        case 'admin':
            return ($module_key === 'admin' || in_array($module_data['status'] ?? 'active', ['active', 'beta']));
            
        case 'logistique':
            return in_array($module_key, ['port', 'adr', 'epi', 'outillages', 'qualite', 'user']);
            
        case 'user':
            return (($module_data['status'] ?? 'active') === 'active');
            
        default:
            return false;
    }
}

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

<!-- CSS spécifique dashboard -->
<style>
:root {
    --color-primary: #2563eb;
    --color-secondary: #1e40af;
    --color-success: #059669;
    --color-warning: #d97706;
    --color-danger: #dc2626;
    --color-info: #0284c7;
    --color-gray-100: #f3f4f6;
    --color-gray-200: #e5e7eb;
    --color-gray-500: #6b7280;
    --color-gray-600: #4b5563;
    --color-gray-700: #374151;
    --color-gray-800: #1f2937;
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 2rem;
    --spacing-xl: 3rem;
    --border-radius: 8px;
    --border-radius-lg: 12px;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: var(--spacing-lg);
    min-height: calc(100vh - 300px);
}

.welcome-section {
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    color: white;
    padding: var(--spacing-xl);
    border-radius: var(--border-radius-lg);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.welcome-section::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(50px, -50px);
}

.welcome-content {
    position: relative;
    z-index: 1;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.stat-card {
    background: white;
    padding: var(--spacing-lg);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    text-align: center;
    border-left: 4px solid var(--color-primary);
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--color-primary);
    line-height: 1;
    margin-bottom: var(--spacing-sm);
}

.stat-label {
    font-size: 1rem;
    color: var(--color-gray-600);
    font-weight: 500;
}

.stat-sublabel {
    font-size: 0.8rem;
    color: var(--color-gray-500);
    margin-top: var(--spacing-xs);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-md);
    border-bottom: 2px solid var(--color-gray-200);
}

.section-title {
    font-size: 1.75rem;
    color: var(--color-gray-800);
    font-weight: bold;
}

.role-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: #f8fafc;
    border-radius: var(--border-radius);
    border-left: 4px solid var(--color-primary);
    margin-bottom: var(--spacing-lg);
}

.role-badge {
    padding: var(--spacing-xs) var(--spacing-md);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-user { background: #dbeafe; color: #1e40af; }
.role-admin { background: #fef3c7; color: #92400e; }
.role-dev { background: #f3e8ff; color: #7c3aed; }
.role-logistique { background: #dcfce7; color: #166534; }

.category-section {
    margin-bottom: var(--spacing-xl);
}

.category-title {
    font-size: 1.2rem;
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.category-stats {
    font-size: 0.85rem;
    color: var(--color-gray-500);
    background: var(--color-gray-100);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: 12px;
}

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-lg);
}

.module-card {
    background: white;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    padding: var(--spacing-lg);
    transition: var(--transition);
    text-decoration: none;
    color: inherit;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.module-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--module-color, var(--color-primary));
}

.module-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--color-primary);
}

.module-card.no-access {
    opacity: 0.7;
    border: 2px dashed #d1d5db;
}

.module-card.no-access:hover {
    opacity: 1;
    border-color: var(--color-warning);
    cursor: not-allowed;
}

.module-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: var(--spacing-md);
    gap: var(--spacing-md);
}

.module-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.module-info {
    flex: 1;
}

.module-name {
    font-weight: bold;
    color: var(--color-gray-800);
    font-size: 1.1rem;
    margin-bottom: var(--spacing-xs);
}

.module-status-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
    display: inline-block;
}

.status-active { background: #dcfce7; color: #166534; }
.status-beta { background: #fef3c7; color: #92400e; }
.status-development { background: #fee2e2; color: #991b1b; }

.module-description {
    color: var(--color-gray-600);
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: var(--spacing-md);
}

.module-features {
    margin: var(--spacing-md) 0;
}

.module-features h4 {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-sm);
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.features-list li {
    padding: 0.2rem 0;
    font-size: 0.8rem;
    color: var(--color-gray-600);
    position: relative;
    padding-left: 1rem;
}

.features-list li:before {
    content: "▸";
    position: absolute;
    left: 0;
    color: var(--color-primary);
    font-weight: bold;
}

.module-footer {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.access-status {
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.status-available { color: var(--color-success); }
.status-restricted { color: var(--color-danger); }
.status-coming-soon { color: var(--color-warning); }

.quick-actions {
    margin-top: var(--spacing-xl);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.action-card {
    background: white;
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    text-decoration: none;
    color: inherit;
    border-left: 4px solid var(--action-color, var(--color-primary));
    transition: var(--transition);
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.system-info {
    background: var(--color-gray-100);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--color-success);
    margin-top: var(--spacing-xl);
}

.alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-md);
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-md);
}

.alert-info { background: #eff6ff; border-left: 4px solid var(--color-info); }

@media (max-width: 768px) {
    .dashboard-container {
        padding: var(--spacing-md);
    }
    
    .stats-grid,
    .modules-grid,
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-md);
    }
}
</style>

<!-- Container principal du dashboard -->
<div class="dashboard-container">
    
    <!-- Section de bienvenue -->
    <section class="welcome-section">
        <div class="welcome-content">
            <h1>👋 Bienvenue, <?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?> !</h1>
            <p style="font-size: 1.1rem; margin: var(--spacing-md) 0; opacity: 0.9;">
                Accès au portail Guldagil - Solutions professionnelles pour le traitement de l'eau et la logistique
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-md); font-size: 0.9rem; opacity: 0.9;">
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
        <div class="stat-sublabel">
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
            <div style="font-size: 0.9rem; color: var(--color-gray-600);">
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
            <span style="font-size: 1.5rem;">ℹ️</span>
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
                     style="--module-color: <?= $module['color'] ?>">
                    
                    <?php if ($module['can_access'] && $module['status'] === 'active'): ?>
                    <a href="<?= htmlspecialchars($module['url']) ?>" style="text-decoration: none; color: inherit; display: block; height: 100%;">
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
                                else: ?>
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
            <span style="font-size: 1.5rem;">ℹ️</span>
            <div>
                <strong><?= htmlspecialchars($system_alerts[0]['message']) ?></strong><br>
                <small><?= htmlspecialchars($system_alerts[0]['action']) ?></small>
            </div>
        </div>
        <?php endif; ?>
        
        </section>
    </div>
</div>

<!-- Footer -->
<footer class="system-info">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p>© <?= date('Y') ?> <?= htmlspecialchars($app_name) ?> - Version <?= htmlspecialchars($app_version) ?></p>
            </div>
            <div class="col-md-6 text-md-right">
                <p>Conçu par <?= htmlspecialchars($app_author) ?></p>
            </div>
        </div>
    </div>
</footer>

</body>
</html>

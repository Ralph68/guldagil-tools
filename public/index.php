<?php
/**
 * Titre: Page d'accueil du portail Guldagil - AUTHENTIFICATION VIA HEADER
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Chargement configuration
$config_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Variables de base pour le template
$page_title = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$page_subtitle = 'Tableau de bord principal';
$page_description = 'Portail Guldagil - Solutions pour le traitement de l\'eau et la logistique';
$current_module = 'home';
$module_css = false;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// Fonctions utilitaires
function shouldShowModule($module_id, $module, $user_role) {
    if (isset($module['admin_only']) && $module['admin_only'] && !in_array($user_role, ['admin', 'dev'])) {
        return false;
    }
    
    if (isset($module['roles']) && !in_array($user_role, $module['roles'])) {
        return false;
    }
    
    return true;
}

/**
 * Vérifie si l'utilisateur peut accéder à un module
 */
function canAccessModule($module_id, $module, $user_role) {
    // DONE: Correction - Vérification de l'existence de la clé 'status'
    $status = $module['status'] ?? 'active';
    
    // Logique d'accès basée sur le rôle et le statut
    if ($status === 'development' && $user_role !== 'dev') {
        return false;
    }
    
    if (isset($module['coming_soon']) && $module['coming_soon']) {
        return false;
    }
    
    return shouldShowModule($module_id, $module, $user_role);
}

// Modules disponibles
$all_modules = [
    'port' => [
        'name' => 'Calculateur Frais de Port',
        'description' => 'Calcul intelligent des frais de transport selon différents transporteurs',
        'icon' => '📦',
        'url' => '/port/',
        'status' => 'active',
        'color' => '#3182ce',
        'category' => 'Logistique & Transport',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Calcul automatique multi-transporteurs',
            'Zones et tarifications personnalisées', 
            'Gestion des surcharges et options',
            'Historique et statistiques',
            'Export des résultats'
        ],
        'priority' => 1
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses selon réglementation ADR',
        'icon' => '⚠️',
        'url' => '/adr/',
        'status' => 'active',
        'color' => '#f56500',
        'category' => 'Sécurité & Conformité',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Classification marchandises dangereuses',
            'Génération automatique de documents',
            'Vérification conformité réglementaire',
            'Base de données produits ADR',
            'Formation et certifications'
        ],
        'priority' => 2
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Gestion complète du système qualité et des contrôles',
        'icon' => '✅',
        'url' => '/qualite/',
        'status' => 'development',
        'color' => '#10b981',
        'category' => 'Qualité & Contrôles',
        'roles' => ['admin', 'dev', 'qualite'],
        'features' => [
            'Planification des contrôles qualité',
            'Suivi des non-conformités',
            'Tableaux de bord qualité',
            'Audits et certifications',
            'Documentation qualité'
        ],
        'priority' => 3,
        'coming_soon' => true
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection individuelle',
        'icon' => '🦺',
        'url' => '/epi/',
        'status' => 'development',
        'color' => '#8b5cf6',
        'category' => 'Sécurité & Conformité',
        'roles' => ['admin', 'dev', 'securite'],
        'features' => [
            'Inventaire complet des EPI',
            'Suivi des dates d\'expiration',
            'Attribution personnalisée',
            'Contrôles périodiques',
            'Commandes et réapprovisionnement'
        ],
        'priority' => 4,
        'coming_soon' => true
    ],
    'materiel' => [
        'name' => 'Gestion du Matériel',
        'description' => 'Gestion complète de l\'outillage et des équipements techniques',
        'icon' => '🔧',
        'url' => '/materiel/',
        'status' => 'active',
        'color' => '#ea580c',
        'category' => 'Équipements & Outillage',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Inventaire outillage complet',
            'Attribution par technicien',
            'Maintenance et révisions',
            'Demandes de matériel',
            'Tableaux de bord par agence'
        ],
        'priority' => 5
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

// IMPORTANT : Inclure header.php qui gère l'authentification obligatoire
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($page_title) . '</title></head><body>';
    echo '<h1>Erreur : Template header.php manquant</h1>';
    exit;
}

// À partir d'ici, on est SÛR que l'utilisateur est authentifié
// Les variables $user_authenticated (true) et $current_user sont disponibles

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
    $categories_stats[$cat][$status]++;
}

// Statistiques globales simulées
$global_stats = [
    'calculs_today' => 47,
    'users_active' => 12,
    'modules_available' => count($user_modules),
    'uptime_percentage' => 99.8
];

?>

<!-- Container principal -->
<div class="portal-container">
    
    <!-- Section de bienvenue -->
    <section class="welcome-section">
        <div class="welcome-header">
            <h1 class="welcome-title">
                🌊 Bienvenue sur <?= htmlspecialchars($page_title) ?>
            </h1>
            <p class="welcome-subtitle">
                Votre portail de solutions professionnelles pour le traitement de l'eau et la logistique
            </p>
        </div>
        
        <div class="user-greeting">
            <div class="greeting-content">
                <div class="user-avatar">
                    <?= strtoupper(substr($current_user['username'], 0, 1)) ?>
                </div>
                <div class="greeting-text">
                    <p class="greeting-main">
                        Bonjour <strong><?= htmlspecialchars($current_user['username']) ?></strong>
                    </p>
                    <p class="greeting-role">
                        Connecté en tant que <span class="role-badge role-<?= $current_user['role'] ?>"><?= htmlspecialchars($current_user['role']) ?></span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistiques rapides -->
    <section class="quick-stats">
        <div class="stats-grid">
            <?php foreach ($global_stats as $key => $value): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <?php
                    $icons = [
                        'calculs_today' => '📊',
                        'users_active' => '👥', 
                        'modules_available' => '📋',
                        'uptime_percentage' => '⚡'
                    ];
                    echo $icons[$key] ?? '📈';
                    ?>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $value ?><?= str_contains($key, 'percentage') ? '%' : '' ?></div>
                    <div class="stat-label"><?= ucfirst(str_replace('_', ' ', $key)) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

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
            <div class="module-card <?= $module['status'] ?> <?= !$module['can_access'] ? 'disabled' : '' ?>" 
                 data-module="<?= $id ?>" 
                 style="--module-color: <?= $module['color'] ?>">
                
                <div class="module-header">
                    <div class="module-icon" style="background: <?= $module['color'] ?>20">
                        <?= $module['icon'] ?>
                    </div>
                    <div class="module-status">
                        <?php if ($module['status'] === 'development'): ?>
                            <span class="status-badge development">En développement</span>
                        <?php elseif (isset($module['coming_soon']) && $module['coming_soon']): ?>
                            <span class="status-badge coming-soon">Bientôt disponible</span>
                        <?php else: ?>
                            <span class="status-badge active">Actif</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="module-content">
                    <h3 class="module-name"><?= htmlspecialchars($module['name']) ?></h3>
                    <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                    
                    <?php if (!empty($module['features'])): ?>
                    <div class="module-features">
                        <ul class="features-list">
                            <?php foreach (array_slice($module['features'], 0, 3) as $feature): ?>
                            <li class="feature-item">
                                <span class="feature-icon">✓</span>
                                <span class="feature-text"><?= htmlspecialchars($feature) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($module['features']) > 3): ?>
                        <div class="features-more">
                            +<?= count($module['features']) - 3 ?> autres fonctionnalités
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="module-footer">
                    <?php if ($module['can_access']): ?>
                        <a href="<?= htmlspecialchars($module['url']) ?>" class="module-button primary">
                            <span class="button-icon">🚀</span>
                            <span class="button-text">Accéder</span>
                        </a>
                    <?php elseif (isset($module['coming_soon']) && $module['coming_soon']): ?>
                        <button class="module-button disabled" disabled>
                            <span class="button-icon">⏳</span>
                            <span class="button-text">Bientôt disponible</span>
                        </button>
                    <?php else: ?>
                        <button class="module-button disabled" disabled title="Accès restreint selon votre rôle">
                            <span class="button-icon">🔒</span>
                            <span class="button-text">Accès restreint</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <?php endforeach; ?>

    <!-- Section aide rapide -->
    <section class="help-section">
        <div class="help-content">
            <h2 class="help-title">❓ Besoin d'aide ?</h2>
            <div class="help-links">
                <a href="/help/" class="help-link">
                    <span class="help-icon">📚</span>
                    <span class="help-text">Documentation</span>
                </a>
                <a href="mailto:support@guldagil.fr" class="help-link">
                    <span class="help-icon">📧</span>
                    <span class="help-text">Support technique</span>
                </a>
                <a href="/admin/" class="help-link" <?= !in_array($user_role, ['admin', 'dev']) ? 'style="display:none"' : '' ?>>
                    <span class="help-icon">⚙️</span>
                    <span class="help-text">Administration</span>
                </a>
            </div>
        </div>
    </section>

</div>

<!-- Styles CSS intégrés pour l'index -->
<style>
.portal-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.welcome-section {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    color: white;
}

.welcome-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.welcome-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 30px;
}

.user-greeting {
    margin-top: 20px;
}

.greeting-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.user-avatar {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

.greeting-main {
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.role-badge {
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.quick-stats {
    margin-bottom: 40px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #3182ce;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    text-transform: capitalize;
}

.modules-category {
    margin-bottom: 50px;
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

.category-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2d3748;
}

.category-stats {
    display: flex;
    gap: 15px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    color: #666;
}

.stat-count {
    font-weight: 600;
    color: #3182ce;
}

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.module-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.module-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: var(--module-color, #3182ce);
}

.module-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.module-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 20px 0;
}

.module-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.development {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.coming-soon {
    background: #e0e7ff;
    color: #3730a3;
}

.module-content {
    padding: 20px;
}

.module-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #1a202c;
}

.module-description {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.5;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.feature-icon {
    color: #10b981;
    font-weight: bold;
}

.features-more {
    font-size: 0.8rem;
    color: #666;
    font-style: italic;
    margin-top: 10px;
}

.module-footer {
    padding: 0 20px 20px;
}

.module-button {
    width: 100%;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.module-button.primary {
    background: var(--module-color, #3182ce);
    color: white;
}

.module-button.primary:hover {
    filter: brightness(110%);
    transform: translateY(-1px);
}

.module-button.disabled {
    background: #e2e8f0;
    color: #a0aec0;
    cursor: not-allowed;
}

.help-section {
    background: #f7fafc;
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    margin-top: 40px;
}

.help-title {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: #2d3748;
}

.help-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.help-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border-radius: 8px;
    text-decoration: none;
    color: #3182ce;
    font-weight: 500;
    transition: all 0.2s ease;
}

.help-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .portal-container {
        padding: 10px;
    }
    
    .welcome-title {
        font-size: 2rem;
    }
    
    .modules-grid {
        grid-template-columns: 1fr;
    }
    
    .greeting-content {
        flex-direction: column;
        gap: 10px;
    }
    
    .category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<?php
// Inclure footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    echo '</body></html>';
}
?>

<?php
/**
 * Titre: Page d'accueil principale du portail - NAVIGATION MATÉRIEL MISE À JOUR
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
// 📋 MODULES COMPLETS - MATÉRIEL MIS À JOUR
// =====================================

$all_modules = [
    'port' => [
        'name' => 'Calculateur Frais de Port',
        'description' => 'Calcul intelligent des frais de transport selon différents transporteurs et types d\'envoi',
        'icon' => '📦',
        'url' => '/port/',
        'status' => 'active',
        'color' => '#059669',
        'category' => 'Logistique & Transport',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Calcul automatique selon transporteur',
            'Support multi-formats (palettes, colis)',
            'Historique des calculs effectués',
            'Export données pour facturation',
            'API pour intégration ERP'
        ],
        'priority' => 1
    ],
    'adr' => [
        'name' => 'Module ADR',
        'description' => 'Gestion complète des matières dangereuses et transport ADR',
        'icon' => '⚠️',
        'url' => '/adr/',
        'status' => 'active',
        'color' => '#dc2626',
        'category' => 'Sécurité & Réglementation',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Classification automatique ADR',
            'Génération documents transport',
            'Vérification compatibilités',
            'Base de données matières',
            'Alertes réglementaires'
        ],
        'priority' => 2
    ],
    'epi' => [
        'name' => 'Gestion EPI',
        'description' => 'Suivi complet des équipements de protection individuelle',
        'icon' => '🦺',
        'url' => '/epi/',
        'status' => 'active',
        'color' => '#7c3aed',
        'category' => 'Sécurité & Protection',
        'roles' => ['user', 'admin', 'dev', 'logistique'],
        'features' => [
            'Inventaire EPI en temps réel',
            'Alertes dates d\'expiration',
            'Attribution par employé',
            'Contrôles périodiques',
            'Statistiques de conformité'
        ],
        'priority' => 3
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Système de contrôle qualité et traçabilité des processus',
        'icon' => '✅',
        'url' => '/qualite/',
        'status' => 'development',
        'color' => '#059669',
        'category' => 'Qualité & Conformité',
        'roles' => ['admin', 'dev', 'logistique'],
        'features' => [
            'Plans de contrôle qualité',
            'Traçabilité complète produits',
            'Non-conformités et actions',
            'Indicateurs qualité temps réel',
            'Audits et certifications'
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
    $categories_stats[$cat][$status]++;
}

// =====================================
// 📊 STATISTIQUES PORTAIL TEMPS RÉEL
// =====================================

$portal_stats = [
    'total_modules' => count($all_modules),
    'active_modules' => count(array_filter($all_modules, fn($m) => ($m['status'] ?? 'active') === 'active')),
    'user_accessible' => count($user_modules),
    'categories' => count($categories_stats),
    'completion_rate' => round((count(array_filter($all_modules, fn($m) => ($m['status'] ?? 'active') === 'active')) / count($all_modules)) * 100)
];

?>

<!-- =====================================
     🎨 INTERFACE UTILISATEUR PRINCIPALE
     ===================================== -->

<!-- Section héro avec informations utilisateur -->
<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="welcome-message">
                <h1>👋 Bonjour, <strong><?= htmlspecialchars($current_user['prenom'] ?? $current_user['username'] ?? 'Utilisateur') ?></strong></h1>
                <p class="hero-subtitle">
                    Bienvenue sur votre portail de gestion centralisé
                    <span class="user-badge role-<?= htmlspecialchars($user_role) ?>"><?= htmlspecialchars(strtoupper($user_role)) ?></span>
                </p>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $portal_stats['user_accessible'] ?></span>
                    <span class="stat-label">Modules accessibles</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $portal_stats['completion_rate'] ?>%</span>
                    <span class="stat-label">Portail complété</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation modules par catégorie -->
<div class="modules-section">
    <div class="container">
        <?php foreach ($categories_stats as $category => $stats): ?>
        <div class="category-section">
            <div class="category-header">
                <h2><?= htmlspecialchars($category) ?></h2>
                <div class="category-stats">
                    <span class="badge active"><?= $stats['active'] ?> actifs</span>
                    <?php if ($stats['development'] > 0): ?>
                    <span class="badge development"><?= $stats['development'] ?> en dev</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modules-grid">
                <?php 
                $category_modules = array_filter($user_modules, fn($m) => ($m['category'] ?? 'Général') === $category);
                foreach ($category_modules as $id => $module): 
                ?>
                <div class="module-card <?= !$module['can_access'] ? 'disabled' : '' ?>" 
                     style="--module-color: <?= $module['color'] ?>">
                    
                    <div class="module-header">
                        <div class="module-icon"><?= $module['icon'] ?></div>
                        <div class="module-title">
                            <h3><?= htmlspecialchars($module['name']) ?></h3>
                            <div class="module-status-badges">
                                <?php if ($module['status'] === 'development'): ?>
                                <span class="status-badge dev">DEV</span>
                                <?php elseif ($module['status'] === 'beta'): ?>
                                <span class="status-badge beta">BETA</span>
                                <?php elseif (isset($module['coming_soon']) && $module['coming_soon']): ?>
                                <span class="status-badge coming-soon">BIENTÔT</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="module-description">
                        <p><?= htmlspecialchars($module['description']) ?></p>
                    </div>

                    <div class="module-features">
                        <ul>
                            <?php foreach (array_slice($module['features'], 0, 3) as $feature): ?>
                            <li><?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                            <?php if (count($module['features']) > 3): ?>
                            <li class="more-features">+<?= count($module['features']) - 3 ?> autres fonctionnalités</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="module-actions">
                        <?php if ($module['can_access']): ?>
                        <a href="<?= htmlspecialchars($module['url']) ?>" class="btn btn-primary">
                            <span class="btn-icon">🚀</span>
                            Accéder au module
                        </a>
                        <?php elseif (isset($module['coming_soon']) && $module['coming_soon']): ?>
                        <button class="btn btn-disabled" disabled>
                            <span class="btn-icon">⏳</span>
                            Bientôt disponible
                        </button>
                        <?php else: ?>
                        <button class="btn btn-restricted" disabled>
                            <span class="btn-icon">🔒</span>
                            Accès restreint
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Section informations système -->
<div class="system-info-section">
    <div class="container">
        <div class="system-cards">
            <div class="info-card">
                <h3>📊 Utilisation système</h3>
                <div class="progress-bars">
                    <div class="progress-item">
                        <span>Modules actifs</span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= ($portal_stats['active_modules'] / $portal_stats['total_modules']) * 100 ?>%"></div>
                        </div>
                        <span><?= $portal_stats['active_modules'] ?>/<?= $portal_stats['total_modules'] ?></span>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h3>🔗 Liens rapides</h3>
                <div class="quick-links">
                    <a href="/user/profile.php" class="quick-link">
                        <span>👤</span> Mon profil
                    </a>
                    <?php if (in_array($user_role, ['admin', 'dev'])): ?>
                    <a href="/admin/" class="quick-link">
                        <span>⚙️</span> Administration
                    </a>
                    <?php endif; ?>
                    <a href="/about.php" class="quick-link">
                        <span>ℹ️</span> À propos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== STYLES SPÉCIFIQUES PAGE D'ACCUEIL ===== */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 3rem;
}

.hero-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.welcome-message h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    font-weight: 300;
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.user-badge.role-admin { background: #f59e0b; }
.user-badge.role-dev { background: #ef4444; }
.user-badge.role-logistique { background: #10b981; }
.user-badge.role-user { background: #6366f1; }

.hero-stats {
    display: flex;
    gap: 2rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.modules-section {
    padding: 2rem 0;
}

.category-section {
    margin-bottom: 3rem;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e5e5;
}

.category-header h2 {
    color: #333;
    font-size: 1.8rem;
    font-weight: 600;
}

.category-stats {
    display: flex;
    gap: 0.5rem;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge.active { background: #dcfce7; color: #166534; }
.badge.development { background: #fef3c7; color: #92400e; }

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.module-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-left: 4px solid var(--module-color);
    transition: all 0.3s ease;
    position: relative;
}

.module-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.module-card.disabled {
    opacity: 0.6;
    transform: none !important;
}

.module-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.module-icon {
    font-size: 2.5rem;
    line-height: 1;
    flex-shrink: 0;
}

.module-title h3 {
    font-size: 1.3rem;
    color: #333;
    margin-bottom: 0.25rem;
}

.module-status-badges {
    display: flex;
    gap: 0.5rem;
}

.status-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 0.75rem;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.dev { background: #fef3c7; color: #92400e; }
.status-badge.beta { background: #dbeafe; color: #1e40af; }
.status-badge.coming-soon { background: #f3e8ff; color: #7c3aed; }

.module-description {
    margin-bottom: 1rem;
}

.module-description p {
    color: #666;
    line-height: 1.5;
}

.module-features ul {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem 0;
}

.module-features li {
    padding: 0.25rem 0;
    color: #555;
    font-size: 0.9rem;
    position: relative;
    padding-left: 1.5rem;
}

.module-features li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--module-color);
    font-weight: bold;
}

.module-features .more-features {
    font-style: italic;
    color: #888;
}

.module-actions .btn {
    width: 100%;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-primary {
    background: var(--module-color);
    color: white;
}

.btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn-disabled, .btn-restricted {
    background: #e5e5e5;
    color: #999;
    cursor: not-allowed;
}

.system-info-section {
    background: #f8f9fa;
    padding: 2rem 0;
    margin-top: 3rem;
}

.system-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.info-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.info-card h3 {
    margin-bottom: 1rem;
    color: #333;
}

.progress-bars {
    space-y: 1rem;
}

.progress-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: #e5e5e5;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #059669;
    transition: width 0.3s ease;
}

.quick-links {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quick-link {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
}

.quick-link:hover {
    background: #e9ecef;
    transform: translateX(4px);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-content {
        flex-direction: column;
        text-align: center;
    }
    
    .hero-stats {
        justify-content: center;
    }
    
    .category-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .modules-grid {
        grid-template-columns: 1fr;
    }
    
    .welcome-message h1 {
        font-size: 2rem;
    }
}
</style>

<?php
// =====================================
// 🎨 INCLUSION FOOTER
// =====================================

if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    echo '</body></html>';
}
?>

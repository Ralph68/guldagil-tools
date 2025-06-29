<?php
/**
 * Titre: Page d'accueil du portail Guldagil avec authentification
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Chargement des dépendances
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/version.php';
require_once __DIR__ . '/../core/auth/AuthManager.php';
require_once __DIR__ . '/../core/middleware/AuthMiddleware.php';

// Initialisation de l'authentification
$auth = AuthManager::getInstance();
$middleware = new AuthMiddleware();

// Vérification de l'authentification (optionnelle pour l'accueil)
$user_authenticated = $auth->isAuthenticated();
$current_user = $user_authenticated ? $auth->getCurrentUser() : null;

// Obtenir les modules accessibles selon les droits utilisateur
if ($user_authenticated) {
    $available_modules = $middleware->getAccessibleModules();
} else {
    // Modules en mode visiteur (accès limité)
    $available_modules = [
        'calculateur' => [
            'name' => 'Calculateur de frais',
            'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
            'icon' => '🧮',
            'color' => 'blue',
            'status' => 'login_required',
            'path' => '/auth/login.php',
            'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique']
        ],
        'adr' => [
            'name' => 'Gestion ADR',
            'description' => 'Transport de marchandises dangereuses - Déclarations et suivi réglementaire',
            'icon' => '⚠️',
            'color' => 'orange',
            'status' => 'login_required',
            'path' => '/auth/login.php',
            'features' => ['Déclarations ADR', 'Gestion des quotas', 'Suivi réglementaire']
        ],
        'controle-qualite' => [
            'name' => 'Contrôle Qualité',
            'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
            'icon' => '✅',
            'color' => 'green',
            'status' => 'development',
            'path' => '#',
            'features' => ['Tests et validations', 'Rapports de conformité', 'Suivi des équipements']
        ],
        'epi' => [
            'name' => 'Équipements EPI',
            'description' => 'Gestion des équipements de protection individuelle - Stock et maintenance',
            'icon' => '🛡️',
            'color' => 'purple',
            'status' => 'development',
            'path' => '#',
            'features' => ['Inventaire EPI', 'Suivi des dates d\'expiration', 'Gestion des commandes']
        ],
        'outillages' => [
            'name' => 'Outillages',
            'description' => 'Gestion des outils et équipements techniques - Maintenance et traçabilité',
            'icon' => '🔧',
            'color' => 'gray',
            'status' => 'development',
            'path' => '#',
            'features' => ['Inventaire outillage', 'Planning maintenance', 'Suivi d\'utilisation']
        ]
    ];
}

// Statistiques pour le dashboard
$stats = [
    'modules_total' => count($available_modules),
    'modules_actifs' => count(array_filter($available_modules, fn($m) => $m['status'] === 'active')),
    'modules_dev' => count(array_filter($available_modules, fn($m) => $m['status'] === 'development')),
    'modules_restricted' => count(array_filter($available_modules, fn($m) => $m['status'] === 'login_required'))
];

// Configuration pour le header modulaire
$page_title = 'Portail Guldagil';
$page_subtitle = 'Portail d\'outils professionnels';
$page_description = 'Portail d\'outils Guldagil - Solutions intégrées pour transport, logistique et gestion des équipements';
$current_module = 'home';
$module_css = false;
$module_js = false;

// Breadcrumbs pour l'accueil
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

$nav_info = $stats['modules_total'] . ' modules disponibles';
$show_admin_footer = $user_authenticated && $auth->hasPermission('admin');

// Variables utilisateur pour le header
if ($user_authenticated) {
    $user_name = $current_user['name'];
} else {
    $user_name = null;
}

// Inclure le header modulaire
include __DIR__ . '/../templates/header.php';
?>

<!-- Section de bienvenue -->
<section class="welcome-section">
    <div class="welcome-content">
        <?php if ($user_authenticated): ?>
            <h2 class="welcome-title">Bienvenue, <?= htmlspecialchars($current_user['name']) ?> !</h2>
            <p class="welcome-description">
                Accédez à vos outils de gestion industrielle selon vos droits d'accès.
                <br><strong>Rôle :</strong> <?= ucfirst($current_user['role']) ?>
                <?php if (!empty($current_user['modules'])): ?>
                    <br><strong>Modules autorisés :</strong> <?= implode(', ', $current_user['modules']) ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <h2 class="welcome-title">Solutions intégrées pour la gestion industrielle</h2>
            <p class="welcome-description">
                Plateforme centralisée pour la gestion des frais de port, marchandises dangereuses ADR, 
                contrôle qualité, équipements EPI et outillages professionnels.
                <br><br>
                <a href="/auth/login.php" class="login-link">
                    <strong>🔐 Connectez-vous pour accéder aux outils</strong>
                </a>
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- Section modules -->
<section class="modules-section">
    <h3 class="section-title">Modules applicatifs</h3>
    
    <?php if (!$user_authenticated): ?>
    <div class="access-notice">
        <div class="notice-content">
            <span class="notice-icon">🔐</span>
            <span class="notice-text">Authentification requise pour accéder aux modules</span>
            <a href="/auth/login.php" class="notice-btn">Se connecter</a>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="modules-grid">
        <?php foreach ($available_modules as $moduleId => $module): ?>
        <article class="module-card <?= getModuleStatusClass($module['status']) ?>" 
                 data-module="<?= $moduleId ?>" 
                 onclick="navigateToModule('<?= $moduleId ?>', '<?= $module['path'] ?>', '<?= $module['status'] ?>')">
            
            <div class="module-header">
                <div class="module-icon module-icon-<?= $module['color'] ?>">
                    <span class="icon"><?= $module['icon'] ?></span>
                </div>
                <div class="module-meta">
                    <h4 class="module-name"><?= htmlspecialchars($module['name']) ?></h4>
                    <span class="module-status status-<?= $module['status'] ?>">
                        <?= getStatusLabel($module['status']) ?>
                    </span>
                </div>
            </div>
            
            <div class="module-body">
                <p class="module-description">
                    <?= htmlspecialchars($module['description']) ?>
                </p>
                
                <ul class="module-features">
                    <?php foreach ($module['features'] as $feature): ?>
                    <li><?= getFeatureIcon($module['status']) ?> <?= htmlspecialchars($feature) ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (isset($module['reason'])): ?>
                <div class="module-restriction">
                    <span class="restriction-icon">⚠️</span>
                    <span class="restriction-text"><?= htmlspecialchars($module['reason']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="module-footer">
                <?php if ($module['status'] === 'active'): ?>
                    <a href="<?= $module['path'] ?>" class="module-button btn-primary">
                        <span class="btn-text">Accéder</span>
                        <span class="btn-icon">→</span>
                    </a>
                <?php elseif ($module['status'] === 'login_required'): ?>
                    <a href="<?= $module['path'] ?>" class="module-button btn-warning">
                        <span class="btn-text">Se connecter</span>
                        <span class="btn-icon">🔐</span>
                    </a>
                <?php elseif ($module['status'] === 'development'): ?>
                    <span class="module-button btn-secondary">
                        <span class="btn-text">En développement</span>
                        <span class="btn-icon">🚧</span>
                    </span>
                <?php elseif ($module['status'] === 'disabled'): ?>
                    <span class="module-button btn-disabled">
                        <span class="btn-text">Accès insuffisant</span>
                        <span class="btn-icon">🚫</span>
                    </span>
                <?php else: ?>
                    <span class="module-button btn-disabled">
                        <span class="btn-text">Bientôt disponible</span>
                    </span>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Section statistiques pour les utilisateurs connectés -->
<?php if ($user_authenticated): ?>
<section class="dashboard-section">
    <h3 class="section-title">Tableau de bord</h3>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-number"><?= $stats['modules_actifs'] ?></div>
            <div class="stat-label">Modules actifs</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🚧</div>
            <div class="stat-number"><?= $stats['modules_dev'] ?></div>
            <div class="stat-label">En développement</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏱️</div>
            <div class="stat-number"><?= date('H:i') ?></div>
            <div class="stat-label">Heure actuelle</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👤</div>
            <div class="stat-number"><?= count($current_user['modules']) ?></div>
            <div class="stat-label">Vos accès</div>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
/* Styles additionnels pour l'authentification */
.login-link {
    color: var(--primary-blue);
    text-decoration: none;
    padding: var(--spacing-sm) var(--spacing-lg);
    background: rgba(59, 130, 246, 0.1);
    border-radius: var(--radius-md);
    display: inline-block;
    margin-top: var(--spacing-md);
    transition: var(--transition-normal);
}

.login-link:hover {
    background: rgba(59, 130, 246, 0.2);
    transform: translateY(-1px);
}

.access-notice {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(79, 70, 229, 0.1));
    border: 2px solid rgba(59, 130, 246, 0.2);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
    text-align: center;
}

.notice-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.notice-icon {
    font-size: var(--font-size-xl);
}

.notice-text {
    color: var(--gray-700);
    font-weight: 500;
}

.notice-btn {
    background: var(--primary-blue);
    color: white;
    padding: var(--spacing-sm) var(--spacing-lg);
    border-radius: var(--radius-md);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition-normal);
    min-height: var(--touch-target);
    display: flex;
    align-items: center;
}

.notice-btn:hover {
    background: var(--primary-blue-dark);
    transform: translateY(-1px);
}

/* Classes de statut pour les modules */
.module-login-required {
    border-left: 4px solid var(--color-warning);
    opacity: 0.8;
}

.module-disabled {
    border-left: 4px solid var(--gray-400);
    opacity: 0.6;
}

.module-restriction {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: var(--radius-sm);
    padding: var(--spacing-sm);
    margin-top: var(--spacing-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.restriction-text {
    font-size: var(--font-size-sm);
    color: var(--color-warning);
    font-weight: 500;
}

/* Boutons spécialisés */
.btn-warning {
    background: var(--color-warning);
    color: white;
    border: 1px solid var(--color-warning);
}

.btn-warning:hover {
    background: #d97706;
    border-color: #d97706;
}

/* Dashboard */
.dashboard-section {
    margin-top: var(--spacing-2xl);
    padding-top: var(--spacing-xl);
    border-top: 1px solid var(--gray-200);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
}

.stat-card {
    background: white;
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    text-align: center;
    border: 1px solid var(--gray-200);
    transition: var(--transition-normal);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: var(--spacing-sm);
}

.stat-number {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: var(--spacing-xs);
}

.stat-label {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
    font-weight: 500;
}
</style>

<script>
// Configuration JavaScript avec données utilisateur
window.PortalConfig = Object.assign(window.PortalConfig || {}, {
    userAuthenticated: <?= $user_authenticated ? 'true' : 'false' ?>,
    userRole: '<?= $current_user['role'] ?? 'guest' ?>',
    userName: '<?= htmlspecialchars($current_user['name'] ?? 'Invité') ?>',
    availableModules: <?= json_encode(array_keys($available_modules)) ?>
});

// Gestionnaire de navigation spécialisé
function navigateToModule(moduleId, path, status) {
    console.log(`Navigation vers ${moduleId} (${status})`);
    
    switch (status) {
        case 'login_required':
            window.location.href = '/auth/login.php?redirect=' + encodeURIComponent(window.location.pathname);
            break;
            
        case 'development':
            if (confirm(`Le module "${moduleId}" est en développement.\nVoulez-vous continuer vers l'aperçu ?`)) {
                window.location.href = path !== '#' ? path : '#';
            }
            break;
            
        case 'disabled':
            alert(`Accès insuffisant au module "${moduleId}".\nContactez l'administrateur pour obtenir les droits nécessaires.`);
            break;
            
        case 'active':
            // Animation de chargement
            const card = event.currentTarget;
            card.style.opacity = '0.7';
            card.style.pointerEvents = 'none';
            
            setTimeout(() => {
                window.location.href = path;
            }, 200);
            break;
            
        default:
            console.warn('Statut de module inconnu:', status);
    }
}

// Extension des fonctions utilitaires pour l'auth
window.PortalUtils = Object.assign(window.PortalUtils || {}, {
    logout: function() {
        if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
            window.location.href = '/auth/logout.php';
        }
    },
    
    goToLogin: function() {
        window.location.href = '/auth/login.php';
    }
});
</script>

<?php
// Fonctions utilitaires pour le rendu
function getModuleStatusClass($status) {
    switch ($status) {
        case 'active':
            return 'module-active';
        case 'development':
            return 'module-development';
        case 'login_required':
            return 'module-login-required';
        case 'disabled':
            return 'module-disabled';
        default:
            return '';
    }
}

function getStatusLabel($status) {
    switch ($status) {
        case 'active':
            return 'Actif';
        case 'development':
            return 'En développement';
        case 'login_required':
            return 'Connexion requise';
        case 'disabled':
            return 'Accès insuffisant';
        default:
            return 'Non disponible';
    }
}

function getFeatureIcon($status) {
    switch ($status) {
        case 'active':
            return '✓';
        case 'login_required':
            return '🔐';
        case 'disabled':
            return '🚫';
        default:
            return '○';
    }
}

// Inclure le footer modulaire
include __DIR__ . '/../templates/footer.php';
?>

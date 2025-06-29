<?php
/**
 * Titre: Page d'accueil du portail Guldagil - Complète sécurisée
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chargement sécurisé de la configuration
if (!file_exists(__DIR__ . '/../config/config.php')) {
    die('<h1>❌ Erreur Configuration</h1><p>Le fichier config.php est manquant dans /config/</p>');
}
if (!file_exists(__DIR__ . '/../config/version.php')) {
    die('<h1>❌ Erreur Version</h1><p>Le fichier version.php est manquant dans /config/</p>');
}

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/version.php';
} catch (Exception $e) {
    die('<h1>❌ Erreur de chargement</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// AUTHENTIFICATION REQUISE
session_start();

// Vérifier authentification avec système AuthManager si disponible
$user_authenticated = false;
$current_user = null;

if (file_exists(__DIR__ . '/../core/auth/AuthManager.php')) {
    require_once __DIR__ . '/../core/auth/AuthManager.php';
    $auth = AuthManager::getInstance();
    $user_authenticated = $auth->isAuthenticated();
    $current_user = $user_authenticated ? $auth->getCurrentUser() : null;
} else {
    // Fallback session basique
    $user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    $current_user = $user_authenticated ? ($_SESSION['user'] ?? ['name' => 'Utilisateur', 'role' => 'user']) : null;
}

// REDIRECTION si non connecté
if (!$user_authenticated) {
    header('Location: /auth/login.php');
    exit;
}

// Variables pour le template
$page_title = 'Portail Guldagil';
$page_subtitle = 'Solutions professionnelles';
$page_description = 'Portail de gestion - Calcul frais, ADR, contrôle qualité';
$current_module = 'home';
$module_css = false;
$module_js = false;

// Fil d'Ariane
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

$nav_info = 'Tableau de bord principal';
$show_admin_footer = true;

// Modules disponibles - COMPLET avec tous les modules
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'path' => '/calculateur/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique'],
        'min_role' => 'user'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses - Déclarations et suivi réglementaire',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'development',
        'path' => '#',
        'features' => ['Déclarations ADR', 'Gestion des quotas', 'Suivi réglementaire'],
        'min_role' => 'user'
    ],
    'controle-qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
        'icon' => '✅',
        'color' => 'green',
        'status' => 'development',
        'path' => '#',
        'features' => ['Tests et validations', 'Rapports de conformité', 'Suivi des équipements'],
        'min_role' => 'user'
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection individuelle - Stock et maintenance',
        'icon' => '🛡️',
        'color' => 'purple',
        'status' => 'development',
        'path' => '#',
        'features' => ['Inventaire EPI', 'Suivi des dates d\'expiration', 'Gestion des commandes'],
        'min_role' => 'user'
    ],
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion des outils et équipements techniques - Maintenance et traçabilité',
        'icon' => '🔧',
        'color' => 'gray',
        'status' => 'development',
        'path' => '#',
        'features' => ['Inventaire outils', 'Planning de maintenance', 'Traçabilité et historique'],
        'min_role' => 'user'
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et maintenance du portail - Gestion des utilisateurs et paramètres',
        'icon' => '⚙️',
        'color' => 'red',
        'status' => 'admin_only',
        'path' => '/admin/',
        'features' => ['Configuration portail', 'Gestion utilisateurs', 'Maintenance système'],
        'min_role' => 'admin'
    ]
];

// Filtrer modules selon droits utilisateur
$roles = ['user' => 1, 'admin' => 2, 'dev' => 3];
$userLevel = $roles[$current_user['role']] ?? 1;

$accessible_modules = array_filter($available_modules, function($module) use ($userLevel, $roles) {
    $requiredLevel = $roles[$module['min_role']] ?? 999;
    return $userLevel >= $requiredLevel;
});

// Fonctions utilitaires
function getStatusLabel($status) {
    return match($status) {
        'active' => 'Disponible',
        'development' => 'En développement',
        'admin_only' => 'Administrateur',
        'maintenance' => 'Maintenance',
        default => 'Non disponible'
    };
}

function getModuleStatusClass($status) {
    return match($status) {
        'active' => 'module-available',
        'development' => 'module-dev',
        'admin_only' => 'module-admin',
        'maintenance' => 'module-maintenance',
        default => 'module-disabled'
    };
}

// Inclure le header si disponible, sinon header intégré
if (file_exists(__DIR__ . '/../templates/header.php')) {
    include __DIR__ . '/../templates/header.php';
} else {
    // Header intégré avec logo
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Version <?= APP_VERSION ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="<?= APP_AUTHOR ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= BUILD_NUMBER ?>">
    
    <style>
        /* CSS critique intégré pour éviter FOUC */
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --gray-100: #f7fafc;
            --gray-200: #e5e7eb;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition-normal: 0.3s ease;
        }
        
        /* Couleurs modules */
        .module-icon-blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
        .module-icon-orange { background: linear-gradient(135deg, #fed7aa, #fdba74); }
        .module-icon-green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .module-icon-purple { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); }
        .module-icon-gray { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); }
        .module-icon-red { background: linear-gradient(135deg, #fecaca, #fca5a5); }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="portal-header">
        <div class="header-container container">
            <div class="header-brand">
                <?php if (file_exists(__DIR__ . '/assets/img/logo-guldagil.png')): ?>
                <img src="/assets/img/logo-guldagil.png" alt="Logo Guldagil" class="portal-logo">
                <?php endif; ?>
                
                <div class="brand-info">
                    <h1 class="portal-title"><?= htmlspecialchars($page_title) ?></h1>
                    <p class="portal-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="version-badge">
                    <span class="version-text">v<?= APP_VERSION ?></span>
                </div>
                
                <div class="user-area">
                    <span class="user-icon">👤</span>
                    <div class="user-text">
                        <div><strong><?= htmlspecialchars($current_user['name'] ?? 'Utilisateur') ?></strong></div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">
                            <?= htmlspecialchars($current_user['role'] ?? 'user') ?>
                        </div>
                    </div>
                    <a href="/auth/logout.php" style="color: white; text-decoration: none; margin-left: 1rem; font-size: 0.9rem;">
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="portal-nav">
        <div class="nav-container container">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <li class="breadcrumb-item <?= $crumb['active'] ? 'active' : '' ?>">
                    <span><?= $crumb['icon'] ?></span>
                    <?= $crumb['active'] ? $crumb['text'] : '<a href="' . $crumb['url'] . '">' . $crumb['text'] . '</a>' ?>
                </li>
                <?php if ($index < count($breadcrumbs) - 1): ?>
                <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                <?php endforeach; ?>
            </ol>
            
            <div class="nav-info">
                <?= htmlspecialchars($nav_info) ?>
            </div>
        </div>
    </nav>
<?php } ?>

    <!-- Contenu principal -->
    <main class="portal-main">
        <div class="main-container container">
            <!-- Section bienvenue -->
            <section class="welcome-section">
                <h2 class="welcome-title">Bienvenue sur le portail Guldagil</h2>
                <p class="welcome-description">
                    Votre plateforme centralisée pour la gestion des frais de transport, 
                    des marchandises dangereuses et du contrôle qualité. Sélectionnez un module ci-dessous pour commencer.
                </p>
            </section>

            <!-- Section modules -->
            <section class="modules-section">
                <h3 class="section-title">Modules applicatifs</h3>
                
                <div class="modules-grid">
                    <?php foreach ($accessible_modules as $moduleId => $module): ?>
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
                            
                            <?php if (!empty($module['features'])): ?>
                            <ul class="module-features">
                                <?php foreach ($module['features'] as $feature): ?>
                                <li><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            
                            <?php if ($module['status'] === 'development'): ?>
                            <div class="dev-notice">
                                <span>🚧</span> Module en cours de développement
                            </div>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Statistiques rapides (si connecté en admin/dev) -->
            <?php if (in_array($current_user['role'], ['admin', 'dev'])): ?>
            <section class="stats-section">
                <h3 class="section-title">Aperçu système</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= count($accessible_modules) ?></div>
                            <div class="stat-label">Modules accessibles</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔧</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= APP_VERSION ?></div>
                            <div class="stat-label">Version portail</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">👤</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= ucfirst($current_user['role']) ?></div>
                            <div class="stat-label">Niveau d'accès</div>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>

<?php
// Inclure le footer si disponible, sinon footer intégré
if (file_exists(__DIR__ . '/../templates/footer.php')) {
    include __DIR__ . '/../templates/footer.php';
} else {
?>
    <!-- Footer -->
    <footer class="portal-footer">
        <div class="footer-container container">
            <div class="footer-info">
                <div class="footer-brand">
                    <div class="footer-title"><?= APP_NAME ?></div>
                    <div class="footer-copyright">&copy; <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?></div>
                </div>
                <div class="footer-tech">
                    <div class="version-info">
                        Version <?= APP_VERSION ?><br>
                        Build <?= BUILD_NUMBER ?>
                    </div>
                    <div class="build-info">
                        <?= BUILD_DATE ?>
                    </div>
                </div>
            </div>
            
            <?php if ($show_admin_footer && in_array($current_user['role'], ['admin', 'dev'])): ?>
            <div class="admin-footer">
                <div class="admin-links">
                    <a href="/admin/" class="admin-link">⚙️ Administration</a>
                    <a href="/admin/maintenance.php" class="admin-link">🔧 Maintenance</a>
                    <?php if ($current_user['role'] === 'dev'): ?>
                    <a href="/admin/dev-tools.php" class="admin-link">🛠️ Outils dev</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </footer>

    <script>
        // Navigation sécurisée
        function navigateToModule(moduleId, path, status) {
            if (status === 'development') {
                if (confirm('Ce module est en développement. Continuer ?')) {
                    // En dev, on peut quand même naviguer pour tests
                    if (path && path !== '#') {
                        window.location.href = path;
                    }
                }
                return;
            }
            
            if (status === 'maintenance') {
                alert('Module temporairement indisponible (maintenance en cours)');
                return;
            }
            
            if (path && path !== '#') {
                window.location.href = path;
            }
        }

        // Animation d'apparition
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.module-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
<?php } ?>

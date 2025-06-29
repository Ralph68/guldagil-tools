<?php
/**
 * Titre: Page d'accueil du portail Guldagil - Version corrigée
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Protection et initialisation
session_start();

// Configuration des erreurs selon l'environnement
$is_production = (getenv('APP_ENV') === 'production');
if (!$is_production) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Définition du chemin racine
define('ROOT_PATH', dirname(__DIR__));

// Chargement sécurisé de la configuration
$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Erreur Configuration</h1><p>Fichier manquant : ' . basename($file) . '</p>');
    }
}

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
} catch (Exception $e) {
    http_response_code(500);
    $error_msg = $is_production ? 'Erreur de configuration' : htmlspecialchars($e->getMessage());
    die('<h1>❌ Erreur</h1><p>' . $error_msg . '</p>');
}

// AUTHENTIFICATION REQUISE
$user_authenticated = false;
$current_user = null;

// Système d'authentification modulaire
if (function_exists('AuthManager') && class_exists('AuthManager')) {
    $auth = AuthManager::getInstance();
    $user_authenticated = $auth->isAuthenticated();
    $current_user = $user_authenticated ? $auth->getCurrentUser() : null;
} else {
    // Fallback session basique
    $user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    $current_user = $user_authenticated ? ($_SESSION['user'] ?? null) : null;
}

// Redirection si non authentifié
if (!$user_authenticated) {
    header('Location: /auth/login.php');
    exit;
}

// Sécurisation utilisateur par défaut
if (!$current_user) {
    $current_user = [
        'username' => 'Utilisateur',
        'name' => 'Utilisateur',
        'role' => 'user',
        'level' => 1
    ];
}

// Vérification connexion BDD
if (!isset($db) || !($db instanceof PDO)) {
    http_response_code(500);
    die('<h1>❌ Erreur Base de Données</h1><p>Connexion non disponible</p>');
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
$show_admin_footer = ($current_user['role'] ?? 'user') === 'admin';

// Configuration des rôles et niveaux d'accès
$roles = [
    'guest' => 0,
    'user' => 1,
    'manager' => 2,
    'admin' => 3,
    'superadmin' => 4
];

$user_level = $roles[$current_user['role']] ?? 1;

// Modules disponibles avec configuration complète
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'path' => '/calculateur/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique'],
        'min_role' => 'user',
        'category' => 'transport'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Module de gestion des matières dangereuses et réglementation ADR',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'development',
        'path' => '/adr/',
        'features' => ['Classification matières', 'Documents réglementaires', 'Formation'],
        'min_role' => 'user',
        'category' => 'reglementation'
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Système de gestion et suivi qualité produits',
        'icon' => '🔬',
        'color' => 'green',
        'status' => 'development',
        'path' => '/qualite/',
        'features' => ['Contrôles produits', 'Rapports qualité', 'Traçabilité'],
        'min_role' => 'manager',
        'category' => 'production'
    ],
    'inventory' => [
        'name' => 'Gestion Stocks',
        'description' => 'Système de gestion des stocks et inventaires',
        'icon' => '📦',
        'color' => 'purple',
        'status' => 'development',
        'path' => '/inventory/',
        'features' => ['Suivi stocks', 'Alertes', 'Mouvements'],
        'min_role' => 'user',
        'category' => 'logistique'
    ],
    'achats' => [
        'name' => 'Gestion Achats',
        'description' => 'Module de gestion des achats et fournisseurs',
        'icon' => '💰',
        'color' => 'green',
        'status' => 'development',
        'path' => '/achats/',
        'features' => ['Commandes', 'Fournisseurs', 'Budget'],
        'min_role' => 'manager',
        'category' => 'finance'
    ],
    'reporting' => [
        'name' => 'Rapports & Analytics',
        'description' => 'Tableaux de bord et rapports d\'activité',
        'icon' => '📊',
        'color' => 'blue',
        'status' => 'development',
        'path' => '/reporting/',
        'features' => ['Dashboards', 'Exports', 'KPI'],
        'min_role' => 'manager',
        'category' => 'analyse'
    ],
    'maintenance' => [
        'name' => 'Maintenance Système',
        'description' => 'Outils de maintenance et optimisation du portail',
        'icon' => '🔧',
        'color' => 'gray',
        'status' => 'admin_only',
        'path' => '/admin/maintenance.php',
        'features' => ['Diagnostics', 'Optimisation', 'Logs'],
        'min_role' => 'admin',
        'category' => 'technique'
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Panel d\'administration du portail',
        'icon' => '⚙️',
        'color' => 'red',
        'status' => 'admin_only',
        'path' => '/admin/',
        'features' => ['Utilisateurs', 'Configuration', 'Sécurité'],
        'min_role' => 'admin',
        'category' => 'administration'
    ]
];

// Filtrage des modules selon les droits utilisateur
$accessible_modules = array_filter($available_modules, function($module) use ($user_level, $roles) {
    $required_level = $roles[$module['min_role']] ?? 999;
    return $user_level >= $required_level;
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

function getModulesByCategory($modules) {
    $categories = [];
    foreach ($modules as $key => $module) {
        $category = $module['category'] ?? 'autres';
        $categories[$category][] = array_merge($module, ['key' => $key]);
    }
    return $categories;
}

// Groupement par catégories
$modules_by_category = getModulesByCategory($accessible_modules);

// Variables pour le footer
$version_info = [
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5-beta',
    'build' => defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000',
    'formatted_date' => defined('BUILD_DATE') ? BUILD_DATE : date('d/m/Y H:i')
];

// Inclure le header
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Header de fallback intégré
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Version <?= htmlspecialchars($version_info['version']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="Jean-Thomas RUNSER">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= htmlspecialchars($version_info['build']) ?>">
    
    <style>
        /* CSS de base intégré */
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --radius-lg: 0.75rem;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
            margin: 0;
            padding: 0;
            color: var(--gray-900);
        }
        
        .portal-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            color: white;
            padding: var(--spacing-md);
            box-shadow: var(--shadow-lg);
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .brand-info h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <header class="portal-header">
        <div class="header-container">
            <div class="brand-info">
                <h1><?= htmlspecialchars($page_title) ?></h1>
                <p><?= htmlspecialchars($page_subtitle) ?></p>
            </div>
            <div class="user-section">
                <span>👤</span>
                <span><?= htmlspecialchars($current_user['username']) ?></span>
                <span>(<?= htmlspecialchars($current_user['role']) ?>)</span>
            </div>
        </div>
    </header>
    <main style="padding: var(--spacing-xl); max-width: 1400px; margin: 0 auto;">
<?php } ?>

<!-- Contenu principal de la page d'accueil -->
<div class="welcome-section">
    <div class="welcome-header">
        <h2>👋 Bienvenue, <?= htmlspecialchars($current_user['username']) ?></h2>
        <p class="welcome-subtitle">Accédez à vos outils professionnels depuis ce portail centralisé</p>
    </div>
</div>

<!-- Modules par catégories -->
<?php foreach ($modules_by_category as $category => $modules): ?>
    <section class="modules-section">
        <h3 class="section-title">
            <?= ucfirst(str_replace('_', ' ', $category)) ?>
            <span class="module-count">(<?= count($modules) ?> module<?= count($modules) > 1 ? 's' : '' ?>)</span>
        </h3>
        
        <div class="modules-grid">
            <?php foreach ($modules as $module): ?>
                <div class="module-card <?= getModuleStatusClass($module['status']) ?> module-<?= htmlspecialchars($module['color']) ?>">
                    <div class="module-header">
                        <div class="module-icon module-icon-<?= htmlspecialchars($module['color']) ?>">
                            <?= $module['icon'] ?>
                        </div>
                        <div class="module-status">
                            <span class="status-badge status-<?= htmlspecialchars($module['status']) ?>">
                                <?= getStatusLabel($module['status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="module-content">
                        <h4 class="module-title"><?= htmlspecialchars($module['name']) ?></h4>
                        <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                        
                        <?php if (!empty($module['features'])): ?>
                            <ul class="module-features">
                                <?php foreach (array_slice($module['features'], 0, 3) as $feature): ?>
                                    <li>• <?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <div class="module-actions">
                        <?php if ($module['status'] === 'active'): ?>
                            <a href="<?= htmlspecialchars($module['path']) ?>" class="module-btn btn-primary">
                                Accéder au module
                            </a>
                        <?php elseif ($module['status'] === 'development'): ?>
                            <button class="module-btn btn-disabled" disabled>
                                En développement
                            </button>
                        <?php else: ?>
                            <button class="module-btn btn-secondary" onclick="showModuleInfo('<?= htmlspecialchars($module['key']) ?>')">
                                Plus d'infos
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<!-- Section statistiques système pour les admins -->
<?php if ($show_admin_footer): ?>
    <section class="admin-section">
        <h3 class="section-title">📊 Aperçu système</h3>
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
                    <div class="stat-value"><?= htmlspecialchars($version_info['version']) ?></div>
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
            <div class="stat-card">
                <div class="stat-icon">🕒</div>
                <div class="stat-info">
                    <div class="stat-value"><?= date('H:i') ?></div>
                    <div class="stat-label">Heure serveur</div>
                </div>
            </div>
        </div>
        
        <div class="admin-actions">
            <a href="/admin/" class="admin-btn">🛠️ Administration</a>
            <a href="/admin/maintenance.php" class="admin-btn">🔧 Maintenance</a>
            <a href="/admin/logs.php" class="admin-btn">📋 Logs système</a>
        </div>
    </section>
<?php endif; ?>

<?php
// Inclure le footer si disponible, sinon footer intégré
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    // Footer de fallback
?>
    </main>
    
    <!-- Footer intégré -->
    <footer style="background: var(--gray-100); padding: var(--spacing-lg); margin-top: var(--spacing-xl); border-top: 1px solid var(--gray-200);">
        <div style="max-width: 1400px; margin: 0 auto; text-align: center;">
            <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                <span>Version: <?= htmlspecialchars($version_info['version']) ?></span>
                <span style="margin: 0 1rem;">|</span>
                <span>Build: #<?= substr($version_info['build'], -8) ?></span>
                <span style="margin: 0 1rem;">|</span>
                <span><?= htmlspecialchars($version_info['formatted_date']) ?></span>
            </p>
            <p style="margin: 0.5rem 0 0; color: var(--gray-600); font-size: 0.875rem;">
                &copy; <?= date('Y') ?> Jean-Thomas RUNSER - Guldagil
            </p>
        </div>
    </footer>

    <!-- JavaScript intégré -->
    <script>
        // Fonction de retour à l'accueil
        function goHome() {
            if (window.location.pathname !== '/' && window.location.pathname !== '/index.php') {
                window.location.href = '/';
            }
        }

        // Affichage d'infos module
        function showModuleInfo(moduleKey) {
            alert('Module en cours de développement. Plus d\'informations bientôt disponibles.');
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Portail Guldagil v<?= htmlspecialchars($version_info['version']) ?> chargé');
            
            // Performance: préchargement des assets critiques
            const criticalPaths = ['/assets/css/portal.css', '/calculateur/'];
            criticalPaths.forEach(path => {
                if (path.endsWith('.css')) {
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = path;
                    document.head.appendChild(link);
                }
            });
        });
    </script>
</body>
</html>
<?php } ?>

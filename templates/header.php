<?php
/**
 * Titre: Header du portail Guldagil - Version fusionnée modulaire complète
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Gestion erreur simple
if (file_exists(ROOT_PATH . '/config/error_handler_simple.php')) {
    require_once ROOT_PATH . '/config/error_handler_simple.php';
}

// Configuration modules avec couleurs et icônes
$all_modules = [
    'port' => [
        'name' => 'Frais de port',
        'icon' => '🚛',
        'color' => '#3498db',
        'status' => 'active',
        'routes' => ['port', 'calculateur', 'frais']
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'icon' => '⚠️',
        'color' => '#e74c3c',
        'status' => 'active',
        'routes' => ['adr', 'dangereuses']
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'icon' => '🦺',
        'color' => '#f39c12',
        'status' => 'development',
        'routes' => ['epi', 'equipements']
    ],
    'outillages' => [
        'name' => 'Outillages',
        'icon' => '🔧',
        'color' => '#95a5a6',
        'status' => 'development',
        'routes' => ['outillages', 'outils']
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'icon' => '✅',
        'color' => '#2ecc71',
        'status' => 'development',
        'routes' => ['qualite', 'controle-qualite']
    ],
    'admin' => [
        'name' => 'Administration',
        'icon' => '⚙️',
        'color' => '#9b59b6',
        'status' => 'active',
        'routes' => ['admin', 'administration'],
        'auth_required' => true
    ]
];

// --- Authentification utilisateur (identique ancien + nouveau) ---
$user_authenticated = false;
$current_user = null;

try {
    // Tentative AuthManager
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = new AuthManager();
        if ($auth->isAuthenticated()) {
            $user_authenticated = true;
            $current_user = $auth->getCurrentUser();
        }
    } else {
        // Fallback session simple
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            $user_authenticated = true;
            $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
        }
    }
} catch (Exception $e) {
    error_log("Erreur auth header: " . $e->getMessage());
    // Continue sans auth si erreur
}

// --- Variables fallback sécurisées ---
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
$page_description = htmlspecialchars($page_description ?? 'Portail de gestion');
$current_module = htmlspecialchars($current_module ?? 'home');
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// --- Détection automatique du module depuis l'URL si non défini ou "home" ---
if ($current_module === 'home') {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path_parts = explode('/', trim($request_uri, '/'));
    $first_segment = $path_parts[0] ?? '';
    foreach ($all_modules as $module_key => $module_data) {
        if (in_array($first_segment, $module_data['routes'])) {
            $current_module = $module_key;
            break;
        }
    }
}

// --- Fil d'Ariane par défaut ---
$breadcrumbs = $breadcrumbs ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// --- CSS et JS modulaire : par défaut toujours chargé (conserve compatibilité) ---
$module_css = $module_css ?? true;
$module_js = $module_js ?? true;

// --- Titre complet ---
$full_title = $page_title . ' - Guldagil v' . $app_version;

// --- Icône, couleur, statut du module courant (conserve fallback pour anciens modules) ---
$module_icon = $all_modules[$current_module]['icon'] ?? match($current_module) {
    'calculateur' => '🚛',
    'adr' => '⚠️',
    'admin' => '⚙️',
    'qualite' => '✅',
    'maintenance' => '🔧',
    'stats' => '📊',
    'user', 'profile' => '👤',
    default => '🏠'
};
$module_color = $all_modules[$current_module]['color'] ?? '#3182ce';
$module_status = $all_modules[$current_module]['status'] ?? 'active';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $page_description ?>">
    <meta name="author" content="<?= $app_author ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="<?= $module_color ?>">
    
    <title><?= $full_title ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

    <!-- CSS principal du portail -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <!-- CSS Header et Footer globaux -->
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <!-- CSS Composants globaux -->
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">

    <!-- Couleur dynamique du module via CSS variable -->
    <style>
        :root {
            --current-module-color: <?= $module_color ?>;
            --current-module-color-light: <?= $module_color ?>20;
            --current-module-color-dark: <?= $module_color ?>dd;
        }
    </style>
    
    <!-- CSS spécifique au module (compatible ancien ET nouveau système) -->
    <?php
    $module_css_loaded = false;
if ($module_css && $current_module !== 'home') {
    // Nouveau chemin
    $module_css_path = "/$current_module/assets/css/$current_module.css";
    $module_css_file = ROOT_PATH . $module_css_path;

    // DEBUG CHEMIN CSS
    echo "<!-- ROOT_PATH = " . ROOT_PATH . " -->";
    echo "<!-- Test file_exists: $module_css_file -->";

    if (file_exists($module_css_file)) {
        echo '<link rel="stylesheet" href="' . $module_css_path . '?v=' . $build_number . '">';
        $module_css_loaded = true;
    }
    if (!$module_css_loaded) {
        $legacy_paths = [
            "/{$current_module}/assets/css/{$current_module}.css",
            "/assets/css/modules/{$current_module}.css"
        ];
        foreach ($legacy_paths as $css_path) {
            $legacy_file = ROOT_PATH . $css_path;
            echo "<!-- Test legacy file_exists: $legacy_file -->";
            if (file_exists($legacy_file)) {
                echo '<link rel="stylesheet" href="' . $css_path . '?v=' . $build_number . '">';
                break;
            }
        }
    }
}

    ?>
</head>
<body data-module="<?= $current_module ?>" data-module-status="<?= $module_status ?>">
    <!-- Header principal -->
    <header class="portal-header">
        <div class="header-container">
            <!-- Logo et branding -->
            <a href="/" class="header-brand">
                <div class="header-logo">
                    <?php if (file_exists(ROOT_PATH . '/assets/img/logo.png')): ?>
                        <img src="/assets/img/logo.png" alt="Logo" width="32" height="32">
                    <?php else: ?>
                        🌊
                    <?php endif; ?>
                </div>
                <div class="header-brand-text"><?= $app_name ?></div>
            </a>

            <!-- Informations page courante -->
            <div class="header-page-info">
                <h1 class="page-main-title">
                    <span class="module-icon" style="color: <?= $module_color ?>"><?= $module_icon ?></span>
                    <?= $page_title ?>
                    <?php if ($module_status === 'development'): ?>
                        <span class="status-badge development">DEV</span>
                    <?php elseif ($module_status === 'beta'): ?>
                        <span class="status-badge beta">BETA</span>
                    <?php endif; ?>
                </h1>
                <?php if (!empty($page_subtitle)): ?>
                <div class="page-subtitle">
                    <span class="page-subtitle-text"><?= $page_subtitle ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Navigation utilisateur -->
            <?php if ($user_authenticated && $current_user): ?>
            <div class="header-user-nav">
                <a href="#" class="user-menu-trigger" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar">
                        <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="user-role"><?= ucfirst($current_user['role'] ?? 'user') ?></div>
                    </div>
                    <div class="dropdown-icon">▼</div>
                </a>
                <div class="user-dropdown" role="menu" aria-hidden="true">
                    <div class="dropdown-header">
                        <div class="dropdown-user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="dropdown-user-email"><?= htmlspecialchars($current_user['email'] ?? '') ?></div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-section">
                        <a href="/user/profile.php" class="dropdown-item">
                            <div class="dropdown-item-icon">👤</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Mon profil</div>
                                <div class="dropdown-subtitle">Paramètres personnels</div>
                            </div>
                        </a>
                        <a href="/user/" class="dropdown-item">
                            <div class="dropdown-item-icon">🏠</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Dashboard</div>
                                <div class="dropdown-subtitle">Vue d'ensemble</div>
                            </div>
                        </a>
                    </div>
                    <?php if (($current_user['role'] ?? 'user') === 'admin'): ?>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-section">
                            <a href="/admin/" class="dropdown-item">
                                <div class="dropdown-item-icon">🔧</div>
                                <div class="dropdown-item-text">
                                    <div class="dropdown-title">Administration</div>
                                    <div class="dropdown-subtitle">Gestion du portail</div>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-section">
                        <a href="/auth/logout.php" class="dropdown-item" style="color: #dc2626;">
                            <div class="dropdown-item-icon">🚪</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Déconnexion</div>
                                <div class="dropdown-subtitle">Fermer la session</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="header-auth-actions">
                <a href="/auth/login.php" class="login-btn">
                    <span>🔐</span>
                    Connexion
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Menu modules horizontal (nouveauté) -->
    <nav class="modules-nav">
        <div class="modules-container">
            <div class="modules-list">
                <?php foreach ($all_modules as $module_key => $module_data): ?>
                    <?php 
                    // Gestion droits module selon rôle
                    $user_role = $current_user['role'] ?? 'guest';
                    $has_access = false;
                    if ($user_role === 'dev') {
                        $has_access = true; // Accès total
                    } elseif ($user_role === 'admin') {
                        $has_access = in_array($module_data['status'], ['active', 'beta']);
                    } else {
                        $has_access = ($module_data['status'] === 'active');
                    }
                    if ($has_access): ?>
                        <a href="/<?= $module_key ?>/" 
                           class="module-nav-item <?= $current_module === $module_key ? 'active' : '' ?>"
                           data-module="<?= $module_key ?>"
                           style="--module-color: <?= $module_data['color'] ?>">
                            <span class="module-nav-icon"><?= $module_data['icon'] ?></span>
                            <span class="module-nav-name"><?= $module_data['name'] ?></span>
                            <?php if ($module_data['status'] === 'development'): ?>
                                <span class="status-badge dev">DEV</span>
                            <?php elseif ($module_data['status'] === 'beta'): ?>
                                <span class="status-badge beta">β</span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <!-- Menu burger mobile -->
            <button class="mobile-menu-toggle" aria-label="Menu modules">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <!-- Fil d'Ariane sticky si >1 élément -->
    <?php if (count($breadcrumbs) > 1): ?>
    <nav class="breadcrumb-nav sticky">
        <div class="breadcrumb-container">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                <?php if (!empty($crumb['url']) && !($crumb['active'] ?? false)): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item">
                        <?= $crumb['icon'] ?> <?= htmlspecialchars($crumb['text']) ?>
                    </a>
                <?php else: ?>
                    <span class="breadcrumb-item active">
                        <?= $crumb['icon'] ?> <?= htmlspecialchars($crumb['text']) ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Contenu principal -->
    <main class="portal-main">
    
    <!-- JavaScript Header modulaire -->
    <script src="/assets/js/header.js?v=<?= $build_number ?>"></script>
    <!-- JavaScript spécifique au module (nouveau + fallback ancien) -->
    <?php
    $module_js_loaded = false;
    if ($module_js && $current_module !== 'home') {
        // Nouveau chemin public/nommodule/assets/js/nommodule.js
        $module_js_path = "/public/{$current_module}/assets/js/{$current_module}.js";
        if (file_exists(ROOT_PATH . $module_js_path)) {
            echo '<script src="' . $module_js_path . '?v=' . $build_number . '"></script>';
            $module_js_loaded = true;
        }
        // Compatibilité ancienne : /{$current_module}/assets/js/{$current_module}.js ou /assets/js/modules/{$current_module}.js
        if (!$module_js_loaded) {
            $legacy_paths = [
                "/{$current_module}/assets/js/{$current_module}.js",
                "/assets/js/modules/{$current_module}.js"
            ];
            foreach ($legacy_paths as $js_path) {
                if (file_exists(ROOT_PATH . $js_path)) {
                    echo '<script src="' . $js_path . '?v=' . $build_number . '"></script>';
                    break;
                }
            }
        }
    }
    ?>
    <!-- Config JS globale pour module courant -->
    <script>
        window.PortalConfig = {
            currentModule: '<?= $current_module ?>',
            moduleColor: '<?= $module_color ?>',
            moduleStatus: '<?= $module_status ?>',
            buildNumber: '<?= $build_number ?>',
            userAuthenticated: <?= $user_authenticated ? 'true' : 'false' ?>,
            userRole: '<?= $current_user['role'] ?? 'guest' ?>'
        };
    </script>

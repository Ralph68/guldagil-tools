<?php
/**
 * Titre: Portail Guldagil - Header principal avec navigation modules et menu utilisateur
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 * Description: Header complet avec logo sans nom portail (sauf index), navigation modules sticky, menu utilisateur fonctionnel
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Chargement système de rôles centralisé
require_once ROOT_PATH . '/config/roles.php';

// Chargement config debug si disponible
if (file_exists(ROOT_PATH . '/config/debug.php')) {
    require_once ROOT_PATH . '/config/debug.php';
}

// Initialisation des variables par défaut
$user_authenticated = false;
$current_user = null;

// === AUTHENTIFICATION OBLIGATOIRE ===

// Démarrer session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Pages qui n'ont PAS besoin d'authentification
$public_pages = [
    '/auth/login.php',
    '/auth/logout.php', 
    '/error.php',
    '/maintenance.php'
];

// Vérifier si on est sur une page publique
$current_script = $_SERVER['SCRIPT_NAME'] ?? '';
$is_public_page = false;
foreach ($public_pages as $page) {
    if (strpos($current_script, $page) !== false) {
        $is_public_page = true;
        break;
    }
}

// Initialisation des variables
$user_authenticated = false;
$current_user = null;

// === AUTHENTIFICATION OBLIGATOIRE ===
if (!$is_public_page) {
    require_once ROOT_PATH . '/core/auth/AuthManager.php';
    $auth = AuthManager::getInstance();
    
    // Vérification session + remember me
    if (!$auth->isAuthenticated()) {
        // Redirection avec nettoyage session
        session_destroy();
        header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $current_user = $auth->getCurrentUser();
    $user_authenticated = true;
    
    // Variables compatibilité
    $_SESSION['user'] = $current_user;
    $_SESSION['authenticated'] = true;
    $_SESSION['user_role'] = $current_user['role'];
}

// Variables avec fallbacks sécurisés
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? 'Plateforme collaborative multi-métiers');
$page_description = htmlspecialchars($page_description ?? 'Portail interne Guldagil pour tous les collaborateurs');
$current_module = htmlspecialchars($current_module ?? 'home');

// Utilisation des nouvelles variables de config
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '001';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// Configuration modules avec routes et permissions
$all_modules = [
    'home' => ['icon' => '🏠', 'color' => '#3182ce', 'status' => 'active', 'name' => 'Accueil', 'routes' => ['', 'home']],
    'port' => ['icon' => '📦', 'color' => '#059669', 'status' => 'active', 'name' => 'Frais de port', 'routes' => ['port', 'calculateur']],
    'adr' => ['icon' => '⚠️', 'color' => '#dc2626', 'status' => 'active', 'name' => 'ADR', 'routes' => ['adr']],
    'epi' => ['icon' => '🦺', 'color' => '#7c3aed', 'status' => 'active', 'name' => 'EPI', 'routes' => ['epi']],
    'qualite' => ['icon' => '✅', 'color' => '#059669', 'status' => 'active', 'name' => 'Qualité', 'routes' => ['qualite']],
    'materiel' => ['icon' => '🔧', 'color' => '#ea580c', 'status' => 'active', 'name' => 'Matériels', 'routes' => ['materiel']],
    'user' => ['icon' => '👤', 'color' => '#7c2d12', 'status' => 'active', 'name' => 'Mon compte', 'routes' => ['user', 'profile']],
    'admin' => ['icon' => '⚙️', 'color' => '#1f2937', 'status' => 'active', 'name' => 'Administration', 'routes' => ['admin']],
    'dev' => ['icon' => '💻', 'color' => '#dc2626', 'status' => 'development', 'name' => 'Développement', 'routes' => ['dev', 'debug']]
];

// Détection automatique du module actuel depuis l'URL
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

// Détecter si on est sur la page d'index générale
$is_portal_index = ($current_module === 'home' && ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php'));

// Fil d'Ariane par défaut
$breadcrumbs = $breadcrumbs ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// Configuration CSS et JS modulaire
$module_css = $module_css ?? true;
$module_js = $module_js ?? true;

// Titre complet de la page
$full_title = $page_title . ' - ' . $app_name . ' v' . $app_version;

// Icône, couleur et statut du module actuel
$module_icon = $all_modules[$current_module]['icon'] ?? '🏠';
$module_color = $all_modules[$current_module]['color'] ?? '#3182ce';
$module_status = $all_modules[$current_module]['status'] ?? 'active';

// Navigation modules avec système de rôles centralisé
$navigation_modules = [];
if ($user_authenticated) {
    $user_role = $current_user['role'] ?? 'user';
    $navigation_modules = getNavigationModules($user_role, $all_modules);
}
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
    <link rel="icon" type="image/png" href="/assets/img/favicon_32x32.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon_180x180.png">

    <!-- CSS principal OBLIGATOIRE - chemins critiques à préserver -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    <!-- CSS corrections header navigation et menu utilisateur -->
    <link rel="stylesheet" href="/assets/css/header_fixes.css?v=<?= $build_number ?>">
    <!-- CSS bannière cookie RGPD -->
    <link rel="stylesheet" href="/assets/css/cookie_banner.css?v=<?= $build_number ?>">

    <!-- CSS modulaire avec fallback intelligent -->
    <?php if ($module_css && $current_module !== 'home'): ?>
        <?php 
        // 1. Priorité : nouveau système dans module/assets/
        $new_css_path = "{$current_module}/assets/css/{$current_module}.css";
        $module_css_loaded = false;
        
        if (file_exists(ROOT_PATH . $new_css_path)): ?>
            <link rel="stylesheet" href="<?= $new_css_path ?>?v=<?= $build_number ?>">
            <?php $module_css_loaded = true; ?>
        <?php endif; ?>
        
        <?php if (!$module_css_loaded): ?>
            <?php 
            // 2. Fallback : ancien système
            $legacy_paths = [
                "/{$current_module}/assets/css/{$current_module}.css",
                "/assets/css/modules/{$current_module}.css"
            ];
            
            foreach ($legacy_paths as $css_path):
                if (file_exists(ROOT_PATH . "/public" . $css_path)): ?>
                    <link rel="stylesheet" href="<?= $css_path ?>?v=<?= $build_number ?>">
                    <?php break; ?>
                <?php endif;
            endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Variable CSS pour la couleur du module -->
    <style>
        :root {
            --current-module-color: <?= $module_color ?>;
            --current-module-color-light: <?= $module_color ?>20;
            --current-module-color-dark: <?= $module_color ?>dd;
        }
    </style>
    
    <!-- JavaScript bannière cookie RGPD -->
    <script src="/assets/js/cookie_banner.js?v=<?= $build_number ?>"></script>
    <script src="/assets/js/cookie_config.js?v=<?= $build_number ?>"></script>
    <!-- Analytics -->
    <script src="/assets/js/analytics.js?v=<?= $build_number ?>"></script>
</head>
<body data-module="<?= $current_module ?>" data-module-status="<?= $module_status ?>" 
      class="<?= $user_authenticated ? 'authenticated' : 'auth-page' ?>">

    <!-- Bannière de debug (masquée en production) -->
    <?php if (defined('DEBUG') && DEBUG === true): ?>
    <div class="debug-banner" style="background: #dc2626; color: white; padding: 0.5rem; text-align: center; font-size: 0.875rem;">
        🔒 MODE DEBUG - <?= htmlspecialchars($current_user['username'] ?? 'non connecté') ?> 
        <?php if ($current_user): ?>(<?= htmlspecialchars($current_user['role'] ?? 'User') ?>)<?php endif; ?> | 
        <?= date('H:i:s') ?> | 
        IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') ?> |
        Module: <?= $current_module ?> |
        Build: <?= $build_number ?>
    </div>
    <?php endif; ?>

    <!-- Header principal -->
    <header class="portal-header">
        <div class="header-container">
            <!-- Logo SANS nom du portail (sauf page index) -->
            <a href="/" class="header-brand">
                <div class="header-logo">
                    <?php if (file_exists(ROOT_PATH . '/assets/img/logo.png')): ?>
                        <img src="/assets/img/logo.png" alt="Logo Guldagil" width="32" height="32" style="object-fit: contain;">
                    <?php else: ?>
                        💧
                    <?php endif; ?>
                </div>
                <?php if ($is_portal_index): ?>
                    <!-- Afficher le nom du portail SEULEMENT sur l'index général -->
                    <div class="header-brand-text"><?= $app_name ?></div>
                <?php endif; ?>
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
                <div class="user-menu-trigger" id="userMenuTrigger" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar">
                        <?php if (!empty($current_user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($current_user['avatar']) ?>" alt="Avatar" width="36" height="36" style="border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 2)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="user-role">
                            <span class="role-badge role-<?= htmlspecialchars($current_user['role'] ?? 'user') ?>">
                                <?= htmlspecialchars(ucfirst($current_user['role'] ?? 'user')) ?>
                            </span>
                        </div>
                    </div>
                    <div class="dropdown-icon">▼</div>
                </div>

                <!-- Menu dropdown utilisateur -->
                <div class="user-dropdown-menu" id="userDropdownMenu" role="menu" aria-hidden="true">
                    <div class="dropdown-header">
                        <div class="dropdown-user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="dropdown-user-email"><?= htmlspecialchars($current_user['email'] ?? '') ?></div>
                    </div>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="/user/profile.php" class="dropdown-item">
                        <span class="dropdown-icon">👤</span>
                        <span class="dropdown-text">Mon profil</span>
                    </a>
                    <a href="/user/" class="dropdown-item">
                        <span class="dropdown-icon">⚙️</span>
                        <span class="dropdown-text">Paramètres</span>
                    </a>
                    
                    <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'view_admin')): ?>
                    <div class="dropdown-divider"></div>
                    <a href="/admin/" class="dropdown-item">
                        <span class="dropdown-icon">🔧</span>
                        <span class="dropdown-text">Administration</span>
                    </a>
                    
                    <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'manage_users')): ?>
                    <a href="/admin/users.php" class="dropdown-item">
                        <span class="dropdown-icon">👥</span>
                        <span class="dropdown-text">Utilisateurs</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'manage_system')): ?>
                    <a href="/admin/system.php" class="dropdown-item">
                        <span class="dropdown-icon">🛠️</span>
                        <span class="dropdown-text">Système</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'access_dev')): ?>
                    <a href="/dev/" class="dropdown-item">
                        <span class="dropdown-icon">💻</span>
                        <span class="dropdown-text">Développement</span>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item" onclick="showHelp()">
                        <span class="dropdown-icon">❓</span>
                        <span class="dropdown-text">Aide</span>
                    </a>
                    <a href="/auth/logout.php" class="dropdown-item logout">
                        <span class="dropdown-icon">🚪</span>
                        <span class="dropdown-text">Déconnexion</span>
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Navigation pour utilisateur non connecté -->
            <div class="header-auth-nav">
                <a href="/auth/login.php" class="btn btn-primary">
                    <span class="btn-icon">🔑</span>
                    Connexion
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Navigation modules (si utilisateur connecté) -->
    <?php if ($user_authenticated && !empty($navigation_modules)): ?>
    <nav class="modules-nav">
        <div class="modules-nav-container">
            <div class="modules-nav-items">
                <?php foreach ($navigation_modules as $module_key => $module_data): 
                    $is_active = $current_module === $module_key;
                    $css_classes = ['module-nav-item'];
                    if ($is_active) $css_classes[] = 'active';
                    
                    $href = "/{$module_key}/";
                ?>
                    <a href="<?= $href ?>" 
                       class="<?= implode(' ', $css_classes) ?>"
                       style="--module-color: <?= $module_data['color'] ?? '#3182ce' ?>">
                        <span class="module-nav-icon"><?= $module_data['icon'] ?? '📁' ?></span>
                        <span class="module-nav-name"><?= htmlspecialchars($module_data['name']) ?></span>
                        <?php if ($module_data['status'] === 'beta'): ?>
                            <span class="status-badge beta">BETA</span>
                        <?php elseif ($module_data['status'] === 'development'): ?>
                            <span class="status-badge dev">DEV</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Menu mobile toggle -->
            <button class="mobile-menu-toggle" aria-label="Menu modules" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Fil d'Ariane -->
    <nav class="breadcrumb-nav">
        <div class="breadcrumb-container">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                <?php if (!empty($crumb['url']) && !($crumb['active'] ?? false)): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item">
                        <span><?= $crumb['icon'] ?? '' ?></span>
                        <span><?= htmlspecialchars($crumb['text']) ?></span>
                    </a>
                <?php else: ?>
                    <span class="breadcrumb-item active">
                        <span><?= $crumb['icon'] ?? '' ?></span>
                        <span><?= htmlspecialchars($crumb['text']) ?></span>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="portal-main">

    <!-- JavaScript pour interactions header et menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === GESTION MENU UTILISATEUR ===
            const userMenuTrigger = document.getElementById('userMenuTrigger');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            if (userMenuTrigger && userDropdownMenu) {
                // Toggle menu au clic
                userMenuTrigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isExpanded = userMenuTrigger.getAttribute('aria-expanded') === 'true';
                    toggleUserMenu(!isExpanded);
                });
                
                // Fermer menu si clic ailleurs
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.header-user-nav')) {
                        toggleUserMenu(false);
                    }
                });
                
                // Gestion clavier (ESC)
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        toggleUserMenu(false);
                        userMenuTrigger.focus();
                    }
                });
                
                // Fonction toggle menu
                function toggleUserMenu(show) {
                    userMenuTrigger.setAttribute('aria-expanded', show);
                    userDropdownMenu.setAttribute('aria-hidden', !show);
                    userDropdownMenu.style.display = show ? 'block' : 'none';
                }
            }
            
            // === GESTION MENU MOBILE ===
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const modulesNav = document.querySelector('.modules-nav');
            
            if (mobileMenuToggle && modulesNav) {
                mobileMenuToggle.addEventListener('click', function() {
                    modulesNav.classList.toggle('mobile-open');
                    mobileMenuToggle.classList.toggle('open');
                });
            }
        });
        
        // Fonction helper pour aide
        function showHelp() {
            alert('Aide contextuelle - Module: <?= $current_module ?>\nVersion: <?= $app_version ?>\nBuild: <?= $build_number ?>');
        }
    </script>

    <!-- Scripts nécessaires -->
    <script src="/assets/js/header.js?v=<?= $build_number ?>"></script>
    
    <!-- JavaScript spécifique au module -->
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        // Système de détection JS modulaire avec fallback
        $module_js_paths = [
            "{$current_module}/assets/js/{$current_module}.js",
            "/{$current_module}/assets/js/{$current_module}.js",
            "/assets/js/modules/{$current_module}.js"
        ];
        
        foreach ($module_js_paths as $js_path):
            if (file_exists(ROOT_PATH . "/public" . $js_path)): ?>
                <script src="<?= $js_path ?>?v=<?= $build_number ?>"></script>
                <?php break; ?>
            <?php endif;
        endforeach; ?>
    <?php endif; ?>

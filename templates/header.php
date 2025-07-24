<?php
/**
 * Titre: Header adaptatif avec navigation sticky et réduction au scroll
 * Chemin: /templates/header.php  
 * Version: 0.5 beta + build auto
 */

// Chargement des configurations
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Chargement des fichiers config additionnels si disponibles
$additional_configs = [
    ROOT_PATH . '/config/roles.php',
    ROOT_PATH . '/config/functions.php',
    ROOT_PATH . '/config/modules.php'
];

foreach ($additional_configs as $config_file) {
    if (file_exists($config_file)) {
        require_once $config_file;
    }
}

// Variables par défaut si pas définies
$app_name = $app_name ?? 'Portail Guldagil';
$page_title = $page_title ?? 'Accueil';
$page_subtitle = $page_subtitle ?? '';
$current_module = $current_module ?? 'home';
$module_icon = $module_icon ?? '🏠';
$module_color = $module_color ?? '#3182ce';
$module_status = $module_status ?? 'active';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$breadcrumbs = $breadcrumbs ?? [];
$module_css = $module_css ?? false;
$module_js = $module_js ?? false;

// Authentification - variables par défaut
$user_authenticated = false;
$current_user = null;

// Vérification AuthManager en priorité
if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
    try {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = new AuthManager();
        if ($auth->isAuthenticated()) {
            $user_authenticated = true;
            $current_user = $auth->getCurrentUser();
        }
    } catch (Exception $e) {
        error_log("Erreur AuthManager: " . $e->getMessage());
    }
}

// Fallback session classique
if (!$user_authenticated && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $user_authenticated = true;
    $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
}

// Fonction helper pour classes rôle si pas définie
if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass($role) {
        return 'role-' . strtolower($role);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description ?? $page_title . ' - ' . $app_name) ?>">
    <title><?= htmlspecialchars($page_title . ' - ' . $app_name) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon_32x32.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon_180x180.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/assets/img/icon_512x512.png">
    
    <!-- CSS principal OBLIGATOIRE -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">

    <!-- CSS modulaire avec fallback intelligent -->
    <?php if ($module_css && $current_module !== 'home'): ?>
        <?php 
        // 1. Priorité : nouveau système dans /public/module/assets/
        $new_css_path = "/public/{$current_module}/assets/css/{$current_module}.css";
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

    <!-- Header principal adaptatif -->
    <header class="portal-header" id="mainHeader">
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
                    <span class="page-title-text"><?= $page_title ?></span>
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
                <div class="user-menu-trigger" id="userMenuTrigger" aria-haspopup="true" aria-expanded="false" tabindex="0">
                    <div class="user-avatar">
                        <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="user-role">
                            <span class="role-badge <?= getRoleBadgeClass($current_user['role'] ?? 'user') ?>">
                                <?= ucfirst($current_user['role'] ?? 'user') ?>
                            </span>
                        </div>
                    </div>
                    <div class="dropdown-icon">▼</div>
                </div>

                <div class="user-dropdown" id="userDropdown" role="menu" aria-hidden="true">
                    <div class="dropdown-header">
                        <div class="dropdown-user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="dropdown-user-email"><?= htmlspecialchars($current_user['email'] ?? '') ?></div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="/user/" class="dropdown-item">
                        👤 Mon profil
                    </a>
                    <a href="/user/settings" class="dropdown-item">
                        ⚙️ Paramètres
                    </a>
                    <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                    <div class="dropdown-divider"></div>
                    <a href="/admin/" class="dropdown-item">
                        🔧 Administration
                    </a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="/auth/logout.php" class="dropdown-item dropdown-item-danger">
                        🚪 Déconnexion
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Boutons d'authentification pour utilisateurs non connectés -->
            <div class="header-auth-actions">
                <a href="/auth/login.php" class="login-btn">
                    🔐 Connexion
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Navigation sticky avec menu modules + fil d'Ariane -->
    <nav class="sticky-navigation" id="stickyNav">
        <div class="nav-container">
            <!-- Menu modules horizontal -->
            <?php if ($user_authenticated): ?>
            <div class="module-navigation">
                <?php 
                // Configuration des modules disponibles
                $all_modules = $all_modules ?? [
                    'home' => ['icon' => '🏠', 'color' => '#059669', 'name' => 'Accueil', 'url' => '/', 'status' => 'active'],
                    'port' => ['icon' => '📦', 'color' => '#3182ce', 'name' => 'Frais de port', 'url' => '/port/', 'status' => 'active'],
                    'adr' => ['icon' => '⚠️', 'color' => '#dc2626', 'name' => 'ADR', 'url' => '/adr/', 'status' => 'active'],
                    'user' => ['icon' => '👤', 'color' => '#7c2d12', 'name' => 'Mon compte', 'url' => '/user/', 'status' => 'active'],
                    'admin' => ['icon' => '⚙️', 'color' => '#1f2937', 'name' => 'Administration', 'url' => '/admin/', 'status' => 'active']
                ];

                // Fonction simple de navigation si pas définie
                if (!function_exists('getNavigationModules')) {
                    function getNavigationModules($user_role, $all_modules) {
                        $accessible = [];
                        foreach ($all_modules as $key => $module) {
                            if ($key === 'home') continue; // Exclure home de la nav
                            
                            // Logique d'accès simple
                            switch ($user_role) {
                                case 'admin':
                                case 'dev':
                                    $accessible[$key] = $module;
                                    break;
                                case 'logistique':
                                    if (in_array($key, ['port', 'adr', 'user'])) {
                                        $accessible[$key] = $module;
                                    }
                                    break;
                                case 'user':
                                    if (in_array($key, ['port', 'user'])) {
                                        $accessible[$key] = $module;
                                    }
                                    break;
                            }
                        }
                        return $accessible;
                    }
                }

                $navigation_modules = getNavigationModules($current_user['role'] ?? 'user', $all_modules);
                foreach ($navigation_modules as $nav_key => $nav_module): 
                    $is_active = $nav_key === $current_module;
                    $nav_class = 'module-nav-item' . ($is_active ? ' active' : '');
                ?>
                <a href="<?= $nav_module['url'] ?>" class="<?= $nav_class ?>" 
                   style="--module-color: <?= $nav_module['color'] ?>">
                    <span class="module-nav-icon"><?= $nav_module['icon'] ?></span>
                    <span class="module-nav-name"><?= htmlspecialchars($nav_module['name']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Fil d'Ariane -->
            <?php if (!empty($breadcrumbs)): ?>
            <div class="breadcrumb-navigation">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($index > 0): ?>
                        <span class="breadcrumb-separator">›</span>
                    <?php endif; ?>
                    <?php if (!($crumb['active'] ?? false)): ?>
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
            <?php endif; ?>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="portal-main">

    <!-- JavaScript Header adaptatif -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.getElementById('mainHeader');
        const stickyNav = document.getElementById('stickyNav');
        const userMenuTrigger = document.getElementById('userMenuTrigger');
        const userDropdown = document.getElementById('userDropdown');
        const scrollProgress = document.getElementById('scrollProgress');
        
        let isHeaderCompact = false;
        let scrollTimeout;

        // Gestion du scroll pour header adaptatif
        function handleScroll() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const shouldBeCompact = scrollTop > 100;

            if (shouldBeCompact !== isHeaderCompact) {
                isHeaderCompact = shouldBeCompact;
                
                if (isHeaderCompact) {
                    header.classList.add('header-compact');
                    stickyNav.classList.add('nav-sticky');
                } else {
                    header.classList.remove('header-compact');
                    stickyNav.classList.remove('nav-sticky');
                }
            }

            // Debounce pour performance
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // Actions supplémentaires si nécessaire
            }, 10);
        }

        // Gestion du menu utilisateur
        function toggleUserMenu(show) {
            if (!userMenuTrigger || !userDropdown) return;
            
            const isShown = show !== undefined ? show : userDropdown.getAttribute('aria-hidden') === 'true';
            
            userDropdown.setAttribute('aria-hidden', !isShown);
            userMenuTrigger.setAttribute('aria-expanded', isShown);
            
            if (isShown) {
                userDropdown.style.display = 'block';
                // Focus sur premier élément
                const firstItem = userDropdown.querySelector('.dropdown-item');
                if (firstItem) firstItem.focus();
            } else {
                userDropdown.style.display = 'none';
            }
        }

        // Event listeners
        window.addEventListener('scroll', handleScroll, { passive: true });
        
        if (userMenuTrigger) {
            userMenuTrigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleUserMenu();
            });

            userMenuTrigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleUserMenu();
                }
            });
        }

        // Fermer menu au clic extérieur
        document.addEventListener('click', (e) => {
            if (userDropdown && userMenuTrigger) {
                if (!userMenuTrigger.contains(e.target) && !userDropdown.contains(e.target)) {
                    toggleUserMenu(false);
                }
            }
        });

        // Gestion clavier pour menu
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && userDropdown && userDropdown.getAttribute('aria-hidden') === 'false') {
                toggleUserMenu(false);
                userMenuTrigger.focus();
            }
        });

        // Initialisation
        handleScroll();
    });
    </script>

    <!-- CSS pour le comportement adaptatif -->
    <style>
        /* Styles pour header adaptatif */
        .portal-header {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: var(--spacing-lg, 1.5rem) 0;
            background: linear-gradient(135deg, #2563eb, #1d4ed8); /* Bleu eau cohérent */
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg, 1.5rem);
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: var(--spacing-lg, 1.5rem);
            min-height: 64px;
        }

        /* Page info centrée */
        .header-page-info {
            text-align: center;
            justify-self: center;
        }

        .page-main-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm, 0.5rem);
        }

        .page-subtitle {
            margin-top: var(--spacing-xs, 0.25rem);
            opacity: 0.9;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .portal-header.header-compact {
            padding: var(--spacing-sm, 0.5rem) 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.15);
        }

        .portal-header.header-compact .header-brand-text {
            font-size: 0.9rem;
        }

        .portal-header.header-compact .page-main-title {
            font-size: 1.1rem;
        }

        .portal-header.header-compact .page-subtitle {
            opacity: 0;
            height: 0;
            overflow: hidden;
        }

        .portal-header.header-compact .user-details {
            display: none;
        }

        .portal-header.header-compact .user-avatar {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
        }

        /* Navigation sticky - Fil d'Ariane uniquement */
        .sticky-navigation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e5e7eb;
            transform: translateY(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .sticky-navigation.nav-sticky {
            transform: translateY(0);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-sm, 0.5rem) var(--spacing-lg, 1.5rem);
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--spacing-md, 1rem);
        }

        /* Menu modules horizontal */
        .module-navigation {
            display: flex;
            gap: var(--spacing-xs, 0.25rem);
            align-items: center;
        }

        .module-nav-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs, 0.25rem);
            padding: var(--spacing-sm, 0.5rem) var(--spacing-md, 1rem);
            border-radius: 0.375rem;
            text-decoration: none;
            color: #4b5563;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .module-nav-item:hover {
            background: var(--module-color, #2563eb)20;
            color: var(--module-color, #2563eb);
            transform: translateY(-1px);
        }

        .module-nav-item.active {
            background: var(--module-color, #2563eb);
            color: white;
            font-weight: 600;
        }

        .module-nav-icon {
            font-size: 1rem;
        }

        /* Fil d'Ariane centré */
        .breadcrumb-navigation {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs, 0.25rem);
            font-size: var(--font-size-sm, 0.875rem);
        }

        .breadcrumb-item {
            color: #4b5563;
            text-decoration: none;
            padding: var(--spacing-xs, 0.25rem) var(--spacing-sm, 0.5rem);
            border-radius: 0.25rem;
            transition: all 0.2s ease;
        }

        .breadcrumb-item:hover:not(.active) {
            background: #f3f4f6;
            color: #111827;
        }

        .breadcrumb-item.active {
            color: #2563eb; /* Bleu eau cohérent */
            font-weight: 600;
        }

        .breadcrumb-separator {
            color: #9ca3af;
            margin: 0 var(--spacing-xs, 0.25rem);
        }

        /* Ajustement du contenu principal pour header fixe */
        .portal-main {
            margin-top: 120px; /* Espace pour header + nav */
            transition: margin-top 0.3s ease;
        }

        body.header-compact .portal-main {
            margin-top: 100px; /* Moins d'espace en mode compact */
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                grid-template-columns: auto 1fr auto;
                gap: var(--spacing-sm, 0.5rem);
            }

            .header-page-info {
                text-align: center;
            }

            .page-main-title {
                font-size: 1.1rem;
            }

            .module-nav-name {
                display: none;
            }

            .module-nav-item {
                padding: var(--spacing-sm, 0.5rem);
                min-width: 40px;
                justify-content: center;
            }

            .breadcrumb-navigation {
                display: none;
            }

            .scroll-indicator {
                width: 40px;
            }

            .portal-main {
                margin-top: 100px;
            }

            body.header-compact .portal-main {
                margin-top: 80px;
            }
        }

        @media (max-width: 480px) {
            .header-brand-text {
                display: none;
            }

            .user-details {
                display: none;
            }

            .module-navigation {
                gap: 0;
            }

            .nav-container {
                padding: var(--spacing-sm, 0.5rem);
            }

            .portal-main {
                margin-top: 80px;
            }

            body.header-compact .portal-main {
                margin-top: 60px;
            }
        }

        /* Améliorations accessibilité */
        @media (prefers-reduced-motion: reduce) {
            .portal-header, .sticky-navigation, .module-nav-item, .scroll-progress {
                transition: none !important;
            }
        }

        @media (prefers-contrast: high) {
            .sticky-navigation {
                border-bottom: 2px solid var(--gray-800);
            }
            
            .module-nav-item.active {
                border: 2px solid white;
            }
        }

        /* Mode sombre */
        @media (prefers-color-scheme: dark) {
            .sticky-navigation {
                background: rgba(31, 41, 55, 0.95);
                border-bottom-color: var(--gray-700);
            }

            .module-nav-item {
                color: var(--gray-300);
            }

            .module-nav-item:hover {
                background: var(--gray-700);
                color: white;
            }

            .breadcrumb-item {
                color: var(--gray-400);
            }

            .breadcrumb-item:hover:not(.active) {
                background: var(--gray-700);
                color: var(--gray-200);
            }

            .scroll-indicator {
                background: var(--gray-700);
            }
        }
    </style>

    <!-- JavaScript Header modulaire (chargé en fin de header) -->
    <script src="/assets/js/header.js?v=<?= $build_number ?>"></script>
    
    <!-- JavaScript spécifique au module -->
    <?php if ($module_js): ?>
        <?php 
        // Ordre de priorité pour trouver le JS du module
        $module_js_paths = [
            "/public/{$current_module}/assets/js/{$current_module}.js",
            "/{$current_module}/assets/js/{$current_module}.js",
            "/assets/js/modules/{$current_module}.js"
        ];
        
        foreach ($module_js_paths as $js_path) {
            if (file_exists(ROOT_PATH . $js_path)): ?>
                <script src="<?= $js_path ?>?v=<?= $build_number ?>"></script>
                <?php break; ?>
            <?php endif;
        } ?>
    <?php endif; ?>

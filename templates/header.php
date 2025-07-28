<?php
/**
 * Titre: Header principal du portail - Version complète avec MenuManager et compatibilité totale
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Variables par défaut si non définies
$current_module = $current_module ?? 'home';
$app_name = $app_name ?? 'Guldagil';
$app_version = $app_version ?? '0.5 beta';
$build_number = $build_number ?? date('Ymd') . '001';
$page_title = $page_title ?? 'Accueil';
$page_description = $page_description ?? 'Portail de gestion';
$app_author = $app_author ?? 'Guldagil';
$user_authenticated = $user_authenticated ?? false;
$current_user = $current_user ?? null;
$module_css = $module_css ?? true;
$module_js = $module_js ?? true;

// Titre complet de la page
$full_title = $page_title . ' - ' . $app_name . ' v' . $app_version;

// === GESTION MODULES VIA MENUMANAGER ===
$all_modules = [];
$navigation_modules = [];
$current_module_config = null;

if (class_exists('MenuManager')) {
    $menuManager = MenuManager::getInstance();
    if ($user_authenticated) {
        $user_role = $current_user['role'] ?? 'user';
        $navigation_modules = $menuManager->getModulesForRole($user_role);
        $all_modules = $navigation_modules;
    }
    $current_module_config = $menuManager->getModule($current_module);
} else {
    // Fallback : chargement direct configuration modules
    $modulesFile = ROOT_PATH . '/config/modules.php';
    if (file_exists($modulesFile)) {
        $all_modules = require $modulesFile;
        if ($user_authenticated) {
            $user_role = $current_user['role'] ?? 'user';
            if (function_exists('getNavigationModules')) {
                $navigation_modules = getNavigationModules($user_role, $all_modules);
            } else {
                // Fallback basique
                $navigation_modules = array_filter($all_modules, function($module) use ($user_role) {
                    $allowedRoles = $module['roles'] ?? ['user', 'admin', 'dev'];
                    return in_array($user_role, $allowedRoles);
                });
            }
        }
        $current_module_config = $all_modules[$current_module] ?? null;
    }
}

// Propriétés du module actuel
$module_icon = $current_module_config['icon'] ?? '🏠';
$module_color = $current_module_config['color'] ?? '#3182ce';
$module_status = $current_module_config['status'] ?? 'active';

// === BREADCRUMBS ===
$breadcrumbs = [];
if (class_exists('RouteManager')) {
    $routeManager = RouteManager::getInstance();
    $breadcrumbs = $routeManager->getBreadcrumbs();
} else {
    // Fallback manuel pour breadcrumb
    $breadcrumbs = [
        ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => $current_module === 'home']
    ];
    
    if ($current_module !== 'home') {
        $module_names = [
            'admin' => ['icon' => '⚙️', 'text' => 'Administration'],
            'user' => ['icon' => '👤', 'text' => 'Utilisateur'],
            'port' => ['icon' => '🚚', 'text' => 'Calculateur Port'],
            'materiel' => ['icon' => '🔧', 'text' => 'Matériel'],
            'qualite' => ['icon' => '🔬', 'text' => 'Qualité'],
            'epi' => ['icon' => '🦺', 'text' => 'EPI'],
            'adr' => ['icon' => '⚠️', 'text' => 'ADR'],
            'auth' => ['icon' => '🔐', 'text' => 'Authentification']
        ];
        
        if (isset($module_names[$current_module])) {
            $breadcrumbs[0]['active'] = false;
            $breadcrumbs[] = [
                'icon' => $module_names[$current_module]['icon'],
                'text' => $module_names[$current_module]['text'],
                'url' => "/{$current_module}/",
                'active' => true
            ];
        } elseif ($current_module_config) {
            $breadcrumbs[0]['active'] = false;
            $breadcrumbs[] = [
                'icon' => $module_icon,
                'text' => $current_module_config['name'],
                'url' => "/{$current_module}/",
                'active' => true
            ];
        }
    }
}

// Classe body pour page d'accueil
$is_home_page = ($current_module === 'home');

// Fonction helper pour badges de rôle
if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass($role) {
        $classes = [
            'admin' => 'admin',
            'dev' => 'dev', 
            'manager' => 'manager',
            'user' => 'user'
        ];
        return $classes[$role] ?? 'user';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="<?= htmlspecialchars($app_author) ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="<?= htmlspecialchars($module_color) ?>">
    
    <title><?= htmlspecialchars($full_title) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

    <!-- CSS principal OBLIGATOIRE -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/cookie_banner.css?v=<?= $build_number ?>">

    <!-- CSS modulaire - AssetManager UNIQUEMENT -->
    <?php if ($module_css && $current_module !== 'home'): ?>
        <?php 
        if (class_exists('AssetManager')) {
            $assetManager = AssetManager::getInstance();
            $assetManager->loadModuleAssets($current_module);
            echo $assetManager->renderCss();
        }
        ?>
    <?php endif; ?>

    <!-- Variable CSS pour couleur module -->
    <style>
        :root {
            --current-module-color: <?= htmlspecialchars($module_color) ?>;
            --current-module-color-light: <?= htmlspecialchars($module_color) ?>20;
            --current-module-color-dark: <?= htmlspecialchars($module_color) ?>dd;
            --module-color: <?= htmlspecialchars($module_color) ?>;
        }
    </style>
</head>
<body data-module="<?= htmlspecialchars($current_module) ?>" 
      data-module-status="<?= htmlspecialchars($module_status) ?>" 
      class="<?= $user_authenticated ? 'authenticated' : 'auth-page' ?><?= $is_home_page ? ' home-page' : '' ?><?= count($breadcrumbs) <= 1 ? ' no-breadcrumb' : '' ?>">

    <!-- Bannière de debug -->
    <?php if (defined('DEBUG') && DEBUG === true): ?>
    <div class="debug-banner" style="background: #dc2626; color: white; padding: 0.5rem; text-align: center; font-size: 0.875rem;">
        🔒 MODE DEBUG - <?= htmlspecialchars($current_user['username'] ?? 'non connecté') ?> 
        | Module: <?= htmlspecialchars($current_module) ?> | Build: <?= htmlspecialchars($build_number) ?>
        | Timestamp: <?= date('H:i:s') ?> | IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') ?>
    </div>
    <?php endif; ?>

    <!-- Header principal -->
    <header class="portal-header" id="mainHeader">
        <div class="header-container">
            <!-- Logo et branding -->
            <a href="/" class="header-brand">
                <div class="header-logo">
                    <?php if (file_exists(ROOT_PATH . '/public/assets/img/logo.png')): ?>
                        <img src="/assets/img/logo.png" alt="<?= htmlspecialchars($app_name) ?>" width="48" height="48">
                    <?php else: ?>
                        <div class="logo-placeholder">
                            <span class="logo-emoji">🏢</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="header-brand-text">
                    <span class="brand-name"><?= htmlspecialchars($app_name) ?></span>
                    <span class="brand-version">v<?= htmlspecialchars($app_version) ?></span>
                </div>
            </a>

            <!-- Titre dynamique (centre sur accueil, réduit au scroll) -->
            <div class="page-title-section">
                <?php if ($is_home_page): ?>
                    <h1 class="portal-title"><?= htmlspecialchars($app_name) ?></h1>
                    <p class="portal-subtitle">Portail de gestion unifié</p>
                <?php else: ?>
                    <div class="current-page-info">
                        <span class="module-icon"><?= $module_icon ?></span>
                        <span class="page-title"><?= htmlspecialchars($page_title) ?></span>
                        <?php if ($module_status === 'development'): ?>
                            <span class="status-badge development">DEV</span>
                        <?php elseif ($module_status === 'beta'): ?>
                            <span class="status-badge beta">BETA</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Zone utilisateur -->
            <div class="user-nav">
                <?php if ($user_authenticated): ?>
                    <div class="user-menu">
                        <button class="user-trigger" id="userTrigger" aria-expanded="false" aria-haspopup="true">
                            <div class="user-avatar">
                                <?php if (!empty($current_user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($current_user['avatar']) ?>" alt="Avatar">
                                <?php else: ?>
                                    <span class="avatar-initials"><?= strtoupper(substr($current_user['username'] ?? 'U', 0, 2)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></span>
                                <span class="user-role">
                                    <span class="role-badge <?= getRoleBadgeClass($current_user['role'] ?? 'user') ?>">
                                        <?= htmlspecialchars(ucfirst($current_user['role'] ?? 'user')) ?>
                                    </span>
                                </span>
                            </div>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        
                        <div class="user-dropdown" id="userDropdown" role="menu">
                            <div class="dropdown-header">
                                <div class="dropdown-user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                                <div class="dropdown-user-role"><?= htmlspecialchars(ucfirst($current_user['role'] ?? 'user')) ?></div>
                            </div>
                            <hr class="dropdown-divider">
                            <a href="/user/profile.php" class="dropdown-item" role="menuitem">
                                <span class="item-icon">👤</span>
                                Mon profil
                            </a>
                            <a href="/user/settings.php" class="dropdown-item" role="menuitem">
                                <span class="item-icon">⚙️</span>
                                Paramètres
                            </a>
                            <?php if (in_array($current_user['role'] ?? '', ['admin', 'dev'])): ?>
                                <hr class="dropdown-divider">
                                <a href="/admin/" class="dropdown-item" role="menuitem">
                                    <span class="item-icon">🔧</span>
                                    Administration
                                </a>
                            <?php endif; ?>
                            <hr class="dropdown-divider">
                            <a href="/auth/logout.php" class="dropdown-item logout" role="menuitem">
                                <span class="item-icon">🚪</span>
                                Se déconnecter
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-actions">
                        <a href="/auth/login.php" class="btn-login">Se connecter</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Header scroll overlay -->
        <div class="scroll-header">
            <div class="scroll-header-container">
                <div class="module-info">
                    <span class="module-icon"><?= $module_icon ?></span>
                    <span class="module-name"><?= htmlspecialchars($current_module_config['name'] ?? ucfirst($current_module)) ?></span>
                </div>
                <div class="current-page-title"><?= htmlspecialchars($page_title) ?></div>
                <div class="scroll-user-info">
                    <?= htmlspecialchars($current_user['username'] ?? '') ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation modules -->
    <?php if ($user_authenticated && !empty($navigation_modules)): ?>
    <nav class="modules-nav" id="mainNav">
        <div class="nav-container">
            <div class="nav-items">
                <?php foreach ($navigation_modules as $module_key => $module_data): ?>
                    <?php 
                    $module_status = $module_data['status'] ?? 'active';
                    $access_state = $module_data['access_state'] ?? $module_status;
                    $is_disabled = in_array($access_state, ['disabled', 'visible_locked']);
                    ?>
                    <?php if ($access_state !== 'hidden'): ?>
                        <a href="<?= htmlspecialchars($module_data['url'] ?? "/{$module_key}/") ?>" 
                           class="nav-item <?= $module_key === $current_module ? 'active' : '' ?><?= $is_disabled ? ' disabled' : '' ?>"
                           title="<?= htmlspecialchars($module_data['description'] ?? $module_data['name']) ?>"
                           style="--module-color: <?= htmlspecialchars($module_data['color'] ?? '#3182ce') ?>"
                           <?= $is_disabled ? 'onclick="return false;"' : '' ?>>
                            <span class="nav-icon"><?= $module_data['icon'] ?? '📁' ?></span>
                            <span class="nav-text"><?= htmlspecialchars($module_data['name']) ?></span>
                            <?php if ($access_state === 'beta'): ?>
                                <span class="status-badge beta">β</span>
                            <?php elseif ($access_state === 'dev_access'): ?>
                                <span class="status-badge dev">DEV</span>
                            <?php elseif ($access_state === 'visible_locked'): ?>
                                <span class="status-badge locked">🔒</span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Menu burger mobile -->
            <button class="mobile-nav-toggle" aria-label="Menu modules" id="mobileNavToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Fil d'Ariane -->
    <?php if (count($breadcrumbs) > 1): ?>
    <nav class="breadcrumb-nav" id="breadcrumbNav">
        <div class="breadcrumb-container">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                
                <?php if (!empty($crumb['url']) && !($crumb['active'] ?? false)): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item">
                        <span class="breadcrumb-icon"><?= $crumb['icon'] ?? '' ?></span>
                        <span class="breadcrumb-text"><?= htmlspecialchars($crumb['text']) ?></span>
                    </a>
                <?php else: ?>
                    <span class="breadcrumb-item active">
                        <span class="breadcrumb-icon"><?= $crumb['icon'] ?? '' ?></span>
                        <span class="breadcrumb-text"><?= htmlspecialchars($crumb['text']) ?></span>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Zone de contenu principal -->
    <div class="page-wrapper">
        <?php 
        // Messages flash
        if (isset($_SESSION['flash_messages'])): 
            foreach ($_SESSION['flash_messages'] as $type => $messages):
                foreach ($messages as $message): ?>
                    <div class="alert alert-<?= htmlspecialchars($type) ?>" role="alert">
                        <span class="alert-icon">
                            <?php 
                            $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
                            echo $icons[$type] ?? 'ℹ️';
                            ?>
                        </span>
                        <div class="alert-content">
                            <?= htmlspecialchars($message) ?>
                        </div>
                        <button type="button" class="alert-close" aria-label="Fermer">&times;</button>
                    </div>
                <?php endforeach;
            endforeach;
            unset($_SESSION['flash_messages']);
        endif; ?>

    <!-- JavaScript global -->
    <script src="/assets/js/cookie_banner.js?v=<?= $build_number ?>"></script>
    <script src="/assets/js/cookie_config.js?v=<?= $build_number ?>"></script>
    <script src="/assets/js/analytics.js?v=<?= $build_number ?>"></script>
    <script src="/assets/js/header.js?v=<?= $build_number ?>"></script>

    <!-- JavaScript modulaire - AssetManager UNIQUEMENT -->
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        if (class_exists('AssetManager')) {
            $assetManager = AssetManager::getInstance();
            echo $assetManager->renderJs();
        }
        ?>
    <?php endif; ?>
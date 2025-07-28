<?php
/**
 * Titre: Header principal du portail - Version corrigée
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
$all_modules = $all_modules ?? []; 
$module_css = $module_css ?? true;
$module_js = $module_js ?? true;

// Charger les modules depuis config si vide
if (empty($all_modules) && file_exists(ROOT_PATH . '/config/modules.php')) {
    $all_modules = require ROOT_PATH . '/config/modules.php';
}

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

// Breadcrumb par défaut
if (!isset($breadcrumbs)) {
    $breadcrumbs = [
        ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => ($current_module === 'home')]
    ];
    
    if ($current_module !== 'home' && isset($all_modules[$current_module])) {
        $breadcrumbs[] = [
            'icon' => $module_icon,
            'text' => $all_modules[$current_module]['name'] ?? ucfirst($current_module),
            'url' => "/{$current_module}/",
            'active' => true
        ];
        $breadcrumbs[0]['active'] = false;
    }
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
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

    <!-- CSS principal OBLIGATOIRE - chemins critiques à préserver -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/cookie_banner.css?v=<?= $build_number ?>">

    <!-- CSS modulaire - CORRECTION DU CHEMIN SIMPLE -->
    <?php if ($module_css && $current_module !== 'home'): ?>
        <?php 
        $module_css_path = "/{$current_module}/assets/css/{$current_module}.css";
        $module_css_file = ROOT_PATH . "/public{$module_css_path}";
        
        if (file_exists($module_css_file)): ?>
            <link rel="stylesheet" href="<?= $module_css_path ?>?v=<?= $build_number ?>">
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

    <!-- Header principal TOUJOURS visible -->
    <header class="portal-header">
        <div class="header-container">
            <!-- Logo et branding -->
            <a href="/" class="header-brand">
                <div class="header-logo">
                    <?php if (file_exists(ROOT_PATH . '/public/assets/img/logo.png')): ?>
                        <img src="/assets/img/logo.png" alt="<?= $app_name ?>" width="32" height="32">
                    <?php else: ?>
                        <span class="logo-emoji">🏢</span>
                    <?php endif; ?>
                </div>
                <div class="header-brand-text">
                    <span class="brand-name"><?= $app_name ?></span>
                    <span class="brand-version">v<?= $app_version ?></span>
                </div>
            </a>

            <!-- MENU NAVIGATION TOUJOURS VISIBLE (si connecté) -->
            <?php if ($user_authenticated && !empty($navigation_modules)): ?>
                <nav class="header-nav" role="navigation" aria-label="Navigation principale">
                    <div class="nav-items">
                        <?php foreach ($navigation_modules as $mod_key => $module): ?>
                            <?php if (($module['status'] ?? 'active') !== 'disabled'): ?>
                                <a href="/<?= $mod_key ?>/" 
                                   class="nav-item <?= $mod_key === $current_module ? 'active' : '' ?>"
                                   style="--module-color: <?= $module['color'] ?? '#3182ce' ?>"
                                   title="<?= htmlspecialchars($module['description'] ?? $module['name']) ?>"
                                   <?php if ($mod_key === $current_module): ?>aria-current="page"<?php endif; ?>>
                                    <span class="nav-icon"><?= $module['icon'] ?? '📄' ?></span>
                                    <span class="nav-text"><?= htmlspecialchars($module['name'] ?? ucfirst($mod_key)) ?></span>
                                    <?php if (($module['status'] ?? 'active') === 'beta'): ?>
                                        <span class="badge-beta">β</span>
                                    <?php endif; ?>
                                    <?php if (($module['status'] ?? 'active') === 'development'): ?>
                                        <span class="badge-dev">DEV</span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </nav>
            <?php endif; ?>

            <!-- Informations page courante -->
            <div class="header-page-info">
                <h1 class="page-main-title">
                    <span class="module-icon"><?= $module_icon ?></span>
                    <?= htmlspecialchars($page_title) ?>
                    <?php if ($module_status === 'development'): ?>
                        <span class="status-badge development">DEV</span>
                    <?php elseif ($module_status === 'beta'): ?>
                        <span class="status-badge beta">BETA</span>
                    <?php endif; ?>
                </h1>
                <?php if (!empty($page_subtitle)): ?>
                    <div class="page-subtitle">
                        <span class="page-subtitle-text"><?= htmlspecialchars($page_subtitle) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Zone utilisateur -->
            <?php if ($user_authenticated): ?>
                <div class="header-user-nav">
                    <div class="user-menu" role="group">
                        <button class="user-menu-trigger" 
                                aria-expanded="false" 
                                aria-haspopup="true"
                                onclick="toggleUserMenu()">
                            <div class="user-avatar">
                                <?php if (!empty($current_user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($current_user['avatar']) ?>" alt="Avatar">
                                <?php else: ?>
                                    <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 2)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="user-details">
                                <span class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></span>
                                <span class="user-role">
                                    <span class="role-badge role-<?= $current_user['role'] ?? 'user' ?>">
                                        <?= htmlspecialchars(ucfirst($current_user['role'] ?? 'user')) ?>
                                    </span>
                                </span>
                            </div>
                            <div class="dropdown-icon">▼</div>
                        </button>

                        <!-- Menu utilisateur -->
                        <div class="user-dropdown-menu" id="userDropdownMenu" aria-hidden="true">
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
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            <a href="/auth/logout.php" class="dropdown-item logout">
                                <span class="dropdown-icon">🚪</span>
                                <span class="dropdown-text">Déconnexion</span>
                            </a>
                        </div>
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

    <!-- BREADCRUMB STICKY FULL WIDTH -->
    <?php if ($user_authenticated && !empty($breadcrumbs)): ?>
    <nav class="breadcrumb-nav sticky" role="navigation" aria-label="Fil d'Ariane">
        <div class="breadcrumb-container">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li class="breadcrumb-item <?= $crumb['active'] ? 'active' : '' ?>">
                        <?php if (!$crumb['active'] && !empty($crumb['url'])): ?>
                            <a href="<?= htmlspecialchars($crumb['url']) ?>">
                                <span class="breadcrumb-icon"><?= $crumb['icon'] ?></span>
                                <span class="breadcrumb-text"><?= htmlspecialchars($crumb['text']) ?></span>
                            </a>
                        <?php else: ?>
                            <span class="breadcrumb-icon"><?= $crumb['icon'] ?></span>
                            <span class="breadcrumb-text"><?= htmlspecialchars($crumb['text']) ?></span>
                        <?php endif; ?>
                        
                        <?php if (!$crumb['active'] && $index < count($breadcrumbs) - 1): ?>
                            <span class="breadcrumb-separator">›</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </nav>
    <?php endif; ?>

    <!-- JavaScript modulaire - CORRECTION DU CHEMIN DOUBLE -->
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        $js_path = "/{$current_module}/assets/js/{$current_module}.js";
        $js_file = ROOT_PATH . "/public{$js_path}";
        
        if (file_exists($js_file)): ?>
            <script src="<?= $js_path ?>?v=<?= $build_number ?>" defer></script>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- JavaScript global -->
    <script src="/assets/js/portal.js?v=<?= $build_number ?>" defer></script>
    
    <script>
    // Menu utilisateur
    function toggleUserMenu() {
        const menu = document.getElementById('userDropdownMenu');
        const trigger = document.querySelector('.user-menu-trigger');
        const isOpen = menu.getAttribute('aria-hidden') === 'false';
        
        menu.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        menu.style.display = isOpen ? 'none' : 'block';
    }

    // Fermer menu si clic extérieur
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-menu')) {
            const menu = document.getElementById('userDropdownMenu');
            const trigger = document.querySelector('.user-menu-trigger');
            if (menu && trigger) {
                menu.setAttribute('aria-hidden', 'true');
                trigger.setAttribute('aria-expanded', 'false');
                menu.style.display = 'none';
            }
        }
    });
    </script>
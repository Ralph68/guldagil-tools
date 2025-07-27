<?php
/**
 * Titre: Header principal du portail avec chemins CSS/JS corrigés
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
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

    <!-- CSS principal OBLIGATOIRE - chemins critiques à préserver -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    <!-- CSS bannière cookie RGPD -->
    <link rel="stylesheet" href="/assets/css/cookie_banner.css?v=<?= $build_number ?>">

    <!-- CSS modulaire avec fallback intelligent et chemins CORRIGÉS -->
    <?php if ($module_css && $current_module !== 'home'): ?>
        <?php 
        // 1. Priorité : nouveau système dans /public/module/assets/
        $new_css_path = "{$current_module}/assets/css/{$current_module}.css";
        $module_css_loaded = false;
        
        // Vérification avec le chemin absolu pour file_exists
        if (file_exists(ROOT_PATH . "/public{$new_css_path}")): ?>
            <link rel="stylesheet" href="<?= $new_css_path ?>?v=<?= $build_number ?>">
            <?php $module_css_loaded = true; ?>
        <?php endif; ?>
        
        <?php if (!$module_css_loaded): ?>
            <?php 
            // 2. Fallback : ancien système
            $legacy_paths = [
                "/assets/css/modules/{$current_module}.css",
                "/{$current_module}/css/{$current_module}.css"
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
            <!-- Logo et branding -->
            <a href="/" class="header-brand">
                <div class="header-logo">
                    <?php if (file_exists(ROOT_PATH . '/public/assets/img/logo.png')): ?>
                        <img src="/assets/img/logo.png" alt="<?= $app_name ?>" width="32" height="32">
                    <?php else: ?>
                        <div class="logo-placeholder"><?= $module_icon ?></div>
                    <?php endif; ?>
                </div>
                <div class="header-brand-text">
                    <span class="brand-name"><?= $app_name ?></span>
                    <span class="brand-version">v<?= $app_version ?></span>
                </div>
            </a>

            <!-- Titre de page/module -->
            <div class="header-page-info">
                <h1 class="page-title"><?= $page_title ?></h1>
                <?php if ($current_module !== 'home'): ?>
                    <div class="module-indicator">
                        <span class="module-icon"><?= $module_icon ?></span>
                        <span class="module-name"><?= $all_modules[$current_module]['name'] ?? ucfirst($current_module) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Zone utilisateur -->
            <?php if ($user_authenticated): ?>
                <div class="header-user">
                    <div class="user-dropdown">
                        <button class="user-menu-trigger" id="userMenuTrigger">
                            <div class="user-avatar">
                                <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></span>
                                <span class="user-role"><?= htmlspecialchars($current_user['role'] ?? 'User') ?></span>
                            </div>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <a href="/user/profile.php" class="dropdown-item">
                                👤 Mon profil
                            </a>
                            <a href="/user/settings.php" class="dropdown-item">
                                ⚙️ Paramètres
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/auth/logout.php" class="dropdown-item logout">
                                🚪 Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="header-auth">
                    <a href="/auth/login.php" class="btn btn-primary">Se connecter</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Navigation modules -->
    <?php if ($user_authenticated && !empty($navigation_modules)): ?>
    <nav class="modules-nav">
        <div class="nav-container">
            <div class="nav-items">
                <?php foreach ($navigation_modules as $module => $module_info): ?>
                    <a href="/<?= $module ?>/" 
                       class="nav-item <?= $current_module === $module ? 'active' : '' ?>"
                       title="<?= htmlspecialchars($module_info['description'] ?? '') ?>">
                        <span class="nav-icon"><?= $module_info['icon'] ?? '📁' ?></span>
                        <span class="nav-text"><?= htmlspecialchars($module_info['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Contenu principal commence ici -->
    <main class="portal-main">

<!-- JavaScript CORRIGÉ pour éviter duplication du nom du module -->
<?php if ($module_js && $current_module !== 'home'): ?>
    <?php 
    // Priorité : nouveau système dans /public/module/assets/ - CHEMIN CORRIGÉ
    $new_js_path = "{$current_module}/assets/js/{$current_module}.js";
    $module_js_loaded = false;
    
    // Vérification avec le chemin absolu pour file_exists
    if (file_exists(ROOT_PATH . "/public{$new_js_path}")): ?>
        <script src="<?= $new_js_path ?>?v=<?= $build_number ?>"></script>
        <?php $module_js_loaded = true; ?>
    <?php endif; ?>
    
    <?php if (!$module_js_loaded): ?>
        <?php 
        // Fallback : ancien système
        $legacy_js_paths = [
            "/assets/js/modules/{$current_module}.js",
            "/{$current_module}/js/{$current_module}.js"
        ];
        
        foreach ($legacy_js_paths as $js_path):
            if (file_exists(ROOT_PATH . "/public" . $js_path)): ?>
                <script src="<?= $js_path ?>?v=<?= $build_number ?>"></script>
                <?php break; ?>
            <?php endif;
        endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<!-- JavaScript bannière cookie RGPD -->
<script src="/assets/js/cookie_banner.js?v=<?= $build_number ?>"></script>
<script src="/assets/js/cookie_config.js?v=<?= $build_number ?>"></script>
<!-- Analytics -->
<script src="/assets/js/analytics.js?v=<?= $build_number ?>"></script>

<!-- JavaScript pour dropdown utilisateur -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userMenuTrigger = document.getElementById('userMenuTrigger');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userMenuTrigger && userDropdownMenu) {
        userMenuTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            userDropdownMenu.classList.toggle('show');
        });
        
        // Fermer au clic extérieur
        document.addEventListener('click', function(e) {
            if (!userMenuTrigger.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
    }
});
</script>
<?php
/**
 * Titre: Header du portail Guldagil - VERSION COMPLÈTE CORRIGÉE
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Chargement config debug si disponible
if (file_exists(ROOT_PATH . '/config/debug.php')) {
    require_once ROOT_PATH . '/config/debug.php';
}

// Initialisation des variables par défaut
$user_authenticated = false;
$current_user = null;

// Vérification authentification selon système disponible
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
    if (defined('DEBUG') && DEBUG) {
        error_log("Erreur auth header: " . $e->getMessage());
    }
    // Continue sans auth si erreur
}

// Variables avec fallbacks sécurisés
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
$page_description = htmlspecialchars($page_description ?? 'Portail de gestion');
$current_module = htmlspecialchars($current_module ?? 'home');

// Utilisation des nouvelles variables de config
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '001';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// Configuration modules avec routes et permissions
$all_modules = [
    'home' => ['icon' => '🏠', 'color' => '#3182ce', 'status' => 'active', 'name' => 'Accueil', 'routes' => ['', 'home']],
    'calculateur' => ['icon' => '🚛', 'color' => '#059669', 'status' => 'active', 'name' => 'Calculateur', 'routes' => ['calculateur', 'calc']],
    'adr' => ['icon' => '⚠️', 'color' => '#dc2626', 'status' => 'active', 'name' => 'ADR', 'routes' => ['adr']],
    'admin' => ['icon' => '⚙️', 'color' => '#7c3aed', 'status' => 'active', 'name' => 'Admin', 'routes' => ['admin']],
    'qualite' => ['icon' => '✅', 'color' => '#059669', 'status' => 'active', 'name' => 'Qualité', 'routes' => ['qualite']],
    'maintenance' => ['icon' => '🔧', 'color' => '#ea580c', 'status' => 'beta', 'name' => 'Maintenance', 'routes' => ['maintenance']],
    'stats' => ['icon' => '📊', 'color' => '#0891b2', 'status' => 'active', 'name' => 'Stats', 'routes' => ['stats']],
    'user' => ['icon' => '👤', 'color' => '#7c2d12', 'status' => 'active', 'name' => 'Utilisateur', 'routes' => ['user', 'profile']],
    'debug' => ['icon' => '🔧', 'color' => '#dc2626', 'status' => 'development', 'name' => 'Debug', 'routes' => ['debug']]
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

// Fonction pour obtenir la classe CSS du badge de rôle
function getRoleBadgeClass($role) {
    switch(strtolower($role)) {
        case 'admin': return 'role-admin';
        case 'manager': return 'role-manager';
        case 'moderator': return 'role-moderator';
        default: return 'role-user';
    }
}

// Navigation modules disponibles
$navigation_modules = [];
if ($user_authenticated) {
    $user_role = $current_user['role'] ?? 'user';
    foreach ($all_modules as $key => $module) {
        // Filtrer selon permissions
        if ($module['status'] === 'development' && !in_array($user_role, ['admin', 'dev'])) {
            continue;
        }
        if ($key !== 'home') {
            $navigation_modules[$key] = $module;
        }
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
                <div class="user-menu-trigger" id="userMenuTrigger" aria-haspopup="true" aria-expanded="false">
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
                    
                    <div class="dropdown-section">
                        <a href="/user/profile.php" class="dropdown-item">
                            <div class="dropdown-item-icon">👤</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Mon profil</div>
                                <div class="dropdown-subtitle">Informations personnelles</div>
                            </div>
                        </a>
                        <a href="/user/" class="dropdown-item">
                            <div class="dropdown-item-icon">⚙️</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Paramètres</div>
                                <div class="dropdown-subtitle">Préférences utilisateur</div>
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
                        <a href="#" class="dropdown-item" onclick="showHelp()">
                            <div class="dropdown-item-icon">❓</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Aide</div>
                                <div class="dropdown-subtitle">Support et documentation</div>
                            </div>
                        </a>
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
                    $can_access = !($module_data['status'] === 'development' && !in_array($user_role, ['admin', 'dev']));
                    $is_active = $current_module === $module_key;
                    $css_classes = ['module-nav-item'];
                    if ($is_active) $css_classes[] = 'active';
                    if (!$can_access) $css_classes[] = 'disabled';
                    
                    if ($can_access || ($module_data['status'] === 'development' && in_array($user_role, ['admin', 'dev']))):
                        $href = $can_access ? "/{$module_key}/" : "#";
                ?>
                    <a href="<?= $href ?>" 
                       class="<?= implode(' ', $css_classes) ?>"
                       style="--module-color: <?= $module_data['color'] ?? '#3182ce' ?>"
                       <?= !$can_access ? 'title="Module en développement - Accès restreint"' : '' ?>>
                        <span class="module-nav-icon"><?= $module_data['icon'] ?? '📁' ?></span>
                        <span class="module-nav-name"><?= htmlspecialchars($module_data['name']) ?></span>
                        <?php if ($module_data['status'] === 'beta'): ?>
                            <span class="status-badge beta">BETA</span>
                        <?php elseif ($module_data['status'] === 'development'): ?>
                            <span class="status-badge dev">DEV</span>
                        <?php endif; ?>
                    </a>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
            
            <!-- Menu burger mobile -->
            <button class="mobile-menu-toggle" aria-label="Menu modules" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Fil d'Ariane -->
    <?php if (count($breadcrumbs) > 1): ?>
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
    <?php endif; ?>

    <!-- Contenu principal -->
    <main class="portal-main">

    <!-- JavaScript pour interactions header -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion menu utilisateur
            const userMenuTrigger = document.getElementById('userMenuTrigger');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userMenuTrigger && userDropdown) {
                // Toggle menu au clic
                userMenuTrigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isExpanded = userMenuTrigger.getAttribute('aria-expanded') === 'true';
                    userMenuTrigger.setAttribute('aria-expanded', !isExpanded);
                    userDropdown.setAttribute('aria-hidden', isExpanded);
                    userDropdown.classList.toggle('show');
                });
                
                // Fermer menu si clic ailleurs
                document.addEventListener('click', function(e) {
                    if (!userMenuTrigger.contains(e.target) && !userDropdown.contains(e.target)) {
                        userMenuTrigger.setAttribute('aria-expanded', 'false');
                        userDropdown.setAttribute('aria-hidden', 'true');
                        userDropdown.classList.remove('show');
                    }
                });
                
                // Gestion clavier (ESC)
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        userMenuTrigger.setAttribute('aria-expanded', 'false');
                        userDropdown.setAttribute('aria-hidden', 'true');
                        userDropdown.classList.remove('show');
                    }
                });
            }
            
            // Gestion menu mobile
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

    <!-- JavaScript spécifique au module -->
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        // JS depuis /public/nomdumodule/assets/js/nomdumodule.js
        $module_js_path = "/public/{$current_module}/assets/js/{$current_module}.js";
        
        if (file_exists(ROOT_PATH . $module_js_path)): ?>
            <script src="<?= $module_js_path ?>?v=<?= $build_number ?>"></script>
        <?php endif; ?>
    <?php endif; ?>

<?php
/**
 * Titre: Header du portail Guldagil - VERSION AVEC SYSTÈME DE RÔLES CENTRALISÉ + ADAPTATIF
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
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

// === VÉRIFICATION AUTHENTIFICATION ===
if (!$is_public_page) {
    // Page protégée : authentification OBLIGATOIRE
    
    $auth_success = false;
    
    // Méthode 1: Essayer AuthManager
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        try {
            require_once ROOT_PATH . '/core/auth/AuthManager.php';
            $auth = new AuthManager();
            
            if ($auth->isAuthenticated()) {
                $current_user = $auth->getCurrentUser();
                $auth_success = true;
                
                // Synchroniser avec sessions pour compatibilité
                $_SESSION['authenticated'] = true;
                $_SESSION['user'] = $current_user;
                $_SESSION['user_id'] = $current_user['id'] ?? 1;
                $_SESSION['user_role'] = $current_user['role'] ?? 'user';
                $_SESSION['username'] = $current_user['username'] ?? 'Utilisateur';
            }
        } catch (Exception $e) {
            error_log("Erreur AuthManager: " . $e->getMessage());
            // Continuer vers fallback
        }
    }
    
    // Méthode 2: Fallback session simple
    if (!$auth_success) {
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
            $auth_success = true;
            
            // Synchroniser pour admin
            $_SESSION['user_id'] = $current_user['id'] ?? 1;
            $_SESSION['user_role'] = $current_user['role'] ?? 'user';
        }
    }
    
    // Si aucune authentification trouvée : REDIRECTION FORCÉE
    if (!$auth_success) {
        $redirect_url = $_SERVER['REQUEST_URI'] ?? '/';
        $login_url = '/auth/login.php?redirect=' . urlencode($redirect_url);
        header('Location: ' . $login_url);
        exit;
    }
    
    $user_authenticated = true;
    
} else {
    // Page publique : juste vérifier le statut sans forcer
    
    // Méthode 1: AuthManager
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
    
    // Méthode 2: Fallback session
    if (!$user_authenticated) {
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            $user_authenticated = true;
            $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
        }
    }
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
    <!-- CSS bannière cookie RGPD -->
    <link rel="stylesheet" href="/assets/css/cookie_banner.css?v=<?= $build_number ?>">

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
        
        /* CSS Header adaptatif intégré */
        .portal-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .portal-header.header-compact {
            padding: 0.5rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.15);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 1.5rem;
            min-height: 64px;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: all 0.15s ease;
            padding: 0.5rem;
            border-radius: 0.375rem;
        }

        .header-brand:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .header-logo {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .header-brand-text {
            font-size: 1rem;
        }

        .portal-header.header-compact .header-brand-text {
            font-size: 0.9rem;
        }

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
            gap: 0.5rem;
        }

        .portal-header.header-compact .page-main-title {
            font-size: 1.1rem;
        }

        .page-subtitle {
            margin-top: 0.25rem;
            opacity: 0.9;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .portal-header.header-compact .page-subtitle {
            opacity: 0;
            height: 0;
            overflow: hidden;
        }

        .header-user-nav {
            position: relative;
        }

        .user-menu-trigger {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-menu-trigger:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .portal-header.header-compact .user-avatar {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .portal-header.header-compact .user-details {
            display: none;
        }

        .user-name {
            color: white;
            font-weight: 500;
            font-size: 0.875rem;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-role {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .dropdown-icon {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
            transition: transform 0.2s ease;
        }

        .user-menu-trigger[aria-expanded="true"] .dropdown-icon {
            transform: rotate(180deg);
        }

        .user-dropdown {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            min-width: 220px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            overflow: hidden;
            display: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .portal-main {
            margin-top: 120px;
            transition: margin-top 0.3s ease;
        }

        body.header-compact .portal-main {
            margin-top: 100px;
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
    <header class="portal-header" id="mainHeader">
        <div class="header-container">
            <!-- Logo et branding -->
            <a href="/" class="header-brand">
                <div class="header-logo">
                    <?php if (file_exists(ROOT_PATH . '/assets/img/logo.png')): ?>
                        <img src="/assets/img/logo.png" alt="Logo" width="32" height="32">
                    <?php else: ?>
                        💧
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
                    
                    <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'view_admin')): ?>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-section">
                        <a href="/admin/" class="dropdown-item">
                            <div class="dropdown-item-icon">🔧</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Administration</div>
                                <div class="dropdown-subtitle">Gestion du portail</div>
                            </div>
                        </a>
                        
                        <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'manage_users')): ?>
                        <a href="/admin/users.php" class="dropdown-item">
                            <div class="dropdown-item-icon">👥</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Utilisateurs</div>
                                <div class="dropdown-subtitle">Gestion des comptes</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'manage_system')): ?>
                        <a href="/admin/system.php" class="dropdown-item">
                            <div class="dropdown-item-icon">🛠️</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Système</div>
                                <div class="dropdown-subtitle">Configuration avancée</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'access_dev')): ?>
                        <a href="/dev/" class="dropdown-item">
                            <div class="dropdown-item-icon">💻</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Développement</div>
                                <div class="dropdown-subtitle">Outils développeur</div>
                            </div>
                        </a>
                        <?php endif; ?>
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
            <div class="modules-nav-items" style="justify-content: center;">
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
            const header = document.getElementById('mainHeader');
            const userMenuTrigger = document.getElementById('userMenuTrigger');
            const userDropdown = document.getElementById('userDropdown');
            
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
                        document.body.classList.add('header-compact');
                    } else {
                        header.classList.remove('header-compact');
                        document.body.classList.remove('header-compact');
                    }
                }

                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    // Actions supplémentaires si nécessaire
                }, 10);
            }

            // Gestion menu utilisateur
            if (userMenuTrigger && userDropdown) {
                // Toggle menu au clic
                userMenuTrigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isExpanded = userMenuTrigger.getAttribute('aria-expanded') === 'true';
                    userMenuTrigger.setAttribute('aria-expanded', !isExpanded);
                    userDropdown.setAttribute('aria-hidden', isExpanded);
                    userDropdown.style.display = !isExpanded ? 'block' : 'none';
                });
                
                // Fermer menu si clic ailleurs
                document.addEventListener('click', function(e) {
                    if (!userMenuTrigger.contains(e.target) && !userDropdown.contains(e.target)) {
                        userMenuTrigger.setAttribute('aria-expanded', 'false');
                        userDropdown.setAttribute('aria-hidden', 'true');
                        userDropdown.style.display = 'none';
                    }
                });
                
                // Gestion clavier (ESC)
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        userMenuTrigger.setAttribute('aria-expanded', 'false');
                        userDropdown.setAttribute('aria-hidden', 'true');
                        userDropdown.style.display = 'none';
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

            // Event listener scroll
            window.addEventListener('scroll', handleScroll, { passive: true });
            
            // Initialisation
            handleScroll();
        });
        
        // Fonction helper pour aide
        function showHelp() {
            alert('Aide contextuelle - Module: <?= $current_module ?>\nVersion: <?= $app_version ?>\nBuild: <?= $build_number ?>');
        }
    </script>

    <!-- JavaScript spécifique au module -->
    <?php if ($module_js && $current_module !== 'home'): ?>
    <script src="/public/<?= $current_module ?>/assets/js/<?= $current_module ?>.js?v=<?= $build_number ?>"></script>
    <?php endif; ?>
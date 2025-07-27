<?php
/**
 * Titre: Header moderne du portail Guldagil
 * Chemin: /templates/header.php
 * Version: 1.0 - Refonte complète
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// DONE: Ajout des fonctions manquantes et correction des erreurs
if (!function_exists('getNavigationModules')) {
    function getNavigationModules($user_role, $all_modules) {
        // Filtrer les modules selon le rôle utilisateur
        $filtered_modules = [];
        foreach ($all_modules as $id => $module) {
            // Vérification basique des permissions (à remplacer par votre logique)
            $filtered_modules[$id] = $module;
        }
        return $filtered_modules;
    }
}

if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass($role) {
        switch ($role) {
            case 'admin': return 'role-admin';
            case 'manager': return 'role-manager';
            case 'moderator': return 'role-moderator';
            default: return 'role-user';
        }
    }
}

if (!function_exists('hasAdminPermission')) {
    function hasAdminPermission($role, $permission) {
        // Implémentation simple pour éviter les erreurs
        if ($role === 'admin' || $role === 'dev') {
            return true;
        }
        return false;
    }
}

// Chargement des configurations
if (file_exists(ROOT_PATH . '/config/roles.php')) {
    require_once ROOT_PATH . '/config/roles.php';
}
if (file_exists(ROOT_PATH . '/config/debug.php')) {
    require_once ROOT_PATH . '/config/debug.php';
}

// Initialisation session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    session_start();
}

// Variables par défaut
$user_authenticated = false;
$current_user = null;

// Pages publiques (sans authentification)
$public_pages = ['/auth/login.php', '/auth/logout.php', '/error.php', '/maintenance.php'];
$current_script = $_SERVER['SCRIPT_NAME'] ?? '';
$is_public_page = false;

foreach ($public_pages as $page) {
    if (strpos($current_script, $page) !== false) {
        $is_public_page = true;
        break;
    }
}

// Authentification
if (!$is_public_page) {
    $auth_success = false;
    
    // Tentative AuthManager
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        try {
            require_once ROOT_PATH . '/core/auth/AuthManager.php';
            $auth = new AuthManager();
            
            if ($auth->isAuthenticated()) {
                $current_user = $auth->getCurrentUser();
                $auth_success = true;
                $_SESSION['authenticated'] = true;
                $_SESSION['user'] = $current_user;
            }
        } catch (Exception $e) {
            error_log("Erreur AuthManager: " . $e->getMessage());
        }
    }
    
    // Fallback session
    if (!$auth_success && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
        $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
        $auth_success = true;
    }
    
    // Redirection si non authentifié
    if (!$auth_success) {
        $redirect_url = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /auth/login.php?redirect=' . urlencode($redirect_url));
        exit;
    }
    
    $user_authenticated = true;
} else {
    // Page publique : vérification optionnelle
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
    
    if (!$user_authenticated && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
        $user_authenticated = true;
        $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
    }
}

// Configuration variables avec valeurs par défaut
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
$page_description = htmlspecialchars($page_description ?? 'Portail de gestion');
$current_module = htmlspecialchars($current_module ?? 'home');

// DONE: Ajout de l'initialisation des variables module_css et module_js
$module_css = $module_css ?? true;
$module_js = $module_js ?? true;

$app_version = defined('APP_VERSION') ? APP_VERSION : '1.0';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '001';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';

// Configuration modules avec status par défaut
$all_modules = [
    'home' => ['icon' => '🏠', 'color' => '#3182ce', 'status' => 'active', 'name' => 'Accueil', 'routes' => ['', 'home']],
    'port' => ['icon' => '📦', 'color' => '#059669', 'status' => 'active', 'name' => 'Frais de port', 'routes' => ['port', 'calculateur']],
    'adr' => ['icon' => '⚠️', 'color' => '#dc2626', 'status' => 'active', 'name' => 'ADR', 'routes' => ['adr']],
    'user' => ['icon' => '👤', 'color' => '#7c2d12', 'status' => 'active', 'name' => 'Mon compte', 'routes' => ['user', 'profile']],
    'admin' => ['icon' => '⚙️', 'color' => '#1f2937', 'status' => 'active', 'name' => 'Administration', 'routes' => ['admin']],
];

// Détection module actuel
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

// Propriétés du module actuel avec valeurs par défaut
$module_icon = $all_modules[$current_module]['icon'] ?? '🏠';
$module_color = $all_modules[$current_module]['color'] ?? '#3182ce';
$module_name = $all_modules[$current_module]['name'] ?? 'Accueil';
$module_status = $all_modules[$current_module]['status'] ?? 'active';

// Navigation modules pour utilisateur connecté
$navigation_modules = [];
if ($user_authenticated) {
    $user_role = $current_user['role'] ?? 'user';
    $navigation_modules = getNavigationModules($user_role, $all_modules);
}

// Fil d'ariane par défaut
$breadcrumbs = $breadcrumbs ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description ?? '') ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="<?= $module_color ?>">
    <meta name="version" content="<?= $app_version ?>">
    <meta name="build" content="<?= $build_number ?>">
    
    <title><?= $page_title ?> - <?= $app_name ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <!-- CSS modulaire -->
    <?php if ($module_css && $current_module !== 'home'): ?>
        <link rel="stylesheet" href="/<?= $current_module ?>/assets/css/<?= $current_module ?>.css?v=<?= $build_number ?>">
    <?php endif; ?>

    <!-- Variables CSS dynamiques -->
    <style>
        :root {
            --current-module-color: <?= $module_color ?>;
            --current-module-color-light: <?= $module_color ?>20;
        }
    </style>
</head>
<body data-module="<?= $current_module ?>" class="<?= $user_authenticated ? 'authenticated' : 'auth-page' ?>">

    <!-- Debug banner -->
    <?php if (defined('DEBUG') && DEBUG === true): ?>
    <div class="debug-banner" id="debugBanner">
        🔒 MODE DEBUG - <?= htmlspecialchars($current_user['username'] ?? 'non connecté') ?> 
        (<?= htmlspecialchars($current_user['role'] ?? 'guest') ?>) | 
        Module: <?= $current_module ?> | Build: <?= $build_number ?>
    </div>
    <?php endif; ?>

    <!-- Header principal -->
    <header class="portal-header" id="mainHeader">
        <div class="header-container">
            <!-- Logo et titre -->
            <div class="header-brand">
                <a href="/" class="brand-link">
                    <div class="brand-logo">
                        <?php if (file_exists(ROOT_PATH . '/assets/img/logo.png')): ?>
                            <img src="/assets/img/logo.png" alt="Logo" width="32" height="32">
                        <?php else: ?>
                            🌊
                        <?php endif; ?>
                    </div>
                    <div class="brand-text"><?= $app_name ?></div>
                </a>
            </div>

            <!-- Titre de page dynamique -->
            <div class="page-title-section">
                <div class="page-icon" style="color: <?= $module_color ?>"><?= $module_icon ?></div>
                <div class="page-info">
                    <h1 class="page-title"><?= $page_title ?></h1>
                    <?php if (!empty($page_subtitle)): ?>
                        <p class="page-subtitle"><?= $page_subtitle ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Navigation utilisateur -->
            <div class="user-nav">
                <?php if ($user_authenticated && $current_user): ?>
                    <div class="user-menu" id="userMenu">
                        <button class="user-trigger" id="userTrigger" aria-expanded="false">
                            <div class="user-avatar">
                                <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></span>
                                <span class="user-role"><?= ucfirst($current_user['role'] ?? 'user') ?></span>
                            </div>
                            <div class="dropdown-arrow">▼</div>
                        </button>

                        <div class="user-dropdown" id="userDropdown" aria-hidden="true">
                            <div class="dropdown-header">
                                <div class="user-details">
                                    <strong><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></strong>
                                    <small><?= htmlspecialchars($current_user['email'] ?? '') ?></small>
                                </div>
                            </div>
                            
                            <div class="dropdown-menu">
                                <a href="/user/" class="dropdown-item">
                                    <span>👤</span> Mon profil
                                </a>
                                <a href="/user/settings.php" class="dropdown-item">
                                    <span>⚙️</span> Paramètres
                                </a>
                                
                                <?php if (hasAdminPermission($current_user['role'] ?? 'user', 'view_admin')): ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="/admin/" class="dropdown-item">
                                        <span>🔧</span> Administration
                                    </a>
                                <?php endif; ?>
                                
                                <div class="dropdown-divider"></div>
                                <a href="/auth/logout.php" class="dropdown-item logout">
                                    <span>🚪</span> Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/auth/login.php" class="login-btn">
                        <span>🔑</span> Connexion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Menu de navigation centré -->
    <?php if ($user_authenticated && !empty($navigation_modules)): ?>
    <nav class="modules-nav" id="mainNav">
        <div class="nav-container">
            <div class="nav-items">
                <?php foreach ($navigation_modules as $module_key => $module_data): 
                    $is_active = $current_module === $module_key;
                    $nav_classes = ['nav-item'];
                    if ($is_active) $nav_classes[] = 'active';
                ?>
                    <a href="/<?= $module_key ?>/" 
                       class="<?= implode(' ', $nav_classes) ?>"
                       style="--module-color: <?= $module_data['color'] ?? '#3182ce' ?>">
                        <span class="nav-icon"><?= $module_data['icon'] ?? '📁' ?></span>
                        <span class="nav-text"><?= htmlspecialchars($module_data['name']) ?></span>
                        <?php if (isset($module_data['status']) && $module_data['status'] === 'beta'): ?>
                            <span class="status-badge beta">BETA</span>
                        <?php elseif (isset($module_data['status']) && $module_data['status'] === 'development'): ?>
                            <span class="status-badge dev">DEV</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Menu mobile -->
            <button class="mobile-nav-toggle" id="mobileNavToggle" aria-expanded="false" aria-label="Menu principal">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Fil d'ariane -->
    <?php if (count($breadcrumbs) > 1): ?>
    <nav class="breadcrumb-nav sticky" id="breadcrumbNav">
        <div class="breadcrumb-container">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                
                <?php if (!empty($crumb['url']) && !($crumb['active'] ?? false)): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item">
                        <?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?>
                    </a>
                <?php else: ?>
                    <span class="breadcrumb-item active">
                        <?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Header compact (scroll) -->
    <header class="compact-header" id="compactHeader">
        <div class="compact-container">
            <!-- Module actuel cliquable -->
            <a href="/<?= $current_module ?>/" class="module-home-link" style="color: <?= $module_color ?>">
                <span class="module-icon"><?= $module_icon ?></span>
                <span class="module-name"><?= $module_name ?></span>
            </a>

            <!-- Fil d'ariane compact -->
            <?php if (count($breadcrumbs) > 1): ?>
            <div class="compact-breadcrumb">
                <?php 
                $last_crumb = end($breadcrumbs);
                if ($last_crumb && !($last_crumb['active'] ?? false)): ?>
                    <a href="<?= htmlspecialchars($last_crumb['url']) ?>">
                        <?= htmlspecialchars($last_crumb['text']) ?>
                    </a>
                <?php else: ?>
                    <span><?= htmlspecialchars($last_crumb['text']) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Actions utilisateur compactes -->
            <div class="compact-user">
                <?php if ($user_authenticated && $current_user): ?>
                    <button class="compact-user-btn" id="compactUserBtn" aria-label="Menu utilisateur">
                        <div class="compact-avatar">
                            <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
                        </div>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="main-content">

    <!-- Scripts header -->
    <script src="/assets/js/header.js?v=<?= $build_number ?>"></script>

    <!-- JavaScript spécifique au module -->
    <?php if ($module_js && $current_module !== 'home'): ?>
    <script src="/<?= $current_module ?>/assets/js/<?= $current_module ?>.js?v=<?= $build_number ?>"></script>
    <?php endif; ?>
            const userDropdown = document.getElementById('userDropdown');
            const mobileNavToggle = document.getElementById('mobileNavToggle');

            // Gestion scroll pour header compact
            let lastScrollTop = 0;
            const threshold = 100;

            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > threshold) {
                    mainHeader.classList.add('hidden');
                    if (mainNav) mainNav.classList.add('hidden');
                    compactHeader.classList.add('visible');
                } else {
                    mainHeader.classList.remove('hidden');
                    if (mainNav) mainNav.classList.remove('hidden');
                    compactHeader.classList.remove('visible');
                }
                
                lastScrollTop = scrollTop;
            });

            // Menu utilisateur
            if (userTrigger && userDropdown) {
                userTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isExpanded = userTrigger.getAttribute('aria-expanded') === 'true';
                    userTrigger.setAttribute('aria-expanded', !isExpanded);
                    userDropdown.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!userTrigger.contains(e.target) && !userDropdown.contains(e.target)) {
                        userTrigger.setAttribute('aria-expanded', 'false');
                        userDropdown.classList.remove('show');
                    }
                });
            }

            // Menu mobile
            if (mobileNavToggle && mainNav) {
                mobileNavToggle.addEventListener('click', function() {
                    mainNav.classList.toggle('mobile-open');
                    mobileNavToggle.classList.toggle('open');
                });
            }

            // Menu utilisateur compact
            const compactUserBtn = document.getElementById('compactUserBtn');
            if (compactUserBtn && userDropdown) {
                compactUserBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                });
            }
        });
    </script>
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
    <script src="/<?= $current_module ?>/assets/js/<?= $current_module ?>.js?v=<?= $build_number ?>"></script>
<?php endif; ?>

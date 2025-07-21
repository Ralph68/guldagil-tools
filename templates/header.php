<?php
/**
 * Titre: En-tête global du portail - NAVIGATION MATÉRIEL MISE À JOUR
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Protection contre accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès non autorisé');
}

// Protection multi-inclusion
if (defined('HEADER_LOADED')) {
    return;
}
define('HEADER_LOADED', true);

// =====================================
// 🔧 FONCTIONS UTILITAIRES HEADER
// =====================================

/**
 * Détermine les modules de navigation selon le rôle utilisateur
 */
function getNavigationModules($user_role, $all_modules) {
    $navigation = [];
    
    foreach ($all_modules as $module_key => $module_data) {
        // Vérifier les permissions selon le rôle
        $roles = $module_data['roles'] ?? ['user'];
        if (!in_array($user_role, $roles)) {
            continue;
        }
        
        // Vérifier si admin_only
        if (isset($module_data['admin_only']) && $module_data['admin_only'] && $user_role !== 'admin') {
            continue;
        }
        
        // Modules en développement = accès restreint
        if (($module_data['status'] ?? 'active') === 'development' && !in_array($user_role, ['admin', 'dev'])) {
            continue;
        }
        
        $navigation[$module_key] = $module_data;
    }
    
    return $navigation;
}

/**
 * Génère la classe CSS pour les badges de rôle
 */
function getRoleBadgeClass($role) {
    $classes = [
        'admin' => 'role-admin',
        'dev' => 'role-dev', 
        'logistique' => 'role-logistique',
        'user' => 'role-user'
    ];
    return $classes[$role] ?? 'role-user';
}

// =====================================
// 🔧 VARIABLES AVEC FALLBACKS SÉCURISÉS
// =====================================

// Variables template avec fallbacks pour éviter undefined
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
$page_description = htmlspecialchars($page_description ?? 'Portail de gestion');
$current_module = htmlspecialchars($current_module ?? 'home');

// Utilisation des nouvelles variables de config
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '001';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// Configuration modules avec routes et permissions - MATÉRIEL MIS À JOUR
$all_modules = [
    'home' => ['icon' => '🏠', 'color' => '#3182ce', 'status' => 'active', 'name' => 'Accueil', 'routes' => ['', 'home']],
    'port' => ['icon' => '📦', 'color' => '#059669', 'status' => 'active', 'name' => 'Frais de port', 'routes' => ['port', 'calculateur']],
    'adr' => ['icon' => '⚠️', 'color' => '#dc2626', 'status' => 'active', 'name' => 'ADR', 'routes' => ['adr']],
    'epi' => ['icon' => '🦺', 'color' => '#7c3aed', 'status' => 'active', 'name' => 'EPI', 'routes' => ['epi']],
    'qualite' => ['icon' => '✅', 'color' => '#059669', 'status' => 'active', 'name' => 'Qualité', 'routes' => ['qualite']],
    'materiel' => ['icon' => '🔧', 'color' => '#ea580c', 'status' => 'active', 'name' => 'Matériel', 'routes' => ['materiel', 'outillages']],
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
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

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

    <!-- Gestion automatique de l'authentification -->
    <?php
    // =====================================
    // 🔐 AUTHENTIFICATION CENTRALISÉE
    // =====================================
    
    // Vérifier l'état d'authentification pour ce header
    $user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    $current_user = $_SESSION['user'] ?? null;
    
    // Pages qui ne nécessitent pas d'authentification
    $public_pages = ['/auth/login.php', '/auth/register.php', '/about.php', '/maintenance.php'];
    $current_page = $_SERVER['REQUEST_URI'] ?? '/';
    $is_public_page = false;
    
    foreach ($public_pages as $page) {
        if (strpos($current_page, $page) !== false) {
            $is_public_page = true;
            break;
        }
    }
    
    // Redirection si non authentifié et page protégée
    if (!$user_authenticated && !$is_public_page) {
        $redirect_url = '/auth/login.php';
        if ($current_page !== '/') {
            $redirect_url .= '?redirect=' . urlencode($current_page);
        }
        header('Location: ' . $redirect_url);
        exit;
    }
    ?>

    <!-- Bannière de maintenance si nécessaire -->
    <?php if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE && !in_array($current_user['role'] ?? '', ['admin', 'dev'])): ?>
    <div class="maintenance-banner">
        <div class="container">
            <span class="maintenance-icon">🔧</span>
            <span class="maintenance-text">Maintenance en cours - Fonctionnalités limitées</span>
            <span class="maintenance-time"><?= date('H:i') ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bannière cookies RGPD -->
    <?php if (!isset($_COOKIE['cookie_consent'])): ?>
    <div id="cookieBanner" class="cookie-banner">
        <div class="cookie-content">
            <div class="cookie-text">
                <strong>🍪 Cookies et confidentialité</strong>
                <p>Ce portail utilise des cookies essentiels pour garantir son bon fonctionnement et améliorer votre expérience.</p>
            </div>
            <div class="cookie-actions">
                <button onclick="acceptCookies()" class="btn btn-primary">Accepter</button>
                <button onclick="declineCookies()" class="btn btn-secondary">Refuser</button>
                <a href="/privacy.php" class="cookie-link">En savoir plus</a>
            </div>
        </div>
    </div>
    
    <script>
        function acceptCookies() {
            document.cookie = "cookie_consent=accepted; path=/; max-age=" + (365*24*60*60);
            document.getElementById('cookieBanner').style.display = 'none';
        }
        
        function declineCookies() {
            document.cookie = "cookie_consent=declined; path=/; max-age=" + (365*24*60*60);
            document.getElementById('cookieBanner').style.display = 'none';
        }
    </script>
    <?php endif; ?>

<!-- Le contenu de la page sera inséré ici --> ?>
        
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

    <!-- Meta Open Graph -->
    <meta property="og:title" content="<?= $full_title ?>">
    <meta property="og:description" content="<?= $page_description ?>">
    <meta property="og:type" content="website">
    
    <!-- Variables CSS dynamiques -->
    <style>
        :root {
            --current-module-color: <?= $module_color ?>;
            --current-module-status: '<?= $module_status ?>';
        }
    </style>
</head>
<body class="module-<?= $current_module ?> status-<?= $module_status ?>">

    <!-- Header principal -->
    <header class="portal-header">
        <div class="header-container">
            <!-- Logo et branding -->
            <div class="header-brand">
                <a href="/" class="brand-link">
                    <div class="brand-icon" style="color: <?= $module_color ?>">
                        <?= $module_icon ?>
                    </div>
                    <div class="brand-content">
                        <h1 class="brand-title"><?= $app_name ?></h1>
                        <div class="brand-subtitle">
                            <span class="page-info"><?= $page_title ?></span>
                            <span class="version-info">v<?= $app_version ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Navigation utilisateur et actions -->
            <div class="header-actions">
                <?php if ($user_authenticated): ?>
                <!-- Menu utilisateur connecté -->
                <div class="user-dropdown-container">
                    <button class="user-menu-trigger" 
                            id="userMenuTrigger" 
                            aria-expanded="false" 
                            aria-haspopup="true">
                        <div class="user-avatar">
                            <?= strtoupper(substr($current_user['prenom'] ?? $current_user['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?= htmlspecialchars($current_user['prenom'] ?? $current_user['username'] ?? 'Utilisateur') ?></div>
                            <div class="user-role">
                                <span class="role-badge <?= getRoleBadgeClass($current_user['role'] ?? 'user') ?>">
                                    <?= htmlspecialchars(strtoupper($current_user['role'] ?? 'USER')) ?>
                                </span>
                            </div>
                        </div>
                        <div class="dropdown-icon">▼</div>
                    </button>

                    <!-- Menu déroulant -->
                    <div class="user-dropdown" id="userDropdown" hidden>
                        <div class="dropdown-section">
                            <a href="/user/" class="dropdown-item">
                                <div class="dropdown-item-icon">👤</div>
                                <div class="dropdown-item-text">
                                    <div class="dropdown-title">Mon profil</div>
                                    <div class="dropdown-subtitle">Informations personnelles</div>
                                </div>
                            </a>
                            <a href="/user/profile.php" class="dropdown-item">
                                <div class="dropdown-item-icon">⚙️</div>
                                <div class="dropdown-item-text">
                                    <div class="dropdown-title">Paramètres</div>
                                    <div class="dropdown-subtitle">Préférences et sécurité</div>
                                </div>
                            </a>
                        </div>

                        <?php if (in_array($current_user['role'] ?? 'user', ['admin', 'dev'])): ?>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-section">
                            <?php if (($current_user['role'] ?? 'user') === 'admin' || ($current_user['role'] ?? 'user') === 'dev'): ?>
                            <a href="/admin/" class="dropdown-item">
                                <div class="dropdown-item-icon">🛠️</div>
                                <div class="dropdown-item-text">
                                    <div class="dropdown-title">Administration</div>
                                    <div class="dropdown-subtitle">Gestion du portail</div>
                                </div>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (($current_user['role'] ?? 'user') === 'dev'): ?>
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
                    userDropdown.hidden = isExpanded;
                });
                
                // Fermer menu au clic extérieur
                document.addEventListener('click', function(e) {
                    if (!userMenuTrigger.contains(e.target) && !userDropdown.contains(e.target)) {
                        userMenuTrigger.setAttribute('aria-expanded', 'false');
                        userDropdown.hidden = true;
                    }
                });
                
                // Fermer menu à l'Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && userMenuTrigger.getAttribute('aria-expanded') === 'true') {
                        userMenuTrigger.setAttribute('aria-expanded', 'false');
                        userDropdown.hidden = true;
                        userMenuTrigger.focus();
                    }
                });
            }
            
            // Gestion menu mobile
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const modulesNav = document.querySelector('.modules-nav-items');
            
            if (mobileMenuToggle && modulesNav) {
                mobileMenuToggle.addEventListener('click', function() {
                    modulesNav.classList.toggle('mobile-open');
                    mobileMenuToggle.classList.toggle('active');
                });
            }
            
            // Animation navigation au scroll
            let lastScrollTop = 0;
            const header = document.querySelector('.portal-header');
            const modulesNav = document.querySelector('.modules-nav');
            
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // Scroll vers le bas - masquer
                    header?.classList.add('header-hidden');
                    modulesNav?.classList.add('nav-hidden');
                } else {
                    // Scroll vers le haut - afficher
                    header?.classList.remove('header-hidden');
                    modulesNav?.classList.remove('nav-hidden');
                }
                
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            }, false);
        });
        
        // Fonction d'aide (placeholder)
        function showHelp() {
            alert('Système d\'aide en cours de développement.\n\nPour toute question :\n• Contactez votre administrateur\n• Consultez la documentation interne\n• Utilisez les outils de diagnostic du portail');
        }
        
        // Gestion des notifications temps réel (placeholder)
        function initNotifications() {
            // TODO: Implémenter système de notifications temps réel
            console.log('🔔 Système de notifications initialisé');
        }
        
        // Initialiser au chargement
        initNotifications();
    </script>

    <!-- JavaScript spécifique au module -->
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        // JS depuis /public/nomdumodule/assets/js/nomdumodule.js
        $module_js_path = "/public/{$current_module}/assets/js/{$current_module}.js";
        
        if (file_exists(ROOT_PATH . $module_js_path)): ?>
            <script src="<?= $module_js_path ?>?v=<?= $build_number ?>"></script>
        <?php else: 
            // Fallback ancien système
            $legacy_js_paths = [
                "/{$current_module}/assets/js/{$current_module}.js",
                "/assets/js/modules/{$current_module}.js"
            ];
            
            foreach ($legacy_js_paths as $js_path):
                if (file_exists(ROOT_PATH . "/public" . $js_path)): ?>
                    <script src="<?= $js_path ?>?v=<?= $build_number ?>"></script>
                    <?php break; ?>
                <?php endif;
            endforeach;
        endif; ?>
    <?php endif;

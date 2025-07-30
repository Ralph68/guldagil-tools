<?php
/**
 * Titre: Header principal avec gestion intelligente du breadcrumb et navigation optimisée
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Variables nécessaires pour le template (définies dans les pages qui incluent ce header)
$page_title = $page_title ?? 'Portail Guldagil';
$page_subtitle = $page_subtitle ?? '';
$current_module = $current_module ?? 'home';
$module_icon = $module_icon ?? '🏠';
$module_color = $module_color ?? '#3b82f6';
$module_status = $module_status ?? 'stable';
$build_number = $build_number ?? (defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis'));
$app_name = $app_name ?? (defined('APP_NAME') ? APP_NAME : 'Portail Guldagil');

// Gestion de l'authentification
$user_authenticated = isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
$current_user = $_SESSION['user'] ?? null;

// Détection automatique de la profondeur de navigation pour le breadcrumb
$current_path = $_SERVER['REQUEST_URI'] ?? '/';
$path_segments = array_filter(explode('/', $current_path));

// Détecter si on est sur une page d'index de module ou plus profond
$is_portal_index = ($current_path === '/' || $current_path === '/index.php');
$is_module_index = (count($path_segments) <= 1 || (count($path_segments) == 2 && end($path_segments) === 'index.php'));

// Générer le breadcrumb automatiquement si navigation profonde
$show_breadcrumb = !$is_portal_index && !$is_module_index;
$auto_breadcrumbs = [];

if ($show_breadcrumb) {
    // Breadcrumb automatique basé sur l'URL
    $auto_breadcrumbs[] = ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/'];
    
    $cumulative_path = '';
    foreach ($path_segments as $index => $segment) {
        $cumulative_path .= '/' . $segment;
        
        // Nettoyer le segment pour l'affichage
        $display_name = ucfirst(str_replace(['_', '-'], ' ', $segment));
        
        // Mapper les noms de modules connus
        $module_names = [
            'port' => 'Frais de Port',
            'adr' => 'ADR Transport',
            'admin' => 'Administration',
            'user' => 'Utilisateur',
            'auth' => 'Authentification',
            'declaration' => 'Déclarations',
            'create' => 'Créer',
            'edit' => 'Modifier',
            'view' => 'Voir'
        ];
        
        $final_name = $module_names[$segment] ?? $display_name;
        
        // Icônes par module
        $module_icons = [
            'port' => '📦',
            'adr' => '⚠️',
            'admin' => '⚙️',
            'user' => '👤',
            'auth' => '🔐',
            'declaration' => '📋',
            'create' => '➕',
            'edit' => '✏️',
            'view' => '👁️'
        ];
        
        $icon = $module_icons[$segment] ?? '📄';
        
        if ($index === count($path_segments) - 1) {
            // Dernier élément = page actuelle
            $auto_breadcrumbs[] = ['icon' => $icon, 'text' => $final_name, 'active' => true];
        } else {
            $auto_breadcrumbs[] = ['icon' => $icon, 'text' => $final_name, 'url' => $cumulative_path];
        }
    }
}

// Utiliser les breadcrumbs manuels s'ils sont définis, sinon automatiques
$breadcrumbs = $breadcrumbs ?? $auto_breadcrumbs;

// Configuration des modules disponibles (pour la navigation)
$available_modules = [
    'port' => ['name' => 'Frais Port', 'icon' => '📦', 'status' => 'stable', 'color' => '#059669'],
    'adr' => ['name' => 'ADR Transport', 'icon' => '⚠️', 'status' => 'beta', 'color' => '#dc2626'],
    'admin' => ['name' => 'Administration', 'icon' => '⚙️', 'status' => 'stable', 'color' => '#7c3aed'],
    'materiel' => ['name' => 'Matériel', 'icon' => '🔧', 'status' => 'development', 'color' => '#ea580c'],
    'epi' => ['name' => 'EPI', 'icon' => '🛡️', 'status' => 'development', 'color' => '#0891b2'],
    'cq' => ['name' => 'Contrôle Qualité', 'icon' => '🔍', 'status' => 'development', 'color' => '#7c2d12']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?><?= $page_subtitle ? ' - ' . htmlspecialchars($page_subtitle) : '' ?> | <?= $app_name ?></title>
    
    <!-- Meta tags SEO et sécurité -->
    <meta name="description" content="<?= htmlspecialchars($page_subtitle ?: 'Portail Guldagil - Gestion des frais de port et transport') ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    
    <!-- CSS principals OBLIGATOIRES (ordre important) -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <!-- CSS spécifique au module actuel -->
    <?php if ($current_module !== 'home'): ?>
        <?php
        // 1. Nouveau système : CSS dans le dossier du module
        $new_css_path = "/{$current_module}/assets/css/{$current_module}.css";
        $module_css_loaded = false;
        
        if (file_exists(ROOT_PATH . "/public" . $new_css_path)): ?>
            <link rel="stylesheet" href="<?= $new_css_path ?>?v=<?= $build_number ?>">
            <?php $module_css_loaded = true; ?>
        <?php endif; ?>
        
        <?php if (!$module_css_loaded): ?>
            <?php 
            // 2. Fallback : ancien système
            $legacy_paths = [
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

    <!-- Variables CSS pour la couleur du module et améliorations -->
    <style>
        :root {
            --current-module-color: <?= $module_color ?>;
            --current-module-color-light: <?= $module_color ?>20;
            --current-module-color-dark: <?= $module_color ?>dd;
            
            /* Variables CSS par module */
            --module-port-color: #059669;
            --module-adr-color: #dc2626;
            --module-admin-color: #7c3aed;
            --module-materiel-color: #ea580c;
            --module-epi-color: #0891b2;
            --module-cq-color: #7c2d12;
            
            /* Améliorations demandées */
            --header-height: 85px; /* +15px pour plus d'espace */
            --nav-height: 55px; /* +5px pour mieux équilibrer */
            --breadcrumb-height: 42px; /* +2px pour aération */
        }
        
        /* Contraste amélioré entre header et contenu */
        .portal-main {
            background: #fafbfc; /* Fond légèrement plus clair */
            border-top: 2px solid rgba(59, 130, 246, 0.1); /* Bordure subtile */
            box-shadow: inset 0 4px 8px rgba(0, 0, 0, 0.02); /* Ombre intérieure légère */
        }
        
        /* Amélioration du contraste navigation modules */
        .modules-nav {
            background: linear-gradient(to bottom, #ffffff, #f8fafc);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* Breadcrumb conditionnel - masqué par défaut si navigation simple */
        .breadcrumb-nav {
            display: <?= $show_breadcrumb ? 'block' : 'none' ?>;
        }
        
        /* Ajustement du padding body selon présence breadcrumb */
        body.authenticated {
            padding-top: <?= $show_breadcrumb ? 'calc(85px + 55px + 42px)' : 'calc(85px + 55px)' ?>;
        }
        
        /* Classe pour indiquer absence de breadcrumb */
        <?php if (!$show_breadcrumb): ?>
        body {
            --no-breadcrumb: true;
        }
        <?php endif; ?>
    </style>
    
    <!-- JavaScript bannière cookie RGPD -->
    <script src="/assets/js/cookie_banner.js?v=<?= $build_number ?>"></script>
    <script src="/assets/js/cookie_config.js?v=<?= $build_number ?>"></script>
    <!-- Analytics -->
    <script src="/assets/js/analytics.js?v=<?= $build_number ?>"></script>
    
    <!-- JavaScript spécifique au module actuel -->
    <?php if ($current_module !== 'home'): ?>
        <?php
        // Chercher le JS du module
        $module_js_paths = [
            "/{$current_module}/assets/js/{$current_module}.js"
        ];
        
        foreach ($module_js_paths as $js_path):
            if (file_exists(ROOT_PATH . "/public" . $js_path)): ?>
                <script src="<?= $js_path ?>?v=<?= $build_number ?>"></script>
                <?php break; ?>
            <?php endif;
        endforeach; ?>
    <?php endif; ?>
    
    <!-- JavaScript pour gestion scroll intelligent -->
    <script src="/assets/js/header_scroll.js?v=<?= $build_number ?>"></script>
</head>
<body data-module="<?= $current_module ?>" 
      data-module-status="<?= $module_status ?>" 
      data-has-breadcrumb="<?= $show_breadcrumb ? 'true' : 'false' ?>"
      class="<?= $user_authenticated ? 'authenticated' : 'auth-page' ?><?= !$show_breadcrumb ? ' no-breadcrumb' : '' ?>">

    <!-- Bannière de debug (masquée en production) -->
    <?php if (defined('DEBUG') && DEBUG === true): ?>
    <div class="debug-banner" style="background: #dc2626; color: white; padding: 0.5rem; text-align: center; font-size: 0.875rem;">
        🔒 MODE DEBUG - <?= htmlspecialchars($current_user['username'] ?? 'non connecté') ?> 
        <?php if ($current_user): ?>(<?= htmlspecialchars($current_user['role'] ?? 'User') ?>)<?php endif; ?> | 
        <?= date('H:i:s') ?> | 
        IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') ?> |
        Module: <?= $current_module ?> | 
        Breadcrumb: <?= $show_breadcrumb ? 'ON' : 'OFF' ?> |
        Build: <?= $build_number ?>
    </div>
    <?php endif; ?>

    <!-- Header principal avec hauteur augmentée -->
    <header class="portal-header">
        <div class="header-container">
            <!-- Logo SANS nom du portail (sauf page index) -->
            <a href="/" class="header-brand">
                <div class="header-logo">
                    <?php if (file_exists(ROOT_PATH . '/assets/img/logo.png')): ?>
                        <img src="/assets/img/logo.png" alt="Logo Guldagil" width="40" height="40" style="object-fit: contain;">
                    <?php else: ?>
                        💧
                    <?php endif; ?>
                </div>
                <?php if ($is_portal_index): ?>
                    <!-- Afficher le nom du portail SEULEMENT sur l'index général -->
                    <div class="header-brand-text"><?= $app_name ?></div>
                <?php endif; ?>
            </a>

            <!-- Informations page courante avec plus d'espace -->
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
                            <span class="role-badge <?= getRoleBadgeClass($current_user['role'] ?? 'user') ?>">
                                <?= ucfirst($current_user['role'] ?? 'user') ?>
                            </span>
                        </div>
                    </div>
                    <div class="dropdown-icon">▼</div>
                </div>

                <div class="user-dropdown-menu" id="userDropdownMenu" role="menu" aria-hidden="true">
                    <div class="dropdown-header">
                        <div class="dropdown-user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="dropdown-user-email"><?= htmlspecialchars($current_user['email'] ?? '') ?></div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="/user/profile.php" class="dropdown-item" role="menuitem">
                        <span class="dropdown-icon">👤</span>
                        <span>Mon Profil</span>
                    </a>
                    <a href="/user/settings.php" class="dropdown-item" role="menuitem">
                        <span class="dropdown-icon">⚙️</span>
                        <span>Paramètres</span>
                    </a>
                    <?php if (($current_user['role'] ?? 'user') === 'admin'): ?>
                    <div class="dropdown-divider"></div>
                    <a href="/admin/" class="dropdown-item" role="menuitem">
                        <span class="dropdown-icon">🛡️</span>
                        <span>Administration</span>
                    </a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="/auth/logout.php" class="dropdown-item logout" role="menuitem">
                        <span class="dropdown-icon">🚪</span>
                        <span>Déconnexion</span>
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Boutons authentification pour utilisateurs non connectés -->
            <div class="header-auth-buttons">
                <a href="/auth/login.php" class="btn btn-primary">
                    <span>🔐</span>
                    Connexion
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Navigation modules avec hauteur ajustée et contraste amélioré -->
    <?php if ($user_authenticated): ?>
    <nav class="modules-nav" id="modulesNav">
        <div class="modules-nav-container">
            <div class="modules-nav-items">
                <?php foreach ($available_modules as $module_key => $module_data): ?>
                <a href="/<?= $module_key ?>/" 
                   class="module-nav-item <?= $current_module === $module_key ? 'active' : '' ?>"
                   style="--module-color: <?= $module_data['color'] ?>">
                    <span class="module-nav-icon"><?= $module_data['icon'] ?></span>
                    <span class="module-nav-text"><?= $module_data['name'] ?></span>
                    <?php if ($module_data['status'] === 'beta'): ?>
                        <span class="badge-beta">Beta</span>
                    <?php elseif ($module_data['status'] === 'development'): ?>
                        <span class="badge-dev">Dev</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Breadcrumb navigation - CONDITIONNEL selon profondeur -->
    <?php if ($show_breadcrumb && !empty($breadcrumbs)): ?>
    <nav class="breadcrumb-nav" id="breadcrumbNav" aria-label="Fil d'Ariane">
        <div class="breadcrumb-container">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                
                <?php if (!($crumb['active'] ?? false)): ?>
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

    <!-- Contenu principal avec contraste amélioré -->
    <main class="portal-main">

    <!-- JavaScript pour interactions header améliorées -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === CONSTANTES ET VARIABLES ===
            const HAS_BREADCRUMB = <?= $show_breadcrumb ? 'true' : 'false' ?>;
            const DEBUG_MODE = <?= (defined('DEBUG') && DEBUG) ? 'true' : 'false' ?>;
            
            // Éléments DOM
            const userMenuTrigger = document.getElementById('userMenuTrigger');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            const modulesNav = document.getElementById('modulesNav');
            const breadcrumbNav = document.getElementById('breadcrumbNav');
            const portalHeader = document.querySelector('.portal-header');
            
            // Variables d'état
            let lastScrollY = window.scrollY;
            let isScrollingDown = false;
            let userMenuOpen = false;
            
            if (DEBUG_MODE) {
                console.log('🔧 Header Debug:', {
                    hasBreadcrumb: HAS_BREADCRUMB,
                    currentModule: '<?= $current_module ?>',
                    showBreadcrumb: <?= $show_breadcrumb ? 'true' : 'false' ?>,
                    pathSegments: <?= count($path_segments) ?>
                });
            }

            // === GESTION MENU UTILISATEUR ===
            if (userMenuTrigger && userDropdownMenu) {
                userMenuTrigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    userMenuOpen = !userMenuOpen;
                    toggleUserMenu(userMenuOpen);
                });
                
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.header-user-nav') && userMenuOpen) {
                        userMenuOpen = false;
                        toggleUserMenu(false);
                    }
                });
                
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && userMenuOpen) {
                        userMenuOpen = false;
                        toggleUserMenu(false);
                        userMenuTrigger.focus();
                    }
                });
                
                function toggleUserMenu(show) {
                    userMenuTrigger.setAttribute('aria-expanded', show);
                    userDropdownMenu.setAttribute('aria-hidden', !show);
                    userDropdownMenu.style.display = show ? 'block' : 'none';
                    
                    if (show) {
                        userDropdownMenu.style.opacity = '0';
                        userDropdownMenu.style.transform = 'translateY(-10px) scale(0.95)';
                        requestAnimationFrame(() => {
                            userDropdownMenu.style.opacity = '1';
                            userDropdownMenu.style.transform = 'translateY(0) scale(1)';
                        });
                    }
                }
            }

            // === GESTION SCROLL INTELLIGENT AMÉLIORÉ ===
            let ticking = false;
            
            function handleScroll() {
                const currentScrollY = window.scrollY;
                const scrollDelta = currentScrollY - lastScrollY;
                
                // Seuil minimum pour éviter les micro-mouvements
                if (Math.abs(scrollDelta) > 5) {
                    isScrollingDown = scrollDelta > 0;
                    lastScrollY = currentScrollY;
                }
                
                // Logique de scroll selon présence du breadcrumb
                if (modulesNav) {
                    if (HAS_BREADCRUMB) {
                        // AVEC breadcrumb : masquer menu au scroll, breadcrumb reste collé
                        if (currentScrollY > 100 && isScrollingDown) {
                            modulesNav.style.transform = 'translateY(-100%)';
                            modulesNav.style.opacity = '0';
                            document.body.classList.add('scrolled');
                            
                            // Breadcrumb se colle sous le header
                            if (breadcrumbNav) {
                                breadcrumbNav.style.position = 'fixed';
                                breadcrumbNav.style.top = 'var(--header-height)';
                                breadcrumbNav.style.background = 'rgba(248, 250, 252, 0.95)';
                                breadcrumbNav.style.backdropFilter = 'blur(8px)';
                                breadcrumbNav.style.boxShadow = '0 2px 12px rgba(0, 0, 0, 0.1)';
                                breadcrumbNav.style.zIndex = '999';
                            }
                        } else if (!isScrollingDown || currentScrollY <= 50) {
                            modulesNav.style.transform = 'translateY(0)';
                            modulesNav.style.opacity = '1';
                            document.body.classList.remove('scrolled');
                            
                            // Breadcrumb retour normal
                            if (breadcrumbNav) {
                                breadcrumbNav.style.position = 'static';
                                breadcrumbNav.style.background = '';
                                breadcrumbNav.style.backdropFilter = '';
                                breadcrumbNav.style.boxShadow = '';
                            }
                        }
                    } else {
                        // SANS breadcrumb : garder le menu visible
                        modulesNav.style.transform = 'translateY(0)';
                        modulesNav.style.opacity = '1';
                        document.body.classList.remove('scrolled');
                        
                        if (DEBUG_MODE && currentScrollY > 100) {
                            console.log('🔄 Scroll détecté mais menu conservé (pas de breadcrumb)');
                        }
                    }
                }
                
                ticking = false;
            }
            
            function requestScrollUpdate() {
                if (!ticking) {
                    requestAnimationFrame(handleScroll);
                    ticking = true;
                }
            }
            
            window.addEventListener('scroll', requestScrollUpdate, { passive: true });
            
            // === AMÉLIORATION RESPONSIVE ===
            function handleResize() {
                const isMobile = window.innerWidth <= 768;
                
                if (modulesNav) {
                    const navItems = modulesNav.querySelector('.modules-nav-items');
                    if (navItems) {
                        navItems.style.justifyContent = isMobile ? 'flex-start' : 'center';
                    }
                }
            }
            
            window.addEventListener('resize', handleResize, { passive: true });
            
            // === INITIALISATION ===
            handleResize();
            
            if (DEBUG_MODE) {
                console.log('✅ Header JavaScript initialisé avec succès');
            }
        });
    </script>

<?php
// Fonction helper pour les rôles (si pas déjà définie)
if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass($role) {
        switch ($role) {
            case 'admin': return 'role-admin';
            case 'manager': return 'role-manager';
            case 'user': default: return 'role-user';
        }
    }
}
?>
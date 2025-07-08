<?php
/**
 * Titre: Header principal du portail Guldagil - Version 0.5 beta + build auto
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Variables par défaut avec fallbacks sécurisés
$page_title         = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle      = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
$page_description   = htmlspecialchars($page_description ?? 'Portail de gestion - Solutions transport et ADR');
$current_module     = htmlspecialchars($current_module ?? 'home');
$user_authenticated = $user_authenticated ?? false;
$current_user       = $current_user ?? null;
$app_version        = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number       = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';

// Fil d'Ariane par défaut
$breadcrumbs = $breadcrumbs ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// Configuration CSS et JS modulaire
$module_css = $module_css ?? false;
$module_js = $module_js ?? false;
$nav_info = $nav_info ?? '';

// Génération du titre complet
$full_title = $page_title . ' - Guldagil v' . $app_version;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $page_description ?>">
    <meta name="author" content="Jean-Thomas RUNSER">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#3182ce">
    
    <title><?= $full_title ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

    <!-- CSS principal - Version cachée avec build -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    
    <!-- CSS spécifique au module -->
    <?php if ($module_css && file_exists(ROOT_PATH . "/assets/css/modules/{$current_module}.css")): ?>
        <link rel="stylesheet" href="/assets/css/modules/<?= $current_module ?>.css?v=<?= $build_number ?>">
    <?php endif; ?>

    <!-- CSS critique intégré pour performance -->
    <style>
        /* Variables CSS - Thème bleu (secteur traitement de l'eau) */
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --primary-blue-light: #63b3ed;
            --secondary-blue: #4299e1;
            --accent-blue: #3182ce;
            
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            --font-mono: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }

        /* Reset et base */
        * { box-sizing: border-box; }
        
        body {
            margin: 0;
            padding: 0;
            font-family: var(--font-family);
            background: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
        }

        /* Header principal */
        .portal-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            color: white;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--spacing-md);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--spacing-lg);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .header-brand:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .portal-logo {
            height: 48px;
            width: auto;
            border-radius: var(--radius-md);
        }

        .brand-info h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .brand-info p {
            margin: 0;
            font-size: 0.875rem;
            opacity: 0.9;
        }

        /* Section utilisateur */
        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .user-dropdown-container {
            position: relative;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: var(--transition-fast);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-section:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .user-dropdown {
            transition: var(--transition-fast);
        }

        .user-dropdown.open {
            transform: rotate(180deg);
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + var(--spacing-sm));
            right: 0;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--gray-200);
            min-width: 200px;
            overflow: hidden;
            z-index: 1001;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md);
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .dropdown-item:hover {
            background: var(--gray-50);
            color: var(--primary-blue);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--gray-200);
            margin: var(--spacing-sm) 0;
        }

        /* Navigation / Breadcrumbs */
        .portal-nav {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: var(--spacing-md) 0;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .breadcrumb-item {
            color: var(--gray-600);
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .breadcrumb-item:hover {
            color: var(--primary-blue);
        }

        .breadcrumb-item.active {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .breadcrumb-separator {
            color: var(--gray-400);
            margin: 0 var(--spacing-sm);
        }

        .nav-info {
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        /* Messages flash */
        .flash-messages {
            position: fixed;
            top: 80px;
            right: var(--spacing-md);
            z-index: 1002;
            max-width: 400px;
        }

        .flash-message {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-sm);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
        }

        .flash-success { background: var(--success); color: white; }
        .flash-error { background: var(--error); color: white; }
        .flash-warning { background: var(--warning); color: white; }
        .flash-info { background: var(--info); color: white; }

        .flash-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.25rem;
            margin-left: auto;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Main content */
        .portal-main {
            min-height: calc(100vh - 140px);
            padding: var(--spacing-lg) 0;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
        }

        /* Couleurs modules */
        .module-blue { color: var(--primary-blue); }
        .module-green { color: var(--success); }
        .module-orange { color: var(--warning); }
        .module-red { color: var(--error); }
        .module-purple { color: #8b5cf6; }
        .module-gray { color: var(--gray-600); }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: var(--spacing-md);
            }
            
            .nav-container {
                flex-direction: column;
                gap: var(--spacing-sm);
                align-items: flex-start;
            }
            
            .flash-messages {
                left: var(--spacing-md);
                right: var(--spacing-md);
                max-width: none;
            }
        }

        /* Performance - Réduction des repaints */
        .header-brand, .user-section, .dropdown-item {
            will-change: transform;
        }
    </style>
</head>
<body class="portal-body module-<?= $current_module ?>" data-module="<?= $current_module ?>" data-version="<?= $app_version ?>">

    <!-- Header principal -->
    <header class="portal-header" role="banner">
        <div class="header-container">
            <!-- Logo + titre -->
            <div class="header-brand" 
                 onclick="goHome()" 
                 role="button" 
                 tabindex="0" 
                 aria-label="Retour à l'accueil"
                 onkeydown="if(event.key==='Enter'||event.key===' ')goHome()">
                
                <?php if (file_exists(ROOT_PATH . '/assets/img/logo.png')): ?>
                    <img src="/assets/img/logo.png" 
                         alt="Logo Guldagil" 
                         class="portal-logo"
                         loading="eager">
                <?php else: ?>
                    <div class="portal-logo-fallback" style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-weight:bold;">G</div>
                <?php endif; ?>
                
                <div class="brand-info">
                    <h1 class="portal-title"><?= $page_title ?></h1>
                    <p class="portal-subtitle"><?= $page_subtitle ?></p>
                </div>
            </div>

            <!-- Section utilisateur -->
            <div class="header-actions">
                <div class="user-dropdown-container">
                    <div class="user-section" 
                         role="button" 
                         tabindex="0" 
                         aria-label="Menu utilisateur"
                         aria-expanded="false"
                         onkeydown="if(event.key==='Enter'||event.key===' ')toggleUserMenu()">
                        
                        <?php if ($user_authenticated && $current_user): ?>
                            <span class="user-icon" aria-hidden="true">👤</span>
                            <span class="user-text"><?= htmlspecialchars($current_user['username'] ?? $current_user['name'] ?? 'Utilisateur') ?></span>
                            <span class="user-role">(<?= htmlspecialchars($current_user['role'] ?? 'user') ?>)</span>
                            <span class="user-dropdown" aria-hidden="true">▼</span>
                        <?php else: ?>
                            <span class="user-icon" aria-hidden="true">👤</span>
                            <span class="user-text">Connexion</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($user_authenticated && $current_user): ?>
                        <div class="user-dropdown-menu" 
                             id="user-menu" 
                             style="display: none;"
                             role="menu"
                             aria-label="Options utilisateur">
                            
                            <a href="/profile" class="dropdown-item" role="menuitem">
                                <span class="item-icon" aria-hidden="true">👤</span>
                                <span class="item-text">Mon profil</span>
                            </a>
                            
                            <a href="/settings" class="dropdown-item" role="menuitem">
                                <span class="item-icon" aria-hidden="true">⚙️</span>
                                <span class="item-text">Paramètres</span>
                            </a>
                            
                            <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                                <div class="dropdown-divider" role="separator"></div>
                                <a href="/admin" class="dropdown-item" role="menuitem">
                                    <span class="item-icon" aria-hidden="true">🛠️</span>
                                    <span class="item-text">Administration</span>
                                </a>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider" role="separator"></div>
                            <a href="/auth/logout.php" class="dropdown-item logout" role="menuitem">
                                <span class="item-icon" aria-hidden="true">🚪</span>
                                <span class="item-text">Déconnexion</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation / Breadcrumbs -->
    <nav class="portal-nav" role="navigation" aria-label="Navigation principale">
        <div class="nav-container">
            <nav class="nav-breadcrumb" role="breadcrumb" aria-label="Fil d'Ariane">
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <?php if ($i > 0): ?>
                        <span class="breadcrumb-separator" aria-hidden="true">›</span>
                    <?php endif; ?>
                    
                    <?php if (!empty($crumb['active'])): ?>
                        <span class="breadcrumb-item active" aria-current="page">
                            <?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item">
                            <?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            
            <?php if (!empty($nav_info)): ?>
                <div class="nav-info">
                    <span class="nav-text"><?= htmlspecialchars($nav_info) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Messages flash -->
    <?php if (!empty($_SESSION['flash_messages'])): ?>
        <div class="flash-messages" role="alert" aria-live="polite">
            <?php foreach ($_SESSION['flash_messages'] as $type => $msgs): ?>
                <?php foreach ($msgs as $msg): ?>
                    <div class="flash-message flash-<?= htmlspecialchars($type) ?>">
                        <span class="flash-icon" aria-hidden="true">
                            <?= $type === 'success' ? '✅' : ($type === 'error' ? '❌' : ($type === 'warning' ? '⚠️' : 'ℹ️')) ?>
                        </span>
                        <span class="flash-text"><?= htmlspecialchars($msg) ?></span>
                        <button class="flash-close" 
                                onclick="this.parentElement.remove()" 
                                aria-label="Fermer ce message">×</button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash_messages']); ?>
        </div>
    <?php endif; ?>

    <!-- Contenu principal -->
    <main class="portal-main" role="main">
        <div class="main-container">

    <!-- JavaScript intégré critique -->
    <script>
        // Fonctions globales pour le header
        
        // Navigation accueil
        function goHome() {
            if (window.location.pathname !== '/' && window.location.pathname !== '/index.php') {
                window.location.href = '/';
            }
        }

        // Gestion du menu utilisateur
        function toggleUserMenu() {
            const userSection = document.querySelector('.user-section');
            const userMenu = document.getElementById('user-menu');
            const dropdownArrow = userSection?.querySelector('.user-dropdown');
            
            if (userMenu) {
                const isOpen = userMenu.style.display === 'block';
                userMenu.style.display = isOpen ? 'none' : 'block';
                userSection?.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                dropdownArrow?.classList.toggle('open', !isOpen);
            }
        }

        // Initialisation DOM
        document.addEventListener('DOMContentLoaded', function() {
            const userSection = document.querySelector('.user-section');
            const userMenu = document.getElementById('user-menu');
            
            // Gestion clic menu utilisateur
            if (userSection && userMenu) {
                userSection.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleUserMenu();
                });
                
                // Fermeture clic extérieur
                document.addEventListener('click', function() {
                    userMenu.style.display = 'none';
                    userSection.setAttribute('aria-expanded', 'false');
                    userSection.querySelector('.user-dropdown')?.classList.remove('open');
                });
            }

            // Auto-fermeture messages flash après 5s
            document.querySelectorAll('.flash-message').forEach(function(msg) {
                setTimeout(function() {
                    if (msg.parentElement) {
                        msg.style.animation = 'slideOut 0.3s ease';
                        setTimeout(() => msg.remove(), 300);
                    }
                }, 5000);
            });

            // Performance: Préchargement CSS modules
            <?php if ($module_css && file_exists(ROOT_PATH . "/assets/css/modules/{$current_module}.css")): ?>
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = '/assets/css/modules/<?= $current_module ?>.css?v=<?= $build_number ?>';
                document.head.appendChild(link);
            <?php endif; ?>
        });

        // Animation sortie pour les flash messages
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>

    <!-- JS spécifique au module -->
    <?php if ($module_js && file_exists(ROOT_PATH . "/assets/js/modules/{$current_module}.js")): ?>
        <script defer src="/assets/js/modules/<?= $current_module ?>.js?v=<?= $build_number ?>"></script>
    <?php endif; ?>

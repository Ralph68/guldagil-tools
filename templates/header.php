<?php
/**
 * Titre: Header professionnel du portail Guldagil - Version améliorée
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
$app_name           = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author         = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

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

// Déterminer l'icône du module pour le header
$module_icon = match($current_module) {
    'calculateur' => '🚛',
    'adr' => '⚠️',
    'admin' => '⚙️',
    'qualite' => '✅',
    'maintenance' => '🔧',
    'stats' => '📊',
    default => '🏠'
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $page_description ?>">
    <meta name="author" content="<?= $app_author ?>">
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
        /* Variables CSS */
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --primary-blue-light: #63b3ed;
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
            --white: #ffffff;
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
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
        }
        
        /* Reset et base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--gray-900);
            background: var(--gray-50);
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
            padding: var(--spacing-lg) var(--spacing-md);
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            align-items: center;
            gap: var(--spacing-lg);
        }
        
        /* Logo et branding */
        .header-brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            text-decoration: none;
            color: white;
            transition: var(--transition-normal);
        }
        
        .header-brand:hover {
            transform: translateY(-1px);
            color: var(--primary-blue-light);
        }
        
        .header-logo {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow-md);
        }
        
        .header-brand-text {
            display: flex;
            flex-direction: column;
        }
        
        .header-brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .header-brand-tagline {
            font-size: 0.75rem;
            opacity: 0.9;
            font-weight: 400;
        }
        
        /* Titre de page dans header */
        .header-page-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            min-width: 0;
        }
        
        .page-module-icon {
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-md);
            padding: var(--spacing-sm);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
        }
        
        .page-title-info {
            min-width: 0;
        }
        
        .page-main-title {
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 0.125rem;
        }
        
        .page-subtitle-text {
            font-size: 0.875rem;
            opacity: 0.85;
            font-weight: 400;
        }
        
        /* Navigation utilisateur */
        .header-user-nav {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: rgba(255, 255, 255, 0.1);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        .user-role {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        /* Actions header */
        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .header-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        
        .header-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-1px);
        }
        
        .version-badge {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.025em;
        }
        
        /* Responsive header */
        @media (max-width: 1024px) {
            .header-container {
                grid-template-columns: auto 1fr auto;
                gap: var(--spacing-md);
            }
            
            .header-page-info {
                order: 2;
                grid-column: 1 / -1;
                justify-self: center;
                margin-top: var(--spacing-sm);
                padding-top: var(--spacing-sm);
                border-top: 1px solid rgba(255, 255, 255, 0.2);
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: var(--spacing-md);
            }
            
            .header-brand,
            .header-page-info {
                justify-self: center;
            }
            
            .header-user-nav,
            .header-actions {
                justify-self: center;
            }
            
            .page-main-title {
                font-size: 1.25rem;
            }
            
            .user-details {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header-container {
                padding: var(--spacing-md);
            }
            
            .header-brand-text {
                display: none;
            }
            
            .page-subtitle-text {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header principal -->
    <header class="portal-header">
        <div class="header-container">
            <!-- Logo et branding -->
            <a href="/" class="header-brand">
                <div class="header-logo">
                    <?php if (file_exists(ROOT_PATH . '/assets/img/logo.svg')): ?>
                        <img src="/assets/img/logo.svg" alt="Guldagil" style="width: 32px; height: 32px;">
                    <?php else: ?>
                        🌊
                    <?php endif; ?>
                </div>
                <div class="header-brand-text">
                    <div class="header-brand-name">Guldagil</div>
                    <div class="header-brand-tagline">Solutions pros</div>
                </div>
            </a>
            
            <!-- Titre de la page actuelle -->
            <div class="header-page-info">
                <div class="page-module-icon">
                    <?= $module_icon ?>
                </div>
                <div class="page-title-info">
                    <h1 class="page-main-title"><?= $page_title ?></h1>
                    <?php if ($page_subtitle): ?>
                        <div class="page-subtitle-text"><?= $page_subtitle ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informations utilisateur -->
            <?php if ($user_authenticated && $current_user): ?>
            <div class="header-user-nav">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="user-role"><?= htmlspecialchars($current_user['role'] ?? 'user') ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Actions et version -->
            <div class="header-actions">
                <?php if ($current_module !== 'home'): ?>
                    <a href="/" class="header-btn">
                        <span>🏠</span>
                        Accueil
                    </a>
                <?php endif; ?>
                
                <?php if ($user_authenticated && ($current_user['role'] ?? 'user') === 'admin'): ?>
                    <a href="/admin/" class="header-btn">
                        <span>⚙️</span>
                        Admin
                    </a>
                <?php endif; ?>
                
                <div class="version-badge">
                    v<?= $app_version ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Fil d'Ariane (si nécessaire) -->
    <?php if (count($breadcrumbs) > 1): ?>
    <nav class="breadcrumb-nav">
        <div class="breadcrumb-container">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                
                <?php if ($crumb['active']): ?>
                    <span class="breadcrumb-current">
                        <span class="breadcrumb-icon"><?= $crumb['icon'] ?></span>
                        <?= htmlspecialchars($crumb['text']) ?>
                    </span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-link">
                        <span class="breadcrumb-icon"><?= $crumb['icon'] ?></span>
                        <?= htmlspecialchars($crumb['text']) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    
    <style>
        .breadcrumb-nav {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: var(--spacing-sm) 0;
        }
        
        .breadcrumb-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: 0.875rem;
        }
        
        .breadcrumb-link {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--gray-600);
            text-decoration: none;
            transition: var(--transition-fast);
        }
        
        .breadcrumb-link:hover {
            color: var(--primary-blue);
        }
        
        .breadcrumb-current {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--gray-900);
            font-weight: 500;
        }
        
        .breadcrumb-separator {
            color: var(--gray-400);
            font-weight: 300;
        }
        
        .breadcrumb-icon {
            font-size: 0.875rem;
        }
    </style>
    <?php endif; ?>

    <!-- Container principal -->
    <main class="portal-main">
        <div class="main-container">

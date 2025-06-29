<?php
/**
 * Titre: Header modulaire du portail Guldagil
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Vérifier si les variables nécessaires sont définies
$page_title = $page_title ?? 'Portail Guldagil';
$page_subtitle = $page_subtitle ?? 'Portail d\'outils professionnels';
$current_module = $current_module ?? 'home';
$show_admin = $show_admin ?? false;
$user_authenticated = $user_authenticated ?? false;

// Informations de version
$version_info = function_exists('getVersionInfo') ? getVersionInfo() : [
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5 beta'
];

// Chemins de navigation
$breadcrumbs = $breadcrumbs ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description ?? 'Portail d\'outils Guldagil - Solutions professionnelles') ?>">
    <meta name="author" content="Jean-Thomas RUNSER">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $version_info['version'] ?>">
    
    <!-- CSS spécifique au module -->
    <?php if (isset($module_css) && $module_css): ?>
    <link rel="stylesheet" href="/assets/css/modules/<?= $current_module ?>.css?v=<?= $version_info['version'] ?>">
    <?php endif; ?>
    
    <!-- Meta pour cache busting -->
    <meta name="build-version" content="<?= $version_info['version'] ?>">
    
    <!-- Variables CSS personnalisées pour le module -->
    <?php if (isset($module_colors)): ?>
    <style>
        :root {
            --module-primary: <?= $module_colors['primary'] ?? 'var(--primary-blue)' ?>;
            --module-secondary: <?= $module_colors['secondary'] ?? 'var(--gray-500)' ?>;
            --module-accent: <?= $module_colors['accent'] ?? 'var(--primary-blue-light)' ?>;
        }
    </style>
    <?php endif; ?>
</head>
<body class="portal-body module-<?= $current_module ?>" data-module="<?= $current_module ?>">
    
    <!-- Header principal -->
    <header class="portal-header">
        <div class="header-container">
            <!-- Brand / Logo -->
            <div class="header-brand" onclick="goHome()" role="button" tabindex="0" aria-label="Retour à l'accueil">
                <img src="public/assets/img/logo.png" alt="Guldagil" class="portal-logo">
                <div class="brand-info">
                    <h1 class="portal-title"><?= htmlspecialchars($page_title) ?></h1>
                    <p class="portal-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
                </div>
            </div>
            
            <!-- Actions header -->
            <div class="header-actions">
                <!-- Badge version -->
                <div class="version-badge" title="Version du portail">
                    <span class="version-text"><?= $version_info['version'] ?></span>
                </div>
                
                <!-- Zone utilisateur -->
                <div class="user-section">
    <?php if (isset($current_user) && $current_user): ?>
        <span class="user-icon">👤</span>
        <span class="user-text"><?= htmlspecialchars($current_user['username']) ?></span>
        <span class="user-role">(<?= $current_user['role'] ?>)</span>
        <span class="user-dropdown">▼</span>
    <?php else: ?>
        <span class="user-icon">👤</span>
        <span class="user-text">Connexion</span>
    <?php endif; ?>
</div>
                
                <!-- Menu utilisateur (caché par défaut) -->
<?php if (isset($current_user) && $current_user): ?>
<div class="user-dropdown-menu" id="user-menu" style="display: none;">
    <a href="/profile" class="dropdown-item">
        <span class="item-icon">👤</span>
        <span class="item-text">Mon profil</span>
    </a>
    <a href="/settings" class="dropdown-item">
        <span class="item-icon">⚙️</span>
        <span class="item-text">Paramètres</span>
    </a>
    <div class="dropdown-divider"></div>
    <a href="/logout.php" class="dropdown-item logout">
        <span class="item-icon">🚪</span>
        <span class="item-text">Déconnexion</span>
    </a>
</div>
<?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Navigation / Breadcrumbs -->
    <nav class="portal-nav" role="navigation" aria-label="Navigation principale">
        <div class="nav-container">
            <!-- Fil d'Ariane -->
            <div class="nav-breadcrumb" role="breadcrumb" aria-label="Fil d'Ariane">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($index > 0): ?>
                        <span class="breadcrumb-separator" aria-hidden="true">›</span>
                    <?php endif; ?>
                    
                    <?php if ($crumb['active'] ?? false): ?>
                        <span class="breadcrumb-item active" aria-current="page">
                            <?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item">
                            <?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Informations contextuelles -->
            <div class="nav-info">
                <?php if (isset($nav_info)): ?>
                    <span class="nav-text"><?= htmlspecialchars($nav_info) ?></span>
                <?php elseif ($current_module === 'home'): ?>
                    <span class="nav-text"><?= count($modules ?? []) ?> modules disponibles</span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Début du contenu principal -->
    <main class="portal-main" role="main">
        
        <!-- Messages flash / alertes -->
        <?php if (isset($_SESSION['flash_messages'])): ?>
        <div class="flash-messages">
            <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                <div class="flash-message flash-<?= $type ?>" role="alert">
                    <span class="flash-icon">
                        <?= $type === 'success' ? '✅' : ($type === 'error' ? '❌' : ($type === 'warning' ? '⚠️' : 'ℹ️')) ?>
                    </span>
                    <span class="flash-text"><?= htmlspecialchars($message) ?></span>
                    <button class="flash-close" onclick="this.parentElement.remove()" aria-label="Fermer">×</button>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?php 
        // Nettoyer les messages après affichage
        unset($_SESSION['flash_messages']); 
        ?>
        <?php endif; ?>

        <!-- Container du contenu -->
        <div class="main-container">
            <!-- Le contenu spécifique à chaque page sera inséré ici -->

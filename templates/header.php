<?php
/**
 * Titre: Header principal du portail - Version nettoyée
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
$all_modules = $all_modules ?? []; // TODO: Remplacer par une inclusion depuis config/modules.php
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
    <link rel="stylesheet" href="/assets/css/cookie_banner.css?v=<?= $build_number ?>">

    <!-- CSS modulaire - Architecture standardisée -->
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

    <!-- Header principal -->
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

            <!-- Navigation module ou titre page -->
            <div class="header-center">
                <?php if (!empty($navigation_modules)): ?>
                    <!-- Navigation horizontale -->
                    <nav class="header-nav" role="navigation" aria-label="Navigation principale">
                        <?php foreach ($navigation_modules as $mod_key => $module): ?>
                            <?php if ($module['status'] !== 'disabled'): ?>
                                <a href="/<?= $mod_key ?>/" 
                                   class="nav-item <?= $mod_key === $current_module ? 'active' : '' ?>"
                                   title="<?= htmlspecialchars($module['description'] ?? $module['name']) ?>"
                                   <?php if ($mod_key === $current_module): ?>aria-current="page"<?php endif; ?>>
                                    <span class="nav-icon"><?= $module['icon'] ?></span>
                                    <span class="nav-text"><?= htmlspecialchars($module['name']) ?></span>
                                    <?php if ($module['status'] === 'beta'): ?>
                                        <span class="badge-beta">β</span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                <?php else: ?>
                    <!-- Titre de la page actuelle -->
                    <div class="header-page-info">
                        <h1 class="page-title">
                            <span class="module-icon"><?= $module_icon ?></span>
                            <?= htmlspecialchars($page_title) ?>
                        </h1>
                        <?php if (!empty($page_subtitle)): ?>
                            <p class="page-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Zone utilisateur -->
            <div class="header-user">
                <?php if ($user_authenticated): ?>
                    <!-- Utilisateur connecté -->
                    <div class="user-info" role="banner">
                        <div class="user-menu">
                            <button class="user-button" aria-expanded="false" aria-haspopup="true">
                                <div class="user-avatar">
                                    <?php if (!empty($current_user['avatar'])): ?>
                                        <img src="<?= htmlspecialchars($current_user['avatar']) ?>" alt="Avatar">
                                    <?php else: ?>
                                        <span class="avatar-initials"><?= strtoupper(substr($current_user['username'] ?? 'U', 0, 2)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <span class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></span>
                                    <span class="user-role"><?= htmlspecialchars(ucfirst($current_user['role'] ?? 'user')) ?></span>
                                </div>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            
                            <div class="user-dropdown" role="menu">
                                <a href="/user/profile.php" class="dropdown-item" role="menuitem">
                                    <span class="item-icon">👤</span>
                                    Mon profil
                                </a>
                                <a href="/user/settings.php" class="dropdown-item" role="menuitem">
                                    <span class="item-icon">⚙️</span>
                                    Paramètres
                                </a>
                                <?php if (in_array($current_user['role'] ?? '', ['admin', 'dev'])): ?>
                                    <hr class="dropdown-divider">
                                    <a href="/admin/" class="dropdown-item" role="menuitem">
                                        <span class="item-icon">🔧</span>
                                        Administration
                                    </a>
                                <?php endif; ?>
                                <hr class="dropdown-divider">
                                <a href="/auth/logout.php" class="dropdown-item logout" role="menuitem">
                                    <span class="item-icon">🚪</span>
                                    Se déconnecter
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Utilisateur non connecté -->
                    <div class="auth-actions">
                        <a href="/auth/login.php" class="btn-login">Se connecter</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Zone de contenu principal -->
    <div class="page-wrapper">
        <?php 
        // Affichage des messages flash s'ils existent
        if (isset($_SESSION['flash_messages'])): 
            foreach ($_SESSION['flash_messages'] as $type => $messages):
                foreach ($messages as $message): ?>
                    <div class="alert alert-<?= $type ?>" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="alert-close" aria-label="Fermer">&times;</button>
                    </div>
                <?php endforeach;
            endforeach;
            unset($_SESSION['flash_messages']);
        endif; ?>

    <!-- JavaScript RGPD et global -->
    <script src="/assets/js/cookie_banner.js?v=<?= $build_number ?>"></script>
    <script src="/assets/js/cookie_config.js?v=<?= $build_number ?>"></script>
    <script src="/assets/js/analytics.js?v=<?= $build_number ?>"></script>

    <!-- JavaScript modulaire -->
    <?php if ($module_js && $current_module !== 'home'): ?>
        <?php 
        $module_js_path = "/{$current_module}/assets/js/{$current_module}.js";
        $module_js_file = ROOT_PATH . "/public{$module_js_path}";
        
        if (file_exists($module_js_file)): ?>
            <script src="<?= $module_js_path ?>?v=<?= $build_number ?>"></script>
        <?php endif; ?>
    <?php endif; ?>

    <!-- JavaScript header interactif -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Menu utilisateur dropdown
        const userButton = document.querySelector('.user-button');
        const userDropdown = document.querySelector('.user-dropdown');
        
        if (userButton && userDropdown) {
            userButton.addEventListener('click', function(e) {
                e.stopPropagation();
                const isExpanded = userButton.getAttribute('aria-expanded') === 'true';
                userButton.setAttribute('aria-expanded', !isExpanded);
                userDropdown.style.display = isExpanded ? 'none' : 'block';
            });
            
            document.addEventListener('click', function() {
                userButton.setAttribute('aria-expanded', 'false');
                userDropdown.style.display = 'none';
            });
        }
        
        // Fermeture des alertes
        document.querySelectorAll('.alert-close').forEach(function(closeBtn) {
            closeBtn.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });
    });
    </script>

<!-- TODO: Éléments à mettre à jour dans les modules -->
<?php
/**
 * TODO LIST:
 * 
 * 1. MODULES À VÉRIFIER:
 *    - Vérifier que tous les modules ont leur CSS dans /assets/css/
 *    - Créer les JS manquants dans /assets/js/ si nécessaire
 * 
 * 2. ASSETS MANQUANTS À CRÉER:
 *    - /public/auth/assets/css/auth.css (si n'existe pas)
 *    - /public/port/assets/css/port.css (si n'existe pas) 
 *    - Vérifier autres modules (adr, materiel, qualite)
 * 
 * 3. JAVASCRIPT À STANDARDISER:
 *    - Créer /public/admin/assets/js/admin.js
 *    - Créer /public/auth/assets/js/auth.js
 *    - Standardiser tous les JS modules
 * 
 * 4. CONFIGURATION À EXTERNALISER:
 *    - Créer config/modules.php avec définition des modules
 *    - Déplacer $all_modules vers un fichier de configuration centralisé
 * 
 * 5. FONCTIONS À MIGRER:
 *    - getNavigationModules() vers core/auth/ ou core/modules/
 *    - Fonctions helper vers core/helpers/
 * 
 * 6. OPTIMISATIONS:
 *    - Cache file_exists() dans une classe Assets
 *    - Minification CSS/JS automatique
 *    - Compression gzip assets
 * 
 * 7. SÉCURITÉ:
 *    - Validation des noms de modules
 *    - Protection contre path traversal
 *    - CSP headers pour assets
 */
?>
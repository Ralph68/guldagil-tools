<?php
/**
 * Titre: Header du portail Guldagil - Version modulaire CORRIGÉE
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

if (file_exists(ROOT_PATH . '/config/error_handler_simple.php')) {
    require_once ROOT_PATH . '/config/error_handler_simple.php';
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
    error_log("Erreur auth header: " . $e->getMessage());
    // Continue sans auth si erreur
}

// Variables avec fallbacks sécurisés
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
$page_description = htmlspecialchars($page_description ?? 'Portail de gestion');
$current_module = htmlspecialchars($current_module ?? 'home');
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// Fil d'Ariane par défaut
$breadcrumbs = $breadcrumbs ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// Configuration CSS et JS modulaire
$module_css = $module_css ?? false;
$module_js = $module_js ?? false;

// Titre complet
$full_title = $page_title . ' - Guldagil v' . $app_version;

// Icône du module
$module_icon = match($current_module) {
    'calculateur' => '🚛',
    'adr' => '⚠️',
    'admin' => '⚙️',
    'qualite' => '✅',
    'maintenance' => '🔧',
    'stats' => '📊',
    'user', 'profile' => '👤',
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

    <!-- CSS principal du portail -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    
    <!-- CSS Header et Footer globaux -->
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    
    <!-- CSS Composants globaux -->
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <!-- CSS spécifique au module -->
    <?php if ($module_css): ?>
        <?php 
        // Ordre de priorité pour trouver le CSS du module
        $module_css_paths = [
            "/{$current_module}/assets/css/{$current_module}.css",
            "/assets/css/modules/{$current_module}.css"
        ];
        
        foreach ($module_css_paths as $css_path) {
            if (file_exists(ROOT_PATH . $css_path)): ?>
                <link rel="stylesheet" href="<?= $css_path ?>?v=<?= $build_number ?>">
                <?php break; ?>
            <?php endif;
        } ?>
    <?php endif; ?>
</head>
<body>
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
                    <span class="module-icon"><?= $module_icon ?></span>
                    <?= $page_title ?>
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
                <a href="#" class="user-menu-trigger" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar">
                        <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="user-role"><?= ucfirst($current_user['role'] ?? 'user') ?></div>
                    </div>
                    <div class="dropdown-icon">▼</div>
                </a>

                <div class="user-dropdown" role="menu" aria-hidden="true">
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
                                <div class="dropdown-subtitle">Paramètres personnels</div>
                            </div>
                        </a>
                        <a href="/user/" class="dropdown-item">
                            <div class="dropdown-item-icon">🏠</div>
                            <div class="dropdown-item-text">
                                <div class="dropdown-title">Dashboard</div>
                                <div class="dropdown-subtitle">Vue d'ensemble</div>
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
            <div class="header-auth-actions">
                <a href="/auth/login.php" class="login-btn">
                    <span>🔐</span>
                    Connexion
                </a>
            </div>
            <?php endif; ?>
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
                
                <?php if (!empty($crumb['url']) && !($crumb['active'] ?? false)): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item">
                        <?= $crumb['icon'] ?> <?= htmlspecialchars($crumb['text']) ?>
                    </a>
                <?php else: ?>
                    <span class="breadcrumb-item active">
                        <?= $crumb['icon'] ?> <?= htmlspecialchars($crumb['text']) ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Contenu principal -->
    <main class="portal-main">

    <!-- JavaScript Header modulaire (chargé en fin de header) -->
    <script src="../templates/assets/js/header.js?v=<?= $build_number ?>"></script>
    
    <!-- JavaScript spécifique au module -->
    <?php if ($module_js): ?>
        <?php 
        // Ordre de priorité pour trouver le JS du module
        $module_js_paths = [
            "/{$current_module}/assets/js/{$current_module}.js",
            "/assets/js/modules/{$current_module}.js"
        ];
        
        foreach ($module_js_paths as $js_path) {
            if (file_exists(ROOT_PATH . $js_path)): ?>
                <script src="<?= $js_path ?>?v=<?= $build_number ?>"></script>
                <?php break; ?>
            <?php endif;
        } ?>
    <?php endif; ?>

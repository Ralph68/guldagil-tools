<?php
/**
 * Titre: Header Unifié du Portail Guldagil
 * Chemin: /templates/header.php
 * Version: 1.3 - Nettoyage final et centralisation
 */

// Protection et configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
require_once ROOT_PATH . '/config/roles.php';

// --- Initialisation et Authentification ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_authenticated = false;
$current_user = null;
if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
    require_once ROOT_PATH . '/core/auth/AuthManager.php';
    $auth = new AuthManager();
    if ($auth->isAuthenticated()) {
        $user_authenticated = true;
        $current_user = $auth->getCurrentUser();
    }
}

// Redirection si la page n'est pas publique et que l'utilisateur n'est pas connecté
$public_pages = ['/auth/login.php', '/auth/logout.php', '/error.php'];
$is_public_page = in_array($_SERVER['SCRIPT_NAME'], $public_pages);

if (!$is_public_page && !$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    exit;
}

// --- Variables de Page et Module ---
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
$current_module = $current_module ?? 'home';

$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd');

// Définition des modules (si non définis par la page appelante)
$all_modules = $all_modules ?? [
    'home' => ['name' => 'Accueil', 'icon' => '🏠', 'color' => '#0ea5e9'],
    'port' => ['name' => 'Frais de port', 'icon' => '📦', 'color' => '#06b6d4', 'alias' => 'calculateur'],
    'adr' => ['name' => 'ADR', 'icon' => '⚠️', 'color' => '#dc2626'],
    'qualite' => ['name' => 'Contrôle qualité', 'icon' => '🔬', 'color' => '#059669'],
    'materiel' => ['name' => 'Gestion du matériel', 'icon' => '🔨', 'color' => '#6b7280'],
    'epi' => ['name' => 'Équipements de protection', 'icon' => '🥽', 'color' => '#f59e0b'],
    'user' => ['name' => 'Mon compte', 'icon' => '👤', 'color' => '#3b82f6', 'alias' => 'profile'],
    'admin' => ['name' => 'Administration', 'icon' => '⚙️', 'color' => '#64748b', 'restricted' => ['admin', 'dev']],
];

// Détection et propriétés du module actuel
$module_icon = $all_modules[$current_module]['icon'] ?? '💧';
$module_color = $all_modules[$current_module]['color'] ?? '#3b82f6';
$module_name = $all_modules[$current_module]['name'] ?? 'Guldagil';

// Navigation et fil d'ariane
$navigation_modules = $user_authenticated ? getNavigationModules($current_user['role'], $all_modules) : [];

// Génération automatique du fil d'ariane pour toutes les pages sauf l'index principal
$is_home_page = ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php');
if (!isset($breadcrumbs) || empty($breadcrumbs)) {
    $breadcrumbs = [['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => $is_home_page]];
    
    if (!$is_home_page) {
        // Ajouter le module actuel
        if (isset($all_modules[$current_module]) && $current_module !== 'home') {
            $breadcrumbs[] = [
                'icon' => $all_modules[$current_module]['icon'] ?? '📁',
                'text' => $all_modules[$current_module]['name'] ?? ucfirst($current_module),
                'url' => '/' . $current_module . '/',
                'active' => ($page_title == $all_modules[$current_module]['name'])
            ];
        }
        
        // Ajouter la page courante si elle est différente du module
        if ($page_title != ($all_modules[$current_module]['name'] ?? '') && !$is_home_page) {
            $breadcrumbs[] = [
                'icon' => '', 
                'text' => $page_title,
                'url' => '', 
                'active' => true
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= $app_name ?></title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <?php if (($module_css ?? true) && $current_module !== 'home' && file_exists(ROOT_PATH . "/public/{$current_module}/assets/css/{$current_module}.css")): ?>
        <link rel="stylesheet" href="/<?= $current_module ?>/assets/css/<?= $current_module ?>.css?v=<?= $build_number ?>">
    <?php endif; ?>
    <style>:root { --current-module-color: <?= $module_color ?>; }</style>
</head>
<body data-module="<?= $current_module ?>" class="<?= $user_authenticated ? 'authenticated' : 'guest' ?><?= defined('DEBUG') && DEBUG ? ' has-debug' : '' ?>">

    <?php if (defined('DEBUG') && DEBUG === true): ?>
    <div class="debug-banner">MODE DEBUG ACTIVÉ</div>
    <?php endif; ?>

    <header class="portal-header" id="mainHeader">
        <div class="header-container">
            <a href="/" class="header-brand">
                <span class="brand-logo">💧</span>
                <span class="brand-text"><?= $app_name ?></span>
            </a>
            <div class="page-title-section">
                <h1 class="page-title"><?= $page_title ?></h1>
                <?php if (!empty($page_subtitle)): ?><p class="page-subtitle"><?= $page_subtitle ?></p><?php endif; ?>
            </div>
            <?php if ($user_authenticated && $current_user): ?>
            <div class="user-nav">
                <button class="user-trigger" id="userTrigger" aria-expanded="false">
                    <div class="user-avatar"><?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?></div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></span>
                        <span class="user-role"><?= ucfirst($current_user['role'] ?? 'user') ?></span>
                    </div>
                    <span class="dropdown-arrow">▼</span>
                </button>
                <div class="user-dropdown" id="userDropdown" aria-hidden="true">
                    <a href="/user/" class="dropdown-item">👤 Mon profil</a>
                    <a href="/user/settings.php" class="dropdown-item">⚙️ Paramètres</a>
                    <?php if (RoleManager::hasCapability($current_user['role'], 'view_admin')): ?>
                        <div class="dropdown-divider"></div>
                        <a href="/admin/" class="dropdown-item">🔧 Administration</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="/auth/logout.php" class="dropdown-item logout">🚪 Déconnexion</a>
                </div>
            </div>
            <?php else: ?>
            <a href="/auth/login.php" class="login-btn">🔑 Connexion</a>
            <?php endif; ?>
        </div>
        
        <!-- Élément caché qui apparaît au scroll -->
        <div class="scroll-header" id="scrollHeader">
            <div class="scroll-header-container">
                <div class="module-info">
                    <span class="module-icon"><?= $module_icon ?></span>
                    <span class="module-name"><?= $module_name ?></span>
                </div>
                <div class="current-page-title"><?= $page_title ?></div>
                <div class="scroll-placeholder"></div>
            </div>
        </div>
    </header>

    <?php if ($user_authenticated && !empty($navigation_modules)): ?>
    <nav class="modules-nav" id="mainNav">
        <div class="nav-container">
            <div class="nav-items">
                <?php foreach ($navigation_modules as $module_key => $module_data): ?>
                    <a href="/<?= $module_key ?>/" class="nav-item <?= $current_module === $module_key ? 'active' : '' ?>" style="--module-color: <?= $module_data['color'] ?? '#3b82f6' ?>">
                        <span class="nav-icon"><?= $module_data['icon'] ?? '📁' ?></span>
                        <span class="nav-text"><?= htmlspecialchars($module_data['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Menu principal"><span></span><span></span><span></span></button>
        </div>
    </nav>
    <?php endif; ?>

    <?php if (count($breadcrumbs) > 1): ?>
    <nav class="breadcrumb-nav" id="breadcrumbNav">
        <div class="breadcrumb-container">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0) echo '<span class="breadcrumb-separator">›</span>'; ?>
                <?php if (!empty($crumb['url']) && !($crumb['active'] ?? false)): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item"><?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?></a>
                <?php else: ?>
                    <span class="breadcrumb-item active"><?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php else: ?>
    <!-- Ajout d'une classe pour identifier l'absence de fil d'ariane -->
    <script>document.body.classList.add('no-breadcrumb');</script>
    <?php endif; ?>

    <!-- Ajouter un indicateur visuel pour aider à identifier la transition entre l'en-tête et le contenu -->
    <div class="content-separator"></div>

    <main class="main-content">
    <!-- Le contenu de la page commence ici -->

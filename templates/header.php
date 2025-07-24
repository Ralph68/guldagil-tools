<?php
/**
 * Titre: Header adaptatif du portail Guldagil - VERSION SIMPLIFIÉE
 * Chemin: /templates/header.php  
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// Chargement des configurations
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Chargement des fichiers config additionnels si disponibles
$additional_configs = [
    ROOT_PATH . '/config/roles.php',
    ROOT_PATH . '/config/functions.php',
    ROOT_PATH . '/config/modules.php'
];

foreach ($additional_configs as $config_file) {
    if (file_exists($config_file)) {
        require_once $config_file;
    }
}

// Démarrage session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Pages publiques
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
$user_authenticated = false;
$current_user = null;

if (!$is_public_page) {
    // AuthManager en priorité
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
    
    // Fallback session
    if (!$user_authenticated && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
        $user_authenticated = true;
        $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
    }
    
    // Redirection si pas authentifié
    if (!$user_authenticated) {
        header('Location: /auth/login.php');
        exit;
    }
}

// Variables par défaut
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? '');
$page_description = htmlspecialchars($page_description ?? 'Portail de gestion');
$current_module = htmlspecialchars($current_module ?? 'home');

$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '001';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// Configuration modules
$all_modules = [
    'home' => ['icon' => '🏠', 'color' => '#3182ce', 'status' => 'active', 'name' => 'Accueil', 'url' => '/'],
    'port' => ['icon' => '📦', 'color' => '#059669', 'status' => 'active', 'name' => 'Frais de port', 'url' => '/port/'],
    'adr' => ['icon' => '⚠️', 'color' => '#dc2626', 'status' => 'active', 'name' => 'ADR', 'url' => '/adr/'],
    'user' => ['icon' => '👤', 'color' => '#7c2d12', 'status' => 'active', 'name' => 'Mon compte', 'url' => '/user/'],
    'admin' => ['icon' => '⚙️', 'color' => '#1f2937', 'status' => 'active', 'name' => 'Administration', 'url' => '/admin/']
];

// Module actuel
$module_icon = $all_modules[$current_module]['icon'] ?? '🏠';
$module_color = $all_modules[$current_module]['color'] ?? '#3182ce';
$module_status = $all_modules[$current_module]['status'] ?? 'active';

// Variables par défaut
$breadcrumbs = $breadcrumbs ?? [];
$module_css = $module_css ?? false;
$module_js = $module_js ?? false;

// Navigation modules
$navigation_modules = [];
if ($user_authenticated && function_exists('getNavigationModules')) {
    $user_role = $current_user['role'] ?? 'user';
    $navigation_modules = getNavigationModules($user_role, $all_modules);
} elseif ($user_authenticated) {
    // Fallback simple
    foreach ($all_modules as $key => $module) {
        if ($key !== 'home') {
            $navigation_modules[$key] = $module;
        }
    }
}

// Fonction helper si pas définie
if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass($role) {
        return 'role-' . strtolower($role);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <title><?= htmlspecialchars($page_title . ' - ' . $app_name) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon_32x32.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon_180x180.png">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">

    <!-- CSS modulaire -->
    <?php if ($module_css && $current_module !== 'home'): ?>
        <link rel="stylesheet" href="/public/<?= $current_module ?>/assets/css/<?= $current_module ?>.css?v=<?= $build_number ?>">
    <?php endif; ?>

    <style>
        :root {
            --current-module-color: <?= $module_color ?>;
        }
        
        /* Header adaptatif */
        .portal-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .portal-header.header-compact {
            padding: 0.5rem 0;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 1.5rem;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: white;
        }
        
        .header-logo {
            font-size: 1.5rem;
        }
        
        .header-brand-text {
            font-weight: 600;
        }
        
        .header-page-info {
            text-align: center;
        }
        
        .page-main-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .page-subtitle {
            opacity: 0.9;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .portal-header.header-compact .page-subtitle {
            opacity: 0;
            height: 0;
            overflow: hidden;
        }
        
        /* Navigation */
        .main-navigation {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e5e7eb;
            position:
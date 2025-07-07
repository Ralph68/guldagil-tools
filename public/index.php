<?php
/**
 * Titre: Page d'accueil du portail Guldagil - Version corrigée avec authentification
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Définir ROOT_PATH pour sécurité
define('ROOT_PATH', dirname(__DIR__));

// Chargement sécurisé de la configuration
$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Configuration manquante</h1><p>Fichier requis : ' . basename($file) . '</p>');
    }
}

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
} catch (Exception $e) {
    http_response_code(500);
    die('<h1>❌ Erreur de chargement</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Variables par défaut avec fallbacks sécurisés
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$build_date = defined('BUILD_DATE') ? BUILD_DATE : date('d/m/Y H:i');
$is_debug = defined('DEBUG') && DEBUG;

// AUTHENTIFICATION - Gestion session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Vérifier authentification
$user_authenticated = false;
$current_user = null;

if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
    require_once ROOT_PATH . '/core/auth/AuthManager.php';
    try {
        $auth = AuthManager::getInstance();
        $user_authenticated = $auth->isAuthenticated();
        $current_user = $user_authenticated ? $auth->getCurrentUser() : null;
    } catch (Exception $e) {
        // Fallback vers session basique si AuthManager échoue
        $user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
        $current_user = $user_authenticated ? ($_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user']) : null;
    }
} else {
    // Session basique
    $user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    $current_user = $user_authenticated ? ($_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user']) : null;
}

// ========================================
// 🔐 AUTHENTIFICATION OBLIGATOIRE
// ========================================
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Variables pour le template
$page_title = 'Portail Guldagil';
$page_subtitle = 'Solutions professionnelles';
$page_description = 'Portail de gestion - Calcul frais, ADR, contrôle qualité';
$current_module = 'home';

// Fil d'Ariane
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

$show_admin_footer = true;

// Modules disponibles - COMPLET avec tous les modules
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'path' => '/port/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique'],
        'estimated_completion' => '100%'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses - Déclarations et suivi réglementaire',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'active',
        'path' => '/adr/',
        'features' => ['Déclarations ADR', 'Gestion des quotas', 'Suivi réglementaire'],
        'estimated_completion' => '85%'
    ],
    'controle-qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
        'icon' => '✅',
        'color' => 'green',
        'status' => 'development',
        'path' => '/controle-qualite/',
        'features' => ['Tests et validations', 'Rapports de conformité', 'Suivi des équipements'],
        'estimated_completion' => '60%'
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection individuelle - Stock et maintenance',
        'icon' => '🛡️',
        'color' => 'purple',
        'status' => 'development',
        'path' => '/epi/',
        'features' => ['Inventaire EPI', 'Suivi des dates d\'expiration', 'Gestion des commandes'],
        'estimated_completion' => '40%'
    ],
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion des outils et équipements techniques - Maintenance et traçabilité',
        'icon' => '🔧',
        'color' => 'gray',
        'status' => 'development',
        'path' => '/outillages/',
        'features' => ['Inventaire outillage', 'Planning maintenance', 'Suivi d\'utilisation'],
        'estimated_completion' => '25%'
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et gestion globale du portail - Réservé aux administrateurs',
        'icon' => '⚙️',
        'color' => 'red',
        'status' => 'admin_only',
        'path' => '/admin/',
        'features' => ['Configuration système', 'Gestion utilisateurs', 'Maintenance'],
        'estimated_completion' => '95%'
    ]
];

// Statistiques pour le dashboard
$stats = [
    'modules_total' => count($available_modules),
    'modules_actifs' => count(array_filter($available_modules, fn($m) => $m['status'] === 'active')),
    'modules_dev' => count(array_filter($available_modules, fn($m) => $m['status'] === 'development')),
    'completion_moyenne' => round(
        array_sum(array_map(fn($m) => (int)str_replace('%', '', $m['estimated_completion']), $available_modules)) 
        / count($available_modules)
    )
];

// Version info formatée
$version_info = [
    'version' => $app_version,
    'build' => $build_number,
    'short_build' => substr($build_number, -8),
    'date' => $build_date,
    'year' => date('Y')
];

// Fonction pour déterminer l'accès aux modules
function canAccessModule($module, $user_role = 'user') {
    if ($module['status'] === 'admin_only') {
        return in_array($user_role, ['admin', 'dev']);
    }
    return true;
}

// Fonction pour les labels de statut
function getStatusLabel($status) {
    return match($status) {
        'active' => 'Disponible',
        'development' => 'En développement',
        'admin_only' => 'Administrateurs',
        'maintenance' => 'Maintenance',
        default => 'Non disponible'
    };
}

function getModuleStatusClass($status) {
    return match($status) {
        'active' => 'status-active',
        'development' => 'status-dev',
        'admin_only' => 'status-admin',
        'maintenance' => 'status-maintenance',
        default => 'status-disabled'
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Version <?= htmlspecialchars($app_version) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="<?= htmlspecialchars($app_author) ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= htmlspecialchars($version_info['short_build']) ?>">
    
    <style>
        /* CSS critique intégré pour éviter FOUC et respecter la charte */
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
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition-normal: 0.3s ease;
        }
        
        * { box-sizing: border-box; }
        
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
        }
        
        /* Header avec logo intégré */
        .portal-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            color: white;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--spacing-lg);
            padding: var(--spacing-lg) 0;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            cursor: pointer;
            transition: var(--transition-normal);
            text-decoration: none;
            color: inherit;
        }
        
        .header-brand:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .portal-logo {
            height: 48px;
            width: auto;
            border-radius: var(--radius-md);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
        
        /* Section utilisateur - CORRIGE LE PROBLÈME */
        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: rgba(255, 255, 255, 0.1);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            position: relative;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .user-role {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        .user-dropdown {
            position: relative;
        }
        
        .dropdown-trigger {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: var(--spacing-xs);
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }
        
        .dropdown-trigger:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            z-index: 1000;
            display: none;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            color: var(--gray-700);
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .dropdown-item:hover {
            background: var(--gray-50);
        }
        
        .dropdown-item.logout {
            color: var(--color-danger);
            border-top: 1px solid var(--gray-200);
        }
        
        /* Main content */
        .portal-main {
            flex: 1;
            padding: var(--spacing-xl) 0;
        }
        
        /* Welcome section */
        .welcome-section {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-md);
        }
        
        .welcome-description {
            font-size: 1.125rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto var(--spacing-lg);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-md);
            margin-top: var(--spacing-lg);
        }
        
        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: var(--spacing-xs);
        }
        
        /* Modules grid */
        .modules-section {
            margin-bottom: var(--spacing-2xl);
        }
        
        .section-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-xl);
            text-align: center;
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--spacing-xl);
        }
        
        .module-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            transition: var(--transition-normal);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .module-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .module-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .module-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .module-icon.blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
        .module-icon.orange { background: linear-gradient(135deg, #fed7aa, #fdba74); }
        .module-icon.green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .module-icon.purple { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); }
        .module-icon.gray { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); }
        .module-icon.red { background: linear-gradient(135deg, #fecaca, #fca5a5); }
        
        .module-info h3 {
            margin: 0 0 var(--spacing-xs);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .module-description {
            color: var(--gray-600);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: var(--spacing-lg);
        }
        
        .module-status {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin-bottom: var(--spacing-md);
        }
        
        .status-active { background: #d1fae5; color: #065f46; }
        .status-dev { background: #fef3c7; color: #92400e; }
        .status-admin { background: #fecaca; color: #991b1b; }
        .status-maintenance { background: #e5e7eb; color: #374151; }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin: 0 0 var(--spacing-lg);
        }
        
        .features-list li {
            padding: var(--spacing-xs) 0;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .features-list li:before {
            content: "✓";
            color: var(--color-success);
            font-weight: 600;
            margin-right: var(--spacing-sm);
        }
        
        .progress-bar {
            background: var(--gray-200);
            border-radius: var(--radius-md);
            height: 6px;
            overflow: hidden;
            margin-top: var(--spacing-md);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-blue), var(--primary-blue-light));
            border-radius: var(--radius-md);
            transition: width 0.6s ease;
        }
        
        /* Footer */
        .portal-footer {
            background: linear-gradient(135deg, var(--gray-800), var(--gray-900));
            color: white;
            padding: var(--spacing-xl) 0;
            margin-top: auto;
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: var(--spacing-lg);
        }
        
        .footer-brand {
            text-align: left;
        }
        
        .footer-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .footer-subtitle {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .footer-info {
            text-align: center;
            font-size: 0.875rem;
        }
        
        .version-info {
            margin-bottom: 0.25rem;
        }
        
        .build-info {
            opacity: 0.7;
            font-size: 0.8rem;
        }
        
        .footer-copyright {
            text-align: right;
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: var(--spacing-md);
                text-align: center;
                padding: var(--spacing-lg) 0;
            }
            
            .portal-title {
                font-size: 1.75rem;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: var(--spacing-md);
            }
            
            .footer-brand,
            .footer-copyright {
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 var(--spacing-md);
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header avec logo et authentification -->
    <header class="portal-header">
        <div class="header-container container">
            <!-- Logo + titre -->
            <a href="/" class="header-brand">
                <?php if (file_exists(ROOT_PATH . '/public/assets/img/logo.png')): ?>
                    <img src="/assets/img/logo.png" alt="Logo Guldagil" class="portal-logo">
                <?php endif; ?>
                <div class="brand-info">
                    <h1><?= htmlspecialchars($app_name) ?></h1>
                    <p><?= htmlspecialchars($page_subtitle) ?></p>
                </div>
            </a>
            
            <!-- Section utilisateur - CORRIGE LE PROBLÈME DU BOUTON CONNEXION -->
            <div class="header-actions">
                <?php if ($user_authenticated && $current_user): ?>
                    <div class="user-section">
                        <div class="user-avatar">
                            <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                            <div class="user-role"><?= htmlspecialchars($current_user['role'] ?? 'Utilisateur') ?></div>
                        </div>
                        
                        <div class="user-dropdown">
                            <button class="dropdown-trigger" onclick="toggleUserMenu()" aria-label="Menu utilisateur">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                </svg>
                            </button>
                            
                            <div id="user-menu" class="dropdown-menu">
                                <a href="/profile/" class="dropdown-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                    </svg>
                                    Mon profil
                                </a>
                                <a href="/settings/" class="dropdown-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                                        <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
                                    </svg>
                                    Paramètres
                                </a>
                                <?php if (in_array($current_user['role'] ?? '', ['admin', 'dev'])): ?>
                                <a href="/admin/" class="dropdown-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M6 .5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v1H6v-1zM11 1v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3H1.5a.5.5 0 0 1 0-1H5V1a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1z"/>
                                    </svg>
                                    Administration
                                </a>
                                <?php endif; ?>
                                <button onclick="logout()" class="dropdown-item logout">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                                    </svg>
                                    Déconnexion
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Bouton connexion pour utilisateurs non connectés -->
                    <a href="/auth/login.php" class="login-btn">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/>
                            <path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                        </svg>
                        Connexion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <main class="portal-main">
        <div class="container">
            <!-- Section de bienvenue -->
            <section class="welcome-section">
                <h1 class="welcome-title">Bienvenue <?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></h1>
                <p class="welcome-description">
                    <?= htmlspecialchars($page_description) ?>
                </p>
                
                <!-- Statistiques du portail -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['modules_total'] ?></div>
                        <div class="stat-label">Modules totaux</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['modules_actifs'] ?></div>
                        <div class="stat-label">Modules actifs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['modules_dev'] ?></div>
                        <div class="stat-label">En développement</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['completion_moyenne'] ?>%</div>
                        <div class="stat-label">Avancement moyen</div>
                    </div>
                </div>
            </section>

            <!-- Modules disponibles -->
            <section class="modules-section">
                <h2 class="section-title">Modules disponibles</h2>
                
                <div class="modules-grid">
                    <?php foreach ($available_modules as $module_id => $module): ?>
                        <?php 
                        $user_role = $current_user['role'] ?? 'user';
                        $can_access = canAccessModule($module, $user_role);
                        $module_class = $can_access ? '' : 'disabled';
                        ?>
                        <div class="module-card <?= $module_class ?>" 
                             onclick="navigateToModule('<?= htmlspecialchars($module_id) ?>', '<?= htmlspecialchars($module['path']) ?>', '<?= htmlspecialchars($module['status']) ?>', <?= $can_access ? 'true' : 'false' ?>)">
                            
                            <div class="module-header">
                                <div class="module-icon <?= htmlspecialchars($module['color']) ?>">
                                    <?= $module['icon'] ?>
                                </div>
                                <div class="module-info">
                                    <h3><?= htmlspecialchars($module['name']) ?></h3>
                                    <div class="module-status <?= getModuleStatusClass($module['status']) ?>">
                                        <?= getStatusLabel($module['status']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="module-description">
                                <?= htmlspecialchars($module['description']) ?>
                            </p>
                            
                            <ul class="features-list">
                                <?php foreach ($module['features'] as $feature): ?>
                                    <li><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" 
                                     style="width: <?= htmlspecialchars($module['estimated_completion']) ?>"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
        <div class="footer-container container">
            <div class="footer-brand">
                <div class="footer-title"><?= htmlspecialchars($app_name) ?></div>
                <div class="footer-subtitle">Solutions professionnelles</div>
            </div>
            
            <div class="footer-info">
                <div class="version-info">
                    Version <?= htmlspecialchars($version_info['version']) ?> | 
                    Build #<?= htmlspecialchars($version_info['short_build']) ?>
                </div>
                <div class="build-info">
                    <?= htmlspecialchars($version_info['date']) ?>
                </div>
            </div>
            
            <div class="footer-copyright">
                &copy; <?= htmlspecialchars($version_info['year']) ?> <?= htmlspecialchars($app_author) ?>
            </div>
        </div>
    </footer>

    <script>
        // Gestion du menu utilisateur - CORRIGE LE PROBLÈME DU BOUTON CONNEXION
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('show');
        }

        // Fermer le menu si on clique ailleurs
        document.addEventListener('click', function(event) {
            const userSection = document.querySelector('.user-dropdown');
            const menu = document.getElementById('user-menu');
            
            if (userSection && !userSection.contains(event.target)) {
                menu?.classList.remove('show');
            }
        });

        // Navigation sécurisée vers les modules
        function navigateToModule(moduleId, path, status, canAccess) {
            if (!canAccess) {
                alert('Accès refusé : Vous n\'avez pas les permissions nécessaires pour ce module.');
                return;
            }
            
            if (status === 'development') {
                if (confirm('Ce module est en cours de développement. Souhaitez-vous continuer ?')) {
                    window.location.href = path;
                }
                return;
            }
            
            if (status === 'maintenance') {
                alert('Module temporairement indisponible pour maintenance.');
                return;
            }
            
            if (status === 'admin_only' && !<?= json_encode(in_array($current_user['role'] ?? '', ['admin', 'dev'])) ?>) {
                alert('Accès réservé aux administrateurs.');
                return;
            }
            
            if (path && path !== '#') {
                window.location.href = path;
            }
        }

        // Fonction de déconnexion - CORRIGE LE PROBLÈME
        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                // Créer un formulaire pour la déconnexion sécurisée
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/auth/logout.php';
                
                // Ajouter un token CSRF si disponible
                <?php if (isset($_SESSION['csrf_token'])): ?>
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
                form.appendChild(csrfInput);
                <?php endif; ?>
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Animation d'apparition des cartes
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des statistiques
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach((stat, index) => {
                const finalValue = stat.textContent;
                stat.textContent = '0';
                
                setTimeout(() => {
                    animateCounter(stat, 0, parseInt(finalValue) || 0, 1000);
                }, index * 200);
            });
            
            // Animation des cartes modules
            const cards = document.querySelectorAll('.module-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150 + 500);
            });
            
            // Animation des barres de progression
            setTimeout(() => {
                const progressBars = document.querySelectorAll('.progress-fill');
                progressBars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            }, 1000);
            
            console.log('✅ Portail Guldagil v<?= htmlspecialchars($version_info['version']) ?> chargé - Build #<?= htmlspecialchars($version_info['short_build']) ?>');
        });

        // Animation de compteur pour les statistiques
        function animateCounter(element, start, end, duration) {
            const increment = end / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current) + (end.toString().includes('%') ? '%' : '');
            }, 16);
        }
        
        // Recherche rapide de modules (bonus)
        function filterModules(searchTerm) {
            const cards = document.querySelectorAll('.module-card');
            const term = searchTerm.toLowerCase();
            
            cards.forEach(card => {
                const title = card.querySelector('.module-info h3').textContent.toLowerCase();
                const description = card.querySelector('.module-description').textContent.toLowerCase();
                
                if (title.includes(term) || description.includes(term)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Raccourcis clavier
        document.addEventListener('keydown', function(event) {
            // Alt + H = Accueil
            if (event.altKey && event.key === 'h') {
                window.location.href = '/';
            }
            
            // Alt + L = Déconnexion
            if (event.altKey && event.key === 'l') {
                logout();
            }
            
            // Échap = Fermer les menus
            if (event.key === 'Escape') {
                document.getElementById('user-menu')?.classList.remove('show');
            }
        });
    </script>
</body>
</html>

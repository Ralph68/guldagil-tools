<?php
/**
 * Titre: Header du portail - AUTHENTIFICATION OBLIGATOIRE + GESTION RÔLES COMPLÈTE
 * Chemin: /templates/header.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    exit('Accès direct interdit');
}

// ========================================
// 🔐 AUTHENTIFICATION OBLIGATOIRE CENTRALISÉE
// ========================================

// Pages exemptées d'authentification (peuvent être consultées sans connexion)
$exempt_paths = [
    '/public/auth/login.php',
    '/public/auth/logout.php', 
    '/public/auth/mfa.php',
    '/public/errors/',
    '/assets/',
    '/favicon.ico'
];

// Vérifier si on doit bypasser l'auth pour cette page
$current_script = $_SERVER['SCRIPT_NAME'] ?? '';
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$bypass_auth = false;

foreach ($exempt_paths as $exempt_path) {
    if (strpos($current_script, $exempt_path) === 0 || strpos($current_uri, $exempt_path) === 0) {
        $bypass_auth = true;
        break;
    }
}

// === CHARGEMENT CONFIGURATION ET MODULES ===
$config_loaded = false;
$all_modules = [];

// Chargement configuration modules
if (file_exists(ROOT_PATH . '/config/modules.php')) {
    require_once ROOT_PATH . '/config/modules.php';
    $all_modules = $modules ?? [];
    $config_loaded = true;
}

// Fallback modules si config non trouvée
if (empty($all_modules)) {
    $all_modules = [
        'port' => ['name' => 'Frais de port', 'icon' => '📦', 'status' => 'beta', 'color' => '#3498db', 'routes' => ['port']],
        'adr' => ['name' => 'Gestion ADR', 'icon' => '⚠️', 'status' => 'development', 'color' => '#e74c3c', 'routes' => ['adr']],
        'qualite' => ['name' => 'Contrôle Qualité', 'icon' => '✅', 'status' => 'development', 'color' => '#2ecc71', 'routes' => ['qualite']],
        'epi' => ['name' => 'Équipements EPI', 'icon' => '🦺', 'status' => 'development', 'color' => '#f39c12', 'routes' => ['epi']],
        'outillages' => ['name' => 'Outillages', 'icon' => '🔧', 'status' => 'development', 'color' => '#95a5a6', 'routes' => ['outillages']],
        'user' => ['name' => 'Mon Espace', 'icon' => '👤', 'status' => 'active', 'color' => '#9b59b6', 'routes' => ['user']],
        'admin' => ['name' => 'Administration', 'icon' => '⚙️', 'status' => 'active', 'color' => '#34495e', 'routes' => ['admin']]
    ];
}

// === AUTHENTIFICATION ===
$user_authenticated = false;
$current_user = null;

// Si pas de bypass, vérifier l'authentification OBLIGATOIRE
if (!$bypass_auth) {
    // Démarrer session si pas déjà fait
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    try {
        // Méthode 1 : AuthManager (prioritaire)
        if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
            require_once ROOT_PATH . '/core/auth/AuthManager.php';
            $auth = new AuthManager();
            
            if ($auth->isAuthenticated()) {
                $user_authenticated = true;
                $current_user = $auth->getCurrentUser();
                
                // Synchroniser avec session PHP
                $_SESSION['authenticated'] = true;
                $_SESSION['user'] = $current_user;
                $_SESSION['last_activity'] = time();
            }
        }
        
        // Méthode 2 : Session PHP simple (fallback)
        if (!$user_authenticated) {
            if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && isset($_SESSION['user'])) {
                // Vérifier expiration de session
                $last_activity = $_SESSION['last_activity'] ?? 0;
                $session_timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800; // 30 minutes
                
                if ((time() - $last_activity) <= $session_timeout) {
                    $user_authenticated = true;
                    $current_user = $_SESSION['user'];
                    $_SESSION['last_activity'] = time();
                } else {
                    // Session expirée - nettoyer
                    session_destroy();
                    session_start();
                }
            }
        }
    } catch (Exception $e) {
        error_log("Erreur auth header: " . $e->getMessage());
        $user_authenticated = false;
    }
    
    // ========================================
    // 🚫 REDIRECTION OBLIGATOIRE SI PAS AUTHENTIFIÉ
    // ========================================
    if (!$user_authenticated) {
        $redirect_param = ($current_uri !== '/' && $current_uri !== '/index.php') ? '?redirect=' . urlencode($current_uri) : '';
        
        // Headers de sécurité
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Location: /auth/login.php' . $redirect_param);
        exit;
    }
    
    // Log d'accès pour utilisateurs authentifiés
    $access_log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $current_user['username'] ?? 'unknown',
        'role' => $current_user['role'] ?? 'user',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'page' => $current_script,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
    ];
    error_log("ACCESS: " . json_encode($access_log));
}

// === VARIABLES AVEC FALLBACKS SÉCURISÉS ===
$page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
$page_subtitle = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
$page_description = htmlspecialchars($page_description ?? 'Portail de gestion');
$current_module = htmlspecialchars($current_module ?? 'home');
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// === DÉTECTION AUTOMATIQUE DU MODULE DEPUIS L'URL ===
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

// ========================================
// 🔒 VÉRIFICATION PERMISSIONS MODULE (si utilisateur authentifié)
// ========================================
if ($user_authenticated && $current_module !== 'home' && !$bypass_auth) {
    $user_role = $current_user['role'] ?? 'user';
    
    if (!canAccessModule($current_module, $all_modules[$current_module] ?? [], $user_role)) {
        // Utilisateur connecté mais pas les droits pour ce module
        header('Location: /errors/403.php?module=' . urlencode($current_module));
        exit;
    }
}

// === FIL D'ARIANE PAR DÉFAUT ===
$breadcrumbs = $breadcrumbs ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// === CSS ET JS MODULAIRE ===
$module_css = $module_css ?? true;
$module_js = $module_js ?? true;

// === TITRE COMPLET ===
$full_title = $page_title . ' - Guldagil v' . $app_version;

// === ICÔNE, COULEUR, STATUT DU MODULE COURANT ===
$module_icon = $all_modules[$current_module]['icon'] ?? match($current_module) {
    'calculateur', 'port' => '🚛',
    'adr' => '⚠️',
    'admin' => '⚙️',
    'qualite' => '✅',
    'epi' => '🦺',
    'outillages' => '🔧',
    'maintenance' => '🔧',
    'stats' => '📊',
    'user', 'profile' => '👤',
    default => '🏠'
};
$module_color = $all_modules[$current_module]['color'] ?? '#3182ce';
$module_status = $all_modules[$current_module]['status'] ?? 'active';

// === FONCTION D'ACCÈS AUX MODULES SELON RÔLE (PRÉSERVÉE) ===
function canAccessModule($module_key, $module_data, $user_role) {
    if (!$user_role || $user_role === 'guest') {
        return false; // Non connecté = pas d'accès
    }
    
    switch ($user_role) {
        case 'dev':
            return true; // Accès total sans restriction
            
        case 'admin':
            // Accès à tous modules sauf /dev, statuts 'active' et 'beta'
            return ($module_key !== 'dev' && in_array($module_data['status'] ?? 'active', ['active', 'beta']));
            
        case 'logistique':
            // Accès à port (beta), adr et qualité mais seulement si pas en développement
            if (in_array($module_key, ['port', 'adr', 'qualite'])) {
                if ($module_key === 'port' && ($module_data['status'] ?? 'active') === 'beta') {
                    return true; // Port en beta = accès
                }
                // ADR et Qualité en développement = pas d'accès pour l'instant
                return false;
            }
            return false;
            
        case 'user':
            // Accès uniquement aux modules actifs
            return (($module_data['status'] ?? 'active') === 'active');
            
        default:
            return false;
    }
}

function shouldShowModule($module_key, $module_data, $user_role) {
    if (!$user_role || $user_role === 'guest') {
        return false;
    }
    
    switch ($user_role) {
        case 'dev':
            return true; // Tout voir
            
        case 'admin':
            // Voir tous modules active/beta + admin (exclu dev)
            return ($module_key === 'admin' || in_array($module_data['status'] ?? 'active', ['active', 'beta']));
            
        case 'logistique':
            // Voir port + adr + epi + outillages + qualité (même si pas d'accès pour certains)
            return in_array($module_key, ['port', 'adr', 'epi', 'outillages', 'qualite']);
            
        case 'user':
            return (($module_data['status'] ?? 'active') === 'active');
            
        default:
            return false;
    }
}

// === GESTION ROLE BADGE CSS (PRÉSERVÉE) ===
function getRoleBadgeClass($role) {
    return match($role) {
        'dev' => 'role-dev',
        'admin' => 'role-admin', 
        'logistique' => 'role-logistique',
        'user' => 'role-user',
        default => 'role-user'
    };
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="blue">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $full_title ?></title>
    <meta name="description" content="<?= $page_description ?>">
    <meta name="author" content="<?= $app_author ?>">
    <meta name="version" content="<?= $app_version ?>">
    <meta name="build" content="<?= $build_number ?>">
    
    <!-- Headers de sécurité -->
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon.ico">
    
    <!-- CSS principal OBLIGATOIRE - chemins critiques à préserver -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <!-- CSS modulaire avec fallback intelligent -->
    <?php if ($module_css && $current_module !== 'home'): ?>
        <?php 
        // 1. Priorité : nouveau système dans /public/module/assets/
        $new_css_path = "/{$current_module}/assets/css/{$current_module}.css";
        $module_css_loaded = false;
        
        if (file_exists(ROOT_PATH . $new_css_path)): ?>
            <link rel="stylesheet" href="<?= $new_css_path ?>?v=<?= $build_number ?>">
            <?php $module_css_loaded = true; ?>
        <?php endif; ?>
        
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
    
    <!-- CSS pour roles badges (PRÉSERVÉ + AMÉLIORÉ) -->
    <style>
        .role-badge.role-logistique {
            background: #059669;
            color: white;
        }
        .role-badge.role-dev {
            background: #7c3aed;
            color: white;
        }
        .role-badge.role-admin {
            background: #d97706;
            color: white;
        }
        .role-badge.role-user {
            background: #2563eb;
            color: white;
        }
        .module-nav-item.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
        .module-nav-item.disabled::after {
            content: ' (Développement)';
            font-size: 0.75rem;
            opacity: 0.7;
        }
        .security-indicator {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #059669;
            color: white;
            text-align: center;
            padding: 0.25rem;
            font-size: 0.8rem;
            z-index: 1000;
            font-weight: bold;
        }
        body {
            margin-top: 2rem; /* Compenser la bannière de sécurité */
        }
    </style>
</head>
<body data-module="<?= $current_module ?>" data-module-status="<?= $module_status ?>" class="<?= $bypass_auth ? 'auth-page' : 'authenticated' ?>">

    <?php if (!$bypass_auth): ?>
    <!-- Bannière de sécurité -->
    <div class="security-indicator">
        🔒 SESSION SÉCURISÉE - <?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?> 
        (<?= htmlspecialchars(ucfirst($current_user['role'] ?? 'user')) ?>) 
        | <?= date('H:i') ?> | IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A') ?>
    </div>
    <?php endif; ?>

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
                <a href="#" class="user-menu-trigger" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar">
                        <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 1)) ?>
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
                </a>

                <div class="user-dropdown" role="menu" aria-hidden="true">
                    <div class="dropdown-header">
                        <div class="dropdown-user-name"><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></div>
                        <div class="dropdown-user-email"><?= htmlspecialchars($current_user['email'] ?? '') ?></div>
                        <div class="dropdown-user-role">
                            Rôle : <span class="role-badge <?= getRoleBadgeClass($current_user['role'] ?? 'user') ?>">
                                <?= ucfirst($current_user['role'] ?? 'user') ?>
                            </span>
                        </div>
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
                    
                    <?php if (in_array($current_user['role'] ?? 'user', ['admin', 'dev'])): ?>
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

    <!-- Menu modules horizontal - LOGIQUE PRÉSERVÉE + SÉCURISÉE -->
    <?php if ($user_authenticated): ?>
    <nav class="modules-nav">
        <div class="modules-container">
            <div class="modules-list">
                <?php 
                $user_role = $current_user['role'] ?? 'user';
                foreach ($all_modules as $module_key => $module_data): 
                    if (shouldShowModule($module_key, $module_data, $user_role)):
                        $can_access = canAccessModule($module_key, $module_data, $user_role);
                        $css_classes = ['module-nav-item'];
                        
                        if ($current_module === $module_key) {
                            $css_classes[] = 'active';
                        }
                        
                        if (!$can_access) {
                            $css_classes[] = 'disabled';
                        }
                        
                        $href = $can_access ? "/{$module_key}/" : "#";
                ?>
                    <a href="<?= $href ?>" 
                       class="<?= implode(' ', $css_classes) ?>"
                       style="--module-color: <?= $module_data['color'] ?? '#3182ce' ?>"
                       <?= !$can_access ? 'title="Module en développement - Accès restreint"' : '' ?>>
                        <span class="module-nav-icon"><?= $module_data['icon'] ?? '📁' ?></span>
                        <span class="module-nav-name"><?= htmlspecialchars($module_data['name']) ?></span>
                        <?php if ($module_data['status'] === 'beta'): ?>
                            <span class="status-badge beta">BETA</span>
                        <?php elseif ($module_data['status'] === 'development'): ?>
                            <span class="status-badge dev">DEV</span>
                        <?php endif; ?>
                    </a>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
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

    <!-- JavaScript pour interactions header (PRÉSERVÉ + AMÉLIORÉ) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion menu utilisateur
            const userMenuTrigger = document.querySelector('.user-menu-trigger');
            const userDropdown = document.querySelector('.user-dropdown');
            
            if (userMenuTrigger && userDropdown) {
                userMenuTrigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isOpen = userDropdown.getAttribute('aria-hidden') === 'false';
                    
                    userDropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
                    userMenuTrigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                });
                
                // Fermer au clic externe
                document.addEventListener('click', function(e) {
                    if (!userMenuTrigger.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.setAttribute('aria-hidden', 'true');
                        userMenuTrigger.setAttribute('aria-expanded', 'false');
                    }
                });
            }
            
            // Gestion modules désactivés
            const disabledModules = document.querySelectorAll('.module-nav-item.disabled');
            disabledModules.forEach(function(module) {
                module.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Ce module est en cours de développement et n\'est pas encore accessible.');
                });
            });
            
            // Log sécurisé
            console.log('🔗 Header initialisé avec authentification obligatoire');
            console.log('👤 Utilisateur:', '<?= htmlspecialchars($current_user['username'] ?? 'guest') ?>');
            console.log('🎭 Rôle:', '<?= htmlspecialchars($current_user['role'] ?? 'guest') ?>');
            console.log('🔒 Auth method:', '<?= htmlspecialchars($_SESSION['auth_method'] ?? 'Session PHP') ?>');
        });
    </script>

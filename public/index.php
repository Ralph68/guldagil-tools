<?php
/**
 * Titre: Page d'accueil du portail Guldagil - Sécurisée
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration d'erreur pour développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chargement sécurisé de la configuration
if (!file_exists(__DIR__ . '/../config/config.php')) {
    die('Configuration manquante');
}
if (!file_exists(__DIR__ . '/../config/version.php')) {
    die('Fichier version manquant');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/version.php';

// AUTHENTIFICATION REQUISE - Redirection si non connecté
session_start();

// Vérifier authentification
$user_authenticated = false;
if (file_exists(__DIR__ . '/../core/auth/AuthManager.php')) {
    require_once __DIR__ . '/../core/auth/AuthManager.php';
    $auth = AuthManager::getInstance();
    $user_authenticated = $auth->isAuthenticated();
    $current_user = $user_authenticated ? $auth->getCurrentUser() : null;
} else {
    // Fallback basique si pas de système auth avancé
    $user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    $current_user = $user_authenticated ? $_SESSION['user'] ?? null : null;
}

// REDIRECTION OBLIGATOIRE vers login si non connecté
if (!$user_authenticated) {
    header('Location: /auth/login.php');
    exit;
}

// Variables pour le template
$page_title = 'Portail Guldagil';
$page_subtitle = 'Solutions professionnelles';
$page_description = 'Portail de gestion - Calcul frais, ADR, contrôle qualité';
$current_module = 'home';

// Modules disponibles selon les droits
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'path' => '/calculateur/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export']
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'active',
        'path' => '/adr/',
        'features' => ['Déclarations ADR', 'Gestion quotas', 'Suivi réglementaire']
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et maintenance du portail',
        'icon' => '⚙️',
        'color' => 'purple',
        'status' => 'admin_only',
        'path' => '/admin/',
        'features' => ['Configuration', 'Gestion utilisateurs', 'Maintenance']
    ]
];

// Fonctions utilitaires
function getStatusLabel($status) {
    return match($status) {
        'active' => 'Disponible',
        'development' => 'En développement',
        'admin_only' => 'Administrateur',
        default => 'Non disponible'
    };
}

function getModuleStatusClass($status) {
    return match($status) {
        'active' => 'module-available',
        'development' => 'module-dev',
        'admin_only' => 'module-admin',
        default => 'module-disabled'
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Version <?= APP_VERSION ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="<?= APP_AUTHOR ?>">
    
    <!-- CSS principal avec versioning pour cache-busting -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= BUILD_NUMBER ?>">
    
    <style>
        /* CSS intégré pour éviter les problèmes de chargement */
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --gray-100: #f7fafc;
            --gray-200: #e5e7eb;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition-normal: 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gray-100);
            color: var(--gray-800);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .portal-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            padding: var(--spacing-lg) 0;
            box-shadow: var(--shadow-lg);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .portal-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .portal-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            background: rgba(255, 255, 255, 0.1);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
        }

        /* Contenu principal */
        .portal-main {
            flex: 1;
            padding: var(--spacing-2xl) 0;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
        }

        .welcome-section {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-lg);
        }

        .welcome-description {
            font-size: 1.2rem;
            color: var(--gray-600);
            max-width: 800px;
            margin: 0 auto;
        }

        /* Modules */
        .modules-section h3 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-xl);
            text-align: center;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-2xl);
        }

        .module-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: var(--transition-normal);
            border: 1px solid var(--gray-200);
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.15);
        }

        .module-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .module-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .module-icon-blue { background: #dbeafe; }
        .module-icon-orange { background: #fed7aa; }
        .module-icon-purple { background: #e0e7ff; }

        .module-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
        }

        .module-status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background: #d1fae5;
            color: #059669;
        }

        .status-admin_only {
            background: #e0e7ff;
            color: #7c3aed;
        }

        .module-description {
            color: var(--gray-600);
            margin-bottom: var(--spacing-lg);
            line-height: 1.6;
        }

        .module-features {
            list-style: none;
        }

        .module-features li {
            color: var(--gray-500);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .module-features li:before {
            content: '✓';
            color: var(--primary-blue);
            font-weight: bold;
            margin-right: 0.5rem;
        }

        /* Footer */
        .portal-footer {
            background: var(--gray-800);
            color: white;
            padding: var(--spacing-lg) 0;
            text-align: center;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .version-info {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: var(--spacing-md);
                text-align: center;
            }

            .modules-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }

            .welcome-title {
                font-size: 2rem;
            }

            .footer-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="portal-header">
        <div class="header-container">
            <div class="brand-info">
                <h1 class="portal-title"><?= htmlspecialchars($page_title) ?></h1>
                <p class="portal-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
            </div>
            
            <div class="user-info">
                <span>👤</span>
                <div>
                    <div><strong><?= htmlspecialchars($current_user['name'] ?? 'Utilisateur') ?></strong></div>
                    <div style="font-size: 0.9rem; opacity: 0.8;">
                        <?= htmlspecialchars($current_user['role'] ?? 'user') ?>
                    </div>
                </div>
                <a href="/auth/logout.php" style="color: white; text-decoration: none; margin-left: 1rem;">
                    Déconnexion
                </a>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="portal-main">
        <div class="main-container">
            <!-- Section bienvenue -->
            <section class="welcome-section">
                <h2 class="welcome-title">Bienvenue sur le portail Guldagil</h2>
                <p class="welcome-description">
                    Votre plateforme centralisée pour la gestion des frais de transport, 
                    des marchandises dangereuses et du contrôle qualité.
                </p>
            </section>

            <!-- Section modules -->
            <section class="modules-section">
                <h3>Modules disponibles</h3>
                
                <div class="modules-grid">
                    <?php foreach ($available_modules as $moduleId => $module): ?>
                        <?php 
                        // Vérifier accès selon le rôle
                        $canAccess = true;
                        if ($module['status'] === 'admin_only' && $current_user['role'] !== 'admin' && $current_user['role'] !== 'dev') {
                            $canAccess = false;
                        }
                        ?>
                        
                        <article class="module-card <?= getModuleStatusClass($module['status']) ?>" 
                                 data-module="<?= $moduleId ?>"
                                 <?= $canAccess ? 'onclick="window.location.href=\'' . $module['path'] . '\'"' : '' ?>>
                            
                            <div class="module-header">
                                <div class="module-icon module-icon-<?= $module['color'] ?>">
                                    <span><?= $module['icon'] ?></span>
                                </div>
                                <div class="module-meta">
                                    <h4 class="module-name"><?= htmlspecialchars($module['name']) ?></h4>
                                    <span class="module-status status-<?= $module['status'] ?>">
                                        <?= getStatusLabel($module['status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="module-body">
                                <p class="module-description">
                                    <?= htmlspecialchars($module['description']) ?>
                                </p>
                                
                                <?php if (!empty($module['features'])): ?>
                                <ul class="module-features">
                                    <?php foreach ($module['features'] as $feature): ?>
                                    <li><?= htmlspecialchars($feature) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                                
                                <?php if (!$canAccess): ?>
                                <div style="color: #dc2626; font-size: 0.9rem; margin-top: 1rem;">
                                    ⚠️ Accès restreint - Droits administrateur requis
                                </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
        <div class="footer-container">
            <div class="footer-info">
                <div>
                    <strong><?= APP_NAME ?></strong><br>
                    &copy; <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?>
                </div>
                <div class="version-info">
                    Version <?= APP_VERSION ?><br>
                    Build <?= BUILD_NUMBER ?><br>
                    <?= BUILD_DATE ?>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Sécurisation navigation
        function navigateToModule(moduleId, path, status) {
            if (status === 'development') {
                alert('Module en développement');
                return;
            }
            if (path && path !== '#') {
                window.location.href = path;
            }
        }
    </script>
</body>
</html>

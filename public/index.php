<?php
/**
 * Titre: Page d'accueil du portail Guldagil - Mise à jour avec liens fonctionnels
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chargement sécurisé de la configuration
if (!file_exists(__DIR__ . '/../config/config.php')) {
    die('<h1>❌ Erreur Configuration</h1><p>Le fichier config.php est manquant dans /config/</p>');
}
if (!file_exists(__DIR__ . '/../config/version.php')) {
    die('<h1>❌ Erreur Version</h1><p>Le fichier version.php est manquant dans /config/</p>');
}

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/version.php';
} catch (Exception $e) {
    die('<h1>❌ Erreur de chargement</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// AUTHENTIFICATION REQUISE
session_start();

// Vérifier authentification avec système AuthManager si disponible
$user_authenticated = false;
$current_user = null;

if (file_exists(__DIR__ . '/../core/auth/AuthManager.php')) {
    require_once __DIR__ . '/../core/auth/AuthManager.php';
    $auth = AuthManager::getInstance();
    $user_authenticated = $auth->isAuthenticated();
    $current_user = $user_authenticated ? $auth->getCurrentUser() : null;
} else {
    // Fallback session basique
    $user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    $current_user = $user_authenticated ? ($_SESSION['user'] ?? ['name' => 'Utilisateur', 'role' => 'user']) : null;
}

// REDIRECTION si non connecté (désactivée temporairement pour le développement)
// if (!$user_authenticated) {
//     header('Location: /auth/login.php');
//     exit;
// }

// Variables pour le template
$page_title = 'Portail Guldagil';
$page_subtitle = 'Solutions professionnelles';
$page_description = 'Portail de gestion - Calcul frais, ADR, contrôle qualité';
$current_module = 'home';
$module_css = false;
$module_js = false;

// Fil d'Ariane
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

$nav_info = 'Tableau de bord principal';
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
        'estimated_completion' => ''
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Module de gestion des marchandises dangereuses et réglementation ADR',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'development',
        'path' => '#',
        'features' => ['Classification matières', 'Documents obligatoires', 'Codes ADR'],
        'estimated_completion' => 'Q2 2025'
    ],
    'qualite' => [
        'name' => 'Contrôle qualité',
        'description' => 'Suivi et contrôle qualité des produits et processus',
        'icon' => '🔬',
        'color' => 'green',
        'status' => 'planned',
        'path' => '#',
        'features' => ['Tests qualité', 'Rapports conformité', 'Traçabilité'],
        'estimated_completion' => 'Q3 2025'
    ],
    'epi' => [
        'name' => 'Gestion EPI',
        'description' => 'Gestion des équipements de protection individuelle',
        'icon' => '🦺',
        'color' => 'orange',
        'status' => 'planned',
        'path' => '#',
        'features' => ['Inventaire EPI', 'Attribution personnel', 'Contrôle périodique'],
        'estimated_completion' => 'Q4 2025'
    ],
    'outillage' => [
        'name' => 'Gestion outillage',
        'description' => 'Suivi et maintenance des outils et équipements',
        'icon' => '🔧',
        'color' => 'purple',
        'status' => 'planned',
        'path' => '#',
        'features' => ['Inventaire outils', 'Planning maintenance', 'Réservations'],
        'estimated_completion' => '2026'
    ],
    'maintenance' => [
        'name' => 'Maintenance',
        'description' => 'Planification et suivi de la maintenance préventive et curative',
        'icon' => '🔧',
        'color' => 'red',
        'status' => 'admin',
        'path' => '/admin/',
        'features' => ['Planning maintenance', 'Historique interventions', 'Gestion pièces'],
        'estimated_completion' => ''
    ]
];

// Statistiques rapides (simulées pour le moment)
$quick_stats = [
    'calculations_today' => 0,
    'active_modules' => count(array_filter($available_modules, fn($m) => $m['status'] === 'active')),
    'total_modules' => count($available_modules),
    'system_status' => 'operational'
];

// Inclure header si disponible, sinon header intégré
if (file_exists(__DIR__ . '/../templates/header.php')) {
    include __DIR__ . '/../templates/header.php';
} else {
    // Header intégré avec logo
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Version <?= APP_VERSION ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="<?= APP_AUTHOR ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= BUILD_NUMBER ?>">
    
    <!-- Toast CSS intégré -->
    <style>
        /* CSS critique intégré pour éviter FOUC */
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
        
        /* Couleurs modules */
        .module-icon-blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
        .module-icon-orange { background: linear-gradient(135deg, #fed7aa, #fdba74); }
        .module-icon-green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .module-icon-purple { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); }
        .module-icon-gray { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); }
        .module-icon-red { background: linear-gradient(135deg, #fecaca, #fca5a5); }
        
        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast {
            background: white;
            border-left: 4px solid #3182ce;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
            max-width: 350px;
            min-width: 250px;
            opacity: 0;
            padding: 16px;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }
        
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .toast.toast-info { border-left-color: #3182ce; }
        .toast.toast-warning { border-left-color: #f59e0b; }
        .toast.toast-success { border-left-color: #10b981; }
        .toast.toast-error { border-left-color: #ef4444; }
        
        .toast-content {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .toast-icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .toast-message {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: #111827;
        }
        
        .toast-text {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            margin-left: 8px;
        }
        
        .toast-close:hover {
            color: #6b7280;
        }
        
        /* Module cards styling */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .module-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .module-card:hover {
            border-color: #3182ce;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .module-card.disabled {
            opacity: 0.6;
            cursor: pointer;
            filter: grayscale(20%);
        }
        
        .module-card.disabled:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .module-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .module-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .module-info h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
        }
        
        .module-info p {
            margin: 0.5rem 0 0;
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .module-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-development {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-planned {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .status-admin {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Body styling */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            line-height: 1.6;
            color: #374151;
            background: #f9fafb;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        .portal-header {
            background: linear-gradient(135deg, #3182ce, #2563eb);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .portal-logo {
            width: 48px;
            height: 48px;
            border-radius: 8px;
        }
        
        .brand-info h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .brand-info p {
            margin: 0;
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .version-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1rem;
        }
        
        .welcome-description {
            font-size: 1.125rem;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .footer {
            margin-top: 4rem;
            padding: 2rem 0;
            border-top: 1px solid #e5e7eb;
            background: white;
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="portal-header">
        <div class="header-container container">
            <div class="header-brand">
                <?php if (file_exists(__DIR__ . '/assets/img/logo.png')): ?>
                <img src="/assets/img/logo.png" alt="Logo Guldagil" class="portal-logo">
                <?php endif; ?>
                
                <div class="brand-info">
                    <h1 class="portal-title"><?= htmlspecialchars($page_title) ?></h1>
                    <p class="portal-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="version-badge">
                    <span class="version-text">v<?= APP_VERSION ?></span>
                </div>
            </div>
        </div>
    </header>
<?php } ?>

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Contenu principal -->
    <main class="portal-main">
        <div class="main-container container">
            <!-- Section bienvenue -->
            <section class="welcome-section">
                <h2 class="welcome-title">Bienvenue sur le portail Guldagil</h2>
                <p class="welcome-description">
                    Votre plateforme centralisée pour la gestion des frais de transport, 
                    des marchandises dangereuses et du contrôle qualité. Sélectionnez un module ci-dessous pour commencer.
                </p>
            </section>

            <!-- Modules disponibles -->
            <section class="modules-section">
                <div class="modules-grid">
                    <?php foreach ($available_modules as $key => $module): ?>
                    <div class="module-card <?= $module['status'] !== 'active' ? 'disabled' : '' ?>" 
                         onclick="<?= $module['status'] === 'active' ? "window.location.href='" . $module['path'] . "'" : "showDevelopmentToast('" . $key . "')" ?>">
                        
                        <div class="module-status status-<?= $module['status'] ?>">
                            <?= ucfirst($module['status']) ?>
                        </div>
                        
                        <div class="module-header">
                            <div class="module-icon module-icon-<?= $module['color'] ?>">
                                <?= $module['icon'] ?>
                            </div>
                            <div class="module-info">
                                <h3><?= htmlspecialchars($module['name']) ?></h3>
                                <p><?= htmlspecialchars($module['description']) ?></p>
                                <?php if (!empty($module['estimated_completion'])): ?>
                                <p style="font-style: italic; color: #9ca3af; margin-top: 0.25rem;">
                                    Disponible : <?= htmlspecialchars($module['estimated_completion']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>
                <?= APP_NAME ?> - Version <?= APP_VERSION ?> (Build <?= BUILD_NUMBER ?>) 
                | © <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?> 
                | Horodatage: <?= BUILD_DATE ?>
            </p>
        </div>
    </footer>

    <!-- JavaScript pour les toasts -->
    <script>
        // Toast system
        function showToast(type, title, message, duration = 5000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icons = {
                info: '🛠️',
                warning: '⚠️',
                success: '✅',
                error: '❌'
            };
            
            toast.innerHTML = `
                <div class="toast-content">
                    <div class="toast-icon">${icons[type] || icons.info}</div>
                    <div class="toast-message">
                        <div class="toast-title">${title}</div>
                        <div class="toast-text">${message}</div>
                    </div>
                    <button class="toast-close" onclick="hideToast(this)">&times;</button>
                </div>
            `;
            
            container.appendChild(toast);
            
            // Animation d'entrée
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            // Auto-hide
            setTimeout(() => {
                hideToast(toast.querySelector('.toast-close'));
            }, duration);
        }
        
        function hideToast(closeBtn) {
            const toast = closeBtn.closest('.toast');
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
        
        // Toast spécifique pour les modules en développement
        function showDevelopmentToast(moduleKey) {
            const modules = <?= json_encode($available_modules) ?>;
            const module = modules[moduleKey];
            
            let message = `Le module "${module.name}" est actuellement en cours de développement.`;
            
            if (module.estimated_completion) {
                message += ` Disponibilité prévue : ${module.estimated_completion}.`;
            }
            
            message += ' Merci de votre patience ! 🚀';
            
            showToast('info', 'Module en développement', message, 6000);
        }
        
        // Toast pour l'administration
        function showAdminToast() {
            showToast('warning', 'Accès Administration', 
                'Redirection vers l\'interface d\'administration...', 3000);
            setTimeout(() => {
                window.location.href = '/admin/';
            }, 1000);
        }
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Portail Guldagil v<?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>');
        });
    </script>
</body>
</html>

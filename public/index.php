<?php
/**
 * Titre: Page d'accueil du portail Guldagil - VERSION CORRIGÉE
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
define('ROOT_PATH', dirname(__DIR__));
session_start();

// Debug mode (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tentative de chargement config
$config_loaded = false;
$possible_configs = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($possible_configs as $config_path) {
    if (file_exists($config_path)) {
        try {
            require_once $config_path;
            $config_loaded = true;
        } catch (Exception $e) {
            error_log("Erreur config: " . $e->getMessage());
        }
    }
}

// Variables de base (avec ou sans config)
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd');
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';

// Variables pour template
$page_title = 'Accueil du portail';
$page_subtitle = 'Solutions professionnelles';
$page_description = 'Portail Guldagil - Solutions pour le traitement de l\'eau et la logistique';
$current_module = 'home';
$module_css = false; // CSS spéciaux pas nécessaires pour l'accueil

// Gestion utilisateur
$user_authenticated = false;
$current_user = null;

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
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            $user_authenticated = true;
            $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
        }
    }
} catch (Exception $e) {
    error_log("Erreur auth: " . $e->getMessage());
}

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// Modules disponibles
$modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul automatique des frais de port selon transporteur',
        'icon' => '🚛',
        'url' => '/calculateur/',
        'status' => 'active',
        'color' => 'blue'
    ],
    'adr' => [
        'name' => 'Module ADR',
        'description' => 'Gestion des marchandises dangereuses',
        'icon' => '⚠️',
        'url' => '/adr/',
        'status' => 'active',
        'color' => 'orange'
    ],
    'qualite' => [
        'name' => 'Contrôle qualité',
        'description' => 'Gestion et suivi de la qualité',
        'icon' => '✅',
        'url' => '/qualite/',
        'status' => 'development',
        'color' => 'green'
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection',
        'icon' => '🦺',
        'url' => '/epi/',
        'status' => 'development',
        'color' => 'purple'
    ],
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion du matériel et outillages',
        'icon' => '🔧',
        'url' => '/outillages/',
        'status' => 'development',
        'color' => 'gray'
    ]
];

// Statistiques globales (simulées)
$global_stats = [
    'calculs_today' => 47,
    'users_active' => 12,
    'modules_available' => count($modules),
    'uptime_percentage' => 99.8
];

// Inclure header avec CSS complets
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Header minimal de secours
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($app_name) ?></title>
        <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
        <meta name="author" content="<?= htmlspecialchars($app_author) ?>">
        <meta name="robots" content="noindex, nofollow">
        <link rel="icon" type="image/png" href="/public/assets/img/favicon.png">
        
        <!-- CSS COMPLETS pour public/index.php -->
        <link rel="stylesheet" href="/public/assets/css/portal.css?v=<?= $build_number ?>">
        <link rel="stylesheet" href="/public/assets/css/components.css?v=<?= $build_number ?>">
        <link rel="stylesheet" href="/templates/assets/css/header.css?v=<?= $build_number ?>">
        <link rel="stylesheet" href="/templates/assets/css/footer.css?v=<?= $build_number ?>">
        
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                margin: 0; 
                padding: 0; 
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .container { 
                max-width: 1200px; 
                margin: 0 auto; 
                background: white; 
                padding: 20px; 
                border-radius: 8px; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
                flex: 1;
            }
            .header { 
                background: linear-gradient(135deg, #2c5282, #3182ce); 
                color: white; 
                padding: 1rem; 
                border-radius: 8px; 
                margin-bottom: 20px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
            }
            .modules-grid { 
                display: grid; 
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
                gap: 20px; 
                margin: 20px 0;
            }
            .module-card { 
                background: white; 
                border: 1px solid #e5e7eb; 
                border-radius: 8px; 
                padding: 20px; 
                transition: transform 0.2s;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .module-card:hover { 
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            .module-header { 
                display: flex; 
                align-items: center; 
                gap: 10px; 
                margin-bottom: 15px; 
            }
            .btn { 
                display: inline-block; 
                padding: 8px 16px; 
                background: #3182ce; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px; 
                transition: background 0.2s;
            }
            .btn:hover { 
                background: #2c5282; 
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .stat-card {
                background: #f8fafc;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                border-left: 4px solid #3182ce;
            }
            .stat-number {
                font-size: 2rem;
                font-weight: bold;
                color: #3182ce;
            }
            .footer {
                margin-top: 40px;
                padding: 20px;
                background: #f8fafc;
                border-radius: 8px;
                text-align: center;
                font-size: 0.875rem;
                color: #6b7280;
            }
        </style>
    </head>
    <body>
        <header class="header">
            <h1><?= htmlspecialchars($app_name) ?></h1>
            <div>
                <?php if ($user_authenticated): ?>
                    Connecté: <?= htmlspecialchars($current_user['username']) ?> 
                    (<?= htmlspecialchars($current_user['role']) ?>)
                    | <a href="/auth/logout.php" style="color: white;">Se déconnecter</a>
                <?php else: ?>
                    <a href="/auth/login.php" style="color: white;">Se connecter</a>
                <?php endif; ?>
            </div>
        </header>
    <?php
}
?>

<div class="container">
    <!-- En-tête d'accueil -->
    <section class="welcome-section">
        <h1 class="welcome-title">
            🌊 Bienvenue sur <?= htmlspecialchars($app_name) ?>
        </h1>
        <p class="welcome-description">
            Votre portail de solutions professionnelles pour le traitement de l'eau et la logistique. 
            Accédez à tous vos outils en un seul endroit.
        </p>
    </section>

    <!-- Statistiques globales -->
    <section class="stats-section">
        <h2 class="section-title">📊 Statistiques du jour</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $global_stats['calculs_today'] ?></div>
                <div class="stat-label">Calculs effectués</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $global_stats['users_active'] ?></div>
                <div class="stat-label">Utilisateurs actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $global_stats['modules_available'] ?></div>
                <div class="stat-label">Modules disponibles</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $global_stats['uptime_percentage'] ?>%</div>
                <div class="stat-label">Disponibilité</div>
            </div>
        </div>
    </section>

    <!-- Grille des modules -->
    <section class="modules-section">
        <h2 class="section-title">🎯 Modules disponibles</h2>
        <div class="modules-grid">
            <?php foreach ($modules as $key => $module): ?>
            <div class="module-card module-<?= $module['status'] ?>">
                <div class="module-header">
                    <span class="module-icon" style="font-size: 2rem;"><?= $module['icon'] ?></span>
                    <div>
                        <h3 class="module-name"><?= htmlspecialchars($module['name']) ?></h3>
                        <span class="module-status status-<?= $module['status'] ?>">
                            <?= $module['status'] === 'active' ? '🟢 Actif' : '🟡 En développement' ?>
                        </span>
                    </div>
                </div>
                
                <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                
                <div class="module-actions">
                    <?php if ($module['status'] === 'active'): ?>
                        <a href="<?= htmlspecialchars($module['url']) ?>" class="btn btn-primary">
                            Accéder au module
                        </a>
                    <?php else: ?>
                        <span class="btn-disabled">Bientôt disponible</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Actions rapides -->
    <section class="quick-actions">
        <h2 class="section-title">⚡ Actions rapides</h2>
        <div class="actions-grid">
            <a href="/calculateur/" class="action-card">
                <span class="action-icon">🚛</span>
                <span class="action-text">Nouveau calcul</span>
            </a>
            <a href="/user/profile.php" class="action-card">
                <span class="action-icon">👤</span>
                <span class="action-text">Mon profil</span>
            </a>
            <a href="/admin/" class="action-card">
                <span class="action-icon">⚙️</span>
                <span class="action-text">Administration</span>
            </a>
            <a href="/about.php" class="action-card">
                <span class="action-icon">ℹ️</span>
                <span class="action-text">À propos</span>
            </a>
        </div>
    </section>

    <!-- Footer informations -->
    <footer class="footer">
        <p>
            <strong><?= htmlspecialchars($app_name) ?></strong> v<?= htmlspecialchars($app_version) ?> 
            - Build <?= htmlspecialchars($build_number) ?>
            <br>
            Développé par <?= htmlspecialchars($app_author) ?> 
            - 
            <a href="/legal/">Mentions légales</a>
        </p>
    </footer>
</div>

<?php
// Inclure footer si disponible
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
}
?>

<!-- JavaScript pour interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée pour les cartes
    const cards = document.querySelectorAll('.module-card, .stat-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Compteurs animés
    const numbers = document.querySelectorAll('.stat-number');
    numbers.forEach(num => {
        const target = parseInt(num.textContent);
        let current = 0;
        const increment = target / 50;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            num.textContent = Math.floor(current) + (num.textContent.includes('%') ? '%' : '');
        }, 30);
    });
});
</script>

</body>
</html>

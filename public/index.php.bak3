<?php
/**
 * Titre: Page d'accueil du portail Guldagil - Interface professionnelle
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Protection et initialisation
session_start();
define('ROOT_PATH', dirname(__DIR__));

// Configuration des erreurs selon l'environnement
$is_production = (getenv('APP_ENV') === 'production');
if (!$is_production) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Chargement sécurisé de la configuration
$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Erreur Configuration</h1><p>Fichier manquant : ' . basename($file) . '</p>');
    }
}

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
} catch (Exception $e) {
    http_response_code(500);
    $error_msg = $is_production ? 'Erreur de configuration' : htmlspecialchars($e->getMessage());
    die('<h1>❌ Erreur</h1><p>' . $error_msg . '</p>');
}

// AUTHENTIFICATION
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $user_authenticated ? ($_SESSION['user'] ?? null) : null;

if (!$user_authenticated) {
    header('Location: /auth/login.php');
    exit;
}

// Sécurisation utilisateur par défaut
if (!$current_user) {
    $current_user = [
        'username' => 'Utilisateur',
        'role' => 'user'
    ];
}

// Vérification BDD
if (!isset($db) || !($db instanceof PDO)) {
    http_response_code(500);
    die('<h1>❌ Erreur Base de Données</h1><p>Connexion non disponible</p>');
}

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

// Configuration des rôles
$roles = ['guest' => 0, 'user' => 1, 'manager' => 2, 'admin' => 3, 'dev' => 4];
$user_level = $roles[$current_user['role']] ?? 1;

// Modules disponibles - DESIGN ORIGINAL
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'path' => '/calculateur/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique'],
        'min_role' => 'user'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Module de gestion des matières dangereuses et réglementation ADR',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'development',
        'path' => '/adr/',
        'features' => ['Classification matières', 'Documents réglementaires', 'Formation'],
        'min_role' => 'user'
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Système de gestion et suivi qualité produits',
        'icon' => '🔬',
        'color' => 'green',
        'status' => 'development',
        'path' => '/qualite/',
        'features' => ['Contrôles produits', 'Rapports qualité', 'Traçabilité'],
        'min_role' => 'manager'
    ],
    'inventory' => [
        'name' => 'Gestion Stocks',
        'description' => 'Système de gestion des stocks et inventaires',
        'icon' => '📦',
        'color' => 'purple',
        'status' => 'development',
        'path' => '/inventory/',
        'features' => ['Suivi stocks', 'Alertes', 'Mouvements'],
        'min_role' => 'user'
    ],
    'achats' => [
        'name' => 'Gestion Achats',
        'description' => 'Module de gestion des achats et fournisseurs',
        'icon' => '💰',
        'color' => 'green',
        'status' => 'development',
        'path' => '/achats/',
        'features' => ['Commandes', 'Fournisseurs', 'Budget'],
        'min_role' => 'manager'
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Panel d\'administration du portail',
        'icon' => '⚙️',
        'color' => 'red',
        'status' => 'admin_only',
        'path' => '/admin/',
        'features' => ['Utilisateurs', 'Configuration', 'Sécurité'],
        'min_role' => 'admin'
    ]
];

// Filtrage des modules selon les droits
$accessible_modules = array_filter($available_modules, function($module) use ($user_level, $roles) {
    $required_level = $roles[$module['min_role']] ?? 999;
    return $user_level >= $required_level;
});

// Fonctions utilitaires
function getStatusLabel($status) {
    return match($status) {
        'active' => 'Disponible',
        'development' => 'En développement',
        'admin_only' => 'Administrateur',
        'maintenance' => 'Maintenance',
        default => 'Non disponible'
    };
}

function getModuleStatusClass($status) {
    return match($status) {
        'active' => 'module-available',
        'development' => 'module-dev',
        'admin_only' => 'module-admin',
        'maintenance' => 'module-maintenance',
        default => 'module-disabled'
    };
}

// Version info
$version_info = [
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5-beta',
    'build' => defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000',
    'formatted_date' => defined('BUILD_DATE') ? BUILD_DATE : date('d/m/Y H:i')
];

// Inclure le header
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Header de fallback intégré PROFESSIONNEL
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Version <?= htmlspecialchars($version_info['version']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="Jean-Thomas RUNSER">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= htmlspecialchars($version_info['build']) ?>">
    
    <style>
        /* CSS PROFESSIONNEL INTÉGRÉ */
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        * { box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
            margin: 0;
            padding: 0;
            color: var(--gray-900);
            line-height: 1.6;
        }
        
        .portal-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            color: white;
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-lg);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .brand-info h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .brand-info p {
            margin: 0;
            opacity: 0.9;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
        }
        
        /* CONTENU PRINCIPAL - DESIGN PROFESSIONNEL */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .welcome-section h2 {
            font-size: 2rem;
            color: var(--gray-900);
            margin: 0 0 var(--spacing-md);
        }
        
        .welcome-section p {
            font-size: 1.1rem;
            color: var(--gray-600);
            margin: 0;
        }
        
        /* GRILLE MODULES - DESIGN ORIGINAL AMÉLIORÉ */
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: var(--spacing-xl) 0 var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 2rem;
            background: var(--primary-blue);
            border-radius: 2px;
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }
        
        .module-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        
        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .module-available {
            border-left: 4px solid var(--success);
        }
        
        .module-dev {
            border-left: 4px solid var(--warning);
        }
        
        .module-admin {
            border-left: 4px solid var(--error);
        }
        
        .module-header {
            padding: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .module-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            font-weight: 600;
        }
        
        .module-icon-blue { background: linear-gradient(135deg, #3182ce, #2563eb); }
        .module-icon-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .module-icon-green { background: linear-gradient(135deg, #10b981, #059669); }
        .module-icon-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .module-icon-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        
        .module-meta {
            flex: 1;
        }
        
        .module-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 var(--spacing-sm);
            color: var(--gray-900);
        }
        
        .module-status {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-md);
            font-weight: 500;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-development {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-admin_only {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .module-body {
            padding: var(--spacing-lg);
            flex: 1;
        }
        
        .module-description {
            font-size: 1rem;
            color: var(--gray-600);
            margin: 0 0 var(--spacing-md);
            line-height: 1.5;
        }
        
        .module-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .module-features li {
            padding: 0.25rem 0;
            color: var(--gray-700);
            font-size: 0.9rem;
        }
        
        .module-features li::before {
            content: '✓';
            color: var(--success);
            font-weight: bold;
            margin-right: var(--spacing-sm);
        }
        
        .module-btn {
            display: inline-block;
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s ease;
            margin-top: var(--spacing-md);
            width: 100%;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-blue-dark);
        }
        
        .btn-disabled {
            background: var(--gray-200);
            color: var(--gray-600);
            cursor: not-allowed;
        }
        
        /* ADMIN STATS */
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }
        
        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        /* FOOTER */
        .portal-footer {
            background: var(--gray-100);
            padding: var(--spacing-lg);
            margin-top: var(--spacing-xl);
            border-top: 1px solid var(--gray-200);
            text-align: center;
        }
        
        .footer-version {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin: 0;
        }
        
        .footer-copyright {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin: 0.5rem 0 0;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .modules-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: var(--spacing-lg);
            }
            
            .header-container {
                flex-direction: column;
                gap: var(--spacing-md);
            }
        }
    </style>
</head>
<body>
    <header class="portal-header">
        <div class="header-container">
            <div class="brand-info">
                <h1><?= htmlspecialchars($page_title) ?></h1>
                <p><?= htmlspecialchars($page_subtitle) ?></p>
            </div>
            <div class="user-section">
                <span>👤</span>
                <span><?= htmlspecialchars($current_user['username']) ?></span>
                <span>(<?= htmlspecialchars($current_user['role']) ?>)</span>
            </div>
        </div>
    </header>

    <main class="main-content">
<?php } ?>

        <!-- Bienvenue -->
        <div class="welcome-section">
            <h2>👋 Bienvenue, <?= htmlspecialchars($current_user['username']) ?></h2>
            <p>Accédez à vos outils professionnels depuis ce portail centralisé.</p>
        </div>

        <!-- Modules disponibles -->
        <section>
            <h3 class="section-title">Modules applicatifs</h3>
            
            <div class="modules-grid">
                <?php foreach ($accessible_modules as $moduleId => $module): ?>
                <div class="module-card <?= getModuleStatusClass($module['status']) ?>" 
                     onclick="navigateToModule('<?= $moduleId ?>', '<?= $module['path'] ?>', '<?= $module['status'] ?>')">
                    
                    <div class="module-header">
                        <div class="module-icon module-icon-<?= $module['color'] ?>">
                            <?= $module['icon'] ?>
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
                        
                        <?php if ($module['status'] === 'active'): ?>
                            <a href="<?= htmlspecialchars($module['path']) ?>" class="module-btn btn-primary">
                                Accéder au module
                            </a>
                        <?php else: ?>
                            <div class="module-btn btn-disabled">
                                <?= getStatusLabel($module['status']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Stats système pour admins -->
        <?php if (in_array($current_user['role'], ['admin', 'dev'])): ?>
        <section>
            <h3 class="section-title">📊 Aperçu système</h3>
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?= count($accessible_modules) ?></div>
                    <div class="stat-label">Modules accessibles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-value"><?= htmlspecialchars($version_info['version']) ?></div>
                    <div class="stat-label">Version portail</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-value"><?= ucfirst($current_user['role']) ?></div>
                    <div class="stat-label">Niveau d'accès</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🕒</div>
                    <div class="stat-value"><?= date('H:i') ?></div>
                    <div class="stat-label">Heure serveur</div>
                </div>
            </div>
        </section>
        <?php endif; ?>

<?php
// Footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
?>
    </main>
    
    <footer class="portal-footer">
        <div class="footer-version">
            Version: <?= htmlspecialchars($version_info['version']) ?> | 
            Build: #<?= substr($version_info['build'], -8) ?> | 
            <?= htmlspecialchars($version_info['formatted_date']) ?>
        </div>
        <div class="footer-copyright">
            &copy; <?= date('Y') ?> Jean-Thomas RUNSER - Guldagil
        </div>
    </footer>

    <script>
        function navigateToModule(moduleId, path, status) {
            if (status === 'active') {
                window.location.href = path;
            } else {
                alert('Module ' + moduleId + ' : ' + status);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Portail Guldagil v<?= htmlspecialchars($version_info['version']) ?> chargé');
        });
    </script>
</body>
</html>
<?php } ?>

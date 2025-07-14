<?php
/**
 * Titre: Page d'accueil du portail Guldagil
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

// DÉTECTION AUTOMATIQUE DES CHEMINS CONFIG
// Structure O2Switch avec config à la racine du serveur
$possible_config_paths = [
    '/config/config.php',                                // O2Switch - racine serveur
    ROOT_PATH . '/config/config.php',                    // Structure standard
    __DIR__ . '/../config/config.php',                   // Relatif depuis public/
    dirname($_SERVER['DOCUMENT_ROOT']) . '/config/config.php', // Parent du document root
    $_SERVER['DOCUMENT_ROOT'] . '/../config/config.php'  // Document root parent
];

$config_path = null;
$version_path = null;

// Trouver config.php
foreach ($possible_config_paths as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        break;
    }
}

// Chargement sécurisé de la configuration
if (!$config_path) {
    // Debug amélioré pour voir la structure réelle
    $debug_info = [
        'Document Root détecté' => $_SERVER['DOCUMENT_ROOT'] ?? 'Non défini',
        'Script actuel' => __FILE__,
        'Dossier script' => __DIR__,
        'ROOT_PATH calculé' => ROOT_PATH,
        'Structure' => []
    ];
    
    // Analyser la structure des dossiers
    foreach ([ROOT_PATH, dirname(ROOT_PATH), __DIR__] as $dir) {
        if (is_dir($dir)) {
            $debug_info['Structure'][$dir] = array_filter(scandir($dir), function($item) {
                return $item !== '.' && $item !== '..';
            });
        }
    }
    
    http_response_code(500);
    echo '<h1>❌ Erreur Configuration</h1>';
    echo '<p>Fichier config.php introuvable dans :<br>' . implode('<br>', $possible_config_paths) . '</p>';
    echo '<h2>🔍 Debug Structure</h2>';
    echo '<pre>' . print_r($debug_info, true) . '</pre>';
    die();
}

// Chercher version.php dans le même dossier que config.php
if ($config_path) {
    $version_path = dirname($config_path) . '/version.php';
    if (!file_exists($version_path)) {
        // Autres emplacements possibles pour O2Switch
        $possible_version_paths = [
            '/config/version.php',
            ROOT_PATH . '/config/version.php',
            dirname($_SERVER['DOCUMENT_ROOT']) . '/config/version.php'
        ];
        
        foreach ($possible_version_paths as $path) {
            if (file_exists($path)) {
                $version_path = $path;
                break;
            }
        }
    }
}

try {
    require_once $config_path;
    
    if ($version_path && file_exists($version_path)) {
        require_once $version_path;
    }
} catch (Exception $e) {
    http_response_code(500);
    $error_msg = $is_production ? 'Erreur de configuration' : htmlspecialchars($e->getMessage());
    die('<h1>❌ Erreur</h1><p>' . $error_msg . '</p>');
}

// Vérification BDD
if (!isset($db) || !($db instanceof PDO)) {
    http_response_code(500);
    die('<h1>❌ Erreur Base de Données</h1><p>Connexion non disponible</p>');
}

// AUTHENTIFICATION - DÉSACTIVÉE TEMPORAIREMENT POUR TESTS
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $user_authenticated ? ($_SESSION['user'] ?? null) : null;

// BYPASS TEMPORAIRE - Commentez ces lignes une fois l'auth réparée
if (!$user_authenticated) {
    // Créer utilisateur temporaire pour éviter la boucle de redirection
    $current_user = [
        'username' => 'Demo User',
        'role' => 'admin'  // Admin pour accès complet pendant les tests
    ];
    $user_authenticated = true;
    
    // Message de debug (à supprimer en production)
    $demo_mode = true;
}

// Sécurisation utilisateur par défaut
if (!$current_user) {
    $current_user = [
        'username' => 'Utilisateur',
        'role' => 'user'
    ];
}

// ============================================
// VARIABLES POUR LES TEMPLATES HEADER/FOOTER
// ============================================

// Informations de la page
$page_title = 'Portail Guldagil';
$page_subtitle = 'Solutions professionnelles';
$page_description = 'Portail de gestion - Calcul frais, ADR, contrôle qualité';
$current_module = 'home';

// Variables pour le header
$app_name = 'Guldagil';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$module_css = false;
$module_js = false;

// Variables pour le footer avec fallbacks
$version_info = [
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5-beta',
    'build' => defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000',
    'short_build' => defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : '????????',
    'date' => defined('BUILD_DATE') ? BUILD_DATE : date('Y-m-d'),
    'year' => date('Y')
];
$show_admin_footer = true;

// Fil d'Ariane
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

$nav_info = 'Tableau de bord principal';

// Configuration des niveaux d'accès
$roles = ['guest' => 0, 'user' => 1, 'manager' => 2, 'admin' => 3, 'dev' => 4];
$user_level = $roles[$current_user['role']] ?? 0;

// Modules disponibles - TOUS LES MODULES
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'status_label' => 'OPÉRATIONNEL',
        'path' => '/port/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique'],
        'min_level' => 1,
        'estimated_completion' => '100%'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses - Déclarations et suivi réglementaire',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'active',
        'status_label' => 'OPÉRATIONNEL',
        'path' => '/adr/',
        'features' => ['Déclarations ADR', 'Gestion des quotas', 'Suivi réglementaire'],
        'min_level' => 1,
        'estimated_completion' => '95%'
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
        'icon' => '✅',
        'color' => 'green',
        'status' => 'active',
        'status_label' => 'OPÉRATIONNEL',
        'path' => '/qualite/',
        'features' => ['Tests et validations', 'Rapports de conformité', 'Suivi des équipements'],
        'min_level' => 1,
        'estimated_completion' => '85%'
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection individuelle - Stock et maintenance',
        'icon' => '🛡️',
        'color' => 'purple',
        'status' => 'active',
        'status_label' => 'OPÉRATIONNEL',
        'path' => '/epi/',
        'features' => ['Inventaire EPI', 'Suivi des dates d\'expiration', 'Gestion des commandes'],
        'min_level' => 1,
        'estimated_completion' => '75%'
    ],
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion des outils et équipements techniques - Maintenance et traçabilité',
        'icon' => '🔧',
        'color' => 'gray',
        'status' => 'development',
        'status_label' => 'EN DÉVELOPPEMENT',
        'path' => '/outillages/',
        'features' => ['Inventaire outillage', 'Planning maintenance', 'Suivi d\'utilisation'],
        'min_level' => 1,
        'estimated_completion' => '40%'
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et gestion globale du portail - Réservé aux administrateurs',
        'icon' => '⚙️',
        'color' => 'red',
        'status' => 'restricted',
        'status_label' => 'ADMINISTRATEURS',
        'path' => '/admin/',
        'features' => ['Configuration système', 'Gestion utilisateurs', 'Maintenance'],
        'min_level' => 3,
        'estimated_completion' => '90%'
    ]
];

// Filtrer les modules selon les droits d'accès
$accessible_modules = array_filter($available_modules, function($module) use ($user_level) {
    return $user_level >= $module['min_level'];
});

// Statistiques détaillées
$stats = [
    'modules_total' => count($available_modules),
    'modules_accessible' => count($accessible_modules),
    'modules_active' => count(array_filter($available_modules, fn($m) => $m['status'] === 'active')),
    'modules_dev' => count(array_filter($available_modules, fn($m) => $m['status'] === 'development')),
    'user_role' => $current_user['role'],
    'user_level' => $user_level
];

// Fonctions utilitaires
function getStatusLabel($status) {
    return match($status) {
        'active' => 'Disponible',
        'development' => 'En développement',
        'restricted' => 'Accès restreint',
        'maintenance' => 'Maintenance',
        default => 'Non disponible'
    };
}

function getModuleStatusClass($status) {
    return match($status) {
        'active' => 'module-available',
        'development' => 'module-dev',
        'restricted' => 'module-restricted',
        'maintenance' => 'module-maintenance',
        default => 'module-disabled'
    };
}

// Inclure le header SANS CSS inline
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($page_title) ?></title>
        <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
        <meta name="author" content="<?= htmlspecialchars($app_author) ?>">
        <meta name="robots" content="noindex, nofollow">
        <link rel="icon" type="image/png" href="/assets/img/favicon.png">
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .header { background: #2563eb; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
            .demo-warning { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 1rem; border-radius: 4px; margin-bottom: 20px; }
            .modules-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
            .module-card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
            .module-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
            .btn { display: inline-block; padding: 8px 16px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px; }
            .btn:hover { background: #1d4ed8; }
        </style>
    </head>
    <body>
        <header class="header">
            <h1><?= htmlspecialchars($app_name) ?></h1>
            <div>
                Connecté: <?= htmlspecialchars($current_user['username']) ?> 
                (<?= htmlspecialchars($current_user['role']) ?>)
                <?php if (!$user_authenticated): ?>
                | <a href="/auth/login.php" style="color: white;">Se connecter</a>
                <?php endif; ?>
            </div>
        </header>
    <?php
}
?>

<div class="container">
    <!-- Message de mode démo -->
    <?php if (isset($demo_mode)): ?>
    <div class="demo-warning">
        <strong>⚠️ MODE DÉMO ACTIVÉ</strong> - Authentification désactivée pour les tests. 
        Réactivez l'authentification une fois les chemins corrigés.
        <br><small>Chemins détectés : Config = <?= htmlspecialchars($config_path) ?> | Version = <?= htmlspecialchars($version_path ?: 'Non trouvé') ?></small>
    </div>
    <?php endif; ?>

    <!-- Section de bienvenue -->
    <section class="welcome-section">
        <div class="welcome-header">
            <h1 class="welcome-title">
                Bienvenue, <?= htmlspecialchars($current_user['username']) ?>
            </h1>
            <p class="welcome-subtitle">
                <?= htmlspecialchars($page_description) ?>
            </p>
            <div class="version-badge">
                Version <?= htmlspecialchars($version_info['version']) ?> 
                <span class="build-info">(Build #<?= htmlspecialchars($version_info['short_build']) ?>)</span>
            </div>
        </div>
    </section>

    <!-- Statistiques système -->
    <section class="stats-section">
        <h2>📊 Aperçu système</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
            <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #2563eb;"><?= $stats['modules_accessible'] ?></div>
                <div>Modules accessibles</div>
            </div>
            <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #059669;"><?= $stats['modules_active'] ?></div>
                <div>Modules actifs</div>
            </div>
            <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #7c3aed;"><?= htmlspecialchars($version_info['version']) ?></div>
                <div>Version portail</div>
            </div>
            <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #dc2626;"><?= ucfirst($current_user['role']) ?></div>
                <div>Niveau d'accès</div>
            </div>
        </div>
    </section>

    <!-- Navigation modules -->
    <section class="modules-section">
        <h2>🚀 Vos modules</h2>
        
        <div class="modules-grid">
            <?php foreach ($accessible_modules as $module_key => $module): ?>
            <div class="module-card">
                <div class="module-header">
                    <div style="font-size: 2rem;"><?= $module['icon'] ?></div>
                    <div>
                        <h3 style="margin: 0;"><?= htmlspecialchars($module['name']) ?></h3>
                        <span style="background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                            <?= $module['status_label'] ?>
                        </span>
                    </div>
                    <div style="margin-left: auto;">
                        <span style="font-weight: bold;"><?= $module['estimated_completion'] ?></span>
                    </div>
                </div>
                
                <div class="module-body">
                    <p><?= htmlspecialchars($module['description']) ?></p>
                    
                    <div style="margin-top: 15px;">
                        <h4 style="margin: 0 0 10px 0;">Fonctionnalités :</h4>
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($module['features'] as $feature): ?>
                            <li><?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <?php if ($module['status'] === 'active'): ?>
                        <a href="<?= htmlspecialchars($module['path']) ?>" class="btn">
                            Accéder au module
                        </a>
                    <?php elseif ($module['status'] === 'development'): ?>
                        <button class="btn" style="background: #6b7280;" disabled>
                            En développement
                        </button>
                    <?php elseif ($module['status'] === 'restricted'): ?>
                        <a href="<?= htmlspecialchars($module['path']) ?>" class="btn" style="background: #dc2626;">
                            Accès administrateur
                        </a>
                    <?php else: ?>
                        <button class="btn" style="background: #6b7280;" disabled>
                            Non disponible
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Actions rapides -->
    <section style="margin-top: 40px;">
        <h2>⚡ Actions rapides</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <a href="/port/" class="btn" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem;">🧮</div>
                <div>Nouveau calcul</div>
            </a>
            <a href="/adr/" class="btn" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem;">⚠️</div>
                <div>Déclaration ADR</div>
            </a>
            <a href="/qualite/" class="btn" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem;">✅</div>
                <div>Contrôle qualité</div>
            </a>
            <?php if ($user_level >= 3): ?>
            <a href="/admin/" class="btn" style="text-align: center; padding: 20px; background: #dc2626;">
                <div style="font-size: 2rem;">⚙️</div>
                <div>Administration</div>
            </a>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php
// Inclure le footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    ?>
    <footer style="background: #374151; color: white; padding: 20px; margin-top: 40px; text-align: center;">
        <div>
            <h4><?= htmlspecialchars($app_name) ?></h4>
            <p><?= htmlspecialchars($page_subtitle) ?></p>
            <div style="font-size: 0.9rem; margin-top: 10px;">
                Version <?= htmlspecialchars($version_info['version']) ?> - Build #<?= htmlspecialchars($version_info['short_build']) ?>
                <br>Compilé le <?= htmlspecialchars($version_info['date']) ?>
                <br>© <?= $version_info['year'] ?> <?= htmlspecialchars($app_author) ?> - Tous droits réservés
            </div>
        </div>
    </footer>
    </body>
    </html>
    <?php
}
?>

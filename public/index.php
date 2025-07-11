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

// Vérification BDD
if (!isset($db) || !($db instanceof PDO)) {
    http_response_code(500);
    die('<h1>❌ Erreur Base de Données</h1><p>Connexion non disponible</p>');
}

// AUTHENTIFICATION - AUCUN BYPASS
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

// Variables pour le footer
$version_info = [
    'version' => APP_VERSION,
    'build' => BUILD_NUMBER,
    'short_build' => substr(BUILD_NUMBER, 0, 8),
    'date' => BUILD_DATE,
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
        <link rel="stylesheet" href="/assets/css/portal.css?v=<?= htmlspecialchars($version_info['short_build']) ?>">
    </head>
    <body>
        <header class="portal-header">
            <div class="header-container container">
                <a href="/" class="header-brand">
                    <h1 class="portal-title"><?= htmlspecialchars($app_name) ?></h1>
                </a>
                <div class="user-menu">
                    <span class="user-info">
                        Connecté : <?= htmlspecialchars($current_user['username']) ?> 
                        <span class="user-role">(<?= htmlspecialchars($current_user['role']) ?>)</span>
                    </span>
                    <a href="/auth/logout.php" class="logout-btn">Déconnexion</a>
                </div>
            </div>
        </header>
    <?php
}
?>

<!-- ===============================
     CONTENU PRINCIPAL COMPLET
     =============================== -->
<main class="portal-main">
    <div class="container">
        
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
            <h2 class="section-title">
                <span class="section-icon">📊</span>
                Aperçu système
            </h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $stats['modules_accessible'] ?></div>
                        <div class="stat-label">Modules accessibles</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🚀</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $stats['modules_active'] ?></div>
                        <div class="stat-label">Modules actifs</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= htmlspecialchars($version_info['version']) ?></div>
                        <div class="stat-label">Version portail</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= ucfirst($current_user['role']) ?></div>
                        <div class="stat-label">Niveau d'accès</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Navigation modules -->
        <section class="modules-section">
            <h2 class="section-title">
                <span class="section-icon">🚀</span>
                Vos modules
            </h2>
            
            <div class="modules-grid">
                <?php foreach ($accessible_modules as $module_key => $module): ?>
                <div class="module-card <?= getModuleStatusClass($module['status']) ?> module-<?= $module['color'] ?>">
                    <div class="module-header">
                        <div class="module-icon"><?= $module['icon'] ?></div>
                        <div class="module-info">
                            <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                            <span class="module-status status-<?= $module['status'] ?>">
                                <?= $module['status_label'] ?>
                            </span>
                        </div>
                        <div class="module-progress">
                            <span class="progress-text"><?= $module['estimated_completion'] ?></span>
                        </div>
                    </div>
                    
                    <div class="module-body">
                        <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                        
                        <div class="module-features">
                            <h4>Fonctionnalités :</h4>
                            <ul>
                                <?php foreach ($module['features'] as $feature): ?>
                                <li><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="module-footer">
                        <?php if ($module['status'] === 'active'): ?>
                            <a href="<?= htmlspecialchars($module['path']) ?>" class="btn btn-primary">
                                Accéder au module
                            </a>
                        <?php elseif ($module['status'] === 'development'): ?>
                            <button class="btn btn-disabled" disabled>
                                En développement
                            </button>
                        <?php elseif ($module['status'] === 'restricted'): ?>
                            <a href="<?= htmlspecialchars($module['path']) ?>" class="btn btn-warning">
                                Accès administrateur
                            </a>
                        <?php else: ?>
                            <button class="btn btn-disabled" disabled>
                                Non disponible
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section informations -->
        <section class="info-section">
            <div class="info-grid">
                <div class="info-card">
                    <h3 class="info-title">
                        <span class="info-icon">🛡️</span>
                        Sécurité
                    </h3>
                    <ul class="info-list">
                        <li>Authentification obligatoire</li>
                        <li>Sessions sécurisées</li>
                        <li>Contrôle d'accès par rôles</li>
                        <li>Logs d'activité</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3 class="info-title">
                        <span class="info-icon">🔧</span>
                        Support
                    </h3>
                    <ul class="info-list">
                        <li>Documentation intégrée</li>
                        <li>Système de maintenance</li>
                        <li>Sauvegarde automatique</li>
                        <li>Monitoring système</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3 class="info-title">
                        <span class="info-icon">⚡</span>
                        Performance
                    </h3>
                    <ul class="info-list">
                        <li>Cache intelligent</li>
                        <li>Optimisation BDD</li>
                        <li>Compression assets</li>
                        <li>CDN pour les ressources</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Messages utilisateur -->
        <?php if (isset($_GET['msg'])): ?>
        <section class="messages-section">
            <?php if ($_GET['msg'] === 'login_success'): ?>
            <div class="alert alert-success">
                <strong>✅ Connexion réussie !</strong> Bienvenue sur le portail.
            </div>
            <?php elseif ($_GET['msg'] === 'logout_success'): ?>
            <div class="alert alert-info">
                <strong>ℹ️ Déconnexion effectuée.</strong> À bientôt !
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Actions rapides -->
        <section class="quick-actions">
            <h2 class="section-title">
                <span class="section-icon">⚡</span>
                Actions rapides
            </h2>
            <div class="actions-grid">
                <a href="/port/" class="action-card">
                    <div class="action-icon">🧮</div>
                    <div class="action-text">Nouveau calcul</div>
                </a>
                <a href="/adr/" class="action-card">
                    <div class="action-icon">⚠️</div>
                    <div class="action-text">Déclaration ADR</div>
                </a>
                <a href="/qualite/" class="action-card">
                    <div class="action-icon">✅</div>
                    <div class="action-text">Contrôle qualité</div>
                </a>
                <?php if ($user_level >= 3): ?>
                <a href="/admin/" class="action-card admin-only">
                    <div class="action-icon">⚙️</div>
                    <div class="action-text">Administration</div>
                </a>
                <?php endif; ?>
            </div>
        </section>

    </div>
</main>

<?php
// Inclure le footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    ?>
    <footer class="portal-footer">
        <div class="footer-container container">
            <div class="footer-brand">
                <h4 class="footer-title"><?= htmlspecialchars($app_name) ?></h4>
                <p class="footer-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
            </div>
            
            <div class="footer-info">
                <div class="version-info">
                    Version <?= htmlspecialchars($version_info['version']) ?> - Build #<?= htmlspecialchars($version_info['short_build']) ?>
                </div>
                <div class="build-info">
                    Compilé le <?= htmlspecialchars($version_info['date']) ?>
                </div>
            </div>
            
            <div class="footer-copyright">
                © <?= $version_info['year'] ?> <?= htmlspecialchars($app_author) ?><br>
                Tous droits réservés
            </div>
        </div>
    </footer>
    </body>
    </html>
    <?php
}
?>

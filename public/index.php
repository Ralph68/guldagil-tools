<?php
/**
 * Titre: Page d'accueil du portail Guldagil - SÉCURISÉE
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Définir ROOT_PATH pour sécurité
define('ROOT_PATH', dirname(__DIR__));

// ========================================
// 🔐 AUTHENTIFICATION OBLIGATOIRE - DÉBUT
// ========================================

// Gestion session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$is_debug = defined('DEBUG') && DEBUG;

// VÉRIFICATION AUTHENTIFICATION
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

// 🚨 REDIRECTION FORCÉE SI NON CONNECTÉ
if (!$user_authenticated) {
    // Headers sécurité anti-cache
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    
    // Redirection vers login
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit('Redirection vers authentification...');
}

// ========================================
// 🔐 AUTHENTIFICATION OBLIGATOIRE - FIN
// ========================================

// [RESTE DE VOTRE CODE EXISTANT...]

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

$nav_info = 'Tableau de bord principal - Connecté : ' . htmlspecialchars($current_user['username'] ?? 'Utilisateur');
$show_admin_footer = true;

// Modules disponibles selon le rôle utilisateur
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'path' => '/calculateur/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export'],
        'access_level' => 'user'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Classification et étiquetage des marchandises dangereuses',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'active',
        'path' => '/adr/',
        'features' => ['Classification ONU', 'Étiquetage automatique', 'Réglementation'],
        'access_level' => 'user'
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et gestion du portail',
        'icon' => '⚙️',
        'color' => 'gray',
        'status' => 'active',
        'path' => '/admin/',
        'features' => ['Gestion tarifs', 'Utilisateurs', 'Configuration'],
        'access_level' => 'admin'
    ]
];

// Filtrer modules selon le rôle
$user_role = $current_user['role'] ?? 'user';
if ($user_role !== 'admin') {
    unset($available_modules['admin']);
}

// Vérifier connexion base de données si nécessaire
$db_status = 'unknown';
try {
    if (function_exists('getDB')) {
        $db = getDB();
        $db_status = 'connected';
    }
} catch (Exception $e) {
    $db_status = 'error';
    if ($is_debug) {
        $nav_info .= ' | BDD: ' . $e->getMessage();
    }
}

// Inclure le header (template)
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Header minimal intégré si template manquant
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title . ' - ' . $app_name) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #3182ce; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 20px; }
        .modules { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .module { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 0.9rem; }
        .logout { float: right; color: #fff; text-decoration: none; }
        .logout:hover { text-decoration: underline; }
    </style>
</head>
<body>
<?php } ?>

<!-- CONTENU PRINCIPAL -->
<div class="container">
    
    <div class="header">
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <p><?= htmlspecialchars($page_subtitle) ?></p>
        <p>Connecté : <strong><?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?></strong> (<?= htmlspecialchars($user_role) ?>)</p>
        <a href="/auth/logout.php" class="logout">🚪 Déconnexion</a>
    </div>

    <?php if ($is_debug): ?>
    <div style="background: #fef3cd; border: 1px solid #f6cc0d; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <h3>🔧 Mode Développement</h3>
        <p>Version: <?= $app_version ?> | Build: <?= $build_number ?></p>
        <p>BDD: <?= $db_status ?> | Auth: <?= $user_authenticated ? 'OK' : 'KO' ?></p>
    </div>
    <?php endif; ?>

    <div class="modules">
        <?php foreach ($available_modules as $module_id => $module): ?>
        <div class="module">
            <h3><?= $module['icon'] ?> <?= htmlspecialchars($module['name']) ?></h3>
            <p><?= htmlspecialchars($module['description']) ?></p>
            <ul>
                <?php foreach ($module['features'] as $feature): ?>
                <li><?= htmlspecialchars($feature) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= htmlspecialchars($module['path']) ?>" 
               style="display: inline-block; background: #3182ce; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;">
                Accéder →
            </a>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- FOOTER -->
<div class="footer">
    <p><?= htmlspecialchars($app_name) ?> - Version <?= htmlspecialchars($app_version) ?> - Build <?= htmlspecialchars($build_number) ?></p>
    <p>© <?= date('Y') ?> <?= defined('APP_AUTHOR') ? htmlspecialchars(APP_AUTHOR) : 'Jean-Thomas RUNSER' ?></p>
    <?php if ($is_debug): ?>
    <p style="color: #e53e3e;">⚠️ Mode développement actif</p>
    <?php endif; ?>
</div>

<?php
// Inclure le footer si template disponible
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    echo '</body></html>';
}
?>

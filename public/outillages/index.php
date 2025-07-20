<?php
/**
 * Titre: Page d'accueil module Outillages - Version Production
 * Chemin: /public/outillages/index.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// CONFIGURATION ET SÉCURITÉ
// =====================================
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Session et authentification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification authentification
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once ROOT_PATH . '/config/modules.php';

// Variables pour template
$page_title = 'Module Outillages';
$page_subtitle = 'Gestion des outils et équipements';
$page_description = 'Gestion complète de l\'outillage - Inventaire, attributions, demandes et maintenance';
$current_module = 'outillages';
$module_css = true;
$user_authenticated = true;
$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🔧', 'text' => 'Module Outillages', 'url' => '/outillages/', 'active' => true]
];

// =====================================
// VÉRIFICATION ACCÈS MODULE
// =====================================
$user_role = $current_user['role'] ?? 'guest';
$module_data = $modules['outillages'] ?? ['status' => 'development', 'name' => 'Outillages'];

if (!canAccessModule('outillages', $module_data, $user_role)) {
    header('Location: ../../index.php?error=access_denied');
    exit;
}

// =====================================
// INITIALISATION MODULE
// =====================================
require_once './classes/OutillageManager.php';

$outillageManager = new OutillageManager();
$moduleStatus = $outillageManager->getModuleStatus();

// Auto-installation si nécessaire
if (!$moduleStatus['tables_exist'] && $moduleStatus['database_connected']) {
    $installation_result = $outillageManager->install();
    if ($installation_result) {
        $moduleStatus = $outillageManager->getModuleStatus();
        $install_message = "✅ Module Outillages installé avec succès !";
    } else {
        $install_error = "❌ Erreur lors de l'installation du module";
    }
}

// =====================================
// GESTION DES ACTIONS
// =====================================
$action = $_GET['action'] ?? 'dashboard';
$allowed_actions = ['dashboard', 'inventory', 'employees', 'demandes', 'reports'];

if (!in_array($action, $allowed_actions)) {
    $action = 'dashboard';
}

// Variables pour template
$build_number = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Portail Guldagil</title>
    
    <!-- CSS principal OBLIGATOIRE -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <!-- CSS module -->
    <link rel="stylesheet" href="./assets/css/outillage.css?v=<?= $build_number ?>">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include ROOT_PATH . '/templates/header.php'; ?>
    
    <main class="main-container">
        <!-- Navigation module -->
        <div class="module-nav">
            <nav class="module-tabs">
                <a href="?action=dashboard" class="tab-link <?= $action === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?action=inventory" class="tab-link <?= $action === 'inventory' ? 'active' : '' ?>">
                    <i class="fas fa-boxes"></i> Inventaire
                </a>
                <a href="?action=demandes" class="tab-link <?= $action === 'demandes' ? 'active' : '' ?>">
                    <i class="fas fa-paper-plane"></i> Demandes
                </a>
                <?php if (in_array($user_role, ['admin', 'dev'])): ?>
                <a href="?action=employees" class="tab-link <?= $action === 'employees' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Employés
                </a>
                <a href="?action=reports" class="tab-link <?= $action === 'reports' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> Rapports
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Messages d'installation -->
        <?php if (isset($install_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($install_message) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($install_error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($install_error) ?>
        </div>
        <?php endif; ?>

        <!-- Statut du module -->
        <?php if (!$moduleStatus['database_connected']): ?>
        <div class="alert alert-warning">
            <i class="fas fa-database"></i>
            <strong>Base de données non connectée</strong> - Module en mode démonstration
        </div>
        <?php elseif (!$moduleStatus['tables_exist']): ?>
        <div class="alert alert-info">
            <i class="fas fa-wrench"></i>
            <strong>Installation requise</strong> - Le module va s'installer automatiquement
        </div>
        <?php endif; ?>

        <!-- Contenu principal -->
        <div class="module-content">
            <?php
            switch ($action) {
                case 'dashboard':
                    include './components/dashboard.php';
                    break;
                    
                case 'inventory':
                    include './components/inventory.php';
                    break;
                    
                case 'demandes':
                    include './components/demandes.php';
                    break;
                    
                case 'employees':
                    if (in_array($user_role, ['admin', 'dev'])) {
                        include './components/employees.php';
                    } else {
                        echo '<div class="alert alert-danger">Accès non autorisé</div>';
                    }
                    break;
                    
                case 'reports':
                    if (in_array($user_role, ['admin', 'dev'])) {
                        include './components/reports.php';
                    } else {
                        echo '<div class="alert alert-danger">Accès non autorisé</div>';
                    }
                    break;
                    
                default:
                    echo '<div class="alert alert-warning">Page non trouvée</div>';
            }
            ?>
        </div>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>

    <script>
        // Configuration module
        window.OutillageModule = {
            status: <?= json_encode($moduleStatus) ?>,
            userRole: '<?= $user_role ?>',
            currentAction: '<?= $action ?>',
            baseUrl: './api/'
        };

        console.log('🔧 Module Outillages v0.5 beta');
        console.log('📊 Statut:', window.OutillageModule.status);
    </script>
</body>
</html>

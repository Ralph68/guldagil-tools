<?php
/**
 * Titre: Module Matériel - Index corrigé final
 * Chemin: /public/materiel/index.php
 * Version: 0.5 beta + build auto - CORRIGÉ COMPLET
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Variables pour template
$page_title = 'Gestion du Matériel';
$page_subtitle = 'Outillage et Équipements';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// Authentification
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Connexion BDD avec gestion d'erreurs
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
} catch (Exception $e) {
    $db_connected = false;
    error_log("Erreur BDD Matériel: " . $e->getMessage());
}

// Statistiques robustes
$stats = [
    'total_materiel' => 0,
    'materiel_attribue' => 0,
    'demandes_attente' => 0,
    'maintenance_due' => 0
];

if ($db_connected) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM materiel_items");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats['total_materiel'] = $result['total'] ?? 0;
        }
        
        $stmt = $db->query("SELECT COUNT(*) as attribues FROM materiel_attributions WHERE etat_attribution = 'active'");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats['materiel_attribue'] = $result['attribues'] ?? 0;
        }
        
        $stmt = $db->query("SELECT COUNT(*) as en_attente FROM materiel_demandes WHERE statut = 'en_attente'");
        if ($stmt) {
            $result = $stmt->fetch();
            $stats['demandes_attente'] = $result['en_attente'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Erreur stats matériel: " . $e->getMessage());
    }
}

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/'],
    ['icon' => '🔧', 'text' => 'Matériel', 'url' => '', 'active' => true]
];

$build_number = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis');

include ROOT_PATH . '/templates/header.php';
?>

<!-- Fil d'ariane sticky -->
<div class="breadcrumb-container sticky-top">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <?php foreach ($breadcrumbs as $crumb): ?>
                <?php if (isset($crumb['active']) && $crumb['active']): ?>
                    <li class="breadcrumb-item active">
                        <?= $crumb['icon'] ?> <?= htmlspecialchars($crumb['text']) ?>
                    </li>
                <?php else: ?>
                    <li class="breadcrumb-item">
                        <a href="<?= $crumb['url'] ?>">
                            <?= $crumb['icon'] ?> <?= htmlspecialchars($crumb['text']) ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>

<main class="main-content">
    <div class="container">
        <!-- En-tête du module -->
        <div class="module-header">
            <div class="module-title">
                <h1>🔧 <?= htmlspecialchars($page_title) ?></h1>
                <p class="module-description"><?= htmlspecialchars($page_subtitle) ?></p>
            </div>
            
            <div class="module-actions">
                <a href="./inventory/" class="btn btn-outline">
                    <i class="fas fa-boxes"></i> Inventaire
                </a>
                <a href="./requests/create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle demande
                </a>
            </div>
        </div>

        <?php if (!$db_connected): ?>
        <div class="alert alert-danger">
            <strong>⚠️ Problème de connexion base de données</strong><br>
            <a href="./debug.php" class="btn btn-sm btn-info" style="margin-top: 10px;">Diagnostic</a>
        </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-overview">
            <div class="stat-box">
                <div class="stat-number"><?= $stats['total_materiel'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-tools"></i> Total matériel
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['materiel_attribue'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-user-check"></i> Matériel attribué
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['demandes_attente'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-clock"></i> Demandes en attente
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['maintenance_due'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-wrench"></i> Maintenance due
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="main-content-area">
                <!-- Actions rapides -->
                <div class="quick-actions">
                    <h3>Actions rapides</h3>
                    <div class="actions-grid">
                        <a href="./inventory/" class="action-card">
                            <i class="fas fa-boxes"></i>
                            <h4>Inventaire</h4>
                            <p>Voir tout le matériel disponible</p>
                        </a>
                        
                        <a href="./requests/create.php" class="action-card">
                            <i class="fas fa-plus-circle"></i>
                            <h4>Faire une demande</h4>
                            <p>Demander du nouveau matériel</p>
                        </a>
                        
                        <a href="/admin/" class="action-card">
                            <i class="fas fa-cogs"></i>
                            <h4>Administration</h4>
                            <p>Gérer les catégories et modèles</p>
                        </a>
                        
                        <a href="./reports/" class="action-card">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Rapports</h4>
                            <p>Statistiques et analyses</p>
                        </a>
                    </div>
                </div>

                <?php if ($db_connected && $stats['total_materiel'] > 0): ?>
                <div class="alert alert-success">
                    <strong>✅ Module fonctionnel</strong><br>
                    Base de données connectée, <?= $stats['total_materiel'] ?> éléments trouvés.
                </div>
                <?php elseif ($db_connected): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Base de données connectée</strong><br>
                    <?= $stats['total_materiel'] ?> éléments dans l'inventaire.
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-section">
                    <h3>Informations</h3>
                    <div class="info-item">
                        <strong>Module :</strong> Matériel v<?= defined('VERSION') ? VERSION : '0.5' ?>
                    </div>
                    <div class="info-item">
                        <strong>Base de données :</strong>
                        <span class="badge badge-<?= $db_connected ? 'success' : 'danger' ?>">
                            <?= $db_connected ? 'Connectée' : 'Erreur' ?>
                        </span>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Liens rapides</h3>
                    <a href="/admin/scanner.php" class="btn btn-outline btn-sm" style="width: 100%; margin: 5px 0;">
                        <i class="fas fa-search"></i> Scanner système
                    </a>
                    <a href="./debug.php" class="btn btn-info btn-sm" style="width: 100%; margin: 5px 0;">
                        <i class="fas fa-wrench"></i> Debug module
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include ROOT_PATH . '/templates/footer.php'; ?>

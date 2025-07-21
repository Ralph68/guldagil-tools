<?php
/**
 * Titre: Dashboard Module Matériel - Version corrigée
 * Chemin: /public/materiel/dashboard.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once ROOT_PATH . '/config/modules.php';

// Variables pour template
$page_title = 'Gestion du Matériel';
$page_subtitle = 'Outillage et Équipements';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// Vérification authentification
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = $current_user['role'] ?? 'guest';
$module_data = $modules['materiel'] ?? ['status' => 'development', 'name' => 'Matériel'];

if (!canAccessModule('materiel', $module_data, $user_role)) {
    header('Location: ../../index.php?error=access_denied');
    exit;
}

// Connexion BDD
try {
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
} catch (Exception $e) {
    error_log("Erreur BDD Matériel: " . $e->getMessage());
    $db_connected = false;
}

// Initialisation MaterielManager
require_once __DIR__ . '/classes/MaterielManager.php';
$materielManager = new MaterielManager($db);

// Auto-installation si nécessaire
if ($db_connected) {
    $materielManager->install();
}

// Récupération des données
$stats = [];
$demandesEnAttente = [];

if ($db_connected) {
    try {
        // Statistiques avec les bonnes tables
        $stmt = $db->query("SELECT COUNT(*) as total FROM materiel_items");
        $stats['total_materiel'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(*) as attribues FROM materiel_attributions WHERE etat_attribution = 'active'");
        $stats['materiel_attribue'] = $stmt->fetch()['attribues'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(*) as en_attente FROM materiel_demandes WHERE statut = 'en_attente'");
        $stats['demandes_attente'] = $stmt->fetch()['en_attente'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(*) as maintenance FROM materiel_items WHERE prochaine_maintenance <= CURDATE() AND prochaine_maintenance IS NOT NULL");
        $stats['maintenance_due'] = $stmt->fetch()['maintenance'] ?? 0;
        
        // Dernières demandes
        $demandesEnAttente = $materielManager->getDemandesEnAttente(5);
        
    } catch (Exception $e) {
        error_log("Erreur dashboard matériel: " . $e->getMessage());
        $stats = ['total_materiel' => 0, 'materiel_attribue' => 0, 'demandes_attente' => 0, 'maintenance_due' => 0];
    }
} else {
    $stats = ['total_materiel' => 0, 'materiel_attribue' => 0, 'demandes_attente' => 0, 'maintenance_due' => 0];
}

// Configuration des droits
$isResponsable = in_array($user_role, ['admin', 'dev']);
$canValidateRequests = $isResponsable;

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
    
    <!-- CSS module corrigé -->
    <link rel="stylesheet" href="./assets/css/materiel.css?v=<?= $build_number ?>">
    
    <!-- Font Awesome et Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
    .dashboard-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; margin: 2rem 0; }
    .main-content { min-height: 600px; }
    .sidebar { background: #f8f9fa; padding: 1.5rem; border-radius: 12px; }
    .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
    .stat-box { background: white; padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--primary-blue); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-number { font-size: 2rem; font-weight: 700; color: var(--primary-blue); }
    .stat-label { color: #666; font-size: 0.9rem; margin-top: 0.5rem; }
    .chart-container { background: white; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .alert-item { display: flex; align-items: center; padding: 1rem; border-radius: 6px; margin: 0.5rem 0; }
    .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
    .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
    .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; }
    .request-card { background: white; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; margin: 0.5rem 0; }
    .request-meta { font-size: 0.85rem; color: #666; margin: 0.5rem 0; }
    .btn-group { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
    .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.8rem; }
    .agence-item { display: flex; justify-content: space-between; padding: 0.5rem; border-bottom: 1px solid #eee; }
    .progress-bar { width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; margin: 0.5rem 0; }
    .progress-fill { height: 100%; background: var(--primary-blue); transition: width 0.3s; }
    </style>
</head>
<body>
    <?php include ROOT_PATH . '/templates/header.php'; ?>
    
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

            <!-- Statistiques globales -->
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

            <!-- Layout principal -->
            <div class="dashboard-grid">
                <!-- Contenu principal -->
                <div class="main-content">
                    <!-- Alertes -->
                    <?php if ($stats['maintenance_due'] > 0): ?>
                    <div class="alert-item alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?= $stats['maintenance_due'] ?> équipement(s) nécessitent une maintenance</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($stats['demandes_attente'] > 0): ?>
                    <div class="alert-item alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span><?= $stats['demandes_attente'] ?> demande(s) en attente de validation</span>
                    </div>
                    <?php endif; ?>

                    <!-- Graphiques -->
                    <div class="chart-container">
                        <h3>Répartition du matériel</h3>
                        <canvas id="materielChart" width="400" height="200"></canvas>
                    </div>

                    <!-- Actions rapides selon le rôle -->
                    <div class="quick-actions">
                        <h3>Actions rapides</h3>
                        <div class="action-grid">
                            <a href="./inventory/" class="action-card">
                                <i class="fas fa-search"></i>
                                <h4>Consulter l'inventaire</h4>
                                <p>Voir tout le matériel disponible</p>
                            </a>
                            
                            <a href="./requests/create.php" class="action-card">
                                <i class="fas fa-plus-circle"></i>
                                <h4>Faire une demande</h4>
                                <p>Demander du nouveau matériel</p>
                            </a>
                            
                            <?php if ($isResponsable): ?>
                            <a href="./admin/" class="action-card">
                                <i class="fas fa-cogs"></i>
                                <h4>Administration</h4>
                                <p>Gérer les catégories et modèles</p>
                            </a>
                            <?php endif; ?>
                            
                            <a href="./reports/" class="action-card">
                                <i class="fas fa-chart-bar"></i>
                                <h4>Rapports</h4>
                                <p>Statistiques et analyses</p>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Demandes récentes -->
                    <div class="sidebar-section">
                        <h3>Dernières demandes</h3>
                        <?php if (!empty($demandesEnAttente)): ?>
                            <?php foreach ($demandesEnAttente as $demande): ?>
                            <div class="request-card">
                                <h4><?= htmlspecialchars($demande['designation'] ?? 'Matériel non spécifié') ?></h4>
                                <div class="request-meta">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?>
                                </div>
                                <div class="request-meta">
                                    <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($demande['created_at'])) ?>
                                </div>
                                <?php if ($canValidateRequests): ?>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-success">Valider</button>
                                    <button class="btn btn-sm btn-danger">Rejeter</button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Aucune demande en attente</p>
                        <?php endif; ?>
                        
                        <a href="./requests/" class="btn btn-outline btn-sm">Voir toutes les demandes</a>
                    </div>

                    <!-- Informations du module -->
                    <div class="sidebar-section">
                        <h3>Informations</h3>
                        <div class="info-item">
                            <strong>Module :</strong> Matériel v<?= defined('VERSION') ? VERSION : '0.5' ?>
                        </div>
                        <div class="info-item">
                            <strong>Statut :</strong> 
                            <span class="badge badge-<?= $module_data['status'] === 'active' ? 'success' : 'warning' ?>">
                                <?= ucfirst($module_data['status']) ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Base de données :</strong>
                            <span class="badge badge-<?= $db_connected ? 'success' : 'danger' ?>">
                                <?= $db_connected ? 'Connectée' : 'Erreur' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>

    <script>
    // Graphique répartition matériel
    const ctx = document.getElementById('materielChart').getContext('2d');
    const materielChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Attribué', 'Disponible', 'Maintenance'],
            datasets: [{
                data: [
                    <?= $stats['materiel_attribue'] ?>,
                    <?= $stats['total_materiel'] - $stats['materiel_attribue'] - $stats['maintenance_due'] ?>,
                    <?= $stats['maintenance_due'] ?>
                ],
                backgroundColor: [
                    '#28a745',
                    '#007bff', 
                    '#ffc107'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>

    <style>
    .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0; }
    .action-card { display: block; background: white; padding: 1.5rem; border-radius: 8px; text-decoration: none; color: inherit; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s; }
    .action-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
    .action-card i { font-size: 2rem; color: var(--primary-blue); margin-bottom: 1rem; }
    .action-card h4 { margin: 0.5rem 0; color: var(--primary-blue); }
    .action-card p { margin: 0; color: #666; font-size: 0.9rem; }
    .sidebar-section { margin-bottom: 2rem; }
    .sidebar-section h3 { color: var(--primary-blue); border-bottom: 2px solid var(--primary-blue); padding-bottom: 0.5rem; margin-bottom: 1rem; }
    .info-item { padding: 0.5rem 0; border-bottom: 1px solid #eee; }
    .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .text-muted { color: #6c757d; font-style: italic; }
    </style>
</body>
</html>

<?php
/**
 * Titre: Dashboard Module Matériel - Version propre
 * Chemin: /public/materiel/dashboard.php
 * Version: 0.5 beta + build auto
 */

// Configuration
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once ROOT_PATH . '/config/modules.php';

// Variables template
$page_title = 'Gestion du Matériel';
$page_subtitle = 'Outillage et Équipements';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// Vérifications
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = $current_user['role'] ?? 'guest';
$module_data = $modules['materiel'] ?? ['status' => 'development', 'name' => 'Matériel'];

// Connexion BDD
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
} catch (Exception $e) {
    error_log("Erreur BDD Matériel: " . $e->getMessage());
    $db_connected = false;
}

// Récupération des stats
$stats = ['total_materiel' => 0, 'materiel_attribue' => 0, 'demandes_attente' => 0, 'maintenance_due' => 0];

if ($db_connected) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM materiel_items");
        $result = $stmt->fetch();
        $stats['total_materiel'] = $result ? $result['total'] : 0;
        
        $stmt = $db->query("SELECT COUNT(*) as attribues FROM materiel_attributions WHERE etat_attribution = 'active'");
        $result = $stmt->fetch();
        $stats['materiel_attribue'] = $result ? $result['attribues'] : 0;
        
        $stmt = $db->query("SELECT COUNT(*) as en_attente FROM materiel_demandes WHERE statut = 'en_attente'");
        $result = $stmt->fetch();
        $stats['demandes_attente'] = $result ? $result['en_attente'] : 0;
        
        $stmt = $db->query("SELECT COUNT(*) as maintenance FROM materiel_items WHERE prochaine_maintenance <= CURDATE() AND prochaine_maintenance IS NOT NULL");
        $result = $stmt->fetch();
        $stats['maintenance_due'] = $result ? $result['maintenance'] : 0;
        
    } catch (Exception $e) {
        error_log("Erreur stats matériel: " . $e->getMessage());
    }
}

$isResponsable = in_array($user_role, ['admin', 'dev']);
$build_number = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Portail Guldagil</title>
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <!-- CSS module -->
    <link rel="stylesheet" href="./assets/css/materiel.css?v=<?= $build_number ?>">
    
    <!-- External libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

            <!-- Dashboard -->
            <div class="dashboard-grid">
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

                    <!-- Graphique -->
                    <div class="chart-container">
                        <h3>Répartition du matériel</h3>
                        <canvas id="materielChart" width="400" height="200"></canvas>
                    </div>

                    <!-- Actions rapides -->
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
                    <div class="sidebar-section">
                        <h3>Dernières demandes</h3>
                        <p class="text-muted">Aucune demande en attente</p>
                        <a href="./requests/" class="btn btn-outline btn-sm">Voir toutes les demandes</a>
                    </div>

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
    const ctx = document.getElementById('materielChart').getContext('2d');
    const materielChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Attribué', 'Disponible', 'Maintenance'],
            datasets: [{
                data: [
                    <?= $stats['materiel_attribue'] ?>,
                    <?= max(0, $stats['total_materiel'] - $stats['materiel_attribue'] - $stats['maintenance_due']) ?>,
                    <?= $stats['maintenance_due'] ?>
                ],
                backgroundColor: ['#28a745', '#007bff', '#ffc107'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    </script>

    <style>
    .dashboard-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; margin: 2rem 0; }
    .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
    .stat-box { background: white; padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--primary-blue); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-number { font-size: 2rem; font-weight: 700; color: var(--primary-blue); }
    .stat-label { color: #666; font-size: 0.9rem; margin-top: 0.5rem; }
    .chart-container { background: white; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .alert-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 6px; margin: 0.5rem 0; }
    .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
    .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
    .sidebar { background: #f8f9fa; padding: 1.5rem; border-radius: 12px; }
    .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0; }
    .action-card { display: block; background: white; padding: 1.5rem; border-radius: 8px; text-decoration: none; color: inherit; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s; }
    .action-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
    .action-card i { font-size: 2rem; color: var(--primary-blue); margin-bottom: 1rem; }
    .sidebar-section { margin-bottom: 2rem; }
    .info-item { padding: 0.5rem 0; border-bottom: 1px solid #eee; }
    .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .text-muted { color: #6c757d; font-style: italic; }
    @media (max-width: 768px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</body>
</html>

<?php
/**
 * Titre: Dashboard Matériel - Module complet sans CDN
 * Chemin: /materiel/dashboard.php 
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit;
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

$page_title = 'Dashboard Matériel';
$page_subtitle = 'Vue d\'ensemble et gestion du matériel';
$page_description = 'Dashboard complet pour la gestion du matériel d\'entreprise';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = true;
$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
$user_role = $current_user['role'] ?? 'user';

// Stats matériel
$stats = [
    'total_materiel' => 0,
    'materiel_attribue' => 0,
    'demandes_attente' => 0,
    'maintenance_due' => 0
];

$db_connected = false;
try {
    $db = $db ?? new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
    
    // Calcul stats
    $stmt = $db->query("SELECT COUNT(*) as total FROM materiel_items WHERE 1");
    $result = $stmt->fetch();
    $stats['total_materiel'] = $result ? $result['total'] : 0;
    
    $stmt = $db->query("SELECT COUNT(*) as attribue FROM materiel_items WHERE statut = 'attribue'");
    $result = $stmt->fetch();
    $stats['materiel_attribue'] = $result ? $result['attribue'] : 0;
    
    $stmt = $db->query("SELECT COUNT(*) as en_attente FROM materiel_demandes WHERE statut = 'en_attente'");
    $result = $stmt->fetch();
    $stats['demandes_attente'] = $result ? $result['en_attente'] : 0;
    
    $stmt = $db->query("SELECT COUNT(*) as maintenance FROM materiel_items WHERE prochaine_maintenance <= CURDATE() AND prochaine_maintenance IS NOT NULL");
    $result = $stmt->fetch();
    $stats['maintenance_due'] = $result ? $result['maintenance'] : 0;
    
} catch (Exception $e) {
    error_log("Erreur stats matériel: " . $e->getMessage());
}

$build_number = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis');
include ROOT_PATH . '/templates/header.php';
?>

<main class="module-main materiel-main">
    <div class="module-container">
        <!-- En-tête module -->
        <div class="module-header">
            <div class="module-title-section">
                <h1>🔧 Dashboard Matériel</h1>
                <p class="module-subtitle">Gestion complète du matériel d'entreprise</p>
            </div>
            <div class="module-actions">
                <a href="./inventory/" class="btn btn-primary">📋 Inventaire</a>
                <a href="./request/" class="btn btn-secondary">➕ Nouvelle demande</a>
            </div>
        </div>

        <!-- Alerte connexion BDD -->
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
                    🔧 Total matériel
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['materiel_attribue'] ?></div>
                <div class="stat-label">
                    👤 Matériel attribué
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['demandes_attente'] ?></div>
                <div class="stat-label">
                    ⏰ Demandes en attente
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?= $stats['maintenance_due'] ?></div>
                <div class="stat-label">
                    🔨 Maintenance due
                </div>
            </div>
        </div>

        <!-- Dashboard principal -->
        <div class="dashboard-grid">
            <div class="main-content">
                <!-- Alertes -->
                <?php if ($stats['maintenance_due'] > 0): ?>
                <div class="alert-item alert-warning">
                    ⚠️ <span><?= $stats['maintenance_due'] ?> équipement(s) nécessitent une maintenance</span>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['demandes_attente'] > 0): ?>
                <div class="alert-item alert-info">
                    ℹ️ <span><?= $stats['demandes_attente'] ?> demande(s) en attente de validation</span>
                </div>
                <?php endif; ?>

                <!-- Graphique CSS pur -->
                <div class="chart-container">
                    <h3>🔧 Répartition du matériel</h3>
                    <div class="chart-css-container">
                        <div class="chart-legend">
                            <div class="legend-item">
                                <span class="legend-color" style="background: #28a745;"></span>
                                Attribué (<?= $stats['materiel_attribue'] ?>)
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background: #007bff;"></span>
                                Disponible (<?= max(0, $stats['total_materiel'] - $stats['materiel_attribue'] - $stats['maintenance_due']) ?>)
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background: #ffc107;"></span>
                                Maintenance (<?= $stats['maintenance_due'] ?>)
                            </div>
                        </div>
                        <div class="chart-bars">
                            <?php 
                            $total = max(1, $stats['total_materiel']);
                            $attribue_pct = ($stats['materiel_attribue'] / $total) * 100;
                            $disponible_pct = (max(0, $total - $stats['materiel_attribue'] - $stats['maintenance_due']) / $total) * 100;
                            $maintenance_pct = ($stats['maintenance_due'] / $total) * 100;
                            ?>
                            <div class="chart-bar" style="width: <?= $attribue_pct ?>%; background: #28a745;" title="Attribué: <?= $stats['materiel_attribue'] ?>"></div>
                            <div class="chart-bar" style="width: <?= $disponible_pct ?>%; background: #007bff;" title="Disponible"></div>
                            <div class="chart-bar" style="width: <?= $maintenance_pct ?>%; background: #ffc107;" title="Maintenance: <?= $stats['maintenance_due'] ?>"></div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="quick-actions">
                    <h3>Actions rapides</h3>
                    <div class="action-grid">
                        <a href="./inventory/" class="action-card">
                            🔍
                            <h4>Consulter l'inventaire</h4>
                            <p>Vue complète du matériel disponible</p>
                        </a>
                        
                        <a href="./request/" class="action-card">
                            ➕
                            <h4>Nouvelle demande</h4>
                            <p>Demander du matériel</p>
                        </a>
                        
                        <a href="./maintenance/" class="action-card">
                            🔨
                            <h4>Planifier maintenance</h4>
                            <p>Programmer les interventions</p>
                        </a>
                        
                        <a href="./reports/" class="action-card">
                            📊
                            <h4>Rapports</h4>
                            <p>Statistiques et analyses</p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-section">
                    <h3>📊 Informations système</h3>
                    <div class="info-item">
                        <strong>Base de données :</strong>
                        <span class="badge <?= $db_connected ? 'badge-success' : 'badge-danger' ?>">
                            <?= $db_connected ? 'Connectée' : 'Erreur' ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Version module :</strong> 0.5 beta
                    </div>
                    <div class="info-item">
                        <strong>Build :</strong> <?= $build_number ?>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h3>🔧 Actions admin</h3>
                    <?php if (in_array($user_role, ['admin', 'dev'])): ?>
                        <a href="./config/" class="action-card">
                            ⚙️
                            <h4>Configuration</h4>
                            <p>Paramètres du module</p>
                        </a>
                        <a href="./import/" class="action-card">
                            📥
                            <h4>Import données</h4>
                            <p>Importer inventaire</p>
                        </a>
                    <?php else: ?>
                        <p class="text-muted">Accès administrateur requis</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include ROOT_PATH . '/templates/footer.php'; ?>

<!-- CSS intégré pour chart et émojis -->
<style>
/* Chart CSS pur */
.chart-css-container { padding: 1rem; }
.chart-legend { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
.legend-color { width: 16px; height: 16px; border-radius: 3px; }
.chart-bars { display: flex; height: 40px; border-radius: 8px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
.chart-bar { transition: all 0.3s ease; position: relative; }
.chart-bar:hover { opacity: 0.8; transform: scaleY(1.1); }

/* Layout grille */
.dashboard-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; margin: 2rem 0; }
@media (max-width: 768px) { .dashboard-grid { grid-template-columns: 1fr; } }
</style>
</body>
</html>
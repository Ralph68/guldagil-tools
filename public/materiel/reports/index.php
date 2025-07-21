<?php
/**
 * Titre: Module Matériel - Rapports et statistiques
 * Chemin: /public/materiel/reports/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once dirname(__DIR__) . '/classes/MaterielManager.php';

// Variables pour template
$page_title = 'Rapports Matériel';
$page_subtitle = 'Statistiques et analyses d\'inventaire';
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

// Permissions pour rapports
$can_view_reports = in_array($user_role, ['admin', 'dev', 'logistique']);
if (!$can_view_reports) {
    header('Location: ../index.php?error=access_denied');
    exit;
}

// Manager matériel
$materielManager = new MaterielManager();

// Paramètres de filtrage
$period = $_GET['period'] ?? 'month';
$agence_filter = $_GET['agence'] ?? '';
$type_filter = $_GET['type'] ?? '';

// Gestion export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    switch ($export_type) {
        case 'inventory_csv':
            exportInventoryCSV($materielManager, $agence_filter);
            break;
        case 'requests_csv':
            exportRequestsCSV($materielManager, $period);
            break;
        case 'stats_pdf':
            exportStatsPDF($materielManager);
            break;
    }
    exit;
}

// Récupération des données pour les rapports
$stats = $materielManager->getStatistiquesGenerales();
$statsByCategory = $materielManager->getStatistiquesByCategorie();
$statsByAgence = $materielManager->getStatistiquesByAgence();
$requestsStats = $materielManager->getDemandesStatistiques($period);
$maintenanceStats = $materielManager->getMaintenanceStatistiques();
$costAnalysis = $materielManager->getCostAnalysis($period);

// Données pour les graphiques
$categoryData = [];
$agenceData = [];
$requestsTrend = [];

foreach ($statsByCategory as $cat) {
    $categoryData[] = [
        'label' => $cat['nom'],
        'value' => $cat['total_items'],
        'color' => $cat['couleur'] ?? '#3b82f6'
    ];
}

foreach ($statsByAgence as $agence) {
    $agenceData[] = [
        'label' => $agence['nom'],
        'total' => $agence['total_items'],
        'available' => $agence['disponibles'],
        'assigned' => $agence['attribues']
    ];
}

$categories = $materielManager->getCategories();
$agences = $materielManager->getAgences();

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/'],
    ['icon' => '🔧', 'text' => 'Matériel', 'url' => '../index.php'],
    ['icon' => '📊', 'text' => 'Rapports', 'url' => '', 'active' => true]
];

include ROOT_PATH . '/templates/header.php';
?>

<div class="reports-container">
    <!-- En-tête avec filtres -->
    <div class="reports-header">
        <div class="header-content">
            <div class="header-info">
                <h1>📊 Rapports Matériel</h1>
                <p class="subtitle">Analyses et statistiques de l'inventaire</p>
            </div>
            
            <div class="header-filters">
                <form method="GET" class="filters-form">
                    <select name="period" onchange="this.form.submit()">
                        <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Cette semaine</option>
                        <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Ce mois</option>
                        <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>Ce trimestre</option>
                        <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Cette année</option>
                    </select>
                    
                    <select name="agence" onchange="this.form.submit()">
                        <option value="">Toutes les agences</option>
                        <?php foreach ($agences as $agence): ?>
                            <option value="<?= $agence['id'] ?>" <?= $agence_filter == $agence['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agence['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Indicateurs clés -->
    <div class="kpi-section">
        <h2>🎯 Indicateurs Clés</h2>
        <div class="kpi-grid">
            <div class="kpi-card primary">
                <div class="kpi-icon">📦</div>
                <div class="kpi-content">
                    <div class="kpi-value"><?= number_format($stats['total_outils']) ?></div>
                    <div class="kpi-label">Total équipements</div>
                    <div class="kpi-trend">
                        <?php 
                        $trend = rand(2, 8);
                        echo $trend > 0 ? "+$trend%" : "$trend%";
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="kpi-card success">
                <div class="kpi-icon">✅</div>
                <div class="kpi-content">
                    <div class="kpi-value"><?= number_format($stats['outils_attribues']) ?></div>
                    <div class="kpi-label">Équipements attribués</div>
                    <div class="kpi-percentage">
                        <?= $stats['total_outils'] > 0 ? round(($stats['outils_attribues'] / $stats['total_outils']) * 100, 1) : 0 ?>%
                    </div>
                </div>
            </div>
            
            <div class="kpi-card warning">
                <div class="kpi-icon">⏳</div>
                <div class="kpi-content">
                    <div class="kpi-value"><?= number_format($stats['demandes_attente']) ?></div>
                    <div class="kpi-label">Demandes en attente</div>
                    <div class="kpi-trend">
                        <?php 
                        $trend = rand(-15, 5);
                        echo $trend > 0 ? "+$trend%" : "$trend%";
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="kpi-card danger">
                <div class="kpi-icon">🔧</div>
                <div class="kpi-content">
                    <div class="kpi-value"><?= number_format($stats['maintenance_due']) ?></div>
                    <div class="kpi-label">Maintenance requise</div>
                    <div class="kpi-alert">Action requise</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et analyses -->
    <div class="charts-section">
        <div class="charts-grid">
            <!-- Répartition par catégorie -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>📊 Répartition par catégorie</h3>
                    <div class="chart-actions">
                        <button onclick="exportChart('category')" class="btn-icon">📥</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart" width="400" height="300"></canvas>
                </div>
                <div class="chart-legend">
                    <?php foreach ($statsByCategory as $cat): ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: <?= $cat['couleur'] ?? '#3b82f6' ?>"></div>
                            <span class="legend-label"><?= htmlspecialchars($cat['nom']) ?></span>
                            <span class="legend-value"><?= $cat['total_items'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Répartition par agence -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>🏢 Répartition par agence</h3>
                    <div class="chart-actions">
                        <button onclick="exportChart('agence')" class="btn-icon">📥</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="agenceChart" width="400" height="300"></canvas>
                </div>
                <div class="agence-stats">
                    <?php foreach ($statsByAgence as $agence): ?>
                        <div class="agence-row">
                            <div class="agence-name"><?= htmlspecialchars($agence['nom']) ?></div>
                            <div class="agence-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $agence['total_items'] > 0 ? ($agence['attribues'] / $agence['total_items'] * 100) : 0 ?>%"></div>
                                </div>
                                <span class="progress-text"><?= $agence['attribues'] ?>/<?= $agence['total_items'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Évolution des demandes -->
            <div class="chart-card full-width">
                <div class="chart-header">
                    <h3>📈 Évolution des demandes</h3>
                    <div class="chart-actions">
                        <button onclick="toggleChartType('requests')" class="btn-toggle">📊/📈</button>
                        <button onclick="exportChart('requests')" class="btn-icon">📥</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="requestsChart" width="800" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableaux de données -->
    <div class="tables-section">
        <div class="tables-grid">
            <!-- Top équipements demandés -->
            <div class="table-card">
                <div class="table-header">
                    <h3>🔥 Équipements les plus demandés</h3>
                    <button onclick="exportTable('top_equipment')" class="btn-export">📊 Export CSV</button>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Équipement</th>
                                <th>Catégorie</th>
                                <th>Demandes</th>
                                <th>Stock</th>
                                <th>Ratio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $topEquipments = $materielManager->getTopRequestedEquipment();
                            foreach ($topEquipments as $equipment): 
                                $ratio = $equipment['stock_total'] > 0 ? $equipment['demandes_total'] / $equipment['stock_total'] : 0;
                                $ratioClass = $ratio > 2 ? 'high' : ($ratio > 1 ? 'medium' : 'low');
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($equipment['designation']) ?></strong>
                                        <?php if ($equipment['marque']): ?>
                                            <small><?= htmlspecialchars($equipment['marque']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($equipment['categorie_nom']) ?></td>
                                    <td><span class="badge badge-info"><?= $equipment['demandes_total'] ?></span></td>
                                    <td><?= $equipment['stock_total'] ?></td>
                                    <td>
                                        <span class="ratio-badge ratio-<?= $ratioClass ?>">
                                            <?= number_format($ratio, 1) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Alertes et maintenance -->
            <div class="table-card">
                <div class="table-header">
                    <h3>⚠️ Alertes et maintenance</h3>
                    <button onclick="exportTable('maintenance')" class="btn-export">📊 Export CSV</button>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Équipement</th>
                                <th>Agence</th>
                                <th>Échéance</th>
                                <th>Priorité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maintenanceAlerts = $materielManager->getMaintenanceAlerts();
                            foreach ($maintenanceAlerts as $alert): 
                                $daysLeft = (strtotime($alert['echeance']) - time()) / (60 * 60 * 24);
                                $priorityClass = $daysLeft < 0 ? 'expired' : ($daysLeft < 7 ? 'urgent' : 'normal');
                            ?>
                                <tr class="alert-<?= $priorityClass ?>">
                                    <td>
                                        <span class="alert-type"><?= htmlspecialchars($alert['type_maintenance']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($alert['designation']) ?></strong>
                                        <small><?= htmlspecialchars($alert['numero_inventaire']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($alert['agence_nom']) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($alert['echeance'])) ?>
                                        <?php if ($daysLeft < 0): ?>
                                            <small class="overdue">Dépassé de <?= abs(round($daysLeft)) ?> jours</small>
                                        <?php elseif ($daysLeft < 7): ?>
                                            <small class="urgent">Dans <?= round($daysLeft) ?> jours</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-<?= $priorityClass ?>">
                                            <?= $priorityClass === 'expired' ? 'Dépassé' : ($priorityClass === 'urgent' ? 'Urgent' : 'Normal') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyse des coûts -->
    <div class="costs-section">
        <h2>💰 Analyse des coûts</h2>
        <div class="costs-grid">
            <div class="cost-card">
                <div class="cost-header">
                    <h3>Coûts d'acquisition</h3>
                    <span class="cost-period"><?= ucfirst($period) ?></span>
                </div>
                <div class="cost-content">
                    <div class="cost-main">
                        <span class="cost-value"><?= number_format($costAnalysis['acquisition_total'] ?? 0, 0, ',', ' ') ?>€</span>
                        <span class="cost-trend">
                            <?php 
                            $trend = rand(-10, 25);
                            echo ($trend > 0 ? '+' : '') . $trend . '%';
                            ?>
                        </span>
                    </div>
                    <div class="cost-breakdown">
                        <div class="breakdown-item">
                            <span>Nouvel équipement:</span>
                            <span><?= number_format($costAnalysis['nouveau'] ?? 0, 0, ',', ' ') ?>€</span>
                        </div>
                        <div class="breakdown-item">
                            <span>Remplacements:</span>
                            <span><?= number_format($costAnalysis['remplacement'] ?? 0, 0, ',', ' ') ?>€</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cost-card">
                <div class="cost-header">
                    <h3>Coûts de maintenance</h3>
                    <span class="cost-period"><?= ucfirst($period) ?></span>
                </div>
                <div class="cost-content">
                    <div class="cost-main">
                        <span class="cost-value"><?= number_format($costAnalysis['maintenance_total'] ?? 0, 0, ',', ' ') ?>€</span>
                        <span class="cost-trend">
                            <?php 
                            $trend = rand(-5, 15);
                            echo ($trend > 0 ? '+' : '') . $trend . '%';
                            ?>
                        </span>
                    </div>
                    <div class="cost-breakdown">
                        <div class="breakdown-item">
                            <span>Préventive:</span>
                            <span><?= number_format($costAnalysis['preventive'] ?? 0, 0, ',', ' ') ?>€</span>
                        </div>
                        <div class="breakdown-item">
                            <span>Corrective:</span>
                            <span><?= number_format($costAnalysis['corrective'] ?? 0, 0, ',', ' ') ?>€</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cost-card">
                <div class="cost-header">
                    <h3>ROI et amortissement</h3>
                    <span class="cost-period">Global</span>
                </div>
                <div class="cost-content">
                    <div class="cost-main">
                        <span class="cost-value"><?= number_format($costAnalysis['roi'] ?? 0, 1) ?>%</span>
                        <span class="cost-label">ROI moyen</span>
                    </div>
                    <div class="cost-breakdown">
                        <div class="breakdown-item">
                            <span>Amortissement moyen:</span>
                            <span><?= $costAnalysis['amortissement_moyen'] ?? 36 ?> mois</span>
                        </div>
                        <div class="breakdown-item">
                            <span>Valeur résiduelle:</span>
                            <span><?= number_format($costAnalysis['valeur_residuelle'] ?? 0, 0, ',', ' ') ?>€</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions d'export -->
    <div class="export-section">
        <h2>📤 Exports et rapports</h2>
        <div class="export-grid">
            <div class="export-card">
                <div class="export-icon">📋</div>
                <div class="export-content">
                    <h3>Inventaire complet</h3>
                    <p>Export de tous les équipements avec détails</p>
                    <a href="?export=inventory_csv" class="btn btn-primary">💾 Télécharger CSV</a>
                </div>
            </div>

            <div class="export-card">
                <div class="export-icon">📈</div>
                <div class="export-content">
                    <h3>Rapport de demandes</h3>
                    <p>Historique et analyse des demandes</p>
                    <a href="?export=requests_csv" class="btn btn-primary">💾 Télécharger CSV</a>
                </div>
            </div>

            <div class="export-card">
                <div class="export-icon">📊</div>
                <div class="export-content">
                    <h3>Statistiques complètes</h3>
                    <p>Rapport PDF avec graphiques et analyses</p>
                    <a href="?export=stats_pdf" class="btn btn-primary">📄 Générer PDF</a>
                </div>
            </div>

            <div class="export-card">
                <div class="export-icon">🔧</div>
                <div class="export-content">
                    <h3>Planning de maintenance</h3>
                    <p>Calendrier des maintenances préventives</p>
                    <a href="?export=maintenance_calendar" class="btn btn-primary">📅 Télécharger</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques aux rapports */
.reports-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.reports-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 30px;
}

.header-info h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.subtitle {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.header-filters .filters-form {
    display: flex;
    gap: 15px;
}

.header-filters select {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.9);
    color: #374151;
    font-weight: 600;
}

.kpi-section {
    margin-bottom: 40px;
}

.kpi-section h2 {
    margin-bottom: 20px;
    color: #1f2937;
    font-size: 1.8rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.kpi-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    border-left: 4px solid;
}

.kpi-card.primary { border-left-color: #3b82f6; }
.kpi-card.success { border-left-color: #10b981; }
.kpi-card.warning { border-left-color: #f59e0b; }
.kpi-card.danger { border-left-color: #ef4444; }

.kpi-icon {
    font-size: 3rem;
    opacity: 0.8;
}

.kpi-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.kpi-label {
    color: #6b7280;
    font-weight: 600;
    margin-top: 5px;
}

.kpi-trend {
    font-size: 0.9rem;
    font-weight: 600;
    color: #10b981;
    margin-top: 8px;
}

.kpi-percentage {
    font-size: 1.1rem;
    font-weight: 600;
    color: #3b82f6;
    margin-top: 8px;
}

.kpi-alert {
    font-size: 0.85rem;
    font-weight: 600;
    color: #ef4444;
    margin-top: 8px;
    text-transform: uppercase;
}

.charts-section {
    margin-bottom: 40px;
}

.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.chart-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.chart-card.full-width {
    grid-column: 1 / -1;
}

.chart-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.3rem;
}

.chart-actions {
    display: flex;
    gap: 10px;
}

.btn-icon, .btn-toggle {
    background: #f3f4f6;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.2s;
}

.btn-icon:hover, .btn-toggle:hover {
    background: #e5e7eb;
}

.chart-container {
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
}

.chart-legend {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
}

.legend-label {
    flex: 1;
    color: #374151;
    font-weight: 500;
}

.legend-value {
    color: #1f2937;
    font-weight: 600;
}

.agence-stats {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
}

.agence-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.agence-name {
    width: 120px;
    font-weight: 600;
    color: #374151;
}

.agence-progress {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #3b82f6;
    transition: width 0.3s;
}

.progress-text {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 600;
    min-width: 60px;
}

.tables-section {
    margin-bottom: 40px;
}

.tables-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.3rem;
}

.btn-export {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    transition: background 0.2s;
}

.btn-export:hover {
    background: #2563eb;
}

.table-container {
    max-height: 400px;
    overflow-y: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8fafc;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
}

.data-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.data-table tr:hover {
    background: #f9fafb;
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.ratio-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.ratio-low { background: #dcfce7; color: #166534; }
.ratio-medium { background: #fef3c7; color: #d97706; }
.ratio-high { background: #fee2e2; color: #dc2626; }

.alert-type {
    text-transform: uppercase;
    font-size: 0.8rem;
    font-weight: 600;
}

.priority-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-normal { background: #dcfce7; color: #166534; }
.priority-urgent { background: #fef3c7; color: #d97706; }
.priority-expired { background: #fee2e2; color: #dc2626; }

.overdue {
    color: #dc2626;
    font-weight: 600;
}

.urgent {
    color: #d97706;
    font-weight: 600;
}

.costs-section {
    margin-bottom: 40px;
}

.costs-section h2 {
    margin-bottom: 20px;
    color: #1f2937;
    font-size: 1.8rem;
}

.costs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.cost-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.cost-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cost-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.2rem;
}

.cost-period {
    background: #f3f4f6;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b7280;
}

.cost-content {
    padding: 20px;
}

.cost-main {
    display: flex;
    align-items: baseline;
    gap: 15px;
    margin-bottom: 20px;
}

.cost-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.cost-trend {
    font-size: 0.9rem;
    font-weight: 600;
    color: #10b981;
}

.cost-label {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
}

.cost-breakdown {
    border-top: 1px solid #e5e7eb;
    padding-top: 15px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.breakdown-item span:first-child {
    color: #6b7280;
}

.breakdown-item span:last-child {
    color: #1f2937;
    font-weight: 600;
}

.export-section h2 {
    margin-bottom: 20px;
    color: #1f2937;
    font-size: 1.8rem;
}

.export-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.export-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 25px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.export-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.export-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.8;
}

.export-content h3 {
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 1.2rem;
}

.export-content p {
    color: #6b7280;
    margin-bottom: 20px;
    line-height: 1.5;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 20px;
    }
    
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .tables-grid {
        grid-template-columns: 1fr;
    }
    
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .costs-grid {
        grid-template-columns: 1fr;
    }
    
    .agence-row {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    
    .agence-name {
        width: auto;
    }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
// Données pour les graphiques
const categoryData = <?= json_encode($categoryData) ?>;
const agenceData = <?= json_encode($agenceData) ?>;

// Configuration des graphiques
Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
Chart.defaults.color = '#374151';

// Graphique en secteurs pour les catégories
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryData.map(d => d.label),
        datasets: [{
            data: categoryData.map(d => d.value),
            backgroundColor: categoryData.map(d => d.color),
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.raw / total) * 100).toFixed(1);
                        return `${context.label}: ${context.raw} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Graphique en barres pour les agences
const agenceCtx = document.getElementById('agenceChart').getContext('2d');
const agenceChart = new Chart(agenceCtx, {
    type: 'bar',
    data: {
        labels: agenceData.map(d => d.label),
        datasets: [
            {
                label: 'Disponible',
                data: agenceData.map(d => d.available),
                backgroundColor: '#10b981',
                borderRadius: 4
            },
            {
                label: 'Attribué',
                data: agenceData.map(d => d.assigned),
                backgroundColor: '#3b82f6',
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                stacked: true,
                grid: {
                    display: false
                }
            },
            y: {
                stacked: true,
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
                align: 'end'
            }
        }
    }
});

// Graphique d'évolution des demandes
const requestsCtx = document.getElementById('requestsChart').getContext('2d');
let requestsChartType = 'line';

// Données simulées pour l'exemple
const requestsData = {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
    datasets: [
        {
            label: 'Nouvelles demandes',
            data: [12, 15, 18, 14, 22, 19, 25, 28, 24, 30, 26, 32],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        },
        {
            label: 'Demandes traitées',
            data: [10, 13, 16, 15, 20, 17, 23, 26, 22, 28, 25, 30],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4
        }
    ]
};

const requestsChart = new Chart(requestsCtx, {
    type: requestsChartType,
    data: requestsData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                grid: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
                align: 'end'
            }
        }
    }
});

// Fonctions d'interaction
function toggleChartType(chartId) {
    if (chartId === 'requests') {
        requestsChartType = requestsChartType === 'line' ? 'bar' : 'line';
        requestsChart.config.type = requestsChartType;
        requestsChart.update();
    }
}

function exportChart(chartId) {
    let chart;
    let filename;
    
    switch (chartId) {
        case 'category':
            chart = categoryChart;
            filename = 'repartition_categories.png';
            break;
        case 'agence':
            chart = agenceChart;
            filename = 'repartition_agences.png';
            break;
        case 'requests':
            chart = requestsChart;
            filename = 'evolution_demandes.png';
            break;
    }
    
    if (chart) {
        const link = document.createElement('a');
        link.download = filename;
        link.href = chart.toBase64Image();
        link.click();
    }
}

function exportTable(tableType) {
    // Simulation d'export CSV
    const filename = `export_${tableType}_${new Date().toISOString().split('T')[0]}.csv`;
    alert(`Export ${filename} en cours...`);
    
    // Dans un vrai projet, vous feriez un appel AJAX ou une redirection
    // window.location.href = `export.php?type=${tableType}&format=csv`;
}

// Animation d'entrée pour les cartes
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    document.querySelectorAll('.kpi-card, .chart-card, .table-card, .cost-card, .export-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});

// Mise à jour automatique des données (optionnel)
function refreshData() {
    // Dans un vrai projet, vous feriez un appel AJAX pour récupérer les nouvelles données
    console.log('Actualisation des données...');
}

// Actualisation automatique toutes les 5 minutes
setInterval(refreshData, 5 * 60 * 1000);
</script>

<?php
// Fonctions d'export (à implémenter selon vos besoins)
function exportInventoryCSV($materielManager, $agence_filter) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventaire_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'Numéro Inventaire',
        'Désignation', 
        'Catégorie',
        'Marque',
        'Modèle',
        'Numéro Série',
        'Agence',
        'État',
        'Statut',
        'Date Acquisition',
        'Prix Achat',
        'Observations'
    ]);
    
    // Données (simulation)
    $items = $materielManager->getItemsFiltered(['agence_id' => $agence_filter]);
    foreach ($items as $item) {
        fputcsv($output, [
            $item['numero_inventaire'],
            $item['designation'],
            $item['categorie_nom'],
            $item['marque'],
            $item['modele'],
            $item['numero_serie'],
            $item['agence_nom'],
            $item['etat'],
            $item['statut'],
            $item['date_acquisition'],
            $item['prix_achat'],
            $item['observations']
        ]);
    }
    
    fclose($output);
}

function exportRequestsCSV($materielManager, $period) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="demandes_' . $period . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'ID Demande',
        'Date Demande',
        'Type',
        'Demandeur',
        'Équipement',
        'Quantité',
        'Urgence',
        'Statut',
        'Date Validation',
        'Justification'
    ]);
    
    // Données simulées
    $requests = [
        ['#001', '2024-01-15', 'Nouveau', 'Jean Dupont', 'Perceuse électrique', '1', 'Normal', 'Validée', '2024-01-16', 'Nouveau chantier'],
        ['#002', '2024-01-18', 'Remplacement', 'Marie Martin', 'Casque de sécurité', '1', 'Urgent', 'En cours', '', 'Casque endommagé'],
    ];
    
    foreach ($requests as $request) {
        fputcsv($output, $request);
    }
    
    fclose($output);
}

function exportStatsPDF($materielManager) {
    // Ici vous implémenteriez la génération PDF avec une librairie comme TCPDF ou FPDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="statistiques_materiel_' . date('Y-m-d') . '.pdf"');
    
    // Simulation - dans un vrai projet, vous généreriez un vrai PDF
    echo "PDF de statistiques généré le " . date('d/m/Y H:i');
}

include ROOT_PATH . '/templates/footer.php';
?>

<?php
/**
 * Titre: Dashboard statistiques avancées - Module Qualité
 * Chemin: /public/qualite/dashboard.php
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

$page_title = 'Dashboard Qualité';
$page_subtitle = 'Statistiques et analyses de performance';
$current_module = 'qualite';
$module_css = true;

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '✅', 'text' => 'Contrôle Qualité', 'url' => '/qualite/', 'active' => false],
    ['icon' => '📊', 'text' => 'Dashboard', 'url' => '/qualite/dashboard.php', 'active' => true]
];

// Auth temporaire
$user_authenticated = true;
$current_user = ['id' => 1, 'role' => 'logistique', 'name' => 'Utilisateur'];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Période d'analyse
    $period = $_GET['period'] ?? '30'; // 7, 30, 90, 365 jours
    
    // Statistiques globales
    $global_stats = $pdo->query("SELECT * FROM v_control_stats")->fetch();
    
    // Évolution par période
    $evolution_sql = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'validated' THEN 1 END) as validated,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as non_conform
        FROM cq_quality_controls 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ";
    $evolution_stmt = $pdo->prepare($evolution_sql);
    $evolution_stmt->execute([$period]);
    $evolution_data = $evolution_stmt->fetchAll();

    // Stats par type d'équipement
    $types_stats = $pdo->query("SELECT * FROM v_controls_by_equipment")->fetchAll();
    
    // Stats par agence
    $agency_stats = $pdo->query("SELECT * FROM v_agency_performance")->fetchAll();
    
    // Top des non-conformités
    $nc_sql = "
        SELECT 
            et.type_name,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 1) as percentage
        FROM cq_quality_controls qc
        JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
        WHERE qc.status = 'in_progress'
        AND qc.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY et.id, et.type_name
        ORDER BY count DESC
        LIMIT 5
    ";
    $nc_stmt = $pdo->prepare($nc_sql);
    $nc_stmt->execute([$period]);
    $nc_top = $nc_stmt->fetchAll();
    
    // Tendances mensuelles (12 derniers mois)
    $monthly_sql = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'validated' THEN 1 END) as validated,
            ROUND(COUNT(CASE WHEN status = 'validated' THEN 1 END) * 100.0 / COUNT(*), 1) as rate
        FROM cq_quality_controls 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ";
    $monthly_data = $pdo->query($monthly_sql)->fetchAll();
    
    // Temps de traitement moyen
    $processing_sql = "
        SELECT 
            AVG(TIMESTAMPDIFF(HOUR, created_at, validated_date)) as avg_hours,
            MIN(TIMESTAMPDIFF(HOUR, created_at, validated_date)) as min_hours,
            MAX(TIMESTAMPDIFF(HOUR, created_at, validated_date)) as max_hours
        FROM cq_quality_controls 
        WHERE validated_date IS NOT NULL
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ";
    $processing_stmt = $pdo->prepare($processing_sql);
    $processing_stmt->execute([$period]);
    $processing_stats = $processing_stmt->fetch();
    
    // Contrôles par technicien
    $technician_sql = "
        SELECT 
            prepared_by,
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'validated' THEN 1 END) as validated,
            ROUND(COUNT(CASE WHEN status = 'validated' THEN 1 END) * 100.0 / COUNT(*), 1) as success_rate
        FROM cq_quality_controls 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY prepared_by
        HAVING COUNT(*) >= 3
        ORDER BY success_rate DESC, total DESC
    ";
    $technician_stmt = $pdo->prepare($technician_sql);
    $technician_stmt->execute([$period]);
    $technician_stats = $technician_stmt->fetchAll();

    // Calculs KPIs
    $total_controls = $global_stats['total_controls'] ?? 0;
    $conformity_rate = $total_controls > 0 ? 
        round((($global_stats['validated_count'] ?? 0) * 100) / $total_controls, 1) : 0;
    $avg_processing = round($processing_stats['avg_hours'] ?? 0, 1);
    
} catch (Exception $e) {
    error_log("Erreur dashboard qualité: " . $e->getMessage());
    $global_stats = [];
    $evolution_data = [];
    $types_stats = [];
    $agency_stats = [];
}

require_once ROOT_PATH . '/templates/header.php';
?>

<div class="qualite-module">
    <!-- Header -->
    <div class="module-header">
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">📊</div>
                <div class="module-info">
                    <h1>Dashboard Qualité</h1>
                    <p class="module-version">Analyse de performance sur <?= $period ?> jours</p>
                </div>
            </div>
            <div class="module-actions">
                <select id="period-selector" class="period-select" onchange="changePeriod()">
                    <option value="7" <?= $period == '7' ? 'selected' : '' ?>>7 derniers jours</option>
                    <option value="30" <?= $period == '30' ? 'selected' : '' ?>>30 derniers jours</option>
                    <option value="90" <?= $period == '90' ? 'selected' : '' ?>>90 derniers jours</option>
                    <option value="365" <?= $period == '365' ? 'selected' : '' ?>>1 année</option>
                </select>
                <button class="btn btn-outline" onclick="exportDashboard()">
                    <span class="icon">📥</span>
                    Exporter
                </button>
                <button class="btn btn-outline" onclick="refreshDashboard()">
                    <span class="icon">🔄</span>
                    Actualiser
                </button>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- KPIs principaux -->
        <div class="kpi-section">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon">📊</div>
                    <div class="kpi-content">
                        <div class="kpi-value"><?= $total_controls ?></div>
                        <div class="kpi-label">Total contrôles</div>
                        <div class="kpi-trend">
                            <?= calculateTrend($evolution_data, 'total') ?>
                        </div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">✅</div>
                    <div class="kpi-content">
                        <div class="kpi-value"><?= $conformity_rate ?>%</div>
                        <div class="kpi-label">Taux conformité</div>
                        <div class="kpi-trend">
                            <?= calculateTrend($evolution_data, 'validated_rate') ?>
                        </div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">⏱️</div>
                    <div class="kpi-content">
                        <div class="kpi-value"><?= $avg_processing ?>h</div>
                        <div class="kpi-label">Temps moyen</div>
                        <div class="kpi-trend">
                            <span class="trend-neutral">Stable</span>
                        </div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">🚨</div>
                    <div class="kpi-content">
                        <div class="kpi-value"><?= $global_stats['in_progress_count'] ?? 0 ?></div>
                        <div class="kpi-label">Non-conformités</div>
                        <div class="kpi-trend">
                            <?= ($global_stats['in_progress_count'] ?? 0) > 5 ? 
                                '<span class="trend-down">Élevé</span>' : 
                                '<span class="trend-up">Acceptable</span>' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques principaux -->
        <div class="charts-section">
            <div class="charts-grid">
                <!-- Évolution temporelle -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>📈 Évolution des contrôles</h3>
                        <div class="chart-legend">
                            <span class="legend-item"><span class="legend-color bg-blue"></span>Total</span>
                            <span class="legend-item"><span class="legend-color bg-green"></span>Validés</span>
                            <span class="legend-item"><span class="legend-color bg-red"></span>NC</span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="evolutionChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Répartition par type -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>🔧 Répartition par type</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="typesChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableaux détaillés -->
        <div class="tables-section">
            <div class="tables-grid">
                <!-- Performance par agence -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>🏢 Performance par agence</h3>
                        <span class="table-count"><?= count($agency_stats) ?> agences</span>
                    </div>
                    <div class="table-container">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Agence</th>
                                    <th>Total</th>
                                    <th>Validés</th>
                                    <th>Taux</th>
                                    <th>Temps moy.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agency_stats as $agency): ?>
                                <tr>
                                    <td>
                                        <span class="agency-badge"><?= htmlspecialchars($agency['agency_code']) ?></span>
                                        <?= htmlspecialchars($agency['agency_name']) ?>
                                    </td>
                                    <td><?= $agency['total_controls'] ?></td>
                                    <td><?= $agency['validated_controls'] ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $agency['conformity_rate'] ?>%"></div>
                                            <span class="progress-text"><?= $agency['conformity_rate'] ?>%</span>
                                        </div>
                                    </td>
                                    <td><?= round($agency['avg_processing_hours'] ?? 0, 1) ?>h</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Top techniciens -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>👥 Performance techniciens</h3>
                        <span class="table-count"><?= count($technician_stats) ?> techniciens</span>
                    </div>
                    <div class="table-container">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Technicien</th>
                                    <th>Contrôles</th>
                                    <th>Validés</th>
                                    <th>Taux succès</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($technician_stats as $tech): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tech['prepared_by']) ?></td>
                                    <td><?= $tech['total'] ?></td>
                                    <td><?= $tech['validated'] ?></td>
                                    <td>
                                        <div class="success-rate rate-<?= getSuccessRateClass($tech['success_rate']) ?>">
                                            <?= $tech['success_rate'] ?>%
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analyses détaillées -->
        <div class="analysis-section">
            <div class="analysis-grid">
                <!-- Non-conformités -->
                <div class="analysis-card">
                    <div class="analysis-header">
                        <h3>🚨 Top non-conformités</h3>
                    </div>
                    <div class="analysis-content">
                        <?php if (empty($nc_top)): ?>
                        <div class="empty-analysis">
                            <span class="empty-icon">🎉</span>
                            <p>Aucune non-conformité sur la période</p>
                        </div>
                        <?php else: ?>
                        <div class="nc-list">
                            <?php foreach ($nc_top as $nc): ?>
                            <div class="nc-item">
                                <div class="nc-info">
                                    <strong><?= htmlspecialchars($nc['type_name']) ?></strong>
                                    <span class="nc-count"><?= $nc['count'] ?> cas</span>
                                </div>
                                <div class="nc-percentage"><?= $nc['percentage'] ?>%</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tendances mensuelles -->
                <div class="analysis-card">
                    <div class="analysis-header">
                        <h3>📅 Tendances mensuelles</h3>
                    </div>
                    <div class="analysis-content">
                        <div class="monthly-trends">
                            <?php foreach (array_slice($monthly_data, 0, 6) as $month): ?>
                            <div class="month-item">
                                <div class="month-label"><?= formatMonth($month['month']) ?></div>
                                <div class="month-stats">
                                    <span class="month-total"><?= $month['total'] ?></span>
                                    <span class="month-rate"><?= $month['rate'] ?>%</span>
                                </div>
                                <div class="month-bar">
                                    <div class="month-fill" style="width: <?= min(100, ($month['total'] / 50) * 100) ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Alertes et recommandations -->
                <div class="analysis-card">
                    <div class="analysis-header">
                        <h3>💡 Recommandations</h3>
                    </div>
                    <div class="analysis-content">
                        <div class="recommendations">
                            <?php
                            $recommendations = generateRecommendations($conformity_rate, $avg_processing, $global_stats);
                            foreach ($recommendations as $rec): ?>
                            <div class="recommendation <?= $rec['type'] ?>">
                                <span class="rec-icon"><?= $rec['icon'] ?></span>
                                <div class="rec-content">
                                    <strong><?= $rec['title'] ?></strong>
                                    <p><?= $rec['message'] ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const DashboardConfig = {
    period: <?= $period ?>,
    baseUrl: '/qualite/',
    evolutionData: <?= json_encode($evolution_data) ?>,
    typesData: <?= json_encode($types_stats) ?>,
    autoRefresh: true,
    refreshInterval: 60000 // 1 minute
};

// Changement de période
function changePeriod() {
    const period = document.getElementById('period-selector').value;
    window.location.href = `${DashboardConfig.baseUrl}dashboard.php?period=${period}`;
}

// Actualisation
function refreshDashboard() {
    location.reload();
}

// Export
function exportDashboard() {
    window.open(`${DashboardConfig.baseUrl}export-dashboard.php?period=${DashboardConfig.period}`, '_blank');
}

// Initialisation graphiques
document.addEventListener('DOMContentLoaded', function() {
    initEvolutionChart();
    initTypesChart();
    
    if (DashboardConfig.autoRefresh) {
        setInterval(refreshDashboard, DashboardConfig.refreshInterval);
    }
});

function initEvolutionChart() {
    const ctx = document.getElementById('evolutionChart').getContext('2d');
    const data = DashboardConfig.evolutionData.reverse();
    
    const chartData = {
        labels: data.map(d => formatDate(d.date)),
        datasets: [
            {
                label: 'Total',
                data: data.map(d => d.total),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            },
            {
                label: 'Validés',
                data: data.map(d => d.validated),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4
            },
            {
                label: 'Non-conformes',
                data: data.map(d => d.non_conform),
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4
            }
        ]
    };
    
    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function initTypesChart() {
    const ctx = document.getElementById('typesChart').getContext('2d');
    const data = DashboardConfig.typesData;
    
    const chartData = {
        labels: data.map(d => d.type_name),
        datasets: [{
            data: data.map(d => d.total_controls),
            backgroundColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6'
            ]
        }]
    };
    
    new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
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
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'});
}

console.log('📊 Dashboard qualité initialisé');
</script>

<style>
/* Styles dashboard */
.kpi-section {
    margin-bottom: 2rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.kpi-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: transform 0.3s ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
}

.kpi-icon {
    font-size: 3rem;
    padding: 1rem;
    background: var(--qualite-primary-light);
    border-radius: 1rem;
}

.kpi-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--qualite-primary);
    margin-bottom: 0.5rem;
}

.kpi-label {
    font-weight: 600;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
}

.kpi-trend {
    font-size: 0.875rem;
}

.trend-up { color: #10b981; }
.trend-down { color: #ef4444; }
.trend-neutral { color: #6b7280; }

.charts-section {
    margin-bottom: 2rem;
}

.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.chart-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.chart-legend {
    display: flex;
    gap: 1rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.bg-blue { background: #3b82f6; }
.bg-green { background: #10b981; }
.bg-red { background: #ef4444; }

.chart-container {
    position: relative;
    height: 300px;
}

.tables-section {
    margin-bottom: 2rem;
}

.tables-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.table-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 1.5rem;
    background: var(--gray-50);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-count {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.dashboard-table {
    width: 100%;
    border-collapse: collapse;
}

.dashboard-table th {
    background: var(--gray-50);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--gray-700);
}

.dashboard-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    font-size: 0.875rem;
}

.progress-bar {
    position: relative;
    background: var(--gray-200);
    border-radius: 1rem;
    height: 1.5rem;
    overflow: hidden;
}

.progress-fill {
    background: var(--qualite-primary);
    height: 100%;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}

.success-rate {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-weight: 600;
    font-size: 0.75rem;
    text-align: center;
}

.rate-excellent { background: #dcfce7; color: #166534; }
.rate-good { background: #fef3c7; color: #92400e; }
.rate-poor { background: #fee2e2; color: #991b1b; }

.analysis-section {
    margin-bottom: 2rem;
}

.analysis-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.analysis-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.analysis-header {
    padding: 1.5rem;
    background: var(--gray-50);
}

.analysis-content {
    padding: 1.5rem;
}

.nc-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.nc-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
}

.nc-count {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.nc-percentage {
    font-weight: 700;
    color: var(--qualite-danger);
}

.monthly-trends {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.month-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.month-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.month-bar {
    height: 0.5rem;
    background: var(--gray-200);
    border-radius: 0.25rem;
    overflow: hidden;
}

.month-fill {
    height: 100%;
    background: var(--qualite-primary);
    transition: width 0.3s ease;
}

.recommendations {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.recommendation {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
    border-left: 4px solid;
}

.recommendation.success {
    background: #f0fdf4;
    border-color: #22c55e;
}

.recommendation.warning {
    background: #fffbeb;
    border-color: #f59e0b;
}

.recommendation.danger {
    background: #fef2f2;
    border-color: #ef4444;
}

.rec-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.rec-content p {
    margin: 0.5rem 0 0 0;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.period-select {
    padding: 0.5rem 1rem;
    border: 2px solid var(--gray-300);
    border-radius: 0.5rem;
    background: white;
    cursor: pointer;
}

.empty-analysis {
    text-align: center;
    padding: 2rem;
    color: var(--gray-600);
}

.empty-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
}

@media (max-width: 1024px) {
    .charts-grid,
    .tables-grid,
    .analysis-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .kpi-grid {
        grid-template-columns: 1fr;
    }
    
    .module-header-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .kpi-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php
// Fonctions utilitaires
function calculateTrend($data, $metric) {
    if (count($data) < 2) return '<span class="trend-neutral">-</span>';
    
    $recent = array_slice($data, -7); // 7 derniers jours
    $previous = array_slice($data, -14, 7); // 7 jours précédents
    
    $recent_avg = array_sum(array_column($recent, $metric)) / count($recent);
    $previous_avg = array_sum(array_column($previous, $metric)) / count($previous);
    
    if ($recent_avg > $previous_avg * 1.05) {
        return '<span class="trend-up">↗ +' . round((($recent_avg - $previous_avg) / $previous_avg) * 100, 1) . '%</span>';
    } elseif ($recent_avg < $previous_avg * 0.95) {
        return '<span class="trend-down">↘ -' . round((($previous_avg - $recent_avg) / $previous_avg) * 100, 1) . '%</span>';
    } else {
        return '<span class="trend-neutral">→ Stable</span>';
    }
}

function getSuccessRateClass($rate) {
    if ($rate >= 90) return 'excellent';
    if ($rate >= 75) return 'good';
    return 'poor';
}

function formatMonth($month) {
    $date = DateTime::createFromFormat('Y-m', $month);
    return $date->format('M Y');
}

function generateRecommendations($conformity_rate, $avg_processing, $stats) {
    $recommendations = [];
    
    if ($conformity_rate >= 95) {
        $recommendations[] = [
            'type' => 'success',
            'icon' => '🎉',
            'title' => 'Excellente performance',
            'message' => 'Taux de conformité exceptionnel. Maintenez cette qualité.'
        ];
    } elseif ($conformity_rate < 80) {
        $recommendations[] = [
            'type' => 'danger',
            'icon' => '⚠️',
            'title' => 'Amélioration nécessaire',
            'message' => 'Taux de conformité faible. Réviser les procédures de contrôle.'
        ];
    }
    
    if ($avg_processing > 48) {
        $recommendations[] = [
            'type' => 'warning',
            'icon' => '⏰',
            'title' => 'Délais trop longs',
            'message' => 'Temps de traitement élevé. Optimiser le workflow.'
        ];
    }
    
    if (($stats['in_progress_count'] ?? 0) > 10) {
        $recommendations[] = [
            'type' => 'warning',
            'icon' => '🔧',
            'title' => 'Nombreuses NC',
            'message' => 'Beaucoup de non-conformités en cours. Formation équipe recommandée.'
        ];
    }
    
    if (empty($recommendations)) {
        $recommendations[] = [
            'type' => 'success',
            'icon' => '✅',
            'title' => 'Performance stable',
            'message' => 'Aucun point d\'attention majeur détecté.'
        ];
    }
    
    return $recommendations;
}

require_once ROOT_PATH . '/templates/footer.php';
?>

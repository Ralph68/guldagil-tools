<?php
/**
 * Titre: Interface Analytics Admin
 * Chemin: /public/admin/analytics.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../..'));
require_once ROOT_PATH . '/config/config.php';

// Vérification authentification admin
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'dev'])) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit('Accès non autorisé');
}

// Variables de page
$page_title = 'Analytics - Portail Admin';
$current_module = 'admin';

// Traitement des filtres
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$module_filter = isset($_GET['module']) ? $_GET['module'] : 'all';

// Récupération des données analytics
$analytics_data = [];
$visitors_count = 0;
$page_views = 0;
$unique_visitors = [];
$popular_pages = [];
$modules_usage = [];
$user_agents = [];
$daily_views = [];
$referrers = [];

$analytics_dir = ROOT_PATH . '/storage/analytics/';
if (file_exists($analytics_dir)) {
    // Définir la période de recherche
    $current_date = $start_date;
    while (strtotime($current_date) <= strtotime($end_date)) {
        $log_file = $analytics_dir . 'visits_' . $current_date . '.log';
        
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            // Initialiser compteur pour ce jour
            $date_key = date('d/m', strtotime($current_date));
            if (!isset($daily_views[$date_key])) {
                $daily_views[$date_key] = 0;
            }
            
            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                
                // Appliquer filtre module si nécessaire
                if ($module_filter !== 'all' && $entry['module'] !== $module_filter) {
                    continue;
                }
                
                // Comptabiliser visiteur unique
                $unique_visitors[$entry['ip_hash']] = true;
                
                // Comptabiliser page vue
                $page_views++;
                $daily_views[$date_key]++;
                
                // Comptabiliser pages populaires
                if (!isset($popular_pages[$entry['page']])) {
                    $popular_pages[$entry['page']] = 0;
                }
                $popular_pages[$entry['page']]++;
                
                // Comptabiliser utilisation modules
                if (!isset($modules_usage[$entry['module']])) {
                    $modules_usage[$entry['module']] = 0;
                }
                $modules_usage[$entry['module']]++;
                
                // Analyser agents utilisateurs
                $ua = isset($entry['user_agent']) ? $entry['user_agent'] : 'Unknown';
                if (!isset($user_agents[$ua])) {
                    $user_agents[$ua] = 0;
                }
                $user_agents[$ua]++;
                
                // Comptabiliser les référents
                if (!empty($entry['referer'])) {
                    $referer = parse_url($entry['referer'], PHP_URL_HOST) ?: $entry['referer'];
                    if (!isset($referrers[$referer])) {
                        $referrers[$referer] = 0;
                    }
                    $referrers[$referer]++;
                }
                
                // Ajouter aux données complètes
                $analytics_data[] = $entry;
            }
        }
        
        // Passer au jour suivant
        $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
    }
}

// Statistiques calculées
$visitors_count = count($unique_visitors);
arsort($popular_pages);
arsort($modules_usage);
arsort($referrers);

// Identifier les navigateurs utilisés
$browsers = [];
foreach ($user_agents as $ua => $count) {
    $browser = 'Autre';
    
    if (strpos($ua, 'Chrome') !== false && strpos($ua, 'Edg') === false) {
        $browser = 'Chrome';
    } elseif (strpos($ua, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($ua, 'Safari') !== false && strpos($ua, 'Chrome') === false) {
        $browser = 'Safari';
    } elseif (strpos($ua, 'Edg') !== false) {
        $browser = 'Edge';
    } elseif (strpos($ua, 'MSIE') !== false || strpos($ua, 'Trident') !== false) {
        $browser = 'Internet Explorer';
    } elseif (strpos($ua, 'Opera') !== false || strpos($ua, 'OPR') !== false) {
        $browser = 'Opera';
    }
    
    if (!isset($browsers[$browser])) {
        $browsers[$browser] = 0;
    }
    $browsers[$browser] += $count;
}
arsort($browsers);

// Déterminer modules disponibles pour filtre
$available_modules = array_keys($modules_usage);
if (empty($available_modules)) {
    $available_modules = ['home', 'calculateur', 'adr', 'qualite', 'user'];
}

// Header
include_once ROOT_PATH . '/templates/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><span class="module-icon">📊</span> Analytics Portail</h1>
        <p class="admin-description">Statistiques d'utilisation du portail Guldagil</p>
    </div>

    <!-- Filtres -->
    <div class="analytics-filters">
        <form action="" method="GET" class="filters-form">
            <div class="filter-group">
                <label for="start_date">Du</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="filter-group">
                <label for="end_date">Au</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="filter-group">
                <label for="module">Module</label>
                <select id="module" name="module">
                    <option value="all" <?= $module_filter === 'all' ? 'selected' : '' ?>>Tous les modules</option>
                    <?php foreach($available_modules as $module): ?>
                    <option value="<?= htmlspecialchars($module) ?>" <?= $module_filter === $module ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($module)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="analytics.php" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Statistiques principales -->
    <div class="analytics-dashboard">
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($visitors_count) ?></div>
                    <div class="stat-label">Visiteurs uniques</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👁️</div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($page_views) ?></div>
                    <div class="stat-label">Pages vues</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <div class="stat-value"><?= $page_views > 0 ? number_format($page_views / max(1, $visitors_count), 1) : '0' ?></div>
                    <div class="stat-label">Pages/visiteur</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <div class="stat-value"><?= ceil((strtotime($end_date) - strtotime($start_date)) / 86400) ?></div>
                    <div class="stat-label">Jours analysés</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et détails -->
    <div class="analytics-details">
        <div class="analytics-row">
            <!-- Pages populaires -->
            <div class="analytics-panel">
                <h2>Pages les plus visitées</h2>
                <div class="table-container">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Vues</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 0;
                            foreach ($popular_pages as $page => $count): 
                                if (++$i > 10) break; // Limiter à 10 résultats
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($page) ?></td>
                                <td><?= number_format($count) ?></td>
                                <td><?= $page_views > 0 ? number_format(($count / $page_views) * 100, 1) : '0' ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($popular_pages)): ?>
                            <tr>
                                <td colspan="3" class="empty-state">Aucune donnée disponible</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Utilisation des modules -->
            <div class="analytics-panel">
                <h2>Utilisation des modules</h2>
                <div class="chart-container">
                    <div class="pie-chart">
                        <?php foreach ($modules_usage as $module => $count): ?>
                        <div class="pie-segment" style="--segment-value: <?= $page_views > 0 ? ($count / $page_views) * 100 : 0 ?>; --segment-color: var(--<?= htmlspecialchars($module) ?>-color, var(--primary-color));">
                            <span class="segment-label"><?= htmlspecialchars(ucfirst($module)) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($modules_usage)): ?>
                        <div class="empty-state">Aucune donnée disponible</div>
                        <?php endif; ?>
                    </div>
                    <div class="chart-legend">
                        <?php foreach ($modules_usage as $module => $count): ?>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: var(--<?= htmlspecialchars($module) ?>-color, var(--primary-color));"></span>
                            <span class="legend-label"><?= htmlspecialchars(ucfirst($module)) ?></span>
                            <span class="legend-value"><?= number_format($count) ?> (<?= $page_views > 0 ? number_format(($count / $page_views) * 100, 1) : '0' ?>%)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="analytics-row">
            <!-- Évolution des visites -->
            <div class="analytics-panel">
                <h2>Évolution des visites</h2>
                <div class="chart-container">
                    <div class="bar-chart">
                        <?php foreach ($daily_views as $date => $count): ?>
                        <div class="bar-item">
                            <div class="bar-value" style="height: <?= max(5, ($count / max($daily_views)) * 100) ?>%">
                                <span class="bar-tooltip"><?= number_format($count) ?> vues</span>
                            </div>
                            <div class="bar-label"><?= $date ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($daily_views)): ?>
                        <div class="empty-state">Aucune donnée disponible</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Navigateurs utilisés -->
            <div class="analytics-panel">
                <h2>Navigateurs utilisés</h2>
                <div class="table-container">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Navigateur</th>
                                <th>Vues</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($browsers as $browser => $count): ?>
                            <tr>
                                <td><?= htmlspecialchars($browser) ?></td>
                                <td><?= number_format($count) ?></td>
                                <td><?= $page_views > 0 ? number_format(($count / $page_views) * 100, 1) : '0' ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($browsers)): ?>
                            <tr>
                                <td colspan="3" class="empty-state">Aucune donnée disponible</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="analytics-row">
            <!-- Sources de trafic -->
            <div class="analytics-panel">
                <h2>Sources de trafic</h2>
                <div class="table-container">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Visites</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 0;
                            foreach ($referrers as $referrer => $count): 
                                if (++$i > 10) break; // Limiter à 10 résultats
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($referrer) ?></td>
                                <td><?= number_format($count) ?></td>
                                <td><?= $page_views > 0 ? number_format(($count / $page_views) * 100, 1) : '0' ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($referrers)): ?>
                            <tr>
                                <td colspan="3" class="empty-state">Aucune donnée disponible</td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td><em>Accès direct</em></td>
                                <td><?= number_format($page_views - array_sum($referrers)) ?></td>
                                <td><?= $page_views > 0 ? number_format((($page_views - array_sum($referrers)) / $page_views) * 100, 1) : '0' ?>%</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="analytics-panel">
                <h2>Actions</h2>
                <div class="action-buttons">
                    <a href="analytics_export.php?start=<?= htmlspecialchars($start_date) ?>&end=<?= htmlspecialchars($end_date) ?>&module=<?= htmlspecialchars($module_filter) ?>" class="btn btn-primary">
                        <span class="btn-icon">📥</span>
                        Exporter les données (CSV)
                    </a>
                    <?php if (!empty($analytics_data)): ?>
                    <button class="btn btn-secondary" onclick="printAnalytics()">
                        <span class="btn-icon">🖨️</span>
                        Imprimer rapport
                    </button>
                    <?php endif; ?>
                    <a href="/admin/" class="btn btn-tertiary">
                        <span class="btn-icon">⬅️</span>
                        Retour au dashboard
                    </a>
                </div>
                
                <div class="maintenance-info">
                    <h3>Maintenance des logs</h3>
                    <p>Taille totale des logs: <strong>
                        <?php
                        $total_size = 0;
                        if (file_exists($analytics_dir)) {
                            foreach (glob($analytics_dir . '*.log') as $file) {
                                $total_size += filesize($file);
                            }
                        }
                        echo number_format($total_size / 1024 / 1024, 2) . ' MB';
                        ?>
                    </strong></p>
                    <p>Nombre de fichiers: <strong>
                        <?php
                        $files_count = 0;
                        if (file_exists($analytics_dir)) {
                            $files_count = count(glob($analytics_dir . '*.log'));
                        }
                        echo $files_count;
                        ?>
                    </strong></p>
                    
                    <div class="maintenance-actions">
                        <a href="analytics_maintenance.php?action=cleanup&before=<?= date('Y-m-d', strtotime('-90 days')) ?>" class="btn btn-warning" onclick="return confirm('Êtes-vous sûr de vouloir supprimer les logs de plus de 90 jours?')">
                            Nettoyer logs (+90 jours)
                        </a>
                        <a href="analytics_maintenance.php?action=optimize" class="btn btn-secondary">
                            Optimiser stockage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript pour l'interface analytics
function printAnalytics() {
    window.print();
}

// Initialisation des graphiques
document.addEventListener('DOMContentLoaded', function() {
    // Animation des barres du graphique
    const bars = document.querySelectorAll('.bar-value');
    setTimeout(() => {
        bars.forEach(bar => {
            bar.style.opacity = '1';
        });
    }, 300);
    
    // Tooltip sur les segments du camembert
    const segments = document.querySelectorAll('.pie-segment');
    segments.forEach(segment => {
        segment.addEventListener('mouseover', function() {
            this.classList.add('hover');
        });
        segment.addEventListener('mouseout', function() {
            this.classList.remove('hover');
        });
    });
});
</script>

<style>
/* Styles spécifiques pour la page analytics */
.analytics-filters {
    background-color: var(--gray-100);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-600);
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid var(--gray-300);
    border-radius: 4px;
    background-color: white;
}

.filter-actions {
    display: flex;
    gap: 8px;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.stat-icon {
    font-size: 24px;
    background-color: var(--primary-blue-light);
    color: white;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-800);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.analytics-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.analytics-panel {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.analytics-panel h2 {
    font-size: 1.25rem;
    color: var(--gray-800);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gray-200);
}

.table-container {
    overflow-x: auto;
}

.analytics-table {
    width: 100%;
    border-collapse: collapse;
}

.analytics-table th {
    background-color: var(--gray-100);
    text-align: left;
    padding: 12px;
    font-weight: 600;
    color: var(--gray-700);
}

.analytics-table td {
    padding: 12px;
    border-bottom: 1px solid var(--gray-200);
    color: var(--gray-800);
}

.analytics-table tr:last-child td {
    border-bottom: none;
}

.empty-state {
    text-align: center;
    color: var(--gray-500);
    padding: 20px;
    font-style: italic;
}

/* Graphiques */
.chart-container {
    height: 300px;
    position: relative;
}

.pie-chart {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    position: relative;
    overflow: hidden;
    margin: 0 auto;
}

.pie-segment {
    position: absolute;
    width: 100%;
    height: 100%;
    transform: rotate(0deg);
    clip-path: polygon(50% 50%, 50% 0%, 100% 0%, 100% 100%, 0% 100%, 0% 0%, 50% 0%);
    transform-origin: 50% 50%;
    background-color: var(--primary-color);
    transition: all 0.3s ease;
}

.pie-segment.hover {
    transform: scale(1.05);
    filter: brightness(1.1);
}

.segment-label {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
    white-space: nowrap;
}

.pie-segment:hover .segment-label {
    opacity: 1;
}

.chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.legend-label {
    color: var(--gray-700);
}

.legend-value {
    color: var(--gray-600);
    font-size: 0.75rem;
}

.bar-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    height: 250px;
    gap: 4px;
}

.bar-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.bar-value {
    width: 100%;
    background-color: var(--primary-blue);
    border-radius: 4px 4px 0 0;
    position: relative;
    opacity: 0;
    transition: opacity 0.5s ease;
}

.bar-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
    white-space: nowrap;
}

.bar-value:hover .bar-tooltip {
    opacity: 1;
}

.bar-label {
    margin-top: 8px;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.btn-icon {
    margin-right: 8px;
}

.maintenance-info {
    background-color: var(--gray-100);
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
}

.maintenance-info h3 {
    font-size: 1rem;
    color: var(--gray-700);
    margin-bottom: 12px;
}

.maintenance-actions {
    display: flex;
    gap: 12px;
    margin-top: 16px;
}

.btn-warning {
    background-color: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background-color: #d97706;
}

/* Variables couleurs pour les modules */
:root {
    --home-color: #3182ce;
    --calculateur-color: #059669;
    --adr-color: #db2777;
    --qualite-color: #8b5cf6;
    --user-color: #f59e0b;
}

/* Styles d'impression */
@media print {
    .filters-form,
    .action-buttons,
    .maintenance-info,
    .admin-header .admin-description {
        display: none;
    }
    
    .analytics-panel {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #eee;
    }
    
    .analytics-row {
        grid-template-columns: 1fr;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .analytics-row {
        grid-template-columns: 1fr;
    }
    
    .filters-form {
        flex-direction: column;
    }
    
    .chart-container {
        height: auto;
    }
    
    .bar-chart {
        height: 200px;
    }
}
</style>

<?php
// Footer
include_once ROOT_PATH . '/templates/footer.php';
?>

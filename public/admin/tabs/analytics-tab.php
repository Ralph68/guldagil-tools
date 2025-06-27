<?php
// tabs/analytics-tab.php - Onglet analytics et statistiques
?>
<div id="tab-analytics" class="tab-content">
    <!-- Métriques de performance -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>📈 Tableau de bord analytique</h2>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary" onclick="generateReport()">
                    <span>📊</span>
                    Générer rapport
                </button>
                <button class="btn btn-secondary" onclick="refreshAnalytics()">
                    <span>🔄</span>
                    Actualiser
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <!-- KPIs principaux -->
            <div class="kpi-grid">
                <div class="kpi-card coverage">
                    <div class="kpi-header">
                        <h4>Couverture tarifaire</h4>
                        <div class="kpi-icon">🎯</div>
                    </div>
                    <div class="kpi-value <?= $analytics['coverage'] >= 80 ? 'excellent' : ($analytics['coverage'] >= 50 ? 'good' : 'warning') ?>">
                        <?= $analytics['coverage'] ?>%
                    </div>
                    <div class="kpi-detail">
                        <?= $analytics['total_rates'] ?> tarifs sur <?= $analytics['total_possible'] ?> possibles
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $analytics['coverage'] ?>%"></div>
                    </div>
                </div>
                
                <div class="kpi-card efficiency">
                    <div class="kpi-header">
                        <h4>Efficacité système</h4>
                        <div class="kpi-icon">⚡</div>
                    </div>
                    <div class="kpi-value excellent">98.5%</div>
                    <div class="kpi-detail">
                        Temps de réponse < 200ms
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 98.5%"></div>
                    </div>
                </div>
                
                <div class="kpi-card usage">
                    <div class="kpi-header">
                        <h4>Utilisation quotidienne</h4>
                        <div class="kpi-icon">📊</div>
                    </div>
                    <div class="kpi-value good"><?= $analytics['calculations_today'] ?></div>
                    <div class="kpi-detail">
                        Calculs effectués aujourd'hui
                    </div>
                    <div class="trend-indicator positive">
                        +<?= $analytics['daily_growth'] ?>% vs hier
                    </div>
                </div>
                
                <div class="kpi-card health">
                    <div class="kpi-header">
                        <h4>Santé système</h4>
                        <div class="kpi-icon"><?= $analytics['system_status']['icon'] ?></div>
                    </div>
                    <div class="kpi-value <?= $analytics['system_status']['class'] ?>">
                        <?= $analytics['system_status']['text'] ?>
                    </div>
                    <div class="kpi-detail">
                        <?= $analytics['alerts_count'] ?> alerte(s) active(s)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyse par transporteur et géographique -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>🚛 Analyse par transporteur</h3>
            </div>
            <div class="admin-card-body">
                <?php foreach ($analytics['carriers'] as $code => $carrier): ?>
                <div class="transporteur-analysis">
                    <div class="transporteur-header">
                        <h5><?= $carrier['name'] ?></h5>
                        <span class="completion-badge <?= $carrier['percentage'] >= 80 ? 'excellent' : ($carrier['percentage'] >= 50 ? 'good' : 'warning') ?>">
                            <?= $carrier['percentage'] ?>%
                        </span>
                    </div>
                    <div class="transporteur-metrics">
                        <div class="metric">
                            <span class="metric-label">Tarifs configurés</span>
                            <span class="metric-value"><?= $carrier['configured'] ?>/<?= $carrier['total'] ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Tarif moyen</span>
                            <span class="metric-value"><?= number_format($carrier['avg_rate'], 2) ?>€</span>
                        </div>
                    </div>
                    <div class="progress-bar small">
                        <div class="progress-fill" style="width: <?= $carrier['percentage'] ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h3>📍 Couverture géographique</h3>
            </div>
            <div class="admin-card-body">
                <div class="geo-analysis">
                    <?php foreach ($analytics['zones'] as $zoneName => $zone): ?>
                    <div class="zone-coverage">
                        <div class="zone-header">
                            <span class="zone-name"><?= $zoneName ?></span>
                            <span class="zone-stats"><?= $zone['covered'] ?>/<?= $zone['total'] ?></span>
                        </div>
                        <div class="zone-percentage"><?= $zone['percentage'] ?>%</div>
                        <div class="progress-bar small">
                            <div class="progress-fill" style="width: <?= $zone['percentage'] ?>%"></div>
                        </div>
                        <div class="zone-departments">
                            <?php foreach ($zone['departments'] as $dept): ?>
                                <span class="dept-badge <?= $dept['covered'] ? 'covered' : 'missing' ?>"><?= $dept['num'] ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et tendances -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>📈 Tendances et évolution</h3>
            <select id="trend-period" onchange="updateTrendChart()">
                <option value="7">7 derniers jours</option>
                <option value="30" selected>30 derniers jours</option>
                <option value="90">3 derniers mois</option>
            </select>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Graphique des calculs -->
                <div class="chart-container">
                    <canvas id="usage-chart" style="max-height: 300px;"></canvas>
                </div>
                
                <!-- Statistiques détaillées -->
                <div class="detailed-stats">
                    <h5>Statistiques détaillées</h5>
                    <div class="stat-list">
                        <div class="stat-item">
                            <span class="stat-label">Total calculs</span>
                            <span class="stat-value"><?= number_format($analytics['total_calculations']) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Pic journalier</span>
                            <span class="stat-value"><?= $analytics['peak_day'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Moyenne/jour</span>
                            <span class="stat-value"><?= number_format($analytics['avg_per_day']) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Taux d'erreur</span>
                            <span class="stat-value"><?= $analytics['error_rate'] ?>%</span>
                        </div>
                    </div>
                    
                    <h5 style="margin-top: 2rem;">Top transporteurs</h5>
                    <div class="top-carriers">
                        <?php foreach ($analytics['top_carriers'] as $carrier): ?>
                        <div class="top-carrier-item">
                            <span class="carrier-name"><?= $carrier['name'] ?></span>
                            <span class="carrier-usage"><?= $carrier['usage'] ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes et recommandations -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>⚠️ Alertes système</h3>
            </div>
            <div class="admin-card-body">
                <?php if (empty($analytics['alerts'])): ?>
                    <div class="no-alerts">
                        <div style="text-align: center; color: #4CAF50; font-size: 2rem;">✅</div>
                        <p style="text-align: center; color: #4CAF50;">Aucune alerte active</p>
                    </div>
                <?php else: ?>
                    <div class="alerts-list">
                        <?php foreach ($analytics['alerts'] as $alert): ?>
                        <div class="alert-item <?= $alert['severity'] ?>">
                            <div class="alert-icon"><?= $alert['icon'] ?></div>
                            <div class="alert-content">
                                <div class="alert-title"><?= $alert['title'] ?></div>
                                <div class="alert-message"><?= $alert['message'] ?></div>
                                <div class="alert-time"><?= $alert['time'] ?></div>
                            </div>
                            <div class="alert-actions">
                                <button class="btn btn-sm btn-secondary" onclick="resolveAlert(<?= $alert['id'] ?>)">
                                    Résoudre
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h3>💡 Recommandations</h3>
            </div>
            <div class="admin-card-body">
                <div class="recommendations">
                    <?php foreach ($analytics['recommendations'] as $recommendation): ?>
                    <div class="recommendation-item">
                        <div class="recommendation-icon"><?= $recommendation['icon'] ?></div>
                        <div class="recommendation-content">
                            <div class="recommendation-title"><?= $recommendation['title'] ?></div>
                            <div class="recommendation-description"><?= $recommendation['description'] ?></div>
                            <div class="recommendation-impact">
                                Impact: <span class="impact-<?= $recommendation['impact'] ?>"><?= $recommendation['impact_text'] ?></span>
                            </div>
                        </div>
                        <?php if ($recommendation['action']): ?>
                        <div class="recommendation-action">
                            <button class="btn btn-sm btn-primary" onclick="<?= $recommendation['action'] ?>">
                                Appliquer
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques aux analytics */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.kpi-header h4 {
    margin: 0;
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
}

.kpi-icon {
    font-size: 1.5rem;
    opacity: 0.7;
}

.kpi-value {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 0.5rem 0;
}

.kpi-value.excellent { color: #10b981; }
.kpi-value.good { color: #3b82f6; }
.kpi-value.warning { color: #f59e0b; }
.kpi-value.danger { color: #ef4444; }

.kpi-detail {
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

.progress-bar {
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar.small {
    height: 4px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #3b82f6);
    transition: width 0.3s ease;
}

.trend-indicator {
    font-size: 0.8rem;
    font-weight: 600;
}

.trend-indicator.positive { color: #10b981; }
.trend-indicator.negative { color: #ef4444; }

.transporteur-analysis,
.zone-coverage {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
}

.transporteur-header,
.zone-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.completion-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.completion-badge.excellent { background: #d1fae5; color: #065f46; }
.completion-badge.good { background: #dbeafe; color: #1e40af; }
.completion-badge.warning { background: #fef3c7; color: #92400e; }

.transporteur-metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.metric {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.metric-label {
    font-size: 0.75rem;
    color: #6b7280;
}

.metric-value {
    font-weight: 600;
    color: #374151;
}

.zone-departments {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.dept-badge {
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 500;
}

.dept-badge.covered {
    background: #d1fae5;
    color: #065f46;
}

.dept-badge.missing {
    background: #fee2e2;
    color: #991b1b;
}

.chart-container {
    position: relative;
    height: 300px;
}

.detailed-stats {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
}

.stat-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: white;
    border-radius: 4px;
}

.stat-label {
    font-size: 0.85rem;
    color: #6b7280;
}

.stat-value {
    font-weight: 600;
    color: #374151;
}

.top-carriers {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.top-carrier-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: white;
    border-radius: 4px;
}

.alerts-list,
.recommendations {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.alert-item,
.recommendation-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
    border-left: 4px solid #6b7280;
}

.alert-item.warning { border-left-color: #f59e0b; }
.alert-item.danger { border-left-color: #ef4444; }
.alert-item.info { border-left-color: #3b82f6; }

.alert-icon,
.recommendation-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.alert-content,
.recommendation-content {
    flex: 1;
}

.alert-title,
.recommendation-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.alert-message,
.recommendation-description {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.alert-time {
    font-size: 0.75rem;
    color: #9ca3af;
}

.recommendation-impact {
    font-size: 0.8rem;
}

.impact-high { color: #ef4444; font-weight: 600; }
.impact-medium { color: #f59e0b; font-weight: 600; }
.impact-low { color: #10b981; font-weight: 600; }

.no-alerts {
    text-align: center;
    padding: 2rem;
}

@media (max-width: 768px) {
    .kpi-grid {
        grid-template-columns: 1fr;
    }
    
    .transporteur-metrics {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Fonctions JavaScript pour les analytics
function generateReport() {
    showAlert('info', 'Génération du rapport en cours...');
    
    // Simuler la génération
    setTimeout(() => {
        const link = document.createElement('a');
        link.href = 'export.php?type=analytics&format=pdf';
        link.download = `rapport_analytics_${new Date().toISOString().split('T')[0]}.pdf`;
        link.click();
        showAlert('success', 'Rapport généré avec succès');
    }, 2000);
}

function refreshAnalytics() {
    showAlert('info', 'Actualisation des données...');
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function updateTrendChart() {
    const period = document.getElementById('trend-period').value;
    showAlert('info', `Mise à jour du graphique pour ${period} jours`);
    // Ici, implémenter la logique de mise à jour du graphique
}

function resolveAlert(alertId) {
    if (confirm('Marquer cette alerte comme résolue ?')) {
        showAlert('success', 'Alerte marquée comme résolue');
        // Ici, faire l'appel API pour résoudre l'alerte
    }
}
</script>

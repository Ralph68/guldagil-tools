<div class="config-options">
                    <label class="config-option">
                        <input type="checkbox" checked>
                        <span>Alertes par email</span>
                    </label>
                    <label class="config-option">
                        <input type="checkbox" checked>
                        <span>Notifications temps réel</span>
                    </label>
                    <label class="config-option">
                        <input type="checkbox">
                        <span>SMS pour alertes critiques</span>
                    </label>
                    <label class="config-option">
                        <input type="checkbox">
                        <span>Rapport hebdomadaire</span>
                    </label>
                </div>
                <div class="config-actions">
                    <input type="email" placeholder="Email notifications" value="admin@guldagil.com">
                    <button class="btn btn-sm btn-primary" onclick="saveNotificationConfig()">
                        💾 Sauvegarder
                    </button>
                </div>
            </div>
            
            <div class="config-card">
                <h6>Seuils d'alerte</h6>
                <div class="threshold-settings">
                    <div class="threshold-item">
                        <label>Temps de réponse DB :</label>
                        <input type="number" value="500" min="100" max="5000">
                        <span>ms</span>
                    </div>
                    <div class="threshold-item">
                        <label>Utilisation disque :</label>
                        <input type="number" value="85" min="50" max="95">
                        <span>%</span>
                    </div>
                    <div class="threshold-item">
                        <label>Quota ADR :</label>
                        <input type="number" value="80" min="50" max="95">
                        <span>%</span>
                    </div>
                    <div class="threshold-item">
                        <label>Erreurs/heure :</label>
                        <input type="number" value="10" min="1" max="100">
                        <span>erreurs</span>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary" onclick="saveThresholdConfig()">
                    💾 Sauvegarder seuils
                </button>
            </div>
            
            <div class="config-card">
                <h6>Rétention des données</h6>
                <div class="retention-settings">
                    <div class="retention-item">
                        <label>Métriques détaillées :</label>
                        <select>
                            <option value="7">7 jours</option>
                            <option value="30" selected>30 jours</option>
                            <option value="90">90 jours</option>
                        </select>
                    </div>
                    <div class="retention-item">
                        <label>Métriques agrégées :</label>
                        <select>
                            <option value="90">90 jours</option>
                            <option value="365" selected>1 an</option>
                            <option value="730">2 ans</option>
                        </select>
                    </div>
                    <div class="retention-item">
                        <label>Logs d'alertes :</label>
                        <select>
                            <option value="180">6 mois</option>
                            <option value="365" selected>1 an</option>
                            <option value="730">2 ans</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary" onclick="saveRetentionConfig()">
                    💾 Sauvegarder rétention
                </button>
            </div>
        </div>
    </div>
    
    <div class="monitoring-section">
        <h5>🔧 Actions rapides</h5>
        <div class="quick-actions">
            <button class="btn btn-primary" onclick="runSystemHealthCheck()">
                🩺 Diagnostic complet
            </button>
            <button class="btn btn-secondary" onclick="generatePerformanceReport()">
                📊 Rapport performance
            </button>
            <button class="btn btn-warning" onclick="clearAllAlerts()">
                🗑️ Effacer alertes
            </button>
            <button class="btn btn-info" onclick="exportMetrics()">
                📤 Exporter métriques
            </button>
            <button class="btn btn-success" onclick="testAlertSystem()">
                🧪 Tester alertes
            </button>
            <button class="btn btn-secondary" onclick="resetMetrics()">
                🔄 Reset métriques
            </button>
        </div>
        <div id="monitoring-actions-result" class="maintenance-result"></div>
    </div>
    
    <div class="monitoring-section">
        <h5>📋 Logs système récents</h5>
        <div class="logs-viewer">
            <div class="logs-controls">
                <button class="btn btn-sm btn-primary" onclick="refreshLogs()">
                    🔄 Actualiser
                </button>
                <button class="btn btn-sm btn-secondary" onclick="clearLogsDisplay()">
                    🗑️ Effacer affichage
                </button>
                <select id="log-level-filter" onchange="filterLogs()">
                    <option value="">Tous les niveaux</option>
                    <option value="error">Erreurs</option>
                    <option value="warning">Avertissements</option>
                    <option value="info">Informations</option>
                </select>
            </div>
            <div class="logs-display" id="system-logs">
                <div class="log-entry info">[15:34:12] INFO - Monitoring système démarré</div>
                <div class="log-entry success">[15:33:45] SUCCESS - Sauvegarde automatique terminée</div>
                <div class="log-entry warning">[15:32:18] WARNING - Quota Heppner à 85%</div>
                <div class="log-entry info">[15:31:02] INFO - Optimisation base de données planifiée</div>
                <div class="log-entry info">[15:30:34] INFO - 3 utilisateurs connectés</div>
            </div>
        </div>
    </div>
    
    <div class="monitoring-section">
        <h5>⚡ Statut des services</h5>
        <div class="services-grid">
            <div class="service-card service-operational">
                <div class="service-header">
                    <span class="service-icon">🗄️</span>
                    <span class="service-name">Base de données</span>
                    <span class="service-status">Opérationnel</span>
                </div>
                <div class="service-metrics">
                    <span>Uptime: 99.9%</span>
                    <span>Latence: 142ms</span>
                </div>
            </div>
            
            <div class="service-card service-operational">
                <div class="service-header">
                    <span class="service-icon">🌐</span>
                    <span class="service-name">Interface Web</span>
                    <span class="service-status">Opérationnel</span>
                </div>
                <div class="service-metrics">
                    <span>Uptime: 100%</span>
                    <span>Chargement: 1.2s</span>
                </div>
            </div>
            
            <div class="service-card service-warning">
                <div class="service-header">
                    <span class="service-icon">📤</span>
                    <span class="service-name">Système backup</span>
                    <span class="service-status">Attention</span>
                </div>
                <div class="service-metrics">
                    <span>Dernière: Il y a 6h</span>
                    <span>Prochaine: Dans 18h</span>
                </div>
            </div>
            
            <div class="service-card service-operational">
                <div class="service-header">
                    <span class="service-icon">📧</span>
                    <span class="service-name">Notifications</span>
                    <span class="service-status">Opérationnel</span>
                </div>
                <div class="service-metrics">
                    <span>Envoyées: 47</span>
                    <span>Échecs: 0</span>
                </div>
            </div>
        </div>
    </div>
</div>
                    <label class="config-option">
                        <input type="checkbox" checked>
                        <span>Alertes par email</span>
                    </label>
                    <label class="config-option">
                        <input type="checkbox" checked>
                        <span>Notifications temps réel</span>
                    </label>
                    <label class="config-option">
                        <input type="checkbox">
                        <span>SMS pour alertes critiques</span>
                    </label>
                    <label class="config-option">
                        <input type="checkbox">
                        <span>Rapport hebdomadaire</span>
                    </label>
                </div>
                <div class="config-actions">
                    <input type="email" placeholder="Email notifications" value="admin@guldagil.com">
                    <button class="btn btn-sm btn-primary" onclick="saveNotificationConfig()">
                        💾 Sauvegarder
                    </button>
                </div>
            </div>
            
            <div class="config-card">
                <h6>Seuils d'alerte</h6>
                <div class="threshold-settings">
                    <div class="threshold-item">
                        <label>Temps de réponse DB :</label>
                        <input type="number" value="500" min="100" max="5000">
                        <span>ms</span>
                    </div>
                    <div class="threshold-item">
                        <label>Utilisation disque :</label>
                        <input type="number" value="85" min="50" max="95">
                        <span>%</span>
                    </div>
                    <div class="threshold-item">
                        <label>Quota ADR :</label>
                        <input type="number" value="80" min="50" max="95">
                        <span>%</span>
                    </div>
                    <div class="threshold-item">
                        <label>Erreurs/heure :</label>
                        <input type="number" value="10" min="1" max="100">
                        <span>erreurs</span>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary" onclick="saveThresholdConfig()">
                    💾 Sauvegarder seuils
                </button>
            </div>
            
            <div class="config-card">
                <h6>Rétention des données</h6>
                <div class="retention-settings">
                    <div class="retention-item">
                        <label>Métriques détaillées :</label>
                        <select>
                            <option value="7">7 jours</option>
                            <option value="30" selected>30 jours</option>
                            <option value="90">90 jours</option>
                        </select>
                    </div>
                    <div class="retention-item">
                        <label>Métriques agrégées :</label>
                        <select>
                            <option value="90">90 jours</option>
                            <option value="365" selected>1 an</option>
                            <option value="730">2 ans</option>
                        </select>
                    </div>
                    <div class="retention-item">
                        <label>Logs d'alertes :</label>
                        <select>
                            <option value="180">6 mois</option>
                            <option value="365" selected>1 an</option>
                            <option value="730">2 ans</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary" onclick="saveRetentionConfig()">
                    💾 Sauvegarder rétention
                </button>
            </div>
        </div>
    </div>
    
    <div class="monitoring-section">
        <h5>🔧 Actions rapides</h5>
        <div class="quick-actions">
            <button class="btn btn-primary" onclick="runSystemHealthCheck()">
                🩺 Diagnostic complet
            </button>
            <button class="btn btn-secondary" onclick="generatePerformanceReport()">
                📊 Rapport performance
            </button>
            <button class="btn btn-warning" onclick="clearAllAlerts()">
                🗑️ Effacer alertes
            </button>
            <button class="btn btn-info" onclick="exportMetrics()">
                📤 Exporter métriques
            </button>
        </div>
        <div id="monitoring-actions-result" class="maintenance-result"></div>
    </div>
</div>

<style>
/* Styles pour l'onglet monitoring */
.monitoring-overview {
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.system-status {
    display: flex;
    align-items: center;
    gap: 20px;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
    animation: pulse-status 2s infinite;
}

@keyframes pulse-status {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.status-indicator.status-warning .status-dot {
    background: #ffc107;
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.3);
}

.status-indicator.status-error .status-dot {
    background: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.3);
}

.uptime-info {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.uptime-value {
    font-size: 1.8rem;
    font-weight: bold;
}

.uptime-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.monitoring-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.monitoring-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #ddd;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.card-header h5 {
    margin: 0;
    color: #333;
    font-size: 1rem;
}

.metrics-list {
    display: grid;
    gap: 12px;
}

.metric {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 10px;
    align-items: center;
    padding: 8px 0;
}

.metric-label {
    font-size: 0.9rem;
    color: #666;
}

.metric-value {
    font-weight: 600;
    color: #333;
    text-align: right;
}

.metric-trend {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 12px;
    min-width: 50px;
    text-align: center;
}

.trend-good {
    background: #d4edda;
    color: #155724;
}

.trend-warning {
    background: #fff3cd;
    color: #856404;
}

.trend-bad {
    background: #f8d7da;
    color: #721c24;
}

.trend-stable {
    background: #e2e3e5;
    color: #383d41;
}

.trend-excellent {
    background: #d1ecf1;
    color: #0c5460;
}

.storage-chart {
    margin-top: 15px;
}

.chart-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #ffc107 70%, #dc3545 100%);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.chart-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #666;
    margin-top: 5px;
}

.monitoring-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.monitoring-section h5 {
    margin: 0 0 20px 0;
    color: #ff6b35;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.alerts-container {
    display: grid;
    gap: 15px;
    margin-bottom: 20px;
}

.alert-item {
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid;
}

.alert-item.alert-warning {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.alert-item.alert-info {
    background: #d1ecf1;
    border-left-color: #17a2b8;
}

.alert-item.alert-success {
    background: #d4edda;
    border-left-color: #28a745;
}

.alert-item.alert-error {
    background: #f8d7da;
    border-left-color: #dc3545;
}

.alert-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.alert-icon {
    font-size: 1.2rem;
}

.alert-title {
    flex: 1;
    font-weight: 600;
    color: #333;
}

.alert-time {
    font-size: 0.8rem;
    color: #666;
}

.alert-description {
    margin-bottom: 10px;
    color: #555;
    font-size: 0.9rem;
    line-height: 1.4;
}

.alert-actions {
    display: flex;
    gap: 8px;
}

.alerts-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
}

.summary-stat {
    text-align: center;
}

.summary-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #ff6b35;
}

.summary-label {
    font-size: 0.9rem;
    color: #666;
}

.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.chart-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #dee2e6;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.chart-header h6 {
    margin: 0;
    color: #333;
}

.chart-period {
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.8rem;
}

.chart-container {
    height: 150px;
    position: relative;
    margin-bottom: 10px;
}

.simple-chart {
    height: 100%;
    position: relative;
    background: linear-gradient(to top, rgba(255, 107, 53, 0.1) 0%, transparent 100%);
    border-radius: 4px;
}

.chart-line {
    position: relative;
    height: 100%;
}

.chart-point {
    position: absolute;
    width: 6px;
    height: 6px;
    background: #ff6b35;
    border-radius: 50%;
    transform: translate(-50%, 50%);
}

.chart-point::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 12px;
    height: 12px;
    background: rgba(255, 107, 53, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    z-index: -1;
}

.dual-chart {
    height: 100%;
    display: flex;
    align-items: end;
    gap: 2px;
    padding: 0 10px;
}

.chart-series {
    display: flex;
    align-items: end;
    gap: 4px;
    flex: 1;
}

.chart-bar {
    flex: 1;
    min-height: 2px;
    border-radius: 2px;
    transition: height 0.3s ease;
}

.cpu-series .chart-bar {
    background: #ff6b35;
}

.memory-series .chart-bar {
    background: #17a2b8;
}

.chart-info,
.chart-legend {
    font-size: 0.8rem;
    color: #666;
    text-align: center;
}

.chart-legend {
    display: flex;
    justify-content: center;
    gap: 20px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.cpu-color {
    background: #ff6b35;
}

.memory-color {
    background: #17a2b8;
}

.config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.config-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #dee2e6;
}

.config-card h6 {
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
}

.config-options {
    display: grid;
    gap: 10px;
    margin-bottom: 15px;
}

.config-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.config-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.config-actions input[type="email"] {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.threshold-settings,
.retention-settings {
    display: grid;
    gap: 12px;
    margin-bottom: 15px;
}

.threshold-item,
.retention-item {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 10px;
    align-items: center;
}

.threshold-item label,
.retention-item label {
    color: #333;
    font-weight: 500;
}

.threshold-item input,
.retention-item select {
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 80px;
}

.threshold-item span {
    font-size: 0.9rem;
    color: #666;
    min-width: 60px;
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.logs-viewer {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    border: 1px solid #ddd;
}

.logs-controls {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.logs-display {
    background: #2d3748;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    height: 200px;
    overflow-y: auto;
    border: 1px solid #4a5568;
}

.log-entry {
    margin-bottom: 5px;
    padding: 2px 0;
}

.log-entry.error {
    color: #ff6b6b;
}

.log-entry.warning {
    color: #ffd93d;
}

.log-entry.info {
    color: #74c0fc;
}

.log-entry.success {
    color: #51cf66;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.service-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #ddd;
    border-left: 4px solid;
}

.service-card.service-operational {
    border-left-color: #28a745;
}

.service-card.service-warning {
    border-left-color: #ffc107;
}

.service-card.service-error {
    border-left-color: #dc3545;
}

.service-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.service-icon {
    font-size: 1.2rem;
}

.service-name {
    flex: 1;
    font-weight: 600;
    color: #333;
}

.service-status {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: 12px;
}

.service-operational .service-status {
    background: #d4edda;
    color: #155724;
}

.service-warning .service-status {
    background: #fff3cd;
    color: #856404;
}

.service-error .service-status {
    background: #f8d7da;
    color: #721c24;
}

.service-metrics {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .monitoring-overview {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .monitoring-grid {
        grid-template-columns: 1fr;
    }
    
    .metric {
        grid-template-columns: 1fr;
        gap: 5px;
        text-align: center;
    }
    
    .charts-container {
        grid-template-columns: 1fr;
    }
    
    .config-grid {
        grid-template-columns: 1fr;
    }
    
    .threshold-item,
    .retention-item {
        grid-template-columns: 1fr;
        gap: 5px;
        text-align: center;
    }
    
    .quick-actions {
        flex-direction: column;
    }
    
    .alert-header {
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .alert-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Variables pour le monitoring
let monitoringInterval = null;
let metricsData = {};

// Initialisation du monitoring
document.addEventListener('DOMContentLoaded', function() {
    startMonitoring();
});

// ========== FONCTIONS DE SURVEILLANCE ==========

function startMonitoring() {
    // Rafraîchir les métriques toutes les 30 secondes
    monitoringInterval = setInterval(updateAllMetrics, 30000);
    
    // Mise à jour initiale
    updateAllMetrics();
    
    console.log('📊 Monitoring temps réel démarré');
}

function stopMonitoring() {
    if (monitoringInterval) {
        clearInterval(monitoringInterval);
        monitoringInterval = null;
        console.log('📊 Monitoring temps réel arrêté');
    }
}

function updateAllMetrics() {
    refreshDbMetrics();
    refreshStorageMetrics();
    refreshAdrMetrics();
    refreshPerformanceMetrics();
}

// ========== MÉTRIQUES BASE DE DONNÉES ==========

function refreshDbMetrics() {
    const metrics = generateDbMetrics();
    
    document.getElementById('db-response-time').textContent = metrics.responseTime + 'ms';
    document.getElementById('db-connections').textContent = metrics.connections;
    document.getElementById('db-cache-ratio').textContent = metrics.cacheRatio + '%';
    document.getElementById('db-queries-sec').textContent = metrics.queriesPerSec;
    
    updateMetricTrends('db', metrics);
}

function generateDbMetrics() {
    return {
        responseTime: Math.floor(Math.random() * 100 + 100),
        connections: Math.floor(Math.random() * 10 + 3) + '/100',
        cacheRatio: (Math.random() * 2 + 97).toFixed(1),
        queriesPerSec: (Math.random() * 20 + 15).toFixed(1)
    };
}

// ========== MÉTRIQUES STOCKAGE ==========

function refreshStorageMetrics() {
    const metrics = generateStorageMetrics();
    
    document.getElementById('disk-usage').textContent = metrics.usage + ' GB';
    document.getElementById('disk-free').textContent = metrics.free + ' GB';
    
    // Mettre à jour la barre de progression
    const usagePercent = (parseFloat(metrics.usage) / 50) * 100;
    const barFill = document.querySelector('.bar-fill');
    if (barFill) {
        barFill.style.width = usagePercent + '%';
        barFill.setAttribute('data-tooltip', `${metrics.usage}GB utilisés / 50GB total`);
    }
}

function generateStorageMetrics() {
    return {
        usage: (Math.random() * 1 + 2).toFixed(1),
        free: (50 - Math.random() * 1 - 2).toFixed(1)
    };
}

// ========== MÉTRIQUES ADR ==========

function refreshAdrMetrics() {
    const metrics = generateAdrMetrics();
    
    document.getElementById('expeditions-today').textContent = metrics.expeditions;
    document.getElementById('active-users').textContent = metrics.users;
    
    updateMetricTrends('adr', metrics);
}

function generateAdrMetrics() {
    return {
        expeditions: Math.floor(Math.random() * 10 + 8),
        users: Math.floor(Math.random() * 3 + 2),
        searches: Math.floor(Math.random() * 30 + 30),
        quotas: Math.floor(Math.random() * 20 + 60)
    };
}

// ========== MÉTRIQUES PERFORMANCE ==========

function refreshPerformanceMetrics() {
    // Simulation de métriques de performance
    const metrics = {
        loadTime: (Math.random() * 0.8 + 0.8).toFixed(1),
        memory: Math.floor(Math.random() * 20 + 35),
        cpu: Math.floor(Math.random() * 10 + 8),
        errors: Math.floor(Math.random() * 2)
    };
    
    metricsData.performance = metrics;
}

function updateMetricTrends(category, newMetrics) {
    // Simuler des tendances basées sur les métriques précédentes
    const trends = calculateTrends(category, newMetrics);
    
    // Mettre à jour les indicateurs de tendance
    Object.keys(trends).forEach(metric => {
        const trendElement = document.querySelector(`[data-metric="${category}-${metric}"]`);
        if (trendElement) {
            trendElement.textContent = trends[metric].display;
            trendElement.className = `metric-trend ${trends[metric].class}`;
        }
    });
}

function calculateTrends(category, newMetrics) {
    // Simuler des tendances
    const trends = {};
    
    Object.keys(newMetrics).forEach(metric => {
        const change = Math.random() - 0.5;
        
        if (Math.abs(change) < 0.1) {
            trends[metric] = { display: '→', class: 'trend-stable' };
        } else if (change > 0) {
            trends[metric] = { display: '↑ +' + Math.abs(change * 10).toFixed(0), class: 'trend-good' };
        } else {
            trends[metric] = { display: '↓ -' + Math.abs(change * 10).toFixed(0), class: 'trend-good' };
        }
    });
    
    return trends;
}

// ========== GESTION DES ALERTES ==========

function acknowledgeAlert(alertId) {
    const alertElement = document.querySelector(`[data-alert-id="${alertId}"]`) || 
                        document.querySelector('.alert-item');
    
    if (alertElement) {
        alertElement.style.opacity = '0.5';
        alertElement.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            alertElement.remove();
            updateAlertsCount();
        }, 300);
    }
    
    showMaintenanceResult('monitoring-actions-result', `✅ Alerte ${alertId} acquittée`, 'success');
}

function clearAllAlerts() {
    if (!confirm('Acquitter toutes les alertes ?')) {
        return;
    }
    
    const alerts = document.querySelectorAll('.alert-item');
    alerts.forEach((alert, index) => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, index * 100);
    });
    
    setTimeout(() => {
        updateAlertsCount();
        showMaintenanceResult('monitoring-actions-result', '✅ Toutes les alertes ont été acquittées', 'success');
    }, 1000);
}

function updateAlertsCount() {
    const remainingAlerts = document.querySelectorAll('.alert-item').length;
    const summaryValue = document.querySelector('.summary-value');
    if (summaryValue) {
        summaryValue.textContent = remainingAlerts;
    }
}

// ========== ACTIONS DÉTAILLÉES ==========

function viewQuotaDetails(transporteur) {
    const quotaInfo = {
        'heppner': {
            current: 850,
            max: 1000,
            percentage: 85,
            trend: '+15% depuis hier',
            expeditions: 12
        }
    };
    
    const info = quotaInfo[transporteur];
    if (info) {
        const details = `📊 DÉTAILS QUOTA ${transporteur.toUpperCase()}

UTILISATION ACTUELLE:
  • Points utilisés: ${info.current}/${info.max} (${info.percentage}%)
  • Tendance: ${info.trend}
  • Expéditions concernées: ${info.expeditions}

SEUILS:
  • Alerte: 80% (800 points) ⚠️ DÉPASSÉ
  • Critique: 95% (950 points)
  • Maximum: 100% (1000 points)

RECOMMANDATIONS:
  • Surveiller les prochaines expéditions
  • Planifier sur d'autres transporteurs
  • Contacter le transporteur si nécessaire`;
        
        showMaintenanceResult('monitoring-actions-result', details, 'warning');
    }
}

function viewBackupDetails() {
    const backupInfo = `💾 DÉTAILS DERNIÈRE SAUVEGARDE

INFORMATIONS:
  • Fichier: backup_adr_20250115_020000.sql
  • Taille: 45.2 MB
  • Durée: 2 minutes 34 secondes
  • Statut: ✅ Terminée avec succès

CONTENU:
  • Tables: 8 tables sauvegardées
  • Lignes: 15,847 lignes
  • Index: Inclus
  • Contraintes: Incluses

VÉRIFICATION:
  • Intégrité: ✅ OK
  • Compression: 67%
  • Stockage: /backups/daily/`;
    
    showMaintenanceResult('monitoring-actions-result', backupInfo, 'info');
}

// ========== GRAPHIQUES ==========

function updateChart(chartType, period) {
    console.log(`Mise à jour graphique ${chartType} pour la période ${period}`);
    
    // Simuler la mise à jour des données du graphique
    const chartContainer = document.getElementById(chartType + '-chart');
    if (chartContainer) {
        // Animation de mise à jour
        chartContainer.style.opacity = '0.5';
        
        setTimeout(() => {
            // Ici on mettrait à jour les vraies données
            chartContainer.style.opacity = '1';
            
            showMaintenanceResult('monitoring-actions-result', 
                `📈 Graphique ${chartType} mis à jour (période: ${period})`, 'info');
        }, 500);
    }
}

// ========== CONFIGURATION ==========

function saveNotificationConfig() {
    const notifications = {
        email: document.querySelector('input[type="email"]').value,
        emailAlerts: document.querySelector('.config-option:nth-child(1) input').checked,
        realTime: document.querySelector('.config-option:nth-child(2) input').checked,
        sms: document.querySelector('.config-option:nth-child(3) input').checked,
        weekly: document.querySelector('.config-option:nth-child(4) input').checked
    };
    
    showMaintenanceResult('monitoring-actions-result', 'Sauvegarde configuration notifications...', 'info');
    
    setTimeout(() => {
        const result = `✅ Configuration notifications sauvegardée

PARAMÈTRES:
  • Email: ${notifications.email}
  • Alertes email: ${notifications.emailAlerts ? 'Activées' : 'Désactivées'}
  • Temps réel: ${notifications.realTime ? 'Activé' : 'Désactivé'}
  • SMS critiques: ${notifications.sms ? 'Activés' : 'Désactivés'}
  • Rapport hebdo: ${notifications.weekly ? 'Activé' : 'Désactivé'}

Les paramètres prendront effet immédiatement.`;
        
        showMaintenanceResult('monitoring-actions-result', result, 'success');
    }, 1000);
}

function saveThresholdConfig() {
    const thresholds = {
        dbResponse: document.querySelector('.threshold-item:nth-child(1) input').value,
        diskUsage: document.querySelector('.threshold-item:nth-child(2) input').value,
        quotaAdr: document.querySelector('.threshold-item:nth-child(3) input').value,
        errorsHour: document.querySelector('.threshold-item:nth-child(4) input').value
    };
    
    showMaintenanceResult('monitoring-actions-result', 'Sauvegarde seuils d\'alerte...', 'info');
    
    setTimeout(() => {
        const result = `⚠️ Seuils d'alerte mis à jour

NOUVEAUX SEUILS:
  • Temps réponse DB: ${thresholds.dbResponse}ms
  • Utilisation disque: ${thresholds.diskUsage}%
  • Quota ADR: ${thresholds.quotaAdr}%
  • Erreurs/heure: ${thresholds.errorsHour}

Les nouveaux seuils sont<?php
// public/adr/modals/tabs/monitoring-tab.php - Onglet surveillance système
?>

<div id="maintenance-tab-monitoring" class="maintenance-tab-content">
    <h4>📊 Monitoring système</h4>
    
    <div class="monitoring-overview">
        <div class="system-status">
            <div class="status-indicator status-operational">
                <span class="status-dot"></span>
                <span class="status-text">Système opérationnel</span>
            </div>
            <div class="uptime-info">
                <span class="uptime-value">99.8%</span>
                <span class="uptime-label">Disponibilité (30j)</span>
            </div>
        </div>
    </div>
    
    <div class="monitoring-grid">
        <div class="monitoring-card">
            <div class="card-header">
                <h5>🗄️ Base de données</h5>
                <button class="btn btn-sm btn-secondary" onclick="refreshDbMetrics()">🔄</button>
            </div>
            <div class="metrics-list">
                <div class="metric">
                    <span class="metric-label">Temps de réponse</span>
                    <span class="metric-value" id="db-response-time">142ms</span>
                    <span class="metric-trend trend-good">↓ -8ms</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Connexions actives</span>
                    <span class="metric-value" id="db-connections">5/100</span>
                    <span class="metric-trend trend-stable">→</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Cache hit ratio</span>
                    <span class="metric-value" id="db-cache-ratio">98.7%</span>
                    <span class="metric-trend trend-good">↑ +0.3%</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Requêtes/sec</span>
                    <span class="metric-value" id="db-queries-sec">23.4</span>
                    <span class="metric-trend trend-stable">→</span>
                </div>
            </div>
        </div>
        
        <div class="monitoring-card">
            <div class="card-header">
                <h5>💾 Stockage</h5>
                <button class="btn btn-sm btn-secondary" onclick="refreshStorageMetrics()">🔄</button>
            </div>
            <div class="metrics-list">
                <div class="metric">
                    <span class="metric-label">Espace utilisé</span>
                    <span class="metric-value" id="disk-usage">2.3 GB</span>
                    <span class="metric-trend trend-warning">↑ +150MB</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Espace libre</span>
                    <span class="metric-value" id="disk-free">47.7 GB</span>
                    <span class="metric-trend trend-stable">→</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Base de données</span>
                    <span class="metric-value">156 MB</span>
                    <span class="metric-trend trend-good">↓ -12MB</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Fichiers temporaires</span>
                    <span class="metric-value">89 MB</span>
                    <span class="metric-trend trend-warning">↑ +23MB</span>
                </div>
            </div>
            <div class="storage-chart">
                <div class="chart-bar">
                    <div class="bar-fill" style="width: 4.6%" data-tooltip="2.3GB utilisés / 50GB total"></div>
                </div>
                <div class="chart-labels">
                    <span>0GB</span>
                    <span>50GB</span>
                </div>
            </div>
        </div>
        
        <div class="monitoring-card">
            <div class="card-header">
                <h5>⚠️ Activité ADR</h5>
                <button class="btn btn-sm btn-secondary" onclick="refreshAdrMetrics()">🔄</button>
            </div>
            <div class="metrics-list">
                <div class="metric">
                    <span class="metric-label">Expéditions aujourd'hui</span>
                    <span class="metric-value" id="expeditions-today">12</span>
                    <span class="metric-trend trend-good">↑ +3</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Utilisateurs actifs</span>
                    <span class="metric-value" id="active-users">3</span>
                    <span class="metric-trend trend-stable">→</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Recherches/heure</span>
                    <span class="metric-value">47</span>
                    <span class="metric-trend trend-good">↑ +12</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Quotas utilisés</span>
                    <span class="metric-value">73%</span>
                    <span class="metric-trend trend-warning">↑ +15%</span>
                </div>
            </div>
        </div>
        
        <div class="monitoring-card">
            <div class="card-header">
                <h5>🚀 Performance</h5>
                <button class="btn btn-sm btn-secondary" onclick="refreshPerformanceMetrics()">🔄</button>
            </div>
            <div class="metrics-list">
                <div class="metric">
                    <span class="metric-label">Temps de chargement</span>
                    <span class="metric-value">1.2s</span>
                    <span class="metric-trend trend-good">↓ -0.3s</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Mémoire utilisée</span>
                    <span class="metric-value">45%</span>
                    <span class="metric-trend trend-stable">→</span>
                </div>
                <div class="metric">
                    <span class="metric-label">CPU utilisé</span>
                    <span class="metric-value">12%</span>
                    <span class="metric-trend trend-good">↓ -3%</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Erreurs/heure</span>
                    <span class="metric-value">0</span>
                    <span class="metric-trend trend-excellent">✓</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="monitoring-section">
        <h5>🚨 Alertes système</h5>
        <div class="alerts-container">
            <div class="alert-item alert-warning">
                <div class="alert-header">
                    <span class="alert-icon">⚠️</span>
                    <span class="alert-title">Quota ADR élevé</span>
                    <span class="alert-time">Il y a 2h</span>
                </div>
                <div class="alert-description">
                    Quota Heppner à 85% (850/1000 points) - Surveillance requise
                </div>
                <div class="alert-actions">
                    <button class="btn btn-sm btn-secondary" onclick="viewQuotaDetails('heppner')">
                        📊 Détails
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="acknowledgeAlert('quota_heppner')">
                        ✓ Acquitter
                    </button>
                </div>
            </div>
            
            <div class="alert-item alert-info">
                <div class="alert-header">
                    <span class="alert-icon">ℹ️</span>
                    <span class="alert-title">Sauvegarde terminée</span>
                    <span class="alert-time">Il y a 6h</span>
                </div>
                <div class="alert-description">
                    Sauvegarde automatique terminée avec succès (45.2 MB)
                </div>
                <div class="alert-actions">
                    <button class="btn btn-sm btn-secondary" onclick="viewBackupDetails()">
                        📄 Détails
                    </button>
                    <button class="btn btn-sm btn-info" onclick="acknowledgeAlert('backup_success')">
                        ✓ Acquitter
                    </button>
                </div>
            </div>
            
            <div class="alert-item alert-success">
                <div class="alert-header">
                    <span class="alert-icon">✅</span>
                    <span class="alert-title">Optimisation terminée</span>
                    <span class="alert-time">Il y a 1j</span>
                </div>
                <div class="alert-description">
                    Optimisation des tables terminée - Performance améliorée de 12%
                </div>
                <div class="alert-actions">
                    <button class="btn btn-sm btn-info" onclick="acknowledgeAlert('optimization_success')">
                        ✓ Acquitter
                    </button>
                </div>
            </div>
        </div>
        
        <div class="alerts-summary">
            <div class="summary-stat">
                <span class="summary-value">1</span>
                <span class="summary-label">Alerte active</span>
            </div>
            <div class="summary-stat">
                <span class="summary-value">2</span>
                <span class="summary-label">Infos</span>
            </div>
            <div class="summary-stat">
                <span class="summary-value">24h</span>
                <span class="summary-label">Depuis dernière alerte critique</span>
            </div>
        </div>
    </div>
    
    <div class="monitoring-section">
        <h5>📈 Graphiques de performance</h5>
        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-header">
                    <h6>Temps de réponse (24h)</h6>
                    <select class="chart-period" onchange="updateChart('response-time', this.value)">
                        <option value="1h">1 heure</option>
                        <option value="24h" selected>24 heures</option>
                        <option value="7d">7 jours</option>
                    </select>
                </div>
                <div class="chart-container" id="response-time-chart">
                    <div class="simple-chart">
                        <div class="chart-line">
                            <div class="chart-point" style="left: 10%; bottom: 70%"></div>
                            <div class="chart-point" style="left: 20%; bottom: 65%"></div>
                            <div class="chart-point" style="left: 30%; bottom: 75%"></div>
                            <div class="chart-point" style="left: 40%; bottom: 60%"></div>
                            <div class="chart-point" style="left: 50%; bottom: 80%"></div>
                            <div class="chart-point" style="left: 60%; bottom: 55%"></div>
                            <div class="chart-point" style="left: 70%; bottom: 85%"></div>
                            <div class="chart-point" style="left: 80%; bottom: 70%"></div>
                            <div class="chart-point" style="left: 90%; bottom: 75%"></div>
                        </div>
                    </div>
                    <div class="chart-info">
                        Moyenne: 142ms | Max: 280ms | Min: 89ms
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h6>Utilisation CPU & Mémoire</h6>
                    <select class="chart-period" onchange="updateChart('cpu-memory', this.value)">
                        <option value="1h">1 heure</option>
                        <option value="24h" selected>24 heures</option>
                        <option value="7d">7 jours</option>
                    </select>
                </div>
                <div class="chart-container" id="cpu-memory-chart">
                    <div class="dual-chart">
                        <div class="chart-series cpu-series">
                            <div class="chart-bar" style="height: 12%"></div>
                            <div class="chart-bar" style="height: 15%"></div>
                            <div class="chart-bar" style="height: 8%"></div>
                            <div class="chart-bar" style="height: 20%"></div>
                            <div class="chart-bar" style="height: 12%"></div>
                            <div class="chart-bar" style="height: 18%"></div>
                            <div class="chart-bar" style="height: 10%"></div>
                            <div class="chart-bar" style="height: 14%"></div>
                        </div>
                        <div class="chart-series memory-series">
                            <div class="chart-bar" style="height: 45%"></div>
                            <div class="chart-bar" style="height: 48%"></div>
                            <div class="chart-bar" style="height: 42%"></div>
                            <div class="chart-bar" style="height: 50%"></div>
                            <div class="chart-bar" style="height: 45%"></div>
                            <div class="chart-bar" style="height: 47%"></div>
                            <div class="chart-bar" style="height: 44%"></div>
                            <div class="chart-bar" style="height: 46%"></div>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-color cpu-color"></span>
                            CPU (12%)
                        </span>
                        <span class="legend-item">
                            <span class="legend-color memory-color"></span>
                            Mémoire (45%)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="monitoring-section">
        <h5>⚙️ Configuration monitoring</h5>
        <div class="config-grid">
            <div class="config-card">
                <h6>Notifications</h6>
                <div class="config-options">

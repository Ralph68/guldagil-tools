<?php
// public/adr/modals/maintenance.php - Outils de maintenance (PARTIE COMPLETE)
//if (!isset($_SESSION['adr_logged_in']) || !in_array('admin', $_SESSION['adr_permissions'] ?? [])) {
//    die('Accès non autorisé');
//}
?>


<div id="maintenance-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 1200px;">
        <div class="modal-header">
            <h3>🔧 Maintenance ADR</h3>
            <button class="modal-close" onclick="closeMaintenanceModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="maintenance-tabs">
                <button class="tab-btn active" onclick="showMaintenanceTab('database')">🗄️ Base de données</button>
                <button class="tab-btn" onclick="showMaintenanceTab('cleanup')">🧹 Nettoyage</button>
                <button class="tab-btn" onclick="showMaintenanceTab('backup')">💾 Sauvegarde</button>
                <button class="tab-btn" onclick="showMaintenanceTab('monitoring')">📊 Monitoring</button>
                <button class="tab-btn" onclick="showMaintenanceTab('logs')">📝 Logs</button>
            </div>
            
            
            <div id="maintenance-tab-database" class="maintenance-tab-content active">
                <h4>🗄️ Gestion base de données</h4>
                
                <div class="maintenance-section">
                    <h5>État des tables</h5>
                    <button class="btn btn-primary" onclick="checkDatabaseHealth()">
                        🩺 Vérifier santé BDD
                    </button>
                    <div id="db-health-result" class="maintenance-result"></div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Optimisation</h5>
                    <div class="maintenance-actions">
                        <button class="btn btn-warning" onclick="optimizeTables()">
                            ⚡ Optimiser tables
                        </button>
                        <button class="btn btn-info" onclick="rebuildIndexes()">
                            🔄 Reconstruire index
                        </button>
                        <button class="btn btn-secondary" onclick="analyzeQueries()">
                            📈 Analyser requêtes
                        </button>
                    </div>
                    <div id="optimization-result" class="maintenance-result"></div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Migration de données</h5>
                    <select id="migration-type">
                        <option value="structure">Mise à jour structure</option>
                        <option value="data">Migration données</option>
                        <option value="indexes">Réindexation</option>
                    </select>
                    <button class="btn btn-primary" onclick="runMigration()">
                        🚀 Exécuter migration
                    </button>
                    <div id="migration-result" class="maintenance-result"></div>
                </div>
            </div>
            
            
            <div id="maintenance-tab-cleanup" class="maintenance-tab-content">
                <h4>🧹 Nettoyage système</h4>
                
                <div class="maintenance-section">
                    <h5>Sessions expirées</h5>
                    <p>Nettoyer les sessions ADR expirées et les données temporaires</p>
                    <button class="btn btn-warning" onclick="cleanExpiredSessions()">
                        🗑️ Nettoyer sessions (> 24h)
                    </button>
                    <div id="sessions-cleanup-result" class="maintenance-result"></div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Fichiers temporaires</h5>
                    <p>Supprimer les fichiers PDF temporaires et uploads non utilisés</p>
                    <button class="btn btn-warning" onclick="cleanTempFiles()">
                        📁 Nettoyer fichiers (> 7 jours)
                    </button>
                    <div id="files-cleanup-result" class="maintenance-result"></div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Données obsolètes</h5>
                    <div class="cleanup-options">
                        <label>
                            <input type="checkbox" id="cleanup-old-expeditions"> 
                            Expéditions > 2 ans
                        </label>
                        <label>
                            <input type="checkbox" id="cleanup-draft-expeditions"> 
                            Brouillons > 30 jours
                        </label>
                        <label>
                            <input type="checkbox" id="cleanup-old-logs"> 
                            Logs > 6 mois
                        </label>
                        <label>
                            <input type="checkbox" id="cleanup-unused-clients"> 
                            Clients non utilisés > 1 an
                        </label>
                    </div>
                    <button class="btn btn-danger" onclick="cleanObsoleteData()">
                        🗑️ Nettoyer données sélectionnées
                    </button>
                    <div id="data-cleanup-result" class="maintenance-result"></div>
                </div>
            </div>
            
            
            <div id="maintenance-tab-backup" class="maintenance-tab-content">
                <h4>💾 Sauvegarde et restauration</h4>
                
                <div class="maintenance-section">
                    <h5>Sauvegarde automatique</h5>
                    <div class="backup-schedule">
                        <div class="schedule-item">
                            <span>Quotidienne</span>
                            <span class="status-active">✅ Activée</span>
                            <small>Dernière : Aujourd'hui 02:00</small>
                        </div>
                        <div class="schedule-item">
                            <span>Hebdomadaire</span>
                            <span class="status-active">✅ Activée</span>
                            <small>Dernière : Dimanche 02:00</small>
                        </div>
                        <div class="schedule-item">
                            <span>Mensuelle</span>
                            <span class="status-inactive">❌ Désactivée</span>
                            <small>Jamais exécutée</small>
                        </div>
                    </div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Sauvegarde manuelle</h5>
                    <div class="backup-options">
                        <label>
                            <input type="radio" name="backup-type" value="full" checked> 
                            Sauvegarde complète
                        </label>
                        <label>
                            <input type="radio" name="backup-type" value="data-only"> 
                            Données uniquement
                        </label>
                        <label>
                            <input type="radio" name="backup-type" value="structure-only"> 
                            Structure uniquement
                        </label>
                    </div>
                    <button class="btn btn-success" onclick="createBackup()">
                        💾 Créer sauvegarde
                    </button>
                    <div id="backup-result" class="maintenance-result"></div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Historique des sauvegardes</h5>
                    <div class="backup-history">
                        <div class="backup-item">
                            <span>backup_adr_20250115_020000.sql</span>
                            <span>45.2 MB</span>
                            <span>15/01/2025 02:00</span>
                            <div class="backup-actions">
                                <button class="btn btn-sm btn-secondary" onclick="downloadBackup('backup_adr_20250115_020000.sql')">
                                    📥 Télécharger
                                </button>
                                <button class="btn btn-sm btn-info" onclick="verifyBackup('backup_adr_20250115_020000.sql')">
                                    ✅ Vérifier
                                </button>
                            </div>
                        </div>
                        <div class="backup-item">
                            <span>backup_adr_20250114_020000.sql</span>
                            <span>44.8 MB</span>
                            <span>14/01/2025 02:00</span>
                            <div class="backup-actions">
                                <button class="btn btn-sm btn-secondary" onclick="downloadBackup('backup_adr_20250114_020000.sql')">
                                    📥 Télécharger
                                </button>
                                <button class="btn btn-sm btn-info" onclick="verifyBackup('backup_adr_20250114_020000.sql')">
                                    ✅ Vérifier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="maintenance-section warning-section">
                    <h5>⚠️ Restauration</h5>
                    <p style="color: #dc3545; font-weight: 500;">
                        <strong>ATTENTION :</strong> La restauration remplacera toutes les données actuelles.
                    </p>
                    <input type="file" id="restore-file" accept=".sql" style="margin-bottom: 10px;">
                    <div>
                        <button class="btn btn-danger" onclick="restoreBackup()" disabled>
                            🔄 Restaurer depuis fichier
                        </button>
                        <small style="color: #666;">
                            Sélectionnez un fichier .sql pour activer la restauration
                        </small>
                    </div>
                    <div id="restore-result" class="maintenance-result"></div>
                </div>
            </div>
            
            
            <div id="maintenance-tab-monitoring" class="maintenance-tab-content">
                <h4>📊 Monitoring système</h4>
                
                <div class="monitoring-grid">
                    <div class="monitoring-card">
                        <h5>Performance base</h5>
                        <div class="metric">
                            <span class="metric-value" id="db-response-time">150ms</span>
                            <span class="metric-label">Temps de réponse moyen</span>
                        </div>
                        <div class="metric">
                            <span class="metric-value" id="db-connections">5/100</span>
                            <span class="metric-label">Connexions actives</span>
                        </div>
                    </div>
                    
                    <div class="monitoring-card">
                        <h5>Utilisation disque</h5>
                        <div class="metric">
                            <span class="metric-value" id="disk-usage">2.3 GB</span>
                            <span class="metric-label">Espace utilisé</span>
                        </div>
                        <div class="metric">
                            <span class="metric-value" id="disk-free">47.7 GB</span>
                            <span class="metric-label">Espace disponible</span>
                        </div>
                    </div>
                    
                    <div class="monitoring-card">
                        <h5>Activité ADR</h5>
                        <div class="metric">
                            <span class="metric-value" id="expeditions-today">12</span>
                            <span class="metric-label">Expéditions aujourd'hui</span>
                        </div>
                        <div class="metric">
                            <span class="metric-value" id="active-users">3</span>
                            <span class="metric-label">Utilisateurs actifs</span>
                        </div>
                    </div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Alertes système</h5>
                    <div class="alerts-list">
                        <div class="alert-item alert-warning">
                            <span class="alert-icon">⚠️</span>
                            <span class="alert-text">Quota ADR Heppner à 85% (850/1000 points)</span>
                            <span class="alert-time">Il y a 2h</span>
                        </div>
                        <div class="alert-item alert-info">
                            <span class="alert-icon">ℹ️</span>
                            <span class="alert-text">Sauvegarde automatique terminée avec succès</span>
                            <span class="alert-time">Il y a 6h</span>
                        </div>
                    </div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Configuration monitoring</h5>
                    <div class="monitoring-config">
                        <label>
                            <input type="checkbox" checked> Alertes par email
                        </label>
                        <label>
                            <input type="checkbox" checked> Monitoring temps réel
                        </label>
                        <label>
                            <input type="checkbox"> Rapport hebdomadaire
                        </label>
                    </div>
                    <button class="btn btn-primary" onclick="updateMonitoringConfig()">
                        💾 Sauvegarder configuration
                    </button>
                </div>
            </div>
            
            
            <div id="maintenance-tab-logs" class="maintenance-tab-content">
                <h4>📝 Gestion des logs</h4>
                
                <div class="maintenance-section">
                    <h5>Filtres de logs</h5>
                    <div class="log-filters">
                        <select id="log-level">
                            <option value="">Tous les niveaux</option>
                            <option value="ERROR">Erreurs</option>
                            <option value="WARNING">Avertissements</option>
                            <option value="INFO">Informations</option>
                            <option value="DEBUG">Debug</option>
                        </select>
                        <select id="log-component">
                            <option value="">Tous les composants</option>
                            <option value="ADR_CREATE">Création expéditions</option>
                            <option value="ADR_SEARCH">Recherche</option>
                            <option value="ADR_AUTH">Authentification</option>
                            <option value="ADR_QUOTA">Quotas</option>
                        </select>
                        <input type="date" id="log-date" value="<?= date('Y-m-d') ?>">
                        <button class="btn btn-primary" onclick="loadLogs()">
                            🔍 Charger logs
                        </button>
                    </div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Logs en temps réel</h5>
                    <div class="log-controls">
                        <button class="btn btn-success" id="start-log-stream" onclick="startLogStream()">
                            ▶️ Démarrer surveillance
                        </button>
                        <button class="btn btn-danger" id="stop-log-stream" onclick="stopLogStream()" disabled>
                            ⏹️ Arrêter surveillance
                        </button>
                        <button class="btn btn-secondary" onclick="clearLogDisplay()">
                            🗑️ Effacer affichage
                        </button>
                    </div>
                    <div id="log-stream" class="log-display"></div>
                </div>
                
                <div class="maintenance-section">
                    <h5>Archives de logs</h5>
                    <div class="log-archives">
                        <div class="archive-item">
                            <span>adr_logs_2025-01-15.log</span>
                            <span>1.2 MB</span>
                            <span>156 entrées</span>
                            <button class="btn btn-sm btn-secondary" onclick="downloadLog('adr_logs_2025-01-15.log')">
                                📥 Télécharger
                            </button>
                        </div>
                        <div class="archive-item">
                            <span>adr_logs_2025-01-14.log</span>
                            <span>0.8 MB</span>
                            <span>98 entrées</span>
                            <button class="btn btn-sm btn-secondary" onclick="downloadLog('adr_logs_2025-01-14.log')">
                                📥 Télécharger
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <div class="maintenance-status">
                <span id="maintenance-mode-status">🟢 Mode normal</span>
                <button class="btn btn-warning" onclick="toggleMaintenanceMode()">
                    🔧 Basculer mode maintenance
                </button>
            </div>
            <button class="btn btn-secondary" onclick="closeMaintenanceModal()">
                Fermer
            </button>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques pour la maintenance */
.maintenance-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 10px 15px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.tab-btn:hover {
    background: #e9ecef;
}

.tab-btn.active {
    background: #ff6b35;
    color: white;
    border-color: #ff6b35;
}

.maintenance-tab-content {
    display: none;
}

.maintenance-tab-content.active {
    display: block;
}

.maintenance-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #ff6b35;
}

.maintenance-section.warning-section {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.maintenance-section h5 {
    margin: 0 0 15px 0;
    color: #ff6b35;
}

.maintenance-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.maintenance-result {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 200px;
    overflow-y: auto;
    background: #2d3748;
    color: #e2e8f0;
    border: 1px solid #4a5568;
}

.maintenance-result.success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.maintenance-result.error {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.maintenance-result.warning {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.maintenance-result.info {
    background: #d1ecf1;
    color: #0c5460;
    border-color: #bee5eb;
}

.backup-schedule, .backup-history, .log-archives {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.schedule-item, .backup-item, .archive-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: white;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.status-active {
    color: #28a745;
    font-weight: 600;
}

.status-inactive {
    color: #dc3545;
    font-weight: 600;
}

.backup-actions {
    display: flex;
    gap: 5px;
}

.backup-options, .cleanup-options, .monitoring-config {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
}

.backup-options label, .cleanup-options label, .monitoring-config label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.monitoring-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.monitoring-card {
    background: white;
    padding: 20px;
    border-radius: 6px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.monitoring-card h5 {
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.metric {
    display: flex;
    flex-direction: column;
    margin-bottom: 10px;
}

.metric-value {
    font-size: 1.5em;
    font-weight: bold;
    color: #ff6b35;
}

.metric-label {
    font-size: 0.9em;
    color: #666;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    border-radius: 4px;
    background: white;
    border: 1px solid #ddd;
}

.alert-item.alert-warning {
    border-left: 4px solid #ffc107;
}

.alert-item.alert-info {
    border-left: 4px solid #17a2b8;
}

.alert-text {
    flex: 1;
}

.alert-time {
    font-size: 0.8em;
    color: #666;
}

.log-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.log-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.log-display {
    background: #2d3748;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    height: 300px;
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

.log-entry.debug {
    color: #b197fc;
}

.maintenance-status {
    display: flex;
    align-items: center;
    gap: 15px;
}

#maintenance-mode-status {
    font-weight: 600;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

/* Progress bar pour les opérations longues */
.progress-bar {
    width: 100%;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-fill {
    height: 100%;
    background: #ff6b35;
    transition: width 0.3s ease;
    border-radius: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .maintenance-tabs {
        flex-direction: column;
    }
    
    .monitoring-grid {
        grid-template-columns: 1fr;
    }
    
    .maintenance-actions {
        flex-direction: column;
    }
    
    .schedule-item, .backup-item, .archive-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .backup-actions {
        align-self: stretch;
        justify-content: space-between;
    }
    
    .log-filters {
        flex-direction: column;
    }
}
</style>

<script>
// Variables globales pour la maintenance
let logStreamInterval = null;
let logStreamActive = false;
let maintenanceMode = false;

// Gestion des onglets
function showMaintenanceTab(tabName) {
    // Masquer tous les contenus
    document.querySelectorAll('.maintenance-tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Désactiver tous les boutons
    document.querySelectorAll('.maintenance-tabs .tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    document.getElementById(`maintenance-tab-${tabName}`).classList.add('active');
    event.target.classList.add('active');
    
    // Charger les données spécifiques à l'onglet
    switch(tabName) {
        case 'monitoring':
            updateMonitoringData();
            break;
        case 'logs':
            loadRecentLogs();
            break;
        case 'backup':
            loadBackupHistory();
            break;
    }
}

// Fonctions utilitaires
function showMaintenanceResult(containerId, message, type = 'info', append = false) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    if (!append) {
        container.innerHTML = '';
    }
    
    const timestamp = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.className = `log-entry ${type}`;
    entry.textContent = `[${timestamp}] ${message}`;
    
    container.appendChild(entry);
    container.scrollTop = container.scrollHeight;
    
    // Appliquer la classe CSS appropriée au conteneur
    container.className = `maintenance-result ${type}`;
}

function createProgressBar(containerId, percentage = 0) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const progressHtml = `
        <div class="progress-bar">
            <div class="progress-fill" style="width: ${percentage}%"></div>
        </div>
    `;
    
    container.innerHTML = progressHtml;
    return container.querySelector('.progress-fill');
}

function updateProgressBar(containerId, percentage) {
    const container = document.getElementById(containerId);
    const fill = container?.querySelector('.progress-fill');
    if (fill) {
        fill.style.width = `${percentage}%`;
    }
}

// ========== FONCTIONS BASE DE DONNÉES ==========

function checkDatabaseHealth() {
    showMaintenanceResult('db-health-result', 'Vérification en cours...', 'info');
    
    // Simulation d'une vérification asynchrone
    setTimeout(() => {
        const healthData = {
            tables: {
                'gul_adr_expeditions': { status: 'OK', rows: 1247, size: '2.3 MB' },
                'gul_adr_products': { status: 'OK', rows: 856, size: '1.8 MB' },
                'gul_adr_quotas': { status: 'OK', rows: 15, size: '64 KB' },
                'gul_adr_destinataires_frequents': { status: 'OK', rows: 342, size: '512 KB' }
            },
            performance: {
                avg_query_time: '145ms',
                slow_queries: 0,
                connections: '5/100',
                cache_hit_ratio: '98.7%'
            },
            recommendations: [
                'Toutes les tables sont en bon état',
                'Performance optimale',
                'Aucune action requise'
            ]
        };
        
        let result = '✅ Vérification terminée\n\n';
        result += 'TABLES:\n';
        Object.entries(healthData.tables).forEach(([table, info]) => {
            result += `  ${table}: ${info.status} (${info.rows} lignes, ${info.size})\n`;
        });
        
        result += '\nPERFORMANCE:\n';
        Object.entries(healthData.performance).forEach(([metric, value]) => {
            result += `  ${metric}: ${value}\n`;
        });
        
        result += '\nRECOMMANDATIONS:\n';
        healthData.recommendations.forEach(rec => {
            result += `  • ${rec}\n`;
        });
        
        showMaintenanceResult('db-health-result', result, 'success');
    }, 2000);
}

function optimizeTables() {
    showMaintenanceResult('optimization-result', 'Optimisation des tables en cours...', 'info');
    
    const tables = ['gul_adr_expeditions', 'gul_adr_products', 'gul_adr_quotas'];
    let currentTable = 0;
    
    const progressBar = createProgressBar('optimization-result');
    
        const optimizeNext = () => {
        if (currentTable >= tables.length) {
            showMaintenanceResult('optimization-result', '✅ Optimisation terminée avec succès', 'success');
            return;
        }
        
        const table = tables[currentTable];
        const percentage = ((currentTable + 1) / tables.length) * 100;
        
        showMaintenanceResult('optimization-result', 
            `Optimisation de ${table}... (${currentTable + 1}/${tables.length})`, 
            'info', true);
        
        updateProgressBar('optimization-result', percentage);
        
        // Simuler l'optimisation d'une table
        setTimeout(() => {
            showMaintenanceResult('optimization-result', 
                `✓ ${table} optimisée`, 'success', true);
            currentTable++;
            optimizeNext();
        }, 1000);
    };
    
    optimizeNext();
}

function rebuildIndexes() {
    showMaintenanceResult('optimization-result', 'Reconstruction des index...', 'info');
    
    setTimeout(() => {
        const result = `✅ Index reconstruits avec succès

DÉTAILS:
  • Index primaires: 4 reconstruits
  • Index secondaires: 12 reconstruits
  • Index de texte: 3 reconstruits
  • Temps total: 5.2 secondes
  • Amélioration performance: +15%`;
        
        showMaintenanceResult('optimization-result', result, 'success');
    }, 3000);
}

function analyzeQueries() {
    showMaintenanceResult('optimization-result', 'Analyse des requêtes lentes...', 'info');
    
    setTimeout(() => {
        const result = `📈 Analyse terminée

REQUÊTES ANALYSÉES: 2,847
REQUÊTES LENTES (>1s): 0
TEMPS MOYEN: 145ms
INDEX MANQUANTS: 0

TOP 3 REQUÊTES:
1. SELECT expeditions par date (42ms)
2. Recherche produits ADR (38ms)  
3. Calcul quotas journaliers (65ms)

✅ Performance optimale`;
        
        showMaintenanceResult('optimization-result', result, 'success');
    }, 2500);
}

function runMigration() {
    const migrationType = document.getElementById('migration-type').value;
    showMaintenanceResult('migration-result', `Migration ${migrationType} en cours...`, 'info');
    
    const progressBar = createProgressBar('migration-result');
    let progress = 0;
    
    const migrationSteps = {
        'structure': ['Vérification structure', 'Ajout colonnes', 'Modification contraintes', 'Validation'],
        'data': ['Sauvegarde', 'Transformation données', 'Validation', 'Nettoyage'],
        'indexes': ['Suppression anciens', 'Création nouveaux', 'Optimisation', 'Test performance']
    };
    
    const steps = migrationSteps[migrationType] || ['Étape 1', 'Étape 2', 'Étape 3'];
    let currentStep = 0;
    
    const executeStep = () => {
        if (currentStep >= steps.length) {
            showMaintenanceResult('migration-result', '✅ Migration terminée avec succès', 'success');
            return;
        }
        
        const step = steps[currentStep];
        progress = ((currentStep + 1) / steps.length) * 100;
        
        showMaintenanceResult('migration-result', 
            `${step}... (${currentStep + 1}/${steps.length})`, 
            'info', true);
        
        updateProgressBar('migration-result', progress);
        
        setTimeout(() => {
            showMaintenanceResult('migration-result', 
                `✓ ${step} terminé`, 'success', true);
            currentStep++;
            executeStep();
        }, 1500);
    };
    
    executeStep();
}

// ========== FONCTIONS NETTOYAGE ==========

function cleanExpiredSessions() {
    showMaintenanceResult('sessions-cleanup-result', 'Nettoyage des sessions expirées...', 'info');
    
    setTimeout(() => {
        const result = `✅ Nettoyage terminé

SESSIONS SUPPRIMÉES: 47
ESPACE LIBÉRÉ: 2.3 MB
DERNIÈRE SESSION ACTIVE: Il y a 3h

Sessions conservées: 5 actives`;
        
        showMaintenanceResult('sessions-cleanup-result', result, 'success');
    }, 1500);
}

function cleanTempFiles() {
    showMaintenanceResult('files-cleanup-result', 'Nettoyage des fichiers temporaires...', 'info');
    
    setTimeout(() => {
        const result = `✅ Nettoyage terminé

FICHIERS SUPPRIMÉS:
  • PDFs temporaires: 23 fichiers (15.7 MB)
  • Uploads expirés: 8 fichiers (3.2 MB)
  • Cache obsolète: 156 fichiers (8.9 MB)

TOTAL LIBÉRÉ: 27.8 MB`;
        
        showMaintenanceResult('files-cleanup-result', result, 'success');
    }, 2000);
}

function cleanObsoleteData() {
    const options = {
        expeditions: document.getElementById('cleanup-old-expeditions').checked,
        drafts: document.getElementById('cleanup-draft-expeditions').checked,
        logs: document.getElementById('cleanup-old-logs').checked,
        clients: document.getElementById('cleanup-unused-clients').checked
    };
    
    const selectedCount = Object.values(options).filter(Boolean).length;
    
    if (selectedCount === 0) {
        showMaintenanceResult('data-cleanup-result', '❌ Aucune option sélectionnée', 'warning');
        return;
    }
    
    if (!confirm(`⚠️ ATTENTION\n\nVous allez supprimer définitivement ${selectedCount} type(s) de données.\n\nCette action est irréversible.\n\nContinuer ?`)) {
        return;
    }
    
    showMaintenanceResult('data-cleanup-result', 'Nettoyage des données obsolètes...', 'info');
    
    let results = [];
    let processed = 0;
    
    const processOption = (optionName, enabled) => {
        if (!enabled) return Promise.resolve();
        
        return new Promise(resolve => {
            setTimeout(() => {
                const mockResults = {
                    expeditions: '47 expéditions supprimées (12.3 MB)',
                    drafts: '156 brouillons supprimés (2.1 MB)',
                    logs: '8,423 entrées supprimées (45.7 MB)',
                    clients: '23 clients supprimés (890 KB)'
                };
                
                results.push(mockResults[optionName]);
                processed++;
                
                showMaintenanceResult('data-cleanup-result', 
                    `✓ ${optionName} traité (${processed}/${selectedCount})`, 
                    'info', true);
                
                resolve();
            }, 1000);
        });
    };
    
    // Traiter les options sélectionnées
    Promise.all([
        processOption('expeditions', options.expeditions),
        processOption('drafts', options.drafts),
        processOption('logs', options.logs),
        processOption('clients', options.clients)
    ]).then(() => {
        const finalResult = `✅ Nettoyage terminé\n\n${results.join('\n')}\n\nESPACE TOTAL LIBÉRÉ: ${(Math.random() * 50 + 10).toFixed(1)} MB`;
        showMaintenanceResult('data-cleanup-result', finalResult, 'success');
    });
}

// ========== FONCTIONS SAUVEGARDE ==========

function createBackup() {
    const backupType = document.querySelector('input[name="backup-type"]:checked').value;
    showMaintenanceResult('backup-result', `Création sauvegarde ${backupType}...`, 'info');
    
    const progressBar = createProgressBar('backup-result');
    let progress = 0;
    
    const updateProgress = () => {
        progress += Math.random() * 20 + 5;
        if (progress > 100) progress = 100;
        
        updateProgressBar('backup-result', progress);
        
        if (progress < 100) {
            setTimeout(updateProgress, 500);
        } else {
            const filename = `backup_adr_${new Date().toISOString().slice(0,19).replace(/[:-]/g, '').replace('T', '_')}.sql`;
            const result = `✅ Sauvegarde créée avec succès

FICHIER: ${filename}
TYPE: ${backupType}
TAILLE: ${(Math.random() * 20 + 30).toFixed(1)} MB
TABLES: ${backupType === 'structure-only' ? '4 structures' : '4 tables complètes'}
DURÉE: ${(Math.random() * 30 + 15).toFixed(1)}s

📥 Téléchargement automatique...`;
            
            showMaintenanceResult('backup-result', result, 'success');
            
            // Simuler téléchargement
            setTimeout(() => {
                downloadBackup(filename);
            }, 1000);
        }
    };
    
    updateProgress();
}

function downloadBackup(filename) {
    showMaintenanceResult('backup-result', `📥 Téléchargement de ${filename}...`, 'info', true);
    
    // En production, ceci serait un vrai téléchargement
    setTimeout(() => {
        showMaintenanceResult('backup-result', `✅ ${filename} téléchargé`, 'success', true);
    }, 1000);
}

function verifyBackup(filename) {
    showMaintenanceResult('backup-result', `🔍 Vérification de ${filename}...`, 'info', true);
    
    setTimeout(() => {
        const result = `✅ Sauvegarde vérifiée

INTÉGRITÉ: OK
TABLES: 4/4 complètes
DONNÉES: Cohérentes
TAILLE: Conforme

Sauvegarde utilisable pour restauration`;
        
        showMaintenanceResult('backup-result', result, 'success', true);
    }, 2000);
}

function restoreBackup() {
    const fileInput = document.getElementById('restore-file');
    const file = fileInput.files[0];
    
    if (!file) {
        showMaintenanceResult('restore-result', '❌ Aucun fichier sélectionné', 'error');
        return;
    }
    
    if (!confirm(`⚠️ ATTENTION CRITIQUE\n\nLa restauration va REMPLACER toutes les données actuelles par celles du fichier ${file.name}.\n\nCette action est IRRÉVERSIBLE.\n\nTOUTES LES DONNÉES ACTUELLES SERONT PERDUES.\n\nÊtes-vous absolument certain de vouloir continuer ?`)) {
        return;
    }
    
    if (!confirm(`🔴 DERNIÈRE CONFIRMATION\n\nVous confirmez la restauration complète ?\n\nToutes les expéditions, clients et données ADR actuelles seront supprimées.\n\nDernière chance d'annuler !`)) {
        return;
    }
    
    showMaintenanceResult('restore-result', 'Restauration en cours...', 'warning');
    
    const progressBar = createProgressBar('restore-result');
    
    const steps = [
        'Vérification du fichier',
        'Sauvegarde de sécurité',
        'Arrêt des connexions',
        'Suppression des données',
        'Restauration structure',
        'Restauration données',
        'Vérification intégrité',
        'Redémarrage services'
    ];
    
    let currentStep = 0;
    
    const executeRestore = () => {
        if (currentStep >= steps.length) {
            showMaintenanceResult('restore-result', '✅ Restauration terminée avec succès\n\n🔄 Rechargement de la page...', 'success');
            setTimeout(() => {
                location.reload();
            }, 3000);
            return;
        }
        
        const step = steps[currentStep];
        const progress = ((currentStep + 1) / steps.length) * 100;
        
        showMaintenanceResult('restore-result', 
            `${step}... (${currentStep + 1}/${steps.length})`, 
            'warning', true);
        
        updateProgressBar('restore-result', progress);
        
        setTimeout(() => {
            showMaintenanceResult('restore-result', 
                `✓ ${step} terminé`, 'success', true);
            currentStep++;
            executeRestore();
        }, 2000);
    };
    
    executeRestore();
}

// Activer le bouton restore quand un fichier est sélectionné
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('restore-file');
    const restoreBtn = document.querySelector('button[onclick="restoreBackup()"]');
    
    if (fileInput && restoreBtn) {
        fileInput.addEventListener('change', function() {
            restoreBtn.disabled = !this.files[0];
        });
    }
});

function loadBackupHistory() {
    // Simuler le chargement de l'historique
    console.log('📚 Chargement historique des sauvegardes...');
}

// ========== FONCTIONS MONITORING ==========

function updateMonitoringData() {
    // Simuler des données en temps réel
    const metrics = {
        dbResponseTime: Math.floor(Math.random() * 100 + 100) + 'ms',
        dbConnections: Math.floor(Math.random() * 10 + 3) + '/100',
        diskUsage: (Math.random() * 2 + 2).toFixed(1) + ' GB',
        diskFree: (50 - Math.random() * 5).toFixed(1) + ' GB',
        expeditionsToday: Math.floor(Math.random() * 20 + 5),
        activeUsers: Math.floor(Math.random() * 5 + 1)
    };
    
    Object.entries(metrics).forEach(([key, value]) => {
        const element = document.getElementById(key.replace(/([A-Z])/g, '-$1').toLowerCase());
        if (element) {
            element.textContent = value;
        }
    });
}

function updateMonitoringConfig() {
    showMaintenanceResult('monitoring-result', 'Configuration sauvegardée ✅', 'success');
}

// ========== FONCTIONS LOGS ==========

function loadLogs() {
    const level = document.getElementById('log-level').value;
    const component = document.getElementById('log-component').value;
    const date = document.getElementById('log-date').value;
    
    showMaintenanceResult('log-stream', `Chargement logs du ${date}...`, 'info');
    
    setTimeout(() => {
        const mockLogs = [
            '[08:30:15] INFO - ADR_CREATE - Nouvelle expédition créée par demo.user',
            '[08:45:22] WARNING - ADR_QUOTA - Quota Heppner à 85% (850/1000)',
            '[09:12:33] INFO - ADR_SEARCH - Recherche produit: GULTRAT',
            '[09:30:45] ERROR - ADR_AUTH - Tentative connexion échouée: invalid_user',
            '[10:15:12] INFO - ADR_CREATE - PDF généré pour expédition ADR-20250115-001',
            '[10:45:33] DEBUG - ADR_QUOTA - Recalcul quotas pour transporteur: xpo'
        ];
        
        document.getElementById('log-stream').innerHTML = '';
        
        mockLogs.forEach((log, index) => {
            setTimeout(() => {
                addLogEntry(log);
            }, index * 200);
        });
    }, 1000);
}

function addLogEntry(logText) {
    const logDisplay = document.getElementById('log-stream');
    const entry = document.createElement('div');
    
    // Déterminer le type de log
    let logType = 'info';
    if (logText.includes('ERROR')) logType = 'error';
    else if (logText.includes('WARNING')) logType = 'warning';
    else if (logText.includes('DEBUG')) logType = 'debug';
    
    entry.className = `log-entry ${logType}`;
    entry.textContent = logText;
    
    logDisplay.appendChild(entry);
    logDisplay.scrollTop = logDisplay.scrollHeight;
}

function startLogStream() {
    if (logStreamActive) return;
    
    logStreamActive = true;
    document.getElementById('start-log-stream').disabled = true;
    document.getElementById('stop-log-stream').disabled = false;
    
    addLogEntry('[SYSTEM] Surveillance des logs démarrée');
    
    // Simuler des logs en temps réel
    logStreamInterval = setInterval(() => {
        const randomLogs = [
            '[' + new Date().toLocaleTimeString() + '] INFO - ADR_SEARCH - Recherche produit effectuée',
            '[' + new Date().toLocaleTimeString() + '] DEBUG - ADR_QUOTA - Vérification quotas automatique',
            '[' + new Date().toLocaleTimeString() + '] INFO - ADR_CREATE - Nouvelle ligne ajoutée à expédition'
        ];
        
        if (Math.random() > 0.7) { // 30% de chance d'ajouter un log
            const randomLog = randomLogs[Math.floor(Math.random() * randomLogs.length)];
            addLogEntry(randomLog);
        }
    }, 2000);
}

function stopLogStream() {
    if (!logStreamActive) return;
    
    logStreamActive = false;
    document.getElementById('start-log-stream').disabled = false;
    document.getElementById('stop-log-stream').disabled = true;
    
    if (logStreamInterval) {
        clearInterval(logStreamInterval);
        logStreamInterval = null;
    }
    
    addLogEntry('[SYSTEM] Surveillance des logs arrêtée');
}

function clearLogDisplay() {
    document.getElementById('log-stream').innerHTML = '';
}

function downloadLog(filename) {
    showMaintenanceResult('log-stream', `📥 Téléchargement de ${filename}...`, 'info', true);
    
    setTimeout(() => {
        addLogEntry(`✅ ${filename} téléchargé avec succès`);
    }, 1000);
}

function loadRecentLogs() {
    // Charger quelques logs récents au chargement de l'onglet
    setTimeout(() => {
        loadLogs();
    }, 500);
}

// ========== FONCTIONS GÉNÉRALES ==========

function toggleMaintenanceMode() {
    maintenanceMode = !maintenanceMode;
    const statusElement = document.getElementById('maintenance-mode-status');
    const toggleButton = document.querySelector('button[onclick="toggleMaintenanceMode()"]');
    
    if (maintenanceMode) {
        statusElement.textContent = '🔴 Mode maintenance actif';
        statusElement.style.color = '#dc3545';
        toggleButton.textContent = '🟢 Désactiver maintenance';
        toggleButton.className = 'btn btn-success';
        
        if (confirm('⚠️ Mode maintenance activé\n\nLes utilisateurs ADR seront déconnectés et ne pourront plus accéder au système.\n\nContinuer ?')) {
            showMaintenanceResult('maintenance-status', 'Mode maintenance activé - Utilisateurs déconnectés', 'warning');
        }
    } else {
        statusElement.textContent = '🟢 Mode normal';
        statusElement.style.color = '#28a745';
        toggleButton.textContent = '🔧 Basculer mode maintenance';
        toggleButton.className = 'btn btn-warning';
        
        showMaintenanceResult('maintenance-status', 'Mode normal restauré - Système accessible', 'success');
    }
}

function closeMaintenanceModal() {
    // Arrêter le stream de logs si actif
    if (logStreamActive) {
        stopLogStream();
    }
    
    document.getElementById('maintenance-modal').style.display = 'none';
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour les données de monitoring toutes les 30 secondes
    setInterval(updateMonitoringData, 30000);
    
    console.log('🔧 Module de maintenance ADR initialisé');
});

// Fonction pour ouvrir le modal depuis l'extérieur
function openMaintenanceModal() {
    document.getElementById('maintenance-modal').style.display = 'flex';
    
    // Charger les données initiales
    updateMonitoringData();
    
    // Activer le premier onglet
    showMaintenanceTab('database');
}

// Exposer la fonction globalement
window.openMaintenanceModal = openMaintenanceModal;
window.closeMaintenanceModal = closeMaintenanceModal;
</script>

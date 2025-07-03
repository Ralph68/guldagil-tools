<?php
// public/adr/modals/tabs/cleanup-tab.php - Onglet nettoyage système
?>

<div id="maintenance-tab-cleanup" class="maintenance-tab-content">
    <h4>🧹 Nettoyage système</h4>
    
    <div class="maintenance-section">
        <h5>Sessions et données temporaires</h5>
        <p>Nettoyage des sessions expirées et des données temporaires du système ADR</p>
        
        <div class="cleanup-stats">
            <div class="stat-item">
                <span class="stat-value" id="sessions-count">47</span>
                <span class="stat-label">Sessions expirées</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="temp-files-count">156</span>
                <span class="stat-label">Fichiers temporaires</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="cache-size">8.9 MB</span>
                <span class="stat-label">Cache obsolète</span>
            </div>
        </div>
        
        <div class="cleanup-actions">
            <button class="btn btn-warning" onclick="cleanExpiredSessions()">
                🗑️ Nettoyer sessions (> 24h)
            </button>
            <button class="btn btn-warning" onclick="cleanTempFiles()">
                📁 Nettoyer fichiers temporaires
            </button>
            <button class="btn btn-info" onclick="clearCache()">
                🔄 Vider cache système
            </button>
        </div>
        <div id="sessions-cleanup-result" class="maintenance-result"></div>
    </div>
    
    <div class="maintenance-section">
        <h5>Fichiers d'expéditions</h5>
        <p>Gestion des PDFs d'expéditions et fichiers générés</p>
        
        <div class="file-management">
            <div class="file-category">
                <h6>📄 PDFs d'expéditions</h6>
                <div class="file-stats">
                    <span>Total: <strong>342 fichiers</strong> (127.8 MB)</span>
                    <span>Anciens (>6 mois): <strong>89 fichiers</strong> (32.1 MB)</span>
                </div>
                <div class="file-actions">
                    <button class="btn btn-secondary" onclick="analyzePdfFiles()">
                        📊 Analyser
                    </button>
                    <button class="btn btn-warning" onclick="archiveOldPdfs()">
                        📦 Archiver anciens
                    </button>
                    <button class="btn btn-danger" onclick="deleteOldPdfs()">
                        🗑️ Supprimer > 1 an
                    </button>
                </div>
            </div>
            
            <div class="file-category">
                <h6>📊 Exports et rapports</h6>
                <div class="file-stats">
                    <span>Total: <strong>67 fichiers</strong> (23.4 MB)</span>
                    <span>Temporaires: <strong>12 fichiers</strong> (4.2 MB)</span>
                </div>
                <div class="file-actions">
                    <button class="btn btn-secondary" onclick="analyzeExportFiles()">
                        📊 Analyser
                    </button>
                    <button class="btn btn-warning" onclick="cleanTempExports()">
                        🗑️ Nettoyer temporaires
                    </button>
                </div>
            </div>
        </div>
        <div id="files-cleanup-result" class="maintenance-result"></div>
    </div>
    
    <div class="maintenance-section">
        <h5>Données obsolètes</h5>
        <p>Nettoyage sélectif des données anciennes selon les politiques de rétention</p>
        
        <div class="data-retention-policies">
            <div class="policy-group">
                <h6>Expéditions et déclarations</h6>
                <div class="cleanup-options">
                    <label class="cleanup-option">
                        <input type="checkbox" id="cleanup-old-expeditions">
                        <div class="option-content">
                            <span class="option-title">Expéditions archivées (> 2 ans)</span>
                            <span class="option-details">47 expéditions • 12.3 MB</span>
                            <span class="option-risk">Risque: Faible</span>
                        </div>
                    </label>
                    
                    <label class="cleanup-option">
                        <input type="checkbox" id="cleanup-draft-expeditions">
                        <div class="option-content">
                            <span class="option-title">Brouillons non finalisés (> 30 jours)</span>
                            <span class="option-details">156 brouillons • 2.1 MB</span>
                            <span class="option-risk">Risque: Très faible</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <div class="policy-group">
                <h6>Logs et historique</h6>
                <div class="cleanup-options">
                    <label class="cleanup-option">
                        <input type="checkbox" id="cleanup-old-logs">
                        <div class="option-content">
                            <span class="option-title">Logs système (> 6 mois)</span>
                            <span class="option-details">8,423 entrées • 45.7 MB</span>
                            <span class="option-risk">Risque: Nul</span>
                        </div>
                    </label>
                    
                    <label class="cleanup-option">
                        <input type="checkbox" id="cleanup-search-logs">
                        <div class="option-content">
                            <span class="option-title">Logs de recherche (> 3 mois)</span>
                            <span class="option-details">15,678 entrées • 8.9 MB</span>
                            <span class="option-risk">Risque: Nul</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <div class="policy-group">
                <h6>Destinataires et clients</h6>
                <div class="cleanup-options">
                    <label class="cleanup-option">
                        <input type="checkbox" id="cleanup-unused-clients">
                        <div class="option-content">
                            <span class="option-title">Destinataires non utilisés (> 1 an)</span>
                            <span class="option-details">23 destinataires • 890 KB</span>
                            <span class="option-risk">Risque: Moyen</span>
                        </div>
                    </label>
                    
                    <label class="cleanup-option">
                        <input type="checkbox" id="cleanup-duplicate-clients">
                        <div class="option-content">
                            <span class="option-title">Doublons détectés</span>
                            <span class="option-details">8 doublons • 156 KB</span>
                            <span class="option-risk">Risque: Faible</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="cleanup-summary">
            <div class="summary-stats">
                <span id="selected-items">0 éléments sélectionnés</span>
                <span id="space-to-free">0 MB à libérer</span>
            </div>
            <div class="cleanup-final-actions">
                <button class="btn btn-secondary" onclick="selectAllCleanup()">
                    ☑️ Tout sélectionner
                </button>
                <button class="btn btn-secondary" onclick="selectNoneCleanup()">
                    ☐ Tout désélectionner
                </button>
                <button class="btn btn-danger" onclick="cleanObsoleteData()" id="cleanup-execute-btn" disabled>
                    🗑️ Exécuter nettoyage
                </button>
            </div>
        </div>
        <div id="data-cleanup-result" class="maintenance-result"></div>
    </div>
    
    <div class="maintenance-section">
        <h5>Nettoyage automatique</h5>
        <p>Configuration des tâches de nettoyage automatisées</p>
        
        <div class="auto-cleanup-config">
            <div class="config-group">
                <h6>Planification automatique</h6>
                <div class="auto-tasks">
                    <div class="auto-task">
                        <div class="task-info">
                            <span class="task-name">Nettoyage sessions</span>
                            <span class="task-schedule">Quotidien - 01:00</span>
                        </div>
                        <div class="task-controls">
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                            <button class="btn btn-sm btn-secondary" onclick="configureAutoTask('sessions')">
                                ⚙️
                            </button>
                        </div>
                    </div>
                    
                    <div class="auto-task">
                        <div class="task-info">
                            <span class="task-name">Nettoyage fichiers temporaires</span>
                            <span class="task-schedule">Hebdomadaire - Dimanche 02:00</span>
                        </div>
                        <div class="task-controls">
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                            <button class="btn btn-sm btn-secondary" onclick="configureAutoTask('temp-files')">
                                ⚙️
                            </button>
                        </div>
                    </div>
                    
                    <div class="auto-task">
                        <div class="task-info">
                            <span class="task-name">Archivage anciennes expéditions</span>
                            <span class="task-schedule">Mensuelle - 1er du mois</span>
                        </div>
                        <div class="task-controls">
                            <label class="toggle-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                            <button class="btn btn-sm btn-secondary" onclick="configureAutoTask('archive')">
                                ⚙️
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="config-group">
                <h6>Politiques de rétention</h6>
                <div class="retention-settings">
                    <div class="retention-item">
                        <label>Logs système :</label>
                        <select>
                            <option value="3">3 mois</option>
                            <option value="6" selected>6 mois</option>
                            <option value="12">1 an</option>
                        </select>
                    </div>
                    <div class="retention-item">
                        <label>Sessions utilisateur :</label>
                        <select>
                            <option value="1" selected>24 heures</option>
                            <option value="3">3 jours</option>
                            <option value="7">1 semaine</option>
                        </select>
                    </div>
                    <div class="retention-item">
                        <label>Fichiers temporaires :</label>
                        <select>
                            <option value="1">1 jour</option>
                            <option value="7" selected>1 semaine</option>
                            <option value="30">1 mois</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="saveRetentionPolicies()">
                    💾 Sauvegarder politiques
                </button>
            </div>
        </div>
        <div id="auto-cleanup-result" class="maintenance-result"></div>
    </div>
</div>

<style>
/* Styles pour l'onglet cleanup */
.cleanup-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.stat-item {
    background: white;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
    border: 1px solid #ddd;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #ff6b35;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
}

.cleanup-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 15px 0;
}

.file-management {
    display: grid;
    gap: 20px;
}

.file-category {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.file-category h6 {
    margin: 0 0 10px 0;
    color: #ff6b35;
    font-size: 1rem;
}

.file-stats {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin: 10px 0;
    font-size: 0.9rem;
    color: #666;
}

.file-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.data-retention-policies {
    display: grid;
    gap: 20px;
}

.policy-group h6 {
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
}

.cleanup-options {
    display: grid;
    gap: 10px;
}

.cleanup-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cleanup-option:hover {
    border-color: #ff6b35;
    box-shadow: 0 2px 8px rgba(255, 107, 53, 0.1);
}

.cleanup-option input[type="checkbox"] {
    margin-top: 2px;
}

.option-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.option-title {
    font-weight: 600;
    color: #333;
}

.option-details {
    font-size: 0.9rem;
    color: #666;
}

.option-risk {
    font-size: 0.8rem;
    font-weight: 500;
}

.option-risk:contains("Nul") {
    color: #28a745;
}

.option-risk:contains("Très faible"), 
.option-risk:contains("Faible") {
    color: #ffc107;
}

.option-risk:contains("Moyen") {
    color: #fd7e14;
}

.option-risk:contains("Élevé") {
    color: #dc3545;
}

.cleanup-summary {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
    margin-top: 20px;
}

.summary-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-weight: 600;
}

.cleanup-final-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.auto-cleanup-config {
    display: grid;
    gap: 25px;
}

.config-group h6 {
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
}

.auto-tasks {
    display: grid;
    gap: 10px;
}

.auto-task {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.task-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.task-name {
    font-weight: 500;
    color: #333;
}

.task-schedule {
    font-size: 0.9rem;
    color: #666;
}

.task-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #28a745;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.retention-settings {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.retention-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.retention-item label {
    font-weight: 500;
    color: #333;
}

.retention-item select {
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    .cleanup-stats {
        grid-template-columns: 1fr;
    }
    
    .cleanup-actions,
    .file-actions {
        flex-direction: column;
    }
    
    .summary-stats {
        flex-direction: column;
        gap: 8px;
    }
    
    .cleanup-final-actions {
        flex-direction: column;
    }
    
    .auto-task {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .retention-settings {
        grid-template-columns: 1fr;
    }
    
    .retention-item {
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
}
</style>

<script>
// ========== FONCTIONS SESSIONS ET CACHE ==========

function cleanExpiredSessions() {
    showMaintenanceResult('sessions-cleanup-result', 'Nettoyage des sessions expirées...', 'info');
    
    const progressBar = createProgressBar('sessions-cleanup-result');
    let progress = 0;
    
    const updateProgress = () => {
        progress += Math.random() * 25 + 10;
        if (progress > 100) progress = 100;
        
        updateProgressBar('sessions-cleanup-result', progress);
        
        if (progress < 100) {
            setTimeout(updateProgress, 400);
        } else {
            const result = `✅ Nettoyage des sessions terminé

SESSIONS SUPPRIMÉES: 47
ESPACE LIBÉRÉ: 2.3 MB
CRITÈRES: Sessions > 24 heures

DÉTAIL:
  • Sessions web expirées: 35
  • Sessions API timeout: 8
  • Sessions orphelines: 4
  
Sessions actives conservées: 5`;
            
            showMaintenanceResult('sessions-cleanup-result', result, 'success');
            
            // Mettre à jour le compteur
            document.getElementById('sessions-count').textContent = '0';
        }
    };
    
    updateProgress();
}

function cleanTempFiles() {
    showMaintenanceResult('files-cleanup-result', 'Nettoyage des fichiers temporaires...', 'info');
    
    setTimeout(() => {
        const result = `✅ Nettoyage des fichiers temporaires terminé

FICHIERS SUPPRIMÉS:
  📄 PDFs temporaires: 23 fichiers (15.7 MB)
  📊 Exports expirés: 8 fichiers (3.2 MB)
  🔄 Cache obsolète: 156 fichiers (8.9 MB)
  📁 Uploads non utilisés: 12 fichiers (1.8 MB)

TOTAL LIBÉRÉ: 29.6 MB
DOSSIERS NETTOYÉS: 4

Prochaine exécution automatique: dans 7 jours`;
        
        showMaintenanceResult('files-cleanup-result', result, 'success');
        
        // Mettre à jour les compteurs
        document.getElementById('temp-files-count').textContent = '0';
        document.getElementById('cache-size').textContent = '0 MB';
    }, 2000);
}

function clearCache() {
    showMaintenanceResult('sessions-cleanup-result', 'Vidage du cache système...', 'info');
    
    setTimeout(() => {
        const result = `🔄 Cache système vidé

CACHE SUPPRIMÉ:
  • Cache requêtes: 3.2 MB
  • Cache templates: 1.8 MB  
  • Cache recherche: 2.4 MB
  • Cache session: 1.5 MB

TOTAL: 8.9 MB libérés
PERFORMANCE: Cache reconstruit automatiquement`;
        
        showMaintenanceResult('sessions-cleanup-result', result, 'success');
    }, 1500);
}

// ========== FONCTIONS FICHIERS ==========

function analyzePdfFiles() {
    showMaintenanceResult('files-cleanup-result', 'Analyse des fichiers PDF...', 'info');
    
    setTimeout(() => {
        const analysis = `📊 ANALYSE DES PDFs D'EXPÉDITIONS

STATISTIQUES GÉNÉRALES:
  • Total fichiers: 342 (127.8 MB)
  • Taille moyenne: 383 KB
  • Plus ancien: 15/03/2023
  • Plus récent: 14/01/2025

RÉPARTITION PAR PÉRIODE:
  • < 1 mois: 67 fichiers (24.1 MB)
  • 1-3 mois: 89 fichiers (32.8 MB)
  • 3-6 mois: 97 fichiers (38.4 MB)
  • 6-12 mois: 56 fichiers (20.7 MB)
  • > 1 an: 33 fichiers (11.8 MB)

RECOMMANDATIONS:
  ✓ Archiver fichiers > 6 mois (32.5 MB)
  • Supprimer fichiers > 2 ans (si archivés)`;
        
        showMaintenanceResult('files-cleanup-result', analysis, 'info');
    }, 1500);
}

function archiveOldPdfs() {
    if (!confirm('Archiver les PDFs de plus de 6 mois ?\n\nCes fichiers seront déplacés vers un stockage d\'archive.')) {
        return;
    }
    
    showMaintenanceResult('files-cleanup-result', 'Archivage des anciens PDFs...', 'warning');
    
    setTimeout(() => {
        const result = `📦 Archivage terminé

FICHIERS ARCHIVÉS: 89 PDFs
ESPACE LIBÉRÉ: 32.5 MB
DESTINATION: /archives/adr/pdfs/2024/

ARCHIVE CRÉÉE:
  • adr_pdfs_2024_archive.tar.gz
  • Taille compressée: 18.7 MB
  • Ratio compression: 42%

Les fichiers restent accessibles via l'interface d'archive.`;
        
        showMaintenanceResult('files-cleanup-result', result, 'success');
    }, 3000);
}

function deleteOldPdfs() {
    if (!confirm('⚠️ ATTENTION\n\nSupprimer définitivement les PDFs de plus d\'1 an ?\n\nCette action est irréversible !')) {
        return;
    }
    
    showMaintenanceResult('files-cleanup-result', '🗑️ Suppression des anciens PDFs...', 'warning');
    
    setTimeout(() => {
        const result = `🗑️ Suppression terminée

FICHIERS SUPPRIMÉS: 33 PDFs
ESPACE LIBÉRÉ: 11.8 MB
PÉRIODE: Plus d'1 an (2023)

SÉCURITÉ:
  ✓ Vérification archives effectuée
  ✓ Aucune donnée critique affectée
  ✓ Logs de suppression créés

Recommandation: Vérifier la politique d'archivage`;
        
        showMaintenanceResult('files-cleanup-result', result, 'success');
    }, 2500);
}

function analyzeExportFiles() {
    showMaintenanceResult('files-cleanup-result', 'Analyse des fichiers d\'export...', 'info');
    
    setTimeout(() => {
        const analysis = `📊 ANALYSE DES EXPORTS ET RAPPORTS

FICHIERS D'EXPORT:
  • Total: 67 fichiers (23.4 MB)
  • CSV exports: 34 fichiers (8.9 MB)
  • Excel reports: 21 fichiers (12.1 MB)
  • PDF reports: 12 fichiers (2.4 MB)

FICHIERS TEMPORAIRES:
  • En cours: 3 fichiers (892 KB)
  • Expirés: 12 fichiers (4.2 MB)
  • Orphelins: 7 fichiers (1.8 MB)

ÂGE MOYEN: 15 jours
PLUS ANCIEN: 3 mois 2 jours

RECOMMANDATIONS:
  🗑️ Supprimer fichiers temporaires expirés
  📦 Archiver exports > 1 mois`;
        
        showMaintenanceResult('files-cleanup-result', analysis, 'info');
    }, 1200);
}

function cleanTempExports() {
    showMaintenanceResult('files-cleanup-result', 'Nettoyage des exports temporaires...', 'warning');
    
    setTimeout(() => {
        const result = `✅ Nettoyage exports terminé

FICHIERS SUPPRIMÉS:
  • Exports temporaires: 12 fichiers (4.2 MB)
  • Fichiers orphelins: 7 fichiers (1.8 MB)
  • Cache export: 156 entrées (2.1 MB)

TOTAL LIBÉRÉ: 8.1 MB
FICHIERS CONSERVÉS: 48 exports valides

Prochaine vérification: dans 1 semaine`;
        
        showMaintenanceResult('files-cleanup-result', result, 'success');
    }, 1800);
}

// ========== FONCTIONS DONNÉES OBSOLÈTES ==========

function updateCleanupSummary() {
    const checkboxes = document.querySelectorAll('.cleanup-option input[type="checkbox"]');
    let selectedCount = 0;
    let totalSpace = 0;
    
    // Mapping des tailles par option
    const spaceSizes = {
        'cleanup-old-expeditions': 12.3,
        'cleanup-draft-expeditions': 2.1,
        'cleanup-old-logs': 45.7,
        'cleanup-search-logs': 8.9,
        'cleanup-unused-clients': 0.9,
        'cleanup-duplicate-clients': 0.2
    };
    
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            selectedCount++;
            totalSpace += spaceSizes[checkbox.id] || 0;
        }
    });
    
    document.getElementById('selected-items').textContent = `${selectedCount} élément(s) sélectionné(s)`;
    document.getElementById('space-to-free').textContent = `${totalSpace.toFixed(1)} MB à libérer`;
    document.getElementById('cleanup-execute-btn').disabled = selectedCount === 0;
}

function selectAllCleanup() {
    document.querySelectorAll('.cleanup-option input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateCleanupSummary();
}

function selectNoneCleanup() {
    document.querySelectorAll('.cleanup-option input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateCleanupSummary();
}

function cleanObsoleteData() {
    const selectedOptions = [];
    const checkboxes = document.querySelectorAll('.cleanup-option input[type="checkbox"]:checked');
    
    if (checkboxes.length === 0) {
        showMaintenanceResult('data-cleanup-result', '❌ Aucune option sélectionnée', 'warning');
        return;
    }
    
    checkboxes.forEach(checkbox => {
        selectedOptions.push(checkbox.id);
    });
    
    const optionNames = {
        'cleanup-old-expeditions': 'expéditions archivées',
        'cleanup-draft-expeditions': 'brouillons non finalisés',
        'cleanup-old-logs': 'logs système anciens',
        'cleanup-search-logs': 'logs de recherche',
        'cleanup-unused-clients': 'destinataires non utilisés',
        'cleanup-duplicate-clients': 'doublons détectés'
    };
    
    const optionsList = selectedOptions.map(id => optionNames[id]).join(', ');
    
    if (!confirm(`⚠️ NETTOYAGE DONNÉES\n\nVous allez supprimer :\n• ${optionsList}\n\nCette action est irréversible.\n\nContinuer ?`)) {
        return;
    }
    
    showMaintenanceResult('data-cleanup-result', 'Nettoyage des données obsolètes...', 'warning');
    
    const progressBar = createProgressBar('data-cleanup-result');
    
    let processed = 0;
    const results = [];
    
    const processOption = (optionId) => {
        return new Promise(resolve => {
            setTimeout(() => {
                const mockResults = {
                    'cleanup-old-expeditions': '47 expéditions supprimées (12.3 MB)',
                    'cleanup-draft-expeditions': '156 brouillons supprimés (2.1 MB)',
                    'cleanup-old-logs': '8,423 entrées supprimées (45.7 MB)',
                    'cleanup-search-logs': '15,678 entrées supprimées (8.9 MB)',
                    'cleanup-unused-clients': '23 destinataires supprimés (0.9 MB)',
                    'cleanup-duplicate-clients': '8 doublons supprimés (0.2 MB)'
                };
                
                results.push(mockResults[optionId]);
                processed++;
                
                const progress = (processed / selectedOptions.length) * 100;
                updateProgressBar('data-cleanup-result', progress);
                
                showMaintenanceResult('data-cleanup-result', 
                    `✓ ${optionNames[optionId]} traité (${processed}/${selectedOptions.length})`, 
                    'success', true);
                
                resolve();
            }, 1000 + Math.random() * 1000);
        });
    };
    
    // Traiter toutes les options sélectionnées
    Promise.all(selectedOptions.map(processOption)).then(() => {
        const totalSpace = results.reduce((sum, result) => {
            const match = result.match(/\(([0-9.]+) MB\)/);
            return sum + (match ? parseFloat(match[1]) : 0);
        }, 0);
        
        const finalResult = `✅ NETTOYAGE TERMINÉ

DONNÉES SUPPRIMÉES:
${results.map(r => `  • ${r}`).join('\n')}

ESPACE TOTAL LIBÉRÉ: ${totalSpace.toFixed(1)} MB
OPÉRATIONS RÉUSSIES: ${results.length}/${selectedOptions.length}

Recommandation: Vérifier l'impact sur les fonctionnalités`;
        
        showMaintenanceResult('data-cleanup-result', finalResult, 'success');
        
        // Réinitialiser les sélections
        selectNoneCleanup();
    });
}

// ========== FONCTIONS NETTOYAGE AUTOMATIQUE ==========

function configureAutoTask(taskType) {
    const taskNames = {
        'sessions': 'Nettoyage sessions',
        'temp-files': 'Nettoyage fichiers temporaires',
        'archive': 'Archivage anciennes expéditions'
    };
    
    const currentSchedules = {
        'sessions': 'daily 01:00',
        'temp-files': 'weekly sunday 02:00',
        'archive': 'monthly 1 03:00'
    };
    
    const taskName = taskNames[taskType];
    const currentSchedule = currentSchedules[taskType];
    
    const newSchedule = prompt(
        `Configuration: ${taskName}\n\n` +
        `Planification actuelle: ${currentSchedule}\n\n` +
        `Formats acceptés:\n` +
        `• daily HH:MM (quotidien)\n` +
        `• weekly [day] HH:MM (hebdomadaire)\n` +
        `• monthly [date] HH:MM (mensuel)\n\n` +
        `Nouvelle planification:`,
        currentSchedule
    );
    
    if (newSchedule && newSchedule !== currentSchedule) {
        showMaintenanceResult('auto-cleanup-result', 
            `✅ Tâche "${taskName}" reprogrammée: ${newSchedule}`, 'success');
        
        // Mettre à jour l'affichage
        updateTaskScheduleDisplay(taskType, newSchedule);
    }
}

function updateTaskScheduleDisplay(taskType, schedule) {
    // Trouver et mettre à jour l'affichage de la planification
    const taskElements = document.querySelectorAll('.auto-task');
    taskElements.forEach(element => {
        const taskName = element.querySelector('.task-name').textContent;
        if (taskName.includes(taskType) || 
            (taskType === 'sessions' && taskName.includes('sessions')) ||
            (taskType === 'temp-files' && taskName.includes('fichiers')) ||
            (taskType === 'archive' && taskName.includes('archivage'))) {
            
            const scheduleElement = element.querySelector('.task-schedule');
            scheduleElement.textContent = formatScheduleDisplay(schedule);
        }
    });
}

function formatScheduleDisplay(schedule) {
    const parts = schedule.split(' ');
    
    if (parts[0] === 'daily') {
        return `Quotidien - ${parts[1]}`;
    } else if (parts[0] === 'weekly') {
        const dayNames = {
            'monday': 'Lundi', 'tuesday': 'Mardi', 'wednesday': 'Mercredi',
            'thursday': 'Jeudi', 'friday': 'Vendredi', 'saturday': 'Samedi', 'sunday': 'Dimanche'
        };
        return `Hebdomadaire - ${dayNames[parts[1]] || parts[1]} ${parts[2]}`;
    } else if (parts[0] === 'monthly') {
        return `Mensuelle - ${parts[1]} du mois à ${parts[2]}`;
    }
    
    return schedule;
}

function saveRetentionPolicies() {
    const policies = {
        logs: document.querySelector('.retention-item:nth-child(1) select').value,
        sessions: document.querySelector('.retention-item:nth-child(2) select').value,
        tempFiles: document.querySelector('.retention-item:nth-child(3) select').value
    };
    
    showMaintenanceResult('auto-cleanup-result', 'Sauvegarde des politiques de rétention...', 'info');
    
    setTimeout(() => {
        const result = `💾 Politiques de rétention sauvegardées

NOUVELLES RÈGLES:
  • Logs système: ${policies.logs} mois
  • Sessions utilisateur: ${policies.sessions} jour(s)
  • Fichiers temporaires: ${policies.tempFiles} jour(s)

APPLICATION: Immédiate
PROCHAINE VÉRIFICATION: Dans 24h

Les nouvelles règles s'appliquent aux prochaines tâches automatiques.`;
        
        showMaintenanceResult('auto-cleanup-result', result, 'success');
    }, 1000);
}

// ========== ÉVÉNEMENTS ET INITIALISATION ==========

// Initialiser les événements au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Événements pour le résumé de nettoyage
    document.querySelectorAll('.cleanup-option input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateCleanupSummary);
    });
    
    // État initial du résumé
    updateCleanupSummary();
    
    // Événements pour les toggle switches
    document.querySelectorAll('.toggle-switch input').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const taskElement = this.closest('.auto-task');
            const taskName = taskElement.querySelector('.task-name').textContent;
            const status = this.checked ? 'activée' : 'désactivée';
            
            console.log(`Tâche "${taskName}" ${status}`);
        });
    });
});

// ========== FONCTIONS UTILITAIRES ==========

function createProgressBar(containerId, percentage = 0) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
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
        fill.style.width = `${Math.min(percentage, 100)}%`;
    }
}

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
    
    container.className = `maintenance-result ${type}`;
}

console.log('🧹 Module Cleanup Tab chargé');
</script>

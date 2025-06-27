<?php
// public/admin/components/alerts-container.php
// Conteneur pour les alertes système et notifications

// Vérifier si des alertes sont disponibles dans les données du dashboard
$alerts = $dashboardData['alerts'] ?? [];
$systemAlerts = [];
$userAlerts = [];

// Séparer les alertes système des alertes utilisateur
foreach ($alerts as $alert) {
    if (in_array($alert['priority'], ['critical', 'high'])) {
        $systemAlerts[] = $alert;
    } else {
        $userAlerts[] = $alert;
    }
}
?>

<!-- Container principal pour toutes les alertes -->
<div id="alerts-main-container">
    
    <!-- Alertes système critiques (toujours visibles) -->
    <?php if (!empty($systemAlerts)): ?>
    <div class="system-alerts-banner">
        <?php foreach ($systemAlerts as $alert): ?>
        <div class="system-alert alert-<?= $alert['type'] ?> priority-<?= $alert['priority'] ?>" 
             data-alert-id="<?= $alert['id'] ?? uniqid() ?>">
            <div class="alert-content">
                <div class="alert-icon"><?= $alert['icon'] ?></div>
                <div class="alert-text">
                    <div class="alert-title"><?= htmlspecialchars($alert['title']) ?></div>
                    <div class="alert-description"><?= htmlspecialchars($alert['description']) ?></div>
                </div>
                <?php if (!empty($alert['action'])): ?>
                <div class="alert-actions">
                    <button class="alert-action-btn" onclick="<?= $alert['action'] ?>" title="Résoudre le problème">
                        Résoudre
                    </button>
                </div>
                <?php endif; ?>
                <button class="alert-dismiss" onclick="dismissAlert(this)" title="Ignorer cette alerte">
                    ×
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Alertes contextuelles (créées dynamiquement par JavaScript) -->
    <div id="alert-container" class="dynamic-alerts-container">
        <!-- Les alertes JavaScript seront insérées ici -->
    </div>

    <!-- Alertes de notification utilisateur -->
    <?php if (!empty($userAlerts)): ?>
    <div class="user-alerts-panel" id="user-alerts-panel">
        <div class="alerts-header">
            <h4>📢 Notifications</h4>
            <button class="alerts-toggle" onclick="toggleUserAlerts()" id="alerts-toggle-btn">
                <span id="alerts-count-badge" class="alerts-count"><?= count($userAlerts) ?></span>
                <span>🔔</span>
            </button>
        </div>
        
        <div class="alerts-content" id="alerts-content" style="display: none;">
            <?php foreach ($userAlerts as $index => $alert): ?>
            <div class="user-alert alert-<?= $alert['type'] ?>" 
                 data-alert-index="<?= $index ?>"
                 data-priority="<?= $alert['priority'] ?>">
                <div class="alert-header">
                    <span class="alert-icon"><?= $alert['icon'] ?></span>
                    <span class="alert-title"><?= htmlspecialchars($alert['title']) ?></span>
                    <span class="alert-time" title="<?= date('d/m/Y H:i:s') ?>">
                        <?= formatRelativeTime(time()) ?>
                    </span>
                </div>
                
                <div class="alert-body">
                    <p class="alert-description"><?= htmlspecialchars($alert['description']) ?></p>
                    
                    <?php if (!empty($alert['action'])): ?>
                    <div class="alert-actions">
                        <button class="btn btn-sm btn-primary" onclick="<?= $alert['action'] ?>">
                            Action recommandée
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="alert-footer">
                    <button class="alert-mark-read" onclick="markAlertAsRead(<?= $index ?>)" title="Marquer comme lu">
                        ✓ Lu
                    </button>
                    <button class="alert-dismiss-user" onclick="dismissUserAlert(<?= $index ?>)" title="Supprimer">
                        🗑️
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="alerts-footer">
                <button class="btn btn-sm btn-secondary" onclick="markAllAlertsAsRead()">
                    ✓ Tout marquer comme lu
                </button>
                <button class="btn btn-sm btn-secondary" onclick="clearAllUserAlerts()">
                    🗑️ Tout effacer
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Zone de statut global du système -->
    <div class="system-status-bar" id="system-status-bar">
        <div class="status-item">
            <span class="status-indicator" id="db-status">
                <span class="status-dot" id="db-dot"></span>
                Base de données
            </span>
        </div>
        
        <div class="status-item">
            <span class="status-indicator" id="api-status">
                <span class="status-dot" id="api-dot"></span>
                API
            </span>
        </div>
        
        <div class="status-item">
            <span class="status-indicator" id="performance-status">
                <span class="status-dot" id="performance-dot"></span>
                Performance
            </span>
        </div>
        
        <div class="status-item last-update">
            Dernière vérification : <span id="last-check-time"><?= date('H:i:s') ?></span>
        </div>
    </div>
</div>

<script>
// Fonctions JavaScript pour la gestion des alertes
document.addEventListener('DOMContentLoaded', function() {
    initializeAlertsSystem();
});

/**
 * Initialise le système d'alertes
 */
function initializeAlertsSystem() {
    console.log('🔔 Initialisation du système d\'alertes');
    
    // Vérifier le statut du système toutes les 30 secondes
    setInterval(checkSystemStatus, 30000);
    
    // Vérification initiale
    checkSystemStatus();
    
    // Gestion des alertes persistantes
    loadPersistentAlerts();
    
    console.log('✅ Système d\'alertes initialisé');
}

/**
 * Bascule l'affichage des alertes utilisateur
 */
function toggleUserAlerts() {
    const content = document.getElementById('alerts-content');
    const toggleBtn = document.getElementById('alerts-toggle-btn');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggleBtn.classList.add('active');
        
        // Marquer les alertes comme vues
        setTimeout(() => {
            markAlertsAsViewed();
        }, 2000);
    } else {
        content.style.display = 'none';
        toggleBtn.classList.remove('active');
    }
}

/**
 * Ferme une alerte système
 */
function dismissAlert(button) {
    const alert = button.closest('.system-alert');
    const alertId = alert.dataset.alertId;
    
    // Animation de fermeture
    alert.style.animation = 'slideOutRight 0.3s ease';
    
    setTimeout(() => {
        alert.remove();
        
        // Sauvegarder l'état (alerte fermée)
        saveDismissedAlert(alertId);
        
        // Vérifier s'il reste des alertes système
        checkSystemAlertsBanner();
    }, 300);
}

/**
 * Marque une alerte utilisateur comme lue
 */
function markAlertAsRead(alertIndex) {
    const alert = document.querySelector(`[data-alert-index="${alertIndex}"]`);
    if (alert) {
        alert.classList.add('read');
        
        // Sauvegarder l'état
        saveAlertState(alertIndex, 'read');
        
        updateAlertsCount();
    }
}

/**
 * Supprime une alerte utilisateur
 */
function dismissUserAlert(alertIndex) {
    const alert = document.querySelector(`[data-alert-index="${alertIndex}"]`);
    if (alert) {
        alert.style.animation = 'fadeOut 0.3s ease';
        
        setTimeout(() => {
            alert.remove();
            updateAlertsCount();
            
            // Sauvegarder l'état
            saveAlertState(alertIndex, 'dismissed');
        }, 300);
    }
}

/**
 * Marque toutes les alertes comme lues
 */
function markAllAlertsAsRead() {
    const alerts = document.querySelectorAll('.user-alert:not(.read)');
    alerts.forEach((alert, index) => {
        alert.classList.add('read');
        const alertIndex = alert.dataset.alertIndex;
        saveAlertState(alertIndex, 'read');
    });
    
    updateAlertsCount();
    showAlert('success', 'Toutes les alertes ont été marquées comme lues');
}

/**
 * Efface toutes les alertes utilisateur
 */
function clearAllUserAlerts() {
    if (confirm('Êtes-vous sûr de vouloir effacer toutes les alertes ?')) {
        const alerts = document.querySelectorAll('.user-alert');
        alerts.forEach(alert => {
            alert.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        });
        
        updateAlertsCount();
        
        // Masquer le panel s'il est vide
        setTimeout(() => {
            const panel = document.getElementById('user-alerts-panel');
            if (panel && panel.querySelectorAll('.user-alert').length === 0) {
                panel.style.display = 'none';
            }
        }, 500);
        
        showAlert('info', 'Toutes les alertes ont été effacées');
    }
}

/**
 * Met à jour le compteur d'alertes
 */
function updateAlertsCount() {
    const unreadAlerts = document.querySelectorAll('.user-alert:not(.read)');
    const countBadge = document.getElementById('alerts-count-badge');
    
    if (countBadge) {
        const count = unreadAlerts.length;
        countBadge.textContent = count;
        countBadge.style.display = count > 0 ? 'inline' : 'none';
        
        // Animation du badge si nouvelles alertes
        if (count > 0) {
            countBadge.classList.add('pulse');
            setTimeout(() => countBadge.classList.remove('pulse'), 1000);
        }
    }
}

/**
 * Vérifie le statut du système
 */
function checkSystemStatus() {
    const statusItems = {
        'db-status': checkDatabaseStatus(),
        'api-status': checkAPIStatus(),
        'performance-status': checkPerformanceStatus()
    };
    
    Promise.all(Object.values(statusItems))
        .then(results => {
            updateStatusIndicators(results);
            updateLastCheckTime();
        })
        .catch(error => {
            console.error('Erreur vérification statut:', error);
            showSystemAlert('error', 'Erreur de vérification du système', error.message);
        });
}

/**
 * Vérifie l'état de la base de données
 */
async function checkDatabaseStatus() {
    try {
        const response = await fetch('api-rates.php?action=health-check', {
            method: 'GET',
            headers: { 'X-Health-Check': 'database' }
        });
        
        if (response.ok) {
            const data = await response.json();
            return { status: 'online', element: 'db-dot' };
        } else {
            return { status: 'error', element: 'db-dot' };
        }
    } catch (error) {
        return { status: 'offline', element: 'db-dot' };
    }
}

/**
 * Vérifie l'état de l'API
 */
async function checkAPIStatus() {
    try {
        const startTime = performance.now();
        const response = await fetch('api-rates.php?action=carriers');
        const endTime = performance.now();
        
        if (response.ok) {
            const responseTime = endTime - startTime;
            return { 
                status: responseTime < 500 ? 'online' : 'warning', 
                element: 'api-dot',
                responseTime 
            };
        } else {
            return { status: 'error', element: 'api-dot' };
        }
    } catch (error) {
        return { status: 'offline', element: 'api-dot' };
    }
}

/**
 * Vérifie les performances
 */
async function checkPerformanceStatus() {
    const startTime = performance.now();
    
    // Test simple de performance
    return new Promise(resolve => {
        setTimeout(() => {
            const endTime = performance.now();
            const processingTime = endTime - startTime;
            
            resolve({
                status: processingTime < 100 ? 'online' : 'warning',
                element: 'performance-dot',
                processingTime
            });
        }, 50);
    });
}

/**
 * Met à jour les indicateurs de statut
 */
function updateStatusIndicators(results) {
    results.forEach(result => {
        const dot = document.getElementById(result.element);
        if (dot) {
            // Supprimer les anciennes classes
            dot.classList.remove('online', 'warning', 'error', 'offline');
            
            // Ajouter la nouvelle classe
            dot.classList.add(result.status);
            
            // Mettre à jour le titre avec des informations détaillées
            let title = `Statut: ${result.status}`;
            if (result.responseTime) {
                title += ` (${Math.round(result.responseTime)}ms)`;
            }
            if (result.processingTime) {
                title += ` (${Math.round(result.processingTime)}ms traitement)`;
            }
            dot.parentElement.title = title;
        }
    });
}

/**
 * Met à jour l'heure de dernière vérification
 */
function updateLastCheckTime() {
    const timeElement = document.getElementById('last-check-time');
    if (timeElement) {
        timeElement.textContent = new Date().toLocaleTimeString('fr-FR');
    }
}

/**
 * Affiche une alerte système
 */
function showSystemAlert(type, title, description) {
    const banner = document.querySelector('.system-alerts-banner') || createSystemAlertsBanner();
    
    const alertHtml = `
        <div class="system-alert alert-${type} priority-high" data-alert-id="${Date.now()}">
            <div class="alert-content">
                <div class="alert-icon">${getAlertIcon(type)}</div>
                <div class="alert-text">
                    <div class="alert-title">${title}</div>
                    <div class="alert-description">${description}</div>
                </div>
                <button class="alert-dismiss" onclick="dismissAlert(this)" title="Ignorer cette alerte">×</button>
            </div>
        </div>
    `;
    
    banner.insertAdjacentHTML('beforeend', alertHtml);
}

/**
 * Crée le banner d'alertes système s'il n'existe pas
 */
function createSystemAlertsBanner() {
    const container = document.getElementById('alerts-main-container');
    const banner = document.createElement('div');
    banner.className = 'system-alerts-banner';
    container.insertBefore(banner, container.firstChild);
    return banner;
}

/**
 * Retourne l'icône appropriée pour un type d'alerte
 */
function getAlertIcon(type) {
    const icons = {
        'success': '✅',
        'info': 'ℹ️',
        'warning': '⚠️',
        'error': '❌',
        'critical': '🚨'
    };
    return icons[type] || 'ℹ️';
}

/**
 * Fonctions de sauvegarde d'état (localStorage)
 */
function saveDismissedAlert(alertId) {
    const dismissed = JSON.parse(localStorage.getItem('dismissedAlerts') || '[]');
    dismissed.push(alertId);
    localStorage.setItem('dismissedAlerts', JSON.stringify(dismissed));
}

function saveAlertState(alertIndex, state) {
    const alertStates = JSON.parse(localStorage.getItem('alertStates') || '{}');
    alertStates[alertIndex] = state;
    localStorage.setItem('alertStates', JSON.stringify(alertStates));
}

function loadPersistentAlerts() {
    const dismissed = JSON.parse(localStorage.getItem('dismissedAlerts') || '[]');
    const states = JSON.parse(localStorage.getItem('alertStates') || '{}');
    
    // Masquer les alertes déjà fermées
    dismissed.forEach(alertId => {
        const alert = document.querySelector(`[data-alert-id="${alertId}"]`);
        if (alert) {
            alert.style.display = 'none';
        }
    });
    
    // Appliquer les états des alertes utilisateur
    Object.entries(states).forEach(([index, state]) => {
        const alert = document.querySelector(`[data-alert-index="${index}"]`);
        if (alert) {
            if (state === 'read') {
                alert.classList.add('read');
            } else if (state === 'dismissed') {
                alert.style.display = 'none';
            }
        }
    });
    
    updateAlertsCount();
}

function markAlertsAsViewed() {
    // Marquer toutes les alertes comme "vues" (mais pas nécessairement lues)
    const alerts = document.querySelectorAll('.user-alert:not(.viewed)');
    alerts.forEach(alert => {
        alert.classList.add('viewed');
    });
}

function checkSystemAlertsBanner() {
    const banner = document.querySelector('.system-alerts-banner');
    if (banner && banner.children.length === 0) {
        banner.style.display = 'none';
    }
}

/**
 * Fonction utilitaire pour formater le temps relatif
 */
function formatRelativeTime(timestamp) {
    const now = Date.now();
    const diff = now - (timestamp * 1000);
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'À l\'instant';
    if (minutes < 60) return `Il y a ${minutes}min`;
    if (hours < 24) return `Il y a ${hours}h`;
    return `Il y a ${days}j`;
}
</script>

<style>
/* Styles pour le système d'alertes */
#alerts-main-container {
    position: relative;
    z-index: 1001;
}

/* Alertes système critiques */
.system-alerts-banner {
    position: fixed;
    top: var(--fixed-header-height, 140px);
    left: 0;
    right: 0;
    z-index: 1002;
    background: rgba(0,0,0,0.1);
    backdrop-filter: blur(4px);
    padding: 0.5rem 0;
}

.system-alert {
    background: white;
    margin: 0.25rem 1rem;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    border-left: 4px solid;
    animation: slideInDown 0.3s ease;
}

.system-alert.alert-error { border-left-color: var(--error-color); }
.system-alert.alert-warning { border-left-color: var(--warning-color); }
.system-alert.alert-info { border-left-color: var(--primary-color); }
.system-alert.alert-success { border-left-color: var(--success-color); }

.alert-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
}

.alert-icon {
    font-size: 1.2rem;
    flex-shrink: 0;
}

.alert-text {
    flex: 1;
}

.alert-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.alert-description {
    font-size: 0.9rem;
    color: #666;
}

.alert-actions {
    margin-left: auto;
}

.alert-action-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: var(--transition);
}

.alert-action-btn:hover {
    background: var(--primary-hover);
}

.alert-dismiss {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 0.25rem;
    margin-left: 0.5rem;
    border-radius: 50%;
    transition: var(--transition);
}

.alert-dismiss:hover {
    background: rgba(0,0,0,0.1);
}

/* Conteneur d'alertes dynamiques */
.dynamic-alerts-container {
    position: fixed;
    top: 150px;
    right: 20px;
    z-index: 10000;
    max-width: 400px;
    pointer-events: none;
}

.dynamic-alerts-container .alert {
    pointer-events: all;
    margin-bottom: 0.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}

/* Panel d'alertes utilisateur */
.user-alerts-panel {
    position: fixed;
    top: 100px;
    right: 20px;
    width: 350px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    z-index: 1001;
    max-height: 60vh;
    overflow: hidden;
}

.alerts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--primary-color);
    color: white;
    border-radius: 8px 8px 0 0;
}

.alerts-header h4 {
    margin: 0;
    font-size: 1rem;
}

.alerts-toggle {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    position: relative;
    transition: var(--transition);
}

.alerts-toggle:hover {
    background: rgba(255,255,255,0.3);
}

.alerts-toggle.active {
    background: rgba(255,255,255,0.4);
}

.alerts-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--error-color);
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    border-radius: 10px;
    min-width: 16px;
    text-align: center;
    font-weight: bold;
}

.alerts-count.pulse {
    animation: pulse 0.5s ease;
}

.alerts-content {
    max-height: 400px;
    overflow-y: auto;
}

.user-alert {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    transition: var(--transition);
}

.user-alert:hover {
    background: #f8f9fa;
}

.user-alert.read {
    opacity: 0.7;
    background: #f0f0f0;
}

.alert-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.alert-time {
    margin-left: auto;
    font-size: 0.7rem;
    color: #999;
}

.alert-footer {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #eee;
}

.alert-mark-read, .alert-dismiss-user {
    background: none;
    border: none;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: var(--transition);
}

.alert-mark-read:hover {
    background: var(--success-color);
    color: white;
}

.alert-dismiss-user:hover {
    background: var(--error-color);
    color: white;
}

.alerts-footer {
    padding: 1rem;
    background: #f8f9fa;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 0.5rem;
}

/* Barre de statut système */
.system-status-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #333;
    color: white;
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    font-size: 0.8rem;
    z-index: 1000;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    transition: var(--transition);
}

.status-dot.online { background: var(--success-color); }
.status-dot.warning { background: var(--warning-color); }
.status-dot.error { background: var(--error-color); }
.status-dot.offline { background: #666; }

.last-update {
    margin-left: auto;
    font-style: italic;
}

/* Animations */
@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Responsive */
@media (max-width: 768px) {
    .user-alerts-panel {
        width: calc(100vw - 40px);
        right: 20px;
        left: 20px;
    }
    
    .system-status-bar {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .last-update {
        margin-left: 0;
    }
}
</style>

<?php
/**
 * Fonction utilitaire pour formater le temps relatif (côté PHP)
 */
function formatRelativeTime($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    $minutes = floor($diff / 60);
    $hours = floor($diff / 3600);
    $days = floor($diff / 86400);
    
    if ($minutes < 1) return 'À l\'instant';
    if ($minutes < 60) return "Il y a {$minutes}min";
    if ($hours < 24) return "Il y a {$hours}h";
    return "Il y a {$days}j";
}
?>

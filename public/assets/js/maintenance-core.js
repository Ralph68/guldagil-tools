// public/assets/js/maintenance-core.js - Fonctions core maintenance

console.log('🔧 Chargement maintenance-core.js...');

// ========== VARIABLES GLOBALES ==========
window.MaintenanceCore = {
    intervals: {},
    activeTab: 'database',
    
    // Configuration
    config: {
        autoRefresh: 30000, // 30 secondes
        maxRetries: 3,
        timeout: 10000
    }
};

// ========== FONCTIONS UTILITAIRES ==========

/**
 * Affiche un résultat dans un conteneur avec gestion des types
 */
function showMaintenanceResult(containerId, message, type = 'info', append = false) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn(`Conteneur ${containerId} non trouvé`);
        return;
    }
    
    if (!append) {
        container.innerHTML = '';
    }
    
    const timestamp = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.textContent = `[${timestamp}] ${message}`;
    
    container.appendChild(entry);
    container.scrollTop = container.scrollHeight;
    
    // Appliquer la classe CSS appropriée
    container.className = `maintenance-result ${type}`;
}

/**
 * Crée une barre de progression
 */
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

/**
 * Met à jour une barre de progression
 */
function updateProgressBar(containerId, percentage) {
    const container = document.getElementById(containerId);
    const fill = container?.querySelector('.progress-fill');
    if (fill) {
        fill.style.width = `${Math.min(100, Math.max(0, percentage))}%`;
    }
}

/**
 * Simule une opération asynchrone avec progression
 */
function simulateProgress(containerId, steps, callback) {
    if (!Array.isArray(steps) || steps.length === 0) {
        console.error('Steps doit être un tableau non vide');
        return;
    }
    
    const progressBar = createProgressBar(containerId);
    let currentStep = 0;
    
    const executeNext = () => {
        if (currentStep >= steps.length) {
            if (callback) callback();
            return;
        }
        
        const step = steps[currentStep];
        const percentage = ((currentStep + 1) / steps.length) * 100;
        
        showMaintenanceResult(containerId, 
            `${step.message || step} (${currentStep + 1}/${steps.length})`, 
            'info', true);
        
        updateProgressBar(containerId, percentage);
        
        setTimeout(() => {
            showMaintenanceResult(containerId, 
                `✓ ${step.success || step} terminé`, 'success', true);
            currentStep++;
            executeNext();
        }, step.delay || 1000);
    };
    
    executeNext();
}

/**
 * Gestion des confirmations sécurisées
 */
function confirmDangerousAction(message, title = 'Confirmation requise') {
    return confirm(`⚠️ ${title}\n\n${message}\n\nCette action peut être irréversible.\n\nÊtes-vous sûr de vouloir continuer ?`);
}

/**
 * Gestion des intervals pour éviter les fuites mémoire
 */
function setMaintenanceInterval(name, callback, delay) {
    // Nettoyer l'interval existant si il y en a un
    if (MaintenanceCore.intervals[name]) {
        clearInterval(MaintenanceCore.intervals[name]);
    }
    
    // Créer le nouvel interval
    MaintenanceCore.intervals[name] = setInterval(callback, delay);
    
    console.log(`📊 Interval '${name}' configuré (${delay}ms)`);
}

/**
 * Nettoyage d'un interval spécifique
 */
function clearMaintenanceInterval(name) {
    if (MaintenanceCore.intervals[name]) {
        clearInterval(MaintenanceCore.intervals[name]);
        delete MaintenanceCore.intervals[name];
        console.log(`🛑 Interval '${name}' arrêté`);
    }
}

/**
 * Nettoyage de tous les intervals
 */
function clearAllMaintenanceIntervals() {
    Object.keys(MaintenanceCore.intervals).forEach(name => {
        clearMaintenanceInterval(name);
    });
    console.log('🧹 Tous les intervals de maintenance nettoyés');
}

/**
 * Formateur de taille de fichier
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Formateur de durée
 */
function formatDuration(milliseconds) {
    const seconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    
    if (hours > 0) {
        return `${hours}h ${minutes % 60}min`;
    } else if (minutes > 0) {
        return `${minutes}min ${seconds % 60}s`;
    } else {
        return `${seconds}s`;
    }
}

/**
 * Générateur de données mockées
 */
function generateMockData(type, options = {}) {
    const generators = {
        metrics: () => ({
            'db-response-time': Math.floor(Math.random() * 50 + 100) + 'ms',
            'db-connections': Math.floor(Math.random() * 8 + 3) + '/100',
            'memory-usage': Math.floor(Math.random() * 30 + 40) + '%',
            'disk-usage': (Math.random() * 5 + 45).toFixed(1) + ' GB',
            'active-users': Math.floor(Math.random() * 5 + 2),
            'expeditions-today': Math.floor(Math.random() * 15 + 8)
        }),
        
        logs: (count = 5) => {
            const levels = ['INFO', 'WARNING', 'ERROR', 'DEBUG'];
            const components = ['ADR_CREATE', 'ADR_SEARCH', 'ADR_AUTH', 'ADR_QUOTA'];
            const logs = [];
            
            for (let i = 0; i < count; i++) {
                const time = new Date(Date.now() - i * 60000).toLocaleTimeString();
                const level = levels[Math.floor(Math.random() * levels.length)];
                const component = components[Math.floor(Math.random() * components.length)];
                const messages = {
                    'INFO': ['Opération terminée', 'Connexion utilisateur', 'Recherche effectuée'],
                    'WARNING': ['Quota élevé', 'Performance dégradée', 'Cache plein'],
                    'ERROR': ['Connexion échouée', 'Fichier introuvable', 'Timeout'],
                    'DEBUG': ['Variable définie', 'Fonction appelée', 'État changé']
                };
                const message = messages[level][Math.floor(Math.random() * messages[level].length)];
                
                logs.push(`[${time}] ${level} - ${component} - ${message}`);
            }
            
            return logs;
        },
        
        backups: (count = 3) => {
            const backups = [];
            for (let i = 0; i < count; i++) {
                const date = new Date(Date.now() - i * 86400000);
                const filename = `backup_adr_${date.toISOString().slice(0,10).replace(/-/g, '')}_020000.sql`;
                const size = (Math.random() * 20 + 30).toFixed(1) + ' MB';
                
                backups.push({
                    name: filename,
                    size: size,
                    date: date.toLocaleDateString('fr-FR'),
                    timestamp: date.getTime()
                });
            }
            return backups;
        }
    };
    
    return generators[type] ? generators[type](options.count || options) : null;
}

/**
 * Gestionnaire d'erreurs global
 */
function handleMaintenanceError(error, context = '') {
    console.error(`Erreur maintenance ${context}:`, error);
    
    const message = error.message || 'Erreur inconnue';
    const containerId = context ? `${context}-result` : 'error-container';
    
    if (document.getElementById(containerId)) {
        showMaintenanceResult(containerId, `❌ Erreur: ${message}`, 'error');
    }
}

/**
 * Notification toast simple
 */
function showToast(message, type = 'info', duration = 3000) {
    // Créer ou réutiliser le conteneur
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }
    
    // Créer le toast
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${getToastColor(type)};
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        pointer-events: all;
        animation: slideInRight 0.3s ease;
        font-weight: 500;
    `;
    
    toast.textContent = message;
    container.appendChild(toast);
    
    // Auto-suppression
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }, duration);
}

function getToastColor(type) {
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    return colors[type] || colors.info;
}

// ========== ANIMATIONS CSS ==========
if (!document.getElementById('maintenance-animations')) {
    const style = document.createElement('style');
    style.id = 'maintenance-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .maintenance-loading {
            display: inline-block;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    `;
    document.head.appendChild(style);
}

// ========== NETTOYAGE À LA FERMETURE ==========
window.addEventListener('beforeunload', function() {
    clearAllMaintenanceIntervals();
});

// ========== EXPORT DES FONCTIONS ==========
window.MaintenanceUtils = {
    showResult: showMaintenanceResult,
    createProgressBar,
    updateProgressBar,
    simulateProgress,
    confirmDangerous: confirmDangerousAction,
    setInterval: setMaintenanceInterval,
    clearInterval: clearMaintenanceInterval,
    clearAllIntervals: clearAllMaintenanceIntervals,
    formatFileSize,
    formatDuration,
    generateMockData,
    handleError: handleMaintenanceError,
    showToast
};

console.log('✅ maintenance-core.js chargé - Fonctions utilitaires disponibles');

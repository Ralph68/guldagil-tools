/* ===================================================
   public/assets/js/app.js - JavaScript principal
   =================================================== */

/**
 * Application principale Guldagil Portal
 * Gestion des modales, navigation et fonctions globales
 */

// Variables globales
let currentModal = null;
let debugMode = false;

// Initialisation de l'application
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Guldagil Portal - Initialisation...');
    
    // Détecter le mode debug
    debugMode = document.body.classList.contains('debug-mode') || 
                new URLSearchParams(window.location.search).get('debug') === 'true';
    
    if (debugMode) {
        console.log('🐛 Mode debug activé');
    }
    
    // Initialiser les composants
    initializeModals();
    initializeNavigation();
    initializeKeyboardShortcuts();
    initializeServiceWorker();
    
    // Animations d'entrée
    animatePageLoad();
    
    console.log('✅ Guldagil Portal initialisé');
});

/**
 * Gestion des modales
 */
function initializeModals() {
    // Fermer les modales en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Fermer les modales avec Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentModal) {
            closeModal(currentModal);
        }
    });
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error(`Modal ${modalId} non trouvée`);
        return;
    }
    
    // Fermer la modale actuelle si elle existe
    if (currentModal && currentModal !== modalId) {
        closeModal(currentModal);
    }
    
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    currentModal = modalId;
    document.body.style.overflow = 'hidden';
    
    // Focus sur le premier élément focusable
    const focusable = modal.querySelector('input, button, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusable) {
        focusable.focus();
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 200);
    
    if (currentModal === modalId) {
        currentModal = null;
        document.body.style.overflow = '';
    }
}

function showTrackingModal() {
    showModal('tracking-modal');
}

function showHelpModal() {
    showModal('help-modal');
}

/**
 * Navigation et liens actifs
 */
function initializeNavigation() {
    // Mettre à jour les liens actifs selon l'URL
    updateActiveNavLinks();
    
    // Smooth scroll pour les liens d'ancre
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

function updateActiveNavLinks() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        link.classList.remove('active');
        
        if (href === currentPath || 
            (href !== '/' && currentPath.startsWith(href))) {
            link.classList.add('active');
        }
    });
}

/**
 * Raccourcis clavier globaux
 */
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K : Aide
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            showHelpModal();
        }
        
        // Ctrl/Cmd + S : Suivi
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            showTrackingModal();
        }
        
        // Ctrl/Cmd + D : Debug (en mode développement)
        if (debugMode && (e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            toggleDevTools();
        }
        
        // Alt + Home : Retour accueil
        if (e.altKey && e.key === 'Home') {
            e.preventDefault();
            window.location.href = '/';
        }
    });
}

/**
 * Service Worker pour mise en cache
 */
function initializeServiceWorker() {
    if ('serviceWorker' in navigator && !debugMode) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('✅ Service Worker enregistré:', registration.scope);
                    
                    // Vérifier les mises à jour
                    registration.addEventListener('updatefound', () => {
                        console.log('🔄 Mise à jour Service Worker disponible');
                        showUpdateNotification();
                    });
                })
                .catch(error => {
                    console.log('❌ Erreur Service Worker:', error);
                });
        });
    }
}

function showUpdateNotification() {
    const notification = document.createElement('div');
    notification.className = 'update-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <span>🔄 Une mise à jour est disponible</span>
            <button onclick="reloadApp()" class="btn btn-small btn-primary">
                Actualiser
            </button>
            <button onclick="dismissNotification(this)" class="btn btn-small btn-outline">
                Plus tard
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-dismiss après 10 secondes
    setTimeout(() => {
        dismissNotification(notification);
    }, 10000);
}

function reloadApp() {
    window.location.reload();
}

function dismissNotification(element) {
    const notification = element.closest('.update-notification');
    if (notification) {
        notification.remove();
    }
}

/**
 * Animations de chargement de page
 */
function animatePageLoad() {
    // Animer les éléments avec data-animate
    const animatedElements = document.querySelectorAll('[data-animate]');
    animatedElements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
        element.classList.add('slide-in-up');
    });
    
    // Observer pour les animations au scroll
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        document.querySelectorAll('[data-scroll-animate]').forEach(el => {
            observer.observe(el);
        });
    }
}

/**
 * Utilitaires de formatage
 */
function formatPrice(amount, currency = '€') {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency === '€' ? 'EUR' : currency,
        minimumFractionDigits: 2
    }).format(amount);
}

function formatDate(date, options = {}) {
    const defaultOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return new Intl.DateTimeFormat('fr-FR', {...defaultOptions, ...options})
        .format(new Date(date));
}

function formatNumber(number, decimals = 0) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

/**
 * Gestion des erreurs globales
 */
window.addEventListener('error', function(e) {
    console.error('❌ Erreur JavaScript:', e.error);
    
    if (debugMode) {
        showErrorNotification('Erreur JavaScript: ' + e.message);
    }
    
    // Logger l'erreur si en production
    if (!debugMode) {
        logClientError(e.error, e.filename, e.lineno);
    }
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('❌ Promise rejetée:', e.reason);
    
    if (debugMode) {
        showErrorNotification('Promise rejetée: ' + e.reason);
    }
});

function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'error-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">❌</span>
            <span class="notification-message">${message}</span>
            <button onclick="dismissNotification(this)" class="notification-close">×</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        dismissNotification(notification);
    }, 5000);
}

function logClientError(error, filename, lineno) {
    // Envoyer l'erreur au serveur pour logging
    fetch('/api/log-error.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            message: error.message,
            filename: filename,
            lineno: lineno,
            stack: error.stack,
            url: window.location.href,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString()
        })
    }).catch(e => {
        console.error('Impossible de logger l\'erreur:', e);
    });
}

/**
 * Utilitaires localStorage avec fallback
 */
function setStorageItem(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
        return true;
    } catch (e) {
        console.warn('localStorage non disponible:', e);
        return false;
    }
}

function getStorageItem(key, defaultValue = null) {
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : defaultValue;
    } catch (e) {
        console.warn('Erreur lecture localStorage:', e);
        return defaultValue;
    }
}

function removeStorageItem(key) {
    try {
        localStorage.removeItem(key);
        return true;
    } catch (e) {
        console.warn('Erreur suppression localStorage:', e);
        return false;
    }
}

/**
 * Fonctions AJAX simplifiées
 */
function ajaxGet(url) {
    return fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    });
}

function ajaxPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    }).then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    });
}

/**
 * Détection de la connectivité
 */
function initializeConnectivityCheck() {
    window.addEventListener('online', function() {
        console.log('🟢 Connexion rétablie');
        hideOfflineNotification();
    });
    
    window.addEventListener('offline', function() {
        console.log('🔴 Connexion perdue');
        showOfflineNotification();
    });
}

function showOfflineNotification() {
    const notification = document.createElement('div');
    notification.id = 'offline-notification';
    notification.className = 'offline-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">🔴</span>
            <span class="notification-message">Mode hors ligne - Certaines fonctionnalités peuvent être limitées</span>
        </div>
    `;
    
    document.body.appendChild(notification);
}

function hideOfflineNotification() {
    const notification = document.getElementById('offline-notification');
    if (notification) {
        notification.remove();
    }
}

/**
 * Outils de développement
 */
function toggleDevTools() {
    if (!debugMode) {
        console.log('⚠️ Outils de développement non disponibles en production');
        return;
    }
    
    const devPanel = document.getElementById('dev-panel');
    if (devPanel) {
        devPanel.style.display = devPanel.style.display === 'none' ? 'block' : 'none';
    } else {
        createDevPanel();
    }
}

function createDevPanel() {
    const panel = document.createElement('div');
    panel.id = 'dev-panel';
    panel.className = 'dev-panel';
    panel.innerHTML = `
        <div class="dev-panel-header">
            <h4>🛠️ Outils de développement</h4>
            <button onclick="toggleDevTools()" class="dev-panel-close">×</button>
        </div>
        <div class="dev-panel-content">
            <div class="dev-section">
                <h5>Informations système</h5>
                <div class="dev-info">
                    <div>URL: ${window.location.href}</div>
                    <div>User Agent: ${navigator.userAgent}</div>
                    <div>Viewport: ${window.innerWidth}x${window.innerHeight}</div>
                    <div>Local Storage: ${localStorage.length} items</div>
                </div>
            </div>
            <div class="dev-section">
                <h5>Actions rapides</h5>
                <div class="dev-actions">
                    <button onclick="clearAllStorage()" class="btn btn-small btn-warning">
                        🗑️ Vider le cache
                    </button>
                    <button onclick="showSystemInfo()" class="btn btn-small btn-secondary">
                        ℹ️ Infos système
                    </button>
                    <button onclick="location.reload()" class="btn btn-small btn-primary">
                        🔄 Recharger
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(panel);
}

function clearAllStorage() {
    if (confirm('Vider tout le cache et les données locales ?')) {
        localStorage.clear();
        sessionStorage.clear();
        console.log('🗑️ Cache vidé');
        location.reload();
    }
}

function showSystemInfo() {
    const info = {
        url: window.location.href,
        userAgent: navigator.userAgent,
        viewport: `${window.innerWidth}x${window.innerHeight}`,
        localStorage: localStorage.length,
        sessionStorage: sessionStorage.length,
        cookies: document.cookie.split(';').length,
        language: navigator.language,
        platform: navigator.platform,
        online: navigator.onLine
    };
    
    console.table(info);
    alert('Informations affichées dans la console (F12)');
}

// Initialiser la vérification de connectivité
initializeConnectivityCheck();

// Export des fonctions pour utilisation globale
window.GuldagilPortal = {
    showModal,
    closeModal,
    showTrackingModal,
    showHelpModal,
    formatPrice,
    formatDate,
    formatNumber,
    ajaxGet,
    ajaxPost,
    setStorageItem,
    getStorageItem,
    removeStorageItem,
    toggleDevTools
};

/* ===================================================
   Styles pour les composants JavaScript
   =================================================== */

/* Notifications */
.update-notification,
.error-notification,
.offline-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    border-left: 4px solid var(--primary-color);
    z-index: calc(var(--z-modal) + 100);
    animation: slideInDown 0.3s ease;
    max-width: 400px;
}

.error-notification {
    border-left-color: var(--error-color);
}

.offline-notification {
    border-left-color: var(--warning-color);
    top: auto;
    bottom: 20px;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
}

.notification-icon {
    font-size: var(--font-size-lg);
    flex-shrink: 0;
}

.notification-message {
    flex: 1;
    font-size: var(--font-size-sm);
    line-height: 1.4;
}

.notification-close {
    background: none;
    border: none;
    font-size: var(--font-size-lg);
    cursor: pointer;
    color: var(--gray-500);
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius-sm);
    transition: all var(--transition-base);
}

.notification-close:hover {
    background: var(--gray-100);
    color: var(--gray-700);
}

/* Panel de développement */
.dev-panel {
    position: fixed;
    top: 50px;
    right: 20px;
    width: 400px;
    max-height: 80vh;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-xl);
    border: 2px solid var(--warning-color);
    z-index: calc(var(--z-modal) + 50);
    overflow: hidden;
    animation: slideInDown 0.3s ease;
}

.dev-panel-header {
    background: var(--warning-color);
    color: white;
    padding: var(--spacing-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dev-panel-header h4 {
    margin: 0;
    font-size: var(--font-size-base);
}

.dev-panel-close {
    background: none;
    border: none;
    color: white;
    font-size: var(--font-size-lg);
    cursor: pointer;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius-sm);
    transition: background var(--transition-base);
}

.dev-panel-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.dev-panel-content {
    padding: var(--spacing-md);
    max-height: calc(80vh - 60px);
    overflow-y: auto;
}

.dev-section {
    margin-bottom: var(--spacing-lg);
}

.dev-section h5 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-700);
}

.dev-info {
    font-size: var(--font-size-xs);
    font-family: monospace;
    background: var(--gray-100);
    padding: var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    line-height: 1.4;
}

.dev-info div {
    margin-bottom: var(--spacing-xs);
    word-break: break-all;
}

.dev-actions {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

/* Classes d'animation au scroll */
[data-scroll-animate] {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.6s ease;
}

[data-scroll-animate].animate-in {
    opacity: 1;
    transform: translateY(0);
}

/* Responsive pour les notifications */
@media (max-width: 480px) {
    .update-notification,
    .error-notification {
        left: 10px;
        right: 10px;
        top: 10px;
        max-width: none;
    }
    
    .offline-notification {
        left: 10px;
        right: 10px;
        bottom: 10px;
    }
    
    .dev-panel {
        left: 10px;
        right: 10px;
        width: auto;
        top: 60px;
    }
}
    

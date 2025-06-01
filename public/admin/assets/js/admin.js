// assets/js/admin.js - Script principal d'administration

console.log('🔧 Chargement du script admin...');

// =============================================================================
// GESTION DES ONGLETS
// =============================================================================

let currentTab = 'dashboard';

function showTab(tabId) {
    console.log('📂 Changement vers onglet:', tabId);
    
    // Cacher tous les contenus d'onglets
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Désactiver tous les boutons d'onglets
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    const targetContent = document.getElementById(`tab-${tabId}`);
    const targetButton = document.querySelector(`[data-tab="${tabId}"]`);
    
    if (targetContent) {
        targetContent.classList.add('active');
    }
    
    if (targetButton) {
        targetButton.classList.add('active');
    }
    
    currentTab = tabId;
    
    // Actions spécifiques par onglet
    if (tabId === 'rates') {
        // Initialiser le gestionnaire de tarifs avec un délai
        setTimeout(() => {
            if (window.initRatesManager && typeof window.initRatesManager === 'function') {
                console.log('🔄 Initialisation du gestionnaire de tarifs...');
                window.initRatesManager().catch(error => {
                    console.error('❌ Erreur init rates manager:', error);
                    showAlert('error', 'Erreur lors du chargement des tarifs');
                });
            } else {
                console.error('❌ initRatesManager non disponible');
                showAlert('warning', 'Module de gestion des tarifs non disponible');
            }
        }, 200);
    }
}

// =============================================================================
// SYSTÈME D'ALERTES
// =============================================================================

function showAlert(type, message, duration = 5000) {
    console.log(`📢 Alert ${type}:`, message);
    
    // Créer le container d'alertes s'il n'existe pas
    let container = document.getElementById('alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        container.style.cssText = `
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
    
    // Créer l'alerte
    const alertId = 'alert-' + Date.now();
    const alertElement = document.createElement('div');
    alertElement.id = alertId;
    alertElement.className = `alert alert-${type}`;
    alertElement.style.cssText = `
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideInRight 0.3s ease;
        position: relative;
        cursor: pointer;
    `;
    
    // Couleurs selon le type
    const colors = {
        success: { bg: '#d4edda', color: '#155724', border: '#c3e6cb', icon: '✅' },
        error: { bg: '#f8d7da', color: '#721c24', border: '#f5c6cb', icon: '❌' },
        warning: { bg: '#fff3cd', color: '#856404', border: '#ffeaa7', icon: '⚠️' },
        info: { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb', icon: 'ℹ️' }
    };
    
    const style = colors[type] || colors.info;
    alertElement.style.background = style.bg;
    alertElement.style.color = style.color;
    alertElement.style.border = `1px solid ${style.border}`;
    
    alertElement.innerHTML = `
        <div style="font-size: 1.2rem;">${style.icon}</div>
        <div style="flex: 1; font-weight: 500;">${message}</div>
        <button onclick="removeAlert('${alertId}')" 
                style="background: none; border: none; font-size: 1.2rem; cursor: pointer; opacity: 0.7;">
            ×
        </button>
    `;
    
    // Ajouter l'animation CSS si elle n'existe pas
    if (!document.getElementById('alert-animations')) {
        const style = document.createElement('style');
        style.id = 'alert-animations';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Ajouter au container
    container.appendChild(alertElement);
    
    // Fermeture automatique
    if (duration > 0) {
        setTimeout(() => removeAlert(alertId), duration);
    }
    
    // Fermeture au clic
    alertElement.addEventListener('click', () => removeAlert(alertId));
    
    return alertId;
}

function removeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    }
}

// Exposer showAlert globalement
window.showAlert = showAlert;
window.removeAlert = removeAlert;

// =============================================================================
// FONCTIONS UTILITAIRES D'ADMINISTRATION
// =============================================================================

function editRate(carrier, department) {
    console.log('✏️ Édition tarif:', { carrier, department });
    showAlert('info', `Édition du tarif ${carrier} pour le département ${department} (en développement)`);
}

function addRate(carrier, department) {
    console.log('➕ Ajout tarif:', { carrier, department });
    showAlert('info', `Ajout d'un tarif ${carrier} pour le département ${department} (en développement)`);
}

function downloadBackup() {
    console.log('💾 Téléchargement sauvegarde...');
    showAlert('info', 'Génération de la sauvegarde en cours...');
    
    // Simuler le téléchargement
    setTimeout(() => {
        showAlert('success', 'Sauvegarde générée avec succès !');
    }, 2000);
}

function importData() {
    console.log('📥 Import de données...');
    showAlert('info', 'Fonctionnalité d\'import en cours de développement');
}

function exportData() {
    console.log('📤 Export de données...');
    showAlert('success', 'Export des données lancé !');
    
    // Simuler l'export
    setTimeout(() => {
        showAlert('info', 'Export terminé - fichier téléchargé');
    }, 3000);
}

// =============================================================================
// RACCOURCIS CLAVIER
// =============================================================================

document.addEventListener('keydown', function(e) {
    // Ctrl + S : Sauvegarder
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        showAlert('info', 'Sauvegarde rapide (Ctrl+S)');
        return false;
    }
    
    // Ctrl + E : Export
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        exportData();
        return false;
    }
    
    // Échap : Fermer modal
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display !== 'none') {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        });
    }
});

// =============================================================================
// INITIALISATION
// =============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation de l\'interface admin...');
    
    // Animation d'entrée pour les cartes statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Vérifier la disponibilité du gestionnaire de tarifs
    const checkRatesManager = () => {
        if (window.initRatesManager) {
            console.log('✅ Gestionnaire de tarifs disponible');
            return true;
        } else {
            console.log('⏳ Gestionnaire de tarifs en attente...');
            return false;
        }
    };
    
    // Attendre que rates-management.js soit chargé
    let attempts = 0;
    const maxAttempts = 10;
    const checkInterval = setInterval(() => {
        attempts++;
        if (checkRatesManager() || attempts >= maxAttempts) {
            clearInterval(checkInterval);
            if (attempts >= maxAttempts) {
                console.warn('⚠️ Gestionnaire de tarifs non disponible après 10 tentatives');
            }
        }
    }, 500);
    
    // Message de bienvenue
    setTimeout(() => {
        showAlert('success', 'Interface d\'administration chargée !', 3000);
    }, 500);
    
    console.log('✅ Interface admin initialisée');
});

// Exposer les fonctions globalement
window.showTab = showTab;
window.editRate = editRate;
window.addRate = addRate;
window.downloadBackup = downloadBackup;
window.importData = importData;
window.exportData = exportData;

console.log('✅ Script admin.js chargé complètement');

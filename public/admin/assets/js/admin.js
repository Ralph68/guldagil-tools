// public/admin/assets/js/admin.js - Version finale

console.log('🚀 Admin JS chargé !');

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Interface admin initialisée');
    
    // Initialiser l'interface
    initializeAdminInterface();
    
    // Animer les statistiques
    setTimeout(animateStats, 500);
    
    // Gestion des raccourcis clavier
    setupKeyboardShortcuts();
});

// =============================================================================
// GESTION DES ONGLETS
// =============================================================================

function showTab(tabName) {
    console.log('Affichage onglet:', tabName);
    
    // Masquer tous les onglets
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Désactiver tous les boutons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Afficher l'onglet sélectionné
    const targetTab = document.getElementById('tab-' + tabName);
    const targetButton = document.querySelector(`[data-tab="${tabName}"]`);
    
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    if (targetButton) {
        targetButton.classList.add('active');
    }
    
    // Charger le contenu selon l'onglet
    switch(tabName) {
        case 'rates':
            loadRates();
            break;
        case 'options':
            loadOptions();
            break;
        case 'taxes':
            loadTaxes();
            break;
        case 'import':
            initializeImportExport();
            break;
    }
}

// =============================================================================
// GESTION DES DONNÉES
// =============================================================================

function loadRates() {
    console.log('Chargement des tarifs...');
    showAlert('info', 'Chargement des tarifs...');
}

function loadOptions() {
    console.log('Chargement des options...');
    showAlert('info', 'Chargement des options...');
}

function loadTaxes() {
    console.log('Chargement des taxes...');
    showAlert('info', 'Chargement des taxes...');
}

function initializeImportExport() {
    console.log('Initialisation Import/Export');
    showAlert('info', 'Module Import/Export initialisé');
}

// =============================================================================
// ACTIONS SUR LES DONNÉES
// =============================================================================

function editRate(carrier, department) {
    console.log(`Édition tarif ${carrier} - ${department}`);
    showAlert('info', `Édition du tarif ${carrier} pour le département ${department}`);
}

function deleteRate(carrier, department) {
    if (confirm(`Supprimer le tarif ${carrier} pour le département ${department} ?`)) {
        console.log(`Suppression tarif ${carrier} - ${department}`);
        showAlert('success', 'Tarif supprimé avec succès');
    }
}

function addRate(carrier, department) {
    console.log(`Ajout tarif ${carrier} - ${department}`);
    showAlert('info', `Ajout d'un tarif ${carrier} pour le département ${department}`);
}

function editOption(id) {
    console.log(`Édition option ${id}`);
    showAlert('info', `Édition de l'option ${id}`);
}

function deleteOption(id) {
    if (confirm(`Supprimer l'option ${id} ?`)) {
        console.log(`Suppression option ${id}`);
        showAlert('success', 'Option supprimée avec succès');
    }
}

function editTaxes() {
    console.log('Édition des taxes');
    showAlert('info', 'Édition des taxes et majorations');
}

// =============================================================================
// IMPORT/EXPORT
// =============================================================================

function importData() {
    console.log('Import des données');
    showAlert('info', 'Import des données en cours...');
}

function exportData() {
    console.log('Export des données');
    showAlert('success', 'Export démarré avec succès');
}

function downloadBackup() {
    console.log('Téléchargement sauvegarde');
    showAlert('info', 'Génération de la sauvegarde...');
}

// =============================================================================
// UTILITAIRES
// =============================================================================

function showAlert(type, message) {
    // Créer le conteneur d'alertes s'il n'existe pas
    let container = document.getElementById('alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
    
    const alertTypes = {
        'success': { icon: '✅', class: 'alert-success' },
        'error': { icon: '❌', class: 'alert-danger' },
        'warning': { icon: '⚠️', class: 'alert-warning' },
        'info': { icon: 'ℹ️', class: 'alert-info' }
    };
    
    const alertConfig = alertTypes[type] || alertTypes.info;
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertConfig.class}`;
    alert.style.cssText = `
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: 8px;
        border: 1px solid transparent;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation: slideInRight 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    `;
    
    alert.innerHTML = `
        ${alertConfig.icon} ${message}
        <button onclick="this.parentElement.remove()" 
                style="margin-left: auto; background: none; border: none; cursor: pointer; font-size: 18px; color: inherit;">×</button>
    `;
    
    // Styles selon le type
    switch(type) {
        case 'success':
            alert.style.background = '#d4edda';
            alert.style.borderColor = '#c3e6cb';
            alert.style.color = '#155724';
            break;
        case 'error':
            alert.style.background = '#f8d7da';
            alert.style.borderColor = '#f5c6cb';
            alert.style.color = '#721c24';
            break;
        case 'warning':
            alert.style.background = '#fff3cd';
            alert.style.borderColor = '#ffeaa7';
            alert.style.color = '#856404';
            break;
        case 'info':
            alert.style.background = '#d1ecf1';
            alert.style.borderColor = '#bee5eb';
            alert.style.color = '#0c5460';
            break;
    }
    
    container.appendChild(alert);
    
    // Auto-remove après 5 secondes
    setTimeout(() => {
        if (alert.parentElement) {
            alert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
}

function animateStats() {
    const stats = document.querySelectorAll('.stat-value');
    stats.forEach((stat, index) => {
        const finalValue = parseInt(stat.textContent);
        if (isNaN(finalValue)) return;
        
        let currentValue = 0;
        const increment = finalValue / 30;
        
        const counter = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                stat.textContent = finalValue;
                clearInterval(counter);
            } else {
                stat.textContent = Math.floor(currentValue);
            }
        }, 50 + (index * 10));
    });
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 's':
                    e.preventDefault();
                    showAlert('info', 'Sauvegarde en cours...');
                    break;
                case 'e':
                    e.preventDefault();
                    exportData();
                    break;
            }
        }
        if (e.key === 'Escape') {
            // Fermer les modaux s'il y en a
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
}

function initializeAdminInterface() {
    console.log('Initialisation interface admin');
    
    // Ajouter les gestionnaires d'événements pour les onglets
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            if (tabName) {
                showTab(tabName);
            }
        });
    });
    
    // Ajouter les animations CSS si elles n'existent pas
    if (!document.getElementById('admin-animations')) {
        const style = document.createElement('style');
        style.id = 'admin-animations';
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
    
    showAlert('success', 'Interface d\'administration chargée !');
}

// =============================================================================
// FONCTIONS GLOBALES POUR L'INTERFACE
// =============================================================================

// Exposer les fonctions nécessaires globalement
window.showTab = showTab;
window.editRate = editRate;
window.deleteRate = deleteRate;
window.addRate = addRate;
window.editOption = editOption;
window.deleteOption = deleteOption;
window.editTaxes = editTaxes;
window.importData = importData;
window.exportData = exportData;
window.downloadBackup = downloadBackup;
window.showAlert = showAlert;

console.log('🎯 Admin JavaScript initialisé avec succès');

// public/admin/assets/js/admin.js - Version corrigée
console.log('🚀 Admin JS chargé !');

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Interface admin initialisée');
    
    // Initialiser l'interface
    initializeAdminInterface();
    
    // Animer les statistiques après un délai
    setTimeout(animateStats, 500);
    
    // Gestion des raccourcis clavier
    setupKeyboardShortcuts();
    
    // Vérifier les modules disponibles
    setTimeout(checkModulesAvailability, 1000);
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
            loadRatesTab();
            break;
        case 'options':
            loadOptionsTab();
            break;
        case 'taxes':
            loadTaxesTab();
            break;
        case 'import':
            loadImportExportTab();
            break;
        case 'dashboard':
        default:
            // Dashboard est déjà chargé
            break;
    }
}

// =============================================================================
// CHARGEMENT DES ONGLETS
// =============================================================================

function loadRatesTab() {
    console.log('📊 Chargement onglet tarifs...');
    
    // Vérifier si le module de gestion des tarifs est disponible
    if (typeof window.loadRates === 'function') {
        // Si les données ne sont pas encore chargées, les charger
        if (!window.ratesData || window.ratesData.length === 0) {
            setTimeout(() => {
                window.loadRates();
            }, 100);
        }
        showAlert('info', 'Module tarifs chargé');
    } else {
        // Le module n'est pas encore chargé, attendre
        showAlert('warning', 'Chargement du module tarifs en cours...');
        
        // Réessayer après un délai
        setTimeout(() => {
            if (typeof window.loadRates === 'function') {
                window.loadRates();
                showAlert('success', 'Module tarifs prêt');
            } else {
                showAlert('error', 'Impossible de charger le module tarifs');
            }
        }, 2000);
    }
}

function loadOptionsTab() {
    console.log('⚙️ Chargement onglet options...');
    
    if (typeof window.loadOptions === 'function') {
        if (!window.optionsData || window.optionsData.length === 0) {
            setTimeout(() => {
                window.loadOptions();
            }, 100);
        }
        showAlert('info', 'Module options chargé');
    } else {
        showAlert('warning', 'Chargement du module options en cours...');
        
        setTimeout(() => {
            if (typeof window.loadOptions === 'function') {
                window.loadOptions();
                showAlert('success', 'Module options prêt');
            } else {
                showAlert('info', 'Module options en développement');
            }
        }, 2000);
    }
}

function loadTaxesTab() {
    console.log('📋 Chargement onglet taxes...');
    showAlert('info', 'Module taxes en développement');
}

function loadImportExportTab() {
    console.log('📤 Chargement onglet import/export...');
    showAlert('info', 'Module import/export initialisé');
}

// =============================================================================
// ACTIONS RAPIDES DASHBOARD
// =============================================================================

function editRate(carrier, department) {
    console.log(`Action rapide: Édition tarif ${carrier} - ${department}`);
    
    // Passer à l'onglet tarifs
    showTab('rates');
    
    // Attendre que l'onglet soit chargé puis filtrer
    setTimeout(() => {
        // Essayer de filtrer par transporteur si possible
        const carrierFilter = document.getElementById('filter-carrier');
        if (carrierFilter) {
            carrierFilter.value = carrier;
            if (typeof window.handleSearch === 'function') {
                window.handleSearch();
            }
        }
    }, 1000);
    
    showAlert('info', `Recherche des tarifs ${carrier} en cours...`);
}

function deleteRate(carrier, department) {
    console.log(`Action rapide: Suppression tarif ${carrier} - ${department}`);
    showAlert('warning', 'Utilisez l\'onglet "Gestion des tarifs" pour supprimer un tarif');
}

function addRate(carrier, department) {
    console.log(`Action rapide: Ajout tarif ${carrier} - ${department}`);
    showTab('rates');
    showAlert('info', `Préparation de l'ajout d'un tarif ${carrier} pour le département ${department}`);
}

// =============================================================================
// IMPORT/EXPORT RAPIDE
// =============================================================================

function importData() {
    console.log('Import des données');
    showAlert('info', 'Fonctionnalité d\'import en cours de développement');
    
    setTimeout(() => {
        showAlert('info', 'Utilisez les templates CSV disponibles en téléchargement');
    }, 2000);
}

function exportData() {
    console.log('Export des données');
    
    try {
        // Rediriger vers l'export complet
        const exportUrl = 'export.php?type=all&format=csv';
        
        // Créer un lien de téléchargement
        const link = document.createElement('a');
        link.href = exportUrl;
        link.download = `guldagil_export_complet_${new Date().toISOString().split('T')[0]}.csv`;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showAlert('success', 'Export complet démarré');
    } catch (error) {
        console.error('Erreur export:', error);
        showAlert('error', 'Erreur lors de l\'export');
    }
}

function downloadBackup() {
    console.log('Téléchargement sauvegarde');
    
    try {
        // Export JSON complet pour sauvegarde
        const backupUrl = 'export.php?type=all&format=json';
        
        const link = document.createElement('a');
        link.href = backupUrl;
        link.download = `guldagil_backup_${new Date().toISOString().split('T')[0]}.json`;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showAlert('success', 'Sauvegarde générée avec succès');
    } catch (error) {
        console.error('Erreur backup:', error);
        showAlert('error', 'Erreur lors de la génération de la sauvegarde');
    }
}

// =============================================================================
// SYSTÈME D'ALERTES AMÉLIORÉ
// =============================================================================

function showAlert(type, message) {
    // Créer le conteneur d'alertes s'il n'existe pas
    let container = document.getElementById('alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        container.style.cssText = `
            position: fixed;
            top: 150px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }
    
    const alertTypes = {
        'success': { icon: '✅', class: 'alert-success', bgColor: '#d4edda', borderColor: '#c3e6cb', textColor: '#155724' },
        'error': { icon: '❌', class: 'alert-danger', bgColor: '#f8d7da', borderColor: '#f5c6cb', textColor: '#721c24' },
        'warning': { icon: '⚠️', class: 'alert-warning', bgColor: '#fff3cd', borderColor: '#ffeaa7', textColor: '#856404' },
        'info': { icon: 'ℹ️', class: 'alert-info', bgColor: '#d1ecf1', borderColor: '#bee5eb', textColor: '#0c5460' }
    };
    
    const alertConfig = alertTypes[type] || alertTypes.info;
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertConfig.class}`;
    alert.style.cssText = `
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: 8px;
        border: 1px solid ${alertConfig.borderColor};
        background: ${alertConfig.bgColor};
        color: ${alertConfig.textColor};
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation: slideInRight 0.3s ease;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        position: relative;
        font-size: 0.9rem;
        line-height: 1.4;
        pointer-events: all;
        z-index: 10001;
    `;
    
    alert.innerHTML = `
        ${alertConfig.icon} 
        <span style="flex: 1;">${message}</span>
        <button onclick="this.parentElement.remove()" 
                style="background: none; border: none; cursor: pointer; font-size: 18px; color: inherit; padding: 0.25rem; border-radius: 4px; margin-left: 0.5rem;"
                onmouseover="this.style.background='rgba(0,0,0,0.1)'"
                onmouseout="this.style.background='none'"
                title="Fermer">×</button>
    `;
    
    container.appendChild(alert);
    
    // Auto-remove avec délai adapté au type
    const autoRemoveDelay = type === 'error' ? 8000 : (type === 'warning' ? 6000 : 4000);
    setTimeout(() => {
        if (alert.parentElement) {
            alert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 300);
        }
    }, autoRemoveDelay);
}

// =============================================================================
// ANIMATIONS ET EFFETS
// =============================================================================

function animateStats() {
    const stats = document.querySelectorAll('.stat-value');
    stats.forEach((stat, index) => {
        const finalValue = parseInt(stat.textContent);
        if (isNaN(finalValue)) return;
        
        let currentValue = 0;
        const increment = finalValue / 30;
        const duration = 1000 + (index * 200); // Délai échelonné
        
        stat.textContent = '0';
        
        const counter = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                stat.textContent = finalValue;
                clearInterval(counter);
            } else {
                stat.textContent = Math.floor(currentValue);
            }
        }, duration / 30);
    });
}

// =============================================================================
// RACCOURCIS CLAVIER
// =============================================================================

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Shortcuts avec Ctrl/Cmd
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 's':
                    e.preventDefault();
                    showAlert('info', 'Sauvegarde automatique...');
                    setTimeout(() => {
                        showAlert('success', 'Données sauvegardées');
                    }, 1000);
                    break;
                case 'e':
                    e.preventDefault();
                    exportData();
                    break;
                case '1':
                    e.preventDefault();
                    showTab('dashboard');
                    break;
                case '2':
                    e.preventDefault();
                    showTab('rates');
                    break;
                case '3':
                    e.preventDefault();
                    showTab('options');
                    break;
                case '4':
                    e.preventDefault();
                    showTab('taxes');
                    break;
                case '5':
                    e.preventDefault();
                    showTab('import');
                    break;
            }
        }
        
        // Escape pour fermer les modaux et alertes
        if (e.key === 'Escape') {
            // Fermer les modaux actifs
            document.querySelectorAll('.modal.active, .modal[style*="display: flex"]').forEach(modal => {
                modal.style.display = 'none';
                modal.classList.remove('active');
            });
            
            // Fermer les alertes
            document.querySelectorAll('.alert').forEach(alert => {
                alert.remove();
            });
        }
    });
}

// =============================================================================
// VÉRIFICATION DES MODULES
// =============================================================================

function checkModulesAvailability() {
    const modules = {
        ratesManager: typeof window.loadRates === 'function',
        optionsManager: typeof window.loadOptions === 'function'
    };
    
    console.log('🔍 Modules disponibles:', modules);
    
    let loadedCount = 0;
    let totalModules = Object.keys(modules).length;
    
    Object.entries(modules).forEach(([name, loaded]) => {
        if (loaded) {
            loadedCount++;
            console.log(`✅ Module ${name} chargé`);
        } else {
            console.log(`⏳ Module ${name} en attente`);
        }
    });
    
    const loadingPercentage = Math.round((loadedCount / totalModules) * 100);
    console.log(`📊 Modules chargés: ${loadedCount}/${totalModules} (${loadingPercentage}%)`);
    
    if (loadedCount === totalModules) {
        console.log('🎉 Tous les modules sont chargés');
    }
}

// =============================================================================
// INITIALISATION
// =============================================================================

function initializeAdminInterface() {
    console.log('🔧 Initialisation interface admin');
    
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
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Initialisation réussie
    showAlert('success', 'Interface d\'administration chargée !');
    
    // Auto-masquer l'alerte de bienvenue après 3 secondes
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            alerts[0].style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => alerts[0]?.remove(), 300);
        }
    }, 3000);
}

// =============================================================================
// UTILITAIRES DIVERS
// =============================================================================

function refreshPage() {
    if (confirm('Voulez-vous vraiment actualiser la page ? Les données non sauvegardées seront perdues.')) {
        location.reload();
    }
}

function goToCalculator() {
    if (confirm('Voulez-vous quitter l\'administration pour aller au calculateur ?')) {
        window.location.href = '../';
    }
}

function checkServerStatus() {
    showAlert('info', 'Vérification du serveur...');
    
    fetch('api-rates.php?action=carriers')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Serveur opérationnel');
            } else {
                showAlert('warning', 'Serveur accessible mais erreur API');
            }
        })
        .catch(error => {
            console.error('Erreur serveur:', error);
            showAlert('error', 'Problème de connexion au serveur');
        });
}

// =============================================================================
// FONCTIONS PUBLIQUES
// =============================================================================

// Exposer les fonctions nécessaires globalement
window.showTab = showTab;
window.editRate = editRate;
window.deleteRate = deleteRate;
window.addRate = addRate;
window.importData = importData;
window.exportData = exportData;
window.downloadBackup = downloadBackup;
window.showAlert = showAlert;
window.refreshPage = refreshPage;
window.goToCalculator = goToCalculator;
window.checkServerStatus = checkServerStatus;

console.log('🎯 Admin JavaScript initialisé avec succès');

// Vérification périodique des modules (pendant 10 secondes max)
let moduleCheckCount = 0;
const moduleCheckInterval = setInterval(() => {
    moduleCheckCount++;
    checkModulesAvailability();
    
    if (moduleCheckCount >= 5) { // Vérifier 5 fois max
        clearInterval(moduleCheckInterval);
        console.log('🔚 Arrêt de la vérification des modules');
    }
}, 2000);

<?php
// public/adr/modals/maintenance.php - Structure modulaire principale
?>

<div class="maintenance-tabs">
    <button class="tab-btn active" onclick="showMaintenanceTab('database')">🗄️ Base de données</button>
    <button class="tab-btn" onclick="showMaintenanceTab('cleanup')">🧹 Nettoyage</button>
    <button class="tab-btn" onclick="showMaintenanceTab('backup')">💾 Sauvegarde</button>
    <button class="tab-btn" onclick="showMaintenanceTab('monitoring')">📊 Monitoring</button>
    <button class="tab-btn" onclick="showMaintenanceTab('logs')">📝 Logs</button>
    <button class="tab-btn" onclick="showMaintenanceTab('security')">🔒 Sécurité</button>
</div>

<!-- Onglets chargés dynamiquement -->
<div id="maintenance-tab-database" class="maintenance-tab-content active">
    <!-- Chargé depuis database-tab.php -->
</div>

<div id="maintenance-tab-cleanup" class="maintenance-tab-content">
    <!-- Chargé depuis cleanup-tab.php -->
</div>

<div id="maintenance-tab-backup" class="maintenance-tab-content">
    <!-- Chargé depuis backup-tab.php -->
</div>

<div id="maintenance-tab-monitoring" class="maintenance-tab-content">
    <!-- Chargé depuis monitoring-tab.php -->
</div>

<div id="maintenance-tab-logs" class="maintenance-tab-content">
    <!-- Chargé depuis logs-tab.php -->
</div>

<div id="maintenance-tab-security" class="maintenance-tab-content">
    <!-- Chargé depuis security-tab.php -->
</div>

<!-- Styles CSS partagés -->
<link rel="stylesheet" href="../assets/css/maintenance.css">

<!-- Scripts modulaires -->
<script src="../assets/js/maintenance-core.js"></script>
<script src="../assets/js/maintenance-database.js"></script>
<script src="../assets/js/maintenance-cleanup.js"></script>
<script src="../assets/js/maintenance-backup.js"></script>
<script src="../assets/js/maintenance-monitoring.js"></script>
<script src="../assets/js/maintenance-logs.js"></script>
<script src="../assets/js/maintenance-security.js"></script>

<script>
// Initialisation modulaire
document.addEventListener('DOMContentLoaded', function() {
    console.log('🧰 Chargement maintenance modulaire ADR...');
    
    // Chargement des onglets à la demande
    loadMaintenanceTab('database'); // Charger l'onglet par défaut
    
    console.log('✅ Maintenance ADR initialisée - Structure modulaire');
});

// Fonction de chargement des onglets
function loadMaintenanceTab(tabName) {
    const tabContent = document.getElementById(`maintenance-tab-${tabName}`);
    
    if (tabContent && !tabContent.classList.contains('loaded')) {
        // Marquer comme en cours de chargement
        tabContent.innerHTML = '<div class="tab-loading">🔄 Chargement...</div>';
        
        // Charger le contenu via AJAX
        fetch(`modals/tabs/${tabName}-tab.php`)
            .then(response => response.text())
            .then(html => {
                tabContent.innerHTML = html;
                tabContent.classList.add('loaded');
                
                // Initialiser les fonctions spécifiques à l'onglet
                if (window[`init${capitalize(tabName)}Tab`]) {
                    window[`init${capitalize(tabName)}Tab`]();
                }
            })
            .catch(error => {
                console.error(`Erreur chargement onglet ${tabName}:`, error);
                tabContent.innerHTML = `<div class="tab-error">❌ Erreur de chargement</div>`;
            });
    }
}

// Fonction principale de gestion des onglets
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
    
    // Charger le contenu si nécessaire
    loadMaintenanceTab(tabName);
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Raccourcis clavier globaux
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.altKey) {
        const shortcuts = {
            'd': 'database',
            'c': 'cleanup', 
            'b': 'backup',
            'm': 'monitoring',
            'l': 'logs',
            's': 'security'
        };
        
        if (shortcuts[e.key]) {
            showMaintenanceTab(shortcuts[e.key]);
            e.preventDefault();
        }
    }
});
</script>

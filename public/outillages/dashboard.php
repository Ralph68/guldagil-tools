<?php
/**
 * Titre: Composant Dashboard Outillages
 * Chemin: /public/outillages/components/dashboard.php
 * Version: 0.5 beta + build auto
 */

// Récupération des données
$stats = $outillageManager->getStatistiquesGenerales();
$demandesEnAttente = $outillageManager->getDemandesEnAttente();

// Configuration des droits selon le rôle
$canManageInventory = in_array($user_role, ['admin', 'dev']);
$canValidateDemands = in_array($user_role, ['admin', 'dev']);
$canViewStats = in_array($user_role, ['admin', 'dev']);
$canManageEmployees = in_array($user_role, ['admin', 'dev']);
?>

<!-- En-tête du dashboard -->
<div class="dashboard-header">
    <div class="header-content">
        <h1><i class="fas fa-tools"></i> Dashboard Outillages</h1>
        <p>Gestion complète des outils et équipements</p>
    </div>
    <div class="header-actions">
        <?php if ($canManageInventory): ?>
        <button class="btn btn-primary" onclick="window.location.href='?action=inventory&mode=add'">
            <i class="fas fa-plus"></i> Ajouter un outil
        </button>
        <?php endif; ?>
        <button class="btn btn-secondary" onclick="window.location.href='?action=inventory'">
            <i class="fas fa-search"></i> Consulter inventaire
        </button>
    </div>
</div>

<!-- Statistiques principales -->
<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-icon">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['total_outils']) ?></h3>
            <p>Outils total</p>
            <span class="stat-change">Inventaire complet</span>
        </div>
    </div>
    
    <div class="stat-card assigned">
        <div class="stat-icon">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['outils_attribues']) ?></h3>
            <p>Outils attribués</p>
            <span class="stat-change">
                <?php 
                $percentage = $stats['total_outils'] > 0 ? round(($stats['outils_attribues'] / $stats['total_outils']) * 100) : 0;
                echo $percentage . '% du total';
                ?>
            </span>
        </div>
    </div>
    
    <div class="stat-card pending">
        <div class="stat-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['demandes_attente']) ?></h3>
            <p>Demandes en attente</p>
            <span class="stat-change">
                <?php if ($stats['demandes_attente'] > 0): ?>
                    Nécessite attention
                <?php else: ?>
                    Toutes traitées
                <?php endif; ?>
            </span>
        </div>
    </div>
    
    <div class="stat-card maintenance">
        <div class="stat-icon danger">
            <i class="fas fa-wrench"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['maintenance_due']) ?></h3>
            <p>Maintenance due</p>
            <span class="stat-change">
                <?php if ($stats['maintenance_due'] > 0): ?>
                    Action requise
                <?php else: ?>
                    À jour
                <?php endif; ?>
            </span>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="quick-actions">
    <h2><i class="fas fa-bolt"></i> Actions rapides</h2>
    <div class="actions-grid">
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-hand-paper"></i>
            </div>
            <h3>Faire une demande</h3>
            <p>Demander l'attribution d'un outil spécifique</p>
            <button class="btn btn-info" onclick="window.location.href='?action=demandes&mode=new'">
                <i class="fas fa-paper-plane"></i> Nouvelle demande
            </button>
        </div>
        
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>Rechercher un outil</h3>
            <p>Trouver un outil dans l'inventaire</p>
            <button class="btn btn-secondary" onclick="showSearchModal()">
                <i class="fas fa-search"></i> Rechercher
            </button>
        </div>
        
        <?php if ($canManageInventory): ?>
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-qrcode"></i>
            </div>
            <h3>Scanner QR Code</h3>
            <p>Identifier rapidement un outil</p>
            <button class="btn btn-primary" onclick="startQRScanner()">
                <i class="fas fa-camera"></i> Scanner
            </button>
        </div>
        <?php endif; ?>
        
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h3>Mes attributions</h3>
            <p>Voir les outils qui me sont attribués</p>
            <button class="btn btn-warning" onclick="window.location.href='?action=demandes&view=my-tools'">
                <i class="fas fa-list"></i> Voir mes outils
            </button>
        </div>
    </div>
</div>

<!-- Demandes en attente (pour les gestionnaires) -->
<?php if ($canValidateDemands && !empty($demandesEnAttente)): ?>
<div class="pending-requests">
    <h2><i class="fas fa-bell"></i> Demandes à traiter</h2>
    <div class="requests-container">
        <?php foreach (array_slice($demandesEnAttente, 0, 5) as $demande): ?>
        <div class="request-card">
            <div class="request-info">
                <h4><?= htmlspecialchars($demande['designation'] ?? 'Outil non spécifié') ?></h4>
                <p><strong>Demandeur:</strong> <?= htmlspecialchars($demande['demandeur'] ?? 'N/A') ?></p>
                <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($demande['created_at'] ?? 'now')) ?></p>
                <?php if (!empty($demande['raison_demande'])): ?>
                <p><strong>Raison:</strong> <?= htmlspecialchars($demande['raison_demande']) ?></p>
                <?php endif; ?>
            </div>
            <div class="request-actions">
                <button class="btn btn-sm btn-success" onclick="approuveDemande(<?= $demande['id'] ?? 0 ?>)">
                    <i class="fas fa-check"></i> Approuver
                </button>
                <button class="btn btn-sm btn-danger" onclick="rejetDemande(<?= $demande['id'] ?? 0 ?>)">
                    <i class="fas fa-times"></i> Rejeter
                </button>
                <button class="btn btn-sm btn-info" onclick="viewDemande(<?= $demande['id'] ?? 0 ?>)">
                    <i class="fas fa-eye"></i> Détails
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (count($demandesEnAttente) > 5): ?>
        <div class="view-all">
            <button class="btn btn-outline" onclick="window.location.href='?action=demandes&filter=pending'">
                Voir toutes les demandes (<?= count($demandesEnAttente) ?>)
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Graphiques et analyses -->
<?php if ($canViewStats): ?>
<div class="analytics-section">
    <h2><i class="fas fa-chart-line"></i> Analyses</h2>
    <div class="charts-grid">
        <div class="chart-container">
            <h3>Répartition des outils</h3>
            <canvas id="outilsChart" width="400" height="300"></canvas>
        </div>
        <div class="chart-container">
            <h3>Évolution des demandes</h3>
            <canvas id="demandesChart" width="400" height="300"></canvas>
        </div>
        <div class="chart-container">
            <h3>Outils par catégorie</h3>
            <canvas id="categoriesChart" width="400" height="300"></canvas>
        </div>
        <div class="chart-container">
            <h3>Taux d'utilisation</h3>
            <canvas id="utilisationChart" width="400" height="300"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Alertes et notifications -->
<div class="alerts-section">
    <h2><i class="fas fa-exclamation-triangle"></i> Alertes</h2>
    <div class="alerts-grid">
        <?php if ($stats['maintenance_due'] > 0): ?>
        <div class="alert-card maintenance">
            <div class="alert-icon">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="alert-content">
                <h4>Maintenance requise</h4>
                <p><?= $stats['maintenance_due'] ?> outil(s) nécessitent une maintenance</p>
                <button class="btn btn-sm btn-warning" onclick="window.location.href='?action=inventory&filter=maintenance'">
                    Voir les outils
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($stats['demandes_attente'] > 10): ?>
        <div class="alert-card requests">
            <div class="alert-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="alert-content">
                <h4>Backlog important</h4>
                <p>Plus de 10 demandes en attente de traitement</p>
                <button class="btn btn-sm btn-info" onclick="window.location.href='?action=demandes&filter=pending'">
                    Traiter les demandes
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php 
        $available_tools = $stats['total_outils'] - $stats['outils_attribues'];
        if ($available_tools < 5): ?>
        <div class="alert-card stock">
            <div class="alert-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <div class="alert-content">
                <h4>Stock faible</h4>
                <p>Seulement <?= $available_tools ?> outil(s) disponible(s)</p>
                <button class="btn btn-sm btn-primary" onclick="window.location.href='?action=inventory&mode=add'">
                    Ajouter des outils
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de recherche -->
<div id="searchModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-search"></i> Rechercher un outil</h3>
            <button class="close-btn" onclick="closeSearchModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="search-form">
                <input type="text" id="searchInput" placeholder="Nom, marque, numéro de série..." class="form-input">
                <select id="categoryFilter" class="form-select">
                    <option value="">Toutes les catégories</option>
                    <option value="manuel">Outils manuels</option>
                    <option value="electrique">Outils électriques</option>
                    <option value="mesure">Mesure et contrôle</option>
                    <option value="securite">Sécurité</option>
                </select>
                <button class="btn btn-primary" onclick="performSearch()">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </div>
            <div id="searchResults" class="search-results"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphiques
<?php if ($canViewStats): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique répartition outils
    const outilsCtx = document.getElementById('outilsChart').getContext('2d');
    new Chart(outilsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Attribués', 'Disponibles', 'En maintenance'],
            datasets: [{
                data: [
                    <?= $stats['outils_attribues'] ?>, 
                    <?= $stats['total_outils'] - $stats['outils_attribues'] - $stats['maintenance_due'] ?>, 
                    <?= $stats['maintenance_due'] ?>
                ],
                backgroundColor: ['#4CAF50', '#2196F3', '#FF9800'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Graphique évolution demandes
    const demandesCtx = document.getElementById('demandesChart').getContext('2d');
    new Chart(demandesCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
            datasets: [{
                label: 'Demandes',
                data: [12, 19, 15, 25, 22, <?= $stats['demandes_attente'] ?>],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique catégories
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    new Chart(categoriesCtx, {
        type: 'bar',
        data: {
            labels: ['Manuel', 'Électrique', 'Mesure', 'Sécurité', 'Consommable'],
            datasets: [{
                label: 'Nombre d\'outils',
                data: [15, 12, 8, 6, 4],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique taux d'utilisation
    const utilisationCtx = document.getElementById('utilisationChart').getContext('2d');
    new Chart(utilisationCtx, {
        type: 'radar',
        data: {
            labels: ['Perceuses', 'Clés', 'Multimètres', 'Casques', 'Gants'],
            datasets: [{
                label: 'Taux d\'utilisation (%)',
                data: [85, 92, 76, 98, 88],
                borderColor: '#FF6384',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                pointBackgroundColor: '#FF6384'
            }]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
});
<?php endif; ?>

// Fonctions de gestion des demandes
function approuveDemande(id) {
    if (confirm('Approuver cette demande ?')) {
        fetch('./api/demandes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'approve', id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Demande approuvée avec succès', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Erreur: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showNotification('Erreur de communication', 'error');
        });
    }
}

function rejetDemande(id) {
    const raison = prompt('Raison du rejet (optionnel):');
    if (raison !== null) {
        fetch('./api/demandes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reject', id: id, raison: raison })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Demande rejetée', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Erreur: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showNotification('Erreur de communication', 'error');
        });
    }
}

function viewDemande(id) {
    window.location.href = `?action=demandes&view=${id}`;
}

// Modal de recherche
function showSearchModal() {
    document.getElementById('searchModal').style.display = 'flex';
    document.getElementById('searchInput').focus();
}

function closeSearchModal() {
    document.getElementById('searchModal').style.display = 'none';
}

function performSearch() {
    const query = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    
    if (query.length < 2) {
        showNotification('Veuillez saisir au moins 2 caractères', 'warning');
        return;
    }
    
    fetch('./api/search.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: query, category: category })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySearchResults(data.results);
        } else {
            showNotification('Erreur de recherche: ' + data.error, 'error');
        }
    })
    .catch(error => {
        showNotification('Erreur de communication', 'error');
    });
}

function displaySearchResults(results) {
    const container = document.getElementById('searchResults');
    
    if (results.length === 0) {
        container.innerHTML = '<p class="no-results">Aucun résultat trouvé</p>';
        return;
    }
    
    let html = '<div class="results-list">';
    results.forEach(tool => {
        html += `
            <div class="result-item">
                <div class="tool-info">
                    <h4>${tool.designation}</h4>
                    <p>Marque: ${tool.marque || 'N/A'} | Modèle: ${tool.modele || 'N/A'}</p>
                    <span class="status ${tool.etat}">${tool.etat}</span>
                </div>
                <div class="tool-actions">
                    <button class="btn btn-sm btn-info" onclick="viewTool(${tool.id})">Voir</button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Scanner QR Code (simulé)
function startQRScanner() {
    if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
        showNotification('Scanner QR Code en développement', 'info');
        // Ici, intégrer une librairie de scan QR comme QuaggaJS ou ZXing
    } else {
        showNotification('Caméra non disponible sur cet appareil', 'warning');
    }
}

// Système de notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Fermeture modal avec échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSearchModal();
    }
});

console.log('🔧 Dashboard Outillages chargé');
console.log('📊 Stats:', <?= json_encode($stats) ?>);
</script>

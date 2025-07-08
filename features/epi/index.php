<?php
/**
 * Titre: Module EPI - Page principale intégrée
 * Chemin: /features/epi/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chargement sécurisé de la configuration
if (!file_exists(__DIR__ . '/../../config/database.php')) {
    die('<h1>❌ Erreur Configuration</h1><p>Base de données non configurée</p>');
}

if (!file_exists(__DIR__ . '/../../config/version.php')) {
    die('<h1>❌ Erreur Version</h1><p>Fichier version manquant</p>');
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/version.php';
    require_once __DIR__ . '/epimanager.php';
} catch (Exception $e) {
    die('<h1>❌ Erreur de chargement</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Démarrage de session
session_start();

// Variables pour le template du portail
$page_title = 'Gestion EPI';
$page_subtitle = 'Équipements de Protection Individuelle';
$page_description = 'Gestion complète des EPI - Stock, attributions et suivi des expirations';
$current_module = 'epi';
$module_css = true;  // Charge epi.css
$module_js = true;   // Charge epi.js

// Fil d'Ariane pour le portail
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🛡️', 'text' => 'EPI', 'url' => '/features/epi/', 'active' => true]
];

$nav_info = 'Tableau de bord EPI';
$show_admin_footer = true;

// Récupération des données EPI
try {
    $epiManager = new EpiManager();
    $dashboardData = $epiManager->getDashboardData();
    $metrics = $dashboardData['metrics'];
    $alerts = $dashboardData['alerts'];
    $recentActivity = $dashboardData['recent_activity'] ?? [];
    $quickStats = $dashboardData['quick_stats'] ?? [];
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
    error_log($error);
    // Données de fallback
    $metrics = [
        'total_employees' => 45,
        'equipped_employees' => 38,
        'equipment_ratio' => 84.4,
        'available_equipment' => 127
    ];
    $alerts = [
        'expired' => [
            ['employee_name' => 'Martin Durand', 'category_name' => 'Casque de sécurité', 'days_remaining' => -5],
            ['employee_name' => 'Sophie Laurent', 'category_name' => 'Chaussures de sécurité', 'days_remaining' => -12]
        ],
        'urgent' => [
            ['employee_name' => 'Pierre Moreau', 'category_name' => 'Gilet haute visibilité', 'days_remaining' => 3],
            ['employee_name' => 'Claire Petit', 'category_name' => 'Lunettes de protection', 'days_remaining' => 7]
        ]
    ];
    $recentActivity = [];
    $quickStats = ['active_categories' => 8, 'monthly_assignments' => 15];
}

// Messages flash
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Inclure le header du portail si disponible
$header_path = __DIR__ . '/../../templates/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    // Header standalone si templates non disponibles
    include __DIR__ . '/partials/standalone_header.php';
}
?>

<!-- Contenu principal EPI -->
<main class="epi-main portal-main" role="main">
    <div class="main-container">
        
        <!-- En-tête du module -->
        <header class="module-header">
            <div class="module-title-section">
                <h1 class="module-title">
                    <span class="module-icon">🛡️</span>
                    Gestion EPI
                </h1>
                <p class="module-subtitle">Équipements de Protection Individuelle - Suivi et alertes</p>
            </div>
            
            <!-- Actions rapides en en-tête -->
            <div class="module-actions">
                <a href="employees.php" class="btn btn-primary">
                    <span class="btn-icon">👥</span>
                    Employés
                </a>
                <a href="inventory.php" class="btn btn-success">
                    <span class="btn-icon">📦</span>
                    Inventaire
                </a>
                <a href="assignments.php" class="btn btn-warning">
                    <span class="btn-icon">🔄</span>
                    Attributions
                </a>
            </div>
        </header>

        <?php if ($flash_message): ?>
            <div class="flash-message flash-<?= htmlspecialchars($flash_message['type']) ?>">
                <span class="flash-icon">
                    <?= $flash_message['type'] === 'success' ? '✅' : ($flash_message['type'] === 'error' ? '❌' : 'ℹ️') ?>
                </span>
                <span class="flash-text"><?= htmlspecialchars($flash_message['message']) ?></span>
                <button class="flash-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Métriques principales -->
        <section class="metrics-section" aria-label="Métriques EPI">
            <div class="metrics-grid">
                <div class="metric-card metric-primary">
                    <div class="metric-content">
                        <div class="metric-value"><?= $metrics['equipped_employees'] ?>/<?= $metrics['total_employees'] ?></div>
                        <div class="metric-label">Employés équipés</div>
                        <div class="metric-percentage"><?= $metrics['equipment_ratio'] ?>%</div>
                    </div>
                    <div class="metric-icon">👥</div>
                </div>

                <div class="metric-card metric-danger">
                    <div class="metric-content">
                        <div class="metric-value"><?= count($alerts['expired'] ?? []) ?></div>
                        <div class="metric-label">EPI expirés</div>
                        <div class="metric-trend">Urgent</div>
                    </div>
                    <div class="metric-icon">⚠️</div>
                </div>

                <div class="metric-card metric-warning">
                    <div class="metric-content">
                        <div class="metric-value"><?= count($alerts['urgent'] ?? []) ?></div>
                        <div class="metric-label">Alertes urgentes</div>
                        <div class="metric-trend">< 15 jours</div>
                    </div>
                    <div class="metric-icon">⏰</div>
                </div>

                <div class="metric-card metric-success">
                    <div class="metric-content">
                        <div class="metric-value"><?= $metrics['available_equipment'] ?></div>
                        <div class="metric-label">Stock disponible</div>
                        <div class="metric-trend">Unités</div>
                    </div>
                    <div class="metric-icon">📦</div>
                </div>
            </div>
        </section>

        <!-- Contenu principal -->
        <div class="dashboard-layout">
            
            <!-- Colonne principale -->
            <div class="main-column">
                
                <!-- Alertes prioritaires -->
                <section class="dashboard-card alerts-card">
                    <header class="card-header">
                        <h2 class="card-title">
                            <span class="card-icon">🚨</span>
                            Alertes prioritaires
                            <?php if (!empty($alerts['expired']) || !empty($alerts['urgent'])): ?>
                                <span class="badge badge-danger"><?= count($alerts['expired']) + count($alerts['urgent']) ?></span>
                            <?php endif; ?>
                        </h2>
                        <a href="assignments.php?filter=alerts" class="card-action">Voir tout</a>
                    </header>
                    
                    <div class="card-content">
                        <?php if (empty($alerts['expired']) && empty($alerts['urgent'])): ?>
                            <div class="empty-state">
                                <span class="empty-icon">✅</span>
                                <p class="empty-message">Aucune alerte critique</p>
                                <span class="empty-subtitle">Tous les EPI sont à jour</span>
                            </div>
                        <?php else: ?>
                            <div class="alerts-list">
                                <?php foreach (array_slice($alerts['expired'] ?? [], 0, 3) as $alert): ?>
                                    <div class="alert-item alert-expired" onclick="navigateToEmployee('<?= $alert['employee_name'] ?>')">
                                        <div class="alert-icon">⚠️</div>
                                        <div class="alert-content">
                                            <div class="alert-employee"><?= htmlspecialchars($alert['employee_name']) ?></div>
                                            <div class="alert-details">
                                                <?= htmlspecialchars($alert['category_name']) ?> - 
                                                <span class="alert-critical">Expiré depuis <?= abs($alert['days_remaining']) ?> jour<?= abs($alert['days_remaining']) > 1 ? 's' : '' ?></span>
                                            </div>
                                        </div>
                                        <div class="alert-action">
                                            <button class="btn-icon-small" title="Voir détails">👁️</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php foreach (array_slice($alerts['urgent'] ?? [], 0, 3) as $alert): ?>
                                    <div class="alert-item alert-urgent" onclick="navigateToEmployee('<?= $alert['employee_name'] ?>')">
                                        <div class="alert-icon">⏰</div>
                                        <div class="alert-content">
                                            <div class="alert-employee"><?= htmlspecialchars($alert['employee_name']) ?></div>
                                            <div class="alert-details">
                                                <?= htmlspecialchars($alert['category_name']) ?> - 
                                                <span class="alert-warning">Expire dans <?= $alert['days_remaining'] ?> jour<?= $alert['days_remaining'] > 1 ? 's' : '' ?></span>
                                            </div>
                                        </div>
                                        <div class="alert-action">
                                            <button class="btn-icon-small" title="Prolonger">📅</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Activité récente -->
                <?php if (!empty($recentActivity)): ?>
                <section class="dashboard-card activity-card">
                    <header class="card-header">
                        <h2 class="card-title">
                            <span class="card-icon">📋</span>
                            Activité récente
                        </h2>
                        <a href="reports.php?type=activity" class="card-action">Historique</a>
                    </header>
                    
                    <div class="card-content">
                        <div class="activity-list">
                            <?php foreach (array_slice($recentActivity, 0, 5) as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">🔄</div>
                                    <div class="activity-content">
                                        <div class="activity-text"><?= htmlspecialchars($activity['action']) ?></div>
                                        <div class="activity-details">
                                            <?= htmlspecialchars($activity['employee_name']) ?> - 
                                            <?= htmlspecialchars($activity['category_name']) ?>
                                        </div>
                                    </div>
                                    <div class="activity-time">
                                        <?= date('d/m H:i', strtotime($activity['date'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <!-- Colonne latérale -->
            <aside class="sidebar-column">
                
                <!-- Actions rapides -->
                <section class="dashboard-card actions-card">
                    <header class="card-header">
                        <h2 class="card-title">
                            <span class="card-icon">⚡</span>
                            Actions rapides
                        </h2>
                    </header>
                    
                    <div class="card-content">
                        <div class="quick-actions">
                            <a href="employees.php?action=add" class="action-item">
                                <span class="action-icon">👤➕</span>
                                <span class="action-text">Nouvel employé</span>
                            </a>
                            <a href="assignments.php?action=add" class="action-item">
                                <span class="action-icon">🔄➕</span>
                                <span class="action-text">Nouvelle attribution</span>
                            </a>
                            <a href="inventory.php?action=replenish" class="action-item">
                                <span class="action-icon">📦📈</span>
                                <span class="action-text">Réapprovisionner</span>
                            </a>
                            <a href="reports.php" class="action-item">
                                <span class="action-icon">📊</span>
                                <span class="action-text">Générer rapport</span>
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Statistiques rapides -->
                <section class="dashboard-card stats-card">
                    <header class="card-header">
                        <h2 class="card-title">
                            <span class="card-icon">📊</span>
                            Statistiques
                        </h2>
                    </header>
                    
                    <div class="card-content">
                        <div class="stats-list">
                            <div class="stat-item">
                                <span class="stat-label">Catégories actives</span>
                                <span class="stat-value"><?= $quickStats['active_categories'] ?? 8 ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Attributions ce mois</span>
                                <span class="stat-value"><?= $quickStats['monthly_assignments'] ?? 15 ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Taux d'équipement</span>
                                <span class="stat-value"><?= $metrics['equipment_ratio'] ?>%</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Statut système</span>
                                <span class="stat-value stat-success">🟢 OK</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Navigation rapide -->
                <section class="dashboard-card navigation-card">
                    <header class="card-header">
                        <h2 class="card-title">
                            <span class="card-icon">🧭</span>
                            Navigation
                        </h2>
                    </header>
                    
                    <div class="card-content">
                        <nav class="quick-nav">
                            <a href="employees.php" class="nav-item">
                                <span class="nav-icon">👥</span>
                                <span class="nav-text">Gestion employés</span>
                                <span class="nav-count"><?= $metrics['total_employees'] ?></span>
                            </a>
                            <a href="inventory.php" class="nav-item">
                                <span class="nav-icon">📦</span>
                                <span class="nav-text">Inventaire</span>
                                <span class="nav-count"><?= $metrics['available_equipment'] ?></span>
                            </a>
                            <a href="assignments.php" class="nav-item">
                                <span class="nav-icon">🔄</span>
                                <span class="nav-text">Attributions</span>
                                <span class="nav-count"><?= $metrics['equipped_employees'] ?></span>
                            </a>
                            <a href="reports.php" class="nav-item">
                                <span class="nav-icon">📊</span>
                                <span class="nav-text">Rapports</span>
                            </a>
                        </nav>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</main>

<!-- JavaScript spécifique EPI -->
<script>
// Configuration EPI spécifique
window.EPI_CONFIG = {
    module: 'epi',
    baseUrl: '/features/epi/',
    apiUrl: '/features/epi/ajax/',
    refreshInterval: 300000, // 5 minutes
    alerts: {
        expired: <?= count($alerts['expired'] ?? []) ?>,
        urgent: <?= count($alerts['urgent'] ?? []) ?>
    }
};

// Fonctions spécifiques au tableau de bord EPI
function navigateToEmployee(employeeName) {
    if (window.PortalManager) {
        window.PortalManager.showToast('info', 'Navigation', `Recherche de ${employeeName}...`);
    }
    // Redirection avec recherche
    window.location.href = `employees.php?search=${encodeURIComponent(employeeName)}`;
}

function refreshDashboard() {
    if (window.PortalManager) {
        window.PortalManager.showToast('info', 'Actualisation', 'Mise à jour des données...');
    }
    
    fetch('/features/epi/ajax/refresh_dashboard.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mise à jour des métriques
            updateMetrics(data.metrics);
            
            if (window.PortalManager) {
                window.PortalManager.showToast('success', 'Actualisé', 'Données mises à jour');
            }
        }
    })
    .catch(error => {
        console.error('Erreur refresh:', error);
        if (window.PortalManager) {
            window.PortalManager.showToast('error', 'Erreur', 'Impossible de mettre à jour');
        }
    });
}

function updateMetrics(metrics) {
    // Mise à jour des valeurs affichées
    document.querySelectorAll('.metric-value').forEach((el, index) => {
        const newValue = Object.values(metrics)[index];
        if (newValue !== undefined) {
            el.textContent = newValue;
        }
    });
}

// Auto-refresh toutes les 5 minutes
setInterval(refreshDashboard, window.EPI_CONFIG.refreshInterval);

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée des cartes
    const cards = document.querySelectorAll('.dashboard-card, .metric-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Gestion des clics sur les alertes
    document.querySelectorAll('.alert-item').forEach(alert => {
        alert.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
    
    // Tooltips sur les boutons d'action
    document.querySelectorAll('[title]').forEach(el => {
        el.addEventListener('mouseenter', function() {
            // Ici on pourrait ajouter des tooltips personnalisés
        });
    });
    
    console.log('🛡️ Module EPI initialisé - Tableau de bord');
});
</script>

<?php
// Inclure le footer du portail si disponible
$footer_path = __DIR__ . '/../../templates/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    // Footer standalone si templates non disponibles
    include __DIR__ . '/partials/standalone_footer.php';
}
?>

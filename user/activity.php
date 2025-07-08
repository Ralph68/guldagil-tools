<?php
/**
 * Titre: Page d'activité utilisateur
 * Chemin: /user/activity.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Configuration et sécurité
define('ROOT_PATH', dirname(__DIR__));

// Vérifier si le dossier existe, sinon le créer
if (!is_dir(dirname(__FILE__))) {
    mkdir(dirname(__FILE__), 0755, true);
}

// Chargement configuration
$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Configuration manquante</h1><p>Fichier requis : ' . basename($file) . '</p>');
    }
    require_once $file;
}

// Vérification authentification
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];

// Variables pour le template
$page_title = 'Mon Activité';
$page_subtitle = 'Historique et statistiques d\'utilisation';
$page_description = 'Activité utilisateur - Historique et statistiques';
$current_module = 'activity';
$module_css = true;
$user_authenticated = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '📊', 'text' => 'Mon Activité', 'url' => '/user/activity.php', 'active' => true]
];

// Simulation de données d'activité (à remplacer par vraies données BDD)
$activity_stats = [
    'total_sessions' => 156,
    'total_calculations' => 89,
    'total_adr_declarations' => 23,
    'total_quality_checks' => 45,
    'last_login' => '2024-07-09 14:32:15',
    'avg_session_duration' => '00:23:45',
    'favorite_module' => 'calculateur'
];

// Activité récente (simulation)
$recent_activities = [
    [
        'id' => 1,
        'type' => 'login',
        'icon' => '🔐',
        'title' => 'Connexion au portail',
        'description' => 'Accès depuis 192.168.1.100',
        'timestamp' => '2024-07-09 14:32:15',
        'status' => 'success'
    ],
    [
        'id' => 2,
        'type' => 'calculation',
        'icon' => '🚛',
        'title' => 'Calcul frais de port',
        'description' => 'Transport vers Lyon - 25kg - Résultat: 48.50€',
        'timestamp' => '2024-07-09 11:15:30',
        'status' => 'success'
    ],
    [
        'id' => 3,
        'type' => 'adr',
        'icon' => '⚠️',
        'title' => 'Déclaration ADR',
        'description' => 'Création expédition MD-2024-001',
        'timestamp' => '2024-07-08 16:45:22',
        'status' => 'success'
    ],
    [
        'id' => 4,
        'type' => 'profile',
        'icon' => '👤',
        'title' => 'Modification profil',
        'description' => 'Mise à jour de l\'adresse email',
        'timestamp' => '2024-07-08 09:20:10',
        'status' => 'success'
    ],
    [
        'id' => 5,
        'type' => 'quality',
        'icon' => '✅',
        'title' => 'Contrôle qualité',
        'description' => 'Validation équipement EQ-2024-045',
        'timestamp' => '2024-07-07 15:30:45',
        'status' => 'success'
    ]
];

// Statistiques par module
$module_stats = [
    'calculateur' => ['uses' => 89, 'time' => '12h 45m', 'percentage' => 45],
    'adr' => ['uses' => 23, 'time' => '5h 20m', 'percentage' => 20],
    'qualite' => ['uses' => 45, 'time' => '8h 15m', 'percentage' => 25],
    'admin' => ['uses' => 12, 'time' => '2h 30m', 'percentage' => 10]
];

// Inclure le header
include ROOT_PATH . '/templates/header.php';
?>

<div class="activity-container">
    <div class="activity-header">
        <h1 class="activity-title">
            <span class="title-icon">📊</span>
            Mon Activité
        </h1>
        <p class="activity-description">
            Suivez votre utilisation du portail et consultez vos statistiques personnelles
        </p>
    </div>

    <!-- Statistiques générales -->
    <div class="stats-overview">
        <div class="stats-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-value"><?= $activity_stats['total_sessions'] ?></div>
                <div class="stat-label">Sessions totales</div>
            </div>
        </div>
        
        <div class="stats-card">
            <div class="stat-icon">🚛</div>
            <div class="stat-content">
                <div class="stat-value"><?= $activity_stats['total_calculations'] ?></div>
                <div class="stat-label">Calculs effectués</div>
            </div>
        </div>
        
        <div class="stats-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <div class="stat-value"><?= $activity_stats['total_adr_declarations'] ?></div>
                <div class="stat-label">Déclarations ADR</div>
            </div>
        </div>
        
        <div class="stats-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?= $activity_stats['total_quality_checks'] ?></div>
                <div class="stat-label">Contrôles qualité</div>
            </div>
        </div>
    </div>

    <div class="activity-layout">
        <!-- Colonne principale -->
        <div class="activity-main">
            <!-- Activité récente -->
            <div class="activity-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">🕐</span>
                        Activité récente
                    </h2>
                    <div class="section-actions">
                        <button class="btn btn-secondary" onclick="exportActivity()">
                            <span class="btn-icon">📥</span>
                            Exporter
                        </button>
                        <button class="btn btn-secondary" onclick="filterActivity()">
                            <span class="btn-icon">🔍</span>
                            Filtrer
                        </button>
                    </div>
                </div>
                
                <div class="activity-timeline">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="timeline-item <?= $activity['status'] ?>">
                        <div class="timeline-icon">
                            <?= $activity['icon'] ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <div class="timeline-title"><?= htmlspecialchars($activity['title']) ?></div>
                                <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($activity['timestamp'])) ?></div>
                            </div>
                            <div class="timeline-description"><?= htmlspecialchars($activity['description']) ?></div>
                            <div class="timeline-meta">
                                <span class="activity-type"><?= ucfirst($activity['type']) ?></span>
                                <span class="activity-status status-<?= $activity['status'] ?>">
                                    <?= $activity['status'] === 'success' ? 'Réussi' : 'Échec' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="load-more">
                    <button class="btn btn-secondary" onclick="loadMoreActivity()">
                        <span class="btn-icon">⬇️</span>
                        Charger plus d'activités
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Sidebar statistiques -->
        <div class="activity-sidebar">
            <!-- Informations de session -->
            <div class="sidebar-card">
                <h3 class="card-title">
                    <span class="card-icon">🔐</span>
                    Session actuelle
                </h3>
                <div class="session-info">
                    <div class="info-item">
                        <span class="info-label">Connecté depuis</span>
                        <span class="info-value"><?= date('H:i', strtotime($activity_stats['last_login'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Durée moyenne</span>
                        <span class="info-value"><?= $activity_stats['avg_session_duration'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Module favori</span>
                        <span class="info-value"><?= ucfirst($activity_stats['favorite_module']) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques par module -->
            <div class="sidebar-card">
                <h3 class="card-title">
                    <span class="card-icon">📊</span>
                    Utilisation par module
                </h3>
                <div class="module-stats">
                    <?php foreach ($module_stats as $module => $stats): ?>
                    <div class="module-stat">
                        <div class="module-header">
                            <span class="module-name"><?= ucfirst($module) ?></span>
                            <span class="module-uses"><?= $stats['uses'] ?> fois</span>
                        </div>
                        <div class="module-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $stats['percentage'] ?>%"></div>
                            </div>
                            <span class="progress-text"><?= $stats['percentage'] ?>%</span>
                        </div>
                        <div class="module-time"><?= $stats['time'] ?> de temps total</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="sidebar-card">
                <h3 class="card-title">
                    <span class="card-icon">⚡</span>
                    Actions rapides
                </h3>
                <div class="quick-actions">
                    <a href="/calculateur/" class="quick-action">
                        <span class="action-icon">🚛</span>
                        <span class="action-text">Nouveau calcul</span>
                    </a>
                    <a href="/adr/declaration/create.php" class="quick-action">
                        <span class="action-icon">⚠️</span>
                        <span class="action-text">Déclaration ADR</span>
                    </a>
                    <a href="/user/settings.php" class="quick-action">
                        <span class="action-icon">⚙️</span>
                        <span class="action-text">Paramètres</span>
                    </a>
                    <a href="/help/" class="quick-action">
                        <span class="action-icon">❓</span>
                        <span class="action-text">Aide</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS intégré pour la page activité -->
<style>
    .activity-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: var(--spacing-xl) var(--spacing-md);
    }
    
    .activity-header {
        text-align: center;
        margin-bottom: var(--spacing-2xl);
        padding-bottom: var(--spacing-xl);
        border-bottom: 1px solid var(--gray-200);
    }
    
    .activity-title {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-md);
        font-size: 2rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 var(--spacing-md);
    }
    
    .title-icon {
        font-size: 2rem;
    }
    
    .activity-description {
        color: var(--gray-600);
        font-size: 1.125rem;
        margin: 0;
        max-width: 600px;
        margin: 0 auto;
    }
    
    /* Statistiques overview */
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--spacing-lg);
        margin-bottom: var(--spacing-2xl);
    }
    
    .stats-card {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        padding: var(--spacing-xl);
        display: flex;
        align-items: center;
        gap: var(--spacing-lg);
        box-shadow: var(--shadow-md);
        transition: var(--transition-normal);
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--gray-900);
        line-height: 1;
        margin-bottom: var(--spacing-xs);
    }
    
    .stat-label {
        color: var(--gray-600);
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    /* Layout principal */
    .activity-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: var(--spacing-2xl);
        align-items: start;
    }
    
    /* Section activité */
    .activity-section {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-xl);
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
    }
    
    .section-title {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
    }
    
    .section-icon {
        font-size: 1.25rem;
    }
    
    .section-actions {
        display: flex;
        gap: var(--spacing-sm);
    }
    
    /* Timeline d'activité */
    .activity-timeline {
        padding: var(--spacing-xl);
    }
    
    .timeline-item {
        display: flex;
        gap: var(--spacing-lg);
        margin-bottom: var(--spacing-xl);
        position: relative;
    }
    
    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 24px;
        top: 48px;
        bottom: -24px;
        width: 2px;
        background: var(--gray-200);
    }
    
    .timeline-icon {
        width: 48px;
        height: 48px;
        background: white;
        border: 3px solid var(--primary-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        z-index: 1;
        position: relative;
    }
    
    .timeline-content {
        flex: 1;
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
    }
    
    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: var(--spacing-sm);
    }
    
    .timeline-title {
        font-weight: 600;
        color: var(--gray-900);
        font-size: 1rem;
    }
    
    .timeline-time {
        color: var(--gray-500);
        font-size: 0.875rem;
        white-space: nowrap;
    }
    
    .timeline-description {
        color: var(--gray-700);
        font-size: 0.875rem;
        margin-bottom: var(--spacing-md);
        line-height: 1.5;
    }
    
    .timeline-meta {
        display: flex;
        gap: var(--spacing-md);
        align-items: center;
    }
    
    .activity-type {
        background: var(--gray-200);
        color: var(--gray-700);
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: capitalize;
    }
    
    .activity-status {
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .status-success {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #065f46;
    }
    
    .status-error {
        background: linear-gradient(135deg, #fef2f2, #fecaca);
        color: #7f1d1d;
    }
    
    .load-more {
        padding: var(--spacing-xl);
        text-align: center;
        border-top: 1px solid var(--gray-200);
    }
    
    /* Sidebar */
    .activity-sidebar {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-xl);
        position: sticky;
        top: var(--spacing-xl);
    }
    
    .sidebar-card {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        padding: var(--spacing-xl);
        box-shadow: var(--shadow-md);
    }
    
    .card-title {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0 0 var(--spacing-lg);
    }
    
    .card-icon {
        font-size: 1.25rem;
    }
    
    /* Informations de session */
    .session-info {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-sm) 0;
        border-bottom: 1px solid var(--gray-100);
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: var(--gray-600);
        font-size: 0.875rem;
    }
    
    .info-value {
        color: var(--gray-900);
        font-weight: 500;
        font-size: 0.875rem;
    }
    
    /* Statistiques par module */
    .module-stats {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-lg);
    }
    
    .module-stat {
        padding: var(--spacing-md);
        background: var(--gray-50);
        border-radius: var(--radius-md);
        border: 1px solid var(--gray-200);
    }
    
    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-sm);
    }
    
    .module-name {
        font-weight: 500;
        color: var(--gray-900);
        text-transform: capitalize;
    }
    
    .module-uses {
        color: var(--gray-600);
        font-size: 0.875rem;
    }
    
    .module-progress {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        margin-bottom: var(--spacing-xs);
    }
    
    .progress-bar {
        flex: 1;
        height: 8px;
        background: var(--gray-200);
        border-radius: 4px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    
    .progress-text {
        font-size: 0.75rem;
        color: var(--gray-600);
        font-weight: 500;
        min-width: 35px;
        text-align: right;
    }
    
    .module-time {
        color: var(--gray-500);
        font-size: 0.75rem;
    }
    
    /* Actions rapides */
    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .quick-action {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        padding: var(--spacing-md);
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        text-decoration: none;
        color: var(--gray-700);
        transition: var(--transition-fast);
    }
    
    .quick-action:hover {
        background: var(--gray-100);
        border-color: var(--primary-blue);
        color: var(--primary-blue);
        transform: translateX(2px);
    }
    
    .action-icon {
        font-size: 1.125rem;
        width: 20px;
        text-align: center;
    }
    
    .action-text {
        font-weight: 500;
    }
    
    /* Boutons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) var(--spacing-md);
        border: none;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: var(--transition-normal);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-lg);
    }
    
    .btn-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
        border: 1px solid var(--gray-300);
    }
    
    .btn-secondary:hover {
        background: var(--gray-200);
        border-color: var(--gray-400);
    }
    
    .btn-icon {
        font-size: 1rem;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .activity-layout {
            grid-template-columns: 1fr;
            gap: var(--spacing-xl);
        }
        
        .activity-sidebar {
            position: static;
            order: -1;
        }
        
        .stats-overview {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .stats-overview {
            grid-template-columns: 1fr;
        }
        
        .section-header {
            flex-direction: column;
            gap: var(--spacing-md);
            align-items: stretch;
        }
        
        .section-actions {
            justify-content: center;
        }
        
        .timeline-item {
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .timeline-item::after {
            display: none;
        }
        
        .timeline-icon {
            align-self: flex-start;
        }
        
        .timeline-header {
            flex-direction: column;
            gap: var(--spacing-xs);
            align-items: flex-start;
        }
    }
</style>

<!-- JavaScript pour la page activité -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'apparition des statistiques
    animateStats();
    
    // Animation des barres de progression
    animateProgressBars();
    
    // Animation d'apparition des éléments
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.timeline-item, .sidebar-card, .stats-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

function animateStats() {
    const statValues = document.querySelectorAll('.stat-value');
    
    statValues.forEach(stat => {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = finalValue / 50;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                stat.textContent = finalValue;
                clearInterval(timer);
            } else {
                stat.textContent = Math.floor(currentValue);
            }
        }, 30);
    });
}

function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-fill');
    
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
}

function exportActivity() {
    // Simulation d'export d'activité
    const activityData = {
        user: '<?= htmlspecialchars($current_user['username']) ?>',
        exportDate: new Date().toISOString(),
        stats: <?= json_encode($activity_stats) ?>,
        recentActivities: <?= json_encode($recent_activities) ?>,
        moduleStats: <?= json_encode($module_stats) ?>
    };
    
    const dataStr = JSON.stringify(activityData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `guldagil-activity-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
    // Notification de succès
    showNotification('Activité exportée avec succès', 'success');
}

function filterActivity() {
    // Simulation de filtrage d'activité
    const filterOptions = [
        'Toutes les activités',
        'Connexions uniquement',
        'Calculs uniquement',
        'Déclarations ADR',
        'Contrôles qualité',
        'Modifications profil'
    ];
    
    const filter = prompt('Choisissez un filtre:\n' + filterOptions.map((opt, i) => `${i}: ${opt}`).join('\n'));
    
    if (filter !== null && filter >= 0 && filter < filterOptions.length) {
        const filterName = filterOptions[filter];
        
        if (filter == 0) {
            // Afficher toutes les activités
            document.querySelectorAll('.timeline-item').forEach(item => {
                item.style.display = 'flex';
            });
        } else {
            // Filtrer par type
            const filterTypes = ['', 'login', 'calculation', 'adr', 'quality', 'profile'];
            const selectedType = filterTypes[filter];
            
            document.querySelectorAll('.timeline-item').forEach(item => {
                const activityType = item.querySelector('.activity-type').textContent.toLowerCase();
                if (activityType.includes(selectedType) || selectedType === '') {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        showNotification(`Filtre appliqué: ${filterName}`, 'info');
    }
}

function loadMoreActivity() {
    // Simulation de chargement d'activités supplémentaires
    const timeline = document.querySelector('.activity-timeline');
    const loadMoreBtn = document.querySelector('.load-more .btn');
    
    // Désactiver le bouton temporairement
    loadMoreBtn.disabled = true;
    loadMoreBtn.innerHTML = '<span class="btn-icon">⏳</span>Chargement...';
    
    // Simuler un délai de chargement
    setTimeout(() => {
        // Ajouter des activités fictives
        const newActivities = [
            {
                icon: '🔧',
                title: 'Maintenance système',
                description: 'Mise à jour des tarifs transporteurs',
                time: '2024-07-06 14:20:00',
                type: 'system',
                status: 'success'
            },
            {
                icon: '📊',
                title: 'Génération rapport',
                description: 'Rapport mensuel des expéditions',
                time: '2024-07-05 16:30:00',
                type: 'report',
                status: 'success'
            }
        ];
        
        newActivities.forEach(activity => {
            const timelineItem = document.createElement('div');
            timelineItem.className = 'timeline-item success';
            timelineItem.style.opacity = '0';
            timelineItem.style.transform = 'translateY(20px)';
            
            timelineItem.innerHTML = `
                <div class="timeline-icon">${activity.icon}</div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <div class="timeline-title">${activity.title}</div>
                        <div class="timeline-time">${new Date(activity.time).toLocaleDateString('fr-FR')} ${new Date(activity.time).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</div>
                    </div>
                    <div class="timeline-description">${activity.description}</div>
                    <div class="timeline-meta">
                        <span class="activity-type">${activity.type}</span>
                        <span class="activity-status status-${activity.status}">Réussi</span>
                    </div>
                </div>
            `;
            
            timeline.appendChild(timelineItem);
            
            // Animation d'apparition
            setTimeout(() => {
                timelineItem.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                timelineItem.style.opacity = '1';
                timelineItem.style.transform = 'translateY(0)';
            }, 100);
        });
        
        // Restaurer le bouton
        loadMoreBtn.disabled = false;
        loadMoreBtn.innerHTML = '<span class="btn-icon">⬇️</span>Charger plus d\'activités';
        
        showNotification('Nouvelles activités chargées', 'success');
        
    }, 1500);
}

function showNotification(message, type = 'info') {
    // Créer une notification temporaire
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span class="notification-message">${message}</span>
        </div>
    `;
    
    // Ajouter les styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Suppression automatique
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>

<?php
// Inclure le footer
include ROOT_PATH . '/templates/footer.php';
?>

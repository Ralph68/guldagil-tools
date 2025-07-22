<?php
/**
 * Titre: Dashboard Module Contrôle Qualité - Index Principal Restructuré
 * Chemin: /public/qualite/index.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// 🔧 CONFIGURATION & SÉCURITÉ
// =====================================

// Définir ROOT_PATH avant tout
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chargement configuration
$config_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Variables template obligatoires
$page_title = 'Contrôle Qualité';
$page_subtitle = 'Gestion des contrôles et conformité';
$page_description = 'Module professionnel de contrôle qualité des équipements';
$current_module = 'qualite';
$module_css = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '✅', 'text' => 'Contrôle Qualité', 'url' => '/qualite/', 'active' => true]
];

// =====================================
// 🔐 AUTHENTIFICATION SIMPLE
// =====================================

// Auth temporaire pour développement - À remplacer par AuthManager
$user_authenticated = true;
$current_user = [
    'id' => 1,
    'username' => 'TestUser',
    'role' => 'logistique',
    'name' => 'Contrôleur Qualité',
    'email' => 'qualite@guldagil.com'
];

// Vérification droits module qualité
$allowed_roles = ['admin', 'dev', 'logistique', 'resp_materiel'];
if (!in_array($current_user['role'], $allowed_roles)) {
    header('Location: /');
    exit;
}

// =====================================
// 📊 DONNÉES & STATISTIQUES
// =====================================

try {
    // Connexion BDD (simulation)
    $stats = [
        'controles_total' => 245,
        'controles_mois' => 47,
        'controles_semaine' => 12,
        'controles_aujourd_hui' => 3,
        'taux_conformite' => 94.7,
        'non_conformites_ouvertes' => 8,
        'equipements_types' => 12,
        'alertes_actives' => 2
    ];
    
    // Contrôles récents (simulation)
    $recent_controls = [
        [
            'id' => 'CQ-2025-0156',
            'type' => 'Adoucisseur',
            'model' => 'DUPLEX-25L',
            'agence' => 'AG001',
            'date' => '2025-07-23',
            'status' => 'completed',
            'conformite' => true
        ],
        [
            'id' => 'CQ-2025-0155',
            'type' => 'Pompe doseuse',
            'model' => 'DOSATRON-8L',
            'agence' => 'AG003',
            'date' => '2025-07-22',
            'status' => 'in_progress',
            'conformite' => null
        ],
        [
            'id' => 'CQ-2025-0154',
            'type' => 'Adoucisseur',
            'model' => 'COMPACT-15L',
            'agence' => 'AG002',
            'date' => '2025-07-22',
            'status' => 'validated',
            'conformite' => false
        ]
    ];
    
    // Alertes actives
    $active_alerts = [
        [
            'type' => 'non_conformite',
            'message' => 'Non-conformité bloquante sur CQ-2025-0154',
            'priority' => 'high',
            'date' => '2025-07-22'
        ],
        [
            'type' => 'controle_retard',
            'message' => '2 contrôles en retard de validation',
            'priority' => 'medium',
            'date' => '2025-07-21'
        ]
    ];
    
} catch (Exception $e) {
    error_log("Erreur module qualité: " . $e->getMessage());
    $stats = ['error' => true];
    $recent_controls = [];
    $active_alerts = [];
}

// =====================================
// 🎨 FONCTIONS UTILITAIRES
// =====================================

function getStatusBadge(string $status): string {
    $badges = [
        'draft' => '<span class="badge badge-gray">Brouillon</span>',
        'in_progress' => '<span class="badge badge-blue">En cours</span>',
        'completed' => '<span class="badge badge-orange">Terminé</span>',
        'validated' => '<span class="badge badge-green">Validé</span>',
        'sent' => '<span class="badge badge-purple">Envoyé</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-gray">Inconnu</span>';
}

function getConformiteBadge(?bool $conformite): string {
    if ($conformite === null) return '<span class="badge badge-gray">En attente</span>';
    return $conformite 
        ? '<span class="badge badge-success">✓ Conforme</span>'
        : '<span class="badge badge-danger">✗ Non conforme</span>';
}

function getAlertIcon(string $type): string {
    $icons = [
        'non_conformite' => '🚨',
        'controle_retard' => '⏰',
        'equipement_defaut' => '⚙️',
        'validation_pending' => '📋'
    ];
    return $icons[$type] ?? '⚠️';
}

// =====================================
// 📄 TEMPLATE HEADER
// =====================================
require_once ROOT_PATH . '/templates/header.php';
?>

<!-- ===================================== -->
<!-- 📊 MODULE CONTRÔLE QUALITÉ -->
<!-- ===================================== -->

<div class="qualite-module">
    
    <!-- Header Module -->
    <div class="module-header">
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">✅</div>
                <div class="module-info">
                    <h1>Contrôle Qualité</h1>
                    <p class="module-version">Version <?= defined('APP_VERSION') ? APP_VERSION : '0.5 beta' ?></p>
                </div>
            </div>
            <div class="module-actions">
                <button class="btn btn-primary" onclick="createNewControl()">
                    <span class="icon">➕</span>
                    Nouveau contrôle
                </button>
                <button class="btn btn-outline" onclick="showSearch()">
                    <span class="icon">🔍</span>
                    Rechercher
                </button>
            </div>
        </div>
    </div>

    <div class="main-content">
        
        <!-- Alertes actives -->
        <?php if (!empty($active_alerts)): ?>
        <div class="alerts-section">
            <?php foreach ($active_alerts as $alert): ?>
            <div class="alert alert-<?= $alert['priority'] ?>">
                <span class="alert-icon"><?= getAlertIcon($alert['type']) ?></span>
                <span class="alert-text"><?= htmlspecialchars($alert['message']) ?></span>
                <span class="alert-date"><?= $alert['date'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Statistiques principales -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['controles_aujourd_hui'] ?></div>
                        <div class="stat-label">Contrôles aujourd'hui</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['controles_semaine'] ?></div>
                        <div class="stat-label">Cette semaine</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['taux_conformite'] ?>%</div>
                        <div class="stat-label">Taux conformité</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🚨</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['non_conformites_ouvertes'] ?></div>
                        <div class="stat-label">Non-conformités ouvertes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions principales -->
        <div class="actions-section">
            <h2>Actions principales</h2>
            <div class="actions-grid">
                
                <div class="action-card" onclick="createNewControl()">
                    <div class="action-icon">🆕</div>
                    <div class="action-content">
                        <h3>Nouveau contrôle</h3>
                        <p>Créer un nouveau contrôle qualité pour un équipement</p>
                    </div>
                    <div class="action-arrow">→</div>
                </div>
                
                <div class="action-card" onclick="showControlsList()">
                    <div class="action-icon">📋</div>
                    <div class="action-content">
                        <h3>Consulter contrôles</h3>
                        <p>Voir l'historique et les contrôles en cours</p>
                    </div>
                    <div class="action-arrow">→</div>
                </div>
                
                <div class="action-card" onclick="showNonConformites()">
                    <div class="action-icon">🚨</div>
                    <div class="action-content">
                        <h3>Non-conformités</h3>
                        <p>Gérer les non-conformités et plans d'action</p>
                    </div>
                    <div class="action-arrow">→</div>
                </div>
                
                <div class="action-card" onclick="showDashboard()">
                    <div class="action-icon">📊</div>
                    <div class="action-content">
                        <h3>Tableau de bord</h3>
                        <p>Statistiques et analyses qualité avancées</p>
                    </div>
                    <div class="action-arrow">→</div>
                </div>
                
                <div class="action-card" onclick="showEquipmentConfig()">
                    <div class="action-icon">⚙️</div>
                    <div class="action-content">
                        <h3>Configuration</h3>
                        <p>Gérer les types d'équipements et modèles</p>
                    </div>
                    <div class="action-arrow">→</div>
                </div>
                
                <div class="action-card" onclick="showReports()">
                    <div class="action-icon">📄</div>
                    <div class="action-content">
                        <h3>Rapports</h3>
                        <p>Générer et consulter les rapports de contrôle</p>
                    </div>
                    <div class="action-arrow">→</div>
                </div>
                
            </div>
        </div>

        <!-- Contrôles récents -->
        <div class="recent-section">
            <div class="section-header">
                <h2>Contrôles récents</h2>
                <button class="btn btn-outline btn-sm" onclick="showControlsList()">Voir tout</button>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N° Contrôle</th>
                            <th>Type</th>
                            <th>Modèle</th>
                            <th>Agence</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Conformité</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_controls as $control): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($control['id']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($control['type']) ?></td>
                            <td><?= htmlspecialchars($control['model']) ?></td>
                            <td>
                                <span class="agency-badge"><?= htmlspecialchars($control['agence']) ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($control['date'])) ?></td>
                            <td><?= getStatusBadge($control['status']) ?></td>
                            <td><?= getConformiteBadge($control['conformite']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon" onclick="viewControl('<?= $control['id'] ?>')" title="Voir">👁️</button>
                                    <?php if ($control['status'] !== 'validated'): ?>
                                    <button class="btn-icon" onclick="editControl('<?= $control['id'] ?>')" title="Modifier">✏️</button>
                                    <?php endif; ?>
                                    <button class="btn-icon" onclick="generatePDF('<?= $control['id'] ?>')" title="PDF">📄</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ===================================== -->
<!-- 🔧 JAVASCRIPT MODULE -->
<!-- ===================================== -->

<script>
// Configuration module qualité
const QualiteConfig = {
    baseUrl: '/qualite/',
    apiUrl: '/qualite/api/',
    currentUser: <?= json_encode($current_user) ?>,
    stats: <?= json_encode($stats) ?>
};

// Actions principales
function createNewControl() {
    window.location.href = QualiteConfig.baseUrl + 'create.php';
}

function showControlsList() {
    window.location.href = QualiteConfig.baseUrl + 'list.php';
}

function showNonConformites() {
    window.location.href = QualiteConfig.baseUrl + 'non-conformites.php';
}

function showDashboard() {
    window.location.href = QualiteConfig.baseUrl + 'dashboard.php';
}

function showEquipmentConfig() {
    window.location.href = QualiteConfig.baseUrl + 'config.php';
}

function showReports() {
    window.location.href = QualiteConfig.baseUrl + 'reports.php';
}

function showSearch() {
    // Ouvrir modal de recherche
    console.log('Recherche avancée');
}

// Actions sur contrôles
function viewControl(controlId) {
    window.location.href = QualiteConfig.baseUrl + 'view.php?id=' + controlId;
}

function editControl(controlId) {
    window.location.href = QualiteConfig.baseUrl + 'edit.php?id=' + controlId;
}

function generatePDF(controlId) {
    window.open(QualiteConfig.baseUrl + 'pdf.php?id=' + controlId, '_blank');
}

// Mise à jour stats temps réel
function refreshStats() {
    fetch(QualiteConfig.apiUrl + 'stats.php')
        .then(response => response.json())
        .then(data => {
            console.log('Stats mises à jour:', data);
            // Mettre à jour l'interface
        })
        .catch(error => console.error('Erreur stats:', error));
}

// Auto-refresh toutes les 30 secondes
setInterval(refreshStats, 30000);

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔬 Module Contrôle Qualité initialisé');
});
</script>

<?php
// =====================================
// 📄 TEMPLATE FOOTER
// =====================================
require_once ROOT_PATH . '/templates/footer.php';
?>

<?php
/**
 * Titre: Dashboard Module Outillages - Version corrigée
 * Chemin: /public/outillages/dashboard.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// CONFIGURATION ET SÉCURITÉ
// =====================================
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Session et authentification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once ROOT_PATH . '/config/modules.php'; // Pour $modules

// Variables pour template
$page_title = 'Module Outillages';
$page_subtitle = 'Gestion des outils et équipements';
$page_description = 'Dashboard de gestion de l\'outillage - Inventaire, attributions et demandes';
$current_module = 'outillages';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🔧', 'text' => 'Module Outillages', 'url' => '/outillages/', 'active' => true]
];

// =====================================
// VÉRIFICATION AUTHENTIFICATION
// =====================================
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = $current_user['role'] ?? 'guest';

// Vérification accès module avec paramètres corrects
$module_data = $modules['outillages'] ?? ['status' => 'development', 'name' => 'Outillages'];
if (!canAccessModule('outillages', $module_data, $user_role)) {
    header('Location: ../../index.php?error=access_denied');
    exit;
}

// =====================================
// CONNEXION BASE DE DONNÉES
// =====================================
$db_connected = false;
$db = null;

try {
    if (function_exists('getDB')) {
        $db = getDB();
        $db_connected = true;
    } else {
        // Connexion directe si getDB() n'existe pas
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $db_connected = true;
    }
} catch (Exception $e) {
    error_log("Erreur connexion BDD Outillages: " . $e->getMessage());
}

// =====================================
// GESTION DES DONNÉES
// =====================================
$stats = [
    'total_outils' => 0,
    'outils_attribues' => 0,
    'demandes_attente' => 0,
    'maintenance_due' => 0
];

$demandesEnAttente = [];

if ($db_connected) {
    try {
        // Vérifier si les tables existent avant de les utiliser
        $tables_check = $db->query("SHOW TABLES LIKE 'outillage_%'")->fetchAll();
        
        if (count($tables_check) > 0) {
            // Tables existantes - récupérer les vraies données
            $stmt = $db->query("SELECT COUNT(*) as total FROM outillage_items");
            $stats['total_outils'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as attribues FROM outillage_attributions WHERE etat_attribution = 'active'");
            $stats['outils_attribues'] = $stmt->fetch(PDO::FETCH_ASSOC)['attribues'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as en_attente FROM outillage_demandes WHERE statut = 'en_attente'");
            $stats['demandes_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['en_attente'] ?? 0;
            
            // Demandes récentes
            $stmt = $db->query("SELECT d.*, t.designation, CONCAT(e.prenom, ' ', e.nom) as demandeur
                               FROM outillage_demandes d
                               LEFT JOIN outillage_templates t ON d.template_id = t.id
                               LEFT JOIN outillage_employees e ON d.employee_id = e.id
                               WHERE d.statut = 'en_attente'
                               ORDER BY d.created_at DESC LIMIT 5");
            $demandesEnAttente = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Tables non créées - données de démonstration
            $stats = [
                'total_outils' => 47,
                'outils_attribues' => 32,
                'demandes_attente' => 5,
                'maintenance_due' => 3
            ];
            
            $demandesEnAttente = [
                ['designation' => 'Perceuse sans fil', 'demandeur' => 'Martin Dupont', 'created_at' => date('Y-m-d H:i:s')],
                ['designation' => 'Clé à molette 24mm', 'demandeur' => 'Sophie Bernard', 'created_at' => date('Y-m-d H:i:s')],
                ['designation' => 'Multimètre digital', 'demandeur' => 'Pierre Martin', 'created_at' => date('Y-m-d H:i:s')]
            ];
        }
    } catch (Exception $e) {
        error_log("Erreur récupération données outillages: " . $e->getMessage());
        // Utiliser données de démonstration en cas d'erreur
        $stats = [
            'total_outils' => 47,
            'outils_attribues' => 32,
            'demandes_attente' => 5,
            'maintenance_due' => 3
        ];
    }
}

// Configuration des droits selon le rôle
$canManageInventory = in_array($user_role, ['admin', 'dev']);
$canValidateDemands = in_array($user_role, ['admin', 'dev']);
$canViewStats = in_array($user_role, ['admin', 'dev']);
$canManageEmployees = in_array($user_role, ['admin', 'dev']);

// =====================================
// CHARGEMENT DU TEMPLATE
// =====================================
$build_number = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Portail Guldagil</title>
    
    <!-- CSS principal OBLIGATOIRE -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <!-- CSS module -->
    <link rel="stylesheet" href="./assets/css/outillage.css?v=<?= $build_number ?>">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php 
    // Inclusion du header avec toutes les variables nécessaires
    include ROOT_PATH . '/templates/header.php'; 
    ?>
    
    <main class="main-container">
        <!-- En-tête du module -->
        <div class="module-header">
            <h1><i class="fas fa-tools"></i> Gestion de l'Outillage</h1>
            <p>Dashboard de gestion des outils et équipements</p>
            
            <?php if (!$db_connected): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Mode démonstration - Base de données non connectée
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistiques principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_outils']) ?></h3>
                    <p>Outils total</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['outils_attribues']) ?></h3>
                    <p>Outils attribués</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['demandes_attente']) ?></h3>
                    <p>Demandes en attente</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['maintenance_due']) ?></h3>
                    <p>Maintenance due</p>
                </div>
            </div>
        </div>

        <!-- Actions principales -->
        <div class="actions-grid">
            <?php if ($canManageInventory): ?>
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3>Ajouter un outil</h3>
                <p>Enregistrer un nouvel outil dans l'inventaire</p>
                <button class="btn btn-primary" onclick="window.location.href='./inventory.php?action=add'">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
            <?php endif; ?>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-list"></i>
                </div>
                <h3>Consulter l'inventaire</h3>
                <p>Voir tous les outils et leur statut</p>
                <button class="btn btn-secondary" onclick="window.location.href='./inventory.php'">
                    <i class="fas fa-search"></i> Consulter
                </button>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-hand-paper"></i>
                </div>
                <h3>Faire une demande</h3>
                <p>Demander l'attribution d'un outil</p>
                <button class="btn btn-info" onclick="window.location.href='./demandes.php?action=new'">
                    <i class="fas fa-paper-plane"></i> Demander
                </button>
            </div>
            
            <?php if ($canManageEmployees): ?>
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Gérer les employés</h3>
                <p>Gestion des profils et attributions</p>
                <button class="btn btn-warning" onclick="window.location.href='./employees.php'">
                    <i class="fas fa-user-cog"></i> Gérer
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Demandes récentes -->
        <?php if ($canValidateDemands && !empty($demandesEnAttente)): ?>
        <div class="section">
            <h2><i class="fas fa-bell"></i> Demandes en attente</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Outil demandé</th>
                            <th>Demandeur</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandesEnAttente as $demande): ?>
                        <tr>
                            <td><?= htmlspecialchars($demande['designation'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($demande['demandeur'] ?? 'N/A') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($demande['created_at'] ?? 'now')) ?></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="approuveDemande(<?= $demande['id'] ?? 0 ?>)">
                                    <i class="fas fa-check"></i> Approuver
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="rejetDemande(<?= $demande['id'] ?? 0 ?>)">
                                    <i class="fas fa-times"></i> Rejeter
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Graphiques -->
        <?php if ($canViewStats): ?>
        <div class="charts-section">
            <div class="chart-container">
                <h3>Répartition des outils</h3>
                <canvas id="outilsChart" width="400" height="200"></canvas>
            </div>
            <div class="chart-container">
                <h3>Demandes par mois</h3>
                <canvas id="demandesChart" width="400" height="200"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Configuration des graphiques
        <?php if ($canViewStats): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Graphique répartition outils
            const outilsCtx = document.getElementById('outilsChart').getContext('2d');
            new Chart(outilsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Attribués', 'Disponibles', 'En maintenance'],
                    datasets: [{
                        data: [<?= $stats['outils_attribues'] ?>, <?= $stats['total_outils'] - $stats['outils_attribues'] - $stats['maintenance_due'] ?>, <?= $stats['maintenance_due'] ?>],
                        backgroundColor: ['#4CAF50', '#2196F3', '#FF9800']
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

            // Graphique demandes
            const demandesCtx = document.getElementById('demandesChart').getContext('2d');
            new Chart(demandesCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Demandes',
                        data: [12, 19, 15, 25, 22, <?= $stats['demandes_attente'] ?>],
                        borderColor: '#667eea',
                        tension: 0.1
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
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                });
            }
        }

        function rejetDemande(id) {
            if (confirm('Rejeter cette demande ?')) {
                fetch('./api/demandes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'reject', id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                });
            }
        }

        // Message de développement
        console.log('🔧 Module Outillages - Version corrigée v0.5 beta');
        console.log('📊 Stats:', <?= json_encode($stats) ?>);
        console.log('🔗 Connexion DB:', <?= $db_connected ? 'true' : 'false' ?>);
    </script>
</body>
</html>

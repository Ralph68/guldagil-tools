<?php
/**
 * Titre: Module Matériel - Rapports et statistiques
 * Chemin: /public/materiel/reports/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once dirname(__DIR__) . '/classes/MaterielManager.php';

// Variables pour template
$page_title = 'Rapports Matériel';
$page_subtitle = 'Statistiques et analyses d\'inventaire';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// Vérification authentification
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = $current_user['role'] ?? 'guest';

// Permissions pour rapports
$can_view_reports = in_array($user_role, ['admin', 'dev', 'logistique']);
if (!$can_view_reports) {
    header('Location: ../index.php?error=access_denied');
    exit;
}

// Connexion BDD et Manager matériel - CORRECTION: avec paramètre $db
try {
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $materielManager = new MaterielManager($db); // CORRECTION: paramètre ajouté
} catch (Exception $e) {
    error_log("Erreur BDD reports matériel: " . $e->getMessage());
    die("Erreur de connexion à la base de données");
}

// Paramètres de filtrage
$period = $_GET['period'] ?? 'month';
$agence_filter = $_GET['agence'] ?? '';
$type_filter = $_GET['type'] ?? '';

// Gestion export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    switch ($export_type) {
        case 'inventory_csv':
            exportInventoryCSV($materielManager, $agence_filter);
            break;
        case 'requests_csv':
            exportRequestsCSV($materielManager, $period);
            break;
        case 'stats_pdf':
            exportStatsPDF($materielManager);
            break;
    }
    exit;
}

// Récupération des données pour les rapports
$stats = $materielManager->getStatistiquesGenerales();
$statsByCategory = $materielManager->getStatistiquesByCategorie();
$statsByAgence = $materielManager->getStatistiquesByAgence();
$requestsStats = $materielManager->getDemandesStatistiques($period);
$maintenanceStats = $materielManager->getMaintenanceStatistiques();
$costAnalysis = $materielManager->getCostAnalysis($period);

// Données pour les graphiques
$categoryData = [];
$agenceData = [];
$requestsTrend = [];

foreach ($statsByCategory as $cat) {
    $categoryData[] = [
        'label' => $cat['nom'],
        'value' => $cat['total_items'],
        'color' => $cat['couleur'] ?? '#6B7280'
    ];
}

foreach ($statsByAgence as $agence) {
    $agenceData[] = [
        'label' => $agence['nom'],
        'value' => $agence['total_items'],
        'attribues' => $agence['items_attribues']
    ];
}

// Fonctions d'export
function exportInventoryCSV($manager, $agence_filter) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventaire_materiel_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Numéro', 'Désignation', 'Marque', 'Modèle', 'Catégorie', 'Agence', 'État', 'Statut']);
    
    // Récupération des données d'inventaire
    // TODO: Implémenter getInventoryData() dans MaterielManager
    fclose($output);
}

function exportRequestsCSV($manager, $period) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="demandes_materiel_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Employé', 'Matériel', 'Type', 'Quantité', 'Statut', 'Urgence']);
    
    // Récupération des données de demandes
    // TODO: Implémenter getRequestsData() dans MaterielManager
    fclose($output);
}

function exportStatsPDF($manager) {
    // TODO: Implémenter génération PDF avec TCPDF ou similaire
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="statistiques_materiel_' . date('Y-m-d') . '.pdf"');
}

// Headers de base
$template_header = ROOT_PATH . '/templates/header.php';
$template_footer = ROOT_PATH . '/templates/footer.php';

if (file_exists($template_header)) {
    include $template_header;
}
?>

<!-- RAPPORT MATÉRIEL -->
<div class="container-fluid py-4">
    <!-- En-tête des rapports -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1">📊 Rapports Matériel</h1>
                            <p class="text-muted mb-0">Statistiques et analyses d'inventaire</p>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                📥 Exporter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?export=inventory_csv">Inventaire CSV</a></li>
                                <li><a class="dropdown-item" href="?export=requests_csv">Demandes CSV</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?export=stats_pdf">Statistiques PDF</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Période</label>
                            <select name="period" class="form-select">
                                <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Cette semaine</option>
                                <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Ce mois</option>
                                <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Cette année</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Agence</label>
                            <select name="agence" class="form-select">
                                <option value="">Toutes les agences</option>
                                <?php foreach ($materielManager->getAgences() as $agence): ?>
                                    <option value="<?= $agence['id'] ?>" <?= $agence_filter == $agence['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($agence['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="">Tous les types</option>
                                <option value="outillage_manuel" <?= $type_filter === 'outillage_manuel' ? 'selected' : '' ?>>Outillage manuel</option>
                                <option value="materiel" <?= $type_filter === 'materiel' ? 'selected' : '' ?>>Matériel</option>
                                <option value="electroportatif" <?= $type_filter === 'electroportatif' ? 'selected' : '' ?>>Électroportatif</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-primary mb-2">📦</div>
                    <h5 class="card-title">Total Matériel</h5>
                    <h2 class="text-primary mb-0"><?= number_format($stats['total_materiel']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-success mb-2">✅</div>
                    <h5 class="card-title">Attribué</h5>
                    <h2 class="text-success mb-0"><?= number_format($stats['materiel_attribue']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-warning mb-2">⏳</div>
                    <h5 class="card-title">Demandes en attente</h5>
                    <h2 class="text-warning mb-0"><?= number_format($stats['demandes_attente']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-danger mb-2">🔧</div>
                    <h5 class="card-title">Maintenance due</h5>
                    <h2 class="text-danger mb-0"><?= number_format($stats['maintenance_due']) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📊 Répartition par catégorie</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🏢 Répartition par agence</h5>
                </div>
                <div class="card-body">
                    <canvas id="agenceChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableaux détaillés -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📋 Statistiques par catégorie</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th class="text-end">Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statsByCategory as $cat): ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: <?= $cat['couleur'] ?? '#6B7280' ?>">
                                            <?= htmlspecialchars($cat['nom']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?= number_format($cat['total_items']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts pour graphiques -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
// Données pour les graphiques
const categoryData = <?= json_encode($categoryData) ?>;
const agenceData = <?= json_encode($agenceData) ?>;

// Graphique par catégorie
if (document.getElementById('categoryChart')) {
    const ctx1 = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(item => item.label),
            datasets: [{
                data: categoryData.map(item => item.value),
                backgroundColor: categoryData.map(item => item.color),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Graphique par agence
if (document.getElementById('agenceChart')) {
    const ctx2 = document.getElementById('agenceChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: agenceData.map(item => item.label),
            datasets: [
                {
                    label: 'Total',
                    data: agenceData.map(item => item.value),
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                },
                {
                    label: 'Attribués',
                    data: agenceData.map(item => item.attribues || 0),
                    backgroundColor: 'rgba(34, 197, 94, 0.5)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

<?php
if (file_exists($template_footer)) {
    include $template_footer;
}
?>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🏢 Statistiques par agence</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Agence</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Attribués</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statsByAgence as $agence): ?>
                                <tr>
                                    <td><?= htmlspecialchars($agence['nom']) ?></td>
                                    <td class="text-end"><?= number_format($agence['total_items']) ?></td>
                                    <td class="text-end"><?= number_format($agence['items_attribues']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

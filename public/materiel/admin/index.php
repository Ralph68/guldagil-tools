<?php
/**
 * Titre: Module Matériel - Administration
 * Chemin: /public/materiel/admin/index.php
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
$page_title = 'Administration Matériel';
$page_subtitle = 'Configuration et gestion avancée';
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

// Permissions pour administration matériel - uniquement admin et dev
$can_admin_materiel = in_array($user_role, ['admin', 'dev']);
if (!$can_admin_materiel) {
    header('Location: ../index.php?error=access_denied');
    exit;
}

// Manager matériel
$materielManager = new MaterielManager();

// Gestion des actions administratives
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

// Traitement POST pour actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['action']) {
            case 'create_category':
                $success = $materielManager->createCategory($_POST);
                $message = $success ? 'Catégorie créée avec succès' : 'Erreur lors de la création';
                break;
                
            case 'update_category':
                $success = $materielManager->updateCategory($_POST['id'], $_POST);
                $message = $success ? 'Catégorie mise à jour avec succès' : 'Erreur lors de la mise à jour';
                break;
                
            case 'delete_category':
                $success = $materielManager->deleteCategory($_POST['id']);
                $message = $success ? 'Catégorie supprimée avec succès' : 'Erreur lors de la suppression';
                break;
                
            case 'create_template':
                $success = $materielManager->createTemplate($_POST);
                $message = $success ? 'Modèle créé avec succès' : 'Erreur lors de la création';
                break;
                
            case 'update_template':
                $success = $materielManager->updateTemplate($_POST['id'], $_POST);
                $message = $success ? 'Modèle mis à jour avec succès' : 'Erreur lors de la mise à jour';
                break;
                
            case 'delete_template':
                $success = $materielManager->deleteTemplate($_POST['id']);
                $message = $success ? 'Modèle supprimé avec succès' : 'Erreur lors de la suppression';
                break;
                
            case 'create_agence':
                $success = $materielManager->createAgence($_POST);
                $message = $success ? 'Agence créée avec succès' : 'Erreur lors de la création';
                break;
                
            case 'update_agence':
                $success = $materielManager->updateAgence($_POST['id'], $_POST);
                $message = $success ? 'Agence mise à jour avec succès' : 'Erreur lors de la mise à jour';
                break;
                
            case 'import_data':
                $result = $materielManager->importData($_FILES['import_file']);
                $message = $result['success'] ? 'Import réalisé avec succès' : 'Erreur lors de l\'import';
                break;
                
            case 'purge_old_data':
                $result = $materielManager->purgeOldData($_POST['older_than_days']);
                $message = $result ? 'Données anciennes supprimées' : 'Erreur lors de la suppression';
                break;
        }
    } catch (Exception $e) {
        $error = 'Erreur : ' . $e->getMessage();
    }
}

// Récupération des données pour l'admin
$categories = $materielManager->getCategories();
$templates = $materielManager->getTemplates();
$agences = $materielManager->getAgences();
$stats = $materielManager->getAdminStatistics();

// Statistiques système
$systemStats = [
    'total_items' => $stats['total_items'] ?? 0,
    'total_categories' => count($categories),
    'total_templates' => count($templates),
    'total_agences' => count($agences),
    'pending_requests' => $stats['pending_requests'] ?? 0,
    'maintenance_items' => $stats['maintenance_items'] ?? 0
];

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/'],
    ['icon' => '🔧', 'text' => 'Matériel', 'url' => '../index.php'],
    ['icon' => '⚙️', 'text' => 'Administration', 'url' => '', 'active' => true]
];

include ROOT_PATH . '/templates/header.php';
?>

<main class="admin-materiel">
    <div class="container-fluid">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques système -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistiques Système</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 mb-0"><?= $systemStats['total_items'] ?></div>
                                    <small>Équipements</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 mb-0"><?= $systemStats['total_categories'] ?></div>
                                    <small>Catégories</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 mb-0"><?= $systemStats['total_templates'] ?></div>
                                    <small>Modèles</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 mb-0"><?= $systemStats['total_agences'] ?></div>
                                    <small>Agences</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 mb-0 text-warning"><?= $systemStats['pending_requests'] ?></div>
                                    <small>Demandes en attente</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 mb-0 text-danger"><?= $systemStats['maintenance_items'] ?></div>
                                    <small>En maintenance</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets d'administration -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="adminTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button">
                                    <i class="fas fa-tags me-1"></i>Catégories
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button">
                                    <i class="fas fa-cube me-1"></i>Modèles
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="agences-tab" data-bs-toggle="tab" data-bs-target="#agences" type="button">
                                    <i class="fas fa-building me-1"></i>Agences
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button">
                                    <i class="fas fa-tools me-1"></i>Outils
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="adminTabsContent">
                            <!-- Gestion des Catégories -->
                            <div class="tab-pane fade show active" id="categories" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Gestion des Catégories</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                        <i class="fas fa-plus me-1"></i>Nouvelle catégorie
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nom</th>
                                                <th>Description</th>
                                                <th>Couleur</th>
                                                <th>Nb d'éléments</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><?= $category['id'] ?></td>
                                                <td><?= htmlspecialchars($category['nom']) ?></td>
                                                <td><?= htmlspecialchars($category['description'] ?? '') ?></td>
                                                <td>
                                                    <span class="badge" style="background-color: <?= $category['couleur'] ?? '#007bff' ?>">
                                                        <?= $category['couleur'] ?? '#007bff' ?>
                                                    </span>
                                                </td>
                                                <td><?= $category['total_items'] ?? 0 ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?= $category['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $category['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Gestion des Modèles -->
                            <div class="tab-pane fade" id="templates" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Gestion des Modèles d'Équipements</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
                                        <i class="fas fa-plus me-1"></i>Nouveau modèle
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nom</th>
                                                <th>Catégorie</th>
                                                <th>Marque</th>
                                                <th>Référence</th>
                                                <th>Nb d'exemplaires</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($templates as $template): ?>
                                            <tr>
                                                <td><?= $template['id'] ?></td>
                                                <td><?= htmlspecialchars($template['nom']) ?></td>
                                                <td><?= htmlspecialchars($template['categorie_nom'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($template['marque'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($template['reference'] ?? '') ?></td>
                                                <td><?= $template['total_items'] ?? 0 ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editTemplate(<?= $template['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(<?= $template['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Gestion des Agences -->
                            <div class="tab-pane fade" id="agences" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Gestion des Agences</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agenceModal">
                                        <i class="fas fa-plus me-1"></i>Nouvelle agence
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nom</th>
                                                <th>Code</th>
                                                <th>Ville</th>
                                                <th>Responsable</th>
                                                <th>Nb d'équipements</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($agences as $agence): ?>
                                            <tr>
                                                <td><?= $agence['id'] ?></td>
                                                <td><?= htmlspecialchars($agence['nom']) ?></td>
                                                <td><?= htmlspecialchars($agence['code'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($agence['ville'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($agence['responsable'] ?? '') ?></td>
                                                <td><?= $agence['total_items'] ?? 0 ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editAgence(<?= $agence['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Outils d'administration -->
                            <div class="tab-pane fade" id="tools" role="tabpanel">
                                <h5>Outils d'Administration</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6><i class="fas fa-upload me-1"></i>Import de Données</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="post" enctype="multipart/form-data">
                                                    <input type="hidden" name="action" value="import_data">
                                                    <div class="mb-3">
                                                        <label class="form-label">Fichier CSV/Excel</label>
                                                        <input type="file" class="form-control" name="import_file" accept=".csv,.xlsx,.xls" required>
                                                        <div class="form-text">Formats supportés : CSV, Excel</div>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-upload me-1"></i>Importer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6><i class="fas fa-broom me-1"></i>Nettoyage des Données</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="post">
                                                    <input type="hidden" name="action" value="purge_old_data">
                                                    <div class="mb-3">
                                                        <label class="form-label">Supprimer les données de plus de</label>
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" name="older_than_days" value="365" min="30">
                                                            <span class="input-group-text">jours</span>
                                                        </div>
                                                    </div>
                                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ces données ?')">
                                                        <i class="fas fa-broom me-1"></i>Nettoyer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6><i class="fas fa-download me-1"></i>Exports</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="btn-group" role="group">
                                                    <a href="../reports/index.php?export=inventory_csv" class="btn btn-outline-primary">
                                                        <i class="fas fa-file-csv me-1"></i>Export Inventaire CSV
                                                    </a>
                                                    <a href="../reports/index.php?export=stats_pdf" class="btn btn-outline-primary">
                                                        <i class="fas fa-file-pdf me-1"></i>Rapport Statistiques PDF
                                                    </a>
                                                    <button class="btn btn-outline-primary" onclick="generateQRCodes()">
                                                        <i class="fas fa-qrcode me-1"></i>Générer QR Codes
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modals -->
<!-- Modal Catégorie -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_category">
                    <div class="mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Couleur</label>
                        <input type="color" class="form-control" name="couleur" value="#007bff">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
function editCategory(id) {
    // Implémenter l'édition de catégorie
    alert('Fonctionnalité d\'édition à implémenter');
}

function deleteCategory(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function editTemplate(id) {
    alert('Fonctionnalité d\'édition à implémenter');
}

function deleteTemplate(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_template">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function editAgence(id) {
    alert('Fonctionnalité d\'édition à implémenter');
}

function generateQRCodes() {
    if (confirm('Générer les QR codes pour tous les équipements ?')) {
        window.open('../tools/qr-generator.php', '_blank');
    }
}
</script>

<?php include ROOT_PATH . '/templates/footer.php'; ?>

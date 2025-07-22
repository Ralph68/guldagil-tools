<?php
/**
 * Titre: Module Matériel - Liste des demandes
 * Chemin: /public/materiel/requests/index.php
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
$page_title = 'Demandes Matériel';
$page_subtitle = 'Gestion des demandes d\'équipement';
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

// Connexion BDD et Manager matériel
try {
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $materielManager = new MaterielManager($db);
} catch (Exception $e) {
    error_log("Erreur BDD liste demandes: " . $e->getMessage());
    die("Erreur de connexion à la base de données");
}

// Paramètres de filtrage
$status_filter = $_GET['status'] ?? '';
$employee_filter = $_GET['employee'] ?? '';
$urgence_filter = $_GET['urgence'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

// Récupération des demandes
try {
    $where_conditions = [];
    $params = [];
    
    if ($status_filter) {
        $where_conditions[] = "md.statut = ?";
        $params[] = $status_filter;
    }
    
    if ($employee_filter) {
        $where_conditions[] = "md.employee_id = ?";
        $params[] = $employee_filter;
    }
    
    if ($urgence_filter) {
        $where_conditions[] = "md.urgence = ?";
        $params[] = $urgence_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Requête pour les demandes avec pagination
    $sql = "
        SELECT 
            md.*,
            me.nom as employee_nom,
            me.prenom as employee_prenom,
            mt.designation,
            mt.marque,
            mt.modele,
            mc.nom as categorie_nom
        FROM materiel_demandes md
        LEFT JOIN materiel_employees me ON md.employee_id = me.id
        LEFT JOIN materiel_templates mt ON md.template_id = mt.id
        LEFT JOIN materiel_categories mc ON mt.categorie_id = mc.id
        $where_clause
        ORDER BY 
            CASE md.urgence 
                WHEN 'critique' THEN 1 
                WHEN 'urgente' THEN 2 
                WHEN 'normale' THEN 3 
            END,
            md.created_at DESC
        LIMIT " . (($page - 1) * $per_page) . ", $per_page
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total pour la pagination
    $count_sql = "
        SELECT COUNT(*) 
        FROM materiel_demandes md
        LEFT JOIN materiel_employees me ON md.employee_id = me.id
        LEFT JOIN materiel_templates mt ON md.template_id = mt.id
        $where_clause
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_requests = $count_stmt->fetchColumn();
    
    $total_pages = ceil($total_requests / $per_page);
    
} catch (Exception $e) {
    error_log("Erreur récupération demandes: " . $e->getMessage());
    $demandes = [];
    $total_requests = 0;
    $total_pages = 1;
}

// Récupération des données pour les filtres
$employees = $materielManager->getEmployees();

// Traitement des actions (validation, rejet, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $demande_id = (int)($_POST['demande_id'] ?? 0);
    
    switch ($action) {
        case 'validate':
            if (in_array($user_role, ['admin', 'dev', 'logistique'])) {
                try {
                    $stmt = $db->prepare("
                        UPDATE materiel_demandes 
                        SET statut = 'validee', validee_par = ?, date_validation = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$current_user['id'] ?? 0, $demande_id]);
                    $_SESSION['materiel_success'] = "Demande validée avec succès";
                } catch (Exception $e) {
                    $_SESSION['materiel_error'] = "Erreur lors de la validation";
                }
            }
            break;
            
        case 'reject':
            if (in_array($user_role, ['admin', 'dev', 'logistique'])) {
                $raison = trim($_POST['raison_rejet'] ?? '');
                try {
                    $stmt = $db->prepare("
                        UPDATE materiel_demandes 
                        SET statut = 'rejetee', raison_rejet = ?, validee_par = ?, date_validation = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$raison, $current_user['id'] ?? 0, $demande_id]);
                    $_SESSION['materiel_success'] = "Demande rejetée";
                } catch (Exception $e) {
                    $_SESSION['materiel_error'] = "Erreur lors du rejet";
                }
            }
            break;
    }
    
    // Redirection pour éviter double soumission
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Messages de session
$success_message = $_SESSION['materiel_success'] ?? '';
$error_message = $_SESSION['materiel_error'] ?? '';
unset($_SESSION['materiel_success'], $_SESSION['materiel_error']);

// Headers de base
$template_header = ROOT_PATH . '/templates/header.php';
$template_footer = ROOT_PATH . '/templates/footer.php';

if (file_exists($template_header)) {
    include $template_header;
}
?>

<!-- LISTE DES DEMANDES MATÉRIEL -->
<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?= $status_filter === 'en_attente' ? 'selected' : '' ?>>⏳ En attente</option>
                                <option value="validee" <?= $status_filter === 'validee' ? 'selected' : '' ?>>✅ Validée</option>
                                <option value="rejetee" <?= $status_filter === 'rejetee' ? 'selected' : '' ?>>❌ Rejetée</option>
                                <option value="en_cours" <?= $status_filter === 'en_cours' ? 'selected' : '' ?>>🔄 En cours</option>
                                <option value="traitee" <?= $status_filter === 'traitee' ? 'selected' : '' ?>>✅ Traitée</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employé</label>
                            <select name="employee" class="form-select">
                                <option value="">Tous les employés</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['id'] ?>" <?= $employee_filter == $employee['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($employee['nom'] . ' ' . $employee['prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Urgence</label>
                            <select name="urgence" class="form-select">
                                <option value="">Toutes urgences</option>
                                <option value="normale" <?= $urgence_filter === 'normale' ? 'selected' : '' ?>>🟢 Normale</option>
                                <option value="urgente" <?= $urgence_filter === 'urgente' ? 'selected' : '' ?>>🟠 Urgente</option>
                                <option value="critique" <?= $urgence_filter === 'critique' ? 'selected' : '' ?>>🔴 Critique</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Filtrer</button>
                                <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-warning mb-2">⏳</div>
                    <h6>En attente</h6>
                    <h4 class="text-warning mb-0">
                        <?= count(array_filter($demandes, fn($d) => $d['statut'] === 'en_attente')) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-success mb-2">✅</div>
                    <h6>Validées</h6>
                    <h4 class="text-success mb-0">
                        <?= count(array_filter($demandes, fn($d) => $d['statut'] === 'validee')) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-info mb-2">🔄</div>
                    <h6>En cours</h6>
                    <h4 class="text-info mb-0">
                        <?= count(array_filter($demandes, fn($d) => $d['statut'] === 'en_cours')) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-danger mb-2">🔴</div>
                    <h6>Critiques</h6>
                    <h4 class="text-danger mb-0">
                        <?= count(array_filter($demandes, fn($d) => $d['urgence'] === 'critique')) ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des demandes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Demandes (<?= number_format($total_requests) ?> total<?= $total_requests > 1 ? 's' : '' ?>)
                    </h5>
                    <div class="text-muted">
                        Page <?= $page ?> sur <?= $total_pages ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($demandes)): ?>
                    <div class="text-center py-5">
                        <div class="display-1 text-muted mb-3">📭</div>
                        <h5 class="text-muted">Aucune demande trouvée</h5>
                        <p class="text-muted">
                            <?php if ($status_filter || $employee_filter || $urgence_filter): ?>
                                Essayez de modifier vos filtres ou 
                                <a href="index.php" class="text-decoration-none">voir toutes les demandes</a>
                            <?php else: ?>
                                <a href="create.php" class="btn btn-primary">Créer la première demande</a>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Demandeur</th>
                                    <th>Matériel</th>
                                    <th>Type</th>
                                    <th>Qté</th>
                                    <th>Urgence</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($demandes as $demande): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($demande['created_at'])) ?><br>
                                            <?= date('H:i', strtotime($demande['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($demande['employee_nom'] . ' ' . $demande['employee_prenom']) ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($demande['designation']) ?></strong>
                                            <?php if ($demande['marque'] || $demande['modele']): ?>
                                                <br><small class="text-muted">
                                                    <?= htmlspecialchars(trim($demande['marque'] . ' ' . $demande['modele'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $type_icons = [
                                            'nouveau' => '🆕',
                                            'remplacement' => '🔄',
                                            'reparation' => '🔧',
                                            'formation' => '📚'
                                        ];
                                        echo $type_icons[$demande['type_demande']] ?? '❓';
                                        echo ' ' . ucfirst($demande['type_demande']);
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $demande['quantite_demandee'] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $urgence_classes = [
                                            'normale' => 'bg-success',
                                            'urgente' => 'bg-warning',
                                            'critique' => 'bg-danger'
                                        ];
                                        $urgence_icons = [
                                            'normale' => '🟢',
                                            'urgente' => '🟠',
                                            'critique' => '🔴'
                                        ];
                                        $urgence_class = $urgence_classes[$demande['urgence']] ?? 'bg-secondary';
                                        $urgence_icon = $urgence_icons[$demande['urgence']] ?? '⚪';
                                        ?>
                                        <span class="badge <?= $urgence_class ?>">
                                            <?= $urgence_icon ?> <?= ucfirst($demande['urgence']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statut_classes = [
                                            'en_attente' => 'bg-warning',
                                            'validee' => 'bg-success',
                                            'rejetee' => 'bg-danger',
                                            'en_cours' => 'bg-info',
                                            'traitee' => 'bg-primary'
                                        ];
                                        $statut_icons = [
                                            'en_attente' => '⏳',
                                            'validee' => '✅',
                                            'rejetee' => '❌',
                                            'en_cours' => '🔄',
                                            'traitee' => '✅'
                                        ];
                                        $statut_class = $statut_classes[$demande['statut']] ?? 'bg-secondary';
                                        $statut_icon = $statut_icons[$demande['statut']] ?? '❓';
                                        ?>
                                        <span class="badge <?= $statut_class ?>">
                                            <?= $statut_icon ?> <?= ucfirst(str_replace('_', ' ', $demande['statut'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group-sm">
                                            <!-- Bouton détails -->
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#detailModal<?= $demande['id'] ?>">
                                                👁️
                                            </button>
                                            
                                            <!-- Actions admin -->
                                            <?php if (in_array($user_role, ['admin', 'dev', 'logistique']) && $demande['statut'] === 'en_attente'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="demande_id" value="<?= $demande['id'] ?>">
                                                <input type="hidden" name="action" value="validate">
                                                <button type="submit" class="btn btn-outline-success btn-sm" 
                                                        onclick="return confirm('Valider cette demande ?')">
                                                    ✅
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#rejectModal<?= $demande['id'] ?>">
                                                ❌
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?= $status_filter ? '&status=' . $status_filter : '' ?><?= $employee_filter ? '&employee=' . $employee_filter : '' ?><?= $urgence_filter ? '&urgence=' . $urgence_filter : '' ?>">‹‹</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $employee_filter ? '&employee=' . $employee_filter : '' ?><?= $urgence_filter ? '&urgence=' . $urgence_filter : '' ?>">‹</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $employee_filter ? '&employee=' . $employee_filter : '' ?><?= $urgence_filter ? '&urgence=' . $urgence_filter : '' ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $employee_filter ? '&employee=' . $employee_filter : '' ?><?= $urgence_filter ? '&urgence=' . $urgence_filter : '' ?>">›</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $employee_filter ? '&employee=' . $employee_filter : '' ?><?= $urgence_filter ? '&urgence=' . $urgence_filter : '' ?>">››</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modals pour détails et actions -->
<?php foreach ($demandes as $demande): ?>
<!-- Modal détails -->
<div class="modal fade" id="detailModal<?= $demande['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la demande #<?= $demande['id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informations générales</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Demandeur :</th>
                                <td><?= htmlspecialchars($demande['employee_nom'] . ' ' . $demande['employee_prenom']) ?></td>
                            </tr>
                            <tr>
                                <th>Date :</th>
                                <td><?= date('d/m/Y H:i', strtotime($demande['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <th>Type :</th>
                                <td><?= ucfirst($demande['type_demande']) ?></td>
                            </tr>
                            <tr>
                                <th>Urgence :</th>
                                <td>
                                    <?php
                                    $urgence_icon = $urgence_icons[$demande['urgence']] ?? '⚪';
                                    echo $urgence_icon . ' ' . ucfirst($demande['urgence']);
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Statut :</th>
                                <td>
                                    <?php
                                    $statut_icon = $statut_icons[$demande['statut']] ?? '❓';
                                    echo $statut_icon . ' ' . ucfirst(str_replace('_', ' ', $demande['statut']));
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Matériel demandé</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Désignation :</th>
                                <td><?= htmlspecialchars($demande['designation']) ?></td>
                            </tr>
                            <?php if ($demande['marque']): ?>
                            <tr>
                                <th>Marque :</th>
                                <td><?= htmlspecialchars($demande['marque']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($demande['modele']): ?>
                            <tr>
                                <th>Modèle :</th>
                                <td><?= htmlspecialchars($demande['modele']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Catégorie :</th>
                                <td><?= htmlspecialchars($demande['categorie_nom']) ?></td>
                            </tr>
                            <tr>
                                <th>Quantité :</th>
                                <td><?= $demande['quantite_demandee'] ?></td>
                            </tr>
                            <?php if ($demande['date_livraison_souhaitee']): ?>
                            <tr>
                                <th>Date souhaitée :</th>
                                <td><?= date('d/m/Y', strtotime($demande['date_livraison_souhaitee'])) ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <?php if ($demande['justification']): ?>
                <div class="mt-3">
                    <h6>Justification</h6>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($demande['justification'])) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($demande['raison_rejet']): ?>
                <div class="mt-3">
                    <h6 class="text-danger">Raison du rejet</h6>
                    <div class="border border-danger rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($demande['raison_rejet'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal rejet -->
<?php if (in_array($user_role, ['admin', 'dev', 'logistique']) && $demande['statut'] === 'en_attente'): ?>
<div class="modal fade" id="rejectModal<?= $demande['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Rejeter la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="demande_id" value="<?= $demande['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    
                    <div class="mb-3">
                        <label for="raison_rejet<?= $demande['id'] ?>" class="form-label">Raison du rejet *</label>
                        <textarea name="raison_rejet" id="raison_rejet<?= $demande['id'] ?>" 
                                  class="form-control" rows="3" required
                                  placeholder="Expliquez pourquoi cette demande est rejetée..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <small>⚠️ Cette action est définitive. L'employé sera notifié du rejet.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Rejeter la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<?php
if (file_exists($template_footer)) {
    include $template_footer;
}
?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1">📋 Demandes Matériel</h1>
                            <p class="text-muted mb-0">Gestion des demandes d'équipement</p>
                        </div>
                        <div>
                            <a href="create.php" class="btn btn-primary">
                                ➕ Nouvelle demande
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show">
                ✅ <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show">
                ❌ <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

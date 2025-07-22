<?php
/**
 * Titre: Module Matériel - Gestion inventaire
 * Chemin: /public/materiel/inventory/index.php
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
$page_title = 'Inventaire Matériel';
$page_subtitle = 'Gestion du stock et des équipements';
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

// Permissions pour inventaire
$can_view_inventory = in_array($user_role, ['admin', 'dev', 'logistique']);
if (!$can_view_inventory) {
    header('Location: ../index.php?error=access_denied');
    exit;
}

// Connexion BDD et Manager matériel
try {
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $materielManager = new MaterielManager($db);
} catch (Exception $e) {
    error_log("Erreur BDD inventaire: " . $e->getMessage());
    die("Erreur de connexion à la base de données");
}

// Paramètres de filtrage
$category_filter = $_GET['category'] ?? '';
$agence_filter = $_GET['agence'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;

// Récupération de l'inventaire
try {
    $where_conditions = ["1=1"];
    $params = [];
    
    if ($category_filter) {
        $where_conditions[] = "mc.id = ?";
        $params[] = $category_filter;
    }
    
    if ($agence_filter) {
        $where_conditions[] = "mi.agence_id = ?";
        $params[] = $agence_filter;
    }
    
    if ($status_filter) {
        $where_conditions[] = "mi.statut = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(mt.designation LIKE ? OR mt.marque LIKE ? OR mt.modele LIKE ? OR mi.numero_inventaire LIKE ?)";
        $search_term = "%$search%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Requête principale avec pagination
    $sql = "
        SELECT 
            mi.*,
            mt.designation,
            mt.marque,
            mt.modele,
            mt.reference,
            mt.prix_unitaire,
            mc.nom as categorie_nom,
            mc.couleur as categorie_couleur,
            mc.type as categorie_type,
            ma.nom as agence_nom
        FROM materiel_items mi
        JOIN materiel_templates mt ON mi.template_id = mt.id
        JOIN materiel_categories mc ON mt.categorie_id = mc.id
        LEFT JOIN materiel_agences ma ON mi.agence_id = ma.id
        $where_clause
        ORDER BY mi.created_at DESC
        LIMIT " . (($page - 1) * $per_page) . ", $per_page
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total
    $count_sql = "
        SELECT COUNT(*) 
        FROM materiel_items mi
        JOIN materiel_templates mt ON mi.template_id = mt.id
        JOIN materiel_categories mc ON mt.categorie_id = mc.id
        LEFT JOIN materiel_agences ma ON mi.agence_id = ma.id
        $where_clause
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetchColumn();
    
    $total_pages = ceil($total_items / $per_page);
    
} catch (Exception $e) {
    error_log("Erreur récupération inventaire: " . $e->getMessage());
    $inventory = [];
    $total_items = 0;
    $total_pages = 1;
}

// Récupération des données pour les filtres
$categories = $materielManager->getCategories();
$agences = $materielManager->getAgences();
$stats = $materielManager->getStatistiquesGenerales();

// Headers de base
$template_header = ROOT_PATH . '/templates/header.php';
$template_footer = ROOT_PATH . '/templates/footer.php';

if (file_exists($template_header)) {
    include $template_header;
}
?>

<!-- INVENTAIRE MATÉRIEL -->
<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1">📦 Inventaire Matériel</h1>
                            <p class="text-muted mb-0">Gestion du stock et des équipements</p>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                ➕ Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="importInventory()">Importer CSV</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?export=csv">Exporter CSV</a></li>
                                <li><a class="dropdown-item" href="?export=pdf">Rapport PDF</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-primary mb-2">📦</div>
                    <h6>Total Items</h6>
                    <h4 class="text-primary mb-0"><?= number_format($stats['total_materiel']) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-success mb-2">✅</div>
                    <h6>Disponible</h6>
                    <h4 class="text-success mb-0">
                        <?= number_format(count(array_filter($inventory, fn($i) => $i['statut'] === 'disponible'))) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-warning mb-2">👤</div>
                    <h6>Attribué</h6>
                    <h4 class="text-warning mb-0">
                        <?= number_format(count(array_filter($inventory, fn($i) => $i['statut'] === 'attribue'))) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-info mb-2">🔧</div>
                    <h6>Maintenance</h6>
                    <h4 class="text-info mb-0">
                        <?= number_format(count(array_filter($inventory, fn($i) => in_array($i['statut'], ['maintenance', 'reparation'])))) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-danger mb-2">❌</div>
                    <h6>Réformé</h6>
                    <h4 class="text-danger mb-0">
                        <?= number_format(count(array_filter($inventory, fn($i) => $i['statut'] === 'reforme'))) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-secondary mb-2">💰</div>
                    <h6>Valeur estimée</h6>
                    <h5 class="text-secondary mb-0">
                        <?= number_format(array_sum(array_column($inventory, 'prix_achat'))) ?>€
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Recherche</label>
                            <input type="text" name="search" class="form-control" 
                                   value="<?= htmlspecialchars($search) ?>"
                                   placeholder="Désignation, marque, modèle, n° inventaire...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Catégorie</label>
                            <select name="category" class="form-select">
                                <option value="">Toutes</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Agence</label>
                            <select name="agence" class="form-select">
                                <option value="">Toutes</option>
                                <?php foreach ($agences as $agence): ?>
                                    <option value="<?= $agence['id'] ?>" <?= $agence_filter == $agence['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($agence['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="">Tous</option>
                                <option value="disponible" <?= $status_filter === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="attribue" <?= $status_filter === 'attribue' ? 'selected' : '' ?>>Attribué</option>
                                <option value="maintenance" <?= $status_filter === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                <option value="reparation" <?= $status_filter === 'reparation' ? 'selected' : '' ?>>Réparation</option>
                                <option value="reforme" <?= $status_filter === 'reforme' ? 'selected' : '' ?>>Réformé</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                                <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste de l'inventaire -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Inventaire (<?= number_format($total_items) ?> item<?= $total_items > 1 ? 's' : '' ?>)
                    </h5>
                    <div class="text-muted">
                        Page <?= $page ?> sur <?= $total_pages ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($inventory)): ?>
                    <div class="text-center py-5">
                        <div class="display-1 text-muted mb-3">📭</div>
                        <h5 class="text-muted">Aucun matériel trouvé</h5>
                        <p class="text-muted">
                            <?php if ($search || $category_filter || $agence_filter || $status_filter): ?>
                                Essayez de modifier vos filtres ou 
                                <a href="index.php" class="text-decoration-none">voir tout l'inventaire</a>
                            <?php else: ?>
                                L'inventaire est vide. Commencez par ajouter du matériel.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>N° Inventaire</th>
                                    <th>Désignation</th>
                                    <th>Catégorie</th>
                                    <th>Agence</th>
                                    <th>État</th>
                                    <th>Statut</th>
                                    <th>Prix</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['numero_inventaire'] ?: 'N/A') ?></strong>
                                        <?php if ($item['numero_serie']): ?>
                                            <br><small class="text-muted">S/N: <?= htmlspecialchars($item['numero_serie']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($item['designation']) ?></strong>
                                            <?php if ($item['marque'] || $item['modele']): ?>
                                                <br><small class="text-muted">
                                                    <?= htmlspecialchars(trim($item['marque'] . ' ' . $item['modele'])) ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if ($item['reference']): ?>
                                                <br><small class="text-muted">Réf: <?= htmlspecialchars($item['reference']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: <?= $item['categorie_couleur'] ?? '#6B7280' ?>">
                                            <?= htmlspecialchars($item['categorie_nom']) ?>
                                        </span>
                                        <br><small class="text-muted"><?= ucfirst($item['categorie_type']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($item['agence_nom'] ?: 'Non assigné') ?>
                                        <?php if ($item['emplacement']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($item['emplacement']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $etat_classes = [
                                            'neuf' => 'bg-success',
                                            'bon' => 'bg-primary',
                                            'use' => 'bg-warning',
                                            'defaillant' => 'bg-danger'
                                        ];
                                        $etat_icons = [
                                            'neuf' => '🆕',
                                            'bon' => '✅',
                                            'use' => '⚠️',
                                            'defaillant' => '❌'
                                        ];
                                        $etat_class = $etat_classes[$item['etat']] ?? 'bg-secondary';
                                        $etat_icon = $etat_icons[$item['etat']] ?? '❓';
                                        ?>
                                        <span class="badge <?= $etat_class ?>">
                                            <?= $etat_icon ?> <?= ucfirst($item['etat']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statut_classes = [
                                            'disponible' => 'bg-success',
                                            'attribue' => 'bg-warning',
                                            'reparation' => 'bg-info',
                                            'maintenance' => 'bg-info',
                                            'reforme' => 'bg-danger'
                                        ];
                                        $statut_icons = [
                                            'disponible' => '✅',
                                            'attribue' => '👤',
                                            'reparation' => '🔧',
                                            'maintenance' => '🔧',
                                            'reforme' => '❌'
                                        ];
                                        $statut_class = $statut_classes[$item['statut']] ?? 'bg-secondary';
                                        $statut_icon = $statut_icons[$item['statut']] ?? '❓';
                                        ?>
                                        <span class="badge <?= $statut_class ?>">
                                            <?= $statut_icon ?> <?= ucfirst($item['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['prix_achat']): ?>
                                            <strong><?= number_format($item['prix_achat'], 2) ?>€</strong>
                                            <?php if ($item['date_acquisition']): ?>
                                                <br><small class="text-muted"><?= date('d/m/Y', strtotime($item['date_acquisition'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non renseigné</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#itemModal<?= $item['id'] ?>">
                                                👁️
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                    onclick="editItem(<?= $item['id'] ?>)">
                                                ✏️
                                            </button>
                                            <?php if ($item['qr_code']): ?>
                                            <button type="button" class="btn btn-outline-info btn-sm"
                                                    onclick="showQRCode(<?= $item['id'] ?>)">
                                                📱
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
                                <a class="page-link" href="?page=1<?= http_build_query(['category' => $category_filter, 'agence' => $agence_filter, 'status' => $status_filter, 'search' => $search], '', '&') ?>">‹‹</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= http_build_query(['category' => $category_filter, 'agence' => $agence_filter, 'status' => $status_filter, 'search' => $search], '', '&') ?>">‹</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= http_build_query(['category' => $category_filter, 'agence' => $agence_filter, 'status' => $status_filter, 'search' => $search], '', '&') ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= http_build_query(['category' => $category_filter, 'agence' => $agence_filter, 'status' => $status_filter, 'search' => $search], '', '&') ?>">›</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?><?= http_build_query(['category' => $category_filter, 'agence' => $agence_filter, 'status' => $status_filter, 'search' => $search], '', '&') ?>">››</a>
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

<!-- Modals pour détails des items -->
<?php foreach ($inventory as $item): ?>
<div class="modal fade" id="itemModal<?= $item['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails - <?= htmlspecialchars($item['designation']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informations générales</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>N° Inventaire :</th>
                                <td><?= htmlspecialchars($item['numero_inventaire'] ?: 'Non assigné') ?></td>
                            </tr>
                            <?php if ($item['numero_serie']): ?>
                            <tr>
                                <th>N° Série :</th>
                                <td><?= htmlspecialchars($item['numero_serie']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Désignation :</th>
                                <td><?= htmlspecialchars($item['designation']) ?></td>
                            </tr>
                            <?php if ($item['marque']): ?>
                            <tr>
                                <th>Marque :</th>
                                <td><?= htmlspecialchars($item['marque']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($item['modele']): ?>
                            <tr>
                                <th>Modèle :</th>
                                <td><?= htmlspecialchars($item['modele']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($item['reference']): ?>
                            <tr>
                                <th>Référence :</th>
                                <td><?= htmlspecialchars($item['reference']) ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>État et localisation</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Catégorie :</th>
                                <td>
                                    <span class="badge" style="background-color: <?= $item['categorie_couleur'] ?? '#6B7280' ?>">
                                        <?= htmlspecialchars($item['categorie_nom']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Agence :</th>
                                <td><?= htmlspecialchars($item['agence_nom'] ?: 'Non assigné') ?></td>
                            </tr>
                            <?php if ($item['emplacement']): ?>
                            <tr>
                                <th>Emplacement :</th>
                                <td><?= htmlspecialchars($item['emplacement']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>État :</th>
                                <td>
                                    <?php
                                    $etat_icon = $etat_icons[$item['etat']] ?? '❓';
                                    echo $etat_icon . ' ' . ucfirst($item['etat']);
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Statut :</th>
                                <td>
                                    <?php
                                    $statut_icon = $statut_icons[$item['statut']] ?? '❓';
                                    echo $statut_icon . ' ' . ucfirst($item['statut']);
                                    ?>
                                </td>
                            </tr>
                            <?php if ($item['prix_achat']): ?>
                            <tr>
                                <th>Prix d'achat :</th>
                                <td><?= number_format($item['prix_achat'], 2) ?>€</td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($item['date_acquisition']): ?>
                            <tr>
                                <th>Date d'acquisition :</th>
                                <td><?= date('d/m/Y', strtotime($item['date_acquisition'])) ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <?php if ($item['observations']): ?>
                <div class="mt-3">
                    <h6>Observations</h6>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($item['observations'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" onclick="editItem(<?= $item['id'] ?>)">
                    ✏️ Modifier
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Scripts -->
<script>
function addItemModal() {
    alert('Fonctionnalité en développement - Ajout d\'item');
}

function editItem(id) {
    alert('Fonctionnalité en développement - Modification item #' + id);
}

function showQRCode(id) {
    alert('Fonctionnalité en développement - QR Code item #' + id);
}

function importInventory() {
    alert('Fonctionnalité en développement - Import CSV');
}

// Auto-submit du formulaire après changement de filtre
document.querySelectorAll('select[name="category"], select[name="agence"], select[name="status"]').forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php
if (file_exists($template_footer)) {
    include $template_footer;
}
?>="dropdown-item" href="#" onclick="addItemModal()">Ajouter un item</a></li>
                                <li><a class

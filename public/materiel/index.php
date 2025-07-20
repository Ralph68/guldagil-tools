<?php
/**
 * Titre: Module Matériel - Index avec vues par rôle
 * Chemin: /public/outillages/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once ROOT_PATH . '/config/modules.php';

// Variables pour template
$page_title = 'Gestion du Matériel';
$page_subtitle = 'Outillage et Équipements';
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
$module_data = $modules['outillages'] ?? ['status' => 'development', 'name' => 'Matériel'];

if (!canAccessModule('materiel', $module_data, $user_role)) {
    header('Location: ../../index.php?error=access_denied');
    exit;
}

// Connexion BDD
try {
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
} catch (Exception $e) {
    error_log("Erreur BDD Matériel: " . $e->getMessage());
    $db_connected = false;
}

// Configuration des rôles et permissions
$permissions = [
    'user' => [
        'view_my_equipment' => true,
        'create_request' => true,
        'view_inventory' => false,
        'manage_equipment' => false,
        'validate_requests' => false,
        'view_all_stats' => false
    ],
    'logistique' => [
        'view_my_equipment' => true,
        'create_request' => true,
        'view_inventory' => true,
        'manage_equipment' => false,
        'validate_requests' => false,
        'view_all_stats' => true
    ],
    'admin' => [
        'view_my_equipment' => true,
        'create_request' => true,
        'view_inventory' => true,
        'manage_equipment' => true,
        'validate_requests' => true,
        'view_all_stats' => true
    ],
    'dev' => [
        'view_my_equipment' => true,
        'create_request' => true,
        'view_inventory' => true,
        'manage_equipment' => true,
        'validate_requests' => true,
        'view_all_stats' => true
    ]
];

$user_permissions = $permissions[$user_role] ?? $permissions['user'];

// Récupération des données selon les droits
$stats = [];
$my_equipment = [];
$pending_requests = [];

if ($db_connected) {
    try {
        // Stats globales (selon permissions)
        if ($user_permissions['view_all_stats']) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM outillage_items");
            $stats['total_equipment'] = $stmt->fetch()['total'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as assigned FROM outillage_attributions WHERE etat_attribution = 'active'");
            $stats['assigned_equipment'] = $stmt->fetch()['assigned'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as pending FROM outillage_demandes WHERE statut = 'en_attente'");
            $stats['pending_requests'] = $stmt->fetch()['pending'] ?? 0;
        }
        
        // Mon matériel (tous les rôles)
        if ($user_permissions['view_my_equipment']) {
            // Trouver l'employé lié à l'utilisateur
            $stmt = $db->prepare("SELECT id FROM outillage_employees WHERE email = ? OR nom LIKE ?");
            $stmt->execute([$current_user['email'] ?? '', '%' . $current_user['username'] . '%']);
            $employee = $stmt->fetch();
            
            if ($employee) {
                $stmt = $db->prepare("
                    SELECT attr.*, i.numero_serie, t.designation, t.marque, t.modele, c.nom as categorie
                    FROM outillage_attributions attr
                    LEFT JOIN outillage_items i ON attr.item_id = i.id
                    LEFT JOIN outillage_templates t ON i.template_id = t.id
                    LEFT JOIN outillage_categories c ON t.categorie_id = c.id
                    WHERE attr.employee_id = ? AND attr.etat_attribution = 'active'
                    ORDER BY t.designation
                ");
                $stmt->execute([$employee['id']]);
                $my_equipment = $stmt->fetchAll();
            }
        }
        
        // Demandes en attente (responsables)
        if ($user_permissions['validate_requests']) {
            $stmt = $db->query("
                SELECT d.*, t.designation, CONCAT(e.prenom, ' ', e.nom) as demandeur,
                       a.nom as agence_nom, DATEDIFF(NOW(), d.created_at) as jours_attente
                FROM outillage_demandes d
                LEFT JOIN outillage_templates t ON d.template_id = t.id
                LEFT JOIN outillage_employees e ON d.employee_id = e.id
                LEFT JOIN outillage_agences a ON e.agence_id = a.id
                WHERE d.statut = 'en_attente'
                ORDER BY d.created_at ASC
                LIMIT 10
            ");
            $pending_requests = $stmt->fetchAll();
        }
        
    } catch (Exception $e) {
        error_log("Erreur récupération données: " . $e->getMessage());
    }
}

$build_number = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'dev-' . date('ymdHis');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Portail Guldagil</title>
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="./assets/css/outillage.css?v=<?= $build_number ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
    .role-header { background: var(--primary-blue); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
    .quick-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
    .stat-item { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
    .stat-number { font-size: 2rem; font-weight: 700; color: var(--primary-blue); }
    .stat-label { color: #666; margin-top: 0.5rem; }
    .action-section { background: white; padding: 2rem; border-radius: 8px; margin: 1rem 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .action-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem; }
    .action-btn { display: flex; align-items: center; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #2d3748; transition: all 0.2s; }
    .action-btn:hover { border-color: var(--primary-blue); background: #f7fafc; }
    .action-btn i { font-size: 1.5rem; margin-right: 1rem; color: var(--primary-blue); }
    .equipment-list { max-height: 400px; overflow-y: auto; }
    .equipment-item { display: flex; justify-content: between; align-items: center; padding: 0.75rem; border-bottom: 1px solid #e2e8f0; }
    .request-item { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 6px; margin: 0.5rem 0; }
    .request-meta { font-size: 0.85rem; color: #666; margin-top: 0.5rem; }
    .btn-group { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
    .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.8rem; border: none; border-radius: 4px; cursor: pointer; }
    .btn-success { background: #48bb78; color: white; }
    .btn-danger { background: #f56565; color: white; }
    .btn-info { background: #4299e1; color: white; }
    </style>
</head>
<body>
    <?php include ROOT_PATH . '/templates/header.php'; ?>
    
    <main class="main-container">
        <!-- En-tête spécifique au rôle -->
        <div class="role-header">
            <h1><i class="fas fa-tools"></i> Gestion du Matériel</h1>
            <p>
                <?php if ($user_role === 'user'): ?>
                    Espace employé - Consultez votre matériel et faites vos demandes
                <?php elseif ($user_role === 'logistique'): ?>
                    Espace logistique - Suivi et inventaire du matériel
                <?php else: ?>
                    Espace responsable - Gestion complète du matériel et validation des demandes
                <?php endif; ?>
            </p>
        </div>

        <!-- Statistiques rapides (selon rôle) -->
        <?php if ($user_permissions['view_all_stats'] && !empty($stats)): ?>
        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['total_equipment']) ?></div>
                <div class="stat-label">Équipements total</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['assigned_equipment']) ?></div>
                <div class="stat-label">Attribués</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['pending_requests']) ?></div>
                <div class="stat-label">Demandes en attente</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= count($my_equipment) ?></div>
                <div class="stat-label">Mon matériel</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions principales -->
        <div class="action-section">
            <h2><i class="fas fa-bolt"></i> Actions rapides</h2>
            <div class="action-buttons">
                <?php if ($user_permissions['view_my_equipment']): ?>
                <a href="#my-equipment" class="action-btn" onclick="showMyEquipment()">
                    <i class="fas fa-list"></i>
                    <div>
                        <strong>Mon matériel</strong>
                        <br><small>Voir mes attributions (<?= count($my_equipment) ?>)</small>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if ($user_permissions['create_request']): ?>
                <a href="#" class="action-btn" onclick="showRequestModal()">
                    <i class="fas fa-plus-circle"></i>
                    <div>
                        <strong>Nouvelle demande</strong>
                        <br><small>Demander du matériel</small>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if ($user_permissions['view_inventory']): ?>
                <a href="dashboard.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <div>
                        <strong>Dashboard complet</strong>
                        <br><small>Statistiques et analyses</small>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if ($user_permissions['manage_equipment']): ?>
                <a href="#" class="action-btn" onclick="alert('Module inventaire en développement')">
                    <i class="fas fa-boxes"></i>
                    <div>
                        <strong>Gérer l'inventaire</strong>
                        <br><small>Ajouter/modifier équipements</small>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mon matériel (tous les rôles) -->
        <?php if ($user_permissions['view_my_equipment']): ?>
        <div class="action-section" id="my-equipment-section">
            <h2><i class="fas fa-user-tools"></i> Mon matériel attribué</h2>
            <?php if (!empty($my_equipment)): ?>
                <div class="equipment-list">
                    <?php foreach ($my_equipment as $item): ?>
                    <div class="equipment-item">
                        <div style="flex: 1;">
                            <strong><?= htmlspecialchars($item['designation']) ?></strong>
                            <?php if ($item['marque']): ?>
                                - <?= htmlspecialchars($item['marque']) ?>
                            <?php endif; ?>
                            <?php if ($item['modele']): ?>
                                <?= htmlspecialchars($item['modele']) ?>
                            <?php endif; ?>
                            <br>
                            <small class="text-muted">
                                Catégorie: <?= htmlspecialchars($item['categorie']) ?>
                                <?php if ($item['numero_serie']): ?>
                                | S/N: <?= htmlspecialchars($item['numero_serie']) ?>
                                <?php endif; ?>
                                | Attribué le: <?= date('d/m/Y', strtotime($item['date_attribution'])) ?>
                            </small>
                        </div>
                        <div>
                            <button class="btn-sm btn-info" onclick="reportIssue(<?= $item['id'] ?>)">
                                Signaler problème
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Aucun matériel actuellement attribué.</p>
                <button class="btn btn-primary" onclick="showRequestModal()">
                    <i class="fas fa-plus"></i> Faire une première demande
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Demandes à valider (responsables uniquement) -->
        <?php if ($user_permissions['validate_requests'] && !empty($pending_requests)): ?>
        <div class="action-section">
            <h2><i class="fas fa-clock"></i> Demandes à traiter (<?= count($pending_requests) ?>)</h2>
            <?php foreach ($pending_requests as $request): ?>
            <div class="request-item">
                <strong><?= htmlspecialchars($request['designation']) ?></strong>
                <div class="request-meta">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($request['demandeur']) ?>
                    (<?= htmlspecialchars($request['agence_nom']) ?>)
                    | <i class="fas fa-calendar"></i> Il y a <?= $request['jours_attente'] ?> jour(s)
                    <?php if ($request['jours_attente'] > 7): ?>
                        <span style="color: #f56565; font-weight: bold;">⚠️ URGENT</span>
                    <?php endif; ?>
                </div>
                <div class="btn-group">
                    <button class="btn-sm btn-success" onclick="approveRequest(<?= $request['id'] ?>)">
                        <i class="fas fa-check"></i> Valider
                    </button>
                    <button class="btn-sm btn-danger" onclick="rejectRequest(<?= $request['id'] ?>)">
                        <i class="fas fa-times"></i> Refuser
                    </button>
                    <button class="btn-sm btn-info" onclick="viewRequest(<?= $request['id'] ?>)">
                        <i class="fas fa-eye"></i> Détails
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>

    <!-- Modal nouvelle demande -->
    <div id="requestModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nouvelle demande de matériel</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="requestForm">
                    <div class="form-group">
                        <label>Type de demande :</label>
                        <select name="type_demande" class="form-select" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="nouveau">Nouveau matériel</option>
                            <option value="remplacement">Remplacement</option>
                            <option value="reparation">Réparation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Équipement demandé :</label>
                        <input type="text" name="equipement" class="form-input" 
                               placeholder="Ex: Tournevis isolé 5.5x150, Perceuse sans fil..." required>
                    </div>
                    <div class="form-group">
                        <label>Justification :</label>
                        <textarea name="raison" class="form-textarea" rows="3" 
                                  placeholder="Expliquez pourquoi vous avez besoin de cet équipement..." required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                        <button type="submit" class="btn btn-primary">Envoyer la demande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Fonctions principales
    function showMyEquipment() {
        document.getElementById('my-equipment-section').scrollIntoView({ behavior: 'smooth' });
    }

    function showRequestModal() {
        document.getElementById('requestModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('requestModal').style.display = 'none';
    }

    function reportIssue(itemId) {
        const issue = prompt('Décrivez le problème rencontré:');
        if (issue) {
            fetch('./api/requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'report_issue', 
                    item_id: itemId, 
                    issue: issue 
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? 'Problème signalé avec succès' : 'Erreur: ' + data.error);
            });
        }
    }

    function approveRequest(id) {
        if (confirm('Valider cette demande ?')) {
            fetch('./api/requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'approve', id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Demande validée');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.error);
                }
            });
        }
    }

    function rejectRequest(id) {
        const reason = prompt('Raison du refus:');
        if (reason) {
            fetch('./api/requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject', id: id, reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Demande refusée');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.error);
                }
            });
        }
    }

    function viewRequest(id) {
        alert('Détails demande #' + id + ' - Module en développement');
    }

    // Soumission formulaire
    document.getElementById('requestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        fetch('./api/requests.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create', ...data })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Demande envoyée avec succès !');
                closeModal();
                location.reload();
            } else {
                alert('Erreur: ' + data.error);
            }
        });
    });

    console.log('📋 Module Matériel - Rôle: <?= $user_role ?>');
    console.log('🔑 Permissions:', <?= json_encode($user_permissions) ?>);
    </script>
</body>
</html>

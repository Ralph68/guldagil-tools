<?php
/**
 * Titre: Dashboard Module Matériel - Version Production
 * Chemin: /public/materiel/dashboard.php
 * Version: 0.5 beta + build auto
 */

// =====================================
// CONFIGURATION ET SÉCURITÉ
// =====================================
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
$page_description = 'Gestion complète du matériel : outillage manuel, électroportatif et équipements';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// =====================================
// VÉRIFICATION AUTHENTIFICATION
// =====================================
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

// =====================================
// CONNEXION BASE DE DONNÉES
// =====================================
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

// =====================================
// RÉCUPÉRATION DONNÉES RÉELLES
// =====================================
$stats = [];
$demandesEnAttente = [];
$alerts = [];

if ($db_connected) {
    try {
        // Statistiques globales
        $stmt = $db->query("SELECT COUNT(*) as total FROM outillage_items");
        $stats['total_materiel'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(*) as attribues FROM outillage_attributions WHERE etat_attribution = 'active'");
        $stats['materiel_attribue'] = $stmt->fetch()['attribues'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(*) as en_attente FROM outillage_demandes WHERE statut = 'en_attente'");
        $stats['demandes_attente'] = $stmt->fetch()['en_attente'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(*) as maintenance FROM outillage_items i 
                           JOIN outillage_templates t ON i.template_id = t.id 
                           WHERE t.maintenance_requise = 1 AND i.prochaine_maintenance <= CURDATE()");
        $stats['maintenance_due'] = $stmt->fetch()['maintenance'] ?? 0;
        
        // Statistiques par type
        $stmt = $db->query("SELECT c.type, c.nom, COUNT(i.id) as count 
                           FROM outillage_categories c
                           LEFT JOIN outillage_templates t ON c.id = t.categorie_id
                           LEFT JOIN outillage_items i ON t.id = i.template_id
                           GROUP BY c.id, c.type, c.nom
                           ORDER BY c.type, count DESC");
        $stats['par_type'] = $stmt->fetchAll();
        
        // Statistiques par agence
        $stmt = $db->query("SELECT a.nom, a.code, COUNT(i.id) as total_items,
                           COUNT(CASE WHEN attr.etat_attribution = 'active' THEN 1 END) as attribues
                           FROM outillage_agences a
                           LEFT JOIN outillage_items i ON a.id = i.agence_id
                           LEFT JOIN outillage_attributions attr ON i.id = attr.item_id AND attr.etat_attribution = 'active'
                           GROUP BY a.id, a.nom, a.code
                           ORDER BY total_items DESC");
        $stats['par_agence'] = $stmt->fetchAll();
        
        // Demandes en attente avec détails
        $stmt = $db->query("SELECT d.*, t.designation, t.marque, t.modele,
                           CONCAT(e.prenom, ' ', e.nom) as demandeur,
                           a.nom as agence_nom, a.code as agence_code,
                           p.nom as profil_nom,
                           DATEDIFF(NOW(), d.created_at) as jours_attente
                           FROM outillage_demandes d
                           LEFT JOIN outillage_templates t ON d.template_id = t.id
                           LEFT JOIN outillage_employees e ON d.employee_id = e.id
                           LEFT JOIN outillage_agences a ON e.agence_id = a.id
                           LEFT JOIN outillage_profils p ON e.profil_id = p.id
                           WHERE d.statut = 'en_attente'
                           ORDER BY d.created_at DESC");
        $demandesEnAttente = $stmt->fetchAll();
        
        // Alertes automatiques
        if ($stats['maintenance_due'] > 0) {
            $alerts[] = [
                'type' => 'maintenance',
                'level' => 'warning',
                'message' => $stats['maintenance_due'] . ' équipement(s) nécessitent une maintenance',
                'action' => '?view=maintenance'
            ];
        }
        
        if ($stats['demandes_attente'] > 10) {
            $alerts[] = [
                'type' => 'demandes',
                'level' => 'info',
                'message' => 'Backlog important : ' . $stats['demandes_attente'] . ' demandes en attente',
                'action' => '?view=demandes&filter=pending'
            ];
        }
        
        // Demandes anciennes (> 7 jours)
        $anciennes = array_filter($demandesEnAttente, fn($d) => $d['jours_attente'] > 7);
        if (count($anciennes) > 0) {
            $alerts[] = [
                'type' => 'urgent',
                'level' => 'danger',
                'message' => count($anciennes) . ' demande(s) de plus de 7 jours sans traitement',
                'action' => '?view=demandes&filter=old'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Erreur récupération données: " . $e->getMessage());
        $stats = ['total_materiel' => 0, 'materiel_attribue' => 0, 'demandes_attente' => 0, 'maintenance_due' => 0];
    }
} else {
    $stats = ['total_materiel' => 0, 'materiel_attribue' => 0, 'demandes_attente' => 0, 'maintenance_due' => 0];
}

// Configuration des droits
$isResponsable = in_array($user_role, ['admin', 'dev']);
$isRespTech = $isResponsable; // À adapter selon la logique métier
$isRespAchats = $isResponsable;
$canValidateRequests = $isResponsable;

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
    <link rel="stylesheet" href="./assets/css/materiel?v=<?= $build_number ?>">
    
    <!-- Font Awesome et Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
    .dashboard-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; margin: 2rem 0; }
    .main-content { min-height: 600px; }
    .sidebar { background: #f8f9fa; padding: 1.5rem; border-radius: 12px; }
    .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
    .stat-box { background: white; padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--primary-blue); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-number { font-size: 2rem; font-weight: 700; color: var(--primary-blue); }
    .stat-label { color: #666; font-size: 0.9rem; margin-top: 0.5rem; }
    .chart-container { background: white; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .alert-item { display: flex; align-items: center; padding: 1rem; border-radius: 6px; margin: 0.5rem 0; }
    .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
    .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
    .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; }
    .request-card { background: white; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; margin: 0.5rem 0; }
    .request-meta { font-size: 0.85rem; color: #666; margin: 0.5rem 0; }
    .btn-group { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
    .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.8rem; }
    .agence-item { display: flex; justify-content: space-between; padding: 0.5rem; border-bottom: 1px solid #eee; }
    .progress-bar { width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; margin: 0.5rem 0; }
    .progress-fill { height: 100%; background: var(--primary-blue); transition: width 0.3s; }
    </style>
</head>
<body>
    <?php include ROOT_PATH . '/templates/header.php'; ?>
    
    <main class="main-container">
        <!-- En-tête avec actions -->
        <div class="module-header">
            <div>
                <h1><i class="fas fa-tools"></i> Gestion du Matériel</h1>
                <p>Dashboard global - <?= count($stats['par_agence'] ?? []) ?> agences - <?= $stats['total_materiel'] ?> équipements</p>
            </div>
            <div class="header-actions">
                <?php if ($isRespTech): ?>
                <button class="btn btn-primary" onclick="window.location.href='?action=add-equipment'">
                    <i class="fas fa-plus"></i> Nouvel équipement
                </button>
                <?php endif; ?>
                <button class="btn btn-secondary" onclick="window.location.href='?action=search'">
                    <i class="fas fa-search"></i> Rechercher
                </button>
                <button class="btn btn-info" onclick="showNewRequestModal()">
                    <i class="fas fa-hand-paper"></i> Nouvelle demande
                </button>
            </div>
        </div>

        <!-- Alertes -->
        <?php if (!empty($alerts)): ?>
        <div class="alerts-section">
            <?php foreach ($alerts as $alert): ?>
            <div class="alert-item alert-<?= $alert['level'] ?>">
                <i class="fas fa-<?= $alert['level'] === 'danger' ? 'exclamation-triangle' : ($alert['level'] === 'warning' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <span style="margin-left: 0.5rem; flex: 1;"><?= htmlspecialchars($alert['message']) ?></span>
                <button class="btn btn-sm btn-outline" onclick="window.location.href='<?= $alert['action'] ?>'">
                    Voir
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Statistiques principales -->
        <div class="stats-overview">
            <div class="stat-box">
                <div class="stat-number"><?= number_format($stats['total_materiel']) ?></div>
                <div class="stat-label">Équipements total</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= number_format($stats['materiel_attribue']) ?></div>
                <div class="stat-label">Actuellement attribués</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= number_format($stats['demandes_attente']) ?></div>
                <div class="stat-label">Demandes en attente</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= number_format($stats['maintenance_due']) ?></div>
                <div class="stat-label">Maintenance due</div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="dashboard-grid">
            <div class="main-content">
                <!-- Répartition par type -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> Répartition par type d'équipement</h3>
                    <canvas id="typeChart" width="400" height="200"></canvas>
                </div>

                <!-- Répartition par agence -->
                <div class="chart-container">
                    <h3><i class="fas fa-map-marker-alt"></i> Répartition par agence</h3>
                    <canvas id="agenceChart" width="400" height="200"></canvas>
                </div>

                <!-- Demandes récentes (pour responsables) -->
                <?php if ($canValidateRequests && !empty($demandesEnAttente)): ?>
                <div class="chart-container">
                    <h3><i class="fas fa-clock"></i> Demandes à traiter (<?= count($demandesEnAttente) ?>)</h3>
                    <?php foreach (array_slice($demandesEnAttente, 0, 5) as $demande): ?>
                    <div class="request-card">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <strong><?= htmlspecialchars($demande['designation']) ?></strong>
                                <?php if ($demande['marque']): ?>
                                - <?= htmlspecialchars($demande['marque']) ?>
                                <?php endif; ?>
                                <div class="request-meta">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($demande['demandeur']) ?>
                                    (<?= htmlspecialchars($demande['profil_nom']) ?>)
                                    <br>
                                    <i class="fas fa-building"></i> <?= htmlspecialchars($demande['agence_nom']) ?>
                                    <br>
                                    <i class="fas fa-calendar"></i> Il y a <?= $demande['jours_attente'] ?> jour(s)
                                    <?php if ($demande['jours_attente'] > 7): ?>
                                    <span style="color: #dc3545; font-weight: bold;">⚠️ URGENT</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($demande['raison_demande']): ?>
                                <div style="font-style: italic; color: #666; margin-top: 0.5rem;">
                                    "<?= htmlspecialchars($demande['raison_demande']) ?>"
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-success" onclick="approveRequest(<?= $demande['id'] ?>)">
                                    <i class="fas fa-check"></i> Valider
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?= $demande['id'] ?>)">
                                    <i class="fas fa-times"></i> Refuser
                                </button>
                                <button class="btn btn-sm btn-info" onclick="viewRequest(<?= $demande['id'] ?>)">
                                    <i class="fas fa-eye"></i> Détails
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($demandesEnAttente) > 5): ?>
                    <div style="text-align: center; margin-top: 1rem;">
                        <button class="btn btn-outline" onclick="window.location.href='?view=all-requests'">
                            Voir toutes les demandes (<?= count($demandesEnAttente) ?>)
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="sidebar">
                <!-- Actions rapides -->
                <div class="chart-container" style="margin: 0 0 1.5rem 0;">
                    <h4><i class="fas fa-bolt"></i> Actions rapides</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <button class="btn btn-info btn-sm" onclick="showMyEquipment()">
                            <i class="fas fa-list"></i> Mon matériel
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="showSearchModal()">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        <?php if ($isRespTech): ?>
                        <button class="btn btn-warning btn-sm" onclick="showMaintenanceReport()">
                            <i class="fas fa-wrench"></i> Maintenance
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="showInventoryReport()">
                            <i class="fas fa-clipboard-list"></i> Inventaire
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Répartition par agence -->
                <div class="chart-container" style="margin: 0;">
                    <h4><i class="fas fa-building"></i> Par agence</h4>
                    <?php foreach ($stats['par_agence'] ?? [] as $agence): ?>
                    <div class="agence-item">
                        <div>
                            <strong><?= htmlspecialchars($agence['code']) ?></strong>
                            <br>
                            <small><?= htmlspecialchars($agence['nom']) ?></small>
                        </div>
                        <div style="text-align: right;">
                            <strong><?= $agence['total_items'] ?></strong>
                            <br>
                            <small><?= $agence['attribues'] ?> attribués</small>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $agence['total_items'] > 0 ? round(($agence['attribues'] / $agence['total_items']) * 100) : 0 ?>%"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>

    <!-- Modal nouvelle demande -->
    <div id="newRequestModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-hand-paper"></i> Nouvelle demande de matériel</h3>
                <button class="close-btn" onclick="closeModal('newRequestModal')">&times;</button>
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
                        <input type="text" name="equipement" class="form-input" placeholder="Ex: Tournevis isolé 5.5x150" required>
                    </div>
                    <div class="form-group">
                        <label>Raison de la demande :</label>
                        <textarea name="raison" class="form-textarea" rows="3" placeholder="Expliquez pourquoi vous avez besoin de cet équipement..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('newRequestModal')">Annuler</button>
                        <button type="submit" class="btn btn-primary">Envoyer la demande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Graphiques
    document.addEventListener('DOMContentLoaded', function() {
        // Graphique par type
        const typeData = <?= json_encode($stats['par_type'] ?? []) ?>;
        if (typeData.length > 0) {
            const typeCtx = document.getElementById('typeChart').getContext('2d');
            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: typeData.map(item => item.nom),
                    datasets: [{
                        data: typeData.map(item => item.count),
                        backgroundColor: ['#3182ce', '#38a169', '#ed8936', '#9f7aea', '#38b2ac', '#d53f8c']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        // Graphique par agence  
        const agenceData = <?= json_encode($stats['par_agence'] ?? []) ?>;
        if (agenceData.length > 0) {
            const agenceCtx = document.getElementById('agenceChart').getContext('2d');
            new Chart(agenceCtx, {
                type: 'bar',
                data: {
                    labels: agenceData.map(item => item.code),
                    datasets: [{
                        label: 'Total équipements',
                        data: agenceData.map(item => item.total_items),
                        backgroundColor: '#3182ce'
                    }, {
                        label: 'Attribués',
                        data: agenceData.map(item => item.attribues),
                        backgroundColor: '#38a169'
                    }]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    });

    // Fonctions de gestion
    function showNewRequestModal() {
        document.getElementById('newRequestModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function approveRequest(id) {
        if (confirm('Valider cette demande ?')) {
            // Appel API pour validation
            fetch('./api/requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'approve', id: id })
            }).then(() => location.reload());
        }
    }

    function rejectRequest(id) {
        const reason = prompt('Raison du refus (optionnel):');
        if (reason !== null) {
            fetch('./api/requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject', id: id, reason: reason })
            }).then(() => location.reload());
        }
    }

    function viewRequest(id) {
        window.location.href = `?action=view-request&id=${id}`;
    }

    function showMyEquipment() {
        window.location.href = '?action=my-equipment';
    }

    function showSearchModal() {
        // TODO: Implémenter modal de recherche avancée
        alert('Module de recherche en développement');
    }

    function showMaintenanceReport() {
        window.location.href = '?action=maintenance-report';
    }

    function showInventoryReport() {
        window.location.href = '?action=inventory-report';
    }

    // Soumission formulaire demande
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
                closeModal('newRequestModal');
                location.reload();
            } else {
                alert('Erreur: ' + data.error);
            }
        });
    });

    console.log('🔧 Dashboard Matériel v0.5 - Mode Production');
    console.log('📊 Stats:', <?= json_encode($stats) ?>);
    </script>
</body>
</html>

<?php
/**
 * Titre: Gestion des attributions EPI
 * Chemin: /public/epi/assignments.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/epimanager.php';

session_start();
$page_title = 'Attributions EPI';

try {
    $epiManager = new EpiManager();
    
    // Paramètres
    $action = $_GET['action'] ?? 'list';
    $employeeId = $_GET['employee_id'] ?? null;
    $search = $_GET['search'] ?? '';
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    
    // Données pour les formulaires
    $employees = $epiManager->getEmployees();
    $categories = $epiManager->getCategories();
    $inventory = $epiManager->getInventory();
    
    // Récupération des attributions
    $assignments = getAssignments($employeeId, $search);
    
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
    error_log($error);
    $assignments = [];
    $employees = [];
    $categories = [];
}

/**
 * Récupérer les attributions avec filtres
 */
function getAssignments($employeeId = null, $search = '') {
    global $db;
    
    $sql = "
        SELECT 
            a.id,
            a.assigned_date,
            a.expiry_date,
            a.notes,
            a.status,
            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
            e.department,
            c.name as category_name,
            c.description as category_description,
            CASE 
                WHEN a.expiry_date IS NULL THEN 'permanent'
                WHEN a.expiry_date < NOW() THEN 'expired'
                WHEN a.expiry_date <= DATE_ADD(NOW(), INTERVAL 15 DAY) THEN 'urgent'
                ELSE 'valid'
            END as assignment_status,
            CASE 
                WHEN a.expiry_date IS NULL THEN NULL
                ELSE DATEDIFF(a.expiry_date, NOW())
            END as days_remaining
        FROM epi_assignments a
        JOIN epi_employees e ON a.employee_id = e.id
        JOIN epi_categories c ON a.category_id = c.id
        WHERE a.status = 'active'
    ";
    
    $params = [];
    
    if ($employeeId) {
        $sql .= " AND a.employee_id = :employee_id";
        $params['employee_id'] = $employeeId;
    }
    
    if ($search) {
        $sql .= " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :search OR c.name LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    $sql .= " ORDER BY a.assigned_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Portail Guldagil</title>
    <link rel="stylesheet" href="assets/css/epi.css">
</head>
<body>
    <header class="epi-header">
        <div class="header-container">
            <h1>🔄 Attributions EPI</h1>
            <p>Gestion des équipements attribués aux employés</p>
        </div>
    </header>

    <main class="dashboard-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="epi/index.php">🛡️ EPI</a>
            <span>›</span>
            <span>Attributions</span>
            <?php if ($employeeId): ?>
                <span>›</span>
                <span><?= htmlspecialchars(getEmployeeName($employeeId, $employees)) ?></span>
            <?php endif; ?>
        </nav>

        <?php if ($message): ?>
            <div class="message message-<?= $message['type'] ?>">
                <?= htmlspecialchars($message['text']) ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Filtres et actions -->
            <div class="epi-card mb-3">
                <div class="d-flex justify-between align-center">
                    <div>
                        <h3>📊 Attributions actives (<?= count($assignments) ?>)</h3>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="GET" class="d-flex gap-2">
                            <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                            <input 
                                type="text" 
                                name="search" 
                                placeholder="Rechercher..." 
                                value="<?= htmlspecialchars($search) ?>"
                                class="form-input"
                                style="width: 200px;"
                            >
                            <button type="submit" class="btn btn-primary">🔍</button>
                        </form>
                        <?php if (!$employeeId): ?>
                            <select onchange="if(this.value) window.location.href='?employee_id='+this.value" class="form-select">
                                <option value="">Tous les employés</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= $employeeId == $emp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['last_name'] . ' ' . $emp['first_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <a href="?action=add<?= $employeeId ? '&employee_id='.$employeeId : '' ?>" class="btn btn-success">➕ Nouvelle attribution</a>
                    </div>
                </div>
            </div>

            <!-- Liste des attributions -->
            <div class="epi-card">
                <?php if (empty($assignments)): ?>
                    <div class="text-center p-3">
                        <p>Aucune attribution trouvée.</p>
                        <a href="?action=add" class="btn btn-primary">Créer la première attribution</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="epi-table">
                            <thead>
                                <tr>
                                    <th>Employé</th>
                                    <th>Département</th>
                                    <th>Équipement</th>
                                    <th>Date attribution</th>
                                    <th>Expiration</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($assignment['employee_name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="status status-active">
                                                <?= htmlspecialchars($assignment['department'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($assignment['category_name']) ?></strong>
                                                <?php if ($assignment['category_description']): ?>
                                                    <div style="font-size: 0.8rem; color: var(--epi-gray);">
                                                        <?= htmlspecialchars($assignment['category_description']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($assignment['assigned_date'])) ?></td>
                                        <td>
                                            <?php if ($assignment['expiry_date']): ?>
                                                <div>
                                                    <?= date('d/m/Y', strtotime($assignment['expiry_date'])) ?>
                                                    <?php if ($assignment['days_remaining'] !== null): ?>
                                                        <div style="font-size: 0.8rem; color: <?= $assignment['days_remaining'] < 0 ? 'var(--epi-danger)' : ($assignment['days_remaining'] <= 15 ? 'var(--epi-warning)' : 'var(--epi-success)') ?>">
                                                            <?= $assignment['days_remaining'] < 0 ? 'Expiré depuis '.abs($assignment['days_remaining']).' j' : ($assignment['days_remaining'] == 0 ? 'Expire aujourd\'hui' : 'Dans '.$assignment['days_remaining'].' j') ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="status status-ok">Permanent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusConfig = [
                                                'expired' => ['class' => 'status-expired', 'text' => '❌ Expiré'],
                                                'urgent' => ['class' => 'status-urgent', 'text' => '⚠️ Urgent'],
                                                'valid' => ['class' => 'status-ok', 'text' => '✅ Valide'],
                                                'permanent' => ['class' => 'status-active', 'text' => '♾️ Permanent']
                                            ];
                                            $status = $statusConfig[$assignment['assignment_status']] ?? $statusConfig['valid'];
                                            ?>
                                            <span class="status <?= $status['class'] ?>">
                                                <?= $status['text'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="?action=view&id=<?= $assignment['id'] ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">👁️</a>
                                                <a href="?action=extend&id=<?= $assignment['id'] ?>" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">📅</a>
                                                <a href="?action=return&id=<?= $assignment['id'] ?>" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">↩️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'add'): ?>
            <!-- Formulaire nouvelle attribution -->
            <div class="epi-card">
                <h3>➕ Nouvelle attribution</h3>
                <form method="POST" action="ajax/manage_assignment.php" class="epi-form">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="employee_id">Employé *</label>
                        <select id="employee_id" name="employee_id" class="form-select" required <?= $employeeId ? 'readonly' : '' ?>>
                            <option value="">Sélectionner un employé...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $employeeId == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['last_name'] . ' ' . $emp['first_name']) ?> 
                                    (<?= htmlspecialchars($emp['department'] ?? 'N/A') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Équipement EPI *</label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Sélectionner un équipement...</option>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                // Vérifier le stock disponible
                                $stockInfo = array_filter($inventory, fn($item) => $item['category_name'] === $category['name']);
                                $stock = !empty($stockInfo) ? array_values($stockInfo)[0]['quantity_available'] : 0;
                                ?>
                                <option value="<?= $category['id'] ?>" <?= $stock <= 0 ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                    (Stock: <?= $stock ?><?= $stock <= 0 ? ' - Rupture' : '' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="expiry_type">Type de validité *</label>
                        <select id="expiry_type" name="expiry_type" class="form-select" required onchange="toggleExpiryDate()">
                            <option value="">Sélectionner...</option>
                            <option value="permanent">Permanent (sans expiration)</option>
                            <option value="temporary">Temporaire (avec date d'expiration)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="expiry_date_group" style="display: none;">
                        <label for="expiry_date">Date d'expiration</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-input" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (optionnel)</label>
                        <textarea id="notes" name="notes" class="form-textarea" rows="3" placeholder="Informations complémentaires..."></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">✅ Créer l'attribution</button>
                        <a href="assignments.php<?= $employeeId ? '?employee_id='.$employeeId : '' ?>" class="btn btn-primary">❌ Annuler</a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'view' && isset($_GET['id'])): ?>
            <?php
            $assignmentId = (int)$_GET['id'];
            $assignmentDetails = array_filter($assignments, fn($a) => $a['id'] == $assignmentId)[0] ?? null;
            ?>
            
            <?php if ($assignmentDetails): ?>
                <!-- Détails de l'attribution -->
                <div class="dashboard-grid">
                    <div class="epi-card">
                        <h3>👤 Informations employé</h3>
                        <div style="display: grid; gap: 1rem;">
                            <div><strong>Nom:</strong> <?= htmlspecialchars($assignmentDetails['employee_name']) ?></div>
                            <div><strong>Département:</strong> <?= htmlspecialchars($assignmentDetails['department'] ?? 'Non défini') ?></div>
                        </div>
                    </div>
                    
                    <div class="epi-card">
                        <h3>🛡️ Équipement attribué</h3>
                        <div style="display: grid; gap: 1rem;">
                            <div><strong>Équipement:</strong> <?= htmlspecialchars($assignmentDetails['category_name']) ?></div>
                            <div><strong>Description:</strong> <?= htmlspecialchars($assignmentDetails['category_description'] ?? 'Aucune') ?></div>
                            <div><strong>Date d'attribution:</strong> <?= date('d/m/Y', strtotime($assignmentDetails['assigned_date'])) ?></div>
                            <?php if ($assignmentDetails['expiry_date']): ?>
                                <div><strong>Date d'expiration:</strong> <?= date('d/m/Y', strtotime($assignmentDetails['expiry_date'])) ?></div>
                                <?php if ($assignmentDetails['days_remaining'] !== null): ?>
                                    <div><strong>Jours restants:</strong> 
                                        <span style="color: <?= $assignmentDetails['days_remaining'] < 0 ? 'var(--epi-danger)' : ($assignmentDetails['days_remaining'] <= 15 ? 'var(--epi-warning)' : 'var(--epi-success)') ?>">
                                            <?= $assignmentDetails['days_remaining'] < 0 ? 'Expiré depuis '.abs($assignmentDetails['days_remaining']).' jours' : $assignmentDetails['days_remaining'].' jours' ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div><strong>Validité:</strong> <span style="color: var(--epi-success);">Permanent</span></div>
                            <?php endif; ?>
                            <?php if ($assignmentDetails['notes']): ?>
                                <div><strong>Notes:</strong> <?= htmlspecialchars($assignmentDetails['notes']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="epi-card mt-3">
                    <h3>⚡ Actions</h3>
                    <div class="quick-actions">
                        <a href="?action=extend&id=<?= $assignmentId ?>" class="action-btn">📅 Prolonger</a>
                        <a href="?action=return&id=<?= $assignmentId ?>" class="action-btn" style="background: var(--epi-danger);">↩️ Retourner</a>
                        <a href="employees.php?action=view&id=<?= getEmployeeIdFromAssignment($assignmentId, $assignments) ?>" class="action-btn">👤 Voir employé</a>
                        <a href="assignments.php" class="action-btn">📋 Toutes les attributions</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="epi-card">
                    <p>Attribution non trouvée.</p>
                    <a href="assignments.php" class="btn btn-primary">← Retour aux attributions</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Retour -->
        <div class="text-center mt-3">
            <a href="epi/index.php" style="color: var(--epi-primary);">← Retour au tableau de bord EPI</a>
        </div>
    </main>

    <script src="epi/assets/js/epi.js"></script>
    <script>
        // Script spécifique aux attributions
        function toggleExpiryDate() {
            const expiryType = document.getElementById('expiry_type').value;
            const expiryDateGroup = document.getElementById('expiry_date_group');
            const expiryDateInput = document.getElementById('expiry_date');
            
            if (expiryType === 'temporary') {
                expiryDateGroup.style.display = 'block';
                expiryDateInput.required = true;
            } else {
                expiryDateGroup.style.display = 'none';
                expiryDateInput.required = false;
                expiryDateInput.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Validation du formulaire
            const form = document.querySelector('form[action*="manage_assignment"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!EpiUtils.validateForm(this)) {
                        e.preventDefault();
                        window.epiManager.showNotification('Veuillez remplir tous les champs obligatoires', 'error');
                        return;
                    }
                    
                    // Validation spécifique
                    const expiryType = document.getElementById('expiry_type').value;
                    const expiryDate = document.getElementById('expiry_date').value;
                    
                    if (expiryType === 'temporary' && !expiryDate) {
                        e.preventDefault();
                        window.epiManager.showNotification('Veuillez sélectionner une date d\'expiration', 'error');
                        return;
                    }
                });
            }
            
            // Confirmation pour les retours d'équipement
            const returnBtns = document.querySelectorAll('a[href*="action=return"]');
            returnBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!confirm('Confirmer le retour de cet équipement ?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Fonctions utilitaires
function getEmployeeName($employeeId, $employees) {
    foreach ($employees as $emp) {
        if ($emp['id'] == $employeeId) {
            return $emp['last_name'] . ' ' . $emp['first_name'];
        }
    }
    return 'Employé inconnu';
}

function getEmployeeIdFromAssignment($assignmentId, $assignments) {
    foreach ($assignments as $assignment) {
        if ($assignment['id'] == $assignmentId) {
            return $assignment['employee_id'] ?? null;
        }
    }
    return null;
}
?>

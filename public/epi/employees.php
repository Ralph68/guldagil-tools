<?php
/**
 * Titre: Gestion des employés EPI
 * Chemin: /public/epi/employees.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/epimanager.php';

session_start();
$page_title = 'Gestion Employés EPI';

try {
    $epiManager = new EpiManager();
    
    // Gestion des actions
    $action = $_GET['action'] ?? 'list';
    $search = $_GET['search'] ?? '';
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    
    // Récupération des employés
    $employees = $epiManager->getEmployees(null, $search);
    $totalEmployees = count($employees);
    
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
    error_log($error);
    $employees = [];
    $totalEmployees = 0;
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
            <h1>👥 Gestion des Employés</h1>
            <p>Suivi des équipements par employé</p>
        </div>
    </header>

    <main class="dashboard-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="epi/index.php">🛡️ EPI</a>
            <span>›</span>
            <span>Employés</span>
        </nav>

        <?php if ($message): ?>
            <div class="message message-<?= $message['type'] ?>">
                <?= htmlspecialchars($message['text']) ?>
            </div>
        <?php endif; ?>

        <!-- Barre d'actions -->
        <div class="epi-card mb-3">
            <div class="d-flex justify-between align-center">
                <div>
                    <h3>📊 Employés (<?= $totalEmployees ?>)</h3>
                </div>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Rechercher un employé..." 
                            value="<?= htmlspecialchars($search) ?>"
                            class="form-input"
                            style="width: 250px;"
                        >
                        <button type="submit" class="btn btn-primary">🔍 Rechercher</button>
                    </form>
                    <a href="?action=add" class="btn btn-success">➕ Nouvel employé</a>
                </div>
            </div>
        </div>

        <?php if ($action === 'list'): ?>
            <!-- Liste des employés -->
            <div class="epi-card">
                <?php if (empty($employees)): ?>
                    <div class="text-center p-3">
                        <p>Aucun employé trouvé.</p>
                        <a href="?action=add" class="btn btn-primary">Ajouter le premier employé</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="epi-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Département</th>
                                    <th>Date d'embauche</th>
                                    <th>EPI attribués</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($employee['last_name'] . ' ' . $employee['first_name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($employee['email'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="status status-active">
                                                <?= htmlspecialchars($employee['department'] ?? 'Non défini') ?>
                                            </span>
                                        </td>
                                        <td><?= $employee['hire_date'] ? date('d/m/Y', strtotime($employee['hire_date'])) : 'N/A' ?></td>
                                        <td>
                                            <span class="status <?= $employee['assignments_count'] > 0 ? 'status-active' : 'status-expired' ?>">
                                                <?= $employee['assignments_count'] ?> EPI
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status status-<?= $employee['status'] ?>">
                                                <?= ucfirst($employee['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="?action=view&id=<?= $employee['id'] ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">👁️</a>
                                                <a href="assignments.php?employee_id=<?= $employee['id'] ?>" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">🔄</a>
                                                <a href="?action=edit&id=<?= $employee['id'] ?>" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">✏️</a>
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
            <!-- Formulaire d'ajout -->
            <div class="epi-card">
                <h3>➕ Nouvel employé</h3>
                <form method="POST" action="ajax/manage_employee.php" class="epi-form">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="first_name">Prénom *</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Nom *</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Département</label>
                        <select id="department" name="department" class="form-select">
                            <option value="">Sélectionner...</option>
                            <option value="Production">Production</option>
                            <option value="Logistique">Logistique</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Qualité">Qualité</option>
                            <option value="Administration">Administration</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="hire_date">Date d'embauche</label>
                        <input type="date" id="hire_date" name="hire_date" class="form-input">
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">✅ Créer l'employé</button>
                        <a href="employees.php" class="btn btn-primary">❌ Annuler</a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'view' && isset($_GET['id'])): ?>
            <?php
            $employeeId = (int)$_GET['id'];
            // Simuler les détails de l'employé (à implémenter dans EpiManager)
            $employeeDetails = array_filter($employees, fn($emp) => $emp['id'] == $employeeId)[0] ?? null;
            ?>
            
            <?php if ($employeeDetails): ?>
                <!-- Détails de l'employé -->
                <div class="dashboard-grid">
                    <div class="epi-card">
                        <h3>👤 Informations personnelles</h3>
                        <div style="display: grid; gap: 1rem;">
                            <div><strong>Nom complet:</strong> <?= htmlspecialchars($employeeDetails['last_name'] . ' ' . $employeeDetails['first_name']) ?></div>
                            <div><strong>Email:</strong> <?= htmlspecialchars($employeeDetails['email'] ?? 'Non renseigné') ?></div>
                            <div><strong>Département:</strong> <?= htmlspecialchars($employeeDetails['department'] ?? 'Non défini') ?></div>
                            <div><strong>Date d'embauche:</strong> <?= $employeeDetails['hire_date'] ? date('d/m/Y', strtotime($employeeDetails['hire_date'])) : 'Non renseignée' ?></div>
                            <div><strong>Statut:</strong> <span class="status status-<?= $employeeDetails['status'] ?>"><?= ucfirst($employeeDetails['status']) ?></span></div>
                        </div>
                    </div>
                    
                    <div class="epi-card">
                        <h3>🛡️ EPI attribués</h3>
                        <div class="text-center">
                            <div class="metric-value" style="color: var(--epi-primary); font-size: 3rem;">
                                <?= $employeeDetails['assignments_count'] ?>
                            </div>
                            <div class="metric-label" style="color: var(--epi-gray);">
                                Équipements attribués
                            </div>
                            <div class="mt-2">
                                <a href="assignments.php?employee_id=<?= $employeeId ?>" class="btn btn-primary">
                                    📋 Voir les détails
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="epi-card mt-3">
                    <h3>⚡ Actions rapides</h3>
                    <div class="quick-actions">
                        <a href="assignments.php?action=add&employee_id=<?= $employeeId ?>" class="action-btn">➕ Attribuer EPI</a>
                        <a href="?action=edit&id=<?= $employeeId ?>" class="action-btn">✏️ Modifier</a>
                        <a href="reports.php?employee_id=<?= $employeeId ?>" class="action-btn">📊 Rapports</a>
                        <a href="employees.php" class="action-btn">📋 Liste employés</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="epi-card">
                    <p>Employé non trouvé.</p>
                    <a href="employees.php" class="btn btn-primary">← Retour à la liste</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Retour -->
        <div class="text-center mt-3">
            <a href="epi/index.php" style="color: var(--epi-primary);">← Retour au tableau de bord EPI</a>
        </div>
    </main>

    <script src="assets/js/epi.js"></script>
    <script>
        // Script spécifique à la page employés
        document.addEventListener('DOMContentLoaded', function() {
            // Validation du formulaire
            const form = document.querySelector('form[action*="manage_employee"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!EpiUtils.validateForm(this)) {
                        e.preventDefault();
                        window.epiManager.showNotification('Veuillez remplir tous les champs obligatoires', 'error');
                    }
                });
            }
            
            // Recherche en temps réel (avec debounce)
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                const debouncedSearch = window.epiManager.debounce(function() {
                    searchInput.form.submit();
                }, 500);
                
                searchInput.addEventListener('input', debouncedSearch);
            }
        });
    </script>
</body>
</html>

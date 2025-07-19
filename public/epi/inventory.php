<?php
/**
 * Titre: Gestion de l'inventaire EPI
 * Chemin: public/epi/inventory.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/epimanager.php';

session_start();
$page_title = 'Inventaire EPI';

try {
    $epiManager = new EpiManager();
    
    // Gestion des paramètres
    $categoryFilter = $_GET['category'] ?? null;
    $action = $_GET['action'] ?? 'list';
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    
    // Récupération des données
    $inventory = $epiManager->getInventory($categoryFilter);
    $categories = $epiManager->getCategories();
    
    // Calcul des statistiques
    $totalItems = array_sum(array_column($inventory, 'quantity_total'));
    $availableItems = array_sum(array_column($inventory, 'quantity_available'));
    $lowStockCount = count(array_filter($inventory, fn($item) => $item['stock_status'] === 'low_stock'));
    $outOfStockCount = count(array_filter($inventory, fn($item) => $item['stock_status'] === 'out_of_stock'));
    
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
    error_log($error);
    $inventory = [];
    $categories = [];
    $totalItems = $availableItems = $lowStockCount = $outOfStockCount = 0;
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
            <h1>📦 Inventaire EPI</h1>
            <p>Gestion du stock et approvisionnement</p>
        </div>
    </header>

    <main class="dashboard-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="./index.php">🛡️ EPI</a>
            <span>›</span>
            <span>Inventaire</span>
        </nav>

        <?php if ($message): ?>
            <div class="message message-<?= $message['type'] ?>">
                <?= htmlspecialchars($message['text']) ?>
            </div>
        <?php endif; ?>

        <!-- Métriques inventaire -->
        <section class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?= $totalItems ?></div>
                <div class="metric-label">Total équipements</div>
            </div>
            <div class="metric-card" style="background: linear-gradient(135deg, var(--epi-success) 0%, #34D399 100%);">
                <div class="metric-value"><?= $availableItems ?></div>
                <div class="metric-label">Disponibles</div>
            </div>
            <div class="metric-card" style="background: linear-gradient(135deg, var(--epi-warning) 0%, #FBBF24 100%);">
                <div class="metric-value"><?= $lowStockCount ?></div>
                <div class="metric-label">Stock bas</div>
            </div>
            <div class="metric-card" style="background: linear-gradient(135deg, var(--epi-danger) 0%, #F87171 100%);">
                <div class="metric-value"><?= $outOfStockCount ?></div>
                <div class="metric-label">Rupture</div>
            </div>
        </section>

        <!-- Filtres et actions -->
        <div class="epi-card mb-3">
            <div class="d-flex justify-between align-center">
                <div>
                    <h3>📊 Stock par catégorie</h3>
                </div>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $categoryFilter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <a href="?action=add" class="btn btn-success">➕ Nouveau stock</a>
                    <a href="?action=replenish" class="btn btn-warning">📈 Réapprovisionner</a>
                </div>
            </div>
        </div>

        <?php if ($action === 'list'): ?>
            <!-- Liste inventaire -->
            <div class="epi-card">
                <?php if (empty($inventory)): ?>
                    <div class="text-center p-3">
                        <p>Aucun stock trouvé pour les critères sélectionnés.</p>
                        <a href="?action=add" class="btn btn-primary">Ajouter du stock</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="epi-table">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Description</th>
                                    <th>Emplacement</th>
                                    <th>Stock total</th>
                                    <th>Disponible</th>
                                    <th>Stock min.</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item['category_name']) ?></strong>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.9rem; color: var(--epi-gray);">
                                                <?= htmlspecialchars($item['category_description'] ?? 'Aucune description') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status status-active">
                                                <?= htmlspecialchars($item['location'] ?? 'Non défini') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <strong><?= $item['quantity_total'] ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span style="font-size: 1.1rem; font-weight: 600; color: <?= $item['quantity_available'] > 0 ? 'var(--epi-success)' : 'var(--epi-danger)' ?>">
                                                <?= $item['quantity_available'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?= $item['minimum_stock'] ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = match($item['stock_status']) {
                                                'out_of_stock' => 'status-expired',
                                                'low_stock' => 'status-urgent',
                                                default => 'status-ok'
                                            };
                                            $statusText = match($item['stock_status']) {
                                                'out_of_stock' => '❌ Rupture',
                                                'low_stock' => '⚠️ Stock bas',
                                                default => '✅ OK'
                                            };
                                            ?>
                                            <span class="status <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="?action=edit&id=<?= $item['id'] ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">✏️</a>
                                                <a href="?action=add_stock&id=<?= $item['id'] ?>" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">➕</a>
                                                <?php if ($item['stock_status'] !== 'ok'): ?>
                                                    <a href="?action=replenish&id=<?= $item['id'] ?>" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">📈</a>
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

        <?php elseif ($action === 'add'): ?>
            <!-- Formulaire d'ajout de stock -->
            <div class="epi-card">
                <h3>➕ Nouveau stock</h3>
                <form method="POST" action="ajax/manage_inventory.php" class="epi-form">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="category_id">Catégorie d'EPI *</label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Sélectionner une catégorie...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity_total">Quantité totale *</label>
                        <input type="number" id="quantity_total" name="quantity_total" class="form-input" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity_available">Quantité disponible *</label>
                        <input type="number" id="quantity_available" name="quantity_available" class="form-input" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="minimum_stock">Stock minimum *</label>
                        <input type="number" id="minimum_stock" name="minimum_stock" class="form-input" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Emplacement</label>
                        <input type="text" id="location" name="location" class="form-input" placeholder="Ex: Magasin A - Étagère 3">
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">✅ Créer le stock</button>
                        <a href="inventory.php" class="btn btn-primary">❌ Annuler</a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'replenish'): ?>
            <!-- Réapprovisionnement -->
            <div class="epi-card">
                <h3>📈 Réapprovisionnement</h3>
                
                <?php
                $lowStockItems = array_filter($inventory, fn($item) => 
                    $item['stock_status'] === 'low_stock' || $item['stock_status'] === 'out_of_stock'
                );
                ?>
                
                <?php if (empty($lowStockItems)): ?>
                    <div class="message message-success">
                        ✅ Tous les stocks sont au niveau optimal !
                    </div>
                    <a href="inventory.php" class="btn btn-primary">← Retour à l'inventaire</a>
                <?php else: ?>
                    <p>Articles nécessitant un réapprovisionnement :</p>
                    
                    <div style="overflow-x: auto;">
                        <table class="epi-table">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Stock actuel</th>
                                    <th>Stock minimum</th>
                                    <th>Suggestion</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockItems as $item): ?>
                                    <?php 
                                    $shortage = max(0, $item['minimum_stock'] - $item['quantity_available']);
                                    $suggestion = $shortage + ($item['minimum_stock'] * 0.5); // 50% de marge
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['category_name']) ?></td>
                                        <td class="text-center">
                                            <span style="color: var(--epi-danger); font-weight: 600;">
                                                <?= $item['quantity_available'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= $item['minimum_stock'] ?></td>
                                        <td class="text-center">
                                            <span style="color: var(--epi-success); font-weight: 600;">
                                                +<?= ceil($suggestion) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="ajax/manage_inventory.php" style="display: inline;">
                                                <input type="hidden" name="action" value="replenish">
                                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                <input type="number" name="quantity" value="<?= ceil($suggestion) ?>" min="1" style="width: 80px; margin-right: 0.5rem;">
                                                <button type="submit" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                    📈 Ajouter
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Actions globales -->
        <div class="epi-card mt-3">
            <h3>⚡ Actions rapides</h3>
            <div class="quick-actions">
                <a href="?action=add" class="action-btn">➕ Nouveau stock</a>
                <a href="?action=replenish" class="action-btn">📈 Réapprovisionner</a>
                <a href="reports.php?type=inventory" class="action-btn">📊 Rapport stock</a>
                <a href="assignments.php" class="action-btn">🔄 Attributions</a>
            </div>
        </div>

        <!-- Retour -->
        <div class="text-center mt-3">
            <a href="./index.php" style="color: var(--epi-primary);">← Retour au tableau de bord EPI</a>
        </div>
    </main>

    <script src="assets/js/epi.js"></script>
    <script>
        // Script spécifique à l'inventaire
        document.addEventListener('DOMContentLoaded', function() {
            // Validation du formulaire
            const form = document.querySelector('form[action*="manage_inventory"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!EpiUtils.validateForm(this)) {
                        e.preventDefault();
                        window.epiManager.showNotification('Veuillez remplir tous les champs obligatoires', 'error');
                    }
                });

                // Synchronisation quantité totale/disponible
                const totalInput = document.getElementById('quantity_total');
                const availableInput = document.getElementById('quantity_available');
                
                if (totalInput && availableInput) {
                    totalInput.addEventListener('input', function() {
                        const maxAvailable = parseInt(this.value) || 0;
                        availableInput.max = maxAvailable;
                        if (parseInt(availableInput.value) > maxAvailable) {
                            availableInput.value = maxAvailable;
                        }
                    });
                }
            }

            // Confirmation pour les réapprovisionnements
            const replenishForms = document.querySelectorAll('form[action*="manage_inventory"] input[name="action"][value="replenish"]');
            replenishForms.forEach(function(input) {
                input.closest('form').addEventListener('submit', function(e) {
                    const quantity = this.querySelector('input[name="quantity"]').value;
                    if (!confirm(`Confirmer l'ajout de ${quantity} unités au stock ?`)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>

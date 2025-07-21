<?php
/**
 * Titre: Module Matériel - Gestion Inventaire
 * Chemin: /public/materiel/inventaire.php
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
require_once __DIR__ . '/classes/MaterielManager.php';

// Variables pour template
$page_title = 'Inventaire Matériel';
$page_subtitle = 'Gestion complète de l\'inventaire';
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
    header('Location: ./index.php?error=access_denied');
    exit;
}

// Manager matériel
$materielManager = new MaterielManager();

// Gestion des actions
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['action']) {
            case 'add_item':
                $success = $materielManager->createItem($_POST);
                $message = $success ? 'Équipement ajouté avec succès' : 'Erreur lors de l\'ajout';
                break;
                
            case 'update_item':
                $success = $materielManager->updateItem($_POST['id'], $_POST);
                $message = $success ? 'Équipement mis à jour avec succès' : 'Erreur lors de la mise à jour';
                break;
                
            case 'delete_item':
                $success = $materielManager->deleteItem($_POST['id']);
                $message = $success ? 'Équipement supprimé avec succès' : 'Erreur lors de la suppression';
                break;
                
            case 'change_status':
                $success = $materielManager->updateItemStatus($_POST['id'], $_POST['statut']);
                $message = $success ? 'Statut mis à jour avec succès' : 'Erreur lors de la mise à jour du statut';
                break;
        }
    } catch (Exception $e) {
        $error = 'Erreur : ' . $e->getMessage();
    }
}

// Récupération des données
$filters = [
    'agence_id' => $_GET['agence'] ?? '',
    'categorie_id' => $_GET['categorie'] ?? '',
    'statut' => $_GET['statut'] ?? '',
    'etat' => $_GET['etat'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$items = $materielManager->getItemsFiltered($filters);
$categories = $materielManager->getCategories();
$agences = $materielManager->getAgences();
$templates = $materielManager->getTemplatesByCategory();

// Statistiques
$stats = [
    'total' => count($items),
    'disponible' => count(array_filter($items, fn($i) => $i['statut'] === 'disponible')),
    'attribue' => count(array_filter($items, fn($i) => $i['statut'] === 'attribue')),
    'maintenance' => count(array_filter($items, fn($i) => $i['statut'] === 'maintenance')),
    'reforme' => count(array_filter($items, fn($i) => $i['statut'] === 'reforme'))
];

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/'],
    ['icon' => '🔧', 'text' => 'Matériel', 'url' => './index.php'],
    ['icon' => '📋', 'text' => 'Inventaire', 'url' => '', 'active' => true]
];

include ROOT_PATH . '/templates/header.php';
?>

<div class="materiel-container">
    <!-- En-tête avec statistiques -->
    <div class="materiel-header">
        <div class="header-content">
            <div class="header-info">
                <h1>📋 Inventaire Matériel</h1>
                <p class="subtitle">Gestion complète de l'équipement et de l'outillage</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total équipements</div>
                </div>
                <div class="stat-card available">
                    <div class="stat-number"><?= $stats['disponible'] ?></div>
                    <div class="stat-label">Disponibles</div>
                </div>
                <div class="stat-card assigned">
                    <div class="stat-number"><?= $stats['attribue'] ?></div>
                    <div class="stat-label">Attribués</div>
                </div>
                <div class="stat-card maintenance">
                    <div class="stat-number"><?= $stats['maintenance'] ?></div>
                    <div class="stat-label">Maintenance</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Filtres et recherche -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filters-row">
                <div class="filter-group">
                    <label for="search">🔍 Recherche</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                           placeholder="Numéro, désignation, marque...">
                </div>
                
                <div class="filter-group">
                    <label for="agence">🏢 Agence</label>
                    <select id="agence" name="agence">
                        <option value="">Toutes les agences</option>
                        <?php foreach ($agences as $agence): ?>
                            <option value="<?= $agence['id'] ?>" <?= $filters['agence_id'] == $agence['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agence['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="categorie">📂 Catégorie</label>
                    <select id="categorie" name="categorie">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filters['categorie_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="statut">📊 Statut</label>
                    <select id="statut" name="statut">
                        <option value="">Tous les statuts</option>
                        <option value="disponible" <?= $filters['statut'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="attribue" <?= $filters['statut'] === 'attribue' ? 'selected' : '' ?>>Attribué</option>
                        <option value="maintenance" <?= $filters['statut'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        <option value="reforme" <?= $filters['statut'] === 'reforme' ? 'selected' : '' ?>>Réformé</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="?action=list" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Actions rapides -->
    <div class="actions-bar">
        <div class="actions-left">
            <button type="button" class="btn btn-success" onclick="showAddModal()">
                ➕ Ajouter un équipement
            </button>
            <button type="button" class="btn btn-info" onclick="exportInventory()">
                📊 Exporter (CSV)
            </button>
        </div>
        
        <div class="actions-right">
            <div class="view-toggle">
                <button type="button" class="btn btn-outline active" data-view="table">📋 Tableau</button>
                <button type="button" class="btn btn-outline" data-view="cards">🗃️ Cartes</button>
            </div>
        </div>
    </div>

    <!-- Tableau des équipements -->
    <div class="inventory-table-container" id="table-view">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>N° Inventaire</th>
                    <th>Désignation</th>
                    <th>Catégorie</th>
                    <th>Marque/Modèle</th>
                    <th>Agence</th>
                    <th>État</th>
                    <th>Statut</th>
                    <th>Acquisition</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr data-id="<?= $item['id'] ?>" class="status-<?= $item['statut'] ?>">
                        <td class="inventory-number">
                            <strong><?= htmlspecialchars($item['numero_inventaire']) ?></strong>
                            <?php if ($item['numero_serie']): ?>
                                <small>S/N: <?= htmlspecialchars($item['numero_serie']) ?></small>
                            <?php endif; ?>
                        </td>
                        
                        <td class="designation">
                            <div class="item-title"><?= htmlspecialchars($item['designation']) ?></div>
                            <?php if ($item['observations']): ?>
                                <small class="observations"><?= htmlspecialchars(substr($item['observations'], 0, 50)) ?><?= strlen($item['observations']) > 50 ? '...' : '' ?></small>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <span class="category-badge" style="background-color: <?= $item['categorie_couleur'] ?? '#666' ?>33">
                                <?= htmlspecialchars($item['categorie_nom']) ?>
                            </span>
                        </td>
                        
                        <td>
                            <?php if ($item['marque']): ?>
                                <strong><?= htmlspecialchars($item['marque']) ?></strong><br>
                            <?php endif; ?>
                            <?php if ($item['modele']): ?>
                                <small><?= htmlspecialchars($item['modele']) ?></small>
                            <?php endif; ?>
                        </td>
                        
                        <td><?= htmlspecialchars($item['agence_nom']) ?></td>
                        
                        <td>
                            <span class="etat-badge etat-<?= $item['etat'] ?>">
                                <?= ucfirst($item['etat']) ?>
                            </span>
                        </td>
                        
                        <td>
                            <span class="statut-badge statut-<?= $item['statut'] ?>">
                                <?= ucfirst($item['statut']) ?>
                            </span>
                        </td>
                        
                        <td>
                            <?php if ($item['date_acquisition']): ?>
                                <?= date('d/m/Y', strtotime($item['date_acquisition'])) ?>
                            <?php endif; ?>
                            <?php if ($item['prix_achat']): ?>
                                <small><?= number_format($item['prix_achat'], 2) ?>€</small>
                            <?php endif; ?>
                        </td>
                        
                        <td class="actions-cell">
                            <div class="actions-dropdown">
                                <button class="btn-actions" onclick="toggleActions(<?= $item['id'] ?>)">⋮</button>
                                <div class="actions-menu" id="actions-<?= $item['id'] ?>">
                                    <a href="#" onclick="editItem(<?= $item['id'] ?>)">✏️ Modifier</a>
                                    <a href="#" onclick="viewHistory(<?= $item['id'] ?>)">📋 Historique</a>
                                    <a href="#" onclick="printQR(<?= $item['id'] ?>)">🏷️ QR Code</a>
                                    <a href="#" onclick="changeStatus(<?= $item['id'] ?>, '<?= $item['statut'] ?>')">🔄 Changer statut</a>
                                    <?php if (in_array($user_role, ['admin', 'dev'])): ?>
                                        <a href="#" onclick="deleteItem(<?= $item['id'] ?>)" class="danger">🗑️ Supprimer</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>Aucun équipement trouvé</h3>
                <p>Aucun équipement ne correspond aux critères de recherche.</p>
                <button type="button" class="btn btn-primary" onclick="showAddModal()">Ajouter le premier équipement</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vue en cartes (masquée par défaut) -->
    <div class="inventory-cards-container" id="cards-view" style="display: none;">
        <div class="cards-grid">
            <?php foreach ($items as $item): ?>
                <div class="equipment-card status-<?= $item['statut'] ?>">
                    <div class="card-header">
                        <div class="card-number"><?= htmlspecialchars($item['numero_inventaire']) ?></div>
                        <div class="card-status">
                            <span class="statut-badge statut-<?= $item['statut'] ?>"><?= ucfirst($item['statut']) ?></span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h4 class="card-title"><?= htmlspecialchars($item['designation']) ?></h4>
                        <div class="card-details">
                            <div class="detail-row">
                                <span class="label">Catégorie:</span>
                                <span class="value"><?= htmlspecialchars($item['categorie_nom']) ?></span>
                            </div>
                            <?php if ($item['marque']): ?>
                                <div class="detail-row">
                                    <span class="label">Marque:</span>
                                    <span class="value"><?= htmlspecialchars($item['marque']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="label">Agence:</span>
                                <span class="value"><?= htmlspecialchars($item['agence_nom']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button class="btn btn-sm btn-outline" onclick="editItem(<?= $item['id'] ?>)">✏️ Modifier</button>
                        <button class="btn btn-sm btn-outline" onclick="viewHistory(<?= $item['id'] ?>)">📋 Détails</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal d'ajout/modification -->
<div class="modal" id="itemModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter un équipement</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="itemForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add_item">
            <input type="hidden" name="id" id="itemId">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="template_id">Modèle d'équipement *</label>
                        <select id="template_id" name="template_id" required>
                            <option value="">Sélectionner un modèle</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?= $template['id'] ?>" 
                                        data-designation="<?= htmlspecialchars($template['designation']) ?>"
                                        data-marque="<?= htmlspecialchars($template['marque']) ?>"
                                        data-modele="<?= htmlspecialchars($template['modele']) ?>">
                                    <?= htmlspecialchars($template['categorie_nom']) ?> - <?= htmlspecialchars($template['designation']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="agence_id">Agence *</label>
                        <select id="agence_id" name="agence_id" required>
                            <option value="">Sélectionner une agence</option>
                            <?php foreach ($agences as $agence): ?>
                                <option value="<?= $agence['id'] ?>"><?= htmlspecialchars($agence['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_serie">Numéro de série</label>
                        <input type="text" id="numero_serie" name="numero_serie" placeholder="Optionnel">
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_inventaire">N° Inventaire</label>
                        <input type="text" id="numero_inventaire" name="numero_inventaire" placeholder="Auto-généré si vide">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="etat">État</label>
                        <select id="etat" name="etat">
                            <option value="neuf">Neuf</option>
                            <option value="bon">Bon état</option>
                            <option value="use">Usé</option>
                            <option value="defaillant">Défaillant</option>
                            <option value="reforme">À réformer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut">
                            <option value="disponible">Disponible</option>
                            <option value="attribue">Attribué</option>
                            <option value="maintenance">En maintenance</option>
                            <option value="reforme">Réformé</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_acquisition">Date d'acquisition</label>
                        <input type="date" id="date_acquisition" name="date_acquisition" value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="prix_achat">Prix d'achat (€)</label>
                        <input type="number" id="prix_achat" name="prix_achat" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="observations">Observations</label>
                    <textarea id="observations" name="observations" rows="3" placeholder="Commentaires, notes particulières..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Styles spécifiques à l'inventaire */
.materiel-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.materiel-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 30px;
}

.header-info h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.subtitle {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.15);
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    backdrop-filter: blur(10px);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.filters-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filters-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
    gap: 20px;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #374151;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.actions-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-primary { background: #3b82f6; color: white; }
.btn-success { background: #10b981; color: white; }
.btn-info { background: #06b6d4; color: white; }
.btn-secondary { background: #6b7280; color: white; }
.btn-outline { background: white; color: #374151; border: 1px solid #d1d5db; }

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.inventory-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.inventory-table th {
    background: #f8fafc;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.inventory-table td {
    padding: 15px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.inventory-table tr:hover {
    background-color: #f9fafb;
}

.inventory-number strong {
    font-size: 1.1rem;
    color: #1f2937;
}

.inventory-number small {
    display: block;
    color: #6b7280;
    font-size: 0.8rem;
    margin-top: 2px;
}

.item-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.observations {
    color: #6b7280;
    font-style: italic;
}

.category-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    border: 1px solid rgba(0,0,0,0.1);
}

.etat-badge, .statut-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.etat-neuf { background: #dcfce7; color: #166534; }
.etat-bon { background: #dbeafe; color: #1e40af; }
.etat-use { background: #fef3c7; color: #d97706; }
.etat-defaillant { background: #fee2e2; color: #dc2626; }
.etat-reforme { background: #f3f4f6; color: #6b7280; }

.statut-disponible { background: #dcfce7; color: #166534; }
.statut-attribue { background: #dbeafe; color: #1e40af; }
.statut-maintenance { background: #fef3c7; color: #d97706; }
.statut-reforme { background: #f3f4f6; color: #6b7280; }

.actions-dropdown {
    position: relative;
    display: inline-block;
}

.btn-actions {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 1.2rem;
}

.actions-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 100;
    min-width: 150px;
}

.actions-menu a {
    display: block;
    padding: 10px 15px;
    color: #374151;
    text-decoration: none;
    font-size: 0.9rem;
    border-bottom: 1px solid #f3f4f6;
}

.actions-menu a:hover {
    background: #f9fafb;
}

.actions-menu a.danger {
    color: #dc2626;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.equipment-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.equipment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.card-header {
    padding: 15px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-number {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1f2937;
}

.card-body {
    padding: 20px;
}

.card-title {
    margin: 0 0 15px 0;
    font-size: 1.2rem;
    color: #1f2937;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.detail-row .label {
    color: #6b7280;
    font-weight: 500;
}

.detail-row .value {
    color: #1f2937;
    font-weight: 600;
}

.card-footer {
    padding: 15px;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #374151;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 30px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background: white;
    margin: 50px auto;
    width: 90%;
    max-width: 600px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 20px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filters-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .actions-bar {
        flex-direction: column;
        gap: 15px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .cards-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Fonctions JavaScript pour l'inventaire
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter un équipement';
    document.getElementById('formAction').value = 'add_item';
    document.getElementById('itemId').value = '';
    document.getElementById('itemForm').reset();
    document.getElementById('itemModal').style.display = 'block';
}

function editItem(itemId) {
    // Ici vous pourriez charger les données de l'item via AJAX
    document.getElementById('modalTitle').textContent = 'Modifier l\'équipement';
    document.getElementById('formAction').value = 'update_item';
    document.getElementById('itemId').value = itemId;
    document.getElementById('itemModal').style.display = 'block';
    
    // TODO: Charger les données existantes via AJAX
}

function closeModal() {
    document.getElementById('itemModal').style.display = 'none';
}

function toggleActions(itemId) {
    const menu = document.getElementById('actions-' + itemId);
    const allMenus = document.querySelectorAll('.actions-menu');
    
    // Fermer tous les autres menus
    allMenus.forEach(m => {
        if (m !== menu) m.style.display = 'none';
    });
    
    // Toggle le menu actuel
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function viewHistory(itemId) {
    // TODO: Afficher l'historique de l'équipement
    alert('Historique de l\'équipement #' + itemId);
}

function printQR(itemId) {
    // TODO: Générer et imprimer le QR code
    window.open('qr_code.php?item_id=' + itemId, '_blank');
}

function changeStatus(itemId, currentStatus) {
    const newStatus = prompt('Nouveau statut (disponible, attribue, maintenance, reforme):', currentStatus);
    if (newStatus && newStatus !== currentStatus) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="change_status">
            <input type="hidden" name="id" value="${itemId}">
            <input type="hidden" name="statut" value="${newStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteItem(itemId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet équipement ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="id" value="${itemId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportInventory() {
    // TODO: Export CSV de l'inventaire
    window.open('export.php?type=inventory', '_blank');
}

// Toggle entre vue tableau et cartes
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('[data-view]');
    const tableView = document.getElementById('table-view');
    const cardsView = document.getElementById('cards-view');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Mise à jour des boutons
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Affichage des vues
            if (view === 'table') {
                tableView.style.display = 'block';
                cardsView.style.display = 'none';
            } else {
                tableView.style.display = 'none';
                cardsView.style.display = 'block';
            }
        });
    });
    
    // Fermer les menus d'actions en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.actions-dropdown')) {
            document.querySelectorAll('.actions-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
    
    // Fermer modal en cliquant à l'extérieur
    document.getElementById('itemModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
});
</script>

<?php include ROOT_PATH . '/templates/footer.php'; ?>

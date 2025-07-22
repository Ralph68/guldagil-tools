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

// Vérification authentification et permissions
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = $current_user['role'] ?? 'guest';

// Permissions admin uniquement
$can_admin_materiel = in_array($user_role, ['admin', 'dev']);
if (!$can_admin_materiel) {
    header('Location: ../index.php?error=access_denied');
    exit;
}

// Manager matériel
$materielManager = new MaterielManager();

// Gestion des actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$message = '';
$error = '';

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
                
            case 'create_template':
                $success = $materielManager->createTemplate($_POST);
                $message = $success ? 'Modèle d\'équipement créé avec succès' : 'Erreur lors de la création';
                break;
                
            case 'create_agence':
                $success = $materielManager->createAgence($_POST);
                $message = $success ? 'Agence créée avec succès' : 'Erreur lors de la création';
                break;
                
            case 'create_profil':
                $success = $materielManager->createProfil($_POST);
                $message = $success ? 'Profil créé avec succès' : 'Erreur lors de la création';
                break;
                
            case 'validate_request':
                $success = $materielManager->validateRequest($_POST['request_id'], $_POST['decision'], $_POST['comments'] ?? '');
                $message = $success ? 'Demande traitée avec succès' : 'Erreur lors du traitement';
                break;
                
            case 'bulk_import':
                $success = processBulkImport($_FILES['import_file']);
                $message = $success ? 'Import effectué avec succès' : 'Erreur lors de l\'import';
                break;
                
            case 'maintenance_schedule':
                $success = $materielManager->scheduleMaintenance($_POST);
                $message = $success ? 'Maintenance programmée avec succès' : 'Erreur lors de la programmation';
                break;
        }
    } catch (Exception $e) {
        $error = 'Erreur : ' . $e->getMessage();
    }
}

// Récupération des données pour l'admin
$stats = $materielManager->getStatistiquesGenerales();
$categories = $materielManager->getCategories();
$templates = $materielManager->getTemplatesByCategory();
$agences = $materielManager->getAgences();
$profils = $materielManager->getProfils();
$pending_requests = $materielManager->getDemandesEnAttente();
$maintenance_alerts = $materielManager->getMaintenanceAlerts();

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/'],
    ['icon' => '🔧', 'text' => 'Matériel', 'url' => '../index.php'],
    ['icon' => '⚙️', 'text' => 'Administration', 'url' => '', 'active' => true]
];

include ROOT_PATH . '/templates/header.php';
?>

<div class="admin-container">
    <!-- En-tête administration -->
    <div class="admin-header">
        <div class="header-content">
            <div class="header-info">
                <h1>⚙️ Administration Matériel</h1>
                <p class="subtitle">Configuration et gestion avancée du module</p>
            </div>
            
            <div class="admin-status">
                <div class="status-indicator">
                    <div class="status-dot <?= $stats['total_outils'] > 0 ? 'online' : 'offline' ?>"></div>
                    <span>Module <?= $stats['total_outils'] > 0 ? 'Actif' : 'Inactif' ?></span>
                </div>
                <div class="last-sync">
                    Dernière sync: <?= date('H:i') ?>
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

    <!-- Navigation des sections -->
    <div class="admin-nav">
        <button class="nav-tab active" data-section="dashboard">📊 Tableau de bord</button>
        <button class="nav-tab" data-section="categories">📂 Catégories</button>
        <button class="nav-tab" data-section="templates">🔧 Modèles</button>
        <button class="nav-tab" data-section="requests">📝 Demandes</button>
        <button class="nav-tab" data-section="maintenance">🔧 Maintenance</button>
        <button class="nav-tab" data-section="import">📤 Import/Export</button>
        <button class="nav-tab" data-section="settings">⚙️ Paramètres</button>
    </div>

    <!-- Section Tableau de bord -->
    <div class="admin-section active" id="dashboard">
        <h2>📊 Tableau de bord administrateur</h2>
        
        <div class="dashboard-grid">
            <!-- Statistiques rapides -->
            <div class="stats-card">
                <h3>📈 Statistiques globales</h3>
                <div class="stats-items">
                    <div class="stat-item">
                        <div class="stat-label">Total équipements</div>
                        <div class="stat-value"><?= number_format($stats['total_outils']) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Catégories</div>
                        <div class="stat-value"><?= count($categories) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Modèles</div>
                        <div class="stat-value"><?= count($templates) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Agences</div>
                        <div class="stat-value"><?= count($agences) ?></div>
                    </div>
                </div>
            </div>

            <!-- Alertes et notifications -->
            <div class="alerts-card">
                <h3>🚨 Alertes importantes</h3>
                <div class="alerts-list">
                    <?php if ($stats['maintenance_due'] > 0): ?>
                        <div class="alert-item warning">
                            <div class="alert-icon">🔧</div>
                            <div class="alert-content">
                                <div class="alert-title">Maintenance requise</div>
                                <div class="alert-desc"><?= $stats['maintenance_due'] ?> équipement(s) nécessitent une maintenance</div>
                            </div>
                            <button class="alert-action" onclick="switchSection('maintenance')">Voir</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($stats['demandes_attente'] > 5): ?>
                        <div class="alert-item info">
                            <div class="alert-icon">📝</div>
                            <div class="alert-content">
                                <div class="alert-title">Demandes en attente</div>
                                <div class="alert-desc"><?= $stats['demandes_attente'] ?> demandes nécessitent validation</div>
                            </div>
                            <button class="alert-action" onclick="switchSection('requests')">Traiter</button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert-item success">
                        <div class="alert-icon">✅</div>
                        <div class="alert-content">
                            <div class="alert-title">Système opérationnel</div>
                            <div class="alert-desc">Tous les services fonctionnent normalement</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="quick-actions-card">
                <h3>⚡ Actions rapides</h3>
                <div class="actions-grid">
                    <button class="action-btn" onclick="showModal('addCategoryModal')">
                        <div class="action-icon">📂</div>
                        <span>Nouvelle catégorie</span>
                    </button>
                    <button class="action-btn" onclick="showModal('addTemplateModal')">
                        <div class="action-icon">🔧</div>
                        <span>Nouveau modèle</span>
                    </button>
                    <button class="action-btn" onclick="showModal('bulkImportModal')">
                        <div class="action-icon">📤</div>
                        <span>Import en lot</span>
                    </button>
                    <button class="action-btn" onclick="generateReport()">
                        <div class="action-icon">📊</div>
                        <span>Rapport complet</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Catégories -->
    <div class="admin-section" id="categories">
        <div class="section-header">
            <h2>📂 Gestion des catégories</h2>
            <button class="btn btn-primary" onclick="showModal('addCategoryModal')">➕ Nouvelle catégorie</button>
        </div>
        
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card" data-id="<?= $category['id'] ?>">
                    <div class="category-header" style="background-color: <?= $category['couleur'] ?? '#3b82f6' ?>22">
                        <div class="category-icon" style="color: <?= $category['couleur'] ?? '#3b82f6' ?>">
                            <?= $category['icone'] ?? '📦' ?>
                        </div>
                        <div class="category-actions">
                            <button onclick="editCategory(<?= $category['id'] ?>)">✏️</button>
                            <button onclick="deleteCategory(<?= $category['id'] ?>)">🗑️</button>
                        </div>
                    </div>
                    <div class="category-content">
                        <h4><?= htmlspecialchars($category['nom']) ?></h4>
                        <p class="category-type"><?= ucfirst($category['type']) ?></p>
                        <?php if ($category['description']): ?>
                            <p class="category-desc"><?= htmlspecialchars($category['description']) ?></p>
                        <?php endif; ?>
                        <div class="category-stats">
                            <span class="stat-badge"><?= $category['total_templates'] ?? 0 ?> modèles</span>
                            <span class="stat-badge"><?= $category['total_items'] ?? 0 ?> items</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section Modèles -->
    <div class="admin-section" id="templates">
        <div class="section-header">
            <h2>🔧 Modèles d'équipements</h2>
            <button class="btn btn-primary" onclick="showModal('addTemplateModal')">➕ Nouveau modèle</button>
        </div>
        
        <div class="templates-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th>Catégorie</th>
                        <th>Marque/Modèle</th>
                        <th>Prix unitaire</th>
                        <th>Maintenance</th>
                        <th>Items actifs</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($template['designation']) ?></strong>
                                <?php if ($template['reference']): ?>
                                    <small>Réf: <?= htmlspecialchars($template['reference']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="category-badge" style="background-color: <?= $template['categorie_couleur'] ?? '#3b82f6' ?>22">
                                    <?= htmlspecialchars($template['categorie_nom']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($template['marque']): ?>
                                    <strong><?= htmlspecialchars($template['marque']) ?></strong><br>
                                <?php endif; ?>
                                <?php if ($template['modele']): ?>
                                    <small><?= htmlspecialchars($template['modele']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($template['prix_unitaire']): ?>
                                    <?= number_format($template['prix_unitaire'], 2) ?>€
                                <?php else: ?>
                                    <span class="text-muted">Non défini</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="maintenance-badge <?= $template['maintenance_requise'] ? 'required' : 'none' ?>">
                                    <?= $template['maintenance_requise'] ? 'Requise' : 'Aucune' ?>
                                </span>
                            </td>
                            <td>
                                <span class="items-count"><?= $template['items_count'] ?? 0 ?></span>
                            </td>
                            <td class="actions-cell">
                                <div class="table-actions">
                                    <button onclick="editTemplate(<?= $template['id'] ?>)" title="Modifier">✏️</button>
                                    <button onclick="duplicateTemplate(<?= $template['id'] ?>)" title="Dupliquer">📋</button>
                                    <button onclick="viewTemplateItems(<?= $template['id'] ?>)" title="Voir les items">👁️</button>
                                    <button onclick="deleteTemplate(<?= $template['id'] ?>)" title="Supprimer" class="danger">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section Demandes -->
    <div class="admin-section" id="requests">
        <div class="section-header">
            <h2>📝 Validation des demandes</h2>
            <div class="filters">
                <select id="requestsFilter" onchange="filterRequests()">
                    <option value="all">Toutes les demandes</option>
                    <option value="en_attente">En attente</option>
                    <option value="urgent">Urgentes</option>
                    <option value="today">Aujourd'hui</option>
                </select>
            </div>
        </div>
        
        <div class="requests-container">
            <?php foreach ($pending_requests as $request): ?>
                <div class="request-card <?= $request['urgence'] ?>" data-id="<?= $request['id'] ?>">
                    <div class="request-header">
                        <div class="request-info">
                            <h4>Demande #<?= $request['id'] ?></h4>
                            <span class="request-type"><?= ucfirst($request['type_demande']) ?></span>
                            <span class="urgence-badge <?= $request['urgence'] ?>"><?= ucfirst($request['urgence']) ?></span>
                        </div>
                        <div class="request-date">
                            <?= date('d/m/Y H:i', strtotime($request['created_at'])) ?>
                        </div>
                    </div>
                    
                    <div class="request-content">
                        <div class="request-details">
                            <div class="detail-row">
                                <strong>Demandeur:</strong> <?= htmlspecialchars($request['demandeur']) ?>
                            </div>
                            <div class="detail-row">
                                <strong>Équipement:</strong> <?= htmlspecialchars($request['designation']) ?>
                            </div>
                            <?php if ($request['quantite_demandee'] > 1): ?>
                                <div class="detail-row">
                                    <strong>Quantité:</strong> <?= $request['quantite_demandee'] ?>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <strong>Justification:</strong> <?= htmlspecialchars($request['justification']) ?>
                            </div>
                        </div>
                        
                        <div class="request-actions">
                            <button class="btn btn-success" onclick="validateRequest(<?= $request['id'] ?>, 'approve')">
                                ✅ Approuver
                            </button>
                            <button class="btn btn-warning" onclick="validateRequest(<?= $request['id'] ?>, 'modify')">
                                ✏️ Modifier
                            </button>
                            <button class="btn btn-danger" onclick="validateRequest(<?= $request['id'] ?>, 'reject')">
                                ❌ Rejeter
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($pending_requests)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>Aucune demande en attente</h3>
                    <p>Toutes les demandes ont été traitées.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<div class="profil-info">
                                <strong><?= htmlspecialchars($profil['nom']) ?></strong>
                                <small><?= htmlspecialchars($profil['description']) ?></small>
                            </div>
                            <div class="profil-level">
                                <span class="level-badge <?= $profil['niveau_acces'] ?>">
                                    <?= ucfirst($profil['niveau_acces']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-secondary" onclick="showModal('addProfilModal')">➕ Nouveau profil</button>
            </div>
            
            <div class="settings-card">
                <h3>🔧 Configuration système</h3>
                <form class="config-form">
                    <div class="form-group">
                        <label>Délai d'alerte maintenance (jours)</label>
                        <input type="number" value="30" min="1" max="365">
                    </div>
                    <div class="form-group">
                        <label>Auto-validation demandes normales</label>
                        <input type="checkbox">
                    </div>
                    <div class="form-group">
                        <label>Notifications par email</label>
                        <input type="checkbox" checked>
                    </div>
                    <div class="form-group">
                        <label>Génération automatique QR codes</label>
                        <input type="checkbox" checked>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Modal Nouvelle catégorie -->
<div class="modal" id="addCategoryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📂 Nouvelle catégorie</h3>
            <button class="modal-close" onclick="closeModal('addCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_category">
            <div class="modal-body">
                <div class="form-group">
                    <label for="cat_nom">Nom de la catégorie *</label>
                    <input type="text" id="cat_nom" name="nom" required>
                </div>
                <div class="form-group">
                    <label for="cat_type">Type *</label>
                    <select id="cat_type" name="type" required>
                        <option value="outillage">Outillage manuel</option>
                        <option value="electroportatif">Électroportatif</option>
                        <option value="epi">EPI</option>
                        <option value="materiel">Matériel général</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cat_icone">Icône</label>
                    <input type="text" id="cat_icone" name="icone" placeholder="🔧">
                </div>
                <div class="form-group">
                    <label for="cat_couleur">Couleur</label>
                    <input type="color" id="cat_couleur" name="couleur" value="#3b82f6">
                </div>
                <div class="form-group">
                    <label for="cat_description">Description</label>
                    <textarea id="cat_description" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModal')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nouveau modèle -->
<div class="modal" id="addTemplateModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🔧 Nouveau modèle d'équipement</h3>
            <button class="modal-close" onclick="closeModal('addTemplateModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_template">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="tpl_categorie">Catégorie *</label>
                        <select id="tpl_categorie" name="categorie_id" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tpl_designation">Désignation *</label>
                        <input type="text" id="tpl_designation" name="designation" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="tpl_marque">Marque</label>
                        <input type="text" id="tpl_marque" name="marque">
                    </div>
                    <div class="form-group">
                        <label for="tpl_modele">Modèle</label>
                        <input type="text" id="tpl_modele" name="modele">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="tpl_reference">Référence</label>
                        <input type="text" id="tpl_reference" name="reference">
                    </div>
                    <div class="form-group">
                        <label for="tpl_prix">Prix unitaire (€)</label>
                        <input type="number" id="tpl_prix" name="prix_unitaire" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="maintenance_requise" value="1">
                        Maintenance requise
                    </label>
                </div>
                <div class="form-group">
                    <label for="tpl_observations">Observations</label>
                    <textarea id="tpl_observations" name="observations" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addTemplateModal')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Styles spécifiques à l'administration */
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
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

.admin-status {
    text-align: right;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-dot.online {
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
}

.status-dot.offline {
    background: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3);
}

.last-sync {
    font-size: 0.85rem;
    opacity: 0.8;
}

.admin-nav {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    background: white;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow-x: auto;
}

.nav-tab {
    padding: 12px 20px;
    border: none;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    color: #6b7280;
    transition: all 0.2s;
    white-space: nowrap;
}

.nav-tab:hover {
    background: #f3f4f6;
    color: #374151;
}

.nav-tab.active {
    background: #3b82f6;
    color: white;
}

.admin-section {
    display: none;
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.admin-section.active {
    display: block;
}

.admin-section h2 {
    margin: 0 0 25px 0;
    color: #1f2937;
    font-size: 1.8rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

.stats-card, .alerts-card, .quick-actions-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
}

.stats-card h3, .alerts-card h3, .quick-actions-card h3 {
    margin: 0 0 15px 0;
    color: #1f2937;
    font-size: 1.2rem;
}

.stats-items {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.stat-item {
    text-align: center;
}

.stat-label {
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #3b82f6;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid;
}

.alert-item.warning {
    background: #fef3c7;
    border-left-color: #f59e0b;
}

.alert-item.info {
    background: #dbeafe;
    border-left-color: #3b82f6;
}

.alert-item.success {
    background: #dcfce7;
    border-left-color: #10b981;
}

.alert-icon {
    font-size: 1.5rem;
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.alert-desc {
    font-size: 0.9rem;
    color: #6b7280;
}

.alert-action {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
}

.actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    border-color: #3b82f6;
    background: #f0f9ff;
}

.action-icon {
    font-size: 2rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.category-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.category-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category-icon {
    font-size: 2rem;
}

.category-actions {
    display: flex;
    gap: 8px;
}

.category-actions button {
    background: rgba(255,255,255,0.9);
    border: none;
    padding: 6px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.category-actions button:hover {
    background: white;
}

.category-content {
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
}

.category-content h4 {
    margin: 0 0 8px 0;
    color: #1f2937;
}

.category-type {
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.category-desc {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.category-stats {
    display: flex;
    gap: 10px;
}

.stat-badge {
    background: #f3f4f6;
    color: #374151;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.admin-table th {
    background: #f8fafc;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.admin-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.admin-table tr:hover {
    background: #f9fafb;
}

.category-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.maintenance-badge.required {
    background: #fee2e2;
    color: #dc2626;
}

.maintenance-badge.none {
    background: #f3f4f6;
    color: #6b7280;
}

.table-actions {
    display: flex;
    gap: 8px;
}

.table-actions button {
    background: #f3f4f6;
    border: none;
    padding: 6px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.table-actions button:hover {
    background: #e5e7eb;
}

.table-actions button.danger:hover {
    background: #fee2e2;
}

.request-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 15px;
    overflow: hidden;
}

.request-card.urgente {
    border-left: 4px solid #f59e0b;
}

.request-card.critique {
    border-left: 4px solid #ef4444;
}

.request-header {
    padding: 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.request-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.request-info h4 {
    margin: 0;
    color: #1f2937;
}

.request-type {
    background: #e5e7eb;
    color: #374151;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.urgence-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.urgence-badge.normale {
    background: #dcfce7;
    color: #166534;
}

.urgence-badge.urgente {
    background: #fef3c7;
    color: #d97706;
}

.urgence-badge.critique {
    background: #fee2e2;
    color: #dc2626;
}

.request-content {
    padding: 20px;
}

.request-details {
    margin-bottom: 20px;
}

.detail-row {
    margin-bottom: 10px;
    line-height: 1.5;
}

.request-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.maintenance-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.maintenance-tab {
    padding: 10px 20px;
    border: none;
    background: #f3f4f6;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    color: #6b7280;
    transition: all 0.2s;
}

.maintenance-tab.active {
    background: #3b82f6;
    color: white;
}

.maintenance-panel {
    display: none;
}

.maintenance-panel.active {
    display: block;
}

.maintenance-alert {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 10px;
}

.maintenance-alert.urgent {
    border-left: 4px solid #ef4444;
}

.maintenance-alert.warning {
    border-left: 4px solid #f59e0b;
}

.alert-details h4 {
    margin: 0 0 5px 0;
    color: #1f2937;
}

.alert-details p {
    margin: 0 0 5px 0;
    color: #6b7280;
}

.alert-details small {
    color: #6b7280;
    font-size: 0.85rem;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    margin-top: 15px;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    position: relative;
}

.calendar-day.today {
    background: #3b82f6;
    color: white;
}

.day-number {
    font-weight: 600;
}

.maintenance-scheduled {
    position: absolute;
    bottom: 2px;
    right: 2px;
    font-size: 0.7rem;
}

.import-export-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.import-card, .export-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 25px;
}

.import-card h3, .export-card h3 {
    margin: 0 0 15px 0;
    color: #1f2937;
}

.import-area {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 20px;
}

.import-area:hover {
    border-color: #3b82f6;
    background: #f0f9ff;
}

.import-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.6;
}

.import-text strong {
    display: block;
    margin-bottom: 5px;
    color: #1f2937;
}

.import-text small {
    color: #6b7280;
}

.import-templates h4 {
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 1rem;
}

.template-link {
    display: inline-block;
    margin-right: 15px;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}

.template-link:hover {
    text-decoration: underline;
}

.export-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.export-btn {
    padding: 12px 20px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s;
    font-weight: 600;
}

.export-btn:hover {
    border-color: #3b82f6;
    background: #f0f9ff;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.settings-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 25px;
}

.settings-card h3 {
    margin: 0 0 20px 0;
    color: #1f2937;
}

.agences-list, .profils-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.agence-item, .profil-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.agence-info strong, .profil-info strong {
    display: block;
    color: #1f2937;
}

.agence-info small, .profil-info small {
    color: #6b7280;
}

.agence-actions, .profil-actions {
    display: flex;
    gap: 8px;
}

.agence-actions button, .profil-actions button {
    background: #f3f4f6;
    border: none;
    padding: 6px;
    border-radius: 4px;
    cursor: pointer;
}

.level-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.level-badge.lecture {
    background: #e5e7eb;
    color: #374151;
}

.level-badge.ecriture {
    background: #dbeafe;
    color: #1e40af;
}

.level-badge.admin {
    background: #fee2e2;
    color: #dc2626;
}

.config-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #374151;
}

.form-group input, .form-group select, .form-group textarea {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
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

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.success {
    background: #dcfce7;
    color: #166534;
}

.status-badge.completed {
    background: #dcfce7;
    color: #166534;
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 20px;
    }
    
    .admin-nav {
        flex-direction: column;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .import-export-grid {
        grid-template-columns: 1fr;
    }
    
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Variables globales
let currentSection = 'dashboard';

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    setupNavigation();
    setupMaintenanceTabs();
});

// Navigation entre sections
function setupNavigation() {
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const section = this.dataset.section;
            switchSection(section);
        });
    });
}

function switchSection(sectionId) {
    // Mettre à jour les onglets
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
    
    // Mettre à jour les sections
    document.querySelectorAll('.admin-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId).classList.add('active');
    
    currentSection = sectionId;
}

// Onglets de maintenance
function setupMaintenanceTabs() {
    document.querySelectorAll('.maintenance-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Mettre à jour les onglets
            document.querySelectorAll('.maintenance-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Mettre à jour les panneaux
            document.querySelectorAll('.maintenance-panel').forEach(panel => panel.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
        });
    });
}

// Modals
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Fermer modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// Gestion des catégories
function editCategory(categoryId) {
    console.log('Éditer catégorie:', categoryId);
    // TODO: Charger les données de la catégorie et afficher le modal d'édition
}

function deleteCategory(categoryId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        // TODO: Appel AJAX pour suppression
        console.log('Supprimer catégorie:', categoryId);
    }
}

// Gestion des modèles
function editTemplate(templateId) {
    console.log('Éditer modèle:', templateId);
    // TODO: Afficher modal d'édition avec données pré-remplies
}

function duplicateTemplate(templateId) {
    console.log('Dupliquer modèle:', templateId);
    // TODO: Créer un nouveau modèle basé sur l'existant
}

function viewTemplateItems(templateId) {
    console.log('Voir items du modèle:', templateId);
    // TODO: Afficher la liste des items utilisant ce modèle
}

function deleteTemplate(templateId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?')) {
        console.log('Supprimer modèle:', templateId);
        // TODO: Vérifier s'il y a des items liés avant suppression
    }
}

// Gestion des demandes
function validateRequest(requestId, action) {
    let message = '';
    switch (action) {
        case 'approve':
            message = 'Approuver cette demande ?';
            break;
        case 'modify':
            message = 'Modifier cette demande ?';
            break;
        case 'reject':
            message = 'Rejeter cette demande ?';
            break;
    }
    
    if (confirm(message)) {
        // TODO: Traitement de la demande via formulaire ou AJAX
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="validate_request">
            <input type="hidden" name="request_id" value="${requestId}">
            <input type="hidden" name="decision" value="${action}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterRequests() {
    const filter = document.getElementById('requestsFilter').value;
    console.log('Filtrer demandes:', filter);
    
    // Filtrage côté client pour l'instant
    const cards = document.querySelectorAll('.request-card');
    cards.forEach(card => {
        let show = true;
        
        switch (filter) {
            case 'en_attente':
                // Déjà filtré côté serveur
                break;
            case 'urgent':
                show = card.classList.contains('urgente') || card.classList.contains('critique');
                break;
            case 'today':
                const dateText = card.querySelector('.request-date').textContent;
                const today = new Date().toLocaleDateString('fr-FR');
                show = dateText.includes(today.split(' ')[0]);
                break;
        }
        
        card.style.display = show ? 'block' : 'none';
    });
}

// Gestion de la maintenance
function scheduleMaintenance(itemId) {
    console.log('Programmer maintenance pour item:', itemId);
    // TODO: Afficher modal de programmation de maintenance
    showModal('scheduleMaintenanceModal');
}

function postponeMaintenance(itemId) {
    const days = prompt('Reporter de combien de jours ?', '7');
    if (days && !isNaN(days)) {
        console.log('Reporter maintenance pour item:', itemId, 'de', days, 'jours');
        // TODO: Mettre à jour la date de maintenance
    }
}

// Import/Export
function handleFileImport(input) {
    const file = input.files[0];
    if (file) {
        console.log('Fichier sélectionné:', file.name, 'Taille:', file.size);
        
        // Vérifications basiques
        const allowedTypes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!allowedTypes.includes(file.type)) {
            alert('Type de fichier non supporté. Utilisez CSV ou Excel (.xlsx)');
            input.value = '';
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB
            alert('Fichier trop volumineux. Maximum 10MB.');
            input.value = '';
            return;
        }
        
        // TODO: Traitement du fichier
        const formData = new FormData();
        formData.append('import_file', file);
        formData.append('action', 'bulk_import');
        
        // Simulation d'upload
        console.log('Début de l\'import...');
        // fetch() ou soumission de formulaire
    }
}

function exportData(type) {
    console.log('Export type:', type);
    
    const exportUrls = {
        'inventory': '?export=inventory_csv',
        'requests': '?export=requests_csv',
        'maintenance': '?export=maintenance_pdf',
        'stats': '?export=stats_pdf'
    };
    
    if (exportUrls[type]) {
        window.open(exportUrls[type], '_blank');
    }
}

function viewImportDetails(importId) {
    console.log('Voir détails import:', importId);
    // TODO: Afficher modal avec détails de l'import
}

function downloadImportLog(importId) {
    console.log('Télécharger log import:', importId);
    window.open(`?download_log=${importId}`, '_blank');
}

// Paramètres
function editAgence(agenceId) {
    console.log('Éditer agence:', agenceId);
    // TODO: Modal d'édition d'agence
}

function deleteAgence(agenceId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette agence ?')) {
        console.log('Supprimer agence:', agenceId);
        // TODO: Vérifier s'il y a des équipements liés
    }
}

// Actions rapides
function generateReport() {
    console.log('Génération rapport complet...');
    // Redirection vers la page de rapports
    window.open('../reports/', '_blank');
}

// Sauvegarde automatique des paramètres
document.querySelectorAll('.config-form input, .config-form select').forEach(input => {
    input.addEventListener('change', function() {
        // TODO: Sauvegarde automatique via AJAX
        console.log('Paramètre modifié:', this.name || this.type, this.value || this.checked);
        
        // Indication visuelle de sauvegarde
        const indicator = document.createElement('span');
        indicator.textContent = ' ✓';
        indicator.style.color = '#10b981';
        indicator.style.fontSize = '0.8rem';
        
        // Nettoyer les anciens indicateurs
        const existingIndicator = this.parentNode.querySelector('span');
        if (existingIndicator && existingIndicator.textContent === ' ✓') {
            existingIndicator.remove();
        }
        
        this.parentNode.appendChild(indicator);
        
        // Supprimer l'indicateur après 2 secondes
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.remove();
            }
        }, 2000);
    });
});

// Gestion des erreurs globales
window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
    // TODO: Rapport d'erreur optionnel
});

// Gestion du beforeunload pour les formulaires modifiés
let hasUnsavedChanges = false;

document.querySelectorAll('form input, form select, form textarea').forEach(field => {
    field.addEventListener('change', function() {
        hasUnsavedChanges = true;
    });
});

document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        hasUnsavedChanges = false;
    });
});

window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Auto-refresh des alertes (optionnel)
function refreshAlerts() {
    // TODO: Actualiser les alertes de maintenance via AJAX
    console.log('Actualisation des alertes...');
}

// Actualisation automatique toutes les 5 minutes
setInterval(refreshAlerts, 5 * 60 * 1000);
</script>

<?php
// Fonctions utilitaires

function processBulkImport($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $uploadDir = ROOT_PATH . '/storage/imports/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = time() . '_' . $file['name'];
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return false;
    }
    
    // TODO: Traitement du fichier CSV/Excel
    // - Parse le fichier
    // - Valide les données
    // - Insert en base
    // - Génère un rapport
    
    return true;
}

// Fonctions d'export (à implémenter)
function exportInventoryCSV() {
    // TODO: Export CSV inventaire
}

function exportRequestsCSV() {
    // TODO: Export CSV demandes
}

function exportMaintenancePDF() {
    // TODO: Export PDF planning maintenance
}

function exportStatsPDF() {
    // TODO: Export PDF statistiques
}

include ROOT_PATH . '/templates/footer.php';
?><!-- Section Maintenance -->
    <div class="admin-section" id="maintenance">
        <div class="section-header">
            <h2>🔧 Gestion de la maintenance</h2>
            <button class="btn btn-primary" onclick="showModal('scheduleMaintenanceModal')">📅 Programmer maintenance</button>
        </div>
        
        <div class="maintenance-tabs">
            <button class="maintenance-tab active" data-tab="alerts">🚨 Alertes</button>
            <button class="maintenance-tab" data-tab="scheduled">📅 Programmées</button>
            <button class="maintenance-tab" data-tab="history">📋 Historique</button>
        </div>
        
        <div class="maintenance-content">
            <div class="maintenance-panel active" id="alerts">
                <div class="alerts-list">
                    <?php foreach ($maintenance_alerts as $alert): ?>
                        <div class="maintenance-alert <?= $alert['priority'] ?>">
                            <div class="alert-icon">
                                <?= $alert['priority'] === 'urgent' ? '🚨' : ($alert['priority'] === 'warning' ? '⚠️' : 'ℹ️') ?>
                            </div>
                            <div class="alert-details">
                                <h4><?= htmlspecialchars($alert['designation']) ?></h4>
                                <p><?= htmlspecialchars($alert['numero_inventaire']) ?> - <?= htmlspecialchars($alert['agence_nom']) ?></p>
                                <small>Échéance: <?= date('d/m/Y', strtotime($alert['echeance'])) ?></small>
                            </div>
                            <div class="alert-actions">
                                <button class="btn btn-sm btn-primary" onclick="scheduleMaintenance(<?= $alert['item_id'] ?>)">
                                    📅 Programmer
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="postponeMaintenance(<?= $alert['item_id'] ?>)">
                                    ⏰ Reporter
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="maintenance-panel" id="scheduled">
                <div class="scheduled-maintenance">
                    <div class="maintenance-calendar">
                        <div class="calendar-header">
                            <h4>📅 Planning de maintenance - <?= date('F Y') ?></h4>
                        </div>
                        <div class="calendar-grid">
                            <?php for ($day = 1; $day <= 30; $day++): ?>
                                <div class="calendar-day <?= $day === (int)date('d') ? 'today' : '' ?>">
                                    <div class="day-number"><?= $day ?></div>
                                    <?php if (in_array($day, [5, 12, 18, 25])): ?>
                                        <div class="maintenance-scheduled">📋</div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="maintenance-panel" id="history">
                <div class="maintenance-history">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Équipement</th>
                                <th>Type</th>
                                <th>Technicien</th>
                                <th>Statut</th>
                                <th>Coût</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>15/07/2024</td>
                                <td>Perceuse DW001</td>
                                <td>Préventive</td>
                                <td>Jean Martin</td>
                                <td><span class="status-badge completed">Terminé</span></td>
                                <td>45€</td>
                            </tr>
                            <tr>
                                <td>12/07/2024</td>
                                <td>Scie circulaire SC023</td>
                                <td>Corrective</td>
                                <td>Paul Dubois</td>
                                <td><span class="status-badge completed">Terminé</span></td>
                                <td>120€</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Import/Export -->
    <div class="admin-section" id="import">
        <div class="section-header">
            <h2>📤 Import/Export</h2>
        </div>
        
        <div class="import-export-grid">
            <div class="import-card">
                <h3>📥 Import en lot</h3>
                <p>Importer des équipements depuis un fichier CSV ou Excel</p>
                <div class="import-area" onclick="document.getElementById('importFile').click()">
                    <div class="import-icon">📁</div>
                    <div class="import-text">
                        <strong>Cliquez pour sélectionner un fichier</strong>
                        <small>CSV, Excel (.xlsx) - Max 10MB</small>
                    </div>
                    <input type="file" id="importFile" accept=".csv,.xlsx" style="display: none;" onchange="handleFileImport(this)">
                </div>
                <div class="import-templates">
                    <h4>Modèles de fichiers</h4>
                    <a href="templates/import_equipments.csv" class="template-link">📋 Modèle équipements</a>
                    <a href="templates/import_employees.csv" class="template-link">👥 Modèle employés</a>
                </div>
            </div>
            
            <div class="export-card">
                <h3>📤 Export des données</h3>
                <p>Exporter les données du module vers différents formats</p>
                <div class="export-options">
                    <button class="export-btn" onclick="exportData('inventory')">
                        📋 Inventaire complet (CSV)
                    </button>
                    <button class="export-btn" onclick="exportData('requests')">
                        📝 Historique des demandes (CSV)
                    </button>
                    <button class="export-btn" onclick="exportData('maintenance')">
                        🔧 Planning maintenance (PDF)
                    </button>
                    <button class="export-btn" onclick="exportData('stats')">
                        📊 Rapport statistiques (PDF)
                    </button>
                </div>
            </div>
        </div>
        
        <div class="import-history">
            <h3>📚 Historique des imports</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Fichier</th>
                        <th>Type</th>
                        <th>Éléments</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>20/07/2024 14:30</td>
                        <td>equipments_batch_001.csv</td>
                        <td>Équipements</td>
                        <td>45 créés, 2 erreurs</td>
                        <td><span class="status-badge success">Réussi</span></td>
                        <td>
                            <button onclick="viewImportDetails(1)">👁️ Détails</button>
                            <button onclick="downloadImportLog(1)">📄 Log</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section Paramètres -->
    <div class="admin-section" id="settings">
        <div class="section-header">
            <h2>⚙️ Paramètres du module</h2>
        </div>
        
        <div class="settings-grid">
            <div class="settings-card">
                <h3>🏢 Agences</h3>
                <div class="agences-list">
                    <?php foreach ($agences as $agence): ?>
                        <div class="agence-item">
                            <div class="agence-info">
                                <strong><?= htmlspecialchars($agence['nom']) ?></strong>
                                <small><?= htmlspecialchars($agence['code']) ?></small>
                            </div>
                            <div class="agence-actions">
                                <button onclick="editAgence(<?= $agence['id'] ?>)">✏️</button>
                                <button onclick="deleteAgence(<?= $agence['id'] ?>)">🗑️</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-secondary" onclick="showModal('addAgenceModal')">➕ Nouvelle agence</button>
            </div>
            
            <div class="settings-card">
                <h3>👥 Profils utilisateurs</h3>
                <div class="profils-list">
                    <?php foreach ($profils as $profil): ?>
                        <div class="profil-item">
                            <div class="profil-info">
                                <strong><?= htmlspecialchars($profil['nom']) ?></strong>
                                <small><?= htmlspecialchars($profil['description']) ?></small>

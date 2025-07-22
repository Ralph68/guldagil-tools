<?php
/**
 * Titre: Configuration types et modèles équipements
 * Chemin: /public/qualite/config.php
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

$page_title = 'Configuration Équipements';
$current_module = 'qualite';
$module_css = true;

// Auth
$current_user = ['role' => 'admin'];
if (!in_array($current_user['role'], ['admin', 'dev'])) {
    header('Location: /qualite/');
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Actions CRUD
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_type':
                $pdo->prepare("INSERT INTO cq_equipment_types (type_code, type_name, category, description) VALUES (?, ?, ?, ?)")
                    ->execute([$_POST['type_code'], $_POST['type_name'], $_POST['category'], $_POST['description']]);
                break;
                
            case 'add_model':
                $pdo->prepare("INSERT INTO cq_equipment_models (equipment_type_id, model_code, model_name, manufacturer, technical_specs) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$_POST['type_id'], $_POST['model_code'], $_POST['model_name'], $_POST['manufacturer'], $_POST['technical_specs']]);
                break;
                
            case 'toggle_active':
                $table = $_POST['table'] === 'types' ? 'cq_equipment_types' : 'cq_equipment_models';
                $pdo->prepare("UPDATE {$table} SET active = NOT active WHERE id = ?")
                    ->execute([$_POST['id']]);
                break;
        }
        header('Location: /qualite/config.php');
        exit;
    }

    // Récupération données
    $types = $pdo->query("SELECT * FROM cq_equipment_types ORDER BY category, type_name")->fetchAll();
    $models = $pdo->query("
        SELECT em.*, et.type_name 
        FROM cq_equipment_models em
        JOIN cq_equipment_types et ON em.equipment_type_id = et.id
        ORDER BY et.type_name, em.model_name
    ")->fetchAll();

} catch (Exception $e) {
    error_log("Erreur config: " . $e->getMessage());
    $types = $models = [];
}

require_once ROOT_PATH . '/templates/header.php';
?>

<div class="qualite-module">
    <div class="module-header">
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">⚙️</div>
                <div class="module-info">
                    <h1>Configuration Équipements</h1>
                    <p class="module-version"><?= count($types) ?> types, <?= count($models) ?> modèles</p>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- Types d'équipements -->
        <div class="config-section">
            <div class="section-header">
                <h2>Types d'équipements</h2>
                <button class="btn btn-primary" onclick="showModal('add-type-modal')">+ Ajouter type</button>
            </div>
            
            <div class="table-container">
                <table class="config-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $type): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($type['type_code']) ?></code></td>
                            <td><?= htmlspecialchars($type['type_name']) ?></td>
                            <td><?= htmlspecialchars($type['category']) ?></td>
                            <td><?= htmlspecialchars($type['description']) ?></td>
                            <td>
                                <span class="status-badge <?= $type['active'] ? 'active' : 'inactive' ?>">
                                    <?= $type['active'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="table" value="types">
                                    <input type="hidden" name="id" value="<?= $type['id'] ?>">
                                    <button type="submit" class="btn-sm"><?= $type['active'] ? 'Désactiver' : 'Activer' ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modèles d'équipements -->
        <div class="config-section">
            <div class="section-header">
                <h2>Modèles d'équipements</h2>
                <button class="btn btn-primary" onclick="showModal('add-model-modal')">+ Ajouter modèle</button>
            </div>
            
            <div class="table-container">
                <table class="config-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Fabricant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($models as $model): ?>
                        <tr>
                            <td><?= htmlspecialchars($model['type_name']) ?></td>
                            <td><code><?= htmlspecialchars($model['model_code']) ?></code></td>
                            <td><?= htmlspecialchars($model['model_name']) ?></td>
                            <td><?= htmlspecialchars($model['manufacturer']) ?></td>
                            <td>
                                <span class="status-badge <?= $model['active'] ? 'active' : 'inactive' ?>">
                                    <?= $model['active'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="table" value="models">
                                    <input type="hidden" name="id" value="<?= $model['id'] ?>">
                                    <button type="submit" class="btn-sm"><?= $model['active'] ? 'Désactiver' : 'Activer' ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal ajout type -->
<div class="modal" id="add-type-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajouter type d'équipement</h3>
            <button class="modal-close" onclick="hideModal('add-type-modal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_type">
            <div class="modal-body">
                <div class="form-group">
                    <label>Code type</label>
                    <input type="text" name="type_code" required maxlength="20">
                </div>
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="type_name" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="category" required>
                        <option value="adoucisseur">Adoucisseur</option>
                        <option value="pompe_doseuse">Pompe doseuse</option>
                        <option value="traitement_eau">Traitement eau</option>
                        <option value="dosage">Dosage</option>
                        <option value="mesure">Mesure</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('add-type-modal')">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal ajout modèle -->
<div class="modal" id="add-model-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajouter modèle d'équipement</h3>
            <button class="modal-close" onclick="hideModal('add-model-modal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_model">
            <div class="modal-body">
                <div class="form-group">
                    <label>Type d'équipement</label>
                    <select name="type_id" required>
                        <option value="">-- Sélectionner un type --</option>
                        <?php foreach ($types as $type): ?>
                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Code modèle</label>
                    <input type="text" name="model_code" required maxlength="50">
                </div>
                <div class="form-group">
                    <label>Nom modèle</label>
                    <input type="text" name="model_name" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Fabricant</label>
                    <input type="text" name="manufacturer" maxlength="100">
                </div>
                <div class="form-group">
                    <label>Spécifications techniques (JSON)</label>
                    <textarea name="technical_specs" rows="4" placeholder='{"capacite": "10L/h", "pression": "6bar"}'></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideModal('add-model-modal')">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<script>
// Gestion des modales
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = '';
}

// Fermer modal sur click background
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            hideModal(this.id);
        }
    });
});

// Validation JSON pour spécifications techniques
document.querySelector('textarea[name="technical_specs"]').addEventListener('blur', function() {
    if (this.value.trim() && this.value.trim() !== '') {
        try {
            JSON.parse(this.value);
            this.style.borderColor = '';
        } catch (e) {
            this.style.borderColor = '#e74c3c';
            alert('Format JSON invalide dans les spécifications techniques');
        }
    }
});
</script>

<?php require_once ROOT_PATH . '/templates/footer.php'; ?>

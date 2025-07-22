<?php
/**
 * Titre: Modification contrôle qualité
 * Chemin: /public/qualite/edit.php
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

$control_id = (int)($_GET['id'] ?? 0);
if (!$control_id) {
    header('Location: /qualite/list.php');
    exit;
}

$current_module = 'qualite';
$module_css = true;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Récupération contrôle
    $stmt = $pdo->prepare("
        SELECT qc.*, et.type_name, et.type_code, em.model_name
        FROM cq_quality_controls qc
        JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
        LEFT JOIN cq_equipment_models em ON qc.equipment_model_id = em.id
        WHERE qc.id = ?
    ");
    $stmt->execute([$control_id]);
    $control = $stmt->fetch();

    if (!$control || $control['status'] === 'validated') {
        header('Location: /qualite/view.php?id=' . $control_id);
        exit;
    }

    $technical_data = json_decode($control['technical_data'], true) ?? [];
    $settings_data = json_decode($control['settings_data'], true) ?? [];

    // Traitement mise à jour
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $update_data = [
            'observations' => $_POST['observations'] ?? '',
            'technical_data' => json_encode($_POST['technical'] ?? []),
            'settings_data' => json_encode($_POST['settings'] ?? [])
        ];

        $update_sql = "UPDATE cq_quality_controls SET observations = ?, technical_data = ?, settings_data = ?, updated_at = NOW() WHERE id = ?";
        $pdo->prepare($update_sql)->execute([$update_data['observations'], $update_data['technical_data'], $update_data['settings_data'], $control_id]);

        $pdo->prepare("INSERT INTO cq_control_history (control_id, action, user_name) VALUES (?, 'modified', ?)")
            ->execute([$control_id, 'Utilisateur']);

        header('Location: /qualite/view.php?id=' . $control_id);
        exit;
    }

    $page_title = "Modifier - {$control['control_number']}";
    
} catch (Exception $e) {
    error_log("Erreur edit: " . $e->getMessage());
    header('Location: /qualite/list.php');
    exit;
}

require_once ROOT_PATH . '/templates/header.php';
?>

<div class="qualite-module">
    <div class="module-header">
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">✏️</div>
                <div class="module-info">
                    <h1>Modifier <?= htmlspecialchars($control['control_number']) ?></h1>
                    <p class="module-version"><?= htmlspecialchars($control['type_name']) ?></p>
                </div>
            </div>
            <div class="module-actions">
                <a href="/qualite/view.php?id=<?= $control_id ?>" class="btn btn-outline">← Retour</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <form method="POST" class="edit-form">
            <div class="form-card">
                <h2>Données techniques</h2>
                <div class="technical-fields">
                    <?php foreach ($technical_data as $key => $value): ?>
                        <?php if ($key !== 'quality_checks'): ?>
                        <div class="form-group">
                            <label><?= ucfirst(str_replace('_', ' ', $key)) ?></label>
                            <input type="text" name="technical[<?= $key ?>]" value="<?= htmlspecialchars($value) ?>">
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-card">
                <h2>Observations</h2>
                <textarea name="observations" rows="6" class="form-control"><?= htmlspecialchars($control['observations']) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Enregistrer modifications</button>
                <a href="/qualite/view.php?id=<?= $control_id ?>" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</div>

<style>
.edit-form { display: flex; flex-direction: column; gap: 2rem; }
.form-card { background: white; padding: 2rem; border-radius: 0.75rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.technical-fields { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; color: #374151; }
.form-group input { padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; }
.form-actions { display: flex; gap: 1rem; justify-content: flex-end; }
@media (max-width: 768px) { .technical-fields { grid-template-columns: 1fr; } }
</style>

<?php require_once ROOT_PATH . '/templates/footer.php'; ?>

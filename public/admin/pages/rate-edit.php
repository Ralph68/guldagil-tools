<?php
// -----------------------------------------------------------------------------
// admin/pages/rate-edit.php
// Formulaire d'ajout / modification d'un tarif (Rate)
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Rate.php';
require_once __DIR__ . '/../models/Transporteur.php';

// Récupération de l'ID si en modification
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$errors = [];

// Liste des transporteurs pour le select
$carriers = Transporteur::getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collecte et nettoyage des données
    $data = [
        'carrier_id' => (int) $_POST['carrier_id'],
        'zone'       => trim($_POST['zone']),
        'cost'       => floatval(str_replace(',', '.', $_POST['cost']))
    ];

    // Validation des champs
    if ($data['carrier_id'] <= 0) {
        $errors[] = 'Veuillez sélectionner un transporteur.';
    }
    if ($data['zone'] === '') {
        $errors[] = 'Le champ zone est requis.';
    }
    if ($data['cost'] <= 0) {
        $errors[] = 'Le coût doit être supérieur à zéro.';
    }

    // Si pas d'erreurs, création ou mise à jour en base
    if (empty($errors)) {
        if ($id) {
            Rate::update($id, $data);
        } else {
            Rate::create($data);
        }
        header('Location: index.php?page=rates');
        exit;
    }
}

// Préremplissage du formulaire en cas de modification
$rate = ['carrier_id' => '', 'zone' => '', 'cost' => ''];
if ($id) {
    $rate = Rate::getById($id);
}
?>

<h1><?= $id ? 'Modifier' : 'Ajouter' ?> un tarif</h1>

<?php if (!empty($errors)): ?>
    <ul class="errors">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post">
    <label>Transporteur<br>
        <select name="carrier_id" required>
            <option value="">-- Choisissez --</option>
            <?php foreach ($carriers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($c['id'] == $rate['carrier_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Zone<br>
        <input type="text" name="zone" value="<?= htmlspecialchars($rate['zone']) ?>" required>
    </label><br>

    <label>Coût (€)<br>
        <input type="text" name="cost" value="<?= htmlspecialchars($rate['cost']) ?>" required>
    </label><br>

    <button type="submit">Enregistrer</button>
    <a href="index.php?page=rates" class="button">Annuler</a>
</form>


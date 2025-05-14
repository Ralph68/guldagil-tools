
<?php
// -----------------------------------------------------------------------------
// admin/pages/carrier-edit.php
// Formulaire d'ajout / modification d'un transporteur
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Transporteur.php';

// Récupération de l'ID si en modification
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'zone' => trim($_POST['zone']),
    ];

    // Validation
    if ($data['name'] === '') {
        $errors[] = 'Le nom est requis.';
    }

    // Si pas d'erreurs, création ou mise à jour
    if (empty($errors)) {
        if ($id) {
            Transporteur::update($id, $data);
        } else {
            Transporteur::create($data);
        }
        header('Location: index.php?page=carriers');
        exit;
    }
}

// Préremplissage des valeurs en cas de modification
$carrier = ['name' => '', 'zone' => ''];
if ($id) {
    $carrier = Transporteur::getById($id);
}
?>

<h1><?= $id ? 'Modifier' : 'Ajouter' ?> un transporteur</h1>

<?php if (!empty($errors)): ?>
    <ul class="errors">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post">
    <label>Nom<br>
        <input type="text" name="name" value="<?= htmlspecialchars($carrier['name']) ?>" required>
    </label><br>

    <label>Zone<br>
        <input type="text" name="zone" value="<?= htmlspecialchars($carrier['zone']) ?>" required>
    </label><br>

    <button type="submit">Enregistrer</button>
    <a href="index.php?page=carriers" class="button">Annuler</a>
</form>

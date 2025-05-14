<?php
// -----------------------------------------------------------------------------
// admin/pages/tax-edit.php
// Formulaire d'ajout / modification d'une taxe (Tax)
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Tax.php';

// Récupération de l'ID si modification
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'rate' => floatval(str_replace(',', '.', $_POST['rate']))
    ];

    // Validation
    if ($data['name'] === '') {
        $errors[] = 'Le nom de la taxe est requis.';
    }
    if ($data['rate'] <= 0) {
        $errors[] = 'Le taux doit être supérieur à 0.';
    }

    if (empty($errors)) {
        if ($id) {
            Tax::update($id, $data);
        } else {
            Tax::create($data);
        }
        header('Location: index.php?page=taxes');
        exit;
    }
}

// Préremplissage
$tax = ['name' => '', 'rate' => ''];
if ($id) {
    $tax = Tax::getById($id);
}
?>

<h1><?= $id ? 'Modifier' : 'Ajouter' ?> une taxe</h1>

<?php if (!empty($errors)): ?>
  <ul class="errors">
    <?php foreach ($errors as $error): ?>
      <li><?= htmlspecialchars($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post">
  <label>Nom<br>
    <input type="text" name="name" value="<?= htmlspecialchars($tax['name']) ?>" required>
  </label><br>

  <label>Taux (%)<br>
    <input type="text" name="rate" value="<?= htmlspecialchars($tax['rate']) ?>" required>
  </label><br>

  <button type="submit">Enregistrer</button>
  <a href="index.php?page=taxes" class="button">Annuler</a>
</form>

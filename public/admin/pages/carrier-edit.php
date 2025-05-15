<?php
// public/admin/pages/carrier-edit.php
declare(strict_types=1);

// Charger le modèle
require_once dirname(__DIR__, 3) . '/lib/Transport.php';
$model = new Transport($db);

$errors = [];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$data   = ['code'=>'', 'name'=>'', 'zone'=>''];

// Si soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'code' => trim($_POST['code']),
        'name' => trim($_POST['name']),
        'zone' => trim($_POST['zone']),
    ];

    // Validation
    if ($data['code'] === '') $errors[] = 'Le code est requis.';
    if ($data['name'] === '') $errors[] = 'Le nom est requis.';
    if ($data['zone'] === '') $errors[] = 'La zone est requise.';

    if (empty($errors)) {
        if ($id) {
            $model->update($id, $data);
        } else {
            $model->create($data);
        }
        header('Location: index.php?page=carriers');
        exit;
    }
}

// Si modification, préremplir
if ($id) {
    $row = $model->getById($id);
    if ($row) {
        $data = [
            'code' => $row['code'],
            'name' => $row['name'],
            'zone' => $row['zone'],
        ];
    } else {
        echo '<p class="error">Transporteur introuvable.</p>';
    }
}
?>

<h1><?= $id ? 'Modifier' : 'Ajouter' ?> un transporteur</h1>

<?php if ($errors): ?>
  <ul class="errors">
    <?php foreach ($errors as $e): ?>
      <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post">
  <label>
    Code<br>
    <input type="text" name="code" value="<?= htmlspecialchars($data['code'], ENT_QUOTES) ?>" required>
  </label><br>

  <label>
    Nom<br>
    <input type="text" name="name" value="<?= htmlspecialchars($data['name'], ENT_QUOTES) ?>" required>
  </label><br>

  <label>
    Zone<br>
    <input type="text" name="zone" value="<?= htmlspecialchars($data['zone'], ENT_QUOTES) ?>" required>
  </label><br>

  <button type="submit">Enregistrer</button>
  <a href="index.php?page=carriers" class="button">Annuler</a>
</form>

<?php
require __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$errors = [];

// Valeurs par défaut
$data = [
  'transporteur' => '',
  'code_option' => '',
  'libelle' => '',
  'montant' => '0.00',
  'unite' => 'forfait',
  'actif' => true
];

if ($id) {
  $stmt = $db->prepare("SELECT * FROM gul_options_supplementaires WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if ($row) $data = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($data as $key => $val) {
    $data[$key] = $_POST[$key] ?? ($key === 'actif' ? 0 : '');
  }
  $data['actif'] = isset($_POST['actif']) ? 1 : 0;

  if (!$data['transporteur'] || !$data['code_option']) {
    $errors[] = 'Transporteur et code requis';
  }

  if (empty($errors)) {
    if ($id) {
      $sql = "UPDATE gul_options_supplementaires SET transporteur=?, code_option=?, libelle=?, montant=?, unite=?, actif=? WHERE id=?";
      $stmt = $db->prepare($sql);
      $stmt->execute([$data['transporteur'], $data['code_option'], $data['libelle'], $data['montant'], $data['unite'], $data['actif'], $id]);
    } else {
      $sql = "INSERT INTO gul_options_supplementaires (transporteur, code_option, libelle, montant, unite, actif) VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $db->prepare($sql);
      $stmt->execute([$data['transporteur'], $data['code_option'], $data['libelle'], $data['montant'], $data['unite'], $data['actif']]);
    }
    header('Location: admin-options.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?= $id ? 'Modifier' : 'Ajouter' ?> une option</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <h1><?= $id ? 'Modifier' : 'Ajouter' ?> une option supplémentaire</h1>
  <?php if ($errors): ?><ul style="color:red;"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul><?php endif; ?>
  <form method="post">
    <label>Transporteur
      <input type="text" name="transporteur" value="<?= htmlspecialchars($data['transporteur']) ?>" required />
    </label>
    <label>Code option
      <input type="text" name="code_option" value="<?= htmlspecialchars($data['code_option']) ?>" required />
    </label>
    <label>Libellé
      <input type="text" name="libelle" value="<?= htmlspecialchars($data['libelle']) ?>" />
    </label>
    <label>Montant
      <input type="number" step="0.01" name="montant" value="<?= htmlspecialchars($data['montant']) ?>" />
    </label>
    <label>Unité
      <select name="unite">
        <option value="forfait" <?= $data['unite'] === 'forfait' ? 'selected' : '' ?>>Forfait</option>
        <option value="palette" <?= $data['unite'] === 'palette' ? 'selected' : '' ?>>Par palette</option>
      </select>
    </label>
    <label>
      <input type="checkbox" name="actif" <?= $data['actif'] ? 'checked' : '' ?> /> Actif
    </label>
    <button type="submit">Enregistrer</button>
    <a href="admin-options.php">Annuler</a>
  </form>
</body>
</html>

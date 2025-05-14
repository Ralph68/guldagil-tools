<?php
require __DIR__ . '/../config.php';

// Suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM gul_options_supplementaires WHERE id = ?")->execute([$id]);
    header('Location: admin-options.php');
    exit;
}

// Récupération
$options = $db->query("SELECT * FROM gul_options_supplementaires ORDER BY transporteur, code_option")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Options Supplémentaires Transporteurs</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
    th, td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; }
    .btn { padding: 0.5rem 1rem; background: #007acc; color: #fff; text-decoration: none; border-radius: 4px; }
    .btn:hover { background: #005f99; }
  </style>
</head>
<body>
  <h1>Options Supplémentaires Transporteurs</h1>
  <a href="admin-options-edit.php" class="btn">+ Ajouter une option</a>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Transporteur</th>
        <th>Code</th>
        <th>Libellé</th>
        <th>Montant</th>
        <th>Unité</th>
        <th>Actif</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($options as $opt): ?>
      <tr>
        <td><?= $opt['id'] ?></td>
        <td><?= strtoupper($opt['transporteur']) ?></td>
        <td><?= htmlspecialchars($opt['code_option']) ?></td>
        <td><?= htmlspecialchars($opt['libelle']) ?></td>
        <td><?= number_format($opt['montant'], 2) ?> €</td>
        <td><?= $opt['unite'] ?></td>
        <td><?= $opt['actif'] ? 'Oui' : 'Non' ?></td>
        <td>
          <a href="admin-options-edit.php?id=<?= $opt['id'] ?>">Modifier</a> |
          <a href="?delete=<?= $opt['id'] ?>" onclick="return confirm('Supprimer cette option ?')">Supprimer</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>

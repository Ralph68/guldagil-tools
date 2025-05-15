<?php
// public/admin/pages/carriers.php
declare(strict_types=1);

// Charger le modèle
require_once dirname(__DIR__, 3) . '/lib/Transport.php';
$model = new Transport($db);

// Suppression si demandé
if (isset($_GET['delete'])) {
    $model->delete((int)$_GET['delete']);
    header('Location: index.php?page=carriers');
    exit;
}

// Lecture de tous les transporteurs
$transporteurs = $model->getAll();
?>

<h1>Transporteurs</h1>
<p><a href="index.php?page=carrier-edit" class="button">Ajouter un transporteur</a></p>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Code</th>
      <th>Nom</th>
      <th>Zone</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($transporteurs)): ?>
      <tr><td colspan="5">Aucun transporteur.</td></tr>
    <?php else: ?>
      <?php foreach ($transporteurs as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['id'],   ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($t['code'], ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($t['name'], ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($t['zone'], ENT_QUOTES) ?></td>
          <td>
            <a href="index.php?page=carrier-edit&id=<?= $t['id'] ?>">Modifier</a>
            <a href="index.php?page=carriers&delete=<?= $t['id'] ?>"
               onclick="return confirm('Supprimer ce transporteur ?')">Supprimer</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

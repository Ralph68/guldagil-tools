<?php
declare(strict_types=1);

// charger modèle
require_once dirname(__DIR__, 3) . '/lib/Transport.php';
$model    = new Transport($db);
$carriers = $model->getCarriers();
?>

<h1>Transporteurs</h1>
<table>
  <thead><tr><th>Code</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($carriers as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c, ENT_QUOTES) ?></td>
        <td>
          <!-- lien vers la page des tarifs de ce carrier -->
          <a href="index.php?page=rates&carrier=<?= urlencode($c) ?>">
            Voir tarifs
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

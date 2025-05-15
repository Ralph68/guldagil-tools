<?php
// redirige si on accède directement au fragment
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Location: index.php?page=carriers');
    exit;
}

// charger la config/database
// (déjà fait en index.php, mais pas de mal en dev)
require_once __DIR__ . '/../config.php';

// charger la classe Transporteur (et toutes vos méthodes)
require_once __DIR__ . '/../../lib/Transport.php';

// suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    Transporteur::delete($id);
    header('Location: index.php?page=carriers');
    exit;
}

// récupération
$transporteurs = Transporteur::getAll();
?>

<h1>Transporteurs</h1>
<p><a href="index.php?page=carrier-edit" class="button">Ajouter un transporteur</a></p>
<table>
  <thead>
    <tr><th>ID</th><th>Nom</th><th>Zone</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($transporteurs as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['id'], ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars($t['name'], ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars($t['zone'], ENT_QUOTES) ?></td>
        <td>
          <a href="index.php?page=carrier-edit&id=<?= $t['id'] ?>">Modifier</a>
          <a href="index.php?page=carriers&delete=<?= $t['id'] ?>" onclick="return confirm('Supprimer ce transporteur ?')">Supprimer</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

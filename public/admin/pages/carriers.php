<?php
// -----------------------------------------------------------------------------
// public/admin/pages/carriers.php
// Fragment injecté par admin/index.php sans inclusion de config
// -----------------------------------------------------------------------------

// Charger le modèle Transporteur depuis lib
// Remonte 3 niveaux: pages -> admin -> public -> racine du projet
require_once dirname(__DIR__, 3) . '/lib/Transport.php';

// Suppression si demandé
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    Transporteur::delete($id);
    header('Location: index.php?page=carriers');
    exit;
}

// Récupération des transporteurs
$transporteurs = Transporteur::getAll();
?>

<h1>Transporteurs</h1>
<p><a href="index.php?page=carrier-edit" class="button">Ajouter un transporteur</a></p>
<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Nom</th>
      <th>Zone</th>
      <th>Actions</th>
    </tr>
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

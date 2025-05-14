<?php
// -----------------------------------------------------------------------------
// admin/pages/carriers.php
// Liste des transporteurs (Carriers)
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Transporteur.php';
$transporteurs = Transporteur::getAll();

?>

<h1>Transporteurs</h1>

<p>
  <a href="index.php?page=carrier-edit" class="button">Ajouter un transporteur</a>
</p>

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
        <td><?= htmlspecialchars($t['id']) ?></td>
        <td><?= htmlspecialchars($t['name']) ?></td>
        <td><?= htmlspecialchars($t['zone']) ?></td>
        <td>
          <a href="index.php?page=carrier-edit&id=<?= $t['id'] ?>">Modifier</a>
          <a href="index.php?page=carriers&delete=<?= $t['id'] ?>" onclick="return confirm('Supprimer ce transporteur ?')">Supprimer</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php
// Traitement de la suppression si demandé via GET
if (isset($_GET['delete'])) {
    $idToDelete = (int) $_GET['delete'];
    Transporteur::delete($idToDelete);
    header('Location: index.php?page=carriers');
    exit;
}
?>

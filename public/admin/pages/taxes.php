<?php
// -----------------------------------------------------------------------------
// admin/pages/taxes.php
// Liste des taxes (Taxes)
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Tax.php';
$taxes = Tax::getAll();
?>

<h1>Taxes</h1>

<p>
  <a href="index.php?page=tax-edit" class="button">Ajouter une taxe</a>
</p>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Nom</th>
      <th>Taux (%)</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($taxes)): ?>
      <tr>
        <td colspan="4">Aucune taxe définie.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($taxes as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['id']) ?></td>
          <td><?= htmlspecialchars($t['name']) ?></td>
          <td><?= htmlspecialchars($t['rate']) ?></td>
          <td>
            <a href="index.php?page=tax-edit&id=<?= $t['id'] ?>">Modifier</a>
            <a href="index.php?page=taxes&delete=<?= $t['id'] ?>" onclick="return confirm('Supprimer cette taxe ?')">Supprimer</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php
// Suppression si demandé
if (isset($_GET['delete'])) {
    $idToDelete = (int) $_GET['delete'];
    Tax::delete($idToDelete);
    header('Location: index.php?page=taxes');
    exit;
}
?>



<?php
// -----------------------------------------------------------------------------
// admin/pages/rates.php
// Liste des tarifs (Rates)
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Rate.php';
require_once __DIR__ . '/../models/Transporteur.php';

// Récupère tous les tarifs avec le nom du transporteur
$rates = Rate::getAllWithCarrier(); // Méthode join qui retourne ['id','carrier_name','zone','cost']

?>

<h1>Tarifs</h1>

<p>
  <a href="index.php?page=rate-edit" class="button">Ajouter un tarif</a>
</p>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Transporteur</th>
      <th>Zone</th>
      <th>Coût (€)</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rates as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['id']) ?></td>
        <td><?= htmlspecialchars($r['carrier_name']) ?></td>
        <td><?= htmlspecialchars($r['zone']) ?></td>
        <td><?= number_format($r['cost'], 2, ',', ' ') ?></td>
        <td>
          <a href="index.php?page=rate-edit&id=<?= $r['id'] ?>">Modifier</a>
          <a href="index.php?page=rates&delete=<?= $r['id'] ?>" onclick="return confirm('Supprimer ce tarif ?')">Supprimer</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php
// Traitement de la suppression si demandé via GET
if (isset($_GET['delete'])) {
    $idToDelete = (int) $_GET['delete'];
    Rate::delete($idToDelete);
    header('Location: index.php?page=rates');
    exit;
}
?>

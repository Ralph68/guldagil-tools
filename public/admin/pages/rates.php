<?php
// -----------------------------------------------------------------------------
// admin/pages/rates.php
// Liste des tarifs (Rates) avec filtres
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Rate.php';
require_once __DIR__ . '/../models/Transporteur.php';

// Récupération des transporteurs pour le filtre
$carriers = Transporteur::getAll();

// Lecture des filtres depuis la query string
$filter_carrier = isset($_GET['carrier_id']) && $_GET['carrier_id'] !== ''
    ? (int) $_GET['carrier_id']
    : null;
$filter_zone = isset($_GET['zone'])
    ? trim($_GET['zone'])
    : '';

// Récupère les tarifs filtrés (méthode join prenant en charge les filtres)
$rates = Rate::getAllWithCarrier($filter_carrier, $filter_zone);
?>

<h1>Tarifs</h1>

<!-- Formulaire de filtres -->
<form method="get" class="filters">
  <input type="hidden" name="page" value="rates">

  <label>
    Transporteur&nbsp;:
    <select name="carrier_id">
      <option value="">Tous</option>
      <?php foreach ($carriers as $c): ?>
        <option value="<?= $c['id'] ?>" <?php if ($filter_carrier === $c['id']) echo 'selected'; ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>
    Zone&nbsp;:
    <input type="text" name="zone" value="<?= htmlspecialchars($filter_zone) ?>" placeholder="Ex. Europe">
  </label>

  <button type="submit" class="button">Filtrer</button>
  <a href="index.php?page=rates" class="button">Réinitialiser</a>
</form>

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
    <?php if (empty($rates)): ?>
      <tr><td colspan="5">Aucun tarif trouvé.</td></tr>
    <?php else: ?>
      <?php foreach ($rates as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['id']) ?></td>
          <td><?= htmlspecialchars($r['carrier_name']) ?></td>
          <td><?= htmlspecialchars($r['zone']) ?></td>
          <td><?= number_format($r['cost'], 2, ',', ' ') ?></td>
          <td>
            <a href="index.php?page=rate-edit&id=<?= $r['id'] ?>">Modifier</a>
            <a href="index.php?page=rates&delete=<?= $r['id'] ?>" onclick="return confirm('Supprimer ce tarif ?')">Supprimer</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
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

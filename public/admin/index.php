<?php
// -----------------------------------------------------------------------------
// admin/pages/carriers.php
// Liste des transporteurs (Carriers) - fragment
// -----------------------------------------------------------------------------
// Ce fragment est inclus par public/admin/index.php (qui a déjà chargé config.php).

// Charger la classe Transporteur depuis lib
require_once __DIR__ . '/../../lib/Transport.php';

// Suppression si demandé
if (isset($_GET['delete'])) {
    $idToDelete = (int) $_GET['delete'];
    Transporteur::delete($idToDelete);
    header('Location: index.php?page=carriers');
    exit;
}

// Récupération des transporteurs
$transporteurs = Transporteur::getAll();
?>
<!-- Contenu de la page -->
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

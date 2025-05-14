<?php
// -----------------------------------------------------------------------------
// admin/pages/options.php
// Liste des paramètres généraux (Options)
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Option.php';
$options = Option::getAll();
?>

<h1>Paramètres Généraux</h1>

<p>
  <a href="index.php?page=options-edit" class="button">Ajouter / Modifier un paramètre</a>
</p>

<table>
  <thead>
    <tr>
      <th>Clé</th>
      <th>Valeur</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($options)): ?>
      <tr>
        <td colspan="3">Aucun paramètre défini.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($options as $opt): ?>
        <tr>
          <td><?= htmlspecialchars($opt['key']) ?></td>
          <td><?= htmlspecialchars($opt['value']) ?></td>
          <td>
            <a href="index.php?page=options-edit&key=<?= urlencode($opt['key']) ?>">Modifier</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php
// Note: pas de suppression pour les options, 
// la gestion se fait exclusivement via le formulaire d'édition.
?>

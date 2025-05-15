<?php
// -----------------------------------------------------------------------------
// public/admin/pages/carriers.php
// Fragment injecté par admin/index.php sans inclusion de config
// -----------------------------------------------------------------------------

// Charger le modèle Transporteur depuis lib
require_once dirname(__DIR__, 2) . '/lib/Transport.php';

// Suppression si demandé
if (isset($_GET['delete'])) {
    $id = (int)
        \$_GET['delete'];
    Transporteur::delete($id);
    header('Location: index.php?page=carriers');
    exit;
}

// Récupération des transporteurs
\$transporteurs = Transporteur::getAll();
?>

<h1>Transporteurs</h1>
<p><a href="index.php?page=carrier-edit" class="button">Ajouter un transporteur</a></p>
<table>
  <thead>
    <tr><th>ID</th><th>Nom</th><th>Zone</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach (\$transporteurs as \$t): ?>
      <tr>
        <td><?= htmlspecialchars(\$t['id'], ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars(\$t['name'], ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars(\$t['zone'], ENT_QUOTES) ?></td>
        <td>
          <a href="index.php?page=carrier-edit&id=<?= \$t['id'] ?>">Modifier</a>
          <a href="index.php?page=carriers&delete=<?= \$t['id'] ?>" onclick="return confirm('Supprimer ?')">Supprimer</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Back-office • Calcul Frais de Port</title>
  <link rel="stylesheet" href="css/admin-style.css">
  <script src="js/admin-scripts.js" defer></script>
</head>
<body>
  <header class="admin-header">
    <a href="index.php?page=carriers" class="logo">
      <img src="assets/logo.png" alt="Logo"> Comparateur de frais de port
    </a>
  </header>
  <div class="admin-wrapper">
    <aside class="admin-sidebar">
      <nav>
        <ul>
          <li><a href="index.php?page=carriers" class="<?= \$pageKey==='carriers' ? 'active' : '' ?>">Transporteurs</a></li>
          <li><a href="index.php?page=rates" class="<?= \$pageKey==='rates' ? 'active' : '' ?>">Tarifs</a></li>
          <li><a href="index.php?page=taxes" class="<?= \$pageKey==='taxes' ? 'active' : '' ?>">Taxes</a></li>
          <li><a href="index.php?page=fuel-indices" class="<?= \$pageKey==='fuel-indices' ? 'active' : '' ?>">Indices Gasoil</a></li>
          <li><a href="index.php?page=options" class="<?= \$pageKey==='options' ? 'active' : '' ?>">Paramètres généraux</a></li>
        </ul>
      </nav>
    </aside>

    <main class="admin-content">
      <?= \$content ?>
    </main>
  </div>

  <footer class="admin-footer">
    <p>&copy; <?= date('Y') ?> Guldagil</p>
  </footer>
</body>
</html>

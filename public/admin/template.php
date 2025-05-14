<?php
// -----------------------------------------------------------------------------
// admin/template.php
// Gabarit commun du back-office
// -----------------------------------------------------------------------------
?>
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
  <div class="admin-wrapper">
    <aside class="admin-sidebar">
      <h2>Menu Admin</h2>
      <ul>
        <li>
          <a href="index.php?page=carriers"
             class="<?= \$pageKey === 'carriers' ? 'active' : '' ?>">
            Transporteurs
          </a>
        </li>
        <li>
          <a href="index.php?page=rates"
             class="<?= \$pageKey === 'rates' ? 'active' : '' ?>">
            Tarifs
          </a>
        </li>
        <li>
          <a href="index.php?page=taxes"
             class="<?= \$pageKey === 'taxes' ? 'active' : '' ?>">
            Taxes
          </a>
        </li>
        <li>
          <a href="index.php?page=fuel-indices"
             class="<?= \$pageKey === 'fuel-indices' ? 'active' : '' ?>">
            Indices Gasoil
          </a>
        </li>
        <li>
          <a href="index.php?page=options"
             class="<?= \$pageKey === 'options' ? 'active' : '' ?>">
            Paramètres Généraux
          </a>
        </li>
      </ul>
    </aside>

    <main class="admin-content">
      <?= \$content ?>
    </main>
  </div>
</body>
</html>

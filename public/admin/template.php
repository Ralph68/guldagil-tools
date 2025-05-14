<?php
// admin/template.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Back-office • Calcul Frais de Port</title>
  <link rel="stylesheet" href="css/admin-style.css">
  <script src="js/admin-scripts.js" defer></script>
</head>
<body>
  <div class="admin-wrapper">
    <aside class="admin-sidebar">
      <h2>Menu Admin</h2>
      <ul>
        <li><a href="index.php?page=rates"
          class="<?= $pageKey==='rates' ? 'active' : '' ?>">Transporteurs</a></li>
        <li><a href="index.php?page=rate-edit"
          class="<?= $pageKey==='rate-edit' ? 'active' : '' ?>">Ajouter / Modifier Transporteur</a></li>
        <li><a href="index.php?page=options"
          class="<?= $pageKey==='options' ? 'active' : '' ?>">Options générales</a></li>
        <li><a href="index.php?page=options-edit"
          class="<?= $pageKey==='options-edit' ? 'active' : '' ?>">Éditer Options</a></li>
      </ul>
    </aside>

    <main class="admin-content">
      <?= $content ?>
    </main>
  </div>
</body>
</html>


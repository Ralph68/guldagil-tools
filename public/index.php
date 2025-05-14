<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
$carriers = ['xpo' => 'XPO', 'heppner' => 'Heppner', 'kn' => 'Kuehne+Nagel'];

$dep = $_POST['departement'] ?? '';
$poids = isset($_POST['poids']) ? (float)$_POST['poids'] : null;
$type = $_POST['type'] ?? 'palette';
$adr = $_POST['adr'] ?? '';
$option_sup = $_POST['option_sup'] ?? 'standard';
$enlevement = isset($_POST['enlevement']);
$palettes = isset($_POST['palettes']) ? (int)$_POST['palettes'] : 0;

$results = [];
$best = null;
$bestCarrier = null;
$errors = [];
$invalid = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$dep) {
    $errors[] = "Le champ Département est requis.";
    $invalid['departement'] = true;
  }
  if (!$poids) {
    $errors[] = "Le champ Poids est requis.";
    $invalid['poids'] = true;
  }
  if (!$type) {
    $errors[] = "Le type d'envoi est requis.";
    $invalid['type'] = true;
  }
  if (!$adr) {
    $errors[] = "Le choix ADR est requis.";
    $invalid['adr'] = true;
  }

  if (empty($errors)) {
    $results = $transport->calculateAll($type, $adr, $poids, $option_sup);
    $valid = array_filter($results, fn($p) => $p !== null);
    if ($valid) {
      $best = min($valid);
      $bestCarrier = array_search($best, $results);
    }
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comparateur de frais de port</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .zone-resultat {
      background-color: #eef6ff;
      border-left: 5px solid #007acc;
      padding: 1.2rem;
      border-radius: 6px;
      margin-bottom: 2rem;
    }
    .toggle-details { cursor: pointer; color: #007acc; text-decoration: underline; }
    .details-content { display: none; margin-top: 0.5rem; font-size: 0.9rem; background: #f9f9f9; padding: 1rem; border-left: 3px solid #ccc; }
  </style>
  <script>
    function toggleDetails(id) {
      var el = document.getElementById(id);
      el.style.display = el.style.display === 'block' ? 'none' : 'block';
    }
  </script>
</head>
<body>
<div class="container">

  <header class="site-header">
    <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="logo">
    <h1>Comparateur de frais de port</h1>
    <nav><a href="admin/rates.php">Administration</a></nav>
  </header>

  <section class="zone-resultat">
    <h2>Choix recommandé</h2>
    <?php if ($bestCarrier !== null): ?>
      <p><strong><?= $carriers[$bestCarrier] ?></strong> : <?= number_format($best, 2, ',', ' ') ?> €</p>
      <p><span class="toggle-details" onclick="toggleDetails('calc')">Détails du calcul</span></p>
      <div class="details-content" id="calc">
        <pre><?= var_export($transport->debug[$bestCarrier] ?? [], true) ?></pre>
      </div>
      <p><span class="toggle-details" onclick="toggleDetails('frais')">Frais supplémentaires (représentation / gardiennage)</span></p>
      <div class="details-content" id="frais">
        <ul>
          <li>Représentation : selon CGV (ex. 15,00 €)</li>
          <li>Gardiennage : si livraison impossible, 25,00 €/jour</li>
        </ul>
      </div>
    <?php else: ?>
      <p><em>Aucun tarif sélectionné pour l’instant.</em></p>
    <?php endif; ?>
  </section>

  <main class="main-content">
    <section class="form-section">
      <?php if (!empty($errors)): ?>
        <div class="error">
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-step">
          <label for="departement">Département</label>
          <input type="text" name="departement" id="departement" maxlength="2" pattern="\d{2}" required value="<?= htmlspecialchars($dep) ?>" class="<?= isset($invalid['departement']) ? 'is-invalid' : '' ?>">
        </div>

        <div class="form-step">
          <label for="poids">Poids réel (kg)</label>
          <input type="number" name="poids" id="poids" step="0.1" min="0.1" required value="<?= htmlspecialchars($poids ?? '') ?>" class="<?= isset($invalid['poids']) ? 'is-invalid' : '' ?>">
        </div>

        <div class="form-step">
          <label>Type d'envoi</label>
          <div class="radio-group">
            <input type="radio" name="type" value="colis" id="type-colis" <?= $type==='colis'?'checked':'' ?> class="<?= isset($invalid['type']) ? 'is-invalid' : '' ?>"> <label for="type-colis">Colis</label>
            <input type="radio" name="type" value="palette" id="type-palette" <?= $type==='palette'?'checked':'' ?> class="<?= isset($invalid['type']) ? 'is-invalid' : '' ?>"> <label for="type-palette">Palette</label>
          </div>
        </div>

        <div class="form-step">
          <label>ADR</label>
          <div class="radio-group">
            <input type="radio" name="adr" value="oui" id="adr-oui" <?= $adr==='oui'?'checked':'' ?> class="<?= isset($invalid['adr']) ? 'is-invalid' : '' ?>"> <label for="adr-oui">Oui</label>
            <input type="radio" name="adr" value="non" id="adr-non" <?= $adr==='non'?'checked':'' ?> class="<?= isset($invalid['adr']) ? 'is-invalid' : '' ?>"> <label for="adr-non">Non</label>
          </div>
        </div>

        <!-- Options supplémentaires à venir ici -->

        <div class="form-step" style="text-align:center; margin-top:1rem;">
          <button type="submit">Calculer</button>
        </div>
      </form>
    </section>
  </main>
</div>
</body>
</html>

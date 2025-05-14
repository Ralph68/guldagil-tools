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
    .aide { color: #777; font-size: 0.9rem; margin-top: 0.25rem; }
    .zone-resultat { border: 2px solid #def; border-radius: 6px; padding: 1rem; background: #f9f9ff; margin-top: 1rem; }
    .ecart { font-style: italic; font-size: 0.9rem; color: #555; }
    .depliant { cursor: pointer; color: #007acc; text-decoration: underline; }
    .cache { display: none; margin-top: 0.5rem; }
  </style>
</head>
<body>
  <div class="container">
    <header class="site-header">
      <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="logo">
      <h1>Comparateur de frais de port</h1>
      <nav><a href="admin/rates.php">Administration</a></nav>
    </header>

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
            <input type="text" name="departement" id="departement" maxlength="2" pattern="\d{2}" required value="<?= htmlspecialchars($dep) ?>">
            <div class="aide">Ex : 68</div>
          </div>

          <div class="form-step">
            <label for="poids">Poids réel (kg)</label>
            <input type="number" name="poids" id="poids" step="0.1" min="0.1" required value="<?= htmlspecialchars($poids ?? '') ?>">
          </div>

          <div class="form-step">
            <label>Type d'envoi</label>
            <div class="radio-group">
              <input type="radio" name="type" value="colis" id="type-colis" <?= $type==='colis'?'checked':'' ?>> <label for="type-colis">Colis</label>
              <input type="radio" name="type" value="palette" id="type-palette" <?= $type==='palette'?'checked':'' ?>> <label for="type-palette">Palette</label>
            </div>
          </div>

          <div class="form-step">
            <label>ADR</label>
            <div class="radio-group">
              <input type="radio" name="adr" value="oui" id="adr-oui" <?= $adr==='oui'?'checked':'' ?>> <label for="adr-oui">Oui</label>
              <input type="radio" name="adr" value="non" id="adr-non" <?= $adr==='non'?'checked':'' ?>> <label for="adr-non">Non</label>
            </div>
          </div>

          <div class="form-step">
            <label>Option (standard par défaut)</label>
            <div class="radio-group">
              <input type="radio" name="option_sup" value="standard" id="standard" <?= $option_sup==='standard'?'checked':'' ?>> <label for="standard">Standard</label>
              <input type="radio" name="option_sup" value="rdv" id="rdv" <?= $option_sup==='rdv'?'checked':'' ?>> <label for="rdv">Prise de RDV</label>
              <input type="radio" name="option_sup" value="premium13" id="premium13" <?= $option_sup==='premium13'?'checked':'' ?>> <label for="premium13">Star avant 13h</label>
              <input type="radio" name="option_sup" value="premium18" id="premium18" <?= $option_sup==='premium18'?'checked':'' ?>> <label for="premium18">Star avant 18h</label>
              <input type="radio" name="option_sup" value="datefixe" id="datefixe" <?= $option_sup==='datefixe'?'checked':'' ?>> <label for="datefixe">Date fixe</label>
            </div>
          </div>

          <div class="form-step">
            <label for="enlevement">Enlèvement (remplace toute option)</label>
            <input type="checkbox" name="enlevement" id="enlevement" value="1" <?= $enlevement?'checked':'' ?>>
          </div>

          <div class="form-step">
            <label for="palettes">Nombre de palettes EUR</label>
            <input type="number" name="palettes" id="palettes" min="0" step="1" value="<?= $palettes ?>">
          </div>

          <div class="form-step" style="text-align:center">
            <button type="submit">Calculer</button>
          </div>
        </form>
      </section>

      <section class="zone-resultat">
        <h2>Résultat</h2>
        <?php if ($bestCarrier !== null): ?>
          <p><strong><?= $carriers[$bestCarrier] ?></strong> : <?= number_format($best, 2, ',', ' ') ?> €</p>
          <p class="ecart">Payant pour : <strong><!-- À intégrer depuis Transport.php --> XX kg</strong> | Délai : <strong><!-- À venir --></strong></p>
          <p class="depliant" onclick="this.nextElementSibling.classList.toggle('cache')">Détails du calcul</p>
          <div class="cache"><pre><?= var_export($transport->debug[$bestCarrier] ?? [], true) ?></pre></div>

          <p class="depliant" onclick="this.nextElementSibling.classList.toggle('cache')">Frais de représentation / gardiennage</p>
          <div class="cache">Frais standards à prévoir selon CGV : représentation = XX €, gardiennage = YY €</div>
        <?php else: ?>
          <p>Aucun tarif disponible pour ces critères.</p>
        <?php endif; ?>
      </section>

      <?php if (!empty($results)): ?>
        <section class="zone-resultat">
          <h3>Autres transporteurs</h3>
          <table>
            <thead><tr><th>Transporteur</th><th>Tarif</th><th>Écart</th></tr></thead>
            <tbody>
            <?php foreach ($results as $code => $price): ?>
              <?php if ($code !== $bestCarrier && $price !== null): ?>
                <tr>
                  <td><?= $carriers[$code] ?></td>
                  <td><?= number_format($price, 2, ',', ' ') ?> €</td>
                  <td>+<?= number_format($price - $best, 2, ',', ' ') ?> €</td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
          </table>
        </section>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>

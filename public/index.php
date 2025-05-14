<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
$options = array_keys($transport->getOptionsList());
$carriers = ['xpo' => 'XPO', 'heppner' => 'Heppner', 'kn' => 'Kuehne+Nagel'];

$type = $_POST['type'] ?? '';
$adr = $_POST['adr'] ?? '';
$poids = isset($_POST['poids']) ? (float)$_POST['poids'] : null;
$opt = $_POST['option'] ?? '';
$dep = $_POST['departement'] ?? '';
$option_sup = $_POST['option_sup'] ?? ''; // exclusif
$enlevement = $_POST['enlevement'] ?? '';
$palettes = isset($_POST['palettes']) ? (int)$_POST['palettes'] : 0;

$results = [];
$best = null;
$bestCarrier = null;

if ($dep && $poids && $type && $adr && $opt) {
  $results = $transport->calculateAll($type, $adr, $poids, $opt); // TODO: intégrer options supp
  echo "<pre style='background:#eef;padding:1rem;border:1px solid #ccc'>";
  var_dump($_POST);
  var_dump($results);
  echo "</pre>";
  $valid = array_filter($results, fn($p) => $p !== null);
  if ($valid) {
    $best = min($valid);
    $bestCarrier = array_search($best, $results);
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
  <!-- <script src="assets/js/script.js" defer></script> -->
<!-- Script désactivé temporairement pour debug -->
</head>
<body>
  <div class="container">
    <header class="site-header">
      <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="logo">
      <h1>Comparateur de frais de port</h1>
      <nav><a href="admin/rates.php">Administration</a></nav>
    </header>

    <?php if ($results): ?>
      <section class="result-highlight">
        <h2>Meilleur tarif</h2>
        <?php if ($bestCarrier): ?>
          <p><strong><?= $carriers[$bestCarrier] ?> — <?= number_format($best, 2, ',', ' ') ?> €</strong></p>
        <?php else: ?>
          <p><em>Aucun tarif disponible pour ces critères.</em></p>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <main class="main-content">
      <section class="form-section">
        <form method="post" id="tarif-form">
          <!-- Bouton de soumission manuel pour debug -->
          <div class="form-step" style="text-align:center; margin-top:1rem;">
            <button type="submit">Calculer</button>
          </div>
          <div class="form-step">
            <label for="departement">Département</label>
            <input type="text" name="departement" id="departement" maxlength="2" pattern="\d{2}" required value="<?= htmlspecialchars($dep) ?>">
          </div>

          <div class="form-step">
            <label for="poids">Poids (kg)</label>
            <input type="number" name="poids" id="poids" step="0.1" min="0.1" required value="<?= htmlspecialchars($poids ?? '') ?>">
          </div>

          <div class="form-step">
            <label>Type d'envoi</label>
            <div class="radio-group">
              <input type="radio" name="type" value="colis" id="type-colis" <?= $type === 'colis' ? 'checked' : '' ?>>
              <label for="type-colis">Colis</label>
              <input type="radio" name="type" value="palette" id="type-palette" <?= $type === 'palette' ? 'checked' : '' ?>>
              <label for="type-palette">Palette</label>
            </div>
          </div>

          <div class="form-step">
            <label>ADR</label>
            <div class="radio-group">
              <input type="radio" name="adr" value="oui" id="adr-oui" <?= $adr === 'oui' ? 'checked' : '' ?>>
              <label for="adr-oui">Oui</label>
              <input type="radio" name="adr" value="non" id="adr-non" <?= $adr === 'non' ? 'checked' : '' ?>>
              <label for="adr-non">Non</label>
            </div>
          </div>

          <div class="form-step">
            <label>Option principale</label>
            <div class="radio-group">
              <?php foreach ($options as $o): ?>
                <input type="radio" name="option" value="<?= $o ?>" id="opt-<?= $o ?>" <?= $opt === $o ? 'checked' : '' ?>>
                <label for="opt-<?= $o ?>"><?= ucfirst(str_replace('_', ' ', $o)) ?></label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-step">
            <label>Options supplémentaires</label>
            <div class="radio-group">
              <input type="radio" name="option_sup" value="rdv" id="rdv" <?= $option_sup === 'rdv' ? 'checked' : '' ?>>
              <label for="rdv">Prise de RDV</label>
              <input type="radio" name="option_sup" value="datefixe" id="datefixe" <?= $option_sup === 'datefixe' ? 'checked' : '' ?>>
              <label for="datefixe">Date à prendre</label>
              <input type="radio" name="option_sup" value="premium13" id="premium13" <?= $option_sup === 'premium13' ? 'checked' : '' ?>>
              <label for="premium13">Star/Premium avant 13h</label>
              <input type="radio" name="option_sup" value="premium18" id="premium18" <?= $option_sup === 'premium18' ? 'checked' : '' ?>>
              <label for="premium18">Star/Premium avant 18h</label>
              <input type="checkbox" name="enlevement" id="enlevement" value="1" <?= $enlevement ? 'checked' : '' ?>>
              <label for="enlevement">Enlèvement</label>
            </div>
          </div>

          <div class="form-step">
            <label for="palettes">Nombre de palettes EUR</label>
            <input type="number" name="palettes" id="palettes" min="0" step="1" value="<?= $palettes ?>">
          </div>

          <div class="form-step" style="text-align:center">
            <button type="reset" onclick="window.location='index.php'">Réinitialiser</button>
          </div>
        </form>
      </section>

      <?php if ($results): ?>
        <section class="result-details">
          <h3>Comparaison complète</h3>
          <table>
            <thead><tr><th>Transporteur</th><th>Tarif</th><th>Écart</th></tr></thead>
            <tbody>
              <?php foreach ($results as $code => $price): ?>
                <tr>
                  <td><?= $carriers[$code] ?></td>
                  <td><?= $price !== null ? number_format($price, 2, ',', ' ') . ' €' : '<em>N/A</em>' ?></td>
                  <td><?= ($price !== null && $code !== $bestCarrier) ? '+' . number_format($price - $best, 2, ',', ' ') . ' €' : '-' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>

        <section class="debug-output">
          <h4>Détails techniques</h4>
          <pre><?= var_export($transport->debug, true) ?></pre>
        </section>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>

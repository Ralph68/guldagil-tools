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

$results = [];
$best = null;
$bestCarrier = null;

if ($dep && $poids && $type && $adr && $opt) {
  $results = $transport->calculateAll($type, $adr, $poids, $opt);
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Comparateur de frais de port</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <div class="container">
    <header class="site-header">
      <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="logo" />
      <h1>Comparateur de frais de port</h1>
      <nav><a href="admin/rates.php">Administration</a></nav>
    </header>

    <?php if ($results && $bestCarrier): ?>
    <section class="result-highlight">
      <h2>Transporteur le moins cher</h2>
      <p><strong><?= $carriers[$bestCarrier] ?> — <?= number_format($best, 2, ',', ' ') ?> €</strong></p>
    </section>
    <?php endif; ?>

    <main class="main-content">
      <section class="form-section">
        <form method="post" id="tarif-form">
          <div class="form-step">
            <label for="departement">Département</label>
            <input type="text" name="departement" id="departement" maxlength="2" pattern="\d{2}" required value="<?= htmlspecialchars($dep) ?>" />
          </div>

          <div class="form-step">
            <label for="poids">Poids (kg)</label>
            <input type="number" name="poids" id="poids" step="0.1" min="0.1" required value="<?= htmlspecialchars($poids ?? '') ?>" />
          </div>

          <div class="form-step">
            <label>Type d'envoi</label>
            <div class="radio-group">
              <input type="radio" name="type" value="colis" id="type-colis" <?= $type === 'colis' ? 'checked' : '' ?> />
              <label for="type-colis">Colis</label>
              <input type="radio" name="type" value="palette" id="type-palette" <?= $type === 'palette' ? 'checked' : '' ?> />
              <label for="type-palette">Palette</label>
            </div>
          </div>

          <div class="form-step">
            <label>ADR</label>
            <div class="radio-group">
              <input type="radio" name="adr" value="oui" id="adr-oui" <?= $adr === 'oui' ? 'checked' : '' ?> />
              <label for="adr-oui">Oui</label>
              <input type="radio" name="adr" value="non" id="adr-non" <?= $adr === 'non' ? 'checked' : '' ?> />
              <label for="adr-non">Non</label>
            </div>
          </div>

          <div class="form-step">
            <label>Option</label>
            <div class="radio-group">
              <?php foreach ($options as $o): ?>
                <input type="radio" name="option" value="<?= $o ?>" id="opt-<?= $o ?>" <?= $opt === $o ? 'checked' : '' ?> />
                <label for="opt-<?= $o ?>"><?= ucfirst(str_replace('_', ' ', $o)) ?></label>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="margin-top: 1rem; text-align: center">
            <button type="reset" onclick="window.location='index.php'">Réinitialiser</button>
          </div>
        </form>
      </section>

      <?php if ($results && $bestCarrier): ?>
        <section class="result-details">
          <h3>Comparaison complète</h3>
          <table>
            <thead><tr><th>Transporteur</th><th>Tarif</th><th>Écart</th></tr></thead>
            <tbody>
              <?php foreach ($results as $code => $price): ?>
                <?php if ($price !== null): ?>
                  <tr>
                    <td><?= $carriers[$code] ?></td>
                    <td><?= number_format($price, 2, ',', ' ') ?> €</td>
                    <td><?= ($code !== $bestCarrier) ? '+' . number_format($price - $best, 2, ',', ' ') . ' €' : '-' ?></td>
                  </tr>
                <?php endif; ?>
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

  <script>
    const dep = document.getElementById('departement');
    const poids = document.getElementById('poids');

    dep.addEventListener('input', () => {
      if (dep.value.length === 2) {
        poids.focus();
      }
    });

    dep.addEventListener('focus', () => {
      dep.value = '';
    });
  </script>
</body>
</html>

<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
$options = array_keys($transport->getOptionsList());
$carriers = ['xpo' => 'XPO', 'heppner' => 'Heppner', 'kn' => 'Kuehne+Nagel'];

$type = $_POST['type'] ?? 'colis';
$adr = $_POST['adr'] ?? 'non';
$poids = isset($_POST['poids']) ? (float)$_POST['poids'] : null;
$opt = $_POST['option'] ?? ($options[0] ?? 'standard');
$results = [];
$best = null;

if ($poids && $poids > 0) {
    $results = $transport->calculateAll($type, $adr, $poids, $opt);
    $validPrices = array_filter($results, fn($v) => $v !== null);
    if (!empty($validPrices)) {
        $best = min($validPrices);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calculateur de frais de port</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { font-family: sans-serif; margin: 0; padding: 1rem; }
    form label { display: block; margin-top: 1em; font-weight: bold; }
    form input, form select { width: 100%; padding: 0.5rem; font-size: 1rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1em; }
    th, td { padding: 0.5rem; border: 1px solid #ccc; }
    .best { background-color: #d3fcd3; }
  </style>
</head>
<body>
  <h1>Calculateur de frais de port</h1>

  <form id="calc-form" method="post">
    <label>Département (2 chiffres)
      <input type="text" name="departement" id="departement" maxlength="2" pattern="\d{2}" inputmode="numeric" required>
    </label>

    <label>Type d'envoi
      <select name="type">
        <option value="colis" <?= $type === 'colis' ? 'selected' : '' ?>>Colis</option>
        <option value="palette" <?= $type === 'palette' ? 'selected' : '' ?>>Palette</option>
      </select>
    </label>

    <label>ADR
      <select name="adr">
        <option value="non" <?= $adr === 'non' ? 'selected' : '' ?>>Non</option>
        <option value="oui" <?= $adr === 'oui' ? 'selected' : '' ?>>Oui</option>
      </select>
    </label>

    <label>Poids (kg)
      <input type="number" name="poids" id="poids" step="0.1" min="0.1" required value="<?= htmlspecialchars($poids ?? '') ?>">
    </label>

    <label>Option
      <select name="option">
        <?php foreach ($options as $o): ?>
          <option value="<?= $o ?>" <?= $opt === $o ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $o)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <button type="submit">Calculer</button>
  </form>

  <?php if ($results): ?>
    <h2>Résultats</h2>
    <table>
      <thead>
        <tr><th>Transporteur</th><th>Prix estimé</th></tr>
      </thead>
      <tbody>
        <?php foreach ($results as $code => $price): ?>
          <tr class="<?= ($price !== null && $price === $best) ? 'best' : '' ?>">
            <td><?= htmlspecialchars($carriers[$code]) ?></td>
            <td><?= $price !== null ? number_format($price, 2, ',', ' ') . ' €' : '<em>N/A</em>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <script>
    const depInput = document.getElementById('departement');
    const poidsInput = document.getElementById('poids');

    depInput.addEventListener('input', () => {
      if (depInput.value.length === 2) {
        poidsInput.focus();
      }
    });
  </script>
</body>
</html>

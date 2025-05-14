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
$step = 1;
$results = [];
$best = null;

if (!empty($_POST['departement'])) $step = 2;
if ($poids && $poids > 0) $step = 3;
if (!empty($type) && !empty($adr) && !empty($opt)) {
    $results = $transport->calculateAll($type, $adr, $poids, $opt);
    $valid = array_filter($results, fn($p) => $p !== null);
    if ($valid) $best = min($valid);
    $step = 4;
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
    body { font-family: sans-serif; padding: 1rem; margin: 0; }
    .step { display: none; margin-bottom: 1rem; }
    .step.active { display: block; }
    button[type="submit"] { padding: 0.75rem 1.5rem; font-size: 1.1rem; margin-top: 1rem; }
    .option-btns button { margin-right: 0.5rem; margin-top: 0.5rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; }
    .best { background: #d3fcd3; }
  </style>
</head>
<body>
  <h1>Calculateur de frais de port</h1>
  <form method="post">
    <div class="step <?= $step >= 1 ? 'active' : '' ?>">
      <label>Code département (2 chiffres)
        <input type="text" name="departement" maxlength="2" pattern="\d{2}" required value="<?= htmlspecialchars($_POST['departement'] ?? '') ?>">
      </label>
    </div>

    <?php if ($step >= 2): ?>
    <div class="step <?= $step >= 2 ? 'active' : '' ?>">
      <label>Poids (kg)
        <input type="number" name="poids" step="0.1" min="0.1" required value="<?= htmlspecialchars($poids ?? '') ?>">
      </label>
    </div>
    <?php endif; ?>

    <?php if ($step >= 3): ?>
    <div class="step <?= $step >= 3 ? 'active' : '' ?>">
      <label>Type d'envoi</label>
      <div class="option-btns">
        <button type="submit" name="type" value="colis" class="<?= $type === 'colis' ? 'selected' : '' ?>">Colis</button>
        <button type="submit" name="type" value="palette" class="<?= $type === 'palette' ? 'selected' : '' ?>">Palette</button>
      </div>

      <label>ADR</label>
      <div class="option-btns">
        <button type="submit" name="adr" value="oui" class="<?= $adr === 'oui' ? 'selected' : '' ?>">Oui</button>
        <button type="submit" name="adr" value="non" class="<?= $adr === 'non' ? 'selected' : '' ?>">Non</button>
      </div>

      <label>Option</label>
      <div class="option-btns">
        <?php foreach ($options as $o): ?>
          <button type="submit" name="option" value="<?= $o ?>" class="<?= $opt === $o ? 'selected' : '' ?>">
            <?= ucfirst(str_replace('_', ' ', $o)) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </form>

  <?php if ($step >= 4 && $results): ?>
    <h2>Résultats</h2>
    <table>
      <thead><tr><th>Transporteur</th><th>Prix estimé</th></tr></thead>
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
</body>
</html>

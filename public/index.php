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

// Fausse valeur pour maquette
$results = [
  'xpo' => 18.50,
  'heppner' => 21.90,
  'kn' => 17.30
];
$best = min($results);
$bestCarrier = array_search($best, $results);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Calculateur de frais de port</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { font-family: sans-serif; margin: 0; padding: 1rem; background: #f4f4f4; }
    header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    header img { height: 48px; }
    form, .recap, .main-result, .more-results, .details {
      max-width: 600px; margin: auto; background: white;
      padding: 1rem; border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1rem;
    }
    .form-group { margin-bottom: 1rem; display: none; opacity: 0; transition: all 0.5s ease; }
    .form-group.active { display: block; opacity: 1; }
    label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
    input[type="text"], input[type="number"] {
      width: 100%; padding: 0.6rem; font-size: 1rem; border: 1px solid #ccc; border-radius: 4px;
    }
    .btn-group { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .btn-group label {
      flex: 1 0 45%;
      padding: 0.6rem; background: #eee; text-align: center;
      border: 1px solid #ccc; border-radius: 4px; cursor: pointer;
    }
    .btn-group input[type="radio"] { display: none; }
    .btn-group input[type="radio"]:checked + label {
      background: #007acc; color: white; border-color: #007acc;
    }
    .main-result { background: #e7f9e7; text-align: center; font-size: 1.2rem; }
    .main-result strong { font-size: 1.6rem; display: block; margin: 0.5rem 0; }
    .toggle-btn { text-align: center; margin: 1rem 0; }
    .toggle-btn button {
      background: none; border: none; color: #007acc;
      text-decoration: underline; cursor: pointer; font-size: 1rem;
    }
    .more-results table { width: 100%; border-collapse: collapse; }
    .more-results th, .more-results td {
      padding: 0.5rem; border: 1px solid #ccc; text-align: left;
    }
    .details pre {
      background: #f0f0f0; padding: 1rem; font-size: 0.85rem; border-radius: 4px;
      overflow-x: auto;
    }
  </style>
</head>
<body>
  <header>
    <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil">
    <h1>Calculateur de frais de port</h1>
  </header>

  <form method="post" id="tarif-form">
    <div class="form-group active" id="step1">
      <label for="departement">Code département (2 chiffres)</label>
      <input type="text" name="departement" id="departement" maxlength="2" pattern="\d{2}" required value="<?= htmlspecialchars($dep) ?>">
    </div>

    <div class="form-group" id="step2">
      <label for="poids">Poids (kg)</label>
      <input type="number" name="poids" id="poids" step="0.1" min="0.1" value="<?= htmlspecialchars($poids ?? '') ?>">
    </div>

    <div class="form-group" id="step3">
      <label>Type d'envoi</label>
      <div class="btn-group">
        <input type="radio" name="type" value="colis" id="type-colis" <?= $type === 'colis' ? 'checked' : '' ?>>
        <label for="type-colis">Colis</label>
        <input type="radio" name="type" value="palette" id="type-palette" <?= $type === 'palette' ? 'checked' : '' ?>>
        <label for="type-palette">Palette</label>
      </div>

      <label>ADR</label>
      <div class="btn-group">
        <input type="radio" name="adr" value="oui" id="adr-oui" <?= $adr === 'oui' ? 'checked' : '' ?>>
        <label for="adr-oui">Oui</label>
        <input type="radio" name="adr" value="non" id="adr-non" <?= $adr === 'non' ? 'checked' : '' ?>>
        <label for="adr-non">Non</label>
      </div>

      <label>Option</label>
      <div class="btn-group">
        <?php foreach ($options as $o): ?>
          <input type="radio" name="option" value="<?= $o ?>" id="opt-<?= $o ?>" <?= $opt === $o ? 'checked' : '' ?>>
          <label for="opt-<?= $o ?>"><?= ucfirst(str_replace('_', ' ', $o)) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
  </form>

  <?php if ($dep || $poids || $type || $adr || $opt): ?>
    <div class="recap">
      <strong>Résumé :</strong><br>
      Dépt <?= htmlspecialchars($dep) ?>, <?= $poids ?> kg, <?= $type ?>, ADR <?= $adr ?>, Option <?= ucfirst(str_replace('_',' ',$opt)) ?>
    </div>
  <?php endif; ?>

  <?php if ($results): ?>
    <div class="main-result">
      <p>Transporteur le moins cher :</p>
      <strong><?= $carriers[$bestCarrier] ?> — <?= number_format($best, 2, ',', ' ') ?> €</strong>
    </div>

    <div class="toggle-btn">
      <button type="button" onclick="document.getElementById('more-results').classList.toggle('hidden')">
        Voir les autres transporteurs
      </button>
    </div>

    <div id="more-results" class="more-results hidden">
      <table>
        <thead><tr><th>Transporteur</th><th>Prix</th><th>Écart</th></tr></thead>
        <tbody>
          <?php foreach ($results as $code => $price): ?>
            <?php if ($code !== $bestCarrier): ?>
              <tr>
                <td><?= $carriers[$code] ?></td>
                <td><?= number_format($price, 2, ',', ' ') ?> €</td>
                <td>+<?= number_format($price - $best, 2, ',', ' ') ?> €</td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="details">
      <h3>Détails de calcul (debug)</h3>
      <pre><?= var_export($results, true) ?></pre>
    </div>
  <?php endif; ?>

  <script>
    const dep = document.getElementById('departement');
    const poids = document.getElementById('poids');
    const form = document.getElementById('tarif-form');

    function showStep(id) {
      document.getElementById(id).classList.add('active');
    }

    dep.addEventListener('input', () => {
      if (dep.value.length === 2) {
        showStep('step2');
        poids.focus();
      }
    });

    poids.addEventListener('input', () => {
      if (poids.value && parseFloat(poids.value) > 0) {
        showStep('step3');
      }
    });

    form.addEventListener('change', () => {
      if (
        dep.value.length === 2 &&
        poids.value && parseFloat(poids.value) > 0 &&
        form.type.value &&
        form.adr.value &&
        form.option.value
      ) {
        form.submit();
      }
    });
  </script>
</body>
</html>

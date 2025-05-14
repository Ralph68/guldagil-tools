<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
$errors    = [];
$results   = [];
$best      = null;

// liste pour l’affichage
$carriers = ['xpo'=>'XPO', 'heppner'=>'Heppner', 'kn'=>'Kuehne+Nagel'];
$options  = $transport->getOptionsList();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type   = $_POST['type']        ?? '';
    $adr    = $_POST['adr']         ?? '';
    $weight = (float)($_POST['poids'] ?? 0);
    $opt    = $_POST['option']      ?? '';

    // validations basiques…
    if ($weight <= 0)   $errors[] = "Le poids doit être > 0.";
    if (!$type||!$adr||!$opt) $errors[] = "Tous les champs sont requis.";

    if (empty($errors)) {
        // calcule pour chaque transporteur
        $results = $transport->calculateAll($type, $adr, $weight, $opt);
        // repère le prix minimal non-null
        $filtered = array_filter($results, fn($v) => $v !== null);
        if ($filtered) {
            $best = min($filtered);
        } else {
            $errors[] = "Aucun tarif disponible pour ces critères.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Comparateur de frais de port</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .best { background: #c8e6c9; }
    table { border-collapse: collapse; width: 100%; margin-top:1em; }
    th,td { border:1px solid #ccc; padding:0.5em; text-align:left; }
  </style>
</head>
<body>
  <h1>Comparateur de frais de port</h1>

  <?php if ($errors): ?>
    <ul style="color:red;">
      <?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach ?>
    </ul>
  <?php endif; ?>

  <form method="post">
    <!-- Vos champs type/adr/poids/options habituels -->
    <label>Type d’envoi
      <select name="type" required>
        <option value="colis">Colis</option>
        <option value="palette">Palette</option>
      </select>
    </label>
    <label>ADR
      <select name="adr" required>
        <option value="non">Non</option>
        <option value="oui">Oui</option>
      </select>
    </label>
    <label>Poids (kg)
      <input type="number" name="poids" step="0.1" min="0.1" required>
    </label>
    <label>Option
      <select name="option" required>
        <?php foreach($options as $code=>$coef): ?>
          <option value="<?= $code ?>">
            <?= ucfirst(str_replace('_',' ',$code)) ?> (×<?= $coef ?>)
          </option>
        <?php endforeach ?>
      </select>
    </label>
    <button type="submit">Comparer</button>
  </form>

  <?php if ($results): ?>
    <table>
      <tr><th>Transporteur</th><th>Prix (€)</th></tr>
      <?php foreach($results as $code=>$price): ?>
        <tr class="<?= ($price!==null && $price === $best) ? 'best' : '' ?>">
          <td><?= $carriers[$code] ?></td>
          <td>
            <?= $price!==null 
                ? number_format($price,2) 
                : '<em>N/A</em>' ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>


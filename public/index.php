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
$rdv = $_POST['rdv'] ?? '';
$datefixe = $_POST['datefixe'] ?? '';
$premium = $_POST['premium'] ?? '';
$enlevement = $_POST['enlevement'] ?? '';
$palettes = isset($_POST['palettes']) ? (int)$_POST['palettes'] : 0;

$results = [];
$best = null;
$bestCarrier = null;

if ($dep && $poids && $type && $adr && $opt) {
  $results = $transport->calculateAll($type, $adr, $poids, $opt); // TODO: intégrer les autres options
  $valid = array_filter($results, fn($p) => $p !== null);
  if ($valid) {
    $best = min($valid);
    $bestCarrier = array_search($best, $results);
  }
}
?>

<!-- Formulaire partiel extrait -->
<form method="post" id="tarif-form">
  <!-- Autres champs -->

  <div class="form-step">
    <label>Options supplémentaires</label>
    <div class="radio-group">
      <input type="checkbox" name="rdv" id="rdv" value="1" <?= $rdv ? 'checked' : '' ?>>
      <label for="rdv">Prise de RDV</label>

      <input type="checkbox" name="datefixe" id="datefixe" value="1" <?= $datefixe ? 'checked' : '' ?>>
      <label for="datefixe">Date à prendre</label>

      <input type="checkbox" name="premium" id="premium" value="1" <?= $premium ? 'checked' : '' ?>>
      <label for="premium">Star/Premium avant 13h</label>

      <input type="checkbox" name="premium" id="premium18" value="18" <?= $premium === '18' ? 'checked' : '' ?>>
      <label for="premium18">Star/Premium avant 18h</label>

      <input type="checkbox" name="enlevement" id="enlevement" value="1" <?= $enlevement ? 'checked' : '' ?>>
      <label for="enlevement">Enlèvement</label>
    </div>
  </div>

  <div class="form-step">
    <label for="palettes">Nombre de palettes EUR</label>
    <input type="number" name="palettes" id="palettes" min="0" step="1" value="<?= $palettes ?>">
  </div>
</form>

<!-- Debug TEMPORAIRE -->
<pre style="background:#ffe;text-align:left;padding:1rem;border:1px solid #ccc;">
<?php var_dump($results); ?>
</pre>

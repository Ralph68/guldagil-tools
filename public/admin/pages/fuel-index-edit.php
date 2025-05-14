<?php
// admin/pages/fuel-index-edit.php
require_once __DIR__.'/../models/FuelIndex.php';
$id     = $_GET['id']     ?? null;
$errors = [];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $data = [
    'month' => $_POST['month'],           // format YYYY-MM
    'value' => floatval(str_replace(',','.',$_POST['value']))
  ];
  if($data['month']==='') $errors[] = 'Le mois est requis.';
  if(empty($errors)){
    $id 
      ? FuelIndex::update($id, $data)
      : FuelIndex::create($data);
    header('Location:index.php?page=fuel-indices'); exit;
  }
}

$entry = $id
  ? FuelIndex::getById($id)
  : ['month'=>'','value'=>''];
?>

<h1><?= $id ? 'Modifier' : 'Ajouter' ?> un indice Gasoil</h1>
<?php if($errors): ?>
  <ul class="errors">
    <?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach;?>
  </ul>
<?php endif; ?>
<form method="post">
  <label>Mois<br>
    <input type="month" name="month" value="<?=htmlspecialchars($entry['month'])?>">
  </label><br>
  <label>Valeur (€)<br>
    <input type="text" name="value" value="<?=htmlspecialchars($entry['value'])?>">
  </label><br>
  <button type="submit">Enregistrer</button>
  <a href="index.php?page=fuel-indices">Annuler</a>
</form>

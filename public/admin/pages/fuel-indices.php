<?php
// admin/pages/fuel-indices.php
require_once __DIR__.'/../models/FuelIndex.php';
$indices = FuelIndex::getAll(); 
?>

<h1>Indices Gasoil</h1>
<p><a href="index.php?page=fuel-index-edit" class="button">Ajouter un indice</a></p>
<table>
  <thead><tr><th>Mois</th><th>Valeur (€)</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($indices as $i): ?>
    <tr>
      <td><?=htmlspecialchars($i['month'])?></td>
      <td><?=number_format($i['value'],2,',',' ')?></td>
      <td>
        <a href="index.php?page=fuel-index-edit&id=<?=$i['id']?>">Modifier</a>
        <a href="index.php?page=fuel-indices&delete=<?=$i['id']?>" onclick="return confirm('Supprimer ?')">Supprimer</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php if(isset($_GET['delete'])){ FuelIndex::delete($_GET['delete']); header('Location:index.php?page=fuel-indices'); exit;} ?>

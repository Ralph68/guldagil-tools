<?php
// public/admin/pages/carriers.php
// Liste des transporteurs (fragment injecté par admin/index.php)

declare(strict_types=1);

// Charger la classe Transport depuis lib (remonte trois niveaux)
require_once dirname(__DIR__, 3) . '/lib/Transport.php';

// Instancier le modèle en lui passant la connexion PDO ($db vient de config.php)
$model = new Transport($db);

// Suppression si demandé
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    // Si vous avez implementé une méthode delete en base, sinon ajustez
    // $model->deleteCarrier($id);
    header('Location: index.php?page=carriers');
    exit;
}

// Récupérer la liste des codes de transporteurs
$codes = $model->getCarriers();

// Si vous préférez récupérer depuis la base, utilisez :
// $transporteurs = $model->fetchAllFromDatabase();
?>

<h1>Transporteurs</h1>
<p>
  <a href="index.php?page=carrier-edit" class="button">Ajouter un transporteur</a>
</p>

<table>
  <thead>
    <tr>
      <th>Code</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($codes as $code): ?>
      <tr>
        <td><?= htmlspecialchars($code, ENT_QUOTES) ?></td>
        <td>
          <a href="index.php?page=carrier-edit&code=<?= urlencode($code) ?>">Modifier</a>
          <!--
            Si vous avez une méthode deleteCarrier :
            <a href="index.php?page=carriers&delete=<?= $code ?>" onclick="return confirm('Supprimer ?')">Supprimer</a>
          -->
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

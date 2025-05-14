<?php
require __DIR__ . '/../config.php';

// Suppression d'une tranche si demandé
if (isset($_GET['delete'])) {
    $id = (int)\$_GET['delete'];
    \$stmt = \$db->prepare("DELETE FROM gul_taxes_transporteurs WHERE id = ?");
    \$stmt->execute([\$id]);
    header('Location: rates.php');
    exit;
}

// Récupération de tous les barèmes
\$stmt = \$db->query("SELECT * FROM gul_taxes_transporteurs ORDER BY transporteur, type, adr, poids_max");
\$rates = \$stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des barèmes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
      table { width:100%; border-collapse: collapse; margin-top:1rem; }
      th, td { border:1px solid #ccc; padding:0.5rem; text-align:left; }
      th { background:#f0f0f0; }
      .btn { display:inline-block; margin-bottom:1rem; padding:0.5rem 1rem; background:#007acc; color:#fff; text-decoration:none; border-radius:4px; }
      .btn:hover { background:#005f99; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Gestion des barèmes</h1>
    </header>
    <main>
        <div class="form-container">
            <a href="rate-edit.php" class="btn">+ Ajouter une tranche</a>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Transporteur</th>
                        <th>Type</th>
                        <th>ADR</th>
                        <th>Poids max (kg)</th>
                        <th>Prix (€)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (\$rates as \$r): ?>
                    <tr>
                        <td><?= htmlspecialchars(\$r['id']) ?></td>
                        <td><?= htmlspecialchars(strtoupper(\$r['transporteur'])) ?></td>
                        <td><?= htmlspecialchars(ucfirst(\$r['type'])) ?></td>
                        <td><?= htmlspecialchars(strtoupper(\$r['adr'])) ?></td>
                        <td><?= htmlspecialchars(\$r['poids_max']) ?></td>
                        <td><?= htmlspecialchars(\$r['prix']) ?></td>
                        <td>
                            <a href="rate-edit.php?id=<?= \$r['id'] ?>">Éditer</a> |
                            <a href="rates.php?delete=<?= \$r['id'] ?>" onclick="return confirm('Supprimer cette tranche ?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>

<?php
require __DIR__ . '/../config.php';

// Listes de contrôle
$carriers = ['xpo'=>'XPO', 'heppner'=>'Heppner', 'kn'=>'Kuehne+Nagel'];
$types     = ['colis'=>'Colis', 'palette'=>'Palette'];
$adrs      = ['non'=>'Non', 'oui'=>'Oui'];

$errors = [];
// Récupération de l'ID si édition
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
// Valeurs par défaut
$row = ['transporteur'=>'', 'type'=>'', 'adr'=>'', 'poids_max'=>'', 'prix'=>''];

if ($id) {
    // Chargement des données existantes
    $stmt = $db->prepare("SELECT * FROM gul_taxes_transporteurs WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $row = $existing;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des valeurs postées
    $transporteur = $_POST['transporteur'] ?? '';
    $type        = $_POST['type']        ?? '';
    $adr         = $_POST['adr']         ?? '';
    $poids_max   = $_POST['poids_max']   ?? '';
    $prix        = $_POST['prix']        ?? '';

    // Validations
    if (!isset($carriers[$transporteur])) {
        $errors[] = 'Transporteur invalide.';
    }
    if (!isset($types[$type])) {
        $errors[] = 'Type d’envoi invalide.';
    }
    if (!isset($adrs[$adr])) {
        $errors[] = 'Valeur ADR invalide.';
    }
    if (!is_numeric($poids_max) || (float)$poids_max <= 0) {
        $errors[] = 'Poids max invalide.';
    }
    if (!is_numeric($prix) || (float)$prix <= 0) {
        $errors[] = 'Prix invalide.';
    }

    if (empty($errors)) {
        if ($id) {
            // Mise à jour
            $stmt = $db->prepare(
                "UPDATE gul_taxes_transporteurs
                 SET transporteur = ?, type = ?, adr = ?, poids_max = ?, prix = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $transporteur,
                $type,
                $adr,
                (float)$poids_max,
                (float)$prix,
                $id
            ]);
        } else {
            // Insertion
            $stmt = $db->prepare(
                "INSERT INTO gul_taxes_transporteurs
                 (transporteur, type, adr, poids_max, prix)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $transporteur,
                $type,
                $adr,
                (float)$poids_max,
                (float)$prix
            ]);
        }
        header('Location: rates.php');
        exit;
    }
    // En cas d'erreurs, on réaffecte les valeurs pour le formulaire
    $row = [
        'transporteur' => $transporteur,
        'type'         => $type,
        'adr'          => $adr,
        'poids_max'    => $poids_max,
        'prix'         => $prix
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Éditer' : 'Ajouter' ?> une tranche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container {
            max-width: 400px;
            margin: 2rem auto;
            background: #fff;
            padding: 1.5rem;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-container label {
            display: block;
            margin-top: 1rem;
            font-weight: 600;
        }
        .form-container input,
        .form-container select {
            width: 100%;
            padding: 0.6rem;
            margin-top: 0.25rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-container button {
            margin-top: 1.5rem;
            padding: 0.75rem;
            background: #007acc;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
        }
        .form-container button:hover {
            background: #005f99;
        }
        .errors { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2><?= $id ? 'Éditer' : 'Ajouter' ?> une tranche tarifaire</h2>
        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post">
            <label for="transporteur">Transporteur</label>
            <select id="transporteur" name="transporteur" required>
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($carriers as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= $row['transporteur'] === $code ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="type">Type d’envoi</label>
            <select id="type" name="type" required>
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($types as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= $row['type'] === $code ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="adr">ADR</label>
            <select id="adr" name="adr" required>
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($adrs as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= $row['adr'] === $code ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="poids_max">Poids maximum (kg)</label>
            <input type="number" step="0.1" id="poids_max" name="poids_max" value="<?= htmlspecialchars($row['poids_max']) ?>" required>

            <label for="prix">Prix (€)</label>
            <input type="number" step="0.01" id="prix" name="prix" value="<?= htmlspecialchars($row['prix']) ?>" required>

            <button type="submit"><?= $id ? 'Mettre à jour' : 'Ajouter' ?></button>
        </form>
        <p><a href="rates.php">← Retour à la liste</a></p>
    </div>
</body>
</html>

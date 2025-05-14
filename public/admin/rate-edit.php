<?php
require __DIR__ . '/../config.php';

$carriers = ['xpo'=>'XPO', 'heppner'=>'Heppner', 'kn'=>'Kuehne+Nagel'];
$types    = ['colis'=>'Colis', 'palette'=>'Palette'];
$adrs     = ['non'=>'Non', 'oui'=>'Oui'];

$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Valeurs par défaut
$row = [
    'transporteur'=>'', 'type'=>'', 'adr'=>'', 'poids_max'=>'', 'prix'=>'',
    'coefficient_standard' => '', 'coefficient_premium' => '', 'coefficient_rdv' => '', 'coefficient_date_fixe' => ''
];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM gul_taxes_transporteurs WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $row = array_merge($row, $existing);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($row) as $key) {
        $row[$key] = $_POST[$key] ?? '';
    }

    // Validation de base
    if (!isset($carriers[$row['transporteur']])) {
        $errors[] = 'Transporteur invalide.';
    }
    if (!isset($types[$row['type']])) {
        $errors[] = 'Type d\'envoi invalide.';
    }
    if (!isset($adrs[$row['adr']])) {
        $errors[] = 'Valeur ADR invalide.';
    }
    if (!is_numeric($row['poids_max']) || $row['poids_max'] <= 0) {
        $errors[] = 'Poids max invalide.';
    }
    if (!is_numeric($row['prix']) || $row['prix'] <= 0) {
        $errors[] = 'Prix invalide.';
    }

    // Validation des coefficients
    foreach (['coefficient_standard', 'coefficient_premium', 'coefficient_rdv', 'coefficient_date_fixe'] as $col) {
        if (!is_numeric($row[$col]) || $row[$col] <= 0) {
            $errors[] = "Coefficient invalide pour $col.";
        }
    }

    if (empty($errors)) {
        if ($id) {
            $stmt = $db->prepare("UPDATE gul_taxes_transporteurs SET transporteur=?, type=?, adr=?, poids_max=?, prix=?, coefficient_standard=?, coefficient_premium=?, coefficient_rdv=?, coefficient_date_fixe=? WHERE id=?");
            $stmt->execute([
                $row['transporteur'], $row['type'], $row['adr'],
                $row['poids_max'], $row['prix'],
                $row['coefficient_standard'], $row['coefficient_premium'],
                $row['coefficient_rdv'], $row['coefficient_date_fixe'], $id
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO gul_taxes_transporteurs (transporteur, type, adr, poids_max, prix, coefficient_standard, coefficient_premium, coefficient_rdv, coefficient_date_fixe) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $row['transporteur'], $row['type'], $row['adr'],
                $row['poids_max'], $row['prix'],
                $row['coefficient_standard'], $row['coefficient_premium'],
                $row['coefficient_rdv'], $row['coefficient_date_fixe']
            ]);
        }
        header('Location: rates.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Modifier' : 'Ajouter' ?> une tranche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container { max-width: 500px; margin: 2rem auto; background: #fff; padding: 1.5rem; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 1rem; font-weight: bold; }
        input, select { width: 100%; padding: 0.6rem; margin-top: 0.25rem; border: 1px solid #ccc; border-radius: 4px; }
        button { margin-top: 1.5rem; padding: 0.75rem; background: #007acc; color: #fff; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 1rem; }
        .errors { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2><?= $id ? 'Modifier' : 'Ajouter' ?> une tranche tarifaire</h2>

        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post">
            <label for="transporteur">Transporteur</label>
            <select name="transporteur" id="transporteur" required>
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($carriers as $code => $label): ?>
                    <option value="<?= $code ?>" <?= $row['transporteur'] === $code ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <label for="type">Type</label>
            <select name="type" id="type" required>
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($types as $code => $label): ?>
                    <option value="<?= $code ?>" <?= $row['type'] === $code ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <label for="adr">ADR</label>
            <select name="adr" id="adr" required>
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($adrs as $code => $label): ?>
                    <option value="<?= $code ?>" <?= $row['adr'] === $code ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <label for="poids_max">Poids max (kg)</label>
            <input type="number" step="0.01" name="poids_max" id="poids_max" value="<?= htmlspecialchars($row['poids_max']) ?>" required>

            <label for="prix">Prix €</label>
            <input type="number" step="0.01" name="prix" id="prix" value="<?= htmlspecialchars($row['prix']) ?>" required>

            <label for="coefficient_standard">Coefficient standard</label>
            <input type="number" step="0.01" name="coefficient_standard" id="coefficient_standard" value="<?= htmlspecialchars($row['coefficient_standard']) ?>" required>

            <label for="coefficient_premium">Coefficient premium</label>
            <input type="number" step="0.01" name="coefficient_premium" id="coefficient_premium" value="<?= htmlspecialchars($row['coefficient_premium']) ?>" required>

            <label for="coefficient_rdv">Coefficient RDV</label>
            <input type="number" step="0.01" name="coefficient_rdv" id="coefficient_rdv" value="<?= htmlspecialchars($row['coefficient_rdv']) ?>" required>

            <label for="coefficient_date_fixe">Coefficient Date Fixe</label>
            <input type="number" step="0.01" name="coefficient_date_fixe" id="coefficient_date_fixe" value="<?= htmlspecialchars($row['coefficient_date_fixe']) ?>" required>

            <button type="submit"><?= $id ? 'Mettre à jour' : 'Ajouter' ?></button>
        </form>
        <p><a href="rates.php">&larr; Retour à la liste</a></p>
    </div>
</body>
</html>

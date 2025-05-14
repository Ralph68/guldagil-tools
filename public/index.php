<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
$errors    = [];
$results   = [];
$best      = null;

// Définitions pour l’affichage
$carriers = ['xpo' => 'XPO', 'heppner' => 'Heppner', 'kn' => 'Kuehne+Nagel'];
$options  = $transport->getOptionsList();

// Récupération des valeurs postées
$type   = $_POST['type']    ?? '';
$adr    = $_POST['adr']     ?? '';
$weight = isset($_POST['poids']) ? (float)$_POST['poids'] : null;
$opt    = $_POST['option']  ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validations
    if (!in_array($type, ['colis', 'palette'], true)) {
        $errors[] = 'Type d’envoi invalide.';
    }
    if (!in_array($adr, ['oui', 'non'], true)) {
        $errors[] = 'Valeur ADR invalide.';
    }
    if ($weight <= 0) {
        $errors[] = 'Le poids doit être supérieur à 0.';
    }
    if (!isset($options[$opt])) {
        $errors[] = 'Option sélectionnée invalide.';
    }

    if (empty($errors)) {
        // Calcul pour chaque transporteur
        $results = $transport->calculateAll($type, $adr, $weight, $opt);
        // On garde les tarifs disponibles
        $validPrices = array_filter($results, fn($v) => $v !== null);
        if (!empty($validPrices)) {
            $best = min($validPrices);
        } else {
            $errors[] = 'Aucun tarif disponible pour ces critères.';
        }
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Comparateur de frais de port</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .best { background-color: #c8e6c9; }
        table { border-collapse: collapse; width: 100%; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: left; }
    </style>
</head>
<body>
    <h1>Comparateur de frais de port</h1>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <label for="type">Type d’envoi</label>
        <select id="type" name="type" required>
            <option value="">-- Sélectionnez --</option>
            <option value="colis" <?= $type === 'colis' ? 'selected' : '' ?>>Colis</option>
            <option value="palette" <?= $type === 'palette' ? 'selected' : '' ?>>Palette</option>
        </select>

        <label for="adr">ADR</label>
        <select id="adr" name="adr" required>
            <option value="">-- Sélectionnez --</option>
            <option value="non" <?= $adr === 'non' ? 'selected' : '' ?>>Non</option>
            <option value="oui" <?= $adr === 'oui' ? 'selected' : '' ?>>Oui</option>
        </select>

        <label for="poids">Poids (kg)</label>
        <input type="number" id="poids" name="poids" step="0.1" min="0.1" required value="<?= htmlspecialchars($weight) ?>">

        <label for="option">Option</label>
        <select id="option" name="option" required>
            <option value="">-- Sélectionnez --</option>
            <?php foreach ($options as $code => $coef): ?>
                <option value="<?= htmlspecialchars($code) ?>" <?= $opt === $code ? 'selected' : '' ?>>
                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $code))) ?> (×<?= $coef ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Comparer</button>
    </form>

    <?php if (!empty($results)): ?>
        <table>
            <thead>
                <tr><th>Transporteur</th><th>Prix (€)</th></tr>
            </thead>
            <tbody>
                <?php foreach ($results as $code => $price): ?>
                    <tr class="<?= ($price !== null && $price === $best) ? 'best' : '' ?>">
                        <td><?= htmlspecialchars($carriers[$code] ?? $code) ?></td>
                        <td>
                            <?= $price !== null ? number_format($price, 2, ',', ' ') : '<em>N/A</em>' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>

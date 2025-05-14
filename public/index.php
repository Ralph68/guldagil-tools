<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
$errors    = [];
$results   = [];
$best      = null;

// Cartographie pour l'affichage
$carriers = ['xpo' => 'XPO', 'heppner' => 'Heppner', 'kn' => 'Kuehne+Nagel'];
$options  = $transport->getOptionsList();

// Valeurs postées (ou valeurs par défaut)
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
    if ($weight === null || $weight <= 0) {
        $errors[] = 'Le poids doit être supérieur à 0.';
    }
    if (!isset($options[$opt])) {
        $errors[] = 'Option sélectionnée invalide.';
    }

    if (empty($errors)) {
        // Calcul pour chaque transporteur
        $results = $transport->calculateAll($type, $adr, $weight, $opt);
        // Filtre les tarifs valides
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparateur de frais de port</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <h1>Comparateur de frais de port</h1>
    </header>
    <main>
        <div class="form-container">
            <form id="calc-form" method="post">
                <label for="type">Type d’envoi</label>
                <select id="type" name="type" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="colis"   <?= \$type === 'colis'   ? 'selected' : '' ?>>Colis</option>
                    <option value="palette" <?= \$type === 'palette' ? 'selected' : '' ?>>Palette</option>
                </select>

                <label for="adr">ADR</label>
                <select id="adr" name="adr" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="non" <?= \$adr === 'non' ? 'selected' : '' ?>>Non</option>
                    <option value="oui" <?= \$adr === 'oui' ? 'selected' : '' ?>>Oui</option>
                </select>

                <label for="poids">Poids (kg)</label>
                <input type="number" id="poids" name="poids" step="0.1" min="0.1" required value="<?= htmlspecialchars(\$weight) ?>">

                <label for="option">Option</label>
                <select id="option" name="option" required>
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach (\$options as \$code => \$coef): ?>
                        <option value="<?= htmlspecialchars(\$code) ?>" <?= \$opt === \$code ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', \$code))) ?> (×<?= \$coef ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Comparer</button>
            </form>

            <button type="button" id="reset-btn">Réinitialiser</button>
            <button type="button" id="toggle-alternatives">Voir/masquer les alternatives</button>
        </div>

        <div class="result-container">
            <?php if (!empty(\$results)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Transporteur</th><th>Prix (€)</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (\$results as \$code => \$price): ?>
                                <tr class="<?= (\$price !== null && \$price === \$best) ? 'best' : '' ?>">
                                    <td><?= htmlspecialchars(\$carriers[\$code] ?? \$code) ?></td>
                                    <td><?= \$price !== null ? number_format(\$price, 2, ',', ' ') : '<em>N/A</em>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="assets/js/calculator.js"></script>
</body>
</html>

<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

$transport = new Transport($db);
$options = array_keys($transport->getOptionsList());
$errors = [];
$results = [];
$best = null;
$carriers = ['xpo' => 'XPO', 'heppner' => 'Heppner', 'kn' => 'Kuehne+Nagel'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparateur de frais de port</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .best { background-color: #c8e6c9; }
        table { border-collapse: collapse; width: 100%; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: left; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Comparateur de frais de port</h1>
    </header>
    <main class="form-container">
        <form id="calc-form">
            <label for="type">Type d’envoi</label>
            <select id="type" name="type" required>
                <option value="colis">Colis</option>
                <option value="palette">Palette</option>
            </select>

            <label for="adr">ADR</label>
            <select id="adr" name="adr" required>
                <option value="non">Non</option>
                <option value="oui">Oui</option>
            </select>

            <label for="poids">Poids (kg)</label>
            <input type="number" id="poids" name="poids" step="0.1" min="0.1" required>

            <label for="option">Option</label>
            <select id="option" name="option" required>
                <?php foreach ($options as $opt): ?>
                    <option value="<?= $opt ?>">
                        <?= ucfirst(str_replace('_', ' ', $opt)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Comparer</button>
        </form>

        <div id="result" class="hidden">
            <h2>Résultats :</h2>
            <table>
                <thead>
                    <tr>
                        <th>Transporteur</th>
                        <th>Prix estimé</th>
                    </tr>
                </thead>
                <tbody id="result-body"></tbody>
            </table>
        </div>
    </main>
    <script>
        const form = document.getElementById('calc-form');
        const resultBlock = document.getElementById('result');
        const resultBody = document.getElementById('result-body');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            const params = new URLSearchParams();
            formData.forEach((value, key) => params.append(key, value));

            const response = await fetch('ajax-calc.php?' + params.toString());
            const data = await response.json();

            resultBody.innerHTML = '';
            let best = Infinity;
            Object.entries(data).forEach(([code, price]) => {
                if (price !== null && price < best) best = price;
            });

            Object.entries(data).forEach(([code, price]) => {
                const tr = document.createElement('tr');
                if (price === best) tr.classList.add('best');
                tr.innerHTML = `<td>${code}</td><td>${price !== null ? price.toFixed(2) + ' €' : '<em>N/A</em>'}</td>`;
                resultBody.appendChild(tr);
            });

            resultBlock.classList.remove('hidden');
        });
    </script>
</body>
</html>

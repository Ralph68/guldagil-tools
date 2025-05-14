<?php
// -----------------------------------------------------------------------------
// admin/pages/options-edit.php
// Formulaire d'ajout / modification d'un paramètre général (Option)
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../models/Option.php';

// Récupération de la clé si modification
$key    = isset($_GET['key']) ? trim($_GET['key']) : null;
$errors = [];
$value  = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key   = trim($_POST['key']);
    $value = trim($_POST['value']);

    // Validation
    if ($key === '') {
        $errors[] = 'La clé est requise.';
    }
    if ($value === '') {
        $errors[] = 'La valeur ne peut pas être vide.';
    }

    // Sauvegarde si pas d'erreurs
    if (empty($errors)) {
        Option::set($key, $value);
        header('Location: index.php?page=options');
        exit;
    }
} elseif ($key) {
    // Préremplissage en cas de modification
    $value = Option::get($key);
}
?>

<h1><?= $key ? 'Modifier' : 'Ajouter' ?> un paramètre général</h1>

<?php if (!empty($errors)): ?>
    <ul class="errors">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post">
    <label>Clé<br>
        <input type="text" name="key" value="<?= htmlspecialchars($key) ?>" <?= $key ? 'readonly' : '' ?> required>
    </label><br>

    <label>Valeur<br>
        <textarea name="value" required><?= htmlspecialchars($value) ?></textarea>
    </label><br>

    <button type="submit">Enregistrer</button>
    <a href="index.php?page=options" class="button">Annuler</a>
</form>

<?php
/**
 * public/calculateur/index.php
 * Interface calculateur progressive - Étape 1
 * Version: 0.5 beta + build
 */

// Activation des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Chargement des dépendances
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';
session_start();

// Traitement AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];

    if ($action === 'delay') {
        $carrier = $_GET['carrier'] ?? '';
        $dept = $_GET['dept'] ?? '';
        $option = $_GET['option'] ?? 'standard';

        try {
            $map = ['xpo' => 'gul_xpo_rates', 'heppner' => 'gul_heppner_rates', 'kn' => 'gul_kn_rates'];
            if (!isset($map[$carrier])) throw new Exception('Transporteur invalide');

            $stmt = $db->prepare("SELECT delais FROM {$map[$carrier]} WHERE num_departement = ? LIMIT 1");
            $stmt->execute([$dept]);
            $row = $stmt->fetch();
            $delay = $row['delais'] ?? '24-48h';

            if ($option === 'premium13') $delay .= ' garanti avant 14h';
            elseif ($option === 'rdv') $delay .= ' sur RDV';

            echo json_encode(['success' => true, 'delay' => $delay]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'delay' => '24-48h']);
        }
        exit;
    }

    if ($action === 'calculate') {
        parse_str(file_get_contents('php://input'), $_POST);
        $params = [
            'departement' => str_pad(trim($_POST['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($_POST['poids'] ?? 0),
            'type' => strtolower(trim($_POST['type'] ?? 'colis')),
            'adr' => ($_POST['adr'] ?? 'non') === 'oui',
            'option_sup' => trim($_POST['option_sup'] ?? 'standard'),
            'enlevement' => isset($_POST['enlevement']),
            'palettes' => max(0, intval($_POST['palettes'] ?? 0))
        ];

        // Validation minimaliste
        $errors = [];
        if (!preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5])$/', $params['departement'])) $errors['departement'] = 'Code invalide';
        if ($params['poids'] <= 0 || $params['poids'] > 32000) $errors['poids'] = 'Poids incorrect';
        if (!in_array($params['type'], ['colis', 'palette'])) $errors['type'] = 'Type invalide';

        if ($params['type'] === 'palette' && ($params['palettes'] < 0 || $params['palettes'] > 20)) {
            $errors['palettes'] = 'Nombre de palettes invalide';
        }

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        try {
            require_once __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
            $transport = new Transport($db);
            $results = $transport->calculateAll($params);
            echo json_encode(['success' => true, 'results' => $results]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Requête inconnue']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>

    <!-- Chargement réel des CSS -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/base.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/layout.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/progressive-form.css">

    <!-- Description -->
    <meta name="description" content="Calculateur de frais de port professionnel">
</head>
<body>
    <h1><?= htmlspecialchars($page_title) ?></h1>
    <p>Interface de test calculateur - v<?= $version_info['version'] ?> / Build <?= $version_info['build'] ?></p>

    <form method="POST">
        <label for="departement">Département</label>
        <input type="text" id="departement" name="departement" required maxlength="2">

        <label for="poids">Poids (kg)</label>
        <input type="number" id="poids" name="poids" required step="0.1">

        <label>Type</label>
        <select name="type" required>
            <option value="colis">Colis</option>
            <option value="palette">Palette</option>
        </select>

        <label for="palettes">Palettes EUR</label>
        <input type="number" name="palettes" id="palettes" value="0" min="0" max="20">

        <label>ADR ?</label>
        <input type="radio" name="adr" value="non" checked> Non
        <input type="radio" name="adr" value="oui"> Oui

        <label for="option_sup">Option</label>
        <select name="option_sup">
            <option value="standard">Standard</option>
            <option value="rdv">RDV</option>
            <option value="premium13">Premium</option>
        </select>

        <label><input type="checkbox" name="enlevement"> Enlèvement extérieur</label>

        <button type="submit">Calculer</button>
    </form>

    <!-- JS -->
    <script src="../assets/js/modules/calculateur/main.js" defer></script>
</body>
</html>

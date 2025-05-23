<?php
// ajax-calculate.php
// Version corrigée pour validation renforcée et clés JSON ASCII

require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

header('Content-Type: application/json; charset=UTF-8');

// Démarrer la session pour l'historique
session_start();
if (!isset($_SESSION['historique'])) {
    $_SESSION['historique'] = [];
}

$transport = new Transport($db);
$carriers = ['xpo' => 'XPO', 'heppner' => 'Heppner', 'kn' => 'Kuehne+Nagel'];

// Récupération des paramètres
$dep = $_POST['departement'] ?? '';
$poids = isset($_POST['poids']) ? (float)$_POST['poids'] : null;
$type = $_POST['type'] ?? '';
$adr = $_POST['adr'] ?? '';
$option_sup = $_POST['option_sup'] ?? 'standard';
$palettes = (isset($_POST['palettes']) && $_POST['palettes'] !== '') ? (int)$_POST['palettes'] : 0;

$response = [
    'success'      => false,
    'results'      => [],
    'best'         => null,
    'bestCarrier'  => null,
    'errors'       => [],
    'debug'        => [],
    'affretement'  => false,
    'message'      => ''
];

// Validation renforcée
if (!preg_match('/^[0-9]{2}$/', $dep)) {
    $response['errors'][] = "Le département doit être constitué de 2 chiffres";
}
if ($poids === null || $poids <= 0) {
    $response['errors'][] = "Le poids doit être supérieur à 0";
}
if (!in_array($type, ['colis', 'palette'], true)) {
    $response['errors'][] = "Le type d'envoi est invalide";
}
if (!in_array($adr, ['oui', 'non'], true)) {
    $response['errors'][] = "Le choix ADR est requis";
}
if ($type === 'palette' && $palettes < 1) {
    $response['errors'][] = "Le nombre de palettes doit être au moins 1 pour un envoi en palette";
}

// Si erreurs, on renvoie directement
if (!empty($response['errors'])) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Gestion de l'affrètement si trop lourd
if ($poids > 3000) {
    $response['affretement'] = true;
    $response['message']     = "Pour un poids supérieur à 3000 kg, veuillez contacter le service achat au 03 89 63 42 42 pour un affrètement.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Calcul des tarifs
    $results = $transport->calculateAll($type, $adr, $poids, $option_sup, $dep);
    $response['results'] = $results;
    $response['debug']   = $transport->debug;

    // Sélection du meilleur tarif
    $valid = array_filter($results, fn($p) => $p !== null);
    if ($valid) {
        $response['best']        = min($valid);
        $response['bestCarrier'] = array_search($response['best'], $results);

        // Enregistrement dans l'historique
        $entry = [
            'date'         => date('Y-m-d H:i:s'),
            'departement'  => $dep,
            'poids'        => $poids,
            'type'         => $type,
            'adr'          => $adr,
            'option'       => $option_sup,
            'palettes'     => $palettes,
            'best_carrier' => $carriers[$response['bestCarrier']] ?? $response['bestCarrier'],
            'best_price'   => $response['best'],
        ];
        array_unshift($_SESSION['historique'], $entry);
        $_SESSION['historique'] = array_slice($_SESSION['historique'], 0, 10);
        $response['success'] = true;
    }
} catch (Exception $e) {
    $response['errors'][] = "Erreur lors du calcul : " . $e->getMessage();
}

// Préparation du format pour la réponse JSON
if ($response['success']) {
    $formatted = [];
    foreach ($response['results'] as $carrier => $price) {
        $formatted[$carrier] = [
            'name'      => $carriers[$carrier] ?? $carrier,
            'price'     => $price,
            'formatted' => $price !== null ? number_format($price, 2, ',', ' ') . ' €' : 'Non disponible',
            'debug'     => $response['debug'][$carrier] ?? null,
        ];
    }
    $response['formatted'] = $formatted;
}

// Envoi de la réponse JSON avec accents préservés
echo json_encode($response, JSON_UNESCAPED_UNICODE);

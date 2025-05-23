<?php
// ajax-calculate.php
require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/Transport.php';

header('Content-Type: application/json');

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
$palettes = isset($_POST['palettes']) ? (int)$_POST['palettes'] : 0;

$response = [
    'success' => false,
    'results' => [],
    'best' => null,
    'bestCarrier' => null,
    'errors' => [],
    'debug' => [],
    'affrètement' => false
];

// Validation
if (!$dep) {
    $response['errors'][] = "Le département est requis";
}
if (!$poids || $poids <= 0) {
    $response['errors'][] = "Le poids doit être supérieur à 0";
}
if (!$type) {
    $response['errors'][] = "Le type d'envoi est requis";
}
if (!$adr) {
    $response['errors'][] = "Le choix ADR est requis";
}

// Vérifier si le poids dépasse les limites
if ($poids > 3000) {
    $response['affrètement'] = true;
    $response['message'] = "Pour un poids supérieur à 3000 kg, merci de contacter le service achat au 03 89 63 42 42 pour un affrètement.";
} elseif (empty($response['errors'])) {
    try {
        $results = $transport->calculateAll($type, $adr, $poids, $option_sup, $dep);
        $response['results'] = $results;
        $response['debug'] = $transport->debug;
        
        // Trouver le meilleur prix
        $valid = array_filter($results, fn($p) => $p !== null);
        if ($valid) {
            $response['best'] = min($valid);
            $response['bestCarrier'] = array_search($response['best'], $results);
            
            // Ajouter à l'historique
            $historique_entry = [
                'date' => date('Y-m-d H:i:s'),
                'departement' => $dep,
                'poids' => $poids,
                'type' => $type,
                'adr' => $adr,
                'option' => $option_sup,
                'palettes' => $palettes,
                'best_carrier' => $carriers[$response['bestCarrier']] ?? $response['bestCarrier'],
                'best_price' => $response['best']
            ];
            
            // Garder seulement les 10 derniers
            array_unshift($_SESSION['historique'], $historique_entry);
            $_SESSION['historique'] = array_slice($_SESSION['historique'], 0, 10);
        }
        
        $response['success'] = true;
    } catch (Exception $e) {
        $response['errors'][] = "Erreur lors du calcul : " . $e->getMessage();
    }
}

// Formater les résultats pour l'affichage
if ($response['success'] && !$response['affrètement']) {
    $formatted = [];
    foreach ($response['results'] as $carrier => $price) {
        $formatted[$carrier] = [
            'name' => $carriers[$carrier] ?? $carrier,
            'price' => $price,
            'formatted' => $price !== null ? number_format($price, 2, ',', ' ') . ' €' : 'Non disponible',
            'debug' => $response['debug'][$carrier] ?? null
        ];
    }
    $response['formatted'] = $formatted;
}

echo json_encode($response);

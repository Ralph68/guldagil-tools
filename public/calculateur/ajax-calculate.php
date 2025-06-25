<?php
/**
 * public/calculateur/ajax-calculate.php - JSON CORRIGÉ
 */

header('Content-Type: application/json; charset=UTF-8');

// Démarrer la capture de sortie pour éviter les caractères parasites
ob_start();

$response = [
    'success' => false,
    'carriers' => [],
    'best_rate' => null,
    'message' => '',
    'errors' => []
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Chargement sécurisé
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../lib/Transport.php';
    
    // Validation données
    $dept = isset($_POST['departement']) ? trim($_POST['departement']) : '';
    $poids = isset($_POST['poids']) ? (float)$_POST['poids'] : 0;
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $adr = isset($_POST['adr']) ? $_POST['adr'] : 'non';
    $service = isset($_POST['service_livraison']) ? $_POST['service_livraison'] : 'standard';
    $enlevement = isset($_POST['enlevement']) ? 'oui' : 'non';
    $palettes = isset($_POST['palettes']) ? (int)$_POST['palettes'] : 0;
    
    if (empty($dept) || $poids <= 0 || empty($type)) {
        throw new Exception('Données manquantes');
    }
    
    // Calcul
    $transport = new Transport($db);
    $results = $transport->calculateAll($type, $adr, $poids, $service, $dept, $palettes, $enlevement);
    
    // Traitement résultats
    $carriers = [];
    $validResults = [];
    
    foreach (['xpo', 'heppner', 'kn'] as $carrier) {
        $price = $results[$carrier] ?? null;
        $name = [
            'xpo' => 'XPO Logistics',
            'heppner' => 'Heppner', 
            'kn' => 'Kuehne+Nagel'
        ][$carrier];
        
        if ($price && $price > 0) {
            $carriers[$carrier] = [
                'name' => $name,
                'price' => $price,
                'formatted' => number_format($price, 2, ',', ' ') . ' €'
            ];
            $validResults[$carrier] = $price;
        } else {
            $carriers[$carrier] = [
                'name' => $name,
                'price' => null,
                'formatted' => 'N/A'
            ];
        }
    }
    
    // Meilleur tarif
    $bestRate = null;
    if (!empty($validResults)) {
        $bestCarrier = array_keys($validResults, min($validResults))[0];
        $bestRate = [
            'carrier' => $bestCarrier,
            'carrier_name' => $carriers[$bestCarrier]['name'],
            'price' => $validResults[$bestCarrier],
            'formatted' => $carriers[$bestCarrier]['formatted']
        ];
    }
    
    $response = [
        'success' => true,
        'carriers' => $carriers,
        'best_rate' => $bestRate,
        'message' => count($validResults) . ' transporteur(s) disponible(s)',
        'errors' => []
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'carriers' => [],
        'best_rate' => null,
        'message' => $e->getMessage(),
        'errors' => [$e->getMessage()]
    ];
}

// Nettoyer la sortie et envoyer JSON
ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>

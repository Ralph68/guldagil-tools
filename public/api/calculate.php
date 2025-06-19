<?php
// public/api/calculate.php - VERSION CORRIGÉE
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../config.php';
require __DIR__ . '/../../lib/Transport.php';

// Initialisation de la réponse
$response = [
    'success' => false,
    'results' => [],
    'formatted' => [],
    'debug' => [],
    'errors' => [],
    'suggestions' => [],
    'best' => null
];

try {
    // Validation de la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupération des données
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST; // Fallback pour form-data
    }

    // Validation des paramètres requis
    $requiredFields = ['departement', 'poids', 'type'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            $response['errors'][] = "Le champ '$field' est requis";
        }
    }

    if (!empty($response['errors'])) {
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Normalisation des paramètres
    $params = [
        'departement' => str_pad($input['departement'], 2, '0', STR_PAD_LEFT),
        'poids' => (float)$input['poids'],
        'type' => $input['type'],
        'adr' => isset($input['adr']) && ($input['adr'] === 'oui' || $input['adr'] === true),
        'option_sup' => $input['option_sup'] ?? 'standard',
        'enlevement' => isset($input['enlevement']) && ($input['enlevement'] === true || $input['enlevement'] === 'on'),
        'palettes' => (int)($input['palettes'] ?? 0)
    ];

    // Validation des valeurs
    if ($params['poids'] <= 0 || $params['poids'] > 10000) {
        $response['errors'][] = 'Le poids doit être entre 1 et 10000 kg';
    }

    if (!preg_match('/^[0-9]{1,2}$/', $params['departement']) || $params['departement'] < 1 || $params['departement'] > 95) {
        $response['errors'][] = 'Le département doit être entre 01 et 95';
    }

    if (!in_array($params['type'], ['colis', 'palette'])) {
        $response['errors'][] = 'Le type doit être "colis" ou "palette"';
    }

    if (!empty($response['errors'])) {
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Calcul avec la nouvelle logique
    $transport = new Transport($db);
    $calculationResult = $transport->calculateAll($params);

    // Formatage des résultats
    $carrierNames = [
        'heppner' => 'Heppner',
        'xpo' => 'XPO',
        'kn' => 'Kuehne + Nagel'
    ];

    $formatted = [];
    foreach ($calculationResult['results'] as $carrier => $price) {
        $formatted[$carrier] = [
            'name' => $carrierNames[$carrier],
            'price' => $price,
            'formatted' => $price !== null ? number_format($price, 2, ',', ' ') . ' €' : 'Non disponible',
            'available' => $price !== null,
            'debug' => $calculationResult['debug'][$carrier] ?? null
        ];
    }

    // Gestion des suggestions spéciales
    $suggestions = $calculationResult['suggestions'] ?? [];
    
    // Suggestion ADR + Star/Priority automatique
    if ($params['adr'] && in_array($params['option_sup'], ['star18', 'star13', 'datefixe18', 'datefixe13'])) {
        $heppnerUnavailable = $calculationResult['results']['heppner'] === null;
        $xpoAvailable = $calculationResult['results']['xpo'] !== null;
        
        if ($heppnerUnavailable && $xpoAvailable) {
            // Calculer le standard pour comparaison
            $standardParams = $params;
            $standardParams['option_sup'] = 'standard';
            $standardParams['adr'] = false;
            
            $standardResult = $transport->calculateAll($standardParams);
            $standardPrice = $standardResult['results']['heppner'] ?? $standardResult['results']['xpo'];
            
            if ($standardPrice) {
                $surcoût = $calculationResult['results']['xpo'] - $standardPrice;
                $suggestions[] = [
                    'type' => 'adr_premium_switch',
                    'title' => '⚠️ Option Star/Priority avec ADR',
                    'message' => "Heppner ne propose pas Star/Priority avec ADR. XPO disponible avec un surcoût de " . number_format($surcoût, 2, ',', ' ') . "€ et +24h de délai.",
                    'alternative' => "Pour une livraison très urgente, contacter le service achat pour un expressiste dédié (~400€)."
                ];
            }
        }
    }

    // Gestion enlèvement + options premium
    if ($params['enlevement'] && in_array($params['option_sup'], ['star18', 'star13', 'premium18', 'premium13', 'rdv', 'datefixe18', 'datefixe13'])) {
        $suggestions[] = [
            'type' => 'enlevement_options',
            'title' => 'ℹ️ Enlèvement et options premium',
            'message' => 'Les options premium (RDV, Star/Priority, Date fixe) ne sont pas disponibles avec l\'enlèvement. Le tarif affiché correspond au service standard avec enlèvement.'
        ];
    }

    // Construction de la réponse finale
    $response = [
        'success' => true,
        'results' => $calculationResult['results'],
        'formatted' => $formatted,
        'best' => $calculationResult['best'],
        'debug' => $calculationResult['debug'],
        'suggestions' => $suggestions,
        'params' => $params,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Ajout à l'historique si session active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['historique'])) {
        $_SESSION['historique'] = [];
    }
    
    $historyEntry = [
        'timestamp' => time(),
        'params' => $params,
        'best_carrier' => $calculationResult['best']['carrier'] ?? null,
        'best_price' => $calculationResult['best']['price'] ?? null
    ];
    
    array_unshift($_SESSION['historique'], $historyEntry);
    $_SESSION['historique'] = array_slice($_SESSION['historique'], 0, 10); // Garder 10 derniers

} catch (Exception $e) {
    $response['errors'][] = "Erreur lors du calcul : " . $e->getMessage();
    error_log("Erreur calcul frais de port: " . $e->getMessage());
    
    if (defined('DEBUG') && DEBUG) {
        $response['debug_error'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Ajout des headers de cache
if ($response['success']) {
    header('Cache-Control: private, max-age=300'); // 5 minutes
} else {
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>

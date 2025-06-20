<?php
/**
 * Titre: API de calcul des frais de port
 * Chemin: /public/calculateur/ajax-calculate.php
 * Version: 0.5 beta + build
 * 
 * API REST pour le calcul des tarifs de transport
 * Compatible avec l'architecture JavaScript modulaire
 */

// Configuration headers API
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Gérer preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Chargement configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Initialisation réponse API
$response = [
    'success' => false,
    'type' => 'error',
    'bestCarrier' => null,
    'best' => null,
    'formatted' => [],
    'comparison' => null,
    'affretement' => false,
    'message' => '',
    'errors' => [],
    'warnings' => [],
    'debug' => null,
    'metadata' => [
        'version' => APP_VERSION,
        'build' => BUILD_NUMBER,
        'timestamp' => date('c'),
        'processing_time' => 0
    ]
];

// Mesure du temps de traitement
$start_time = microtime(true);

try {
    // Validation méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode HTTP non autorisée', 405);
    }
    
    // Récupération des données (JSON ou form-data)
    $input = null;
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'application/json') !== false) {
        $json_input = file_get_contents('php://input');
        $input = json_decode($json_input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON invalide: ' . json_last_error_msg(), 400);
        }
    } else {
        $input = $_POST;
    }
    
    if (empty($input)) {
        throw new Exception('Aucune donnée reçue', 400);
    }
    
    // Validation des champs requis
    $required_fields = ['departement', 'poids', 'type'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception('Champs requis manquants: ' . implode(', ', $missing_fields), 400);
    }
    
    // Normalisation et validation des paramètres
    $params = [
        'departement' => str_pad(trim($input['departement']), 2, '0', STR_PAD_LEFT),
        'poids' => (float)$input['poids'],
        'type' => trim(strtolower($input['type'])),
        'adr' => isset($input['adr']) ? (in_array($input['adr'], ['oui', 'yes', '1', 1, true], true) ? 'oui' : 'non') : 'non',
        'option_sup' => trim($input['option_sup'] ?? 'standard'),
        'enlevement' => isset($input['enlevement']) && in_array($input['enlevement'], ['1', 1, true, 'on'], true),
        'palettes' => max(0, (int)($input['palettes'] ?? 0))
    ];
    
    // Validation spécifique des paramètres
    
    // Département
    if (!preg_match('/^\d{2}$/', $params['departement'])) {
        throw new Exception('Département invalide (format: 01-95)', 422);
    }
    
    $dept_num = (int)$params['departement'];
    if ($dept_num < 1 || $dept_num > 95) {
        throw new Exception('Département hors limites (01-95)', 422);
    }
    
    // Poids
    if ($params['poids'] <= 0) {
        throw new Exception('Poids invalide (minimum 0.1kg)', 422);
    }
    
    if ($params['poids'] > 3500) {
        throw new Exception('Poids trop élevé (maximum 3500kg)', 422);
    }
    
    // Type
    if (!in_array($params['type'], ['colis', 'palette'])) {
        throw new Exception('Type d\'envoi invalide (colis ou palette)', 422);
    }
    
    // ADR
    if (!in_array($params['adr'], ['oui', 'non'])) {
        throw new Exception('Option ADR invalide (oui ou non)', 422);
    }
    
    // Option supplémentaire
    $valid_options = ['standard', 'rdv', 'datefixe', 'premium13', 'premium18'];
    if (!in_array($params['option_sup'], $valid_options)) {
        $params['option_sup'] = 'standard';
        $response['warnings'][] = 'Option supplémentaire invalide, remplacée par "standard"';
    }
    
    // Logging des paramètres si debug
    if (DEBUG) {
        $response['debug'] = [
            'input_received' => $input,
            'params_normalized' => $params,
            'request_headers' => getallheaders(),
            'request_method' => $_SERVER['REQUEST_METHOD']
        ];
    }
    
    // Vérification affrètement (poids très élevé ou conditions spéciales)
    if ($params['poids'] > 2000) {
        $response['affretement'] = true;
        $response['message'] = sprintf(
            'Poids de %s kg nécessite un affrètement. Contactez-nous pour un devis personnalisé.',
            number_format($params['poids'], 1)
        );
        $response['metadata']['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
        
        logInfo('Demande affrètement', ['params' => $params]);
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    // Chargement de la classe Transport
    $transport_class_path = __DIR__ . '/../../src/modules/calculateur/services/Transport.php';
    if (!file_exists($transport_class_path)) {
        // Fallback vers ancien emplacement
        $transport_class_path = __DIR__ . '/../../lib/Transport.php';
    }
    
    if (!file_exists($transport_class_path)) {
        throw new Exception('Classe Transport non trouvée', 500);
    }
    
    require_once $transport_class_path;
    
    if (!class_exists('Transport')) {
        throw new Exception('Classe Transport non chargée', 500);
    }
    
    // Initialisation du calculateur
    $transport = new Transport($db);
    
    // Calcul des tarifs pour tous les transporteurs
    $results = $transport->calculateAll(
        $params['type'],
        $params['adr'],
        $params['poids'],
        $params['option_sup'],
        $params['departement'],
        $params['palettes'],
        $params['enlevement']
    );
    
    // Traitement des résultats
    $valid_results = [];
    $formatted_results = [];
    
    $carrier_names = [
        'xpo' => 'XPO Logistics',
        'heppner' => 'Heppner',
        'kn' => 'Kuehne + Nagel'
    ];
    
    foreach ($results as $carrier => $price) {
        if ($price !== null && $price > 0) {
            $valid_results[$carrier] = $price;
            $formatted_results[$carrier] = [
                'name' => $carrier_names[$carrier] ?? strtoupper($carrier),
                'price' => $price,
                'formatted' => number_format($price, 2, ',', ' ') . ' €'
            ];
        }
    }
    
    // Vérification qu'on a au moins un résultat
    if (empty($valid_results)) {
        $response['success'] = false;
        $response['message'] = 'Aucun tarif disponible pour ces critères';
        $response['errors'][] = 'Aucun transporteur ne peut traiter cette expédition';
        
        // Suggestions selon les paramètres
        if ($params['adr'] === 'oui') {
            $response['warnings'][] = 'Les expéditions ADR ont des contraintes particulières';
        }
        if ($params['poids'] > 1000) {
            $response['warnings'][] = 'Poids élevé - Vérifiez les conditions de transport';
        }
        
        $response['metadata']['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    // Détermination du meilleur tarif
    $best_price = min($valid_results);
    $best_carrier = array_search($best_price, $valid_results);
    
    // Construction de la comparaison
    $comparison_data = [];
    foreach ($formatted_results as $carrier => $data) {
        $comparison_data[] = [
            'code' => $carrier,
            'name' => $data['name'],
            'price' => $data['price'],
            'formatted' => $data['formatted']
        ];
    }
    
    // Tri par prix croissant
    usort($comparison_data, function($a, $b) {
        return $a['price'] <=> $b['price'];
    });
    
    $price_range = [
        'min' => $comparison_data[0]['price'],
        'max' => end($comparison_data)['price'],
        'difference' => end($comparison_data)['price'] - $comparison_data[0]['price']
    ];
    
    // Génération des alertes/conseils
    $alerts = [];
    
    if ($params['poids'] > 500) {
        $alerts[] = [
            'type' => 'warning',
            'message' => sprintf('Poids élevé (%s kg) - Délais de transport possiblement allongés', 
                number_format($params['poids'], 1))
        ];
    }
    
    if ($params['adr'] === 'oui') {
        $alerts[] = [
            'type' => 'info',
            'message' => 'Transport ADR - Vérifiez les documents requis et délais supplémentaires'
        ];
    }
    
    if ($best_price > 200) {
        $alerts[] = [
            'type' => 'info',
            'message' => 'Coût élevé - Envisagez de grouper vos expéditions pour optimiser'
        ];
    }
    
    if (count($valid_results) > 1 && $price_range['difference'] > 50) {
        $savings_percent = round(($price_range['difference'] / $price_range['max']) * 100);
        $alerts[] = [
            'type' => 'success',
            'message' => sprintf('Économie possible de %s € (%d%%) en choisissant le meilleur tarif', 
                number_format($price_range['difference'], 2), $savings_percent)
        ];
    }
    
    // Construction de la réponse de succès
    $response['success'] = true;
    $response['type'] = 'success';
    $response['bestCarrier'] = $best_carrier;
    $response['best'] = $best_price;
    $response['formatted'] = $formatted_results;
    $response['comparison'] = [
        'count' => count($comparison_data),
        'carriers' => $comparison_data,
        'range' => count($comparison_data) > 1 ? $price_range : null
    ];
    $response['alerts'] = $alerts;
    $response['message'] = sprintf('Meilleur tarif trouvé: %s à %s', 
        $formatted_results[$best_carrier]['name'], 
        $formatted_results[$best_carrier]['formatted']);
    
    // Ajout des détails de debug si activé
    if (DEBUG && isset($transport->debug)) {
        $response['debug']['transport_debug'] = $transport->debug;
        $response['debug']['calculation_details'] = [
            'total_carriers_checked' => count($results),
            'valid_results_count' => count($valid_results),
            'best_carrier_selection' => [
                'carrier' => $best_carrier,
                'price' => $best_price,
                'competition' => array_diff_key($valid_results, [$best_carrier => true])
            ]
        ];
    }
    
    // Sauvegarde dans l'historique si table existe
    try {
        $stmt = $db->prepare("
            INSERT INTO gul_calculator_history 
            (departement, poids, type, adr, option_sup, enlevement, palettes, 
             best_carrier, best_price, all_results, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $params['departement'],
            $params['poids'],
            $params['type'],
            $params['adr'],
            $params['option_sup'],
            $params['enlevement'] ? 1 : 0,
            $params['palettes'],
            $best_carrier,
            $best_price,
            json_encode($valid_results)
        ]);
    } catch (Exception $e) {
        // Table historique pas obligatoire, ignorer l'erreur
        if (DEBUG) {
            $response['debug']['history_save_error'] = $e->getMessage();
        }
    }
    
    // Log succès
    logInfo('Calcul tarif réussi', [
        'params' => $params,
        'best_carrier' => $best_carrier,
        'best_price' => $best_price,
        'carriers_count' => count($valid_results)
    ]);

} catch (Exception $e) {
    // Gestion des erreurs
    $error_code = $e->getCode() ?: 500;
    http_response_code($error_code);
    
    $response['success'] = false;
    $response['type'] = 'error';
    $response['message'] = $e->getMessage();
    $response['errors'] = [$e->getMessage()];
    
    if (DEBUG) {
        $response['debug']['exception'] = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    // Log erreur
    logError('Erreur calcul tarif', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'input' => $input ?? null,
        'trace' => $e->getTraceAsString()
    ]);
    
} finally {
    // Calcul du temps de traitement
    $response['metadata']['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
    
    // Informations de performance
    if (DEBUG) {
        $response['metadata']['memory_usage'] = [
            'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
        ];
        $response['metadata']['db_queries'] = $db->getAttribute(PDO::ATTR_CONNECTION_STATUS) ?? 'N/A';
    }
}

// Sortie JSON avec formatage conditionnel
$json_flags = JSON_UNESCAPED_UNICODE;
if (DEBUG) {
    $json_flags |= JSON_PRETTY_PRINT;
}

echo json_encode($response, $json_flags);

/**
 * Fonction helper pour logs
 */
function logInfo($message, $context = []) {
    if (function_exists('logMessage')) {
        logMessage('info', $message, $context);
    }
}

function logError($message, $context = []) {
    if (function_exists('logMessage')) {
        logMessage('error', $message, $context);
    } else {
        error_log("API Error: $message - " . json_encode($context));
    }
}

// Headers de cache pour optimisation
if (!DEBUG) {
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

// Header de temps de réponse
header('X-Response-Time: ' . $response['metadata']['processing_time'] . 'ms');
header('X-API-Version: ' . APP_VERSION);
?>

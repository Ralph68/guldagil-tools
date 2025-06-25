<?php
/**
 * Titre: API de calcul des frais de port - MISE À JOUR
 * Chemin: /public/calculateur/ajax-calculate.php
 * Version: 0.5 beta - Compatible architecture modulaire JS
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

// Initialisation réponse API compatible nouvelle architecture JS
$response = [
    'success' => false,
    'carriers' => [],
    'best_rate' => null,
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
    
    // Normalisation et validation des paramètres (compatible JS)
    $params = [
        'departement' => str_pad(trim($input['departement']), 2, '0', STR_PAD_LEFT),
        'poids' => (float)$input['poids'],
        'type' => trim(strtolower($input['type'])),
        'adr' => isset($input['adr']) ? (in_array($input['adr'], ['oui', 'yes', '1', 1, true], true) ? 'oui' : 'non') : 'non',
        'service_livraison' => trim($input['service_livraison'] ?? $input['option_sup'] ?? 'standard'),
        'enlevement' => isset($input['enlevement']) && in_array($input['enlevement'], ['1', 1, true, 'on'], true),
        'palettes' => max(0, (int)($input['palettes'] ?? 0))
    ];
    
    // Validation spécifique des paramètres
    if (!preg_match('/^\d{2}$/', $params['departement'])) {
        throw new Exception('Département invalide (format: 01-95)', 422);
    }
    
    $dept_num = (int)$params['departement'];
    if ($dept_num < 1 || $dept_num > 95) {
        throw new Exception('Département hors limites (01-95)', 422);
    }
    
    if ($params['poids'] <= 0) {
        throw new Exception('Poids invalide (minimum 0.1kg)', 422);
    }
    
    if ($params['poids'] > 3500) {
        throw new Exception('Poids trop élevé (maximum 3500kg)', 422);
    }
    
    if (!in_array($params['type'], ['colis', 'palette'])) {
        throw new Exception('Type d\'envoi invalide (colis ou palette)', 422);
    }
    
    if (!in_array($params['adr'], ['oui', 'non'])) {
        throw new Exception('Option ADR invalide (oui ou non)', 422);
    }
    
    // Option supplémentaire
    $valid_options = ['standard', 'rdv', 'datefixe', 'premium13', 'premium18'];
    if (!in_array($params['service_livraison'], $valid_options)) {
        $params['service_livraison'] = 'standard';
        $response['warnings'][] = 'Option supplémentaire invalide, remplacée par "standard"';
    }
    
    // Logging des paramètres si debug
    if (DEBUG) {
        $response['debug'] = [
            'input_received' => $input,
            'params_normalized' => $params,
            'request_method' => $_SERVER['REQUEST_METHOD']
        ];
    }
    
    // Vérification affrètement (poids très élevé)
    if ($params['poids'] > 2000) {
        $response['affretement'] = true;
        $response['message'] = sprintf(
            'Poids de %s kg nécessite un affrètement. Contactez-nous pour un devis personnalisé.',
            number_format($params['poids'], 1)
        );
        $response['metadata']['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
        
        if (function_exists('logInfo')) {
            logInfo('Demande affrètement', ['params' => $params]);
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    // CHARGEMENT DE LA CLASSE TRANSPORT - CORRIGÉ
    $transport_class_path = __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
    
    require_once $transport_class_path;
    
    if (!class_exists('Transport')) {
        throw new Exception('Classe Transport non chargée', 500);
    }
    
    // Initialisation du calculateur
    $transport = new Transport($db);
    
    // CALCUL DES TARIFS - COMPATIBLE DEUX SIGNATURES
    $results = null;
    $signature_used = 'unknown';
    
    if (method_exists($transport, 'calculateAll')) {
        try {
            // NOUVELLE SIGNATURE (array) - PRIORITÉ
            $results = $transport->calculateAll($params);
            $signature_used = 'array';
            
            if (DEBUG) {
                $response['debug']['signature_used'] = 'array (nouvelle)';
            }
            
        } catch (Exception $e) {
            // ANCIENNE SIGNATURE (paramètres séparés) - FALLBACK
            try {
                $results = $transport->calculateAll(
                    $params['type'],
                    $params['adr'],
                    $params['poids'],
                    $params['service_livraison'],
                    $params['departement'],
                    $params['palettes'],
                    $params['enlevement']
                );
                $signature_used = 'separated_params';
                
                if (DEBUG) {
                    $response['debug']['signature_used'] = 'separated_params (ancienne)';
                    $response['debug']['array_signature_error'] = $e->getMessage();
                }
                
            } catch (Exception $e2) {
                throw new Exception('Erreur calcul avec les deux signatures: ' . $e2->getMessage(), 500);
            }
        }
    } else {
        throw new Exception('Méthode calculateAll non trouvée dans la classe Transport', 500);
    }
    
    // TRAITEMENT DES RÉSULTATS - COMPATIBLE NOUVELLE ARCHITECTURE JS
    $valid_results = [];
    $carrier_names = [
        'xpo' => 'XPO Logistics',
        'heppner' => 'Heppner',
        'kn' => 'Kuehne + Nagel'
    ];
    
    // Extraire les résultats selon la structure retournée
    $carrier_results = [];
    if (isset($results['results'])) {
        // Nouvelle structure avec metadata
        $carrier_results = $results['results'];
        if (DEBUG && isset($results['debug'])) {
            $response['debug']['transport_debug'] = $results['debug'];
        }
    } else {
        // Ancienne structure directe
        $carrier_results = $results;
        if (DEBUG && isset($transport->debug)) {
            $response['debug']['transport_debug'] = $transport->debug;
        }
    }
    
    // Formater les résultats pour l'architecture JS
    foreach ($carrier_results as $carrier => $price) {
        $name = $carrier_names[$carrier] ?? strtoupper($carrier);
        
        if ($price !== null && $price > 0) {
            $valid_results[$carrier] = $price;
            $response['carriers'][$carrier] = [
                'name' => $name,
                'price' => $price,
                'formatted' => number_format($price, 2, ',', ' ') . ' €'
            ];
        } else {
            $response['carriers'][$carrier] = [
                'name' => $name,
                'price' => null,
                'formatted' => 'Non disponible'
            ];
        }
    }
    
    // Déterminer le meilleur tarif
    if (!empty($valid_results)) {
        $best_carrier = array_keys($valid_results, min($valid_results))[0];
        $best_price = $valid_results[$best_carrier];
        
        $response['best_rate'] = [
            'carrier' => $best_carrier,
            'carrier_name' => $carrier_names[$best_carrier] ?? strtoupper($best_carrier),
            'price' => $best_price,
            'formatted' => number_format($best_price, 2, ',', ' ') . ' €',
            'delivery_info' => '' // Peut être enrichi plus tard
        ];
        
        $response['success'] = true;
        $response['message'] = 'Calcul réussi';
        
    } else {
        $response['success'] = false;
        $response['message'] = 'Aucun tarif disponible pour ces critères';
    }
    
    // Sauvegarde historique (optionnel)
    if (!empty($valid_results)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO gul_calculs_historique 
                (departement, poids, type, adr, service_livraison, enlevement, palettes, 
                 best_carrier, best_price, all_results, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $params['departement'],
                $params['poids'],
                $params['type'],
                $params['adr'],
                $params['service_livraison'],
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
    }
    
    // Log succès
    if (function_exists('logInfo')) {
        logInfo('Calcul tarif réussi', [
            'params' => $params,
            'signature' => $signature_used,
            'carriers_count' => count($valid_results)
        ]);
    }

} catch (Exception $e) {
    // Gestion des erreurs
    $error_code = $e->getCode() ?: 500;
    http_response_code($error_code);
    
    $response['success'] = false;
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
    if (function_exists('logError')) {
        logError('Erreur calcul tarif', [
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'input' => $input ?? null
        ]);
    } else {
        error_log("API Error: " . $e->getMessage());
    }
    
} finally {
    // Calcul du temps de traitement
    $response['metadata']['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
    
    // Informations de performance en debug
    if (DEBUG) {
        $response['metadata']['memory_usage'] = [
            'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
        ];
    }
}

// Sortie JSON avec formatage conditionnel
$json_flags = JSON_UNESCAPED_UNICODE;
if (DEBUG) {
    $json_flags |= JSON_PRETTY_PRINT;
}

echo json_encode($response, $json_flags);

// Headers de performance
header('X-Response-Time: ' . $response['metadata']['processing_time'] . 'ms');
header('X-API-Version: ' . APP_VERSION);
?>

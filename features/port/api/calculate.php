<?php
/**
 * Titre: API de calcul des frais de port - VERSION FONCTIONNELLE COMPLÈTE
 * Chemin: /public/calculateur/ajax-calculate.php
 * Version: 0.5 beta + build
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

// Chargement configuration SÉCURISÉ
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
        'version' => defined('APP_VERSION') ? APP_VERSION : '0.5',
        'build' => defined('BUILD_NUMBER') ? BUILD_NUMBER : 'dev',
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
        'service_livraison' => trim($input['service_livraison'] ?? 'standard'),
        'enlevement' => isset($input['enlevement_expediteur']) && in_array($input['enlevement_expediteur'], ['1', 1, true, 'on'], true) ? 'oui' : 'non',
        'palettes' => max(0, (int)($input['nb_palettes'] ?? 0))
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
    
    if (!in_array($params['type'], ['colis', 'palette', 'express'])) {
        throw new Exception('Type d\'envoi invalide (colis, palette ou express)', 422);
    }
    
    // Option supplémentaire validation
    $valid_options = ['standard', 'rdv', 'premium_13h', 'premium_18h'];
    if (!in_array($params['service_livraison'], $valid_options)) {
        $params['service_livraison'] = 'standard';
        $response['warnings'][] = 'Option supplémentaire invalide, remplacée par "standard"';
    }
    
    // Logging des paramètres si debug
    if (defined('DEBUG') && DEBUG) {
        $response['debug'] = [
            'input_received' => $input,
            'params_normalized' => $params,
            'request_method' => $_SERVER['REQUEST_METHOD']
        ];
    }
    
    // Vérification affrètement (poids très élevé)
    if ($params['poids'] > 2000) {
        $response['affretement'] = true;
        $response['success'] = true;
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
    
    // CHARGEMENT DE LA CLASSE TRANSPORT - CHEMIN CORRECT
    $transport_class_path = __DIR__ . '/../transport.php';
    
    if (!file_exists($transport_class_path)) {
        throw new Exception('Classe Transport non trouvée: ' . $transport_class_path, 500);
    }
    
    require_once $transport_class_path;
    
    if (!class_exists('Transport')) {
        throw new Exception('Classe Transport non chargée', 500);
    }
    
    if (defined('DEBUG') && DEBUG) {
        $response['debug']['transport_path'] = $transport_class_path;
    }
    
    // Initialisation du calculateur avec la bonne connexion DB
    $db_connection = null;
    if (isset($pdo)) {
        $db_connection = $pdo;
    } elseif (isset($db)) {
        $db_connection = $db;
    } else {
        throw new Exception('Connexion base de données non disponible', 500);
    }
    
    $transport = new Transport($db_connection);
    
    // CALCUL DES TARIFS - COMPATIBLE DEUX SIGNATURES
    $results = null;
    $signature_used = 'unknown';
    
    if (method_exists($transport, 'calculateAll')) {
        try {
            // NOUVELLE SIGNATURE (array) - PRIORITÉ
            $results = $transport->calculateAll($params);
            $signature_used = 'array';
            
            if (defined('DEBUG') && DEBUG) {
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
                
                if (defined('DEBUG') && DEBUG) {
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
        'kn' => 'Kuehne+Nagel'
    ];
    
    // Extraire les résultats selon la structure retournée
    $carrier_results = [];
    if (isset($results['results'])) {
        // Nouvelle structure avec metadata
        $carrier_results = $results['results'];
        if (defined('DEBUG') && DEBUG && isset($results['debug'])) {
            $response['debug']['transport_debug'] = $results['debug'];
        }
    } else {
        // Ancienne structure directe
        $carrier_results = $results;
        if (defined('DEBUG') && DEBUG && isset($transport->debug)) {
            $response['debug']['transport_debug'] = $transport->debug;
        }
    }
    
    // Formater les résultats pour l'architecture JS
    foreach ($carrier_results as $carrier => $data) {
        $name = $carrier_names[$carrier] ?? strtoupper($carrier);
        
        // Support pour structure simple (prix directement) ou complexe (avec détails)
        $price = null;
        $available = false;
        $carrier_code = $carrier;
        
        if (is_numeric($data) && $data > 0) {
            // Structure simple : prix directement
            $price = (float)$data;
            $available = true;
        } elseif (is_array($data)) {
            // Structure complexe avec détails
            $price = $data['price'] ?? $data['prix'] ?? null;
            $available = isset($data['available']) ? $data['available'] : ($price > 0);
            $carrier_code = $data['code'] ?? $carrier;
        }
        
        if ($price !== null && $price > 0 && $available) {
            $valid_results[$carrier] = $price;
            $response['carriers'][] = [
                'carrier_code' => $carrier_code,
                'carrier_name' => $name,
                'price' => $price,
                'price_display' => number_format($price, 2, ',', ' ') . ' € HT',
                'available' => true,
                'service_description' => ''
            ];
        } else {
            $response['carriers'][] = [
                'carrier_code' => $carrier_code,
                'carrier_name' => $name,
                'price' => null,
                'price_display' => 'Non disponible',
                'available' => false,
                'service_description' => 'Tarif non disponible pour cette destination'
            ];
        }
    }
    
    // Déterminer le meilleur tarif
    if (!empty($valid_results)) {
        $best_carrier = array_keys($valid_results, min($valid_results))[0];
        $best_price = $valid_results[$best_carrier];
        
        $response['best_rate'] = [
            'carrier_code' => $best_carrier,
            'carrier_name' => $carrier_names[$best_carrier] ?? strtoupper($best_carrier),
            'price' => $best_price,
            'price_display' => number_format($best_price, 2, ',', ' ') . ' € HT'
        ];
        
        $response['success'] = true;
        $response['message'] = count($valid_results) . ' transporteur(s) disponible(s)';
    } else {
        $response['success'] = false;
        $response['message'] = 'Aucun transporteur disponible pour cette destination';
        $response['errors'][] = 'Aucun tarif valide trouvé';
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'carriers' => [],
        'best_rate' => null,
        'affretement' => false,
        'message' => $e->getMessage(),
        'errors' => [$e->getMessage()]
    ];
    
    if (defined('DEBUG') && DEBUG) {
        $response['debug']['exception'] = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    } else {
        error_log("API Error: " . $e->getMessage());
    }
    
} finally {
    // Calcul du temps de traitement
    $response['metadata']['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
    
    // Informations de performance en debug
    if (defined('DEBUG') && DEBUG) {
        $response['metadata']['memory_usage'] = [
            'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
        ];
    }
}

// Nettoyer la sortie et envoyer JSON
ob_clean();

// Sortie JSON avec formatage conditionnel
$json_flags = JSON_UNESCAPED_UNICODE;
if (defined('DEBUG') && DEBUG) {
    $json_flags |= JSON_PRETTY_PRINT;
}

echo json_encode($response, $json_flags);

// Headers de performance
header('X-Response-Time: ' . $response['metadata']['processing_time'] . 'ms');
if (defined('APP_VERSION')) {
    header('X-API-Version: ' . APP_VERSION);
}
exit;

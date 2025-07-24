<?php
/**
 * Titre: API de recherche ADR complète avec toutes fonctionnalités
 * Chemin: /public/adr/search/search.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

// Démarrage session et vérification
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié', 'success' => false]);
    exit;
}

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT_PATH . '/config/config.php';

// Configuration de la recherche
const SEARCH_CONFIG = [
    'min_chars' => 2,
    'max_suggestions' => 15,
    'max_results' => 100,
    'fuzzy_threshold' => 0.3
];

// Paramètres de la requête
$action = $_GET['action'] ?? 'suggestions';
$query = trim($_GET['q'] ?? '');
$rawLimit = (int)($_GET['limit'] ?? SEARCH_CONFIG['max_suggestions']);
$limit = max(1, min($rawLimit, SEARCH_CONFIG['max_results']));
$classe = $_GET['classe'] ?? '';
$groupe = $_GET['groupe'] ?? '';
$adr_status = $_GET['adr_status'] ?? '';
$env_danger = isset($_GET['env_danger']) && $_GET['env_danger'] === 'true';
$code = $_GET['code'] ?? '';

try {
    switch ($action) {
        case 'suggestions':
            handleSuggestions($db, $query, $limit);
            break;
            
        case 'search':
            handleFullSearch($db, $query, $limit, $classe, $groupe, $adr_status, $env_danger);
            break;
            
        case 'detail':
            handleProductDetail($db, $code);
            break;
            
        case 'popular':
            handlePopularProducts($db, $limit);
            break;
            
        case 'stats':
            handleStats($db);
            break;
            
        default:
            throw new Exception('Action non supportée');
    }
    
} catch (Exception $e) {
    error_log("Erreur API recherche ADR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}

/**
 * Gestion des suggestions avec recherche avancée
 */
function handleSuggestions($db, $query, $limit) {
    if (strlen($query) < SEARCH_CONFIG['min_chars']) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        return;
    }
    
    $searchQuery = "%{$query}%";
    $exactQuery = $query . "%";
    
    // Recherche étendue avec tous les champs
    $sql = "SELECT DISTINCT 
                code_produit, 
                nom_produit,
                nom_technique,
                numero_un,
                classe_adr,
                groupe_emballage,
                danger_environnement,
                type_contenant,
                categorie_transport,
                corde_article_ferme
            FROM gul_adr_products 
            WHERE actif = 1 
            AND (
                code_produit LIKE ? 
                OR code_produit LIKE ?
                OR nom_produit LIKE ? 
                OR nom_technique LIKE ?
                OR numero_un LIKE ?
                OR CONCAT('UN', numero_un) LIKE ?
                OR nom_description_un LIKE ?
                OR CONCAT('classe:', classe_adr) LIKE ?
            )
            ORDER BY 
                CASE 
                    WHEN code_produit = ? THEN 1
                    WHEN code_produit LIKE ? THEN 2
                    WHEN nom_produit LIKE ? THEN 3
                    WHEN numero_un LIKE ? THEN 4
                    ELSE 5
                END,
                code_produit
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $exactQuery, $searchQuery, $searchQuery, $searchQuery, 
        $searchQuery, $searchQuery, $searchQuery, $searchQuery,
        $query, $exactQuery, $exactQuery, $exactQuery,
        $limit
    ]);
    
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'count' => count($suggestions),
        'query' => $query
    ]);
}

/**
 * Recherche complète avec filtres avancés
 */
function handleFullSearch($db, $query, $limit, $classe, $groupe, $adr_status, $env_danger) {
    $conditions = ['actif = 1'];
    $params = [];
    
    // Recherche textuelle étendue
    if (!empty($query)) {
        // Recherche spéciale pour UN
        if (preg_match('/^UN?(\d+)$/i', $query, $matches)) {
            $conditions[] = "numero_un = ?";
            $params[] = $matches[1];
        }
        // Recherche spéciale pour classe
        elseif (preg_match('/^classe:?(\d+)$/i', $query, $matches)) {
            $conditions[] = "classe_adr = ?";
            $params[] = $matches[1];
        }
        // Recherche générale
        else {
            $searchQuery = "%{$query}%";
            $exactQuery = $query . "%";
            $conditions[] = "(
                code_produit LIKE ? 
                OR code_produit LIKE ?
                OR nom_produit LIKE ? 
                OR nom_technique LIKE ?
                OR numero_un LIKE ?
                OR CONCAT('UN', numero_un) LIKE ?
                OR nom_description_un LIKE ?
            )";
            $params = array_merge($params, [
                $exactQuery, $searchQuery, $searchQuery, $searchQuery,
                $searchQuery, $searchQuery, $searchQuery
            ]);
        }
    }
    
    // Filtre classe ADR
    if (!empty($classe)) {
        $conditions[] = "classe_adr = ?";
        $params[] = $classe;
    }
    
    // Filtre groupe emballage
    if (!empty($groupe)) {
        $conditions[] = "groupe_emballage = ?";
        $params[] = $groupe;
    }
    
    // Filtre statut ADR
    if ($adr_status === 'adr_only') {
        $conditions[] = "numero_un IS NOT NULL AND numero_un != ''";
    } elseif ($adr_status === 'non_adr_only') {
        $conditions[] = "(numero_un IS NULL OR numero_un = '')";
    }
    
    // Filtre environnement
    if ($env_danger) {
        $conditions[] = "danger_environnement = 'OUI'";
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    // Requête principale avec toutes les informations
    $sql = "SELECT 
                p.*,
                q.quota_max_vehicule,
                q.quota_max_colis,
                q.description as description_categorie,
                CASE 
                    WHEN p.numero_un IS NOT NULL AND p.numero_un != '' THEN 'ADR'
                    WHEN p.corde_article_ferme = 'x' THEN 'Fermé'
                    ELSE 'Standard'
                END as statut_produit
            FROM gul_adr_products p
            LEFT JOIN gul_adr_quotas q ON p.categorie_transport = q.categorie_transport
            WHERE {$whereClause}
            ORDER BY 
                CASE 
                    WHEN p.code_produit = ? THEN 1
                    WHEN p.code_produit LIKE ? THEN 2
                    WHEN p.nom_produit LIKE ? THEN 3
                    WHEN p.numero_un LIKE ? THEN 4
                    ELSE 5
                END,
                p.classe_adr,
                p.code_produit
            LIMIT ?";
    
    // Paramètres pour l'ordre + limit
    if (!empty($query) && !preg_match('/^(UN?\d+|classe:\d+)$/i', $query)) {
        $orderParams = [$query, $query . '%', $query . '%', $query . '%'];
    } else {
        $orderParams = ['', '', '', ''];
    }
    
    $allParams = array_merge($params, $orderParams, [$limit]);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($allParams);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter URL FDS et informations complémentaires
    foreach ($products as &$product) {
        $product['fds_url'] = "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($product['code_produit']);
        
        // Détection acide/base
        $text = strtolower(($product['nom_produit'] ?? '') . ' ' . ($product['nom_technique'] ?? ''));
        if (strpos($text, 'acide') !== false || strpos($text, 'acid') !== false) {
            $product['acid_base_type'] = 'acide';
        } elseif (strpos($text, 'base') !== false || strpos($text, 'basique') !== false || 
                  strpos($text, 'soude') !== false || strpos($text, 'hydroxyde') !== false) {
            $product['acid_base_type'] = 'base';
        } elseif (strpos($text, 'neutre') !== false) {
            $product['acid_base_type'] = 'neutre';
        } else {
            $product['acid_base_type'] = null;
        }
    }
    
    // Compter le total
    $countSql = "SELECT COUNT(*) FROM gul_adr_products p WHERE {$whereClause}";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products),
        'total' => $totalCount,
        'query' => $query,
        'filters' => compact('classe', 'groupe', 'adr_status', 'env_danger')
    ]);
}

/**
 * Détail complet d'un produit spécifique
 */
function handleProductDetail($db, $code) {
    if (empty($code)) {
        throw new Exception('Code produit requis');
    }
    
    $sql = "SELECT 
                p.*,
                q.quota_max_vehicule,
                q.quota_max_colis,
                q.description as description_categorie,
                CASE 
                    WHEN p.numero_un IS NOT NULL AND p.numero_un != '' THEN 'ADR'
                    WHEN p.corde_article_ferme = 'x' THEN 'Fermé'
                    ELSE 'Standard'
                END as statut_produit
            FROM gul_adr_products p
            LEFT JOIN gul_adr_quotas q ON p.categorie_transport = q.categorie_transport
            WHERE p.code_produit = ? AND p.actif = 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$code]);
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    // Ajouter informations supplémentaires
    $product['fds_url'] = "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($product['code_produit']);
    
    // Détection acide/base
    $text = strtolower(($product['nom_produit'] ?? '') . ' ' . ($product['nom_technique'] ?? ''));
    if (strpos($text, 'acide') !== false || strpos($text, 'acid') !== false) {
        $product['acid_base_type'] = 'acide';
    } elseif (strpos($text, 'base') !== false || strpos($text, 'basique') !== false || 
              strpos($text, 'soude') !== false || strpos($text, 'hydroxyde') !== false) {
        $product['acid_base_type'] = 'base';
    } elseif (strpos($text, 'neutre') !== false) {
        $product['acid_base_type'] = 'neutre';
    } else {
        $product['acid_base_type'] = null;
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
}

/**
 * Produits populaires basés sur les recherches récentes
 */
function handlePopularProducts($db, $limit) {
    // Requête pour les produits les plus consultés (basé sur date_modification)
    // et les produits ADR les plus courants
    $sql = "SELECT 
                code_produit, 
                nom_produit,
                nom_technique,
                numero_un, 
                classe_adr, 
                danger_environnement,
                type_contenant,
                poids_contenant,
                corde_article_ferme,
                categorie_transport
            FROM gul_adr_products 
            WHERE actif = 1 
            ORDER BY 
                CASE 
                    WHEN numero_un IS NOT NULL THEN 1 
                    ELSE 2 
                END,
                date_modification DESC,
                code_produit 
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter informations supplémentaires
    foreach ($products as &$product) {
        $product['fds_url'] = "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($product['code_produit']);
        
        // Détection acide/base
        $text = strtolower(($product['nom_produit'] ?? '') . ' ' . ($product['nom_technique'] ?? ''));
        if (strpos($text, 'acide') !== false || strpos($text, 'acid') !== false) {
            $product['acid_base_type'] = 'acide';
        } elseif (strpos($text, 'base') !== false || strpos($text, 'basique') !== false || 
                  strpos($text, 'soude') !== false || strpos($text, 'hydroxyde') !== false) {
            $product['acid_base_type'] = 'base';
        } else {
            $product['acid_base_type'] = null;
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
}

/**
 * Statistiques globales pour le dashboard
 */
function handleStats($db) {
    try {
        $stats = [];
        
        // Total produits actifs
        $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1");
        $stats['total'] = $stmt->fetch()['total'] ?? 0;
        
        // Produits ADR (avec numéro UN)
        $stmt = $db->query("
            SELECT COUNT(*) as adr 
            FROM gul_adr_products 
            WHERE actif = 1 AND numero_un IS NOT NULL AND numero_un != ''
        ");
        $stats['adr'] = $stmt->fetch()['adr'] ?? 0;
        
        // Produits dangereux pour l'environnement
        $stmt = $db->query("
            SELECT COUNT(*) as env 
            FROM gul_adr_products 
            WHERE actif = 1 AND danger_environnement = 'OUI'
        ");
        $stats['env'] = $stmt->fetch()['env'] ?? 0;
        
        // Articles fermés
        $stmt = $db->query("
            SELECT COUNT(*) as fermes 
            FROM gul_adr_products 
            WHERE actif = 1 AND corde_article_ferme = 'x'
        ");
        $stats['fermes'] = $stmt->fetch()['fermes'] ?? 0;
        
        // Répartition par classe ADR
        $stmt = $db->query("
            SELECT classe_adr, COUNT(*) as count 
            FROM gul_adr_products 
            WHERE actif = 1 AND classe_adr IS NOT NULL 
            GROUP BY classe_adr 
            ORDER BY classe_adr
        ");
        $stats['classes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Répartition par catégorie transport
        $stmt = $db->query("
            SELECT categorie_transport, COUNT(*) as count 
            FROM gul_adr_products 
            WHERE actif = 1 AND categorie_transport IS NOT NULL 
            GROUP BY categorie_transport 
            ORDER BY categorie_transport
        ");
        $stats['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors du calcul des statistiques',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Recherche de produits similaires (optionnel pour le futur)
 */
function handleSimilarProducts($db, $code, $limit = 10) {
    // Récupérer le produit de référence
    $stmt = $db->prepare("
        SELECT classe_adr, categorie_transport, danger_environnement 
        FROM gul_adr_products 
        WHERE code_produit = ? AND actif = 1
    ");
    $stmt->execute([$code]);
    $reference = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reference) {
        throw new Exception('Produit de référence non trouvé');
    }
    
    // Chercher des produits similaires
    $sql = "SELECT 
                code_produit, 
                nom_produit,
                numero_un, 
                classe_adr, 
                danger_environnement,
                categorie_transport
            FROM gul_adr_products 
            WHERE actif = 1 
            AND code_produit != ?
            AND (
                classe_adr = ? 
                OR categorie_transport = ?
                OR danger_environnement = ?
            )
            ORDER BY 
                CASE 
                    WHEN classe_adr = ? AND categorie_transport = ? THEN 1
                    WHEN classe_adr = ? THEN 2
                    WHEN categorie_transport = ? THEN 3
                    ELSE 4
                END,
                code_produit
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $code,
        $reference['classe_adr'],
        $reference['categorie_transport'],
        $reference['danger_environnement'],
        $reference['classe_adr'],
        $reference['categorie_transport'],
        $reference['classe_adr'],
        $reference['categorie_transport'],
        $limit
    ]);
    
    $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'similar_products' => $similar,
        'count' => count($similar),
        'reference' => $reference
    ]);
}

/**
 * Validation et nettoyage des paramètres d'entrée
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'boolean':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN);
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Logging des recherches pour analytics (optionnel)
 */
function logSearch($query, $action, $resultCount = 0) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $_SESSION['user']['username'] ?? 'anonymous',
        'query' => $query,
        'action' => $action,
        'result_count' => $resultCount,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Log vers fichier ou base de données selon configuration
    if (defined('ENABLE_SEARCH_LOGGING') && ENABLE_SEARCH_LOGGING) {
        $logFile = ROOT_PATH . '/storage/logs/adr_search.log';
        error_log(json_encode($logData) . "\n", 3, $logFile);
    }
}

/**
 * Cache simple pour améliorer les performances (optionnel)
 */
function getCachedResult($key, $ttl = 300) {
    $cacheFile = ROOT_PATH . '/storage/cache/adr_search_' . md5($key) . '.json';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    
    return null;
}

function setCachedResult($key, $data) {
    $cacheDir = ROOT_PATH . '/storage/cache/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . 'adr_search_' . md5($key) . '.json';
    file_put_contents($cacheFile, json_encode($data));
}

/**
 * Nettoyage du cache (à appeler périodiquement)
 */
function cleanCache($maxAge = 3600) {
    $cacheDir = ROOT_PATH . '/storage/cache/';
    if (!is_dir($cacheDir)) return;
    
    $files = glob($cacheDir . 'adr_search_*.json');
    foreach ($files as $file) {
        if (time() - filemtime($file) > $maxAge) {
            unlink($file);
        }
    }
}

/**
 * Export des résultats au format CSV (optionnel)
 */
function exportToCSV($products) {
    $headers = [
        'Code produit',
        'Nom produit', 
        'Nom technique',
        'Numéro UN',
        'Description UN',
        'Classe ADR',
        'Groupe emballage',
        'Catégorie transport',
        'Danger environnement',
        'Type contenant',
        'Poids contenant',
        'Statut',
        'URL FDS'
    ];
    
    $csv = implode(',', array_map(function($h) { return '"' . $h . '"'; }, $headers)) . "\n";
    
    foreach ($products as $product) {
        $row = [
            $product['code_produit'],
            $product['nom_produit'] ?? '',
            $product['nom_technique'] ?? '',
            $product['numero_un'] ?? '',
            $product['nom_description_un'] ?? '',
            $product['classe_adr'] ?? '',
            $product['groupe_emballage'] ?? '',
            $product['categorie_transport'] ?? '',
            $product['danger_environnement'] ?? '',
            $product['type_contenant'] ?? '',
            $product['poids_contenant'] ?? '',
            $product['corde_article_ferme'] === 'x' ? 'Fermé' : 'Actif',
            $product['fds_url'] ?? ''
        ];
        
        $csv .= implode(',', array_map(function($field) { 
            return '"' . str_replace('"', '""', $field) . '"'; 
        }, $row)) . "\n";
    }
    
    return $csv;
}
?>

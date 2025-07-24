<?php
/**
 * Titre: API de recherche ADR corrigée avec vraie structure BDD
 * Chemin: /public/adr/search/search.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié', 'success' => false]);
    exit;
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT_PATH . '/config/config.php';

const SEARCH_CONFIG = [
    'min_chars' => 2,
    'max_suggestions' => 15,
    'max_results' => 100
];

$action = $_GET['action'] ?? 'suggestions';
$query = trim($_GET['q'] ?? '');
$limit = max(1, min((int)($_GET['limit'] ?? 15), SEARCH_CONFIG['max_results']));
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
 * Suggestions avec vraie structure BDD
 */
function handleSuggestions($db, $query, $limit) {
    if (strlen($query) < SEARCH_CONFIG['min_chars']) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        return;
    }
    
    $searchQuery = "%{$query}%";
    $exactQuery = $query . "%";
    
    $sql = "SELECT DISTINCT 
                code_produit, 
                nom_produit,
                nom_technique,
                numero_un,
                numero_etiquette,
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
        $searchQuery, $searchQuery, $searchQuery,
        $query, $exactQuery, $exactQuery, $exactQuery,
        $limit
    ]);
    
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter classe_adr calculée depuis numero_etiquette
    foreach ($suggestions as &$suggestion) {
        $suggestion['classe_adr'] = getClasseFromEtiquette($suggestion['numero_etiquette']);
    }
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'count' => count($suggestions),
        'query' => $query
    ]);
}

/**
 * Recherche complète
 */
function handleFullSearch($db, $query, $limit, $classe, $groupe, $adr_status, $env_danger) {
    $conditions = ['actif = 1'];
    $params = [];
    
    if (!empty($query)) {
        // Recherche spéciale UN
        if (preg_match('/^UN?(\d+)$/i', $query, $matches)) {
            $conditions[] = "numero_un = ?";
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
                p.numero_etiquette,
                p.code_produit
            LIMIT ?";
    
    // Paramètres pour l'ordre
    if (!empty($query) && !preg_match('/^UN?\d+$/i', $query)) {
        $orderParams = [$query, $query . '%', $query . '%', $query . '%'];
    } else {
        $orderParams = ['', '', '', ''];
    }
    
    $allParams = array_merge($params, $orderParams, [$limit]);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($allParams);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enrichir les données
    foreach ($products as &$product) {
        $product['fds_url'] = "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($product['code_produit']);
        $product['classe_adr'] = getClasseFromEtiquette($product['numero_etiquette']);
        
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
        'count' => count($products),
        'total' => count($products),
        'query' => $query,
        'filters' => compact('classe', 'groupe', 'adr_status', 'env_danger')
    ]);
}

/**
 * Détail produit
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
    
    $product['fds_url'] = "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($product['code_produit']);
    $product['classe_adr'] = getClasseFromEtiquette($product['numero_etiquette']);
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
}

/**
 * Produits populaires
 */
function handlePopularProducts($db, $limit) {
    $sql = "SELECT 
                code_produit, 
                nom_produit,
                nom_technique,
                numero_un, 
                numero_etiquette, 
                danger_environnement,
                type_contenant,
                poids_contenant,
                corde_article_ferme,
                categorie_transport,
                groupe_emballage
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
    
    foreach ($products as &$product) {
        $product['fds_url'] = "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($product['code_produit']);
        $product['classe_adr'] = getClasseFromEtiquette($product['numero_etiquette']);
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
}

/**
 * Statistiques
 */
function handleStats($db) {
    try {
        $stats = [];
        
        // Total produits actifs
        $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1");
        $stats['total'] = $stmt->fetch()['total'] ?? 0;
        
        // Produits ADR
        $stmt = $db->query("
            SELECT COUNT(*) as adr 
            FROM gul_adr_products 
            WHERE actif = 1 AND numero_un IS NOT NULL AND numero_un != ''
        ");
        $stats['adr'] = $stmt->fetch()['adr'] ?? 0;
        
        // Produits ENV
        $stmt = $db->query("
            SELECT COUNT(*) as env 
            FROM gul_adr_products 
            WHERE actif = 1 AND danger_environnement = 'OUI'
        ");
        $stats['env'] = $stmt->fetch()['env'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur statistiques',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Convertir numero_etiquette en classe ADR
 */
function getClasseFromEtiquette($numero_etiquette) {
    if (empty($numero_etiquette)) return null;
    
    // Mapping numéro étiquette -> classe ADR
    $mapping = [
        '1' => '1',      // Explosifs
        '2' => '2',      // Gaz
        '3' => '3',      // Liquides inflammables
        '4' => '4',      // Solides inflammables
        '5' => '5',      // Comburants
        '6' => '6',      // Toxiques
        '7' => '7',      // Radioactifs
        '8' => '8',      // Corrosifs
        '9' => '9'       // Divers
    ];
    
    // Extraire le premier chiffre
    $firstChar = substr(trim($numero_etiquette), 0, 1);
    return $mapping[$firstChar] ?? null;
}
?>

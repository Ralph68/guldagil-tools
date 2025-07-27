<?php
/**
 * Titre: API de recherche ADR complète et optimisée
 * Chemin: /public/adr/search/search.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    'max_results' => 100,
    'cache_duration' => 300 // 5 minutes
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
        case 'recent':
            handleRecentUpdates($db, $limit);
            break;
        case 'stats':
            handleStats($db);
            break;
        default:
            throw new Exception('Action non supportée');
    }
} catch (Exception $e) {
    // Log de l'erreur pour débogage
    error_log('Erreur dans search.php: ' . $e->getMessage());

    // Réponse d'erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}

/**
 * Suggestions avec structure BDD réelle
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
            )
            ORDER BY 
                CASE 
                    WHEN code_produit LIKE ? THEN 1
                    WHEN nom_produit LIKE ? THEN 2
                    WHEN nom_technique LIKE ? THEN 3
                    ELSE 4
                END,
                code_produit ASC
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $exactQuery, $searchQuery, $searchQuery, $searchQuery, 
        $query, $exactQuery, $exactQuery, $exactQuery, $exactQuery, $limit
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatage des suggestions
    $suggestions = array_map(function($row) {
        return [
            'code_produit' => $row['code_produit'],
            'nom_produit' => $row['nom_produit'] ?: $row['nom_technique'],
            'numero_un' => $row['numero_un'],
            'type' => 'product',
            'score' => calculateRelevanceScore($row)
        ];
    }, $results);
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'count' => count($suggestions)
    ]);
}

/**
 * Recherche complète avec filtres
 */
function handleFullSearch($db, $query, $limit, $classe, $groupe, $adr_status, $env_danger) {
    if (strlen($query) < SEARCH_CONFIG['min_chars']) {
        echo json_encode(['success' => false, 'error' => 'Requête trop courte']);
        return;
    }
    
    $searchQuery = "%{$query}%";
    $exactQuery = $query . "%";
    
    // Construction de la requête avec filtres
    $sql = "SELECT 
                code_produit,
                nom_produit,
                nom_technique,
                numero_un,
                numero_etiquette,
                groupe_emballage,
                danger_environnement,
                type_contenant,
                categorie_transport,
                corde_article_ferme,
                poids_contenant,
                url_fds,
                date_maj
            FROM gul_adr_products 
            WHERE actif = 1";
    
    $params = [];
    
    // Filtre de recherche principal
    $sql .= " AND (
        code_produit LIKE ? 
        OR nom_produit LIKE ? 
        OR nom_technique LIKE ?
        OR numero_un LIKE ?
        OR CONCAT('UN', numero_un) LIKE ?
    )";
    $params = array_merge($params, [$exactQuery, $searchQuery, $searchQuery, $query, $exactQuery]);
    
    // Filtres additionnels
    if (!empty($classe)) {
        $sql .= " AND categorie_transport = ?";
        $params[] = $classe;
    }
    
    if (!empty($groupe)) {
        $sql .= " AND groupe_emballage = ?";
        $params[] = $groupe;
    }
    
    if ($adr_status === 'adr_only') {
        $sql .= " AND numero_un IS NOT NULL AND numero_un != ''";
    } elseif ($adr_status === 'non_adr') {
        $sql .= " AND (numero_un IS NULL OR numero_un = '')";
    }
    
    if ($env_danger) {
        $sql .= " AND danger_environnement = '1'";
    }
    
    // Tri par pertinence
    $sql .= " ORDER BY 
        CASE 
            WHEN code_produit = ? THEN 1
            WHEN code_produit LIKE ? THEN 2
            WHEN nom_produit LIKE ? THEN 3
            WHEN nom_technique LIKE ? THEN 4
            ELSE 5
        END,
        code_produit ASC
        LIMIT ?";
    
    $params = array_merge($params, [$query, $exactQuery, $exactQuery, $exactQuery, $limit]);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatage des résultats
    $formattedResults = array_map(function($row) {
        return [
            'code_produit' => $row['code_produit'],
            'nom_produit' => $row['nom_produit'] ?: $row['nom_technique'],
            'nom_technique' => $row['nom_technique'],
            'numero_un' => $row['numero_un'],
            'numero_etiquette' => $row['numero_etiquette'],
            'groupe_emballage' => $row['groupe_emballage'],
            'categorie_transport' => $row['categorie_transport'],
            'danger_environnement' => $row['danger_environnement'],
            'type_contenant' => $row['type_contenant'],
            'poids_contenant' => $row['poids_contenant'],
            'corde_article_ferme' => $row['corde_article_ferme'],
            'url_fds' => $row['url_fds'] ?: generateFdsUrl($row['code_produit']),
            'date_maj' => $row['date_maj'],
            'is_adr' => !empty($row['numero_un']),
            'is_closed' => $row['corde_article_ferme'] === 'x'
        ];
    }, $results);
    
    // Log de la recherche pour statistiques
    logSearch($db, $query, count($results));
    
    echo json_encode([
        'success' => true,
        'results' => $formattedResults,
        'count' => count($formattedResults),
        'total_found' => count($formattedResults),
        'query' => $query,
        'filters_applied' => [
            'classe' => $classe,
            'groupe' => $groupe,
            'adr_status' => $adr_status,
            'env_danger' => $env_danger
        ]
    ]);
}

/**
 * Détail d'un produit
 */
function handleProductDetail($db, $code) {
    if (empty($code)) {
        echo json_encode(['success' => false, 'error' => 'Code produit manquant']);
        return;
    }
    
    $sql = "SELECT * FROM gul_adr_products WHERE code_produit = ? AND actif = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$code]);
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Produit non trouvé']);
        return;
    }
    
    // Enrichissement des données
    $product['url_fds'] = $product['url_fds'] ?: generateFdsUrl($code);
    $product['is_adr'] = !empty($product['numero_un']);
    $product['is_closed'] = $product['corde_article_ferme'] === 'x';
    
    // Historique des recherches pour ce produit
    $searchHistory = getProductSearchHistory($db, $code);
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'search_history' => $searchHistory
    ]);
}

/**
 * Produits populaires (les plus recherchés)
 */
function handlePopularProducts($db, $limit) {
    // Récupération depuis les logs de recherche ou table dédiée
    $sql = "SELECT 
                p.code_produit,
                p.nom_produit,
                p.nom_technique,
                p.numero_un,
                COUNT(sl.id) as search_count
            FROM gul_adr_products p
            LEFT JOIN system_logs sl ON sl.message LIKE CONCAT('%', p.code_produit, '%') 
                AND sl.type = 'search'
                AND sl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHERE p.actif = 1
            GROUP BY p.code_produit
            ORDER BY search_count DESC, p.code_produit ASC
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si pas de données de recherche, prendre des produits par défaut
    if (empty($products)) {
        $sql = "SELECT 
                    code_produit,
                    nom_produit,
                    nom_technique,
                    numero_un,
                    0 as search_count
                FROM gul_adr_products 
                WHERE actif = 1 
                AND numero_un IS NOT NULL
                ORDER BY code_produit ASC
                LIMIT ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
}

/**
 * Produits récemment mis à jour
 */
function handleRecentUpdates($db, $limit) {
    $sql = "SELECT 
                code_produit,
                nom_produit,
                nom_technique,
                numero_un,
                date_maj
            FROM gul_adr_products 
            WHERE actif = 1 
            AND date_maj IS NOT NULL
            ORDER BY date_maj DESC
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatage des dates
    foreach ($products as &$product) {
        $product['date_maj_formatted'] = $product['date_maj'] 
            ? date('d/m/Y', strtotime($product['date_maj'])) 
            : 'Date inconnue';
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
}

/**
 * Statistiques globales
 */
function handleStats($db) {
    // Nombre total de produits
    $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1");
    $totalProducts = $stmt->fetch()['total'];
    
    // Nombre de produits ADR
    $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1 AND numero_un IS NOT NULL AND numero_un != ''");
    $adrProducts = $stmt->fetch()['total'];
    
    // Nombre de produits avec FDS
    $stmt = $db->query("SELECT COUNT(*) as total FROM gul_adr_products WHERE actif = 1 AND url_fds IS NOT NULL AND url_fds != ''");
    $fdsProducts = $stmt->fetch()['total'];
    
    // Recherches aujourd'hui
    $stmt = $db->query("SELECT COUNT(*) as total FROM system_logs WHERE type = 'search' AND DATE(created_at) = CURDATE()");
    $searchesToday = $stmt->fetch()['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_products' => (int)$totalProducts,
            'adr_products' => (int)$adrProducts,
            'fds_products' => (int)$fdsProducts,
            'searches_today' => (int)$searchesToday,
            'last_update' => date('Y-m-d H:i:s')
        ]
    ]);
}

/**
 * Calcul du score de pertinence
 */
function calculateRelevanceScore($row) {
    $score = 0;
    
    // Bonus pour code produit exact
    if (!empty($row['code_produit'])) $score += 10;
    
    // Bonus pour nom produit
    if (!empty($row['nom_produit'])) $score += 5;
    
    // Bonus pour produits ADR
    if (!empty($row['numero_un'])) $score += 3;
    
    // Malus pour articles fermés
    if ($row['corde_article_ferme'] === 'x') $score -= 2;
    
    return $score;
}

/**
 * Génération URL FDS par défaut
 */
function generateFdsUrl($codeProduct) {
    return "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($codeProduct);
}

/**
 * Log des recherches pour statistiques
 */
function logSearch($db, $query, $resultsCount) {
    try {
        $sql = "INSERT INTO system_logs (type, message, user_id, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'search',
            "Recherche ADR: '{$query}' - {$resultsCount} résultats",
            $_SESSION['user']['id'] ?? 0
        ]);
    } catch (Exception $e) {
        // Log silencieux, ne pas faire échouer la recherche
        error_log("Erreur log recherche: " . $e->getMessage());
    }
}

/**
 * Historique des recherches d'un produit
 */
function getProductSearchHistory($db, $code) {
    try {
        $sql = "SELECT COUNT(*) as search_count, MAX(created_at) as last_search 
                FROM system_logs 
                WHERE type = 'search' 
                AND message LIKE ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(["%{$code}%"]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return ['search_count' => 0, 'last_search' => null];
    }
}
?>
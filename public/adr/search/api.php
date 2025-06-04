<?php
// public/adr/search/api.php - API de recherche intelligente pour produits ADR
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

// Vérification de base (à améliorer avec l'auth ADR complète)
session_start();
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentification requise']);
    exit;
}

require __DIR__ . '/../../../config.php';

// Configuration de la recherche
const SEARCH_CONFIG = [
    'min_chars' => 2,
    'max_suggestions' => 20,
    'max_results' => 100,
    'fuzzy_threshold' => 0.3  // Seuil pour la recherche floue
];

// Paramètres de la requête
$action = $_GET['action'] ?? 'suggestions';
$query = trim($_GET['q'] ?? '');
$rawLimit = (int)($_GET['limit'] ?? SEARCH_CONFIG['max_suggestions']);
$limit = max(1, min($rawLimit, SEARCH_CONFIG['max_results']));

try {
    switch ($action) {
        case 'suggestions':
            handleSuggestions($db, $query, $limit);
            break;
            
        case 'search':
            handleFullSearch($db, $query, $limit);
            break;
            
        case 'detail':
            handleProductDetail($db, $query);
            break;
            
        case 'popular':
            handlePopularProducts($db, $limit);
            break;
            
        default:
            throw new Exception('Action non supportée');
    }
    
} catch (Exception $e) {
    error_log("Erreur API recherche ADR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la recherche: ' . $e->getMessage()
    ]);
}

/**
 * Gestion des suggestions pour l'autocomplete
 */
function handleSuggestions($db, $query, $limit) {
    if (strlen($query) < SEARCH_CONFIG['min_chars']) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        return;
    }
    
    $suggestions = searchProducts($db, $query, $limit, true);
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'query' => $query,
        'count' => count($suggestions)
    ]);
}

/**
 * Recherche complète avec tous les détails
 */
function handleFullSearch($db, $query, $limit) {
    if (strlen($query) < SEARCH_CONFIG['min_chars']) {
        echo json_encode(['success' => false, 'error' => 'Terme de recherche trop court']);
        return;
    }
    
    $results = searchProducts($db, $query, $limit, false);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => $query,
        'count' => count($results),
        'total_time' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms'
    ]);
}

/**
 * Détail complet d'un produit spécifique
 */
function handleProductDetail($db, $codeProduct) {
    if (empty($codeProduct)) {
        echo json_encode(['success' => false, 'error' => 'Code produit requis']);
        return;
    }
    
    $stmt = $db->prepare("
        SELECT p.*, q.quota_max_vehicule, q.quota_max_colis, q.description as description_categorie
        FROM gul_adr_products p
        LEFT JOIN gul_adr_quotas q ON p.categorie_transport = q.categorie_transport
        WHERE p.code_produit = ? AND p.actif = 1
    ");
    
    $stmt->execute([$codeProduct]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Produit non trouvé']);
        return;
    }
    
    // Enrichir avec des informations complémentaires
    $product = enrichProductData($product);
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'related' => findRelatedProducts($db, $product)
    ]);
}

/**
 * Produits populaires ou récemment consultés
 */
function handlePopularProducts($db, $limit) {
    // Pour l'instant, on retourne les produits ADR les plus "importants"
    // En production, vous pourriez avoir une table de logs de consultation
    
    $stmt = $db->prepare("
        SELECT code_produit, nom_produit, numero_un, categorie_transport, danger_environnement
        FROM gul_adr_products 
        WHERE actif = 1 
        AND numero_un IS NOT NULL 
        AND numero_un != ''
        ORDER BY 
            CASE 
                WHEN danger_environnement = 'OUI' THEN 1
                WHEN categorie_transport IN ('1', '2') THEN 2
                ELSE 3
            END,
            nom_produit
        LIMIT ?
    ");
    
    $stmt->execute([$limit]);
    $popular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'popular' => $popular,
        'count' => count($popular)
    ]);
}

/**
 * Fonction principale de recherche avec logique intelligente
 */
function searchProducts($db, $query, $limit, $suggestionsOnly = false) {
    $query = trim($query);
    
    // Déterminer le type de recherche
    $searchType = detectSearchType($query);
    
    // Construire la requête SQL selon le type
    switch ($searchType) {
        case 'code_exact':
            return searchByExactCode($db, $query, $limit);
            
        case 'un_number':
            return searchByUNNumber($db, $query, $limit);
            
        case 'code_partial':
            return searchByPartialCode($db, $query, $limit, $suggestionsOnly);
            
        case 'name_search':
        default:
            return searchByName($db, $query, $limit, $suggestionsOnly);
    }
}

/**
 * Détecte le type de recherche basé sur le pattern de la requête
 */
function detectSearchType($query) {
    // Code produit exact (pattern: lettres + chiffres)
    if (preg_match('/^[A-Z0-9\-_]{4,}$/i', $query)) {
        return 'code_exact';
    }
    
    // Numéro UN (commence par des chiffres)
    if (preg_match('/^[0-9]{3,4}$/', $query)) {
        return 'un_number';
    }
    
    // Code partiel (commence par des lettres)
    if (preg_match('/^[A-Z]{2,}/i', $query) && strlen($query) < 8) {
        return 'code_partial';
    }
    
    // Recherche par nom
    return 'name_search';
}

/**
 * Recherche par code produit exact
 */
function searchByExactCode($db, $code, $limit) {
    $stmt = $db->prepare("
        SELECT * FROM vue_adr_products_complet 
        WHERE code_produit = ?
        LIMIT 1
    ");
    
    $stmt->execute([$code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? [enrichProductData($result)] : [];
}

/**
 * Recherche par numéro UN
 */
function searchByUNNumber($db, $unNumber, $limit) {
    $stmt = $db->prepare("
        SELECT * FROM vue_adr_products_complet 
        WHERE numero_un = ? OR numero_un LIKE ?
        ORDER BY 
            CASE WHEN numero_un = ? THEN 1 ELSE 2 END,
            nom_produit
        LIMIT ?
    ");
    
    $unPattern = $unNumber . '%';
    $stmt->execute([$unNumber, $unPattern, $unNumber, $limit]);
    
    return array_map('enrichProductData', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Recherche par code partiel
 */
function searchByPartialCode($db, $codePartial, $limit, $suggestionsOnly) {
    $pattern = $codePartial . '%';
    
    $orderBy = $suggestionsOnly ? 
        "ORDER BY LENGTH(code_produit), code_produit" :
        "ORDER BY 
            CASE WHEN code_produit LIKE ? THEN 1 ELSE 2 END,
            LENGTH(code_produit),
            nom_produit";
    
    $stmt = $db->prepare("
        SELECT * FROM vue_adr_products_complet 
        WHERE code_produit LIKE ? OR code_produit LIKE ?
        $orderBy
        LIMIT ?
    ");
    
    $params = $suggestionsOnly ? 
        [$pattern, strtolower($pattern), $limit] :
        [$pattern, $pattern, strtolower($pattern), $limit];
    
    $stmt->execute($params);
    
    return array_map('enrichProductData', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Recherche par nom avec logique floue
 */
function searchByName($db, $name, $limit, $suggestionsOnly) {
    $words = explode(' ', $name);
    $searchTerms = [];
    $params = [];
    
    // Construire la recherche multi-mots
    foreach ($words as $word) {
        if (strlen(trim($word)) >= 2) {
            $searchTerms[] = "(nom_produit LIKE ? OR nom_technique LIKE ? OR nom_description_un LIKE ?)";
            $pattern = '%' . trim($word) . '%';
            $params[] = $pattern;
            $params[] = $pattern;
            $params[] = $pattern;
        }
    }
    
    if (empty($searchTerms)) {
        return [];
    }
    
    $whereClause = implode(' AND ', $searchTerms);
    
    // Recherche exacte d'abord, puis floue
    // Lorsque le nombre de résultats demandé est inférieur à 5, la limite
    // négative du second SELECT provoquait une erreur SQL. On s'assure
    // d'avoir une valeur positive.
    $firstLimit  = min($limit, 10);
    $secondLimit = max($limit - 5, 0);

    $sql = "
        (SELECT *, 1 as priority FROM vue_adr_products_complet
         WHERE nom_produit LIKE ?
         LIMIT $firstLimit)

        UNION ALL

        (SELECT *, 2 as priority FROM vue_adr_products_complet
         WHERE $whereClause
         AND nom_produit NOT LIKE ?
         LIMIT $secondLimit)

        ORDER BY priority,
                 CASE WHEN numero_un IS NOT NULL THEN 1 ELSE 2 END,
                 nom_produit
        LIMIT ?
    ";
    
    $exactPattern = '%' . $name . '%';
    $finalParams = array_merge([$exactPattern], $params, [$exactPattern, $limit]);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($finalParams);
    
    return array_map('enrichProductData', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Enrichit les données produit avec des informations calculées
 */
function enrichProductData($product) {
    if (!$product) return $product;
    
    // Statut ADR
    $product['is_adr'] = !empty($product['numero_un']);
    
    // Niveau de danger (1 = très dangereux, 5 = peu dangereux)
    $product['danger_level'] = calculateDangerLevel($product);
    
    // Informations de transport formatées
    $product['transport_info'] = formatTransportInfo($product);
    
    // Restrictions et avertissements
    $product['warnings'] = generateWarnings($product);
    
    // Score de recherche (pour le ranking)
    $product['search_score'] = calculateSearchScore($product);
    
    return $product;
}

/**
 * Calcule le niveau de danger d'un produit
 */
function calculateDangerLevel($product) {
    if (empty($product['numero_un'])) {
        return 5; // Non dangereux
    }
    
    // Basé sur la catégorie de transport
    switch ($product['categorie_transport']) {
        case '1': return 1; // Très dangereux
        case '2': return 2; // Dangereux
        case '3': return 3; // Moyennement dangereux
        case '4': return 4; // Peu dangereux
        default: return 3;  // Niveau moyen par défaut
    }
}

/**
 * Formate les informations de transport
 */
function formatTransportInfo($product) {
    $info = [];
    
    if (!empty($product['categorie_transport'])) {
        $info['categorie'] = "Catégorie " . $product['categorie_transport'];
        
        if (!empty($product['description_categorie'])) {
            $info['categorie_desc'] = $product['description_categorie'];
        }
    }
    
    if (!empty($product['code_tunnel'])) {
        $info['tunnel'] = "Restriction tunnel: " . $product['code_tunnel'];
    }
    
    if (!empty($product['groupe_emballage'])) {
        $info['emballage'] = "Groupe emballage: " . $product['groupe_emballage'];
    }
    
    if (!empty($product['quota_max_vehicule']) && $product['quota_max_vehicule'] < 999999) {
        $info['quota_vehicule'] = "Max véhicule: " . number_format($product['quota_max_vehicule'], 0) . " kg";
    }
    
    return $info;
}

/**
 * Génère les avertissements pour un produit
 */
function generateWarnings($product) {
    $warnings = [];
    
    if ($product['corde_article_ferme'] === 'x') {
        $warnings[] = [
            'type' => 'error',
            'message' => 'Produit fermé - Ne plus expédier'
        ];
    }
    
    if ($product['danger_environnement'] === 'OUI') {
        $warnings[] = [
            'type' => 'warning',
            'message' => 'Polluant marin - Précautions environnementales requises'
        ];
    }
    
    if (!empty($product['numero_un']) && empty($product['nom_description_un'])) {
        $warnings[] = [
            'type' => 'info',
            'message' => 'Description UN manquante - Vérifier la réglementation'
        ];
    }
    
    if ($product['categorie_transport'] === '1') {
        $warnings[] = [
            'type' => 'error',
            'message' => 'Catégorie 1 - Transport très restreint'
        ];
    }
    
    return $warnings;
}

/**
 * Calcule un score de pertinence pour le ranking
 */
function calculateSearchScore($product) {
    $score = 100;
    
    // Bonus pour les produits ADR (plus importants)
    if (!empty($product['numero_un'])) {
        $score += 20;
    }
    
    // Malus pour les produits fermés
    if ($product['corde_article_ferme'] === 'x') {
        $score -= 30;
    }
    
    // Bonus pour les produits dangereux (plus critiques)
    if ($product['danger_environnement'] === 'OUI') {
        $score += 10;
    }
    
    // Bonus selon la catégorie (plus c'est dangereux, plus c'est important)
    switch ($product['categorie_transport']) {
        case '1': $score += 15; break;
        case '2': $score += 10; break;
        case '3': $score += 5; break;
    }
    
    return max(0, $score);
}

/**
 * Trouve des produits liés (même famille, même UN, etc.)
 */
function findRelatedProducts($db, $product) {
    $related = [];
    
    // Produits avec le même numéro UN
    if (!empty($product['numero_un'])) {
        $stmt = $db->prepare("
            SELECT code_produit, nom_produit, type_contenant, poids_contenant
            FROM gul_adr_products 
            WHERE numero_un = ? 
            AND code_produit != ? 
            AND actif = 1
            LIMIT 5
        ");
        $stmt->execute([$product['numero_un'], $product['code_produit']]);
        $related['same_un'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Produits de la même famille (préfixe du code)
    $codePrefix = substr($product['code_produit'], 0, 3);
    if (strlen($codePrefix) >= 3) {
        $stmt = $db->prepare("
            SELECT code_produit, nom_produit, numero_un
            FROM gul_adr_products 
            WHERE code_produit LIKE ? 
            AND code_produit != ? 
            AND actif = 1
            LIMIT 5
        ");
        $stmt->execute([$codePrefix . '%', $product['code_produit']]);
        $related['same_family'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $related;
}

/**
 * Log des recherches pour analytics (optionnel)
 */
function logSearch($query, $resultCount, $searchType) {
    // Vous pouvez implémenter ici un système de logs pour analyser
    // les recherches les plus fréquentes, optimiser l'index, etc.
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'query' => $query,
        'result_count' => $resultCount,
        'search_type' => $searchType,
        'user' => $_SESSION['adr_user'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Log dans le fichier système
    error_log('ADR_SEARCH: ' . json_encode($logEntry));
}
?>

<?php
/**
 * Titre: API de recherche ADR complète
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
    'min_chars' => 3,
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

try {
    switch ($action) {
        case 'suggestions':
            handleSuggestions($db, $query, $limit);
            break;
            
        case 'search':
            handleFullSearch($db, $query, $limit, $classe, $groupe, $adr_status, $env_danger);
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
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}

/**
 * Gestion des suggestions avec recherche codes liés
 */
function handleSuggestions($db, $query, $limit) {
    if (strlen($query) < SEARCH_CONFIG['min_chars']) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        return;
    }
    
    $searchQuery = "%{$query}%";
    $exactQuery = $query . "%";
    
    // Recherche avec codes liés (ex: SOL11 trouvera aussi SOL111)
    $sql = "SELECT DISTINCT 
                code_produit, 
                nom_produit,
                numero_un,
                classe_adr,
                groupe_emballage,
                danger_environnement,
                type_contenant
            FROM gul_adr_products 
            WHERE actif = 1 
            AND (
                code_produit LIKE ? 
                OR code_produit LIKE ?
                OR nom_produit LIKE ? 
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
        $exactQuery, $searchQuery, $searchQuery, $searchQuery, $searchQuery, $searchQuery,
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
 * Recherche complète avec nouveaux filtres
 */
function handleFullSearch($db, $query, $limit, $classe, $groupe, $adr_status, $env_danger) {
    $conditions = ['actif = 1'];
    $params = [];
    
    // Recherche textuelle avec codes liés
    if (!empty($query)) {
        $searchQuery = "%{$query}%";
        $exactQuery = $query . "%";
        $conditions[] = "(
            code_produit LIKE ? 
            OR code_produit LIKE ?
            OR nom_produit LIKE ? 
            OR numero_un LIKE ?
            OR CONCAT('UN', numero_un) LIKE ?
            OR nom_description_un LIKE ?
            OR nom_technique LIKE ?
        )";
        $params = array_merge($params, [
            $exactQuery, $searchQuery, $searchQuery, 
            $searchQuery, $searchQuery, $searchQuery, $searchQuery
        ]);
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
        $conditions[] = "danger_environnement = 'oui'";
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    // Requête principale avec informations complètes
    $sql = "SELECT 
                p.*,
                q.quota_max_vehicule,
                q.quota_max_colis,
                q.description as description_categorie,
                CASE 
                    WHEN p.numero_un IS NOT NULL AND p.numero_un != '' THEN 'ADR'
                    WHEN p.corde_article_ferme = 'x' THEN 'Fermé'
                    ELSE 'Standard'
                END as statut_produit,
                CASE 
                    WHEN p.classe_adr IS NOT NULL THEN CONCAT('Classe ', p.classe_adr)
                    ELSE NULL
                END as classe_label
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
    if (!empty($query)) {
        $orderParams = [$query, $query . '%', $query . '%', $query . '%'];
    } else {
        $orderParams = ['', '', '', ''];
    }
    
    $allParams = array_merge($params, $orderParams, [$limit]);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($allParams);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter URL FDS pour chaque produit
    foreach ($products as &$product) {
        $product['fds_url'] = "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($product['code_produit']);
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
} THEN 1
                    WHEN nom_produit LIKE ? THEN 2
                    WHEN numero_un LIKE ? THEN 3
                    ELSE 4
                END,
                code_produit
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $searchQuery, $searchQuery, $searchQuery, $searchQuery,
        $query . '%', $query . '%', $query . '%',
        $limit
    ]);
    
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'count' => count($suggestions)
    ]);
}

/**
 * Recherche complète avec filtres
 */
function handleFullSearch($db, $query, $limit, $category, $transport, $adr_only, $env_danger) {
    $conditions = ['actif = 1'];
    $params = [];
    
    // Recherche textuelle
    if (!empty($query)) {
        $searchQuery = "%{$query}%";
        $conditions[] = "(
            code_produit LIKE ? 
            OR nom_produit LIKE ? 
            OR numero_un LIKE ?
            OR CONCAT('UN', numero_un) LIKE ?
            OR nom_description_un LIKE ?
            OR nom_technique LIKE ?
        )";
        $params = array_merge($params, [
            $searchQuery, $searchQuery, $searchQuery, 
            $searchQuery, $searchQuery, $searchQuery
        ]);
    }
    
    // Filtres
    if (!empty($category)) {
        $conditions[] = "categorie_transport = ?";
        $params[] = $category;
    }
    
    if ($adr_only) {
        $conditions[] = "numero_un IS NOT NULL AND numero_un != ''";
    }
    
    if ($env_danger) {
        $conditions[] = "danger_environnement = 'oui'";
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    // Requête principale avec quotas
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
                    WHEN p.code_produit LIKE ? THEN 1
                    WHEN p.nom_produit LIKE ? THEN 2
                    WHEN p.numero_un LIKE ? THEN 3
                    ELSE 4
                END,
                p.categorie_transport,
                p.code_produit
            LIMIT ?";
    
    // Paramètres pour l'ordre + limit
    if (!empty($query)) {
        $orderParams = [$query . '%', $query . '%', $query . '%'];
    } else {
        $orderParams = ['', '', ''];
    }
    
    $allParams = array_merge($params, $orderParams, [$limit]);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($allParams);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total sans limite
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
        'filters' => compact('category', 'transport', 'adr_only', 'env_danger')
    ]);
}

/**
 * Détail d'un produit spécifique
 */
function handleProductDetail($db, $code) {
    if (empty($code)) {
        throw new Exception('Code produit requis');
    }
    
    $sql = "SELECT 
                p.*,
                q.quota_max_vehicule,
                q.quota_max_colis,
                q.description as description_categorie
            FROM gul_adr_products p
            LEFT JOIN gul_adr_quotas q ON p.categorie_transport = q.categorie_transport
            WHERE p.code_produit = ? AND p.actif = 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$code]);
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    // Historique des déclarations récentes
    $historySql = "SELECT 
                        d.date_expedition,
                        d.transporteur,
                        d.quantite_declaree,
                        d.unite_quantite,
                        df.nom as destinataire_nom,
                        df.ville as destinataire_ville
                   FROM gul_adr_declarations d
                   LEFT JOIN gul_adr_destinataires_frequents df ON d.destinataire_id = df.id
                   WHERE d.code_produit = ?
                   ORDER BY d.date_expedition DESC
                   LIMIT 10";
    
    $historyStmt = $db->prepare($historySql);
    $historyStmt->execute([$code]);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'history' => $history
    ]);
}

/**
 * Produits populaires/récents
 */
function handlePopularProducts($db, $limit) {
    // Produits les plus déclarés récemment
    $sql = "SELECT 
                p.code_produit,
                p.nom_produit,
                p.numero_un,
                p.classe_adr,
                p.groupe_emballage,
                p.danger_environnement,
                p.type_contenant,
                COUNT(d.id) as nb_declarations
            FROM gul_adr_products p
            INNER JOIN gul_adr_declarations d ON p.code_produit = d.code_produit
            WHERE p.actif = 1 
            AND d.date_expedition >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.code_produit, p.nom_produit, p.numero_un, 
                     p.classe_adr, p.groupe_emballage, p.danger_environnement, p.type_contenant
            ORDER BY nb_declarations DESC, p.code_produit
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    
    $popular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si pas assez, compléter avec produits ADR récents
    if (count($popular) < $limit) {
        $remaining = $limit - count($popular);
        $existingCodes = array_column($popular, 'code_produit');
        
        $fallbackSql = "SELECT 
                            code_produit,
                            nom_produit,
                            numero_un,
                            classe_adr,
                            groupe_emballage,
                            danger_environnement,
                            type_contenant,
                            0 as nb_declarations
                        FROM gul_adr_products 
                        WHERE actif = 1 
                        AND numero_un IS NOT NULL 
                        AND numero_un != ''";
        
        if (!empty($existingCodes)) {
            $placeholders = str_repeat('?,', count($existingCodes));
            $placeholders = rtrim($placeholders, ',');
            $fallbackSql .= " AND code_produit NOT IN ({$placeholders})";
        }
        
        $fallbackSql .= " ORDER BY date_modification DESC LIMIT ?";
        
        $fallbackStmt = $db->prepare($fallbackSql);
        $fallbackParams = array_merge($existingCodes, [$remaining]);
        $fallbackStmt->execute($fallbackParams);
        
        $fallback = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
        $popular = array_merge($popular, $fallback);
    }
    
    echo json_encode([
        'success' => true,
        'products' => $popular,
        'count' => count($popular)
    ]);
}

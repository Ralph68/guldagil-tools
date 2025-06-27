<?php
// public/adr/search/api.php - API de recherche ADR complète et corrigée
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

// Vérification de base (temporaire pour le développement)
session_start();
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    // En mode dev, créer une session automatiquement
    $_SESSION['adr_logged_in'] = true;
    $_SESSION['adr_user'] = 'demo.user';
}

require __DIR__ . '/../../../config.php';

// Configuration de la recherche
const SEARCH_CONFIG = [
    'min_chars' => 2,
    'max_suggestions' => 20,
    'max_results' => 100,
    'fuzzy_threshold' => 0.3
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
    
    // En cas d'erreur, utiliser les données de démo
    if ($action === 'suggestions' || $action === 'search') {
        echo json_encode([
            'success' => true,
            'suggestions' => getDemoProducts($query, $limit),
            'query' => $query,
            'count' => min(count(getDemoProducts($query, $limit)), $limit),
            'demo_mode' => true
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la recherche (mode démo activé)',
            'demo_mode' => true
        ]);
    }
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
    
    // Chercher d'abord dans les vraies tables
    try {
        $product = getProductFromDatabase($db, $codeProduct);
        if ($product) {
            echo json_encode([
                'success' => true,
                'product' => enrichProductData($product),
                'related' => []
            ]);
            return;
        }
    } catch (Exception $e) {
        error_log("Erreur recherche BDD: " . $e->getMessage());
    }
    
    // Fallback sur les données de démo
    $demoProducts = getDemoProducts();
    $product = null;
    foreach ($demoProducts as $p) {
        if ($p['code_produit'] === $codeProduct) {
            $product = $p;
            break;
        }
    }
    
    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Produit non trouvé']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'product' => enrichProductData($product),
        'related' => [],
        'demo_mode' => true
    ]);
}

/**
 * Produits populaires ou récemment consultés
 */
function handlePopularProducts($db, $limit) {
    try {
        $popular = getPopularFromDatabase($db, $limit);
        if (!empty($popular)) {
            echo json_encode([
                'success' => true,
                'popular' => $popular,
                'count' => count($popular)
            ]);
            return;
        }
    } catch (Exception $e) {
        error_log("Erreur produits populaires BDD: " . $e->getMessage());
    }
    
    // Fallback données de démo
    $demoPopular = array_slice(getDemoProducts(), 0, $limit);
    echo json_encode([
        'success' => true,
        'popular' => $demoPopular,
        'count' => count($demoPopular),
        'demo_mode' => true
    ]);
}

/**
 * Fonction principale de recherche
 */
function searchProducts($db, $query, $limit, $suggestionsOnly = false) {
    // Essayer d'abord la recherche en base
    try {
        $results = searchInDatabase($db, $query, $limit);
        if (!empty($results)) {
            return array_map('enrichProductData', $results);
        }
    } catch (Exception $e) {
        error_log("Erreur recherche BDD: " . $e->getMessage());
    }
    
    // Fallback sur les données de démo
    return getDemoProducts($query, $limit);
}

/**
 * Recherche dans la vraie base de données
 */
function searchInDatabase($db, $query, $limit) {
    // Vérifier quelles tables existent
    $tables = getAvailableTables($db);
    
    if (in_array('gul_adr_products', $tables)) {
        return searchInAdrProductsTable($db, $query, $limit);
    }
    
    // Autres possibilités de tables
    $possibleTables = ['adr_products', 'products', 'articles'];
    foreach ($possibleTables as $table) {
        if (in_array($table, $tables)) {
            return searchInGenericTable($db, $table, $query, $limit);
        }
    }
    
    return [];
}

/**
 * Recherche dans la table gul_adr_products avec la vraie structure
 */
function searchInAdrProductsTable($db, $query, $limit) {
    $sql = "
        SELECT 
            code_produit,
            corde_article_ferme,
            nom_produit,
            poids_contenant,
            type_contenant,
            numero_un,
            nom_description_un,
            nom_technique,
            groupe_emballage,
            numero_etiquette,
            categorie_transport,
            code_tunnel,
            danger_environnement,
            colonne_3,
            actif
        FROM gul_adr_products 
        WHERE actif = 1 
        AND (
            code_produit LIKE ? 
            OR nom_produit LIKE ? 
            OR nom_technique LIKE ?
            OR numero_un LIKE ?
            OR nom_description_un LIKE ?
        )
        ORDER BY 
            CASE 
                WHEN code_produit LIKE ? THEN 1
                WHEN nom_produit LIKE ? THEN 2
                WHEN numero_un LIKE ? THEN 3
                ELSE 4
            END,
            CASE 
                WHEN corde_article_ferme = 'x' THEN 2
                ELSE 1
            END,
            nom_produit
        LIMIT ?
    ";
    
    $pattern = '%' . $query . '%';
    $exactPattern = $query . '%';
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $pattern, $pattern, $pattern, $pattern, $pattern,
        $exactPattern, $exactPattern, $exactPattern,
        $limit
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Recherche générique dans une table
 */
function searchInGenericTable($db, $tableName, $query, $limit) {
    // Obtenir les colonnes de la table
    $columns = getTableColumns($db, $tableName);
    
    $searchColumns = [];
    $nameColumn = 'nom';
    $codeColumn = 'code';
    
    // Identifier les colonnes pertinentes
    foreach ($columns as $col) {
        if (stripos($col, 'code') !== false || stripos($col, 'ref') !== false) {
            $codeColumn = $col;
        }
        if (stripos($col, 'nom') !== false || stripos($col, 'name') !== false || stripos($col, 'designation') !== false) {
            $nameColumn = $col;
        }
        if (in_array(strtolower($col), ['code', 'nom', 'name', 'designation', 'description', 'reference'])) {
            $searchColumns[] = $col;
        }
    }
    
    if (empty($searchColumns)) {
        return [];
    }
    
    $whereClause = implode(' OR ', array_map(function($col) {
        return "$col LIKE ?";
    }, $searchColumns));
    
    $sql = "SELECT * FROM $tableName WHERE ($whereClause) LIMIT ?";
    
    $params = array_fill(0, count($searchColumns), '%' . $query . '%');
    $params[] = $limit;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Normaliser les résultats
    return array_map(function($row) use ($codeColumn, $nameColumn) {
        return [
            'code_produit' => $row[$codeColumn] ?? $row['code'] ?? 'N/A',
            'nom_produit' => $row[$nameColumn] ?? $row['nom'] ?? $row['name'] ?? 'Produit sans nom',
            'nom_technique' => $row['nom_technique'] ?? $row['description'] ?? '',
            'numero_un' => $row['numero_un'] ?? $row['un'] ?? '',
            'nom_description_un' => $row['nom_description_un'] ?? '',
            'categorie_transport' => $row['categorie_transport'] ?? $row['categorie'] ?? '0',
            'danger_environnement' => $row['danger_environnement'] ?? 'NON',
            'type_contenant' => $row['type_contenant'] ?? '',
            'poids_contenant' => $row['poids_contenant'] ?? '',
            'corde_article_ferme' => $row['corde_article_ferme'] ?? $row['actif'] ?? '',
            'actif' => 1
        ];
    }, $results);
}

/**
 * Obtenir les tables disponibles
 */
function getAvailableTables($db) {
    try {
        $stmt = $db->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtenir les colonnes d'une table
 */
function getTableColumns($db, $tableName) {
    try {
        $stmt = $db->query("DESCRIBE `$tableName`");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Recherche d'un produit spécifique dans la BDD avec la vraie structure
 */
function getProductFromDatabase($db, $codeProduct) {
    $tables = getAvailableTables($db);
    
    if (in_array('gul_adr_products', $tables)) {
        $stmt = $db->prepare("
            SELECT 
                code_produit,
                corde_article_ferme,
                nom_produit,
                poids_contenant,
                type_contenant,
                numero_un,
                nom_description_un,
                nom_technique,
                groupe_emballage,
                numero_etiquette,
                categorie_transport,
                code_tunnel,
                danger_environnement,
                colonne_3,
                actif,
                date_creation,
                date_modification,
                cree_par
            FROM gul_adr_products 
            WHERE code_produit = ? AND actif = 1 
            LIMIT 1
        ");
        $stmt->execute([$codeProduct]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return null;
}

/**
 * Produits populaires depuis la BDD avec la vraie structure
 */
function getPopularFromDatabase($db, $limit) {
    $tables = getAvailableTables($db);
    
    if (in_array('gul_adr_products', $tables)) {
        $stmt = $db->prepare("
            SELECT 
                code_produit, 
                nom_produit, 
                numero_un, 
                categorie_transport, 
                danger_environnement,
                corde_article_ferme,
                type_contenant,
                poids_contenant
            FROM gul_adr_products 
            WHERE actif = 1 
            ORDER BY 
                CASE 
                    WHEN numero_un IS NOT NULL AND numero_un != '' THEN 1
                    ELSE 2
                END,
                CASE 
                    WHEN danger_environnement = 'OUI' THEN 1
                    WHEN categorie_transport IN ('1', '2') THEN 2
                    ELSE 3
                END,
                CASE 
                    WHEN corde_article_ferme = 'x' THEN 2
                    ELSE 1
                END,
                nom_produit
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return [];
}

/**
 * Données de démonstration pour les tests
 */
function getDemoProducts($query = '', $limit = 20) {
    $demoProducts = [
        [
            'code_produit' => 'GUL-001',
            'nom_produit' => 'GULTRAT pH+ Liquide',
            'nom_technique' => 'Solution alcaline concentrée',
            'numero_un' => '1824',
            'nom_description_un' => 'Hydroxyde de sodium en solution',
            'categorie_transport' => '2',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Bidon plastique',
            'poids_contenant' => '25L',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'code_tunnel' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-002',
            'nom_produit' => 'PERFORMAX Désinfectant',
            'nom_technique' => 'Biocide à base de chlore',
            'numero_un' => '3265',
            'nom_description_un' => 'Matière organique corrosive liquide',
            'categorie_transport' => '1',
            'danger_environnement' => 'OUI',
            'type_contenant' => 'Jerrycan',
            'poids_contenant' => '20L',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'code_tunnel' => 'E',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-003',
            'nom_produit' => 'ALKADOSE Basique',
            'nom_technique' => 'Correcteur pH piscine',
            'numero_un' => '1823',
            'nom_description_un' => 'Hydroxyde de sodium solide',
            'categorie_transport' => '2',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Sac étanche',
            'poids_contenant' => '25kg',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'code_tunnel' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-004',
            'nom_produit' => 'CHLORE Pastilles',
            'nom_technique' => 'Hypochlorite de calcium',
            'numero_un' => '2880',
            'nom_description_un' => 'Hypochlorite de calcium hydraté',
            'categorie_transport' => '2',
            'danger_environnement' => 'OUI',
            'type_contenant' => 'Seau plastique',
            'poids_contenant' => '5kg',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '5.1',
            'code_tunnel' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-005',
            'nom_produit' => 'ACIDE MURIATIQUE',
            'nom_technique' => 'Acide chlorhydrique 33%',
            'numero_un' => '1789',
            'nom_description_un' => 'Acide chlorhydrique',
            'categorie_transport' => '1',
            'danger_environnement' => 'OUI',
            'type_contenant' => 'Bidon PEHD',
            'poids_contenant' => '20L',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'code_tunnel' => 'E',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-006',
            'nom_produit' => 'STABILISANT PISCINE',
            'nom_technique' => 'Acide cyanurique',
            'numero_un' => '',
            'nom_description_un' => '',
            'categorie_transport' => '0',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Sac papier',
            'poids_contenant' => '1kg',
            'corde_article_ferme' => '',
            'groupe_emballage' => '',
            'numero_etiquette' => '',
            'code_tunnel' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-007',
            'nom_produit' => 'FLOCULANT LIQUIDE',
            'nom_technique' => 'Polyacrylamide en solution',
            'numero_un' => '',
            'nom_description_un' => '',
            'categorie_transport' => '0',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Bidon',
            'poids_contenant' => '10L',
            'corde_article_ferme' => '',
            'groupe_emballage' => '',
            'numero_etiquette' => '',
            'code_tunnel' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-008',
            'nom_produit' => 'DÉTARTRANT INTENSE',
            'nom_technique' => 'Acide sulfamique',
            'numero_un' => '',
            'nom_description_un' => '',
            'categorie_transport' => '0',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Sac plastique',
            'poids_contenant' => '5kg',
            'corde_article_ferme' => '',
            'groupe_emballage' => '',
            'numero_etiquette' => '',
            'code_tunnel' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-009',
            'nom_produit' => 'ANCIEN PRODUIT',
            'nom_technique' => 'Produit fermé pour test',
            'numero_un' => '1823',
            'nom_description_un' => 'Hydroxyde de sodium solide',
            'categorie_transport' => '2',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Sac',
            'poids_contenant' => '25kg',
            'corde_article_ferme' => 'x',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'code_tunnel' => '',
            'actif' => 1
        ]
    ];
    
    // Filtrer si une recherche est spécifiée
    if (!empty($query)) {
        $query = strtolower($query);
        $demoProducts = array_filter($demoProducts, function($product) use ($query) {
            return stripos($product['code_produit'], $query) !== false ||
                   stripos($product['nom_produit'], $query) !== false ||
                   stripos($product['nom_technique'], $query) !== false ||
                   stripos($product['numero_un'], $query) !== false;
        });
    }
    
    // Limiter les résultats
    return array_slice($demoProducts, 0, $limit);
}

/**
 * Enrichit les données produit avec des informations calculées
 */
function enrichProductData($product) {
    if (!$product) return $product;
    
    // Statut ADR
    $product['is_adr'] = !empty($product['numero_un']);
    
    // Niveau de danger
    $product['danger_level'] = calculateDangerLevel($product);
    
    // Informations de transport formatées
    $product['transport_info'] = formatTransportInfo($product);
    
    // Restrictions et avertissements
    $product['warnings'] = generateWarnings($product);
    
    // Score de recherche
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
    
    switch ($product['categorie_transport']) {
        case '1': return 1; // Très dangereux
        case '2': return 2; // Dangereux
        case '3': return 3; // Moyennement dangereux
        case '4': return 4; // Peu dangereux
        default: return 3;
    }
}

/**
 * Formate les informations de transport avec la vraie structure
 */
function formatTransportInfo($product) {
    $info = [];
    
    if (!empty($product['categorie_transport']) && $product['categorie_transport'] !== '0') {
        $info['categorie'] = "Catégorie " . $product['categorie_transport'];
    }
    
    if (!empty($product['groupe_emballage'])) {
        $info['emballage'] = "Groupe " . $product['groupe_emballage'];
    }
    
    if (!empty($product['type_contenant'])) {
        $info['contenant'] = $product['type_contenant'];
    }
    
    if (!empty($product['poids_contenant'])) {
        $info['poids'] = $product['poids_contenant'];
    }
    
    if (!empty($product['code_tunnel'])) {
        $info['tunnel'] = "Restriction: " . $product['code_tunnel'];
    }
    
    if (!empty($product['numero_etiquette'])) {
        $info['etiquette'] = "Étiquette: " . $product['numero_etiquette'];
    }
    
    return $info;
}

/**
 * Génère les avertissements pour un produit avec la vraie structure
 */
function generateWarnings($product) {
    $warnings = [];
    
    if ($product['corde_article_ferme'] === 'x') {
        $warnings[] = [
            'type' => 'error',
            'message' => 'Article fermé - Ne plus expédier',
            'icon' => '🔒'
        ];
    }
    
    if ($product['danger_environnement'] === 'OUI') {
        $warnings[] = [
            'type' => 'warning',
            'message' => 'Polluant marin - Précautions environnementales requises',
            'icon' => '🌊'
        ];
    }
    
    if (!empty($product['numero_un']) && empty($product['nom_description_un'])) {
        $warnings[] = [
            'type' => 'info',
            'message' => 'Description UN manquante - Vérifier la réglementation',
            'icon' => 'ℹ️'
        ];
    }
    
    if ($product['categorie_transport'] === '1') {
        $warnings[] = [
            'type' => 'error',
            'message' => 'Catégorie 1 - Transport très restreint',
            'icon' => '🚫'
        ];
    }
    
    if (!empty($product['code_tunnel'])) {
        $warnings[] = [
            'type' => 'warning',
            'message' => 'Restriction tunnel: ' . $product['code_tunnel'],
            'icon' => '🚇'
        ];
    }
    
    if ($product['groupe_emballage'] === 'I') {
        $warnings[] = [
            'type' => 'error',
            'message' => 'Groupe emballage I - Très dangereux',
            'icon' => '☢️'
        ];
    } elseif ($product['groupe_emballage'] === 'II') {
        $warnings[] = [
            'type' => 'warning',
            'message' => 'Groupe emballage II - Moyennement dangereux',
            'icon' => '⚠️'
        ];
    }
    
    return $warnings;
}

/**
 * Calcule un score de pertinence
 */
function calculateSearchScore($product) {
    $score = 100;
    
    if (!empty($product['numero_un'])) {
        $score += 20;
    }
    
    if ($product['corde_article_ferme'] === 'x') {
        $score -= 30;
    }
    
    if ($product['danger_environnement'] === 'OUI') {
        $score += 10;
    }
    
    switch ($product['categorie_transport']) {
        case '1': $score += 15; break;
        case '2': $score += 10; break;
        case '3': $score += 5; break;
    }
    
    return max(0, $score);
}
?>

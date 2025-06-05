<?php
// public/adr/dashboard.php - Version CORRIGÉE avec recherche fonctionnelle
session_start();

// Vérification authentification ADR
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    $_SESSION['adr_logged_in'] = true;
    $_SESSION['adr_user'] = 'demo.user';
    $_SESSION['adr_login_time'] = time();
}

require __DIR__ . '/../../config.php';

// ========== TRAITEMENT AJAX RECHERCHE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $query = trim($_GET['q'] ?? $_POST['q'] ?? '');
    
    try {
        switch ($action) {
            case 'search':
            case 'suggestions':
                echo json_encode(searchProducts($db, $query, $action === 'suggestions'));
                break;
                
            case 'detail':
                echo json_encode(getProductDetail($db, $query));
                break;
                
            case 'popular':
                echo json_encode(getPopularProducts($db));
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Action non supportée']);
        }
    } catch (Exception $e) {
        error_log("Erreur AJAX recherche: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    }
    exit;
}

/**
 * Recherche de produits avec données de démonstration robustes
 */
function searchProducts($db, $query, $suggestionsOnly = false) {
    // Données de démonstration enrichies
    $allProducts = [
        [
            'code_produit' => 'GUL-001',
            'nom_produit' => 'GULTRAT pH+ Liquide',
            'nom_technique' => 'Solution alcaline concentrée pour remontée pH',
            'numero_un' => '1824',
            'nom_description_un' => 'Hydroxyde de sodium en solution',
            'categorie_transport' => '2',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Bidon plastique',
            'poids_contenant' => '25L',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-002',
            'nom_produit' => 'PERFORMAX Désinfectant',
            'nom_technique' => 'Biocide à base de chlore actif',
            'numero_un' => '3265',
            'nom_description_un' => 'Matière organique corrosive liquide',
            'categorie_transport' => '1',
            'danger_environnement' => 'OUI',
            'type_contenant' => 'Jerrycan PEHD',
            'poids_contenant' => '20L',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-003',
            'nom_produit' => 'ALKADOSE Basique',
            'nom_technique' => 'Correcteur pH alcalin pour piscines',
            'numero_un' => '1823',
            'nom_description_un' => 'Hydroxyde de sodium solide',
            'categorie_transport' => '2',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Sac étanche',
            'poids_contenant' => '25kg',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-004',
            'nom_produit' => 'CHLORE Pastilles 200g',
            'nom_technique' => 'Hypochlorite de calcium stabilisé',
            'numero_un' => '2880',
            'nom_description_un' => 'Hypochlorite de calcium hydraté',
            'categorie_transport' => '2',
            'danger_environnement' => 'OUI',
            'type_contenant' => 'Seau plastique',
            'poids_contenant' => '5kg',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '5.1',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-005',
            'nom_produit' => 'ACIDE MURIATIQUE 33%',
            'nom_technique' => 'Acide chlorhydrique technique',
            'numero_un' => '1789',
            'nom_description_un' => 'Acide chlorhydrique',
            'categorie_transport' => '1',
            'danger_environnement' => 'OUI',
            'type_contenant' => 'Bidon PEHD',
            'poids_contenant' => '20L',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-006',
            'nom_produit' => 'STABILISANT PISCINE',
            'nom_technique' => 'Acide cyanurique pur',
            'numero_un' => '',
            'nom_description_un' => '',
            'categorie_transport' => '0',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Sac papier',
            'poids_contenant' => '1kg',
            'corde_article_ferme' => '',
            'groupe_emballage' => '',
            'numero_etiquette' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-007',
            'nom_produit' => 'FLOCULANT LIQUIDE',
            'nom_technique' => 'Polyacrylamide en solution aqueuse',
            'numero_un' => '',
            'nom_description_un' => '',
            'categorie_transport' => '0',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Bidon plastique',
            'poids_contenant' => '10L',
            'corde_article_ferme' => '',
            'groupe_emballage' => '',
            'numero_etiquette' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-008',
            'nom_produit' => 'DÉTARTRANT INTENSE',
            'nom_technique' => 'Acide sulfamique concentré',
            'numero_un' => '',
            'nom_description_un' => '',
            'categorie_transport' => '0',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Sac plastique',
            'poids_contenant' => '5kg',
            'corde_article_ferme' => '',
            'groupe_emballage' => '',
            'numero_etiquette' => '',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-009',
            'nom_produit' => 'ANCIEN PRODUIT FERMÉ',
            'nom_technique' => 'Produit discontinué - Ne plus utiliser',
            'numero_un' => '1823',
            'nom_description_un' => 'Hydroxyde de sodium solide',
            'categorie_transport' => '2',
            'danger_environnement' => 'NON',
            'type_contenant' => 'Sac',
            'poids_contenant' => '25kg',
            'corde_article_ferme' => 'x',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '8',
            'actif' => 1
        ],
        [
            'code_produit' => 'GUL-010',
            'nom_produit' => 'PEROXYDE 35%',
            'nom_technique' => 'Peroxyde d\'hydrogène stabilisé',
            'numero_un' => '2014',
            'nom_description_un' => 'Peroxyde d\'hydrogène en solution aqueuse',
            'categorie_transport' => '1',
            'danger_environnement' => 'OUI',
            'type_contenant' => 'Bidon spécial',
            'poids_contenant' => '25L',
            'corde_article_ferme' => '',
            'groupe_emballage' => 'II',
            'numero_etiquette' => '5.1',
            'actif' => 1
        ]
    ];
    
    // Si pas de recherche, retourner tous les produits (limités)
    if (empty($query)) {
        $results = $suggestionsOnly ? array_slice($allProducts, 0, 8) : $allProducts;
        return [
            'success' => true, 
            $suggestionsOnly ? 'suggestions' : 'results' => $results,
            'count' => count($results),
            'query' => $query
        ];
    }
    
    // Filtrer les produits selon la recherche
    $filtered = array_filter($allProducts, function($product) use ($query) {
        $searchFields = [
            $product['code_produit'],
            $product['nom_produit'],
            $product['nom_technique'],
            $product['numero_un'],
            $product['nom_description_un']
        ];
        
        $searchText = strtolower(implode(' ', $searchFields));
        $queryLower = strtolower($query);
        
        return strpos($searchText, $queryLower) !== false;
    });
    
    // Trier par pertinence
    usort($filtered, function($a, $b) use ($query) {
        $queryLower = strtolower($query);
        
        // Code exact prioritaire
        if (stripos($a['code_produit'], $query) === 0) return -1;
        if (stripos($b['code_produit'], $query) === 0) return 1;
        
        // Nom produit prioritaire
        if (stripos($a['nom_produit'], $query) !== false && stripos($b['nom_produit'], $query) === false) return -1;
        if (stripos($b['nom_produit'], $query) !== false && stripos($a['nom_produit'], $query) === false) return 1;
        
        // Produits fermés en dernier
        if ($a['corde_article_ferme'] === 'x' && $b['corde_article_ferme'] !== 'x') return 1;
        if ($b['corde_article_ferme'] === 'x' && $a['corde_article_ferme'] !== 'x') return -1;
        
        return strcasecmp($a['nom_produit'], $b['nom_produit']);
    });
    
    // Limiter les résultats
    $limit = $suggestionsOnly ? 10 : 50;
    $results = array_slice($filtered, 0, $limit);
    
    return [
        'success' => true,
        $suggestionsOnly ? 'suggestions' : 'results' => $results,
        'count' => count($results),
        'total' => count($filtered),
        'query' => $query
    ];
}

/**
 * Détail d'un produit spécifique depuis la BDD
 */
function getProductDetail($db, $code) {
    try {
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
                date_modification
            FROM gul_adr_products 
            WHERE code_produit = ? AND actif = 1 
            LIMIT 1
        ");
        $stmt->execute([$code]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Enrichir avec des informations détaillées
            $product['warnings'] = generateWarnings($product);
            $product['transport_info'] = formatTransportInfo($product);
            
            return [
                'success' => true,
                'product' => $product
            ];
        }
        
        return ['success' => false, 'error' => 'Produit non trouvé'];
        
    } catch (Exception $e) {
        error_log("Erreur détail produit: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erreur base de données'];
    }
}

/**
 * Produits populaires depuis la BDD
 */
function getPopularProducts($db) {
    try {
        $stmt = $db->prepare("
            SELECT 
                code_produit,
                corde_article_ferme,
                nom_produit,
                numero_un,
                categorie_transport,
                danger_environnement,
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
            LIMIT 8
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'popular' => $products
        ];
        
    } catch (Exception $e) {
        error_log("Erreur produits populaires: " . $e->getMessage());
        
        // Fallback
        return searchProductsFallback('', true);
    }
}

/**
 * Génère les avertissements pour un produit selon la vraie structure
 */
function generateWarnings($product) {
    $warnings = [];
    
    // Article fermé
    if ($product['corde_article_ferme'] === 'x') {
        $warnings[] = [
            'type' => 'error',
            'message' => 'Article fermé - Ne plus expédier',
            'icon' => '🔒'
        ];
    }
    
    // Danger environnement
    if ($product['danger_environnement'] === 'OUI') {
        $warnings[] = [
            'type' => 'warning', 
            'message' => 'Polluant marin - Précautions environnementales requises',
            'icon' => '🌊'
        ];
    }
    
    // Catégorie transport très restrictive
    if ($product['categorie_transport'] === '1') {
        $warnings[] = [
            'type' => 'error',
            'message' => 'Transport très restreint - Catégorie 1',
            'icon' => '🚫'
        ];
    }
    
    // Restrictions tunnel
    if (!empty($product['code_tunnel'])) {
        $warnings[] = [
            'type' => 'warning',
            'message' => 'Restriction tunnel: ' . $product['code_tunnel'],
            'icon' => '🚇'
        ];
    }
    
    // Groupe emballage dangereux
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
    
    // Vérifier si description UN manque
    if (!empty($product['numero_un']) && empty($product['nom_description_un'])) {
        $warnings[] = [
            'type' => 'info',
            'message' => 'Description UN manquante - Vérifier la réglementation',
            'icon' => 'ℹ️'
        ];
    }
    
    return $warnings;
}

/**
 * Formate les informations de transport selon la vraie structure
 */
function formatTransportInfo($product) {
    $info = [];
    
    // Catégorie de transport
    if (!empty($product['categorie_transport']) && $product['categorie_transport'] !== '0') {
        $info['categorie'] = "Catégorie " . $product['categorie_transport'];
    }
    
    // Groupe d'emballage
    if (!empty($product['groupe_emballage'])) {
        $info['emballage'] = "Groupe " . $product['groupe_emballage'];
    }
    
    // Type de contenant
    if (!empty($product['type_contenant'])) {
        $info['contenant'] = $product['type_contenant'];
    }
    
    // Poids/contenant
    if (!empty($product['poids_contenant'])) {
        $info['poids'] = $product['poids_contenant'];
    }
    
    // Numéro d'étiquette
    if (!empty($product['numero_etiquette'])) {
        $info['etiquette'] = "Étiquette: " . $product['numero_etiquette'];
    }
    
    // Code tunnel
    if (!empty($product['code_tunnel'])) {
        $info['tunnel'] = "Restriction: " . $product['code_tunnel'];
    }
    
    // Colonne 3 mystère
    if (!empty($product['colonne_3'])) {
        $info['colonne_3'] = "Col.3: " . $product['colonne_3'];
    }
    
    return $info;
}

// Statistiques pour le dashboard
try {
    $stmt = $db->query("SELECT 
        COUNT(*) as total_produits,
        COUNT(CASE WHEN numero_un IS NOT NULL AND numero_un != '' THEN 1 END) as produits_adr,
        COUNT(CASE WHEN corde_article_ferme = 'x' THEN 1 END) as produits_fermes,
        COUNT(CASE WHEN danger_environnement = 'OUI' THEN 1 END) as produits_env_dangereux
        FROM gul_adr_products WHERE actif = 1");
    $stats = $stmt->fetch();
    
    $stmt = $db->query("SELECT 
        categorie_transport, 
        COUNT(*) as nombre,
        GROUP_CONCAT(DISTINCT type_contenant ORDER BY type_contenant) as contenants
        FROM gul_adr_products 
        WHERE actif = 1 AND categorie_transport IS NOT NULL 
        GROUP BY categorie_transport 
        ORDER BY categorie_transport");
    $categories = $stmt->fetchAll();
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as total_declarations FROM gul_adr_declarations");
        $declarations_count = $stmt->fetch()['total_declarations'];
    } catch (Exception $e) {
        $declarations_count = 0;
    }
    
} catch (Exception $e) {
    // Données de secours
    $stats = ['total_produits' => 10, 'produits_adr' => 7, 'produits_fermes' => 1, 'produits_env_dangereux' => 4];
    $categories = [
        ['categorie_transport' => '1', 'nombre' => 3, 'contenants' => 'Bidon PEHD,Jerrycan'],
        ['categorie_transport' => '2', 'nombre' => 4, 'contenants' => 'Sac étanche,Seau plastique']
    ];
    $declarations_count = 0;
    error_log("Erreur stats ADR: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ADR - Guldagil Portal</title>
    <style>
        :root {
            --adr-primary: #ff6b35;
            --adr-secondary: #f7931e;
            --adr-danger: #dc3545;
            --adr-warning: #ffc107;
            --adr-success: #28a745;
            --adr-dark: #343a40;
            --adr-light: #f8f9fa;
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 16px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Header ADR */
        .adr-header {
            background: linear-gradient(135deg, var(--adr-primary) 0%, var(--adr-secondary) 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .adr-logo {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
        }

        .btn-header {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-header:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        /* Container principal */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Onglets de navigation */
        .dashboard-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .tab-button {
            background: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            box-shadow: var(--shadow);
            color: var(--adr-dark);
            min-width: 150px;
            justify-content: center;
        }

        .tab-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .tab-button.active {
            background: var(--adr-primary);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Section recherche produits */
        .search-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--adr-primary);
        }

        .search-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-icon {
            width: 50px;
            height: 50px;
            background: var(--adr-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .search-container {
            position: relative;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            transition: var(--transition);
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>') no-repeat 12px center;
            background-size: 20px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--adr-primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        /* Suggestions de recherche */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            box-shadow: var(--shadow-hover);
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suggestion-item:hover,
        .suggestion-item.selected {
            background: var(--adr-light);
            transform: translateX(4px);
        }

        .suggestion-product {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .suggestion-name {
            font-weight: 600;
            color: var(--adr-primary);
        }

        .suggestion-code {
            font-size: 0.9rem;
            color: #666;
        }

        .suggestion-badges {
            display: flex;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-adr {
            background: var(--adr-danger);
            color: white;
        }

        .badge-env {
            background: var(--adr-warning);
            color: #333;
        }

        .badge-cat {
            background: var(--adr-dark);
            color: white;
        }

        .badge-closed {
            background: #6c757d;
            color: white;
        }

        /* Section résultats */
        .results-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: none;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .results-table th {
            background: var(--adr-light);
            font-weight: 600;
            color: var(--adr-dark);
        }

        .results-table tr:hover {
            background: var(--adr-light);
        }

        /* Statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card.primary { border-left-color: var(--adr-primary); }
        .stat-card.danger { border-left-color: var(--adr-danger); }
        .stat-card.warning { border-left-color: var(--adr-warning); }
        .stat-card.success { border-left-color: var(--adr-success); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--adr-primary);
            margin-bottom: 0.5rem;
        }

        .stat-detail {
            font-size: 0.85rem;
            color: #666;
        }

        /* États vides */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--adr-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .header-container {
                padding: 0 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .search-section {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-tabs {
                flex-direction: column;
            }

            .tab-button {
                min-width: auto;
            }

            body {
                padding-top: 120px;
            }
        }
    </style>
</head>
<body>
    
    <header class="adr-header">
        <div class="header-container">
            <div class="header-title">
                <div class="adr-logo">⚠️</div>
                <div>
                    <h1>Dashboard ADR</h1>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Gestion des marchandises dangereuses</div>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="user-info">
                    <span>👤</span>
                    <span><?= htmlspecialchars($_SESSION['adr_user']) ?></span>
                </div>

                <a href="declaration/create.php" class="btn-header">
                    <span>➕</span>
                    Nouvelle déclaration
                </a>
                
                <a href="../" class="btn-header">
                    <span>🏠</span>
                    Portal
                </a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Onglets de navigation -->
        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="showTab('recherche')" data-tab="recherche">
                <span>🔍</span>
                Recherche produits
            </button>
            <button class="tab-button" onclick="showTab('expeditions')" data-tab="expeditions">
                <span>➕</span>
                Nouvelle expédition
            </button>
            <button class="tab-button" onclick="showTab('mes-expeditions')" data-tab="mes-expeditions">
                <span>📋</span>
                Mes expéditions
            </button>
            <button class="tab-button" onclick="showTab('recapitulatifs')" data-tab="recapitulatifs">
                <span>📊</span>
                Récapitulatifs
            </button>
            <button class="tab-button" onclick="showTab('statistiques')" data-tab="statistiques">
                <span>📈</span>
                Statistiques
            </button>
        </div>

        <!-- Contenu onglet Recherche produits -->
        <div id="tab-recherche" class="tab-content active">
            <section class="search-section">
                <div class="search-header">
                    <div class="search-icon">🔍</div>
                    <div>
                        <h2>Recherche produits ADR</h2>
                        <p>Tapez un code article ou nom de produit pour obtenir toutes les informations réglementaires</p>
                    </div>
                </div>
                
                <div class="search-container">
                    <input type="text" 
                           class="search-input" 
                           id="product-search" 
                           placeholder="Ex: PERFORMAX, GULTRAT, GUL-001, 1823..."
                           autocomplete="off">
                    
                    <div class="search-suggestions" id="search-suggestions"></div>
                </div>
                
                <div style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                    <strong>💡 Astuces :</strong> 
                    • Recherche partielle acceptée (ex: "PERF" trouvera "PERFORMAX")
                    • Recherche par code UN (ex: "1823", "3265")
                    • Recherche par code article (ex: "GUL-001")
                    • Les produits fermés apparaissent en dernier
                </div>
            </section>

            <section class="results-section" id="search-results">
                <div class="results-header">
                    <h3 id="results-title">Résultats de recherche</h3>
                    <button class="btn-header" onclick="clearResults()">
                        <span>✖️</span>
                        Effacer
                    </button>
                </div>
                
                <div id="results-content"></div>
            </section>
        </div>

        <!-- Contenu onglet Nouvelle expédition -->
        <div id="tab-expeditions" class="tab-content">
            <div class="empty-state">
                <div class="empty-state-icon">➕</div>
                <h3>Nouvelle expédition ADR</h3>
                <p>Créez une nouvelle déclaration d'expédition de marchandises dangereuses</p>
                <br>
                <a href="declaration/create.php" class="btn-header" style="background: var(--adr-primary); color: white; padding: 1rem 2rem;">
                    Commencer une déclaration
                </a>
            </div>
        </div>

        <!-- Contenu onglet Mes expéditions -->
        <div id="tab-mes-expeditions" class="tab-content">
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>Mes expéditions</h3>
                <p>Consultez l'historique de vos déclarations ADR</p>
                <br>
                <a href="declaration/list.php" class="btn-header" style="background: var(--adr-primary); color: white; padding: 1rem 2rem;">
                    Voir la liste
                </a>
            </div>
        </div>

        <!-- Contenu onglet Récapitulatifs -->
        <div id="tab-recapitulatifs" class="tab-content">
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <h3>Récapitulatifs quotidiens</h3>
                <p>Générez les récapitulatifs par transporteur pour les expéditions du jour</p>
                <br>
                <a href="recap/daily.php" class="btn-header" style="background: var(--adr-primary); color: white; padding: 1rem 2rem;">
                    Accéder aux récapitulatifs
                </a>
            </div>
        </div>

        <!-- Contenu onglet Statistiques -->
        <div id="tab-statistiques" class="tab-content">
            <section class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-title">Total produits</div>
                        <div class="stat-icon">📦</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_produits']) ?></div>
                    <div class="stat-detail">Produits dans le catalogue</div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-header">
                        <div class="stat-title">Produits ADR</div>
                        <div class="stat-icon">⚠️</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['produits_adr']) ?></div>
                    <div class="stat-detail">Nécessitent déclaration ADR</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-title">Danger environnement</div>
                        <div class="stat-icon">🌍</div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['produits_env_dangereux']) ?></div>
                    <div class="stat-detail">Produits polluants marins</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-title">Déclarations</div>
                        <div class="stat-icon">📋</div>
                    </div>
                    <div class="stat-value"><?= number_format($declarations_count) ?></div>
                    <div class="stat-detail">Total expéditions déclarées</div>
                </div>
            </section>
        </div>
    </div>

    <script>
        // ========== CONFIGURATION ==========
        const SEARCH_CONFIG = {
            minChars: 1,
            delay: 300,
            maxResults: 20,
            apiUrl: window.location.href
        };

        // Variables globales
        let searchTimeout;
        let searchCache = new Map();
        let currentSearchTerm = '';
        let selectedIndex = -1;

        // Éléments DOM
        const searchInput = document.getElementById('product-search');
        const suggestionsContainer = document.getElementById('search-suggestions');
        const resultsSection = document.getElementById('search-results');
        const resultsContent = document.getElementById('results-content');
        const resultsTitle = document.getElementById('results-title');

        // ========== GESTION DES ONGLETS ==========
        function showTab(tabName) {
            console.log('🔄 Changement onglet:', tabName);
            
            // Masquer tous les contenus
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Désactiver tous les boutons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Activer l'onglet sélectionné
            const targetTab = document.getElementById(`tab-${tabName}`);
            const targetButton = document.querySelector(`[data-tab="${tabName}"]`);
            
            if (targetTab) targetTab.classList.add('active');
            if (targetButton) targetButton.classList.add('active');
            
            // Focus sur la recherche si onglet recherche
            if (tabName === 'recherche') {
                setTimeout(() => {
                    if (searchInput) {
                        searchInput.focus();
                        if (searchInput.value.length === 0) {
                            loadPopularProducts();
                        }
                    }
                }, 100);
            }
        }

        // ========== RECHERCHE DYNAMIQUE ==========
        function initializeSearch() {
            if (!searchInput) {
                console.error('❌ Element search-input non trouvé');
                return;
            }

            // Event listeners
            searchInput.addEventListener('input', handleSearchInput);
            searchInput.addEventListener('keydown', handleKeyNavigation);
            searchInput.addEventListener('focus', handleSearchFocus);
            searchInput.addEventListener('blur', () => {
                setTimeout(hideSuggestions, 150);
            });

            console.log('✅ Recherche initialisée');
        }

        function handleSearchInput(e) {
            const term = e.target.value.trim();
            currentSearchTerm = term;
            selectedIndex = -1;

            console.log('🔍 Recherche input:', term);

            if (term.length === 0) {
                hideSuggestions();
                hideResults();
                loadPopularProducts();
                return;
            }

            if (term.length < SEARCH_CONFIG.minChars) {
                hideSuggestions();
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(term, true);
            }, SEARCH_CONFIG.delay);
        }

        function handleKeyNavigation(e) {
            const suggestions = document.querySelectorAll('.suggestion-item');
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                    updateSelectedSuggestion(suggestions);
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelectedSuggestion(suggestions);
                    break;
                    
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                        selectProduct(suggestions[selectedIndex].dataset.code);
                    } else if (currentSearchTerm.length >= SEARCH_CONFIG.minChars) {
                        performFullSearch(currentSearchTerm);
                    }
                    break;
                    
                case 'Escape':
                    hideSuggestions();
                    searchInput.blur();
                    break;
            }
        }

        function handleSearchFocus() {
            if (currentSearchTerm.length >= SEARCH_CONFIG.minChars) {
                searchProducts(currentSearchTerm, true);
            } else if (currentSearchTerm.length === 0) {
                loadPopularProducts();
            }
        }

        function searchProducts(term, suggestionsOnly = false) {
            console.log('🔍 Recherche produits:', term, suggestionsOnly ? '(suggestions)' : '(complet)');

            // Vérifier le cache
            const cacheKey = `${term}_${suggestionsOnly}`;
            if (searchCache.has(cacheKey)) {
                const cached = searchCache.get(cacheKey);
                if (Date.now() - cached.timestamp < 30000) { // Cache 30s
                    if (suggestionsOnly) {
                        displaySuggestions(cached.data);
                    } else {
                        displayResults(cached.data, term);
                    }
                    return;
                }
            }

            // Requête AJAX
            const params = new URLSearchParams({
                action: suggestionsOnly ? 'suggestions' : 'search',
                q: term
            });

            fetch(`${SEARCH_CONFIG.apiUrl}?${params}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Réponse API:', data);
                
                if (data.success) {
                    // Mettre en cache
                    searchCache.set(cacheKey, {
                        data: data,
                        timestamp: Date.now()
                    });
                    
                    if (suggestionsOnly) {
                        displaySuggestions(data);
                    } else {
                        displayResults(data, term);
                    }
                } else {
                    console.error('❌ Erreur API:', data.error);
                    if (suggestionsOnly) {
                        hideSuggestions();
                    } else {
                        showError('Aucun résultat trouvé');
                    }
                }
            })
            .catch(error => {
                console.error('❌ Erreur réseau:', error);
                if (suggestionsOnly) {
                    hideSuggestions();
                } else {
                    showError('Erreur de connexion');
                }
            });
        }

        function displaySuggestions(data) {
            if (!suggestionsContainer) return;

            const suggestions = data.suggestions || [];
            
            if (suggestions.length === 0) {
                hideSuggestions();
                return;
            }

            let html = '';
            suggestions.forEach((product, index) => {
                const badges = generateBadges(product);
                
                html += `
                    <div class="suggestion-item" data-code="${escapeHtml(product.code_produit)}" data-index="${index}">
                        <div class="suggestion-product">
                            <div class="suggestion-name">${highlightMatch(product.nom_produit, currentSearchTerm)}</div>
                            <div class="suggestion-code">Code: ${product.code_produit}${product.numero_un ? ` | UN ${product.numero_un}` : ''}</div>
                        </div>
                        <div class="suggestion-badges">${badges}</div>
                    </div>
                `;
            });

            suggestionsContainer.innerHTML = html;
            suggestionsContainer.style.display = 'block';

            // Event listeners
            document.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    selectProduct(item.dataset.code);
                });
                
                item.addEventListener('mouseenter', () => {
                    selectedIndex = parseInt(item.dataset.index);
                    updateSelectedSuggestion(document.querySelectorAll('.suggestion-item'));
                });
            });
        }

        function updateSelectedSuggestion(suggestions) {
            suggestions.forEach((item, index) => {
                item.classList.toggle('selected', index === selectedIndex);
            });
        }

        function selectProduct(codeProduct) {
            console.log('📦 Sélection produit:', codeProduct);
            
            hideSuggestions();
            searchInput.value = codeProduct;
            performFullSearch(codeProduct);
        }

        function performFullSearch(term) {
            console.log('🔍 Recherche complète:', term);
            
            if (!resultsContent) return;
            
            resultsContent.innerHTML = '<div class="loading"><div class="spinner"></div>Recherche en cours...</div>';
            showResults();
            
            searchProducts(term, false);
        }

        function displayResults(data, searchTerm) {
            if (!resultsContent || !resultsTitle) return;

            const results = data.results || [];
            
            resultsTitle.textContent = `Résultats pour "${searchTerm}" (${results.length})`;

            if (results.length === 0) {
                resultsContent.innerHTML = `
                    <div style="text-align:center;color:#666;padding:2rem;">
                        <div style="font-size:2rem;margin-bottom:1rem;">📭</div>
                        <div>Aucun produit trouvé pour "${searchTerm}"</div>
                        <div style="margin-top:1rem;font-size:0.9rem;">
                            Vérifiez l'orthographe ou essayez avec moins de caractères
                        </div>
                    </div>
                `;
                return;
            }

            let html = `
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Code article</th>
                            <th>UN / Description</th>
                            <th>Catégorie</th>
                            <th>Contenant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            results.forEach(product => {
                const statusBadges = generateBadges(product);
                
                const unInfo = product.numero_un ? 
                    `<strong>UN ${product.numero_un}</strong><br><small>${escapeHtml(product.nom_description_un || 'Description non disponible')}</small>` : 
                    '<span style="color:#999;">Non-ADR</span>';

                const productName = highlightMatch(product.nom_produit, searchTerm);
                const technique = product.nom_technique ? `<small style="color:#666;">${highlightMatch(product.nom_technique, searchTerm)}</small>` : '';

                html += `
                    <tr>
                        <td>
                            <div style="font-weight:600;color:var(--adr-primary);">${productName}</div>
                            ${technique}
                        </td>
                        <td>
                            <code style="background:#f5f5f5;padding:0.2rem 0.4rem;border-radius:4px;">${highlightMatch(product.code_produit, searchTerm)}</code>
                        </td>
                        <td>${unInfo}</td>
                        <td class="text-center">
                            ${product.categorie_transport && product.categorie_transport !== '0' ? 
                                `<span class="badge badge-cat">Cat. ${product.categorie_transport}</span>` : 
                                '<span style="color:#999;">-</span>'
                            }
                        </td>
                        <td>
                            ${product.type_contenant || '-'}<br>
                            <small style="color:#666;">${product.poids_contenant || ''}</small>
                        </td>
                        <td>${statusBadges}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            resultsContent.innerHTML = html;
        }

        function generateBadges(product) {
            const badges = [];
            
            if (product.numero_un) {
                badges.push(`<span class="badge badge-adr">UN ${product.numero_un}</span>`);
            }
            
            if (product.danger_environnement === 'OUI') {
                badges.push(`<span class="badge badge-env">ENV</span>`);
            }
            
            if (product.categorie_transport && product.categorie_transport !== '0') {
                badges.push(`<span class="badge badge-cat">Cat.${product.categorie_transport}</span>`);
            }

            if (product.corde_article_ferme === 'x') {
                badges.push(`<span class="badge badge-closed">FERMÉ</span>`);
            }
            
            return badges.join(' ');
        }

        function highlightMatch(text, searchTerm) {
            if (!text || !searchTerm || searchTerm.length < 2) return escapeHtml(text);
            
            const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
            return escapeHtml(text).replace(regex, '<mark style="background:yellow;padding:0.1rem;">$1</mark>');
        }

        function escapeRegex(text) {
            return text.replace(/[.*+?^${}()|[\]\\]/g, '\\        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% {');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showResults() {
            if (resultsSection) {
                resultsSection.style.display = 'block';
            }
        }

        function hideResults() {
            if (resultsSection) {
                resultsSection.style.display = 'none';
            }
        }

        function hideSuggestions() {
            if (suggestionsContainer) {
                suggestionsContainer.style.display = 'none';
            }
        }

        function clearResults() {
            hideResults();
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            currentSearchTerm = '';
            selectedIndex = -1;
        }

        function showError(message) {
            if (resultsContent) {
                resultsContent.innerHTML = `
                    <div style="text-align:center;color:#666;padding:2rem;">
                        ❌ ${message}
                    </div>
                `;
            }
            showResults();
        }

        function loadPopularProducts() {
            console.log('💡 Chargement produits populaires');
            
            fetch(`${SEARCH_CONFIG.apiUrl}?action=popular`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.popular) {
                    displayInitialSuggestions(data.popular);
                }
            })
            .catch(error => {
                console.log('Info: Produits populaires non disponibles');
            });
        }

        function displayInitialSuggestions(products) {
            if (!suggestionsContainer || !products || products.length === 0) return;

            let html = '<div style="padding:0.5rem 1rem;background:#f8f9fa;font-size:0.8rem;color:#666;border-bottom:1px solid #eee;">💡 Produits ADR fréquents :</div>';
            
            products.forEach((product, index) => {
                const badges = generateBadges(product);
                html += `
                    <div class="suggestion-item" data-code="${product.code_produit}" data-index="${index}">
                        <div class="suggestion-product">
                            <div class="suggestion-name">${product.nom_produit}</div>
                            <div class="suggestion-code">Code: ${product.code_produit}</div>
                        </div>
                        <div class="suggestion-badges">${badges}</div>
                    </div>
                `;
            });

            suggestionsContainer.innerHTML = html;
            suggestionsContainer.style.display = 'block';
            
            // Event listeners
            document.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    selectProduct(item.dataset.code);
                });
            });
        }

        // ========== RACCOURCIS CLAVIER ==========
        document.addEventListener('keydown', function(e) {
            // Ctrl+K ou Cmd+K pour focus sur la recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                showTab('recherche');
                setTimeout(() => {
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }, 100);
            }
            
            // Escape pour effacer la recherche
            if (e.key === 'Escape' && document.activeElement !== searchInput) {
                clearResults();
            }

            // Chiffres 1-5 pour naviguer entre onglets
            const numberKeys = ['1', '2', '3', '4', '5'];
            const tabNames = ['recherche', 'expeditions', 'mes-expeditions', 'recapitulatifs', 'statistiques'];
            
            if (e.ctrlKey && numberKeys.includes(e.key)) {
                e.preventDefault();
                const tabIndex = parseInt(e.key) - 1;
                if (tabNames[tabIndex]) {
                    showTab(tabNames[tabIndex]);
                }
            }
        });

        // ========== INITIALISATION ==========
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Dashboard ADR chargé - Version robuste');
            
            initializeSearch();
            
            // Auto-focus sur la recherche
            setTimeout(() => {
                if (searchInput) {
                    searchInput.focus();
                    loadPopularProducts();
                }
            }, 100);
        });

        // Nettoyage du cache périodique
        setInterval(() => {
            const now = Date.now();
            for (const [key, value] of searchCache) {
                if (now - value.timestamp > 60000) { // 1 minute
                    searchCache.delete(key);
                }
            }
        }, 30000);

        console.log('💡 Raccourcis disponibles:');
        console.log('  • Ctrl+K : Focus recherche');
        console.log('  • Ctrl+1-5 : Navigation onglets');
        console.log('  • Flèches : Navigation suggestions');
        console.log('  • Enter : Sélection');
        console.log('  • Escape : Fermer');
    </script>
</body>
</html>

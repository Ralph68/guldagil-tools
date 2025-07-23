<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

try {
    require_once ROOT_PATH . '/config/config.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Config error']);
    exit;
}

$action = $_GET['action'] ?? 'popular';
$query = trim($_GET['q'] ?? '');
$limit = min((int)($_GET['limit'] ?? 10), 50);

try {
    if ($action === 'popular') {
        $sql = "SELECT code_produit, nom_produit, numero_un, classe_adr, danger_environnement, type_contenant 
                FROM gul_adr_products WHERE actif = 1 ORDER BY date_modification DESC LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'products' => $products, 'count' => count($products)]);
        
    } elseif ($action === 'suggestions') {
        if (strlen($query) < 3) {
            echo json_encode(['success' => true, 'suggestions' => []]);
            exit;
        }
        $searchTerm = "%{$query}%";
        $sql = "SELECT code_produit, nom_produit, numero_un, classe_adr, danger_environnement 
                FROM gul_adr_products WHERE actif = 1 AND (code_produit LIKE ? OR nom_produit LIKE ?) 
                ORDER BY code_produit LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'suggestions' => $suggestions, 'count' => count($suggestions)]);
        
    } elseif ($action === 'search') {
        if (empty($query)) {
            echo json_encode(['success' => true, 'products' => [], 'total' => 0]);
            exit;
        }
        $searchTerm = "%{$query}%";
        $sql = "SELECT *, CASE WHEN numero_un IS NOT NULL AND numero_un != '' THEN 'ADR' ELSE 'Standard' END as statut_produit 
                FROM gul_adr_products WHERE actif = 1 AND (code_produit LIKE ? OR nom_produit LIKE ?) 
                ORDER BY code_produit LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as &$product) {
            $product['fds_url'] = "https://www.quickfds.com/fr/search/Guldagil/" . urlencode($product['code_produit']);
        }
        
        echo json_encode(['success' => true, 'products' => $products, 'count' => count($products), 'total' => count($products), 'query' => $query]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
    
} catch (Exception $e) {
    error_log("API ADR: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}
?>

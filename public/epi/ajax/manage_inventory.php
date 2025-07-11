<?php
/**
 * Titre: Gestion AJAX inventaire EPI
 * Chemin: /features/epi/ajax/manage_inventory.php
 * Version: 0.5 beta + build auto
 */

session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../epimanager.php';

// Headers pour AJAX
header('Content-Type: application/json');

// Vérification méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $epiManager = new EpiManager();
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $result = createInventoryItem($epiManager, $_POST);
            break;
            
        case 'update':
            $result = updateInventoryItem($epiManager, $_POST);
            break;
            
        case 'replenish':
            $result = replenishStock($epiManager, $_POST);
            break;
            
        case 'delete':
            $result = deleteInventoryItem($epiManager, $_POST);
            break;
            
        default:
            throw new Exception("Action non reconnue: $action");
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur AJAX inventory: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

/**
 * Créer un nouvel élément d'inventaire
 */
function createInventoryItem($epiManager, $data): array {
    // Validation des données
    $required = ['category_id', 'quantity_total', 'quantity_available', 'minimum_stock'];
    foreach ($required as $field) {
        if (empty($data[$field]) && $data[$field] !== '0') {
            return [
                'success' => false, 
                'message' => "Le champ $field est obligatoire"
            ];
        }
    }
    
    // Validation métier
    $quantityTotal = (int)$data['quantity_total'];
    $quantityAvailable = (int)$data['quantity_available'];
    $minimumStock = (int)$data['minimum_stock'];
    
    if ($quantityAvailable > $quantityTotal) {
        return [
            'success' => false,
            'message' => 'La quantité disponible ne peut pas être supérieure au stock total'
        ];
    }
    
    if ($quantityTotal <= 0) {
        return [
            'success' => false,
            'message' => 'Le stock total doit être supérieur à zéro'
        ];
    }
    
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Vérifier si un stock existe déjà pour cette catégorie
        $stmt = $db->prepare("
            SELECT id FROM epi_inventory 
            WHERE category_id = :category_id AND status = 'active'
        ");
        $stmt->execute(['category_id' => $data['category_id']]);
        
        if ($stmt->fetch()) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Un stock existe déjà pour cette catégorie'
            ];
        }
        
        // Créer le nouvel inventaire
        $stmt = $db->prepare("
            INSERT INTO epi_inventory 
            (category_id, quantity_total, quantity_available, minimum_stock, location, status, created_at)
            VALUES (:category_id, :quantity_total, :quantity_available, :minimum_stock, :location, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            'category_id' => $data['category_id'],
            'quantity_total' => $quantityTotal,
            'quantity_available' => $quantityAvailable,
            'minimum_stock' => $minimumStock,
            'location' => $data['location'] ?? null
        ]);
        
        if ($result) {
            $db->commit();
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Stock créé avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'Stock créé avec succès',
                'redirect' => '/features/epi/inventory.php'
            ];
        } else {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du stock'
            ];
        }
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Erreur PDO create inventory: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}

/**
 * Réapprovisionner le stock
 */
function replenishStock($epiManager, $data): array {
    $itemId = (int)($data['item_id'] ?? 0);
    $quantity = (int)($data['quantity'] ?? 0);
    
    if ($itemId <= 0 || $quantity <= 0) {
        return [
            'success' => false,
            'message' => 'Données invalides pour le réapprovisionnement'
        ];
    }
    
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Récupérer l'état actuel
        $stmt = $db->prepare("
            SELECT quantity_total, quantity_available 
            FROM epi_inventory 
            WHERE id = :id AND status = 'active'
        ");
        $stmt->execute(['id' => $itemId]);
        $current = $stmt->fetch();
        
        if (!$current) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Article non trouvé'
            ];
        }
        
        // Mise à jour des quantités
        $newTotal = $current['quantity_total'] + $quantity;
        $newAvailable = $current['quantity_available'] + $quantity;
        
        $stmt = $db->prepare("
            UPDATE epi_inventory 
            SET quantity_total = :quantity_total,
                quantity_available = :quantity_available,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $result = $stmt->execute([
            'quantity_total' => $newTotal,
            'quantity_available' => $newAvailable,
            'id' => $itemId
        ]);
        
        if ($result) {
            $db->commit();
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => "Stock augmenté de $quantity unités"
            ];
            
            return [
                'success' => true,
                'message' => "Stock réapprovisionné (+$quantity)",
                'redirect' => '/features/epi/inventory.php'
            ];
        } else {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors du réapprovisionnement'
            ];
        }
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Erreur PDO replenish: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}

/**
 * Mettre à jour un élément d'inventaire
 */
function updateInventoryItem($epiManager, $data): array {
    $itemId = (int)($data['item_id'] ?? 0);
    
    if ($itemId <= 0) {
        return [
            'success' => false,
            'message' => 'ID article invalide'
        ];
    }
    
    // Validation similaire à create
    $quantityTotal = (int)($data['quantity_total'] ?? 0);
    $quantityAvailable = (int)($data['quantity_available'] ?? 0);
    $minimumStock = (int)($data['minimum_stock'] ?? 0);
    
    if ($quantityAvailable > $quantityTotal) {
        return [
            'success' => false,
            'message' => 'La quantité disponible ne peut pas être supérieure au stock total'
        ];
    }
    
    global $db;
    
    try {
        $stmt = $db->prepare("
            UPDATE epi_inventory 
            SET quantity_total = :quantity_total,
                quantity_available = :quantity_available,
                minimum_stock = :minimum_stock,
                location = :location,
                updated_at = NOW()
            WHERE id = :id AND status = 'active'
        ");
        
        $result = $stmt->execute([
            'quantity_total' => $quantityTotal,
            'quantity_available' => $quantityAvailable,
            'minimum_stock' => $minimumStock,
            'location' => $data['location'] ?? null,
            'id' => $itemId
        ]);
        
        if ($result) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Stock mis à jour avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'Stock mis à jour',
                'redirect' => '/features/epi/inventory.php'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Aucune modification effectuée'
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur PDO update inventory: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}

/**
 * Supprimer un élément d'inventaire
 */
function deleteInventoryItem($epiManager, $data): array {
    $itemId = (int)($data['item_id'] ?? 0);
    
    if ($itemId <= 0) {
        return [
            'success' => false,
            'message' => 'ID article invalide'
        ];
    }
    
    global $db;
    
    try {
        // Vérifier s'il y a des attributions actives
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM epi_assignments a
            JOIN epi_inventory i ON a.category_id = i.category_id
            WHERE i.id = :inventory_id AND a.status = 'active'
        ");
        $stmt->execute(['inventory_id' => $itemId]);
        $activeAssignments = $stmt->fetchColumn();
        
        if ($activeAssignments > 0) {
            return [
                'success' => false,
                'message' => 'Impossible de supprimer : des EPI sont encore attribués'
            ];
        }
        
        // Suppression logique
        $stmt = $db->prepare("
            UPDATE epi_inventory 
            SET status = 'deleted', updated_at = NOW()
            WHERE id = :id
        ");
        
        $result = $stmt->execute(['id' => $itemId]);
        
        if ($result) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Stock supprimé avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'Stock supprimé',
                'redirect' => '/features/epi/inventory.php'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur PDO delete inventory: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}
?>

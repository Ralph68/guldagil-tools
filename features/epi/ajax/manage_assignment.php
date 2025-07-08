<?php
/**
 * Titre: Gestion AJAX attributions EPI
 * Chemin: /features/epi/ajax/manage_assignment.php
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
            $result = createAssignment($epiManager, $_POST);
            break;
            
        case 'extend':
            $result = extendAssignment($_POST);
            break;
            
        case 'return':
            $result = returnAssignment($_POST);
            break;
            
        case 'update':
            $result = updateAssignment($_POST);
            break;
            
        default:
            throw new Exception("Action non reconnue: $action");
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur AJAX assignment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

/**
 * Créer une nouvelle attribution
 */
function createAssignment($epiManager, $data): array {
    // Validation des données obligatoires
    $required = ['employee_id', 'category_id', 'expiry_type'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return [
                'success' => false, 
                'message' => "Le champ $field est obligatoire"
            ];
        }
    }
    
    $employeeId = (int)$data['employee_id'];
    $categoryId = (int)$data['category_id'];
    $expiryType = $data['expiry_type'];
    $expiryDate = null;
    
    // Gestion de la date d'expiration
    if ($expiryType === 'temporary') {
        if (empty($data['expiry_date'])) {
            return [
                'success' => false,
                'message' => 'Date d\'expiration obligatoire pour une attribution temporaire'
            ];
        }
        
        $expiryDate = $data['expiry_date'];
        
        // Vérifier que la date n'est pas dans le passé
        if (strtotime($expiryDate) <= time()) {
            return [
                'success' => false,
                'message' => 'La date d\'expiration doit être dans le futur'
            ];
        }
    }
    
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Vérifier si l'employé a déjà cette catégorie d'EPI
        $stmt = $db->prepare("
            SELECT id FROM epi_assignments 
            WHERE employee_id = :employee_id 
            AND category_id = :category_id 
            AND status = 'active'
        ");
        $stmt->execute([
            'employee_id' => $employeeId,
            'category_id' => $categoryId
        ]);
        
        if ($stmt->fetch()) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Cet employé possède déjà un EPI de cette catégorie'
            ];
        }
        
        // Vérifier le stock disponible
        $stmt = $db->prepare("
            SELECT quantity_available 
            FROM epi_inventory 
            WHERE category_id = :category_id AND status = 'active'
        ");
        $stmt->execute(['category_id' => $categoryId]);
        $stock = $stmt->fetchColumn();
        
        if ($stock <= 0) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Stock insuffisant pour cette catégorie d\'EPI'
            ];
        }
        
        // Créer l'attribution
        $stmt = $db->prepare("
            INSERT INTO epi_assignments 
            (employee_id, category_id, assigned_date, expiry_date, notes, status, created_at)
            VALUES (:employee_id, :category_id, NOW(), :expiry_date, :notes, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            'employee_id' => $employeeId,
            'category_id' => $categoryId,
            'expiry_date' => $expiryDate,
            'notes' => $data['notes'] ?? null
        ]);
        
        if ($result) {
            // Décrémenter le stock
            $stmt = $db->prepare("
                UPDATE epi_inventory 
                SET quantity_available = quantity_available - 1,
                    updated_at = NOW()
                WHERE category_id = :category_id AND status = 'active'
            ");
            $stmt->execute(['category_id' => $categoryId]);
            
            $db->commit();
            
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Attribution créée avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'Attribution créée avec succès',
                'redirect' => '/features/epi/assignments.php'
            ];
        } else {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de la création de l\'attribution'
            ];
        }
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Erreur PDO create assignment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}

/**
 * Prolonger une attribution
 */
function extendAssignment($data): array {
    $assignmentId = (int)($data['assignment_id'] ?? 0);
    $newExpiryDate = $data['new_expiry_date'] ?? '';
    
    if ($assignmentId <= 0) {
        return [
            'success' => false,
            'message' => 'ID attribution invalide'
        ];
    }
    
    if (empty($newExpiryDate)) {
        return [
            'success' => false,
            'message' => 'Nouvelle date d\'expiration requise'
        ];
    }
    
    // Vérifier que la nouvelle date est dans le futur
    if (strtotime($newExpiryDate) <= time()) {
        return [
            'success' => false,
            'message' => 'La nouvelle date d\'expiration doit être dans le futur'
        ];
    }
    
    global $db;
    
    try {
        $stmt = $db->prepare("
            UPDATE epi_assignments 
            SET expiry_date = :expiry_date,
                updated_at = NOW()
            WHERE id = :assignment_id AND status = 'active'
        ");
        
        $result = $stmt->execute([
            'expiry_date' => $newExpiryDate,
            'assignment_id' => $assignmentId
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Attribution prolongée jusqu\'au ' . date('d/m/Y', strtotime($newExpiryDate))
            ];
            
            return [
                'success' => true,
                'message' => 'Attribution prolongée avec succès',
                'redirect' => '/features/epi/assignments.php'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Attribution non trouvée ou déjà inactive'
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur PDO extend assignment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}

/**
 * Retourner un EPI (terminer l'attribution)
 */
function returnAssignment($data): array {
    $assignmentId = (int)($data['assignment_id'] ?? 0);
    
    if ($assignmentId <= 0) {
        return [
            'success' => false,
            'message' => 'ID attribution invalide'
        ];
    }
    
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Récupérer les infos de l'attribution
        $stmt = $db->prepare("
            SELECT category_id 
            FROM epi_assignments 
            WHERE id = :assignment_id AND status = 'active'
        ");
        $stmt->execute(['assignment_id' => $assignmentId]);
        $assignment = $stmt->fetch();
        
        if (!$assignment) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Attribution non trouvée ou déjà terminée'
            ];
        }
        
        // Terminer l'attribution
        $stmt = $db->prepare("
            UPDATE epi_assignments 
            SET status = 'returned',
                return_date = NOW(),
                updated_at = NOW()
            WHERE id = :assignment_id
        ");
        
        $result = $stmt->execute(['assignment_id' => $assignmentId]);
        
        if ($result) {
            // Remettre en stock
            $stmt = $db->prepare("
                UPDATE epi_inventory 
                SET quantity_available = quantity_available + 1,
                    updated_at = NOW()
                WHERE category_id = :category_id AND status = 'active'
            ");
            $stmt->execute(['category_id' => $assignment['category_id']]);
            
            $db->commit();
            
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'EPI retourné avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'EPI retourné avec succès',
                'redirect' => '/features/epi/assignments.php'
            ];
        } else {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors du retour de l\'EPI'
            ];
        }
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Erreur PDO return assignment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}

/**
 * Mettre à jour une attribution
 */
function updateAssignment($data): array {
    $assignmentId = (int)($data['assignment_id'] ?? 0);
    
    if ($assignmentId <= 0) {
        return [
            'success' => false,
            'message' => 'ID attribution invalide'
        ];
    }
    
    global $db;
    
    try {
        $stmt = $db->prepare("
            UPDATE epi_assignments 
            SET expiry_date = :expiry_date,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :assignment_id AND status = 'active'
        ");
        
        $result = $stmt->execute([
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assignment_id' => $assignmentId
        ]);
        
        if ($result) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Attribution mise à jour avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'Attribution mise à jour',
                'redirect' => '/features/epi/assignments.php'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Aucune modification effectuée'
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur PDO update assignment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}
?>

<?php
/**
 * Titre: Gestion AJAX employés EPI
 * Chemin: /features/epi/ajax/manage_employee.php
 * Version: 0.5 beta + build auto
 */

session_start();
require_once __DIR__ . '/../../../config/database.php';

// Headers pour AJAX
header('Content-Type: application/json');

// Vérification méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $result = createEmployee($_POST);
            break;
            
        case 'update':
            $result = updateEmployee($_POST);
            break;
            
        case 'delete':
            $result = deleteEmployee($_POST);
            break;
            
        default:
            throw new Exception("Action non reconnue: $action");
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur AJAX employee: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

/**
 * Créer un nouvel employé
 */
function createEmployee($data): array {
    global $db;
    
    // Validation des données obligatoires
    $required = ['first_name', 'last_name'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return [
                'success' => false, 
                'message' => "Le champ $field est obligatoire"
            ];
        }
    }
    
    // Validation email si fourni
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Format d\'email invalide'
        ];
    }
    
    try {
        // Vérifier si l'email existe déjà
        if (!empty($data['email'])) {
            $stmt = $db->prepare("SELECT id FROM epi_employees WHERE email = :email AND status = 'active'");
            $stmt->execute(['email' => $data['email']]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Cet email est déjà utilisé'
                ];
            }
        }
        
        // Créer l'employé
        $stmt = $db->prepare("
            INSERT INTO epi_employees 
            (first_name, last_name, email, department, hire_date, status, created_at)
            VALUES (:first_name, :last_name, :email, :department, :hire_date, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'department' => !empty($data['department']) ? trim($data['department']) : null,
            'hire_date' => !empty($data['hire_date']) ? $data['hire_date'] : null
        ]);
        
        if ($result) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Employé créé avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'Employé créé avec succès',
                'redirect' => '/features/epi/employees.php'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Erreur lors de la création de l\'employé'
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur PDO create employee: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}

/**
 * Mettre à jour un employé
 */
function updateEmployee($data): array {
    global $db;
    
    $employeeId = (int)($data['employee_id'] ?? 0);
    
    if ($employeeId <= 0) {
        return [
            'success' => false,
            'message' => 'ID employé invalide'
        ];
    }
    
    // Validation similaire à create
    $required = ['first_name', 'last_name'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return [
                'success' => false, 
                'message' => "Le champ $field est obligatoire"
            ];
        }
    }
    
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Format d\'email invalide'
        ];
    }
    
    try {
        // Vérifier si l'email existe déjà pour un autre employé
        if (!empty($data['email'])) {
            $stmt = $db->prepare("
                SELECT id FROM epi_employees 
                WHERE email = :email AND id != :employee_id AND status = 'active'
            ");
            $stmt->execute(['email' => $data['email'], 'employee_id' => $employeeId]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Cet email est déjà utilisé par un autre employé'
                ];
            }
        }
        
        // Mettre à jour l'employé
        $stmt = $db->prepare("
            UPDATE epi_employees 
            SET first_name = :first_name,
                last_name = :last_name,
                email = :email,
                department = :department,
                hire_date = :hire_date,
                updated_at = NOW()
            WHERE id = :employee_id AND status = 'active'
        ");
        
        $result = $stmt->execute([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'department' => !empty($data['department']) ? trim($data['department']) : null,
            'hire_date' => !empty($data['hire_date']) ? $data['hire_date'] : null,
            'employee_id' => $employeeId
        ]);
        
        if ($result) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Employé mis à jour avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'Employé mis à jour',
                'redirect' => '/features/epi/employees.php'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Aucune modification effectuée'
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur PDO update employee: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur de base de données'
        ];
    }
}

/**
 * Supprimer (désactiver) un employé
 */
function deleteEmployee($data): array {
    global $db;
    
    $employeeId = (int)($data['employee_id'] ?? 0);
    
    if ($employeeId <= 0) {
        return [
            'success' => false,
            'message' => 'ID employé invalide'
        ];
    }
    
    try {
        $db->beginTransaction();
        
        // Vérifier s'il y a des attributions actives
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM epi_assignments 
            WHERE employee_id = :employee_id AND status = 'active'
        ");
        $stmt->execute(['employee_id' => $employeeId]);
        $activeAssignments = $stmt->fetchColumn();
        
        if ($activeAssignments > 0) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Impossible de supprimer : cet employé a des EPI attribués'
            ];
        }
        
        // Désactivation logique de l'employé
        $stmt = $db->prepare("
            UPDATE epi_employees 
            SET status = 'inactive', updated_at = NOW()
            WHERE id = :employee_id
        ");
        
        $result = $stmt->execute(['employee_id' => $employeeId]);
        
        if ($result) {
            $db->commit();
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Employé désactivé avec succès'
            ];
            
            return [
                'success' => true,
                'message' => 'Employé supprimé',
                'redirect' => '/features/epi/employees.php'
            ];
        } else {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ];
        }
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Erreur PDO delete employee: " . $e->getMessage());
        return [

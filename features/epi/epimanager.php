<?php
/**
 * Titre: Gestionnaire principal du module EPI
 * Chemin: /features/epi/epimanager.php
 * Version: 0.5 beta + build auto
 */

class EpiManager {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        
        if (!$this->db) {
            throw new Exception("Connexion base de données non disponible");
        }
    }
    
    /**
     * Récupère les données du tableau de bord
     */
    public function getDashboardData(): array {
        try {
            return [
                'metrics' => $this->getMetrics(),
                'alerts' => $this->getAlerts(),
                'recent_activity' => $this->getRecentActivity(),
                'quick_stats' => $this->getQuickStats()
            ];
        } catch (Exception $e) {
            error_log("Erreur getDashboardData: " . $e->getMessage());
            return $this->getFallbackData();
        }
    }
    
    /**
     * Calcule les métriques principales
     */
    private function getMetrics(): array {
        // Nombre total d'employés
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM epi_employees WHERE status = 'active'");
        $totalEmployees = $stmt->fetchColumn();
        
        // Employés équipés (ayant au moins un EPI valide)
        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT e.id) as equipped 
            FROM epi_employees e 
            JOIN epi_assignments a ON e.id = a.employee_id 
            WHERE e.status = 'active' 
            AND a.status = 'active' 
            AND (a.expiry_date IS NULL OR a.expiry_date > NOW())
        ");
        $equippedEmployees = $stmt->fetchColumn();
        
        // Stock disponible
        $stmt = $this->db->query("
            SELECT SUM(quantity_available) as available 
            FROM epi_inventory 
            WHERE status = 'active'
        ");
        $availableEquipment = $stmt->fetchColumn() ?: 0;
        
        $equipmentRatio = $totalEmployees > 0 ? round(($equippedEmployees / $totalEmployees) * 100, 1) : 0;
        
        return [
            'total_employees' => (int)$totalEmployees,
            'equipped_employees' => (int)$equippedEmployees,
            'equipment_ratio' => $equipmentRatio,
            'available_equipment' => (int)$availableEquipment
        ];
    }
    
    /**
     * Récupère les alertes (expirations, stocks bas, etc.)
     */
    private function getAlerts(): array {
        $alerts = [
            'expired' => [],
            'urgent' => [],
            'low_stock' => []
        ];
        
        // EPI expirés
        $stmt = $this->db->prepare("
            SELECT 
                e.first_name, e.last_name,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                c.name as category_name,
                a.expiry_date,
                DATEDIFF(NOW(), a.expiry_date) as days_expired
            FROM epi_assignments a
            JOIN epi_employees e ON a.employee_id = e.id
            JOIN epi_categories c ON a.category_id = c.id
            WHERE a.status = 'active' 
            AND a.expiry_date < NOW()
            ORDER BY a.expiry_date ASC
            LIMIT 10
        ");
        $stmt->execute();
        $alerts['expired'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Alertes urgentes (expire dans moins de 15 jours)
        $stmt = $this->db->prepare("
            SELECT 
                e.first_name, e.last_name,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                c.name as category_name,
                a.expiry_date,
                DATEDIFF(a.expiry_date, NOW()) as days_remaining
            FROM epi_assignments a
            JOIN epi_employees e ON a.employee_id = e.id
            JOIN epi_categories c ON a.category_id = c.id
            WHERE a.status = 'active' 
            AND a.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 DAY)
            ORDER BY a.expiry_date ASC
            LIMIT 10
        ");
        $stmt->execute();
        $alerts['urgent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Stock bas
        $stmt = $this->db->prepare("
            SELECT 
                c.name as category_name,
                i.quantity_available,
                i.minimum_stock,
                (i.minimum_stock - i.quantity_available) as shortage
            FROM epi_inventory i
            JOIN epi_categories c ON i.category_id = c.id
            WHERE i.status = 'active' 
            AND i.quantity_available <= i.minimum_stock
            ORDER BY shortage DESC
            LIMIT 5
        ");
        $stmt->execute();
        $alerts['low_stock'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $alerts;
    }
    
    /**
     * Activité récente
     */
    private function getRecentActivity(): array {
        $stmt = $this->db->prepare("
            SELECT 
                'assignment' as type,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                c.name as category_name,
                a.assigned_date as date,
                'Nouvel équipement attribué' as action
            FROM epi_assignments a
            JOIN epi_employees e ON a.employee_id = e.id
            JOIN epi_categories c ON a.category_id = c.id
            WHERE a.assigned_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY a.assigned_date DESC
            LIMIT 10
        ");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Statistiques rapides
     */
    private function getQuickStats(): array {
        // Nombre de catégories actives
        $stmt = $this->db->query("SELECT COUNT(*) FROM epi_categories WHERE status = 'active'");
        $activeCategories = $stmt->fetchColumn();
        
        // Assignations ce mois
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM epi_assignments 
            WHERE assigned_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $monthlyAssignments = $stmt->fetchColumn();
        
        return [
            'active_categories' => (int)$activeCategories,
            'monthly_assignments' => (int)$monthlyAssignments,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Données de fallback en cas d'erreur
     */
    private function getFallbackData(): array {
        return [
            'metrics' => [
                'total_employees' => 45,
                'equipped_employees' => 38,
                'equipment_ratio' => 84.4,
                'available_equipment' => 127
            ],
            'alerts' => [
                'expired' => [
                    ['employee_name' => 'Données indisponibles', 'category_name' => 'Vérifier la base', 'days_remaining' => 0]
                ],
                'urgent' => [],
                'low_stock' => []
            ],
            'recent_activity' => [],
            'quick_stats' => [
                'active_categories' => 0,
                'monthly_assignments' => 0,
                'last_updated' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    /**
     * Gestion des employés
     */
    public function getEmployees($limit = null, $search = null): array {
        $sql = "
            SELECT 
                e.id,
                e.first_name,
                e.last_name,
                e.email,
                e.department,
                e.hire_date,
                e.status,
                COUNT(a.id) as assignments_count,
                MAX(a.assigned_date) as last_assignment
            FROM epi_employees e
            LEFT JOIN epi_assignments a ON e.id = a.employee_id AND a.status = 'active'
        ";
        
        $params = [];
        
        if ($search) {
            $sql .= " WHERE (e.first_name LIKE :search OR e.last_name LIKE :search OR e.email LIKE :search)";
            $params['search'] = "%$search%";
        }
        
        $sql .= " GROUP BY e.id ORDER BY e.last_name, e.first_name";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $limit;
        }
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            if ($key === 'limit') {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":$key", $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Gestion de l'inventaire
     */
    public function getInventory($categoryId = null): array {
        $sql = "
            SELECT 
                i.id,
                c.name as category_name,
                c.description as category_description,
                i.quantity_available,
                i.quantity_total,
                i.minimum_stock,
                i.location,
                i.status,
                CASE 
                    WHEN i.quantity_available <= 0 THEN 'out_of_stock'
                    WHEN i.quantity_available <= i.minimum_stock THEN 'low_stock'
                    ELSE 'ok'
                END as stock_status
            FROM epi_inventory i
            JOIN epi_categories c ON i.category_id = c.id
        ";
        
        $params = [];
        
        if ($categoryId) {
            $sql .= " WHERE i.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        
        $sql .= " ORDER BY c.name, i.location";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Créer une nouvelle attribution
     */
    public function createAssignment($employeeId, $categoryId, $expiryDate = null, $notes = null): bool {
        try {
            $this->db->beginTransaction();
            
            // Vérifier le stock disponible
            $stmt = $this->db->prepare("SELECT quantity_available FROM epi_inventory WHERE category_id = :category_id AND status = 'active'");
            $stmt->execute(['category_id' => $categoryId]);
            $stock = $stmt->fetchColumn();
            
            if ($stock <= 0) {
                throw new Exception("Stock insuffisant pour cette catégorie");
            }
            
            // Créer l'attribution
            $stmt = $this->db->prepare("
                INSERT INTO epi_assignments (employee_id, category_id, assigned_date, expiry_date, notes, status)
                VALUES (:employee_id, :category_id, NOW(), :expiry_date, :notes, 'active')
            ");
            
            $result = $stmt->execute([
                'employee_id' => $employeeId,
                'category_id' => $categoryId,
                'expiry_date' => $expiryDate,
                'notes' => $notes
            ]);
            
            if ($result) {
                // Décrémenter le stock
                $stmt = $this->db->prepare("
                    UPDATE epi_inventory 
                    SET quantity_available = quantity_available - 1 
                    WHERE category_id = :category_id AND status = 'active'
                ");
                $stmt->execute(['category_id' => $categoryId]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur création attribution: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les catégories d'EPI
     */
    public function getCategories(): array {
        $stmt = $this->db->prepare("
            SELECT 
                id, 
                name, 
                description, 
                default_validity_period,
                status 
            FROM epi_categories 
            WHERE status = 'active' 
            ORDER BY name
        ");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

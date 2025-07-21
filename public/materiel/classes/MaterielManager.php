<?php
/**
 * Titre: Gestionnaire du module Matériel - Version corrigée
 * Chemin: /public/materiel/classes/MaterielManager.php
 * Version: 0.5 beta + build auto
 */

class MaterielManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Vérifier si les tables existent
     */
    private function checkTablesExist() {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'materiel_%'");
            $tables = $stmt->fetchAll();
            return count($tables) >= 6; // Minimum 6 tables requises
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Créer les tables du module
     */
    public function createTables() {
        if (!$this->db) return false;
        
        try {
            $sql_file = dirname(__DIR__) . '/sql/create_tables.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                $this->db->exec($sql);
                return true;
            }
        } catch (Exception $e) {
            error_log("Erreur création tables matériel: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Statistiques générales
     */
    public function getStatistiquesGenerales() {
        if (!$this->db) return [];
        
        try {
            $stats = [];
            
            // Total matériel
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM materiel_items");
            $stats['total_materiel'] = $stmt->fetch()['total'] ?? 0;
            
            // Matériel attribué
            $stmt = $this->db->query("SELECT COUNT(*) as attribue FROM materiel_attributions WHERE etat_attribution = 'active'");
            $stats['materiel_attribue'] = $stmt->fetch()['attribue'] ?? 0;
            
            // Demandes en attente
            $stmt = $this->db->query("SELECT COUNT(*) as attente FROM materiel_demandes WHERE statut = 'en_attente'");
            $stats['demandes_attente'] = $stmt->fetch()['attente'] ?? 0;
            
            // Maintenance due
            $stmt = $this->db->query("SELECT COUNT(*) as maintenance FROM materiel_items WHERE prochaine_maintenance <= CURDATE() AND prochaine_maintenance IS NOT NULL");
            $stats['maintenance_due'] = $stmt->fetch()['maintenance'] ?? 0;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Erreur statistiques matériel: " . $e->getMessage());
            return [
                'total_materiel' => 0,
                'materiel_attribue' => 0,
                'demandes_attente' => 0,
                'maintenance_due' => 0
            ];
        }
    }
    
    /**
     * Créer un template de matériel
     */
    public function createTemplate($data) {
        if (!$this->db) return false;
        
        $sql = "INSERT INTO materiel_templates (categorie_id, designation, marque, modele, observations, quantite_standard, maintenance_requise) 
                VALUES (:categorie_id, :designation, :marque, :modele, :observations, :quantite_standard, :maintenance_requise)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':categorie_id' => $data['categorie_id'],
            ':designation' => $data['designation'],
            ':marque' => $data['marque'] ?? null,
            ':modele' => $data['modele'] ?? null,
            ':observations' => $data['observations'] ?? null,
            ':quantite_standard' => $data['quantite_standard'] ?? 1,
            ':maintenance_requise' => $data['maintenance_requise'] ?? 0
        ]);
    }
    
    /**
     * Obtenir templates par catégorie
     */
    public function getTemplatesByCategory() {
        if (!$this->db) return [];
        
        $sql = "SELECT t.*, c.nom as categorie_nom, c.type as categorie_type 
                FROM materiel_templates t 
                LEFT JOIN materiel_categories c ON t.categorie_id = c.id 
                ORDER BY c.nom, t.designation";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Créer un item de matériel
     */
    public function createItem($data) {
        if (!$this->db) return false;
        
        $sql = "INSERT INTO materiel_items (template_id, numero_serie, agence_id, etat, date_mise_service, 
                fournisseur, ref_fournisseur, prix_achat, observations) 
                VALUES (:template_id, :numero_serie, :agence_id, :etat, :date_mise_service, 
                :fournisseur, :ref_fournisseur, :prix_achat, :observations)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':template_id' => $data['template_id'],
            ':numero_serie' => $data['numero_serie'] ?? null,
            ':agence_id' => $data['agence_id'],
            ':etat' => $data['etat'] ?? 'neuf',
            ':date_mise_service' => $data['date_mise_service'] ?? date('Y-m-d'),
            ':fournisseur' => $data['fournisseur'] ?? null,
            ':ref_fournisseur' => $data['ref_fournisseur'] ?? null,
            ':prix_achat' => $data['prix_achat'] ?? null,
            ':observations' => $data['observations'] ?? null
        ]);
    }
    
    /**
     * Obtenir items par agence
     */
    public function getItemsByAgence($agence_id = null) {
        if (!$this->db) return [];
        
        $where = $agence_id ? "WHERE i.agence_id = :agence_id" : "";
        
        $sql = "SELECT i.*, t.designation, t.marque, t.modele, c.nom as categorie_nom, a.nom as agence_nom
                FROM materiel_items i
                LEFT JOIN materiel_templates t ON i.template_id = t.id
                LEFT JOIN materiel_categories c ON t.categorie_id = c.id
                LEFT JOIN materiel_agences a ON i.agence_id = a.id
                $where
                ORDER BY c.nom, t.designation";
        
        $stmt = $this->db->prepare($sql);
        if ($agence_id) {
            $stmt->execute([':agence_id' => $agence_id]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Créer une demande
     */
    public function createDemande($data) {
        if (!$this->db) return false;
        
        $sql = "INSERT INTO materiel_demandes (employee_id, template_id, type_demande, item_remplace_id, raison_demande, observations) 
                VALUES (:employee_id, :template_id, :type_demande, :item_remplace_id, :raison_demande, :observations)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':employee_id' => $data['employee_id'],
            ':template_id' => $data['template_id'],
            ':type_demande' => $data['type_demande'],
            ':item_remplace_id' => $data['item_remplace_id'] ?? null,
            ':raison_demande' => $data['raison_demande'] ?? null,
            ':observations' => $data['observations'] ?? null
        ]);
    }
    
    /**
     * Obtenir demandes en attente
     */
    public function getDemandesEnAttente($limit = 10) {
        if (!$this->db) return [];
        
        $sql = "SELECT d.*, e.nom, e.prenom, t.designation, t.marque
                FROM materiel_demandes d
                LEFT JOIN materiel_employees e ON d.employee_id = e.id
                LEFT JOIN materiel_templates t ON d.template_id = t.id
                WHERE d.statut = 'en_attente'
                ORDER BY d.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Utilitaires
     */
    public function getCategories() {
        if (!$this->db) return [];
        
        $stmt = $this->db->query("SELECT * FROM materiel_categories ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProfils() {
        if (!$this->db) return [];
        
        $stmt = $this->db->query("SELECT * FROM materiel_profils ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAgences() {
        if (!$this->db) return [];
        
        $stmt = $this->db->query("SELECT * FROM materiel_agences ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Installation du module
     */
    public function install() {
        if (!$this->checkTablesExist()) {
            return $this->createTables();
        }
        return true;
    }
    
    /**
     * État du module
     */
    public function getModuleStatus() {
        return [
            'database_connected' => $this->db !== null,
            'tables_exist' => $this->checkTablesExist(),
            'data_available' => $this->db ? $this->getStatistiquesGenerales()['total_materiel'] > 0 : false
        ];
    }
}
?>

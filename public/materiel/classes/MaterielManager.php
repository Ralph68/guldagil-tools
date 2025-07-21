<?php
/**
 * Titre: Gestionnaire du module Matériel - Version complète et corrigée
 * Chemin: /public/materiel/classes/MaterielManager.php
 * Version: 0.5 beta + build auto
 */

class MaterielManager {
    private $db;
    
    /**
     * Constructeur - OBLIGATOIRE avec paramètre database
     */
    public function __construct($database) {
        $this->db = $database;
        
        if (!$this->db) {
            throw new Exception("Base de données requise pour MaterielManager");
        }
    }
    
    /**
     * Vérifier le statut du module
     */
    public function getModuleStatus() {
        return [
            'database_connected' => $this->db !== null,
            'tables_exist' => $this->checkTablesExist(),
            'data_available' => $this->hasData()
        ];
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
     * Vérifier si des données existent
     */
    private function hasData() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM materiel_templates");
            $count = $stmt->fetchColumn();
            return $count > 0;
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
        if (!$this->db) return $this->getDefaultStats();
        
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
            $stmt = $this->db->query("
                SELECT COUNT(*) as maintenance 
                FROM materiel_electroportatif me
                JOIN materiel_items mi ON me.item_id = mi.id
                WHERE me.prochaine_revision <= CURDATE()
            ");
            $stats['maintenance_due'] = $stmt->fetch()['maintenance'] ?? 0;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Erreur statistiques matériel: " . $e->getMessage());
            return $this->getDefaultStats();
        }
    }
    
    /**
     * Statistiques par défaut en cas d'erreur
     */
    private function getDefaultStats() {
        return [
            'total_materiel' => 0,
            'materiel_attribue' => 0,
            'demandes_attente' => 0,
            'maintenance_due' => 0
        ];
    }
    
    /**
     * Récupérer les catégories
     */
    public function getCategories() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("SELECT * FROM materiel_categories ORDER BY nom");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Récupérer templates par catégorie
     */
    public function getTemplatesByCategory($category_id) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM materiel_templates 
                WHERE categorie_id = ? 
                ORDER BY designation
            ");
            $stmt->execute([$category_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Créer une demande de matériel
     */
    public function createDemande($data) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO materiel_demandes 
                (employee_id, template_id, type_demande, quantite_demandee, justification, urgence, date_livraison_souhaitee)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $data['employee_id'],
                $data['template_id'],
                $data['type_demande'],
                $data['quantite_demandee'],
                $data['justification'],
                $data['urgence'],
                $data['date_livraison_souhaitee']
            ]);
        } catch (Exception $e) {
            error_log("Erreur création demande: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer le matériel d'un employé - MÉTHODE MANQUANTE AJOUTÉE
     */
    public function getMyEquipment($employee_id) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    mi.numero_inventaire,
                    mt.designation,
                    mt.marque,
                    mt.modele,
                    ma.date_attribution,
                    ma.date_retour_prevue,
                    ma.etat_attribution,
                    mc.nom as categorie
                FROM materiel_attributions ma
                JOIN materiel_items mi ON ma.item_id = mi.id
                JOIN materiel_templates mt ON mi.template_id = mt.id
                JOIN materiel_categories mc ON mt.categorie_id = mc.id
                WHERE ma.employee_id = ? AND ma.etat_attribution = 'active'
                ORDER BY ma.date_attribution DESC
            ");
            $stmt->execute([$employee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur récupération équipement: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les demandes d'un employé
     */
    public function getMyRequests($employee_id) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    md.*,
                    mt.designation,
                    mt.marque,
                    mt.modele
                FROM materiel_demandes md
                JOIN materiel_templates mt ON md.template_id = mt.id
                WHERE md.employee_id = ?
                ORDER BY md.created_at DESC
            ");
            $stmt->execute([$employee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Statistiques par catégorie
     */
    public function getStatistiquesByCategorie() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    mc.nom,
                    mc.couleur,
                    COUNT(mi.id) as total_items
                FROM materiel_categories mc
                LEFT JOIN materiel_templates mt ON mc.id = mt.categorie_id
                LEFT JOIN materiel_items mi ON mt.id = mi.template_id
                GROUP BY mc.id, mc.nom, mc.couleur
                ORDER BY total_items DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Statistiques par agence
     */
    public function getStatistiquesByAgence() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    ma.nom,
                    COUNT(mi.id) as total_items,
                    COUNT(CASE WHEN mi.statut = 'attribue' THEN 1 END) as items_attribues
                FROM materiel_agences ma
                LEFT JOIN materiel_items mi ON ma.id = mi.agence_id
                GROUP BY ma.id, ma.nom
                ORDER BY total_items DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Statistiques des demandes
     */
    public function getDemandesStatistiques($period = 'month') {
        if (!$this->db) return [];
        
        try {
            $date_filter = match($period) {
                'week' => "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)",
                'month' => "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)",
                'year' => "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)",
                default => "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"
            };
            
            $stmt = $this->db->query("
                SELECT 
                    statut,
                    COUNT(*) as total
                FROM materiel_demandes
                WHERE $date_filter
                GROUP BY statut
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Statistiques de maintenance
     */
    public function getMaintenanceStatistiques() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    'due' as type,
                    COUNT(*) as total
                FROM materiel_electroportatif me
                JOIN materiel_items mi ON me.item_id = mi.id
                WHERE me.prochaine_revision <= CURDATE()
                UNION ALL
                SELECT 
                    'upcoming' as type,
                    COUNT(*) as total
                FROM materiel_electroportatif me
                JOIN materiel_items mi ON me.item_id = mi.id
                WHERE me.prochaine_revision BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Analyse des coûts
     */
    public function getCostAnalysis($period = 'month') {
        if (!$this->db) return [];
        
        try {
            $date_filter = match($period) {
                'week' => "DATE(mi.date_acquisition) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)",
                'month' => "DATE(mi.date_acquisition) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)",
                'year' => "DATE(mi.date_acquisition) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)",
                default => "DATE(mi.date_acquisition) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)"
            };
            
            $stmt = $this->db->query("
                SELECT 
                    mc.nom as categorie,
                    SUM(mi.prix_achat) as total_cost,
                    COUNT(mi.id) as total_items,
                    AVG(mi.prix_achat) as avg_cost
                FROM materiel_items mi
                JOIN materiel_templates mt ON mi.template_id = mt.id
                JOIN materiel_categories mc ON mt.categorie_id = mc.id
                WHERE $date_filter AND mi.prix_achat IS NOT NULL
                GROUP BY mc.id, mc.nom
                ORDER BY total_cost DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Récupérer les agences
     */
    public function getAgences() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("SELECT * FROM materiel_agences ORDER BY nom");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Récupérer les employés
     */
    public function getEmployees() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("SELECT * FROM materiel_employees WHERE actif = 1 ORDER BY nom, prenom");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>

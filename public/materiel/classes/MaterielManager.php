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
            $stmt = $this->db->query("SELECT COUNT(*) as maintenance FROM materiel_items WHERE prochaine_maintenance <= CURDATE() AND prochaine_maintenance IS NOT NULL");
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
     * Statistiques par catégorie
     */
    public function getStatistiquesByCategorie() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT c.nom, c.couleur, COUNT(i.id) as total_items
                FROM materiel_categories c
                LEFT JOIN materiel_templates t ON c.id = t.categorie_id
                LEFT JOIN materiel_items i ON t.id = i.template_id
                GROUP BY c.id, c.nom, c.couleur
                ORDER BY total_items DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur statistiques par catégorie: " . $e->getMessage());
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
                SELECT a.nom, COUNT(i.id) as total_items
                FROM materiel_agences a
                LEFT JOIN materiel_items i ON a.id = i.agence_id
                GROUP BY a.id, a.nom
                ORDER BY total_items DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur statistiques par agence: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Statistiques des demandes
     */
    public function getDemandesStatistiques($period = 'month') {
        if (!$this->db) return [];
        
        try {
            $date_condition = match($period) {
                'week' => "DATE_SUB(CURDATE(), INTERVAL 1 WEEK)",
                'month' => "DATE_SUB(CURDATE(), INTERVAL 1 MONTH)",
                'quarter' => "DATE_SUB(CURDATE(), INTERVAL 3 MONTH)",
                'year' => "DATE_SUB(CURDATE(), INTERVAL 1 YEAR)",
                default => "DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"
            };
            
            $stmt = $this->db->query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_demandes,
                    SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END) as validees,
                    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                    SUM(CASE WHEN statut = 'refusee' THEN 1 ELSE 0 END) as refusees
                FROM materiel_demandes
                WHERE created_at >= $date_condition
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur statistiques demandes: " . $e->getMessage());
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
                    COUNT(*) as total_items,
                    SUM(CASE WHEN prochaine_maintenance <= CURDATE() THEN 1 ELSE 0 END) as maintenance_due,
                    SUM(CASE WHEN prochaine_maintenance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as maintenance_30j,
                    SUM(CASE WHEN derniere_maintenance IS NULL THEN 1 ELSE 0 END) as jamais_maintenu
                FROM materiel_items
                WHERE statut != 'reforme'
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur statistiques maintenance: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Analyse des coûts
     */
    public function getCostAnalysis($period = 'month') {
        if (!$this->db) return [];
        
        try {
            $date_condition = match($period) {
                'week' => "DATE_SUB(CURDATE(), INTERVAL 1 WEEK)",
                'month' => "DATE_SUB(CURDATE(), INTERVAL 1 MONTH)",
                'quarter' => "DATE_SUB(CURDATE(), INTERVAL 3 MONTH)",
                'year' => "DATE_SUB(CURDATE(), INTERVAL 1 YEAR)",
                default => "DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"
            };
            
            $stmt = $this->db->query("
                SELECT 
                    SUM(i.prix_achat) as total_acquisitions,
                    COUNT(i.id) as items_achetes,
                    AVG(i.prix_achat) as prix_moyen,
                    c.nom as categorie
                FROM materiel_items i
                JOIN materiel_templates t ON i.template_id = t.id
                JOIN materiel_categories c ON t.categorie_id = c.id
                WHERE i.date_acquisition >= $date_condition
                GROUP BY c.id, c.nom
                ORDER BY total_acquisitions DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur analyse coûts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les catégories
     */
    public function getCategories() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT id, nom, type, description, couleur
                FROM materiel_categories
                ORDER BY nom ASC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur récupération catégories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les agences
     */
    public function getAgences() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT id, nom, adresse
                FROM materiel_agences
                ORDER BY nom ASC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur récupération agences: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les templates par catégorie
     */
    public function getTemplatesByCategory($categorie_id = null) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT t.id, t.categorie_id, t.designation, t.marque, t.modele, 
                       t.observations, t.quantite_standard, c.nom as categorie_nom
                FROM materiel_templates t
                JOIN materiel_categories c ON t.categorie_id = c.id
            ";
            
            if ($categorie_id) {
                $sql .= " WHERE t.categorie_id = :categorie_id";
            }
            
            $sql .= " ORDER BY c.nom, t.designation";
            
            $stmt = $this->db->prepare($sql);
            
            if ($categorie_id) {
                $stmt->bindParam(':categorie_id', $categorie_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur récupération templates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer l'équipement d'un utilisateur
     */
    public function getMyEquipment($user) {
        if (!$this->db) return [];
        
        try {
            $employee_id = $this->getEmployeeIdByUser($user);
            if (!$employee_id) return [];
            
            $stmt = $this->db->prepare("
                SELECT 
                    i.id, i.numero_serie, i.numero_inventaire, i.etat, i.statut,
                    t.designation, t.marque, t.modele,
                    c.nom as categorie,
                    a.date_attribution, a.etat_attribution
                FROM materiel_items i
                JOIN materiel_templates t ON i.template_id = t.id
                JOIN materiel_categories c ON t.categorie_id = c.id
                JOIN materiel_attributions a ON i.id = a.item_id
                WHERE a.employee_id = :employee_id 
                AND a.etat_attribution = 'active'
                ORDER BY t.designation
            ");
            
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur récupération équipement utilisateur: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer l'ID employé par utilisateur
     */
    public function getEmployeeIdByUser($user) {
        if (!$this->db || !$user) return null;
        
        try {
            // Chercher par nom d'utilisateur ou email
            $stmt = $this->db->prepare("
                SELECT id FROM materiel_employees 
                WHERE email = :username OR numero_badge = :username
                LIMIT 1
            ");
            
            $username = $user['username'] ?? $user['email'] ?? '';
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : null;
            
        } catch (Exception $e) {
            error_log("Erreur récupération ID employé: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Créer une demande
     */
    public function createDemande($data) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO materiel_demandes (
                    employee_id, type_demande, template_id, item_remplace_id,
                    quantite_demandee, justification, urgence, 
                    date_livraison_souhaitee, observations, statut, created_at
                ) VALUES (
                    :employee_id, :type_demande, :template_id, :item_remplace_id,
                    :quantite_demandee, :justification, :urgence,
                    :date_livraison_souhaitee, :observations, 'en_attente', NOW()
                )
            ");
            
            $stmt->bindParam(':employee_id', $data['employee_id'], PDO::PARAM_INT);
            $stmt->bindParam(':type_demande', $data['type_demande']);
            $stmt->bindParam(':template_id', $data['template_id'], PDO::PARAM_INT);
            $stmt->bindParam(':item_remplace_id', $data['item_remplace_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantite_demandee', $data['quantite_demandee'], PDO::PARAM_INT);
            $stmt->bindParam(':justification', $data['justification']);
            $stmt->bindParam(':urgence', $data['urgence']);
            $stmt->bindParam(':date_livraison_souhaitee', $data['date_livraison_souhaitee']);
            $stmt->bindParam(':observations', $data['observations']);
            
            $stmt->execute();
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Erreur création demande: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les demandes d'un utilisateur
     */
    public function getMyDemandes($user) {
        if (!$this->db) return [];
        
        try {
            $employee_id = $this->getEmployeeIdByUser($user);
            if (!$employee_id) return [];
            
            $stmt = $this->db->prepare("
                SELECT 
                    d.id, d.type_demande, d.quantite_demandee, d.justification,
                    d.urgence, d.statut, d.date_livraison_souhaitee, d.created_at,
                    d.date_validation, d.observations_validation,
                    t.designation, t.marque, t.modele,
                    c.nom as categorie
                FROM materiel_demandes d
                LEFT JOIN materiel_templates t ON d.template_id = t.id
                LEFT JOIN materiel_categories c ON t.categorie_id = c.id
                WHERE d.employee_id = :employee_id
                ORDER BY d.created_at DESC
            ");
            
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur récupération demandes utilisateur: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les items avec filtres
     */
    public function getItemsFiltered($filters = []) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT 
                    i.id, i.numero_serie, i.numero_inventaire, i.date_acquisition,
                    i.prix_achat, i.etat, i.statut, i.emplacement, i.observations,
                    t.designation, t.marque, t.modele,
                    c.nom as categorie, c.couleur as categorie_couleur,
                    a.nom as agence,
                    attr.employee_id, 
                    CONCAT(e.prenom, ' ', e.nom) as utilisateur
                FROM materiel_items i
                JOIN materiel_templates t ON i.template_id = t.id
                JOIN materiel_categories c ON t.categorie_id = c.id
                LEFT JOIN materiel_agences a ON i.agence_id = a.id
                LEFT JOIN materiel_attributions attr ON i.id = attr.item_id AND attr.etat_attribution = 'active'
                LEFT JOIN materiel_employees e ON attr.employee_id = e.id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Filtres
            if (!empty($filters['agence_id'])) {
                $sql .= " AND i.agence_id = :agence_id";
                $params['agence_id'] = $filters['agence_id'];
            }
            
            if (!empty($filters['categorie_id'])) {
                $sql .= " AND c.id = :categorie_id";
                $params['categorie_id'] = $filters['categorie_id'];
            }
            
            if (!empty($filters['statut'])) {
                $sql .= " AND i.statut = :statut";
                $params['statut'] = $filters['statut'];
            }
            
            if (!empty($filters['etat'])) {
                $sql .= " AND i.etat = :etat";
                $params['etat'] = $filters['etat'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (t.designation LIKE :search OR t.marque LIKE :search OR i.numero_serie LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            $sql .= " ORDER BY t.designation, i.numero_serie";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur récupération items filtrés: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mettre à jour le statut d'un item
     */
    public function updateItemStatus($item_id, $new_status) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE materiel_items 
                SET statut = :statut, updated_at = NOW()
                WHERE id = :item_id
            ");
            
            $stmt->bindParam(':statut', $new_status);
            $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour statut item: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recherche globale dans le matériel
     */
    public function searchMateriel($query, $limit = 20) {
        if (!$this->db || empty($query)) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    i.id, i.numero_serie, i.numero_inventaire, i.statut,
                    t.designation, t.marque, t.modele,
                    c.nom as categorie,
                    'item' as type
                FROM materiel_items i
                JOIN materiel_templates t ON i.template_id = t.id
                JOIN materiel_categories c ON t.categorie_id = c.id
                WHERE t.designation LIKE :query 
                   OR t.marque LIKE :query 
                   OR i.numero_serie LIKE :query
                   OR i.numero_inventaire LIKE :query
                ORDER BY t.designation
                LIMIT :limit
            ");
            
            $search_term = '%' . $query . '%';
            $stmt->bindParam(':query', $search_term);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur recherche matériel: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exporter les données en CSV
     */
    public function exportToCsv($type = 'inventory', $filters = []) {
        if (!$this->db) return false;
        
        try {
            switch ($type) {
                case 'inventory':
                    $data = $this->getItemsFiltered($filters);
                    $filename = 'inventaire_materiel_' . date('Y-m-d') . '.csv';
                    break;
                    
                case 'requests':
                    $data = $this->getAllDemandes($filters);
                    $filename = 'demandes_materiel_' . date('Y-m-d') . '.csv';
                    break;
                    
                default:
                    return false;
            }
            
            if (empty($data)) return false;
            
            // Headers pour téléchargement
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');
            
            $output = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fwrite($output, "\xEF\xBB\xBF");
            
            // En-têtes
            if ($type === 'inventory') {
                fputcsv($output, [
                    'ID', 'Désignation', 'Marque', 'Modèle', 'Catégorie',
                    'N° Série', 'N° Inventaire', 'Statut', 'État', 'Agence',
                    'Utilisateur', 'Date acquisition', 'Prix achat'
                ], ';');
                
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['id'],
                        $row['designation'],
                        $row['marque'],
                        $row['modele'],
                        $row['categorie'],
                        $row['numero_serie'],
                        $row['numero_inventaire'],
                        $row['statut'],
                        $row['etat'],
                        $row['agence'],
                        $row['utilisateur'],
                        $row['date_acquisition'],
                        $row['prix_achat']
                    ], ';');
                }
            }
            
            fclose($output);
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur export CSV: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Initialiser des données de test
     */
    public function initializeTestData() {
        if (!$this->db) return false;
        
        try {
            // Créer des catégories de base
            $categories = [
                ['nom' => 'Outillage électroportatif', 'type' => 'outillage', 'couleur' => '#3182ce'],
                ['nom' => 'EPI', 'type' => 'protection', 'couleur' => '#dc2626'],
                ['nom' => 'Outillage manuel', 'type' => 'outillage', 'couleur' => '#059669'],
                ['nom' => 'Véhicules', 'type' => 'vehicule', 'couleur' => '#d97706']
            ];
            
            foreach ($categories as $cat) {
                $stmt = $this->db->prepare("
                    INSERT IGNORE INTO materiel_categories (nom, type, couleur)
                    VALUES (:nom, :type, :couleur)
                ");
                $stmt->execute($cat);
            }
            
            // Créer une agence par défaut
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO materiel_agences (nom, adresse)
                VALUES ('Agence principale', 'Adresse par défaut')
            ");
            $stmt->execute();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur initialisation données test: " . $e->getMessage());
            return false;
        }
    }
}
?>

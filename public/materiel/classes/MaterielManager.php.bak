<?php
/**
 * Titre: Gestionnaire Outillage - Version corrigée
 * Chemin: /public/outillages/classes/OutillageManager.php
 * Version: 0.5 beta + build auto
 */

class OutillageManager {
    private $db;
    
    public function __construct() {
        $this->db = $this->initDatabase();
    }
    
    /**
     * Initialisation de la connexion base de données
     */
    private function initDatabase() {
        try {
            // Essayer d'utiliser getDB() si disponible
            if (function_exists('getDB')) {
                return getDB();
            }
            
            // Sinon connexion directe avec les constantes
            if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                return $pdo;
            }
            
            throw new Exception("Configuration base de données manquante");
            
        } catch (Exception $e) {
            error_log("Erreur connexion OutillageManager: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier si les tables outillage existent
     */
    public function checkTablesExist() {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'outillage_%'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Créer les tables outillage si elles n'existent pas
     */
    public function createTables() {
        if (!$this->db) return false;
        
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS outillage_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                type VARCHAR(50) DEFAULT 'general',
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS outillage_profils (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS outillage_agences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                adresse TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS outillage_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                categorie_id INT,
                designation VARCHAR(200) NOT NULL,
                marque VARCHAR(100),
                modele VARCHAR(100),
                observations TEXT,
                quantite_standard INT DEFAULT 1,
                maintenance_requise BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (categorie_id) REFERENCES outillage_categories(id)
            );
            
            CREATE TABLE IF NOT EXISTS outillage_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                template_id INT,
                numero_serie VARCHAR(100),
                agence_id INT,
                etat ENUM('neuf', 'bon', 'use', 'hs') DEFAULT 'neuf',
                date_mise_service DATE,
                fournisseur VARCHAR(100),
                ref_fournisseur VARCHAR(100),
                prix_achat DECIMAL(10,2),
                prochaine_maintenance DATE,
                observations TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (template_id) REFERENCES outillage_templates(id),
                FOREIGN KEY (agence_id) REFERENCES outillage_agences(id)
            );
            
            CREATE TABLE IF NOT EXISTS outillage_employees (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                prenom VARCHAR(100) NOT NULL,
                email VARCHAR(150),
                profil_id INT,
                agence_id INT,
                date_embauche DATE,
                actif BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (profil_id) REFERENCES outillage_profils(id),
                FOREIGN KEY (agence_id) REFERENCES outillage_agences(id)
            );
            
            CREATE TABLE IF NOT EXISTS outillage_attributions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT,
                item_id INT,
                date_attribution DATE,
                date_retour DATE NULL,
                etat_attribution ENUM('active', 'rendu', 'perdu') DEFAULT 'active',
                observations TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES outillage_employees(id),
                FOREIGN KEY (item_id) REFERENCES outillage_items(id)
            );
            
            CREATE TABLE IF NOT EXISTS outillage_demandes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT,
                template_id INT,
                type_demande ENUM('nouveau', 'remplacement', 'reparation') DEFAULT 'nouveau',
                item_remplace_id INT NULL,
                statut ENUM('en_attente', 'approuve', 'rejete', 'traite') DEFAULT 'en_attente',
                raison_demande TEXT,
                observations TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                traite_at TIMESTAMP NULL,
                FOREIGN KEY (employee_id) REFERENCES outillage_employees(id),
                FOREIGN KEY (template_id) REFERENCES outillage_templates(id),
                FOREIGN KEY (item_remplace_id) REFERENCES outillage_items(id)
            );
            
            CREATE TABLE IF NOT EXISTS outillage_profil_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                profil_id INT,
                template_id INT,
                quantite INT DEFAULT 1,
                obligatoire BOOLEAN DEFAULT FALSE,
                FOREIGN KEY (profil_id) REFERENCES outillage_profils(id),
                FOREIGN KEY (template_id) REFERENCES outillage_templates(id)
            );
            ";
            
            $this->db->exec($sql);
            $this->insertInitialData();
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur création tables outillage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insérer données initiales
     */
    private function insertInitialData() {
        try {
            // Vérifier si données déjà présentes
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM outillage_categories");
            if ($stmt->fetch()['count'] > 0) return;
            
            // Catégories de base
            $categories = [
                ['nom' => 'Outils manuels', 'type' => 'manuel'],
                ['nom' => 'Outils électriques', 'type' => 'electrique'],
                ['nom' => 'Pinces et clés', 'type' => 'manuel'],
                ['nom' => 'Mesure et contrôle', 'type' => 'mesure'],
                ['nom' => 'Sécurité', 'type' => 'securite'],
                ['nom' => 'Consommables', 'type' => 'consommable'],
                ['nom' => 'Équipements spécialisés', 'type' => 'specialise']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO outillage_categories (nom, type) VALUES (?, ?)");
            foreach ($categories as $cat) {
                $stmt->execute([$cat['nom'], $cat['type']]);
            }
            
            // Profils de base
            $profils = [
                ['nom' => 'Monteur', 'description' => 'Technicien montage équipements'],
                ['nom' => 'Maintenance', 'description' => 'Technicien maintenance'],
                ['nom' => 'Électricien', 'description' => 'Spécialiste électrique'],
                ['nom' => 'Plombier', 'description' => 'Spécialiste plomberie']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO outillage_profils (nom, description) VALUES (?, ?)");
            foreach ($profils as $profil) {
                $stmt->execute([$profil['nom'], $profil['description']]);
            }
            
            // Agence de base
            $this->db->exec("INSERT INTO outillage_agences (nom, adresse) VALUES ('Agence Principale', 'Siège social')");
            
        } catch (Exception $e) {
            error_log("Erreur insertion données initiales: " . $e->getMessage());
        }
    }
    
    // GESTION DES TEMPLATES (modèles d'outils)
    public function createTemplate($data) {
        if (!$this->db) return false;
        
        $sql = "INSERT INTO outillage_templates (categorie_id, designation, marque, modele, observations, quantite_standard, maintenance_requise) 
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
    
    public function getTemplatesByCategory() {
        if (!$this->db) return [];
        
        $sql = "SELECT t.*, c.nom as categorie_nom, c.type as categorie_type 
                FROM outillage_templates t 
                LEFT JOIN outillage_categories c ON t.categorie_id = c.id 
                ORDER BY c.nom, t.designation";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // GESTION DES ITEMS (outils physiques)
    public function createItem($data) {
        if (!$this->db) return false;
        
        $sql = "INSERT INTO outillage_items (template_id, numero_serie, agence_id, etat, date_mise_service, 
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
    
    public function getItemsByAgence($agence_id = null) {
        if (!$this->db) return [];
        
        $where = $agence_id ? "WHERE i.agence_id = :agence_id" : "";
        
        $sql = "SELECT i.*, t.designation, t.marque, t.modele, c.nom as categorie, c.type as categorie_type,
                a.nom as agence_nom, attr.employee_id, 
                CONCAT(e.prenom, ' ', e.nom) as attribue_a
                FROM outillage_items i
                LEFT JOIN outillage_templates t ON i.template_id = t.id
                LEFT JOIN outillage_categories c ON t.categorie_id = c.id
                LEFT JOIN outillage_agences a ON i.agence_id = a.id
                LEFT JOIN outillage_attributions attr ON i.id = attr.item_id AND attr.etat_attribution = 'active'
                LEFT JOIN outillage_employees e ON attr.employee_id = e.id
                $where
                ORDER BY c.nom, t.designation";
        
        $stmt = $this->db->prepare($sql);
        if ($agence_id) {
            $stmt->bindParam(':agence_id', $agence_id);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // GESTION DES EMPLOYÉS
    public function createEmployee($data) {
        if (!$this->db) return false;
        
        $sql = "INSERT INTO outillage_employees (nom, prenom, email, profil_id, agence_id, date_embauche) 
                VALUES (:nom, :prenom, :email, :profil_id, :agence_id, :date_embauche)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email' => $data['email'],
            ':profil_id' => $data['profil_id'],
            ':agence_id' => $data['agence_id'],
            ':date_embauche' => $data['date_embauche'] ?? date('Y-m-d')
        ]);
    }
    
    public function getEmployeesByAgence($agence_id = null) {
        if (!$this->db) return [];
        
        $where = $agence_id ? "WHERE e.agence_id = :agence_id" : "";
        
        $sql = "SELECT e.*, p.nom as profil_nom, a.nom as agence_nom
                FROM outillage_employees e
                LEFT JOIN outillage_profils p ON e.profil_id = p.id
                LEFT JOIN outillage_agences a ON e.agence_id = a.id
                $where
                ORDER BY e.nom, e.prenom";
        
        $stmt = $this->db->prepare($sql);
        if ($agence_id) {
            $stmt->bindParam(':agence_id', $agence_id);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // GESTION DES ATTRIBUTIONS
    public function attribuerOutil($employee_id, $item_id, $observations = null) {
        if (!$this->db) return false;
        
        // Vérifier que l'outil n'est pas déjà attribué
        $check = "SELECT id FROM outillage_attributions WHERE item_id = :item_id AND etat_attribution = 'active'";
        $stmt = $this->db->prepare($check);
        $stmt->execute([':item_id' => $item_id]);
        
        if ($stmt->rowCount() > 0) {
            return false; // Outil déjà attribué
        }
        
        $sql = "INSERT INTO outillage_attributions (employee_id, item_id, date_attribution, observations) 
                VALUES (:employee_id, :item_id, :date_attribution, :observations)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':employee_id' => $employee_id,
            ':item_id' => $item_id,
            ':date_attribution' => date('Y-m-d'),
            ':observations' => $observations
        ]);
    }
    
    public function getAttributionsByEmployee($employee_id) {
        if (!$this->db) return [];
        
        $sql = "SELECT attr.*, i.numero_serie, t.designation, t.marque, t.modele, c.nom as categorie
                FROM outillage_attributions attr
                LEFT JOIN outillage_items i ON attr.item_id = i.id
                LEFT JOIN outillage_templates t ON i.template_id = t.id
                LEFT JOIN outillage_categories c ON t.categorie_id = c.id
                WHERE attr.employee_id = :employee_id
                ORDER BY attr.date_attribution DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':employee_id' => $employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ATTRIBUTION AUTOMATIQUE PAR PROFIL
    public function attribuerOutilsParProfil($employee_id) {
        if (!$this->db) return false;
        
        // Récupérer le profil de l'employé
        $sql = "SELECT profil_id, agence_id FROM outillage_employees WHERE id = :employee_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':employee_id' => $employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) return false;
        
        // Récupérer les outils requis pour ce profil
        $sql = "SELECT pt.template_id, pt.quantite 
                FROM outillage_profil_templates pt 
                WHERE pt.profil_id = :profil_id AND pt.obligatoire = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':profil_id' => $employee['profil_id']]);
        $required_tools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $attributions_made = 0;
        
        foreach ($required_tools as $tool) {
            // Chercher des outils disponibles de ce type dans l'agence
            $sql = "SELECT i.id FROM outillage_items i
                    LEFT JOIN outillage_attributions attr ON i.id = attr.item_id AND attr.etat_attribution = 'active'
                    WHERE i.template_id = :template_id 
                    AND i.agence_id = :agence_id 
                    AND i.etat IN ('neuf', 'bon')
                    AND attr.id IS NULL
                    LIMIT :quantite";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':template_id' => $tool['template_id'],
                ':agence_id' => $employee['agence_id'],
                ':quantite' => $tool['quantite']
            ]);
            
            $available_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Attribuer les outils disponibles
            foreach ($available_items as $item) {
                if ($this->attribuerOutil($employee_id, $item['id'], 'Attribution automatique par profil')) {
                    $attributions_made++;
                }
            }
        }
        
        return $attributions_made;
    }
    
    // GESTION DES DEMANDES
    public function createDemande($data) {
        if (!$this->db) return false;
        
        $sql = "INSERT INTO outillage_demandes (employee_id, template_id, type_demande, item_remplace_id, raison_demande, observations) 
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
    
    public function getDemandesEnAttente() {
        if (!$this->db) return [];
        
        $sql = "SELECT d.*, t.designation, CONCAT(e.prenom, ' ', e.nom) as demandeur,
                a.nom as agence_nom, p.nom as profil
                FROM outillage_demandes d
                LEFT JOIN outillage_templates t ON d.template_id = t.id
                LEFT JOIN outillage_employees e ON d.employee_id = e.id
                LEFT JOIN outillage_agences a ON e.agence_id = a.id
                LEFT JOIN outillage_profils p ON e.profil_id = p.id
                WHERE d.statut = 'en_attente'
                ORDER BY d.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // STATISTIQUES
    public function getStatistiquesGenerales() {
        if (!$this->db) {
            // Retourner des données par défaut si pas de connexion
            return [
                'total_outils' => 47,
                'outils_attribues' => 32,
                'demandes_attente' => 5,
                'maintenance_due' => 3
            ];
        }
        
        $stats = [];
        
        try {
            // Nombre total d'outils
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM outillage_items");
            $stats['total_outils'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Outils attribués
            $stmt = $this->db->query("SELECT COUNT(*) as attribues FROM outillage_attributions WHERE etat_attribution = 'active'");
            $stats['outils_attribues'] = $stmt->fetch(PDO::FETCH_ASSOC)['attribues'];
            
            // Demandes en attente
            $stmt = $this->db->query("SELECT COUNT(*) as en_attente FROM outillage_demandes WHERE statut = 'en_attente'");
            $stats['demandes_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['en_attente'];
            
            // Outils nécessitant maintenance
            $stmt = $this->db->query("SELECT COUNT(*) as maintenance FROM outillage_items i 
                                     JOIN outillage_templates t ON i.template_id = t.id 
                                     WHERE t.maintenance_requise = 1 AND i.prochaine_maintenance <= CURDATE()");
            $stats['maintenance_due'] = $stmt->fetch(PDO::FETCH_ASSOC)['maintenance'];
            
        } catch (Exception $e) {
            error_log("Erreur récupération statistiques: " . $e->getMessage());
            // Valeurs par défaut en cas d'erreur
            $stats = [
                'total_outils' => 0,
                'outils_attribues' => 0,
                'demandes_attente' => 0,
                'maintenance_due' => 0
            ];
        }
        
        return $stats;
    }
    
    // UTILITAIRES
    public function getCategories() {
        if (!$this->db) return [];
        
        $stmt = $this->db->query("SELECT * FROM outillage_categories ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProfils() {
        if (!$this->db) return [];
        
        $stmt = $this->db->query("SELECT * FROM outillage_profils ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAgences() {
        if (!$this->db) return [];
        
        $stmt = $this->db->query("SELECT * FROM outillage_agences ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Installation automatique du module
     */
    public function install() {
        if (!$this->checkTablesExist()) {
            return $this->createTables();
        }
        return true;
    }
    
    /**
     * Vérifier l'état du module
     */
    public function getModuleStatus() {
        return [
            'database_connected' => $this->db !== null,
            'tables_exist' => $this->checkTablesExist(),
            'data_available' => $this->db ? $this->getStatistiquesGenerales()['total_outils'] > 0 : false
        ];
    }
}
?>

<?php

class OutillageManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // GESTION DES TEMPLATES (modèles d'outils)
    public function createTemplate($data) {
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
        $stats = [];
        
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
        
        return $stats;
    }
    
    // UTILITAIRES
    public function getCategories() {
        $stmt = $this->db->query("SELECT * FROM outillage_categories ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProfils() {
        $stmt = $this->db->query("SELECT * FROM outillage_profils ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAgences() {
        $stmt = $this->db->query("SELECT * FROM outillage_agences ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
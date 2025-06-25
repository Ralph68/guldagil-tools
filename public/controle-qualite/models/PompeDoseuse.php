<?php
// /public/controle-qualite/models/PompeDoseuse.php

class PompeDoseuse {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($controle_id, $data) {
        try {
            $sql = "INSERT INTO gul_controle_pompes 
                    (controle_id, marque, modele, ref_gul, numero_serie, debit_maxi, cylindree_maxi, equipements, documentation, compteur)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $controle_id,
                $data['marque'] ?? '',
                $data['modele'] ?? '',
                $data['ref_gul'] ?? '',
                $data['numero_serie'] ?? '',
                $data['debit_maxi'] ?? null,
                $data['cylindree_maxi'] ?? null,
                json_encode($this->extractEquipements($data)),
                json_encode($this->extractDocumentation($data)),
                json_encode($this->extractCompteur($data))
            ]);
        } catch (Exception $e) {
            error_log("Erreur création pompe: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByControleId($controle_id) {
        try {
            $sql = "SELECT * FROM gul_controle_pompes WHERE controle_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$controle_id]);
            
            $pompe = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($pompe) {
                $pompe['equipements'] = json_decode($pompe['equipements'] ?? '{}', true);
                $pompe['documentation'] = json_decode($pompe['documentation'] ?? '{}', true);
                $pompe['compteur'] = json_decode($pompe['compteur'] ?? '{}', true);
            }
            
            return $pompe;
        } catch (Exception $e) {
            error_log("Erreur récupération pompe: " . $e->getMessage());
            return false;
        }
    }
    
    private function extractEquipements($data) {
        $equipements = [];
        $fields = [
            'socle_plastique', 'connecteur_compteur_3vis', 'connecteur_moule_4broches',
            'raccords_pompes', 'canne_injection_pvdf', 'crepine_aspiration_pvdf',
            'contact_niveau', 'connecteur_2vis_niveau', 'tuyau_souple_transparent',
            'tuyau_semi_rigide_opaque', 'vis_plastique_4'
        ];
        
        foreach ($fields as $field) {
            $equipements[$field] = isset($data[$field]) ? 1 : 0;
        }
        
        return $equipements;
    }
    
    private function extractDocumentation($data) {
        $documentation = [];
        $fields = [
            'doc_technaevo_em136081', 'doc_technaevo_em136060', 'notice_grundfos_v2',
            'doc_commercial_dos6', 'doc_commercial_dos4_8v'
        ];
        
        foreach ($fields as $field) {
            $documentation[$field] = isset($data[$field]) ? 1 : 0;
        }
        
        return $documentation;
    }
    
    private function extractCompteur($data) {
        return [
            'present' => isset($data['compteur_present']) ? 1 : 0,
            'numero_serie' => $data['compteur_numero_serie'] ?? '',
            'ref_gul' => $data['compteur_ref_gul'] ?? '',
            'type' => $data['compteur_type'] ?? '',
            'diametre' => $data['compteur_diametre'] ?? '',
            'k_cteur' => $data['compteur_k_cteur'] ?? '',
            'doc_guldagil' => isset($data['doc_compteur_guldagil']) ? 1 : 0
        ];
    }
    
    public function getEquipementsList() {
        return [
            'socle_plastique' => 'Socle Plastique',
            'connecteur_compteur_3vis' => 'Connecteur Compteur 3 vis vert (seko)',
            'connecteur_moule_4broches' => 'Connecteur moulé à 4 broches (DDE)',
            'raccords_pompes' => 'Raccords de pompes',
            'canne_injection_pvdf' => 'Canne d\'injection PVDF (Blanche)',
            'crepine_aspiration_pvdf' => 'Crépine d\'aspiration PVDF (Blanche)',
            'contact_niveau' => '1 Contact de niveau',
            'connecteur_2vis_niveau' => 'Connecteur 2 vis pour contact de niveau vert (seko)',
            'tuyau_souple_transparent' => 'Tuyau souple transparent ≥ 2 mètres',
            'tuyau_semi_rigide_opaque' => 'Tuyau semi rigide opaque ≥ 5 mètres',
            'vis_plastique_4' => '4 vis plastique'
        ];
    }
    
    public function getDocumentationList() {
        return [
            'doc_technaevo_em136081' => 'Doc instructions Technaevo EM00136081',
            'doc_technaevo_em136060' => 'Doc instructions Technaevo EM00136060',
            'notice_grundfos_v2' => 'Notice d\'installation DDE 6-10 grundfos V2',
            'doc_commercial_dos6' => 'Doc commerciale DOS 6 DDE',
            'doc_commercial_dos4_8v' => 'Doc commerciale DOS 4-8V'
        ];
    }
}

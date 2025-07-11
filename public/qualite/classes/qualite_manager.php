<?php
/**
 * Titre: Classe principale du module Contrôle Qualité
 * Chemin: /features/qualite/classes/qualite_manager.php
 * Version: 0.5 beta + build auto
 */

class QualiteManager
{
    private PDO $db;
    private array $config;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->config = [
            'pdf_output_path' => __DIR__ . '/../storage/pdfs/',
            'temp_path' => __DIR__ . '/../storage/temp/',
            'email_enabled' => true,
            'auto_numbering' => true,
            'validation_strict' => true
        ];
    }

    // ========== GESTION DES TYPES D'ÉQUIPEMENTS ==========
    
    /**
     * Récupère tous les types d'équipements actifs
     */
    public function getEquipmentTypes(): array
    {
        $sql = "SELECT * FROM cq_equipment_types WHERE active = 1 ORDER BY category, type_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les types d'équipements par catégorie
     */
    public function getEquipmentTypesByCategory(string $category): array
    {
        $sql = "SELECT * FROM cq_equipment_types WHERE category = ? AND active = 1 ORDER BY type_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les modèles pour un type d'équipement donné
     */
    public function getEquipmentModels(int $equipmentTypeId): array
    {
        $sql = "SELECT * FROM cq_equipment_models WHERE equipment_type_id = ? AND active = 1 ORDER BY model_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$equipmentTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les données techniques d'un modèle
     */
    public function getModelSpecs(int $modelId): ?array
    {
        $sql = "SELECT technical_specs, default_settings FROM cq_equipment_models WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$modelId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return [
                'technical_specs' => json_decode($result['technical_specs'], true) ?? [],
                'default_settings' => json_decode($result['default_settings'], true) ?? []
            ];
        }
        return null;
    }

    // ========== GESTION DES CONTRÔLES ==========
    
    /**
     * Génère un nouveau numéro de contrôle unique
     */
    public function generateControlNumber(int $equipmentTypeId): string
    {
        try {
            $sql = "CALL sp_generate_control_number(?, @control_number)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$equipmentTypeId]);
            
            $result = $this->db->query("SELECT @control_number as control_number")->fetch();
            return $result['control_number'] ?? $this->generateFallbackControlNumber($equipmentTypeId);
        } catch (Exception $e) {
            return $this->generateFallbackControlNumber($equipmentTypeId);
        }
    }

    /**
     * Génère un numéro de contrôle de secours
     */
    private function generateFallbackControlNumber(int $equipmentTypeId): string
    {
        $typeCode = $this->getEquipmentTypeCode($equipmentTypeId);
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $time = date('His');
        
        return "{$typeCode}_{$year}{$month}{$day}_{$time}";
    }

    /**
     * Récupère le code d'un type d'équipement
     */
    private function getEquipmentTypeCode(int $equipmentTypeId): string
    {
        $sql = "SELECT type_code FROM cq_equipment_types WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$equipmentTypeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['type_code'] ?? 'UNKNOWN';
    }

    /**
     * Crée un nouveau contrôle qualité
     */
    public function createQualityControl(array $data): int
    {
        $controlNumber = $this->generateControlNumber($data['equipment_type_id']);
        
        $sql = "INSERT INTO cq_quality_controls (
                    control_number, equipment_type_id, equipment_model_id,
                    agency_code, dossier_number, arc_number, installation_name,
                    serial_number, technical_data, settings_data, 
                    prepared_by, prepared_date, observations, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $controlNumber,
            $data['equipment_type_id'],
            $data['equipment_model_id'] ?? null,
            $data['agency_code'],
            $data['dossier_number'],
            $data['arc_number'],
            $data['installation_name'],
            $data['serial_number'] ?? '',
            json_encode($data['technical_data'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($data['settings_data'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['prepared_by'],
            $data['prepared_date'] ?? date('Y-m-d'),
            $data['observations'] ?? '',
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour un contrôle qualité
     */
    public function updateQualityControl(int $controlId, array $data): bool
    {
        $sql = "UPDATE cq_quality_controls SET 
                    equipment_model_id = ?,
                    agency_code = ?, dossier_number = ?, arc_number = ?, 
                    installation_name = ?, serial_number = ?,
                    technical_data = ?, settings_data = ?,
                    observations = ?, status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['equipment_model_id'] ?? null,
            $data['agency_code'],
            $data['dossier_number'],
            $data['arc_number'],
            $data['installation_name'],
            $data['serial_number'] ?? '',
            json_encode($data['technical_data'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($data['settings_data'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['observations'] ?? '',
            $data['status'] ?? 'in_progress',
            $controlId
        ]);
    }

    /**
     * Récupère un contrôle par ID
     */
    public function getQualityControl(int $controlId): ?array
    {
        $sql = "SELECT qc.*, et.type_name, et.type_code, em.model_name, a.agency_name
                FROM cq_quality_controls qc
                JOIN cq_equipment_types et ON qc.equipment_type_id = et.id
                LEFT JOIN cq_equipment_models em ON qc.equipment_model_id = em.id
                LEFT JOIN cq_agencies a ON qc.agency_code = a.agency_code
                WHERE qc.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$controlId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $result['technical_data'] = json_decode($result['technical_data'], true) ?? [];
            $result['settings_data'] = json_decode($result['settings_data'], true) ?? [];
        }
        
        return $result ?: null;
    }

    /**
     * Récupère la liste des contrôles avec filtres
     */
    public function getQualityControls(array $filters = []): array
    {
        $sql = "SELECT * FROM v_controls_active WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['agency_code'])) {
            $sql .= " AND agency_code = ?";
            $params[] = $filters['agency_code'];
        }
        
        if (!empty($filters['equipment_type'])) {
            $sql .= " AND equipment_type LIKE ?";
            $params[] = '%' . $filters['equipment_type'] . '%';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ========== VALIDATION ==========
    
    /**
     * Valide les données d'un contrôle
     */
    public function validateControlData(array $data, string $equipmentType): array
    {
        $errors = [];
        $warnings = [];
        
        // Validations communes
        $errors = array_merge($errors, $this->validateCommonFields($data));
        
        // Validations spécifiques par type d'équipement
        if (strpos($equipmentType, 'ADOU') !== false) {
            $errors = array_merge($errors, $this->validateAdoucisseurData($data));
        } elseif (strpos($equipmentType, 'POMPE') !== false) {
            $errors = array_merge($errors, $this->validatePompeData($data));
        }
        
        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validations communes à tous les équipements
     */
    private function validateCommonFields(array $data): array
    {
        $errors = [];
        
        $requiredFields = [
            'agency_code' => 'Code agence',
            'dossier_number' => 'Numéro de dossier',
            'arc_number' => 'Numéro ARC',
            'installation_name' => 'Nom de l\'installation',
            'prepared_by' => 'Préparé par'
        ];
        
        foreach ($requiredFields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = "Le champ '$label' est obligatoire";
            }
        }
        
        return $errors;
    }

    /**
     * Validations spécifiques pour les adoucisseurs

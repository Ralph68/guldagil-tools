<?php
/**
 * Titre: API Données équipements et modèles
 * Chemin: /public/qualite/api/equipment.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: application/json; charset=UTF-8');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT_PATH . '/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'types':
            echo json_encode(getEquipmentTypes($pdo));
            break;
            
        case 'models':
            $type_id = (int)($_GET['type_id'] ?? 0);
            echo json_encode(getEquipmentModels($pdo, $type_id));
            break;
            
        case 'specs':
            $model_id = (int)($_GET['model_id'] ?? 0);
            echo json_encode(getModelSpecs($pdo, $model_id));
            break;
            
        case 'form_config':
            $type_id = (int)($_GET['type_id'] ?? 0);
            echo json_encode(getFormConfig($pdo, $type_id));
            break;
            
        case 'agencies':
            echo json_encode(getAgencies($pdo));
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }

} catch (Exception $e) {
    error_log("Erreur API equipment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

function getEquipmentTypes(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM cq_equipment_types WHERE active = 1 ORDER BY category, type_name");
    $types = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $types
    ];
}

function getEquipmentModels(PDO $pdo, int $type_id): array {
    if ($type_id <= 0) {
        return ['success' => false, 'message' => 'ID type requis'];
    }
    
    $stmt = $pdo->prepare("
        SELECT id, model_code, model_name, manufacturer, technical_specs, default_settings
        FROM cq_equipment_models 
        WHERE equipment_type_id = ? AND active = 1 
        ORDER BY model_name
    ");
    $stmt->execute([$type_id]);
    $models = $stmt->fetchAll();
    
    // Décoder JSON
    foreach ($models as &$model) {
        $model['technical_specs'] = json_decode($model['technical_specs'] ?? '{}', true);
        $model['default_settings'] = json_decode($model['default_settings'] ?? '{}', true);
    }
    
    return [
        'success' => true,
        'data' => $models
    ];
}

function getModelSpecs(PDO $pdo, int $model_id): array {
    if ($model_id <= 0) {
        return ['success' => false, 'message' => 'ID modèle requis'];
    }
    
    $stmt = $pdo->prepare("
        SELECT em.*, et.type_name, et.category
        FROM cq_equipment_models em
        JOIN cq_equipment_types et ON em.equipment_type_id = et.id
        WHERE em.id = ? AND em.active = 1
    ");
    $stmt->execute([$model_id]);
    $model = $stmt->fetch();
    
    if (!$model) {
        return ['success' => false, 'message' => 'Modèle non trouvé'];
    }
    
    // Décoder données JSON
    $model['technical_specs'] = json_decode($model['technical_specs'] ?? '{}', true);
    $model['default_settings'] = json_decode($model['default_settings'] ?? '{}', true);
    $model['documentation_refs'] = json_decode($model['documentation_refs'] ?? '{}', true);
    
    return [
        'success' => true,
        'data' => $model
    ];
}

function getFormConfig(PDO $pdo, int $type_id): array {
    if ($type_id <= 0) {
        return ['success' => false, 'message' => 'ID type requis'];
    }
    
    // Récupérer template de formulaire
    $stmt = $pdo->prepare("
        SELECT form_config, validation_rules
        FROM cq_form_templates 
        WHERE equipment_type_id = ? AND active = 1
        ORDER BY version DESC 
        LIMIT 1
    ");
    $stmt->execute([$type_id]);
    $template = $stmt->fetch();
    
    if (!$template) {
        // Configuration par défaut selon type
        $default_config = getDefaultFormConfig($type_id, $pdo);
        return [
            'success' => true,
            'data' => $default_config,
            'source' => 'default'
        ];
    }
    
    return [
        'success' => true,
        'data' => [
            'form_config' => json_decode($template['form_config'], true),
            'validation_rules' => json_decode($template['validation_rules'] ?? '{}', true)
        ],
        'source' => 'template'
    ];
}

function getDefaultFormConfig(int $type_id, PDO $pdo): array {
    // Récupérer le type pour déterminer la config
    $stmt = $pdo->prepare("SELECT type_code, category FROM cq_equipment_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $type = $stmt->fetch();
    
    if (!$type) {
        return ['form_config' => [], 'validation_rules' => []];
    }
    
    // Configurations par défaut selon type
    switch ($type['type_code']) {
        case 'ADOUC': // Adoucisseur
            return [
                'form_config' => [
                    'sections' => [
                        [
                            'section_id' => 'water_analysis',
                            'title' => 'Analyse de l\'eau',
                            'fields' => [
                                ['field_id' => 'raw_water_hardness', 'label' => 'TH eau brute (°f)', 'type' => 'number', 'required' => true],
                                ['field_id' => 'target_hardness', 'label' => 'TH à obtenir (°f)', 'type' => 'number', 'required' => true],
                                ['field_id' => 'daily_consumption_total', 'label' => 'Consommation journalière totale (m³)', 'type' => 'number', 'required' => true],
                                ['field_id' => 'daily_consumption_0f', 'label' => 'Consommation à 0°F (m³)', 'type' => 'number', 'required' => true]
                            ]
                        ],
                        [
                            'section_id' => 'equipment_sizing',
                            'title' => 'Dimensionnement équipement',
                            'fields' => [
                                ['field_id' => 'resin_volume', 'label' => 'Volume résine (L)', 'type' => 'number', 'required' => true],
                                ['field_id' => 'flow_rate', 'label' => 'Débit nominal (m³/h)', 'type' => 'number', 'required' => true],
                                ['field_id' => 'salt_consumption', 'label' => 'Consommation sel (kg/régén)', 'type' => 'number', 'required' => true]
                            ]
                        ]
                    ]
                ],
                'validation_rules' => [
                    ['rule' => 'target_hardness < raw_water_hardness', 'message' => 'Le TH à obtenir doit être inférieur au TH eau brute'],
                    ['rule' => 'daily_consumption_0f <= daily_consumption_total', 'message' => 'Consommation 0°F ≤ consommation totale']
                ]
            ];
            
        case 'POMPE_DOS': // Pompe doseuse
            return [
                'form_config' => [
                    'sections' => [
                        [
                            'section_id' => 'hydraulic_data',
                            'title' => 'Données hydrauliques',
                            'fields' => [
                                ['field_id' => 'flow_rate', 'label' => 'Débit nominal (L/h)', 'type' => 'number', 'required' => true],
                                ['field_id' => 'max_pressure', 'label' => 'Pression max (bar)', 'type' => 'number', 'required' => true],
                                ['field_id' => 'suction_height', 'label' => 'Hauteur aspiration (m)', 'type' => 'number', 'required' => true]
                            ]
                        ],
                        [
                            'section_id' => 'chemical_compatibility',
                            'title' => 'Compatibilité produit',
                            'fields' => [
                                ['field_id' => 'product_type', 'label' => 'Type de produit', 'type' => 'select', 'required' => true],
                                ['field_id' => 'concentration', 'label' => 'Concentration (%)', 'type' => 'number', 'required' => true],
                                ['field_id' => 'temperature', 'label' => 'Température (°C)', 'type' => 'number', 'required' => true]
                            ]
                        ]
                    ]
                ],
                'validation_rules' => [
                    ['rule' => 'concentration <= 100', 'message' => 'Concentration max 100%'],
                    ['rule' => 'temperature >= -10 && temperature <= 60', 'message' => 'Température entre -10°C et 60°C']
                ]
            ];
            
        default:
            return [
                'form_config' => [
                    'sections' => [
                        [
                            'section_id' => 'general',
                            'title' => 'Paramètres généraux',
                            'fields' => [
                                ['field_id' => 'capacity', 'label' => 'Capacité', 'type' => 'text', 'required' => true],
                                ['field_id' => 'power', 'label' => 'Puissance', 'type' => 'text', 'required' => false]
                            ]
                        ]
                    ]
                ],
                'validation_rules' => []
            ];
    }
}

function getAgencies(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT agency_code, agency_name, email, contact_person
        FROM cq_agencies 
        WHERE active = 1 
        ORDER BY agency_name
    ");
    $agencies = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $agencies
    ];
}
?>

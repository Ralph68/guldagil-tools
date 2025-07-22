<?php
/**
 * Titre: API Création de contrôle qualité
 * Chemin: /public/qualite/api/create.php
 * Version: 0.5 beta + build auto
 */

// Configuration sécurité
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Chemins et config
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/public/qualite/classes/qualite_manager.php';

// Session et auth
session_start();

// Auth temporaire - À remplacer par AuthManager
$user_authenticated = true;
$current_user = [
    'id' => 1,
    'username' => 'TestUser',
    'role' => 'logistique',
    'name' => 'Contrôleur Qualité'
];

if (!$user_authenticated || !in_array($current_user['role'], ['admin', 'dev', 'logistique', 'resp_materiel'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

try {
    // Connexion BDD
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $qualiteManager = new QualiteManager($pdo);

    // Validation données
    $errors = [];
    
    // Champs obligatoires
    $required_fields = ['equipment_type', 'agency_code', 'prepared_by'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Le champ {$field} est obligatoire";
        }
    }

    // Validation format email agence
    if (!empty($_POST['agency_code']) && !preg_match('/^[A-Z]{2,4}$/', $_POST['agency_code'])) {
        $errors[] = "Code agence invalide";
    }

    // Validation numéro série
    if (!empty($_POST['serial_number']) && strlen($_POST['serial_number']) > 50) {
        $errors[] = "Numéro de série trop long (max 50 caractères)";
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Récupération type équipement
    $equipment_type_id = getEquipmentTypeId($_POST['equipment_type'], $pdo);
    if (!$equipment_type_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type d\'équipement invalide']);
        exit;
    }

    // Récupération modèle équipement
    $equipment_model_id = null;
    if (!empty($_POST['equipment_model'])) {
        $equipment_model_id = getEquipmentModelId($_POST['equipment_model'], $equipment_type_id, $pdo);
    }

    // Préparation données techniques
    $technical_data = prepareTechnicalData($_POST['technical'] ?? []);
    $settings_data = prepareSettingsData($_POST['settings'] ?? []);
    $quality_checks = prepareQualityChecks($_POST['quality_checks'] ?? []);

    // Données pour insertion
    $control_data = [
        'equipment_type_id' => $equipment_type_id,
        'equipment_model_id' => $equipment_model_id,
        'agency_code' => sanitizeString($_POST['agency_code']),
        'dossier_number' => sanitizeString($_POST['dossier_number'] ?? ''),
        'arc_number' => sanitizeString($_POST['arc_number'] ?? ''),
        'installation_name' => sanitizeString($_POST['installation_name'] ?? ''),
        'serial_number' => sanitizeString($_POST['serial_number'] ?? ''),
        'technical_data' => array_merge($technical_data, ['quality_checks' => $quality_checks]),
        'settings_data' => $settings_data,
        'prepared_by' => sanitizeString($_POST['prepared_by']),
        'prepared_date' => validateDate($_POST['prepared_date'] ?? date('Y-m-d')),
        'observations' => sanitizeString($_POST['observations'] ?? ''),
        'final_status' => sanitizeString($_POST['final_status'] ?? 'draft')
    ];

    // Transaction
    $pdo->beginTransaction();

    try {
        // Création du contrôle
        $control_id = $qualiteManager->createQualityControl($control_data);

        // Historique de création
        $pdo->prepare("INSERT INTO cq_control_history (control_id, action, user_name) VALUES (?, 'created', ?)")
            ->execute([$control_id, $current_user['name']]);

        // Mise à jour statut selon décision finale
        $status = determineStatus($_POST['final_status'] ?? 'draft');
        if ($status !== 'draft') {
            $pdo->prepare("UPDATE cq_quality_controls SET status = ? WHERE id = ?")
                ->execute([$status, $control_id]);
                
            $pdo->prepare("INSERT INTO cq_control_history (control_id, action, new_value, user_name) VALUES (?, 'modified', ?, ?)")
                ->execute([$control_id, "Statut: {$status}", $current_user['name']]);
        }

        // Si conforme, marquer comme validé
        if ($_POST['final_status'] === 'conforme') {
            $pdo->prepare("UPDATE cq_quality_controls SET status = 'validated', validated_by = ?, validated_date = NOW() WHERE id = ?")
                ->execute([$current_user['name'], $control_id]);
                
            $pdo->prepare("INSERT INTO cq_control_history (control_id, action, user_name) VALUES (?, 'validated', ?)")
                ->execute([$control_id, $current_user['name']]);
        }

        $pdo->commit();

        // Génération PDF si contrôle terminé
        $pdf_generated = false;
        if (in_array($status, ['completed', 'validated'])) {
            try {
                $pdf_generated = generateControlPDF($control_id, $qualiteManager);
            } catch (Exception $e) {
                error_log("Erreur génération PDF: " . $e->getMessage());
            }
        }

        // Réponse succès
        echo json_encode([
            'success' => true,
            'message' => 'Contrôle créé avec succès',
            'control_id' => $control_id,
            'status' => $status,
            'pdf_generated' => $pdf_generated,
            'redirect_url' => "/qualite/view.php?id={$control_id}"
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Erreur BDD create control: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
} catch (Exception $e) {
    error_log("Erreur create control: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}

// =====================================
// FONCTIONS UTILITAIRES
// =====================================

function getEquipmentTypeId(string $type_key, PDO $pdo): ?int {
    $type_mapping = [
        'pompe_doseuse' => 'POMPE_DOS',
        'adoucisseur' => 'ADOUC'
    ];
    
    $type_code = $type_mapping[$type_key] ?? null;
    if (!$type_code) return null;
    
    $stmt = $pdo->prepare("SELECT id FROM cq_equipment_types WHERE type_code = ? AND active = 1");
    $stmt->execute([$type_code]);
    $result = $stmt->fetch();
    
    return $result['id'] ?? null;
}

function getEquipmentModelId(string $model_key, int $type_id, PDO $pdo): ?int {
    $stmt = $pdo->prepare("SELECT id FROM cq_equipment_models WHERE model_code = ? AND equipment_type_id = ? AND active = 1");
    $stmt->execute([$model_key, $type_id]);
    $result = $stmt->fetch();
    
    return $result['id'] ?? null;
}

function prepareTechnicalData(array $technical): array {
    $sanitized = [];
    foreach ($technical as $key => $value) {
        if (is_numeric($value)) {
            $sanitized[$key] = (float)$value;
        } else {
            $sanitized[$key] = sanitizeString($value);
        }
    }
    return $sanitized;
}

function prepareSettingsData(array $settings): array {
    $sanitized = [];
    foreach ($settings as $key => $value) {
        $sanitized[$key] = sanitizeString($value);
    }
    return $sanitized;
}

function prepareQualityChecks(array $checks): array {
    $results = [];
    foreach ($checks as $check => $value) {
        $results[$check] = [
            'checked' => !empty($value),
            'timestamp' => date('Y-m-d H:i:s'),
            'verified_by' => $GLOBALS['current_user']['name'] ?? 'Unknown'
        ];
    }
    
    // Ajouter résultats commentaires
    if (!empty($_POST['control_results'])) {
        $results['_comments'] = sanitizeString($_POST['control_results']);
    }
    
    return $results;
}

function sanitizeString(string $input): string {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function validateDate(string $date): string {
    $datetime = DateTime::createFromFormat('Y-m-d', $date);
    if ($datetime && $datetime->format('Y-m-d') === $date) {
        return $date;
    }
    return date('Y-m-d');
}

function determineStatus(string $final_status): string {
    switch ($final_status) {
        case 'conforme':
            return 'completed';
        case 'non_conforme':
            return 'in_progress';
        case 'en_attente':
            return 'draft';
        default:
            return 'draft';
    }
}

function generateControlPDF(int $control_id, QualiteManager $manager): bool {
    try {
        // Récupérer données contrôle
        $control = $manager->getQualityControl($control_id);
        if (!$control) return false;

        // Génération PDF simple (à améliorer avec vraie lib PDF)
        $pdf_content = generateSimplePDF($control);
        
        // Sauvegarde fichier
        $pdf_dir = ROOT_PATH . '/storage/pdfs/qualite/';
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0755, true);
        }
        
        $pdf_filename = "controle_{$control['control_number']}.pdf";
        $pdf_path = $pdf_dir . $pdf_filename;
        
        // Simulation génération PDF (remplacer par vraie génération)
        file_put_contents($pdf_path, $pdf_content);
        
        // Mise à jour BDD
        $pdo = $GLOBALS['pdo'] ?? null;
        if ($pdo) {
            $pdo->prepare("UPDATE cq_quality_controls SET pdf_generated = 1, pdf_path = ? WHERE id = ?")
                ->execute([$pdf_filename, $control_id]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur génération PDF: " . $e->getMessage());
        return false;
    }
}

function generateSimplePDF(array $control): string {
    // Génération PDF basique - À remplacer par vraie bibliothèque PDF
    $content = "=== RAPPORT DE CONTROLE QUALITE ===\n\n";
    $content .= "N° Contrôle: {$control['control_number']}\n";
    $content .= "Date: " . date('d/m/Y') . "\n";
    $content .= "Type: {$control['equipment_type']}\n";
    $content .= "Modèle: {$control['equipment_model']}\n";
    $content .= "Agence: {$control['agency_code']}\n";
    $content .= "Contrôlé par: {$control['prepared_by']}\n\n";
    
    if (!empty($control['technical_data'])) {
        $technical = json_decode($control['technical_data'], true);
        $content .= "=== DONNEES TECHNIQUES ===\n";
        foreach ($technical as $key => $value) {
            if ($key !== 'quality_checks') {
                $content .= "{$key}: {$value}\n";
            }
        }
        $content .= "\n";
    }
    
    $content .= "=== STATUT ===\n";
    $content .= "Statut: {$control['status']}\n";
    
    if (!empty($control['observations'])) {
        $content .= "\n=== OBSERVATIONS ===\n";
        $content .= $control['observations'] . "\n";
    }
    
    $content .= "\n--- Rapport généré automatiquement le " . date('d/m/Y H:i') . " ---";
    
    return $content;
}
?>

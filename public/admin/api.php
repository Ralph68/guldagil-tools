<?php
// public/admin/api.php
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../lib/Transport.php';

header('Content-Type: application/json; charset=UTF-8');
session_start();

$transport = new Transport($db);
$response = ['success' => false, 'message' => '', 'data' => null];

// Récupérer l'action depuis POST ou GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // =============================================================================
        // GESTION DES TARIFS
        // =============================================================================
        
        case 'get_rates':
            $carrier = $_GET['carrier'] ?? '';
            $search = $_GET['search'] ?? '';
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $rates = getRates($db, $carrier, $search, $limit, $offset);
            $response = ['success' => true, 'data' => $rates];
            break;
            
        case 'save_rate':
            $rateData = [
                'id' => $_POST['id'] ?? null,
                'carrier' => $_POST['carrier'] ?? '',
                'department' => $_POST['department'] ?? '',
                'tarif_0_9' => !empty($_POST['tarif_0_9']) ? (float)$_POST['tarif_0_9'] : null,
                'tarif_10_19' => !empty($_POST['tarif_10_19']) ? (float)$_POST['tarif_10_19'] : null,
                'tarif_90_99' => !empty($_POST['tarif_90_99']) ? (float)$_POST['tarif_90_99'] : null,
                'tarif_100_299' => !empty($_POST['tarif_100_299']) ? (float)$_POST['tarif_100_299'] : null,
                'tarif_500_999' => !empty($_POST['tarif_500_999']) ? (float)$_POST['tarif_500_999'] : null,
                'delais' => $_POST['delais'] ?? ''
            ];
            
            if (saveRate($db, $rateData)) {
                $response = ['success' => true, 'message' => 'Tarif enregistré avec succès'];
            } else {
                throw new Exception('Erreur lors de l\'enregistrement du tarif');
            }
            break;
            
        case 'delete_rate':
            $id = (int)($_POST['id'] ?? 0);
            $carrier = $_POST['carrier'] ?? '';
            
            if (deleteRate($db, $id, $carrier)) {
                $response = ['success' => true, 'message' => 'Tarif supprimé'];
            } else {
                throw new Exception('Erreur lors de la suppression du tarif');
            }
            break;
            
        // =============================================================================
        // GESTION DES OPTIONS
        // =============================================================================
        
        case 'get_options':
            $carrier = $_GET['carrier'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $options = getOptions($db, $carrier, $search);
            $response = ['success' => true, 'data' => $options];
            break;
            
        case 'save_option':
            $optionData = [
                'id' => $_POST['id'] ?? null,
                'transporteur' => $_POST['transporteur'] ?? '',
                'code_option' => $_POST['code_option'] ?? '',
                'libelle' => $_POST['libelle'] ?? '',
                'montant' => (float)($_POST['montant'] ?? 0),
                'unite' => $_POST['unite'] ?? 'forfait',
                'actif' => isset($_POST['actif']) ? 1 : 0
            ];
            
            if (saveOption($db, $optionData)) {
                $response = ['success' => true, 'message' => 'Option enregistrée avec succès'];
            } else {
                throw new Exception('Erreur lors de l\'enregistrement de l\'option');
            }
            break;
            
        case 'toggle_option':
            $id = (int)($_POST['id'] ?? 0);
            
            if (toggleOption($db, $id)) {
                $response = ['success' => true, 'message' => 'Statut de l\'option modifié'];
            } else {
                throw new Exception('Erreur lors de la modification du statut');
            }
            break;
            
        case 'delete_option':
            $id = (int)($_POST['id'] ?? 0);
            
            if (deleteOption($db, $id)) {
                $response = ['success' => true, 'message' => 'Option supprimée'];
            } else {
                throw new Exception('Erreur lors de la suppression de l\'option');
            }
            break;
            
        // =============================================================================
        // GESTION DES TAXES
        // =============================================================================
        
        case 'get_taxes':
            $taxes = getTaxes($db);
            $response = ['success' => true, 'data' => $taxes];
            break;
            
        case 'save_taxes':
            // TODO: Implémenter la sauvegarde des taxes
            $response = ['success' => true, 'message' => 'Taxes mises à jour'];
            break;
            
        // =============================================================================
        // IMPORT/EXPORT
        // =============================================================================
        
        case 'import':
            if (!isset($_FILES['import_file'])) {
                throw new Exception('Aucun fichier sélectionné');
            }
            
            $file = $_FILES['import_file'];
            $result = importFile($db, $file);
            $response = ['success' => true, 'message' => 'Import terminé', 'data' => $result];
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

// =============================================================================
// FONCTIONS POUR LES TARIFS
// =============================================================================

function getRates($db, $carrier = '', $search = '', $limit = 50, $offset = 0) {
    $carriers = [
        'heppner' => ['table' => 'gul_heppner_rates', 'name' => 'Heppner'],
        'xpo' => ['table' => 'gul_xpo_rates', 'name' => 'XPO'],
        'kn' => ['table' => 'gul_kn_rates', 'name' => 'Kuehne + Nagel']
    ];
    
    $rates = [];
    
    foreach ($carriers as $code => $info) {
        if ($carrier && $carrier !== $code) continue;
        
        $sql = "SELECT * FROM {$info['table']} WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (num_departement LIKE :search OR departement LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY num_departement LIMIT $limit OFFSET $offset";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['carrier_code'] = $code;
            $row['carrier_name'] = $info['name'];
            $rates[] = $row;
        }
    }
    
    return $rates;
}

function saveRate($db, $data) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $table = $tables[$data['carrier']] ?? null;
    if (!$table) {
        throw new Exception('Transporteur invalide');
    }
    
    if ($data['id']) {
        // Mise à jour
        $sql = "UPDATE `$table` SET 
                num_departement = :department,
                tarif_0_9 = :tarif_0_9,
                tarif_10_19 = :tarif_10_19,
                tarif_90_99 = :tarif_90_99,
                tarif_100_299 = :tarif_100_299,
                tarif_500_999 = :tarif_500_999,
                delais = :delais
                WHERE id = :id";
        
        $params = [
            ':id' => $data['id'],
            ':department' => $data['department'],
            ':tarif_0_9' => $data['tarif_0_9'],
            ':tarif_10_19' => $data['tarif_10_19'],
            ':tarif_90_99' => $data['tarif_90_99'],
            ':tarif_100_299' => $data['tarif_100_299'],
            ':tarif_500_999' => $data['tarif_500_999'],
            ':delais' => $data['delais']
        ];
    } else {
        // Insertion
        $sql = "INSERT INTO `$table` 
                (num_departement, tarif_0_9, tarif_10_19, tarif_90_99, tarif_100_299, tarif_500_999, delais)
                VALUES (:department, :tarif_0_9, :tarif_10_19, :tarif_90_99, :tarif_100_299, :tarif_500_999, :delais)";
        
        $params = [
            ':department' => $data['department'],
            ':tarif_0_9' => $data['tarif_0_9'],
            ':tarif_10_19' => $data['tarif_10_19'],
            ':tarif_90_99' => $data['tarif_90_99'],
            ':tarif_100_299' => $data['tarif_100_299'],
            ':tarif_500_999' => $data['tarif_500_999'],
            ':delais' => $data['delais']
        ];
    }
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function deleteRate($db, $id, $carrier) {
    $tables = [
        'heppner' => 'gul_heppner_rates',
        'xpo' => 'gul_xpo_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    $table = $tables[$carrier] ?? null;
    if (!$table) {
        throw new Exception('Transporteur invalide');
    }
    
    $sql = "DELETE FROM `$table` WHERE id = :id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

// =============================================================================
// FONCTIONS POUR LES OPTIONS
// =============================================================================

function getOptions($db, $carrier = '', $search = '') {
    $sql = "SELECT * FROM gul_options_supplementaires WHERE 1=1";
    $params = [];
    
    if ($carrier) {
        $sql .= " AND transporteur = :carrier";
        $params[':carrier'] = $carrier;
    }
    
    if ($search) {
        $sql .= " AND (code_option LIKE :search OR libelle LIKE :search)";
        $params[':search

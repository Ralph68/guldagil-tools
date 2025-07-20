<?php
/**
 * Titre: API Gestion des demandes de matériel
 * Chemin: /public/outillages/api/requests.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuration
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';

// Vérification authentification
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$current_user = $_SESSION['user'] ?? ['id' => 0, 'username' => 'unknown', 'role' => 'guest'];
$user_role = $current_user['role'] ?? 'guest';

// Connexion BDD
try {
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
    exit;
}

// Récupération données POST
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

// Fonctions utilitaires
function sendEmail($to, $subject, $message) {
    // TODO: Implémenter envoi email réel
    error_log("EMAIL TO: $to | SUBJECT: $subject | MESSAGE: $message");
    return true;
}

function getUserById($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM outillage_employees WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getRequestById($db, $request_id) {
    $stmt = $db->prepare("
        SELECT d.*, t.designation, t.marque, t.modele,
               CONCAT(e.prenom, ' ', e.nom) as demandeur, e.email as demandeur_email,
               a.nom as agence_nom, p.nom as profil_nom
        FROM outillage_demandes d
        LEFT JOIN outillage_templates t ON d.template_id = t.id
        LEFT JOIN outillage_employees e ON d.employee_id = e.id
        LEFT JOIN outillage_agences a ON e.agence_id = a.id
        LEFT JOIN outillage_profils p ON e.profil_id = p.id
        WHERE d.id = ?
    ");
    $stmt->execute([$request_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Actions selon le type
switch ($action) {
    
    // =====================================
    // CRÉER UNE NOUVELLE DEMANDE
    // =====================================
    case 'create':
        try {
            $type_demande = $input['type_demande'] ?? '';
            $equipement = $input['equipement'] ?? '';
            $raison = $input['raison'] ?? '';
            
            if (empty($type_demande) || empty($equipement)) {
                throw new Exception('Données manquantes');
            }
            
            // Trouver l'employé correspondant au user connecté
            $stmt = $db->prepare("SELECT id FROM outillage_employees WHERE email = ? OR nom LIKE ?");
            $stmt->execute([$current_user['email'] ?? '', '%' . $current_user['username'] . '%']);
            $employee = $stmt->fetch();
            
            if (!$employee) {
                throw new Exception('Profil employé non trouvé');
            }
            
            // Chercher ou créer le template d'équipement
            $stmt = $db->prepare("SELECT id FROM outillage_templates WHERE designation LIKE ?");
            $stmt->execute(['%' . $equipement . '%']);
            $template = $stmt->fetch();
            
            if (!$template) {
                // Créer un nouveau template temporaire
                $stmt = $db->prepare("INSERT INTO outillage_templates (categorie_id, designation, observations) VALUES (7, ?, 'Créé automatiquement via demande')");
                $stmt->execute([$equipement]);
                $template_id = $db->lastInsertId();
            } else {
                $template_id = $template['id'];
            }
            
            // Créer la demande
            $stmt = $db->prepare("
                INSERT INTO outillage_demandes (employee_id, template_id, type_demande, raison_demande, statut, created_at) 
                VALUES (?, ?, ?, ?, 'en_attente', NOW())
            ");
            $stmt->execute([$employee['id'], $template_id, $type_demande, $raison]);
            $demande_id = $db->lastInsertId();
            
            // Email notification aux responsables
            $stmt = $db->query("SELECT email FROM auth_users WHERE role IN ('admin', 'dev') AND email IS NOT NULL");
            $responsables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($responsables as $email) {
                sendEmail(
                    $email,
                    "Nouvelle demande de matériel #$demande_id",
                    "Nouvelle demande de {$current_user['username']}: $equipement ($type_demande)\nRaison: $raison\n\nÀ traiter sur le portail."
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Demande créée avec succès',
                'demande_id' => $demande_id
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // =====================================
    // VALIDER UNE DEMANDE
    // =====================================
    case 'approve':
        if (!in_array($user_role, ['admin', 'dev'])) {
            echo json_encode(['success' => false, 'error' => 'Droits insuffisants']);
            break;
        }
        
        try {
            $request_id = $input['id'] ?? 0;
            $observations = $input['observations'] ?? '';
            
            if (!$request_id) {
                throw new Exception('ID demande manquant');
            }
            
            // Récupérer les détails de la demande
            $request = getRequestById($db, $request_id);
            if (!$request) {
                throw new Exception('Demande non trouvée');
            }
            
            // Mettre à jour le statut
            $stmt = $db->prepare("
                UPDATE outillage_demandes 
                SET statut = 'validee', date_validation = NOW(), validee_par = ?, observations = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $current_user['id'] ?? 0,
                $observations,
                $request_id
            ]);
            
            // Email de confirmation au demandeur
            if ($request['demandeur_email']) {
                sendEmail(
                    $request['demandeur_email'],
                    "Demande validée #$request_id",
                    "Votre demande pour '{$request['designation']}' a été validée.\n\nLe matériel sera mis à disposition prochainement.\n\nObservations: $observations"
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Demande validée avec succès'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // =====================================
    // REFUSER UNE DEMANDE
    // =====================================
    case 'reject':
        if (!in_array($user_role, ['admin', 'dev'])) {
            echo json_encode(['success' => false, 'error' => 'Droits insuffisants']);
            break;
        }
        
        try {
            $request_id = $input['id'] ?? 0;
            $reason = $input['reason'] ?? 'Aucune raison spécifiée';
            
            if (!$request_id) {
                throw new Exception('ID demande manquant');
            }
            
            $request = getRequestById($db, $request_id);
            if (!$request) {
                throw new Exception('Demande non trouvée');
            }
            
            // Mettre à jour le statut
            $stmt = $db->prepare("
                UPDATE outillage_demandes 
                SET statut = 'rejetee', date_validation = NOW(), validee_par = ?, observations = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $current_user['id'] ?? 0,
                "REFUSÉ: $reason",
                $request_id
            ]);
            
            // Email de refus au demandeur
            if ($request['demandeur_email']) {
                sendEmail(
                    $request['demandeur_email'],
                    "Demande refusée #$request_id",
                    "Votre demande pour '{$request['designation']}' a été refusée.\n\nRaison: $reason\n\nVous pouvez faire une nouvelle demande si nécessaire."
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Demande refusée'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // =====================================
    // MARQUER COMME LIVRÉ
    // =====================================
    case 'deliver':
        if (!in_array($user_role, ['admin', 'dev'])) {
            echo json_encode(['success' => false, 'error' => 'Droits insuffisants']);
            break;
        }
        
        try {
            $request_id = $input['id'] ?? 0;
            $item_id = $input['item_id'] ?? null;
            $notes = $input['notes'] ?? '';
            
            if (!$request_id) {
                throw new Exception('ID demande manquant');
            }
            
            $request = getRequestById($db, $request_id);
            if (!$request) {
                throw new Exception('Demande non trouvée');
            }
            
            // Marquer comme traitée
            $stmt = $db->prepare("
                UPDATE outillage_demandes 
                SET statut = 'traitee', observations = CONCAT(COALESCE(observations, ''), '\n[LIVRÉ] ', ?)
                WHERE id = ?
            ");
            $stmt->execute([$notes, $request_id]);
            
            // Si un item spécifique est livré, créer l'attribution
            if ($item_id) {
                $stmt = $db->prepare("
                    INSERT INTO outillage_attributions (employee_id, item_id, date_attribution, observations, etat_attribution) 
                    VALUES (?, ?, NOW(), ?, 'active')
                ");
                $stmt->execute([
                    $request['employee_id'], 
                    $item_id, 
                    "Attribution suite demande #$request_id"
                ]);
            }
            
            // Email de livraison (avec demande d'AR)
            if ($request['demandeur_email']) {
                sendEmail(
                    $request['demandeur_email'],
                    "Matériel livré - AR requis #$request_id",
                    "Votre matériel '{$request['designation']}' a été mis à disposition.\n\n⚠️ MERCI DE CONFIRMER LA RÉCEPTION sur le portail.\n\nNotes: $notes"
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Livraison enregistrée'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // =====================================
    // ACCUSÉ DE RÉCEPTION
    // =====================================
    case 'acknowledge_receipt':
        try {
            $request_id = $input['id'] ?? 0;
            $received = $input['received'] ?? false;
            $comments = $input['comments'] ?? '';
            
            if (!$request_id) {
                throw new Exception('ID demande manquant');
            }
            
            $request = getRequestById($db, $request_id);
            if (!$request) {
                throw new Exception('Demande non trouvée');
            }
            
            // Vérifier que c'est bien le demandeur
            $stmt = $db->prepare("SELECT id FROM outillage_employees WHERE email = ? OR nom LIKE ?");
            $stmt->execute([$current_user['email'] ?? '', '%' . $current_user['username'] . '%']);
            $employee = $stmt->fetch();
            
            if (!$employee || $employee['id'] != $request['employee_id']) {
                throw new Exception('Vous ne pouvez accuser réception que de vos propres demandes');
            }
            
            // Mettre à jour avec AR
            $ar_status = $received ? 'REÇU' : 'NON-REÇU';
            $stmt = $db->prepare("
                UPDATE outillage_demandes 
                SET observations = CONCAT(COALESCE(observations, ''), '\n[AR] ', ?, ' - ', ?)
                WHERE id = ?
            ");
            $stmt->execute([$ar_status, $comments, $request_id]);
            
            // Notifier les responsables
            $stmt = $db->query("SELECT email FROM auth_users WHERE role IN ('admin', 'dev') AND email IS NOT NULL");
            $responsables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($responsables as $email) {
                sendEmail(
                    $email,
                    "AR matériel #$request_id - $ar_status",
                    "Accusé de réception de {$request['demandeur']}: $ar_status\n\nMatériel: {$request['designation']}\nCommentaires: $comments"
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Accusé de réception enregistré'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // =====================================
    // LISTER LES DEMANDES
    // =====================================
    case 'list':
        try {
            $filters = $input['filters'] ?? [];
            $limit = min($input['limit'] ?? 50, 100);
            $offset = max($input['offset'] ?? 0, 0);
            
            $where_clauses = [];
            $params = [];
            
            // Filtres
            if (!empty($filters['status'])) {
                $where_clauses[] = "d.statut = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['employee_id'])) {
                $where_clauses[] = "d.employee_id = ?";
                $params[] = $filters['employee_id'];
            }
            
            if (!empty($filters['agence_id'])) {
                $where_clauses[] = "e.agence_id = ?";
                $params[] = $filters['agence_id'];
            }
            
            $where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);
            
            $stmt = $db->prepare("
                SELECT d.*, t.designation, t.marque, t.modele,
                       CONCAT(e.prenom, ' ', e.nom) as demandeur,
                       a.nom as agence_nom, p.nom as profil_nom,
                       DATEDIFF(NOW(), d.created_at) as jours_attente
                FROM outillage_demandes d
                LEFT JOIN outillage_templates t ON d.template_id = t.id
                LEFT JOIN outillage_employees e ON d.employee_id = e.id
                LEFT JOIN outillage_agences a ON e.agence_id = a.id
                LEFT JOIN outillage_profils p ON e.profil_id = p.id
                $where_sql
                ORDER BY d.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt->execute($params);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'requests' => $requests,
                'count' => count($requests)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // =====================================
    // RECHERCHE MATÉRIEL
    // =====================================
    case 'search_equipment':
        try {
            $query = $input['query'] ?? '';
            $category = $input['category'] ?? '';
            $agence_id = $input['agence_id'] ?? '';
            
            if (strlen($query) < 2) {
                throw new Exception('Requête trop courte');
            }
            
            $where_clauses = ["(t.designation LIKE ? OR t.marque LIKE ? OR t.modele LIKE ?)"];
            $params = ["%$query%", "%$query%", "%$query%"];
            
            if ($category) {
                $where_clauses[] = "c.type = ?";
                $params[] = $category;
            }
            
            if ($agence_id) {
                $where_clauses[] = "i.agence_id = ?";
                $params[] = $agence_id;
            }
            
            $where_sql = implode(' AND ', $where_clauses);
            
            $stmt = $db->prepare("
                SELECT i.*, t.designation, t.marque, t.modele, c.nom as categorie,
                       a.nom as agence_nom, attr.employee_id,
                       CONCAT(e.prenom, ' ', e.nom) as attribue_a
                FROM outillage_items i
                LEFT JOIN outillage_templates t ON i.template_id = t.id
                LEFT JOIN outillage_categories c ON t.categorie_id = c.id
                LEFT JOIN outillage_agences a ON i.agence_id = a.id
                LEFT JOIN outillage_attributions attr ON i.id = attr.item_id AND attr.etat_attribution = 'active'
                LEFT JOIN outillage_employees e ON attr.employee_id = e.id
                WHERE $where_sql
                ORDER BY t.designation
                LIMIT 20
            ");
            
            $stmt->execute($params);
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'equipment' => $equipment,
                'count' => count($equipment)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
        break;
}
?>

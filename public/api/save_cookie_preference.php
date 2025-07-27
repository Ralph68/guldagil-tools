<?php
/**
 * Endpoint API pour sauvegarder les préférences de cookies en base de données
 * Chemin: /api/save_cookie_preference.php
 */

// Protection contre l'accès direct sans session
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Récupérer l'ID utilisateur
$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
    exit;
}

// Vérifier que la requête est en POST et contient des données JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['preference']) || !in_array($data['preference'], ['accepted', 'minimal'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Préférence invalide']);
    exit;
}

// Définir le chemin vers le fichier de configuration
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config/database.php';

try {
    // Mettre à jour la préférence de cookies dans la table auth_users
    $stmt = $db->prepare("UPDATE auth_users SET cookie_preference = :preference, cookie_preference_date = NOW() WHERE id = :user_id");
    $success = $stmt->execute([
        'preference' => $data['preference'],
        'user_id' => $user_id
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Préférence sauvegardée avec succès']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
    }
} catch (PDOException $e) {
    // En cas d'erreur, il se peut que les colonnes n'existent pas encore
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur base de données. Les colonnes nécessaires existent-elles?',
        'error' => $e->getMessage()
    ]);
}

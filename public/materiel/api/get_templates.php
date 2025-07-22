<?php
/**
 * Titre: API pour récupération des templates par catégorie
 * Chemin: /public/materiel/api/get_templates.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT_PATH . '/config/config.php';
require_once dirname(__DIR__) . '/classes/MaterielManager.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des paramètres
$category_id = (int)($_GET['category_id'] ?? 0);

if (!$category_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID catégorie requis']);
    exit;
}

try {
    // Connexion BDD
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Manager matériel
    $materielManager = new MaterielManager($db);
    
    // Récupération des templates
    $templates = $materielManager->getTemplatesByCategory($category_id);
    
    echo json_encode([
        'success' => true,
        'templates' => $templates,
        'count' => count($templates)
    ]);
    
} catch (Exception $e) {
    error_log("Erreur API get_templates: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur'
    ]);
}
?>

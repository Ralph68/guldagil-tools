<?php
/**
 * Titre: Module Matériel - Index avec vues par rôle
 * Chemin: /public/materiel/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once ROOT_PATH . '/config/modules.php';

// Variables pour template
$page_title = 'Gestion du Matériel';
$page_subtitle = 'Outillage et Équipements';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// Vérification authentification
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = $current_user['role'] ?? 'guest';
$module_data = $modules['materiel'] ?? ['status' => 'development', 'name' => 'Matériel'];

if (!canAccessModule('materiel', $module_data, $user_role)) {
    header('Location: ../../index.php?error=access_denied');
    exit;
}

// Connexion BDD
try {
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_connected = true;
} catch (Exception $e) {
    error_log("Erreur BDD Matériel: " . $e->getMessage());
    $db_connected = false;
}

// Configuration des rôles et permissions
$permissions = [
    'user' => [
        'view_my_equipment' => true,
        'create_request' => true,
        'view_inventory' => false,
        'manage_equipment' => false,
        'validate_requests' => false,
        'view_all_stats' => false
    ],
    'logistique' => [
        'view_my_equipment' => true,
        'create_request' => true,
        'view_inventory' => true,
        'manage_equipment' => false,
        'validate_requests' => false,
        'view_all_stats' => true
    ],
    'admin' => [
        'view_my_equipment' => true,
        'create_request' => true,
        'view_inventory' => true,
        'manage_equipment' => true,
        'validate_requests' => true,
        'view_all_stats' => true
    ],
    'dev' => [
        'view_my_equipment' => true,
        'create_request' => true,
        'view_inventory' => true,
        'manage_equipment' => true,
        'validate_requests' => true,
        'view_all_stats' => true
    ]
];

$user_permissions = $permissions[$user_role] ?? $permissions['user'];

// Auto-installation des tables si nécessaire
if ($db_connected) {
    try {
        // Vérifier si les tables existent
        $stmt = $db->query("SHOW TABLES LIKE 'materiel_%'");
        $tables = $stmt->fetchAll();
        
        if (count($tables) === 0) {
            // Auto-installation des tables matériel
            $sql_file = __DIR__ . '/sql/create_tables.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                // Remplacer outillage_ par materiel_ dans le SQL
                $sql = str_replace('outillage_', 'materiel_', $sql);
                $db->exec($sql);
                error_log("Tables matériel installées automatiquement");
            }
        }
    } catch (Exception $e) {
        error_log("Erreur installation tables matériel: " . $e->getMessage());
    }
}

// Redirection vers dashboard par défaut
header('Location: ./dashboard.php');
exit;
?>

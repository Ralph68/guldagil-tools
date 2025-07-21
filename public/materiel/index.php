<?php
/**
 * Titre: Module Matériel - Index complet restauré
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

// Fonction de vérification d'accès module
if (!function_exists('canAccessModule')) {
    function canAccessModule($module_id, $module, $user_role) {
        if ($module['status'] === 'development' && !in_array($user_role, ['admin', 'dev'])) {
            return false;
        }
        if (isset($module['access_roles']) && !in_array($user_role, $module['access_roles'])) {
            return false;
        }
        return true;
    }
}

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
                
                // Message de succès en session
                $_SESSION['materiel_install_success'] = true;
            }
        }
    } catch (Exception $e) {
        error_log("Erreur installation tables matériel: " . $e->getMessage());
        $_SESSION['materiel_install_error'] = $e->getMessage();
    }
}

// Chargement du MaterielManager
require_once __DIR__ . '/classes/MaterielManager.php';
$materielManager = $db_connected ? new MaterielManager($db) : null;

// Vérification du statut du module
$module_status = $materielManager ? $materielManager->getModuleStatus() : [
    'database_connected' => false,
    'tables_exist' => false,
    'data_available' => false
];

// Routage interne du module selon l'action demandée
$action = $_GET['action'] ?? 'dashboard';
$view = $_GET['view'] ?? '';

// Actions autorisées
$allowed_actions = [
    'dashboard' => 'dashboard.php',
    'inventory' => 'inventory/index.php',
    'requests' => 'requests/index.php',
    'admin' => 'admin/index.php',
    'reports' => 'reports/index.php'
];

// Vérification des permissions pour l'action
$can_access_action = true;
switch ($action) {
    case 'admin':
        $can_access_action = $user_permissions['manage_equipment'];
        break;
    case 'inventory':
        $can_access_action = $user_permissions['view_inventory'];
        break;
    case 'reports':
        $can_access_action = $user_permissions['view_all_stats'];
        break;
}

if (!$can_access_action) {
    $action = 'dashboard'; // Fallback vers dashboard
}

// Redirection simple vers dashboard (pas d'include)
header('Location: ./dashboard.php');
exit;

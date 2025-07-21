<?php
/**
 * Titre: Module Matériel - Index simplifié
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

// Chargement config minimal
require_once ROOT_PATH . '/config/config.php';

// Vérification authentification simple
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Auto-installation rapide des tables
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Vérifier tables matériel
    $stmt = $db->query("SHOW TABLES LIKE 'materiel_%'");
    $tables = $stmt->fetchAll();
    
    if (count($tables) === 0) {
        $sql_file = __DIR__ . '/sql/create_tables.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $sql = str_replace('outillage_', 'materiel_', $sql);
            $db->exec($sql);
        }
    }
} catch (Exception $e) {
    error_log("Erreur matériel: " . $e->getMessage());
}

// Redirection immédiate vers dashboard
header('Location: ./dashboard.php');
exit;

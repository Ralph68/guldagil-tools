<?php
/**
 * Titre: Point d'entrée principal du portail
 * Chemin: /public/index.php
 */

// Sécurité et configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration principale
require_once __DIR__ . '/../config/app.php';

// Session
session_start();

// Démarrage de l'application
try {
    $app = new App($config);
    $app->run();
} catch (Exception $e) {
    // Log l'erreur
    error_log("Erreur App: " . $e->getMessage());
    
    if ($config['app']['debug'] ?? false) {
        die('<h1>❌ Erreur Application</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
    } else {
        http_response_code(500);
        require_once __DIR__ . '/../templates/error.php';
    }
}

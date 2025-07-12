<?php
/**
 * Script de diagnostic pour AuthManager
 * Placez ce fichier dans public/auth/debug.php
 */

// Activer le mode debug
define('DEBUG', true);

// Configuration de l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialisation
try {
    require_once __DIR__ . '/../../core/auth/AuthManager.php';
    $auth = AuthManager::getInstance();
    
    // Test de la connexion à la base de données
    echo "Test de connexion à la base de données...\n";
    $auth->initDatabase();
    echo "✓ Connexion à la base de données réussie\n";
    
    // Test de la session
    echo "\nTest de la session...\n";
    $auth->initSession();
    echo "✓ Session initialisée\n";
    
    // Test de la validation d'un utilisateur
    echo "\nTest de la validation...\n";
    $result = $auth->login('test', 'test');
    print_r($result);
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
}

// Afficher les derniers logs d'erreur
echo "\nDerniers logs d'erreur :\n";
$logs = file('/var/log/apache2/error.log');
foreach (array_slice($logs, -10) as $log) {
    echo $log;
}

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

// Afficher la structure des fichiers
echo "=== Structure des fichiers ===\n";
$rootPath = dirname(__DIR__, 3);
echo "Répertoire racine : " . $rootPath . "\n";

// Vérifier l'existence des fichiers
$authManagerPath = $rootPath . '/core/auth/AuthManager.php';
echo "\nChemin AuthManager.php : " . $authManagerPath . "\n";
echo "Existe ? " . (file_exists($authManagerPath) ? "Oui" : "Non") . "\n";

// Afficher les permissions
echo "\nPermissions des fichiers :\n";
echo "debug.php : " . substr(sprintf('%o', fileperms(__FILE__)), -4) . "\n";
echo "AuthManager.php : " . (file_exists($authManagerPath) ? substr(sprintf('%o', fileperms($authManagerPath)), -4) : "Non trouvé") . "\n";

// Afficher le contenu du répertoire core/auth
echo "\nContenu du répertoire core/auth :\n";
$authDir = $rootPath . '/core/auth/';
if (is_dir($authDir)) {
    $files = scandir($authDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- " . $file . "\n";
        }
    }
} else {
    echo "Le répertoire n'existe pas\n";
}

// Tentative d'inclusion
try {
    require_once $authManagerPath;
    echo "\n✓ AuthManager.php inclus avec succès\n";
    
    // Vérification que la classe est bien définie
    if (!class_exists('AuthManager')) {
        throw new Exception('Classe AuthManager non trouvée');
    }
    
    echo "✓ Classe AuthManager trouvée\n";
    
    // Test de la connexion à la base de données
    echo "\nTest de connexion à la base de données...\n";
    $auth = AuthManager::getInstance();
    $auth->initDatabase();
    echo "✓ Connexion à la base de données réussie\n";
    
} catch (Exception $e) {
    echo "\nErreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
}

// Afficher les derniers logs d'erreur
echo "\nDerniers logs d'erreur :\n";
$logs = file('/var/log/apache2/error.log');
foreach (array_slice($logs, -10) as $log) {
    echo $log;
}

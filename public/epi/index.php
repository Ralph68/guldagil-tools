<?php
/**
 * Titre: Proxy EPI - Redirection vers module features
 * Chemin: /public/epi/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration de sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification des chemins
$features_path = __DIR__ . '/../../features/epi/index.php';
$config_path = __DIR__ . '/../../config/version.php';

// Validation de l'existence des fichiers critiques
if (!file_exists($features_path)) {
    http_response_code(404);
    die('<h1>❌ Module EPI Non Trouvé</h1><p>Le module EPI n\'est pas disponible à l\'emplacement attendu.</p>');
}

if (!file_exists($config_path)) {
    http_response_code(500);
    die('<h1>❌ Configuration Manquante</h1><p>Fichier de configuration version.php introuvable.</p>');
}

// Démarrage de session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logging de l'accès (optionnel en mode debug)
if (defined('DEBUG') && DEBUG) {
    error_log("Accès module EPI via proxy: " . $_SERVER['REQUEST_URI'] . " - IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']));
}

// Gestion des paramètres de requête
$query_string = $_SERVER['QUERY_STRING'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Variables d'environnement pour le module
$_ENV['EPI_ACCESSED_VIA_PROXY'] = true;
$_ENV['EPI_PROXY_PATH'] = '/public/epi/';
$_ENV['EPI_ORIGINAL_URI'] = $_SERVER['REQUEST_URI'] ?? '';

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Header pour identification du proxy
header('X-EPI-Proxy: 1.0');

try {
    // Inclusion sécurisée du module EPI
    require_once $features_path;
    
} catch (Exception $e) {
    // Gestion des erreurs avec logging
    error_log("Erreur proxy EPI: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    
    // Affichage d'erreur selon l'environnement
    if (defined('DEBUG') && DEBUG) {
        echo '<h1>❌ Erreur Module EPI</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Fichier:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Ligne:</strong> ' . $e->getLine() . '</p>';
        echo '<details><summary>Stack Trace</summary><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></details>';
    } else {
        echo '<h1>❌ Service Temporairement Indisponible</h1>';
        echo '<p>Le module EPI rencontre actuellement des difficultés techniques.</p>';
        echo '<p><a href="/">← Retour à l\'accueil</a></p>';
    }
    
} catch (ParseError $e) {
    // Erreur de syntaxe PHP
    error_log("Erreur de syntaxe module EPI: " . $e->getMessage());
    
    http_response_code(500);
    echo '<h1>❌ Erreur de Configuration</h1>';
    echo '<p>Le module EPI présente un problème de configuration.</p>';
    echo '<p><a href="/">← Retour à l\'accueil</a></p>';
    
} catch (Error $e) {
    // Erreur fatale PHP
    error_log("Erreur fatale module EPI: " . $e->getMessage());
    
    http_response_code(500);
    echo '<h1>❌ Erreur Système</h1>';
    echo '<p>Une erreur système empêche le chargement du module EPI.</p>';
    echo '<p><a href="/">← Retour à l\'accueil</a></p>';
}
?>

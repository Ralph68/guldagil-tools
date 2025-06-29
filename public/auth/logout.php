<?php
/**
 * Titre: Page de déconnexion sécurisée
 * Chemin: /public/auth/logout.php
 * Version: 0.5 beta + build auto
 */

// Démarrer session pour pouvoir la détruire
session_start();

// Si système auth avancé disponible
if (file_exists(__DIR__ . '/../../core/auth/AuthManager.php')) {
    require_once __DIR__ . '/../../core/auth/AuthManager.php';
    $auth = AuthManager::getInstance();
    
    if ($auth->isAuthenticated()) {
        $auth->logout('manual');
    }
}

// Nettoyage complet de la session
$_SESSION = array();

// Détruire cookie de session si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire session
session_destroy();

// Régénérer ID session pour sécurité
session_start();
session_regenerate_id(true);

// Headers sécurité
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Redirection vers login avec message
header('Location: /auth/login.php?msg=disconnected');
exit;
?>

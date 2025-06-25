<?php
// /public/controle-qualite/index.php
// Routeur principal du module

require_once '../config/config.php';
require_once '../config/version.php';
require_once 'config.php';

// Routage simple
$controller = $_GET['controller'] ?? 'accueil';
$action = $_GET['action'] ?? 'index';

// Sécurité : liste blanche des contrôleurs
$controllers_autorisés = ['accueil', 'pompe-doseuse', 'recherche', 'admin'];

if (!in_array($controller, $controllers_autorisés)) {
    $controller = 'accueil';
}

// Charger le contrôleur
$controller_file = "controllers/" . ucfirst($controller) . "Controller.php";

if (file_exists($controller_file)) {
    require_once $controller_file;
    $controller_class = ucfirst($controller) . "Controller";
    
    if (class_exists($controller_class)) {
        $ctrl = new $controller_class($pdo);
        
        // Exécuter l'action
        if (method_exists($ctrl, $action)) {
            $ctrl->$action();
        } else {
            $ctrl->index();
        }
    } else {
        // Erreur contrôleur
        header('Location: index.php');
        exit;
    }
} else {
    // Contrôleur par défaut
    require_once 'controllers/AccueilController.php';
    $ctrl = new AccueilController($pdo);
    $ctrl->index();
}

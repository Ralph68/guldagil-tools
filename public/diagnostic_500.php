<?php
/**
 * Fichier de diagnostic pour le portail Guldagil
 * Placez ce fichier dans /public/diagnostic_500.php et ouvrez-le dans le navigateur pour obtenir un rapport détaillé.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

function checkFile($file) {
    if (!file_exists($file)) {
        return "<span style='color:red'>❌ Fichier manquant :</span> $file";
    }
    if (!is_readable($file)) {
        return "<span style='color:orange'>⚠️ Non lisible :</span> $file";
    }
    return "<span style='color:green'>✅ OK :</span> $file";
}

function checkClass($class) {
    return class_exists($class) ? "<span style='color:green'>✅ Classe chargée :</span> $class" : "<span style='color:red'>❌ Classe absente :</span> $class";
}

function checkFunction($function) {
    return function_exists($function) ? "<span style='color:green'>✅ Fonction :</span> $function" : "<span style='color:red'>❌ Fonction absente :</span> $function";
}

function checkDB() {
    try {
        $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->query('SELECT 1');
        return "<span style='color:green'>✅ Connexion DB OK</span>";
    } catch(Exception $e) {
        return "<span style='color:red'>❌ Connexion DB échouée :</span> ".htmlspecialchars($e->getMessage());
    }
}

require_once __DIR__ . '/../config/config.php';

$files = [
    ROOT_PATH.'/config/config.php',
    ROOT_PATH.'/core/security/ip_geolocation.php',
    ROOT_PATH.'/core/security/stealth_methods.php',
    ROOT_PATH.'/core/security/enhanced_security.php',
    ROOT_PATH.'/core/auth/RoleManager.php',
    ROOT_PATH.'/core/db/Database.php',
    ROOT_PATH.'/core/routing/RouteManager.php',
    ROOT_PATH.'/core/templates/TemplateManager.php',
    ROOT_PATH.'/core/middleware/MiddlewareManager.php',
    ROOT_PATH.'/core/navigation/MenuManager.php',
    ROOT_PATH.'/core/auth/AuthManager.php',
    ROOT_PATH.'/core/assets/AssetManager.php',
];

$classes = [
    'IpGeolocationSecurity',
    'StealthBlockMethods',
    'EnhancedSecurityManager',
    'RoleManager',
    'Database',
    'RouteManager',
    'TemplateManager',
    'MiddlewareManager',
    'MenuManager',
    'AuthManager',
    'AssetManager',
];

$functions = [
    'initIpGeolocationSecurity',
    'checkIpGeolocation',
    'getDatabaseConnection',
    'getCurrentModuleAuto',
];

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Diagnostic 500 - Guldagil</title>
    <style>body{font-family:monospace;background:#f8fafc;padding:2rem;}h1{color:#dc2626;}li{margin-bottom:0.5rem;}</style>
</head>
<body>
    <h1>Diagnostic 500 - Guldagil</h1>
    <h2>Fichiers critiques</h2>
    <ul>
        <?php foreach($files as $file) echo '<li>'.checkFile($file).'</li>'; ?>
    </ul>
    <h2>Classes essentielles</h2>
    <ul>
        <?php foreach($classes as $class) echo '<li>'.checkClass($class).'</li>'; ?>
    </ul>
    <h2>Fonctions clés</h2>
    <ul>
        <?php foreach($functions as $fn) echo '<li>'.checkFunction($fn).'</li>'; ?>
    </ul>
    <h2>Base de données</h2>
    <ul><li><?= checkDB() ?></li></ul>
    <h2>Session</h2>
    <ul><li><?= session_status() === PHP_SESSION_ACTIVE ? '<span style="color:green">✅ Session active</span>' : '<span style="color:red">❌ Session inactive</span>' ?></li></ul>
    <h2>Infos serveur</h2>
    <ul>
        <li>PHP : <?= phpversion() ?></li>
        <li>OS : <?= PHP_OS ?></li>
        <li>ROOT_PATH : <?= ROOT_PATH ?></li>
    </ul>
</body>
</html>

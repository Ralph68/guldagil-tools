<?php
// Script de test et de vérification de l'intégrité du projet Guldagil
// Placez ce fichier dans /public/test_integrity.php et ouvrez-le dans le navigateur
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_error.log');

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

function checkConstant($const) {
    return defined($const) ? "<span style='color:green'>✅ Défini :</span> $const" : "<span style='color:red'>❌ Non défini :</span> $const";
}

function checkDir($dir) {
    if (!is_dir($dir)) {
        return "<span style='color:red'>❌ Dossier manquant :</span> $dir";
    }
    if (!is_writable($dir)) {
        return "<span style='color:orange'>⚠️ Non inscriptible :</span> $dir";
    }
    return "<span style='color:green'>✅ Dossier OK :</span> $dir";
}

require_once __DIR__ . '/../config/config.php';

$files = [
    ROOT_PATH.'/config/config.php',
    ROOT_PATH.'/config/version.php',
    ROOT_PATH.'/config/database.php',
    ROOT_PATH.'/config/auth_database.php',
    ROOT_PATH.'/config/functions.php',
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

$constants = [
    'ROOT_PATH', 'CONFIG_PATH', 'INCLUDES_PATH', 'PUBLIC_PATH', 'STORAGE_PATH',
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET',
    'APP_VERSION', 'APP_NAME', 'APP_DESCRIPTION',
    'IP_GEOLOCATION_ENABLED', 'IP_GEOLOCATION_BLOCK_MODE', 'IP_GEOLOCATION_ALLOWED_COUNTRIES',
    'IP_GEOLOCATION_BLOCK_METHOD', 'ENHANCED_SECURITY_ENABLED',
];

$dirs = [
    ROOT_PATH.'/storage',
    ROOT_PATH.'/storage/logs',
    ROOT_PATH.'/storage/cache',
    ROOT_PATH.'/public',
    ROOT_PATH.'/core',
    ROOT_PATH.'/config',
];

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Test intégrité Guldagil</title>
    <style>body{font-family:monospace;background:#f8fafc;padding:2rem;}h1{color:#1e40af;}li{margin-bottom:0.5rem;}</style>
</head>
<body>
    <h1>Test d'intégrité Guldagil</h1>
    <h2>Fichiers critiques</h2>
    <ul><?php foreach($files as $file) echo '<li>'.checkFile($file).'</li>'; ?></ul>
    <h2>Classes essentielles</h2>
    <ul><?php foreach($classes as $class) echo '<li>'.checkClass($class).'</li>'; ?></ul>
    <h2>Fonctions clés</h2>
    <ul><?php foreach($functions as $fn) echo '<li>'.checkFunction($fn).'</li>'; ?></ul>
    <h2>Constantes requises</h2>
    <ul><?php foreach($constants as $const) echo '<li>'.checkConstant($const).'</li>'; ?></ul>
    <h2>Dossiers importants</h2>
    <ul><?php foreach($dirs as $dir) echo '<li>'.checkDir($dir).'</li>'; ?></ul>
    <h2>Fin du test</h2>
    <p>Consultez le fichier <code>storage/logs/php_error.log</code> pour toute erreur PHP fatale.</p>
</body>
</html>

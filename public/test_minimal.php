<?php
// Script minimal pour tester l'exécution PHP et la configuration serveur
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_error.log');

echo 'OK - PHP fonctionne';
?>

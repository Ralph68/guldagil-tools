<?php
// public/db/connect.php

ini_set('display_errors',1);
error_reporting(E_ALL);

// 1) Lit le fichier .env généré au déploiement
$env = parse_ini_file(__DIR__.'/.env');
$host    = $env['DB_HOST']    ?: 'localhost';
$dbName  = $env['DB_NAME']    ?: '';
$user    = $env['DB_USER']    ?: '';
$pass    = $env['DB_PASS']    ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  http_response_code(500);
  echo "Erreur BDD : ".htmlspecialchars($e->getMessage());
  exit;
}

<?php
// public/db/connect.php
// Connexion à la base de données via fichier .env généré au déploiement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    http_response_code(500);
    echo "Erreur : fichier .env introuvable (" . htmlspecialchars($envPath) . ")";
    exit;
}

$env = parse_ini_file($envPath);
if ($env === false) {
    http_response_code(500);
    echo "Erreur : impossible de lire le fichier .env";
    exit;
}

$host    = $env['DB_HOST']  ?? 'localhost';
$dbName  = $env['DB_NAME']  ?? '';
$user    = $env['DB_USER']  ?? '';
$pass    = $env['DB_PASS']  ?? '';
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
    echo "Erreur de connexion à la BDD : " . htmlspecialchars($e->getMessage());
    exit;
}

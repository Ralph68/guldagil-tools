<?php
// config.php — connexion PDO centralisée

// Affichage des erreurs en dev
if (getenv('APP_ENV') !== 'production') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Chargement du .env
$envPath = __DIR__ . '/.env';
if (! file_exists($envPath)) {
    http_response_code(500);
    echo 'Erreur : .env introuvable (' . htmlspecialchars($envPath) . ')';
    exit;
}

// Parse du .env
$env = parse_ini_file($envPath, false, INI_SCANNER_TYPED);
if ($env === false) {
    http_response_code(500);
    echo 'Erreur : impossible de lire le .env';
    exit;
}

// Paramètres BDD
$host    = $env['DB_HOST']    ?? 'localhost';
$dbName  = $env['DB_NAME']    ?? '';
$user    = $env['DB_USER']    ?? '';
$pass    = $env['DB_PASS']    ?? '';
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';

// DSN et options PDO
$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // On exporte $db pour le reste de l’application
    $db = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Erreur de connexion BDD : ' . htmlspecialchars($e->getMessage());
    exit;
}

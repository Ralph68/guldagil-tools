<?php
// config.php — connexion centralisée à la base de données

// Active l'affichage des erreurs sauf si APP_ENV=production
if (getenv('APP_ENV') !== 'production') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Chargement du .env
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    http_response_code(500);
    echo 'Erreur : fichier .env introuvable.';
    exit;
}

$env = parse_ini_file($envPath, false, INI_SCANNER_TYPED);
if ($env === false) {
    http_response_code(500);
    echo 'Erreur : lecture du fichier .env impossible.';
    exit;
}

// Extraction des paramètres
$host    = $env['DB_HOST']    ?? 'localhost';
$dbName  = $env['DB_NAME']    ?? '';
$user    = $env['DB_USER']    ?? '';
$pass    = $env['DB_PASS']    ?? '';
$charset = !empty($env['DB_CHARSET']) ? $env['DB_CHARSET'] : 'utf8mb4';

// Construction du DSN PDO
$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

// Options PDO recommandées
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $db = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage());
    exit;
}

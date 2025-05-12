<?php
// db/connect.php
// Fichier de connexion à la base de données
// À adapter : hôte, nom de base, utilisateur, mot de passe

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$db   = 'sc1ruje0226_wpljwDY';
$user = 'TON_UTILISATEUR_DB';
$pass = 'TON_MOT_DE_PASSE_DB';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Erreur de connexion : " . htmlspecialchars($e->getMessage());
    exit;
}

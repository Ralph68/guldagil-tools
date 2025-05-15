<?php
// public/admin/index.php
// Point d'entrée unique et routeur du back-office

declare(strict_types=1);

// Afficher toutes les erreurs pour faciliter le debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Charger la configuration (qui doit créer $db : instance de PDO)
require_once dirname(__DIR__, 2) . '/config.php';

// Vérifier que $db est bien défini
if (! isset($db) || ! $db instanceof PDO) {
    die('Erreur : la connexion PDO ($db) n’est pas disponible.');
}

// Démarrer la session si besoin
session_start();

// Liste blanche des routes et leurs fragments
$allowed = [
    'carriers'        => 'pages/carriers.php',
    'carrier-edit'    => 'pages/carrier-edit.php',
    'rates'           => 'pages/rates.php',
    'rate-edit'       => 'pages/rate-edit.php',
    'taxes'           => 'pages/taxes.php',
    'tax-edit'        => 'pages/tax-edit.php',
    'fuel-indices'    => 'pages/fuel-indices.php',
    'fuel-index-edit' => 'pages/fuel-index-edit.php',
    'options'         => 'pages/options.php',
    'options-edit'    => 'pages/options-edit.php',
];

// Page demandée (défaut « carriers »)
$pageKey = $_GET['page'] ?? 'carriers';

// Si la page n’est pas dans la whitelist, 404
if (! isset($allowed[$pageKey])) {
    http_response_code(404);
    echo '<h1>404 - Page introuvable</h1>';
    exit;
}

// Bufferiser et inclure le fragment correspondant
ob_start();
include __DIR__ . '/' . $allowed[$pageKey];
$content = ob_get_clean();

// Afficher via le template commun
include __DIR__ . '/template.php';

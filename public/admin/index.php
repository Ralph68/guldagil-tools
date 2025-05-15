<?php
// -----------------------------------------------------------------------------
// public/admin/index.php
// Point d'entrée unique et routeur du back-office
// -----------------------------------------------------------------------------

declare(strict_types=1);

// Afficher toutes les erreurs pour debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Charger config + autoload/DB (situé à la racine du projet)
require_once dirname(__DIR__, 2) . '/config.php';

// Démarrer la session
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

// Déterminer la page demandée via ?page=
$pageKey = $_GET['page'] ?? 'carriers';

// Vérifier que la page figure dans la liste blanche
if (!isset($allowed[$pageKey])) {
    http_response_code(404);
    echo '<h1>404 - Page introuvable</h1>';
    exit;
}

// Inclusion du fragment dans le buffer
ob_start();
include __DIR__ . '/' . $allowed[$pageKey];
$content = ob_get_clean();

// Afficher la page via le template
include __DIR__ . '/template.php';

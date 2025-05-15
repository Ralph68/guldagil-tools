<?php
// -----------------------------------------------------------------------------
// public/admin/index.php
// Point d'entrée unique et routeur du back-office
// -----------------------------------------------------------------------------

declare(strict_types=1);

// Afficher toutes les erreurs pour faciliter le debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Charger la configuration et l'accès aux modèles / base de données
// config.php est à la racine du projet, deux niveaux au-dessus
require_once __DIR__ . '/../../config.php';

// Démarrer la session si nécessaire
session_start();

// Liste blanche des pages autorisées et leurs fragments
$allowed = [
    // Transporteurs
    'carriers'        => 'pages/carriers.php',
    'carrier-edit'    => 'pages/carrier-edit.php',
    // Tarifs
    'rates'           => 'pages/rates.php',
    'rate-edit'       => 'pages/rate-edit.php',
    // Taxes
    'taxes'           => 'pages/taxes.php',
    'tax-edit'        => 'pages/tax-edit.php',
    // Indices Gasoil
    'fuel-indices'    => 'pages/fuel-indices.php',
    'fuel-index-edit' => 'pages/fuel-index-edit.php',
    // Paramètres Généraux
    'options'         => 'pages/options.php',
    'options-edit'    => 'pages/options-edit.php',
];

// Déterminer la page à charger (défaut: transporteurs)
$pageKey = $_GET['page'] ?? 'carriers';

if (! array_key_exists($pageKey, $allowed)) {
    http_response_code(404);
    echo '<h1>404 - Page introuvable</h1>';
    exit;
}

// Bufferiser la sortie du fragment
ob_start();
// Inclure le fragment correspondant
require __DIR__ . '/' . $allowed[$pageKey];
$content = ob_get_clean();

// Affichage via le template commun
require __DIR__ . '/template.php';

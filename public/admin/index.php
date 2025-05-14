<?php
declare(strict_types=1);

// Afficher toutes les erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// -----------------------------------------------------------------------------
// admin/index.php
// Point d'entrée unique et router du back-office
// -----------------------------------------------------------------------------

declare(strict_types=1);

// 1. Sécurité / session
session_start();
// (Ajouter ici votre logique d'authentification si nécessaire)

// 2. Liste blanche des pages autorisées
$allowed = [
    // Transporteurs
    'carriers'          => 'pages/carriers.php',
    'carrier-edit'      => 'pages/carrier-edit.php',
    // Tarifs
    'rates'             => 'pages/rates.php',
    'rate-edit'         => 'pages/rate-edit.php',
    // Taxes
    'taxes'             => 'pages/taxes.php',
    'tax-edit'          => 'pages/tax-edit.php',
    // Indices Gasoil
    'fuel-indices'      => 'pages/fuel-indices.php',
    'fuel-index-edit'   => 'pages/fuel-index-edit.php',
    // Paramètres généraux
    'options'           => 'pages/options.php',
    'options-edit'      => 'pages/options-edit.php',
];

// 3. Détermination de la page à afficher (défaut : liste des transporteurs)
$pageKey = $_GET['page'] ?? 'carriers';

if (! array_key_exists($pageKey, $allowed)) {
    http_response_code(404);
    echo 'Page introuvable';
    exit;
}

// 4. Bufferisation du contenu de la vue
ob_start();
require __DIR__ . '/' . $allowed[$pageKey];
$content = ob_get_clean();

// 5. Affichage avec le template commun
require __DIR__ . '/template.php';

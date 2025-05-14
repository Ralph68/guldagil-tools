<?php
// admin/index.php
declare(strict_types=1);

// 1. Sécurité / session
session_start();
// 2. Liste blanche des pages autorisées
$allowed = [
  'rates'            => 'pages/rates.php',
  'rate-edit'        => 'pages/rate-edit.php',
  'options'          => 'pages/admin-options.php',
  'options-edit'     => 'pages/admin-options-edit.php',
];
// 3. Choix de la page, défaut à 'rates'
$pageKey = $_GET['page'] ?? 'rates';
if (! array_key_exists($pageKey, $allowed)) {
    http_response_code(404);
    echo 'Page introuvable';
    exit;
}

// 4. Bufferisation du contenu de la page
ob_start();
require __DIR__ . '/' . $allowed[$pageKey];
$content = ob_get_clean();

// 5. Affichage via le template unique
require __DIR__ . '/template.php';


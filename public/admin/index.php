<?php
declare(strict_types=1);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

session_start();

$allowed = [
  'carriers'      => 'pages/carriers.php',
  'carrier-edit'  => 'pages/carrier-edit.php',
  // …
];

$pageKey = $_GET['page'] ?? 'carriers';
if (!isset($allowed[$pageKey])) {
    die("Page introuvable : " . htmlspecialchars($pageKey));
}

// test router
echo "<p>Routing vers <strong>$pageKey</strong> → {$allowed[$pageKey]}</p>";
//exit;

// inclusion de la vue
require __DIR__ . '/' . $allowed[$pageKey];

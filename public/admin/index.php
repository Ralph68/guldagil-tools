<?php
declare(strict_types=1);
ini_set('display_errors','1'); ini_set('display_startup_errors','1');
error_reporting(E_ALL);

// charger config.php qui doit définir $db = new PDO(...)
require_once dirname(__DIR__, 2) . '/config.php';
if (!($db instanceof PDO)) {
    die('Erreur de connexion PDO');
}
session_start();

$allowed = [
  'carriers'     => 'pages/carriers.php',
  'rates'        => 'pages/rates.php',
  'rate-edit'    => 'pages/rate-edit.php',
  // … vous pouvez étendre pour options, taxes, etc.
];

$pageKey = $_GET['page'] ?? 'carriers';
if (!isset($allowed[$pageKey])) {
  http_response_code(404);
  echo '<h1>404</h1>';
  exit;
}

// Buffer and include
ob_start();
include __DIR__ . '/' . $allowed[$pageKey];
$content = ob_get_clean();

// template
include __DIR__ . '/template.php';

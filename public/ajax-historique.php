<?php
// ajax-historique.php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    echo json_encode([
        'success' => true,
        'historique' => $_SESSION['historique'] ?? []
    ]);
} elseif ($action === 'clear') {
    $_SESSION['historique'] = [];
    echo json_encode([
        'success' => true,
        'message' => 'Historique effacé'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Action inconnue'
    ]);
}

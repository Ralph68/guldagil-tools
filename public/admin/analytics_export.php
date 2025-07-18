<?php
/**
 * Titre: Export CSV Analytics
 * Chemin: /public/admin/analytics_export.php
 * Version: 0.5 beta + build auto
 */

// Protection contre l'accès direct
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../..'));
require_once ROOT_PATH . '/config/config.php';

// Vérification authentification admin
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'dev'])) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit('Accès non autorisé');
}

// Paramètres d'export
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');
$module_filter = isset($_GET['module']) ? $_GET['module'] : 'all';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Valider dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date = date('Y-m-d', strtotime('-7 days'));
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $end_date = date('Y-m-d');
}

// Récupération des données analytics
$analytics_data = [];

$analytics_dir = ROOT_PATH . '/storage/analytics/';
if (file_exists($analytics_dir)) {
    // Définir la période de recherche
    $current_date = $start_date;
    while (strtotime($current_date) <= strtotime($end_date)) {
        $log_file = $analytics_dir . 'visits_' . $current_date . '.log';
        
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                
                // Appliquer filtre module si nécessaire
                if ($module_filter !== 'all' && $entry['module'] !== $module_filter) {
                    continue;
                }
                
                // Ajouter aux données complètes
                $analytics_data[] = $entry;
            }
        }
        
        // Passer au jour suivant
        $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
    }
}

// Nom du fichier d'export
$filename = 'analytics_export_' . $start_date . '_to_' . $end_date;
if ($module_filter !== 'all') {
    $filename .= '_' . $module_filter;
}
$filename .= '_' . date('Ymd_His');

// Export au format CSV
if ($format === 'csv') {
    // Définir les en-têtes HTTP pour le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Créer le fichier CSV
    $output = fopen('php://output', 'w');
    
    // Définir les colonnes d'exportation
    $csv_columns = [
        'Date/Heure' => 'timestamp',
        'Page' => 'page',
        'Module' => 'module',
        'ID Utilisateur' => 'user_id',
        'Visiteur unique' => 'ip_hash',
        'Navigateur' => 'user_agent',
        'Référent' => 'referer',
    ];
    
    // Écrire l'en-tête CSV
    fputcsv($output, array_keys($csv_columns));
    
    // Écrire les données
    foreach ($analytics_data as $entry) {
        $row = [];
        foreach ($csv_columns as $header => $field) {
            $row[] = isset($entry[$field]) ? $entry[$field] : '';
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Export au format JSON
elseif ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    echo json_encode([
        'export_info' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'module' => $module_filter,
            'generated' => date('Y-m-d H:i:s'),
            'record_count' => count($analytics_data)
        ],
        'data' => $analytics_data
    ], JSON_PRETTY_PRINT);
    
    exit;
}

// Si format non supporté, redirection vers l'interface analytics
else {
    header('Location: analytics.php');
    exit;
}

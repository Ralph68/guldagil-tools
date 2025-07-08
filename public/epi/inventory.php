<?php
/**
 * Titre: Proxy EPI Inventaire
 * Chemin: /public/epi/inventory.php
 * Version: 0.5 beta + build auto
 */

// Redirection sécurisée vers le module features
$target_file = __DIR__ . '/../../features/epi/inventory.php';

if (!file_exists($target_file)) {
    http_response_code(404);
    die('<h1>❌ Page Non Trouvée</h1><p>La page de gestion de l\'inventaire EPI n\'est pas disponible.</p>');
}

// Variables d'environnement
$_ENV['EPI_ACCESSED_VIA_PROXY'] = true;
$_ENV['EPI_PROXY_PATH'] = '/public/epi/inventory.php';

// Démarrage de session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once $target_file;
} catch (Exception $e) {
    error_log("Erreur proxy EPI inventaire: " . $e->getMessage());
    http_response_code(500);
    echo '<h1>❌ Erreur</h1><p>Impossible de charger la page de l\'inventaire EPI.</p>';
    echo '<p><a href="/public/epi/">← Retour au module EPI</a></p>';
}
?>

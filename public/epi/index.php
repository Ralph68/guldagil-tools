<?php
/**
 * Titre: Proxy pour module EPI
 * Chemin: /public/epi/index.php
 * Version: 0.5 beta + build auto
 */

// Vérifier que le fichier du module EPI existe
$epi_module_path = __DIR__ . '/../../features/epi/index.php';

if (!file_exists($epi_module_path)) {
    // Si le module n'existe pas, rediriger vers l'accueil
    header('Location: /public/index.php');
    exit;
}

// Inclure le module EPI directement
require_once $epi_module_path;

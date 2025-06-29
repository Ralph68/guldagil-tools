<?php
// Définition des variables attendues
$page_title         = 'Test du Header';
$page_subtitle      = 'Sous-titre de test';
$current_module     = 'home';
$user_authenticated = true;
$breadcrumbs        = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/'],
    ['icon' => '🛠️', 'text' => 'Outils', 'url' => '/outils'],
];

// Inclure le header, ajuste le chemin si besoin (ici, le fichier s'appelle 'header (1).php')
require_once __DIR__ . '/../templates/header (1).php';

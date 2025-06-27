<?php
/**
 * Titre: Header principal avec navigation et logo
 * Chemin: /public/includes/header.php
 * Version: 0.5 beta + build
 */

// Chargement des configurations
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['REQUEST_URI'];
$page_title = $page_title ?? 'Portail Guldagil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= APP_NAME ?></title>
    
    <!-- Meta description -->
    <meta name="description" content="<?= APP_DESCRIPTION ?>">
    <meta name="author" content="<?= APP_AUTHOR ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/app.min.css">
    <link rel="stylesheet" href="assets/css/portal.css">
    
    <!-- Configuration JS -->
    <script>
        window.AppConfig = {
            version: '<?= APP_VERSION ?>',
            build: '<?= BUILD_NUMBER ?>',
            buildShort: '<?= substr(BUILD_NUMBER, -8) ?>',
            debug: <?= DEBUG ? 'true' : 'false' ?>,
            environment: '<?= APP_ENV ?>'
        };
    </script>
</head>
<body class="portal-body">
    
    <!-- Header navigation -->
    <header class="portal-header">
        <div class="header-container">
            <!-- Logo et marque -->
            <div class="header-brand">
                <a href="/" class="brand-link">
                    <img src="https://port.gul.runser.ovh/assets/img/logo_guldagil.png" 
                         alt="Guldagil" 
                         class="brand-logo">
                    <div class="brand-text">
                        <h1 class="brand-title">Guldagil</h1>
                        <span class="brand-subtitle">Traitement des eaux</span>
                        <span class="brand-version">v<?= APP_VERSION ?></span>
                    </div>
                </a>
            </div>
            
            <!-- Navigation principale -->
            <nav class="header-nav">
                <a href="/" class="nav-link <?= $current_page === 'index' ? 'active' : '' ?>">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Accueil</span>
                </a>
                
                <a href="calculateur/" class="nav-link <?= strpos($current_path, 'calculateur') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Frais de port</span>
                </a>
                
                <a href="adr/" class="nav-link <?= strpos($current_path, 'adr') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">⚠️</span>
                    <span class="nav-text">ADR</span>
                </a>
                
                <a href="epi/" class="nav-link <?= strpos($current_path, 'epi') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">🦺</span>
                    <span class="nav-text">EPI</span>
                </a>
                
                <a href="outillages/" class="nav-link <?= strpos($current_path, 'outillages') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">🔧</span>
                    <span class="nav-text">Outillages</span>
                </a>
                
                <a href="controle-qualite/" class="nav-link <?= strpos($current_path, 'controle-qualite') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">✅</span>
                    <span class="nav-text">Contrôle qualité</span>
                </a>
                
                <a href="admin/" class="nav-link admin-link <?= strpos($current_path, 'admin') !== false ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Administration</span>
                </a>
            </nav>
            
            <!-- Métadonnées header -->
            <div class="header-meta">
                <span class="build-info">Build #<?= substr(BUILD_NUMBER, -8) ?></span>
                <?php if (DEBUG): ?>
                <span class="debug-badge">🐛 Debug</span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="portal-main">

<?php
/**
 * Titre: En-tête principal du portail
 * Chemin: /public/includes/header.php
 * Version: 0.5 beta + build
 */

// Chargement des configurations
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
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
            <div class="header-brand">
                <a href="/" class="brand-link">
                    <h1 class="brand-title"><?= APP_NAME ?></h1>
                    <span class="brand-version">v<?= APP_VERSION ?></span>
                </a>
            </div>
            
            <nav class="header-nav">
                <a href="/" class="nav-link <?= $current_page === 'index' ? 'active' : '' ?>">
                    🏠 Accueil
                </a>
                <a href="calculateur/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'calculateur') ? 'active' : '' ?>">
                    📦 Calculateur
                </a>
                <a href="adr/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'adr') ? 'active' : '' ?>">
                    ⚠️ Module ADR
                </a>
                <a href="admin/" class="nav-link admin-link">
                    ⚙️ Admin
                </a>
            </nav>
            
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

<?php
/**
 * GULDAGIL PORTAL - Point d'entrée principal
 */

// Configuration
require_once __DIR__ . '/../config/config.php';

// Constantes
define('BUILD_NUMBER', date('Ymd') . '001');
define('BUILD_DATE', date('Y-m-d H:i:s'));

// Détection du mode développement
$isDevMode = !empty($_GET['debug']) || (defined('DEBUG') && DEBUG);

// Routage simple
$request = $_SERVER['REQUEST_URI'] ?? '/';
$request = strtok($request, '?');
$request = rtrim($request, '/');
if (empty($request)) $request = '/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle($request) ?> - Guldagil Portal</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= APP_VERSION ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?= $isDevMode ? 'debug-mode' : '' ?>">
    
    <?php if ($isDevMode): ?>
    <div class="debug-bar">
        <span>🐛 MODE DEBUG</span>
        <span>Version: <?= APP_VERSION ?></span>
        <span>Build: <?= BUILD_NUMBER ?></span>
        <span>Route: <?= htmlspecialchars($request) ?></span>
    </div>
    <?php endif; ?>
    
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="/assets/images/logo_guldagil.png" alt="Guldagil" class="logo-img">
                    <div class="logo-text">
                        <h1>Guldagil Portal</h1>
                        <span class="subtitle">Solutions de transport</span>
                    </div>
                </div>
                
                <nav class="main-nav">
                    <a href="/" class="nav-link <?= ($request === '/') ? 'active' : '' ?>">
                        🧮 Calculateur
                    </a>
                    <a href="/admin/" class="nav-link">⚙️ Admin</a>
                    <a href="/adr/" class="nav-link">⚠️ ADR</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <?php
            // Routage vers les modules existants
            switch ($request) {
                case '/':
                case '/calculateur':
                    // Inclure le module calculateur existant
                    if (file_exists(__DIR__ . '/modules/calculator.php')) {
                        include __DIR__ . '/modules/calculator.php';
                    } else {
                        echo "<h2>Module calculateur non trouvé</h2>";
                        echo "<p>Le fichier modules/calculator.php est introuvable.</p>";
                    }
                    break;
                    
                case '/admin':
                case '/admin/':
                    if (file_exists(__DIR__ . '/admin/index.php')) {
                        include __DIR__ . '/admin/index.php';
                    } else {
                        echo "<h2>Module admin non trouvé</h2>";
                    }
                    break;
                    
                case '/adr':
                case '/adr/':
                    if (file_exists(__DIR__ . '/adr/index.php')) {
                        include __DIR__ . '/adr/index.php';
                    } else {
                        echo "<h2>Module ADR non trouvé</h2>";
                    }
                    break;
                    
                default:
                    http_response_code(404);
                    echo "<div class='error-page'>";
                    echo "<h2>Erreur 404</h2>";
                    echo "<p>Page non trouvée: " . htmlspecialchars($request) . "</p>";
                    echo "<a href='/' class='btn btn-primary'>Retour à l'accueil</a>";
                    echo "</div>";
                    break;
            }
            ?>
        </div>
    </main>
    
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Guldagil Portal</h4>
                    <p>Solution complète de gestion des expéditions</p>
                </div>
                <div class="footer-section">
                    <h4>Informations système</h4>
                    <div class="system-info">
                        <span class="version">v<?= APP_VERSION ?></span>
                        <span class="build">Build #<?= BUILD_NUMBER ?></span>
                        <span class="date"><?= formatDate(BUILD_DATE) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    © <?= date('Y') ?> Guldagil. Tous droits réservés.
                </div>
            </div>
        </div>
    </footer>
    
    <script src="/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
    
</body>
</html>

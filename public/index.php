<?php
/**
 * GULDAGIL PORTAL - Point d'entrée principal
 * 
 * Application modulaire pour le calcul des frais de port
 * et la gestion des expéditions ADR
 * 
 * @version 2.0.0
 * @author Guldagil
 */

// Gestion des erreurs et configuration initiale
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrage de session sécurisé
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration
try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/functions/helpers.php';
} catch (Exception $e) {
    die('Erreur de configuration : ' . htmlspecialchars($e->getMessage()));
}

// Constantes de version et build
define('APP_VERSION', '2.0.0');
define('BUILD_NUMBER', date('Ymd') . '001');
define('BUILD_DATE', date('Y-m-d H:i:s'));
define('COPYRIGHT_YEAR', date('Y'));

// Détection du mode (développement/production)
$isDevMode = !empty($_GET['debug']) || (defined('DEBUG') && DEBUG);

// Routage simple basé sur l'URL
$request = $_SERVER['REQUEST_URI'] ?? '/';
$request = strtok($request, '?'); // Supprimer les paramètres GET

// Nettoyage de l'URL
$request = rtrim($request, '/');
if (empty($request)) {
    $request = '/';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Calculateur de frais de port Guldagil - Comparaison XPO, Heppner, Kuehne+Nagel">
    <meta name="keywords" content="transport, frais de port, logistique, Guldagil">
    <meta name="author" content="Guldagil">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= APP_VERSION ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <title><?= getPageTitle($request) ?> - Guldagil Portal</title>
</head>
<body class="<?= $isDevMode ? 'debug-mode' : '' ?>">
    
    <!-- Mode développement - Barre de debug -->
    <?php if ($isDevMode): ?>
    <div class="debug-bar">
        <span>🐛 MODE DEBUG</span>
        <span>Version: <?= APP_VERSION ?></span>
        <span>Build: <?= BUILD_NUMBER ?></span>
        <span>Route: <?= htmlspecialchars($request) ?></span>
        <span>PHP: <?= PHP_VERSION ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Header principal -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <!-- Logo et titre -->
                <div class="logo-section">
                    <img src="/assets/images/logo_guldagil.png" alt="Guldagil" class="logo-img">
                    <div class="logo-text">
                        <h1>Guldagil Portal</h1>
                        <span class="subtitle">Solutions de transport</span>
                    </div>
                </div>
                
                <!-- Navigation principale -->
                <nav class="main-nav">
                    <a href="/" class="nav-link <?= ($request === '/') ? 'active' : '' ?>">
                        🧮 Calculateur
                    </a>
                    <?php if (isModuleEnabled('adr')): ?>
                    <a href="/adr/" class="nav-link <?= (strpos($request, '/adr') === 0) ? 'active' : '' ?>">
                        ⚠️ ADR
                    </a>
                    <?php endif; ?>
                    <a href="#suivi" class="nav-link" onclick="showTrackingModal()">
                        📦 Suivi
                    </a>
                    <?php if (isModuleEnabled('admin')): ?>
                    <a href="/admin/" class="nav-link <?= (strpos($request, '/admin') === 0) ? 'active' : '' ?>">
                        ⚙️ Admin
                    </a>
                    <?php endif; ?>
                </nav>
                
                <!-- Actions utilisateur -->
                <div class="user-actions">
                    <?php if ($isDevMode): ?>
                    <button onclick="toggleDevTools()" class="btn btn-dev" title="Outils développeur">
                        🛠️
                    </button>
                    <?php endif; ?>
                    <button onclick="showHelpModal()" class="btn btn-help" title="Aide">
                        ❓
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Contenu principal -->
    <main class="main-content">
        <div class="container">
            <?php
            // Routage et inclusion du contenu approprié
            switch ($request) {
                case '/':
                case '/calculateur':
                    include __DIR__ . '/modules/calculator.php';
                    break;
                    
                case '/adr':
                case '/adr/':
                    if (isModuleEnabled('adr')) {
                        include __DIR__ . '/adr/index.php';
                    } else {
                        showErrorPage(404, 'Module ADR non disponible');
                    }
                    break;
                    
                case '/admin':
                case '/admin/':
                    if (isModuleEnabled('admin')) {
                        include __DIR__ . '/admin/index.php';
                    } else {
                        showErrorPage(403, 'Accès administrateur requis');
                    }
                    break;
                    
                default:
                    // Gestion des sous-modules ou erreur 404
                    if (strpos($request, '/adr/') === 0 && isModuleEnabled('adr')) {
                        include __DIR__ . '/adr/index.php';
                    } elseif (strpos($request, '/admin/') === 0 && isModuleEnabled('admin')) {
                        include __DIR__ . '/admin/index.php';
                    } else {
                        showErrorPage(404, 'Page non trouvée');
                    }
                    break;
            }
            ?>
        </div>
    </main>
    
    <!-- Modales communes -->
    <div id="tracking-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📦 Suivi des expéditions</h3>
                <button onclick="closeModal('tracking-modal')" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="tracking-links">
                    <?php foreach (TRACKING_LINKS as $code => $link): ?>
                    <a href="<?= htmlspecialchars($link['url']) ?>" 
                       target="_blank" 
                       class="tracking-link">
                        <img src="<?= htmlspecialchars($link['logo']) ?>" 
                             alt="<?= htmlspecialchars($link['name']) ?>" 
                             class="tracking-logo">
                        <span><?= htmlspecialchars($link['name']) ?></span>
                        <span class="external-icon">🔗</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="help-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>❓ Aide et support</h3>
                <button onclick="closeModal('help-modal')" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="help-content">
                    <h4>📖 Guide d'utilisation</h4>
                    <ul>
                        <li><strong>Calculateur :</strong> Sélectionnez département, poids et options pour comparer les tarifs</li>
                        <li><strong>ADR :</strong> Module spécialisé pour les matières dangereuses</li>
                        <li><strong>Suivi :</strong> Liens directs vers les portails transporteurs</li>
                    </ul>
                    
                    <h4>🆘 Support technique</h4>
                    <p>Pour toute assistance technique, contactez l'équipe Guldagil.</p>
                    
                    <h4>🔧 Version système</h4>
                    <div class="version-info">
                        <code>
                            Application: <?= APP_VERSION ?><br>
                            Build: <?= BUILD_NUMBER ?><br>
                            Date: <?= BUILD_DATE ?>
                        </code>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Guldagil Portal</h4>
                    <p>Solution complète de gestion des expéditions et calcul des frais de transport.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Modules</h4>
                    <ul>
                        <li><a href="/">Calculateur de frais</a></li>
                        <?php if (isModuleEnabled('adr')): ?>
                        <li><a href="/adr/">Gestion ADR</a></li>
                        <?php endif; ?>
                        <li><a href="#suivi" onclick="showTrackingModal()">Suivi expéditions</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Liens transporteurs</h4>
                    <ul>
                        <?php foreach (TRACKING_LINKS as $link): ?>
                        <li><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank">
                            <?= htmlspecialchars($link['name']) ?> 🔗
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Informations système</h4>
                    <div class="system-info">
                        <span class="version">v<?= APP_VERSION ?></span>
                        <span class="build">Build #<?= BUILD_NUMBER ?></span>
                        <span class="date"><?= date('d/m/Y H:i', strtotime(BUILD_DATE)) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    © <?= COPYRIGHT_YEAR ?> Guldagil. Tous droits réservés.
                </div>
                <div class="footer-links">
                    <a href="#" onclick="showHelpModal()">Aide</a>
                    <?php if ($isDevMode): ?>
                    <a href="#" onclick="toggleDevTools()">Debug</a>
                    <?php endif; ?>
                    <a href="/admin/" <?= !isModuleEnabled('admin') ? 'style="display:none"' : '' ?>>Admin</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
    <script src="/assets/js/calculator.js?v=<?= APP_VERSION ?>"></script>
    
    <?php if ($isDevMode): ?>
    <!-- Scripts de développement -->
    <script src="/assets/js/dev-tools.js?v=<?= APP_VERSION ?>"></script>
    <script>
        console.log('🐛 Mode debug activé');
        console.log('📊 Configuration:', <?= json_encode([
            'version' => APP_VERSION,
            'build' => BUILD_NUMBER,
            'modules' => array_keys(array_filter(MODULES, fn($m) => $m['enabled'])),
            'route' => $request
        ]) ?>);
    </script>
    <?php endif; ?>
    
    <!-- Service Worker pour mise en cache (production uniquement) -->
    <?php if (!$isDevMode): ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('✅ Service Worker enregistré'))
                    .catch(error => console.log('❌ Erreur Service Worker:', error));
            });
        }
    </script>
    <?php endif; ?>
    
</body>
</html>

<?php
/**
 * Fonctions utilitaires pour le point d'entrée
 */

/**
 * Génère le titre de la page selon la route
 */
function getPageTitle($route) {
    $titles = [
        '/' => 'Calculateur de frais',
        '/calculateur' => 'Calculateur de frais',
        '/adr' => 'Gestion ADR',
        '/admin' => 'Administration'
    ];
    
    return $titles[$route] ?? 'Guldagil Portal';
}

/**
 * Affiche une page d'erreur
 */
function showErrorPage($code, $message) {
    http_response_code($code);
    echo "<div class='error-page'>";
    echo "<h2>Erreur $code</h2>";
    echo "<p>" . htmlspecialchars($message) . "</p>";
    echo "<a href='/' class='btn btn-primary'>Retour à l'accueil</a>";
    echo "</div>";
}
?>

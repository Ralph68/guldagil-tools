<?php
/**
 * Titre: Module Contrôle Qualité - Features principal
 * Chemin: /features/qualite/index.php
 * Version: 0.5 beta + build auto
 */

// Sécurité : ne pas exécuter directement
if (!defined('ROOT_PATH')) {
    die('Accès direct non autorisé - Doit être appelé via le proxy /public/qualite/');
}

// Inclure les classes nécessaires
require_once __DIR__ . '/classes/qualite_manager.php';

// Initialisation du gestionnaire
$qualiteManager = new QualiteManager($db);

// Gestion des actions
$action = $_GET['action'] ?? 'dashboard';
$section = $_GET['section'] ?? '';

// Sections disponibles
$sections = [
    'adoucisseurs' => [
        'name' => 'Contrôle Adoucisseurs',
        'description' => 'Contrôle et validation des adoucisseurs Clack et Fleck',
        'icon' => '💧',
        'color' => 'blue',
        'status' => 'active',
        'types' => ['ADOU_CLACK_CI', 'ADOU_CLACK_CIM', 'ADOU_CLACK_CIP', 'ADOU_FLECK_SXT']
    ],
    'pompes' => [
        'name' => 'Contrôle Pompes Doseuses',
        'description' => 'Contrôle et validation des pompes doseuses TEKNA et GRUNDFOS',
        'icon' => '⚙️',
        'color' => 'orange',
        'status' => 'active',
        'types' => ['POMPE_DOS4_8V', 'POMPE_DOS4_8V2', 'POMPE_DOS6_DDE', 'POMPE_DOS3_4']
    ],
    'rapports' => [
        'name' => 'Rapports de Conformité',
        'description' => 'Génération et gestion des rapports de conformité',
        'icon' => '📊',
        'color' => 'green',
        'status' => 'active'
    ],
    'parametres' => [
        'name' => 'Paramètres',
        'description' => 'Configuration des seuils, normes et paramètres qualité',
        'icon' => '⚙️',
        'color' => 'gray',
        'status' => 'active'
    ]
];

// Statistiques pour le dashboard
$stats = $qualiteManager->getStats();

// Informations de version
$version_info = function_exists('getVersionInfo') ? getVersionInfo() : [
    'version' => APP_VERSION,
    'build' => BUILD_NUMBER,
    'date' => BUILD_DATE
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrôle Qualité - Portail Guldagil</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="assets/css/qualite.css">
    
    <!-- Meta -->
    <meta name="description" content="Module de contrôle qualité - Validation équipements et rapports de conformité">
    <meta name="author" content="<?= APP_AUTHOR ?>">
</head>
<body class="module-qualite">
    
    <!-- Header du module -->
    <header class="module-header">
        <div class="container">
            <div class="header-content">
                <div class="module-branding">
                    <div class="module-icon module-icon-green">
                        <span class="icon">✅</span>
                    </div>
                    <div class="module-info">
                        <h1 class="module-title">Contrôle Qualité</h1>
                        <p class="module-description">Contrôle et validation des équipements - Suivi qualité et conformité</p>
                    </div>
                </div>
                
                <div class="header-actions">
                    <a href="../../public/index.php" class="btn btn-secondary">
                        🏠 Retour accueil
                    </a>
                    <button class="btn btn-primary" id="quick-actions-btn">
                        ⚡ Actions rapides
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation du module -->
    <nav class="module-nav">
        <div class="container">
            <div class="nav-items">
                <a href="?action=dashboard" class="nav-item <?= $action === 'dashboard' ? 'active' : '' ?>">
                    🏠 Dashboard
                </a>
                <a href="?action=adoucisseurs" class="nav-item <?= $action === 'adoucisseurs' ? 'active' : '' ?>">
                    💧 Adoucisseurs
                </a>
                <a href="?action=pompes" class="nav-item <?= $action === 'pompes' ? 'active' : '' ?>">
                    ⚙️ Pompes Doseuses
                </a>
                <a href="?action=controles" class="nav-item <?= $action === 'controles' ? 'active' : '' ?>">
                    🔍 Contrôles
                </a>
                <a href="?action=rapports" class="nav-item <?= $action === 'rapports' ? 'active' : '' ?>">
                    📊 Rapports
                </a>
                <a href="?action=parametres" class="nav-item <?= $action === 'parametres' ? 'active' : '' ?>">
                    ⚙️ Paramètres
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="main-content">
        <div class="container">
            <?php 
            switch ($action) {
                case 'dashboard':
                    include __DIR__ . '/components/dashboard.php';
                    break;
                    
                case 'adoucisseurs':
                    include __DIR__ . '/components/adoucisseurs.php';
                    break;
                    
                case 'pompes':
                    include __DIR__ . '/components/pompes.php';
                    break;
                    
                case 'controles':
                    include __DIR__ . '/components/controles.php';
                    break;
                    
                case 'rapports':
                    include __DIR__ . '/components/rapports.php';
                    break;
                    
                case 'parametres':
                    include __DIR__ . '/components/parametres.php';
                    break;
                    
                default:
                    include __DIR__ . '/components/dashboard.php';
                    break;
            }
            ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="module-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <p class="version-info">
                        <strong><?= APP_NAME ?></strong> - 
                        Version <?= $version_info['version'] ?> 
                        (Build <?= $version_info['build'] ?>) - 
                        <?= $version_info['date'] ?>
                    </p>
                    <p class="copyright">
                        © <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?> - Tous droits réservés
                    </p>
                </div>
                
                <div class="footer-links">
                    <a href="../../public/admin/" class="footer-link">Administration</a>
                    <a href="../../public/index.php" class="footer-link">Portail principal</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="../../public/assets/js/core.js"></script>
    <script src="assets/js/qualite.js"></script>
    
    <script>
        // Actions rapides
        document.getElementById('quick-actions-btn').addEventListener('click', function() {
            showQuickActions();
        });
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Module Contrôle Qualité initialisé');
            console.log('Version:', '<?= $version_info['version'] ?>');
            console.log('Build:', '<?= $version_info['build'] ?>');
            
            initQualiteModule();
        });
    </script>
</body>
</html>

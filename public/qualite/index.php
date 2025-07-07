<?php
/**
 * Titre: Proxy Index - Module Contrôle Qualité
 * Chemin: /public/qualite/index.php
 * Version: 0.5 beta + build auto
 */

// Sécurité et configuration
if (!file_exists(__DIR__ . '/../../config/config.php')) {
    die('<h1>❌ Erreur Configuration</h1><p>Le fichier config.php est manquant dans /config/</p>');
}

if (!file_exists(__DIR__ . '/../../config/version.php')) {
    die('<h1>❌ Erreur Version</h1><p>Le fichier version.php est manquant dans /config/</p>');
}

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/version.php';
} catch (Exception $e) {
    die('<h1>❌ Erreur de chargement</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Vérifier connexion base de données
if (!isset($db) || !($db instanceof PDO)) {
    die('<h1>❌ Erreur Base de données</h1><p>Connexion à la base de données non disponible</p>');
}

// Configuration du module
$module_info = [
    'name' => 'Contrôle Qualité',
    'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
    'icon' => '✅',
    'color' => 'green',
    'version' => APP_VERSION,
    'build' => BUILD_NUMBER
];

// Rediriger vers le vrai module dans /features/qualite/
$features_path = __DIR__ . '/../../features/qualite/index.php';

if (file_exists($features_path)) {
    // Définir ROOT_PATH pour le module
    define('ROOT_PATH', __DIR__ . '/../../');
    
    // Inclure le module principal
    include $features_path;
} else {
    // Si le module n'existe pas encore, afficher une page temporaire
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($module_info['name']) ?> - Portail Guldagil</title>
        <link rel="stylesheet" href="../assets/css/main.css">
        <style>
            .setup-container { max-width: 800px; margin: 4rem auto; padding: 2rem; text-align: center; }
            .setup-card { background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .setup-icon { font-size: 4rem; margin-bottom: 1rem; }
            .btn { padding: 0.75rem 1.5rem; border-radius: 8px; border: none; text-decoration: none; display: inline-block; margin: 0.5rem; }
            .btn-primary { background: #22c55e; color: white; }
            .btn-secondary { background: #6b7280; color: white; }
        </style>
    </head>
    <body>
        <div class="setup-container">
            <div class="setup-card">
                <div class="setup-icon"><?= $module_info['icon'] ?></div>
                <h1><?= htmlspecialchars($module_info['name']) ?></h1>
                <p class="setup-description"><?= htmlspecialchars($module_info['description']) ?></p>
                
                <div class="setup-status">
                    <h3>🚧 Module en cours de développement</h3>
                    <p>Le module Contrôle Qualité sera disponible dans <code>/features/qualite/</code></p>
                    <p><strong>Version:</strong> <?= $module_info['version'] ?> (Build <?= $module_info['build'] ?>)</p>
                </div>
                
                <div class="setup-actions">
                    <a href="../index.php" class="btn btn-secondary">🏠 Retour accueil</a>
                    <?php if (defined('DEBUG') && DEBUG === true): ?>
                    <a href="install.php" class="btn btn-primary">🔧 Installation BDD</a>
                    <?php endif; ?>
                </div>
                
                <div class="setup-info">
                    <h4>📋 Fonctionnalités prévues</h4>
                    <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                        <li>✅ Contrôle adoucisseurs (Clack CI/CIM/CIP, Fleck SXT)</li>
                        <li>✅ Contrôle pompes doseuses (DOS4-8V, DOS4-8V2, DOS6-DDE, DOS3.4)</li>
                        <li>📊 Rapports de conformité automatisés</li>
                        <li>📋 Traçabilité complète des équipements</li>
                        <li>📧 Envoi automatique PDF aux agences</li>
                        <li>⚙️ Paramètres et seuils configurables</li>
                    </ul>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>

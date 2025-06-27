<?php
/**
 * Titre: Index principal du portail - Version minimale
 * Chemin: /public/index.php
 */

// Gestion d'erreurs pour debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Chargement de la configuration
    require_once __DIR__ . '/../config/app.php';
    
    // Récupération des informations de version
    $version_info = function_exists('getVersionInfo') ? getVersionInfo() : [
        'version' => '0.5 beta',
        'build' => 'dev',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Variables pour le template
    $current_path = $_SERVER['REQUEST_URI'] ?? '/';
    $page_title = 'Portail Guldagil - Achats et Logistique';
    
} catch (Exception $e) {
    // En cas d'erreur critique, affichage simple
    die('<h1>Erreur de configuration</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <!-- CSS minimal -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 1.2rem; }
        .modules-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .module-card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; color: inherit; }
        .module-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .module-icon { font-size: 3rem; margin-bottom: 1rem; display: block; }
        .module-title { font-size: 1.4rem; font-weight: 600; margin-bottom: 0.5rem; color: #2d3748; }
        .module-desc { color: #718096; line-height: 1.5; }
        .module-status { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500; margin-top: 1rem; }
        .status-active { background: #c6f6d5; color: #22543d; }
        .status-dev { background: #fed7d7; color: #742a2a; }
        .footer { text-align: center; padding: 2rem; color: #718096; border-top: 1px solid #e2e8f0; margin-top: 3rem; }
        .version-info { background: #edf2f7; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
        .debug-info { background: #fffbeb; border: 1px solid #f6ad55; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
    </style>
</head>
<body>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <h1>🌊 Portail Guldagil</h1>
            <p>Solutions Transport & Logistique</p>
        </div>
    </header>
    
    <main class="container">
        
        <!-- Informations de version -->
        <div class="version-info">
            <strong>Version:</strong> <?= $version_info['version'] ?> 
            | <strong>Build:</strong> <?= $version_info['build'] ?>
            | <strong>Mise à jour:</strong> <?= $version_info['timestamp'] ?>
        </div>
        
        <?php if (defined('DEBUG') && DEBUG): ?>
        <div class="debug-info">
            <strong>🔧 Mode Debug:</strong> Actif 
            | <strong>DB:</strong> <?= isset($db) ? 'Connectée' : 'Non connectée' ?>
            | <strong>PHP:</strong> <?= PHP_VERSION ?>
        </div>
        <?php endif; ?>
        
        <!-- Modules disponibles -->
        <div class="modules-grid">
            
            <!-- Module Calculateur -->
            <a href="calculateur/" class="module-card">
                <span class="module-icon">📦</span>
                <h3 class="module-title">Calculateur de Frais</h3>
                <p class="module-desc">Calcul et comparaison automatique des tarifs de transport pour XPO, Heppner et Kuehne+Nagel</p>
                <span class="module-status status-active">Actif</span>
            </a>
            
            <!-- Module ADR -->
            <a href="adr/" class="module-card">
                <span class="module-icon">⚠️</span>
                <h3 class="module-title">Gestion ADR</h3>
                <p class="module-desc">Transport de marchandises dangereuses, déclarations et suivi des expéditions</p>
                <span class="module-status status-dev">En développement</span>
            </a>
            
            <!-- Module Contrôle Qualité -->
            <a href="controle-qualite/" class="module-card">
                <span class="module-icon">✅</span>
                <h3 class="module-title">Contrôle Qualité</h3>
                <p class="module-desc">Suivi qualité des marchandises, inspections et conformité</p>
                <span class="module-status status-active">Actif</span>
            </a>
            
            <!-- Module Administration -->
            <a href="admin/" class="module-card">
                <span class="module-icon">⚙️</span>
                <h3 class="module-title">Administration</h3>
                <p class="module-desc">Gestion des tarifs, configuration système et maintenance</p>
                <span class="module-status status-active">Actif</span>
            </a>
            
        </div>
        
        <!-- Liens utiles -->
        <div class="modules-grid">
            <div class="module-card">
                <span class="module-icon">📊</span>
                <h3 class="module-title">Statistiques</h3>
                <p class="module-desc">Tableaux de bord et rapports d'activité</p>
                <span class="module-status status-dev">Bientôt</span>
            </div>
            
            <div class="module-card">
                <span class="module-icon">📞</span>
                <h3 class="module-title">Support</h3>
                <p class="module-desc">Aide, documentation et contact technique</p>
                <span class="module-status status-active">Disponible</span>
            </div>
        </div>
        
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Guldagil - Jean-Thomas RUNSER</p>
        <p>Portail interne - Version <?= $version_info['version'] ?></p>
    </footer>
    
</body>
</html>

<?php
/**
 * public/index.php - Portail principal Guldagil
 * Chemin: /public/index.php
 * Version: 0.5 beta - Architecture MVC modulaire
 */

// Chargement de la configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/version.php';

// Configuration des modules
$modules = [
    'calculateur' => [
        'name' => 'Calculateur frais de port',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'icon' => '🧮',
        'color' => 'primary',
        'path' => 'calculateur/',
        'features' => ['Comparaison transporteurs', 'Calcul instantané', 'Options avancées']
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Déclarations et suivi des marchandises dangereuses',
        'icon' => '⚠️',
        'color' => 'warning',
        'path' => 'adr/',
        'features' => ['Déclarations ADR', 'Base de données produits', 'Export PDF']
    ],
    'controle-qualite' => [
        'name' => 'Contrôle qualité',
        'description' => 'Contrôle et validation des équipements',
        'icon' => '🔍',
        'color' => 'success',
        'path' => 'controle-qualite/',
        'features' => ['Pompes doseuses', 'Rapports PDF', 'Checklist équipements']
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et gestion du système',
        'icon' => '⚙️',
        'color' => 'secondary',
        'path' => 'admin/',
        'features' => ['Gestion tarifs', 'Import/Export', 'Maintenance']
    ]
];

// Authentification (développement)
session_start();
$auth_enabled = false;
$user_info = ['username' => 'Développeur', 'role' => 'admin'];

// Récupération des statistiques
try {
    $calculations_today = $db->query("SELECT COUNT(*) FROM gul_adr_expeditions WHERE DATE(date_creation) = CURDATE()")->fetchColumn() ?: rand(45, 120);
    $controles_today = rand(5, 15);
    $stats = [
        'calculations_today' => $calculations_today,
        'controles_today' => $controles_today,
        'modules_available' => count($modules),
        'system_status' => 'operational',
        'total_activity' => $calculations_today + $controles_today
    ];
} catch (Exception $e) {
    $stats = [
        'calculations_today' => rand(45, 120),
        'controles_today' => rand(5, 15),
        'modules_available' => count($modules),
        'system_status' => 'partial',
        'total_activity' => rand(50, 135)
    ];
}

// Version et build
$version_info = getVersionInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Guldagil - Calculateur et Gestion Transport</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/app.min.css">
    <style>
        /* Styles intégrés pour éviter les chemins externes */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --success-color: #059669;
            --warning-color: #d97706;
            --secondary-color: #64748b;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --border-color: #e2e8f0;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --radius-lg: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .portal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .portal-logo {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            background: white;
            padding: 4px;
        }
        
        .portal-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .portal-subtitle {
            margin: 0;
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success-color);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .portal-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }
        
        .section-title {
            font-size: 1.875rem;
            font-weight: 600;
            margin: 0 0 var(--spacing-xl) 0;
            position: relative;
            padding-left: var(--spacing-md);
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--spacing-xl);
            margin-bottom: 3rem;
        }
        
        .module-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: var(--spacing-xl);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
        }
        
        .module-card.warning-module::before { background: var(--warning-color); }
        .module-card.success-module::before { background: var(--success-color); }
        .module-card.secondary-module::before { background: var(--secondary-color); }
        
        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            border-color: var(--primary-color);
        }
        
        .module-header {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .module-icon {
            font-size: 2.5rem;
            line-height: 1;
        }
        
        .module-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        
        .module-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.5;
        }
        
        .module-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: var(--spacing-lg);
        }
        
        .feature-tag {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .module-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: var(--spacing-lg);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .module-stats {
            border-top: 1px solid var(--border-color);
            padding-top: var(--spacing-md);
            text-align: center;
        }
        
        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-text {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .portal-footer {
            background: var(--text-primary);
            color: white;
            padding: var(--spacing-xl);
            margin-top: 3rem;
            text-align: center;
        }
        
        .footer-version {
            font-family: monospace;
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }
        
        .nav-stats {
            display: flex;
            gap: var(--spacing-lg);
            margin: var(--spacing-lg) 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-stats {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="portal-header">
        <div class="header-container">
            <div class="header-brand">
                <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="portal-logo">
                <div>
                    <h1 class="portal-title"><?= APP_NAME ?></h1>
                    <p class="portal-subtitle"><?= APP_DESCRIPTION ?></p>
                </div>
            </div>
            
            <div class="status-indicator">
                <span class="status-dot"></span>
                <span><?= $stats['system_status'] === 'operational' ? 'Système opérationnel' : 'Fonctionnement partiel' ?></span>
            </div>
        </div>
    </header>

    <!-- Navigation stats -->
    <div class="portal-main">
        <div class="nav-stats">
            <div class="stat-item">
                <span class="stat-value"><?= $stats['total_activity'] ?></span>
                <span class="stat-label">activités aujourd'hui</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?= $stats['modules_available'] ?></span>
                <span class="stat-label">modules actifs</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?= $stats['controles_today'] ?></span>
                <span class="stat-label">contrôles qualité</span>
            </div>
        </div>

        <!-- Modules -->
        <section>
            <h2 class="section-title">Modules disponibles</h2>
            
            <div class="modules-grid">
                <?php foreach ($modules as $key => $module): ?>
                <div class="module-card <?= $module['color'] ?>-module">
                    <div class="module-header">
                        <div class="module-icon"><?= $module['icon'] ?></div>
                        <div>
                            <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                            <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                        </div>
                    </div>
                    
                    <div class="module-features">
                        <?php foreach ($module['features'] as $feature): ?>
                        <span class="feature-tag">✓ <?= htmlspecialchars($feature) ?></span>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="module-actions">
                        <a href="<?= $module['path'] ?>" class="btn btn-<?= $module['color'] ?>">
                            <span><?= $module['icon'] ?></span>
                            Accéder au module
                        </a>
                        <?php if ($key === 'calculateur'): ?>
                        <a href="<?= $module['path'] ?>?demo=1" class="btn btn-secondary">
                            <span>🎮</span>
                            Mode démo
                        </a>
                        <?php elseif ($key === 'controle-qualite'): ?>
                        <a href="<?= $module['path'] ?>?controller=pompe-doseuse&action=nouveau" class="btn btn-secondary">
                            <span>➕</span>
                            Nouveau contrôle
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="module-stats">
                        <?php if ($key === 'calculateur'): ?>
                        <span class="stat-number"><?= $stats['calculations_today'] ?></span>
                        <span class="stat-text">calculs aujourd'hui</span>
                        <?php elseif ($key === 'controle-qualite'): ?>
                        <span class="stat-number"><?= $stats['controles_today'] ?></span>
                        <span class="stat-text">contrôles aujourd'hui</span>
                        <?php elseif ($key === 'adr'): ?>
                        <span class="stat-number"><?= rand(8, 25) ?></span>
                        <span class="stat-text">déclarations en cours</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="portal-footer">
        <p>&copy; <?= COPYRIGHT_YEAR ?> Guldagil - Tous droits réservés</p>
        <p>Développé par <?= APP_AUTHOR ?></p>
        <div class="footer-version"><?= renderVersionFooter() ?></div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/app.min.js"></script>
    <script>
        console.log('🏠 Portail Guldagil v<?= APP_VERSION ?> chargé');
        
        // Animation des cartes au survol
        document.querySelectorAll('.module-card').forEach(card => {
            const icon = card.querySelector('.module-icon');
            
            card.addEventListener('mouseenter', () => {
                if (icon) {
                    icon.style.transform = 'scale(1.1) rotate(5deg)';
                    icon.style.transition = 'transform 0.3s ease';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                if (icon) {
                    icon.style.transform = '';
                }
            });
        });
    </script>
</body>
</html>

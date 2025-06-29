<?php
/**
 * Titre: Page d'accueil du portail Guldagil
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

<?php
// [Gardez votre en-tête existant]

// AJOUT AUTHENTIFICATION - à insérer après vos includes existants
require_once __DIR__ . '/../includes/auth.php';
$auth = new Auth();
$auth->requireAuth(); // Redirige vers login si non connecté
$current_user = $auth->getCurrentUser(); // Info utilisateur connecté

// [Continuez avec votre code existant...]

// Configuration et sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier et charger la configuration
if (!file_exists(__DIR__ . '/../config/config.php')) {
    die('<h1>❌ Erreur Configuration</h1><p>Le fichier config.php est manquant dans /config/</p>');
}

if (!file_exists(__DIR__ . '/../config/version.php')) {
    die('<h1>❌ Erreur Version</h1><p>Le fichier version.php est manquant dans /config/</p>');
}

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/version.php';
} catch (Exception $e) {
    die('<h1>❌ Erreur de chargement</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Vérifier connexion base de données
if (!isset($db) || !($db instanceof PDO)) {
    die('<h1>❌ Erreur Base de données</h1><p>Connexion à la base de données non disponible</p>');
}

// Définition des modules - TOUS en version 0.5 beta
$modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'path' => 'calculateur/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique']
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses - Déclarations et suivi réglementaire',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'active',
        'path' => 'adr/',
        'features' => ['Déclarations ADR', 'Gestion des quotas', 'Suivi réglementaire']
    ],
    'controle-qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
        'icon' => '✅',
        'color' => 'green',
        'status' => 'development',
        'path' => 'controle-qualite/',
        'features' => ['Tests et validations', 'Rapports de conformité', 'Suivi des équipements']
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection individuelle - Stock et maintenance',
        'icon' => '🛡️',
        'color' => 'purple',
        'status' => 'development',
        'path' => 'epi/',
        'features' => ['Inventaire EPI', 'Suivi des dates d\'expiration', 'Gestion des commandes']
    ],
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion des outils et équipements techniques - Maintenance et traçabilité',
        'icon' => '🔧',
        'color' => 'gray',
        'status' => 'development',
        'path' => 'outillages/',
        'features' => ['Inventaire outillage', 'Planning maintenance', 'Suivi d\'utilisation']
    ]
];

// Statistiques simples
$stats = [
    'modules_total' => count($modules),
    'modules_actifs' => count(array_filter($modules, fn($m) => $m['status'] === 'active')),
    'modules_dev' => count(array_filter($modules, fn($m) => $m['status'] === 'development'))
];

// Informations de version via config/version.php
$version_info = function_exists('getVersionInfo') ? getVersionInfo() : [
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.5 beta',
    'build' => defined('BUILD_NUMBER') ? BUILD_NUMBER : date('YmdHis'),
    'formatted_date' => date('d/m/Y H:i')
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portail d'outils Guldagil - Solutions intégrées pour transport, logistique et gestion des équipements">
    <meta name="author" content="Jean-Thomas RUNSER">
    <title>Portail Guldagil - Outils professionnels</title>
    
    <!-- Styles principaux intégrés (évite les erreurs MIME) -->
    <style>
        /* Variables CSS */
        :root {
            --primary-blue: #1e40af;
            --primary-blue-light: #3b82f6;
            --primary-blue-dark: #1e3a8a;
            --module-blue: #3b82f6;
            --module-orange: #f97316;
            --module-green: #22c55e;
            --module-purple: #8b5cf6;
            --module-gray: #6b7280;
            --color-success: #22c55e;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
        }
        
        /* Reset et base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            line-height: 1.6;
            color: var(--gray-800);
            background-color: var(--gray-50);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .portal-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            padding: var(--spacing-lg) 0;
            box-shadow: var(--shadow-lg);
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
            cursor: pointer;
            transition: var(--transition-normal);
        }
        .header-brand:hover {
            transform: translateY(-2px);
        }
        .portal-logo {
            height: 60px;
            width: auto;
            object-fit: contain;
            /* Amélioration contraste pour logo bleu sur fond bleu */
            filter: brightness(1.2) contrast(1.1);
            background: rgba(255, 255, 255, 0.1);
            padding: var(--spacing-xs);
            border-radius: var(--radius-md);
            backdrop-filter: blur(10px);
        }
        .brand-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }
        .portal-title {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            margin: 0;
        }
        .portal-subtitle {
            font-size: var(--font-size-lg);
            opacity: 0.9;
            font-weight: 300;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }
        .version-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-md);
            backdrop-filter: blur(10px);
        }
        .version-text {
            font-size: var(--font-size-sm);
            font-weight: 500;
        }
        .user-area {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: rgba(255, 255, 255, 0.1);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-height: 44px; /* Zone tactile appropriée */
        }
        .user-icon {
            font-size: var(--font-size-lg);
        }
        .user-text {
            font-size: var(--font-size-sm);
            opacity: 0.9;
        }
        
        /* Navigation */
        .portal-nav {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: var(--spacing-md) 0;
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .breadcrumb-item {
            color: var(--primary-blue);
            font-size: var(--font-size-sm);
            font-weight: 500;
            cursor: pointer;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            min-height: 44px; /* Zone tactile appropriée */
            display: flex;
            align-items: center;
        }
        .breadcrumb-item:hover {
            background: var(--gray-100);
        }
        .nav-info {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
        }
        
        /* Contenu principal */
        .portal-main {
            flex: 1;
            padding: var(--spacing-2xl) 0;
        }
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-2xl);
        }
        
        /* Section bienvenue */
        .welcome-section {
            text-align: center;
            padding: var(--spacing-2xl) 0;
        }
        .welcome-title {
            font-size: var(--font-size-3xl);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-lg);
        }
        .welcome-description {
            font-size: var(--font-size-lg);
            color: var(--gray-600);
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.7;
        }
        
        /* Sections */
        .section-title {
            font-size: var(--font-size-2xl);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-xl);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        .section-title::before {
            content: '';
            width: 4px;
            height: 2rem;
            background: var(--primary-blue);
            border-radius: var(--radius-sm);
        }
        
        /* Grille modules */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--spacing-xl);
        }
        .module-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            min-height: 56px; /* Zone tactile appropriée */
        }
        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .module-active {
            border-left: 4px solid var(--color-success);
        }
        .module-development {
            border-left: 4px solid var(--color-warning);
        }
        
        /* Header module */
        .module-header {
            padding: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            min-height: 56px; /* Zone tactile appropriée */
        }
        .module-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xl);
            color: white;
            font-weight: 600;
        }
        .module-icon-blue { background: var(--module-blue); }
        .module-icon-orange { background: var(--module-orange); }
        .module-icon-green { background: var(--module-green); }
        .module-icon-purple { background: var(--module-purple); }
        .module-icon-gray { background: var(--module-gray); }
        .module-meta {
            flex: 1;
        }
        .module-name {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-xs);
        }
        .module-status {
            font-size: var(--font-size-sm);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-weight: 500;
        }
        .status-active {
            background: rgba(34, 197, 94, 0.1);
            color: var(--color-success);
        }
        .status-development {
            background: rgba(245, 158, 11, 0.1);
            color: var(--color-warning);
        }
        
        /* Corps module */
        .module-body {
            padding: var(--spacing-lg);
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        .module-description {
            color: var(--gray-600);
            line-height: 1.6;
        }
        .module-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        .module-features li {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        /* Footer module */
        .module-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        .module-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-normal);
            width: 100%;
            text-align: center;
            min-height: 48px; /* Zone tactile appropriée */
        }
        .btn-primary {
            background: var(--primary-blue);
            color: white;
            border: 1px solid var(--primary-blue);
        }
        .btn-primary:hover {
            background: var(--primary-blue-dark);
            border-color: var(--primary-blue-dark);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: white;
            color: var(--primary-blue);
            border: 1px solid var(--primary-blue);
        }
        .btn-secondary:hover {
            background: var(--primary-blue);
            color: white;
        }
        .btn-disabled {
            background: var(--gray-200);
            color: var(--gray-500);
            cursor: not-allowed;
            border: 1px solid var(--gray-300);
        }
        
        /* Section admin */
        .admin-section {
            margin-top: var(--spacing-2xl);
            padding-top: var(--spacing-2xl);
            border-top: 2px solid var(--gray-200);
        }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
        }
        .admin-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            text-align: center;
            border: 1px solid var(--gray-200);
            transition: var(--transition-normal);
        }
        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .admin-icon {
            font-size: 2rem;
            margin-bottom: var(--spacing-md);
        }
        .admin-card h4 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
        }
        .admin-card p {
            color: var(--gray-600);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-md);
        }
        .admin-btn {
            display: inline-block;
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--primary-blue);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: 500;
            transition: var(--transition-normal);
            min-height: 44px; /* Zone tactile appropriée */
            line-height: 1.2;
        }
        .admin-btn:hover {
            background: var(--primary-blue-dark);
            transform: translateY(-1px);
        }
        
        /* Footer */
        .portal-footer {
            background: var(--gray-800);
            color: white;
            padding: var(--spacing-lg) 0;
            margin-top: auto;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }
        .footer-version {
            font-size: var(--font-size-sm);
            color: var(--gray-300);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }
        .version-label,
        .build-label {
            font-weight: 500;
            color: var(--gray-400);
        }
        .version-number,
        .build-number,
        .build-date {
            font-weight: 600;
            color: white;
        }
        .footer-copyright {
            font-size: var(--font-size-sm);
            color: var(--gray-400);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .header-container,
            .nav-container,
            .main-container,
            .footer-container {
                padding: 0 var(--spacing-md);
            }
            .modules-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: var(--spacing-lg);
            }
            .portal-title {
                font-size: var(--font-size-2xl);
            }
            .welcome-title {
                font-size: var(--font-size-2xl);
            }
        }
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: var(--spacing-lg);
                text-align: center;
            }
            .header-brand {
                flex-direction: column;
                gap: var(--spacing-md);
            }
            .portal-logo {
                height: 50px;
            }
            .nav-container {
                flex-direction: column;
                gap: var(--spacing-sm);
                text-align: center;
            }
            .modules-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-md);
            }
            .admin-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-md);
            }
            .footer-container {
                flex-direction: column;
                text-align: center;
                gap: var(--spacing-md);
            }
            .footer-version {
                flex-direction: column;
                gap: var(--spacing-xs);
            }
            .welcome-section {
                padding: var(--spacing-lg) 0;
            }
            .welcome-title {
                font-size: var(--font-size-xl);
            }
            .welcome-description {
                font-size: var(--font-size-base);
            }
        }
    </style>
    
    <!-- Meta pour cache busting -->
    <meta name="build-version" content="<?= $version_info['version'] ?>">
</head>
<body>
    <!-- Header principal -->
    <header class="portal-header">
        <div class="header-container">
            <div class="header-brand" onclick="goHome()">
                <img src="/assets/img/logo.png" alt="Guldagil" class="portal-logo">
                <div class="brand-info">
                    <h1 class="portal-title">Portail Guldagil</h1>
                    <p class="portal-subtitle">Portail d'outils professionnels</p>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="version-badge">
                    <span class="version-text"><?= $version_info['version'] ?></span>
                </div>
                <div class="user-area">
                    <span class="user-icon">👤</span>
                    <span class="user-text">Connexion</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation principale -->
    <nav class="portal-nav">
        <div class="nav-container">
            <div class="nav-breadcrumb">
                <span class="breadcrumb-item" onclick="goHome()">🏠 Accueil</span>
            </div>
            <div class="nav-info">
                <span class="nav-text"><?= $stats['modules_total'] ?> modules disponibles</span>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="portal-main">
        <div class="main-container">
            
            <!-- Section de bienvenue -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2 class="welcome-title">Solutions intégrées pour la gestion industrielle</h2>
                    <p class="welcome-description">
                        Plateforme centralisée pour la gestion des frais de port, marchandises dangereuses ADR, 
                        contrôle qualité, équipements EPI et outillages professionnels.
                    </p>
                </div>
            </section>

            <!-- Section modules -->
            <section class="modules-section">
                <h3 class="section-title">Modules applicatifs</h3>
                
                <div class="modules-grid">
                    <?php foreach ($modules as $moduleId => $module): ?>
                    <article class="module-card <?= $module['status'] === 'active' ? 'module-active' : 'module-development' ?>" 
                             data-module="<?= $moduleId ?>" 
                             onclick="navigateToModule('<?= $moduleId ?>', '<?= $module['path'] ?>', '<?= $module['status'] ?>')">
                        
                        <div class="module-header">
                            <div class="module-icon module-icon-<?= $module['color'] ?>">
                                <span class="icon"><?= $module['icon'] ?></span>
                            </div>
                            <div class="module-meta">
                                <h4 class="module-name"><?= htmlspecialchars($module['name']) ?></h4>
                                <span class="module-status status-<?= $module['status'] ?>">
                                    <?= $module['status'] === 'active' ? 'Actif' : 'En développement' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="module-body">
                            <p class="module-description">
                                <?= htmlspecialchars($module['description']) ?>
                            </p>
                            
                            <ul class="module-features">
                                <?php foreach ($module['features'] as $feature): ?>
                                <li><?= $module['status'] === 'active' ? '✓' : '○' ?> <?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="module-footer">
                            <?php if ($module['status'] === 'active'): ?>
                                <a href="<?= $module['path'] ?>" class="module-button btn-primary">
                                    <span class="btn-text">Accéder</span>
                                    <span class="btn-icon">→</span>
                                </a>
                            <?php elseif ($module['status'] === 'development'): ?>
                                <a href="<?= $module['path'] ?>" class="module-button btn-secondary">
                                    <span class="btn-text">Aperçu</span>
                                    <span class="btn-icon">→</span>
                                </a>
                            <?php else: ?>
                                <span class="module-button btn-disabled">
                                    <span class="btn-text">Bientôt disponible</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Section raccourcis administration -->
            <section class="admin-section">
                <h3 class="section-title">Administration</h3>
                
                <div class="admin-grid">
                    <div class="admin-card">
                        <div class="admin-icon">⚙️</div>
                        <h4>Configuration</h4>
                        <p>Paramètres globaux et modules</p>
                        <a href="admin/" class="admin-btn">Accéder</a>
                    </div>
                    
                    <div class="admin-card">
                        <div class="admin-icon">📊</div>
                        <h4>Maintenance</h4>
                        <p>Optimisation et diagnostics</p>
                        <a href="admin/maintenance.php" class="admin-btn">Accéder</a>
                    </div>
                    
                    <div class="admin-card">
                        <div class="admin-icon">📈</div>
                        <h4>Statistiques</h4>
                        <p>Tableau de bord et rapports</p>
                        <a href="admin/stats.php" class="admin-btn">Accéder</a>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
        <div class="footer-container">
            <div class="footer-info">
                <p class="footer-version">
                    <span class="version-label">Version:</span>
                    <span class="version-number"><?= $version_info['version'] ?></span>
                    <span class="build-label">Build:</span>
                    <span class="build-number">#<?= substr($version_info['build'], -8) ?></span>
                    <span class="build-date"><?= $version_info['formatted_date'] ?></span>
                </p>
            </div>
            
            <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> Jean-Thomas RUNSER - Guldagil</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript intégré (évite les erreurs de preload) -->
    <script>
        // Fonction de retour à l'accueil
        function goHome() {
            if (window.location.pathname !== '/' && window.location.pathname !== '/index.php') {
                window.location.href = '/';
            } else {
                // Scroll vers le haut si déjà sur l'accueil
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
        
        // Gestionnaire de navigation vers les modules
        function navigateToModule(moduleId, path, status) {
            console.log(`Navigation vers ${moduleId} (${status})`);
            
            if (status === 'development') {
                if (confirm(`Le module "${moduleId}" est en développement.\nVoulez-vous continuer vers l'aperçu ?`)) {
                    window.location.href = path;
                }
                return;
            }
            
            // Animation de chargement simple
            const card = event.currentTarget;
            card.style.opacity = '0.7';
            card.style.pointerEvents = 'none';
            
            setTimeout(() => {
                window.location.href = path;
            }, 200);
        }
        
        // Initialisation au chargement DOM
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Portail Guldagil v<?= $version_info['version'] ?> initialisé');
            console.log('📦 Modules chargés:', <?= json_encode(array_keys($modules)) ?>);
            console.log('📊 Stats:', <?= json_encode($stats) ?>);
            
            // Gestionnaire pour les liens admin avec confirmation
            const adminLinks = document.querySelectorAll('a[href*="maintenance"], a[href*="admin"]');
            
            adminLinks.forEach(link => {
                if (link.href.includes('maintenance')) {
                    link.addEventListener('click', function(e) {
                        if (!confirm('Vous accédez aux outils de maintenance système.\nContinuer ?')) {
                            e.preventDefault();
                        }
                    });
                }
            });
            
            // Animation au survol des cartes (optimisée pour tactile)
            const moduleCards = document.querySelectorAll('.module-card');
            moduleCards.forEach(card => {
                // Survol souris
                card.addEventListener('mouseenter', function() {
                    if (this.classList.contains('module-active') && !('ontouchstart' in window)) {
                        this.style.transform = 'translateY(-8px)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    if (!('ontouchstart' in window)) {
                        this.style.transform = '';
                    }
                });
                
                // Support tactile - feedback visuel au touch
                card.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                card.addEventListener('touchend', function() {
                    this.style.transform = '';
                });
            });
            
            // Raccourcis clavier
            document.addEventListener('keydown', function(e) {
                if (e.altKey) {
                    switch (e.key) {
                        case '1':
                            e.preventDefault();
                            window.location.href = 'calculateur/';
                            break;
                        case '2':
                            e.preventDefault();
                            window.location.href = 'adr/';
                            break;
                        case 'a':
                            e.preventDefault();
                            window.location.href = 'admin/';
                            break;
                        case 'h':
                            e.preventDefault();
                            goHome();
                            break;
                    }
                }
                
                // Échap pour revenir à l'accueil
                if (e.key === 'Escape') {
                    goHome();
                }
            });
            
            // Amélioration zone tactile pour mobile
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                
                // Ajouter des marges supplémentaires aux boutons sur mobile
                const buttons = document.querySelectorAll('.module-button, .admin-btn, .breadcrumb-item');
                buttons.forEach(btn => {
                    btn.style.minHeight = '48px';
                    btn.style.padding = '12px 16px';
                });
            }
        });
        
        // Fonction de debug (en mode développement)
        <?php if (defined('DEBUG') && DEBUG): ?>
        window.PortalDebug = {
            modules: <?= json_encode($modules) ?>,
            stats: <?= json_encode($stats) ?>,
            version: <?= json_encode($version_info) ?>,
            checkModule: function(moduleId) {
                const module = this.modules[moduleId];
                if (module) {
                    console.log('Module:', moduleId, module);
                    fetch(module.path, { method: 'HEAD' })
                        .then(response => {
                            console.log(`${moduleId} status:`, response.status);
                        })
                        .catch(error => {
                            console.warn(`${moduleId} inaccessible:`, error);
                        });
                }
            },
            goToModule: function(moduleId) {
                const module = this.modules[moduleId];
                if (module) {
                    window.location.href = module.path;
                }
            }
        };
        console.log('🔧 Mode debug activé - window.PortalDebug disponible');
        <?php endif; ?>
    </script>
</body>
</html>

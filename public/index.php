<?php
/**
 * VERSION D'URGENCE - Portail fonctionnel
 * À utiliser jusqu'à résolution des problèmes de config
 */

// Démarrage session
session_start();

// Mode debug pour voir les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tentative de chargement config (OPTIONNEL)
$config_loaded = false;
$possible_configs = ['/config/config.php', '../config/config.php'];

foreach ($possible_configs as $config_path) {
    if (file_exists($config_path)) {
        try {
            require_once $config_path;
            $config_loaded = true;
            break;
        } catch (Exception $e) {
            // Ignorer les erreurs de config pour l'instant
        }
    }
}

// Variables de base (avec ou sans config)
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$version = defined('APP_VERSION') ? APP_VERSION : '0.5-emergency';
$build = defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : 'EMERGENCY';

// Authentification temporaire DÉSACTIVÉE
$user_authenticated = true; // BYPASS pour l'urgence
$current_user = [
    'username' => 'Admin Urgence',
    'role' => 'admin',
    'email' => 'admin@guldagil.com'
];

// Modules disponibles (codés en dur)
$modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'icon' => '🧮',
        'path' => '/port/',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'status' => 'active'
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'icon' => '⚠️',
        'path' => '/adr/',
        'description' => 'Transport de marchandises dangereuses',
        'status' => 'active'
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'icon' => '✅',
        'path' => '/qualite/',
        'description' => 'Contrôle et validation des équipements',
        'status' => 'active'
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'icon' => '🛡️',
        'path' => '/epi/',
        'description' => 'Gestion des équipements de protection',
        'status' => 'active'
    ],
    'admin' => [
        'name' => 'Administration',
        'icon' => '⚙️',
        'path' => '/admin/',
        'description' => 'Configuration et gestion du portail',
        'status' => 'active'
    ]
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?> - Portail</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌊</text></svg>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            font-size: 2.5rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f7fafc;
            padding: 10px 20px;
            border-radius: 25px;
            border: 2px solid #e2e8f0;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .emergency-notice {
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .module-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .module-card:hover {
            transform: translateY(-8px);
            border-color: #667eea;
            box-shadow: 0 12px 40px rgba(102,126,234,0.3);
        }

        .module-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .module-icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .module-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
        }

        .module-description {
            color: #718096;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .btn-secondary {
            background: linear-gradient(45deg, #a78bfa, #c084fc);
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 30px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            color: white;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <div class="logo-icon">🌊</div>
                <div>
                    <h1><?= htmlspecialchars($app_name) ?></h1>
                    <div style="font-size: 0.9rem; color: #718096;">Solutions professionnelles</div>
                </div>
            </div>
            <div class="user-info">
                <div class="avatar"><?= strtoupper(substr($current_user['username'], 0, 1)) ?></div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($current_user['username']) ?></div>
                    <div style="font-size: 0.8rem; color: #718096;"><?= ucfirst($current_user['role']) ?></div>
                </div>
            </div>
        </header>

        <!-- Notice d'urgence -->
        <div class="emergency-notice">
            🚨 <strong>MODE URGENCE ACTIVÉ</strong> - Portail fonctionnel en configuration simplifiée
            <br><small>Version <?= htmlspecialchars($version) ?> • Build <?= htmlspecialchars($build) ?> • Config: <?= $config_loaded ? 'Chargée' : 'Mode secours' ?></small>
        </div>

        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-value"><?= count($modules) ?></div>
                <div class="stat-label">Modules disponibles</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚀</div>
                <div class="stat-value"><?= count(array_filter($modules, fn($m) => $m['status'] === 'active')) ?></div>
                <div class="stat-label">Modules actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-value"><?= htmlspecialchars($version) ?></div>
                <div class="stat-label">Version portail</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-value"><?= ucfirst($current_user['role']) ?></div>
                <div class="stat-label">Niveau d'accès</div>
            </div>
        </div>

        <!-- Modules -->
        <h2 class="section-title">🚀 Modules du portail</h2>
        <div class="modules-grid">
            <?php foreach ($modules as $key => $module): ?>
            <div class="module-card">
                <div class="module-header">
                    <div class="module-icon"><?= $module['icon'] ?></div>
                    <div>
                        <div class="module-title"><?= htmlspecialchars($module['name']) ?></div>
                        <div style="font-size: 0.8rem; color: #38a169; font-weight: 500;">● OPÉRATIONNEL</div>
                    </div>
                </div>
                <div class="module-description">
                    <?= htmlspecialchars($module['description']) ?>
                </div>
                <a href="<?= htmlspecialchars($module['path']) ?>" class="btn">
                    Accéder au module
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Actions rapides -->
        <h2 class="section-title" style="margin-top: 40px;">⚡ Actions rapides</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="/port/" class="btn" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">🧮</div>
                Nouveau calcul
            </a>
            <a href="/adr/" class="btn" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">⚠️</div>
                Déclaration ADR
            </a>
            <a href="/admin/" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">⚙️</div>
                Administration
            </a>
            <a href="/auth/login.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">🔐</div>
                Authentification
            </a>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <h3><?= htmlspecialchars($app_name) ?></h3>
            <p>Solutions professionnelles pour le secteur du traitement des eaux</p>
            <div style="margin-top: 15px; font-size: 0.9rem; opacity: 0.8;">
                Version <?= htmlspecialchars($version) ?> • Build <?= htmlspecialchars($build) ?> • <?= date('Y') ?> Jean-Thomas RUNSER
            </div>
        </footer>
    </div>
</body>
</html>

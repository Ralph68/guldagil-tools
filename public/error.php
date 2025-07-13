<?php
/**
 * Titre: Page d'erreur centralisée
 * Chemin: /public/error.php
 * Version: 0.5 beta + build auto
 */

// Protection et initialisation
session_start();
define('ROOT_PATH', dirname(__DIR__));

// Configuration des erreurs
$is_production = (getenv('APP_ENV') === 'production');
if (!$is_production) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Chargement version si disponible
$version_info = ['version' => '0.5-beta', 'build' => '????????', 'date' => date('Y-m-d')];
if (file_exists(ROOT_PATH . '/config/version.php')) {
    try {
        require_once ROOT_PATH . '/config/version.php';
        $version_info = [
            'version' => defined('APP_VERSION') ? APP_VERSION : '0.5-beta',
            'build' => defined('BUILD_NUMBER') ? substr(BUILD_NUMBER, 0, 8) : '????????',
            'date' => defined('BUILD_DATE') ? BUILD_DATE : date('Y-m-d')
        ];
    } catch (Exception $e) {
        error_log("Erreur chargement version: " . $e->getMessage());
    }
}

// Types d'erreurs gérés
$error_types = [
    'db' => [
        'title' => 'Erreur Base de Données',
        'icon' => '🗃️',
        'message' => 'La connexion à la base de données a échoué.',
        'description' => 'Le service est temporairement indisponible. Réessayez dans quelques minutes.',
        'code' => 503
    ],
    'config' => [
        'title' => 'Erreur Configuration',
        'icon' => '⚙️',
        'message' => 'Configuration système incorrecte.',
        'description' => 'Un fichier de configuration est manquant ou invalide.',
        'code' => 500
    ],
    'auth' => [
        'title' => 'Erreur Authentification',
        'icon' => '🔐',
        'message' => 'Problème d\'authentification.',
        'description' => 'Votre session a expiré ou est invalide.',
        'code' => 401
    ],
    'access' => [
        'title' => 'Accès Refusé',
        'icon' => '🚫',
        'message' => 'Vous n\'avez pas les droits nécessaires.',
        'description' => 'Contactez un administrateur si vous pensez que c\'est une erreur.',
        'code' => 403
    ],
    'notfound' => [
        'title' => 'Page Non Trouvée',
        'icon' => '🔍',
        'message' => 'La ressource demandée n\'existe pas.',
        'description' => 'Vérifiez l\'URL ou retournez à l\'accueil.',
        'code' => 404
    ],
    'server' => [
        'title' => 'Erreur Serveur',
        'icon' => '💥',
        'message' => 'Une erreur interne s\'est produite.',
        'description' => 'L\'équipe technique a été notifiée.',
        'code' => 500
    ]
];

// Détection du type d'erreur
$error_type = $_GET['type'] ?? 'server';
if (!isset($error_types[$error_type])) {
    $error_type = 'server';
}

$error = $error_types[$error_type];
http_response_code($error['code']);

// Variables pour template
$page_title = $error['title'];
$page_subtitle = 'Portail Guldagil';
$app_name = 'Guldagil';
$current_module = 'error';

// Informations de debug (uniquement en développement)
$debug_info = [];
if (!$is_production && isset($_GET['debug'])) {
    $debug_info = [
        'time' => date('Y-m-d H:i:s'),
        'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'N/A'
    ];
}

// Log de l'erreur
$log_message = sprintf(
    "Erreur %s: %s - URL: %s - IP: %s",
    $error_type,
    $error['message'],
    $_SERVER['REQUEST_URI'] ?? 'N/A',
    $_SERVER['REMOTE_ADDR'] ?? 'N/A'
);
error_log($log_message);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($app_name) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <style>
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --primary-blue-light: #63b3ed;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --error: #ef4444;
            --warning: #f59e0b;
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--gray-800);
        }

        .error-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 10px;
        }

        .error-code {
            font-size: 1.25rem;
            color: var(--error);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .error-message {
            font-size: 1.125rem;
            color: var(--gray-700);
            margin-bottom: 10px;
            font-weight: 500;
        }

        .error-description {
            color: var(--gray-600);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-blue-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            border-color: var(--gray-400);
        }

        .debug-info {
            margin-top: 30px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--radius);
            border-left: 4px solid var(--warning);
            text-align: left;
        }

        .debug-info h3 {
            color: var(--warning);
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .debug-info dl {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px;
            font-size: 0.875rem;
        }

        .debug-info dt {
            font-weight: 600;
            color: var(--gray-700);
        }

        .debug-info dd {
            color: var(--gray-600);
            word-break: break-all;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .version-info {
            margin-top: 10px;
            font-size: 0.75rem;
            color: var(--gray-400);
        }

        @media (max-width: 640px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <span class="error-icon"><?= $error['icon'] ?></span>
        
        <h1 class="error-title"><?= htmlspecialchars($error['title']) ?></h1>
        <div class="error-code">Erreur <?= $error['code'] ?></div>
        
        <p class="error-message"><?= htmlspecialchars($error['message']) ?></p>
        <p class="error-description"><?= htmlspecialchars($error['description']) ?></p>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                🏠 Retour à l'accueil
            </a>
            
            <?php if ($error_type === 'auth'): ?>
            <a href="/auth/login.php" class="btn btn-secondary">
                🔐 Se reconnecter
            </a>
            <?php else: ?>
            <a href="javascript:history.back()" class="btn btn-secondary">
                ← Page précédente
            </a>
            <?php endif; ?>
            
            <?php if (!$is_production): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['debug' => '1'])) ?>" class="btn btn-secondary">
                🔧 Infos debug
            </a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($debug_info)): ?>
        <div class="debug-info">
            <h3>🔧 Informations de débogage</h3>
            <dl>
                <?php foreach ($debug_info as $key => $value): ?>
                <dt><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?>:</dt>
                <dd><?= htmlspecialchars($value) ?></dd>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php endif; ?>
        
        <footer class="footer">
            <p><strong><?= htmlspecialchars($app_name) ?></strong> - Solutions professionnelles</p>
            <p>© <?= date('Y') ?> Jean-Thomas RUNSER - Tous droits réservés</p>
            
            <div class="version-info">
                Version <?= htmlspecialchars($version_info['version']) ?> 
                • Build <?= htmlspecialchars($version_info['build']) ?>
                • <?= htmlspecialchars($version_info['date']) ?>
            </div>
        </footer>
    </div>
</body>
</html>

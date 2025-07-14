<?php
/**
 * Titre: Page d'erreur centralisée avec signalement
 * Chemin: /public/erors/error.php
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
        
    // Ajout d'informations système avancées pour résolution rapide
    if (function_exists('disk_free_space')) {
        $error_details['disk_free'] = round(disk_free_space('.') / 1024 / 1024 / 1024, 2) . ' GB';
    }
    
    // Vérifications spécifiques selon le type d'erreur
    switch ($error_type) {
        case 'db':
            $error_details['db_check'] = 'Tentative de connexion...';
            if (file_exists(ROOT_PATH . '/config/config.php')) {
                try {
                    require_once ROOT_PATH . '/config/config.php';
                    if (isset($db) && $db instanceof PDO) {
                        $error_details['db_check'] = 'Connexion BDD OK - Erreur ailleurs';
                    } else {
                        $error_details['db_check'] = 'Variable $db non définie ou invalide';
                    }
                } catch (Exception $e) {
                    $error_details['db_check'] = 'Exception: ' . $e->getMessage();
                }
            } else {
                $error_details['db_check'] = 'Fichier config.php manquant';
            }
            break;
            
        case 'config':
            $error_details['config_files'] = [];
            $config_files = [
                '/config/config.php',
                '/config/version.php',
                '/config/database.php',
                '/.env'
            ];
            foreach ($config_files as $file) {
                $path = ROOT_PATH . $file;
                $error_details['config_files'][] = $file . ': ' . (file_exists($path) ? 
                    'Existe (' . filesize($path) . ' bytes)' : 'MANQUANT');
            }
            break;
            
        case 'auth':
            $error_details['auth_details'] = [
                'session_id' => session_id(),
                'session_status' => session_status(),
                'session_vars' => isset($_SESSION) ? array_keys($_SESSION) : 'Aucune',
                'cookies' => isset($_COOKIE) ? array_keys($_COOKIE) : 'Aucun'
            ];
            break;
    }
    
    // Vérification des logs récents
    $log_files = [
        '/storage/logs/app.log',
        '/storage/logs/error.log',
        ini_get('error_log')
    ];
    
    $recent_logs = [];
    foreach ($log_files as $log_file) {
        if ($log_file && file_exists($log_file) && is_readable($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines) {
                $recent_logs[] = "=== " . basename($log_file) . " (5 dernières lignes) ===";
                $recent_logs = array_merge($recent_logs, array_slice($lines, -5));
                $recent_logs[] = "";
            }
        }
    }
    
    if (empty($recent_logs)) {
        $recent_logs[] = "Aucun log récent accessible";
    }
    } catch (Exception $e) {
        error_log("Erreur chargement version: " . $e->getMessage());
    }
}

// Configuration mail admin
$admin_email = 'runser.jean.thomas@guldagil.com';
$mail_sent = false;
$mail_error = '';

// Traitement signalement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report') {
    $user_message = trim($_POST['user_message'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    
    // Collecte des informations de l'erreur
    $error_type = $_GET['type'] ?? 'server';
    $error_details = [
        'timestamp' => date('Y-m-d H:i:s'),
        'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'full_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'unknown') . ($_SERVER['REQUEST_URI'] ?? ''),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
        'real_ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'N/A',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'N/A',
        'error_type' => $error_type,
        'session_data' => isset($_SESSION['user']) ? $_SESSION['user']['username'] ?? 'Inconnu' : 'Non connecté',
        'session_id' => session_id(),
        'version' => $version_info['version'],
        'build' => $version_info['build'],
        'server' => $_SERVER['SERVER_NAME'] ?? 'N/A',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'php_version' => phpversion(),
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        'loaded_extensions' => implode(', ', array_slice(get_loaded_extensions(), 0, 10)) . '...',
        'error_reporting' => error_reporting(),
        'display_errors' => ini_get('display_errors'),
        'log_errors' => ini_get('log_errors'),
        'post_data' => !empty($_POST) ? 'POST: ' . json_encode(array_keys($_POST)) : 'Aucune données POST',
        'get_data' => !empty($_GET) ? 'GET: ' . json_encode($_GET) : 'Aucune données GET',
        'cookies' => !empty($_COOKIE) ? 'Cookies: ' . count($_COOKIE) . ' présents' : 'Aucun cookie',
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'N/A',
        'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'N/A'
    ];
    
    // Construction du message mail
    $subject = "🚨 Erreur signalée - Portail Guldagil (" . strtoupper($error_type) . ")";
    
    $body = "🚨 ERREUR SIGNALÉE - PORTAIL GULDAGIL 🚨\n\n";
    $body .= "=== RÉSUMÉ EXÉCUTIF ===\n";
    $body .= "Type: " . strtoupper($error_type) . "\n";
    $body .= "URL: " . $error_details['full_url'] . "\n";
    $body .= "Utilisateur: " . $error_details['session_data'] . "\n";
    $body .= "Timestamp: " . $error_details['timestamp'] . "\n\n";
    
    $body .= "=== DÉTAILS TECHNIQUES COMPLETS ===\n";
    $body .= "🌐 REQUÊTE\n";
    $body .= "URL complète: " . $error_details['full_url'] . "\n";
    $body .= "Méthode: " . $error_details['method'] . "\n";
    $body .= "Referer: " . $error_details['referer'] . "\n";
    $body .= "Données POST: " . $error_details['post_data'] . "\n";
    $body .= "Données GET: " . $error_details['get_data'] . "\n\n";
    
    $body .= "👤 UTILISATEUR\n";
    $body .= "IP réelle: " . $error_details['real_ip'] . "\n";
    $body .= "IP apparente: " . $error_details['ip'] . "\n";
    $body .= "User-Agent: " . $error_details['user_agent'] . "\n";
    $body .= "Langue: " . $error_details['accept_language'] . "\n";
    $body .= "Encodage: " . $error_details['accept_encoding'] . "\n";
    $body .= "Session ID: " . $error_details['session_id'] . "\n";
    $body .= $error_details['cookies'] . "\n\n";
    
    $body .= "🖥️ SERVEUR & SYSTÈME\n";
    $body .= "Serveur: " . $error_details['server'] . "\n";
    $body .= "Software: " . $error_details['server_software'] . "\n";
    $body .= "PHP: " . $error_details['php_version'] . "\n";
    $body .= "Mémoire utilisée: " . $error_details['memory_usage'] . "\n";
    $body .= "Pic mémoire: " . $error_details['memory_peak'] . "\n";
    if (isset($error_details['disk_free'])) {
        $body .= "Espace libre: " . $error_details['disk_free'] . "\n";
    }
    $body .= "Extensions PHP: " . $error_details['loaded_extensions'] . "\n";
    $body .= "Error reporting: " . $error_details['error_reporting'] . "\n";
    $body .= "Display errors: " . $error_details['display_errors'] . "\n";
    $body .= "Log errors: " . $error_details['log_errors'] . "\n\n";
    
    $body .= "🔧 VERSION & BUILD\n";
    $body .= "Version: " . $error_details['version'] . "\n";
    $body .= "Build: " . $error_details['build'] . "\n\n";
    
    // Informations spécifiques selon le type d'erreur
    switch ($error_type) {
        case 'db':
            $body .= "🗃️ DIAGNOSTIC BASE DE DONNÉES\n";
            $body .= "Test connexion: " . $error_details['db_check'] . "\n\n";
            break;
            
        case 'config':
            $body .= "⚙️ DIAGNOSTIC CONFIGURATION\n";
            foreach ($error_details['config_files'] as $file_info) {
                $body .= $file_info . "\n";
            }
            $body .= "\n";
            break;
            
        case 'auth':
            $body .= "🔐 DIAGNOSTIC AUTHENTIFICATION\n";
            $body .= "Session ID: " . $error_details['auth_details']['session_id'] . "\n";
            $body .= "Session status: " . $error_details['auth_details']['session_status'] . "\n";
            $body .= "Variables session: " . (is_array($error_details['auth_details']['session_vars']) ? 
                implode(', ', $error_details['auth_details']['session_vars']) : 
                $error_details['auth_details']['session_vars']) . "\n";
            $body .= "Cookies: " . (is_array($error_details['auth_details']['cookies']) ? 
                implode(', ', $error_details['auth_details']['cookies']) : 
                $error_details['auth_details']['cookies']) . "\n\n";
            break;
    }
    
    $body .= "📋 LOGS RÉCENTS\n";
    $body .= implode("\n", $recent_logs) . "\n\n";
    
    if (!empty($user_message)) {
        $body .= "💬 MESSAGE UTILISATEUR\n";
        $body .= $user_message . "\n\n";
    }
    
    if (!empty($user_email)) {
        $body .= "📧 CONTACT UTILISATEUR\n";
        $body .= "Email: " . $user_email . "\n\n";
    }
    
    $body .= "🔗 ACTIONS RAPIDES\n";
    $body .= "Lien direct: " . $error_details['full_url'] . "\n";
    $body .= "Logs serveur: ssh et vérifier /var/log/apache2/error.log\n";
    $body .= "Monitoring: https://gul.runser.ovh/admin/logs.php\n\n";
    
    $body .= "🚀 SUGGESTIONS RÉSOLUTION\n";
    switch ($error_type) {
        case 'db':
            $body .= "- Vérifier statut MySQL/MariaDB\n";
            $body .= "- Contrôler /config/config.php\n";
            $body .= "- Tester connexion BDD manuellement\n";
            break;
        case 'config':
            $body .= "- Vérifier permissions fichiers config\n";
            $body .= "- Recréer fichiers manquants\n";
            $body .= "- Contrôler syntaxe PHP\n";
            break;
        case 'auth':
            $body .= "- Vérifier configuration session PHP\n";
            $body .= "- Contrôler table auth_users\n";
            $body .= "- Nettoyer sessions expirées\n";
            break;
        default:
            $body .= "- Vérifier logs PHP et Apache\n";
            $body .= "- Contrôler permissions fichiers\n";
            $body .= "- Redémarrer services si nécessaire\n";
    }
    $body .= "\n";
    
    $body .= "Ce rapport automatique a été généré par le système d'erreurs Guldagil v" . $error_details['version'] . "\n";
    
    // Headers mail
    $headers = [
        'From' => 'noreply@guldagil.com',
        'Reply-To' => !empty($user_email) ? $user_email : 'noreply@guldagil.com',
        'X-Mailer' => 'Guldagil Portal Error Reporter',
        'Content-Type' => 'text/plain; charset=UTF-8'
    ];
    
    // Tentative d'envoi
    try {
        $mail_sent = mail($admin_email, $subject, $body, implode("\r\n", array_map(
            function($k, $v) { return "$k: $v"; }, 
            array_keys($headers), 
            array_values($headers)
        )));
        
        if (!$mail_sent) {
            $mail_error = "Erreur d'envoi de mail";
        }
    } catch (Exception $e) {
        $mail_error = "Exception mail: " . $e->getMessage();
        error_log("Erreur envoi mail signalement: " . $e->getMessage());
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
            --success: #10b981;
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

        .error-message {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--error);
            margin-bottom: 15px;
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
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
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
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
            transform: translateY(-1px);
        }

        .btn-report {
            background: var(--warning);
            color: white;
        }

        .btn-report:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .report-section {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 25px;
            margin: 30px 0;
            text-align: left;
            border: 1px solid var(--gray-200);
        }

        .report-section h3 {
            color: var(--gray-900);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .alert {
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .debug-info {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }

        .debug-info h3 {
            color: var(--gray-900);
            margin-bottom: 15px;
        }

        .debug-info dl {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px 15px;
        }

        .debug-info dt {
            font-weight: 600;
            color: var(--gray-700);
        }

        .debug-info dd {
            color: var(--gray-600);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }

        .footer {
            border-top: 1px solid var(--gray-200);
            padding-top: 20px;
            margin-top: 30px;
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .version-info {
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            color: var(--gray-400);
        }

        .hidden {
            display: none;
        }

        @media (max-width: 640px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
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
        <span class="error-icon"><?= htmlspecialchars($error['icon']) ?></span>
        <h1 class="error-title"><?= htmlspecialchars($error['title']) ?></h1>
        <p class="error-message"><?= htmlspecialchars($error['message']) ?></p>
        <p class="error-description"><?= htmlspecialchars($error['description']) ?></p>
        
        <?php if ($mail_sent): ?>
        <div class="alert alert-success">
            <span>✅</span>
            <span>Le problème a été signalé à l'équipe technique. Nous vous répondrons rapidement si vous avez fourni votre email.</span>
        </div>
        <?php elseif (!empty($mail_error)): ?>
        <div class="alert alert-error">
            <span>❌</span>
            <span>Erreur lors de l'envoi du signalement. Veuillez contacter directement l'administrateur.</span>
        </div>
        <?php endif; ?>
        
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
            
            <button onclick="toggleReportForm()" class="btn btn-report" id="reportBtn">
                📧 Signaler le problème
            </button>
            
            <?php if (!$is_production): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['debug' => '1'])) ?>" class="btn btn-secondary">
                🔧 Infos debug
            </a>
            <?php endif; ?>
        </div>
        
        <div id="reportForm" class="report-section hidden">
            <h3>📧 Signaler ce problème à l'équipe technique</h3>
            <p style="color: var(--gray-600); margin-bottom: 20px;">
                Décrivez brièvement le problème rencontré. Les détails techniques seront automatiquement inclus.
            </p>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="report">
                
                <div class="form-group">
                    <label for="user_message">Description du problème (optionnel) :</label>
                    <textarea 
                        id="user_message" 
                        name="user_message" 
                        placeholder="Que faisiez-vous quand l'erreur s'est produite ? Autres détails utiles..."
                    ></textarea>
                </div>
                
                <div class="form-group">
                    <label for="user_email">Votre email (optionnel, pour suivi) :</label>
                    <input 
                        type="email" 
                        id="user_email" 
                        name="user_email" 
                        placeholder="votre.email@exemple.com"
                    >
                </div>
                
                <div class="error-actions">
                    <button type="submit" class="btn btn-report">
                        📧 Envoyer le signalement
                    </button>
                    <button type="button" onclick="toggleReportForm()" class="btn btn-secondary">
                        Annuler
                    </button>
                </div>
            </form>
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

    <script>
        function toggleReportForm() {
            const form = document.getElementById('reportForm');
            const btn = document.getElementById('reportBtn');
            
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                btn.textContent = '📧 Masquer le formulaire';
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                form.classList.add('hidden');
                btn.textContent = '📧 Signaler le problème';
            }
        }
        
        // Auto-scroll vers les alertes si présentes
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>

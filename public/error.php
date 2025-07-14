<?php
/**
 * Titre: Gestionnaire d'erreurs simple sans redirection circulaire
 * Chemin: /public/error.php
 * Version: 0.5 beta + build auto
 */

// Empêcher les redirections infinies
if (isset($_GET['error_handled']) || basename($_SERVER['SCRIPT_NAME']) === 'error.php') {
    // Déjà en train de gérer une erreur, affichage direct
}

session_start();

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/version.php';

// Détection du mode développement
$is_dev = (
    (isset($_SESSION['user']) && ($_SESSION['user']['role'] === 'dev' || $_SESSION['user']['role'] === 'admin')) ||
    (defined('DEBUG') && DEBUG === true) ||
    (getenv('APP_ENV') === 'development')
);

// Configuration des erreurs par type
$error_configs = [
    '404' => [
        'title' => 'Page non trouvée',
        'icon' => '🔍',
        'message' => 'La page que vous cherchez n\'existe pas.',
        'description' => 'Vérifiez l\'URL ou retournez à l\'accueil.',
        'code' => 404
    ],
    '403' => [
        'title' => 'Accès refusé',
        'icon' => '🚫',
        'message' => 'Vous n\'avez pas les droits nécessaires.',
        'description' => 'Contactez un administrateur si nécessaire.',
        'code' => 403
    ],
    '500' => [
        'title' => 'Erreur serveur',
        'icon' => '💥',
        'message' => 'Une erreur interne s\'est produite.',
        'description' => 'L\'équipe technique a été notifiée.',
        'code' => 500
    ],
    '503' => [
        'title' => 'Service indisponible',
        'icon' => '🔧',
        'message' => 'Le service est temporairement indisponible.',
        'description' => 'Réessayez dans quelques minutes.',
        'code' => 503
    ]
];

// Récupération du type d'erreur
$error_type = $_GET['type'] ?? $_GET['code'] ?? '500';
if (!isset($error_configs[$error_type])) {
    $error_type = '500';
}

$error = $error_configs[$error_type];
http_response_code($error['code']);

// Traitement du signalement d'erreur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'report_error') {
    $report_sent = false;
    
    if (isset($_POST['user_message'])) {
        $error_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $error_type,
            'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'N/A',
            'user_message' => trim($_POST['user_message']),
            'session_data' => $is_dev ? print_r($_SESSION, true) : 'Masqué'
        ];
        
        // Log de l'erreur
        $log_message = sprintf(
            "[%s] Erreur signalée par utilisateur - Type: %s - URL: %s - Message: %s\n",
            $error_data['timestamp'],
            $error_data['type'],
            $error_data['url'],
            $error_data['user_message']
        );
        
        error_log($log_message, 3, ROOT_PATH . '/storage/logs/user_reports.log');
        
        // Envoi email (optionnel, si configuration mail disponible)
        try {
            if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
                $subject = "Erreur signalée - Portail Guldagil";
                $body = "Erreur signalée par un utilisateur :\n\n" . print_r($error_data, true);
                mail(ADMIN_EMAIL, $subject, $body);
            }
            $report_sent = true;
        } catch (Exception $e) {
            // Échec envoi mail, continuer
        }
    }
}

// Variables pour template
$page_title = $error['title'];
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '01';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($error['title']) ?> - Portail Guldagil</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 600px;
            text-align: center;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: 900;
            color: #e74c3c;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .error-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .error-description {
            font-size: 1.1rem;
            color: #7f8c8d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .btn-report {
            background: #e67e22;
            color: white;
        }
        
        .btn-report:hover {
            background: #d35400;
        }
        
        .report-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
            display: none;
        }
        
        .report-form.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .debug-info {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            text-align: left;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .success-message {
            background: #27ae60;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <span class="error-icon"><?= $error['icon'] ?></span>
        <div class="error-code"><?= $error['code'] ?></div>
        <h1 class="error-title"><?= htmlspecialchars($error['title']) ?></h1>
        <p class="error-description"><?= htmlspecialchars($error['description']) ?></p>
        
        <?php if (isset($report_sent) && $report_sent): ?>
            <div class="success-message">
                ✅ Votre signalement a été envoyé avec succès. Merci !
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="/" class="btn btn-primary">
                🏠 Retour à l'accueil
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                ⬅️ Page précédente
            </button>
            <button onclick="toggleReportForm()" class="btn btn-report">
                📧 Signaler le problème
            </button>
        </div>
        
        <div id="reportForm" class="report-form">
            <form method="POST">
                <input type="hidden" name="action" value="report_error">
                <div class="form-group">
                    <label for="user_message">Décrivez le problème rencontré :</label>
                    <textarea name="user_message" id="user_message" 
                              placeholder="Décrivez ce que vous faisiez quand l'erreur s'est produite..."
                              required></textarea>
                </div>
                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        📤 Envoyer le signalement
                    </button>
                    <button type="button" onclick="toggleReportForm()" class="btn btn-secondary">
                        ❌ Annuler
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($is_dev): ?>
            <div class="debug-info">
                <strong>🔧 Informations de debug :</strong><br>
                URL: <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') ?><br>
                Method: <?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A') ?><br>
                User Agent: <?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') ?><br>
                IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A') ?><br>
                Referer: <?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'N/A') ?><br>
                Time: <?= date('Y-m-d H:i:s') ?><br>
                <?php if (isset($_GET['debug_message'])): ?>
                Message: <?= htmlspecialchars($_GET['debug_message']) ?><br>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Portail Guldagil v<?= htmlspecialchars($app_version) ?> - Build <?= htmlspecialchars($build_number) ?></p>
            <p>&copy; <?= date('Y') ?> Jean-Thomas RUNSER</p>
        </div>
    </div>
    
    <script>
        function toggleReportForm() {
            const form = document.getElementById('reportForm');
            form.classList.toggle('active');
            
            if (form.classList.contains('active')) {
                document.getElementById('user_message').focus();
            }
        }
        
        // Auto-refresh en cas d'erreur 503
        <?php if ($error_type === '503'): ?>
        setTimeout(() => {
            window.location.reload();
        }, 30000); // Refresh après 30 secondes
        <?php endif; ?>
    </script>
</body>
</html>

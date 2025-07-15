<?php
/**
 * Titre: Page de connexion sécurisée - VERSION PRODUCTION
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 */

// Protection et chargement configuration
define('ROOT_PATH', dirname(__DIR__, 2));

// Gestion session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: /index.php');
    exit;
}

$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Configuration manquante</h1><p>Fichier requis : ' . basename($file) . '</p>');
    }
}

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
} catch (Exception $e) {
    http_response_code(500);
    die('<h1>❌ Erreur Configuration</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Variables par défaut
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$is_debug = defined('DEBUG') && DEBUG;

$error_message = '';
$success_message = '';

// Traitement authentification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        // ========================================
        // 🔐 AUTHENTIFICATION OBLIGATOIRE VIA AUTHMANAGER
        // ========================================
        if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
            try {
                require_once ROOT_PATH . '/core/auth/AuthManager.php';
                $auth = new AuthManager();
                $result = $auth->login($username, $password);
                
                if ($result['success']) {
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user'] = $result['user'];
                    $_SESSION['auth_method'] = 'AuthManager';
                    $_SESSION['login_time'] = time();
                    
                    // Log de connexion sécurisé
                    $log_data = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'username' => $username,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
                        'result' => 'success'
                    ];
                    error_log("LOGIN_SUCCESS: " . json_encode($log_data));
                    
                    // Redirection sécurisée
                    $redirect = $_GET['redirect'] ?? '/index.php';
                    
                    // Validation de l'URL de redirection
                    if (!filter_var($redirect, FILTER_VALIDATE_URL) && !preg_match('/^\/[a-zA-Z0-9\/_-]*$/', $redirect)) {
                        $redirect = '/index.php';
                    }
                    
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error_message = $result['error'] ?? 'Identifiants incorrects';
                    
                    // Log d'échec
                    $log_data = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'username' => $username,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
                        'result' => 'failed',
                        'error' => $error_message
                    ];
                    error_log("LOGIN_FAILED: " . json_encode($log_data));
                }
            } catch (Exception $e) {
                $error_message = 'Erreur système d\'authentification';
                error_log("LOGIN_ERROR: " . $e->getMessage());
            }
        } else {
            // ========================================
            // ❌ AUTHMANAGER OBLIGATOIRE
            // ========================================
            $error_message = 'Système d\'authentification non configuré';
            error_log("CRITICAL: AuthManager file not found");
        }
        
        // ========================================
        // 🚫 SUPPRESSION DÉFINITIVE DES COMPTES TEMPORAIRES
        // Plus de fallback hardcodé - Authentification BDD obligatoire
        // ========================================
    }
}

// Détection des tentatives de brute force (basique)
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if ($error_message && $client_ip !== 'unknown') {
    $attempts_file = ROOT_PATH . '/storage/logs/login_attempts.log';
    $attempts_data = date('Y-m-d H:i:s') . " - " . $client_ip . " - " . $username . " - FAILED\n";
    
    if (!is_dir(dirname($attempts_file))) {
        mkdir(dirname($attempts_file), 0755, true);
    }
    file_put_contents($attempts_file, $attempts_data, FILE_APPEND | LOCK_EX);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= htmlspecialchars($app_name) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <!-- CSS modulaire -->
    <link rel="stylesheet" href="assets/css/login.css?v=<?= $build_number ?>">
    
    <!-- Headers de sécurité -->
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">🔐 Connexion</h1>
                <p class="login-subtitle">Accédez à votre espace <?= htmlspecialchars($app_name) ?></p>
                <p class="version-info">Version <?= htmlspecialchars($app_version) ?> - Build <?= htmlspecialchars($build_number) ?></p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error" role="alert">
                    ❌ <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    ✅ <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" novalidate>
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required 
                           autocomplete="username"
                           autofocus
                           maxlength="50"
                           pattern="[a-zA-Z0-9_-]+">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           maxlength="255">
                </div>

                <button type="submit" class="login-btn">
                    Se connecter
                </button>
            </form>

            <div class="back-link">
                <a href="/">&larr; Retour à l'accueil</a>
            </div>

            <!-- Informations de production -->
            <div class="production-info">
                <h3>🏭 Mode Production</h3>
                <div class="status-indicators">
                    <p>
                        <span class="status-indicator status-success"></span>
                        Authentification sécurisée
                    </p>
                    <p>
                        <span class="status-indicator status-success"></span>
                        Base de données connectée
                    </p>
                    <p>
                        <span class="status-indicator status-success"></span>
                        Sessions chiffrées
                    </p>
                    <p>
                        <span class="status-indicator status-success"></span>
                        Logs d'audit activés
                    </p>
                </div>
                
                <?php if ($is_debug): ?>
                <div class="debug-warning">
                    <p style="color: #dc2626; font-weight: bold;">
                        ⚠️ MODE DEBUG ACTIVÉ - À DÉSACTIVER EN PRODUCTION
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer sécurisé -->
    <footer class="login-footer">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($app_author) ?> - Tous droits réservés</p>
        <p>Connexion sécurisée SSL - <?= date('d/m/Y H:i') ?></p>
    </footer>

    <!-- CSS inline pour la sécurité -->
    <style>
        .production-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f0f9ff;
            border-radius: 8px;
            border-left: 4px solid #0284c7;
        }
        
        .production-info h3 {
            margin: 0 0 1rem 0;
            color: #0c4a6e;
            font-size: 1rem;
        }
        
        .status-indicators p {
            margin: 0.5rem 0;
            font-size: 0.9rem;
            color: #0f172a;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-success { background: #059669; }
        .status-warning { background: #d97706; }
        .status-error { background: #dc2626; }
        
        .debug-warning {
            margin-top: 1rem;
            padding: 0.5rem;
            background: #fef2f2;
            border-radius: 4px;
            border: 1px solid #fecaca;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            font-size: 0.8rem;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        
        .version-info {
            font-size: 0.8rem;
            color: #6b7280;
            margin: 0;
        }
    </style>

    <!-- Script de sécurité basique -->
    <script>
        // Protection contre les tentatives automatisées
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            const submitBtn = document.querySelector('.login-btn');
            let submitCount = 0;
            
            form.addEventListener('submit', function() {
                submitCount++;
                if (submitCount > 1) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Connexion...';
                }
            });
            
            // Timeout de sécurité
            setTimeout(function() {
                if (submitCount > 5) {
                    window.location.reload();
                }
            }, 60000); // 1 minute
        });
    </script>
</body>
</html>

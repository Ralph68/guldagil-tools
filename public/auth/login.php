<?php
/**
 * Titre: Page de connexion SÉCURISÉE - PRODUCTION READY
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base sécurisée
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Headers de sécurité CRITIQUES
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// Configuration session sécurisée
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Régénération ID session pour sécurité
if (!isset($_SESSION['session_started'])) {
    session_regenerate_id(true);
    $_SESSION['session_started'] = time();
}

// Chargement config sécurisé
$config_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Variables avec fallbacks sécurisés
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Sécurisé';
$app_version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd');
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : '';

// Vérification si déjà connecté
$redirect_to = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL) ?: '/';
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: ' . $redirect_to);
    exit;
}

// Variables d'état sécurisées
$error_message = '';
$login_attempts = (int)($_SESSION['login_attempts'] ?? 0);
$last_attempt = $_SESSION['last_login_attempt'] ?? 0;
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Rate limiting STRICT
$max_attempts = 5;
$cooldown_time = 900; // 15 minutes
$is_rate_limited = $login_attempts >= $max_attempts && 
                   (time() - $last_attempt) < $cooldown_time;

// CSRF Token obligatoire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Logging sécurisé des tentatives
function logSecurityEvent(string $event, array $context = []): void {
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'context' => $context
    ];
    
    $log_file = ROOT_PATH . '/storage/logs/auth.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $log_line = json_encode($log_data, JSON_UNESCAPED_UNICODE) . "\n";
    @file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}

/**
 * Authentification SÉCURISÉE - AUCUN COMPTE EN DUR
 */
function authenticateUser(string $username, string $password): array {
    // Validation input STRICTE
    if (empty($username) || empty($password)) {
        return ['success' => false, 'error' => 'Identifiants manquants'];
    }
    
    if (strlen($username) > 100 || strlen($password) > 200) {
        return ['success' => false, 'error' => 'Identifiants invalides'];
    }
    
    // Caractères interdits
    if (preg_match('/[<>"\']/', $username)) {
        return ['success' => false, 'error' => 'Caractères interdits'];
    }
    
    try {
        // 1. AuthManager en priorité ABSOLUE
        if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
            require_once ROOT_PATH . '/core/auth/AuthManager.php';
            $auth = new AuthManager();
            $result = $auth->login($username, $password);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'user' => $auth->getCurrentUser(),
                    'method' => 'AuthManager'
                ];
            }
        }
        
        // 2. Base de données directe en fallback
        if (defined('DB_DSN')) {
            $db = new PDO(DB_DSN, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $stmt = $db->prepare("
                SELECT id, username, password_hash, role, is_active, failed_attempts, locked_until
                FROM auth_users 
                WHERE username = :username 
                AND is_active = 1
                LIMIT 1
            ");
            
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user) {
                // Vérifier verrouillage compte
                if ($user['locked_until'] && time() < strtotime($user['locked_until'])) {
                    return ['success' => false, 'error' => 'Compte temporairement verrouillé'];
                }
                
                // Vérifier mot de passe
                if (password_verify($password, $user['password_hash'])) {
                    // Réinitialiser tentatives échouées
                    $stmt = $db->prepare("
                        UPDATE auth_users 
                        SET failed_attempts = 0, locked_until = NULL, last_login = NOW()
                        WHERE id = :id
                    ");
                    $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
                    $stmt->execute();
                    
                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'role' => $user['role']
                        ],
                        'method' => 'database'
                    ];
                } else {
                    // Incrémenter tentatives échouées
                    $failed_attempts = $user['failed_attempts'] + 1;
                    $locked_until = null;
                    
                    if ($failed_attempts >= 5) {
                        $locked_until = date('Y-m-d H:i:s', time() + 1800); // 30 min
                    }
                    
                    $stmt = $db->prepare("
                        UPDATE auth_users 
                        SET failed_attempts = :attempts, locked_until = :locked
                        WHERE id = :id
                    ");
                    $stmt->bindValue(':attempts', $failed_attempts, PDO::PARAM_INT);
                    $stmt->bindValue(':locked', $locked_until, PDO::PARAM_STR);
                    $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
                    $stmt->execute();
                    
                    return ['success' => false, 'error' => 'Identifiants incorrects'];
                }
            }
        }
        
        // 3. AUCUN SYSTÈME DE FALLBACK AVEC COMPTES PAR DÉFAUT
        // Sécurité : pas de comptes admin/dev en dur
        
        return ['success' => false, 'error' => 'Identifiants incorrects'];
        
    } catch (Exception $e) {
        error_log("Erreur authentification: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erreur système'];
    }
}

// Traitement formulaire SÉCURISÉ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification rate limiting
    if ($is_rate_limited) {
        $remaining_time = $cooldown_time - (time() - $last_attempt);
        $error_message = "Trop de tentatives. Réessayez dans " . ceil($remaining_time / 60) . " minutes.";
        
        logSecurityEvent('LOGIN_RATE_LIMITED', [
            'attempts' => $login_attempts,
            'remaining_cooldown' => $remaining_time
        ]);
    } else {
        // Vérification CSRF
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            $error_message = 'Token de sécurité invalide';
            logSecurityEvent('LOGIN_CSRF_INVALID');
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Tentative d'authentification
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_login_attempt'] = time();
            
            $auth_result = authenticateUser($username, $password);
            
            if ($auth_result['success']) {
                // Succès : réinitialiser compteurs
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_login_attempt']);
                
                // Session sécurisée
                session_regenerate_id(true);
                $_SESSION['authenticated'] = true;
                $_SESSION['user'] = $auth_result['user'];
                $_SESSION['user_id'] = $auth_result['user']['id'];
                $_SESSION['user_role'] = $auth_result['user']['role'];
                $_SESSION['username'] = $auth_result['user']['username'];
                $_SESSION['login_time'] = time();
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
                logSecurityEvent('LOGIN_SUCCESS', [
                    'username' => $username,
                    'method' => $auth_result['method']
                ]);
                
                header('Location: ' . $redirect_to);
                exit;
            } else {
                $error_message = $auth_result['error'];
                
                logSecurityEvent('LOGIN_FAILED', [
                    'username' => $username,
                    'attempts' => $_SESSION['login_attempts'],
                    'error' => $auth_result['error']
                ]);
            }
        }
    }
}

// Générer nouveau token CSRF après traitement
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= htmlspecialchars($app_name) ?></title>
    
    <!-- CSS sécurisé -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    
    <style>
        :root {
            --color-primary: #2563eb;
            --color-danger: #dc2626;
            --color-warning: #f59e0b;
            --color-success: #10b981;
            --color-gray-50: #f9fafb;
            --color-gray-100: #f3f4f6;
            --color-gray-800: #1f2937;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: var(--spacing-md);
        }

        .login-container {
            background: white;
            padding: var(--spacing-xl);
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .login-header h1 {
            color: var(--color-gray-800);
            margin: 0 0 var(--spacing-sm) 0;
            font-size: 1.875rem;
            font-weight: 700;
        }

        .login-header p {
            color: #6b7280;
            margin: 0;
            font-size: 0.875rem;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--color-danger);
            padding: var(--spacing-md);
            border-radius: 6px;
            margin-bottom: var(--spacing-lg);
            font-size: 0.875rem;
            text-align: center;
        }

        .rate-limit-warning {
            background: #fffbeb;
            border: 1px solid #fed7aa;
            color: var(--color-warning);
            padding: var(--spacing-md);
            border-radius: 6px;
            margin-bottom: var(--spacing-lg);
            font-size: 0.875rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-group label {
            display: block;
            margin-bottom: var(--spacing-sm);
            color: var(--color-gray-800);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.15s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .login-btn {
            width: 100%;
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .login-btn:hover:not(:disabled) {
            background: #1d4ed8;
        }

        .login-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .back-link {
            text-align: center;
            margin-top: var(--spacing-lg);
        }

        .back-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .back-link a:hover {
            color: var(--color-primary);
        }

        .security-notice {
            background: var(--color-gray-50);
            border: 1px solid #e5e7eb;
            padding: var(--spacing-md);
            border-radius: 6px;
            margin-top: var(--spacing-lg);
            font-size: 0.75rem;
            color: #6b7280;
            text-align: center;
        }

        .login-footer {
            margin-top: var(--spacing-xl);
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
        }

        @media (max-width: 640px) {
            .login-container {
                padding: var(--spacing-lg);
                margin: var(--spacing-md);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Connexion</h1>
            <p><?= htmlspecialchars($app_name) ?></p>
        </div>

        <!-- Messages d'erreur -->
        <?php if ($error_message): ?>
            <div class="<?= $is_rate_limited ? 'rate-limit-warning' : 'error-message' ?>">
                <?= $is_rate_limited ? '⏱️' : '❌' ?> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire sécurisé -->
        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required 
                       autocomplete="username"
                       maxlength="100"
                       <?= $is_rate_limited ? 'disabled' : '' ?>
                       autofocus>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       autocomplete="current-password"
                       maxlength="200"
                       <?= $is_rate_limited ? 'disabled' : '' ?>>
            </div>

            <button type="submit" class="login-btn" <?= $is_rate_limited ? 'disabled' : '' ?>>
                <?= $is_rate_limited ? '🔒 Verrouillé' : '🔑 Se connecter' ?>
            </button>
        </form>

        <div class="back-link">
            <a href="/">&larr; Retour à l'accueil</a>
        </div>

        <div class="security-notice">
            🛡️ Connexion sécurisée avec protection anti-bruteforce<br>
            Tentatives: <?= $login_attempts ?>/<?= $max_attempts ?>
        </div>

        <div class="login-footer">
            <?= htmlspecialchars($app_name) ?> v<?= htmlspecialchars($app_version) ?><br>
            Build <?= htmlspecialchars($build_number) ?>
            <?php if ($app_author): ?>
                • <?= htmlspecialchars($app_author) ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Protection contre les attaques timing
        setTimeout(() => {
            document.querySelector('form').style.visibility = 'visible';
        }, 100);

        // Nettoyage automatique du mot de passe
        window.addEventListener('beforeunload', () => {
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.value = '';
            }
        });

        // Focus automatique si pas de rate limiting
        <?php if (!$is_rate_limited): ?>
        document.addEventListener('DOMContentLoaded', () => {
            const usernameField = document.getElementById('username');
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            } else {
                document.getElementById('password').focus();
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

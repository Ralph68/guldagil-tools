<?php
/**
 * Titre: Page de connexion sécurisée - Version corrigée
 * Chemin: /public/auth/login.php  
 * Version: 0.5 beta + build auto
 */

// Gestion session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: /index.php');
    exit;
}

// Protection et chargement configuration
define('ROOT_PATH', dirname(__DIR__, 2));

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

// Variables par défaut si constantes manquantes
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
        // Tentative AuthManager
        if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
            try {
                require_once ROOT_PATH . '/core/auth/AuthManager.php';
                $auth = AuthManager::getInstance();
                $result = $auth->login($username, $password);
                
                if ($result['success']) {
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user'] = $result['user'];
                    header('Location: /index.php');
                    exit;
                } else {
                    $error_message = $result['error'] ?? 'Identifiants incorrects';
                }
            } catch (Exception $e) {
                $error_message = $is_debug ? $e->getMessage() : 'Erreur système d\'authentification';
            }
        } else {
            // Authentification temporaire basique (développement uniquement)
            if ($is_debug) {
                $temp_users = [
                    'admin' => ['password' => 'admin123', 'role' => 'admin'],
                    'user' => ['password' => 'user123', 'role' => 'user']
                ];
                
                if (isset($temp_users[$username]) && $temp_users[$username]['password'] === $password) {
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user'] = [
                        'username' => $username,
                        'role' => $temp_users[$username]['role']
                    ];
                    header('Location: /index.php');
                    exit;
                } else {
                    $error_message = 'Identifiants incorrects';
                }
            } else {
                $error_message = 'Système d\'authentification non configuré';
            }
        }
    }
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
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * { box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-900);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: var(--spacing-md);
        }

        .login-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xl);
            text-align: center;
        }

        .login-header {
            margin-bottom: var(--spacing-xl);
        }

        .login-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 var(--spacing-sm);
        }

        .login-subtitle {
            color: var(--gray-600);
            margin: 0;
        }

        .alert {
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
            text-align: left;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--spacing-sm);
        }

        .form-group input {
            width: 100%;
            padding: var(--spacing-md);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .login-btn {
            width: 100%;
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .login-btn:hover:not(:disabled) {
            background: var(--primary-blue-dark);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .back-link {
            margin-top: var(--spacing-lg);
            text-align: center;
        }

        .back-link a {
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.875rem;
        }

        .back-link a:hover {
            color: var(--primary-blue);
        }

        .dev-info {
            margin-top: var(--spacing-xl);
            padding: var(--spacing-lg);
            background: var(--gray-50);
            border-radius: var(--radius-md);
            text-align: left;
            font-size: 0.875rem;
        }

        .dev-info h3 {
            margin: 0 0 var(--spacing-md);
            color: var(--gray-800);
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: var(--spacing-sm);
        }

        .status-ok { background: var(--success); }
        .status-warning { background: var(--warning); }
        .status-error { background: var(--error); }

        .auth-footer {
            margin-top: var(--spacing-xl);
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
        }

        .auth-footer p { margin: 0.25rem 0; }

        @media (max-width: 480px) {
            .login-container { padding: var(--spacing-sm); }
            .login-card { padding: var(--spacing-lg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">Connexion</h1>
                <p class="login-subtitle"><?= htmlspecialchars($app_name) ?></p>
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
                           autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn">
                    Se connecter
                </button>
            </form>

            <div class="back-link">
                <a href="/">← Retour à l'accueil</a>
            </div>

            <?php if ($is_debug): ?>
            <div class="dev-info">
                <h3>🔧 Mode Développement</h3>
                <p><span class="status-indicator status-warning"></span>Comptes temporaires actifs</p>
                <p><strong>admin</strong> : admin123 (administrateur)</p>
                <p><strong>user</strong> : user123 (utilisateur)</p>
                <p><span class="status-indicator status-ok"></span>Version <?= htmlspecialchars($app_version) ?></p>
                <p style="margin-top: var(--spacing-md); font-weight: 600; color: var(--error);">
                    ⚠️ Configurer AuthManager pour la production
                </p>
            </div>
            <?php endif; ?>
        </div>

        <footer class="auth-footer">
            <p><strong><?= htmlspecialchars($app_name) ?></strong></p>
            <p>Version <?= htmlspecialchars($app_version) ?> - Build <?= substr($build_number, -8) ?></p>
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($app_author) ?></p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            const submitBtn = document.querySelector('.login-btn');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            // CORRECTIF: Focus intelligent sans conflit autofocus
            setTimeout(() => {
                if (!usernameInput.value.trim()) {
                    usernameInput.focus();
                } else {
                    passwordInput.focus();
                }
            }, 100);
            
            // Validation et soumission
            form.addEventListener('submit', function(e) {
                const username = usernameInput.value.trim();
                const password = passwordInput.value;
                
                if (!username || !password) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs');
                    return;
                }
                
                // Protection double soumission
                submitBtn.disabled = true;
                submitBtn.textContent = 'Connexion...';
                
                // Timeout sécurité
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Se connecter';
                }, 5000);
            });
            
            // Nettoyage erreurs à la saisie
            [usernameInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    const alerts = document.querySelectorAll('.alert-error');
                    alerts.forEach(alert => alert.style.opacity = '0.5');
                });
            });
        });
    </script>
</body>
</html>

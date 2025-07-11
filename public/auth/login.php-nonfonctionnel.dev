<?php
/**
 * Titre: Page de connexion sécurisée
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Redirection si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: /');
    exit;
}
file_put_contents('/tmp/debug_login.txt', ">>> login.php CHARGÉ\n", FILE_APPEND);

// Configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

$error_message = '';
$success_message = '';
$show_dev_info = defined('DEBUG') && DEBUG === true;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_username = trim($_POST['email_username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($email_or_username) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        try {
            // Charger AuthManager
            require_once __DIR__ . '/../../core/auth/auth_manager.php';
            $auth = AuthManager::getInstance();
            
            $result = $auth->login($email_or_username, $password, $remember_me);
            
            if ($result['success']) {
                // Redirection après connexion
                $redirect = $_GET['redirect'] ?? '/';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error_message = $result['error'];
            }
            
        } catch (Exception $e) {
            $error_message = $show_dev_info ? $e->getMessage() : 'Erreur système';
            if ($show_dev_info) {
                error_log("Erreur login: " . $e->getMessage());
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
    <title>Connexion - <?= htmlspecialchars(APP_NAME) ?></title>
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
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
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
            background: linear-gradient(135deg, var(--primary-blue-light) 0%, var(--primary-blue) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--gray-900);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--gray-700);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgb(49 130 206 / 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }

        .login-btn {
            width: 100%;
            background: var(--primary-blue);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .login-btn:hover {
            background: var(--primary-blue-dark);
        }

        .login-btn:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }

        .alert {
            padding: 0.75rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            font-size: 0.9rem;
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

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .dev-info {
            margin-top: 2rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
        }

        .dev-info h3 {
            color: var(--gray-800);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .dev-info p {
            font-size: 0.8rem;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .status-ok { background: var(--success); }
        .status-warning { background: var(--warning); }
        .status-error { background: var(--error); }

        .footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Connexion</h1>
            <p><?= htmlspecialchars(APP_NAME) ?> - v<?= APP_VERSION ?></p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email_username">Email ou nom d'utilisateur</label>
                <input type="text" 
                       id="email_username" 
                       name="email_username" 
                       value="<?= htmlspecialchars($_POST['email_username'] ?? '') ?>"
                       required 
                       autocomplete="username"
                       autofocus>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       autocomplete="current-password">
            </div>

            <div class="checkbox-group">
                <input type="checkbox" 
                       id="remember_me" 
                       name="remember_me">
                <label for="remember_me">Se souvenir de moi</label>
            </div>

            <button type="submit" class="login-btn">
                Se connecter
            </button>
        </form>

        <div class="back-link">
            <a href="/">&larr; Retour à l'accueil</a>
        </div>

        <?php if ($show_dev_info): ?>
        <div class="dev-info">
            <h3>🔧 Mode Développement</h3>
            <p>
                <span class="status-indicator status-ok"></span>
                Configuration chargée
            </p>
            <p>
                <span class="status-indicator <?= file_exists(__DIR__ . '/../../core/auth/auth_manager.php') ? 'status-ok' : 'status-warning' ?>"></span>
                AuthManager <?= file_exists(__DIR__ . '/../../core/auth/auth_manager.php') ? 'disponible' : 'manquant' ?>
            </p>
            <p>
                <span class="status-indicator status-ok"></span>
                Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?></p>
            <p>Build <?= BUILD_NUMBER ?> - <?= BUILD_DATE ?></p>
        </div>
    </div>
</body>
</html>

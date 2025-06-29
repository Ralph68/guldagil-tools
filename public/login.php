<?php
/**
 * Titre: Page de connexion Portail Guldagil
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';
require_once __DIR__ . '/../../core/auth/AuthManager.php';

$auth = AuthManager::getInstance();
$error_message = '';
$redirect_url = $_GET['redirect'] ?? '/';

// Si déjà connecté, rediriger
if ($auth->isAuthenticated()) {
    header('Location: ' . $redirect_url);
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            // Connexion réussie
            $_SESSION['flash_messages']['success'][] = 'Connexion réussie ! Bienvenue ' . $result['user']['name'];
            header('Location: ' . $redirect_url);
            exit;
        } else {
            $error_message = $result['error'];
        }
    }
}

// Informations de version
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
    <title>Connexion - Portail Guldagil</title>
    <style>
        /* Variables CSS */
        :root {
            --primary-blue: #1e40af;
            --primary-blue-light: #3b82f6;
            --primary-blue-dark: #1e3a8a;
            --color-success: #22c55e;
            --color-danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-lg: 0.75rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: var(--spacing-lg);
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-box {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xl);
            text-align: center;
        }

        .logo-section {
            margin-bottom: var(--spacing-xl);
        }

        .logo {
            height: 60px;
            width: auto;
            margin-bottom: var(--spacing-md);
            filter: brightness(1.1);
        }

        .brand-title {
            color: var(--primary-blue);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
        }

        .brand-subtitle {
            color: var(--gray-600);
            font-size: 1.1rem;
            margin-bottom: var(--spacing-lg);
        }

        .login-form {
            text-align: left;
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-group label {
            display: block;
            margin-bottom: var(--spacing-sm);
            color: var(--gray-700);
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            transition: border-color 0.3s ease;
            min-height: 48px;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .error-message {
            background: #fed7d7;
            color: var(--color-danger);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            border-left: 4px solid var(--color-danger);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .login-btn {
            width: 100%;
            background: var(--primary-blue);
            color: white;
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            min-height: 48px;
        }

        .login-btn:hover {
            background: var(--primary-blue-dark);
        }

        .login-btn:active {
            transform: translateY(1px);
        }

        .back-link {
            margin-top: var(--spacing-lg);
            text-align: center;
        }

        .back-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .dev-info {
            margin-top: var(--spacing-xl);
            padding: var(--spacing-lg);
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary-blue);
        }

        .dev-info h3 {
            color: var(--primary-blue);
            margin-bottom: var(--spacing-md);
            font-size: 1.1rem;
        }

        .dev-info ul {
            list-style: none;
            text-align: left;
        }

        .dev-info li {
            margin-bottom: var(--spacing-sm);
            color: var(--gray-600);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
        }

        .dev-info li strong {
            color: var(--primary-blue);
        }

        .dev-info li em {
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        .auth-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            text-align: center;
            padding: var(--spacing-md);
            font-size: 0.9rem;
        }

        .version-info {
            display: flex;
            justify-content: center;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: var(--spacing-md);
            }
            
            .login-box {
                padding: var(--spacing-lg);
            }
            
            .brand-title {
                font-size: 1.5rem;
            }
            
            .dev-info {
                font-size: 0.85rem;
            }
            
            .dev-info li {
                flex-direction: column;
                gap: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <!-- Logo et branding -->
            <div class="logo-section">
                <img src="/assets/img/logo.png" alt="Guldagil" class="logo">
                <h1 class="brand-title">Portail Guldagil</h1>
                <p class="brand-subtitle">Authentification requise</p>
            </div>

            <!-- Message d'erreur -->
            <?php if ($error_message): ?>
            <div class="error-message">
                <span>❌</span>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required 
                           autocomplete="current-password">
                </div>
                
                <button type="submit" class="login-btn">
                    Se connecter
                </button>
            </form>

            <!-- Lien retour -->
            <div class="back-link">
                <a href="/">← Retour à l'accueil</a>
            </div>

            <!-- Informations de développement -->
            <div class="dev-info">
                <h3>🔧 Comptes de test disponibles</h3>
                <ul>
                    <li>
                        <strong>user_guldagil</strong>
                        <em>Utilisateur standard</em>
                    </li>
                    <li>
                        <span>GulUser2025!</span>
                        <em>Accès: Calculateur</em>
                    </li>
                    
                    <li style="margin-top: 10px;">
                        <strong>admin_guldagil</strong>
                        <em>Administrateur</em>
                    </li>
                    <li>
                        <span>GulAdmin2025!</span>
                        <em>Accès: Tous modules + Admin</em>
                    </li>
                    
                    <li style="margin-top: 10px;">
                        <strong>runser</strong>
                        <em>Développeur</em>
                    </li>
                    <li>
                        <span>RunserDev2025!</span>
                        <em>Accès: Complet + Debug</em>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer version -->
    <footer class="auth-footer">
        <div class="version-info">
            <span>Portail Guldagil v<?= htmlspecialchars($version_info['version']) ?></span>
            <span>Build #<?= htmlspecialchars(substr($version_info['build'], -8)) ?></span>
            <span><?= htmlspecialchars($version_info['formatted_date']) ?></span>
        </div>
        <p>&copy; <?= date('Y') ?> Jean-Thomas RUNSER - Guldagil</p>
    </footer>

    <script>
        // Focus automatique sur le champ username si vide
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            if (usernameField.value === '') {
                usernameField.focus();
            } else {
                passwordField.focus();
            }
            
            // Raccourci clavier pour retour accueil
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.location.href = '/';
                }
            });
            
            // Auto-complétion des comptes de test (mode dev)
            <?php if (defined('DEBUG') && DEBUG): ?>
            const devAccounts = {
                'user_guldagil': 'GulUser2025!',
                'admin_guldagil': 'GulAdmin2025!',
                'runser': 'RunserDev2025!'
            };
            
            usernameField.addEventListener('change', function() {
                if (devAccounts[this.value]) {
                    passwordField.value = devAccounts[this.value];
                    passwordField.focus();
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>

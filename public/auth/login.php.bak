<?php
/**
 * Titre: Page de connexion sécurisée
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Redirection si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: /index.php');
    exit;
}

// Charger configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

$error_message = '';
$show_dev_info = defined('DEBUG') && DEBUG === true;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        // Authentification via système auth si disponible
        if (file_exists(__DIR__ . '/../../core/auth/AuthManager.php')) {
            require_once __DIR__ . '/../../core/auth/AuthManager.php';
            $auth = AuthManager::getInstance();
            $result = $auth->login($username, $password);
            
            if ($result['success']) {
                header('Location: /index.php');
                exit;
            } else {
                $error_message = $result['error'];
            }
        } else {
            // Authentification basique temporaire (à remplacer par BDD)
            $valid_users = [
                // Utilisateurs définis en configuration ou BDD
                // NE JAMAIS mettre d'identifiants en dur ici !
            ];
            
            $error_message = 'Système d\'authentification en cours de configuration';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Portail Guldagil</title>
    <meta name="description" content="Connexion au portail Guldagil">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <style>
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --color-danger: #dc2626;
            --gray-100: #f7fafc;
            --gray-200: #e2e8f0;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --radius-lg: 0.75rem;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
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
            padding: var(--spacing-2xl);
            text-align: center;
        }

        .logo {
            margin-bottom: var(--spacing-xl);
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
            margin-bottom: var(--spacing-xl);
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
            background: var(--gray-100);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary-blue);
            text-align: left;
        }

        .dev-info h3 {
            color: var(--primary-blue);
            margin-bottom: var(--spacing-md);
            font-size: 1.1rem;
        }

        .dev-info p {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: var(--spacing-sm);
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: var(--spacing-xs);
        }

        .status-ok { background: #10b981; }
        .status-warning { background: #f59e0b; }
        .status-error { background: #ef4444; }

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

        .auth-footer p {
            margin: 2px 0;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: var(--spacing-md);
            }
            
            .login-box {
                padding: var(--spacing-xl) var(--spacing-lg);
            }
            
            .brand-title {
                font-size: 1.8rem;
            }
            
            .dev-info {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1 class="brand-title">Guldagil</h1>
                <p class="brand-subtitle">Portail Professionnel</p>
            </div>

            <?php if ($error_message): ?>
            <div class="error-message">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
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
                    <span class="status-indicator status-warning"></span>
                    Système d'authentification en configuration
                </p>
                <p>
                    <span class="status-indicator status-ok"></span>
                    Configuration chargée
                </p>
                <p>
                    <span class="status-indicator status-ok"></span>
                    Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
                </p>
                <p style="margin-top: var(--spacing-md); font-weight: 600; color: var(--color-danger);">
                    ⚠️ SÉCURITÉ : Aucun identifiant en dur dans ce fichier
                </p>
                <p style="font-size: 0.8rem; margin-top: var(--spacing-sm);">
                    Les comptes utilisateur doivent être configurés via la base de données
                    ou le système AuthManager.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="auth-footer">
        <p><strong><?= APP_NAME ?></strong></p>
        <p>Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?></p>
        <p>&copy; <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?></p>
    </footer>

    <script>
        // Sécurisation formulaire
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            const submitBtn = document.querySelector('.login-btn');
            
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;
                
                if (!username || !password) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs');
                    return;
                }
                
                // Désactiver bouton pour éviter double soumission
                submitBtn.disabled = true;
                submitBtn.textContent = 'Connexion...';
                
                // Réactiver si erreur côté client
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Se connecter';
                }, 5000);
            });
            
            // Focus automatique sur premier champ vide
            if (!document.getElementById('username').value) {
                document.getElementById('username').focus();
            } else {
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>

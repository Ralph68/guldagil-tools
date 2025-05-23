<?php
// public/admin/login.php
session_start();

// Configuration des utilisateurs (en production, utilisez une base de données)
$admin_users = [
    'admin' => password_hash('GuldagilAdmin2025!', PASSWORD_DEFAULT),
    'runser' => password_hash('Runser2025!', PASSWORD_DEFAULT)
];

// Si déjà connecté, rediriger
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (isset($admin_users[$username]) && password_verify($password, $admin_users[$username])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        
        // Log de la connexion
        error_log("ADMIN_LOGIN: User $username logged in from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        header('Location: index.php');
        exit;
    } else {
        $error = 'Nom d\'utilisateur ou mot de passe incorrect';
        
        // Log de la tentative échouée
        error_log("ADMIN_LOGIN_FAILED: Failed login attempt for user $username from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Délai pour éviter les attaques par force brute
        sleep(2);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Administration Guldagil</title>
    <style>
        :root {
            --primary-color: #007acc;
            --primary-hover: #005f99;
            --error-color: #f44336;
            --bg-light: #f8f9fa;
            --border-color: #ddd;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 3rem;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo-section {
            margin-bottom: 2rem;
        }

        .logo-section h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .logo-section p {
            color: #666;
            margin: 0.5rem 0 0 0;
            font-size: 0.9rem;
        }

        .login-form {
            text-align: left;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 122, 204, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .error-message {
            background: #ffebee;
            border: 1px solid var(--error-color);
            color: #c62828;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .help-text {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.85rem;
            color: #666;
            text-align: center;
        }

        .back-link {
            margin-top: 1rem;
            text-align: center;
        }

        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <h1>⚙️ Administration</h1>
            <p>Guldagil Port Calculator</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-control" 
                       required 
                       autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       required 
                       autocomplete="current-password">
            </div>

            <button type="submit" class="btn-login">
                🔐 Se connecter
            </button>
        </form>

        <div class="help-text">
            <strong>Accès réservé aux administrateurs</strong><br>
            En cas de problème, contactez :<br>
            📧 runser.jean.thomas@guldagil.com<br>
            📞 03 89 63 42 42
        </div>

        <div class="back-link">
            <a href="../">← Retour au calculateur</a>
        </div>
    </div>

    <script>
        // Focus automatique sur le premier champ
        document.getElementById('username').focus();

        // Validation côté client
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            if (!username || !password) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs');
                return false;
            }

            if (username.length < 3) {
                e.preventDefault();
                alert('Le nom d\'utilisateur doit contenir au moins 3 caractères');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères');
                return false;
            }
        });

        // Gestion de l'auto-complétion
        setTimeout(() => {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            if (username.value && !password.value) {
                password.focus();
            }
        }, 500);
    </script>
</body>
</html>

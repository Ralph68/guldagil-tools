<?php
// public/auth-login.php - Page d'authentification
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Portail Guldagil</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-logo {
            height: 4rem;
            margin-bottom: 2rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            text-align: left;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .btn-login {
            background: #1e3a8a;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: #1e40af;
            transform: translateY(-1px);
        }

        .error-message {
            background: #fef2f2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #fecaca;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .login-info {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.8rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="assets/img/logo_guldagil.png" alt="Logo Guldagil" class="login-logo">
        
        <h1 class="login-title">Portail Guldagil</h1>
        <p class="login-subtitle">Accès sécurisé aux outils logistiques</p>

        <?php if (isset($_POST['password']) && $_POST['password'] !== $auth_password): ?>
            <div class="error-message">
                ❌ Mot de passe incorrect
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="password">🔐 Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="Saisissez le mot de passe"
                       required 
                       autofocus>
            </div>

            <button type="submit" class="btn-login">
                🚪 Se connecter
            </button>
        </form>

        <div class="login-info">
            <p><strong>Accès réservé au personnel autorisé</strong></p>
            <p>Support : runser.jean.thomas@guldagil.com</p>
        </div>
    </div>

    <script>
        // Auto-focus sur le champ mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('password').focus();
        });

        // Gestion Enter sur le formulaire
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    </script>
</body>
</html>

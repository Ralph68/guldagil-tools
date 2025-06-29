<?php
/**
 * Titre: Page de déconnexion Portail Guldagil
 * Chemin: /public/auth/logout.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/auth/AuthManager.php';

$auth = AuthManager::getInstance();

// Récupérer le nom de l'utilisateur avant déconnexion
$user_name = 'Utilisateur';
if ($auth->isAuthenticated()) {
    $current_user = $auth->getCurrentUser();
    $user_name = $current_user['name'] ?? 'Utilisateur';
}

// Effectuer la déconnexion
$auth->logout('manual');

// Message de confirmation
$_SESSION['flash_messages']['success'][] = "Déconnexion réussie. À bientôt, {$user_name} !";

// Redirection vers l'accueil après 2 secondes
header('Refresh: 2; url=/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Portail Guldagil</title>
    <style>
        :root {
            --primary-blue: #1e40af;
            --primary-blue-dark: #1e3a8a;
            --color-success: #22c55e;
            --gray-600: #6b7280;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }

        .logout-container {
            max-width: 400px;
            padding: 2rem;
        }

        .logout-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .logout-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .logout-message {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--color-success);
            width: 0%;
            transition: width 2s ease-in-out;
        }

        .redirect-text {
            font-size: 0.9rem;
            opacity: 0.7;
        }

        .manual-link {
            color: white;
            text-decoration: underline;
            margin-left: 0.5rem;
        }

        .manual-link:hover {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">👋</div>
        <h1 class="logout-title">Déconnexion réussie</h1>
        <p class="logout-message">
            À bientôt, <?= htmlspecialchars($user_name) ?> !
        </p>
        
        <div class="progress-bar">
            <div class="progress-fill" id="progress"></div>
        </div>
        
        <p class="redirect-text">
            Redirection automatique vers l'accueil...
            <a href="/" class="manual-link">ou cliquez ici</a>
        </p>
    </div>

    <script>
        // Animation de la barre de progression
        document.addEventListener('DOMContentLoaded', function() {
            const progress = document.getElementById('progress');
            setTimeout(() => {
                progress.style.width = '100%';
            }, 100);
            
            // Redirection manuelle au clic
            document.addEventListener('click', function() {
                window.location.href = '/';
            });
            
            // Redirection au bout de 2 secondes si pas automatique
            setTimeout(() => {
                window.location.href = '/';
            }, 2000);
        });
    </script>
</body>
</html>

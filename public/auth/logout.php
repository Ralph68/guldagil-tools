<?php
/**
 * Titre: Déconnexion Portail Guldagil
 * Chemin: /public/auth/logout.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/auth/AuthManager.php';

$auth = AuthManager::getInstance();

// Récupérer nom utilisateur avant déconnexion
$user_name = 'Utilisateur';
if ($auth->isAuthenticated()) {
    $user = $auth->getCurrentUser();
    $user_name = $user['name'] ?? 'Utilisateur';
}

// Déconnexion
$auth->logout('manual');

// Message flash
$_SESSION['flash_messages']['success'][] = "Déconnexion réussie. À bientôt, {$user_name} !";

// Redirection immédiate OU page intermédiaire selon paramètre
if (isset($_GET['quick'])) {
    header('Location: /');
    exit;
}

// Page intermédiaire avec redirection auto
header('Refresh: 2; url=/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Portail Guldagil</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            margin: 0;
        }
        .logout-container {
            max-width: 400px;
            padding: 2rem;
        }
        .logout-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .logout-title {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .logout-message {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }
        .manual-link {
            color: white;
            text-decoration: underline;
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
        <p>
            Redirection automatique...
            <a href="/" class="manual-link">ou cliquez ici</a>
        </p>
    </div>
    
    <script>
        // Redirection manuelle au clic
        document.addEventListener('click', () => window.location.href = '/');
        
        // Redirection auto après 2s
        setTimeout(() => window.location.href = '/', 2000);
    </script>
</body>
</html>

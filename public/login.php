<?php
/**
 * Page de connexion - Gul Calc Frais de port
 * Chemin : /public/login.php
 * Version : 0.5 beta
 */

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$error = '';

// Rediriger si déjà connecté
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        if ($auth->login($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gul Portail</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1>🔧 Gul Portail</h1>
                <p>Calc Frais de port</p>
            </div>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="login-btn">Se connecter</button>
            </form>
            
            <div class="dev-info">
                <h3>Comptes de développement :</h3>
                <ul>
                    <li><strong>dev</strong> / dev123 <em>(session illimitée)</em></li>
                    <li><strong>admin</strong> / admin123 <em>(8h)</em></li>
                    <li><strong>user</strong> / user123 <em>(2h)</em></li>
                </ul>
            </div>
        </div>
    </div>
    
    <footer class="auth-footer">
        <p>© 2025 Gul Portail - Version 0.5 beta - Build <?= date('YmdHi') ?></p>
        <p>Horodatage : <?= date('d/m/Y H:i:s') ?></p>
    </footer>
</body>
</html>

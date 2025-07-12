<?php
/**
 * Titre: Page de vérification MFA
 * Chemin: /auth/mfa.php
 * Version: 0.5 beta
 */

// Protection et chargement configuration
define('ROOT_PATH', dirname(__DIR__, 2));

$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php',
    ROOT_PATH . '/core/auth/AuthManager.php'
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
    require_once ROOT_PATH . '/core/auth/AuthManager.php';
} catch (Exception $e) {
    http_response_code(500);
    die('<h1>❌ Erreur Configuration</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Initialisation
session_start();
$auth = AuthManager::getInstance();

// Redirection si déjà authentifié
if ($auth->isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

// Redirection si MFA non requis
if (!isset($_SESSION['mfa_required']) || !$_SESSION['mfa_required']) {
    header('Location: /auth/login.php');
    exit;
}

// Variables par défaut
$error_message = '';
$success_message = '';

// Traitement du code MFA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mfa_code = trim($_POST['mfa_code'] ?? '');
    
    if (empty($mfa_code)) {
        $error_message = 'Veuillez entrer le code MFA';
    } else {
        $user_id = $_SESSION['user_id'] ?? null;
        if ($user_id && $auth->verifyMFA($user_id, $mfa_code)) {
            unset($_SESSION['mfa_required']);
            header('Location: /index.php');
            exit;
        }
        $error_message = 'Code MFA invalide';
    }
}

// En-tête HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification MFA - <?= htmlspecialchars($app_name) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <style>
        /* Style identique à login.php pour la cohérence */
        /* ... copier le CSS de login.php ... */
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">Vérification MFA</h1>
                <p class="login-subtitle">Veuillez entrer le code reçu par email</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error" role="alert">
                    ❌ <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" novalidate>
                <div class="form-group">
                    <label for="mfa_code">Code MFA</label>
                    <input type="text" 
                           id="mfa_code" 
                           name="mfa_code" 
                           value="<?= htmlspecialchars($_POST['mfa_code'] ?? '') ?>"
                           required 
                           autocomplete="off"
                           maxlength="6"
                           pattern="[0-9]{6}">
                </div>

                <button type="submit" class="login-btn">
                    Vérifier
                </button>
            </form>

            <div class="back-link">
                <a href="/auth/login.php">← Retour à la connexion</a>
            </div>
        </div>
    </div>
</body>
</html>

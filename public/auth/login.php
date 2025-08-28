
<?php

// === MODE DEBUG : Affiche toutes les erreurs PHP pour diagnostic ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


/**
 * Titre: Login FINAL - AuthManager seulement, zéro fallback
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 */

// Inclusion du header global (inclut enhanced_security.php et lance la sécurité)
include_once dirname(__DIR__, 2) . '/templates/header.php';

define('ROOT_PATH', dirname(dirname(__DIR__)));

// Config session 9h30
if (file_exists(ROOT_PATH . '/config/session_timeout.php')) {
    require_once ROOT_PATH . '/config/session_timeout.php';
}
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 34200);
}

ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
session_start();

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Variables
$error_message = '';
$redirect_param = $_GET['redirect'] ?? '/';
$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');

// === REDIRECTION SI DÉJÀ CONNECTÉ (GET seulement) ===
if (!$is_post) {
    require_once ROOT_PATH . '/core/auth/AuthManager.php';
    $auth = AuthManager::getInstance();
    
    if ($auth->isAuthenticated()) {
        $destination = '/';
        if (!empty($redirect_param) && 
            $redirect_param !== '/auth/login.php' && 
            strpos($redirect_param, '/auth/login') === false &&
            preg_match('/^\/[a-zA-Z0-9\/_.-]*$/', $redirect_param)) {
            $destination = $redirect_param;
        }
        header('Location: ' . $destination);
        exit;
    }
}

// Rate limiting
$login_attempts = $_SESSION['login_attempts'] ?? 0;
$last_attempt = $_SESSION['last_login_attempt'] ?? 0;
$is_rate_limited = ($login_attempts >= 5) && (time() - $last_attempt < 300);

// CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === TRAITEMENT POST ===
if ($is_post && !$is_rate_limited) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $_SESSION['login_attempts'] = $login_attempts + 1;
        $_SESSION['last_login_attempt'] = time();
        
        if ($username && $password) {
            require_once ROOT_PATH . '/core/auth/AuthManager.php';
            $auth = AuthManager::getInstance();
            $result = $auth->login($username, $password, isset($_POST['remember']));

            if ($result['success']) {
                // Reset tentatives
                unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);
                
                // Redirection
                $destination = '/';
                if (!empty($redirect_param) && 
                    strpos($redirect_param, '/auth/login') === false &&
                    preg_match('/^\/[a-zA-Z0-9\/_.-]*$/', $redirect_param)) {
                    $destination = $redirect_param;
                }
                
                header('Location: ' . $destination);
                exit;
            } else {
                $error_message = $result['error'];
            }
        } else {
            $error_message = 'Veuillez remplir tous les champs';
        }
    } else {
        $error_message = 'Token de sécurité invalide';
    }
} elseif ($is_post && $is_rate_limited) {
    $remaining = ceil((300 - (time() - $last_attempt)) / 60);
    $error_message = "Trop de tentatives. Réessayez dans {$remaining} minutes.";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Portail Guldagil</title>
    
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/login.css?v=<?= $build_number ?>">
    
    <!-- CSS fix logo -->
    <style>
    .login-logo img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: contain;
        object-position: center;
        background: white;
        padding: 8px;
        border: 2px solid rgba(59, 130, 246, 0.2);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .login-logo img:hover {
        transform: scale(1.05);
        border-color: #3b82f6;
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    }
    </style>
    
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <?php if (file_exists(ROOT_PATH . '/public/assets/img/logo.png')): ?>
                <img src="/assets/img/logo.png" alt="Logo Guldagil" width="80" height="80">
                <?php else: ?>
                <div class="logo-placeholder">🌊</div>
                <?php endif; ?>
            </div>
            <h1>Portail Guldagil</h1>
            <p>Connexion sécurisée</p>
        </div>

        <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'disconnected'): ?>
        <div class="alert alert-success">
            Vous avez été déconnecté avec succès.
        </div>
        <?php endif; ?>

        <?php if (!$is_rate_limited): ?>
        <form method="POST" action="/auth/login.php<?= $redirect_param ? '?redirect=' . urlencode($redirect_param) : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-input"
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
                       class="form-input"
                       required 
                       autocomplete="current-password">
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkmark"></span>
                    Se souvenir de moi (30 jours)
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                🔑 Se connecter
            </button>
        </form>
        <?php else: ?>
        <div class="alert alert-danger">
            <strong>Compte bloqué</strong><br>
            Trop de tentatives de connexion.
        </div>
        <?php endif; ?>

        <div class="footer-info">
            <p>Session: 9h30 • Version <?= $version ?? '0.5' ?> • Build <?= $build_number ?? '001' ?></p>
        </div>
    </div>
</div>

<script>
document.querySelector('form')?.addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        e.preventDefault();
        alert('Veuillez remplir tous les champs');
        return;
    }
    
    // Loading state
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Connexion...';
});
</script>

</body>
</html>
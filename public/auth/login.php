<?php
/**
 * Titre: Page de connexion - SESSIONS SYNCHRONISÉES
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chargement config
$config_files = [
    ROOT_PATH . '/config/version.php',
    ROOT_PATH . '/config/config.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Variables avec fallbacks
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Sécurisé';
$app_version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd');
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : '';

// Redirection si déjà connecté
$redirect_to = $_GET['redirect'] ?? '/';
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: ' . $redirect_to);
    exit;
}

// Variables d'état
$error_message = '';
$login_attempts = (int)($_SESSION['login_attempts'] ?? 0);
$last_attempt = $_SESSION['last_login_attempt'] ?? 0;

// Rate limiting
$max_attempts = 5;
$cooldown_time = 900; // 15 minutes
$is_rate_limited = $login_attempts >= $max_attempts && 
                   (time() - $last_attempt) < $cooldown_time;

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

/**
 * Fonction d'authentification unifiée
 */
function authenticateUser($username, $password) {
    // 1. Essayer AuthManager en priorité
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        try {
            require_once ROOT_PATH . '/core/auth/AuthManager.php';
            $auth = new AuthManager();
            $result = $auth->login($username, $password);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'user' => $auth->getCurrentUser(),
                    'method' => 'AuthManager'
                ];
            }
        } catch (Exception $e) {
            error_log("Erreur AuthManager login: " . $e->getMessage());
        }
    }
    
    // 2. Fallback : utilisateurs par défaut
    $valid_users = [
        'admin' => ['password' => 'admin', 'role' => 'admin'],
        'dev' => ['password' => 'dev', 'role' => 'dev'], 
        'user' => ['password' => 'user', 'role' => 'user'],
        'logistique' => ['password' => 'logistique', 'role' => 'logistique']
    ];
    
    if (isset($valid_users[$username]) && $valid_users[$username]['password'] === $password) {
        return [
            'success' => true,
            'user' => [
                'id' => 1,
                'username' => $username,
                'role' => $valid_users[$username]['role'],
                'authenticated_at' => time()
            ],
            'method' => 'fallback'
        ];
    }
    
    return ['success' => false, 'error' => 'Identifiants incorrects'];
}

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérification CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error_message = 'Erreur de sécurité. Rechargez la page.';
    }
    elseif ($is_rate_limited) {
        $remaining_time = $cooldown_time - (time() - $last_attempt);
        $error_message = sprintf('Trop de tentatives. Réessayez dans %d minutes.', ceil($remaining_time / 60));
    }
    else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = $_POST['password'] ?? '';
        
        $username = $username ? trim($username) : '';
        
        if (empty($username) || empty($password)) {
            $error_message = 'Veuillez remplir tous les champs.';
        }
        elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error_message = 'Nom d\'utilisateur invalide.';
        }
        elseif (strlen($password) < 3) {
            $error_message = 'Mot de passe trop court.';
        }
        else {
            // Authentification
            try {
                $auth_result = authenticateUser($username, $password);

                if ($auth_result['success']) {
                    $current_user = $auth_result['user'];
                    $role = $current_user['role'];
                    
                    // === SYNCHRONISATION COMPLÈTE DES SESSIONS ===
                    
                    // Variables standard pour tous les modules
                    $_SESSION['authenticated'] = true;
                    $_SESSION['login_attempts'] = 0;
                    unset($_SESSION['last_login_attempt']);
                    
                    // Variables AuthManager et modules généraux
                    $_SESSION['user'] = $current_user;
                    
                    // Variables spécifiques pour le module ADMIN (obligatoires)
                    $_SESSION['user_id'] = $current_user['id'] ?? 1;
                    $_SESSION['user_role'] = $current_user['role'] ?? $role;
                    $_SESSION['username'] = $current_user['username'] ?? $username;
                    
                    // Variables de sécurité
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Régénération sécurisée de l'ID de session
                    session_regenerate_id(true);
                    
                    // Log de connexion
                    error_log(sprintf('[LOGIN SUCCESS] User: %s | Role: %s | Method: %s | IP: %s | Session: %s', 
                        $username, 
                        $role,
                        $auth_result['method'],
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        session_id()
                    ));
                    
                    // Redirection
                    header('Location: ' . $redirect_to);
                    exit;
                } else {
                    $error_message = $auth_result['error'];
                }
                
            } catch (Exception $e) {
                $error_message = 'Erreur de connexion.';
                error_log('[LOGIN ERROR] ' . $e->getMessage());
            }
            
            // Incrémenter tentatives ratées
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_login_attempt'] = time();
        }
    }
    
    // Nouveau token CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= htmlspecialchars($app_name) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="<?= htmlspecialchars($app_author) ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="assets/css/login.css?v=<?= $build_number ?>">
    
    <!-- Headers de sécurité -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>

<body class="login-page">
    
    <div class="login-container">
        <div class="login-card">
            
            <!-- Header -->
            <header class="login-header">
                <div class="login-logo">
                    <div class="logo-icon">🌊</div>
                    <div class="logo-text">
                        <h1><?= htmlspecialchars($app_name) ?></h1>
                        <p class="tagline">Solutions professionnelles</p>
                    </div>
                </div>
                <div class="version-badge">v<?= htmlspecialchars($app_version) ?></div>
            </header>
            
            <!-- Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message" role="alert">
                    <span class="error-icon">⚠️</span>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($login_attempts >= 3 && !$is_rate_limited): ?>
                <div class="warning-message">
                    <span class="warning-icon">⚠️</span>
                    Attention : <?= $login_attempts ?> tentatives échouées. Après <?= $max_attempts ?> tentatives, l'accès sera temporairement bloqué.
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de connexion -->
            <form method="POST" class="login-form" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">
                        <span class="label-icon">👤</span>
                        <span class="label-text">Nom d'utilisateur</span>
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required 
                        autocomplete="username"
                        maxlength="50"
                        <?= $is_rate_limited ? 'disabled' : '' ?>
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <span class="label-icon">🔑</span>
                        <span class="label-text">Mot de passe</span>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        required 
                        autocomplete="current-password"
                        <?= $is_rate_limited ? 'disabled' : '' ?>
                    >
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" class="checkbox-input">
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-text">Se souvenir de moi</span>
                    </label>
                </div>
                
                <button 
                    type="submit" 
                    class="login-button"
                    <?= $is_rate_limited ? 'disabled' : '' ?>
                >
                    <?php if ($is_rate_limited): ?>
                        <span class="button-icon">⏳</span>
                        <span class="button-text">Bloqué temporairement</span>
                    <?php else: ?>
                        <span class="button-icon">🚀</span>
                        <span class="button-text">Se connecter</span>
                    <?php endif; ?>
                </button>
            </form>
            
            <!-- Informations de développement -->
            <?php if (defined('DEBUG') && DEBUG): ?>
            <div class="dev-info">
                <h3>🔧 Informations de développement</h3>
                <div class="dev-details">
                    <p><strong>Comptes de test :</strong></p>
                    <ul>
                        <li><code>admin/admin</code> - Accès administrateur</li>
                        <li><code>dev/dev</code> - Accès développeur</li>
                        <li><code>user/user</code> - Accès utilisateur</li>
                        <li><code>logistique/logistique</code> - Accès logistique</li>
                    </ul>
                    <p><strong>Session ID :</strong> <code><?= session_id() ?></code></p>
                    <p><strong>AuthManager :</strong> <?= file_exists(ROOT_PATH . '/core/auth/AuthManager.php') ? '✅ Disponible' : '❌ Indisponible' ?></p>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Footer -->
        <footer class="login-footer">
            <div class="footer-info">
                <p class="footer-version">
                    <?= htmlspecialchars($app_name) ?> v<?= htmlspecialchars($app_version) ?> 
                    <span class="build-number">(build <?= htmlspecialchars($build_number) ?>)</span>
                </p>
                <?php if (!empty($app_author)): ?>
                <p class="footer-author">
                    © <?= date('Y') ?> <?= htmlspecialchars($app_author) ?>
                </p>
                <?php endif; ?>
            </div>
        </footer>
    </div>
    
    <script>
    // Auto-focus sur le premier champ vide
    document.addEventListener('DOMContentLoaded', function() {
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        
        if (!username.value) {
            username.focus();
        } else {
            password.focus();
        }
        
        // Retirer les messages d'erreur après 10 secondes
        const errorMessage = document.querySelector('.error-message');
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.transition = 'opacity 0.5s ease';
                errorMessage.style.opacity = '0';
                setTimeout(() => errorMessage.remove(), 500);
            }, 10000);
        }
    });
    </script>
    
</body>
</html>

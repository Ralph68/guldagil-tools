<?php
/**
 * Titre: Page de connexion CORRIGÉE - Redirection infinie fixée
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 * 
 * 🔧 CORRECTIONS CRITIQUES :
 * 1. LOGIQUE REDIRECTION RÉÉCRITE complètement
 * 2. Conditions simplifiées pour éviter les boucles
 * 3. Validation robuste des paramètres redirect
 * 4. Headers de cache pour empêcher navigateur de loop
 */

// Configuration de base
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Configuration session AVANT session_start()
if (file_exists(ROOT_PATH . '/config/session_timeout.php')) {
    require_once ROOT_PATH . '/config/session_timeout.php';
}

if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 34200); // 9h30
}

// Configuration session sécurisée
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.name', 'GULDAGIL_PORTAL_SESSION');

// CRITIQUE : Headers anti-cache pour éviter redirections browser
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

session_start();

// Chargement configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// =====================================
// 🚨 NOUVELLE LOGIQUE ANTI-BOUCLE
// =====================================

$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');
$redirect_param = $_GET['redirect'] ?? '';

// Nettoyer et valider le paramètre redirect
function validateRedirectUrl($url) {
    if (empty($url)) return '/';
    
    // Empêcher redirections vers login lui-même
    if (strpos($url, '/auth/login') !== false) return '/';
    
    // Valider format URL interne seulement
    if (!preg_match('/^\/[a-zA-Z0-9\/_.-]*$/', $url)) return '/';
    
    return $url;
}

$safe_redirect = validateRedirectUrl($redirect_param);

// =====================================
// 🔐 VÉRIFICATION AUTHENTIFICATION SIMPLIFIÉE
// =====================================

$user_authenticated = false;
$current_user = null;

// Vérifier AuthManager UNIQUEMENT si disponible
if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
    try {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = AuthManager::getInstance();
        
        if ($auth->isAuthenticated()) {
            $user_authenticated = true;
            $current_user = $auth->getCurrentUser();
        }
    } catch (Exception $e) {
        error_log("AuthManager error: " . $e->getMessage());
        // Continuer avec fallback
    }
}

// Fallback session simple si AuthManager indisponible
if (!$user_authenticated && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $user_authenticated = true;
    $current_user = $_SESSION['user'] ?? ['username' => 'User', 'role' => 'user'];
}

// =====================================
// 🔄 REDIRECTION UTILISATEUR CONNECTÉ
// =====================================

// RÈGLE SIMPLE : Si connecté ET pas POST → rediriger
if ($user_authenticated && !$is_post_request) {
    error_log("AUTHENTICATED_REDIRECT: from={$current_uri} to={$safe_redirect}");
    header('Location: ' . $safe_redirect);
    exit;
}

// =====================================
// 🛡️ SÉCURITÉ ET RATE LIMITING
// =====================================

function logSecurityEvent($event, $data = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
        'data' => $data,
        'session_id' => session_id()
    ];
    error_log('SECURITY_' . $event . ': ' . json_encode($logEntry));
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$login_attempts = $_SESSION['login_attempts'] ?? 0;
$last_attempt = $_SESSION['last_login_attempt'] ?? 0;
$cooldown_time = 300; // 5 minutes
$max_attempts = 5;
$is_rate_limited = ($login_attempts >= $max_attempts) && (time() - $last_attempt < $cooldown_time);

// Variables pour affichage
$error_message = '';
$success_message = '';

// Générer token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =====================================
// 🔐 FONCTIONS AUTHENTIFICATION
// =====================================

function authenticateUser($username, $password) {
    // 1. AuthManager en priorité
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        try {
            require_once ROOT_PATH . '/core/auth/AuthManager.php';
            $auth = AuthManager::getInstance();
            $result = $auth->login($username, $password);
            
            if ($result['success']) {
                error_log("LOGIN SUCCESS via AuthManager: " . $username);
                return [
                    'success' => true,
                    'user' => $result['user'],
                    'method' => 'AuthManager'
                ];
            }
        } catch (Exception $e) {
            error_log("AuthManager login error: " . $e->getMessage());
        }
    }
    
    // 2. Fallback base de données
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $db = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $stmt = $db->prepare("SELECT * FROM auth_users WHERE username = ? AND active = 1 LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                error_log("LOGIN SUCCESS via DB: " . $username);
                return [
                    'success' => true,
                    'user' => $user,
                    'method' => 'database'
                ];
            }
        } catch (Exception $e) {
            error_log("Database auth error: " . $e->getMessage());
        }
    }
    
    return ['success' => false, 'error' => 'Identifiants incorrects'];
}

function createSecureUserSession($user) {
    // Régénérer ID session pour sécurité
    session_regenerate_id(true);
    
    // Créer session compatible AuthManager
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = [
        'id' => $user['id'] ?? 0,
        'username' => $user['username'],
        'email' => $user['email'] ?? '',
        'role' => $user['role'] ?? 'user',
        'active' => $user['active'] ?? 1
    ];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// =====================================
// 📝 TRAITEMENT FORMULAIRE POST
// =====================================

if ($is_post_request) {
    if ($is_rate_limited) {
        $remaining_time = $cooldown_time - (time() - $last_attempt);
        $error_message = "Trop de tentatives. Réessayez dans " . ceil($remaining_time / 60) . " minutes.";
        
        logSecurityEvent('LOGIN_RATE_LIMITED', [
            'attempts' => $login_attempts,
            'remaining_cooldown' => $remaining_time
        ]);
    } else {
        // Vérification CSRF
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            $error_message = 'Token de sécurité invalide';
            logSecurityEvent('LOGIN_CSRF_INVALID');
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Incrémenter tentatives
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_login_attempt'] = time();
            
            $auth_result = authenticateUser($username, $password);
            
            if ($auth_result['success']) {
                // Succès : nettoyer compteurs
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_login_attempt']);
                
                // Créer session utilisateur
                createSecureUserSession($auth_result['user']);
                
                logSecurityEvent('LOGIN_SUCCESS', [
                    'username' => $username,
                    'method' => $auth_result['method']
                ]);
                
                // Redirection POST-LOGIN
                error_log("LOGIN SUCCESS: redirecting to {$safe_redirect}");
                header('Location: ' . $safe_redirect);
                exit;
                
            } else {
                $error_message = $auth_result['error'];
                logSecurityEvent('LOGIN_FAILED', [
                    'username' => $username,
                    'error' => $auth_result['error']
                ]);
            }
        }
    }
}

// =====================================
// 🎨 AFFICHAGE TEMPLATE
// =====================================

$page_title = 'Connexion';
$page_subtitle = 'Accès au portail Guldagil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Portail Guldagil</title>
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $build_number ?>">
    <link rel="stylesheet" href="/assets/css/login.css?v=<?= $build_number ?>">
    
    <!-- Empêcher cache navigateur -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
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

        <?php if ($is_rate_limited): ?>
        <div class="alert alert-danger">
            <strong>Compte temporairement bloqué</strong><br>
            Trop de tentatives de connexion. Réessayez dans <?= ceil(($cooldown_time - (time() - $last_attempt)) / 60) ?> minutes.
        </div>
        <?php else: ?>

        <form method="POST" action="/auth/login.php<?= $redirect_param ? '?redirect=' . urlencode($redirect_param) : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="username" class="form-label">Nom d'utilisateur</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-input"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required 
                       autocomplete="username"
                       maxlength="50"
                       autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-input"
                       required 
                       autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary" id="loginBtn">
                🔑 Se connecter
            </button>
        </form>

        <?php endif; ?>

        <div class="footer-info">
            <p>Session: 9h30 • Version <?= $version ?? '0.5' ?> • Build <?= $build_number ?? '001' ?></p>
            <p>Tentatives: <?= $login_attempts ?>/<?= $max_attempts ?></p>
            <?php if (defined('DEBUG') && DEBUG): ?>
            <p style="font-size: 0.7rem; color: #999;">
                Debug: URI=<?= htmlspecialchars($current_uri) ?> | 
                Redirect=<?= htmlspecialchars($safe_redirect) ?> |
                POST=<?= $is_post_request ? 'Y' : 'N' ?> |
                Auth=<?= $user_authenticated ? 'Y' : 'N' ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-focus
document.addEventListener('DOMContentLoaded', function() {
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    if (usernameField && !usernameField.value.trim()) {
        usernameField.focus();
    } else if (passwordField && !passwordField.value) {
        passwordField.focus();
    }
});

// Validation + loading state
document.querySelector('form').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const submitBtn = document.getElementById('loginBtn');
    
    if (!username || !password) {
        e.preventDefault();
        alert('Veuillez remplir tous les champs');
        return;
    }
    
    if (username.length < 2) {
        e.preventDefault();
        alert('Le nom d\'utilisateur doit contenir au moins 2 caractères');
        return;
    }
    
    // État chargement
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.textContent = 'Connexion...';
    
    // Timeout sécurité
    setTimeout(() => {
        if (submitBtn.disabled) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
            submitBtn.innerHTML = '🔑 Se connecter';
        }
    }, 10000);
});

// Debug si activé
<?php if (defined('DEBUG') && DEBUG): ?>
console.log('Login Debug:', {
    currentURI: <?= json_encode($current_uri) ?>,
    redirectParam: <?= json_encode($redirect_param) ?>,
    safeRedirect: <?= json_encode($safe_redirect) ?>,
    isPost: <?= json_encode($is_post_request) ?>,
    authenticated: <?= json_encode($user_authenticated) ?>
});
<?php endif; ?>
</script>

</body>
</html>
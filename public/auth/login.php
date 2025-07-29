<?php
/**
 * Titre: Page de connexion CORRIGÉE - Session 9h30 + Redirection fixée
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 * 
 * CORRECTIONS CRITIQUES :
 * 1. Configuration session 9h30 AVANT session_start()
 * 2. RÉSOLUTION redirection infinie ERR_TOO_MANY_REDIRECTS
 * 3. Création session compatible AuthManager
 * 4. Cookie lifetime correct
 * 5. Correction DB_DSN vers configuration actuelle
 */

// =====================================
// 🔧 CONFIGURATION PRIORITAIRE
// =====================================
define('ROOT_PATH', dirname(dirname(__DIR__)));

// CRITIQUE : Charger config session AVANT session_start()
if (file_exists(ROOT_PATH . '/config/session_timeout.php')) {
    require_once ROOT_PATH . '/config/session_timeout.php';
}

// Définir SESSION_TIMEOUT si pas encore défini
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 34200); // 9h30 par défaut
}

// Configuration PHP pour sessions 9h30 AVANT démarrage
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.name', 'GULDAGIL_PORTAL_SESSION');

// Démarrer session APRÈS configuration
session_start();

// Chargement configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// =====================================
// 🔐 FONCTIONS AUTHENTIFICATION
// =====================================

/**
 * Fonction d'authentification PRIORITÉ AuthManager
 */
function authenticateUser($username, $password) {
    try {
        // 1. AuthManager en priorité ABSOLUE
        if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
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
        }
        
        // 2. Base de données directe en fallback - CORRIGÉ
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
            try {
                // Construire DSN manuellement à partir des constantes existantes
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $db = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                $stmt = $db->prepare("
                    SELECT id, username, password_hash, role, is_active, failed_attempts, locked_until
                    FROM auth_users 
                    WHERE username = :username 
                    AND is_active = 1
                    LIMIT 1
                ");
                
                $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch();
                
                if ($user) {
                    // Vérifier verrouillage compte
                    if ($user['locked_until'] && time() < strtotime($user['locked_until'])) {
                        return ['success' => false, 'error' => 'Compte temporairement verrouillé'];
                    }
                    
                    // Vérifier mot de passe
                    if (password_verify($password, $user['password_hash'])) {
                        // Réinitialiser tentatives échouées
                        $stmt = $db->prepare("
                            UPDATE auth_users 
                            SET failed_attempts = 0, locked_until = NULL, last_login = NOW()
                            WHERE id = :id
                        ");
                        $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
                        $stmt->execute();
                        
                        error_log("LOGIN SUCCESS via database: " . $username);
                        return [
                            'success' => true,
                            'user' => [
                                'id' => $user['id'],
                                'username' => $user['username'],
                                'role' => $user['role']
                            ],
                            'method' => 'database'
                        ];
                    } else {
                        // Incrémenter tentatives échouées
                        $failed_attempts = $user['failed_attempts'] + 1;
                        $locked_until = null;
                        
                        if ($failed_attempts >= 5) {
                            $locked_until = date('Y-m-d H:i:s', time() + 1800); // 30 min
                        }
                        
                        $stmt = $db->prepare("
                            UPDATE auth_users 
                            SET failed_attempts = :attempts, locked_until = :locked
                            WHERE id = :id
                        ");
                        $stmt->bindValue(':attempts', $failed_attempts, PDO::PARAM_INT);
                        $stmt->bindValue(':locked', $locked_until, PDO::PARAM_STR);
                        $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
                        $stmt->execute();
                        
                        return ['success' => false, 'error' => 'Identifiants incorrects'];
                    }
                }
            } catch (PDOException $e) {
                error_log("Erreur PDO login fallback: " . $e->getMessage());
                return ['success' => false, 'error' => 'Erreur de connexion base de données'];
            }
        }
        
        // 3. Fonction getDB() si disponible
        if (function_exists('getDB')) {
            try {
                $db = getDB();
                $stmt = $db->prepare("
                    SELECT id, username, password_hash, role, is_active, failed_attempts, locked_until
                    FROM auth_users 
                    WHERE username = :username 
                    AND is_active = 1
                    LIMIT 1
                ");
                
                $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    error_log("LOGIN SUCCESS via getDB(): " . $username);
                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'role' => $user['role']
                        ],
                        'method' => 'getDB'
                    ];
                }
            } catch (Exception $e) {
                error_log("Erreur getDB login fallback: " . $e->getMessage());
            }
        }
        
        // 4. AUCUN SYSTÈME DE FALLBACK AVEC COMPTES PAR DÉFAUT
        // Sécurité : pas de comptes admin/dev en dur
        
        return ['success' => false, 'error' => 'Identifiants incorrects'];
        
    } catch (Exception $e) {
        error_log("Erreur authentification: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erreur système'];
    }
}

/**
 * Créer session utilisateur sécurisée 9h30 - CRITIQUE
 */
function createSecureUserSession($user) {
    // Régénérer ID session pour sécurité
    session_regenerate_id(true);
    
    // Session duration 9h30 minimum
    $session_duration = SESSION_TIMEOUT; // 34200 secondes
    
    // Données session COMPATIBLES AuthManager
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regeneration'] = time();
    $_SESSION['expires_at'] = time() + $session_duration; // CRITIQUE !
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Configuration cookie session APRÈS session_start()
    $cookie_params = [
        'lifetime' => $session_duration,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookie_params);
    
    error_log("SESSION CREATED in login.php: duration={$session_duration}s, expires_at=" . $_SESSION['expires_at']);
}

/**
 * Logging sécurité
 */
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

// =====================================
// 🛡️ SÉCURITÉ RATE LIMITING
// =====================================

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$login_attempts = $_SESSION['login_attempts'] ?? 0;
$last_attempt = $_SESSION['last_login_attempt'] ?? 0;
$cooldown_time = 300; // 5 minutes
$max_attempts = 5;
$is_rate_limited = ($login_attempts >= $max_attempts) && (time() - $last_attempt < $cooldown_time);

// =====================================
// 📝 TRAITEMENT FORMULAIRE
// =====================================

$error_message = '';
$success_message = '';

// =====================================
// 🚨 CORRECTION REDIRECTION INFINIE
// =====================================

// Variables pour diagnostic
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_login_page = (strpos($current_uri, '/auth/login.php') !== false);
$redirect_param = $_GET['redirect'] ?? '';
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');

// Détection des redirections infinies potentielles
$potential_infinite_redirect = (
    $is_login_page && 
    !$is_post_request && 
    ($redirect_param === $current_uri || $redirect_param === '/auth/login.php')
);

if ($potential_infinite_redirect) {
    error_log("REDIRECT LOOP DETECTED: URI=$current_uri, redirect=$redirect_param");
    $redirect_param = '/'; // Forcer redirection vers accueil
}

// Redirection si déjà connecté - LOGIQUE CORRIGÉE
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] && !$is_post_request) {
    // Vérifier avec AuthManager si disponible - MAIS sans créer d'instance si pas nécessaire
    $is_authenticated = true;
    
    if (class_exists('AuthManager')) {
        try {
            $auth = AuthManager::getInstance();
            $is_authenticated = $auth->isAuthenticated();
        } catch (Exception $e) {
            error_log("Erreur AuthManager verification: " . $e->getMessage());
            // Garder $is_authenticated = true par défaut
        }
    }
    
    if ($is_authenticated) {
        // Logique de redirection sécurisée
        $redirect = $redirect_param ?: '/';
        
        // Sécurité : éviter redirections infinies
        if ($redirect === $current_uri || $redirect === '/auth/login.php') {
            $redirect = '/';
        }
        
        // Sécurité : valider URL redirection
        if (!preg_match('/^\/[a-zA-Z0-9\/_-]*$/', $redirect)) {
            $redirect = '/';
        }
        
        error_log("REDIRECT AUTHENTICATED USER: from=$current_uri to=$redirect");
        header('Location: ' . $redirect);
        exit;
    } else {
        // Session invalide, la nettoyer
        $_SESSION = array();
        session_destroy();
        session_start();
    }
}

// Générer token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Traitement formulaire SÉCURISÉ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification rate limiting
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
            
            // Tentative d'authentification
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_login_attempt'] = time();
            
            $auth_result = authenticateUser($username, $password);
            
            if ($auth_result['success']) {
                // Succès : réinitialiser compteurs
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_login_attempt']);
                
                // Créer session sécurisée 9h30
                createSecureUserSession($auth_result['user']);
                
                logSecurityEvent('LOGIN_SUCCESS', [
                    'username' => $username,
                    'method' => $auth_result['method']
                ]);
                
                // Redirection POST-REDIRECT-GET pattern
                $redirect = $redirect_param ?: '/';
                
                // Sécurité : éviter redirections infinies
                if ($redirect === $current_uri || $redirect === '/auth/login.php') {
                    $redirect = '/';
                }
                
                error_log("POST LOGIN SUCCESS: redirecting to $redirect");
                header('Location: ' . $redirect);
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
                Redirect=<?= htmlspecialchars($redirect_param) ?> |
                POST=<?= $is_post_request ? 'Y' : 'N' ?> |
                Auth=<?= isset($_SESSION['authenticated']) ? 'Y' : 'N' ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-focus sur premier champ vide
document.addEventListener('DOMContentLoaded', function() {
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    if (usernameField && !usernameField.value.trim()) {
        usernameField.focus();
    } else if (passwordField && !passwordField.value) {
        passwordField.focus();
    }
});

// Validation côté client + loading state
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
    
    // État de chargement
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.textContent = 'Connexion...';
    
    // Réactiver si erreur
    setTimeout(() => {
        if (submitBtn.disabled) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
            submitBtn.innerHTML = '🔑 Se connecter';
        }
    }, 10000); // 10 secondes timeout
});

// Debug redirection (si DEBUG activé)
<?php if (defined('DEBUG') && DEBUG): ?>
console.log('Login Debug:', {
    currentURI: <?= json_encode($current_uri) ?>,
    redirectParam: <?= json_encode($redirect_param) ?>,
    isPost: <?= json_encode($is_post_request) ?>,
    authenticated: <?= json_encode(isset($_SESSION['authenticated'])) ?>,
    potentialLoop: <?= json_encode($potential_infinite_redirect) ?>
});
<?php endif; ?>
</script>

</body>
</html>
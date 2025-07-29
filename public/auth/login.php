<?php
/**
 * Titre: Page de connexion CORRIGÉE - Session 9h30 stable
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 * 
 * CORRECTIONS CRITIQUES :
 * 1. Configuration session 9h30 AVANT session_start()
 * 2. Création session compatible AuthManager
 * 3. Suppression conflits expires_at
 * 4. Cookie lifetime correct
 */

// =====================================
// 🔧 CONFIGURATION PRIORITAIRE
// =====================================
define('ROOT_PATH', dirname(dirname(__DIR__)));

// CRITIQUE : Charger config session AVANT session_start()
require_once ROOT_PATH . '/config/session_timeout.php';

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
        
        // 2. Base de données directe en fallback
        if (defined('DB_DSN')) {
            $db = new PDO(DB_DSN, DB_USER, DB_PASS, [
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
        }
        
        // 3. AUCUN SYSTÈME DE FALLBACK AVEC COMPTES PAR DÉFAUT
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

// Redirection si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    // Vérifier avec AuthManager si disponible
    $is_authenticated = true;
    if (class_exists('AuthManager')) {
        $auth = AuthManager::getInstance();
        $is_authenticated = $auth->isAuthenticated();
    }
    
    if ($is_authenticated) {
        $redirect = $_GET['redirect'] ?? '/';
        header('Location: ' . $redirect);
        exit;
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
                
                // Redirection
                $redirect = $_GET['redirect'] ?? '/';
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
    
    <!-- CSS spécifique login -->
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
        }
        
        .login-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.15s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #f0f9ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .footer-info {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.75rem;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">🌊</div>
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

        <form method="POST" action="">
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
                       maxlength="50">
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

            <button type="submit" class="btn btn-primary">
                🔑 Se connecter
            </button>
        </form>

        <?php endif; ?>

        <div class="footer-info">
            <p>Session: 9h30 • Version <?= $version ?? '0.5' ?> • Build <?= $build_number ?? '001' ?></p>
            <p>Tentatives: <?= $login_attempts ?>/<?= $max_attempts ?></p>
        </div>
    </div>
</div>

<script>
// Auto-focus sur premier champ
document.addEventListener('DOMContentLoaded', function() {
    const usernameField = document.getElementById('username');
    if (usernameField && !usernameField.value) {
        usernameField.focus();
    }
});

// Validation basique côté client
document.querySelector('form').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
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
});
</script>

</body>
</html>
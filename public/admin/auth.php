<?php
// public/admin/auth.php - Vérification d'authentification
session_start();

// Configuration de sécurité
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 heure en secondes
define('ENABLE_IP_CHECK', false); // Activer la vérification IP (optionnel)
define('ALLOWED_IPS', ['127.0.0.1', '::1']); // IPs autorisées si ENABLE_IP_CHECK = true

/**
 * Vérifier si l'utilisateur est connecté et autorisé
 */
function checkAdminAuth() {
    // Vérifier si la session existe
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        redirectToLogin();
    }
    
    // Vérifier le timeout de session
    if (isset($_SESSION['admin_login_time'])) {
        if (time() - $_SESSION['admin_login_time'] > ADMIN_SESSION_TIMEOUT) {
            destroyAdminSession();
            redirectToLogin('Session expirée');
        }
    }
    
    // Vérifier l'IP si activé
    if (ENABLE_IP_CHECK) {
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!in_array($currentIP, ALLOWED_IPS)) {
            destroyAdminSession();
            http_response_code(403);
            die('Accès interdit depuis cette adresse IP');
        }
    }
    
    // Renouveler la session
    $_SESSION['admin_last_activity'] = time();
    
    return true;
}

/**
 * Rediriger vers la page de login
 */
function redirectToLogin($message = '') {
    $loginUrl = 'login.php';
    if ($message) {
        $loginUrl .= '?message=' . urlencode($message);
    }
    header('Location: ' . $loginUrl);
    exit;
}

/**
 * Détruire la session admin
 */
function destroyAdminSession() {
    // Log de la déconnexion
    if (isset($_SESSION['admin_username'])) {
        error_log("ADMIN_LOGOUT: User " . $_SESSION['admin_username'] . " logged out from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    // Supprimer les variables de session admin
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_login_time']);
    unset($_SESSION['admin_last_activity']);
}

/**
 * Obtenir les informations de l'utilisateur connecté
 */
function getAdminUser() {
    return [
        'username' => $_SESSION['admin_username'] ?? 'unknown',
        'login_time' => $_SESSION['admin_login_time'] ?? time(),
        'last_activity' => $_SESSION['admin_last_activity'] ?? time()
    ];
}

/**
 * Vérifier les permissions pour une action donnée
 */
function checkAdminPermission($action) {
    // Pour l'instant, tous les admins ont tous les droits
    // Vous pouvez étendre cette fonction pour gérer des rôles différents
    
    $user = getAdminUser();
    
    // Exemple de restrictions par utilisateur (optionnel)
    $restrictions = [
        'guest' => ['delete_rate', 'delete_option', 'import'], // Actions interdites pour "guest"
    ];
    
    if (isset($restrictions[$user['username']]) && in_array($action, $restrictions[$user['username']])) {
        http_response_code(403);
        throw new Exception('Permission insuffisante pour cette action');
    }
    
    return true;
}

/**
 * Enregistrer une action admin pour audit
 */
function logAdminAction($action, $details = []) {
    $user = getAdminUser();
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $user['username'],
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Log dans le fichier système
    error_log('ADMIN_AUDIT: ' . json_encode($logEntry));
    
    // Optionnel : enregistrer en base de données
    // saveAuditLog($logEntry);
}

/**
 * Générer un token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier un token CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Afficher les informations de session (pour debug)
 */
function getSessionInfo() {
    if (!isset($_SESSION['admin_logged_in'])) {
        return ['logged_in' => false];
    }
    
    $user = getAdminUser();
    $sessionDuration = time() - $user['login_time'];
    $timeRemaining = ADMIN_SESSION_TIMEOUT - $sessionDuration;
    
    return [
        'logged_in' => true,
        'username' => $user['username'],
        'login_time' => date('Y-m-d H:i:s', $user['login_time']),
        'session_duration' => gmdate('H:i:s', $sessionDuration),
        'time_remaining' => gmdate('H:i:s', max(0, $timeRemaining)),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
}

// Si ce fichier est appelé directement, vérifier l'auth
if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
    checkAdminAuth();
}
?>

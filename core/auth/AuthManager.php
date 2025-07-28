<?php
/**
 * Titre: Gestionnaire d'authentification - Session 9h + Remember Me
 * Chemin: /core/auth/AuthManager.php
 * Version: 0.5 beta + build auto
 */

class AuthManager 
{
    private $db;
    private static $instance = null;
    
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minutes
    const SESSION_LIFETIME = 32400; // 9 heures (journée de travail)
    const REMEMBER_LIFETIME = 2592000; // 30 jours
    const REGENERATE_INTERVAL = 7200; // 2h régénération

    public function __construct() {
        $this->initSession();
        $this->db = $this->getDatabase();
        $this->checkRememberToken();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialisation base de données
     */
    private function getDatabase() {
        try {
            if (function_exists('getDB')) {
                return getDB();
            }
            
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $name = defined('DB_NAME') ? DB_NAME : '';
            $user = defined('DB_USER') ? DB_USER : '';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            
            if (empty($name)) {
                throw new Exception("Configuration base de données manquante");
            }
            
            return new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur DB AuthManager: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifier token "Se souvenir de moi" au démarrage
     */
    private function checkRememberToken() {
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
            return; // Déjà connecté
        }

        $token = $_COOKIE['guldagil_remember'] ?? null;
        if (!$token || !$this->db) return;

        try {
            $stmt = $this->db->prepare("
                SELECT id, username, role, session_duration, is_active
                FROM auth_users 
                WHERE remember_token = ? AND remember_expires > NOW() AND is_active = 1
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                // Restaurer session automatiquement
                $this->createUserSession($user, false);
                $this->logActivity('AUTO_LOGIN', $user['username'], ['via' => 'remember_token']);
                
                // Renouveler token
                $this->renewRememberToken($user['id'], $token);
            } else {
                // Token invalide, le supprimer
                setcookie('guldagil_remember', '', time() - 3600, '/', '', false, true);
            }
        } catch (Exception $e) {
            error_log("Erreur remember token: " . $e->getMessage());
        }
    }

    /**
     * Authentification utilisateur avec Remember Me
     */
    public function login($username, $password, $remember = false) {
        try {
            if ($this->isUserLocked($username)) {
                return [
                    'success' => false,
                    'error' => 'Compte temporairement verrouillé. Réessayez dans 15 minutes.'
                ];
            }

            if (!$this->db) {
                return ['success' => false, 'error' => 'Service d\'authentification indisponible'];
            }

            $stmt = $this->db->prepare("
                SELECT id, username, password, role, session_duration, is_active 
                FROM auth_users 
                WHERE username = ? AND is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $this->clearFailedAttempts($username);
                
                // Créer session
                $this->createUserSession($user, $remember);
                
                // Gérer "Se souvenir de moi"
                if ($remember) {
                    $this->createRememberToken($user['id']);
                }
                
                // Mettre à jour dernière connexion
                $stmt = $this->db->prepare("UPDATE auth_users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                $this->logActivity('LOGIN_SUCCESS', $username, ['remember' => $remember]);
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ]
                ];
            } else {
                $this->recordFailedAttempt($username);
                return ['success' => false, 'error' => 'Identifiants incorrects'];
            }
            
        } catch (Exception $e) {
            error_log("Erreur login: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur système lors de la connexion'];
        }
    }

    /**
     * Vérification authentification INDÉPENDANTE
     */
    public function isAuthenticated() {
        // 1. Vérifier session PHP
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return false;
        }

        // 2. Vérifier expiration (9h max)
        if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
            $this->logout('expired');
            return false;
        }

        // 3. Régénération sécurisée périodique (2h)
        if ((time() - ($_SESSION['last_regeneration'] ?? 0)) > self::REGENERATE_INTERVAL) {
            $old_data = $_SESSION;
            session_regenerate_id(true);
            $_SESSION = $old_data;
            $_SESSION['last_regeneration'] = time();
        }

        // 4. Maintenir activité
        $_SESSION['last_activity'] = time();
        
        return true;
    }

    /**
     * Obtenir utilisateur connecté
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        return $_SESSION['user'] ?? null;
    }

    /**
     * Créer session utilisateur (9h par défaut)
     */
    private function createUserSession($user, $remember = false) {
        session_regenerate_id(true);
        
        $session_duration = $user['session_duration'] ?? self::SESSION_LIFETIME;
        
        $_SESSION['authenticated'] = true;
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        $_SESSION['expires_at'] = time() + $session_duration;
        
        // Session cookie prolongé
        ini_set('session.gc_maxlifetime', $session_duration);
        ini_set('session.cookie_lifetime', $session_duration);
    }

    /**
     * Créer token "Se souvenir de moi" dans auth_users
     */
    private function createRememberToken($user_id) {
        if (!$this->db) return;

        try {
            // Créer nouveau token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + self::REMEMBER_LIFETIME);
            
            $stmt = $this->db->prepare("
                UPDATE auth_users 
                SET remember_token = ?, remember_expires = ?
                WHERE id = ?
            ");
            $stmt->execute([$token, $expires, $user_id]);

            // Cookie côté client
            setcookie('guldagil_remember', $token, [
                'expires' => time() + self::REMEMBER_LIFETIME,
                'path' => '/',
                'httponly' => true,
                'secure' => isset($_SERVER['HTTPS']),
                'samesite' => 'Lax'
            ]);

        } catch (Exception $e) {
            error_log("Erreur création remember token: " . $e->getMessage());
        }
    }

    /**
     * Renouveler token remember me
     */
    private function renewRememberToken($user_id, $old_token) {
        if (!$this->db) return;

        try {
            $new_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + self::REMEMBER_LIFETIME);
            
            $stmt = $this->db->prepare("
                UPDATE auth_users 
                SET remember_token = ?, remember_expires = ?
                WHERE id = ? AND remember_token = ?
            ");
            $stmt->execute([$new_token, $expires, $user_id, $old_token]);

            // Nouveau cookie
            setcookie('guldagil_remember', $new_token, [
                'expires' => time() + self::REMEMBER_LIFETIME,
                'path' => '/',
                'httponly' => true,
                'secure' => isset($_SERVER['HTTPS']),
                'samesite' => 'Lax'
            ]);

        } catch (Exception $e) {
            error_log("Erreur renouvellement token: " . $e->getMessage());
        }
    }

    /**
     * Déconnexion complète
     */
    public function logout($reason = 'manual') {
        $username = $_SESSION['user']['username'] ?? 'unknown';
        $user_id = $_SESSION['user']['id'] ?? null;
        
        // Supprimer remember token dans auth_users
        if ($user_id && $this->db) {
            try {
                $stmt = $this->db->prepare("UPDATE auth_users SET remember_token = NULL, remember_expires = NULL WHERE id = ?");
                $stmt->execute([$user_id]);
            } catch (Exception $e) {
                error_log("Erreur suppression remember token: " . $e->getMessage());
            }
        }
        
        // Nettoyer session
        $_SESSION = array();
        
        // Supprimer cookies
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        setcookie('guldagil_remember', '', time() - 3600, '/', '', false, true);
        
        session_destroy();
        
        $this->logActivity('LOGOUT', $username, ['reason' => $reason]);
        return true;
    }

    /**
     * Gestion tentatives échouées
     */
    private function recordFailedAttempt($username) {
        if (!isset($_SESSION['failed_attempts'])) {
            $_SESSION['failed_attempts'] = [];
        }
        
        $_SESSION['failed_attempts'][$username] = [
            'count' => ($_SESSION['failed_attempts'][$username]['count'] ?? 0) + 1,
            'last_attempt' => time()
        ];
        
        $this->logActivity('LOGIN_FAILED', $username);
    }

    private function isUserLocked($username) {
        if (!isset($_SESSION['failed_attempts'][$username])) {
            return false;
        }
        
        $attempts = $_SESSION['failed_attempts'][$username];
        if ($attempts['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            return (time() - $attempts['last_attempt']) < self::LOCKOUT_TIME;
        }
        return false;
    }

    private function clearFailedAttempts($username) {
        unset($_SESSION['failed_attempts'][$username]);
    }

    /**
     * Logging des activités
     */
    private function logActivity($action, $username, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
            'details' => $details,
            'session_id' => session_id()
        ];
        error_log('AUTH_' . $action . ': ' . json_encode($logEntry));
    }

    /**
     * Initialisation session sécurisée (9h)
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', self::SESSION_LIFETIME);
            session_start();
        }
    }

    /**
     * FONCTION GLOBALE : Vérification auth sans header
     */
    public static function requireAuth($allowed_roles = null, $redirect = '/auth/login.php') {
        $auth = self::getInstance();
        
        if (!$auth->isAuthenticated()) {
            $current_url = $_SERVER['REQUEST_URI'] ?? '';
            $redirect_url = $redirect . '?redirect=' . urlencode($current_url);
            header('Location: ' . $redirect_url);
            exit;
        }
        
        // Vérifier rôles si spécifié
        if ($allowed_roles) {
            $user = $auth->getCurrentUser();
            if (!$user || !in_array($user['role'], (array)$allowed_roles)) {
                header('Location: /auth/login.php?error=insufficient_privileges');
                exit;
            }
        }
        
        return $auth->getCurrentUser();
    }
}
?>
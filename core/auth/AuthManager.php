<?php
// === guldagil_new/core/auth/AuthManager.php ===
/**
 * Gestionnaire d'authentification centralisé Guldagil
 * Chemin: /core/auth/AuthManager.php
 */

class AuthManager {
    private static $instance = null;
    private $users = [];
    private $currentUser = null;

    // Configuration sécurité
    const SESSION_TIMEOUT = 7200; // 2h
    const MAX_LOGIN_ATTEMPTS = 3;
    const LOCKOUT_TIME = 900; // 15min

    public function __construct() {
        $this->loadUsers();
        $this->initSession();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Définition des utilisateurs et rôles
     */
    private function loadUsers() {
        $this->users = [
            'user_guldagil' => [
                'password' => password_hash('GulUser2025!', PASSWORD_DEFAULT),
                'role' => 'user',
                'name' => 'Utilisateur Guldagil',
                'modules' => ['port'],
                'permissions' => ['read']
            ],
            'admin_guldagil' => [
                'password' => password_hash('GulAdmin2025!', PASSWORD_DEFAULT),
                'role' => 'admin', 
                'name' => 'Administrateur Guldagil',
                'modules' => ['port', 'adr', 'admin'],
                'permissions' => ['read', 'write', 'admin']
            ],
            'runser' => [
                'password' => password_hash('RunserDev2025!', PASSWORD_DEFAULT),
                'role' => 'dev',
                'name' => 'Jean-Thomas RUNSER (Dev)',
                'modules' => ['port', 'adr', 'quality', 'epi', 'tools', 'admin'],
                'permissions' => ['read', 'write', 'admin', 'dev']
            ]
        ];
    }

    /**
     * Tentative de connexion
     */
    public function login($username, $password) {
        // Vérifier tentatives précédentes
        if ($this->isUserLocked($username)) {
            return ['success' => false, 'error' => 'Compte temporairement verrouillé'];
        }

        if (!isset($this->users[$username])) {
            $this->recordFailedAttempt($username);
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }

        if (!password_verify($password, $this->users[$username]['password'])) {
            $this->recordFailedAttempt($username);
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }

        // Connexion réussie
        $this->createSession($username);
        $this->clearFailedAttempts($username);
        $this->logActivity('LOGIN', $username);

        return ['success' => true, 'user' => $this->getCurrentUser()];
    }

    /**
     * Créer session utilisateur
     */
    private function createSession($username) {
        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'logged_in' => true,
            'username' => $username,
            'role' => $this->users[$username]['role'],
            'login_time' => time(),
            'last_activity' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        $this->currentUser = $this->users[$username];
        $this->currentUser['username'] = $username;
    }

    /**
     * Vérifier authentification
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['auth']['logged_in']) || !$_SESSION['auth']['logged_in']) {
            return false;
        }

        // Vérifier timeout
        if (time() - $_SESSION['auth']['last_activity'] > self::SESSION_TIMEOUT) {
            $this->logout('timeout');
            return false;
        }

        // Renouveler activité
        $_SESSION['auth']['last_activity'] = time();
        return true;
    }

    /**
     * Vérifier accès module
     */
    public function canAccessModule($module) {
        if (!$this->isAuthenticated()) return false;
        
        $user = $this->getCurrentUser();
        return in_array($module, $user['modules']);
    }

    /**
     * Vérifier permission
     */
    public function hasPermission($permission) {
        if (!$this->isAuthenticated()) return false;
        
        $user = $this->getCurrentUser();
        return in_array($permission, $user['permissions']);
    }

    /**
     * Obtenir utilisateur actuel
     */
    public function getCurrentUser() {
        if ($this->currentUser) return $this->currentUser;
        
        if (!$this->isAuthenticated()) return null;
        
        $username = $_SESSION['auth']['username'];
        $this->currentUser = $this->users[$username];
        $this->currentUser['username'] = $username;
        return $this->currentUser;
    }

    /**
     * Déconnexion
     */
    public function logout($reason = 'manual') {
        $username = $_SESSION['auth']['username'] ?? 'unknown';
        $this->logActivity('LOGOUT', $username, ['reason' => $reason]);
        
        unset($_SESSION['auth']);
        $this->currentUser = null;
        
        if ($reason === 'timeout') {
            session_destroy();
        }
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
        if (!isset($_SESSION['failed_attempts'][$username])) return false;
        
        $attempts = $_SESSION['failed_attempts'][$username];
        if ($attempts['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $timeSince = time() - $attempts['last_attempt'];
            return $timeSince < self::LOCKOUT_TIME;
        }
        return false;
    }

    private function clearFailedAttempts($username) {
        unset($_SESSION['failed_attempts'][$username]);
    }

    /**
     * Logging
     */
    private function logActivity($action, $username, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
            'details' => $details
        ];
        
        error_log('AUTH_' . $action . ': ' . json_encode($logEntry));
    }

    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
    }
}
?>

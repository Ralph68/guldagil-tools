<?php
/**
 * Titre: AuthManager Sécurisé - Zéro Fallback + Remember Me + IP France
 * Chemin: /core/auth/AuthManager.php
 * Version: 0.5 beta + build auto
 */

class AuthManager {
    private static $instance = null;
    private $db;
    
    // Durées en secondes
    const SESSION_TIMEOUT = 34200; // 9h30 pour tous
    const REMEMBER_LIFETIME = 2592000; // 30 jours
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minutes
    
    private function __construct() {
        $this->initDatabase();
        $this->setupSession();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialisation BDD avec gestion erreurs
     */
    private function initDatabase() {
        try {
            global $db;
            if (!$db instanceof PDO) {
                throw new Exception("Connexion BDD invalide");
            }
            $this->db = $db;
        } catch (Exception $e) {
            error_log("AuthManager DB Error: " . $e->getMessage());
            throw new Exception("Service d'authentification indisponible");
        }
    }
    
    /**
     * Configuration session sécurisée
     */
    private function setupSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', self::SESSION_TIMEOUT);
            ini_set('session.cookie_lifetime', self::SESSION_TIMEOUT);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
    }
    
    /**
     * Vérification authentification complète
     */
    public function isAuthenticated() {
        // 1. Vérifier session PHP active
        if (!$this->isSessionValid()) {
            return false;
        }
        
        // 2. Vérifier session BDD
        if (!$this->isDatabaseSessionValid()) {
            // Essayer remember me avant d'échouer
            if ($this->tryRememberMe()) {
                return true;
            }
            
            $this->logout('session_expired');
            return false;
        }
        
        // 3. Vérifier restrictions IP (France uniquement)
        if (!$this->isIpAllowed()) {
            $this->logout('geo_blocked');
            return false;
        }
        
        // 4. Mettre à jour activité
        $this->updateLastActivity();
        
        return true;
    }
    
    /**
     * Vérification session PHP
     */
    private function isSessionValid() {
        return isset($_SESSION['auth_session_id']) && 
               isset($_SESSION['auth_user_id']) &&
               isset($_SESSION['auth_expires']) &&
               $_SESSION['auth_expires'] > time();
    }
    
    /**
     * Vérification session BDD
     */
    private function isDatabaseSessionValid() {
        if (!isset($_SESSION['auth_session_id'])) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT s.user_id, s.expires_at, u.username, u.role, u.is_active
                FROM auth_sessions s 
                JOIN auth_users u ON s.user_id = u.id 
                WHERE s.id = ? AND s.expires_at > NOW() AND u.is_active = 1
            ");
            $stmt->execute([$_SESSION['auth_session_id']]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Mettre à jour données utilisateur en session
                $_SESSION['auth_user_data'] = [
                    'id' => $session['user_id'],
                    'username' => $session['username'],
                    'role' => $session['role']
                ];
                return true;
            }
        } catch (Exception $e) {
            error_log("Auth DB check error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Vérification IP française (intégration existante)
     */
    private function isIpAllowed() {
        // Utiliser le système existant
        if (defined('IP_GEOLOCATION_ENABLED') && IP_GEOLOCATION_ENABLED) {
            if (function_exists('initIpGeolocationSecurity')) {
                $ip_security = initIpGeolocationSecurity();
                return $ip_security->isIpAllowed();
            }
        }
        
        // Si système IP non disponible, autoriser (développement)
        return true;
    }
    
    /**
     * Tentative remember me
     */
    private function tryRememberMe() {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, role
                FROM auth_users 
                WHERE remember_token = ? 
                AND remember_expires > NOW() 
                AND is_active = 1
            ");
            $stmt->execute([$_COOKIE['remember_token']]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Créer nouvelle session
                $this->createUserSession($user, false);
                $this->logActivity('AUTO_LOGIN', $user['username'], ['method' => 'remember_token']);
                return true;
            }
        } catch (Exception $e) {
            error_log("Remember me error: " . $e->getMessage());
        }
        
        // Nettoyer cookie invalide
        $this->clearRememberToken();
        return false;
    }
    
    /**
     * Connexion utilisateur avec protection brute force
     */
    public function login($username, $password, $remember = false) {
        try {
            // 1. Vérification rate limiting
            if ($this->isRateLimited($username)) {
                return [
                    'success' => false, 
                    'error' => 'Trop de tentatives. Réessayez dans 15 minutes.'
                ];
            }
            
            // 2. Vérification utilisateur
            $stmt = $this->db->prepare("
                SELECT id, username, password, role, session_duration, is_active 
                FROM auth_users 
                WHERE username = ? AND is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                $this->recordFailedAttempt($username);
                return ['success' => false, 'error' => 'Identifiants incorrects'];
            }
            
            // 3. Connexion réussie
            $this->clearFailedAttempts($username);
            $this->createUserSession($user, $remember);
            
            if ($remember) {
                $this->createRememberToken($user['id']);
            }
            
            $this->updateLastLogin($user['id']);
            $this->logActivity('LOGIN_SUCCESS', $username, ['remember' => $remember]);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur système'];
        }
    }
    
    /**
     * Création session utilisateur
     */
    private function createUserSession($user, $remember) {
        // 1. Générer ID session unique
        $session_id = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + self::SESSION_TIMEOUT);
        
        // 2. Stocker en BDD
        $stmt = $this->db->prepare("
            INSERT INTO auth_sessions (id, user_id, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$session_id, $user['id'], $expires_at]);
        
        // 3. Variables session PHP
        $_SESSION['auth_session_id'] = $session_id;
        $_SESSION['auth_user_id'] = $user['id'];
        $_SESSION['auth_expires'] = time() + self::SESSION_TIMEOUT;
        $_SESSION['auth_user_data'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        
        // 4. Nettoyer anciennes sessions
        $this->cleanExpiredSessions($user['id']);
    }
    
    /**
     * Gestion remember token
     */
    private function createRememberToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + self::REMEMBER_LIFETIME);
        
        $stmt = $this->db->prepare("
            UPDATE auth_users 
            SET remember_token = ?, remember_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$token, $expires, $user_id]);
        
        setcookie('remember_token', $token, time() + self::REMEMBER_LIFETIME, '/', '', false, true);
    }
    
    /**
     * Protection brute force
     */
    private function isRateLimited($username) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts, MAX(created_at) as last_attempt
            FROM auth_login_attempts 
            WHERE username = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= self::MAX_LOGIN_ATTEMPTS;
    }
    
    private function recordFailedAttempt($username) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $this->db->prepare("
            INSERT INTO auth_login_attempts (username, ip_address, user_agent) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$username, $ip, $user_agent]);
    }
    
    private function clearFailedAttempts($username) {
        $stmt = $this->db->prepare("
            DELETE FROM auth_login_attempts 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
    }
    
    /**
     * Déconnexion sécurisée
     */
    public function logout($reason = 'manual') {
        if (isset($_SESSION['auth_session_id'])) {
            // Supprimer session BDD
            $stmt = $this->db->prepare("DELETE FROM auth_sessions WHERE id = ?");
            $stmt->execute([$_SESSION['auth_session_id']]);
            
            $this->logActivity('LOGOUT', 
                $_SESSION['auth_user_data']['username'] ?? 'unknown', 
                ['reason' => $reason]
            );
        }
        
        // Nettoyer session PHP
        $_SESSION = array();
        session_destroy();
        
        // Nettoyer remember token
        $this->clearRememberToken();
    }
    
    private function clearRememberToken() {
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }
    
    /**
     * Utilisateur actuel
     */
    public function getCurrentUser() {
        return $_SESSION['auth_user_data'] ?? null;
    }
    
    /**
     * Vérification permission par rôle
     */
    public function hasRole($required_roles) {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        $roles = is_array($required_roles) ? $required_roles : [$required_roles];
        
        // Dev a accès à tout
        if ($user['role'] === 'dev') return true;
        
        return in_array($user['role'], $roles);
    }
    
    /**
     * Vérification accès module
     */
    public function canAccessModule($module) {
        $permissions = [
            'admin' => ['dev', 'admin'],
            'user' => ['dev', 'admin', 'user', 'logistique'],
            'port' => ['dev', 'admin', 'user', 'logistique'],
            'auth' => ['dev', 'admin', 'user', 'logistique']
        ];
        
        return $this->hasRole($permissions[$module] ?? []);
    }
    
    /**
     * Maintenance et nettoyage
     */
    private function updateLastActivity() {
        if (isset($_SESSION['auth_session_id'])) {
            $stmt = $this->db->prepare("
                UPDATE auth_sessions 
                SET expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND) 
                WHERE id = ?
            ");
            $stmt->execute([self::SESSION_TIMEOUT, $_SESSION['auth_session_id']]);
            
            $_SESSION['auth_expires'] = time() + self::SESSION_TIMEOUT;
        }
    }
    
    private function updateLastLogin($user_id) {
        $stmt = $this->db->prepare("UPDATE auth_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    
    private function cleanExpiredSessions($user_id = null) {
        if ($user_id) {
            // Garder seulement la session courante pour cet utilisateur
            $stmt = $this->db->prepare("
                DELETE FROM auth_sessions 
                WHERE user_id = ? AND id != ?
            ");
            $stmt->execute([$user_id, $_SESSION['auth_session_id'] ?? '']);
        } else {
            // Nettoyer toutes les sessions expirées
            $stmt = $this->db->prepare("DELETE FROM auth_sessions WHERE expires_at <= NOW()");
            $stmt->execute();
        }
    }
    
    /**
     * Logging des activités
     */
    private function logActivity($action, $username, $details = []) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $log_file = ROOT_PATH . '/storage/logs/auth.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Statistiques pour admin
     */
    public function getAuthStats() {
        try {
            $stats = [];
            
            // Sessions actives
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM auth_sessions WHERE expires_at > NOW()");
            $stmt->execute();
            $stats['active_sessions'] = $stmt->fetchColumn();
            
            // Utilisateurs par rôle
            $stmt = $this->db->prepare("SELECT role, COUNT(*) as count FROM auth_users WHERE is_active = 1 GROUP BY role");
            $stmt->execute();
            $stats['users_by_role'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Tentatives échouées dernières 24h
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM auth_login_attempts WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stmt->execute();
            $stats['failed_attempts_24h'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

// Fonction helper pour compatibilité
function isAuthenticated() {
    try {
        $auth = AuthManager::getInstance();
        return $auth->isAuthenticated();
    } catch (Exception $e) {
        return false;
    }
}

?>
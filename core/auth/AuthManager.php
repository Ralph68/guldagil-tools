<?php
/**
 * Titre: Gestionnaire d'authentification avec base de données
 * Chemin: /core/auth/AuthManager.php
 * Version: 0.5 beta + build auto
 */

class AuthManager {
    private static $instance = null;
    private $db;
    private $currentUser = null;

    // Configuration sécurité
    const SESSION_TIMEOUT = 7200; // 2h par défaut
    const MAX_LOGIN_ATTEMPTS = 3;
    const LOCKOUT_TIME = 900; // 15min

    /**
     * Créer session utilisateur
     */
    private function createSession($user) {
        session_regenerate_id(true);
        
        // Nettoyer anciennes sessions
        $this->cleanupOldSessions($user['id']);
        
        // Créer nouvelle session
        $session_id = session_id();
        $session_duration = $user['session_duration'] ?: self::SESSION_TIMEOUT;
        $expires_at = date('Y-m-d H:i:s', time() + $session_duration);
        
        $stmt = $this->db->prepare("
            INSERT INTO auth_sessions (id, user_id, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$session_id, $user['id'], $expires_at]);
        
        $_SESSION['auth'] = [
            'logged_in' => true,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'login_time' => time(),
            'last_activity' => time(),
            'session_duration' => $session_duration,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->currentUser = $user;
    }

    /**
     * Vérifier authentification
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['auth']['logged_in']) || !$_SESSION['auth']['logged_in']) {
            return false;
        }

        // Vérifier session en BDD
        $session_id = session_id();
        $stmt = $this->db->prepare("
            SELECT user_id, expires_at 
            FROM auth_sessions 
            WHERE id = ? AND expires_at > NOW()
        ");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            $this->logout('expired');
            return false;
        }

        // Vérifier timeout
        $session_duration = $_SESSION['auth']['session_duration'] ?? self::SESSION_TIMEOUT;
        if (time() - $_SESSION['auth']['last_activity'] > $session_duration) {
            $this->logout('timeout');
            return false;
        }

        // Renouveler activité
        $_SESSION['auth']['last_activity'] = time();
        return true;
    }

    /**
     * Obtenir utilisateur actuel
     */
    public function getCurrentUser() {
        if ($this->currentUser) return $this->currentUser;
        
        if (!$this->isAuthenticated()) return null;
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, role, session_duration, created_at, last_login
                FROM auth_users 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$_SESSION['auth']['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Mapper vers format attendu
                $this->currentUser = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'name' => $this->getUserDisplayName($user['username'], $user['role']),
                    'role' => $user['role'],
                    'modules' => $this->getUserModules($user['role']),
                    'permissions' => $this->getUserPermissions($user['role'])
                ];
            }
            
            return $this->currentUser;
            
        } catch (Exception $e) {
            error_log('AuthManager getCurrentUser error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mappage des modules par rôle
     */
    private function getUserModules($role) {
        $modules = [
            'dev' => ['calculateur', 'adr', 'controle-qualite', 'epi', 'outillages', 'admin'],
            'admin' => ['calculateur', 'adr', 'admin'],
            'user' => ['calculateur']
        ];
        
        return $modules[$role] ?? [];
    }

    /**
     * Mappage des permissions par rôle
     */
    private function getUserPermissions($role) {
        $permissions = [
            'dev' => ['read', 'write', 'admin', 'dev'],
            'admin' => ['read', 'write', 'admin'],
            'user' => ['read']
        ];
        
        return $permissions[$role] ?? [];
    }

    /**
     * Nom d'affichage selon utilisateur
     */
    private function getUserDisplayName($username, $role) {
        $names = [
            'dev' => 'Développeur',
            'admin' => 'Administrateur',
            'user' => 'Utilisateur'
        ];
        
        return $names[$role] ?? ucfirst($username);
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
     * Déconnexion
     */
    public function logout($reason = 'manual') {
        $username = $_SESSION['auth']['username'] ?? 'unknown';
        $this->logActivity('LOGOUT', $username, ['reason' => $reason]);
        
        // Supprimer session en BDD
        if (isset($_SESSION['auth']['user_id'])) {
            $session_id = session_id();
            $stmt = $this->db->prepare("DELETE FROM auth_sessions WHERE id = ?");
            $stmt->execute([$session_id]);
        }
        
        unset($_SESSION['auth']);
        $this->currentUser = null;
        
        if ($reason === 'timeout' || $reason === 'expired') {
            session_destroy();
        }
    }

    /**
     * Mise à jour dernière connexion
     */
    private function updateLastLogin($user_id) {
        try {
            $stmt = $this->db->prepare("UPDATE auth_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log('AuthManager updateLastLogin error: ' . $e->getMessage());
        }
    }

    /**
     * Nettoyer anciennes sessions
     */
    private function cleanupOldSessions($user_id) {
        try {
            // Supprimer sessions expirées
            $this->db->exec("DELETE FROM auth_sessions WHERE expires_at < NOW()");
            
            // Garder seulement la session la plus récente par utilisateur
            $stmt = $this->db->prepare("
                DELETE FROM auth_sessions 
                WHERE user_id = ? 
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM auth_sessions 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    ) AS latest
                )
            ");
            $stmt->execute([$user_id, $user_id]);
        } catch (Exception $e) {
            error_log('AuthManager cleanupOldSessions error: ' . $e->getMessage());
        }
    }

    /**
     * Gestion tentatives échouées (en session)
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
     * Logging des activités
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

    /**
     * Initialisation session
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
    }
}

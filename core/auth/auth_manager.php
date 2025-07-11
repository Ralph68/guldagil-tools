<?php
/**
 * Titre: Gestionnaire d'authentification centralisé
 * Chemin: /core/auth/auth_manager.php
 * Version: 0.5 beta + build auto
 */

class AuthManager {
    private $db;
    private $max_attempts = 5;
    private $lockout_time = 900; // 15 minutes
    private static $instance = null;
    
    private function __construct() {
        if (function_exists('getDB')) {
            $this->db = getDB();
        } else {
            throw new Exception("Base de données non disponible");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Authentification principale
     */
    public function login($email_or_username, $password, $remember_me = false) {
        try {
            // Vérifier tentatives
            if ($this->isAccountLocked($email_or_username)) {
                return [
                    'success' => false,
                    'error' => 'Compte temporairement bloqué (trop de tentatives)'
                ];
            }
            
            // 1. Vérifier dans auth_users (système principal)
            $user = $this->checkAuthUsers($email_or_username, $password);
            if ($user) {
                $this->handleSuccessfulLogin($user, $remember_me);
                return ['success' => true, 'user' => $user];
            }
            
            // 2. Vérifier dans base EPI (si existe)
            $epi_user = $this->checkEpiUsers($email_or_username, $password);
            if ($epi_user) {
                $this->handleSuccessfulLogin($epi_user, $remember_me);
                return ['success' => true, 'user' => $epi_user];
            }
            
            // Échec : enregistrer tentative
            $this->recordFailedAttempt($email_or_username);
            
            return [
                'success' => false,
                'error' => 'Identifiants incorrects'
            ];
            
        } catch (Exception $e) {
            error_log("Erreur auth: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur système d\'authentification'
            ];
        }
    }
    
    /**
     * Vérifier dans table auth_users existante
     */
    private function checkAuthUsers($email_or_username, $password) {
        $stmt = $this->db->prepare("
            SELECT id, username, password, role, is_active, last_login, session_duration
            FROM auth_users 
            WHERE username = ? AND is_active = 1
        ");
        $stmt->execute([$email_or_username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Debug temporaire
            error_log("DEBUG: Testing password for user " . $user['username']);
            error_log("DEBUG: Hash from DB: " . $user['password']);
            error_log("DEBUG: password_verify result: " . (password_verify($password, $user['password']) ? 'TRUE' : 'FALSE'));
            
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifier dans base EPI existante (future intégration employés)
     */
    private function checkEpiUsers($email_or_username, $password) {
        // TODO: À implémenter plus tard pour BDD employés (email+pw)
        // Vérifier si table employés existe
        if (!$this->tableExists('employees')) {
            return false;
        }
        
        // Logique d'authentification employés à développer
        return false;
    }
    
    /**
     * Vérifier mot de passe EPI (hash MD5 ou autre)
     */
    private function verifyEpiPassword($password, $hash) {
        // Essayer différents formats de hash EPI
        if (md5($password) === $hash) return true;
        if (sha1($password) === $hash) return true;
        if ($password === $hash) return true; // Temporaire pour dev
        
        return false;
    }
    
    /**
     * Migrer utilisateur EPI vers auth_users
     */
    private function migrateEpiUser($epi_user, $password) {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO auth_users (username, email, password_hash, role, is_active, created_at)
            VALUES (?, ?, ?, 'user', 1, NOW())
        ");
        $stmt->execute([
            $epi_user['username'],
            $epi_user['email'],
            password_hash($password, PASSWORD_DEFAULT)
        ]);
    }
    
    /**
     * Gestion connexion réussie
     */
    private function handleSuccessfulLogin($user, $remember_me) {
        // Session
        $_SESSION['authenticated'] = true;
        $_SESSION['user'] = $user;
        $_SESSION['login_time'] = time();
        
        // Reset tentatives
        $this->clearFailedAttempts($user['email'] ?? $user['username']);
        
        // Cookie remember me
        if ($remember_me) {
            $this->setRememberMeCookie($user['id']);
        }
        
        // Log
        $this->logAuthEvent('LOGIN_SUCCESS', $user['email'] ?? $user['username']);
        
        // Mettre à jour last_login
        $this->updateLastLogin($user['id']);
    }
    
    /**
     * Vérifier si compte bloqué
     */
    private function isAccountLocked($identifier) {
        if (!isset($_SESSION['auth_attempts'][$identifier])) {
            return false;
        }
        
        $attempts = $_SESSION['auth_attempts'][$identifier];
        if ($attempts['count'] >= $this->max_attempts) {
            if (time() - $attempts['last_attempt'] < $this->lockout_time) {
                return true;
            }
            // Délai expiré, reset
            unset($_SESSION['auth_attempts'][$identifier]);
        }
        
        return false;
    }
    
    /**
     * Enregistrer tentative échouée
     */
    private function recordFailedAttempt($identifier) {
        if (!isset($_SESSION['auth_attempts'])) {
            $_SESSION['auth_attempts'] = [];
        }
        
        if (!isset($_SESSION['auth_attempts'][$identifier])) {
            $_SESSION['auth_attempts'][$identifier] = ['count' => 0];
        }
        
        $_SESSION['auth_attempts'][$identifier]['count']++;
        $_SESSION['auth_attempts'][$identifier]['last_attempt'] = time();
        
        $this->logAuthEvent('LOGIN_FAILED', $identifier);
    }
    
    /**
     * Effacer tentatives échouées
     */
    private function clearFailedAttempts($identifier) {
        if (isset($_SESSION['auth_attempts'][$identifier])) {
            unset($_SESSION['auth_attempts'][$identifier]);
        }
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        if (isset($_SESSION['user'])) {
            $this->logAuthEvent('LOGOUT', $_SESSION['user']['email'] ?? $_SESSION['user']['username']);
        }
        
        // Supprimer cookie remember me
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Détruire session
        session_destroy();
        session_start();
    }
    
    /**
     * Vérifier authentification
     */
    public function isAuthenticated() {
        // Vérifier session
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            return true;
        }
        
        // Vérifier cookie remember me
        if (isset($_COOKIE['remember_token'])) {
            return $this->checkRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Cookie remember me
     */
    private function setRememberMeCookie($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 jours
        
        // Sauvegarder token en BDD
        $stmt = $this->db->prepare("
            INSERT INTO auth_tokens (user_id, token, expires_at)
            VALUES (?, ?, FROM_UNIXTIME(?))
            ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$user_id, hash('sha256', $token), $expires]);
        
        setcookie('remember_token', $token, $expires, '/', '', true, true);
    }
    
    /**
     * Vérifier token remember me
     */
    private function checkRememberToken($token) {
        $stmt = $this->db->prepare("
            SELECT au.* FROM auth_users au
            JOIN auth_tokens at ON au.id = at.user_id
            WHERE at.token = ? AND at.expires_at > NOW() AND au.is_active = 1
        ");
        $stmt->execute([hash('sha256', $token)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['authenticated'] = true;
            $_SESSION['user'] = $user;
            $_SESSION['login_time'] = time();
            return true;
        }
        
        return false;
    }
    
    /**
     * Utilitaires
     */
    private function tableExists($table) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function updateLastLogin($user_id) {
        $stmt = $this->db->prepare("UPDATE auth_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    
    private function logAuthEvent($event, $identifier, $data = []) {
        // Log simplifié en attendant table auth_logs
        error_log("AUTH: {$event} - {$identifier} - " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
}

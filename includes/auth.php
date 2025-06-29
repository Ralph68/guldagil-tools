<?php
/**
 * Classe Authentification - Gul Calc Frais de port
 * Chemin : /includes/auth.php
 * Version : 0.5 beta
 */

require_once __DIR__ . '/../config/auth_database.php';

class Auth {
    private $db;
    private $config;
    
    public function __construct() {
        AuthDatabase::getInstance(); // Initialise les tables auth
        $this->db = getDB(); // Utilise votre connexion existante
        $this->config = require __DIR__ . '/../config/auth.php';
        $this->initSession();
    }
    
    private function initSession() {
        $sessionConfig = $this->config['session'];
        
        session_name($sessionConfig['name']);
        session_set_cookie_params([
            'secure' => $sessionConfig['secure'],
            'httponly' => $sessionConfig['httponly'],
            'samesite' => $sessionConfig['samesite']
        ]);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($username, $password, $remember = false) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, password, role, session_duration, is_active 
                FROM auth_users 
                WHERE username = :username AND is_active = 1
            ");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                return false;
            }
            
            // Créer la session
            $sessionId = $this->generateSessionId();
            $expiresAt = $this->calculateExpiration($user['session_duration']);
            
            $stmt = $this->db->prepare("
                INSERT INTO auth_sessions (id, user_id, expires_at) 
                VALUES (:id, :user_id, :expires_at)
            ");
            $stmt->execute([
                'id' => $sessionId,
                'user_id' => $user['id'],
                'expires_at' => $expiresAt
            ]);
            
            // Mettre à jour last_login
            $stmt = $this->db->prepare("UPDATE auth_users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['id' => $user['id']]);
            
            // Définir les variables de session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['session_id'] = $sessionId;
            $_SESSION['expires_at'] = $expiresAt;
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur login : " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        if (isset($_SESSION['session_id'])) {
            $stmt = $this->db->prepare("DELETE FROM auth_sessions WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['session_id']]);
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
            return false;
        }
        
        // Vérifier la session en base
        $stmt = $this->db->prepare("
            SELECT expires_at FROM auth_sessions 
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([
            'id' => $_SESSION['session_id'],
            'user_id' => $_SESSION['user_id']
        ]);
        $session = $stmt->fetch();
        
        if (!$session) {
            return false;
        }
        
        // Vérifier l'expiration (NULL = illimitée pour dev)
        if ($session['expires_at'] !== NULL && strtotime($session['expires_at']) < time()) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['role'];
        $roleConfig = $this->config['roles'][$userRole] ?? null;
        
        if (!$roleConfig) {
            return false;
        }
        
        return in_array('*', $roleConfig['permissions']) || 
               in_array($permission, $roleConfig['permissions']);
    }
    
    public function canAccessPage($page) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['role'];
        $roleConfig = $this->config['roles'][$userRole] ?? null;
        
        if (!$roleConfig) {
            return false;
        }
        
        // Accès total pour dev
        if (in_array('*', $roleConfig['pages'])) {
            return true;
        }
        
        foreach ($roleConfig['pages'] as $allowedPage) {
            if (fnmatch($allowedPage, $page)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function requireAuth($redirectTo = null) {
        if (!$this->isLoggedIn()) {
            $redirectTo = $redirectTo ?? $this->config['pages']['login'];
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            header("Location: " . $this->config['pages']['forbidden']);
            exit;
        }
    }
    
    private function generateSessionId() {
        return bin2hex(random_bytes(32));
    }
    
    private function calculateExpiration($duration) {
        return $duration === 0 ? NULL : date('Y-m-d H:i:s', time() + $duration);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
}

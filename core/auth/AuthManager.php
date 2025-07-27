<?php
/**
 * Titre: Gestionnaire d'authentification - Version corrigée
 * Chemin: /core/auth/AuthManager.php
 * Version: 0.5 beta + build auto
 */

class AuthManager 
{
    private $db;
    private static $instance = null;
    
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minutes
    const SESSION_LIFETIME = 7200; // 2 heures par défaut

    public function __construct() {
        $this->initSession();
        $this->db = $this->getDatabase();
    }

    /**
     * Singleton pattern (optionnel, pour compatibilité)
     */
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
            
            // Configuration directe si getDB() n'existe pas
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $name = defined('DB_NAME') ? DB_NAME : '';
            $user = defined('DB_USER') ? DB_USER : '';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            
            if (empty($name)) {
                throw new Exception("Configuration base de données manquante");
            }
            
            $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            return $pdo;
            
        } catch (Exception $e) {
            error_log("Erreur DB AuthManager: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Authentification utilisateur
     */
    public function login($username, $password, $remember = false) {
        try {
            // Vérification tentatives échouées
            if ($this->isUserLocked($username)) {
                return [
                    'success' => false,
                    'error' => 'Compte temporairement verrouillé. Réessayez dans 15 minutes.'
                ];
            }

            // Recherche utilisateur en base
            if ($this->db) {
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
                    
                    // Mettre à jour dernière connexion
                    $stmt = $this->db->prepare("UPDATE auth_users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    $this->logActivity('LOGIN_SUCCESS', $username);
                    
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
                    return [
                        'success' => false,
                        'error' => 'Identifiants incorrects'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'Service d\'authentification indisponible'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erreur login: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur système lors de la connexion'
            ];
        }
    }

    /**
     * Vérifier si utilisateur est connecté
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return false;
        }

        // Vérifier expiration session
        if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
            $this->logout('expired');
            return false;
        }

        // Régénérer ID session périodiquement
        if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }

        return true;
    }

    /**
     * Obtenir utilisateur connecté
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $user = $_SESSION['user'] ?? null;
        
        // Ajouter les préférences de cookies si elles existent
        if ($user && isset($user['id'])) {
            try {
                $stmt = $this->db->prepare("SELECT cookie_preference FROM auth_users WHERE id = :id");
                $stmt->execute(['id' => $user['id']]);
                $cookiePref = $stmt->fetchColumn();
                
                if ($cookiePref) {
                    // Définir un cookie côté client pour maintenir la préférence
                    setcookie('guldagil_cookie_consent', $cookiePref, [
                        'expires' => time() + 60*60*24*730, // 2 ans
                        'path' => '/',
                        'samesite' => 'Lax'
                    ]);
                    
                    // Ajouter au tableau utilisateur
                    $user['cookie_preference'] = $cookiePref;
                }
            } catch (Exception $e) {
                // Silencieux - la colonne n'existe peut-être pas encore
            }
        }
        
        return $user;
    }

    /**
     * Créer session utilisateur
     */
    private function createUserSession($user, $remember = false) {
        // Régénérer ID session pour sécurité
        session_regenerate_id(true);
        
        $_SESSION['authenticated'] = true;
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        
        // Durée de session personnalisée
        $session_duration = $user['session_duration'] ?? self::SESSION_LIFETIME;
        if ($session_duration > 0) {
            $_SESSION['expires_at'] = time() + $session_duration;
        }
        
        // Cookie de session étendu si "Se souvenir"
        if ($remember) {
            $lifetime = $session_duration > 0 ? $session_duration : 86400 * 7; // 7 jours
            ini_set('session.cookie_lifetime', $lifetime);
        }
    }

    /**
     * Déconnexion
     */
    public function logout($reason = 'manual') {
        $username = $_SESSION['user']['username'] ?? 'unknown';
        
        // Nettoyer session
        $_SESSION = array();
        
        // Détruire cookie session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
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
            'details' => $details,
            'session_id' => session_id()
        ];
        error_log('AUTH_' . $action . ': ' . json_encode($logEntry));
    }

    /**
     * Initialisation session sécurisée
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
        }
    }

    /**
     * Vérification MFA (si implémenté)
     */
    public function verifyMFA($userId, $code) {
        // À implémenter selon besoins
        return true;
    }
}

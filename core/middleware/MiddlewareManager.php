<?php
/**
 * Titre: Gestionnaire Middleware CORRIGÉ - Session stable 9h30
 * Chemin: /core/middleware/MiddlewareManager.php
 * Version: 0.5 beta + build auto
 * 
 * CORRECTIONS APPLIQUÉES :
 * 1. Vérification session compatible AuthManager
 * 2. Extension automatique session sur activité
 * 3. Suppression vérifications conflictuelles
 * 4. Alignement timeout 9h30
 */

class MiddlewareManager 
{
    private $middlewares = [];
    
    // Constantes alignées avec AuthManager
    const SESSION_LIFETIME = 34200; // 9h30 - ALIGNÉ !
    const ACTIVITY_EXTEND_TIME = 1800; // 30min pour extension auto
    const REGENERATE_INTERVAL = 7200; // 2h régénération
    
    public function __construct() {
        $this->registerDefaultMiddlewares();
    }
    
    /**
     * Enregistrer middlewares par défaut
     */
    private function registerDefaultMiddlewares() {
        $this->registerMiddleware('session', [$this, 'sessionMiddleware']);
        $this->registerMiddleware('auth', [$this, 'authMiddleware']);
        $this->registerMiddleware('admin', [$this, 'adminMiddleware']);
        $this->registerMiddleware('rate_limit', [$this, 'rateLimitMiddleware']);
    }
    
    /**
     * Enregistrer un middleware
     */
    public function registerMiddleware(string $name, callable $middleware): void {
        $this->middlewares[$name] = $middleware;
    }
    
    /**
     * Exécuter middleware par nom
     */
    public function executeMiddleware(string $name): bool {
        if (!isset($this->middlewares[$name])) {
            error_log("Middleware '$name' introuvable");
            return false;
        }
        
        return call_user_func($this->middlewares[$name]);
    }
    
    /**
     * Redirection vers login
     */
    private function redirectToLogin() {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $redirect_url = '/auth/login.php?redirect=' . urlencode($current_url);
        header('Location: ' . $redirect_url);
        exit;
    }
    
    /**
     * Middleware de session - CORRIGÉ COMPATIBLE AUTHMANAGER
     */
    private function sessionMiddleware(): bool {
        // Si AuthManager disponible, le laisser gérer complètement
        if (class_exists('AuthManager')) {
            // AuthManager gère déjà initSession(), ne pas interférer
            return true;
        }
        
        // Fallback si AuthManager indisponible
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', self::SESSION_LIFETIME);
            ini_set('session.cookie_lifetime', self::SESSION_LIFETIME);
            session_start();
            
            // Régénération périodique de l'ID de session
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > self::REGENERATE_INTERVAL) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
        
        return true;
    }
    
    /**
     * Middleware d'authentification - CORRIGÉ COMPATIBLE
     */
    private function authMiddleware(): bool {
        // 1. Priorité ABSOLUE à AuthManager si disponible
        if (class_exists('AuthManager')) {
            $authManager = AuthManager::getInstance();
            if (!$authManager->isAuthenticated()) {
                $this->redirectToLogin();
                return false;
            }
            
            // Extension automatique sur activité
            $last_activity = $_SESSION['last_activity'] ?? 0;
            if ((time() - $last_activity) < self::ACTIVITY_EXTEND_TIME) {
                $_SESSION['expires_at'] = time() + self::SESSION_LIFETIME;
            }
            $_SESSION['last_activity'] = time();
            
            return true;
        }
        
        // 2. Fallback : vérification session basique
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            $this->redirectToLogin();
            return false;
        }
        
        // 3. Vérifier expiration SEULEMENT si AuthManager pas disponible
        if (isset($_SESSION['expires_at'])) {
            if ($_SESSION['expires_at'] < time()) {
                error_log("MIDDLEWARE: Session expired, expires_at=" . $_SESSION['expires_at'] . ", now=" . time());
                session_destroy();
                $this->redirectToLogin();
                return false;
            }
            
            // Extension automatique sur activité
            $last_activity = $_SESSION['last_activity'] ?? 0;
            if ((time() - $last_activity) < self::ACTIVITY_EXTEND_TIME) {
                $_SESSION['expires_at'] = time() + self::SESSION_LIFETIME;
            }
        } else {
            // Créer expires_at si manquant
            $_SESSION['expires_at'] = time() + self::SESSION_LIFETIME;
        }
        
        // Mise à jour du timestamp de dernière activité
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Middleware admin (nécessite auth + rôle admin/dev) - INCHANGÉ
     */
    private function adminMiddleware(): bool {
        // D'abord vérifier l'authentification
        if (!$this->authMiddleware()) {
            return false;
        }
        
        // Vérifier les permissions admin
        $userRole = $_SESSION['user']['role'] ?? 'guest';
        $adminRoles = ['admin', 'dev'];
        
        if (!in_array($userRole, $adminRoles)) {
            http_response_code(403);
            echo "Accès refusé - Permissions administrateur requises";
            exit;
        }
        
        return true;
    }
    
    /**
     * Middleware de limitation de taux (rate limiting)
     */
    private function rateLimitMiddleware(): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheFile = ROOT_PATH . '/storage/cache/rate_limit_' . md5($ip) . '.json';
        $cacheDir = dirname($cacheFile);
        
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        
        $now = time();
        $windowTime = 3600; // 1 heure
        $maxRequests = 1000; // 1000 requêtes par heure
        
        $data = ['requests' => [], 'blocked_until' => 0];
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            if ($content) {
                $data = json_decode($content, true) ?: $data;
            }
        }
        
        // Vérifier si IP bloquée
        if ($data['blocked_until'] > $now) {
            http_response_code(429);
            header('Retry-After: ' . ($data['blocked_until'] - $now));
            echo "Trop de requêtes. Réessayez plus tard.";
            exit;
        }
        
        // Nettoyer anciennes requêtes
        $data['requests'] = array_filter($data['requests'], function($time) use ($now, $windowTime) {
            return ($now - $time) < $windowTime;
        });
        
        // Ajouter requête actuelle
        $data['requests'][] = $now;
        
        // Vérifier limite
        if (count($data['requests']) > $maxRequests) {
            $data['blocked_until'] = $now + 3600; // Bloquer 1 heure
            file_put_contents($cacheFile, json_encode($data));
            
            http_response_code(429);
            header('Retry-After: 3600');
            echo "Limite de requêtes dépassée. Accès bloqué pendant 1 heure.";
            exit;
        }
        
        // Sauvegarder état
        file_put_contents($cacheFile, json_encode($data));
        
        return true;
    }
    
    /**
     * Middleware de sécurité headers
     */
    public function securityHeadersMiddleware(): bool {
        // Headers de sécurité de base
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // CSP de base pour applications internes
        if (!headers_sent()) {
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
        }
        
        return true;
    }
    
    /**
     * Middleware CORS pour API
     */
    public function corsMiddleware(): bool {
        // Permettre requêtes AJAX depuis même domaine
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $allowed_origins = [
                'http://localhost',
                'https://localhost',
                // Ajouter domaines autorisés
            ];
            
            if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            }
        }
        
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Gérer requêtes OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Max-Age: 86400'); // 24h
            http_response_code(200);
            exit;
        }
        
        return true;
    }
    
    /**
     * Middleware de validation CSRF
     */
    public function csrfMiddleware(): bool {
        // Générer token CSRF si pas existant
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // Vérifier token sur POST/PUT/DELETE
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
            $submitted_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
                http_response_code(403);
                echo "Token CSRF invalide";
                exit;
            }
        }
        
        return true;
    }
    
    /**
     * Exécuter une pile de middlewares
     */
    public function executeStack(array $middleware_names): bool {
        foreach ($middleware_names as $name) {
            if (!$this->executeMiddleware($name)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Middleware pour pages publiques (login, etc.)
     */
    public function publicMiddleware(): bool {
        return $this->executeStack(['session', 'rate_limit', 'security_headers']);
    }
    
    /**
     * Middleware pour pages authentifiées
     */
    public function authPageMiddleware(): bool {
        return $this->executeStack(['session', 'auth', 'rate_limit', 'security_headers']);
    }
    
    /**
     * Middleware pour pages admin
     */
    public function adminPageMiddleware(): bool {
        return $this->executeStack(['session', 'admin', 'rate_limit', 'security_headers']);
    }
    
    /**
     * Middleware pour API
     */
    public function apiMiddleware(): bool {
        return $this->executeStack(['session', 'auth', 'cors', 'rate_limit']);
    }
}
?>
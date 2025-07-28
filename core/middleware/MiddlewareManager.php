<?php
/**
 * Titre: Gestionnaire de middlewares centralisé
 * Chemin: /core/middleware/MiddlewareManager.php
 * Version: 0.5 beta + build auto
 */

class MiddlewareManager 
{
    private static $instance = null;
    private $middlewares = [];
    private $globalMiddlewares = [];
    
    private function __construct() {
        $this->registerDefaultMiddlewares();
    }
    
    public static function getInstance(): MiddlewareManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Enregistrer les middlewares par défaut
     */
    private function registerDefaultMiddlewares(): void {
        // Middlewares globaux (appliqués partout)
        $this->addGlobalMiddleware('security', [$this, 'securityMiddleware']);
        $this->addGlobalMiddleware('session', [$this, 'sessionMiddleware']);
        
        // Middlewares spécifiques
        $this->addMiddleware('auth', [$this, 'authMiddleware']);
        $this->addMiddleware('admin', [$this, 'adminMiddleware']);
        $this->addMiddleware('rate_limit', [$this, 'rateLimitMiddleware']);
        $this->addMiddleware('csrf', [$this, 'csrfMiddleware']);
    }
    
    /**
     * Ajouter un middleware global
     */
    public function addGlobalMiddleware(string $name, callable $handler): void {
        $this->globalMiddlewares[$name] = $handler;
    }
    
    /**
     * Ajouter un middleware spécifique
     */
    public function addMiddleware(string $name, callable $handler): void {
        $this->middlewares[$name] = $handler;
    }
    
    /**
     * Exécuter les middlewares globaux
     */
    public function runGlobalMiddlewares(): bool {
        foreach ($this->globalMiddlewares as $name => $handler) {
            $result = call_user_func($handler);
            if ($result === false) {
                error_log("Middleware global '{$name}' a échoué");
                return false;
            }
        }
        return true;
    }
    
    /**
     * Exécuter des middlewares spécifiques
     */
    public function run(array $middlewareNames): bool {
        // D'abord les middlewares globaux
        if (!$this->runGlobalMiddlewares()) {
            return false;
        }
        
        // Ensuite les middlewares spécifiques
        foreach ($middlewareNames as $name) {
            if (isset($this->middlewares[$name])) {
                $result = call_user_func($this->middlewares[$name]);
                if ($result === false) {
                    error_log("Middleware '{$name}' a échoué");
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Middleware de sécurité global
     */
    private function securityMiddleware(): bool {
        // Headers de sécurité
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
        
        // Validation basique des entrées
        $this->sanitizeGlobalInputs();
        
        return true;
    }
    
    /**
     * Middleware de session global
     */
    private function sessionMiddleware(): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Configuration session sécurisée
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            
            session_start();
            
            // Régénération périodique de l'ID de session
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
        
        return true;
    }
    
    /**
     * Middleware d'authentification
     */
    private function authMiddleware(): bool {
        // Vérifier si l'utilisateur est authentifié
        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            // Tentative d'authentification automatique si AuthManager disponible
            if (class_exists('AuthManager')) {
                $authManager = AuthManager::getInstance();
                if (!$authManager->isAuthenticated()) {
                    $this->redirectToLogin();
                    return false;
                }
            } else {
                $this->redirectToLogin();
                return false;
            }
        }
        
        // Vérifier la validité de la session
        if (isset($_SESSION['expires_at']) && $_SESSION['expires_at'] < time()) {
            session_destroy();
            $this->redirectToLogin();
            return false;
        }
        
        // Mise à jour du timestamp de dernière activité
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Middleware admin (nécessite auth + rôle admin/dev)
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
        
        $now = time();
        $windowSize = 300; // 5 minutes
        $maxRequests = 100; // 100 requêtes par fenêtre
        
        // Lire les données existantes
        $data = ['requests' => [], 'blocked_until' => 0];
        if (file_exists($cacheFile)) {
            $fileData = json_decode(file_get_contents($cacheFile), true);
            if ($fileData) {
                $data = $fileData;
            }
        }
        
        // Vérifier si l'IP est bloquée
        if ($data['blocked_until'] > $now) {
            http_response_code(429);
            echo "Trop de requêtes - Réessayez plus tard";
            exit;
        }
        
        // Nettoyer les anciennes requêtes
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $windowSize) {
            return $timestamp > ($now - $windowSize);
        });
        
        // Ajouter la requête actuelle
        $data['requests'][] = $now;
        
        // Vérifier si la limite est dépassée
        if (count($data['requests']) > $maxRequests) {
            $data['blocked_until'] = $now + 900; // Bloquer 15 minutes
            file_put_contents($cacheFile, json_encode($data));
            
            http_response_code(429);
            echo "Limite de requêtes dépassée - IP bloquée temporairement";
            exit;
        }
        
        // Sauvegarder les données
        file_put_contents($cacheFile, json_encode($data));
        
        return true;
    }
    
    /**
     * Middleware CSRF
     */
    private function csrfMiddleware(): bool {
        // Générer le token CSRF s'il n'existe pas
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // Vérifier le token pour les requêtes POST/PUT/DELETE
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                http_response_code(403);
                echo "Token CSRF invalide";
                exit;
            }
        }
        
        return true;
    }
    
    /**
     * Redirection vers la page de connexion
     */
    private function redirectToLogin(): void {
        $loginUrl = '/auth/login.php';
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Préserver l'URL de destination
        if ($currentUrl !== '/auth/login.php') {
            $loginUrl .= '?redirect=' . urlencode($currentUrl);
        }
        
        if (!headers_sent()) {
            header("Location: {$loginUrl}");
        } else {
            echo "<script>window.location.href='{$loginUrl}';</script>";
        }
        exit;
    }
    
    /**
     * Nettoyage des entrées globales
     */
    private function sanitizeGlobalInputs(): void {
        // Nettoyage basique des superglobales
        array_walk_recursive($_GET, [$this, 'sanitizeInput']);
        array_walk_recursive($_POST, [$this, 'sanitizeInput']);
        array_walk_recursive($_COOKIE, [$this, 'sanitizeInput']);
    }
    
    /**
     * Nettoyage d'une valeur d'entrée
     */
    private function sanitizeInput(&$value, $key): void {
        if (is_string($value)) {
            // Supprimer les caractères de contrôle
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
            // Trim
            $value = trim($value);
        }
    }
    
    /**
     * Obtenir le token CSRF courant
     */
    public function getCsrfToken(): string {
        return $_SESSION['csrf_token'] ?? '';
    }
    
    /**
     * Générer un champ hidden CSRF pour les formulaires
     */
    public function getCsrfField(): string {
        $token = $this->getCsrfToken();
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }
    
    /**
     * Vérifier manuellement un token CSRF
     */
    public function verifyCsrfToken(string $token): bool {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
<?php
/**
 * Titre: Gestionnaire de routes modulaire simple
 * Chemin: /core/routing/RouteManager.php
 * Version: 0.5 beta + build auto
 */

class RouteManager 
{
    private static $instance = null;
    private $routes = [];
    private $currentModule = '';
    private $basePath = '';
    
    private function __construct() {
        $this->basePath = $_SERVER['REQUEST_URI'] ?? '/';
        $this->registerDefaultRoutes();
    }
    
    public static function getInstance(): RouteManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Enregistrement des routes par défaut (compatibilité avec l'existant)
     */
    private function registerDefaultRoutes() {
        // Routes modules existants
        $this->addRoute('/', 'home', 'index.php');
        $this->addRoute('/admin', 'admin', '/public/admin/index.php');
        $this->addRoute('/admin/*', 'admin', '/public/admin/');
        $this->addRoute('/user', 'user', '/public/user/index.php');
        $this->addRoute('/user/*', 'user', '/public/user/');
        $this->addRoute('/auth', 'auth', '/public/auth/login.php');
        $this->addRoute('/auth/*', 'auth', '/public/auth/');
        
        // Module principal calculateur
        $this->addRoute('/calculateur', 'port', '/public/port/index.php');
        $this->addRoute('/port', 'port', '/public/port/index.php');
        $this->addRoute('/port/*', 'port', '/public/port/');
        
        // Nouveaux modules
        $this->addRoute('/materiel', 'materiel', '/public/materiel/index.php');
        $this->addRoute('/materiel/*', 'materiel', '/public/materiel/');
        $this->addRoute('/qualite', 'qualite', '/public/qualite/index.php');
        $this->addRoute('/qualite/*', 'qualite', '/public/qualite/');
        $this->addRoute('/epi', 'epi', '/public/epi/index.php');
        $this->addRoute('/epi/*', 'epi', '/public/epi/');
        $this->addRoute('/adr', 'adr', '/public/adr/index.php');
        $this->addRoute('/adr/*', 'adr', '/public/adr/');
    }
    
    /**
     * Ajout d'une route
     */
    public function addRoute(string $pattern, string $module, string $target): void {
        $this->routes[] = [
            'pattern' => $pattern,
            'module' => $module,
            'target' => $target,
            'regex' => $this->convertPatternToRegex($pattern)
        ];
    }
    
    /**
     * Conversion d'un pattern en regex
     */
    private function convertPatternToRegex(string $pattern): string {
        // Échapper les caractères spéciaux sauf *
        $pattern = preg_quote($pattern, '/');
        // Remplacer * par .*
        $pattern = str_replace('\*', '.*', $pattern);
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Résolution de la route courante
     */
    public function resolve(): array {
        $path = parse_url($this->basePath, PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';
        
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path)) {
                $this->currentModule = $route['module'];
                return [
                    'module' => $route['module'],
                    'target' => $route['target'],
                    'path' => $path,
                    'matched_pattern' => $route['pattern']
                ];
            }
        }
        
        // Route par défaut si aucune correspondance
        return [
            'module' => 'home',
            'target' => '/public/index.php',
            'path' => $path,
            'matched_pattern' => 'default'
        ];
    }
    
    /**
     * Obtenir le module courant
     */
    public function getCurrentModule(): string {
        if (empty($this->currentModule)) {
            $route = $this->resolve();
            $this->currentModule = $route['module'];
        }
        return $this->currentModule;
    }
    
    /**
     * Définir manuellement le module courant (pour compatibilité)
     */
    public function setCurrentModule(string $module): void {
        $this->currentModule = $module;
    }
    
    /**
     * Vérifier si un module est accessible par l'utilisateur
     */
    public function canAccessModule(string $module, array $userRoles = []): bool {
        // Modules publics (sans authentification)
        $publicModules = ['auth'];
        if (in_array($module, $publicModules)) {
            return true;
        }
        
        // Si pas de rôles définis, accès refusé
        if (empty($userRoles)) {
            return false;
        }
        
        // Définition des accès par module (respecte config/roles.php existant)
        $moduleAccess = [
            'admin' => ['dev', 'admin'],
            'user' => ['dev', 'admin', 'logistique', 'qhse', 'labo', 'user'],
            'port' => ['dev', 'admin', 'logistique', 'user'],
            'materiel' => ['dev', 'admin', 'logistique', 'qhse'],
            'qualite' => ['dev', 'admin', 'logistique', 'qhse', 'labo'],
            'epi' => ['dev', 'admin', 'qhse'],
            'adr' => ['dev', 'admin', 'logistique', 'qhse'],
            'home' => ['dev', 'admin', 'logistique', 'qhse', 'labo', 'user']
        ];
        
        if (!isset($moduleAccess[$module])) {
            return false;
        }
        
        return !empty(array_intersect($userRoles, $moduleAccess[$module]));
    }
    
    /**
     * Génération d'URL pour un module
     */
    public function url(string $module, string $path = ''): string {
        $baseUrls = [
            'home' => '/',
            'admin' => '/admin',
            'user' => '/user',
            'auth' => '/auth',
            'port' => '/port',
            'materiel' => '/materiel',
            'qualite' => '/qualite',
            'epi' => '/epi',
            'adr' => '/adr'
        ];
        
        $baseUrl = $baseUrls[$module] ?? "/{$module}";
        
        if (!empty($path)) {
            $path = ltrim($path, '/');
            return "{$baseUrl}/{$path}";
        }
        
        return $baseUrl;
    }
    
    /**
     * Redirection vers un module
     */
    public function redirect(string $module, string $path = '', int $code = 302): void {
        $url = $this->url($module, $path);
        header("Location: {$url}", true, $code);
        exit;
    }
    
    /**
     * Obtenir les breadcrumbs automatiquement
     */
    public function getBreadcrumbs(): array {
        $module = $this->getCurrentModule();
        $path = parse_url($this->basePath, PHP_URL_PATH);
        
        $breadcrumbs = [
            ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false]
        ];
        
        // Informations modules
        $moduleInfo = [
            'admin' => ['icon' => '⚙️', 'text' => 'Administration'],
            'user' => ['icon' => '👤', 'text' => 'Utilisateur'],
            'port' => ['icon' => '🚚', 'text' => 'Calculateur Port'],
            'materiel' => ['icon' => '🔧', 'text' => 'Matériel'],
            'qualite' => ['icon' => '🔬', 'text' => 'Qualité'],
            'epi' => ['icon' => '🦺', 'text' => 'EPI'],
            'adr' => ['icon' => '⚠️', 'text' => 'ADR']
        ];
        
        if ($module !== 'home' && isset($moduleInfo[$module])) {
            $info = $moduleInfo[$module];
            $breadcrumbs[] = [
                'icon' => $info['icon'],
                'text' => $info['text'],
                'url' => $this->url($module),
                'active' => false
            ];
        }
        
        // Marquer le dernier comme actif
        if (count($breadcrumbs) > 1) {
            $breadcrumbs[count($breadcrumbs) - 1]['active'] = true;
        } else {
            $breadcrumbs[0]['active'] = true;
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Obtenir la liste des modules disponibles pour un utilisateur
     */
    public function getAvailableModules(array $userRoles = []): array {
        $allModules = [
            'home' => ['name' => 'Accueil', 'icon' => '🏠', 'color' => '#3b82f6'],
            'port' => ['name' => 'Calculateur Port', 'icon' => '🚚', 'color' => '#059669'],
            'materiel' => ['name' => 'Matériel', 'icon' => '🔧', 'color' => '#dc2626'],
            'qualite' => ['name' => 'Qualité', 'icon' => '🔬', 'color' => '#7c3aed'],
            'epi' => ['name' => 'EPI', 'icon' => '🦺', 'color' => '#ea580c'],
            'adr' => ['name' => 'ADR', 'icon' => '⚠️', 'color' => '#d97706'],
            'user' => ['name' => 'Profil', 'icon' => '👤', 'color' => '#6b7280'],
            'admin' => ['name' => 'Administration', 'icon' => '⚙️', 'color' => '#374151']
        ];
        
        $availableModules = [];
        foreach ($allModules as $module => $info) {
            if ($this->canAccessModule($module, $userRoles)) {
                $availableModules[$module] = $info;
            }
        }
        
        return $availableModules;
    }
    
    /**
     * Debug: afficher toutes les routes
     */
    public function getRoutes(): array {
        return $this->routes;
    }
}
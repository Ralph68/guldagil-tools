<?php
/**
 * Titre: Routeur principal du portail
 * Chemin: /core/App.php
 */

class App {
    private $modules = [];
    private $config = [];
    
    public function __construct($config) {
        $this->config = $config;
        $this->loadModules();
    }
    
    public function run() {
        $uri = $this->getRequestUri();
        $module = $this->getModuleFromUri($uri);
        
        if ($module && $this->isModuleActive($module)) {
            $this->loadModule($module, $uri);
        } else {
            $this->showDashboard();
        }
    }
    
    private function getRequestUri() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        return trim($uri, '/');
    }
    
    private function getModuleFromUri($uri) {
        $parts = explode('/', $uri);
        return $parts[0] ?? null;
    }
    
    private function isModuleActive($moduleName) {
        return isset($this->modules[$moduleName]) && 
               $this->modules[$moduleName]['status'] === 'active';
    }
    
    private function loadModules() {
        require_once __DIR__ . '/../config/modules.php';
        $this->modules = $modules ?? [];
    }
    
    private function loadModule($moduleName, $uri) {
        $moduleConfig = $this->modules[$moduleName];
        $modulePath = __DIR__ . "/../features/{$moduleName}/{$moduleConfig['class']}.php";
        
        if (file_exists($modulePath)) {
            require_once $modulePath;
            $className = $moduleConfig['class'];
            $module = new $className($this->config);
            $module->handle($uri);
        } else {
            $this->show404();
        }
    }
    
    private function showDashboard() {
        require_once __DIR__ . '/../templates/layout.php';
    }
    
    private function show404() {
        http_response_code(404);
        echo "Module non trouvé";
    }
}

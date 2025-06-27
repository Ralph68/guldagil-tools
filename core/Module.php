<?php
/**
 * Titre: Classe base pour tous les modules
 * Chemin: /core/Module.php
 */

abstract class Module {
    protected $config;
    protected $db;
    
    public function __construct($config) {
        $this->config = $config;
        $this->db = $config['database'] ?? null;
    }
    
    abstract public function handle($uri);
    
    protected function render($view, $data = []) {
        extract($data);
        $viewPath = $this->getViewPath($view);
        
        if (file_exists($viewPath)) {
            require_once __DIR__ . '/../templates/layout.php';
        } else {
            throw new Exception("Vue non trouvée: $view");
        }
    }
    
    protected function getViewPath($view) {
        $moduleName = $this->getModuleName();
        return __DIR__ . "/../features/{$moduleName}/views/{$view}.php";
    }
    
    protected function getModuleName() {
        return strtolower(str_replace('Module', '', get_class($this)));
    }
    
    protected function getDatabase() {
        return $this->db;
    }
    
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

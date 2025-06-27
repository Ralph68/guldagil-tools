<?php
// === guldagil_new/core/middleware/AuthMiddleware.php ===
/**
 * Middleware authentification pour contrôle d'accès
 * Chemin: /core/middleware/AuthMiddleware.php
 */

class AuthMiddleware {
    private $auth;
    private $exemptPaths = ['/login', '/assets', '/api/public'];

    public function __construct() {
        $this->auth = AuthManager::getInstance();
    }

    /**
     * Vérifier accès avant exécution
     */
    public function handle($path, $module = null) {
        // Chemins exemptés
        foreach ($this->exemptPaths as $exempt) {
            if (strpos($path, $exempt) !== false) {
                return true;
            }
        }

        // Vérifier authentification
        if (!$this->auth->isAuthenticated()) {
            $this->redirectToLogin();
            return false;
        }

        // Vérifier accès module si spécifié
        if ($module && !$this->auth->canAccessModule($module)) {
            $this->accessDenied($module);
            return false;
        }

        return true;
    }

    /**
     * Obtenir modules accessibles pour l'UI
     */
    public function getAccessibleModules() {
        if (!$this->auth->isAuthenticated()) {
            return [];
        }

        $user = $this->auth->getCurrentUser();
        $allModules = [
            'port' => ['name' => 'Calculateur frais', 'status' => 'active'],
            'adr' => ['name' => 'Gestion ADR', 'status' => 'active'],
            'quality' => ['name' => 'Contrôle Qualité', 'status' => 'development'],
            'epi' => ['name' => 'Équipements EPI', 'status' => 'development'],
            'tools' => ['name' => 'Outillages', 'status' => 'development'],
            'admin' => ['name' => 'Administration', 'status' => 'active']
        ];

        $accessible = [];
        foreach ($allModules as $module => $info) {
            if (in_array($module, $user['modules'])) {
                $accessible[$module] = $info;
            } else {
                // Griser les modules non accessibles
                $info['status'] = 'disabled';
                $info['reason'] = 'Accès insuffisant';
                $accessible[$module] = $info;
            }
        }

        return $accessible;
    }

    private function redirectToLogin() {
        header('Location: /auth/login.php');
        exit;
    }

    private function accessDenied($module) {
        http_response_code(403);
        include 'views/errors/403.php';
        exit;
    }
}

// === guldagil_new/public/index.php ===
/**
 * Point d'entrée unique avec authentification
 * Chemin: /public/index.php
 */

require_once '../config/app.php';
require_once '../core/auth/AuthManager.php';
require_once '../core/middleware/AuthMiddleware.php';

$auth = AuthManager::getInstance();
$middleware = new AuthMiddleware();

// Détecter route demandée
$path = $_SERVER['REQUEST_URI'] ?? '/';
$path = strtok($path, '?'); // Enlever query string

// Appliquer middleware
if (!$middleware->handle($path)) {
    exit; // Middleware a géré la redirection
}

// Router simple basé sur path
switch (true) {
    case $path === '/' || $path === '/index.php':
        include 'dashboard.php';
        break;
        
    case strpos($path, '/port') === 0:
        $middleware->handle($path, 'port');
        include '../features/port/views/calculator.php';
        break;
        
    case strpos($path, '/adr') === 0:
        $middleware->handle($path, 'adr');
        include '../features/adr/views/dashboard.php';
        break;
        
    case strpos($path, '/admin') === 0:
        $middleware->handle($path, 'admin');
        include '../features/admin/views/dashboard.php';
        break;
        
    case strpos($path, '/auth') === 0:
        include '../features/auth/views/login.php';
        break;
        
    default:
        http_response_code(404);
        include 'views/errors/404.php';
}
?>

<?php
/**
 * Titre: Gestionnaire centralisé du menu dynamique
 * Chemin: /core/navigation/MenuManager.php
 * Version: 0.5 beta + build auto
 */

class MenuManager
{
    private static $instance = null;
    private $modules = [];
    private $cache = [];
    
    private function __construct() {
        $this->loadModules();
    }
    
    public static function getInstance(): MenuManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Charger la configuration des modules
     */
    private function loadModules(): void {
        $modulesFile = ROOT_PATH . '/config/modules.php';
        if (file_exists($modulesFile)) {
            $this->modules = require $modulesFile;
        }
    }
    
    /**
     * Obtenir les modules pour un rôle (avec cache session)
     */
    public function getModulesForRole(string $role): array {
        $cacheKey = "menu_modules_{$role}";
        
        // Cache session pour performance
        if (isset($_SESSION[$cacheKey])) {
            return $_SESSION[$cacheKey];
        }
        
        $accessible = [];
        
        foreach ($this->modules as $key => $module) {
            if ($this->canAccessModule($role, $key, $module)) {
                $accessible[$key] = $this->enrichModule($key, $module, $role);
            }
        }
        
        // Trier par priorité
        uasort($accessible, fn($a, $b) => $a['priority'] <=> $b['priority']);
        
        $_SESSION[$cacheKey] = $accessible;
        return $accessible;
    }
    
    /**
     * Vérifier l'accès à un module
     */
    private function canAccessModule(string $role, string $moduleKey, array $module): bool {
        // Modules cachés du menu principal
        if (in_array($moduleKey, ['auth']) || ($module['priority'] ?? 0) > 90) {
            return false;
        }
        
        // Accès universel
        if (in_array('*', $module['roles'] ?? [])) {
            return true;
        }
        
        // Vérification rôle spécifique
        return in_array($role, $module['roles'] ?? []);
    }
    
    /**
     * Enrichir un module avec des données calculées
     */
    private function enrichModule(string $key, array $module, string $role): array {
        $module['key'] = $key;
        $module['url'] = "/{$key}/";
        
        // État d'accès selon statut et rôle
        $module['access_state'] = $this->getAccessState($module, $role);
        
        return $module;
    }
    
    /**
     * Déterminer l'état d'accès du module
     */
    private function getAccessState(array $module, string $role): string {
        $status = $module['status'] ?? 'active';
        
        if ($status === 'active') {
            return 'active';
        }
        
        if ($status === 'development') {
            return in_array($role, ['admin', 'dev']) ? 'dev_access' : 'visible_locked';
        }
        
        return 'inactive';
    }
    
    /**
     * Obtenir un module spécifique
     */
    public function getModule(string $key): ?array {
        return $this->modules[$key] ?? null;
    }
    
    /**
     * Vider le cache menu (utile après changement de rôle)
     */
    public function clearCache(): void {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'menu_modules_') === 0) {
                unset($_SESSION[$key]);
            }
        }
    }
    
    /**
     * Obtenir les statistiques des modules
     */
    public function getStats(): array {
        return [
            'total_modules' => count($this->modules),
            'active_modules' => count(array_filter($this->modules, fn($m) => $m['status'] === 'active')),
            'dev_modules' => count(array_filter($this->modules, fn($m) => $m['status'] === 'development'))
        ];
    }
}
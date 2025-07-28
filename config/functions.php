<?php
/**
 * Titre: Fonctions helper simplifiées pour menu dynamique
 * Chemin: /config/functions.php
 * Version: 0.5 beta + build auto
 */

if (!function_exists('getNavigationModules')) {
    /**
     * Obtenir les modules de navigation - VERSION SIMPLIFIÉE
     */
    function getNavigationModules(string $userRole, array $modules = null): array {
        // Utiliser MenuManager si disponible
        if (class_exists('MenuManager')) {
            return MenuManager::getInstance()->getModulesForRole($userRole);
        }
        
        // Fallback : utiliser modules fournis ou charger config
        if ($modules === null) {
            $modulesFile = ROOT_PATH . '/config/modules.php';
            $modules = file_exists($modulesFile) ? require $modulesFile : [];
        }
        
        $accessible = [];
        
        foreach ($modules as $key => $module) {
            // Skip auth et modules cachés
            if ($key === 'auth' || ($module['priority'] ?? 0) > 90) {
                continue;
            }
            
            // Vérifier accès
            if (in_array('*', $module['roles'] ?? []) || in_array($userRole, $module['roles'] ?? [])) {
                $accessible[$key] = $module;
            }
        }
        
        return $accessible;
    }
}

if (!function_exists('getModuleConfig')) {
    /**
     * Obtenir la config d'un module spécifique
     */
    function getModuleConfig(string $moduleKey): ?array {
        static $modules = null;
        
        if ($modules === null) {
            $modulesFile = ROOT_PATH . '/config/modules.php';
            $modules = file_exists($modulesFile) ? require $modulesFile : [];
        }
        
        return $modules[$moduleKey] ?? null;
    }
}
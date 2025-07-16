<?php
/**
 * Titre: Fonctions helper pour gestion permissions modules
 * Chemin: /config/functions.php
 * Version: 0.5 beta + build auto
 */

if (!function_exists('canAccessModule')) {
    /**
     * Vérifie si un utilisateur peut accéder à un module
     */
    function canAccessModule($module_key, $module_data, $user_role) {
        // Modules toujours accessibles
        if (in_array($module_key, ['home', 'user'])) {
            return true;
        }
        
        // Admin et dev accèdent à tout
        if (in_array($user_role, ['admin', 'dev'])) {
            return true;
        }
        
        // Modules en développement : uniquement dev
        if (($module_data['status'] ?? 'active') === 'development') {
            return $user_role === 'dev';
        }
        
        // Modules spécifiques par rôle
        $role_permissions = [
            'logistique' => ['port', 'adr', 'qualite'],
            'user' => ['port']  // Utilisateur standard n'a accès qu'au calculateur
        ];
        
        return in_array($module_key, $role_permissions[$user_role] ?? []);
    }
}

if (!function_exists('shouldShowModule')) {
    /**
     * Vérifie si un module doit être affiché dans le menu
     */
    function shouldShowModule($module_key, $module_data, $user_role) {
        // Masquer complètement certains modules pour certains rôles
        if ($user_role === 'user' && !in_array($module_key, ['port', 'user'])) {
            return false;
        }
        
        return true;
    }
}
?>

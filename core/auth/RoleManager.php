<?php
/**
 * Classe RoleManager
 * Gère les rôles et permissions utilisateurs pour le portail Guldagil
 * Chemin : /core/auth/RoleManager.php
 */

class RoleManager {
    /**
     * Retourne la liste des rôles disponibles
     * @return array
     */
    public static function getAvailableRoles() {
        return [
            'guest',
            'user',
            'admin',
            'dev',
        ];
    }

    /**
     * Vérifie si un utilisateur a un rôle donné
     * @param array $user
     * @param string $role
     * @return bool
     */
    public static function userHasRole($user, $role) {
        if (!isset($user['role'])) return false;
        return $user['role'] === $role;
    }

    /**
     * Vérifie si un utilisateur a l'un des rôles donnés
     * @param array $user
     * @param array $roles
     * @return bool
     */
    public static function userHasAnyRole($user, $roles) {
        if (!isset($user['role'])) return false;
        return in_array($user['role'], $roles);
    }
}

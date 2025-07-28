<?php
/**
 * Fichier de configuration et de gestion des rôles et permissions du portail.
 * C'est la source de vérité pour tous les droits d'accès.
 */

class RoleManager 
{
    /**
     * Définition des rôles, de leurs capacités et des modules accessibles.
     * Le niveau (level) définit la hiérarchie.
     */
    private static $roles = [
        'dev' => [
            'name' => 'Développeur',
            'description' => 'Accès total à toutes les fonctionnalités et configurations',
            'level' => 100,
            'color' => '#d946ef',
            'icon' => '💻',
            'capabilities' => ['*'], // Joker pour toutes les permissions
            'modules' => ['*'] // Joker pour tous les modules
        ],
        'admin' => [
            'name' => 'Administrateur',
            'description' => 'Gestion complète des utilisateurs et des modules',
            'level' => 90,
            'color' => '#ef4444',
            'icon' => '👑',
            'capabilities' => [
                'manage_users', 'manage_system', 'view_admin', 'edit_config',
                'view_logs', 'manage_shipping', 'manage_adr', 'manage_epi',
                'view_quality', 'view_materiel'
            ],
            'modules' => ['home', 'port', 'adr', 'qualite', 'maintenance', 'admin', 'user']
        ],
        'logistique' => [
            'name' => 'Logistique',
            'description' => 'Gestion des transports et des expéditions',
            'level' => 60,
            'color' => '#f97316',
            'icon' => '🚚',
            'capabilities' => [
                'manage_shipping', 'view_adr', 'view_materiel'
            ],
            'modules' => ['home', 'port', 'adr', 'maintenance']
        ],
        'qhse' => [
            'name' => 'QHSE',
            'description' => 'Qualité, Hygiène, Sécurité, Environnement',
            'level' => 50,
            'color' => '#ea580c',
            'icon' => '🦺',
            'capabilities' => [
                'manage_adr', 'manage_epi', 'view_materiel',
                'quality_control', 'quality_analysis'
            ],
            'modules' => ['home', 'adr', 'qualite', 'maintenance']
        ],
        'labo' => [
            'name' => 'Laboratoire',
            'description' => 'Analyses et contrôles qualité',
            'level' => 40,
            'color' => '#3b82f6',
            'icon' => '🧪',
            'capabilities' => [
                'view_quality', 'quality_control', 'quality_analysis',
                'view_materiel'
            ],
            'modules' => ['home', 'qualite']
        ],
        'user' => [
            'name' => 'Utilisateur',
            'description' => 'Accès standard calculateur',
            'level' => 10,
            'color' => '#374151',
            'icon' => '👤',
            'capabilities' => ['view_shipping'],
            'modules' => ['home', 'port', 'user']
        ]
    ];

    /**
     * Hiérarchie des rôles (héritage des permissions)
     */
    private static $hierarchy = [
        'dev' => ['admin'],
        'admin' => ['logistique', 'qhse', 'labo'],
        'logistique' => ['user'],
        'qhse' => ['user'],
        'labo' => ['user']
    ];

    /**
     * Obtenir tous les rôles définis
     */
    public static function getAllRoles(): array 
    {
        return self::$roles;
    }

    /**
     * Obtenir les informations d'un rôle spécifique
     */
    public static function getRole(string $role): ?array 
    {
        return self::$roles[$role] ?? null;
    }

    /**
     * Vérifier si un rôle peut accéder à un module
     */
    public static function canAccessModule(string $role, string $module): bool 
    {
        $roleData = self::getRole($role);
        if (!$roleData) {
            return false;
        }
        
        if (in_array('*', $roleData['modules'])) {
            return true;
        }

        return in_array($module, $roleData['modules']);
    }

    /**
     * Vérifier si un rôle possède une capacité spécifique
     */
    public static function hasCapability(string $role, string $capability): bool 
    {
        $roleData = self::getRole($role);
        if (!$roleData) {
            return false;
        }

        if (in_array('*', $roleData['capabilities'])) {
            return true;
        }

        if (in_array($capability, $roleData['capabilities'])) {
            return true;
        }

        if (isset(self::$hierarchy[$role])) {
            foreach (self::$hierarchy[$role] as $inheritedRole) {
                if (self::hasCapability($inheritedRole, $capability)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Obtenir tous les modules accessibles pour un rôle
     */
    public static function getAccessibleModules(string $role): array 
    {
        $roleData = self::getRole($role);
        if (!$roleData) {
            return [];
        }

        if (in_array('*', $roleData['modules'])) {
            $all_modules = [];
            foreach(self::$roles as $r) {
                if(isset($r['modules'])) {
                    $all_modules = array_merge($all_modules, $r['modules']);
                }
            }
            return array_keys(array_flip($all_modules));
        }

        $modules = $roleData['modules'];

        if (isset(self::$hierarchy[$role])) {
            foreach (self::$hierarchy[$role] as $inheritedRole) {
                $inheritedModules = self::getAccessibleModules($inheritedRole);
                $modules = array_unique(array_merge($modules, $inheritedModules));
            }
        }

        return $modules;
    }

    /**
     * Comparer le niveau hiérarchique de deux rôles
     */
    public static function isRoleHigher(string $role1, string $role2): bool 
    {
        $level1 = self::$roles[$role1]['level'] ?? 0;
        $level2 = self::$roles[$role2]['level'] ?? 0;
        
        return $level1 > $level2;
    }

    /**
     * Obtenir les rôles qu'un utilisateur peut gérer
     */
    public static function getManageableRoles(string $userRole): array 
    {
        $userLevel = self::$roles[$userRole]['level'] ?? 0;
        $manageableRoles = [];

        foreach (self::$roles as $role => $data) {
            if (($data['level'] ?? 0) < $userLevel) {
                $manageableRoles[$role] = $data;
            }
        }

        return $manageableRoles;
    }

    /**
     * Valider l'existence d'un rôle
     */
    public static function isValidRole(string $role): bool 
    {
        return isset(self::$roles[$role]);
    }

    /**
     * Générer un badge HTML pour un rôle
     */
    public static function getRoleBadge(string $role): string 
    {
        $roleData = self::getRole($role);
        if (!$roleData) {
            return '<span class="role-badge role-unknown">Inconnu</span>';
        }

        return sprintf(
            '<span class="role-badge" style="background-color: %s; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">%s %s</span>',
            $roleData['color'] ?? '#374151',
            $roleData['icon'] ?? '👤',
            htmlspecialchars($roleData['name'])
        );
    }
}

// Configuration de la durée des sessions (par défaut : 8 heures)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 8 * 60 * 60); // 8 heures en secondes
}

ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
session_set_cookie_params(SESSION_TIMEOUT);

/**
 * FONCTIONS UTILITAIRES GLOBALES POUR COMPATIBILITÉ
 * Ces fonctions agissent comme des wrappers pour la classe RoleManager
 * afin d'assurer la compatibilité avec l'ancien code.
 */

if (!function_exists('canAccessModule')) {
    function canAccessModule(string $module_key, array $module_data, string $user_role): bool 
    {
        return RoleManager::canAccessModule($user_role, $module_key);
    }
}

if (!function_exists('getNavigationModules')) {
    /**
     * Récupère les modules de navigation en fonction du rôle de l'utilisateur
     * @param string $userRole Le rôle de l'utilisateur
     * @param array $modules Tableau des modules disponibles
     * @return array Modules filtrés selon les droits
     */
    function getNavigationModules($userRole, $modules) {
        $filteredModules = [];
        
        foreach ($modules as $id => $module) {
            // Sauter le module home qui est toujours accessible via le logo
            if ($id === 'home') continue;
            
            // Vérifier si le module est restreint à certains rôles
            if (isset($module['restricted']) && is_array($module['restricted'])) {
                if (!in_array($userRole, $module['restricted'])) {
                    continue; // Sauter ce module si l'utilisateur n'a pas le rôle requis
                }
            }
            
            // Ajouter le module au tableau filtré
            $filteredModules[$id] = $module;
        }
        
        return $filteredModules;
    }
}

if (!function_exists('hasAdminPermission')) {
    function hasAdminPermission(string $user_role, string $permission): bool 
    {
        return RoleManager::hasCapability($user_role, $permission);
    }
}

if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass(string $role): string 
    {
        return RoleManager::getRole($role) ? 'role-' . $role : 'role-user';
    }
}

if (!function_exists('canManageUser')) {
    function canManageUser(string $currentUserRole, string $targetUserRole): bool 
    {
        return RoleManager::isRoleHigher($currentUserRole, $targetUserRole);
    }
}

if (!function_exists('getRoleColor')) {
    function getRoleColor(string $role): string 
    {
        $roleData = RoleManager::getRole($role);
        return $roleData['color'] ?? '#374151';
    }
}

if (!function_exists('getRoleIcon')) {
    function getRoleIcon(string $role): string 
    {
        $roleData = RoleManager::getRole($role);
        return $roleData['icon'] ?? '👤';
    }
}

/**
 * Note: shouldShowModule() est définie dans /config/functions.php
 * pour éviter les conflits de redéclaration
 */

/**
 * Obtenir les modules pour la navigation
 */
if (!function_exists('getNavigationModules')) {
    function getNavigationModules(string $user_role, array $all_modules): array 
    {
        $accessibleModules = RoleManager::getAccessibleModules($user_role);
        $navigation = [];

        foreach ($all_modules as $key => $module) {
            if ($key !== 'home' && in_array($key, $accessibleModules)) {
                $navigation[$key] = $module;
            }
        }

        return $navigation;
    }
}

/**
 * Vérifier une permission d'administration
 */
if (!function_exists('hasAdminPermission')) {
    function hasAdminPermission(string $user_role, string $permission): bool 
    {
        return RoleManager::hasCapability($user_role, $permission);
    }
}

/**
 * Obtenir la classe CSS pour un badge de rôle
 */
if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass(string $role): string 
    {
        $roleData = RoleManager::getRole($role);
        return $roleData ? 'role-' . $role : 'role-user';
    }
}

/**
 * Vérifier si l'utilisateur actuel peut gérer un autre utilisateur
 */
if (!function_exists('canManageUser')) {
    function canManageUser(string $currentUserRole, string $targetUserRole): bool 
    {
        return RoleManager::isRoleHigher($currentUserRole, $targetUserRole);
    }
}

/**
 * Obtenir la couleur d'un rôle
 */
if (!function_exists('getRoleColor')) {
    function getRoleColor(string $role): string 
    {
        $roleData = RoleManager::getRole($role);
        return $roleData ? $roleData['color'] : '#374151';
    }
}

/**
 * Obtenir l'icône d'un rôle
 */
if (!function_exists('getRoleIcon')) {
    function getRoleIcon(string $role): string 
    {
        $roleData = RoleManager::getRole($role);
        return $roleData ? $roleData['icon'] : '👤';
    }
}

/**
 * Validation finale du fichier
 */
if (!class_exists('RoleManager')) {
    throw new Error('Erreur critique: La classe RoleManager n\'a pas été définie correctement');
}

// Test de base pour vérifier que tout fonctionne
try {
    $testRoles = RoleManager::getAllRoles();
    if (empty($testRoles)) {
        throw new Error('Aucun rôle défini');
    }
} catch (Error $e) {
    error_log('Erreur dans roles.php: ' . $e->getMessage());
    throw $e;
}

?>

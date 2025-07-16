<?php
/**
 * Titre: Système de gestion des rôles centralisé
 * Chemin: /config/roles.php
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    exit('Accès direct interdit');
}

/**
 * DÉFINITION DES RÔLES ET PERMISSIONS
 * Système centralisé pour la gestion des accès
 */
class RoleManager 
{
    // Définition complète des rôles
    private static $roles = [
    'dev' => [
        'name' => 'Développeur',
        'description' => 'Accès absolu total',
        'level' => 100,
        'color' => '#7c3aed',
        'icon' => '💻',
        'capabilities' => [
            'access_dev', 'view_debug', 'manage_system',
            'view_logs', 'edit_modules', 'manage_users',
            'edit_config', 'view_admin'
        ],
        'modules' => ['port', 'adr', 'epi', 'qualite', 'outillage', 'user', 'admin', 'dev']
    ],
    'admin' => [
        'name' => 'Administrateur',
        'description' => 'Accès complet sauf développement',
        'level' => 95,
        'color' => '#dc2626',
        'icon' => '👑',
        'capabilities' => [
            'manage_users', 'manage_system', 'view_admin',
            'edit_config', 'view_logs'
        ],
        'modules' => ['port', 'adr', 'epi', 'qualite', 'outillage', 'user', 'admin']
    ],
        'logistique' => [
            'name' => 'Logistique',
            'description' => 'Gestion transport et qualité',
            'level' => 60,
            'color' => '#059669',
            'icon' => '🚛',
            'capabilities' => [
                'manage_shipping', 'view_quality', 'manage_adr',
                'view_outillage'
            ],
            'modules' => ['port', 'qualite', 'adr', 'outillage', 'user']
        ],
        'qhse' => [
            'name' => 'QHSE',
            'description' => 'Qualité, Hygiène, Sécurité, Environnement',
            'level' => 50,
            'color' => '#ea580c',
            'icon' => '🦺',
            'capabilities' => [
                'manage_adr', 'manage_epi', 'view_outillage',
                'quality_control'
            ],
            'modules' => ['adr', 'epi', 'outillage', 'user']
        ],
        'labo' => [
            'name' => 'Laboratoire',
            'description' => 'Analyses et contrôles qualité',
            'level' => 40,
            'color' => '#3b82f6',
            'icon' => '🧪',
            'capabilities' => [
                'view_shipping', 'manage_adr', 'manage_epi',
                'quality_analysis'
            ],
            'modules' => ['port', 'adr', 'epi', 'user']
        ],
        'user' => [
            'name' => 'Utilisateur',
            'description' => 'Accès utilisateur standard',
            'level' => 10,
            'color' => '#6b7280',
            'icon' => '👤',
            'capabilities' => [
                'view_shipping', 'view_epi'
            ],
            'modules' => ['port', 'epi', 'user']
        ]
    ];

    // Hiérarchie des rôles (rôle supérieur hérite des permissions inférieures)
    private static $hierarchy = [
        'dev' => ['admin', 'logistique', 'qhse', 'labo', 'user'],
        'admin' => ['logistique', 'qhse', 'labo', 'user'],
        'logistique' => ['user'],
        'qhse' => ['user'],
        'labo' => ['user'],
        'user' => []
    ];

    /**
     * Obtenir tous les rôles disponibles
     */
    public static function getAllRoles(): array 
    {
        return self::$roles;
    }

    /**
     * Obtenir les informations d'un rôle
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
        if (!$roleData) return false;

        return in_array($module, $roleData['modules']);
    }

    /**
     * Vérifier si un rôle a une capacité spécifique
     */
    public static function hasCapability(string $role, string $capability): bool 
    {
        $roleData = self::getRole($role);
        if (!$roleData) return false;

        // Vérifier les capacités directes
        if (in_array($capability, $roleData['capabilities'])) {
            return true;
        }

        // Vérifier l'héritage hiérarchique
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
     * Obtenir les modules accessibles pour un rôle
     */
    public static function getAccessibleModules(string $role): array 
    {
        $roleData = self::getRole($role);
        if (!$roleData) return [];

        $modules = $roleData['modules'];

        // Ajouter les modules des rôles hérités
        if (isset(self::$hierarchy[$role])) {
            foreach (self::$hierarchy[$role] as $inheritedRole) {
                $inheritedModules = self::getAccessibleModules($inheritedRole);
                $modules = array_unique(array_merge($modules, $inheritedModules));
            }
        }

        return $modules;
    }

    /**
     * Vérifier si un rôle est supérieur à un autre
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
            if ($data['level'] < $userLevel) {
                $manageableRoles[$role] = $data;
            }
        }

        return $manageableRoles;
    }

    /**
     * Valider si un rôle existe
     */
    public static function isValidRole(string $role): bool 
    {
        return isset(self::$roles[$role]);
    }

    /**
     * Obtenir le badge HTML pour un rôle
     */
    public static function getRoleBadge(string $role): string 
    {
        $roleData = self::getRole($role);
        if (!$roleData) return '';

        return sprintf(
            '<span class="role-badge" style="background-color: %s; color: white;">%s %s</span>',
            $roleData['color'],
            $roleData['icon'],
            $roleData['name']
        );
    }

    /**
     * Configuration pour l'interface admin
     */
    public static function getAdminConfig(): array 
    {
        return [
            'roles' => self::$roles,
            'hierarchy' => self::$hierarchy,
            'permissions' => [
                'manage_users' => 'Gestion des utilisateurs',
                'manage_system' => 'Configuration système',
                'view_admin' => 'Accès administration',
                'edit_config' => 'Modification configuration',
                'view_logs' => 'Consultation des logs',
                'access_dev' => 'Outils développeur',
                'manage_shipping' => 'Gestion transport',
                'view_quality' => 'Consultation qualité',
                'manage_adr' => 'Gestion ADR',
                'view_outillage' => 'Consultation outillage',
                'manage_epi' => 'Gestion EPI',
                'quality_control' => 'Contrôle qualité',
                'view_shipping' => 'Consultation transport',
                'quality_analysis' => 'Analyses qualité',
                'view_epi' => 'Consultation EPI'
            ]
        ];
    }
}

/**
 * FONCTIONS UTILITAIRES GLOBALES
 */

/**
 * Vérifier l'accès à un module (compatible avec le code existant)
 */
function canAccessModule(string $module_key, array $module_data, string $user_role): bool 
{
    return RoleManager::canAccessModule($user_role, $module_key);
}

/**
 * Vérifier si un module doit être affiché
 */
function shouldShowModule(string $module_key, array $module_data, string $user_role): bool 
{
    return RoleManager::canAccessModule($user_role, $module_key);
}

/**
 * Obtenir les modules pour la navigation
 */
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

/**
 * Vérifier une permission admin
 */
function hasAdminPermission(string $user_role, string $permission): bool 
{
    return RoleManager::hasCapability($user_role, $permission);
}

/**
 * Classe CSS pour badge de rôle (compatibilité header existant)
 */
function getRoleBadgeClass(string $role): string 
{
    $roleData = RoleManager::getRole($role);
    return $roleData ? 'role-' . $role : 'role-user';
}

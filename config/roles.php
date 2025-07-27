<?php
/**
 * Fichier de configuration des rôles et permissions du portail.
 * C'est la source de vérité pour tous les droits d'accès.
 */

return [
    // Définition des rôles et de leurs permissions
    'roles' => [
        'user' => [
            'name' => 'Utilisateur',
            'permissions' => [
                'module_port_access',
                'module_user_access',
            ]
        ],
        'logistique' => [
            'name' => 'Logistique',
            'permissions' => [
                'module_port_access',
                'module_adr_access',
                'module_user_access',
            ]
        ],
        'qualite' => [
            'name' => 'Qualité',
            'permissions' => [
                'module_qualite_access',
                'module_user_access',
            ]
        ],
        'maintenance' => [
            'name' => 'Maintenance',
            'permissions' => [
                'module_maintenance_access',
                'module_user_access',
            ]
        ],
        'admin' => [
            'name' => 'Administrateur',
            'permissions' => ['*'] // Le joker '*' donne accès à tout
        ],
        'dev' => [
            'name' => 'Développeur',
            'permissions' => ['*'] // Le joker '*' donne accès à tout
        ]
    ],

    // Association d'une permission à chaque module
    'modules_permissions' => [
        'home'        => null, // null = accessible à tout utilisateur connecté
        'port'        => 'module_port_access',
        'calculateur' => 'module_port_access', // Alias pour 'port'
        'adr'         => 'module_adr_access',
        'qualite'     => 'module_qualite_access',
        'maintenance' => 'module_maintenance_access',
        'user'        => 'module_user_access',
        'profile'     => 'module_user_access', // Alias pour 'user'
        'admin'       => 'module_admin_access'
    ]
];
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
            'modules' => ['home', 'adr', 'epi', 'materiel']
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
            'modules' => ['home', 'qualite', 'materiel']
        ],
        'user' => [
            'name' => 'Utilisateur',
            'description' => 'Accès standard calculateur',
            'level' => 10,
            'color' => '#374151',
            'icon' => '👤',
            'capabilities' => ['view_shipping'],
            'modules' => ['home', 'port', 'materiel']
        ]
    ];

    /**
     * Hiérarchie des rôles (héritage des permissions)
     */
    private static $hierarchy = [
        'dev' => ['admin', 'logistique', 'qhse', 'labo', 'user'],
        'admin' => ['logistique', 'qhse', 'labo', 'user'],
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
     * Obtenir tous les modules accessibles pour un rôle
     */
    public static function getAccessibleModules(string $role): array 
    {
        $roleData = self::getRole($role);
        if (!$roleData) {
            return [];
        }

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
            if ($data['level'] < $userLevel) {
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
            $roleData['color'],
            $roleData['icon'],
            htmlspecialchars($roleData['name'])
        );
    }

    /**
     * Configuration complète pour l'interface d'administration
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
                'view_materiel' => 'Consultation materiel',
                'manage_epi' => 'Gestion EPI',
                'quality_control' => 'Contrôle qualité',
                'view_shipping' => 'Consultation transport',
                'quality_analysis' => 'Analyses qualité',
                'view_epi' => 'Consultation EPI'
            ]
        ];
    }

    /**
     * Obtenir les statistiques des rôles
     */
    public static function getRoleStats(): array 
    {
        $stats = [
            'total_roles' => count(self::$roles),
            'total_modules' => 0,
            'total_capabilities' => 0,
            'roles_by_level' => []
        ];

        $all_modules = [];
        $all_capabilities = [];

        foreach (self::$roles as $role => $data) {
            $all_modules = array_merge($all_modules, $data['modules']);
            $all_capabilities = array_merge($all_capabilities, $data['capabilities']);
            $stats['roles_by_level'][$data['level']] = $role;
        }

        $stats['total_modules'] = count(array_unique($all_modules));
        $stats['total_capabilities'] = count(array_unique($all_capabilities));

        return $stats;
    }
}

/**
 * FONCTIONS UTILITAIRES GLOBALES POUR COMPATIBILITÉ
 */

/**
 * Vérifier l'accès à un module (wrapper pour compatibilité)
 */
if (!function_exists('canAccessModule')) {
    function canAccessModule(string $module_key, array $module_data, string $user_role): bool 
    {
        return RoleManager::canAccessModule($user_role, $module_key);
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

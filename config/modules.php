<?php
/**
 * Titre: Configuration unifiée des modules du portail
 * Chemin: /config/modules.php
 * Version: 0.5 beta + build auto
 */

/**
 * MODULES DU PORTAIL - SOURCE DE VÉRITÉ UNIQUE
 * Cette configuration remplace tous les $all_modules dispersés
 */
return [
    'port' => [
        'name' => 'Calculateur Port',
        'description' => 'Calcul intelligent des frais de transport',
        'icon' => '📦',
        'color' => '#0ea5e9',
        'status' => 'active',
        'category' => 'Logistique',
        'priority' => 1,
        'roles' => ['*'], // Tous les rôles
        'routes' => ['port', 'calculateur'],
        'assets' => [
            'css' => ['port.css'],
            'js' => ['port.js', 'calcul.js']
        ]
    ],
    
    'adr' => [
        'name' => 'Module ADR',
        'description' => 'Gestion des marchandises dangereuses',
        'icon' => '⚠️',
        'color' => '#dc2626',
        'status' => 'active',
        'category' => 'Sécurité',
        'priority' => 2,
        'roles' => ['admin', 'dev', 'logistique'], // Restriction par rôles
        'routes' => ['adr', 'dangereuses'],
        'assets' => [
            'css' => ['adr.css', 'search.css'],
            'js' => ['adr.js', 'search.js']
        ]
    ],
    
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Suivi et contrôle qualité',
        'icon' => '🔬',
        'color' => '#059669',
        'status' => 'development',
        'category' => 'Qualité',
        'priority' => 3,
        'roles' => ['admin', 'dev', 'logistique', 'qhse'],
        'routes' => ['qualite', 'controle'],
        'assets' => [
            'css' => ['qualite.css'],
            'js' => ['qualite.js']
        ]
    ],
    
    'materiel' => [
        'name' => 'Gestion Matériel',
        'description' => 'Inventaire et gestion du matériel',
        'icon' => '🔧',
        'color' => '#6b7280',
        'status' => 'development',
        'category' => 'Maintenance',
        'priority' => 4,
        'roles' => ['admin', 'dev', 'logistique'],
        'routes' => ['materiel', 'outillage'],
        'assets' => [
            'css' => ['materiel.css'],
            'js' => ['materiel.js']
        ]
    ],
    
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des EPI et équipements',
        'icon' => '🦺',
        'color' => '#f59e0b',
        'status' => 'development',
        'category' => 'Sécurité',
        'priority' => 5,
        'roles' => ['admin', 'dev', 'qhse'],
        'routes' => ['epi', 'equipements'],
        'assets' => [
            'css' => ['epi.css'],
            'js' => ['epi.js']
        ]
    ],
    
    'user' => [
        'name' => 'Mon Espace',
        'description' => 'Profil et paramètres personnels',
        'icon' => '👤',
        'color' => '#9b59b6',
        'status' => 'active',
        'category' => 'Personnel',
        'priority' => 6,
        'roles' => ['*'], // Accessible à tous
        'routes' => ['user', 'profile'],
        'assets' => [
            'css' => ['user.css', 'profile.css'],
            'js' => ['user.js', 'profile.js']
        ]
    ],
    
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et gestion système',
        'icon' => '⚙️',
        'color' => '#2c3e50',
        'status' => 'active',
        'category' => 'Système',
        'priority' => 7,
        'roles' => ['admin', 'dev'], // Admin uniquement
        'routes' => ['admin', 'administration'],
        'assets' => [
            'css' => ['admin.css'],
            'js' => ['admin.js']
        ]
    ],
    
    'auth' => [
        'name' => 'Authentification',
        'description' => 'Système de connexion',
        'icon' => '🔐',
        'color' => '#374151',
        'status' => 'active',
        'category' => 'Système',
        'priority' => 99, // Hidden from main menu
        'roles' => ['*'],
        'routes' => ['auth', 'login'],
        'assets' => [
            'css' => ['login.css'],
            'js' => ['login.js']
        ]
    ]
];
<?php
/**
 * Titre: Configuration des modules - MISE À JOUR RÔLES
 * Chemin: /config/modules.php
 * Version: 0.5 beta + build auto
 */

$modules = [
    'port' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'class' => 'PortModule',
        'status' => 'active', // ACTIVE - Accessible par user, logistique, admin, dev
        'icon' => '📦',
        'color' => '#3498db',
        'routes' => ['port', 'calculateur', 'frais'],
        'assets' => [
            'css' => ['port.css'],
            'js' => ['port.js']
        ]
    ],
    
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses',
        'class' => 'ADRModule',
        'status' => 'active', // ACTIVE - Accessible par logistique, admin, dev (pas user)
        'icon' => '⚠️',
        'color' => '#e74c3c',
        'routes' => ['adr', 'dangereuses'],
        'access_roles' => ['logistique', 'admin', 'dev'], // Restriction par rôles
        'assets' => [
            'css' => ['adr.css'],
            'js' => ['adr.js']
        ]
    ],
    
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection',
        'class' => 'EPIModule',
        'status' => 'development',
        'icon' => '🦺',
        'color' => '#f39c12',
        'routes' => ['epi', 'equipements'],
        'assets' => [
            'css' => ['epi.css'],
            'js' => ['epi.js']
        ]
    ],
    
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion des outillages industriels',
        'class' => 'OutillagesModule',
        'status' => 'development',
        'icon' => '🔧',
        'color' => '#95a5a6',
        'routes' => ['outillages', 'outils'],
        'assets' => [
            'css' => ['outillages.css'],
            'js' => ['outillages.js']
        ]
    ],
    
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Suivi qualité des marchandises',
        'class' => 'QualiteModule',
        'status' => 'development',
        'icon' => '✅',
        'color' => '#2ecc71',
        'routes' => ['qualite', 'controle-qualite'],
        'assets' => [
            'css' => ['qualite.css'],
            'js' => ['qualite.js']
        ]
    ],
    
    'admin' => [
        'name' => 'Administration',
        'description' => 'Gestion et configuration du portail',
        'class' => 'AdminModule',
        'status' => 'active',
        'icon' => '⚙️',
        'color' => '#9b59b6',
        'routes' => ['admin', 'administration'],
        'access_roles' => ['admin', 'dev'], // Restriction admin + dev uniquement
        'auth_required' => true
    ]
];

/**
 * DÉFINITION DES ACCÈS PAR RÔLE - MISE À JOUR COMPLÈTE
 * Cette fonction détermine quels modules sont accessibles selon le rôle utilisateur
 */
function getModuleAccessByRole($user_role, $modules) {
    $accessible_modules = [];
    
    foreach ($modules as $module_key => $module_data) {
        $has_access = false;
        $access_reason = '';
        
        switch ($user_role) {
            case 'dev':
                // DEV : Accès total sans restriction (y compris admin)
                $has_access = true;
                $access_reason = 'Développeur - Accès complet';
                break;
                
            case 'admin':
                // ADMIN : Accès à tous modules (active et beta) + module admin
                if ($module_key === 'admin' || in_array($module_data['status'], ['active', 'beta'])) {
                    $has_access = true;
                    $access_reason = $module_key === 'admin' ? 'Admin - Module administration' : 'Admin - Module ' . $module_data['status'];
                }
                break;
                
            case 'logistique':
                // LOGISTIQUE : Accès à port (beta) + voir adr, epi, outillages, qualité (dev mais pas d'accès)
                if ($module_key === 'port' && $module_data['status'] === 'beta') {
                    $has_access = true;
                    $access_reason = 'Logistique - Module en bêta accessible';
                } else if (in_array($module_key, ['adr', 'epi', 'outillages', 'qualite']) && $module_data['status'] === 'development') {
                    $has_access = false; // Visible mais pas accessible
                    $access_reason = 'Logistique - Module en développement (visible mais pas accessible)';
                }
                break;
                
            case 'user':
                // USER : Accès uniquement aux modules actifs (aucun pour le moment)
                $has_access = ($module_data['status'] === 'active' && $module_key !== 'admin');
                $access_reason = $has_access ? 'Utilisateur - Module actif' : 'Utilisateur - Module non disponible';
                break;
                
            default:
                // GUEST : Aucun accès, redirection vers login
                $has_access = false;
                $access_reason = 'Non connecté - Authentification requise';
        }
        
        if ($has_access || ($user_role === 'logistique' && in_array($module_key, ['adr', 'epi', 'outillages', 'qualite']))) {
            $accessible_modules[$module_key] = array_merge($module_data, [
                'access_granted' => $has_access,
                'access_reason' => $access_reason,
                'visible_only' => !$has_access && $user_role === 'logistique'
            ]);
        }
    }
    
    return $accessible_modules;
}

/**
 * LOGIQUE D'AFFICHAGE MENU SELON RÔLE - MISE À JOUR COMPLÈTE
 * À utiliser dans templates/header.php
 */
function shouldShowModuleInMenu($module_key, $module_data, $user_role) {
    // Si utilisateur non connecté, ne rien afficher (redirection login)
    if ($user_role === 'guest' || !$user_role) {
        return false;
    }
    
    // Logique d'affichage selon rôle
    switch ($user_role) {
        case 'dev':
            return true; // Tout voir
            
        case 'admin':
            // Voir tous modules active/beta + admin
            return ($module_key === 'admin' || in_array($module_data['status'], ['active', 'beta']));
            
        case 'logistique':
            // Voir port (beta) + adr, epi, outillages, qualité (dev)
            return in_array($module_key, ['port', 'adr', 'epi', 'outillages', 'qualite']);
            
        case 'user':
            // Voir seulement les modules actifs (sauf admin)
            return ($module_data['status'] === 'active' && $module_key !== 'admin');
            
        default:
            return false;
    }
}

/**
 * STYLES CSS SELON STATUT D'ACCÈS
 */
function getModuleDisplayClass($module_key, $module_data, $user_role) {
    if (!shouldShowModuleInMenu($module_key, $module_data, $user_role)) {
        return 'hidden';
    }
    
    $classes = ['module-nav-item'];
    
    // Module actuel
    global $current_module;
    if ($current_module === $module_key) {
        $classes[] = 'active';
    }
    
    // Statut d'accès
    if ($user_role === 'logistique' && in_array($module_key, ['adr', 'qualite']) && $module_data['status'] === 'development') {
        $classes[] = 'disabled'; // Visible mais non accessible
    }
    
    return implode(' ', $classes);
}
/**
 * FONCTIONS COMMUNES D'ACCÈS AUX MODULES - VERSION UNIQUE
 * Centralisation pour éviter les redéclarations
 */

if (!function_exists('canAccessModule')) {
    /**
     * Vérifie si un utilisateur peut accéder à un module
     */
    function canAccessModule($module_key, $module_data, $user_role) {
        if (!$user_role || $user_role === 'guest') {
            return false;
        }
        
        switch ($user_role) {
            case 'dev':
                return true;
                
            case 'admin':
                return ($module_key === 'admin' || in_array($module_data['status'] ?? 'active', ['active', 'beta']));
                
            case 'logistique':
                return in_array($module_key, ['port', 'adr', 'epi', 'outillages', 'qualite', 'user']);
                
            case 'user':
                return (($module_data['status'] ?? 'active') === 'active');
                
            default:
                return false;
        }
    }
}

if (!function_exists('shouldShowModule')) {
    /**
     * Détermine si un module doit être affiché dans le menu
     */
    function shouldShowModule($module_key, $module_data, $user_role) {
        if (!$user_role || $user_role === 'guest') {
            return false;
        }
        
        switch ($user_role) {
            case 'dev':
                return true;
                
            case 'admin':
                return ($module_key === 'admin' || in_array($module_data['status'] ?? 'active', ['active', 'beta']));
                
            case 'logistique':
                return in_array($module_key, ['port', 'adr', 'epi', 'outillages', 'qualite', 'user']);
                
            case 'user':
                return (($module_data['status'] ?? 'active') === 'active');
                
            default:
                return false;
        }
    }
}


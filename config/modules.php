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
        'status' => 'beta', // Statut mis à jour : beta
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
        'status' => 'development', // Statut : development (pas d'accès pour logistique)
        'icon' => '⚠️',
        'color' => '#e74c3c',
        'routes' => ['adr', 'dangereuses'],
        'assets' => [
            'css' => ['adr.css'],
            'js' => ['adr.js']
        ]
    ],
    
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Suivi qualité des marchandises',
        'class' => 'QualiteModule',
        'status' => 'development', // Statut : development (pas d'accès pour logistique)
        'icon' => '✅',
        'color' => '#2ecc71',
        'routes' => ['qualite', 'controle-qualite']
    ],
    
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection',
        'class' => 'EPIModule',
        'status' => 'development',
        'icon' => '🦺',
        'color' => '#f39c12',
        'routes' => ['epi', 'equipements']
    ],
    
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion des outillages industriels',
        'class' => 'OutillagesModule',
        'status' => 'development',
        'icon' => '🔧',
        'color' => '#95a5a6',
        'routes' => ['outillages', 'outils']
    ],
    
    'admin' => [
        'name' => 'Administration',
        'description' => 'Gestion et configuration',
        'class' => 'AdminModule',
        'status' => 'active',
        'icon' => '⚙️',
        'color' => '#9b59b6',
        'routes' => ['admin', 'administration'],
        'auth_required' => true
    ]
];

/**
 * DÉFINITION DES ACCÈS PAR RÔLE
 * Cette fonction détermine quels modules sont accessibles selon le rôle utilisateur
 */
function getModuleAccessByRole($user_role, $modules) {
    $accessible_modules = [];
    
    foreach ($modules as $module_key => $module_data) {
        $has_access = false;
        $access_reason = '';
        
        switch ($user_role) {
            case 'dev':
                // DEV : Accès total sans restriction
                $has_access = true;
                $access_reason = 'Développeur - Accès complet';
                break;
                
            case 'admin':
                // ADMIN : Accès à tous modules sauf /dev (statuts 'active' et 'beta')
                if ($module_key !== 'dev') {
                    $has_access = in_array($module_data['status'], ['active', 'beta']);
                    $access_reason = $has_access ? 'Admin - Module ' . $module_data['status'] : 'Admin - Module en développement';
                }
                break;
                
            case 'logistique':
                // LOGISTIQUE : Accès à port (beta) + adr + qualité (mais développement = pas d'accès réel)
                if (in_array($module_key, ['port', 'adr', 'qualite'])) {
                    if ($module_key === 'port' && $module_data['status'] === 'beta') {
                        $has_access = true;
                        $access_reason = 'Logistique - Module en bêta';
                    } else if (in_array($module_key, ['adr', 'qualite']) && $module_data['status'] === 'development') {
                        $has_access = false; // Développement = pas d'accès
                        $access_reason = 'Logistique - Module en développement (pas d\'accès)';
                    }
                }
                break;
                
            case 'user':
                // USER : Accès uniquement aux modules actifs (pour le moment seul 'port' si actif)
                $has_access = ($module_data['status'] === 'active');
                $access_reason = $has_access ? 'Utilisateur - Module actif' : 'Utilisateur - Module non actif';
                break;
                
            default:
                // GUEST : Aucun accès, redirection vers login
                $has_access = false;
                $access_reason = 'Non connecté - Authentification requise';
        }
        
        if ($has_access) {
            $accessible_modules[$module_key] = array_merge($module_data, [
                'access_granted' => true,
                'access_reason' => $access_reason
            ]);
        }
    }
    
    return $accessible_modules;
}

/**
 * LOGIQUE D'AFFICHAGE MENU SELON RÔLE
 * À utiliser dans templates/header.php
 */
function shouldShowModuleInMenu($module_key, $module_data, $user_role) {
    // Si utilisateur non connecté, ne rien afficher (redirection login)
    if ($user_role === 'guest' || !$user_role) {
        return false;
    }
    
    // Logique d'accès selon rôle
    switch ($user_role) {
        case 'dev':
            return true; // Tout voir
            
        case 'admin':
            return ($module_key !== 'dev' && in_array($module_data['status'], ['active', 'beta']));
            
        case 'logistique':
            // Voir seulement port (beta), adr et qualité mais sans accès aux modules en dev
            if (in_array($module_key, ['port', 'adr', 'qualite'])) {
                if ($module_key === 'port' && $module_data['status'] === 'beta') {
                    return true; // Accès réel
                } else if (in_array($module_key, ['adr', 'qualite'])) {
                    return true; // Affiché mais grisé/désactivé car en développement
                }
            }
            return false;
            
        case 'user':
            return ($module_data['status'] === 'active');
            
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

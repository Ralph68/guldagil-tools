<?php
/**
 * Titre: Configuration des modules
 * Chemin: /config/modules.php
 */

$modules = [
    'port' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport',
        'class' => 'PortModule',
        'status' => 'active',
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
        'status' => 'active',
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
        'status' => 'development',
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

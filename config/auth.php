<?php
/**
 * Configuration Authentification - Gul Calc Frais de port
 * Chemin : /config/auth.php
 * Version : 0.5 beta
 */

return [
    'session' => [
        'name' => 'GUL_SESSION',
        'secure' => false, // true en production avec HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ],
    
    'roles' => [
        'dev' => [
            'name' => 'Développeur',
            'permissions' => ['*'], // Accès total
            'session_duration' => 0, // Illimitée
            'pages' => ['*']
        ],
        'admin' => [
            'name' => 'Administrateur',
            'permissions' => ['admin', 'read', 'write', 'config'],
            'session_duration' => 28800, // 8h
            'pages' => ['/admin/*', '/public/*']
        ],
        'user' => [
            'name' => 'Utilisateur',
            'permissions' => ['read'],
            'session_duration' => 7200, // 2h
            'pages' => ['/public/index.php', '/public/calculateur.php']
        ]
    ],
    
    'login' => [
        'max_attempts' => 3,
        'lockout_duration' => 900, // 15 min
        'remember_me' => true,
        'remember_duration' => 2592000 // 30 jours
    ],
    
    'password' => [
        'min_length' => 6,
        'require_special' => false, // Pour dev, relaxé
        'cost' => 12
    ],
    
    'pages' => [
        'login' => '/public/login.php',
        'logout' => '/public/logout.php',
        'default' => '/public/index.php',
        'forbidden' => '/public/403.php'
    ]
];

<?php
/**
 * Titre: Configuration principale + autoloader
 * Chemin: /config/app.php
 */

// Autoloader simple
function autoload($className) {
    $paths = [
        __DIR__ . '/../core/',
        __DIR__ . '/../features/port/',
        __DIR__ . '/../features/adr/',
        __DIR__ . '/../features/admin/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}
spl_autoload_register('autoload');

// Configuration
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/database.php';

$config = [
    'app' => [
        'name' => APP_NAME,
        'version' => APP_VERSION,
        'debug' => DEBUG
    ],
    'database' => $db,
    'paths' => [
        'root' => dirname(__DIR__),
        'public' => dirname(__DIR__) . '/public',
        'storage' => dirname(__DIR__) . '/storage',
        'templates' => dirname(__DIR__) . '/templates'
    ]
];

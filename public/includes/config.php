<?php
// Configuration principale Guldagil Portal
session_start();

// Configuration base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'guldagil_portal');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

// Modules disponibles
define('MODULES', [
    'calculateur' => [
        'enabled' => true,
        'public' => true,
        'name' => 'Calculateur de frais',
        'path' => '/'
    ],
    'adr' => [
        'enabled' => true,
        'public' => false,  // Accès restreint
        'name' => 'Gestion ADR',
        'path' => '/adr/'
    ],
    'tracking' => [
        'enabled' => true,
        'public' => true,   // Liens publics
        'name' => 'Suivi expéditions',
        'path' => '/#suivi'
    ],
    'admin' => [
        'enabled' => true,
        'public' => false,  // Admin seulement
        'name' => 'Administration',
        'path' => '/admin/'
    ]
]);

// Configuration ADR
define('ADR_CONFIG', [
    'session_timeout' => 3600, // 1 heure
    'max_declarations_per_day' => 50,
    'pdf_output_path' => __DIR__ . '/../uploads/pdfs/',
    'temp_path' => __DIR__ . '/../uploads/temp/',
    'allowed_file_types' => ['xlsx', 'csv'],
    'max_file_size' => 5 * 1024 * 1024 // 5MB
]);

// Liens externes transporteurs
define('TRACKING_LINKS', [
    'heppner' => [
        'name' => 'Heppner MyPortal',
        'url' => 'https://myportal.heppner-group.com/home',
        'logo' => 'assets/images/logos/heppner-logo.png'
    ],
    'xpo' => [
        'name' => 'XPO Connect',
        'url' => 'https://xpoconnecteu.xpo.com/customer/orders/list',
        'logo' => 'assets/images/logos/xpo-logo.png'
    ],
    'geodis' => [
        'name' => 'Geodis Portal',
        'url' => 'https://portal.geodis.com',
        'logo' => 'assets/images/logos/geodis-logo.png'
    ]
]);

// Chemins absolus
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('BASE_URL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));

// Timezone
date_default_timezone_set('Europe/Paris');

// Gestion des erreurs (désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonctions utilitaires
function isModuleEnabled($module) {
    return MODULES[$module]['enabled'] ?? false;
}

function isModulePublic($module) {
    return MODULES[$module]['public'] ?? false;
}

function getModulePath($module) {
    return MODULES[$module]['path'] ?? '/';
}

// Autoloader simple pour les classes
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/includes/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>

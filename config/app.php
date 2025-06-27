<?php
/**
 * Titre: Configuration applicative corrigée
 * Chemin: /config/app.php
 */

// Inclusion de la configuration principale AVANT tout
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/version.php';

// Vérification que la connexion PDO est bien disponible
if (!isset($db) || !($db instanceof PDO)) {
    throw new Exception('Connexion base de données non disponible dans app.php');
}

// Rendre la connexion accessible globalement
$GLOBALS['db'] = $db;

/**
 * Fonction pour obtenir la connexion DB de manière sécurisée
 * @return PDO
 */
function getDBConnection(): PDO {
    global $db;
    if (!isset($db) || !($db instanceof PDO)) {
        // Tentative de récupération depuis GLOBALS
        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            $db = $GLOBALS['db'];
            return $db;
        }
        throw new Exception('Connexion PDO non disponible');
    }
    return $db;
}

/**
 * Factory pour créer une instance Transport avec la bonne connexion
 * @return Transport
 */
function createTransportInstance(): Transport {
    $db_connection = getDBConnection();
    
    // Vérifier que la classe Transport existe
    if (!class_exists('Transport')) {
        $transport_file = ROOT_PATH . '/features/port/Transport.php';
        if (file_exists($transport_file)) {
            require_once $transport_file;
        } else {
            throw new Exception('Classe Transport non trouvée: ' . $transport_file);
        }
    }
    
    return new Transport($db_connection);
}

// Constantes applicatives
define('APP_INITIALIZED', true);
define('DB_AVAILABLE', isset($db) && $db instanceof PDO);

// Configuration des modules
$modules_config = [
    'port' => [
        'name' => 'Calculateur Frais de Port',
        'class' => 'PortModule',
        'file' => ROOT_PATH . '/features/port/PortModule.php',
        'enabled' => true,
        'requires_db' => true
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'class' => 'ADRModule', 
        'file' => ROOT_PATH . '/features/adr/ADRModule.php',
        'enabled' => true,
        'requires_db' => true
    ],
    'admin' => [
        'name' => 'Administration',
        'class' => 'AdminModule',
        'file' => ROOT_PATH . '/features/admin/AdminModule.php',
        'enabled' => true,
        'requires_db' => true
    ]
];

/**
 * Charge un module en injectant la connexion DB si nécessaire
 * @param string $module_name
 * @return object|null
 */
function loadModule(string $module_name) {
    global $modules_config;
    
    if (!isset($modules_config[$module_name])) {
        throw new Exception("Module '$module_name' non configuré");
    }
    
    $config = $modules_config[$module_name];
    
    if (!$config['enabled']) {
        throw new Exception("Module '$module_name' désactivé");
    }
    
    if (!file_exists($config['file'])) {
        throw new Exception("Fichier module manquant: " . $config['file']);
    }
    
    require_once $config['file'];
    
    if (!class_exists($config['class'])) {
        throw new Exception("Classe '{$config['class']}' non trouvée");
    }
    
    // Instanciation avec injection de dépendance DB si nécessaire
    if ($config['requires_db']) {
        return new $config['class'](getDBConnection());
    } else {
        return new $config['class']();
    }
}

// Test de santé de l'application
function healthCheck(): array {
    $status = [
        'app_initialized' => defined('APP_INITIALIZED'),
        'db_connection' => false,
        'modules_available' => [],
        'version' => getVersionInfo()
    ];
    
    try {
        $db = getDBConnection();
        $db->query("SELECT 1");
        $status['db_connection'] = true;
    } catch (Exception $e) {
        $status['db_error'] = $e->getMessage();
    }
    
    global $modules_config;
    foreach ($modules_config as $name => $config) {
        $status['modules_available'][$name] = [
            'enabled' => $config['enabled'],
            'file_exists' => file_exists($config['file'])
        ];
    }
    
    return $status;
}
?>

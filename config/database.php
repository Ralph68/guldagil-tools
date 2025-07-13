<?php
/**
 * Titre: Configuration base de données
 * Chemin: /config/database.php
 * Version: 0.5 beta + build auto
 */

// Protection directe
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Chargement des variables d'environnement si disponibles
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile, false, INI_SCANNER_TYPED);
    if ($env !== false) {
        foreach ($env as $key => $value) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }
    }
}

// Configuration base de données
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'guldagil_portal');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Options PDO
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

// Connexion PDO globale
try {
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Test simple de la connexion
    $db->query("SELECT 1");
    
    // Variable globale pour la connexion
    $GLOBALS['db'] = $db;
    
} catch (PDOException $e) {
    $errorMessage = (defined('DEBUG') && DEBUG) ? 
        'Erreur de connexion BDD : ' . $e->getMessage() : 
        'Erreur de connexion à la base de données';
    
    if (defined('DEBUG') && DEBUG) {
        error_log("Erreur PDO: " . $e->getMessage());
    }
    
    // En production, redirection vers page d'erreur
    if (!defined('DEBUG') || !DEBUG) {
        header('Location: /error.php?type=db');
        exit;
    }
    
    die($errorMessage);
}

/**
 * Fonction helper pour obtenir la connexion DB
 * @return PDO
 */
function getDB(): PDO {
    global $db;
    if (!isset($db) || !($db instanceof PDO)) {
        throw new Exception('Connexion base de données non initialisée');
    }
    return $db;
}

/**
 * Test de connexion simple
 * @return bool
 */
function testDBConnection(): bool {
    try {
        $db = getDB();
        $db->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        error_log("Test connexion DB échoué: " . $e->getMessage());
        return false;
    }
}

// Test automatique si en mode debug
if (defined('DEBUG') && DEBUG) {
    if (!testDBConnection()) {
        error_log("ATTENTION: Connexion base de données défaillante");
    }
}
?>

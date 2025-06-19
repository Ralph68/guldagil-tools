<?php
// config.php — connexion centralisée à la base de données

// Configuration des erreurs selon l'environnement
$isProduction = (getenv('APP_ENV') === 'production');

if (!$isProduction) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Fonction pour gérer les erreurs sans affichage HTML
function handleConfigError($message) {
    error_log("CONFIG ERROR: " . $message);
    
    // Si c'est une requête AJAX/API, retourner JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ||
        strpos($_SERVER['REQUEST_URI'], '/api-') !== false ||
        isset($_GET['action'])) {
        
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur de configuration de la base de données'
        ]);
        exit;
    }
    
    // Sinon, affichage normal
    http_response_code(500);
    echo $message;
    exit;
}

// Chargement du .env
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    handleConfigError('Erreur : fichier .env introuvable.');
}

$env = parse_ini_file($envPath, false, INI_SCANNER_TYPED);
if ($env === false) {
    handleConfigError('Erreur : lecture du fichier .env impossible.');
}

// Extraction des paramètres
$host    = $env['DB_HOST']    ?? 'localhost';
$dbName  = $env['DB_NAME']    ?? '';
$user    = $env['DB_USER']    ?? '';
$pass    = $env['DB_PASS']    ?? '';
$charset = !empty($env['DB_CHARSET']) ? $env['DB_CHARSET'] : 'utf8mb4';

// Validation des paramètres obligatoires
if (empty($dbName) || empty($user)) {
    handleConfigError('Erreur : paramètres de base de données manquants dans .env');
}

// Construction du DSN PDO
$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

// Options PDO recommandées
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10, // Timeout de 10 secondes
];

try {
    $db = new PDO($dsn, $user, $pass, $options);
    
    // Test de la connexion
    $db->query("SELECT 1");
    
} catch (PDOException $e) {
    $errorMessage = $isProduction ? 
        'Erreur de connexion à la base de données' : 
        'Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage());
    
    handleConfigError($errorMessage);
}
?>

<?php
/**
 * Titre: Script de diagnostic erreur 500 - Module Port
 * Chemin: /public/diagnostic_port.php
 * Version: 0.5 beta + build auto
 * Usage: Accédez à http://votre-domaine/diagnostic_port.php
 */

// Activation affichage erreurs pour diagnostic
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Fonction d'affichage sécurisé
function safe_echo($text) {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// CSS simple pour le diagnostic
$diagnostic_css = '
<style>
body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
.container { max-width: 1200px; margin: 0 auto; }
.section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; padding: 30px; border-radius: 8px; }
.success { color: #059669; font-weight: bold; }
.error { color: #dc2626; font-weight: bold; }
.warning { color: #d97706; font-weight: bold; }
.info { color: #2563eb; font-weight: bold; }
.code { background: #f3f4f6; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; }
.fix-btn { background: #059669; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
.fix-btn:hover { background: #047857; }
pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style>
';

echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Diagnostic Erreur 500 - Module Port</title>
    ' . $diagnostic_css . '
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔧 Diagnostic Erreur 500</h1>
        <p>Module Port - Calculateur de Frais de Port</p>
        <small>Guldagil Portail v0.5 beta</small>
    </div>';

// ===========================================
// 1. VÉRIFICATION STRUCTURE DE BASE
// ===========================================
echo '<div class="section">
    <h2>📁 1. Structure de base</h2>
    <div class="grid">';

$root_path = dirname(__DIR__);
$critical_paths = [
    'config/config.php' => 'Configuration principale',
    'config/version.php' => 'Informations de version',
    'templates/header.php' => 'Header global',
    'templates/footer.php' => 'Footer global',
    'public/port/index.php' => 'Index module port',
    'public/.htaccess' => 'Réécriture URLs',
    'public/assets/css/portal.css' => 'CSS principal',
    'core/transport/transport.php' => 'Moteur de calcul',
];

foreach ($critical_paths as $path => $description) {
    $full_path = $root_path . '/' . $path;
    $exists = file_exists($full_path);
    $readable = $exists && is_readable($full_path);
    
    echo '<div class="card">';
    echo '<h4>' . safe_echo($description) . '</h4>';
    echo '<code>' . safe_echo($path) . '</code><br>';
    
    if ($exists && $readable) {
        echo '<span class="success">✅ OK</span>';
        $size = filesize($full_path);
        echo '<br><small>Taille: ' . number_format($size) . ' octets</small>';
    } elseif ($exists) {
        echo '<span class="error">❌ Existe mais non lisible</span>';
    } else {
        echo '<span class="error">❌ Fichier manquant</span>';
    }
    echo '</div>';
}

echo '</div></div>';

// ===========================================
// 2. TEST SYNTAXE PHP
// ===========================================
echo '<div class="section">
    <h2>🐛 2. Vérification syntaxe PHP</h2>';

$port_index = $root_path . '/public/port/index.php';
if (file_exists($port_index)) {
    echo '<h4>Test syntaxe de index.php :</h4>';
    
    // Test de syntaxe via PHP lint
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($port_index) . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo '<span class="success">✅ Syntaxe PHP correcte</span>';
    } else {
        echo '<span class="error">❌ Erreur de syntaxe détectée :</span>';
        echo '<pre>' . implode("\n", array_map('htmlspecialchars', $output)) . '</pre>';
    }
    
    // Vérifier la taille du fichier
    $file_size = filesize($port_index);
    echo '<p><strong>Taille du fichier :</strong> ' . number_format($file_size) . ' octets</p>';
    
    if ($file_size < 1000) {
        echo '<span class="warning">⚠️ Fichier très petit, possiblement tronqué</span>';
    }
    
    // Afficher le début du fichier
    echo '<h4>Début du fichier (200 caractères) :</h4>';
    $content_preview = file_get_contents($port_index, false, null, 0, 200);
    echo '<pre>' . htmlspecialchars($content_preview) . '</pre>';
    
} else {
    echo '<span class="error">❌ Fichier /public/port/index.php non trouvé</span>';
}

echo '</div>';

// ===========================================
// 3. TEST CONFIGURATION ET CONSTANTES
// ===========================================
echo '<div class="section">
    <h2>⚙️ 3. Configuration et constantes</h2>';

// Définir ROOT_PATH si pas déjà fait
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $root_path);
    echo '<p class="info">📌 ROOT_PATH défini : ' . safe_echo($root_path) . '</p>';
}

// Tester le chargement de config.php
echo '<h4>Chargement config.php :</h4>';
$config_file = ROOT_PATH . '/config/config.php';
if (file_exists($config_file)) {
    try {
        require_once $config_file;
        echo '<span class="success">✅ config.php chargé</span><br>';
        
        // Vérifier les constantes importantes
        $required_constants = ['APP_NAME', 'APP_VERSION', 'DB_HOST', 'DB_NAME', 'DB_USER'];
        foreach ($required_constants as $const) {
            if (defined($const)) {
                echo '<span class="success">✅ ' . $const . '</span>: ' . safe_echo(constant($const)) . '<br>';
            } else {
                echo '<span class="error">❌ ' . $const . ' non définie</span><br>';
            }
        }
        
    } catch (Exception $e) {
        echo '<span class="error">❌ Erreur chargement : ' . safe_echo($e->getMessage()) . '</span>';
    }
} else {
    echo '<span class="error">❌ config.php non trouvé</span>';
}

// Tester version.php
echo '<h4>Chargement version.php :</h4>';
$version_file = ROOT_PATH . '/config/version.php';
if (file_exists($version_file)) {
    try {
        require_once $version_file;
        echo '<span class="success">✅ version.php chargé</span><br>';
        
        if (function_exists('getVersionInfo')) {
            $version_info = getVersionInfo();
            echo '<span class="info">📦 Version : ' . safe_echo($version_info['version']) . '</span><br>';
            echo '<span class="info">🔢 Build : ' . safe_echo($version_info['build_number']) . '</span><br>';
        }
        
    } catch (Exception $e) {
        echo '<span class="error">❌ Erreur chargement : ' . safe_echo($e->getMessage()) . '</span>';
    }
} else {
    echo '<span class="error">❌ version.php non trouvé</span>';
}

echo '</div>';

// ===========================================
// 4. TEST BASE DE DONNÉES
// ===========================================
echo '<div class="section">
    <h2>🗄️ 4. Connexion base de données</h2>';

if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $db = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo '<span class="success">✅ Connexion BDD réussie</span><br>';
        
        // Test tables auth
        $auth_tables = ['auth_users', 'auth_sessions'];
        foreach ($auth_tables as $table) {
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
                $result = $stmt->fetch();
                echo '<span class="success">✅ Table ' . $table . '</span>: ' . $result['count'] . ' enregistrements<br>';
            } catch (Exception $e) {
                echo '<span class="warning">⚠️ Table ' . $table . '</span>: ' . safe_echo($e->getMessage()) . '<br>';
            }
        }
        
    } catch (Exception $e) {
        echo '<span class="error">❌ Erreur connexion BDD : ' . safe_echo($e->getMessage()) . '</span>';
    }
} else {
    echo '<span class="error">❌ Constantes de BDD manquantes</span>';
}

echo '</div>';

// ===========================================
// 5. TEST SESSIONS ET AUTHENTIFICATION
// ===========================================
echo '<div class="section">
    <h2>🔐 5. Sessions et authentification</h2>';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo '<span class="info">📌 Session démarrée</span><br>';
}

echo '<p><strong>Session ID :</strong> ' . safe_echo(session_id()) . '</p>';
echo '<p><strong>Session status :</strong> ' . session_status() . '</p>';

// Simuler une session authentifiée pour les tests
if (!isset($_SESSION['authenticated'])) {
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = ['username' => 'diagnostic', 'role' => 'admin'];
    echo '<span class="info">📌 Session d\'authentification simulée créée</span><br>';
}

echo '<span class="success">✅ authenticated : ' . ($_SESSION['authenticated'] ? 'true' : 'false') . '</span><br>';
if (isset($_SESSION['user'])) {
    echo '<span class="success">✅ user : ' . safe_echo($_SESSION['user']['username']) . ' (' . safe_echo($_SESSION['user']['role']) . ')</span><br>';
}

echo '</div>';

// ===========================================
// 6. TEST D'ACCÈS DIRECT AU MODULE PORT
// ===========================================
echo '<div class="section">
    <h2>🚛 6. Test d\'accès module Port</h2>';

echo '<div class="grid">';

// Test 1: Accès page principale
echo '<div class="card">';
echo '<h4>Page principale (/port/)</h4>';
$port_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/port/';
echo '<p><strong>URL :</strong> <a href="' . $port_url . '" target="_blank">' . safe_echo($port_url) . '</a></p>';
echo '<button class="fix-btn" onclick="window.open(\'' . $port_url . '\', \'_blank\')">🔗 Tester la page</button>';
echo '</div>';

// Test 2: Accès AJAX
echo '<div class="card">';
echo '<h4>API AJAX (/port/?ajax=calculate)</h4>';
$ajax_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/port/?ajax=calculate';
echo '<p><strong>URL :</strong> ' . safe_echo($ajax_url) . '</p>';
echo '<button class="fix-btn" onclick="testAjax()">🔗 Tester l\'API</button>';
echo '<div id="ajax-result"></div>';
echo '</div>';

echo '</div>';

echo '</div>';

// ===========================================
// 7. ACTIONS CORRECTIVES
// ===========================================
echo '<div class="section">
    <h2>🛠️ 7. Actions correctives</h2>';

echo '<div class="grid">';

echo '<div class="card">';
echo '<h4>🔄 Remplacement index.php</h4>';
echo '<p>Si le fichier index.php est corrompu, le remplacer par la version corrigée.</p>';
echo '<button class="fix-btn" onclick="showFixCode()">📄 Voir le code de remplacement</button>';
echo '</div>';

echo '<div class="card">';
echo '<h4>📝 Vérification logs</h4>';
echo '<p>Consulter les logs d\'erreur du serveur web.</p>';
$log_paths = ['/var/log/apache2/error.log', '/var/log/nginx/error.log', 'storage/logs/error.log'];
foreach ($log_paths as $log_path) {
    if (file_exists($log_path)) {
        echo '<p class="success">✅ Log trouvé : ' . safe_echo($log_path) . '</p>';
    }
}
echo '</div>';

echo '<div class="card">';
echo '<h4>🧹 Cache et sessions</h4>';
echo '<p>Nettoyer le cache et les sessions temporaires.</p>';
echo '<button class="fix-btn" onclick="clearCache()">🗑️ Nettoyer cache</button>';
echo '</div>';

echo '</div>';

echo '</div>';

// ===========================================
// JavaScript pour les tests interactifs
// ===========================================
echo '<script>
function testAjax() {
    const resultDiv = document.getElementById("ajax-result");
    resultDiv.innerHTML = "<p>🔄 Test en cours...</p>";
    
    const testData = "departement=75&poids=25&type=colis&adr=non&option_sup=standard&enlevement=non";
    
    fetch("/port/?ajax=calculate", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: testData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            resultDiv.innerHTML = `<p class="success">✅ API fonctionne</p><pre>${JSON.stringify(data, null, 2)}</pre>`;
        } catch (e) {
            resultDiv.innerHTML = `<p class="error">❌ Réponse non-JSON</p><pre>${text.substring(0, 300)}...</pre>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<p class="error">❌ Erreur: ${error.message}</p>`;
    });
}

function showFixCode() {
    alert("Le code de remplacement complet a été généré dans l\'artefact \\"port_index_fixed\\". Copiez-le dans /public/port/index.php");
}

function clearCache() {
    // Simuler le nettoyage de cache
    if (confirm("Voulez-vous vraiment nettoyer le cache ?")) {
        alert("Cache nettoyé (simulation). Rechargez la page et retestez le module port.");
        location.reload();
    }
}
</script>';

echo '</div>
</body>
</html>';
?>
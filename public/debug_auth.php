<?php
/**
 * FICHIER DE DEBUG AUTHENTIFICATION
 * Placez ce fichier dans /public/debug_auth.php
 * Accédez via : http://votre-domaine/debug_auth.php
 */

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 DIAGNOSTIC AUTHENTIFICATION PORTAIL</h1>";
echo "<style>body{font-family:monospace;background:#f5f5f5;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} .section{background:white;padding:15px;margin:10px 0;border-radius:5px;border-left:4px solid #007cba;}</style>";

// =====================================
// 1. STRUCTURE DES FICHIERS
// =====================================
echo "<div class='section'>";
echo "<h2>📁 1. Structure des fichiers</h2>";

$files_to_check = [
    'ROOT_PATH' => dirname(__DIR__),
    '/config/config.php' => dirname(__DIR__) . '/config/config.php',
    '/config/version.php' => dirname(__DIR__) . '/config/version.php',
    '/config/modules.php' => dirname(__DIR__) . '/config/modules.php',
    '/core/auth/AuthManager.php' => dirname(__DIR__) . '/core/auth/AuthManager.php',
    '/templates/header.php' => dirname(__DIR__) . '/templates/header.php',
    '/templates/footer.php' => dirname(__DIR__) . '/templates/footer.php',
    '/public/index.php' => dirname(__DIR__) . '/public/index.php',
    '/public/auth/login.php' => dirname(__DIR__) . '/public/auth/login.php'
];

foreach ($files_to_check as $label => $path) {
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    
    echo "<p>";
    echo "<strong>$label:</strong> ";
    if ($exists) {
        echo "<span class='ok'>✅ Existe</span>";
        if ($readable) {
            echo " <span class='ok'>📖 Lisible</span>";
        } else {
            echo " <span class='error'>🚫 Non lisible</span>";
        }
        echo " <small>(" . filesize($path) . " bytes)</small>";
    } else {
        echo "<span class='error'>❌ MANQUANT : $path</span>";
    }
    echo "</p>";
}
echo "</div>";

// =====================================
// 2. CONFIGURATION PHP
// =====================================
echo "<div class='section'>";
echo "<h2>⚙️ 2. Configuration PHP</h2>";

echo "<p><strong>Version PHP :</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Session module :</strong> " . (extension_loaded('session') ? '<span class="ok">✅ Chargé</span>' : '<span class="error">❌ Manquant</span>') . "</p>";
echo "<p><strong>PDO module :</strong> " . (extension_loaded('pdo') ? '<span class="ok">✅ Chargé</span>' : '<span class="error">❌ Manquant</span>') . "</p>";
echo "<p><strong>Display errors :</strong> " . (ini_get('display_errors') ? '<span class="ok">ON</span>' : '<span class="warning">OFF</span>') . "</p>";
echo "<p><strong>Error reporting :</strong> " . error_reporting() . "</p>";
echo "<p><strong>Memory limit :</strong> " . ini_get('memory_limit') . "</p>";

echo "</div>";

// =====================================
// 3. TEST CHARGEMENT CONFIG
// =====================================
echo "<div class='section'>";
echo "<h2>📋 3. Test chargement configuration</h2>";

define('ROOT_PATH', dirname(__DIR__));
echo "<p><strong>ROOT_PATH défini :</strong> <span class='ok'>✅ " . ROOT_PATH . "</span></p>";

try {
    if (file_exists(ROOT_PATH . '/config/config.php')) {
        echo "<p>🔄 Chargement config.php...</p>";
        require_once ROOT_PATH . '/config/config.php';
        echo "<p><span class='ok'>✅ config.php chargé</span></p>";
        
        // Vérifier constantes importantes
        $constants = ['APP_NAME', 'APP_VERSION', 'BUILD_NUMBER', 'DEBUG'];
        foreach ($constants as $const) {
            if (defined($const)) {
                echo "<p><strong>$const :</strong> <span class='ok'>" . constant($const) . "</span></p>";
            } else {
                echo "<p><strong>$const :</strong> <span class='warning'>Non défini</span></p>";
            }
        }
    } else {
        echo "<p><span class='error'>❌ config.php non trouvé</span></p>";
    }
} catch (Exception $e) {
    echo "<p><span class='error'>❌ Erreur config.php : " . $e->getMessage() . "</span></p>";
}

try {
    if (file_exists(ROOT_PATH . '/config/version.php')) {
        echo "<p>🔄 Chargement version.php...</p>";
        require_once ROOT_PATH . '/config/version.php';
        echo "<p><span class='ok'>✅ version.php chargé</span></p>";
    }
} catch (Exception $e) {
    echo "<p><span class='error'>❌ Erreur version.php : " . $e->getMessage() . "</span></p>";
}

echo "</div>";

// =====================================
// 4. TEST HEADER.PHP
// =====================================
echo "<div class='section'>";
echo "<h2>📄 4. Test header.php</h2>";

// Variables obligatoires pour header
$page_title = 'Test Debug';
$page_subtitle = 'Page de diagnostic';
$current_module = 'debug';

echo "<p>🔄 Variables définies pour header...</p>";
echo "<p><strong>page_title :</strong> $page_title</p>";
echo "<p><strong>current_module :</strong> $current_module</p>";

try {
    if (file_exists(ROOT_PATH . '/templates/header.php')) {
        echo "<p>🔄 Test inclusion header.php...</p>";
        
        // Capture output pour éviter l'affichage
        ob_start();
        include ROOT_PATH . '/templates/header.php';
        $header_output = ob_get_clean();
        
        echo "<p><span class='ok'>✅ header.php inclus sans erreur</span></p>";
        echo "<p><strong>Taille output :</strong> " . strlen($header_output) . " bytes</p>";
        
        // Vérifier si variables sont disponibles après header
        if (isset($user_authenticated)) {
            echo "<p><strong>user_authenticated :</strong> <span class='ok'>" . ($user_authenticated ? 'true' : 'false') . "</span></p>";
        }
        if (isset($current_user)) {
            echo "<p><strong>current_user :</strong> <span class='ok'>" . json_encode($current_user) . "</span></p>";
        }
        
    } else {
        echo "<p><span class='error'>❌ header.php non trouvé</span></p>";
    }
} catch (Exception $e) {
    echo "<p><span class='error'>❌ Erreur header.php : " . $e->getMessage() . "</span></p>";
    echo "<p><strong>Fichier :</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Ligne :</strong> " . $e->getLine() . "</p>";
} catch (ParseError $e) {
    echo "<p><span class='error'>❌ Erreur syntaxe header.php : " . $e->getMessage() . "</span></p>";
    echo "<p><strong>Fichier :</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Ligne :</strong> " . $e->getLine() . "</p>";
}

echo "</div>";

// =====================================
// 5. TEST AUTHMANAGER
// =====================================
echo "<div class='section'>";
echo "<h2>🔐 5. Test AuthManager</h2>";

try {
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        echo "<p>🔄 Chargement AuthManager...</p>";
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        echo "<p><span class='ok'>✅ AuthManager chargé</span></p>";
        
        if (class_exists('AuthManager')) {
            echo "<p><span class='ok'>✅ Classe AuthManager existe</span></p>";
            
            try {
                $auth = new AuthManager();
                echo "<p><span class='ok'>✅ Instance AuthManager créée</span></p>";
                
                // Test méthode isAuthenticated
                $is_auth = $auth->isAuthenticated();
                echo "<p><strong>isAuthenticated() :</strong> " . ($is_auth ? '<span class="ok">true</span>' : '<span class="warning">false</span>') . "</p>";
                
            } catch (Exception $e) {
                echo "<p><span class='error'>❌ Erreur instance AuthManager : " . $e->getMessage() . "</span></p>";
            }
        } else {
            echo "<p><span class='error'>❌ Classe AuthManager non trouvée</span></p>";
        }
    } else {
        echo "<p><span class='warning'>⚠️ AuthManager.php non trouvé (fallback sessions)</span></p>";
    }
} catch (Exception $e) {
    echo "<p><span class='error'>❌ Erreur AuthManager : " . $e->getMessage() . "</span></p>";
}

echo "</div>";

// =====================================
// 6. SESSION ET AUTHENTIFICATION
// =====================================
echo "<div class='section'>";
echo "<h2>🔒 6. Session et authentification</h2>";

// Démarrer session pour test
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<p><strong>Session status :</strong> " . session_status() . "</p>";
echo "<p><strong>Session ID :</strong> " . session_id() . "</p>";
echo "<p><strong>Session name :</strong> " . session_name() . "</p>";

// Vérifier contenu session
echo "<p><strong>Contenu $_SESSION :</strong></p>";
if (!empty($_SESSION)) {
    echo "<pre style='background:#f0f0f0;padding:10px;'>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p><span class='warning'>Session vide</span></p>";
}

echo "</div>";

// =====================================
// 7. TEST INDEX.PHP
// =====================================
echo "<div class='section'>";
echo "<h2>🏠 7. Test index.php</h2>";

try {
    if (file_exists(ROOT_PATH . '/public/index.php')) {
        echo "<p>🔄 Lecture index.php...</p>";
        $index_content = file_get_contents(ROOT_PATH . '/public/index.php');
        echo "<p><strong>Taille :</strong> " . strlen($index_content) . " bytes</p>";
        
        // Vérifier syntaxe PHP
        $syntax_check = php_check_syntax(ROOT_PATH . '/public/index.php');
        if ($syntax_check) {
            echo "<p><span class='ok'>✅ Syntaxe PHP correcte</span></p>";
        } else {
            echo "<p><span class='error'>❌ Erreur syntaxe PHP</span></p>";
        }
        
        // Vérifier présence éléments clés
        $checks = [
            'define(\'ROOT_PATH\'' => 'Définition ROOT_PATH',
            'include ROOT_PATH . \'/templates/header.php\'' => 'Inclusion header',
            '$user_authenticated' => 'Variable user_authenticated',
            '$current_user' => 'Variable current_user'
        ];
        
        foreach ($checks as $search => $label) {
            if (strpos($index_content, $search) !== false) {
                echo "<p><strong>$label :</strong> <span class='ok'>✅ Trouvé</span></p>";
            } else {
                echo "<p><strong>$label :</strong> <span class='warning'>⚠️ Non trouvé</span></p>";
            }
        }
        
    } else {
        echo "<p><span class='error'>❌ index.php non trouvé</span></p>";
    }
} catch (Exception $e) {
    echo "<p><span class='error'>❌ Erreur index.php : " . $e->getMessage() . "</span></p>";
}

echo "</div>";

// =====================================
// 8. LOGS D'ERREUR
// =====================================
echo "<div class='section'>";
echo "<h2>📝 8. Logs d'erreur récents</h2>";

$log_files = [
    'PHP Error Log' => ini_get('error_log'),
    'Apache Error Log' => '/var/log/apache2/error.log',
    'System Log' => '/var/log/syslog'
];

foreach ($log_files as $label => $log_file) {
    if ($log_file && file_exists($log_file) && is_readable($log_file)) {
        echo "<h3>$label ($log_file)</h3>";
        $log_content = file_get_contents($log_file);
        $lines = explode("\n", $log_content);
        $recent_lines = array_slice($lines, -10); // 10 dernières lignes
        
        echo "<pre style='background:#f0f0f0;padding:10px;max-height:200px;overflow:auto;'>";
        foreach ($recent_lines as $line) {
            if (!empty(trim($line))) {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<p><strong>$label :</strong> <span class='warning'>Non accessible</span></p>";
    }
}

echo "</div>";

// =====================================
// 9. RECOMMANDATIONS
// =====================================
echo "<div class='section'>";
echo "<h2>💡 9. Recommandations de correction</h2>";

echo "<ol>";
echo "<li><strong>Vérifier les erreurs PHP</strong> dans les logs ci-dessus</li>";
echo "<li><strong>S'assurer que ROOT_PATH</strong> est correctement défini</li>";
echo "<li><strong>Vérifier les permissions</strong> des fichiers (644) et dossiers (755)</li>";
echo "<li><strong>Tester l'inclusion du header</strong> séparément</li>";
echo "<li><strong>Vérifier la configuration AuthManager</strong> si utilisé</li>";
echo "<li><strong>Activer les logs PHP</strong> si pas déjà fait</li>";
echo "</ol>";

echo "<h3>🔧 Actions rapides :</h3>";
echo "<ul>";
echo "<li><code>chmod 755 " . dirname(__DIR__) . "/templates/</code></li>";
echo "<li><code>chmod 644 " . dirname(__DIR__) . "/templates/header.php</code></li>";
echo "<li><code>chmod 644 " . dirname(__DIR__) . "/public/index.php</code></li>";
echo "<li>Vérifier dans logs Apache : <code>tail -f /var/log/apache2/error.log</code></li>";
echo "</ul>";

echo "</div>";

echo "<p style='text-align:center;margin-top:30px;'><strong>🔍 Debug terminé - " . date('Y-m-d H:i:s') . "</strong></p>";

// Fonction pour vérifier syntaxe (PHP < 8.0)
function php_check_syntax($filename) {
    if (!file_exists($filename)) {
        return false;
    }
    
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($filename) . " 2>&1", $output, $return_var);
    
    return $return_var === 0;
}
?>

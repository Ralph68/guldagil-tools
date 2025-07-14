<?php
/**
 * Titre: Outil de diagnostic unifié - Développement uniquement
 * Chemin: /public/admin/dev-diagnostic.php
 * Version: 0.5 beta + build auto
 * 
 * ⚠️ SUPPRIMER EN PRODUCTION !
 */

// Sécurité - localhost uniquement
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) && 
    !in_array($_SERVER['SERVER_NAME'], ['localhost', 'dev.guldagil.local'])) {
    http_response_code(403);
    die('🚫 Accès interdit - Développement uniquement');
}

// Mode debug forcé
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('DEBUG_MODE', true);

// Démarrage
session_start();
$start_time = microtime(true);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🔧 Diagnostic Dev - Portail Guldagil</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            margin: 0; 
            background: #1a1a1a; 
            color: #e0e0e0; 
            line-height: 1.4;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 20px; 
            margin-bottom: 20px; 
            border-radius: 8px;
        }
        .section { 
            background: #2d2d2d; 
            margin: 20px 0; 
            padding: 20px; 
            border-radius: 8px; 
            border-left: 4px solid #667eea;
        }
        .ok { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #fbbf24; }
        .info { color: #60a5fa; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .metric { 
            background: #374151; 
            padding: 15px; 
            border-radius: 6px; 
            text-align: center;
        }
        .metric-value { font-size: 2rem; font-weight: bold; margin-bottom: 5px; }
        .metric-label { font-size: 0.9rem; opacity: 0.8; }
        pre { 
            background: #111; 
            padding: 15px; 
            border-radius: 4px; 
            overflow-x: auto; 
            font-size: 0.9rem;
        }
        .toggle-btn { 
            background: #4f46e5; 
            color: white; 
            border: none; 
            padding: 8px 16px; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 5px;
        }
        .collapsible { display: none; }
        .collapsible.show { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Diagnostic Développement - Portail Guldagil</h1>
            <p>Outil unifié de diagnostic pour le développement • Version 0.5 beta</p>
            <p><strong>⚠️ SUPPRIMER CE FICHIER EN PRODUCTION !</strong></p>
        </div>

        <!-- Métriques rapides -->
        <div class="section">
            <h2>📊 Métriques système</h2>
            <div class="grid">
                <div class="metric">
                    <div class="metric-value ok"><?= round(memory_get_usage() / 1024 / 1024, 2) ?>MB</div>
                    <div class="metric-label">Mémoire utilisée</div>
                </div>
                <div class="metric">
                    <div class="metric-value info"><?= PHP_VERSION ?></div>
                    <div class="metric-label">Version PHP</div>
                </div>
                <div class="metric">
                    <div class="metric-value warning"><?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?></div>
                    <div class="metric-label">Session</div>
                </div>
                <div class="metric">
                    <div class="metric-value ok"><?= date('H:i:s') ?></div>
                    <div class="metric-label">Heure serveur</div>
                </div>
            </div>
        </div>

        <?php
        // Configuration des tests
        $root_path = dirname(__DIR__);
        
        // 1. STRUCTURE FICHIERS
        echo '<div class="section">';
        echo '<h2>📁 Structure fichiers critiques</h2>';
        
        $critical_files = [
            '/config/config.php' => 'Configuration principale',
            '/config/version.php' => 'Informations version',
            '/config/database.php' => 'Configuration BDD',
            '/core/auth/AuthManager.php' => 'Gestionnaire authentification',
            '/public/index.php' => 'Page d\'accueil',
            '/public/auth/login.php' => 'Page de connexion',
            '/.htaccess' => 'Configuration Apache',
            '/public/.htaccess' => 'Configuration public'
        ];

        foreach ($critical_files as $file => $desc) {
            $path = $root_path . $file;
            $exists = file_exists($path);
            $readable = $exists ? is_readable($path) : false;
            $size = $exists ? filesize($path) : 0;
            
            $status_class = $exists ? ($readable ? 'ok' : 'warning') : 'error';
            $status_icon = $exists ? ($readable ? '✅' : '⚠️') : '❌';
            
            echo "<div class=\"{$status_class}\">";
            echo "{$status_icon} <strong>{$desc}</strong><br>";
            echo "&nbsp;&nbsp;&nbsp;Chemin: {$file}<br>";
            if ($exists) {
                echo "&nbsp;&nbsp;&nbsp;Taille: " . number_format($size) . " octets<br>";
                echo "&nbsp;&nbsp;&nbsp;Permissions: " . substr(sprintf('%o', fileperms($path)), -4);
            }
            echo "</div><br>";
        }
        echo '</div>';

        // 2. CONFIGURATION
        echo '<div class="section">';
        echo '<h2>⚙️ Configuration</h2>';
        
        try {
            define('ROOT_PATH', $root_path);
            require_once $root_path . '/config/config.php';
            echo '<div class="ok">✅ Configuration chargée avec succès</div>';
            
            // Test constantes importantes
            $constants = ['DEBUG', 'DB_HOST', 'DB_NAME', 'APP_VERSION', 'BUILD_NUMBER'];
            foreach ($constants as $const) {
                $defined = defined($const);
                $value = $defined ? constant($const) : 'Non défini';
                $class = $defined ? 'ok' : 'warning';
                echo "<div class=\"{$class}\">• {$const}: {$value}</div>";
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur chargement config: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo '</div>';

        // 3. BASE DE DONNÉES
        echo '<div class="section">';
        echo '<h2>🗄️ Base de données</h2>';
        
        try {
            if (defined('DB_HOST')) {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                    DB_USER, DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                echo '<div class="ok">✅ Connexion BDD réussie</div>';
                
                // Test tables critiques
                $tables_to_check = ['auth_users', 'auth_sessions'];
                foreach ($tables_to_check as $table) {
                    try {
                        $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                        echo "<div class=\"ok\">✅ Table {$table}: {$count} enregistrements</div>";
                    } catch (Exception $e) {
                        echo "<div class=\"warning\">⚠️ Table {$table}: " . $e->getMessage() . "</div>";
                    }
                }
                
            } else {
                echo '<div class="error">❌ Constantes DB non définies</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur BDD: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo '</div>';

        // 4. AUTHENTIFICATION
        echo '<div class="section">';
        echo '<h2>🔐 Système d\'authentification</h2>';
        
        try {
            if (file_exists($root_path . '/core/auth/AuthManager.php')) {
                require_once $root_path . '/core/auth/AuthManager.php';
                echo '<div class="ok">✅ AuthManager chargé</div>';
                
                if (class_exists('AuthManager')) {
                    echo '<div class="ok">✅ Classe AuthManager disponible</div>';
                    
                    // Test instanciation
                    $auth = new AuthManager();
                    echo '<div class="ok">✅ AuthManager instancié</div>';
                    
                    // État session
                    $is_auth = $auth->isAuthenticated();
                    $class = $is_auth ? 'ok' : 'info';
                    echo "<div class=\"{$class}\">• Utilisateur connecté: " . ($is_auth ? 'Oui' : 'Non') . "</div>";
                    
                } else {
                    echo '<div class="error">❌ Classe AuthManager non trouvée</div>';
                }
            } else {
                echo '<div class="error">❌ Fichier AuthManager.php manquant</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur auth: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo '</div>';

        // 5. MODULES
        echo '<div class="section">';
        echo '<h2>🧩 État des modules</h2>';
        
        $modules_dirs = ['auth', 'user', 'admin', 'adr', 'port', 'qualite'];
        foreach ($modules_dirs as $module) {
            $module_path = $root_path . '/public/' . $module;
            $exists = is_dir($module_path);
            $index_exists = file_exists($module_path . '/index.php');
            
            if ($exists) {
                $files_count = count(glob($module_path . '/*.php'));
                $status = $index_exists ? 'ok' : 'warning';
                $icon = $index_exists ? '✅' : '⚠️';
                echo "<div class=\"{$status}\">{$icon} Module {$module}: {$files_count} fichiers PHP</div>";
            } else {
                echo "<div class=\"error\">❌ Module {$module}: Dossier manquant</div>";
            }
        }
        echo '</div>';

        // 6. PERMISSIONS
        echo '<div class="section">';
        echo '<h2>🔒 Permissions</h2>';
        
        $dirs_to_check = [
            '/storage/logs' => 'Logs application',
            '/storage/cache' => 'Cache système',
            '/config' => 'Configuration',
            '/public/assets' => 'Assets statiques'
        ];

        foreach ($dirs_to_check as $dir => $desc) {
            $path = $root_path . $dir;
            if (is_dir($path)) {
                $writable = is_writable($path);
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $class = $writable ? 'ok' : 'warning';
                $icon = $writable ? '✅' : '⚠️';
                echo "<div class=\"{$class}\">{$icon} {$desc}: {$perms}</div>";
            } else {
                echo "<div class=\"error\">❌ {$desc}: Dossier manquant</div>";
            }
        }
        echo '</div>';

        // 7. LOGS D'ERREUR
        echo '<div class="section">';
        echo '<h2>📋 Logs récents</h2>';
        echo '<button class="toggle-btn" onclick="toggleSection(\'logs\')">Afficher/Masquer logs</button>';
        echo '<div id="logs" class="collapsible">';
        
        $log_paths = [
            '/var/log/apache2/error.log',
            '/var/log/nginx/error.log',
            $root_path . '/storage/logs/app.log'
        ];

        foreach ($log_paths as $log_path) {
            if (file_exists($log_path) && is_readable($log_path)) {
                echo "<h4>📄 " . basename($log_path) . "</h4>";
                $lines = file($log_path);
                if ($lines) {
                    $recent_lines = array_slice($lines, -10);
                    echo "<pre>" . htmlspecialchars(implode('', $recent_lines)) . "</pre>";
                } else {
                    echo "<div class=\"info\">Fichier vide</div>";
                }
                break; // Afficher seulement le premier log trouvé
            }
        }
        echo '</div>';
        echo '</div>';

        // 8. INFORMATIONS SYSTÈME
        echo '<div class="section">';
        echo '<h2>💻 Informations système</h2>';
        echo '<button class="toggle-btn" onclick="toggleSection(\'sysinfo\')">Afficher/Masquer détails</button>';
        echo '<div id="sysinfo" class="collapsible">';
        
        $sys_info = [
            'OS' => php_uname(),
            'Serveur Web' => $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu',
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Inconnu',
            'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu',
            'IP Client' => $_SERVER['REMOTE_ADDR'] ?? 'Inconnu',
            'Timezone' => date_default_timezone_get(),
            'Memory Limit' => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time') . 's',
            'Upload Max Size' => ini_get('upload_max_filesize')
        ];

        foreach ($sys_info as $key => $value) {
            echo "<div><strong>{$key}:</strong> " . htmlspecialchars($value) . "</div>";
        }
        echo '</div>';
        echo '</div>';

        // Footer avec temps d'exécution
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        ?>

        <div class="section">
            <h2>⚡ Performance</h2>
            <div class="ok">✅ Diagnostic exécuté en <?= $execution_time ?>ms</div>
            <div class="info">💾 Mémoire pic: <?= round(memory_get_peak_usage() / 1024 / 1024, 2) ?>MB</div>
            
            <h3>🗂️ Actions recommandées</h3>
            <ul>
                <li>Supprimer ce fichier avant mise en production</li>
                <li>Vérifier les permissions des dossiers storage/</li>
                <li>Configurer HTTPS pour la production</li>
                <li>Optimiser les assets CSS/JS</li>
                <li>Mettre en place la surveillance des logs</li>
            </ul>
        </div>
    </div>

    <script>
        function toggleSection(id) {
            const section = document.getElementById(id);
            section.classList.toggle('show');
        }
        
        // Auto-refresh toutes les 30 secondes si demandé
        if (new URLSearchParams(window.location.search).get('autorefresh') === '1') {
            setTimeout(() => window.location.reload(), 30000);
        }
        
        console.log('🔧 Diagnostic dev chargé - Guldagil v0.5');
    </script>
</body>
</html>

<?php
/**
 * Titre: Outil d'audit administrateur complet
 * Chemin: /public/admin/audit.php
 * Version: 0.5 beta + build auto
 */

// Authentification requise
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérification niveau administrateur
$user_role = $_SESSION['user']['role'] ?? 'user';
if (!in_array($user_role, ['admin', 'dev', 'super_admin'])) {
    http_response_code(403);
    die('🚫 Accès interdit - Privilèges administrateur requis');
}

// Mode debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
$start_time = microtime(true);

// Détection automatique des chemins - CORRIGÉ
$current_dir = __DIR__;
$possible_roots = [
    dirname(dirname($current_dir)),  // /public/admin -> racine
    dirname($current_dir),           // /admin -> public  
    $current_dir . '/../..',         // relatif double remontée
];

$root_path = null;
foreach ($possible_roots as $path) {
    if (file_exists($path . '/config/config.php')) {
        $root_path = realpath($path);
        break;
    }
}

if (!$root_path) {
    // Recherche étendue
    $search_paths = [
        '/home/sc1ruje0226/public_html',
        '/var/www/html',
        $_SERVER['DOCUMENT_ROOT'],
        dirname($_SERVER['DOCUMENT_ROOT'])
    ];
    
    foreach ($search_paths as $path) {
        if (file_exists($path . '/config/config.php')) {
            $root_path = $path;
            break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🔍 Audit Système - Admin Guldagil</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { 
            background: rgba(255,255,255,0.95); 
            color: #333; 
            padding: 30px; 
            margin-bottom: 20px; 
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .section { 
            background: rgba(255,255,255,0.95); 
            margin: 20px 0; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .metric { 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center;
            border-left: 4px solid #3b82f6;
        }
        .metric-value { font-size: 2.2rem; font-weight: bold; margin-bottom: 8px; }
        .metric-label { font-size: 0.9rem; color: #6b7280; }
        .ok { color: #059669; }
        .error { color: #dc2626; }
        .warning { color: #d97706; }
        .info { color: #2563eb; }
        .critical { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 6px; border-left: 4px solid #dc2626; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .status-item { 
            padding: 15px; 
            border-radius: 8px; 
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .toggle-btn { 
            background: #4f46e5; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 6px; 
            cursor: pointer; 
            margin: 5px;
            font-weight: 500;
        }
        .collapsible { display: none; }
        .collapsible.show { display: block; }
        pre { 
            background: #f3f4f6; 
            padding: 15px; 
            border-radius: 6px; 
            overflow-x: auto; 
            font-size: 0.85rem;
            border: 1px solid #d1d5db;
        }
        .breadcrumb { color: #6b7280; margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 8px; margin: 10px 0; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-warning { background: #fffbeb; border: 1px solid #fed7aa; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="breadcrumb">Admin > Outils > Audit Système</div>
            <h1>🔍 Audit Système Complet</h1>
            <p>Analyse détaillée de l'état du portail Guldagil • Version 0.5 beta</p>
            <p><strong>Utilisateur:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Inconnu') ?> 
               <strong>Rôle:</strong> <?= htmlspecialchars($user_role) ?></p>
        </div>

        <?php if (!$root_path): ?>
        <div class="alert alert-error">
            <h3>❌ Erreur critique : Chemin racine introuvable</h3>
            <p><strong>Répertoire courant:</strong> <?= htmlspecialchars($current_dir) ?></p>
            <p><strong>Chemins testés:</strong></p>
            <ul>
                <?php foreach ($possible_roots as $path): ?>
                <li><?= htmlspecialchars($path) ?> - <?= file_exists($path . '/config/config.php') ? 'Trouvé' : 'Manquant' ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Veuillez vérifier la structure des dossiers et placer ce fichier dans /public/admin/</p>
        </div>
        <?php 
        echo '</div></body></html>';
        exit;
        endif; 
        ?>

        <div class="alert alert-success">
            <strong>✅ Chemin racine détecté:</strong> <?= htmlspecialchars($root_path) ?>
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
                    <div class="metric-value <?= session_status() === PHP_SESSION_ACTIVE ? 'ok' : 'warning' ?>">
                        <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?>
                    </div>
                    <div class="metric-label">Session</div>
                </div>
                <div class="metric">
                    <div class="metric-value ok"><?= date('H:i:s') ?></div>
                    <div class="metric-label">Heure serveur</div>
                </div>
                <div class="metric">
                    <div class="metric-value info"><?= php_sapi_name() ?></div>
                    <div class="metric-label">SAPI PHP</div>
                </div>
                <div class="metric">
                    <div class="metric-value warning"><?= disk_free_space($root_path) ? round(disk_free_space($root_path) / 1024 / 1024 / 1024, 1) . 'GB' : 'N/A' ?></div>
                    <div class="metric-label">Espace disque libre</div>
                </div>
            </div>
        </div>

        <?php
        // 1. AUDIT FICHIERS CRITIQUES
        echo '<div class="section">';
        echo '<h2>📁 Audit fichiers critiques</h2>';
        
        $critical_files = [
            '/config/config.php' => ['desc' => 'Configuration principale', 'critical' => true],
            '/config/version.php' => ['desc' => 'Informations version', 'critical' => true],
            '/config/database.php' => ['desc' => 'Configuration BDD', 'critical' => false],
            '/config/auth_database.php' => ['desc' => 'Tables authentification', 'critical' => false],
            '/core/auth/AuthManager.php' => ['desc' => 'Gestionnaire auth', 'critical' => true],
            '/public/index.php' => ['desc' => 'Page d\'accueil', 'critical' => true],
            '/public/auth/login.php' => ['desc' => 'Page connexion', 'critical' => true],
            '/.htaccess' => ['desc' => 'Config Apache racine', 'critical' => true],
            '/public/.htaccess' => ['desc' => 'Config Apache public', 'critical' => false],
            '/storage/logs' => ['desc' => 'Dossier logs', 'critical' => false],
            '/storage/cache' => ['desc' => 'Dossier cache', 'critical' => false]
        ];

        $files_status = [];
        foreach ($critical_files as $file => $info) {
            $path = $root_path . $file;
            $is_dir = is_dir($path);
            $exists = $is_dir ? is_dir($path) : file_exists($path);
            $readable = $exists ? is_readable($path) : false;
            $writable = $exists ? is_writable($path) : false;
            $size = ($exists && !$is_dir) ? filesize($path) : 0;
            
            $status = 'ok';
            if (!$exists && $info['critical']) {
                $status = 'critical';
            } elseif (!$exists) {
                $status = 'warning';
            } elseif (!$readable) {
                $status = 'error';
            }
            
            $files_status[] = [
                'file' => $file,
                'desc' => $info['desc'],
                'critical' => $info['critical'],
                'exists' => $exists,
                'readable' => $readable,
                'writable' => $writable,
                'size' => $size,
                'status' => $status,
                'is_dir' => $is_dir,
                'perms' => $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A'
            ];
        }

        echo '<div class="status-grid">';
        foreach ($files_status as $file) {
            $icon = $file['status'] === 'ok' ? '✅' : ($file['status'] === 'critical' ? '🚨' : ($file['status'] === 'error' ? '❌' : '⚠️'));
            echo "<div class=\"status-item {$file['status']}\">";
            echo "<strong>{$icon} {$file['desc']}</strong><br>";
            echo "<small>{$file['file']}</small><br>";
            if ($file['exists']) {
                echo "Perms: {$file['perms']} | ";
                echo ($file['readable'] ? 'R' : '-') . ($file['writable'] ? 'W' : '-');
                if (!$file['is_dir'] && $file['size'] > 0) {
                    echo " | " . number_format($file['size']) . "o";
                }
            } else {
                echo "<span class=\"{$file['status']}\">MANQUANT</span>";
            }
            echo "</div>";
        }
        echo '</div>';
        echo '</div>';

        // 2. AUDIT CONFIGURATION
        echo '<div class="section">';
        echo '<h2>⚙️ Audit configuration</h2>';
        
        $config_loaded = false;
        $config_errors = [];
        
        try {
            if (!defined('ROOT_PATH')) {
                define('ROOT_PATH', $root_path);
            }
            require_once $root_path . '/config/config.php';
            $config_loaded = true;
            echo '<div class="alert alert-success">✅ Configuration chargée avec succès</div>';
            
        } catch (ParseError $e) {
            $config_errors[] = "Erreur syntaxe: " . $e->getMessage() . " ligne " . $e->getLine();
            echo '<div class="alert alert-error">❌ Erreur syntaxe config: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            $config_errors[] = "Erreur: " . $e->getMessage();
            echo '<div class="alert alert-error">❌ Erreur config: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        if ($config_loaded) {
            echo '<h3>Constantes définies</h3>';
            $constants_check = [
                'DEBUG' => ['required' => false, 'type' => 'boolean'],
                'DB_HOST' => ['required' => true, 'type' => 'string'],
                'DB_NAME' => ['required' => true, 'type' => 'string'],
                'DB_USER' => ['required' => true, 'type' => 'string'],
                'DB_PASS' => ['required' => true, 'type' => 'string', 'sensitive' => true],
                'APP_VERSION' => ['required' => false, 'type' => 'string'],
                'BUILD_NUMBER' => ['required' => false, 'type' => 'string'],
                'APP_NAME' => ['required' => false, 'type' => 'string'],
                'STORAGE_PATH' => ['required' => false, 'type' => 'string']
            ];
            
            echo '<div class="status-grid">';
            foreach ($constants_check as $const => $rules) {
                $defined = defined($const);
                $value = $defined ? constant($const) : null;
                
                if ($rules['sensitive'] ?? false) {
                    $display_value = $defined ? (empty($value) ? 'VIDE' : '***') : 'Non défini';
                } else {
                    $display_value = $defined ? htmlspecialchars((string)$value) : 'Non défini';
                }
                
                $status = 'ok';
                if (!$defined && $rules['required']) {
                    $status = 'error';
                } elseif (!$defined) {
                    $status = 'warning';
                }
                
                echo "<div class=\"status-item {$status}\">";
                echo "<strong>{$const}</strong><br>";
                echo "<small>{$display_value}</small>";
                echo "</div>";
            }
            echo '</div>';
        }
        echo '</div>';

        // 3. AUDIT BASE DE DONNÉES
        echo '<div class="section">';
        echo '<h2>🗄️ Audit base de données</h2>';
        
        $db_connected = false;
        $pdo = null;
        
        if ($config_loaded && defined('DB_HOST')) {
            try {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER, DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5
                    ]
                );
                $db_connected = true;
                echo '<div class="alert alert-success">✅ Connexion base de données réussie</div>';
                
                // Info serveur
                $server_info = $pdo->getAttribute(PDO::ATTR_SERVER_INFO);
                $server_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                echo "<p><strong>Serveur:</strong> MySQL {$server_version}</p>";
                
                // Tables critiques
                $critical_tables = [
                    'auth_users' => 'Utilisateurs système',
                    'auth_sessions' => 'Sessions actives',
                    'gul_xpo_rates' => 'Tarifs XPO',
                    'gul_heppner_rates' => 'Tarifs Heppner',
                    'gul_kn_rates' => 'Tarifs KN'
                ];
                
                echo '<h3>État des tables</h3>';
                echo '<div class="status-grid">';
                foreach ($critical_tables as $table => $desc) {
                    try {
                        $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                        echo "<div class=\"status-item ok\">";
                        echo "<strong>✅ {$desc}</strong><br>";
                        echo "<small>{$table}: {$count} enregistrements</small>";
                        echo "</div>";
                    } catch (Exception $e) {
                        echo "<div class=\"status-item error\">";
                        echo "<strong>❌ {$desc}</strong><br>";
                        echo "<small>{$table}: " . htmlspecialchars($e->getMessage()) . "</small>";
                        echo "</div>";
                    }
                }
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="alert alert-error">❌ Erreur connexion BDD: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            echo '<div class="alert alert-warning">⚠️ Configuration BDD non disponible</div>';
        }
        echo '</div>';

        // 4. AUDIT SÉCURITÉ
        echo '<div class="section">';
        echo '<h2>🔐 Audit sécurité</h2>';
        
        $security_checks = [
            'HTTPS' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'Headers sécurisés' => isset($_SERVER['HTTP_X_FORWARDED_PROTO']),
            'Session sécurisée' => session_get_cookie_params()['httponly'] ?? false,
            'Erreurs masquées' => ini_get('display_errors') === '0',
            'PHP à jour' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'Dossier uploads protégé' => !is_executable($root_path . '/storage/uploads'),
            'Config protégée' => !is_readable($root_path . '/config') // via web
        ];
        
        echo '<div class="status-grid">';
        foreach ($security_checks as $check => $status) {
            $class = $status ? 'ok' : 'warning';
            $icon = $status ? '✅' : '⚠️';
            echo "<div class=\"status-item {$class}\">";
            echo "<strong>{$icon} {$check}</strong><br>";
            echo "<small>" . ($status ? 'Conforme' : 'À vérifier') . "</small>";
            echo "</div>";
        }
        echo '</div>';

        // Fichiers sensibles
        echo '<h3>Fichiers sensibles exposés</h3>';
        $sensitive_files = ['.env', 'config.php', '.htpasswd', 'composer.json', 'package.json'];
        $exposed = [];
        
        foreach ($sensitive_files as $file) {
            $web_path = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $file;
            // Simulation test (en réalité il faudrait un check HTTP)
            $potentially_exposed = file_exists($root_path . '/public/' . $file);
            if ($potentially_exposed) {
                $exposed[] = $file;
            }
        }
        
        if (empty($exposed)) {
            echo '<div class="alert alert-success">✅ Aucun fichier sensible détecté dans /public</div>';
        } else {
            echo '<div class="alert alert-error">❌ Fichiers sensibles potentiellement exposés: ' . implode(', ', $exposed) . '</div>';
        }
        echo '</div>';

        // 5. AUDIT PERFORMANCE
        echo '<div class="section">';
        echo '<h2>⚡ Audit performance</h2>';
        
        $perf_metrics = [
            'Limite mémoire' => ini_get('memory_limit'),
            'Temps d\'exécution max' => ini_get('max_execution_time') . 's',
            'Taille upload max' => ini_get('upload_max_filesize'),
            'OPcache activé' => function_exists('opcache_get_status') && opcache_get_status() ? 'Oui' : 'Non',
            'Compression activée' => ini_get('zlib.output_compression') ? 'Oui' : 'Non'
        ];
        
        echo '<div class="grid">';
        foreach ($perf_metrics as $metric => $value) {
            echo "<div class=\"metric\">";
            echo "<div class=\"metric-value info\">{$value}</div>";
            echo "<div class=\"metric-label\">{$metric}</div>";
            echo "</div>";
        }
        echo '</div>';
        echo '</div>';

        // 6. LOGS ET ERREURS
        echo '<div class="section">';
        echo '<h2>📋 Logs et erreurs</h2>';
        echo '<button class="toggle-btn" onclick="toggleSection(\'logs\')">Afficher/Masquer logs récents</button>';
        echo '<div id="logs" class="collapsible">';
        
        $log_sources = [
            'Apache' => '/var/log/apache2/error.log',
            'Nginx' => '/var/log/nginx/error.log',
            'PHP' => ini_get('error_log'),
            'Application' => $root_path . '/storage/logs/app.log'
        ];

        foreach ($log_sources as $source => $log_path) {
            if ($log_path && file_exists($log_path) && is_readable($log_path)) {
                echo "<h4>📄 {$source}</h4>";
                $lines = file($log_path);
                if ($lines && count($lines) > 0) {
                    $recent_lines = array_slice($lines, -15);
                    echo "<pre>" . htmlspecialchars(implode('', $recent_lines)) . "</pre>";
                } else {
                    echo "<div class=\"info\">📝 Fichier vide ou récent</div>";
                }
                break; // Afficher seulement le premier trouvé
            }
        }
        echo '</div>';
        echo '</div>';

        // Footer avec résumé
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        $critical_issues = array_filter($files_status, fn($f) => $f['status'] === 'critical');
        $total_issues = array_filter($files_status, fn($f) => $f['status'] !== 'ok');
        ?>

        <div class="section">
            <h2>📊 Résumé de l'audit</h2>
            
            <div class="grid">
                <div class="metric">
                    <div class="metric-value <?= empty($critical_issues) ? 'ok' : 'error' ?>"><?= count($critical_issues) ?></div>
                    <div class="metric-label">Problèmes critiques</div>
                </div>
                <div class="metric">
                    <div class="metric-value <?= count($total_issues) < 3 ? 'ok' : 'warning' ?>"><?= count($total_issues) ?></div>
                    <div class="metric-label">Total avertissements</div>
                </div>
                <div class="metric">
                    <div class="metric-value ok"><?= $execution_time ?>ms</div>
                    <div class="metric-label">Temps d'audit</div>
                </div>
                <div class="metric">
                    <div class="metric-value info"><?= round(memory_get_peak_usage() / 1024 / 1024, 2) ?>MB</div>
                    <div class="metric-label">Mémoire pic</div>
                </div>
            </div>

            <?php if (!empty($critical_issues)): ?>
            <div class="alert alert-error">
                <h3>🚨 Actions critiques requises</h3>
                <ul>
                    <?php foreach ($critical_issues as $issue): ?>
                    <li><strong><?= htmlspecialchars($issue['desc']) ?></strong> - <?= htmlspecialchars($issue['file']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="alert alert-success">
                <h3>✅ Rapport généré le <?= date('d/m/Y à H:i:s') ?></h3>
                <p>Audit réalisé par <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></p>
                <p><a href="/admin/">← Retour administration</a> | <a href="?refresh=1">🔄 Actualiser l'audit</a></p>
            </div>
        </div>
    </div>

    <script>
        function toggleSection(id) {
            const section = document.getElementById(id);
            section.classList.toggle('show');
        }
        
        // Actualisation automatique si demandée
        const params = new URLSearchParams(window.location.search);
        if (params.get('auto') === '1') {
            setTimeout(() => window.location.reload(), 60000);
        }
    </script>
</body>
</html>

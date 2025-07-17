<?php
/**
 * Titre: Diagnostic permanent erreur 500 - LECTURE SEULE
 * Chemin: /public/diagnostic_500.php
 * Version: 0.5 beta + build auto
 * 
 * ⚠️ SCRIPT DE DIAGNOSTIC UNIQUEMENT - AUCUNE MODIFICATION
 * Objectif: Identifier les causes exactes d'erreur 500 sans rien changer
 */

// Configuration diagnostic
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Empêcher le cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');

// CSS intégré pour éviter dépendances
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🔍 Diagnostic Erreur 500 - Portail Guldagil</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace;
            margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333; line-height: 1.6;
        }
        .container { 
            max-width: 1200px; margin: 0 auto; background: white; 
            border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden;
        }
        .header { 
            background: linear-gradient(45deg, #1e3c72, #2a5298); color: white; 
            padding: 30px; text-align: center; position: relative;
        }
        .header::before {
            content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.1\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }
        .header h1 { margin: 0; font-size: 2.5em; position: relative; z-index: 1; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; position: relative; z-index: 1; }
        
        .section { 
            padding: 25px; border-bottom: 1px solid #f0f0f0; 
        }
        .section:last-child { border-bottom: none; }
        .section h2 { 
            margin: 0 0 20px 0; font-size: 1.4em; color: #2d3748;
            display: flex; align-items: center; gap: 10px;
        }
        
        .status-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 15px; margin: 15px 0; 
        }
        .status-item { 
            background: #f8f9fa; padding: 15px; border-radius: 8px; 
            border-left: 4px solid #6c757d; transition: all 0.3s ease;
        }
        .status-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .status-item.success { border-left-color: #28a745; background: #f8fff9; }
        .status-item.error { border-left-color: #dc3545; background: #fff8f8; }
        .status-item.warning { border-left-color: #ffc107; background: #fffef8; }
        .status-item.info { border-left-color: #007bff; background: #f8fbff; }
        
        .status-item h4 { 
            margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px; 
            font-size: 1.1em; color: #2d3748;
        }
        .status-item p { margin: 0; color: #4a5568; font-size: 0.9em; }
        .status-item code { 
            background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 3px; 
            font-family: "Courier New", monospace; font-size: 0.85em;
        }
        
        .critical-error { 
            background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; 
            padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;
        }
        .critical-error h3 { margin: 0 0 10px 0; font-size: 1.3em; }
        
        .log-viewer { 
            background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; 
            max-height: 300px; overflow-y: auto; font-family: "Courier New", monospace; 
            font-size: 0.85em; margin: 10px 0;
        }
        .log-line { padding: 2px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .log-line.error { color: #fed7d7; background: rgba(254, 107, 107, 0.2); }
        .log-line.warning { color: #fefcbf; background: rgba(255, 193, 7, 0.2); }
        
        .summary { 
            background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; 
            padding: 30px; text-align: center; margin: 0;
        }
        .summary h2 { color: white; margin: 0 0 15px 0; }
        .score { font-size: 3em; font-weight: bold; margin: 10px 0; }
        .score.excellent { color: #00ff88; }
        .score.good { color: #ffd700; }
        .score.poor { color: #ff6b6b; }
        
        .recommendations { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 15px; margin: 20px 0; 
        }
        .recommendation { 
            background: white; padding: 20px; border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 4px solid #007bff;
        }
        .recommendation h4 { margin: 0 0 10px 0; color: #2d3748; }
        .recommendation ul { margin: 0; padding-left: 20px; }
        .recommendation li { margin: 5px 0; color: #4a5568; }
        
        .timestamp { 
            text-align: center; padding: 15px; background: #f8f9fa; 
            color: #6c757d; font-size: 0.9em; margin: 0;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header h1 { font-size: 2em; }
            .status-grid, .recommendations { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">';

// Header
echo '<div class="header">
    <h1>🔍 DIAGNOSTIC ERREUR 500</h1>
    <p>Analyse complète du portail Guldagil - Version 0.5 beta</p>
    <p><strong>MODE LECTURE SEULE</strong> - Aucune modification effectuée</p>
</div>';

// Variables globales pour le diagnostic
$diagnostic_results = [];
$critical_errors = [];
$warnings = [];
$total_checks = 0;
$passed_checks = 0;

/**
 * Fonction d'ajout de résultat de diagnostic
 */
function addDiagnosticResult($category, $name, $status, $message, $details = null) {
    global $diagnostic_results, $critical_errors, $warnings, $total_checks, $passed_checks;
    
    $total_checks++;
    if ($status === 'success') $passed_checks++;
    
    $result = [
        'name' => $name,
        'status' => $status,
        'message' => $message,
        'details' => $details,
        'timestamp' => microtime(true)
    ];
    
    if (!isset($diagnostic_results[$category])) {
        $diagnostic_results[$category] = [];
    }
    $diagnostic_results[$category][] = $result;
    
    if ($status === 'error') {
        $critical_errors[] = $result;
    } elseif ($status === 'warning') {
        $warnings[] = $result;
    }
}

/**
 * Affichage d'une section de diagnostic
 */
function displayDiagnosticSection($title, $icon, $category) {
    global $diagnostic_results;
    
    echo "<div class='section'>";
    echo "<h2>$icon $title</h2>";
    
    if (isset($diagnostic_results[$category])) {
        echo "<div class='status-grid'>";
        foreach ($diagnostic_results[$category] as $result) {
            $status_class = $result['status'];
            $status_icon = [
                'success' => '✅',
                'error' => '❌', 
                'warning' => '⚠️',
                'info' => 'ℹ️'
            ][$result['status']] ?? 'ℹ️';
            
            echo "<div class='status-item $status_class'>";
            echo "<h4>$status_icon {$result['name']}</h4>";
            echo "<p>{$result['message']}</p>";
            
            if ($result['details']) {
                if (is_array($result['details'])) {
                    echo "<code>" . implode(' | ', $result['details']) . "</code>";
                } else {
                    echo "<code>{$result['details']}</code>";
                }
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p>Aucun test effectué dans cette catégorie.</p>";
    }
    
    echo "</div>";
}

// =====================================
// DIAGNOSTIC 1: STRUCTURE ET FICHIERS
// =====================================
$start_time = microtime(true);

// Détection ROOT_PATH
$current_dir = dirname(__FILE__);
$root_path = null;
$possible_roots = [
    dirname($current_dir),
    dirname(dirname($current_dir)),
    realpath($current_dir . '/..'),
    realpath($current_dir . '/../..'),
];

foreach ($possible_roots as $path) {
    if ($path && file_exists($path . '/config/config.php')) {
        $root_path = $path;
        addDiagnosticResult('structure', 'ROOT_PATH', 'success', 
            "Racine du projet détectée", $root_path);
        break;
    }
}

if (!$root_path) {
    addDiagnosticResult('structure', 'ROOT_PATH', 'error', 
        "Impossible de détecter la racine du projet", 
        "Fichier config/config.php introuvable");
    
    echo '<div class="critical-error">
        <h3>🚨 ERREUR CRITIQUE</h3>
        <p>La structure du projet est introuvable. Le fichier config/config.php n\'a pas été trouvé.</p>
        <p><strong>Cette erreur empêche le fonctionnement du portail.</strong></p>
    </div>';
} else {
    define('ROOT_PATH', $root_path);
}

// Vérification dossiers critiques
$critical_dirs = [
    '/config' => 'Configuration du portail',
    '/core' => 'Classes PHP principales', 
    '/public' => 'Fichiers publics accessibles',
    '/templates' => 'Templates HTML',
    '/storage' => 'Stockage données temporaires',
    '/storage/logs' => 'Fichiers de logs',
    '/storage/cache' => 'Cache applicatif'
];

foreach ($critical_dirs as $dir => $description) {
    if ($root_path) {
        $full_path = $root_path . $dir;
        if (is_dir($full_path)) {
            $writable = is_writable($full_path);
            $readable = is_readable($full_path);
            
            if ($readable && ($dir === '/storage/logs' || $dir === '/storage/cache' ? $writable : true)) {
                addDiagnosticResult('structure', basename($dir), 'success', 
                    $description, "Permissions OK");
            } else {
                addDiagnosticResult('structure', basename($dir), 'warning', 
                    $description, 
                    "Lecture: " . ($readable ? 'OK' : 'NON') . " | Écriture: " . ($writable ? 'OK' : 'NON'));
            }
        } else {
            addDiagnosticResult('structure', basename($dir), 'error', 
                $description, "Dossier manquant: $full_path");
        }
    }
}

displayDiagnosticSection('Structure et Dossiers', '📁', 'structure');

// =====================================
// DIAGNOSTIC 2: FICHIERS CRITIQUES
// =====================================

$critical_files = [
    '/config/config.php' => 'Configuration principale',
    '/config/version.php' => 'Informations de version',
    '/public/index.php' => 'Point d\'entrée principal',
    '/public/.htaccess' => 'Réécriture URLs Apache',
    '/templates/header.php' => 'En-tête des pages',
    '/templates/footer.php' => 'Pied de page',
    '/core/auth/AuthManager.php' => 'Gestionnaire authentification'
];

foreach ($critical_files as $file => $description) {
    if ($root_path) {
        $full_path = $root_path . $file;
        
        if (file_exists($full_path)) {
            $readable = is_readable($full_path);
            $size = filesize($full_path);
            $modified = date('Y-m-d H:i', filemtime($full_path));
            
            // Test de syntaxe PHP pour les fichiers .php
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $readable) {
                $syntax_output = [];
                $syntax_return = 0;
                exec("php -l " . escapeshellarg($full_path) . " 2>&1", $syntax_output, $syntax_return);
                
                if ($syntax_return === 0) {
                    addDiagnosticResult('files', basename($file), 'success', 
                        $description, "Syntaxe OK | $size bytes | $modified");
                } else {
                    addDiagnosticResult('files', basename($file), 'error', 
                        $description, "ERREUR SYNTAXE: " . implode(' ', $syntax_output));
                }
            } else {
                addDiagnosticResult('files', basename($file), 'success', 
                    $description, "$size bytes | $modified");
            }
        } else {
            addDiagnosticResult('files', basename($file), 'error', 
                $description, "Fichier manquant: $full_path");
        }
    }
}

displayDiagnosticSection('Fichiers Critiques', '📄', 'files');

// =====================================
// DIAGNOSTIC 3: CONFIGURATION
// =====================================

if ($root_path && file_exists($root_path . '/config/config.php')) {
    // Test d'inclusion sécurisé de la config
    ob_start();
    $config_error = null;
    try {
        include_once $root_path . '/config/config.php';
        addDiagnosticResult('config', 'Inclusion config', 'success', 
            "Configuration chargée sans erreur", null);
    } catch (Exception $e) {
        $config_error = $e->getMessage();
        addDiagnosticResult('config', 'Inclusion config', 'error', 
            "Erreur lors du chargement", $config_error);
    } catch (ParseError $e) {
        $config_error = $e->getMessage();
        addDiagnosticResult('config', 'Inclusion config', 'error', 
            "Erreur de syntaxe PHP", $config_error);
    }
    ob_end_clean();
    
    // Vérification des constantes requises
    $required_constants = [
        'ROOT_PATH' => 'Chemin racine du projet',
        'BASE_URL' => 'URL de base du portail',
        'APP_NAME' => 'Nom de l\'application',
        'DB_HOST' => 'Hôte base de données',
        'DB_NAME' => 'Nom base de données',
        'DB_USER' => 'Utilisateur BDD',
        'DB_PASS' => 'Mot de passe BDD'
    ];
    
    foreach ($required_constants as $constant => $description) {
        if (defined($constant)) {
            $value = constant($constant);
            $display_value = ($constant === 'DB_PASS') ? str_repeat('*', strlen($value)) : $value;
            addDiagnosticResult('config', $constant, 'success', 
                $description, $display_value);
        } else {
            addDiagnosticResult('config', $constant, 'warning', 
                $description, "Constante non définie");
        }
    }
} else {
    addDiagnosticResult('config', 'Configuration', 'error', 
        "Impossible de tester la configuration", "config.php manquant");
}

displayDiagnosticSection('Configuration PHP', '⚙️', 'config');

// =====================================
// DIAGNOSTIC 4: BASE DE DONNÉES
// =====================================

if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        addDiagnosticResult('database', 'Connexion MySQL', 'success', 
            "Connexion à la base de données réussie", DB_HOST . "/" . DB_NAME);
        
        // Test des tables critiques
        $critical_tables = [
            'auth_users' => 'Utilisateurs authentifiés',
            'auth_sessions' => 'Sessions actives'
        ];
        
        foreach ($critical_tables as $table => $description) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $result = $stmt->fetch();
                addDiagnosticResult('database', "Table $table", 'success', 
                    $description, "{$result['count']} enregistrements");
            } catch (PDOException $e) {
                addDiagnosticResult('database', "Table $table", 'warning', 
                    $description, "Table inaccessible: " . $e->getMessage());
            }
        }
        
        // Test version MySQL
        try {
            $stmt = $pdo->query("SELECT VERSION() as version");
            $result = $stmt->fetch();
            addDiagnosticResult('database', 'Version MySQL', 'info', 
                "Version du serveur de base de données", $result['version']);
        } catch (PDOException $e) {
            // Non critique
        }
        
    } catch (PDOException $e) {
        addDiagnosticResult('database', 'Connexion MySQL', 'error', 
            "Échec de connexion à la base de données", $e->getMessage());
    }
} else {
    addDiagnosticResult('database', 'Configuration BDD', 'warning', 
        "Paramètres de base de données incomplets", "Vérifier config.php");
}

displayDiagnosticSection('Base de Données', '🗄️', 'database');

// =====================================
// DIAGNOSTIC 5: TEMPLATES ET ASSETS
// =====================================

if ($root_path) {
    $template_files = [
        '/templates/header.php' => 'En-tête principal',
        '/templates/footer.php' => 'Pied de page principal'
    ];
    
    foreach ($template_files as $file => $description) {
        $full_path = $root_path . $file;
        if (file_exists($full_path)) {
            // Test d'inclusion des templates avec variables minimales
            ob_start();
            $template_error = null;
            try {
                // Variables requises par les templates
                $app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
                $current_module = 'diagnostic';
                $page_title = 'Test';
                $current_user = ['role' => 'guest'];
                $user_authenticated = false;
                
                include $full_path;
                addDiagnosticResult('templates', basename($file), 'success', 
                    $description, "Template inclus sans erreur");
            } catch (Exception $e) {
                addDiagnosticResult('templates', basename($file), 'error', 
                    $description, "Erreur: " . $e->getMessage());
            } catch (ParseError $e) {
                addDiagnosticResult('templates', basename($file), 'error', 
                    $description, "Erreur syntaxe: " . $e->getMessage());
            }
            ob_end_clean();
        } else {
            addDiagnosticResult('templates', basename($file), 'error', 
                $description, "Fichier manquant");
        }
    }
    
    // Vérification des assets CSS/JS critiques
    $critical_assets = [
        '/public/assets/css/portal.css' => 'CSS principal portail',
        '/public/assets/css/header.css' => 'CSS header',
        '/public/assets/css/footer.css' => 'CSS footer',
        '/templates/assets/css/header.css' => 'CSS header alternatif',
        '/templates/assets/css/footer.css' => 'CSS footer alternatif'
    ];
    
    foreach ($critical_assets as $asset => $description) {
        $full_path = $root_path . $asset;
        if (file_exists($full_path)) {
            $size = filesize($full_path);
            if ($size > 0) {
                addDiagnosticResult('templates', basename(dirname($asset)) . '/' . basename($asset), 'success', 
                    $description, "$size bytes");
            } else {
                addDiagnosticResult('templates', basename(dirname($asset)) . '/' . basename($asset), 'warning', 
                    $description, "Fichier vide");
            }
        } else {
            addDiagnosticResult('templates', basename(dirname($asset)) . '/' . basename($asset), 'info', 
                $description, "Fichier optionnel absent");
        }
    }
}

displayDiagnosticSection('Templates et Assets', '🎨', 'templates');

// =====================================
// DIAGNOSTIC 6: LOGS ET ERREURS
// =====================================

$log_files = [
    'PHP Error Log' => ini_get('error_log'),
    'Apache Error' => '/var/log/apache2/error.log',
    'App Logs' => $root_path ? $root_path . '/storage/logs/error.log' : null,
    'Apache Access' => '/var/log/apache2/access.log'
];

$recent_errors_found = [];

foreach ($log_files as $log_name => $log_path) {
    if ($log_path && file_exists($log_path) && is_readable($log_path)) {
        $size = filesize($log_path);
        $modified = date('Y-m-d H:i', filemtime($log_path));
        
        addDiagnosticResult('logs', $log_name, 'success', 
            "Log accessible", "$size bytes | Modifié: $modified");
        
        // Analyser les dernières lignes pour erreurs récentes
        $lines = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $recent_lines = array_slice($lines, -20); // 20 dernières lignes
            foreach ($recent_lines as $line) {
                if (stripos($line, 'fatal') !== false || 
                    stripos($line, 'parse error') !== false ||
                    stripos($line, 'syntax error') !== false) {
                    $recent_errors_found[] = [
                        'source' => $log_name,
                        'line' => $line,
                        'severity' => 'error'
                    ];
                } elseif (stripos($line, 'warning') !== false || 
                         stripos($line, 'notice') !== false) {
                    $recent_errors_found[] = [
                        'source' => $log_name,
                        'line' => $line,
                        'severity' => 'warning'
                    ];
                }
            }
        }
    } else {
        addDiagnosticResult('logs', $log_name, 'info', 
            "Log non accessible", $log_path ? "Chemin: $log_path" : "Chemin non défini");
    }
}

// Affichage des erreurs récentes trouvées
if (!empty($recent_errors_found)) {
    addDiagnosticResult('logs', 'Erreurs récentes', 'warning', 
        count($recent_errors_found) . " erreurs trouvées dans les logs", 
        "Voir détails ci-dessous");
}

displayDiagnosticSection('Logs et Monitoring', '📊', 'logs');

// Affichage des logs récents si erreurs trouvées
if (!empty($recent_errors_found)) {
    echo '<div class="section">';
    echo '<h2>🚨 Erreurs Récentes Détectées</h2>';
    echo '<div class="log-viewer">';
    
    foreach (array_slice($recent_errors_found, -10) as $error) {
        $class = $error['severity'];
        $timestamp = date('H:i:s');
        echo "<div class='log-line $class'>";
        echo "<strong>[{$error['source']}]</strong> " . htmlspecialchars($error['line']);
        echo "</div>";
    }
    
    echo '</div>';
    echo '</div>';
}

// =====================================
// DIAGNOSTIC 7: ENVIRONNEMENT SYSTÈME
// =====================================

$php_version = PHP_VERSION;
$required_php = '7.4.0';
$php_ok = version_compare($php_version, $required_php, '>=');

addDiagnosticResult('system', 'Version PHP', 
    $php_ok ? 'success' : 'warning', 
    "Version PHP du serveur", 
    "$php_version " . ($php_ok ? '(compatible)' : '(< 7.4)'));

// Extensions PHP requises
$required_extensions = [
    'pdo' => 'Accès base de données',
    'pdo_mysql' => 'Pilote MySQL', 
    'session' => 'Gestion sessions',
    'json' => 'Traitement JSON',
    'mbstring' => 'Chaînes multi-octets'
];

foreach ($required_extensions as $ext => $description) {
    if (extension_loaded($ext)) {
        addDiagnosticResult('system', "Extension $ext", 'success', 
            $description, "Chargée");
    } else {
        addDiagnosticResult('system', "Extension $ext", 'error', 
            $description, "MANQUANTE");
    }
}

// Configuration PHP importante
$php_settings = [
    'display_errors' => ini_get('display_errors'),
    'log_errors' => ini_get('log_errors'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
];

foreach ($php_settings as $setting => $value) {
    addDiagnosticResult('system', "php.$setting", 'info', 
        "Configuration PHP", $value);
}

displayDiagnosticSection('Environnement Système', '🖥️', 'system');

// =====================================
// RÉSUMÉ ET RECOMMANDATIONS
// =====================================

$diagnostic_duration = round((microtime(true) - $start_time) * 1000, 2);
$score_percentage = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100) : 0;

// Déterminer l'état global
$status_class = 'poor';
$status_message = 'ERREUR 500 PROBABLE';
$status_icon = '🔴';

if (count($critical_errors) === 0) {
    if ($score_percentage >= 90) {
        $status_class = 'excellent';
        $status_message = 'PORTAIL FONCTIONNEL';
        $status_icon = '🟢';
    } elseif ($score_percentage >= 75) {
        $status_class = 'good';
        $status_message = 'PROBLÈMES MINEURS';
        $status_icon = '🟡';
    } else {
        $status_class = 'poor';
        $status_message = 'PROBLÈMES À CORRIGER';
        $status_icon = '🟠';
    }
} else {
    $status_class = 'poor';
    $status_message = 'ERREURS CRITIQUES';
    $status_icon = '🔴';
}

echo '<div class="summary">';
echo '<h2>📊 Résumé du Diagnostic</h2>';
echo "<div class='score $status_class'>$status_icon $score_percentage%</div>";
echo "<h3>$status_message</h3>";
echo "<p>$passed_checks/$total_checks vérifications réussies</p>";
echo "<p>Diagnostic effectué en {$diagnostic_duration}ms</p>";

if (count($critical_errors) > 0) {
    echo "<p><strong>🚨 " . count($critical_errors) . " erreur(s) critique(s) détectée(s)</strong></p>";
}
if (count($warnings) > 0) {
    echo "<p><strong>⚠️ " . count($warnings) . " avertissement(s)</strong></p>";
}

echo '</div>';

// =====================================
// RECOMMANDATIONS SPÉCIFIQUES
// =====================================

echo '<div class="section">';
echo '<h2>💡 Recommandations de Correction</h2>';

echo '<div class="recommendations">';

// Recommandations basées sur les erreurs critiques
if (!empty($critical_errors)) {
    echo '<div class="recommendation">';
    echo '<h4>🚨 Erreurs Critiques à Corriger</h4>';
    echo '<ul>';
    
    foreach ($critical_errors as $error) {
        echo '<li><strong>' . htmlspecialchars($error['name']) . ':</strong> ' . htmlspecialchars($error['message']);
        if ($error['details']) {
            echo '<br><code>' . htmlspecialchars($error['details']) . '</code>';
        }
        echo '</li>';
    }
    
    echo '</ul>';
    echo '<p><strong>Action prioritaire :</strong> Ces erreurs empêchent le fonctionnement normal du portail.</p>';
    echo '</div>';
}

// Recommandations de structure
$structure_issues = array_filter($diagnostic_results['structure'] ?? [], function($r) { 
    return $r['status'] === 'error'; 
});

if (!empty($structure_issues)) {
    echo '<div class="recommendation">';
    echo '<h4>📁 Structure du Projet</h4>';
    echo '<ul>';
    echo '<li>Vérifier que tous les dossiers requis existent</li>';
    echo '<li>Corriger les permissions des dossiers storage/ (755)</li>';
    echo '<li>S\'assurer que le serveur web peut accéder aux fichiers</li>';
    echo '</ul>';
    echo '<p><strong>Commandes suggérées :</strong></p>';
    echo '<pre>chmod 755 /chemin/vers/storage/logs/<br>chmod 755 /chemin/vers/storage/cache/</pre>';
    echo '</div>';
}

// Recommandations de configuration
$config_issues = array_filter($diagnostic_results['config'] ?? [], function($r) { 
    return $r['status'] === 'error' || $r['status'] === 'warning'; 
});

if (!empty($config_issues)) {
    echo '<div class="recommendation">';
    echo '<h4>⚙️ Configuration</h4>';
    echo '<ul>';
    echo '<li>Vérifier la syntaxe PHP du fichier config/config.php</li>';
    echo '<li>Définir toutes les constantes requises</li>';
    echo '<li>Vérifier les paramètres de base de données</li>';
    echo '</ul>';
    echo '<p><strong>Fichier de configuration minimal :</strong></p>';
    echo '<pre>&lt;?php<br>define(\'ROOT_PATH\', dirname(__DIR__));<br>define(\'BASE_URL\', \'http://\' . $_SERVER[\'HTTP_HOST\']);<br>define(\'DB_HOST\', \'localhost\');<br>// ... autres constantes</pre>';
    echo '</div>';
}

// Recommandations de base de données
$db_issues = array_filter($diagnostic_results['database'] ?? [], function($r) { 
    return $r['status'] === 'error'; 
});

if (!empty($db_issues)) {
    echo '<div class="recommendation">';
    echo '<h4>🗄️ Base de Données</h4>';
    echo '<ul>';
    echo '<li>Vérifier que MySQL/MariaDB est démarré</li>';
    echo '<li>Contrôler les paramètres de connexion</li>';
    echo '<li>Vérifier que la base de données existe</li>';
    echo '<li>S\'assurer que l\'utilisateur a les droits nécessaires</li>';
    echo '</ul>';
    echo '<p><strong>Test de connexion :</strong></p>';
    echo '<pre>mysql -h HOST -u USER -p DATABASE</pre>';
    echo '</div>';
}

// Recommandations de templates
$template_issues = array_filter($diagnostic_results['templates'] ?? [], function($r) { 
    return $r['status'] === 'error'; 
});

if (!empty($template_issues)) {
    echo '<div class="recommendation">';
    echo '<h4>🎨 Templates et Assets</h4>';
    echo '<ul>';
    echo '<li>Vérifier la syntaxe PHP des templates</li>';
    echo '<li>S\'assurer que les variables requises sont définies</li>';
    echo '<li>Contrôler les chemins vers les fichiers CSS/JS</li>';
    echo '</ul>';
    echo '<p><strong>Variables minimales pour templates :</strong></p>';
    echo '<pre>$app_name, $current_module, $page_title, $current_user</pre>';
    echo '</div>';
}

// Recommandations générales si peu d'erreurs
if (count($critical_errors) === 0 && $score_percentage >= 75) {
    echo '<div class="recommendation">';
    echo '<h4>✅ Maintenance Préventive</h4>';
    echo '<ul>';
    echo '<li>Surveiller régulièrement les logs d\'erreur</li>';
    echo '<li>Effectuer des sauvegardes régulières</li>';
    echo '<li>Maintenir PHP et MySQL à jour</li>';
    echo '<li>Tester les fonctionnalités après modifications</li>';
    echo '</ul>';
    echo '<p>Le portail semble fonctionnel. Continuer la surveillance.</p>';
    echo '</div>';
}

echo '</div>'; // recommendations
echo '</div>'; // section

// =====================================
// ACTIONS RAPIDES
// =====================================

echo '<div class="section">';
echo '<h2>🔧 Actions Rapides</h2>';

echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

// Bouton relancer diagnostic
echo '<a href="?' . time() . '" style="display: block; background: linear-gradient(45deg, #007bff, #0056b3); color: white; padding: 15px; text-decoration: none; border-radius: 8px; text-align: center; transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">
    🔄 Relancer Diagnostic
</a>';

// Bouton voir logs (si système admin existe)
if ($root_path && file_exists($root_path . '/public/admin/logs.php')) {
    echo '<a href="/admin/logs.php" target="_blank" style="display: block; background: linear-gradient(45deg, #28a745, #1e7e34); color: white; padding: 15px; text-decoration: none; border-radius: 8px; text-align: center; transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">
        📊 Voir Logs Système
    </a>';
}

// Bouton scanner complet (si existe)
if ($root_path && file_exists($root_path . '/public/admin/scanner.php')) {
    echo '<a href="/admin/scanner.php" target="_blank" style="display: block; background: linear-gradient(45deg, #ffc107, #e0a800); color: white; padding: 15px; text-decoration: none; border-radius: 8px; text-align: center; transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">
        🔍 Scanner Complet
    </a>';
}

// Bouton tester portail
echo '<a href="/" target="_blank" style="display: block; background: linear-gradient(45deg, #17a2b8, #117a8b); color: white; padding: 15px; text-decoration: none; border-radius: 8px; text-align: center; transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">
    🏠 Tester Portail
</a>';

echo '</div>';

// Informations techniques pour développeurs
echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">';
echo '<h4>📋 Informations Techniques</h4>';
echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; font-family: monospace; font-size: 0.9em;">';

echo '<div><strong>ROOT_PATH:</strong><br>' . ($root_path ?? 'Non détecté') . '</div>';
echo '<div><strong>PHP Version:</strong><br>' . PHP_VERSION . '</div>';
echo '<div><strong>Server Software:</strong><br>' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible') . '</div>';
echo '<div><strong>Document Root:</strong><br>' . ($_SERVER['DOCUMENT_ROOT'] ?? 'Non disponible') . '</div>';
echo '<div><strong>Memory Usage:</strong><br>' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB</div>';
echo '<div><strong>Peak Memory:</strong><br>' . round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB</div>';

echo '</div>';
echo '</div>';

echo '</div>'; // section

// =====================================
// GUIDE DE RÉSOLUTION
// =====================================

echo '<div class="section">';
echo '<h2>📖 Guide de Résolution par Type d\'Erreur</h2>';

echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">';

// Guide erreur 500 générale
echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px;">';
echo '<h4>🔴 Erreur 500 - Serveur</h4>';
echo '<p><strong>Causes fréquentes :</strong></p>';
echo '<ul>';
echo '<li>Erreur de syntaxe PHP</li>';
echo '<li>Fichier de configuration manquant</li>';
echo '<li>Problème de permissions</li>';
echo '<li>Connexion base de données échouée</li>';
echo '</ul>';
echo '<p><strong>Première action :</strong> Vérifier les logs Apache et PHP</p>';
echo '</div>';

// Guide erreur configuration
echo '<div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 6px;">';
echo '<h4>⚙️ Erreur Configuration</h4>';
echo '<p><strong>Fichiers à vérifier :</strong></p>';
echo '<ul>';
echo '<li>config/config.php (syntaxe et constantes)</li>';
echo '<li>.htaccess (règles de réécriture)</li>';
echo '<li>public/index.php (point d\'entrée)</li>';
echo '</ul>';
echo '<p><strong>Test :</strong> <code>php -l fichier.php</code></p>';
echo '</div>';

// Guide erreur base de données
echo '<div style="background: #ffe7e7; border: 1px solid #ffb3b3; padding: 15px; border-radius: 6px;">';
echo '<h4>🗄️ Erreur Base de Données</h4>';
echo '<p><strong>Vérifications :</strong></p>';
echo '<ul>';
echo '<li>Service MySQL démarré</li>';
echo '<li>Paramètres de connexion corrects</li>';
echo '<li>Base de données existe</li>';
echo '<li>Droits utilisateur suffisants</li>';
echo '</ul>';
echo '<p><strong>Test :</strong> <code>mysql -h host -u user -p</code></p>';
echo '</div>';

// Guide erreur templates
echo '<div style="background: #f0f9ff; border: 1px solid #c7d2fe; padding: 15px; border-radius: 6px;">';
echo '<h4>🎨 Erreur Templates</h4>';
echo '<p><strong>Problèmes courants :</strong></p>';
echo '<ul>';
echo '<li>Variables non définies</li>';
echo '<li>Inclusion de fichiers manquants</li>';
echo '<li>Chemins CSS/JS incorrects</li>';
echo '</ul>';
echo '<p><strong>Solution :</strong> Définir toutes les variables avant inclusion</p>';
echo '</div>';

echo '</div>';
echo '</div>';

// =====================================
// FOOTER TECHNIQUE
// =====================================

echo '<div class="timestamp">';
echo '<p><strong>Diagnostic terminé le :</strong> ' . date('Y-m-d H:i:s') . '</p>';
echo '<p><strong>Durée d\'exécution :</strong> ' . $diagnostic_duration . ' ms</p>';
echo '<p><strong>Version script :</strong> 0.5 beta + build auto</p>';
echo '<p><strong>Mode :</strong> LECTURE SEULE - Aucune modification effectuée</p>';

if (count($critical_errors) > 0) {
    echo '<p style="color: #dc3545; font-weight: bold;">⚠️ Des erreurs critiques ont été détectées. Le portail peut ne pas fonctionner correctement.</p>';
} elseif (count($warnings) > 0) {
    echo '<p style="color: #ffc107; font-weight: bold;">⚠️ Des avertissements ont été émis. Surveillance recommandée.</p>';
} else {
    echo '<p style="color: #28a745; font-weight: bold;">✅ Aucune erreur critique détectée. Le portail devrait fonctionner normalement.</p>';
}

echo '</div>';

echo '</div></body></html>';

// =====================================
// EXPORT JSON (optionnel pour API)
// =====================================

// Si demande export JSON
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'duration_ms' => $diagnostic_duration,
        'total_checks' => $total_checks,
        'passed_checks' => $passed_checks,
        'score_percentage' => $score_percentage,
        'status' => $status_message,
        'critical_errors_count' => count($critical_errors),
        'warnings_count' => count($warnings),
        'results' => $diagnostic_results,
        'critical_errors' => $critical_errors,
        'warnings' => $warnings,
        'system_info' => [
            'php_version' => PHP_VERSION,
            'root_path' => $root_path,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'memory_usage_mb' => round(memory_get_usage() / 1024 / 1024, 2)
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

?>

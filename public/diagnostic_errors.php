<?php
/**
 * Titre: Page de diagnostic des erreurs et warnings PHP
 * Chemin: /public/diagnostic_errors.php
 * Version: 0.5 beta + build auto
 */

// Protection et configuration
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Activer affichage temporaire pour diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Chargement config si disponible
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
}
if (file_exists(ROOT_PATH . '/config/version.php')) {
    require_once ROOT_PATH . '/config/version.php';
}

// Variables pour tracking
$errors_found = [];
$warnings_found = [];
$suggestions = [];

// Fonction pour capturer les erreurs
function captureError($severity, $message, $file, $line) {
    global $errors_found, $warnings_found;
    
    $error_info = [
        'severity' => $severity,
        'message' => $message,
        'file' => basename($file),
        'line' => $line,
        'full_file' => $file
    ];
    
    if ($severity === E_ERROR || $severity === E_PARSE || $severity === E_CORE_ERROR) {
        $errors_found[] = $error_info;
    } else {
        $warnings_found[] = $error_info;
    }
    
    return true;
}

set_error_handler('captureError');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🔧 Diagnostic Erreurs - Portail Guldagil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f8f9fa;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #007cba, #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .content {
            padding: 2rem;
        }
        
        .section {
            background: white;
            margin: 1.5rem 0;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #007cba;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .section h2 {
            margin: 0 0 1rem 0;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-ok { color: #059669; }
        .status-warning { color: #d97706; }
        .status-error { color: #dc2626; }
        .status-info { color: #2563eb; }
        
        .test-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #e5e7eb;
        }
        
        .test-item.success {
            border-left-color: #059669;
            background: #f0fdf4;
        }
        
        .test-item.warning {
            border-left-color: #d97706;
            background: #fffbeb;
        }
        
        .test-item.error {
            border-left-color: #dc2626;
            background: #fef2f2;
        }
        
        .error-list {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .warning-list {
            background: #fffbeb;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .error-item, .warning-item {
            margin: 0.5rem 0;
            padding: 0.5rem;
            background: rgba(255,255,255,0.5);
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .suggestions {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .code-block {
            background: #1f2937;
            color: #f9fafb;
            padding: 1rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        @media (max-width: 768px) {
            .test-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🔧 Diagnostic des Erreurs PHP</h1>
            <p>Analyse complète du portail Guldagil - <?= date('Y-m-d H:i:s') ?></p>
        </header>
        
        <div class="content">
            <!-- Test de base -->
            <div class="section">
                <h2>📋 Tests de base</h2>
                
                <div class="test-grid">
                    <!-- Test ROOT_PATH -->
                    <div class="test-item <?= defined('ROOT_PATH') ? 'success' : 'error' ?>">
                        <span><?= defined('ROOT_PATH') ? '✅' : '❌' ?></span>
                        <span>ROOT_PATH défini</span>
                    </div>
                    
                    <!-- Test config -->
                    <div class="test-item <?= file_exists(ROOT_PATH . '/config/config.php') ? 'success' : 'error' ?>">
                        <span><?= file_exists(ROOT_PATH . '/config/config.php') ? '✅' : '❌' ?></span>
                        <span>Configuration disponible</span>
                    </div>
                    
                    <!-- Test AuthManager -->
                    <div class="test-item <?= file_exists(ROOT_PATH . '/core/auth/AuthManager.php') ? 'success' : 'warning' ?>">
                        <span><?= file_exists(ROOT_PATH . '/core/auth/AuthManager.php') ? '✅' : '⚠️' ?></span>
                        <span>AuthManager disponible</span>
                    </div>
                    
                    <!-- Test templates -->
                    <div class="test-item <?= file_exists(ROOT_PATH . '/templates/header.php') ? 'success' : 'error' ?>">
                        <span><?= file_exists(ROOT_PATH . '/templates/header.php') ? '✅' : '❌' ?></span>
                        <span>Templates disponibles</span>
                    </div>
                </div>
            </div>
            
            <!-- Test des fichiers critiques -->
            <div class="section">
                <h2>📁 Test des fichiers critiques</h2>
                
                <?php
                $critical_files = [
                    '/public/index.php' => 'Page d\'accueil principale',
                    '/templates/header.php' => 'Template header',
                    '/templates/footer.php' => 'Template footer',
                    '/config/config.php' => 'Configuration principale',
                    '/config/version.php' => 'Informations version',
                    '/core/auth/AuthManager.php' => 'Gestionnaire authentification'
                ];
                
                echo '<div class="test-grid">';
                foreach ($critical_files as $file => $description) {
                    $full_path = ROOT_PATH . $file;
                    $exists = file_exists($full_path);
                    $readable = $exists ? is_readable($full_path) : false;
                    
                    $status = $exists && $readable ? 'success' : 'error';
                    $icon = $exists && $readable ? '✅' : '❌';
                    
                    echo '<div class="test-item ' . $status . '">';
                    echo '<span>' . $icon . '</span>';
                    echo '<div>';
                    echo '<strong>' . $description . '</strong><br>';
                    echo '<small>' . $file . '</small>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                ?>
            </div>
            
            <!-- Test inclusion des fichiers -->
            <div class="section">
                <h2>🔄 Test d'inclusion des fichiers</h2>
                
                <?php
                $files_to_test = [
                    ROOT_PATH . '/public/index.php' => 'index.php',
                    ROOT_PATH . '/templates/header.php' => 'header.php',
                    ROOT_PATH . '/config/config.php' => 'config.php'
                ];
                
                foreach ($files_to_test as $file_path => $file_name) {
                    echo '<h3>Test ' . $file_name . '</h3>';
                    
                    if (!file_exists($file_path)) {
                        echo '<div class="error-list">';
                        echo '<div class="error-item">❌ Fichier non trouvé: ' . $file_path . '</div>';
                        echo '</div>';
                        continue;
                    }
                    
                    // Test syntaxe PHP
                    $output = [];
                    $return_var = 0;
                    exec('php -l ' . escapeshellarg($file_path) . ' 2>&1', $output, $return_var);
                    
                    if ($return_var === 0) {
                        echo '<div class="test-item success">';
                        echo '<span>✅</span>';
                        echo '<span>Syntaxe PHP correcte</span>';
                        echo '</div>';
                    } else {
                        echo '<div class="error-list">';
                        echo '<div class="error-item">❌ Erreur syntaxe PHP:</div>';
                        foreach ($output as $line) {
                            echo '<div class="error-item">' . htmlspecialchars($line) . '</div>';
                        }
                        echo '</div>';
                    }
                    
                    // Test inclusion
                    if ($file_name !== 'index.php') { // Éviter l'inclusion de index qui redirigerait
                        ob_start();
                        $include_error = false;
                        
                        try {
                            // Variables minimales pour éviter les erreurs dans header
                            $page_title = 'Test';
                            $page_subtitle = 'Test';
                            $current_module = 'test';
                            $user_authenticated = false;
                            $current_user = null;
                            
                            if ($file_name === 'config.php') {
                                include $file_path;
                            } else {
                                // Pour header.php, test minimal
                                if ($file_name === 'header.php') {
                                    // Test basique d'inclusion
                                    $header_content = file_get_contents($file_path);
                                    if (strpos($header_content, '<?php') !== false) {
                                        echo '<div class="test-item success">';
                                        echo '<span>✅</span>';
                                        echo '<span>Fichier PHP valide (contient du PHP)</span>';
                                        echo '</div>';
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $include_error = true;
                            echo '<div class="error-list">';
                            echo '<div class="error-item">❌ Erreur inclusion: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            echo '</div>';
                        } catch (ParseError $e) {
                            $include_error = true;
                            echo '<div class="error-list">';
                            echo '<div class="error-item">❌ Erreur parse: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            echo '</div>';
                        }
                        
                        $output_content = ob_get_clean();
                        
                        if (!$include_error && empty($output_content)) {
                            echo '<div class="test-item success">';
                            echo '<span>✅</span>';
                            echo '<span>Inclusion réussie sans erreur</span>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
            
            <!-- Erreurs capturées -->
            <?php if (!empty($errors_found)): ?>
            <div class="section">
                <h2>❌ Erreurs PHP détectées</h2>
                <div class="error-list">
                    <?php foreach ($errors_found as $error): ?>
                    <div class="error-item">
                        <strong>Erreur:</strong> <?= htmlspecialchars($error['message']) ?><br>
                        <strong>Fichier:</strong> <?= htmlspecialchars($error['file']) ?> 
                        <strong>Ligne:</strong> <?= $error['line'] ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Warnings capturés -->
            <?php if (!empty($warnings_found)): ?>
            <div class="section">
                <h2>⚠️ Warnings PHP détectés</h2>
                <div class="warning-list">
                    <?php foreach ($warnings_found as $warning): ?>
                    <div class="warning-item">
                        <strong>Warning:</strong> <?= htmlspecialchars($warning['message']) ?><br>
                        <strong>Fichier:</strong> <?= htmlspecialchars($warning['file']) ?> 
                        <strong>Ligne:</strong> <?= $warning['line'] ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Configuration PHP -->
            <div class="section">
                <h2>⚙️ Configuration PHP</h2>
                
                <div class="test-grid">
                    <div class="test-item <?= ini_get('display_errors') ? 'warning' : 'success' ?>">
                        <span><?= ini_get('display_errors') ? '⚠️' : '✅' ?></span>
                        <span>Display errors: <?= ini_get('display_errors') ? 'ON' : 'OFF' ?></span>
                    </div>
                    
                    <div class="test-item <?= ini_get('log_errors') ? 'success' : 'warning' ?>">
                        <span><?= ini_get('log_errors') ? '✅' : '⚠️' ?></span>
                        <span>Log errors: <?= ini_get('log_errors') ? 'ON' : 'OFF' ?></span>
                    </div>
                    
                    <div class="test-item success">
                        <span>ℹ️</span>
                        <span>PHP Version: <?= PHP_VERSION ?></span>
                    </div>
                    
                    <div class="test-item success">
                        <span>ℹ️</span>
                        <span>Memory limit: <?= ini_get('memory_limit') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Recommandations -->
            <div class="section">
                <h2>💡 Recommandations de correction</h2>
                
                <div class="suggestions">
                    <h3>🔧 Actions prioritaires :</h3>
                    <ol>
                        <li><strong>Définir ROOT_PATH en premier</strong> dans tous les fichiers PHP</li>
                        <li><strong>Vérifier variables obligatoires</strong> avant utilisation (isset/null coalescing)</li>
                        <li><strong>Désactiver display_errors</strong> en production</li>
                        <li><strong>Activer log_errors</strong> pour traçabilité</li>
                        <li><strong>Implémenter gestion d'erreur robuste</strong></li>
                    </ol>
                    
                    <h3>📝 Code de correction type :</h3>
                    <div class="code-block">// En début de fichier PHP
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Pour variables optionnelles
$page_title = $page_title ?? 'Titre par défaut';
$current_user = $current_user ?? ['username' => 'Invité'];

// Configuration erreurs production
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ini_set('log_errors', '1');</div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="action-buttons">
                <a href="/index.php" class="btn btn-primary">🏠 Retour accueil</a>
                <a href="<?= $_SERVER['REQUEST_URI'] ?>" class="btn btn-secondary">🔄 Relancer diagnostic</a>
                <button onclick="window.print()" class="btn btn-secondary">🖨️ Imprimer rapport</button>
            </div>
            
            <!-- Footer diagnostic -->
            <div class="section">
                <p><strong>🔍 Diagnostic terminé le <?= date('Y-m-d H:i:s') ?></strong></p>
                <p>Version portail: <?= defined('APP_VERSION') ? APP_VERSION : 'N/A' ?> | 
                   Build: <?= defined('BUILD_NUMBER') ? BUILD_NUMBER : 'N/A' ?></p>
                <p><em>⚠️ Supprimez ce fichier après correction des erreurs pour des raisons de sécurité</em></p>
            </div>
        </div>
    </div>
</body>
</html>

<?php
/**
 * Titre: Script de diagnostic complet
 * Chemin: /public/diagnostic.php
 * Version: 0.5 beta + build auto
 * Usage: À placer temporairement dans public/ pour tester
 */

// Configuration d'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnostic Portail Guldagil</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
        }
        .test-section {
            margin-bottom: 32px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }
        .test-section h2 {
            margin: 0 0 16px 0;
            color: #1e293b;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .test-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .test-item:last-child {
            border-bottom: none;
        }
        .status {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        .status.ok {
            background: #dcfce7;
            color: #166534;
        }
        .status.error {
            background: #fef2f2;
            color: #991b1b;
        }
        .status.warning {
            background: #fefce8;
            color: #a16207;
        }
        .test-label {
            flex: 1;
            font-weight: 500;
        }
        .test-details {
            font-size: 0.875rem;
            color: #64748b;
        }
        .error-details {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 12px;
            margin-top: 8px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 0.875rem;
            color: #991b1b;
        }
        .code {
            font-family: 'SF Mono', Monaco, monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .summary {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
        }
        .summary h3 {
            margin: 0 0 12px 0;
            color: #0c4a6e;
        }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #1e40af;
            color: white;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnostic Portail Guldagil</h1>
            <p>Vérification complète de l'environnement et des dépendances</p>
        </div>
        
        <div class="content">
            <?php
            $tests = [];
            $errors = [];
            $warnings = [];
            
            // Test 1: PHP et environnement
            echo '<div class="test-section">';
            echo '<h2>🐘 Environnement PHP</h2>';
            
            $php_version = PHP_VERSION;
            $status = version_compare($php_version, '7.4.0', '>=') ? 'ok' : 'error';
            echo '<div class="test-item">';
            echo '<span class="status ' . $status . '">' . ($status === 'ok' ? '✅ OK' : '❌ ERREUR') . '</span>';
            echo '<span class="test-label">Version PHP</span>';
            echo '<span class="test-details">' . $php_version . '</span>';
            echo '</div>';
            
            $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
            foreach ($extensions as $ext) {
                $loaded = extension_loaded($ext);
                echo '<div class="test-item">';
                echo '<span class="status ' . ($loaded ? 'ok' : 'error') . '">' . ($loaded ? '✅ OK' : '❌ MANQUANT') . '</span>';
                echo '<span class="test-label">Extension ' . $ext . '</span>';
                echo '</div>';
                if (!$loaded) $errors[] = "Extension PHP manquante: $ext";
            }
            echo '</div>';
            
            // Test 2: Fichiers de configuration
            echo '<div class="test-section">';
            echo '<h2>⚙️ Configuration</h2>';
            
            $config_files = [
                'config.php' => __DIR__ . '/../config/config.php',
                'version.php' => __DIR__ . '/../config/version.php'
            ];
            
            foreach ($config_files as $name => $path) {
                $exists = file_exists($path);
                echo '<div class="test-item">';
                echo '<span class="status ' . ($exists ? 'ok' : 'error') . '">' . ($exists ? '✅ OK' : '❌ MANQUANT') . '</span>';
                echo '<span class="test-label">' . $name . '</span>';
                echo '<span class="test-details">' . $path . '</span>';
                echo '</div>';
                if (!$exists) $errors[] = "Fichier manquant: $name";
            }
            
            // Test du chargement des configs
            $config_loaded = false;
            $version_loaded = false;
            
            try {
                if (file_exists($config_files['config.php'])) {
                    require_once $config_files['config.php'];
                    $config_loaded = true;
                    
                    echo '<div class="test-item">';
                    echo '<span class="status ok">✅ OK</span>';
                    echo '<span class="test-label">Chargement config.php</span>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="test-item">';
                echo '<span class="status error">❌ ERREUR</span>';
                echo '<span class="test-label">Chargement config.php</span>';
                echo '<div class="error-details">' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '</div>';
                $errors[] = "Erreur config.php: " . $e->getMessage();
            }
            
            try {
                if (file_exists($config_files['version.php'])) {
                    require_once $config_files['version.php'];
                    $version_loaded = true;
                    
                    echo '<div class="test-item">';
                    echo '<span class="status ok">✅ OK</span>';
                    echo '<span class="test-label">Chargement version.php</span>';
                    if (defined('APP_VERSION')) {
                        echo '<span class="test-details">Version: ' . APP_VERSION . '</span>';
                    }
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="test-item">';
                echo '<span class="status error">❌ ERREUR</span>';
                echo '<span class="test-label">Chargement version.php</span>';
                echo '<div class="error-details">' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '</div>';
                $errors[] = "Erreur version.php: " . $e->getMessage();
            }
            
            echo '</div>';
            
            // Test 3: Base de données
            echo '<div class="test-section">';
            echo '<h2>🗄️ Base de données</h2>';
            
            if ($config_loaded && defined('DB_HOST')) {
                echo '<div class="test-item">';
                echo '<span class="status ok">✅ OK</span>';
                echo '<span class="test-label">Constantes DB définies</span>';
                echo '<span class="test-details">Host: ' . DB_HOST . ', DB: ' . DB_NAME . '</span>';
                echo '</div>';
                
                if (isset($db) && $db instanceof PDO) {
                    echo '<div class="test-item">';
                    echo '<span class="status ok">✅ OK</span>';
                    echo '<span class="test-label">Connexion PDO</span>';
                    echo '</div>';
                    
                    try {
                        $test_query = $db->query("SELECT 1 as test")->fetch();
                        if ($test_query['test'] == 1) {
                            echo '<div class="test-item">';
                            echo '<span class="status ok">✅ OK</span>';
                            echo '<span class="test-label">Test de requête</span>';
                            echo '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="test-item">';
                        echo '<span class="status error">❌ ERREUR</span>';
                        echo '<span class="test-label">Test de requête</span>';
                        echo '<div class="error-details">' . htmlspecialchars($e->getMessage()) . '</div>';
                        echo '</div>';
                        $errors[] = "Erreur requête test: " . $e->getMessage();
                    }
                } else {
                    echo '<div class="test-item">';
                    echo '<span class="status error">❌ ERREUR</span>';
                    echo '<span class="test-label">Connexion PDO</span>';
                    echo '<span class="test-details">Variable $db non définie ou incorrecte</span>';
                    echo '</div>';
                    $errors[] = "Connexion PDO non disponible";
                }
            } else {
                echo '<div class="test-item">';
                echo '<span class="status error">❌ ERREUR</span>';
                echo '<span class="test-label">Configuration DB</span>';
                echo '<span class="test-details">Constantes non définies</span>';
                echo '</div>';
                $errors[] = "Configuration base de données manquante";
            }
            
            echo '</div>';
            
            // Test 4: Assets
            echo '<div class="test-section">';
            echo '<h2>🎨 Assets et ressources</h2>';
            
            $assets = [
                'CSS principal' => 'assets/css/app.min.css',
                'CSS portal' => 'assets/css/portal.css',
                'JavaScript' => 'assets/js/app.min.js'
            ];
            
            foreach ($assets as $name => $path) {
                $full_path = __DIR__ . '/' . $path;
                $exists = file_exists($full_path);
                $size = $exists ? filesize($full_path) : 0;
                
                echo '<div class="test-item">';
                echo '<span class="status ' . ($exists ? 'ok' : 'warning') . '">' . ($exists ? '✅ OK' : '⚠️ MANQUANT') . '</span>';
                echo '<span class="test-label">' . $name . '</span>';
                echo '<span class="test-details">' . ($exists ? number_format($size) . ' bytes' : $path) . '</span>';
                echo '</div>';
                
                if (!$exists) $warnings[] = "Asset manquant: $name ($path)";
            }
            
            echo '</div>';
            
            // Test 5: Fonctions de version
            echo '<div class="test-section">';
            echo '<h2>📊 Fonctions système</h2>';
            
            $functions = [
                'getVersionInfo' => 'Informations de version',
                'renderVersionFooter' => 'Rendu footer version'
            ];
            
            foreach ($functions as $func => $desc) {
                $exists = function_exists($func);
                echo '<div class="test-item">';
                echo '<span class="status ' . ($exists ? 'ok' : 'error') . '">' . ($exists ? '✅ OK' : '❌ MANQUANT') . '</span>';
                echo '<span class="test-label">' . $desc . '</span>';
                echo '<span class="test-details">function ' . $func . '()</span>';
                echo '</div>';
                
                if (!$exists) $errors[] = "Fonction manquante: $func";
            }
            
            echo '</div>';
            ?>
            
            <!-- Résumé -->
            <div class="summary">
                <h3>📋 Résumé du diagnostic</h3>
                
                <?php if (empty($errors) && empty($warnings)): ?>
                    <p><strong style="color: #166534;">🎉 Tous les tests sont passés avec succès !</strong></p>
                    <p>Le portail devrait fonctionner correctement. Vous pouvez accéder à <span class="code">index.php</span>.</p>
                
                <?php elseif (empty($errors)): ?>
                    <p><strong style="color: #a16207;">⚠️ Quelques avertissements détectés</strong></p>
                    <p>Le portail devrait fonctionner, mais certains assets sont manquants :</p>
                    <ul>
                        <?php foreach ($warnings as $warning): ?>
                        <li><?= htmlspecialchars($warning) ?></li>
                        <?php endforeach; ?>
                    </ul>
                
                <?php else: ?>
                    <p><strong style="color: #991b1b;">❌ Erreurs critiques détectées</strong></p>
                    <p>Le portail ne peut pas fonctionner correctement. Erreurs à corriger :</p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div class="actions">
                <?php if (empty($errors)): ?>
                <a href="index.php" class="btn btn-primary">
                    🚀 Accéder au portail
                </a>
                <?php endif; ?>
                
                <a href="?" class="btn btn-secondary">
                    🔄 Relancer le diagnostic
                </a>
                
                <?php if (defined('DEBUG') && DEBUG): ?>
                <a href="debug-index.php" class="btn btn-secondary">
                    🐛 Debug détaillé
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        console.log('🔍 Diagnostic Portail Guldagil');
        console.log('PHP Version:', '<?= PHP_VERSION ?>');
        <?php if (defined('APP_VERSION')): ?>
        console.log('App Version:', '<?= APP_VERSION ?>');
        <?php endif; ?>
        console.log('Erreurs:', <?= count($errors) ?>);
        console.log('Avertissements:', <?= count($warnings) ?>);
    </script>
</body>
</html>

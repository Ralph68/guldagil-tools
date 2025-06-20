<?php
/**
 * public/diagnostic.php - Diagnostic indépendant
 * Chemin: /public/diagnostic.php
 * 
 * Fichier de test pour diagnostiquer l'erreur 500
 * À placer directement dans /public/ pour test rapide
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🔍 Diagnostic Guldagil</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { border-left: 5px solid #28a745; }
        .error { border-left: 5px solid #dc3545; }
        .warning { border-left: 5px solid #ffc107; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
        h1 { color: #333; }
        h2 { color: #007bff; margin-top: 0; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic Système Guldagil</h1>
    
    <?php
    $tests = [];
    
    // Test 1: PHP Version
    $phpVersion = PHP_VERSION;
    if (version_compare($phpVersion, '7.4.0', '>=')) {
        $tests[] = ['success', 'PHP Version', "Version $phpVersion (✅ Compatible)"];
    } else {
        $tests[] = ['error', 'PHP Version', "Version $phpVersion (❌ Minimum requis: 7.4)"];
    }
    
    // Test 2: Extensions PHP
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    foreach ($requiredExtensions as $ext) {
        if (extension_loaded($ext)) {
            $tests[] = ['success', "Extension $ext", "✅ Chargée"];
        } else {
            $tests[] = ['error', "Extension $ext", "❌ Manquante"];
        }
    }
    
    // Test 3: Chemins fichiers
    $paths = [
        'Config' => __DIR__ . '/../config/config.php',
        'Transport' => __DIR__ . '/../src/modules/calculateur/services/TransportCalculator.php',
        'Storage' => __DIR__ . '/../storage',
        'Logs' => __DIR__ . '/../storage/logs'
    ];
    
    foreach ($paths as $name => $path) {
        if (file_exists($path)) {
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            $tests[] = ['success', "Fichier $name", "✅ Trouvé ($perms)"];
        } else {
            $tests[] = ['error', "Fichier $name", "❌ Manquant: $path"];
        }
    }
    
    // Test 4: Permissions dossiers
    $writableDirs = [
        __DIR__ . '/../storage',
        __DIR__ . '/../storage/logs',
        __DIR__ . '/../storage/cache'
    ];
    
    foreach ($writableDirs as $dir) {
        if (is_dir($dir)) {
            if (is_writable($dir)) {
                $tests[] = ['success', 'Permissions ' . basename($dir), '✅ Écriture autorisée'];
            } else {
                $tests[] = ['warning', 'Permissions ' . basename($dir), '⚠️ Écriture non autorisée'];
            }
        } else {
            $tests[] = ['warning', 'Dossier ' . basename($dir), '⚠️ Dossier manquant'];
        }
    }
    
    // Test 5: Configuration
    $configTest = true;
    $configError = '';
    
    try {
        $configPath = __DIR__ . '/../config/config.php';
        if (file_exists($configPath)) {
            ob_start();
            require_once $configPath;
            ob_end_clean();
            
            if (defined('APP_VERSION')) {
                $tests[] = ['success', 'Configuration', '✅ Chargée (v' . APP_VERSION . ')'];
            } else {
                $tests[] = ['warning', 'Configuration', '⚠️ Chargée mais constantes manquantes'];
            }
        } else {
            $tests[] = ['error', 'Configuration', '❌ Fichier config.php manquant'];
            $configTest = false;
        }
    } catch (Exception $e) {
        $tests[] = ['error', 'Configuration', '❌ Erreur: ' . $e->getMessage()];
        $configTest = false;
    } catch (Error $e) {
        $tests[] = ['error', 'Configuration', '❌ Erreur fatale: ' . $e->getMessage()];
        $configTest = false;
    }
    
    // Test 6: Base de données (si config OK)
    if ($configTest && isset($db)) {
        try {
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            if ($result && $result['test'] == 1) {
                $tests[] = ['success', 'Base de données', '✅ Connexion OK'];
                
                // Test tables
                try {
                    $tables = $db->query("SHOW TABLES LIKE 'gul_%'")->fetchAll();
                    $tableCount = count($tables);
                    if ($tableCount > 0) {
                        $tests[] = ['success', 'Tables BDD', "✅ $tableCount table(s) trouvée(s)"];
                    } else {
                        $tests[] = ['warning', 'Tables BDD', '⚠️ Aucune table gul_* trouvée'];
                    }
                } catch (Exception $e) {
                    $tests[] = ['warning', 'Tables BDD', '⚠️ Impossible de lister les tables'];
                }
            }
        } catch (Exception $e) {
            $tests[] = ['error', 'Base de données', '❌ Connexion échouée: ' . $e->getMessage()];
        }
    } elseif ($configTest) {
        $tests[] = ['warning', 'Base de données', '⚠️ Variable $db non disponible'];
    }
    
    // Test 7: Classe Transport
    if ($configTest) {
        try {
            $transportPath = __DIR__ . '/../src/modules/calculateur/services/TransportCalculator.php';
            if (file_exists($transportPath)) {
                require_once $transportPath;
                if (class_exists('Transport')) {
                    $tests[] = ['success', 'Classe Transport', '✅ Classe trouvée et chargeable'];
                    
                    if (isset($db)) {
                        try {
                            $transport = new Transport($db);
                            $tests[] = ['success', 'Instance Transport', '✅ Instance créée avec succès'];
                        } catch (Exception $e) {
                            $tests[] = ['error', 'Instance Transport', '❌ Erreur instanciation: ' . $e->getMessage()];
                        }
                    }
                } else {
                    $tests[] = ['error', 'Classe Transport', '❌ Classe Transport non trouvée après inclusion'];
                }
            } else {
                $tests[] = ['error', 'Classe Transport', '❌ Fichier TransportCalculator.php manquant'];
            }
        } catch (Exception $e) {
            $tests[] = ['error', 'Classe Transport', '❌ Erreur chargement: ' . $e->getMessage()];
        } catch (Error $e) {
            $tests[] = ['error', 'Classe Transport', '❌ Erreur fatale: ' . $e->getMessage()];
        }
    }
    
    // Affichage des résultats
    foreach ($tests as $test) {
        [$type, $title, $message] = $test;
        echo "<div class='test $type'>";
        echo "<h2>$title</h2>";
        echo "<p>$message</p>";
        echo "</div>";
    }
    
    // Informations système supplémentaires
    ?>
    
    <div class="test">
        <h2>📊 Informations système</h2>
        <div class="code">
            <strong>Serveur Web:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu' ?><br>
            <strong>PHP SAPI:</strong> <?= php_sapi_name() ?><br>
            <strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'Inconnu' ?><br>
            <strong>Script Name:</strong> <?= $_SERVER['SCRIPT_NAME'] ?? 'Inconnu' ?><br>
            <strong>Memory Limit:</strong> <?= ini_get('memory_limit') ?><br>
            <strong>Max Execution Time:</strong> <?= ini_get('max_execution_time') ?>s<br>
            <strong>Error Reporting:</strong> <?= error_reporting() ?><br>
            <strong>Display Errors:</strong> <?= ini_get('display_errors') ? 'Activé' : 'Désactivé' ?><br>
            <strong>Date/Heure:</strong> <?= date('Y-m-d H:i:s T') ?><br>
            <strong>Timezone:</strong> <?= date_default_timezone_get() ?>
        </div>
    </div>
    
    <div class="test">
        <h2>🔧 Actions recommandées</h2>
        <?php
        $hasErrors = false;
        foreach ($tests as $test) {
            if ($test[0] === 'error') {
                $hasErrors = true;
                break;
            }
        }
        
        if ($hasErrors) {
            echo "<p>❌ <strong>Des erreurs ont été détectées.</strong> Veuillez corriger les problèmes suivants :</p>";
            echo "<ul>";
            foreach ($tests as $test) {
                if ($test[0] === 'error') {
                    echo "<li>" . htmlspecialchars($test[1] . ': ' . $test[2]) . "</li>";
                }
            }
            echo "</ul>";
        } else {
            echo "<p>✅ <strong>Diagnostic réussi !</strong> Tous les composants semblent fonctionner.</p>";
            echo "<p>Si vous avez encore une erreur 500, vérifiez :</p>";
            echo "<ul>";
            echo "<li>Les logs d'erreur du serveur web</li>";
            echo "<li>Les permissions des fichiers (644 pour les fichiers, 755 pour les dossiers)</li>";
            echo "<li>La configuration du VirtualHost</li>";
            echo "</ul>";
        }
        ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px; color: #666;">
        <p><small>Diagnostic généré le <?= date('d/m/Y à H:i:s') ?></small></p>
        <p><small>Supprimez ce fichier après diagnostic pour des raisons de sécurité</small></p>
    </div>
</body>
</html>

<?php
// Test simple de chargement config.php
// Placez dans /public/test_config.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Test chargement config.php</h1>";

$config_path = '/home/sc1ruje0226/public_html/config/config.php';

echo "<h2>Informations fichier</h2>";
echo "<p>Chemin : <code>$config_path</code></p>";
echo "<p>Existe : " . (file_exists($config_path) ? '✅ OUI' : '❌ NON') . "</p>";

if (file_exists($config_path)) {
    echo "<p>Taille : " . filesize($config_path) . " octets</p>";
    echo "<p>Permissions : " . substr(sprintf('%o', fileperms($config_path)), -4) . "</p>";
    echo "<p>Modifié : " . date('Y-m-d H:i:s', filemtime($config_path)) . "</p>";
    
    // Test syntaxe PHP
    echo "<h2>Test syntaxe PHP</h2>";
    $output = [];
    $return_code = 0;
    exec("php -l \"$config_path\" 2>&1", $output, $return_code);
    
    if ($return_code === 0) {
        echo "<p>✅ Syntaxe PHP correcte</p>";
    } else {
        echo "<p>❌ Erreur syntaxe PHP :</p>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    }
    
    // Test inclusion
    echo "<h2>Test inclusion</h2>";
    try {
        // Backup des erreurs
        $old_error_handler = set_error_handler(function($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        
        define('ROOT_PATH', '/home/sc1ruje0226/public_html');
        require_once $config_path;
        
        restore_error_handler();
        
        echo "<p>✅ Inclusion réussie</p>";
        
        // Vérifier les constantes
        echo "<h3>Constantes définies :</h3>";
        $constants = ['DEBUG', 'DB_HOST', 'DB_NAME', 'MODULES'];
        foreach ($constants as $const) {
            $status = defined($const) ? '✅' : '❌';
            echo "<p>$status $const</p>";
        }
        
    } catch (Exception $e) {
        restore_error_handler();
        echo "<p>❌ Erreur inclusion : " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Fichier : " . $e->getFile() . "</p>";
        echo "<p>Ligne : " . $e->getLine() . "</p>";
        
        // Afficher les lignes autour de l'erreur
        if ($e->getFile() === $config_path) {
            $lines = file($config_path);
            $error_line = $e->getLine();
            $start = max(0, $error_line - 5);
            $end = min(count($lines), $error_line + 5);
            
            echo "<h3>Code autour de l'erreur (lignes $start - $end) :</h3>";
            echo "<pre>";
            for ($i = $start; $i < $end; $i++) {
                $line_num = $i + 1;
                $arrow = ($line_num == $error_line) ? '>>> ' : '    ';
                echo sprintf("%s%3d: %s", $arrow, $line_num, htmlspecialchars($lines[$i]));
            }
            echo "</pre>";
        }
    } catch (Error $e) {
        restore_error_handler();
        echo "<p>❌ Erreur fatale : " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Fichier : " . $e->getFile() . "</p>";
        echo "<p>Ligne : " . $e->getLine() . "</p>";
    }
}
?>

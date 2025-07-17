<?php
/**
 * Titre: Debug ultra-précis des premières lignes du header
 * Chemin: /public/debug_first_lines.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
define('ROOT_PATH', dirname(__DIR__));

echo '<h1>🔍 DEBUG PREMIÈRES LIGNES HEADER</h1>';

// Chargement minimal
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Variables de base
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$current_module = 'test';

echo '<p>✅ Configuration de base OK</p>';

// Lire header.php
$header_path = ROOT_PATH . '/templates/header.php';
$lines = file($header_path, FILE_IGNORE_NEW_LINES);

echo '<p>📄 Header chargé: ' . count($lines) . ' lignes</p>';

// Afficher et tester ligne par ligne les 20 premières lignes
echo '<h2>📋 Contenu des 20 premières lignes:</h2>';
echo '<pre style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
for ($i = 0; $i < min(20, count($lines)); $i++) {
    $line_num = $i + 1;
    echo str_pad($line_num, 3, ' ', STR_PAD_LEFT) . ': ' . htmlspecialchars($lines[$i]) . "\n";
}
echo '</pre>';

// Test ligne par ligne pour trouver EXACTEMENT où ça plante
echo '<h2>🧪 Test d\'exécution ligne par ligne</h2>';

$code_buffer = "<?php\n";
$code_buffer .= "define('ROOT_PATH', '" . ROOT_PATH . "');\n";
$code_buffer .= "require_once ROOT_PATH . '/config/config.php';\n";
$code_buffer .= "require_once ROOT_PATH . '/config/version.php';\n";

// Variables nécessaires
$code_buffer .= "\$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';\n";
$code_buffer .= "\$current_module = 'test';\n";
$code_buffer .= "\$page_title = 'Test';\n";
$code_buffer .= "\$user_authenticated = false;\n";
$code_buffer .= "\$current_user = null;\n";

for ($i = 0; $i < min(30, count($lines)); $i++) {
    $line_num = $i + 1;
    $line = $lines[$i];
    
    // Ignorer les lignes vides et commentaires
    if (trim($line) === '' || strpos(trim($line), '//') === 0 || strpos(trim($line), '#') === 0) {
        echo "<p>⏭️ Ligne $line_num: Vide/Commentaire - ignorée</p>";
        continue;
    }
    
    // Ignorer la ligne <?php si c'est la première
    if ($line_num === 1 && strpos($line, '<?php') === 0) {
        echo "<p>⏭️ Ligne $line_num: Tag PHP - ignoré</p>";
        continue;
    }
    
    // Ajouter la ligne au buffer
    $code_buffer .= $line . "\n";
    
    echo "<p>🔍 Test ligne $line_num: <code>" . htmlspecialchars(trim($line)) . "</code></p>";
    
    // Créer un fichier temporaire avec le code jusqu'ici
    $temp_file = '/tmp/header_test_line_' . $line_num . '.php';
    file_put_contents($temp_file, $code_buffer);
    
    // Test de syntaxe
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($temp_file) . " 2>&1", $output, $return_var);
    
    if ($return_var !== 0) {
        echo "<div style='background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px 0;'>";
        echo "<p><strong>❌ ERREUR DE SYNTAXE à la ligne $line_num !</strong></p>";
        echo "<p><strong>Ligne problématique:</strong> <code>" . htmlspecialchars($line) . "</code></p>";
        echo "<p><strong>Erreur PHP:</strong></p>";
        echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
        echo "</div>";
        
        // Analyser la ligne plus en détail
        echo "<h3>🔬 Analyse de la ligne problématique</h3>";
        
        // Vérifier les caractères suspects
        if (!mb_check_encoding($line, 'UTF-8')) {
            echo "<p>⚠️ Problème d'encodage détecté</p>";
        }
        
        // Vérifier les caractères invisibles
        $visible_chars = preg_replace('/[^\x20-\x7E]/', '�', $line);
        if ($visible_chars !== $line) {
            echo "<p>⚠️ Caractères non-ASCII détectés:</p>";
            echo "<p>Original: <code>" . htmlspecialchars($line) . "</code></p>";
            echo "<p>Visible: <code>" . htmlspecialchars($visible_chars) . "</code></p>";
        }
        
        // Vérifier les accolades et parenthèses
        $open_parens = substr_count($line, '(');
        $close_parens = substr_count($line, ')');
        $open_braces = substr_count($line, '{');
        $close_braces = substr_count($line, '}');
        $open_brackets = substr_count($line, '[');
        $close_brackets = substr_count($line, ']');
        
        echo "<p>Parenthèses: $open_parens ouvertes, $close_parens fermées</p>";
        echo "<p>Accolades: $open_braces ouvertes, $close_braces fermées</p>";
        echo "<p>Crochets: $open_brackets ouverts, $close_brackets fermés</p>";
        
        break;
    }
    
    // Test d'exécution
    ob_start();
    $exec_success = false;
    try {
        include $temp_file;
        $exec_success = true;
        echo "<p>✅ Ligne $line_num: Exécution OK</p>";
    } catch (Exception $e) {
        echo "<div style='background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
        echo "<p><strong>⚠️ ERREUR D'EXÉCUTION à la ligne $line_num !</strong></p>";
        echo "<p><strong>Ligne:</strong> <code>" . htmlspecialchars($line) . "</code></p>";
        echo "<p><strong>Erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Type:</strong> " . get_class($e) . "</p>";
        echo "</div>";
        break;
    } catch (Error $e) {
        echo "<div style='background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px 0;'>";
        echo "<p><strong>❌ ERREUR FATALE à la ligne $line_num !</strong></p>";
        echo "<p><strong>Ligne:</strong> <code>" . htmlspecialchars($line) . "</code></p>";
        echo "<p><strong>Erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Type:</strong> " . get_class($e) . "</p>";
        echo "</div>";
        break;
    }
    ob_end_clean();
    
    // Nettoyer le fichier temporaire
    unlink($temp_file);
    
    if (!$exec_success) break;
}

echo '<hr>';
echo '<p><strong>🎯 RÉSULTAT:</strong> La ligne qui cause l\'erreur 500 est identifiée ci-dessus !</p>';
?>

<?php
/**
 * Debug avec capture d'erreur fatale
 */

// Buffer de sortie pour capturer erreurs
ob_start();

// Gestionnaire d'erreur fatale
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        echo "<h1>Erreur PHP Fatale Détectée</h1>";
        echo "<div style='background: #ffebee; padding: 20px; border: 1px solid #f44336; margin: 20px;'>";
        echo "<strong>Erreur:</strong> " . htmlspecialchars($error['message']) . "<br>";
        echo "<strong>Fichier:</strong> " . htmlspecialchars($error['file']) . "<br>";
        echo "<strong>Ligne:</strong> " . $error['line'] . "<br>";
        echo "</div>";
    }
});

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Début du test...<br>";

try {
    echo "1. Inclusion config...<br>";
    require_once __DIR__ . '/../../config/config.php';
    echo "2. Config OK<br>";
    
    echo "3. Inclusion version...<br>";
    require_once __DIR__ . '/../../config/version.php';
    echo "4. Version OK<br>";
    
    echo "5. Test des fonctions...<br>";
    
    // Test fonction de validation
    function validateCalculatorData($data) {
        $errors = [];
        
        if (empty($data['departement']) || !preg_match('/^\d{2}$/', $data['departement'])) {
            $errors['departement'] = 'Département invalide';
        }
        
        if (empty($data['poids']) || $data['poids'] < 0.1) {
            $errors['poids'] = 'Poids invalide';
        }
        
        return $errors;
    }
    
    echo "6. Fonction validation OK<br>";
    
    // Test fonction formatage
    function formatResults($results) {
        if (!$results) return null;
        
        return [
            'success' => true,
            'carriers' => [],
            'best_rate' => null
        ];
    }
    
    echo "7. Fonction format OK<br>";
    
    echo "8. Test complet validation-test.php...<br>";
    
    // Inclusion du fichier problématique ligne par ligne
    $validation_file = __DIR__ . '/validation-test.php';
    if (file_exists($validation_file)) {
        echo "9. Fichier validation-test.php trouvé<br>";
        
        // Lecture du contenu
        $content = file_get_contents($validation_file);
        echo "10. Contenu lu (" . strlen($content) . " caractères)<br>";
        
        // Tentative d'inclusion
        echo "11. Tentative inclusion...<br>";
        include $validation_file;
        echo "12. Inclusion réussie !<br>";
        
    } else {
        echo "❌ Fichier validation-test.php non trouvé<br>";
    }
    
} catch (ParseError $e) {
    echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
    echo "<strong>Erreur de syntaxe PHP:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Ligne:</strong> " . $e->getLine();
    echo "</div>";
    
} catch (Error $e) {
    echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
    echo "<strong>Erreur PHP:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Ligne:</strong> " . $e->getLine();
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
    echo "<strong>Exception:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Ligne:</strong> " . $e->getLine();
    echo "</div>";
}

ob_end_flush();
?>

<?php
/**
 * Titre: Page de test des erreurs (développement uniquement)
 * Chemin: /public/test_errors.php
 * Version: 0.5 beta + build auto
 */

// Seulement en développement
if (getenv('APP_ENV') === 'production') {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$test_type = $_GET['test'] ?? '';

switch ($test_type) {
    case '404':
        header('HTTP/1.0 404 Not Found');
        include __DIR__ . '/errors/404.php';
        break;
        
    case '403':
        header('HTTP/1.0 403 Forbidden');
        include __DIR__ . '/errors/403.php';
        break;
        
    case '500':
        header('HTTP/1.0 500 Internal Server Error');
        include __DIR__ . '/errors/500.php';
        break;
        
    case '503':
        header('HTTP/1.0 503 Service Unavailable');
        include __DIR__ . '/errors/503.php';
        break;
        
    case 'php_error':
        // Déclencher une erreur PHP
        $undefined_var = $this_will_cause_error;
        break;
        
    case 'exception':
        // Déclencher une exception
        throw new Exception('Ceci est un test d\'exception');
        break;
        
    case 'fatal':
        // Erreur fatale
        call_to_undefined_function();
        break;
        
    default:
        echo '<h1>🧪 Test des pages d\'erreur</h1>';
        echo '<p>Choisissez un type d\'erreur à tester :</p>';
        echo '<ul>';
        echo '<li><a href="?test=404">Test 404 - Page non trouvée</a></li>';
        echo '<li><a href="?test=403">Test 403 - Accès interdit</a></li>';
        echo '<li><a href="?test=500">Test 500 - Erreur serveur</a></li>';
        echo '<li><a href="?test=503">Test 503 - Service indisponible</a></li>';
        echo '<li><a href="?test=php_error">Test erreur PHP</a></li>';
        echo '<li><a href="?test=exception">Test exception</a></li>';
        echo '<li><a href="?test=fatal">Test erreur fatale</a></li>';
        echo '</ul>';
        break;
}
?>

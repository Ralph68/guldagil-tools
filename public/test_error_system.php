// Test dans public/test_error_system.php
<?php
require_once '../config/config.php';

try {
    $errorManager = ErrorManager::getInstance();
    $errorManager->logCritical("Test système erreurs", ['module' => 'test']);
    echo "✅ ErrorManager opérationnel";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
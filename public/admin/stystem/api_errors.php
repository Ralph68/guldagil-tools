<?php
/**
 * Titre: API JSON pour widget erreurs header
 * Chemin: /public/admin/system/api_errors.php
 * Version: 0.5 beta + build auto
 */

header('Content-Type: application/json');

// Configuration rapide
require_once dirname(dirname(dirname(__DIR__))) . '/config/config.php';

// Vérification authentification
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if (!in_array($_SESSION['user']['role'] ?? 'user', ['admin', 'dev'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé']);
    exit;
}

try {
    $errors = [];
    
    if (class_exists('ErrorManager')) {
        $errorManager = ErrorManager::getInstance();
        $errors = $errorManager->getRecentErrors(2); // 2 heures pour header
    } else {
        // Fallback lecture logs
        $log_file = ROOT_PATH . '/storage/logs/error.log';
        if (file_exists($log_file)) {
            $lines = array_slice(file($log_file, FILE_IGNORE_NEW_LINES), -10);
            foreach ($lines as $line) {
                if (preg_match('/\[([^\]]+)\] (\w+): (.+)/', $line, $matches)) {
                    $errors[] = [
                        'timestamp' => $matches[1],
                        'level' => strtolower($matches[2]),
                        'message' => $matches[3],
                        'module' => 'system'
                    ];
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'errors' => $errors,
        'count' => count($errors)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
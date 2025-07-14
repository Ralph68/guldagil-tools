<?php
/**
 * Titre: Gestionnaire d'erreurs PHP centralisé
 * Chemin: /config/error_handler.php
 * Version: 0.5 beta + build auto
 */

// Gestionnaire d'erreurs PHP personnalisé
function customErrorHandler($severity, $message, $file, $line) {
    // Ne pas traiter les erreurs supprimées avec @
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error_types = [
        E_ERROR => 'FATAL',
        E_WARNING => 'WARNING', 
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    $error_type = $error_types[$severity] ?? 'UNKNOWN';
    
    // Log l'erreur
    $log_message = sprintf(
        "[%s] %s: %s in %s on line %d",
        date('Y-m-d H:i:s'),
        $error_type,
        $message,
        $file,
        $line
    );
    
    error_log($log_message);
    
    // Redirection vers page d'erreur selon la gravité
    if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
        $error_params = [
            'type' => 'general',
            'code' => 500,
            'message' => $message,
            'file' => basename($file),
            'line' => $line
        ];
        
        header('Location: /error.php?' . http_build_query($error_params));
        exit;
    }
    
    return true;
}

// Gestionnaire d'exceptions non capturées
function customExceptionHandler($exception) {
    $error_params = [
        'type' => 'general',
        'code' => $exception->getCode() ?: 500,
        'message' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ];
    
    error_log(sprintf(
        "[%s] Uncaught Exception: %s in %s on line %d",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
    
    header('Location: /error.php?' . http_build_query($error_params));
    exit;
}

// Gestionnaire d'arrêt fatal
function customShutdownHandler() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $error_params = [
            'type' => 'general',
            'code' => 500,
            'message' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ];
        
        // Ne pas rediriger si les headers sont déjà envoyés
        if (!headers_sent()) {
            header('Location: /error.php?' . http_build_query($error_params));
            exit;
        }
    }
}

// Enregistrement des gestionnaires
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('customShutdownHandler');
?>

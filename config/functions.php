<?php
// config/functions.php

function logMessage($level, $message, $channel = 'app'): void {
    // Le code de la fonction reste le même
    if (!LOG_CONFIG['enabled']) return;
    
    $logFile = LOG_CONFIG['channels'][$channel] ?? LOG_CONFIG['channels']['app'];
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // ... reste du code ...
}

// config/config.php
require_once 'config/functions.php';

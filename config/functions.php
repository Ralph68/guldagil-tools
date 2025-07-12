<?php
/** 
 * Fonctions utilitaires
 */

if (!function_exists('logMessage')) {
    function logMessage($level, $message, $channel = 'app'): void {
        if (!LOG_CONFIG['enabled']) return;
        
        $logFile = LOG_CONFIG['channels'][$channel] ?? LOG_CONFIG['channels']['app'];
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        if (file_exists($logFile) && filesize($logFile) > LOG_CONFIG['max_file_size']) {
            $backupFile = $logFile . '.' . date('Y-m-d-H-i-s');
            rename($logFile, $backupFile);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('getFromCache')) {
    function getFromCache($key, $default = null) {
        if (!CACHE_CONFIG['enabled']) {
            return $default;
        }
        
        $cacheFile = CACHE_CONFIG['path'] . '/' . md5($key) . '.cache';
        
        if (!file_exists($cacheFile)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        
        if ($data['expires'] < time()) {
            unlink($cacheFile);
            return $default;
        }
        
        return $data['value'];
    }
}

if (!function_exists('putInCache')) {
    function putInCache($key, $value, $ttl = null): bool {
        if (!CACHE_CONFIG['enabled']) {
            return false;
        }
        
        $ttl = $ttl ?? CACHE_CONFIG['default_ttl'];
        $cacheFile = CACHE_CONFIG['path'] . '/' . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($cacheFile, serialize($data), LOCK_EX) !== false;
    }
}

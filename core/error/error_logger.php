<?php
/**
 * Titre: Système de logs avancé avec rotation et catégorisation
 * Chemin: /core/error/error_logger.php
 * Version: 0.5 beta + build auto
 */

class ErrorLogger 
{
    private $log_paths;
    private $max_file_size = 10485760; // 10MB
    
    public function __construct() {
        $this->log_paths = [
            'critical' => ROOT_PATH . '/storage/logs/errors/critical.log',
            'database' => ROOT_PATH . '/storage/logs/errors/database.log',
            'auth' => ROOT_PATH . '/storage/logs/errors/auth_errors.log',
            'module' => ROOT_PATH . '/storage/logs/errors/module_errors.log',
            'general' => ROOT_PATH . '/storage/logs/error.log' // ✅ Existant maintenu
        ];
        $this->ensureDirectories();
    }
    
    public function log($error_data) {
        $formatted_entry = $this->formatLogEntry($error_data);
        
        // Log principal (compatible existant)
        $this->writeToFile($this->log_paths['general'], $formatted_entry);
        
        // Log spécialisé
        $specific_log = $this->log_paths[$error_data['type']] ?? null;
        if ($specific_log) {
            $detailed_entry = $this->formatDetailedEntry($error_data);
            $this->writeToFile($specific_log, $detailed_entry);
        }
        
        // Rotation si nécessaire
        $this->rotateIfNeeded($specific_log ?? $this->log_paths['general']);
    }
    
    private function formatLogEntry($error_data) {
        // Format compatible avec logs existants
        return sprintf(
            "[%s] %s: %s in %s\n",
            $error_data['timestamp'],
            strtoupper($error_data['level']),
            $error_data['message'],
            $error_data['module']
        );
    }
    
    private function formatDetailedEntry($error_data) {
        // Format détaillé pour logs spécialisés
        return json_encode([
            'timestamp' => $error_data['timestamp'],
            'type' => $error_data['type'],
            'level' => $error_data['level'],
            'message' => $error_data['message'],
            'module' => $error_data['module'],
            'user_id' => $error_data['user_id'],
            'context' => $error_data['context'],
            'trace' => array_slice($error_data['trace'], 0, 3) // Limité pour taille
        ]) . "\n";
    }
    
    // TODO: Méthodes de recherche et filtrage
    // TODO: Intégration avec visualiseur de logs existant
}
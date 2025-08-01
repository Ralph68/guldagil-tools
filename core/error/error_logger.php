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
    
    /**
     * Crée les dossiers de logs si nécessaires
     */
    private function ensureDirectories() {
        foreach ($this->log_paths as $log_path) {
            $dir = dirname($log_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Écrit de manière sécurisée dans un fichier de log
     */
    private function writeToFile($file_path, $content) {
        try {
            // Vérifier que le dossier existe
            $dir = dirname($file_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Écriture atomique avec verrou
            $result = file_put_contents($file_path, $content, FILE_APPEND | LOCK_EX);
            
            if ($result === false) {
                // Fallback : log dans fichier temporaire si problème
                $temp_log = sys_get_temp_dir() . '/error_fallback_' . date('Y-m-d') . '.log';
                file_put_contents($temp_log, "[FALLBACK] " . $content, FILE_APPEND | LOCK_EX);
            }
            
            return $result !== false;
        } catch (Exception $e) {
            // En cas d'erreur critique, ne pas lever d'exception pour éviter les boucles
            error_log("ErrorLogger: Impossible d'écrire dans $file_path - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Effectue la rotation des logs si nécessaire
     */
    private function rotateIfNeeded($file_path) {
        if (!file_exists($file_path)) {
            return;
        }
        
        $file_size = filesize($file_path);
        if ($file_size < $this->max_file_size) {
            return; // Pas besoin de rotation
        }
        
        try {
            // Rotation simple : rename vers .old, puis création nouveau fichier
            $backup_file = $file_path . '.old';
            
            // Supprimer ancien backup s'il existe
            if (file_exists($backup_file)) {
                unlink($backup_file);
            }
            
            // Déplacer le fichier actuel vers backup
            rename($file_path, $backup_file);
            
            // Le nouveau fichier sera créé automatiquement au prochain log
            
        } catch (Exception $e) {
            error_log("ErrorLogger: Rotation échouée pour $file_path - " . $e->getMessage());
        }
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
    
    /**
     * Récupère les erreurs récentes pour le dashboard
     * TODO: Implémenter parsing intelligent des logs
     */
    public function getRecentErrors($hours = 24) {
        // TODO: Parser les fichiers de logs et retourner array structuré
        // Filtrage par timestamp, tri par gravité, etc.
        return [];
    }
    
    /**
     * Statistiques générales des erreurs
     * TODO: Implémenter pour dashboard admin
     */
    public function getStats() {
        // TODO: Compter erreurs par type, module, période
        // Retourner metrics pour graphiques
        return [
            'total_errors' => 0,
            'critical_errors' => 0,
            'by_module' => [],
            'by_type' => []
        ];
    }
    // TODO: Méthodes de recherche et filtrage
    // TODO: Intégration avec visualiseur de logs existant
}
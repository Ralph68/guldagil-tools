<?php
/**
 * Titre: Module Port - Calculateur de frais
 * Chemin: /features/port/PortModule.php
 */

class PortModule {
    private $db;
    private $transport;
    
    public function __construct($db = null) {
        // Si pas de DB fournie, utiliser la fonction globale
        if ($db === null) {
            if (function_exists('getDBConnection')) {
                $this->db = getDBConnection();
            } else {
                throw new Exception('Connexion DB non disponible pour PortModule');
            }
        } else {
            $this->db = $db;
        }
        
        $this->loadTransport();
    }
    
    private function loadTransport() {
        // Utiliser la factory function si disponible
        if (function_exists('createTransportInstance')) {
            $this->transport = createTransportInstance();
        } else {
            // Fallback - chargement manuel
            $transport_file = dirname(__FILE__) . '/Transport.php';
            if (!file_exists($transport_file)) {
                throw new Exception('Classe Transport non trouvée: ' . $transport_file);
            }
            
            require_once $transport_file;
            
            if (!class_exists('Transport')) {
                throw new Exception('Classe Transport non chargée');
            }
            
            $this->transport = new Transport($this->db);
        }
    }
    
    public function getTransport() {
        return $this->transport;
    }
    
    public function calculate($params) {
        return $this->transport->calculateAll($params);
    }
}
?>

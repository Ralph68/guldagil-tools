<?php
/**
 * Titre: Module calculateur de frais de port
 * Chemin: /features/port/PortModule.php
 */

require_once __DIR__ . '/../../core/Module.php';

class PortModule extends Module {
    private $transport;
    
    public function __construct($config) {
        parent::__construct($config);
        $this->loadTransport();
    }
    
    public function handle($uri) {
        $parts = explode('/', $uri);
        $action = $parts[1] ?? 'index';
        
        switch ($action) {
            case 'ajax':
                $this->handleAjax();
                break;
            case 'calculate':
                $this->handleCalculate();
                break;
            default:
                $this->showCalculator();
        }
    }
    
    private function loadTransport() {
        $transportPath = __DIR__ . '/Transport.php';
        if (file_exists($transportPath)) {
            require_once $transportPath;
            $this->transport = new Transport($this->getDatabase());
        } else {
            throw new Exception('Classe Transport non trouvée');
        }
    }
    
    private function handleAjax() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        
        require_once __DIR__ . '/ajax/calculate.php';
    }
    
    private function handleCalculate() {
        $results = null;
        $errors = [];
        
        if ($_POST) {
            $params = $this->sanitizeInput($_POST);
            $errors = $this->validateInput($params);
            
            if (empty($errors)) {
                try {
                    $results = $this->transport->calculateAll($params);
                } catch (Exception $e) {
                    $errors[] = 'Erreur de calcul: ' . $e->getMessage();
                }
            }
        }
        
        $this->render('results', compact('results', 'errors', 'params'));
    }
    
    private function showCalculator() {
        $this->render('calculator');
    }
    
    private function sanitizeInput($data) {
        return [
            'departement' => str_pad(trim($data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($data['poids'] ?? 0),
            'type' => strtolower(trim($data['type'] ?? 'colis')),
            'adr' => ($data['adr'] ?? 'non') === 'oui',
            'option_sup' => trim($data['option_sup'] ?? 'standard'),
            'enlevement' => isset($data['enlevement']),
            'palettes' => max(0, intval($data['palettes'] ?? 0))
        ];
    }
    
    private function validateInput($data) {
        $errors = [];
        
        if (empty($data['departement']) || !preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5])$/', $data['departement'])) {
            $errors[] = 'Département invalide';
        }
        
        if ($data['poids'] <= 0 || $data['poids'] > 32000) {
            $errors[] = 'Poids doit être entre 0.1 et 32000 kg';
        }
        
        if (!in_array($data['type'], ['colis', 'palette'])) {
            $errors[] = 'Type d\'envoi invalide';
        }
        
        return $errors;
    }
}

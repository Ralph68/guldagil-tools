<?php
require_once __DIR__ . '/../Services/Calculators/XPOCalculator.php';
require_once __DIR__ . '/../Services/Calculators/HeppnerCalculator.php';
require_once __DIR__ . '/../Services/Calculators/KNCalculator.php';

class Transport {
    private array $calculators = [];
    public array $debug = [];
    
    public function __construct(PDO $db) {
        $this->calculators = [
            'xpo' => new XPOCalculator($db),
            'heppner' => new HeppnerCalculator($db),
            'kn' => new KNCalculator($db)
        ];
    }
    
    public function calculateAll(array $params): array {
        $results = [];
        foreach ($this->calculators as $carrier => $calc) {
            $results[$carrier] = $calc->calculate($params);
        }
        
        return [
            'results' => $results,
            'debug' => $this->debug,
            'best' => null
        ];
    }
}
?>

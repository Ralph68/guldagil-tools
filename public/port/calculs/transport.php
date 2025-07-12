<?php
require_once __DIR__ . '/../Services/TransportService.php';

class Transport {
    private $service;
    public $debug = [];
    
    public function __construct(PDO $db) {
        $this->service = new TransportService($db);
    }
    
    public function calculateAll(array $params): array {
        $result = $this->service->calculateAll($params);
        $this->debug = $result['debug'];
        return $result;
    }
}
?>

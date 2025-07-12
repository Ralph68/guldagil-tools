<?php
/**
 * Titre: Transport Principal - Compatible index.php
 * Chemin: /public/port/calculs/transport.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../Services/Calculators/XPOCalculator.php';
require_once __DIR__ . '/../Services/Calculators/HeppnerCalculator.php';
require_once __DIR__ . '/../Services/Calculators/KNCalculator.php';

class TransportFactory {
    public static function create(PDO $db): TransportService {
        return new TransportService(
            new DatabaseRepository($db),
            new MemoryCache()
        );
    }
}

/**
 * WRAPPER RÉTROCOMPATIBILITÉ - API identique
 */
class Transport {
    private TransportService $service;
    public array $debug = [];
    
    public function __construct(PDO $db) {
        $this->service = TransportFactory::create($db);
    }
    
    /**
     * SIGNATURE OBLIGATOIRE - Compatible calculateAll()
     */
    public function calculateAll(array $params): array {
        $result = $this->service->calculateAll($params);
        $this->debug = $result['debug'];
        return $result;
    }
}

<?php
/**
 * Titre: Transport Principal - Compatible index.php
 * Chemin: /public/port/calculs/transport.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/../Services/TransportInterfaces.php';
require_once __DIR__ . '/../Services/DatabaseRepository.php';
require_once __DIR__ . '/../Services/TransportService.php';

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

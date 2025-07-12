<?php
/**
 * Titre: Service Transport Principal
 * Chemin: /public/port/Services/TransportService.php
 * Version: 0.5 beta + build auto
 */

require_once __DIR__ . '/TransportInterfaces.php';

class MemoryCache implements CacheManager {
    private array $cache = [];
    private const MAX_SIZE = 500;
    
    public function get(string $key): ?array {
        return $this->cache[$key] ?? null;
    }
    
    public function set(string $key, array $data): void {
        if (count($this->cache) >= self::MAX_SIZE) {
            $this->cache = array_slice($this->cache, -250, null, true);
        }
        $this->cache[$key] = $data;
    }
}

class TransportService {
    private array $carriers = ['xpo', 'heppner', 'kn'];
    public array $debug = [];
    
    public function __construct(
        private TransportRepository $repository,
        private CacheManager $cache
    ) {}
    
    /**
     * SIGNATURE OBLIGATOIRE - Compatible calculateAll()
     */
    public function calculateAll(array $params): array {
        $this->debug = ['params' => $params];
        
        $cacheKey = md5(serialize($params));
        if ($cached = $this->cache->get($cacheKey)) {
            $this->debug['cache_hit'] = true;
            return $cached;
        }
        
        $params = $this->normalizeParams($params);
        $results = [];
        
        foreach ($this->carriers as $carrier) {
            $results[$carrier] = $this->calculateCarrier($carrier, $params);
        }
        
        $response = [
            'results' => $results,
            'debug' => $this->debug,
            'best' => $this->findBest($results)
        ];
        
        $this->cache->set($cacheKey, $response);
        return $response;
    }
    
    private function calculateCarrier(string $carrier, array $params): ?float {
        try {
            // 1. Validation
            if (!$this->repository->validateConstraints($carrier, $params)) {
                $this->debug[$carrier]['rejected'] = 'constraints';
                return null;
            }
            
            // 2. Tarif de base
            $basePrice = $this->repository->getBasePrice($carrier, $params);
            if (!$basePrice) {
                $this->debug[$carrier]['rejected'] = 'no_rate';
                return null;
            }
            
            // 3. LOGIQUE GULDAGIL - Optimisation 100kg
            if ($params['poids'] <= 100) {
                $price100kg = $this->repository->getBasePrice($carrier, array_merge($params, ['poids' => 100]));
                if ($price100kg && $price100kg < $basePrice) {
                    $basePrice = $price100kg;
                    $this->debug[$carrier]['optimization'] = '100kg_rate';
                }
            }
            
            // 4. Ratio poids > 100kg
            if ($params['poids'] > 100) {
                $ratio = $params['poids'] / 100;
                $basePrice *= $ratio;
                $this->debug[$carrier]['weight_ratio'] = $ratio;
            }
            
            // 5. Options et taxes
            $finalPrice = $this->repository->applyOptions($carrier, $basePrice, $params);
            
            $this->debug[$carrier]['final_price'] = $finalPrice;
            return $finalPrice;
            
        } catch (Exception $e) {
            $this->debug[$carrier]['error'] = $e->getMessage();
            return null;
        }
    }
    
    private function normalizeParams(array $params): array {
        return [
            'departement' => str_pad($params['departement'], 2, '0', STR_PAD_LEFT),
            'poids' => (float)$params['poids'],
            'type' => $params['type'] ?? 'colis',
            'adr' => (bool)($params['adr'] ?? false),
            'enlevement' => (bool)($params['enlevement'] ?? false)
        ];
    }
    
    private function findBest(array $results): ?array {
        $valid = array_filter($results, fn($p) => $p !== null);
        if (empty($valid)) return null;
        
        $best = array_keys($valid, min($valid))[0];
        return ['carrier' => $best, 'price' => $valid[$best]];
    }
}

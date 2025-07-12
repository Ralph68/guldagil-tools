<?php
/**
 * Titre: Calculateur XPO - Logique par tranches
 * Chemin: /public/port/Services/Calculators/XPOCalculator.php
 * Version: 0.5 beta + build auto
 */

declare(strict_types=1);

class XPOCalculator {
    private PDO $db;
    private array $cache = [];
    private const CACHE_TTL = 3600; // 1 heure
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function calculate(array $params): ?float {
        if (!$this->validateConstraints($params)) {
            return null;
        }

        $basePrice = $this->getBasePriceWithOptimization($params);
        if ($basePrice === null) {
            return null;
        }

        $finalPrice = $this->calculateWeightBasedPrice($basePrice, $params);
        return $this->applyAllOptions($finalPrice, $params);
    }

    private function validateConstraints(array $params): bool {
        $constraintsKey = 'constraints_' . serialize([
            'transporteur' => 'XPO',
            'departement' => $params['departement']
        ]);

        if (!isset($this->cache[$constraintsKey])) {
            $stmt = $this->db->prepare("
                SELECT departements_blacklistes, poids_minimum, poids_maximum 
                FROM gul_taxes_transporteurs 
                WHERE transporteur = 'XPO'
            ");
            $stmt->execute();
            $this->cache[$constraintsKey] = $stmt->fetch() ?: [];
        }

        $constraints = $this->cache[$constraintsKey];
        
        if (isset($constraints['departements_blacklistes'])) {
            $blacklisted = explode(',', $constraints['departements_blacklistes']);
            if (in_array($params['departement'], $blacklisted)) {
                return false;
            }
        }

        $minPoids = $constraints['poids_minimum'] ?? 1;
        $maxPoids = $constraints['poids_maximum'] ?? 32000;

        return $params['poids'] >= $minPoids && $params['poids'] <= $maxPoids;
    }

    private function getBasePriceWithOptimization(array $params): ?float {
        $rateCacheKey = 'rates_' . $params['departement'];
        
        if (!isset($this->cache[$rateCacheKey])) {
            $stmt = $this->db->prepare("
                SELECT * FROM gul_xpo_rates 
                WHERE num_departement = ?
                LIMIT 1
            ");
            $stmt->execute([$params['departement']]);
            $this->cache[$rateCacheKey] = $stmt->fetch() ?: null;
        }

        $row = $this->cache[$rateCacheKey];
        if (!$row) {
            return null;
        }

        $weight = $params['poids'];
        $priceField = match(true) {
            $weight <= 99 => 'tarif_0_99',
            $weight <= 499 => 'tarif_100_499',
            $weight <= 999 => 'tarif_500_999',
            $weight <= 1999 => 'tarif_1000_1999',
            default => 'tarif_2000_2999'
        };

        $basePrice = $row[$priceField];

        // Optimisation pour ≤ 100kg
        if ($weight <= 100) {
            $price100kg = $row['tarif_100_499'] ?? null;
            if ($price100kg !== null && $price100kg < $basePrice) {
                $basePrice = $price100kg;
            }
        }

        return $basePrice;
    }

    private function calculateWeightBasedPrice(float $basePrice, array $params): float {
        if ($params['poids'] <= 100) {
            return $basePrice;
        }
        
        // Ratio pour poids > 100kg
        return $basePrice * ($params['poids'] / 100);
    }

    private function applyAllOptions(float $price, array $params): float {
        $taxesCacheKey = 'taxes_XPO';
        
        if (!isset($this->cache[$taxesCacheKey])) {
            $stmt = $this->db->prepare("SELECT * FROM gul_taxes_transporteurs WHERE transporteur = 'XPO'");
            $stmt->execute();
            $this->cache[$taxesCacheKey] = $stmt->fetch() ?: [];
        }

        $taxes = $this->cache[$taxesCacheKey];
        $finalPrice = $price;

        // Application des taxes de base
        foreach (['surete', 'participation_transition_energetique'] as $taxType) {
            if (isset($taxes[$taxType]) && $taxes[$taxType] > 0) {
                $finalPrice += $taxes[$taxType];
            }
        }

        // Majoration Île-de-France
        if (
            isset($taxes['majoration_idf_valeur']) 
            && $taxes['majoration_idf_valeur'] > 0 
            && $this->isRegionParisienne($params['departement'])
        ) {
            $idfValue = $taxes['majoration_idf_valeur'];
            $finalPrice = $taxes['majoration_idf_type'] === 'Pourcentage'
                ? $finalPrice * (1 + $idfValue / 100)
                : $finalPrice + $idfValue;
        }

        // ADR si applicable
        if (
            $params['adr'] 
            && isset($taxes['majoration_adr_taux']) 
            && $taxes['majoration_adr_taux'] > 0
        ) {
            $finalPrice *= (1 + $taxes['majoration_adr_taux'] / 100);
        }

        return $finalPrice;
    }
}

<?php
/**
 * Titre: Calculateur Heppner - Logique par tranches
 * Chemin: /public/port/Services/Calculators/HeppnerCalculator.php
 * Version: 0.5 beta + build auto
 */

declare(strict_types=1);

class HeppnerCalculator {
    private PDO $db;
    private array $cache = [];
    private const WEIGHT_THRESHOLD = 100;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function calculate(array $params): ?float {
        if (!$this->validateConstraints($params)) {
            return null;
        }
        
        $basePrice = $this->getBasePrice($params);
        if ($basePrice === null) {
            return null;
        }
        
        return $this->processPrice($basePrice, $params['poids'], $params);
    }
    
    private function validateConstraints(array $params): bool {
        $constraintsKey = 'constraints_heppner';
        
        if (!isset($this->cache[$constraintsKey])) {
            $stmt = $this->db->prepare("
                SELECT departements_blacklistes, poids_minimum, poids_maximum 
                FROM gul_taxes_transporteurs 
                WHERE transporteur = 'Heppner'
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
    
    private function processPrice(?float $price, float $weight, array $params): ?float {
        if (!$price || $weight <= 0) {
            return null;
        }
        
        // LOGIQUE HEPPNER : ≤100kg = FORFAIT, >100kg = au poids au 100kg
        if ($weight <= self::WEIGHT_THRESHOLD) {
            // Forfait : on garde le prix de la tranche exacte
            return $this->applyOptions($price, $params);
        } else {
            // Au poids au 100kg : ratio sur tarif 100kg
            $price *= ($weight / self::WEIGHT_THRESHOLD);
            return $this->applyOptions($price, $params);
        }
    }
    
    private function getBasePrice(array $params): ?float {
        $rateCacheKey = 'rates_heppner_' . $params['departement'];
        
        if (!isset($this->cache[$rateCacheKey])) {
            $stmt = $this->db->prepare("
                SELECT * FROM gul_heppner_rates
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
        
        $poids = $params['poids'];
        
        // Sélection par tranche selon structure BDD réelle
        $priceField = match(true) {
            $poids <= 9 => 'tarif_0_9',
            $poids <= 19 => 'tarif_10_19', 
            $poids <= 29 => 'tarif_20_29',
            $poids <= 39 => 'tarif_30_39',
            $poids <= 49 => 'tarif_40_49',
            $poids <= 59 => 'tarif_50_59',
            $poids <= 69 => 'tarif_60_69',
            $poids <= 79 => 'tarif_70_79',
            $poids <= 89 => 'tarif_80_89',
            $poids <= 99 => 'tarif_90_99',
            $poids <= 299 => 'tarif_100_299',
            $poids <= 499 => 'tarif_300_499',
            $poids <= 999 => 'tarif_500_999',
            default => 'tarif_1000_1999'
        };

        return $this->convertToFloat($row[$priceField]);
    }

    /**
     * Convertit une valeur en float avec validation
     */
    private function convertToFloat($value): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_numeric($value)) {
            $floatValue = (float) $value;
            return $floatValue > 0 ? $floatValue : null;
        }
        
        return null;
    }
    
    private function applyOptions(float $price, array $params): float {
        $taxesCacheKey = 'taxes_heppner';
        
        if (!isset($this->cache[$taxesCacheKey])) {
            $stmt = $this->db->prepare("
                SELECT * FROM gul_taxes_transporteurs 
                WHERE transporteur = 'Heppner'
                LIMIT 1
            ");
            $stmt->execute();
            $this->cache[$taxesCacheKey] = $stmt->fetch() ?: [];
        }

        $taxes = $this->cache[$taxesCacheKey];
        $finalPrice = $price;

        // Application des taxes de base
        foreach (['surete', 'participation_transition_energetique', 'contribution_sanitaire'] as $taxType) {
            if (isset($taxes[$taxType]) && $taxes[$taxType] > 0) {
                $finalPrice += (float) $taxes[$taxType];
            }
        }

        // Majoration Île-de-France
        if (
            isset($taxes['majoration_idf_valeur']) 
            && $taxes['majoration_idf_valeur'] > 0 
            && $this->isRegionParisienne($params['departement'], $taxes)
        ) {
            $idfValue = (float) $taxes['majoration_idf_valeur'];
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
            $finalPrice *= (1 + (float) $taxes['majoration_adr_taux'] / 100);
        }

        return $finalPrice;
    }
    
    /**
     * Vérifie si le département est en région parisienne via BDD
     */
    private function isRegionParisienne(string $departement, array $taxes): bool {
        if (!isset($taxes['majoration_idf_departements'])) {
            return false;
        }
        
        $departementsIdf = explode(',', $taxes['majoration_idf_departements']);
        return in_array($departement, $departementsIdf);
    }
}

<?php
/**
 * Titre: Calculateur Heppner - Optimisation des fourchettes de poids
 * Chemin: /public/port/Services/Calculators/HeppnerCalculator.php
 * Version: 0.5 beta + build auto
 */

declare(strict_types=1);

class HeppnerCalculator {
    private PDO $db;
    private array $cache = [];
    private const WEIGHT_THRESHOLD = 100;
    
    // Fourchettes de poids Heppner pour optimisation (structure fine)
    private const WEIGHT_BRACKETS = [
        ['min' => 1, 'max' => 9, 'field' => 'tarif_0_9', 'label' => '0-9kg'],
        ['min' => 10, 'max' => 19, 'field' => 'tarif_10_19', 'label' => '10-19kg'],
        ['min' => 20, 'max' => 29, 'field' => 'tarif_20_29', 'label' => '20-29kg'],
        ['min' => 30, 'max' => 39, 'field' => 'tarif_30_39', 'label' => '30-39kg'],
        ['min' => 40, 'max' => 49, 'field' => 'tarif_40_49', 'label' => '40-49kg'],
        ['min' => 50, 'max' => 59, 'field' => 'tarif_50_59', 'label' => '50-59kg'],
        ['min' => 60, 'max' => 69, 'field' => 'tarif_60_69', 'label' => '60-69kg'],
        ['min' => 70, 'max' => 79, 'field' => 'tarif_70_79', 'label' => '70-79kg'],
        ['min' => 80, 'max' => 89, 'field' => 'tarif_80_89', 'label' => '80-89kg'],
        ['min' => 90, 'max' => 99, 'field' => 'tarif_90_99', 'label' => '90-99kg'],
        ['min' => 100, 'max' => 299, 'field' => 'tarif_100_299', 'label' => '100-299kg'],
        ['min' => 300, 'max' => 499, 'field' => 'tarif_300_499', 'label' => '300-499kg'],
        ['min' => 500, 'max' => 999, 'field' => 'tarif_500_999', 'label' => '500-999kg'],
        ['min' => 1000, 'max' => 1999, 'field' => 'tarif_1000_1999', 'label' => '1000-1999kg']
    ];
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Calcul optimisé avec comparaison des fourchettes
     */
    public function calculateWithOptimization(array $params): ?array {
        if (!$this->validateConstraints($params)) {
            return null;
        }

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

        // Calculer le prix pour le poids réel
        $realPrice = $this->calculateFinalPriceForWeight($params, $row);
        if ($realPrice === null) {
            return null;
        }

        // Comparer avec toutes les fourchettes applicables
        $optimization = $this->findBestWeightBracket($params, $row);
        
        return [
            'price' => $optimization['price'],
            'original_price' => $realPrice,
            'weight_declared' => $params['poids'],
            'weight_optimal' => $optimization['optimal_weight'],
            'savings' => $realPrice - $optimization['price'],
            'optimization_message' => $optimization['message'],
            'bracket_info' => $optimization['bracket_info']
        ];
    }

    /**
     * Méthode compatible avec l'ancienne signature
     */
    public function calculate(array $params): ?float {
        $result = $this->calculateWithOptimization($params);
        return $result ? $result['price'] : null;
    }

    /**
     * Trouve la meilleure fourchette de poids pour Heppner
     * Spécificité: forfait ≤100kg, au poids >100kg
     */
    private function findBestWeightBracket(array $params, array $row): array {
        $realWeight = $params['poids'];
        $bestPrice = PHP_FLOAT_MAX;
        $bestWeight = $realWeight;
        $bestBracket = null;
        $optimizationMessage = '';

        // Pour Heppner: logique spéciale forfait vs poids
        foreach (self::WEIGHT_BRACKETS as $bracket) {
            // Ne pas tester des fourchettes inférieures au poids réel
            if ($realWeight > $bracket['max']) {
                continue;
            }

            // Poids de test pour cette fourchette
            $testWeight = max($realWeight, $bracket['min']);
            
            // Calculer le prix final pour ce poids de test
            $testParams = array_merge($params, ['poids' => $testWeight]);
            $testPrice = $this->calculateFinalPriceForWeight($testParams, $row);
            
            if ($testPrice !== null && $testPrice < $bestPrice) {
                $bestPrice = $testPrice;
                $bestWeight = $testWeight;
                $bestBracket = $bracket;
            }
        }

        // Optimisation spéciale Heppner: tester aussi le seuil 100kg si applicable
        if ($realWeight <= 100) {
            // Tester si déclarer exactement 100kg est plus avantageux
            $test100Params = array_merge($params, ['poids' => 100]);
            $test100Price = $this->calculateFinalPriceForWeight($test100Params, $row);
            
            if ($test100Price !== null && $test100Price < $bestPrice) {
                $bestPrice = $test100Price;
                $bestWeight = 100;
                $bestBracket = ['min' => 100, 'max' => 299, 'label' => '100kg forfait'];
            }
        }

        // Générer le message d'optimisation
        if ($bestWeight !== $realWeight && $bestBracket) {
            $savings = $this->calculateFinalPriceForWeight($params, $row) - $bestPrice;
            if ($savings > 0.01) { // Économie significative
                $optimizationMessage = sprintf(
                    "💡 Optimisation Heppner: Déclarer %dkg (%s) - Économie: %.2f€",
                    $bestWeight,
                    $bestBracket['label'],
                    $savings
                );
                
                // Message spécial pour passage forfait/poids
                if ($realWeight <= 100 && $bestWeight == 100) {
                    $optimizationMessage .= " (forfait 100kg plus avantageux)";
                }
            }
        }

        return [
            'price' => $bestPrice,
            'optimal_weight' => $bestWeight,
            'message' => $optimizationMessage,
            'bracket_info' => $bestBracket
        ];
    }

    /**
     * Calcule le prix final pour un poids donné avec logique Heppner
     */
    private function calculateFinalPriceForWeight(array $params, array $row): ?float {
        $basePrice = $this->getBasePriceFromRow($params['poids'], $row);
        if ($basePrice === null) {
            return null;
        }

        $processedPrice = $this->processPrice($basePrice, $params['poids'], $params);
        return $processedPrice;
    }

    /**
     * Récupère le prix de base selon la fourchette Heppner
     */
    private function getBasePriceFromRow(float $poids, array $row): ?float {
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

<?php
/**
 * Titre: Calculateur XPO - Optimisation des fourchettes de poids
 * Chemin: /public/port/Services/Calculators/XPOCalculator.php
 * Version: 0.5 beta + build auto
 */

declare(strict_types=1);

class XPOCalculator {
    private PDO $db;
    private array $cache = [];
    private const CACHE_TTL = 3600; // 1 heure
    
    // Fourchettes de poids XPO pour optimisation
    private const WEIGHT_BRACKETS = [
        ['min' => 1, 'max' => 99, 'field' => 'tarif_0_99', 'label' => '0-99kg'],
        ['min' => 100, 'max' => 499, 'field' => 'tarif_100_499', 'label' => '100-499kg'],
        ['min' => 500, 'max' => 999, 'field' => 'tarif_500_999', 'label' => '500-999kg'],
        ['min' => 1000, 'max' => 1999, 'field' => 'tarif_1000_1999', 'label' => '1000-1999kg'],
        ['min' => 2000, 'max' => 2999, 'field' => 'tarif_2000_2999', 'label' => '2000-2999kg']
    ];
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Calcul optimisé avec comparaison des fourchettes
     * Retourne un array avec prix et informations d'optimisation
     */
    public function calculateWithOptimization(array $params): ?array {
        if (!$this->validateConstraints($params)) {
            return null;
        }

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
     * Trouve la meilleure fourchette de poids (prix final le plus bas)
     */
    private function findBestWeightBracket(array $params, array $row): array {
        $realWeight = $params['poids'];
        $bestPrice = PHP_FLOAT_MAX;
        $bestWeight = $realWeight;
        $bestBracket = null;
        $optimizationMessage = '';

        foreach (self::WEIGHT_BRACKETS as $bracket) {
            // Vérifier si cette fourchette est applicable
            if ($realWeight > $bracket['max']) {
                continue; // On ne peut pas déclarer un poids inférieur au poids réel
            }

            $testWeight = $realWeight <= $bracket['min'] ? $bracket['min'] : $realWeight;
            
            // Si le poids réel est dans cette fourchette, tester le minimum de la fourchette
            if ($realWeight >= $bracket['min'] && $realWeight <= $bracket['max']) {
                $testWeight = $bracket['min'];
            }
            
            // Calculer le prix final pour ce poids de test
            $testParams = array_merge($params, ['poids' => $testWeight]);
            $testPrice = $this->calculateFinalPriceForWeight($testParams, $row);
            
            if ($testPrice !== null && $testPrice < $bestPrice) {
                $bestPrice = $testPrice;
                $bestWeight = $testWeight;
                $bestBracket = $bracket;
            }
        }

        // Générer le message d'optimisation
        if ($bestWeight !== $realWeight && $bestBracket) {
            $savings = $this->calculateFinalPriceForWeight($params, $row) - $bestPrice;
            if ($savings > 0.01) { // Économie significative
                $optimizationMessage = sprintf(
                    "💡 Optimisation: Déclarer %dkg (fourchette %s) - Économie: %.2f€",
                    $bestWeight,
                    $bestBracket['label'],
                    $savings
                );
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
     * Calcule le prix final pour un poids donné (base + taxes + options)
     */
    private function calculateFinalPriceForWeight(array $params, array $row): ?float {
        $basePrice = $this->getBasePriceFromRow($params['poids'], $row);
        if ($basePrice === null) {
            return null;
        }

        $weightBasedPrice = $this->calculateWeightBasedPrice($basePrice, $params);
        return $this->applyAllOptions($weightBasedPrice, $params);
    }

    /**
     * Récupère le prix de base depuis la ligne de tarifs
     */
    private function getBasePriceFromRow(float $weight, array $row): ?float {
        $priceField = match(true) {
            $weight <= 99 => 'tarif_0_99',
            $weight <= 499 => 'tarif_100_499',
            $weight <= 999 => 'tarif_500_999',
            $weight <= 1999 => 'tarif_1000_1999',
            default => 'tarif_2000_2999'
        };

        return $this->convertToFloat($row[$priceField]);
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
                $finalPrice += (float) $taxes[$taxType];
            }
        }

        // Majoration Île-de-France
        if (
            isset($taxes['majoration_idf_valeur']) 
            && $taxes['majoration_idf_valeur'] > 0 
            && $this->isRegionParisienne($params['departement'])
        ) {
            $idfValue = (float) $taxes['majoration_idf_valeur'];
            $finalPrice = $taxes['majoration_idf_type'] === 'Pourcentage'
                ? $finalPrice * (1 + $idfValue / 100)
                : $finalPrice + $idfValue;
        }

        // Gestion palette EUR
        if ($params['type'] === 'palette' && isset($params['palette_eur'])) {
            $paletteEurCount = (int) $params['palette_eur'];
            if ($paletteEurCount > 0) {
                $consigneTarif = $this->getPaletteEurTarif();
                $finalPrice += $paletteEurCount * $consigneTarif;
            }
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
     * Récupère le tarif consigne palette EUR pour XPO
     */
    private function getPaletteEurTarif(): float {
        static $tarifConsigne = null;
        
        if ($tarifConsigne === null) {
            try {
                $stmt = $this->db->prepare("
                    SELECT consigne_palette_eur 
                    FROM gul_taxes_transporteurs 
                    WHERE transporteur = 'XPO'
                    LIMIT 1
                ");
                $stmt->execute();
                $result = $stmt->fetch();
                
                $tarifConsigne = $result && isset($result['consigne_palette_eur']) 
                    ? (float) $result['consigne_palette_eur'] 
                    : 0.00;
                    
            } catch (PDOException $e) {
                $tarifConsigne = 0.00;
            }
        }
        
        return $tarifConsigne;
    }

    /**
     * Vérifie si le département est en région parisienne via BDD
     */
    private function isRegionParisienne(string $departement): bool {
        $taxesCacheKey = 'taxes_XPO';
        
        if (!isset($this->cache[$taxesCacheKey])) {
            $stmt = $this->db->prepare("SELECT * FROM gul_taxes_transporteurs WHERE transporteur = 'XPO'");
            $stmt->execute();
            $this->cache[$taxesCacheKey] = $stmt->fetch() ?: [];
        }

        $taxes = $this->cache[$taxesCacheKey];
        
        if (!isset($taxes['majoration_idf_departements'])) {
            return false;
        }
        
        $departementsIdf = explode(',', $taxes['majoration_idf_departements']);
        return in_array($departement, $departementsIdf);
    }
}

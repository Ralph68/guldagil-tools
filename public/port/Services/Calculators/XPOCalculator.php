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

        // FIX: Conversion explicite en float avec validation
        $basePrice = $this->convertToFloat($row[$priceField]);
        if ($basePrice === null) {
            return null;
        }

        // Optimisation pour ≤ 100kg
        if ($weight <= 100) {
            $price100kg = $this->convertToFloat($row['tarif_100_499'] ?? null);
            if ($price100kg !== null && $price100kg < $basePrice) {
                $basePrice = $price100kg;
            }
        }

        return $basePrice;
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
            && $this->isRegionParisienne($params['departement'], $taxes)
        ) {
            $idfValue = (float) $taxes['majoration_idf_valeur'];
            $finalPrice = $taxes['majoration_idf_type'] === 'Pourcentage'
                ? $finalPrice * (1 + $idfValue / 100)
                : $finalPrice + $idfValue;
        }

        // NOUVEAU : Gestion palette EUR
        if ($params['type'] === 'palette' && isset($params['palette_eur'])) {
            $paletteEurCount = (int) $params['palette_eur'];
            if ($paletteEurCount > 0) {
                // Recherche tarif consigne XPO en BDD
                $consigneTarif = $this->getPaletteEurTarif();
                $finalPrice += $paletteEurCount * $consigneTarif;
            }
            // Si palette_eur = 0 : palette perdue, pas de consigne
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
                    : 0.00; // 0 par défaut si pas en BDD
                    
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
        
        // Utilise le cache déjà chargé dans applyAllOptions
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

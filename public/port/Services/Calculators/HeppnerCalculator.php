<?php
/**
 * Titre: Calculateur Heppner - Logique par tranches
 * Chemin: /public/port/Services/Calculators/HeppnerCalculator.php
 * Version: 0.5 beta + build auto
 */

class HeppnerCalculator {
    private static $cacheRates = [];
    private static $cacheTaxes = [];
    private const WEIGHT_THRESHOLD = 100;
    
    public function __construct(private PDO $db) {}
    
    public function calculate(array $params): ?float {
        if (!$this->validateConstraints($params)) return null;
        
        $cacheKey = "heppner_rate_{$params['departement']}_{$params['poids']}";
        $basePrice = self::$cacheRates[$cacheKey] ?? null;
        
        if ($basePrice === null) {
            $basePrice = $this->getBasePrice($params);
            self::$cacheRates[$cacheKey] = $basePrice;
        }
        
        return $this->processPrice($basePrice, $params['poids'], $params);
    }
    
    private function processPrice(?float $price, float $weight, array $params): ?float {
        if (!$price || $weight <= 0) return null;
        
        if ($weight <= self::WEIGHT_THRESHOLD) {
            $price100kg = $this->getBasePrice(['departement' => $params['departement'], 'poids' => self::WEIGHT_THRESHOLD]);
            $price = min($price, $price100kg ?? $price);
        } else {
            $price *= ($weight / self::WEIGHT_THRESHOLD);
        }
        
        return $this->applyOptions($price, $params);
    }
    
    private function getBasePrice(array $params): ?float {
        $stmt = $this->db->prepare("
            SELECT *
            FROM gul_heppner_rates
            WHERE num_departement = ?
            LIMIT 1
        ");
        $stmt->execute([$params['departement']]);
        $row = $stmt->fetch();
        
        if (!$row) return null;
        
        $poids = $params['poids'];
        return $row['tarif_0_9'] ?? $row['tarif_10_19'] ?? $row['tarif_20_29'] ?? 
               $row['tarif_30_39'] ?? $row['tarif_40_49'] ?? $row['tarif_50_59'] ??
               $row['tarif_60_69'] ?? $row['tarif_70_79'] ?? $row['tarif_80_89'] ??
               $row['tarif_90_99'] ?? $row['tarif_100_299'] ?? $row['tarif_300_499'] ??
               $row['tarif_500_999'] ?? $row['tarif_1000_1999'];
    }
    
    private function applyOptions(float $price, array $params): float {
        static $taxes = null;
        
        if ($taxes === null) {
            $stmt = $this->db->prepare("
                SELECT surete, participation_transition_energetique, 
                       contribution_sanitaire, majoration_idf_valeur
                FROM gul_taxes_transporteurs 
                WHERE transporteur = 'Heppner'
                LIMIT 1
            ");
            $stmt->execute();
            $taxes = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        $total = $price;
        foreach ($taxes as $tax => $value) {
            if ($value > 0) {
                $total += $value;
                if ($tax === 'majoration_idf_valeur' && !$this->isRegionParisienne($params['departement'])) {
                    $total -= $value;
                }
            }
        }
        
        return round($total, 2);
    }
    
    private function isRegionParisienne(string $dept): bool {
        static $idfDepts = null;
        
        if ($idfDepts === null) {
            $stmt = $this->db->prepare("
                SELECT majoration_idf_departements
                FROM gul_taxes_transporteurs
                WHERE transporteur = 'Heppner'
                LIMIT 1
            ");
            $stmt->execute();
            $idfDepts = explode(',', $stmt->fetchColumn());
        }
        
        return in_array($dept, $idfDepts);
    }
}

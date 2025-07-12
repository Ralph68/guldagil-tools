<?php
/**
 * Titre: Calculateur Kuehne+Nagel - Logique par tranches
 * Chemin: /public/port/Services/Calculators/KNCalculator.php
 * Version: 0.5 beta + build auto
 */

class KNCalculator {
    public function __construct(private PDO $db) {}
    
    public function calculate(array $params): ?float {
        if (!$this->validateConstraints($params)) return null;
        
        $basePrice = $this->getBasePrice($params);
        if (!$basePrice) return null;
        
        // LOGIQUE GULDAGIL
        if ($params['poids'] <= 100) {
            $price100kg = $this->getBasePrice(array_merge($params, ['poids' => 100]));
            if ($price100kg && $price100kg < $basePrice) {
                $basePrice = $price100kg;
            }
        }
        
        if ($params['poids'] > 100) {
            $basePrice *= ($params['poids'] / 100);
        }
        
        return $this->applyOptions($basePrice, $params);
    }
    
    private function validateConstraints(array $params): bool {
        $stmt = $this->db->prepare("
            SELECT departements_blacklistes, poids_minimum, poids_maximum 
            FROM gul_taxes_transporteurs 
            WHERE transporteur = 'Kuehne + Nagel'
        ");
        $stmt->execute();
        $constraints = $stmt->fetch();
        
        if (!$constraints) return true;
        
        if ($constraints['departements_blacklistes']) {
            $blacklisted = explode(',', $constraints['departements_blacklistes']);
            if (in_array($params['departement'], $blacklisted)) return false;
        }
        
        return $params['poids'] >= ($constraints['poids_minimum'] ?? 1) 
            && $params['poids'] <= ($constraints['poids_maximum'] ?? 32000);
    }
    
    private function getBasePrice(array $params): ?float {
        $stmt = $this->db->prepare("
            SELECT * FROM gul_kn_rates 
            WHERE num_departement = ?
            LIMIT 1
        ");
        $stmt->execute([$params['departement']]);
        $row = $stmt->fetch();
        
        if (!$row) return null;
        
        $poids = $params['poids'];
        
        // Vérifier si les tarifs existent (KN a beaucoup de NULL)
        if ($poids <= 9 && $row['tarif_0_9']) return $row['tarif_0_9'];
        if ($poids <= 19 && $row['tarif_10_19']) return $row['tarif_10_19'];
        if ($poids <= 29 && $row['tarif_20_29']) return $row['tarif_20_29'];
        if ($poids <= 39 && $row['tarif_30_39']) return $row['tarif_30_39'];
        if ($poids <= 49 && $row['tarif_40_49']) return $row['tarif_40_49'];
        if ($poids <= 59 && $row['tarif_50_59']) return $row['tarif_50_59'];
        if ($poids <= 69 && $row['tarif_60_69']) return $row['tarif_60_69'];
        if ($poids <= 79 && $row['tarif_70_79']) return $row['tarif_70_79'];
        if ($poids <= 89 && $row['tarif_80_89']) return $row['tarif_80_89'];
        if ($poids <= 99 && $row['tarif_90_99']) return $row['tarif_90_99'];
        if ($poids <= 299 && $row['tarif_100_299']) return $row['tarif_100_299'];
        if ($poids <= 499 && $row['tarif_300_499']) return $row['tarif_300_499'];
        if ($poids <= 999 && $row['tarif_500_999']) return $row['tarif_500_999'];
        if ($row['tarif_1000_1999']) return $row['tarif_1000_1999'];
        
        return null; // Pas de tarif disponible
    }
    
    private function applyOptions(float $price, array $params): float {
        $stmt = $this->db->prepare("SELECT * FROM gul_taxes_transporteurs WHERE transporteur = 'Kuehne + Nagel'");
        $stmt->execute();
        $taxes = $stmt->fetch();
        
        if ($taxes) {
            if ($taxes['surete']) $price += $taxes['surete'];
            
            // Région Parisienne/IDF
            if ($this->isRegionParisienne($params['departement']) && $taxes['majoration_idf_valeur']) {
                $price += $taxes['majoration_idf_valeur'];
            }
        }
        
        // ADR
        if ($params['adr'] && $taxes['majoration_adr_taux']) {
            $price *= (1 + $taxes['majoration_adr_taux'] / 100);
        }
        
        return $price;
    }
    
    private function isRegionParisienne(string $dept): bool {
   $stmt = $this->db->prepare("
       SELECT 1 FROM gul_taxes_transporteurs 
       WHERE FIND_IN_SET(?, majoration_idf_departements) > 0 
       LIMIT 1
   ");
   $stmt->execute([$dept]);
   return (bool)$stmt->fetch();
}
}

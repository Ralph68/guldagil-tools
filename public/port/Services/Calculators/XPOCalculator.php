<?php
/**
 * Titre: Calculateur XPO - Logique par tranches
 * Chemin: /public/port/Services/Calculators/XPOCalculator.php
 * Version: 0.5 beta + build auto
 */

class XPOCalculator {
    public function __construct(private PDO $db) {}
    
    public function calculate(array $params): ?float {
        if (!$this->validateConstraints($params)) return null;
        
        $basePrice = $this->getBasePrice($params);
        if (!$basePrice) return null;
        
        // LOGIQUE GULDAGIL - Optimisation 100kg
        if ($params['poids'] <= 100) {
            $price100kg = $this->getBasePrice(array_merge($params, ['poids' => 100]));
            if ($price100kg && $price100kg < $basePrice) {
                $basePrice = $price100kg;
            }
        }
        
        // Ratio poids > 100kg
        if ($params['poids'] > 100) {
            $basePrice *= ($params['poids'] / 100);
        }
        
        return $this->applyOptions($basePrice, $params);
    }
    
    private function validateConstraints(array $params): bool {
        $stmt = $this->db->prepare("
            SELECT departements_blacklistes, poids_minimum, poids_maximum 
            FROM gul_taxes_transporteurs 
            WHERE transporteur = 'XPO'
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
            SELECT * FROM gul_xpo_rates 
            WHERE num_departement = ?
            LIMIT 1
        ");
        $stmt->execute([$params['departement']]);
        $row = $stmt->fetch();
        
        if (!$row) return null;
        
        $poids = $params['poids'];
        
        if ($poids <= 99) return $row['tarif_0_99'];
        if ($poids <= 499) return $row['tarif_100_499'];
        if ($poids <= 999) return $row['tarif_500_999'];
        if ($poids <= 1999) return $row['tarif_1000_1999'];
        return $row['tarif_2000_2999'];
    }
    
    private function applyOptions(float $price, array $params): float {
        // Taxes XPO
        $stmt = $this->db->prepare("SELECT * FROM gul_taxes_transporteurs WHERE transporteur = 'XPO'");
        $stmt->execute();
        $taxes = $stmt->fetch();
        
        if ($taxes) {
            if ($taxes['surete']) $price += $taxes['surete'];
            if ($taxes['participation_transition_energetique']) $price += $taxes['participation_transition_energetique'];
            
            // Région Parisienne
            if ($this->isRegionParisienne($params['departement']) && $taxes['majoration_idf_valeur']) {
                if ($taxes['majoration_idf_type'] === 'Pourcentage') {
                    $price *= (1 + $taxes['majoration_idf_valeur'] / 100);
                } else {
                    $price += $taxes['majoration_idf_valeur'];
                }
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

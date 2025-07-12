<?php
/**
 * Titre: Calculateur XPO - Logique métier dédiée
 * Chemin: /public/port/Services/Calculators/XPOCalculator.php
 * Version: 0.5 beta + build auto
 */

class XPOCalculator {
    public function __construct(private PDO $db) {}
    
    public function calculate(array $params): ?float {
        // 1. Validation contraintes XPO
        if (!$this->validateConstraints($params)) {
            return null;
        }
        
        // 2. Tarif de base XPO
        $basePrice = $this->getBasePrice($params);
        if (!$basePrice) return null;
        
        // 3. LOGIQUE GULDAGIL XPO - Optimisation 100kg
        if ($params['poids'] <= 100) {
            $price100kg = $this->getBasePrice(array_merge($params, ['poids' => 100]));
            if ($price100kg && $price100kg < $basePrice) {
                $basePrice = $price100kg;
            }
        }
        
        // 4. Ratio poids > 100kg
        if ($params['poids'] > 100) {
            $basePrice *= ($params['poids'] / 100);
        }
        
        // 5. Options et taxes XPO
        return $this->applyOptions($basePrice, $params);
    }
    
    private function validateConstraints(array $params): bool {
        $stmt = $this->db->prepare("
            SELECT departements_blacklistes, poids_min, poids_max 
            FROM gul_contraintes_transporteurs 
            WHERE transporteur = 'xpo'
        ");
        $stmt->execute();
        $constraints = $stmt->fetch();
        
        if (!$constraints) return true;
        
        if ($constraints['departements_blacklistes']) {
            $blacklisted = explode(',', $constraints['departements_blacklistes']);
            if (in_array($params['departement'], $blacklisted)) return false;
        }
        
        return $params['poids'] >= ($constraints['poids_min'] ?? 0) 
            && $params['poids'] <= ($constraints['poids_max'] ?? 32000);
    }
    
    private function getBasePrice(array $params): ?float {
        $stmt = $this->db->prepare("
            SELECT prix FROM gul_xpo_rates 
            WHERE departement = ? AND poids_min <= ? AND poids_max >= ? AND type_transport = ? 
            ORDER BY poids_min DESC LIMIT 1
        ");
        $stmt->execute([$params['departement'], $params['poids'], $params['poids'], $params['type']]);
        $result = $stmt->fetch();
        
        return $result ? (float)$result['prix'] : null;
    }
    
    private function applyOptions(float $price, array $params): float {
        // Taxes XPO
        $stmt = $this->db->prepare("SELECT * FROM gul_taxes_transporteurs WHERE transporteur = 'xpo'");
        $stmt->execute();
        $taxes = $stmt->fetch();
        
        if ($taxes) {
            if ($taxes['pase_montant']) $price += $taxes['pase_montant'];
            if ($taxes['region_parisienne_montant'] && $this->isRegionParisienne($params['departement'])) {
                $price += $taxes['region_parisienne_montant'];
            }
            if ($taxes['zfe_montant'] && $this->isZFE($params['departement'])) {
                $price += $taxes['zfe_montant'];
            }
        }
        
        // Options XPO spécifiques
        if ($params['adr']) {
            $stmt = $this->db->prepare("SELECT montant FROM gul_options_supplementaires WHERE transporteur = 'xpo' AND code_option = 'adr' AND actif = 1");
            $stmt->execute();
            $option = $stmt->fetch();
            if ($option) $price += $option['montant'];
        }
        
        if ($params['enlevement']) {
            $stmt = $this->db->prepare("SELECT montant FROM gul_options_supplementaires WHERE transporteur = 'xpo' AND code_option = 'enlevement' AND actif = 1");
            $stmt->execute();
            $option = $stmt->fetch();
            if ($option) $price += $option['montant'];
        }
        
        return $price;
    }
    
    private function isRegionParisienne(string $dept): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM gul_zones_speciales WHERE departement = ? AND type_zone = 'region_parisienne'");
        $stmt->execute([$dept]);
        return (bool)$stmt->fetch();
    }
    
    private function isZFE(string $dept): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM gul_zones_speciales WHERE departement = ? AND type_zone = 'zfe'");
        $stmt->execute([$dept]);
        return (bool)$stmt->fetch();
    }
}

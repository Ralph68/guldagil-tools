<?php
/**
 * Titre: Calculateur Kuehne+Nagel - Logique métier dédiée
 * Chemin: /public/port/Services/Calculators/KNCalculator.php
 * Version: 0.5 beta + build auto
 */

class KNCalculator {
    public function __construct(private PDO $db) {}
    
    public function calculate(array $params): ?float {
        if (!$this->validateConstraints($params)) return null;
        
        $basePrice = $this->getBasePrice($params);
        if (!$basePrice) return null;
        
        // LOGIQUE GULDAGIL KN
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
            SELECT departements_blacklistes, poids_min, poids_maximum 
            FROM gul_taxes_transporteurs 
            WHERE transporteur = 'kn'
        ");
        $stmt->execute();
        $constraints = $stmt->fetch();
        
        if (!$constraints) return true;
        
        if ($constraints['departements_blacklistes']) {
            $blacklisted = explode(',', $constraints['departements_blacklistes']);
            if (in_array($params['departement'], $blacklisted)) return false;
        }
        
        return $params['poids'] >= ($constraints['poids_min'] ?? 0) 
            && $params['poids'] <= ($constraints['poids_maximum'] ?? 32000);
    }
    
    private function getBasePrice(array $params): ?float {
        $zone = $this->getZone($params['departement']);
        $weightCategory = $this->getWeightCategory($params['poids']);
        
        $stmt = $this->db->prepare("
            SELECT prix_base FROM gul_kn_rates 
            WHERE code_postal_zone = ? AND categorie_poids = ? 
            LIMIT 1
        ");
        $stmt->execute([$zone, $weightCategory]);
        $result = $stmt->fetch();
        
        return $result ? (float)$result['prix_base'] : null;
    }
    
    private function applyOptions(float $price, array $params): float {
        // Taxes KN
        $stmt = $this->db->prepare("SELECT * FROM gul_taxes_transporteurs WHERE transporteur = 'kn'");
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
        
        // Options KN
        if ($params['adr']) {
            $stmt = $this->db->prepare("SELECT montant FROM gul_options_supplementaires WHERE transporteur = 'kn' AND code_option = 'adr' AND actif = 1");
            $stmt->execute();
            $option = $stmt->fetch();
            if ($option) $price += $option['montant'];
        }
        
        if ($params['enlevement']) {
            $stmt = $this->db->prepare("SELECT montant FROM gul_options_supplementaires WHERE transporteur = 'kn' AND code_option = 'enlevement' AND actif = 1");
            $stmt->execute();
            $option = $stmt->fetch();
            if ($option) $price += $option['montant'];
        }
        
        return $price;
    }
    
    private function getZone(string $dept): string {
        $stmt = $this->db->prepare("SELECT zone_kn FROM gul_zones_departements WHERE departement = ? AND transporteur = 'kn'");
        $stmt->execute([$dept]);
        $result = $stmt->fetch();
        return $result ? $result['zone_kn'] : 'A';
    }
    
    private function getWeightCategory(float $weight): string {
        $stmt = $this->db->prepare("SELECT categorie FROM gul_categories_poids WHERE transporteur = 'kn' AND poids_min <= ? AND poids_maximum >= ? ORDER BY poids_min DESC LIMIT 1");
        $stmt->execute([$weight, $weight]);
        $result = $stmt->fetch();
        return $result ? $result['categorie'] : '0-50kg';
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

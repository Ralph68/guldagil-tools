<?php
/**
 * Titre: Classe Transport - IMPLÉMENTATION COMPLÈTE
 * Chemin: /features/port/transport.php
 * Version: 0.5 beta + build auto
 */

class Transport {
    private PDO $db;
    public array $debug = [];
    
    private array $tables = [
        'xpo' => 'gul_xpo_rates',
        'heppner' => 'gul_heppner_rates',
        'kn' => 'gul_kn_rates'
    ];
    
    private array $carrierNames = [
        'xpo' => 'XPO Logistics',
        'heppner' => 'Heppner',
        'kn' => 'Kuehne + Nagel'
    ];
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->debug = [];
    }
    
    /**
     * Calcule les tarifs pour tous les transporteurs
     * SIGNATURE NOUVELLE - array de paramètres
     */
    public function calculateAll(array $params): array {
        $results = [];
        $this->debug = [];
        
        // Log des paramètres reçus
        $this->debug['input_params'] = $params;
        
        // Validation et normalisation
        $normalized = $this->normalizeParams($params);
        $this->debug['normalized_params'] = $normalized;
        
        // Règle métier : poids > 60kg = forcément palette
        if ($normalized['poids'] > 60 && $normalized['type'] === 'colis') {
            $normalized['type'] = 'palette';
            $this->debug['force_palette'] = "Poids {$normalized['poids']}kg > 60kg : forcé en palette";
        }
        
        // Calcul pour chaque transporteur
        foreach ($this->tables as $carrier => $table) {
            try {
                $this->debug[$carrier] = [];
                $price = $this->calculateForCarrier($carrier, $normalized);
                $results[$carrier] = $price;
                
                $this->debug[$carrier]['final_price'] = $price;
                
            } catch (Exception $e) {
                $results[$carrier] = null;
                $this->debug[$carrier]['error'] = $e->getMessage();
            }
        }
        
        // Recherche du meilleur tarif
        $bestRate = $this->findBestRate($results);
        
        return [
            'results' => $results,
            'debug' => $this->debug,
            'best' => $bestRate,
            'metadata' => [
                'carrier_count' => count(array_filter($results)),
                'calculation_type' => $normalized['type'],
                'weight_processed' => $normalized['poids']
            ]
        ];
    }
    
    /**
     * Normalise et valide les paramètres d'entrée
     */
    private function normalizeParams(array $params): array {
        return [
            'departement' => str_pad(trim($params['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($params['poids'] ?? 0),
            'type' => strtolower(trim($params['type'] ?? 'colis')),
            'adr' => (bool)($params['adr'] ?? false),
            'option_sup' => trim($params['option_sup'] ?? 'standard'),
            'enlevement' => (bool)($params['enlevement'] ?? false),
            'palettes' => max(0, intval($params['palettes'] ?? 0)),
        ];
    }
    
    /**
     * Calcul pour un transporteur spécifique
     */
    private function calculateForCarrier(string $carrier, array $params): ?float {
        // 1. Vérifications préliminaires
        if (!$this->isCarrierAvailable($carrier, $params)) {
            return null;
        }
        
        // 2. Récupération du tarif de base
        $baseTariff = $this->getBaseTariff($carrier, $params);
        if ($baseTariff === null) {
            $this->debug[$carrier]['tarif_base'] = 'non trouvé';
            return null;
        }
        
        $this->debug[$carrier]['tarif_base'] = $baseTariff;
        
        // 3. Calcul du prix selon le poids
        $price = $this->calculateWeightPrice($carrier, $baseTariff, $params);
        $this->debug[$carrier]['prix_poids'] = $price;
        
        // 4. Ajout des majorations
        $price = $this->addSurcharges($carrier, $price, $params);
        
        return round($price, 2);
    }
    
    /**
     * Vérifie si le transporteur peut traiter cette expédition
     */
    private function isCarrierAvailable(string $carrier, array $params): bool {
        // Vérifier les restrictions par département
        $restrictions = $this->getCarrierRestrictions($carrier);
        if (in_array($params['departement'], $restrictions)) {
            $this->debug[$carrier]['restriction'] = "Département {$params['departement']} non desservi";
            return false;
        }
        
        // Vérifier les limites de poids
        $maxWeight = $this->getMaxWeight($carrier);
        if ($params['poids'] > $maxWeight) {
            $this->debug[$carrier]['restriction'] = "Poids {$params['poids']}kg > limite {$maxWeight}kg";
            return false;
        }
        
        return true;
    }
    
    /**
     * Récupère le tarif de base selon le poids et département
     */
    private function getBaseTariff(string $carrier, array $params): ?float {
        $table = $this->tables[$carrier];
        $weight = $params['poids'];
        $dept = $params['departement'];
        
        // Déterminer la colonne de tarif selon le poids
        $weightColumn = $this->getWeightColumn($carrier, $weight);
        if (!$weightColumn) {
            $this->debug[$carrier]['weight_column'] = 'non trouvée pour ' . $weight . 'kg';
            return null;
        }
        
        $this->debug[$carrier]['weight_column'] = $weightColumn;
        
        // Requête pour récupérer le tarif
        $sql = "SELECT {$weightColumn} as tarif FROM {$table} WHERE num_departement = ? LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dept]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && $row['tarif'] > 0) {
                return (float)$row['tarif'];
            }
            
            $this->debug[$carrier]['sql_result'] = 'tarif nul ou département non trouvé';
            return null;
            
        } catch (Exception $e) {
            $this->debug[$carrier]['sql_error'] = $e->getMessage();
            return null;
        }
    }
    
    /**
     * Détermine la colonne de tarif selon le poids et le transporteur
     */
    private function getWeightColumn(string $carrier, float $weight): ?string {
        switch ($carrier) {
            case 'xpo':
                if ($weight <= 99) return 'tarif_0_99';
                if ($weight <= 499) return 'tarif_100_499';
                if ($weight <= 999) return 'tarif_500_999';
                if ($weight <= 1999) return 'tarif_1000_1999';
                if ($weight <= 2999) return 'tarif_2000_2999';
                return null;
                
            case 'heppner':
            case 'kn':
                if ($weight <= 9) return 'tarif_0_9';
                if ($weight <= 19) return 'tarif_10_19';
                if ($weight <= 29) return 'tarif_20_29';
                if ($weight <= 39) return 'tarif_30_39';
                if ($weight <= 49) return 'tarif_40_49';
                if ($weight <= 59) return 'tarif_50_59';
                if ($weight <= 69) return 'tarif_60_69';
                if ($weight <= 79) return 'tarif_70_79';
                if ($weight <= 89) return 'tarif_80_89';
                if ($weight <= 99) return 'tarif_90_99';
                if ($weight <= 299) return 'tarif_100_299';
                if ($weight <= 499) return 'tarif_300_499';
                if ($weight <= 999) return 'tarif_500_999';
                if ($weight <= 1999) return 'tarif_1000_1999';
                return null;
        }
        
        return null;
    }
    
    /**
     * Calcule le prix selon le poids (règle proportionnelle > 100kg)
     */
    private function calculateWeightPrice(string $carrier, float $baseTariff, array $params): float {
        $weight = $params['poids'];
        
        // Règle métier : au-delà de 100kg, tarification proportionnelle
        if ($weight > 100) {
            $ratio = $weight / 100;
            $adjustedPrice = $baseTariff * $ratio;
            
            $this->debug[$carrier]['weight_calculation'] = [
                'base_tariff' => $baseTariff,
                'weight' => $weight,
                'ratio' => $ratio,
                'adjusted_price' => $adjustedPrice
            ];
            
            return $adjustedPrice;
        }
        
        return $baseTariff;
    }
    
    /**
     * Ajoute toutes les majorations applicables
     */
    private function addSurcharges(string $carrier, float $basePrice, array $params): float {
        $finalPrice = $basePrice;
        $surcharges = [];
        
        // 1. Majoration ADR
        if ($params['adr']) {
            $adrSurcharge = $this->getADRSurcharge($carrier, $basePrice);
            $finalPrice += $adrSurcharge;
            $surcharges['adr'] = $adrSurcharge;
        }
        
        // 2. Options de service
        $serviceSurcharge = $this->getServiceSurcharge($carrier, $params['option_sup']);
        $finalPrice += $serviceSurcharge;
        $surcharges['service'] = $serviceSurcharge;
        
        // 3. Enlèvement
        if ($params['enlevement']) {
            $pickupSurcharge = $this->getPickupSurcharge($carrier);
            $finalPrice += $pickupSurcharge;
            $surcharges['pickup'] = $pickupSurcharge;
        }
        
        // 4. Frais de palettes
        if ($params['type'] === 'palette' && $params['palettes'] > 0) {
            $paletteSurcharge = $this->getPaletteSurcharge($carrier, $params['palettes']);
            $finalPrice += $paletteSurcharge;
            $surcharges['palettes'] = $paletteSurcharge;
        }
        
        // 5. Taxes diverses (gazole, etc.)
        $taxes = $this->getVariousTaxes($carrier, $finalPrice, $params);
        $finalPrice += $taxes;
        $surcharges['taxes'] = $taxes;
        
        $this->debug[$carrier]['surcharges'] = $surcharges;
        $this->debug[$carrier]['final_price_with_surcharges'] = $finalPrice;
        
        return $finalPrice;
    }
    
    /**
     * Calcul majoration ADR
     */
    private function getADRSurcharge(string $carrier, float $basePrice): float {
        try {
            $sql = "SELECT majoration_adr_taux FROM gul_taxes_transporteurs WHERE transporteur = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            if ($row && $row['majoration_adr_taux'] > 0) {
                return $basePrice * ($row['majoration_adr_taux'] / 100);
            }
            
            // Valeurs par défaut si pas en BDD
            $defaultRates = ['xpo' => 20, 'heppner' => 25, 'kn' => 22];
            $rate = $defaultRates[$carrier] ?? 20;
            
            return $basePrice * ($rate / 100);
            
        } catch (Exception $e) {
            return $basePrice * 0.20; // 20% par défaut
        }
    }
    
    /**
     * Calcul majoration options de service
     */
    private function getServiceSurcharge(string $carrier, string $option): float {
        $serviceCosts = [
            'standard' => 0,
            'premium_matin' => 15,
            'rdv' => 12,
            'target' => 25
        ];
        
        return $serviceCosts[$option] ?? 0;
    }
    
    /**
     * Calcul frais d'enlèvement
     */
    private function getPickupSurcharge(string $carrier): float {
        $pickupCosts = [
            'xpo' => 25,
            'heppner' => 0, // Gratuit chez Heppner
            'kn' => 20
        ];
        
        return $pickupCosts[$carrier] ?? 15;
    }
    
    /**
     * Calcul frais palettes
     */
    private function getPaletteSurcharge(string $carrier, int $nbPalettes): float {
        $paletteCosts = [
            'xpo' => 8,
            'heppner' => 10,
            'kn' => 12
        ];
        
        $costPerPallet = $paletteCosts[$carrier] ?? 10;
        return $costPerPallet * $nbPalettes;
    }
    
    /**
     * Calcul taxes diverses (gazole, etc.)
     */
    private function getVariousTaxes(string $carrier, float $currentPrice, array $params): float {
        try {
            $sql = "SELECT surcharge_gasoil, participation_transition_energetique, contribution_sanitaire 
                    FROM gul_taxes_transporteurs WHERE transporteur = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            $taxes = 0;
            
            if ($row) {
                $taxes += (float)($row['surcharge_gasoil'] ?? 0);
                $taxes += (float)($row['participation_transition_energetique'] ?? 0);
                $taxes += (float)($row['contribution_sanitaire'] ?? 0);
            }
            
            return $taxes;
            
        } catch (Exception $e) {
            return 2.50; // Forfait par défaut
        }
    }
    
    /**
     * Récupère les départements non desservis par un transporteur
     */
    private function getCarrierRestrictions(string $carrier): array {
        try {
            $sql = "SELECT departements_blacklistes FROM gul_taxes_transporteurs WHERE transporteur = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            if ($row && $row['departements_blacklistes']) {
                return explode(',', $row['departements_blacklistes']);
            }
            
            return [];
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Récupère le poids maximum autorisé par transporteur
     */
    private function getMaxWeight(string $carrier): float {
        try {
            $sql = "SELECT poids_maximum FROM gul_taxes_transporteurs WHERE transporteur = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            if ($row && $row['poids_maximum'] > 0) {
                return (float)$row['poids_maximum'];
            }
            
            // Valeurs par défaut
            $defaultLimits = ['xpo' => 3000, 'heppner' => 2000, 'kn' => 1999];
            return $defaultLimits[$carrier] ?? 2000;
            
        } catch (Exception $e) {
            return 2000; // Par défaut
        }
    }
    
    /**
     * Trouve le meilleur tarif parmi les résultats
     */
    private function findBestRate(array $results): ?array {
        $validResults = array_filter($results, function($price) {
            return $price !== null && $price > 0;
        });
        
        if (empty($validResults)) {
            return null;
        }
        
        $bestCarrier = array_keys($validResults, min($validResults))[0];
        
        return [
            'carrier' => $bestCarrier,
            'price' => $validResults[$bestCarrier],
            'name' => $this->carrierNames[$bestCarrier] ?? strtoupper($bestCarrier)
        ];
    }
}
?>

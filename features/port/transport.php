<?php
/**
 * Titre: Classe Transport - LOGIQUE PRÉSERVÉE ET COMPLÉTÉE
 * Chemin: /features/port/transport.php
 * Version: 0.5 beta + build auto
 */

class Transport {
    private PDO $db;
    public array $debug = [];
    
    private array $tables = [
        'xpo' => 'gul_xpo_rates',
        'heppner' => 'gul_heppner_rates'
    ];
    
    private array $starTables = [
        'heppner' => 'gul_heppner_star'
    ];
    
    private array $carrierNames = [
        'xpo' => 'XPO Logistics',
        'heppner' => 'Heppner'
    ];
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->debug = [];
    }
    
    /**
     * Calcule les tarifs pour tous les transporteurs
     * SIGNATURE PRÉSERVÉE - ne pas modifier
     */
    public function calculateAll(array $params): array {
        $results = [];
        $this->debug = ['input' => $params];
        
        // Normalisation des paramètres
        $normalized = $this->normalizeParams($params);
        $this->debug['normalized'] = $normalized;
        
        // Règle métier : poids > 60kg = forcément palette
        if ($normalized['poids'] > 60 && $normalized['type'] === 'colis') {
            $normalized['type'] = 'palette';
            $this->debug['rule_applied'] = "Poids {$normalized['poids']}kg > 60kg : forcé en palette";
        }
        
        // Calcul pour chaque transporteur
        foreach ($this->tables as $carrier => $table) {
            try {
                $result = $this->calculateForCarrier($carrier, $normalized);
                $results[$carrier] = $result;
                
            } catch (Exception $e) {
                $results[$carrier] = null;
                $this->debug[$carrier]['error'] = $e->getMessage();
            }
        }
        
        return [
            'results' => $results,
            'debug' => $this->debug,
            'best' => $this->findBestRate($results)
        ];
    }
    
    private function normalizeParams(array $params): array {
        return [
            'departement' => str_pad(trim($params['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($params['poids'] ?? 0),
            'type' => strtolower(trim($params['type'] ?? 'colis')),
            'adr' => (bool)($params['adr'] ?? false),
            'option_sup' => trim($params['option_sup'] ?? 'standard'),
            'enlevement' => (bool)($params['enlevement'] ?? false),
            'palettes' => max(1, intval($params['palettes'] ?? 1)),
        ];
    }
    
    private function calculateForCarrier(string $carrier, array $params): ?array {
        $this->debug[$carrier] = [];
        
        // 1. Récupération du tarif de base
        $baseTariff = $this->getBaseTariff($carrier, $params);
        if ($baseTariff === null) {
            $this->debug[$carrier]['result'] = 'Tarif de base non trouvé';
            return null;
        }
        
        $this->debug[$carrier]['base_tariff'] = $baseTariff;
        $price = $baseTariff;
        
        // 2. Calcul selon le poids pour colis
        if ($params['type'] === 'colis' && $params['poids'] > 100) {
            $ratio = $params['poids'] / 100;
            $price = $baseTariff * $ratio;
            $this->debug[$carrier]['weight_ratio'] = $ratio;
        }
        
        $this->debug[$carrier]['price_after_weight'] = $price;
        
        // 3. Option de service
        $servicePrice = $this->getServicePrice($carrier, $params['option_sup'], $price);
        $this->debug[$carrier]['service_option'] = $params['option_sup'];
        $this->debug[$carrier]['service_price'] = $servicePrice;
        
        // 4. Majorations
        $surcharges = $this->calculateSurcharges($carrier, $servicePrice, $params);
        $this->debug[$carrier]['surcharges'] = $surcharges;
        
        $total = $servicePrice + array_sum($surcharges);
        
        // 5. Enlèvement si demandé
        $enlevementPrice = 0;
        if ($params['enlevement']) {
            $enlevementPrice = $this->getEnlevementPrice($carrier);
            $total += $enlevementPrice;
            $this->debug[$carrier]['enlevement'] = $enlevementPrice;
        }
        
        // 6. Délai selon option
        $delais = $this->getDelais($carrier, $params['departement'], $params['option_sup']);
        
        // 7. Frais représentation et gardiennage
        $additionalFees = $this->getAdditionalFees($carrier, $servicePrice);
        
        return [
            'base' => $baseTariff,
            'service_price' => $servicePrice,
            'surcharges' => $surcharges,
            'enlevement' => $enlevementPrice,
            'total' => round($total, 2),
            'delais' => $delais,
            'service' => $this->getServiceLabel($params['option_sup']),
            'additional_fees' => $additionalFees
        ];
    }
    
    private function getBaseTariff(string $carrier, array $params): ?float {
        $table = $this->tables[$carrier];
        $weight = $params['poids'];
        $dept = $params['departement'];
        
        // Déterminer la colonne selon le poids
        $weightColumn = $this->getWeightColumn($carrier, $weight, $params['type']);
        if (!$weightColumn) {
            $this->debug[$carrier]['weight_column'] = 'Colonne non trouvée pour ' . $weight . 'kg';
            return null;
        }
        
        $this->debug[$carrier]['weight_column'] = $weightColumn;
        
        // Requête
        $sql = "SELECT {$weightColumn} as tarif FROM {$table} WHERE num_departement = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dept]);
        $row = $stmt->fetch();
        
        if (!$row || $row['tarif'] === null) {
            $this->debug[$carrier]['query_result'] = 'Aucun tarif trouvé';
            return null;
        }
        
        return floatval($row['tarif']);
    }
    
    private function getWeightColumn(string $carrier, float $weight, string $type): ?string {
        // Colonnes selon transporteur et type
        if ($type === 'palette') {
            return 'palette_1'; // Colonne standard palette
        }
        
        // Pour colis, selon le poids
        if ($weight <= 10) return 'colis_10kg';
        if ($weight <= 20) return 'colis_20kg';
        if ($weight <= 30) return 'colis_30kg';
        if ($weight <= 50) return 'colis_50kg';
        if ($weight <= 100) return 'colis_100kg';
        
        return 'colis_100kg'; // Sera calculé avec ratio
    }
    
    private function getServicePrice(string $carrier, string $option, float $basePrice): float {
        switch ($option) {
            case 'premium_matin':
                if ($carrier === 'heppner') {
                    // Heppner utilise une grille Star
                    return $this->getStarPrice($carrier, $basePrice);
                } else {
                    // XPO +30%
                    return $basePrice * 1.3;
                }
                
            case 'rdv':
                return $basePrice + $this->getRDVCost($carrier);
                
            case 'target':
                if ($carrier === 'heppner') {
                    return $this->getStarPrice($carrier, $basePrice);
                } else {
                    return $basePrice * 1.15; // XPO +15%
                }
                
            default:
                return $basePrice;
        }
    }
    
    private function getStarPrice(string $carrier, float $basePrice): float {
        if ($carrier !== 'heppner') return $basePrice;
        
        // TODO: Implémenter la logique de la table gul_heppner_star
        // Pour l'instant, retourne le prix de base
        return $basePrice;
    }
    
    private function getRDVCost(string $carrier): float {
        try {
            $sql = "SELECT montant FROM gul_taxes_transporteurs 
                    WHERE transporteur = ? AND type_taxe = 'rdv' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            return $row ? floatval($row['montant']) : 8.0; // Fallback 8€
        } catch (Exception $e) {
            return 8.0;
        }
    }
    
    private function calculateSurcharges(string $carrier, float $price, array $params): array {
        $surcharges = [];
        
        // ADR
        if ($params['adr']) {
            $surcharges['adr'] = $this->getADRSurcharge($carrier, $price);
        }
        
        // Taxes diverses (sureté, environnement, etc.)
        $surcharges = array_merge($surcharges, $this->getStandardTaxes($carrier));
        
        return $surcharges;
    }
    
    private function getADRSurcharge(string $carrier, float $price): float {
        // Règles ADR selon transporteur
        $rates = [
            'xpo' => 0.20, // 20%
            'heppner' => 0.20 // 20%
        ];
        
        $rate = $rates[$carrier] ?? 0.20;
        $surcharge = $price * $rate;
        
        // Min/Max selon transporteur
        $min = 10.0;
        $max = 100.0;
        
        return max($min, min($max, $surcharge));
    }
    
    private function getStandardTaxes(string $carrier): array {
        $taxes = [];
        
        try {
            $sql = "SELECT type_taxe, montant FROM gul_taxes_transporteurs 
                    WHERE transporteur = ? AND type_taxe IN ('surete', 'environnement')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            
            while ($row = $stmt->fetch()) {
                $taxes[$row['type_taxe']] = floatval($row['montant']);
            }
        } catch (Exception $e) {
            // Fallback
            $taxes['surete'] = 0.70;
            $taxes['environnement'] = 0.50;
        }
        
        return $taxes;
    }
    
    private function getEnlevementPrice(string $carrier): float {
        try {
            $sql = "SELECT montant FROM gul_taxes_transporteurs 
                    WHERE transporteur = ? AND type_taxe = 'enlevement' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            return $row ? floatval($row['montant']) : 30.0; // Fallback 30€
        } catch (Exception $e) {
            return 30.0;
        }
    }
    
    private function getDelais(string $carrier, string $dept, string $option): string {
        try {
            $sql = "SELECT delais FROM {$this->tables[$carrier]} WHERE num_departement = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dept]);
            $row = $stmt->fetch();
            
            $delais = $row['delais'] ?? '24-48h';
            
            // Modification selon option
            switch ($option) {
                case 'premium_matin':
                    return $carrier === 'heppner' ? $delais . ' avant 13h' : $delais . ' avant 14h';
                case 'rdv':
                    return $delais . ' sur RDV';
                case 'target':
                    return 'Date imposée';
                default:
                    return $delais;
            }
        } catch (Exception $e) {
            return '24-48h';
        }
    }
    
    private function getServiceLabel(string $option): string {
        $labels = [
            'standard' => 'Standard',
            'premium_matin' => 'Premium Matin',
            'rdv' => 'Sur RDV',
            'target' => 'Date imposée'
        ];
        
        return $labels[$option] ?? 'Standard';
    }
    
    private function getAdditionalFees(string $carrier, float $basePrice): array {
        // Frais de représentation (% du tarif)
        $representation = $basePrice * 0.60; // 60% du montant
        
        // Frais de gardiennage par jour
        $gardiennage = 3.00; // 3€/palette/jour
        
        return [
            'representation' => round($representation, 2),
            'gardiennage_jour' => $gardiennage
        ];
    }
    
    private function findBestRate(array $results): ?string {
        $bestCarrier = null;
        $bestPrice = PHP_FLOAT_MAX;
        
        foreach ($results as $carrier => $result) {
            if ($result && isset($result['total']) && $result['total'] < $bestPrice) {
                $bestPrice = $result['total'];
                $bestCarrier = $carrier;
            }
        }
        
        return $bestCarrier;
    }
}

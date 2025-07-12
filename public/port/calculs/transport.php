<?php
/**
 * Titre: Classe Transport - COMPATIBLE avec public/port/index.php
 * Chemin: /public/port/calculs/transport.php
 * Version: 0.5 beta + build auto
 */

class Transport {
    private const ERROR_DEPARTMENT_FORMAT = 'FORMAT_DEPT_INVALID';
    private const ERROR_WEIGHT_RANGE = 'WEIGHT_OUT_OF_RANGE';
    private const ERROR_CARRIER_NOT_FOUND = 'CARRIER_NOT_FOUND';
    private const ERROR_BLACKLISTED_DEPARTMENT = 'BLACKLISTED_DEPARTMENT';
    private const ERROR_ADR_NOT_FOUND = 'ADR_NOT_FOUND';
    private const ERROR_PICKUP_NOT_FOUND = 'PICKUP_NOT_FOUND';
    private const ERROR_TARIFF_NOT_FOUND = 'TARIFF_NOT_FOUND';
    
    private array $memoizedResults = [];
private const CACHE_MAX_SIZE = 1000; // Nombre maximum de résultats en cache
    
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
        'kn' => 'Kuehne+Nagel'
    ];
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->debug = [];
    }
    
    /**
     * SIGNATURE ATTENDUE par public/port/index.php
     * calculateAll(array $params) OBLIGATOIRE
     */
    public function calculateAll(array $params): array {
    try {
        $this->debug = ['params_received' => $params];
        
        // Normalisation des paramètres
        $normalizedParams = $this->normalizeParams($params);
        $this->debug['params_normalized'] = $normalizedParams;
        
        // Calcul pour chaque transporteur
        $results = [];
        foreach ($this->tables as $carrier => $table) {
            try {
                $price = $this->calculateForCarrier($carrier, $normalizedParams);
                $results[$carrier] = $price;
                $this->debug[$carrier]['final_result'] = $price;
            } catch (Exception $e) {
                $results[$carrier] = null;
                $this->debug[$carrier]['error'] = [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ];
            }
        }
        
        // Format de retour
        return [
            'results' => $results,
            'debug' => $this->debug,
            'best' => $this->findBestRate($results)
        ];
    } catch (Exception $e) {
        $this->debug['error'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        return [
            'error' => 'Une erreur est survenue lors du calcul',
            'debug' => $this->debug
        ];
    }
}
    
    /**
     * Normalisation compatible avec les paramètres de public/port/index.php
     */
    private function normalizeParams(array $params): array {
    return [
        'departement' => str_pad(trim($params['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
        'poids' => floatval($params['poids'] ?? 0),
        'type' => $this->determineTransportType($params),
        'adr' => (bool)($params['adr'] ?? false),
        'palettes' => max(1, intval($params['palettes'] ?? 1)),
        'enlevement' => (bool)($params['enlevement'] ?? false),
        'option_sup' => trim($params['option_sup'] ?? 'standard')
    ];
}
  private function determineTransportType(array $params): string {
    if ($params['poids'] > 60) {
        return 'palette';
    }
    return $params['type'] ?? 'colis';
}
    
    /**
     * LOGIQUE MÉTIER GULDAGIL - PRÉSERVÉE
     */
    private function calculateForCarrier(string $carrier, array $params): ?float {
        $this->debug[$carrier] = [
            'steps' => [],
            'input_params' => $params,
            'carrier_table' => $this->tables[$carrier] ?? 'INCONNU'
        ];
        
        // 1. Validation contraintes
        $constraintsValid = $this->validateCarrierConstraints($carrier, $params);
        $this->debug[$carrier]['constraints_valid'] = $constraintsValid;
        $this->debug[$carrier]['steps'][] = $constraintsValid ? '✓ Contraintes OK' : '✗ Contraintes KO';
        
        if (!$constraintsValid) {
            return null;
        }
        
        // 2. Récupération tarif de base selon le poids (LOGIQUE EXCEL GULDAGIL)
        // 2. Récupération tarif de base selon le poids
$baseTariff = $this->getBaseTariffByWeight($carrier, $params);
$this->debug[$carrier]['base_tariff_result'] = $baseTariff;
        // Debug détaillé
        if ($baseTariff === null) {
            $this->debug[$carrier]['error_details'] = [
                'tariff_not_found' => $this->debug[$carrier]['tariff_not_found'] ?? 'Inconnu',
                'sql_query' => $this->debug[$carrier]['sql_query'] ?? 'Non défini',
                'sql_params' => $this->debug[$carrier]['sql_params'] ?? 'Non définis'
            ];
$this->debug[$carrier]['steps'][] = $baseTariff ? 
    "✓ Tarif base: {$baseTariff}€" : 
    "✗ Tarif base non trouvé - " . ($this->debug[$carrier]['tariff_not_found'] ?? 'Erreur inconnue');

      // 3. Calcul pour poids <= 100kg
    if ($params['poids'] <= 100) {
        $hundredKgPrice = $this->getBaseTariffByWeight($carrier, ['poids' => 100, 'departement' => $params['departement'], 'type' => $params['type']]);
        
        if ($hundredKgPrice !== null) {
            $hundredKgPrice = $hundredKgPrice * ($params['poids'] / 100);
            
            if ($hundredKgPrice < $finalPrice) {
                $finalPrice = $hundredKgPrice;
                $this->debug[$carrier]['steps'][] = "✓ Utilisation du tarif 100kg (déclarer 100kg)";
            }
        }
    }
      
        // 3. SPÉCIFICITÉ GULDAGIL - Calcul proportionnel poids > 100kg
        if ($params['poids'] > 100) {
            $ratio = $params['poids'] / 100;
            $finalPrice = $baseTariff * $ratio;
            $this->debug[$carrier]['weight_ratio'] = $ratio;
            $this->debug[$carrier]['price_after_weight'] = $finalPrice;
            $this->debug[$carrier]['steps'][] = "✓ Poids > 100kg, ratio: {$ratio}, nouveau prix: {$finalPrice}€";
        } else {
            $this->debug[$carrier]['steps'][] = "✓ Poids ≤ 100kg, pas de ratio";
        }
        
        // 4. Majorations spécifiques Guldagil
        $finalPrice = $this->addGuldagilSurcharges($carrier, $finalPrice, $params);
        $this->debug[$carrier]['final_calculated_price'] = $finalPrice;
        $this->debug[$carrier]['steps'][] = "✓ Prix final après majorations: {$finalPrice}€";
        
        return round($finalPrice, 2);
    }
    
    /**
     * MÉTHODE COMPATIBLE avec la logique Excel Guldagil
     * Récupération selon tranches de poids
     */
    private function getBaseTariffByWeight(string $carrier, array $params): ?float {
    $table = $this->tables[$carrier];
    $weight = $params['poids'];
    $dept = $params['departement'];
    
    // Vérification du format du département
    if (!preg_match('/^\\d{2}$/', $dept)) {
        throw new InvalidArgumentException("Format de département invalide. Doit être sur 2 chiffres.");
    }
    
    // Détermination de la colonne de poids
    $weightColumn = $this->getWeightColumnGuldagil($weight, $params['type']);
    
    if (!$weightColumn) {
        throw new RuntimeException("Colonne de poids non trouvée pour {$weight}kg, type: {$params['type']}");
    }
    
    // Requête BDD avec debug complet
    $sql = "SELECT {$weightColumn} as tarif FROM {$table} WHERE num_departement = ? LIMIT 1";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$dept]);
    
    $row = $stmt->fetch();
    
    // Debug détaillé
    $this->debug[$carrier]['sql_result'] = $row;
    $this->debug[$carrier]['tariff_value'] = $row['tarif'] ?? 'null';
    
    if ($row && isset($row['tarif']) && $row['tarif'] > 0) {
        $tariff = (float)$row['tarif'];
        $this->debug[$carrier]['tariff_found'] = $tariff;
        return $tariff;
    }
    
    // Gestion des cas particuliers
    if ($row && isset($row['tarif']) && $row['tarif'] === null) {
        $this->debug[$carrier]['tariff_not_found'] = 'Tarif nul trouvé';
        return null;
    }
    
    $this->debug[$carrier]['tariff_not_found'] = 'Aucun tarif trouvé';
    return null;
}
}
    
    /**
     * LOGIQUE GULDAGIL - Colonnes selon poids et type
     */
    private function getWeightColumnGuldagil(float $weight, string $type): ?string {
        if ($type === 'palette') {
            return 'tarif_palette';
        }
        
        // Tranches de poids colis selon logique Guldagil
        if ($weight <= 5) return 'tarif_5kg';
        if ($weight <= 10) return 'tarif_10kg';
        if ($weight <= 15) return 'tarif_15kg';
        if ($weight <= 20) return 'tarif_20kg';
        if ($weight <= 25) return 'tarif_25kg';
        if ($weight <= 30) return 'tarif_30kg';
        if ($weight <= 50) return 'tarif_50kg';
        if ($weight <= 100) return 'tarif_100kg';
        
        // > 100kg : utiliser tarif 100kg comme base
        return 'tarif_100kg';
    }
    
    /**
     * MAJORATIONS SPÉCIFIQUES GULDAGIL
     */
    private function addGuldagilSurcharges(string $carrier, float $basePrice, array $params): float {
        $finalPrice = $basePrice;
        $this->debug[$carrier]['surcharges'] = [];
        
        // ADR - majoration pourcentage selon BDD ou défaut
        if ($params['adr']) {
            $adrSurcharge = $this->getADRSurchargeGuldagil($carrier, $basePrice);
            $finalPrice += $adrSurcharge;
            $this->debug[$carrier]['surcharges']['adr'] = $adrSurcharge;
            $this->debug[$carrier]['steps'][] = "Majoration ADR: +{$adrSurcharge}€";
        }
        
        // Options de service
        $serviceSurcharge = $this->getServiceSurcharge($params['option_sup']);
        if ($serviceSurcharge > 0) {
            $finalPrice += $serviceSurcharge;
            $this->debug[$carrier]['surcharges']['service'] = $serviceSurcharge;
            $this->debug[$carrier]['steps'][] = "Option service: +{$serviceSurcharge}€";
        }
        
        // Enlèvement - spécifique par transporteur
        if ($params['enlevement']) {
            $pickupSurcharge = $this->getPickupSurchargeGuldagil($carrier);
            $finalPrice += $pickupSurcharge;
            $this->debug[$carrier]['surcharges']['pickup'] = $pickupSurcharge;
            $this->debug[$carrier]['steps'][] = "Enlèvement: +{$pickupSurcharge}€";
        }
        
        // Palettes supplémentaires (si > 1)
        if ($params['palettes'] > 1) {
            $extraPalettes = $params['palettes'] - 1;
            $paletteSurcharge = $this->getPaletteSurchargeGuldagil($carrier, $extraPalettes);
            $finalPrice += $paletteSurcharge;
            $this->debug[$carrier]['surcharges']['palettes'] = $paletteSurcharge;
            $this->debug[$carrier]['steps'][] = "Palettes supp ({$extraPalettes}): +{$paletteSurcharge}€";
        }
        
        return $finalPrice;
    }
    
    /**
     * SPÉCIFICITÉ GULDAGIL - Majoration ADR par transporteur
     */
    private function getADRSurchargeGuldagil(string $carrier, float $basePrice): float {
        try {
            $sql = "SELECT majoration_adr_taux, majoration_adr_fixe FROM gul_taxes_transporteurs WHERE transporteur = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            if ($row) {
                // Majoration en pourcentage
                if ($row['majoration_adr_taux'] > 0) {
                    return $basePrice * ($row['majoration_adr_taux'] / 100);
                }
                // Majoration fixe
                if ($row['majoration_adr_fixe'] > 0) {
                    return (float)$row['majoration_adr_fixe'];
                }
            }
        } catch (Exception $e) {
            // Fallback silencieux
        }
        
        // Valeurs par défaut Guldagil
        $defaultRates = ['xpo' => 20, 'heppner' => 25, 'kn' => 22];
        $rate = $defaultRates[$carrier] ?? 20;
        return $basePrice * ($rate / 100);
    }
    
    /**
     * Options de service - compatible tarification Guldagil
     */
    private function getServiceSurcharge(string $option): float {
        $costs = [
            'standard' => 0,
            'premium_matin' => 15,
            'rdv' => 12,
            'premium13' => 25,
            'target' => 30
        ];
        
        return $costs[$option] ?? 0;
    }
    
    /**
     * SPÉCIFICITÉ GULDAGIL - Frais enlèvement par transporteur
     */
    private function getPickupSurchargeGuldagil(string $carrier): float {
        try {
            $sql = "SELECT frais_enlevement FROM gul_taxes_transporteurs WHERE transporteur = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            if ($row && $row['frais_enlevement'] > 0) {
                return (float)$row['frais_enlevement'];
            }
        } catch (Exception $e) {
            // Fallback silencieux
        }
        
        // Valeurs par défaut Guldagil
        return $carrier === 'xpo' ? 25 : 0; // Heppner gratuit
    }
    
    /**
     * Frais palettes supplémentaires selon logique Guldagil
     */
    private function getPaletteSurchargeGuldagil(string $carrier, int $extraPalettes): float {
        $costPerPallet = $carrier === 'xpo' ? 8 : 10;
        return $costPerPallet * $extraPalettes;
    }
    
    /**
     * Validation contraintes transporteur
     */
    private function validateCarrierConstraints(string $carrier, array $params): bool {
    $this->debug[$carrier]['errors'] = [];
    
    // Vérification du transporteur
    if (!isset($this->tables[$carrier])) {
        $this->debug[$carrier]['errors'][] = [
            'code' => self::ERROR_CARRIER_NOT_FOUND,
            'message' => "Transporteur {$carrier} non reconnu"
        ];
        return false;
    }
    
    // Vérification du département 06 pour XPO
    if ($carrier === 'xpo' && $params['departement'] === '06') {
        $this->debug[$carrier]['errors'][] = [
            'code' => self::ERROR_BLACKLISTED_DEPARTMENT,
            'message' => "Le département 06 n'est pas desservi par XPO"
        ];
        return false;
    }
    
    // Vérification du poids
    $maxWeights = ['heppner' => 3000, 'xpo' => 32000, 'kn' => 32000];
    $maxWeight = $maxWeights[$carrier] ?? 32000;
    
    if ($params['poids'] <= 0 || $params['poids'] > $maxWeight) {
        $this->debug[$carrier]['errors'][] = [
            'code' => self::ERROR_WEIGHT_RANGE,
            'message' => "Poids invalide ({$params['poids']}kg). 
                         Le poids doit être entre 1 et {$maxWeight}kg"
        ];
        return false;
    }
    
    // Vérification des départements blacklistés
    if ($this->isDepartmentBlacklisted($carrier, $params['departement'])) {
        $this->debug[$carrier]['errors'][] = [
            'code' => self::ERROR_BLACKLISTED_DEPARTMENT,
            'message' => "Le département {$params['departement']} est en liste noire"
        ];
        return false;
    }
    
    return true;
}
    
    /**
     * Vérification départements blacklistés
     */
    private function isDepartmentBlacklisted(string $carrier, string $dept): bool {
        try {
            $sql = "SELECT departements_blacklistes FROM gul_taxes_transporteurs WHERE transporteur = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carrier]);
            $row = $stmt->fetch();
            
            if ($row && $row['departements_blacklistes']) {
                $blacklisted = explode(',', str_replace(' ', '', $row['departements_blacklistes']));
                return in_array($dept, $blacklisted);
            }
            
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Détermination meilleur tarif - FORMAT ATTENDU
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
            'name' => $this->carrierNames[$bestCarrier] ?? strtoupper($bestCarrier),
            'savings' => $this->calculateSavings($validResults, $validResults[$bestCarrier])
        ];
    }
    
    /**
     * Calcul économies vs autres transporteurs
     */
    private function calculateSavings(array $results, float $bestPrice): array {
        $savings = [];
        
        foreach ($results as $carrier => $price) {
            if ($price > $bestPrice) {
                $savings[$carrier] = [
                    'amount' => round($price - $bestPrice, 2),
                    'percentage' => round((($price - $bestPrice) / $price) * 100, 1)
                ];
            }
        }
        
        return $savings;
    }
}

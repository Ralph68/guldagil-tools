<?php
// src/modules/calculateur/services/transportcalculateur.php - VERSION CORRIGÉE avec logique Excel

declare(strict_types=1);

class Transport
{
    private PDO $db;
    public array $debug = [];

    /** Mapping transporteur → table des tarifs */
    private array $tables = [
        'xpo'      => 'gul_xpo_rates',
        'heppner'  => 'gul_heppner_rates',
        'kn'       => 'gul_kn_rates',
    ];
    
    /** Mapping transporteur → nom dans la table taxes */
    private array $taxNames = [
        'xpo'      => 'XPO',
        'heppner'  => 'Heppner',
        'kn'       => 'Kuehne + Nagel',
    ];

    /** Départements IDF */
    private array $idfDepartments = ['75', '77', '78', '91', '92', '93', '94', '95'];
    
    /** Départements saisonniers XPO */
    private array $xpoSeasonalDepartments = [
        '04', '05', '06', '09', '11', '13', '17', '20', '30', '31', '32', '33', '34', 
        '40', '44', '47', '56', '64', '65', '66', '83', '84', '85', '98'
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Calcule les tarifs pour tous les transporteurs
     */
    public function calculateAll(array $params): array
    {
        $results = [];
        $this->debug = [];

        foreach ($this->tables as $carrier => $table) {
            try {
                $results[$carrier] = $this->calculateForCarrier($carrier, $params);
            } catch (Exception $e) {
                $results[$carrier] = null;
                $this->debug[$carrier] = ['error' => $e->getMessage()];
            }
        }

        return [
            'results' => $results,
            'debug' => $this->debug,
            'best' => $this->findBestRate($results),
            'suggestions' => $this->generateSuggestions($params, $results)
        ];
    }

    /**
     * Calcule le tarif pour un transporteur spécifique selon logique Excel
     */
    private function calculateForCarrier(string $carrier, array $params): ?float
    {
        $departement = $params['departement'];
        $poids = $params['poids'];
        $type = $params['type'];
        $adr = $params['adr'];
        $option_sup = $params['option_sup'];
        $enlevement = $params['enlevement'];
        $palettes = $params['palettes'];

        $this->debug[$carrier] = [
            'params' => $params,
            'carrier' => $carrier
        ];
        // Normaliser ADR en booléen si c'est une string
if (is_string($params['adr'])) {
    $params['adr'] = ($params['adr'] === 'oui');
}

        // 1. Vérifications de compatibilité
        if (!$this->validateCarrierConstraints($carrier, $params)) {
            return null;
        }

        // 2. Calcul selon le transporteur
        switch ($carrier) {
            case 'heppner':
                return $this->calculateHeppner($departement, $poids, $type, $adr, $option_sup, $enlevement, $palettes);
            
            case 'xpo':
                return $this->calculateXPO($departement, $poids, $type, $adr, $option_sup, $enlevement, $palettes);
            
            case 'kn':
                // TODO: Implémenter K+N plus tard
                return null;
                
            default:
                return null;
        }
    }

    /**
     * CALCUL HEPPNER selon logique Excel
     */
    private function calculateHeppner(string $dept, float $poids, string $type, bool $adr, string $option_sup, bool $enlevement, int $palettes): ?float
    {
        // Vérification: ADR + Star/Priority = impossible
        if ($adr && in_array($option_sup, ['star18', 'star13', 'datefixe18', 'datefixe13'])) {
            $this->debug['heppner']['error'] = 'ADR incompatible avec Star/Priority';
            return null;
        }

        // Limite poids pour Star/Priority
        if ($poids > 1000 && in_array($option_sup, ['star18', 'star13', 'datefixe18', 'datefixe13'])) {
            $this->debug['heppner']['error'] = 'Star/Priority limité à 1000kg';
            return null;
        }

        // 1. Calcul tarif de base : MIN(forfait_100kg, tranche_exacte)
        $tarifForfait = $this->getHeppnerForfait($dept, $poids);
        $tarifTranche = $this->getHeppnerTranche($dept, $poids);
        
        if ($tarifForfait === null && $tarifTranche === null) {
            $this->debug['heppner']['error'] = 'Aucun tarif trouvé';
            return null;
        }

        $tarifBase = min($tarifForfait ?? PHP_FLOAT_MAX, $tarifTranche ?? PHP_FLOAT_MAX);
        
        $this->debug['heppner']['tarif_forfait'] = $tarifForfait;
        $this->debug['heppner']['tarif_tranche'] = $tarifTranche;
        $this->debug['heppner']['tarif_base'] = $tarifBase;

        // Suggestion "Payant pour 100kg"
        if ($poids < 100 && $tarifForfait < $tarifTranche) {
            $this->debug['heppner']['suggestion'] = 'Payant pour 100kg (-' . number_format($tarifTranche - $tarifForfait, 2) . '€)';
        }

        // 2. Application des options selon service
        $tarifFinal = $this->applyHeppnerOptions($tarifBase, $option_sup, $poids);

        // 3. Application surcharge gasoil
        $surchargeGasoil = $this->getSurchargeGasoil('heppner');
        $tarifFinal = $tarifFinal * (1 + $surchargeGasoil);

        $this->debug['heppner']['tarif_final'] = $tarifFinal;
        $this->debug['heppner']['surcharge_gasoil'] = $surchargeGasoil * 100 . '%';

        return round($tarifFinal, 2);
    }

    /**
     * Calcul forfait Heppner (100-300kg + majorations)
     */
    private function getHeppnerForfait(string $dept, float $poids): ?float
    {
        if ($poids >= 100) return null; // Forfait uniquement < 100kg

        $sql = "SELECT tarif_100_299 FROM gul_heppner_rates WHERE num_departement = :dept LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dept' => $dept]);
        $result = $stmt->fetch();

        if (!$result || !$result['tarif_100_299']) {
            return null;
        }

        $tarif = (float)$result['tarif_100_299'];

        // Majorations fixes Heppner
        $tarif += $this->getHeppnerMajorations($dept);

        return $tarif;
    }

    /**
     * Calcul par tranche Heppner
     */
    private function getHeppnerTranche(string $dept, float $poids): ?float
    {
        // Déterminer la colonne selon le poids
        $column = $this->getHeppnerColumn($poids);
        if (!$column) return null;

        $sql = "SELECT `$column` FROM gul_heppner_rates WHERE num_departement = :dept LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dept' => $dept]);
        $result = $stmt->fetch();

        if (!$result || !$result[$column]) {
            return null;
        }

        $tarif = (float)$result[$column];

        // Pour >= 100kg : calcul au poids
        if ($poids >= 100) {
            $tarif = ($poids / 100) * $tarif;
        }

        // Majorations fixes Heppner
        $tarif += $this->getHeppnerMajorations($dept);

        return $tarif;
    }

    /**
     * Majorations fixes Heppner
     */
    private function getHeppnerMajorations(string $dept): float
    {
        $total = 0;

        // Majoration IDF
        if (in_array($dept, $this->idfDepartments)) {
            $total += 7.35;
        }

        // Taxes fixes
        $total += 0.5;  // Sûreté
        $total += 0.4;  // Contribution sanitaire  
        $total += 2.3;  // Transition énergétique

        return $total;
    }

    /**
     * Détermine la colonne Heppner selon le poids
     */
    private function getHeppnerColumn(float $poids): ?string
    {
        if ($poids < 10) return 'tarif_0_9';
        if ($poids < 20) return 'tarif_10_19';
        if ($poids < 30) return 'tarif_20_29';
        if ($poids < 40) return 'tarif_30_39';
        if ($poids < 50) return 'tarif_40_49';
        if ($poids < 60) return 'tarif_50_59';
        if ($poids < 70) return 'tarif_60_69';
        if ($poids < 80) return 'tarif_70_79';
        if ($poids < 90) return 'tarif_80_89';
        if ($poids < 100) return 'tarif_90_99';
        if ($poids < 300) return 'tarif_100_299';
        if ($poids < 500) return 'tarif_300_499';
        if ($poids < 1000) return 'tarif_500_999';
        if ($poids < 2000) return 'tarif_1000_1999';

        return null; // Hors grille
    }

    /**
     * Application des options Heppner
     */
    private function applyHeppnerOptions(float $tarifBase, string $option, float $poids): float
    {
        switch ($option) {
            case 'standard':
                return $tarifBase;
                
            case 'rdv':
                $coutRDV = $this->getOptionCost('heppner', 'rdv');
                return $tarifBase + $coutRDV;
                
            case 'star18':
            case 'star13':
            case 'datefixe18': 
            case 'datefixe13':
                return $this->getHeppnerStarTariff($tarifBase, $option, $poids);
                
            default:
                return $tarifBase;
        }
    }

    /**
     * Tarifs Star/Priority Heppner (Tableau1320)
     */
    private function getHeppnerStarTariff(float $tarifBase, string $option, float $poids): float
    {
        // Recherche dans la table Star/Priority selon le poids
        $poidsKey = $this->getStarWeightKey($poids);
        $column = in_array($option, ['star13', 'datefixe13']) ? 'tarif_13h' : 'tarif_18h';
        
        $sql = "SELECT `$column` FROM gul_heppner_star WHERE poids_max = :poids LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':poids' => $poidsKey]);
        $result = $stmt->fetch();

        if ($result && $result[$column]) {
            $tarifStar = (float)$result[$column];
            // Ajouter les majorations
            $tarifStar += $this->getHeppnerMajorations('75'); // Base IDF pour Star
            return $tarifStar;
        }

        // Fallback sur tarif de base si pas trouvé
        return $tarifBase;
    }

    /**
     * CALCUL XPO selon logique Excel
     */
    private function calculateXPO(string $dept, float $poids, string $type, bool $adr, string $option_sup, bool $enlevement, int $palettes): ?float
    {
        // XPO n'accepte que les palettes
        if ($type === 'colis') {
            $this->debug['xpo']['error'] = 'XPO n\'accepte que les palettes';
            return null;
        }

        // 1. Calcul tarif de base selon logique XPO
        $tarifBase = $this->getXPOBaseTariff($dept, $poids);
        if ($tarifBase === null) {
            $this->debug['xpo']['error'] = 'Aucun tarif trouvé';
            return null;
        }

        $this->debug['xpo']['tarif_base'] = $tarifBase;

        // 2. Majorations XPO
        $tarifBase = $this->applyXPOMajorations($tarifBase, $dept);

        // 3. Majoration ADR
        if ($adr) {
            $majorationADR = max($tarifBase * 0.20, 10);
            $tarifBase += $majorationADR;
            $this->debug['xpo']['majoration_adr'] = $majorationADR;
        }

        // 4. Options de service
        $tarifBase = $this->applyXPOOptions($tarifBase, $option_sup, $adr);

        // 5. Palettes EUR
        if ($palettes > 0) {
            $coutPalettes = $palettes * 1.8;
            $tarifBase += $coutPalettes;
            $this->debug['xpo']['cout_palettes'] = $coutPalettes;
        }

        // 6. Enlèvement
        if ($enlevement) {
            $tarifBase += 25;
            $this->debug['xpo']['enlevement'] = 25;
        }

        // 7. Taxe fixe
        $tarifBase += 1.75;

        // 8. Surcharge gasoil
        $surchargeGasoil = $this->getSurchargeGasoil('xpo');
        $tarifBase = $tarifBase * (1 + $surchargeGasoil);

        $this->debug['xpo']['tarif_final'] = $tarifBase;
        $this->debug['xpo']['surcharge_gasoil'] = $surchargeGasoil * 100 . '%';

        return round($tarifBase, 2);
    }

    /**
     * Calcul tarif de base XPO
     */
    private function getXPOBaseTariff(string $dept, float $poids): ?float
    {
        if ($poids < 100) {
            // MIN(forfait_0-99, calcul_100kg)
            $forfait = $this->getXPOTariff($dept, 'tarif_0_99');
            $calcul100 = $this->getXPOTariff($dept, 'tarif_100_499');
            
            if ($forfait === null && $calcul100 === null) return null;
            
            $tarifMin = min($forfait ?? PHP_FLOAT_MAX, $calcul100 ?? PHP_FLOAT_MAX);
            
            // Suggestion si calcul 100kg plus intéressant
            if ($calcul100 < $forfait) {
                $this->debug['xpo']['suggestion'] = 'Payant pour 100kg (-' . number_format($forfait - $calcul100, 2) . '€)';
            }
            
            return $tarifMin;
        } else {
            // Calcul au poids selon tranches
            $column = $this->getXPOColumn($poids);
            if (!$column) return null;
            
            $tarifAu100 = $this->getXPOTariff($dept, $column);
            if ($tarifAu100 === null) return null;
            
            return ($poids / 100) * $tarifAu100;
        }
    }

    /**
     * Récupère un tarif XPO
     */
    private function getXPOTariff(string $dept, string $column): ?float
    {
        $sql = "SELECT `$column` FROM gul_xpo_rates WHERE num_departement = :dept LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dept' => $dept]);
        $result = $stmt->fetch();

        return $result && $result[$column] ? (float)$result[$column] : null;
    }

    /**
     * Détermine la colonne XPO selon le poids
     */
    private function getXPOColumn(float $poids): ?string
    {
        if ($poids < 100) return 'tarif_0_99';
        if ($poids < 500) return 'tarif_100_499';
        if ($poids < 1000) return 'tarif_500_999';
        if ($poids < 2000) return 'tarif_1000_1999';
        if ($poids < 3000) return 'tarif_2000_2999';

        return null; // Hors grille
    }

    /**
     * Application des majorations XPO
     */
    private function applyXPOMajorations(float $tarif, string $dept): float
    {
        // Majoration IDF
        if (in_array($dept, $this->idfDepartments)) {
            $tarif *= 1.07;
            $this->debug['xpo']['majoration_idf'] = '×1.07';
        }

        // Majoration saisonnière (1er avril - 31 août)
        if ($this->isSeasonalPeriod() && in_array($dept, $this->xpoSeasonalDepartments)) {
            $tarif *= 1.1;
            $this->debug['xpo']['majoration_saisonniere'] = '×1.1';
        }

        // Taxes fixes XPO
        $tarif += 1.45 + 0.7; // 2.15€ total
        $this->debug['xpo']['taxes_fixes'] = '+2.15€';

        return $tarif;
    }

    /**
     * Application des options XPO
     */
    private function applyXPOOptions(float $tarif, string $option, bool $adr): float
    {
        switch ($option) {
            case 'rdv':
                $coutRDV = $this->getOptionCost('xpo', 'rdv');
                return $tarif + $coutRDV;
                
            case 'premium18':
            case 'premium13':
                $majoration = max($tarif * 0.30, 25);
                $this->debug['xpo']['option_premium'] = '+' . number_format($majoration, 2) . '€';
                return $tarif + $majoration;
                
            case 'datefixe18':
            case 'datefixe13':
                $majoration = max(min($tarif * 0.15, 40), 25);
                $this->debug['xpo']['option_datefixe'] = '+' . number_format($majoration, 2) . '€';
                return $tarif + $majoration;
                
            default:
                return $tarif;
        }
    }

    /**
     * Vérifie la période saisonnière
     */
    private function isSeasonalPeriod(): bool
    {
        $now = new DateTime();
        $year = $now->format('Y');
        $start = new DateTime("$year-04-01");
        $end = new DateTime("$year-08-31");
        
        return $now >= $start && $now <= $end;
    }

    /**
     * Validation des contraintes par transporteur
     */
    private function validateCarrierConstraints(string $carrier, array $params): bool
    {
        // XPO et K+N : palettes uniquement
        if (in_array($carrier, ['xpo', 'kn']) && $params['type'] === 'colis') {
            $this->debug[$carrier]['error'] = 'Colis non accepté';
            return false;
        }

        // Vérification poids maximum
        $maxWeight = $this->getCarrierMaxWeight($carrier);
        if ($params['poids'] > $maxWeight) {
            $this->debug[$carrier]['error'] = "Poids dépassé (max: {$maxWeight}kg)";
            return false;
        }

        // Enlèvement + options premium incompatibles
        if ($params['enlevement'] && in_array($params['option_sup'], ['star18', 'star13', 'premium18', 'premium13', 'rdv', 'datefixe18', 'datefixe13'])) {
            $this->debug[$carrier]['warning'] = 'Options premium non disponibles avec enlèvement';
            // On continue mais on ignore l'option
            $params['option_sup'] = 'standard';
        }

        return true;
    }

    /**
     * Trouve le meilleur tarif
     */
    private function findBestRate(array $results): ?array
    {
        $bestPrice = null;
        $bestCarrier = null;

        foreach ($results as $carrier => $price) {
            if ($price !== null && ($bestPrice === null || $price < $bestPrice)) {
                $bestPrice = $price;
                $bestCarrier = $carrier;
            }
        }

        return $bestCarrier ? ['carrier' => $bestCarrier, 'price' => $bestPrice] : null;
    }

    /**
     * Génère des suggestions intelligentes
     */
    private function generateSuggestions(array $params, array $results): array
    {
        $suggestions = [];

        // Suggestion ADR + Star/Priority
        if ($params['adr'] && in_array($params['option_sup'], ['star18', 'star13'])) {
            if ($results['heppner'] === null && $results['xpo'] !== null) {
                $standardPrice = $this->calculateXPO($params['departement'], $params['poids'], $params['type'], false, 'standard', $params['enlevement'], $params['palettes']);
                if ($standardPrice) {
                    $surcoût = $results['xpo'] - $standardPrice;
                    $suggestions[] = [
                        'type' => 'adr_premium',
                        'message' => "⚠️ Heppner ne propose pas Star/Priority avec ADR → XPO +{$surcoût}€ (+24h délai)",
                        'alternative' => 'Expressiste dédié (~400€) - Contacter service achat'
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Récupère le poids maximum d'un transporteur
     */
    private function getCarrierMaxWeight(string $carrier): int
    {
        $sql = "SELECT poids_maximum FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $this->taxNames[$carrier]]);
        $result = $stmt->fetch();

        return $result ? (int)$result['poids_maximum'] : 3000;
    }

    /**
     * Récupère la surcharge gasoil
     */
    private function getSurchargeGasoil(string $carrier): float
    {
        $sql = "SELECT surcharge_gasoil FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $this->taxNames[$carrier]]);
        $result = $stmt->fetch();

        return $result && $result['surcharge_gasoil'] ? (float)$result['surcharge_gasoil'] / 100 : 0;
    }

    /**
     * Récupère le coût d'une option
     */
    private function getOptionCost(string $carrier, string $option): float
    {
        $sql = "SELECT montant FROM gul_options_supplementaires 
                WHERE transporteur = :carrier AND code_option = :option AND actif = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrier, ':option' => $option]);
        $result = $stmt->fetch();

        return $result ? (float)$result['montant'] : 0;
    }

    /**
     * Clé de poids pour table Star
     */
    private function getStarWeightKey(float $poids): int
    {
        if ($poids <= 9) return 9;
        if ($poids <= 39) return 39;
        if ($poids <= 69) return 69;
        if ($poids <= 99) return 99;
        if ($poids <= 249) return 249;
        if ($poids <= 499) return 499;
        if ($poids <= 999) return 999;

        return 999; // Max
    }
}

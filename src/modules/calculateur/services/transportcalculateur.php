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
        // 'kn'       => 'gul_kn_rates', // Désactivé temporairement
    ];
    
    /** Mapping transporteur → nom dans la table taxes */
    private array $taxNames = [
        'xpo'      => 'XPO',
        'heppner'  => 'Heppner',
        'kn'       => 'Kuehne + Nagel',
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
                // Désactivé temporairement
                $this->debug['kn']['error'] = 'K+N temporairement désactivé';
                return null;
                
            default:
                return null;
        }
    }

    /**
     * CALCUL HEPPNER corrigé selon logique Excel
     */
    private function calculateHeppner(string $dept, float $poids, string $type, bool $adr, string $option_sup, bool $enlevement, int $palettes): ?float
    {
        // Vérifications compatibilité
        if ($adr && in_array($option_sup, ['star18', 'star13', 'datefixe18', 'datefixe13'])) {
            $this->debug['heppner']['error'] = 'ADR incompatible avec Star/Priority';
            return null;
        }

        if ($poids > 1000 && in_array($option_sup, ['star18', 'star13', 'datefixe18', 'datefixe13'])) {
            $this->debug['heppner']['error'] = 'Star/Priority limité à 1000kg';
            return null;
        }

        // 1. Calcul tarif de base avec logique MIN(forfait, tranche supérieure)
        $tarifBase = $this->getHeppnerBaseTariffWithComparison($dept, $poids);
        if ($tarifBase === null) {
            $this->debug['heppner']['error'] = 'Aucun tarif trouvé';
            return null;
        }
        
        $this->debug['heppner']['tarif_base'] = $tarifBase;

        // 2. Calcul des pourcentages UNIQUEMENT sur le forfait transport
        $forfaitTransport = $tarifBase; // Le tarif de base = forfait transport expedition
        
        // Surcharge gasoil (% sur forfait transport uniquement)
        $surchargeGasoil = $this->getSurchargeGasoil('heppner');
        $montantSurchargeGasoil = $forfaitTransport * $surchargeGasoil;
        
        $this->debug['heppner']['forfait_transport'] = number_format($forfaitTransport, 2) . '€';
        $this->debug['heppner']['surcharge_gasoil'] = number_format($surchargeGasoil * 100, 2) . '% sur forfait = ' . number_format($montantSurchargeGasoil, 2) . '€';

        // Haute Saison (% sur forfait transport uniquement) - juillet/août
        $montantHauteSaison = 0;
        if ($this->isHauteSaison()) {
            $montantHauteSaison = max($forfaitTransport * 0.10, 5.00);
            $this->debug['heppner']['haute_saison'] = '10% sur forfait (min 5€) = ' . number_format($montantHauteSaison, 2) . '€';
        }
        
        // Sous-total avec pourcentages
        $sousTotal = $forfaitTransport + $montantSurchargeGasoil + $montantHauteSaison;

        // 3. ADR (aucun impact pour Heppner)
        if ($adr) {
            $this->debug['heppner']['adr'] = '0€ (pas d\'impact)';
        }

        // 4. Options de service
        $coutOption = 0;
        switch ($option_sup) {
            case 'rdv':
                $coutOption = $this->getOptionCost('heppner', 'rdv');
                $this->debug['heppner']['option_rdv'] = number_format($coutOption, 2) . '€';
                break;
            case 'star18':
            case 'star13':
            case 'datefixe18': 
            case 'datefixe13':
                // Utiliser les tarifs Star depuis gul_heppner_star
                $tarifStar = $this->getHeppnerStarTariffFromDB($option_sup, $poids);
                if ($tarifStar !== null) {
                    // Recalculer avec le nouveau forfait transport
                    $forfaitTransport = $tarifStar;
                    $montantSurchargeGasoil = $forfaitTransport * $surchargeGasoil;
                    if ($this->isHauteSaison()) {
                        $montantHauteSaison = max($forfaitTransport * 0.10, 5.00);
                    }
                    $sousTotal = $forfaitTransport + $montantSurchargeGasoil + $montantHauteSaison;
                    $this->debug['heppner']['tarif_star_' . $option_sup] = number_format($tarifStar, 2) . '€';
                }
                break;
        }

        // 5. Enlèvement si applicable
        $coutEnlevement = 0;
        if ($enlevement) {
            $coutEnlevement = 0; // À configurer dans gul_taxes_transporteurs si nécessaire
            if ($coutEnlevement > 0) {
                $this->debug['heppner']['enlevement'] = number_format($coutEnlevement, 2) . '€';
            }
        }

        // 6. Contributions depuis BDD gul_taxes_transporteurs
        $contributions = $this->getHeppnerContributions();
        
        $this->debug['heppner']['pase'] = number_format($contributions['surete'], 2) . '€';
        $this->debug['heppner']['csse'] = number_format($contributions['contribution_sanitaire'], 2) . '€';
        $this->debug['heppner']['transition_energetique'] = number_format($contributions['participation_transition_energetique'], 2) . '€';

        // 7. Total final
        $totalContributions = $contributions['surete'] + $contributions['contribution_sanitaire'] + $contributions['participation_transition_energetique'];
        $tarifFinal = $sousTotal + $coutOption + $coutEnlevement + $totalContributions;
        
        $this->debug['heppner']['tarif_final'] = $tarifFinal;
        $this->debug['heppner']['detail_calcul'] = [
            'tarif_base' => $tarifBase,
            'surcharge_gasoil' => $montantSurchargeGasoil,
            'haute_saison' => $montantHauteSaison,
            'option' => $coutOption,
            'enlevement' => $coutEnlevement,
            'taxes_fixes' => $totalContributions,
            'total' => $tarifFinal
        ];

        return round($tarifFinal, 2);
    }

    /**
     * CALCUL XPO selon logique Excel avec BDD
     */
    private function calculateXPO(string $dept, float $poids, string $type, bool $adr, string $option_sup, bool $enlevement, int $palettes): ?float
    {
        // 1. Vérifications contraintes XPO
        if ($type === 'colis') {
            $this->debug['xpo']['error'] = 'XPO n\'accepte que les palettes';
            return null;
        }
        
        // 2. Vérification blacklist depuis BDD
        $blacklistedDepts = $this->getCarrierBlacklist('xpo');
        if (in_array($dept, $blacklistedDepts)) {
            $this->debug['xpo']['error'] = "XPO non disponible pour le département $dept";
            return null;
        }

        // 3. Récupération paramètres BDD
        $xpoParams = $this->getXPOParamsFromDB();
        
        // 4. Tarif de base
        $tarifBase = $this->getXPOBaseTariff($dept, $poids);
        if ($tarifBase === null) {
            $this->debug['xpo']['error'] = 'Aucun tarif trouvé';
            return null;
        }
        
        $this->debug['xpo']['tarif_base'] = number_format($tarifBase, 2) . '€';

        // 5. Surcharge gasoil sur tarif de base UNIQUEMENT
        $surchargeGasoil = $xpoParams['surcharge_gasoil'];
        if ($surchargeGasoil > 0) {
            $montantSurcharge = $tarifBase * $surchargeGasoil;
            $tarifBase += $montantSurcharge;
            $this->debug['xpo']['surcharge_gasoil'] = number_format($surchargeGasoil * 100, 2) . '% sur tarif base = +' . number_format($montantSurcharge, 2) . '€';
        }

        $tarif = $tarifBase;

        // 6. ADR AVANT autres majorations
        if ($adr) {
            $majorationADR = min(max($tarif * ($xpoParams['majoration_adr_taux'] / 100), 10), 100);
            $tarif += $majorationADR;
            $this->debug['xpo']['majoration_adr'] = number_format($majorationADR, 2) . '€ (' . $xpoParams['majoration_adr_taux'] . '%, min 10€, max 100€)';
        }

        // 7. IDF depuis BDD
        if (in_array($dept, explode(',', $xpoParams['majoration_idf_departements']))) {
            $contributionIDF = $tarif * ($xpoParams['majoration_idf_valeur'] / 100);
            $tarif += $contributionIDF;
            $this->debug['xpo']['contribution_idf'] = number_format($contributionIDF, 2) . '€ (+' . $xpoParams['majoration_idf_valeur'] . '%)';
        }

        // 8. Saisonnier depuis BDD
        if ($this->isSeasonalPeriod() && in_array($dept, explode(',', $xpoParams['majoration_saisonniere_departements']))) {
            $majorationSaison = $tarif * ($xpoParams['majoration_saisonniere_taux'] / 100);
            $tarif += $majorationSaison;
            $this->debug['xpo']['majoration_saisonniere'] = number_format($majorationSaison, 2) . '€ (+' . $xpoParams['majoration_saisonniere_taux'] . '%)';
        }

        // 9. Taxes fixes depuis BDD
        $tarif += $xpoParams['participation_transition_energetique'];
        $tarif += $xpoParams['surete'];
        $this->debug['xpo']['pte'] = '+' . $xpoParams['participation_transition_energetique'] . '€';
        $this->debug['xpo']['surete'] = '+' . $xpoParams['surete'] . '€';

        // 10. Palettes EUR
        if ($palettes > 0) {
            $coutPalettes = $palettes * 1.80;
            $tarif += $coutPalettes;
            $this->debug['xpo']['cout_palettes'] = number_format($coutPalettes, 2) . '€ (' . $palettes . ' × 1,80€)';
        }

        // 11. Options de service
        switch ($option_sup) {
            case 'rdv':
                $tarif += $xpoParams['option_rdv_tarif'];
                $this->debug['xpo']['option_rdv'] = '+' . $xpoParams['option_rdv_tarif'] . '€';
                break;
            case 'premium18':
            case 'premium13':
                $majoration = max($tarif * 0.30, 25);
                $tarif += $majoration;
                $this->debug['xpo']['option_premium'] = '+' . number_format($majoration, 2) . '€ (30%, min 25€)';
                break;
            case 'datefixe18':
            case 'datefixe13':
                $majoration = max(min($tarif * 0.15, 40), 25);
                $tarif += $majoration;
                $this->debug['xpo']['option_target'] = '+' . number_format($majoration, 2) . '€ (15%, min 25€, max 40€)';
                break;
        }

        // 12. Enlèvement
        if ($enlevement) {
            $tarif += 25.00;
            $this->debug['xpo']['enlevement'] = '+25,00€';
        }

        $this->debug['xpo']['tarif_final'] = number_format($tarif, 2) . '€';
        return round($tarif, 2);
    }

    /**
     * Vérifie si on est en période haute saison
     */
    private function isHauteSaison(): bool
    {
        return in_array((int)date('n'), [7, 8]);
    }

    /**
     * Calcul tarif de base Heppner avec comparaison forfait/tranche supérieure
     */
    private function getHeppnerBaseTariffWithComparison(string $dept, float $poids): ?float
    {
        $column = $this->getHeppnerColumn($poids);
        if (!$column) return null;

        $sql = "SELECT `$column` FROM gul_heppner_rates WHERE num_departement = :dept LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dept' => $dept]);
        $result = $stmt->fetch();

        if (!$result || !$result[$column]) {
            return null;
        }

        $tarifPour100kg = (float)$result[$column];
        $tarifActuel = $poids >= 100 ? ($poids / 100) * $tarifPour100kg : $tarifPour100kg;
        
        // Comparer avec tranche supérieure pour optimisation
        $tarifOptimal = $this->compareWithUpperTranche($dept, $poids, $tarifActuel);
        
        return $tarifOptimal;
    }

    /**
     * Compare le tarif actuel avec la tranche supérieure
     */
    private function compareWithUpperTranche(string $dept, float $poids, float $tarifActuel): float
    {
        $trancheSuperieure = $this->getUpperTranche($poids);
        if (!$trancheSuperieure) return $tarifActuel;
        
        $sql = "SELECT `{$trancheSuperieure['column']}` FROM gul_heppner_rates WHERE num_departement = :dept LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dept' => $dept]);
        $result = $stmt->fetch();
        
        if ($result && $result[$trancheSuperieure['column']]) {
            $tarifSuperieur = ($trancheSuperieure['poids'] / 100) * (float)$result[$trancheSuperieure['column']];
            
            if ($tarifSuperieur < $tarifActuel) {
                $economie = $tarifActuel - $tarifSuperieur;
                $this->debug['heppner']['suggestion'] = "Payant pour {$trancheSuperieure['poids']}kg (-" . number_format($economie, 2) . "€)";
                return $tarifSuperieur;
            }
        }
        
        return $tarifActuel;
    }

    /**
     * Détermine la tranche supérieure pour comparaison
     */
    private function getUpperTranche(float $poids): ?array
    {
        if ($poids < 100) return ['column' => 'tarif_100_299', 'poids' => 100];
        if ($poids < 300) return ['column' => 'tarif_300_499', 'poids' => 300];
        if ($poids < 500) return ['column' => 'tarif_500_999', 'poids' => 500];
        if ($poids < 1000) return ['column' => 'tarif_1000_1999', 'poids' => 1000];
        
        return null; // Pas de tranche supérieure
    }

    /**
     * Tarifs Star depuis BDD gul_heppner_star
     */
    private function getHeppnerStarTariffFromDB(string $option, float $poids): ?float
    {
        $column = in_array($option, ['star13', 'datefixe13']) ? 'tarif_13h' : 'tarif_18h';
        
        $sql = "SELECT `$column` FROM gul_heppner_star 
                WHERE poids_max >= :poids AND actif = 1 
                ORDER BY poids_max ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':poids' => $poids]);
        $result = $stmt->fetch();
        
        return $result ? (float)$result[$column] : null;
    }

    /**
     * Récupère les contributions Heppner depuis BDD
     */
    private function getHeppnerContributions(): array
    {
        $sql = "SELECT participation_transition_energetique, contribution_sanitaire, surete 
                FROM gul_taxes_transporteurs WHERE transporteur = 'Heppner' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return [
            'participation_transition_energetique' => $result ? (float)$result['participation_transition_energetique'] : 0.50,
            'contribution_sanitaire' => $result ? (float)$result['contribution_sanitaire'] : 0.40,
            'surete' => $result ? (float)$result['surete'] : 2.30
        ];
    }

    /**
     * Correction colonne Heppner pour 450kg
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
        if ($poids < 500) return 'tarif_300_499'; // 450kg tombe ici
        if ($poids < 1000) return 'tarif_500_999';
        if ($poids < 2000) return 'tarif_1000_1999';

        return null;
    }

    /**
     * Calcul tarif de base XPO
     */
    private function getXPOBaseTariff(string $dept, float $poids): ?float
    {
        if ($poids < 100) {
            $forfait = $this->getXPOTariff($dept, 'tarif_0_99');
            $calcul100 = $this->getXPOTariff($dept, 'tarif_100_499');
            
            if ($forfait === null && $calcul100 === null) return null;
            
            $tarifMin = min($forfait ?? PHP_FLOAT_MAX, $calcul100 ?? PHP_FLOAT_MAX);
            
            if ($calcul100 && $forfait && $calcul100 < $forfait) {
                $this->debug['xpo']['suggestion'] = 'Payant pour 100kg (-' . number_format($forfait - $calcul100, 2) . '€)';
            }
            
            return $tarifMin;
        } else {
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
        return null;
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
     * Récupère les départements blacklistés d'un transporteur
     */
    private function getCarrierBlacklist(string $carrier): array
    {
        $sql = "SELECT departements_blacklistes FROM gul_taxes_transporteurs 
                WHERE transporteur = :carrier LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $this->taxNames[$carrier]]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['departements_blacklistes']) {
            return [];
        }
        
        return array_map('trim', explode(',', $result['departements_blacklistes']));
    }

    /**
     * Récupère tous les paramètres XPO depuis la BDD
     */
    private function getXPOParamsFromDB(): array
    {
        $sql = "SELECT * FROM gul_taxes_transporteurs WHERE transporteur = 'XPO' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if (!$result) {
            throw new Exception('Paramètres XPO non trouvés en BDD');
        }
        
        return [
            'majoration_adr_taux' => (float)$result['majoration_adr_taux'],
            'majoration_idf_valeur' => (float)$result['majoration_idf_valeur'],
            'majoration_idf_departements' => $result['majoration_idf_departements'],
            'majoration_saisonniere_taux' => (float)$result['majoration_saisonniere_taux'],
            'majoration_saisonniere_departements' => $result['majoration_saisonniere_departements'],
            'participation_transition_energetique' => (float)$result['participation_transition_energetique'],
            'surete' => (float)$result['surete'],
            'surcharge_gasoil' => (float)$result['surcharge_gasoil'],
            'option_rdv_tarif' => (float)$result['option_rdv_tarif'] ?: 6.50
        ];
    }

    /**
     * Validation des contraintes par transporteur
     */
    private function validateCarrierConstraints(string $carrier, array $params): bool
    {
        switch ($carrier) {
            case 'heppner':
                if ($params['type'] === 'colis') {
                    if ($params['poids'] > 60) {
                        $this->debug['heppner']['error'] = 'Colis limité à 60kg - Utilisez palette';
                        return false;
                    }
                    if ($params['palettes'] > 2) {
                        $this->debug['heppner']['error'] = 'Maximum 2 colis par expédition';
                        return false;
                    }
                }
                break;
            case 'xpo':
            case 'kn':
                if ($params['type'] === 'colis') {
                    $this->debug[$carrier]['error'] = 'Colis non accepté';
                    return false;
                }
                break;
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

        return $result && $result['surcharge_gasoil'] ? (float)$result['surcharge_gasoil'] : 0;
    }

    /**
     * Récupère le coût d'une option
     */
    private function getOptionCost(string $carrier, string $option): float
    {
        if ($option === 'rdv') {
            $sql = "SELECT option_rdv_tarif FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':carrier' => $this->taxNames[$carrier]]);
            $result = $stmt->fetch();
            
            return $result && $result['option_rdv_tarif'] ? (float)$result['option_rdv_tarif'] : 0;
        }
        
        return 0;
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

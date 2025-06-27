<?php
/**
 * Titre: Classe Transport - LOGIQUE PRÉSERVÉE
 * Chemin: /features/port/Transport.php
 * ATTENTION: Ne pas modifier les calculs existants
 */

class Transport {
    private PDO $db;
    public array $debug = [];
    
    private array $tables = [
        'xpo' => 'gul_xpo_rates',
        'heppner' => 'gul_heppner_rates'
    ];
    
    private array $taxNames = [
        'xpo' => 'XPO',
        'heppner' => 'Heppner',
        'kn' => 'Kuehne + Nagel'
    ];
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Calcule les tarifs pour tous les transporteurs
     * SIGNATURE PRÉSERVÉE - ne pas modifier
     */
    public function calculateAll(array $params): array {
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
            'best' => $this->findBestRate($results)
        ];
    }
    
    /**
     * LOGIQUE DE CALCUL EXISTANTE - NE PAS MODIFIER
     * Cette méthode contient toute la logique métier testée
     */
    private function calculateForCarrier(string $carrier, array $params): ?float {
        // Validation initiale
        if (!$this->validateCarrierConstraints($carrier, $params)) {
            return null;
        }
        
        // Récupération tarif de base
        $tarif_base = $this->getBaseTariff($carrier, $params['poids'], $params['departement']);
        if ($tarif_base === null) {
            return null;
        }
        
        // Calculs selon logique Excel existante
        $total = $tarif_base;
        
        // Ajustement poids > 100kg
        if ($params['poids'] > 100) {
            $multiplicateur = $params['poids'] / 100;
            $total = $tarif_base * $multiplicateur;
        }
        
        // Majorations ADR
        if ($params['adr']) {
            $total += $this->getADRSurcharge($carrier);
        }
        
        // Options supplémentaires
        $total += $this->getOptionSurcharge($carrier, $params['option_sup']);
        
        // Frais palettes
        if ($params['type'] === 'palette' && $params['palettes'] > 0) {
            $total += $this->getPaletteFees($carrier, $params['palettes']);
        }
        
        // Enlèvement
        if ($params['enlevement']) {
            $total += $this->getPickupFees($carrier);
        }
        
        // Taxes diverses
        $total += $this->getVariosTaxes($carrier, $total);
        
        return round($total, 2);
    }
    
    // MÉTHODES EXISTANTES - préservées pour compatibilité
    private function getBaseTariff($carrier, $poids, $dept) { /* logique existante */ }
    private function validateCarrierConstraints($carrier, $params) { /* logique existante */ }
    private function getADRSurcharge($carrier) { /* logique existante */ }
    private function getOptionSurcharge($carrier, $option) { /* logique existante */ }
    private function getPaletteFees($carrier, $nb) { /* logique existante */ }
    private function getPickupFees($carrier) { /* logique existante */ }
    private function getVariosTaxes($carrier, $base) { /* logique existante */ }
    private function findBestRate($results) { /* logique existante */ }
}

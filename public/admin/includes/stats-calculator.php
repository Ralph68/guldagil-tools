<?php
// includes/stats-calculator.php - Calculateur de statistiques
class StatsCalculator {
    private PDO $db;
    private array $cache = [];
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Récupère toutes les statistiques principales
     */
    public function getAllStats(): array {
        return [
            'carriers' => $this->getCarriersCount(),
            'departments' => $this->getDepartmentsCount(),
            'total_options' => $this->getOptionsCount()['total'],
            'active_options' => $this->getOptionsCount()['active'],
            'inactive_options' => $this->getOptionsCount()['inactive'],
            'total_rates' => $this->getTotalRatesCount(),
            'coverage' => $this->getCoveragePercentage(),
            'calculations_today' => $this->getCalculationsToday(),
            'system_status' => $this->getSystemStatus(),
            'alerts_count' => $this->getAlertsCount()
        ];
    }
    
    /**
     * Compte les transporteurs actifs
     */
    public function getCarriersCount(): int {
        if (isset($this->cache['carriers'])) {
            return $this->cache['carriers'];
        }
        
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM gul_taxes_transporteurs WHERE poids_maximum > 0");
            $this->cache['carriers'] = $stmt->fetch()['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur getCarriersCount: " . $e->getMessage());
            $this->cache['carriers'] = 3; // Valeur par défaut
        }
        
        return $this->cache['carriers'];
    }
    
    /**
     * Compte les départements avec tarifs
     */
    public function getDepartmentsCount(): int {
        if (isset($this->cache['departments'])) {
            return $this->cache['departments'];
        }
        
        try {
            $sql = "SELECT COUNT(DISTINCT num_departement) as count FROM (
                        SELECT num_departement FROM gul_heppner_rates 
                        WHERE num_departement IS NOT NULL AND num_departement != ''
                        UNION 
                        SELECT num_departement FROM gul_xpo_rates 
                        WHERE num_departement IS NOT NULL AND num_departement != ''
                        UNION 
                        SELECT num_departement FROM gul_kn_rates 
                        WHERE num_departement IS NOT NULL AND num_departement != ''
                    ) as all_departments";
            $stmt = $this->db->query($sql);
            $this->cache['departments'] = $stmt->fetch()['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur getDepartmentsCount: " . $e->getMessage());
            $this->cache['departments'] = 95;
        }
        
        return $this->cache['departments'];
    }
    
    /**
     * Compte les options (total, actives, inactives)
     */
    public function getOptionsCount(): array {
        if (isset($this->cache['options'])) {
            return $this->cache['options'];
        }
        
        try {
            $stmt = $this->db->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN actif = 0 THEN 1 ELSE 0 END) as inactive
                FROM gul_options_supplementaires");
            $result = $stmt->fetch();
            $this->cache['options'] = [
                'total' => $result['total'] ?? 0,
                'active' => $result['active'] ?? 0,
                'inactive' => $result['inactive'] ?? 0
            ];
        } catch (Exception $e) {
            error_log("Erreur getOptionsCount: " . $e->getMessage());
            $this->cache['options'] = ['total' => 0, 'active' => 0, 'inactive' => 0];
        }
        
        return $this->cache['options'];
    }
    
    /**
     * Compte le nombre total de tarifs configurés
     */
    public function getTotalRatesCount(): int {
        if (isset($this->cache['total_rates'])) {
            return $this->cache['total_rates'];
        }
        
        try {
            $sql = "SELECT 
                (SELECT COUNT(*) FROM gul_heppner_rates WHERE tarif_100_299 IS NOT NULL AND tarif_100_299 > 0) +
                (SELECT COUNT(*) FROM gul_xpo_rates WHERE tarif_100_499 IS NOT NULL AND tarif_100_499 > 0) +
                (SELECT COUNT(*) FROM gul_kn_rates WHERE tarif_100_299 IS NOT NULL AND tarif_100_299 > 0) as total_rates";
            $stmt = $this->db->query($sql);
            $this->cache['total_rates'] = $stmt->fetch()['total_rates'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur getTotalRatesCount: " . $e->getMessage());
            $this->cache['total_rates'] = 0;
        }
        
        return $this->cache['total_rates'];
    }
    
    /**
     * Calcule le pourcentage de couverture tarifaire
     */
    public function getCoveragePercentage(): float {
        if (isset($this->cache['coverage'])) {
            return $this->cache['coverage'];
        }
        
        $departments = $this->getDepartmentsCount();
        $totalRates = $this->getTotalRatesCount();
        $carriers = $this->getCarriersCount();
        
        if ($departments > 0 && $carriers > 0) {
            $this->cache['coverage'] = round(($totalRates / ($departments * $carriers)) * 100, 1);
        } else {
            $this->cache['coverage'] = 0.0;
        }
        
        return $this->cache['coverage'];
    }
    
    /**
     * Simule le nombre de calculs du jour
     * TODO: Remplacer par une vraie table de logs
     */
    public function getCalculationsToday(): int {
        if (isset($this->cache['calculations'])) {
            return $this->cache['calculations'];
        }
        
        // Simulation basée sur l'activité
        $baseCalculations = 150;
        $coverage = $this->getCoveragePercentage();
        $options = $this->getOptionsCount()['active'];
        
        // Plus de couverture et d'options = plus d'utilisation
        $bonus = ($coverage / 10) + ($options * 5);
        $this->cache['calculations'] = (int)($baseCalculations + $bonus + rand(-50, 100));
        
        return $this->cache['calculations'];
    }
    
    /**
     * Détermine le statut du système
     */
    public function getSystemStatus(): array {
        $coverage = $this->getCoveragePercentage();
        $totalRates = $this->getTotalRatesCount();
        
        if ($coverage >= 80 && $totalRates > 200) {
            return ['status' => 'excellent', 'color' => 'success', 'icon' => '🟢', 'text' => 'Excellent'];
        } elseif ($coverage >= 50 && $totalRates > 100) {
            return ['status' => 'good', 'color' => 'primary', 'icon' => '🔵', 'text' => 'Bon'];
        } elseif ($coverage >= 25) {
            return ['status' => 'warning', 'color' => 'warning', 'icon' => '🟡', 'text' => 'Attention'];
        } else {
            return ['status' => 'critical', 'color' => 'error', 'icon' => '🔴', 'text' => 'Critique'];
        }
    }
    
    /**
     * Compte les alertes système
     */
    public function getAlertsCount(): int {
        $alerts = 0;
        $coverage = $this->getCoveragePercentage();
        $options = $this->getOptionsCount();
        
        // Alerte si couverture faible
        if ($coverage < 30) $alerts++;
        
        // Alerte si pas d'options configurées
        if ($options['active'] === 0) $alerts++;
        
        // Alerte si un transporteur n'a aucun tarif
        $carriers = ['heppner', 'xpo', 'kn'];
        foreach ($carriers as $carrier) {
            if ($this->getCarrierRatesCount($carrier) === 0) {
                $alerts++;
            }
        }
        
        return $alerts;
    }
    
    /**
     * Compte les tarifs d'un transporteur spécifique
     */
    private function getCarrierRatesCount(string $carrier): int {
        $tables = [
            'heppner' => 'gul_heppner_rates',
            'xpo' => 'gul_xpo_rates',
            'kn' => 'gul_kn_rates'
        ];
        
        if (!isset($tables[$carrier])) {
            return 0;
        }
        
        try {
            $table = $tables[$carrier];
            $column = $carrier === 'xpo' ? 'tarif_100_499' : 'tarif_100_299';
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM `$table` 
                                     WHERE `$column` IS NOT NULL AND `$column` > 0");
            return $stmt->fetch()['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur getCarrierRatesCount pour $carrier: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Génère des tendances simulées
     */
    public function getTrends(): array {
        return [
            'carriers' => ['value' => 0, 'type' => 'neutral'],
            'departments' => ['value' => rand(0, 2), 'type' => 'positive'],
            'options' => ['value' => rand(-1, 3), 'type' => rand(0, 1) ? 'positive' : 'neutral'],
            'calculations' => ['value' => rand(-10, 25), 'type' => rand(0, 1) ? 'positive' : 'negative']
        ];
    }
    
    /**
     * Formate les changements avec icônes
     */
    public function formatChange(int $value, string $type = 'neutral'): array {
        if ($value > 0) {
            return ['text' => "+{$value}", 'class' => 'positive', 'icon' => '📈'];
        } elseif ($value < 0) {
            return ['text' => "{$value}", 'class' => 'negative', 'icon' => '📉'];
        } else {
            return ['text' => "→", 'class' => 'neutral', 'icon' => '📊'];
        }
    }
    
    /**
     * Vide le cache (utile pour les tests)
     */
    public function clearCache(): void {
        $this->cache = [];
    }
}

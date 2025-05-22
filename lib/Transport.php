<?php
// lib/Transport.php

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

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Calcule les tarifs pour tous les transporteurs
     */
    public function calculateAll(string $type, string $adr, float $poids, string $option_sup): array
    {
        $results = [];
        $this->debug = [];

        foreach ($this->tables as $carrier => $table) {
            try {
                $results[$carrier] = $this->calculateForCarrier($carrier, $type, $adr, $poids, $option_sup);
            } catch (Exception $e) {
                $results[$carrier] = null;
                $this->debug[$carrier] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Calcule le tarif pour un transporteur spécifique
     */
    private function calculateForCarrier(string $carrier, string $type, string $adr, float $poids, string $option_sup): ?float
    {
        // 1. Récupérer le tarif de base selon le poids
        $tarif_base = $this->getBaseTariff($carrier, $poids);
        
        if ($tarif_base === null) {
            $this->debug[$carrier] = ['error' => 'Aucun tarif trouvé pour ce poids'];
            return null;
        }

        $this->debug[$carrier] = [
            'tarif_base' => $tarif_base,
            'poids' => $poids,
            'type' => $type,
            'adr' => $adr,
            'option_sup' => $option_sup
        ];

        // 2. Appliquer les majorations selon le type d'envoi
        if ($type === 'palette') {
            $majoration = $this->getMajoration($carrier, 'majoration_palette');
            if ($majoration) {
                $tarif_base *= (1 + $majoration / 100);
                $this->debug[$carrier]['majoration_palette'] = $majoration . '%';
            }
        }

        // 3. Appliquer la majoration ADR si nécessaire
        if ($adr === 'oui') {
            $majoration_adr = $this->getMajoration($carrier, 'majoration_adr');
            if ($majoration_adr !== null) {
                $tarif_base *= (1 + $majoration_adr / 100);
                $this->debug[$carrier]['majoration_adr'] = $majoration_adr . '%';
            }
        }

        // 4. Ajouter l'option supplémentaire
        $option_cost = $this->getOptionCost($carrier, $option_sup);
        if ($option_cost > 0) {
            $tarif_base += $option_cost;
            $this->debug[$carrier]['option_' . $option_sup] = $option_cost . '€';
        }

        // 5. Appliquer la surtaxe carburant (surcharge_gasoil)
        $surcharge = $this->getMajoration($carrier, 'surcharge_gasoil');
        if ($surcharge > 0) {
            $tarif_base += $surcharge;
            $this->debug[$carrier]['surcharge_gasoil'] = $surcharge . '€';
        }

        // 6. Appliquer les autres taxes éventuelles
        $autres_taxes = $this->getAutresTaxes($carrier);
        $tarif_base += $autres_taxes;
        if ($autres_taxes > 0) {
            $this->debug[$carrier]['autres_taxes'] = $autres_taxes . '€';
        }

        $this->debug[$carrier]['tarif_final'] = $tarif_base;
        
        return round($tarif_base, 2);
    }

    /**
     * Récupère le tarif de base selon le poids
     */
    private function getBaseTariff(string $carrier, float $poids): ?float
    {
        $table = $this->tables[$carrier];
        
        // Déterminer la colonne de tarif selon le poids
        $column = $this->getTariffColumn($poids);
        
        if (!$column) {
            return null;
        }

        // Pour l'instant, on prend le premier tarif disponible
        // Dans une version plus avancée, on pourrait filtrer par département
        $sql = "SELECT `$column` FROM `$table` WHERE `$column` IS NOT NULL AND `$column` > 0 LIMIT 1";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (float)$result[$column] : null;
    }

    /**
     * Détermine la colonne de tarif selon le poids
     */
    private function getTariffColumn(float $poids): ?string
    {
        if ($poids <= 9.99) return 'tarif_0_9';
        if ($poids <= 19.99) return 'tarif_10_19';
        if ($poids <= 29.99) return 'tarif_20_29';
        if ($poids <= 39.99) return 'tarif_30_39';
        if ($poids <= 49.99) return 'tarif_40_49';
        if ($poids <= 59.99) return 'tarif_50_59';
        if ($poids <= 69.99) return 'tarif_60_69';
        if ($poids <= 79.99) return 'tarif_70_79';
        if ($poids <= 89.99) return 'tarif_80_89';
        if ($poids <= 99.99) return 'tarif_90_99';
        if ($poids <= 299.99) return 'tarif_100_299';
        if ($poids <= 499.99) return 'tarif_300_499';
        if ($poids <= 999.99) return 'tarif_500_999';
        if ($poids <= 1999.99) return 'tarif_1000_1999';
        if ($poids <= 2999.99) return 'tarif_2000_2999';
        
        return null; // Poids hors limites
    }

    /**
     * Récupère le montant d'une option supplémentaire
     */
    private function getOptionCost(string $carrier, string $option_code): float
    {
        if ($option_code === 'standard') {
            return 0;
        }

        $sql = "SELECT montant FROM gul_options_supplementaires 
                WHERE transporteur = :carrier AND code_option = :option AND actif = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':carrier' => $carrier,
            ':option' => $option_code
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (float)$result['montant'] : 0;
    }

    /**
     * Récupère une majoration/taxe spécifique
     */
    private function getMajoration(string $carrier, string $type): ?float
    {
        $sql = "SELECT $type FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrier]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result[$type] !== null ? (float)$result[$type] : null;
    }

    /**
     * Calcule les autres taxes (sureté, contribution sanitaire, etc.)
     */
    private function getAutresTaxes(string $carrier): float
    {
        $sql = "SELECT surete, contribution_sanitaire, participation_transition_energetique 
                FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrier]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return 0;
        }

        $total = 0;
        $total += $result['surete'] ?? 0;
        $total += $result['contribution_sanitaire'] ?? 0;
        $total += $result['participation_transition_energetique'] ?? 0;
        
        return (float)$total;
    }

    /** Retourne la liste des codes de transporteurs */
    public function getCarriers(): array
    {
        return array_keys($this->tables);
    }

    /**
     * Récupère tous les tarifs pour un transporteur donné
     * @param string $carrier  ex. 'xpo'
     * @return array<mixed>
     */
    public function getRates(string $carrier): array
    {
        if (! isset($this->tables[$carrier])) {
            throw new InvalidArgumentException("Transporteur inconnu : $carrier");
        }
        $table = $this->tables[$carrier];
        $stmt = $this->db->query("SELECT * FROM `$table` ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée ou met à jour un tarif selon l'existence de l'ID.
     * On suppose que toutes les tables ont au moins : id, zone, cost
     */
    public function saveRate(string $carrier, array $data): void
    {
        $table = $this->tables[$carrier];
        if (! empty($data['id'])) {
            // UPDATE
            $stmt = $this->db->prepare(
                "UPDATE `$table` SET zone = :zone, cost = :cost WHERE id = :id"
            );
            $stmt->execute([
                ':zone' => $data['zone'], 
                ':cost' => $data['cost'], 
                ':id'   => $data['id']
            ]);
        } else {
            // INSERT
            $stmt = $this->db->prepare(
                "INSERT INTO `$table` (zone, cost) VALUES (:zone, :cost)"
            );
            $stmt->execute([
                ':zone' => $data['zone'],
                ':cost' => $data['cost'],
            ]);
        }
    }

    /** Supprime un tarif */
    public function deleteRate(string $carrier, int $id): void
    {
        $table = $this->tables[$carrier];
        $stmt = $this->db->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
    }
}

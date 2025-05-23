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
    public function calculateAll(string $type, string $adr, float $poids, string $option_sup, string $departement = null, int $palettes = 0, bool $enlevement = false): array
    {
        $results = [];
        $this->debug = [];

        foreach ($this->tables as $carrier => $table) {
            try {
                $results[$carrier] = $this->calculateForCarrier($carrier, $type, $adr, $poids, $option_sup, $departement, $palettes, $enlevement);
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
    private function calculateForCarrier(string $carrier, string $type, string $adr, float $poids, string $option_sup, string $departement = null, int $palettes = 0, bool $enlevement = false): ?float
    {
        // 1. Récupérer le tarif de base selon le poids et le département
        $tarif_base = $this->getBaseTariff($carrier, $poids, $departement);
        
        if ($tarif_base === null) {
            $this->debug[$carrier] = ['error' => 'Aucun tarif trouvé pour ce poids/département'];
            return null;
        }

        $this->debug[$carrier] = [
            'tarif_base' => number_format($tarif_base, 2),
            'poids' => $poids . ' kg',
            'type' => $type,
            'adr' => $adr,
            'option_sup' => $option_sup,
            'departement' => $departement,
            'palettes' => $palettes,
            'enlevement' => $enlevement ? 'oui' : 'non'
        ];

        // Pour poids > 100kg, calcul au kg selon le transporteur
        if ($poids > 100) {
            // Le tarif dans la base est pour 100kg, on multiplie par le nombre de centaines
            $multiplicateur = $poids / 100;
            $tarif_base = $tarif_base * $multiplicateur;
            $this->debug[$carrier]['calcul_au_poids'] = 'Tarif au 100kg × ' . number_format($multiplicateur, 2) . ' = ' . number_format($tarif_base, 2) . '€';
        }

        // 2. Appliquer les majorations selon le type d'envoi (palette)
        if ($type === 'palette') {
            $majoration = $this->getMajoration($carrier, 'majoration_palette');
            if ($majoration > 0) {
                $montant_majoration = $tarif_base * ($majoration / 100);
                $tarif_base += $montant_majoration;
                $this->debug[$carrier]['majoration_palette'] = '+' . $majoration . '% = ' . number_format($montant_majoration, 2) . '€';
            }
        }

        // 3. Appliquer la majoration ADR si nécessaire
        if ($adr === 'oui') {
            $adr_info = $this->getADRInfo($carrier);
            
            // Pour Heppner : pas d'incidence sur le tarif
            if ($carrier === 'heppner') {
                $this->debug[$carrier]['adr'] = 'ADR sans incidence tarifaire';
            } 
            // Pour XPO et K+N : +20% si ADR
            elseif ($adr_info === '+20% si ADR') {
                $montant_adr = $tarif_base * 0.20;
                $tarif_base += $montant_adr;
                $this->debug[$carrier]['majoration_adr'] = '+20% = ' . number_format($montant_adr, 2) . '€';
            }
        }

        // 4. Ajouter l'option supplémentaire
        $option_cost = $this->getOptionCost($carrier, $option_sup);
        if ($option_cost > 0) {
            $tarif_base += $option_cost;
            $this->debug[$carrier]['option_' . $option_sup] = '+' . number_format($option_cost, 2) . '€';
        }

        // 5. Ajouter le coût de l'enlèvement si applicable
        if ($enlevement) {
            $cout_enlevement = $this->getEnlevementCost($carrier);
            if ($cout_enlevement > 0) {
                $tarif_base += $cout_enlevement;
                $this->debug[$carrier]['enlevement'] = '+' . number_format($cout_enlevement, 2) . '€';
            }
        }

        // 6. Ajouter le coût des palettes si applicable
        if ($palettes > 0 && $type === 'palette') {
            $cout_palettes = $this->getPaletteCost($carrier, $palettes);
            if ($cout_palettes > 0) {
                $tarif_base += $cout_palettes;
                $this->debug[$carrier]['cout_palettes'] = $palettes . ' palette(s) = +' . number_format($cout_palettes, 2) . '€';
            }
        }

        // 7. Appliquer la surtaxe carburant (pourcentage sur le montant actuel)
        $surcharge = $this->getSurchargeGasoil($carrier, $tarif_base);
        if ($surcharge > 0) {
            $tarif_base += $surcharge;
            $this->debug[$carrier]['surcharge_gasoil'] = '+' . number_format($surcharge, 2) . '€';
        }

        // 8. Appliquer les autres taxes fixes
        $autres_taxes = $this->getAutresTaxes($carrier);
        if ($autres_taxes > 0) {
            $tarif_base += $autres_taxes;
            $this->debug[$carrier]['autres_taxes'] = '+' . number_format($autres_taxes, 2) . '€';
        }

        $this->debug[$carrier]['tarif_final'] = number_format($tarif_base, 2) . '€';
        
        return round($tarif_base, 2);
    }

    /**
     * Récupère le tarif de base selon le poids et le département
     */
    private function getBaseTariff(string $carrier, float $poids, string $departement = null): ?float
    {
        $table = $this->tables[$carrier];
        
        // Déterminer la colonne de tarif selon le poids et le transporteur
        if ($carrier === 'xpo') {
            // XPO utilise des tranches plus larges
            $column = $this->getXPOTariffColumn($poids);
        } else {
            // Heppner et KN utilisent des tranches détaillées
            $column = $this->getTariffColumn($poids);
        }
        
        if (!$column) {
            return null;
        }

        // Récupérer le tarif pour le département spécifique
        if ($departement) {
            $sql = "SELECT `$column` FROM `$table` WHERE num_departement = :dep AND `$column` IS NOT NULL AND `$column` > 0 LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':dep' => $departement]);
        } else {
            // Fallback : prendre le premier tarif disponible
            $sql = "SELECT `$column` FROM `$table` WHERE `$column` IS NOT NULL AND `$column` > 0 LIMIT 1";
            $stmt = $this->db->query($sql);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (float)$result[$column] : null;
    }

    /**
     * Détermine la colonne de tarif pour XPO selon le poids
     */
    private function getXPOTariffColumn(float $poids): ?string
    {
        if ($poids <= 99.99) return 'tarif_0_99';
        if ($poids <= 499.99) return 'tarif_100_499';
        if ($poids <= 999.99) return 'tarif_500_999';
        if ($poids <= 1999.99) return 'tarif_1000_1999';
        if ($poids <= 2999.99) return 'tarif_2000_2999';
        
        return null; // Poids hors limites
    }

    /**
     * Détermine la colonne de tarif selon le poids (Heppner et KN)
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
     * Récupère le coût de l'enlèvement
     */
    private function getEnlevementCost(string $carrier): float
    {
        return $this->getOptionCost($carrier, 'enlevement');
    }

    /**
     * Récupère le coût des palettes EUR
     */
    private function getPaletteCost(string $carrier, int $nbPalettes): float
    {
        // Coût fixe par palette selon le transporteur
        $coutParPalette = [
            'xpo' => 8.00,      // À adapter selon vos tarifs réels
            'heppner' => 7.50,  // À adapter selon vos tarifs réels
            'kn' => 9.00        // À adapter selon vos tarifs réels
        ];
        
        return ($coutParPalette[$carrier] ?? 8.00) * $nbPalettes;
    }

    /**
     * Récupère une majoration/taxe spécifique
     */
    private function getMajoration(string $carrier, string $type): ?float
    {
        $carrierName = $this->taxNames[$carrier] ?? $carrier;
        $sql = "SELECT $type FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrierName]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result[$type] !== null ? (float)$result[$type] : null;
    }

    /**
     * Récupère l'information ADR pour un transporteur
     */
    private function getADRInfo(string $carrier): ?string
    {
        $carrierName = $this->taxNames[$carrier] ?? $carrier;
        $sql = "SELECT majoration_adr FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrierName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['majoration_adr'] : null;
    }

    /**
     * Calcule la surcharge gasoil (pourcentage sur le montant)
     */
    private function getSurchargeGasoil(string $carrier, float $montant_base): float
    {
        $carrierName = $this->taxNames[$carrier] ?? $carrier;
        $sql = "SELECT surcharge_gasoil FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrierName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['surcharge_gasoil'] > 0) {
            // La surcharge gasoil est un pourcentage appliqué sur le montant de base
            return $montant_base * (float)$result['surcharge_gasoil'];
        }
        
        return 0;
    }

    /**
     * Calcule les autres taxes fixes (sûreté, contribution sanitaire, etc.)
     */
    private function getAutresTaxes(string $carrier): float
    {
        $carrierName = $this->taxNames[$carrier] ?? $carrier;
        $sql = "SELECT surete, contribution_sanitaire, participation_transition_energetique 
                FROM gul_taxes_transporteurs WHERE transporteur = :carrier LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrierName]);
        
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
     */
    public function saveRate(string $carrier, array $data): void
    {
        $table = $this->tables[$carrier];
        if (! empty($data['id'])) {
            // UPDATE - à adapter selon structure réelle
            $stmt = $this->db->prepare(
                "UPDATE `$table` SET zone = :zone, cost = :cost WHERE id = :id"
            );
            $stmt->execute([
                ':zone' => $data['zone'], 
                ':cost' => $data['cost'], 
                ':id'   => $data['id']
            ]);
        } else {
            // INSERT - à adapter selon structure réelle
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

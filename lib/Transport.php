<?php
// lib/Transport.php

class Transport
{
    private PDO $db;
    private array $carriers = ['kn', 'heppner', 'xpo'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les lignes tarifaires du transporteur demandé.
     */
    public function fetchRatesForCarrier(string $carrier): array
    {
        $sql = "
            SELECT *
              FROM gul_taxes_transporteurs
             WHERE transporteur = :carrier
             ORDER BY type, adr, poids_max
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrier]);

        $rates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rates[$row['type']][$row['adr']][] = $row;
        }
        return $rates;
    }

    /**
     * Calcule le tarif total pour un transporteur donné.
     */
    public function calculate(
        string $carrier,
        string $type,
        string $adr,
        float  $weight,
        string $option = 'standard'
    ): float {
        $rates = $this->fetchRatesForCarrier($carrier);

        if (!isset($rates[$type][$adr])) {
            throw new InvalidArgumentException("Aucun barème pour $carrier / $type / $adr");
        }

        foreach ($rates[$type][$adr] as $row) {
            if ($weight <= $row['poids_max']) {
                $col = 'coefficient_' . $option;
                if (!isset($row[$col])) {
                    throw new InvalidArgumentException("Option inconnue ou non disponible : $option");
                }
                return $row['prix'] * (float)$row[$col];
            }
        }

        throw new OutOfRangeException("Poids $weight kg trop élevé pour $carrier");
    }

    /**
     * Calcule le tarif pour chaque transporteur (kn, heppner, xpo).
     */
    public function calculateAll(
        string $type,
        string $adr,
        float  $weight,
        string $option = 'standard'
    ): array {
        $results = [];
        foreach ($this->carriers as $carrier) {
            try {
                $results[$carrier] = $this->calculate($carrier, $type, $adr, $weight, $option);
            } catch (\Throwable $e) {
                $results[$carrier] = null;
            }
        }
        return $results;
    }

    /**
     * Renvoie la liste des options possibles (extraites dynamiquement d'une ligne au hasard)
     */
    public function getOptionsList(): array
    {
        $stmt = $this->db->query("SELECT * FROM gul_taxes_transporteurs LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $options = [];
        foreach ($row as $key => $val) {
            if (str_starts_with($key, 'coefficient_')) {
                $code = substr($key, strlen('coefficient_'));
                $options[$code] = ucfirst(str_replace('_', ' ', $code));
            }
        }
        return $options;
    }
}

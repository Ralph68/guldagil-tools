<?php
// lib/Transport.php

class Transport
{
    private PDO $db;
    private array $carriers = ['xpo', 'heppner', 'kn'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les tranches tarifaires d'un transporteur
     */
    public function fetchRatesForCarrier(string $carrier): array
    {
        $sql = "
            SELECT * FROM gul_taxes_transporteurs
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
     * Calcule le prix pour un transporteur donné avec les critères fournis
     */
    public function calculate(string $carrier, string $type, string $adr, float $weight, string $option): ?float
    {
        $rates = $this->fetchRatesForCarrier($carrier);
        if (!isset($rates[$type][$adr])) return null;

        foreach ($rates[$type][$adr] as $row) {
            if ($weight <= $row['poids_max']) {
                $col = 'coefficient_' . $option;
                if (!isset($row[$col])) return null;
                return round((float)$row['prix'] * (float)$row[$col], 2);
            }
        }

        return null;
    }

    /**
     * Calcule les prix pour tous les transporteurs disponibles
     */
    public function calculateAll(string $type, string $adr, float $weight, string $option): array
    {
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
     * Renvoie dynamiquement la liste des options disponibles (coefficient_*)
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

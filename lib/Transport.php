<?php
// lib/Transport.php

class Transport
{
    private PDO   $db;
    private array $options;
    // liste des codes disponibles
    private array $carriers = ['xpo', 'heppner', 'kn'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->loadOptions();
    }

    private function loadOptions(): void
    {
        $stmt = $this->db->query("SELECT code, coefficient FROM options");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->options[$row['code']] = (float)$row['coefficient'];
        }
    }

    public function getRates(string $carrier): array
    {
        $stmt = $this->db->prepare("
            SELECT type, adr, poids_max AS max, prix
            FROM tarif_transport
            WHERE transporteur = :tr
            ORDER BY type, adr, poids_max
        ");
        $stmt->execute([':tr' => $carrier]);

        $rates = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rates[$r['type']][$r['adr']][] = [
                'max'   => (float)$r['max'],
                'price' => (float)$r['prix'],
            ];
        }
        return $rates;
    }

    public function calculate(
        string $carrier,
        string $type,
        string $adr,
        float  $weight,
        string $opt
    ): float {
        if (!isset($this->options[$opt])) {
            throw new InvalidArgumentException("Option inconnue « $opt »");
        }
        $rates = $this->getRates($carrier);
        if (!isset($rates[$type][$adr])) {
            throw new InvalidArgumentException("Type ou ADR invalide pour $carrier");
        }
        foreach ($rates[$type][$adr] as $br) {
            if ($weight <= $br['max']) {
                return $br['price'] * $this->options[$opt];
            }
        }
        throw new OutOfRangeException("$carrier – poids trop élevé pour barème");
    }

    /**
     * Boucle sur tous les transporteurs et renvoie un tableau
     * carrier => prix ou null si erreur (pas de tarif).
     */
    public function calculateAll(
        string $type,
        string $adr,
        float  $weight,
        string $opt
    ): array {
        $results = [];
        foreach ($this->carriers as $c) {
            try {
                $results[$c] = $this->calculate($c, $type, $adr, $weight, $opt);
            } catch (\Throwable $e) {
                $results[$c] = null; // ou on skippe
            }
        }
        return $results;
    }

    public function getOptionsList(): array
    {
        return $this->options;
    }
}

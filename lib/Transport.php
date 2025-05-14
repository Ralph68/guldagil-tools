<?php
// lib/Transport.php

class Transport
{
    private PDO   $db;
    private array $options;    // [ 'standard' => 1.0, … ]
    private array $carriers = ['kn', 'heppner', 'xpo'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->loadOptionCoefficients();
    }

    /**
     * Charge les coefficients d'options depuis la table gul_options
     * Structure attendue de gul_options : code VARCHAR, coefficient FLOAT
     */
    private function loadOptionCoefficients(): void
    {
        $sql  = "SELECT code, coefficient FROM gul_options";
        $stmt = $this->db->query($sql);
        $this->options = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->options[$row['code']] = (float)$row['coefficient'];
        }
    }

    /**
     * Récupère toutes les tranches pour un transporteur donné
     * en unionnant les 4 tables de tarifs.
     * @return array Structure: [ 'colis' => ['oui' => [['max'=>..,'price'=>..], ...], 'non'=>[...] ], 'palette' => ... ]
     */
    public function fetchRatesForCarrier(string $carrier): array
    {
        // Union de toutes les tables : XPO, Heppner, KN et taxes_transporteurs
        $sql = <<<SQL
SELECT type, adr, poids_max AS max, prix
  FROM gul_xpo_rates
 WHERE transporteur = :tr
UNION ALL
SELECT type, adr, poids_max AS max, prix
  FROM gul_heppner_rates
 WHERE transporteur = :tr
UNION ALL
SELECT type, adr, poids_max AS max, prix
  FROM gul_kn_rates
 WHERE transporteur = :tr
UNION ALL
SELECT type, adr, poids_max AS max, prix
  FROM gul_taxes_transporteurs
 WHERE transporteur = :tr
ORDER BY type, adr, max
SQL;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tr' => $carrier]);

        $rates = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rates[$r['type']][$r['adr']][] = [
                'max'   => (float)$r['max'],
                'price' => (float)$r['prix']
            ];
        }
        return $rates;
    }

    /**
     * Calcule le tarif pour 1 transporteur.
     * @throws InvalidArgumentException|OutOfRangeException
     */
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

        $rates = $this->fetchRatesForCarrier($carrier);
        if (!isset($rates[$type][$adr])) {
            throw new InvalidArgumentException("Aucun barème pour $carrier / $type / $adr");
        }

        foreach ($rates[$type][$adr] as $br) {
            if ($weight <= $br['max']) {
                return $br['price'] * $this->options[$opt];
            }
        }

        throw new OutOfRangeException("Poids {$weight} kg trop élevé pour $carrier");
    }

    /**
     * Calcule pour tous les transporteurs et retourne
     * [ 'kn'=>prix|null, 'heppner'=>…, 'xpo'=>… ]
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
                $results[$c] = null;
            }
        }
        return $results;
    }

    /**
     * Renvoie la liste des options disponibles (code => coefficient)
     */
    public function getOptionsList(): array
    {
        return $this->options;
    }
}

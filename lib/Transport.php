<?php
class Transport
{
    private PDO $db;
    private array $carriers = ['xpo', 'heppner', 'kn'];
    public array $debug = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function fetchRatesForCarrier(string $carrier): array
    {
        $sql = "SELECT * FROM gul_taxes_transporteurs WHERE transporteur = :carrier ORDER BY type, adr, poids_max";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrier]);

        $rates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rates[$row['type']][$row['adr']][] = $row;
        }
        return $rates;
    }

    public function getExtraCharges(string $carrier): array
    {
        $sql = "SELECT * FROM gul_options_supplementaires WHERE transporteur = :carrier AND actif = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':carrier' => $carrier]);

        $charges = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $charges[$row['code_option']] = [
                'montant' => (float)$row['montant'],
                'unite' => $row['unite']
            ];
        }
        return $charges;
    }

    public function calculate(string $carrier, string $type, string $adr, float $weight, string $option_sup, bool $enlevement = false, int $palettes = 0): ?float
    {
        $this->debug[$carrier] = [
            'params' => compact('type', 'adr', 'weight', 'option_sup', 'enlevement', 'palettes'),
            'matched' => null,
            'base' => null,
            'extras' => [],
            'result' => null
        ];

        $rates = $this->fetchRatesForCarrier($carrier);
        if (!isset($rates[$type][$adr])) return null;

        foreach ($rates[$type][$adr] as $row) {
            if ($weight <= $row['poids_max']) {
                $base = (float)$row['prix'];
                $this->debug[$carrier]['matched'] = $row;
                $this->debug[$carrier]['base'] = $base;
                $total = $base;

                if (!$enlevement) {
                    $extras = $this->getExtraCharges($carrier);

                    if (isset($extras[$option_sup])) {
                        $total += $extras[$option_sup]['montant'];
                        $this->debug[$carrier]['extras'][$option_sup] = $extras[$option_sup]['montant'];
                    }

                    if ($palettes > 0 && isset($extras['palette']) && $extras['palette']['unite'] === 'palette') {
                        $total += $palettes * $extras['palette']['montant'];
                        $this->debug[$carrier]['extras']['palette'] = $palettes . ' x ' . $extras['palette']['montant'];
                    }
                } else {
                    $this->debug[$carrier]['extras']['enlevement'] = 'remplace toutes options';
                }

                $this->debug[$carrier]['result'] = round($total, 2);
                return $this->debug[$carrier]['result'];
            }
        }
        return null;
    }

    public function calculateAll(string $type, string $adr, float $weight, string $option_sup, bool $enlevement = false, int $palettes = 0): array
    {
        $results = [];
        foreach ($this->carriers as $carrier) {
            try {
                $results[$carrier] = $this->calculate($carrier, $type, $adr, $weight, $option_sup, $enlevement, $palettes);
            } catch (\Throwable $e) {
                $results[$carrier] = null;
                $this->debug[$carrier]['error'] = $e->getMessage();
            }
        }
        return $results;
    }
}

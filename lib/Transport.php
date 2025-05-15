<?php
// lib/Transport.php

declare(strict_types=1);

class Transport
{
    private PDO $db;

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

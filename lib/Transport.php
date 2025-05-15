<?php
// lib/Transport.php

declare(strict_types=1);

class Transport
{
    private PDO $db;

    /**
     * Constructeur : on injecte la connexion PDO depuis config.php
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère tous les transporteurs depuis la base.
     * @return array<array{id:int, code:string, name:string, zone:string}>
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT id, code, name, zone FROM transporteurs ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un transporteur par son ID.
     * @param  int $id
     * @return array{id:int, code:string, name:string, zone:string}|null
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, code, name, zone FROM transporteurs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Crée un nouveau transporteur.
     * @param array{code:string,name:string,zone:string} $data
     * @return int ID inséré
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transporteurs (code, name, zone) VALUES (:code, :name, :zone)'
        );
        $stmt->execute([
            ':code' => $data['code'],
            ':name' => $data['name'],
            ':zone' => $data['zone'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Met à jour un transporteur existant.
     * @param  int $id
     * @param  array{code:string,name:string,zone:string} $data
     * @return void
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE transporteurs 
             SET code = :code, name = :name, zone = :zone
             WHERE id = :id'
        );
        $stmt->execute([
            ':code' => $data['code'],
            ':name' => $data['name'],
            ':zone' => $data['zone'],
            ':id'   => $id,
        ]);
    }

    /**
     * Supprime un transporteur.
     * @param  int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM transporteurs WHERE id = ?');
        $stmt->execute([$id]);
    }
}

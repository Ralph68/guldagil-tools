<?php
// lib/Transport.php

declare(strict_types=1);

class Transport
{
    private PDO $db;

    /**
     * Liste des codes de transporteurs disponibles.
     * Vous pouvez ajuster ce tableau selon vos besoins.
     * Ex. ['xpo', 'heppner', 'kn']
     *
     * @var string[]
     */
    private array $carriers = [
        'xpo',
        'heppner',
        'kn',
    ];

    /**
     * Constructeur : on injecte la connexion PDO depuis config.php
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Retourne le tableau des codes de transporteurs.
     *
     * @return string[]
     */
    public function getCarriers(): array
    {
        return $this->carriers;
    }

    /**
     * Exemple de méthode pour récupérer en base les transporteurs
     * (si vous avez une table dédiée).
     * 
     * @return array<mixed>
     */
    public function fetchAllFromDatabase(): array
    {
        $stmt = $this->db->query('SELECT id, code, name, zone FROM transporteurs');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Vous pouvez ajouter ici vos méthodes create, update, delete, etc.
}

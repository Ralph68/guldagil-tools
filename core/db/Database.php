<?php
/**
 * Titre: Gestionnaire centralisé des accès base de données
 * Chemin: /core/db/Database.php
 * Version: 0.5 beta + build auto
 */

class Database 
{
    private static $instance = null;
    private $connection;
    private $config;
    
    /**
     * Singleton pattern pour garantir une seule connexion
     */
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Chargement de la configuration existante
     */
    private function loadConfig() {
        // Utiliser la configuration existante
        $this->config = [
            'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
            'name' => defined('DB_NAME') ? DB_NAME : '',
            'user' => defined('DB_USER') ? DB_USER : '',
            'pass' => defined('DB_PASS') ? DB_PASS : '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ];
    }
    
    /**
     * Connexion à la base de données
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['name']};charset={$this->config['charset']}";
            $this->connection = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['pass'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            error_log("Erreur connexion DB: " . $e->getMessage());
            throw new Exception("Impossible de se connecter à la base de données");
        }
    }
    
    /**
     * Obtenir la connexion PDO (compatible avec le code existant)
     */
    public function getConnection(): PDO {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Méthode factory pour maintenir la compatibilité avec getDB()
     */
    public static function getDB(): PDO {
        return self::getInstance()->getConnection();
    }
    
    /**
     * Exécution de requêtes préparées sécurisées
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur requête SQL: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Erreur lors de l'exécution de la requête");
        }
    }
    
    /**
     * Récupération d'un seul enregistrement
     */
    public function fetch(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Récupération de plusieurs enregistrements
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insertion avec retour de l'ID
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Mise à jour d'enregistrements
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $this->query($sql, array_merge($data, $whereParams));
        
        return $stmt->rowCount();
    }
    
    /**
     * Suppression d'enregistrements
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Démarrage d'une transaction
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Validation d'une transaction
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Annulation d'une transaction
     */
    public function rollback(): bool {
        return $this->connection->rollback();
    }
    
    /**
     * Vérification de l'état de la connexion
     */
    public function isConnected(): bool {
        try {
            return $this->connection && $this->connection->query('SELECT 1') !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Statistiques de la base de données
     */
    public function getStats(): array {
        try {
            $stats = [];
            
            // Tables principales
            $tables = ['auth_users', 'auth_sessions', 'gul_xpo_rates', 'gul_heppner_rates'];
            foreach ($tables as $table) {
                $result = $this->fetch("SELECT COUNT(*) as count FROM {$table}");
                $stats[$table] = $result['count'] ?? 0;
            }
            
            // Taille de la base
            $dbSize = $this->fetch("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            $stats['database_size_mb'] = $dbSize['size_mb'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Nettoyage des sessions expirées (utilitaire)
     */
    public function cleanExpiredSessions(): int {
        $sql = "DELETE FROM auth_sessions WHERE expires_at < NOW()";
        $stmt = $this->query($sql);
        return $stmt->rowCount();
    }
    
    /**
     * Fermeture de la connexion
     */
    public function close(): void {
        $this->connection = null;
    }
    
    /**
     * Empêcher le clonage
     */
    private function __clone() {}
    
    /**
     * Empêcher la désérialisation
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
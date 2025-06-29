<?php
/**
 * Extension Authentification - Gul Calc Frais de port  
 * Chemin : /config/auth_database.php
 * Version : 0.5 beta
 */

require_once __DIR__ . '/database.php';

class AuthDatabase {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = getDB(); // Utilise la connexion existante
        $this->initAuthTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initAuthTables() {
        try {
            $this->createAuthTables();
            $this->insertDefaultUsers();
        } catch (PDOException $e) {
            error_log("Erreur auth tables : " . $e->getMessage());
            throw new Exception("Impossible d'initialiser les tables auth");
        }
    }
    
    private function createAuthTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS auth_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('dev', 'admin', 'user') NOT NULL DEFAULT 'user',
            session_duration INT DEFAULT 7200,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE
        );
        
        CREATE TABLE IF NOT EXISTS auth_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES auth_users(id) ON DELETE CASCADE
        );
        ";
        
        $this->db->exec($sql);
    }
    
    private function insertDefaultUsers() {
        // Vérifier si les utilisateurs existent déjà
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM auth_users");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $users = [
                [
                    'username' => 'dev',
                    'password' => password_hash('dev123', PASSWORD_DEFAULT),
                    'role' => 'dev',
                    'session_duration' => 0 // illimitée
                ],
                [
                    'username' => 'admin',
                    'password' => password_hash('admin123', PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'session_duration' => 28800 // 8h
                ],
                [
                    'username' => 'user',
                    'password' => password_hash('user123', PASSWORD_DEFAULT),
                    'role' => 'user',
                    'session_duration' => 7200 // 2h
                ]
            ];
            
            $stmt = $this->db->prepare("
                INSERT INTO auth_users (username, password, role, session_duration) 
                VALUES (:username, :password, :role, :session_duration)
            ");
            
            foreach ($users as $user) {
                $stmt->execute($user);
            }
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
}

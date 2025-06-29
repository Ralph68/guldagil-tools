<?php
/**
 * Initialisation Authentification - Gul Calc Frais de port
 * Chemin : /includes/auth_init.php
 * Version : 0.5 beta
 */

// Inclure la config existante
require_once __DIR__ . '/../config/database.php';

try {
    // Créer les tables auth dans la base existante
    $db = getDB();
    
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
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES auth_users(id) ON DELETE CASCADE
    );
    ";
    
    $db->exec($sql);
    
    // Insérer utilisateurs de dev si pas déjà fait
    $stmt = $db->prepare("SELECT COUNT(*) FROM auth_users");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $users = [
            ['dev', password_hash('dev123', PASSWORD_DEFAULT), 'dev', 0],
            ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 28800],
            ['user', password_hash('user123', PASSWORD_DEFAULT), 'user', 7200]
        ];
        
        $stmt = $db->prepare("INSERT INTO auth_users (username, password, role, session_duration) VALUES (?, ?, ?, ?)");
        
        foreach ($users as $user) {
            $stmt->execute($user);
        }
        
        echo "✅ Tables auth créées et utilisateurs initialisés\n";
    } else {
        echo "ℹ️  Tables auth déjà existantes\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

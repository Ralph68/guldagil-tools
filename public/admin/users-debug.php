<?php
/**
 * Version debug minimaliste de users.php
 * Pour identifier l'erreur 500
 */

// Activation debug FORCÉ
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== DÉBUT DEBUG USERS.PHP ===<br>";

try {
    echo "✅ Début exécution PHP<br>";
    
    // Test 1: ROOT_PATH
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', dirname(dirname(__DIR__)));
    }
    echo "✅ ROOT_PATH: " . ROOT_PATH . "<br>";
    
    // Test 2: Config principal
    if (file_exists(ROOT_PATH . '/config/config.php')) {
        echo "✅ config.php trouvé<br>";
        require_once ROOT_PATH . '/config/config.php';
        echo "✅ config.php chargé<br>";
    } else {
        echo "❌ config.php manquant<br>";
        exit;
    }
    
    // Test 3: Variables essentielles
    echo "✅ Variables définies:<br>";
    echo "- DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NON DÉFINI') . "<br>";
    echo "- DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NON DÉFINI') . "<br>";
    echo "- DB_USER: " . (defined('DB_USER') ? DB_USER : 'NON DÉFINI') . "<br>";
    
    // Test 4: Connexion BDD
    if (isset($db) && $db instanceof PDO) {
        echo "✅ Connexion \$db disponible<br>";
        $pdo = $db;
    } else {
        echo "⚠️ Connexion \$db non disponible, tentative manuelle<br>";
        
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            try {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                echo "✅ Connexion PDO manuelle réussie<br>";
            } catch (PDOException $e) {
                echo "❌ Erreur connexion PDO: " . $e->getMessage() . "<br>";
                exit;
            }
        } else {
            echo "❌ Constantes DB manquantes<br>";
            exit;
        }
    }
    
    // Test 5: Test requête simple
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM auth_users");
        $result = $stmt->fetch();
        echo "✅ Test BDD réussi: " . $result['count'] . " utilisateurs<br>";
    } catch (PDOException $e) {
        echo "❌ Erreur requête: " . $e->getMessage() . "<br>";
        exit;
    }
    
    // Test 6: Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session démarrée<br>";
    } else {
        echo "✅ Session déjà active<br>";
    }
    
    // Test 7: Variables template simples
    $page_title = 'Debug Users';
    $current_module = 'admin';
    $app_name = 'Portail Debug';
    $version = '0.5';
    $build_number = '001';
    echo "✅ Variables template définies<br>";
    
    echo "<hr>";
    echo "<h2>🎯 Tests réussis - Affichage simple</h2>";
    
    // Récupération utilisateurs
    $stmt = $pdo->query("SELECT id, username, role, is_active FROM auth_users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Utilisateurs (DEBUG)</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Actif</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . ($user['is_active'] ? 'Oui' : 'Non') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<p><strong>✅ DEBUG TERMINÉ AVEC SUCCÈS</strong></p>";
    echo "<p>Si vous voyez ce message, le problème est dans la version complexe.</p>";
    echo "<p><a href='/admin/'>Retour admin</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 20px; border: 2px solid red;'>";
    echo "<h3>❌ ERREUR DÉTECTÉE</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Ligne:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
} catch (Error $e) {
    echo "<div style='background: #ffebee; padding: 20px; border: 2px solid red;'>";
    echo "<h3>❌ ERREUR FATALE PHP</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Ligne:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<br>=== FIN DEBUG ===";
?>

<?php
/**
 * Debug des mots de passe - À SUPPRIMER après test
 */

require_once 'config/config.php';

// Test des hash de votre BDD
$passwords_from_db = [
    'dev' => '$2y$10$wOPbnLlanN8wbvXLvPK3PeLxi4h8J6.3agtLdCXzJz/qptdbSa73a',
    'admin' => '$2y$10$GZvPyUYIGHcy9RPb2D46peATanUGw7jyQ6fJ4LX5bIfDNR1CR993u',
    'user' => '$2y$10$n.Fg.Ov6Oq2vS6HMVwv9vOH3NXSeHHaaiMybc9jRqI.cb0JouZHW.'
];

echo "<h2>Test des mots de passe</h2>";

// Tester différents mots de passe
$test_passwords = ['dev123', 'admin123', 'user123', 'dev', 'admin', 'user'];

foreach ($passwords_from_db as $username => $hash) {
    echo "<h3>Utilisateur: $username</h3>";
    echo "Hash BDD: $hash<br>";
    
    foreach ($test_passwords as $test_pw) {
        $result = password_verify($test_pw, $hash);
        echo "Test '$test_pw': " . ($result ? '✅ MATCH' : '❌ NO') . "<br>";
    }
    echo "<hr>";
}

// Test direct BDD
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT username, password FROM auth_users WHERE username = 'dev'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h3>Test direct BDD utilisateur 'dev'</h3>";
        echo "Hash: " . $user['password'] . "<br>";
        echo "Test 'dev123': " . (password_verify('dev123', $user['password']) ? '✅' : '❌') . "<br>";
    }
} catch (Exception $e) {
    echo "Erreur BDD: " . $e->getMessage();
}
?>

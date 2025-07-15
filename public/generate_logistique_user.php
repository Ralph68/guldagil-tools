<?php
/**
 * Titre: Générateur de hash pour utilisateur Logistique
 * Chemin: /scripts/generate_logistique_user.php
 * Version: 0.5 beta + build auto
 */

// Configuration
$username = 'log';
$password = 'log2025';
$role = 'logistique';

// Génération du hash sécurisé
$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "=== CRÉATION UTILISATEUR LOGISTIQUE ===\n";
echo "Username: {$username}\n";
echo "Password: {$password}\n";
echo "Role: {$role}\n";
echo "Hash généré: {$password_hash}\n\n";

// Requête SQL prête à exécuter
$sql = "INSERT INTO `auth_users` (`username`, `password`, `role`, `session_duration`, `is_active`) 
VALUES (
    '{$username}', 
    '{$password_hash}', 
    '{$role}', 
    14400, 
    1
);";

echo "=== REQUÊTE SQL À EXÉCUTER ===\n";
echo $sql . "\n\n";

// Vérification du hash
if (password_verify($password, $password_hash)) {
    echo "✅ Vérification du hash: SUCCÈS\n";
} else {
    echo "❌ Vérification du hash: ÉCHEC\n";
}

echo "\n=== ÉTAPES D'INSTALLATION ===\n";
echo "1. Modifier la table auth_users pour ajouter le rôle 'logistique'\n";
echo "2. Exécuter la requête SQL ci-dessus\n";
echo "3. Remplacer le fichier /templates/header.php\n";
echo "4. Ajouter le CSS des rôles à /public/assets/css/\n";
echo "5. Mettre à jour /config/modules.php\n\n";

echo "=== ACCÈS PAR RÔLE ===\n";
echo "GUEST: Redirection vers /auth/login.php\n";
echo "USER: Accès au module 'port' uniquement (si statut 'active')\n";
echo "LOGISTIQUE: Accès à 'port' (beta) + 'adr' et 'qualité' visibles mais pas accessibles (dev)\n";
echo "ADMIN: Accès à tous modules sauf /dev (statuts 'active' et 'beta')\n";
echo "DEV: Accès complet sans restriction\n\n";

echo "=== TESTS À EFFECTUER ===\n";
echo "1. Se connecter avec log/log2025\n";
echo "2. Vérifier que seul le module 'port' est accessible\n";
echo "3. Vérifier que 'adr' et 'qualité' sont visibles mais grisés\n";
echo "4. Tester les autres rôles pour s'assurer de la non-régression\n";
?>

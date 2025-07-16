<?php
/**
 * Titre: Interface administration - Gestion des utilisateurs
 * Chemin: /admin/users.php
 * Version: 0.5 beta + build auto
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Chargement du système de rôles
require_once ROOT_PATH . '/config/roles.php';

// Vérification permissions admin
session_start();
$current_user = $_SESSION['user'] ?? null;
if (!$current_user || !hasAdminPermission($current_user['role'] ?? 'user', 'manage_users')) {
    header('Location: /auth/login.php');
    exit;
}

// Variables template
$page_title = 'Gestion des Utilisateurs';
$current_module = 'admin';

// Configuration base de données (à adapter)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=guldagil", "username", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
}

$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_user':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $password = $_POST['password'] ?? '';
            
            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'Tous les champs sont requis';
                break;
            }
            
            if (!RoleManager::isValidRole($role)) {
                $error = 'Rôle invalide';
                break;
            }
            
            // Vérifier si l'utilisateur peut créer ce rôle
            $manageableRoles = RoleManager::getManageableRoles($current_user['role']);
            if (!isset($manageableRoles[$role])) {
                $error = 'Vous ne pouvez pas créer un utilisateur avec ce rôle';
                break;
            }
            
            try {
                // Vérifier unicité
                $stmt = $pdo->prepare("SELECT id FROM auth_users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->rowCount() > 0) {
                    $error = 'Nom d\'utilisateur ou email déjà utilisé';
                    break;
                }
                
                // Créer utilisateur
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO auth_users (username, email, password, role, created_at, created_by) VALUES (?, ?, ?, ?, NOW(), ?)");
                $stmt->execute([$username, $email, $passwordHash, $role, $current_user['id']]);
                
                $message = "Utilisateur créé avec succès";
            } catch (PDOException $e) {
                $error = "Erreur lors de la création: " . $e->getMessage();
            }
            break;
            
        case 'update_role':
            $userId = intval($_POST['user_id'] ?? 0);
            $newRole = $_POST['new_role'] ?? '';
            
            if (!RoleManager::isValidRole($newRole)) {
                $error = 'Rôle invalide';
                break;
            }
            
            // Vérifier permissions
            $manageableRoles = RoleManager::getManageableRoles($current_user['role']);
            if (!isset($manageableRoles[$newRole])) {
                $error = 'Vous ne pouvez pas assigner ce rôle';
                break;
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE auth_users SET role = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $stmt->execute([$newRole, $current_user['id'], $userId]);
                $message = "Rôle mis à jour";
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour: " . $e->getMessage();
            }
            break;
            
        case 'toggle_status':
            $userId = intval($_POST['user_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? 'active';
            
            try {
                $stmt = $pdo->prepare("UPDATE auth_users SET status = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $stmt->execute([$newStatus, $current_user['id'], $userId]);
                $message = "Statut mis à jour";
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour: " . $e->getMessage();
            }
            break;
    }
}

// Récupération des utilisateurs
$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, role, status, created_at, last_login FROM auth_users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des utilisateurs: " . $e->getMessage();
}

// Rôles gérables par l'utilisateur actuel
$manageableRoles = RoleManager::getManageableRoles($current_user['role']);
$allRoles = RoleManager::getAllRoles();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Administration</title>
    <link rel="stylesheet" href="/assets/css/portal.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <style>
        .users-grid { display: grid; gap: 2rem; grid-template-columns: 1fr 2fr; }
        .user-form { background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e5e7eb; }
        .users-table { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .role-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .actions { display: flex; gap: 8px; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include ROOT_PATH . '/templates/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>👥 Gestion des Utilisateurs</h1>
                <p>Créer, modifier et gérer les comptes utilisateurs</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <div class="alert-icon">✅</div>
                    <div class="alert-content"><?= htmlspecialchars($message) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon">❌</div>
                    <div class="alert-content"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="users-grid">
                <!-- Formulaire de création -->
                <div class="user-form">
                    <h2>Créer un Utilisateur</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_user">
                        
                        <div class="form-group">
                            <label class="form-label required">Nom d'utilisateur</label>
                            <input type="text" name="username" class="form-control" required 
                                   pattern="[a-zA-Z0-9_-]{3,20}" title="3-20 caractères, lettres, chiffres, _ et - autorisés">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Rôle</label>
                            <select name="role" class="form-control" required>
                                <?php foreach ($manageableRoles as $roleKey => $roleData): ?>
                                    <option value="<?= $roleKey ?>"><?= $roleData['icon'] ?> <?= $roleData['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-help">Vous ne pouvez créer que des utilisateurs avec un rôle inférieur au vôtre</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Mot de passe</label>
                            <input type="password" name="password" class="form-control" required 
                                   minlength="8" title="Au moins 8 caractères">
                            <div class="form-help">Minimum 8 caractères</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            ➕ Créer l'Utilisateur
                        </button>
                    </form>
                </div>

                <!-- Liste des utilisateurs -->
                <div class="users-table">
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <h2>Utilisateurs Existants</h2>
                        <p style="margin: 0; color: #6b7280;">Total: <?= count($users) ?> utilisateurs</p>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Dernière connexion</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                                <small style="color: #6b7280;"><?= htmlspecialchars($user['email']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $roleData = $allRoles[$user['role']] ?? null;
                                            if ($roleData): 
                                            ?>
                                                <span class="role-badge" style="background-color: <?= $roleData['color'] ?>; color: white;">
                                                    <?= $roleData['icon'] ?> <?= $roleData['name'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="role-badge" style="background-color: #6b7280; color: white;">
                                                    ❓ <?= htmlspecialchars($user['role']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $user['status'] ?>">
                                                <?= $user['status'] === 'active' ? 'Actif' : 'Inactif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['last_login']): ?>
                                                <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                            <?php else: ?>
                                                <em style="color: #6b7280;">Jamais</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['id'] != $current_user['id']): ?>
                                                <div class="actions">
                                                    <!-- Changer rôle -->
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_role">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <select name="new_role" class="btn-sm" onchange="this.form.submit()">
                                                            <option value="">Changer rôle...</option>
                                                            <?php foreach ($manageableRoles as $roleKey => $roleData): ?>
                                                                <?php if ($roleKey !== $user['role']): ?>
                                                                    <option value="<?= $roleKey ?>"><?= $roleData['name'] ?></option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                    
                                                    <!-- Toggle statut -->
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $user['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                        <button type="submit" class="btn btn-sm <?= $user['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>">
                                                            <?= $user['status'] === 'active' ? '⏸️ Désactiver' : '▶️ Activer' ?>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <em style="color: #6b7280;">Vous</em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Informations sur les rôles -->
            <div style="margin-top: 3rem;">
                <h3>📋 Référence des Rôles</h3>
                <div class="grid grid-3">
                    <?php foreach ($allRoles as $roleKey => $roleData): ?>
                        <div class="card">
                            <div class="card-body">
                                <h4 style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.5rem;">
                                    <span style="font-size: 1.5rem;"><?= $roleData['icon'] ?></span>
                                    <?= $roleData['name'] ?>
                                    <span style="background: <?= $roleData['color'] ?>; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px;">
                                        NIV. <?= $roleData['level'] ?>
                                    </span>
                                </h4>
                                <p style="margin-bottom: 1rem; color: #6b7280;"><?= $roleData['description'] ?></p>
                                
                                <div style="margin-bottom: 1rem;">
                                    <strong>Modules accessibles:</strong><br>
                                    <?php foreach ($roleData['modules'] as $module): ?>
                                        <span style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-right: 4px;">
                                            <?= $module ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if (isset($manageableRoles[$roleKey])): ?>
                                    <div style="color: #059669; font-size: 12px;">
                                        ✅ Vous pouvez créer ce rôle
                                    </div>
                                <?php else: ?>
                                    <div style="color: #dc2626; font-size: 12px;">
                                        ❌ Rôle supérieur ou égal au vôtre
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>
</body>
</html>

<?php
/**
 * Titre: Interface administration - Gestion des utilisateurs CORRIGÉE
 * Chemin: /public/admin/users.php
 * Version: 0.5 beta + build auto
 */

// Configuration sécurisée
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Chargement du système existant
require_once ROOT_PATH . '/config/config.php';

// Vérification connexion BDD existante
if (!isset($db) || !($db instanceof PDO)) {
    require_once ROOT_PATH . '/config/database.php';
}

// Vérification disponibilité PDO
if (!isset($db) || !($db instanceof PDO)) {
    die("❌ Erreur : Connexion base de données non disponible. Vérifiez la configuration dans /config/database.php");
}

// Utiliser la connexion existante au lieu de créer une nouvelle
$pdo = $db; // CORRECTION PRINCIPALE

// Variables template
$page_title = 'Gestion des Utilisateurs';
$current_module = 'admin';
$module_css = true;

// =====================================
// AUTHENTIFICATION SÉCURISÉE
// =====================================
session_start();
$current_user = $_SESSION['user'] ?? null;

// Vérification permissions admin/dev
$authorized_roles = ['admin', 'dev'];
$user_role = $current_user['role'] ?? 'guest';

if (!$current_user || !in_array($user_role, $authorized_roles)) {
    $_SESSION['error'] = 'Accès refusé - Administrateurs uniquement';
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// =====================================
// CLASSE DE GESTION DES RÔLES
// =====================================
class RoleManager {
    public static function getAllRoles() {
        return [
            'dev' => ['name' => 'Développeur', 'level' => 100],
            'admin' => ['name' => 'Administrateur', 'level' => 80], 
            'user' => ['name' => 'Utilisateur', 'level' => 10]
        ];
    }
    
    public static function isValidRole($role) {
        return array_key_exists($role, self::getAllRoles());
    }
    
    public static function getManageableRoles($currentRole) {
        $roles = self::getAllRoles();
        $currentLevel = $roles[$currentRole]['level'] ?? 0;
        
        $manageable = [];
        foreach ($roles as $role => $data) {
            if ($data['level'] <= $currentLevel) {
                $manageable[$role] = $data;
            }
        }
        return $manageable;
    }
    
    public static function hasPermission($currentRole, $permission) {
        $permissions = [
            'dev' => ['manage_users', 'manage_system', 'manage_config'],
            'admin' => ['manage_users'],
            'user' => []
        ];
        
        return in_array($permission, $permissions[$currentRole] ?? []);
    }
}

// Vérification permission spécifique
if (!RoleManager::hasPermission($user_role, 'manage_users')) {
    $_SESSION['error'] = 'Permission insuffisante pour gérer les utilisateurs';
    header('Location: /admin/');
    exit;
}

$message = '';
$error = '';

// =====================================
// TRAITEMENT DES ACTIONS
// =====================================
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
            $manageableRoles = RoleManager::getManageableRoles($user_role);
            if (!isset($manageableRoles[$role])) {
                $error = 'Vous ne pouvez pas créer un utilisateur avec ce rôle';
                break;
            }
            
            try {
                // Vérifier unicité (table auth_users)
                $stmt = $pdo->prepare("SELECT id FROM auth_users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    $error = 'Nom d\'utilisateur déjà utilisé';
                    break;
                }
                
                // Créer utilisateur
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO auth_users (username, password, role, created_at, is_active) VALUES (?, ?, ?, NOW(), 1)");
                $stmt->execute([$username, $passwordHash, $role]);
                
                $message = "✅ Utilisateur '$username' créé avec succès";
            } catch (PDOException $e) {
                $error = "❌ Erreur lors de la création: " . $e->getMessage();
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
            $manageableRoles = RoleManager::getManageableRoles($user_role);
            if (!isset($manageableRoles[$newRole])) {
                $error = 'Vous ne pouvez pas assigner ce rôle';
                break;
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE auth_users SET role = ? WHERE id = ?");
                $stmt->execute([$newRole, $userId]);
                $message = "✅ Rôle mis à jour";
            } catch (PDOException $e) {
                $error = "❌ Erreur lors de la mise à jour: " . $e->getMessage();
            }
            break;
            
        case 'toggle_status':
            $userId = intval($_POST['user_id'] ?? 0);
            $newStatus = intval($_POST['new_status'] ?? 1);
            
            try {
                $stmt = $pdo->prepare("UPDATE auth_users SET is_active = ? WHERE id = ?");
                $stmt->execute([$newStatus, $userId]);
                $status_text = $newStatus ? 'activé' : 'désactivé';
                $message = "✅ Utilisateur $status_text";
            } catch (PDOException $e) {
                $error = "❌ Erreur lors de la mise à jour: " . $e->getMessage();
            }
            break;
    }
}

// =====================================
// RÉCUPÉRATION DES UTILISATEURS
// =====================================
$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, role, session_duration, created_at, last_login, is_active FROM auth_users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "❌ Erreur lors du chargement des utilisateurs: " . $e->getMessage();
}

// Variables pour le template
$manageableRoles = RoleManager::getManageableRoles($user_role);
$allRoles = RoleManager::getAllRoles();

// =====================================
// INCLUSION DU HEADER
// =====================================
$header_file = ROOT_PATH . '/templates/header.php';
if (!file_exists($header_file)) {
    $header_file = ROOT_PATH . '/includes/header.php';
}
if (file_exists($header_file)) {
    include $header_file;
} else {
    echo "⚠️ Header non trouvé. Vérifiez /templates/header.php ou /includes/header.php";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Administration</title>
    
    <!-- CSS Core -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= BUILD_NUMBER ?? '1' ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= BUILD_NUMBER ?? '1' ?>">
    
    <!-- CSS Admin -->
    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=<?= BUILD_NUMBER ?? '1' ?>">
    
    <style>
        .users-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .users-grid {
            display: grid;
            gap: 2rem;
            grid-template-columns: 1fr 2fr;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .users-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .user-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .users-table {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 12px;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-dev {
            background: #fef3c7;
            color: #92400e;
        }
        
        .role-admin {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-user {
            background: #f3f4f6;
            color: #374151;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>

<div class="users-container">
    <div class="page-header">
        <h1>👥 Gestion des Utilisateurs</h1>
        <p>Administration des comptes et permissions du portail</p>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= count($users) ?></div>
            <div class="stat-label">Utilisateurs total</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count(array_filter($users, fn($u) => $u['is_active'])) ?></div>
            <div class="stat-label">Comptes actifs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></div>
            <div class="stat-label">Administrateurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count(array_filter($users, fn($u) => $u['last_login'])) ?></div>
            <div class="stat-label">Connexions récentes</div>
        </div>
    </div>

    <div class="users-grid">
        <!-- Formulaire de création -->
        <div class="user-form">
            <h3>➕ Créer un utilisateur</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_user">
                
                <div class="form-group">
                    <label class="form-label" for="username">Nom d'utilisateur *</label>
                    <input type="text" name="username" id="username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="role">Rôle *</label>
                    <select name="role" id="role" class="form-select" required>
                        <?php foreach ($manageableRoles as $roleKey => $roleData): ?>
                            <option value="<?= htmlspecialchars($roleKey) ?>">
                                <?= htmlspecialchars($roleData['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Mot de passe *</label>
                    <input type="password" name="password" id="password" class="form-input" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    ➕ Créer l'utilisateur
                </button>
            </form>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="users-table">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                <h3>📋 Liste des utilisateurs (<?= count($users) ?>)</h3>
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
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem; color: #6b7280;">
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <br>
                                        <small style="color: #6b7280;">
                                            ID: <?= $user['id'] ?> • 
                                            Créé: <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
                                            <?= htmlspecialchars($allRoles[$user['role']]['name'] ?? $user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $user['is_active'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <span style="color: #6b7280;">Jamais</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <!-- Changement de rôle -->
                                            <?php if ($user['id'] != $current_user['id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <select name="new_role" class="btn-sm" style="padding: 4px;" onchange="this.form.submit()">
                                                        <?php foreach ($manageableRoles as $roleKey => $roleData): ?>
                                                            <option value="<?= htmlspecialchars($roleKey) ?>" 
                                                                    <?= $roleKey === $user['role'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($roleData['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                                
                                                <!-- Toggle statut -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= $user['is_active'] ? 0 : 1 ?>">
                                                    <button type="submit" class="btn btn-sm <?= $user['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                                        <?= $user['is_active'] ? '⏸️ Désactiver' : '▶️ Activer' ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #6b7280; font-size: 12px;">Compte actuel</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// =====================================
// INCLUSION DU FOOTER
// =====================================
$footer_file = ROOT_PATH . '/templates/footer.php';
if (!file_exists($footer_file)) {
    $footer_file = ROOT_PATH . '/includes/footer.php';
}
if (file_exists($footer_file)) {
    include $footer_file;
}
?>

</body>
</html>

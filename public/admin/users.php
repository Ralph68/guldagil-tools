<?php
/**
 * Titre: Gestion des utilisateurs - Version stable
 * Chemin: /public/admin/users.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Chargement config existante
require_once ROOT_PATH . '/config/config.php';

// Utiliser la connexion existante
$pdo = $db;

// Variables template
$page_title = 'Gestion des Utilisateurs';
$current_module = 'admin';

// Session et auth
session_start();
$current_user = $_SESSION['user'] ?? null;

// Vérification rôles admin/dev
$authorized_roles = ['admin', 'dev'];
$user_role = $current_user['role'] ?? 'guest';

if (!$current_user || !in_array($user_role, $authorized_roles)) {
    header('Location: /auth/login.php');
    exit;
}

// Gestion des rôles
class RoleManager {
    public static function getAllRoles() {
        return [
            'dev' => ['name' => 'Développeur', 'level' => 100],
            'admin' => ['name' => 'Administrateur', 'level' => 80], 
            'logistique' => ['name' => 'Logistique', 'level' => 50],
            'user' => ['name' => 'Utilisateur', 'level' => 10]
        ];
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
}

$message = '';
$error = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_user':
            $username = trim($_POST['username'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Nom d\'utilisateur et mot de passe requis';
                break;
            }
            
            try {
                // Vérifier unicité
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
                
                $message = "Utilisateur '$username' créé avec succès";
            } catch (PDOException $e) {
                $error = "Erreur: " . $e->getMessage();
            }
            break;
            
        case 'update_role':
            $userId = intval($_POST['user_id'] ?? 0);
            $newRole = $_POST['new_role'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE auth_users SET role = ? WHERE id = ?");
                $stmt->execute([$newRole, $userId]);
                $message = "Rôle mis à jour";
            } catch (PDOException $e) {
                $error = "Erreur: " . $e->getMessage();
            }
            break;
            
        case 'toggle_status':
            $userId = intval($_POST['user_id'] ?? 0);
            $newStatus = intval($_POST['new_status'] ?? 1);
            
            try {
                $stmt = $pdo->prepare("UPDATE auth_users SET is_active = ? WHERE id = ?");
                $stmt->execute([$newStatus, $userId]);
                $status_text = $newStatus ? 'activé' : 'désactivé';
                $message = "Utilisateur $status_text";
            } catch (PDOException $e) {
                $error = "Erreur: " . $e->getMessage();
            }
            break;
    }
}

// Récupération des utilisateurs
$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, role, session_duration, created_at, last_login, is_active FROM auth_users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur chargement: " . $e->getMessage();
}

$manageableRoles = RoleManager::getManageableRoles($user_role);
$allRoles = RoleManager::getAllRoles();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Administration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1a202c;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .header .breadcrumb {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            border: 1px solid #e2e8f0;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3182ce;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .main-grid {
            display: grid;
            gap: 2rem;
            grid-template-columns: 1fr 2fr;
        }
        
        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #3182ce;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2c5aa0;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }
        
        .btn-success {
            background: #38a169;
            color: white;
        }
        
        .btn-warning {
            background: #d69e2e;
            color: white;
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.875rem;
        }
        
        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-dev {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .badge-admin {
            background: #bee3f8;
            color: #2a4365;
        }
        
        .badge-logistique {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .badge-user {
            background: #e2e8f0;
            color: #2d3748;
        }
        
        .badge-active {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .badge-inactive {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .footer {
            margin-top: 3rem;
            padding: 2rem;
            text-align: center;
            color: #718096;
            font-size: 0.875rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .back-link {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>👥 Gestion des Utilisateurs</h1>
    <div class="breadcrumb">Administration du Portail Guldagil</div>
</div>

<div class="container">
    <div class="back-link">
        <a href="/admin/" class="btn btn-secondary">← Retour Dashboard</a>
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

    <div class="main-grid">
        <!-- Formulaire de création -->
        <div class="card">
            <div class="card-header">
                <h3>➕ Créer un utilisateur</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="form-group">
                        <label class="form-label" for="username">Nom d'utilisateur *</label>
                        <input type="text" name="username" id="username" class="form-input" required>
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
        </div>

        <!-- Liste des utilisateurs -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Liste des utilisateurs (<?= count($users) ?>)</h3>
            </div>
            <div class="table-container">
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
                                <td colspan="5" style="text-align: center; padding: 2rem; color: #718096;">
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <br>
                                        <small style="color: #718096;">
                                            ID: <?= $user['id'] ?> • 
                                            Créé: <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($user['role']) ?>">
                                            <?= htmlspecialchars($allRoles[$user['role']]['name'] ?? $user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $user['is_active'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <span style="color: #718096;">Jamais</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <?php if ($user['id'] != $current_user['id']): ?>
                                                <!-- Changement de rôle -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <select name="new_role" class="form-select" style="width: auto; padding: 0.25rem;" onchange="this.form.submit()">
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
                                                <span style="color: #718096; font-size: 0.75rem;">Compte actuel</span>
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

    <?php
    // Gestion de la mise à jour de la durée des sessions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_timeout'])) {
        $new_timeout = intval($_POST['session_timeout']);
        if ($new_timeout >= 3600 && $new_timeout <= 24 * 60 * 60) { // Entre 1 heure et 24 heures
            file_put_contents(ROOT_PATH . '/config/session_timeout.php', "<?php\n define('SESSION_TIMEOUT', $new_timeout);");
            echo '<div class="alert alert-success">Durée des sessions mise à jour avec succès.</div>';
        } else {
            echo '<div class="alert alert-danger">Durée invalide. Elle doit être comprise entre 1 heure et 24 heures.</div>';
        }
    }
    ?>

    <h2>⚙️ Configuration des sessions</h2>
    <form method="POST" action="">
        <label for="session_timeout">Durée des sessions (en secondes) :</label>
        <input type="number" id="session_timeout" name="session_timeout" value="<?= SESSION_TIMEOUT ?>" min="3600" max="86400" required>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>

<div class="footer">
    <p>&copy; <?= date('Y') ?> Portail Guldagil • Version <?= $version ?? '0.5' ?> • Build <?= $build_number ?? '001' ?></p>
    <p>Connecté en tant que : <strong><?= htmlspecialchars($current_user['username']) ?></strong> (<?= htmlspecialchars($user_role) ?>)</p>
</div>

</body>
</html>

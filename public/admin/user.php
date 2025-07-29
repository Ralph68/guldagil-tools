<?php
/**
 * Titre: Interface CRUD Utilisateurs - Admin Panel
 * Chemin: /public/admin/users.php
 * Version: 0.5 beta + build auto
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Authentification admin uniquement
require_once ROOT_PATH . '/core/auth/AuthManager.php';
$auth = AuthManager::getInstance();

if (!$auth->isAuthenticated() || !$auth->hasRole(['dev', 'admin'])) {
    header('Location: /auth/login.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$page_title = "Gestion Utilisateurs";
$current_module = 'admin';

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'create_user':
                if (!$auth->hasRole(['dev', 'admin'])) {
                    throw new Exception('Permission refusée');
                }
                
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                
                if (strlen($username) < 3) {
                    throw new Exception('Nom d\'utilisateur trop court');
                }
                
                if (strlen($password) < 6) {
                    throw new Exception('Mot de passe trop court');
                }
                
                // Vérifier unicité
                $stmt = $db->prepare("SELECT id FROM auth_users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    throw new Exception('Nom d\'utilisateur déjà utilisé');
                }
                
                // Créer utilisateur
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO auth_users (username, password, role, is_active) 
                    VALUES (?, ?, ?, 1)
                ");
                $stmt->execute([$username, $hashed_password, $role]);
                
                $response = ['success' => true, 'message' => 'Utilisateur créé avec succès'];
                break;
                
            case 'update_user':
                if (!$auth->hasRole(['dev', 'admin'])) {
                    throw new Exception('Permission refusée');
                }
                
                $user_id = intval($_POST['user_id']);
                $new_role = $_POST['role'];
                $is_active = intval($_POST['is_active']);
                
                // Dev seulement peut modifier les roles admin/dev
                if (!$auth->hasRole(['dev']) && in_array($new_role, ['dev', 'admin'])) {
                    throw new Exception('Seul dev peut modifier les rôles administrateurs');
                }
                
                $stmt = $db->prepare("
                    UPDATE auth_users 
                    SET role = ?, is_active = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$new_role, $is_active, $user_id]);
                
                $response = ['success' => true, 'message' => 'Utilisateur mis à jour'];
                break;
                
            case 'reset_password':
                if (!$auth->hasRole(['dev', 'admin'])) {
                    throw new Exception('Permission refusée');
                }
                
                $user_id = intval($_POST['user_id']);
                $new_password = bin2hex(random_bytes(8)); // Mot de passe temporaire
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE auth_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $response = [
                    'success' => true, 
                    'message' => 'Mot de passe réinitialisé',
                    'temp_password' => $new_password
                ];
                break;
                
            case 'delete_user':
                if (!$auth->hasRole(['dev'])) {
                    throw new Exception('Seul dev peut supprimer des utilisateurs');
                }
                
                $user_id = intval($_POST['user_id']);
                
                // Protection : ne pas supprimer soi-même
                if ($user_id == $current_user['id']) {
                    throw new Exception('Impossible de supprimer son propre compte');
                }
                
                // Désactiver au lieu de supprimer
                $stmt = $db->prepare("UPDATE auth_users SET is_active = 0 WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $response = ['success' => true, 'message' => 'Utilisateur désactivé'];
                break;
                
            default:
                throw new Exception('Action non reconnue');
        }
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}

// Récupération des utilisateurs
try {
    $stmt = $db->prepare("
        SELECT id, username, role, is_active, created_at, last_login,
               (SELECT COUNT(*) FROM auth_sessions WHERE user_id = auth_users.id AND expires_at > NOW()) as active_sessions
        FROM auth_users 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
    $error = $e->getMessage();
}

// Statistiques
$stats = $auth->getAuthStats();

// Template
include ROOT_PATH . '/templates/header.php';
?>

<div class="admin-content">
    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= count($users) ?></div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🟢</div>
            <div class="stat-value"><?= $stats['active_sessions'] ?? 0 ?></div>
            <div class="stat-label">Sessions actives</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-value"><?= $stats['failed_attempts_24h'] ?? 0 ?></div>
            <div class="stat-label">Échecs 24h</div>
        </div>
    </div>

    <!-- Actions -->
    <div class="admin-actions">
        <button id="btnCreateUser" class="btn btn-primary">➕ Nouvel utilisateur</button>
        <button id="btnRefresh" class="btn btn-secondary">🔄 Actualiser</button>
    </div>

    <!-- Table utilisateurs -->
    <div class="table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Status</th>
                    <th>Dernière connexion</th>
                    <th>Sessions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr data-user-id="<?= $user['id'] ?>">
                    <td><?= $user['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                        <?php if ($user['id'] == $current_user['id']): ?>
                        <span class="badge badge-info">Vous</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="role-badge role-<?= $user['role'] ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['is_active']): ?>
                        <span class="status-badge status-active">Actif</span>
                        <?php else: ?>
                        <span class="status-badge status-inactive">Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['last_login']): ?>
                        <span title="<?= $user['last_login'] ?>">
                            <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                        </span>
                        <?php else: ?>
                        <em>Jamais</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['active_sessions'] > 0): ?>
                        <span class="session-count active"><?= $user['active_sessions'] ?></span>
                        <?php else: ?>
                        <span class="session-count">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="user-actions">
                            <button class="btn-edit btn-sm" data-user-id="<?= $user['id'] ?>">✏️</button>
                            <button class="btn-reset-pwd btn-sm" data-user-id="<?= $user['id'] ?>">🔑</button>
                            <?php if ($auth->hasRole(['dev']) && $user['id'] != $current_user['id']): ?>
                            <button class="btn-delete btn-sm" data-user-id="<?= $user['id'] ?>">🗑️</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals -->
<div id="createUserModal" class="modal">
    <div class="modal-content">
        <h3>Créer un utilisateur</h3>
        <form id="createUserForm">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" required minlength="3">
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Rôle</label>
                <select name="role" required>
                    <option value="user">Utilisateur</option>
                    <option value="logistique">Logistique</option>
                    <?php if ($auth->hasRole(['dev'])): ?>
                    <option value="admin">Admin</option>
                    <option value="dev">Développeur</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">Créer</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('createUserModal')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
// Interface utilisateurs avec AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Création utilisateur
    document.getElementById('btnCreateUser').addEventListener('click', function() {
        openModal('createUserModal');
    });
    
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'create_user');
        
        fetch('/admin/users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                closeModal('createUserModal');
                location.reload();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Erreur réseau', 'error');
        });
    });
    
    // Actions utilisateurs
    document.querySelectorAll('.btn-reset-pwd').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            
            if (confirm('Réinitialiser le mot de passe ?')) {
                const formData = new FormData();
                formData.append('action', 'reset_password');
                formData.append('user_id', userId);
                
                fetch('/admin/users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`Nouveau mot de passe: ${data.temp_password}`, 'success');
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
            }
        });
    });
});

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showNotification(message, type) {
    // TODO: Système de notifications
    alert(message);
}
</script>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid var(--primary-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.role-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
}

.role-dev { background: #8b5cf6; color: white; }
.role-admin { background: #ef4444; color: white; }
.role-logistique { background: #f59e0b; color: white; }
.role-user { background: #10b981; color: white; }

.status-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; }
.status-active { background: #d1fae5; color: #065f46; }
.status-inactive { background: #fee2e2; color: #991b1b; }

.users-table { width: 100%; border-collapse: collapse; background: white; }
.users-table th, .users-table td { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; }
.users-table th { background: #f9fafb; font-weight: 600; }

.user-actions { display: flex; gap: 0.5rem; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; border: none; border-radius: 4px; cursor: pointer; }

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
}
</style>

<?php include ROOT_PATH . '/templates/footer.php'; ?>
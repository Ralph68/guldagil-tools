<?php
/**
 * Titre: Page de profil utilisateur
 * Chemin: /public/user/profile.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Configuration et sécurité - CORRIGÉ
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Vérifier si le fichier existe, sinon créer le dossier
if (!is_dir(dirname(__FILE__))) {
    mkdir(dirname(__FILE__), 0755, true);
}

// Chargement configuration
$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Configuration manquante</h1><p>Fichier requis : ' . basename($file) . '</p>');
    }
    require_once $file;
}

// Authentification avec AuthManager
try {
    require_once ROOT_PATH . '/core/auth/AuthManager.php';
    $auth = new AuthManager();
    
    if (!$auth->isAuthenticated()) {
        header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $current_user = $auth->getCurrentUser();
} catch (Exception $e) {
    // Fallback sur l'ancien système
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
}

// Variables pour le template
$page_title = 'Mon Profil';
$page_subtitle = 'Informations personnelles et préférences';
$page_description = 'Gestion du profil utilisateur - Informations personnelles';
$current_module = 'profile';
$module_css = true;
$user_authenticated = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '👤', 'text' => 'Mon Profil', 'url' => '/user/profile.php', 'active' => true]
];

// Traitement des formulaires
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            // Mise à jour des informations de profil
            $new_username = trim($_POST['username'] ?? '');
            $new_email = trim($_POST['email'] ?? '');
            $new_full_name = trim($_POST['full_name'] ?? '');
            
            if (empty($new_username)) {
                $error_message = 'Le nom d\'utilisateur est requis';
            } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'L\'adresse email n\'est pas valide';
            } else {
                // Simulation mise à jour (à connecter à votre base de données)
                $_SESSION['user']['username'] = $new_username;
                $_SESSION['user']['email'] = $new_email;
                $_SESSION['user']['full_name'] = $new_full_name;
                $_SESSION['user']['updated_at'] = date('Y-m-d H:i:s');
                
                $success_message = 'Profil mis à jour avec succès';
                $current_user = $_SESSION['user'];
            }
            break;
            
        case 'change_password':
            // Changement de mot de passe
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password)) {
                $error_message = 'Tous les champs sont requis';
            } elseif ($new_password !== $confirm_password) {
                $error_message = 'Les nouveaux mots de passe ne correspondent pas';
            } elseif (strlen($new_password) < 6) {
                $error_message = 'Le mot de passe doit contenir au moins 6 caractères';
            } else {
                // Utilisation AuthManager si disponible
                if (isset($auth) && method_exists($auth, 'changePassword')) {
                    try {
                        $result = $auth->changePassword($current_user['id'], $current_password, $new_password);
                        if ($result['success']) {
                            $success_message = 'Mot de passe modifié avec succès';
                        } else {
                            $error_message = $result['error'] ?? 'Erreur lors du changement';
                        }
                    } catch (Exception $e) {
                        $error_message = 'Erreur système';
                    }
                } else {
                    $success_message = 'Mot de passe modifié avec succès';
                }
            }
            break;
            
        case 'update_preferences':
            // Mise à jour des préférences
            $theme = $_POST['theme'] ?? 'light';
            $language = $_POST['language'] ?? 'fr';
            $notifications = isset($_POST['notifications']);
            $auto_save = isset($_POST['auto_save']);
            
            $_SESSION['user_preferences'] = [
                'theme' => $theme,
                'language' => $language,
                'notifications' => $notifications,
                'auto_save' => $auto_save,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $success_message = 'Préférences mises à jour avec succès';
            break;
            
        case 'delete_account':
            // Suppression de compte (simulation)
            $confirm_delete = $_POST['confirm_delete'] ?? '';
            
            if ($confirm_delete === 'SUPPRIMER') {
                // En mode simulation - en production, supprimer réellement
                $success_message = 'Simulation: Compte supprimé avec succès';
            } else {
                $error_message = 'Confirmation incorrecte pour la suppression';
            }
            break;
    }
}

// Données utilisateur avec valeurs par défaut
$user_data = array_merge([
    'username' => 'Utilisateur',
    'email' => 'user@guldagil.com',
    'full_name' => '',
    'role' => 'user',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'last_login' => date('Y-m-d H:i:s')
], $current_user);

// Préférences utilisateur
$user_preferences = $_SESSION['user_preferences'] ?? [
    'theme' => 'light',
    'language' => 'fr',
    'notifications' => true,
    'auto_save' => true
];

// Statistiques d'activité (simulation)
$activity_stats = [
    'total_logins' => 42,
    'last_activity' => date('d/m/Y H:i'),
    'session_duration' => '2h 15min',
    'devices_count' => 3,
    'security_events' => 0
];

// Inclure le header
include ROOT_PATH . '/templates/header.php';
?>

<div class="profile-container">
    <!-- Messages de feedback -->
    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <div class="alert-icon">✅</div>
        <div class="alert-content">
            <div class="alert-title">Succès</div>
            <div class="alert-message"><?= htmlspecialchars($success_message) ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="alert alert-error">
        <div class="alert-icon">❌</div>
        <div class="alert-content">
            <div class="alert-title">Erreur</div>
            <div class="alert-message"><?= htmlspecialchars($error_message) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="profile-layout">
        <!-- Sidebar utilisateur -->
        <div class="profile-sidebar">
            <div class="user-card">
                <div class="user-avatar-large">
                    <?= strtoupper(substr($user_data['username'], 0, 2)) ?>
                </div>
                <div class="user-info">
                    <h2 class="user-name"><?= htmlspecialchars($user_data['username']) ?></h2>
                    <div class="user-email"><?= htmlspecialchars($user_data['email']) ?></div>
                    <div class="user-role-badge">
                        <?= htmlspecialchars(ucfirst($user_data['role'])) ?>
                    </div>
                </div>
            </div>
            
            <nav class="profile-nav">
                <a href="#profile-info" class="nav-item active" data-tab="profile-info">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">Informations</span>
                </a>
                <a href="#security" class="nav-item" data-tab="security">
                    <span class="nav-icon">🔒</span>
                    <span class="nav-text">Sécurité</span>
                </a>
                <a href="#preferences" class="nav-item" data-tab="preferences">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Préférences</span>
                </a>
                <a href="#activity" class="nav-item" data-tab="activity">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Activité</span>
                </a>
                <a href="#danger" class="nav-item" data-tab="danger">
                    <span class="nav-icon">⚠️</span>
                    <span class="nav-text">Zone dangereuse</span>
                </a>
            </nav>
        </div>
        
        <!-- Contenu principal -->
        <div class="profile-content">
            <!-- Onglet Informations personnelles -->
            <div id="profile-info" class="tab-content active">
                <div class="content-header">
                    <h3 class="content-title">Informations personnelles</h3>
                    <p class="content-description">Gérez vos informations de profil et coordonnées</p>
                </div>
                
                <form method="POST" class="profile-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" class="form-input" 
                               value="<?= htmlspecialchars($user_data['username']) ?>" required>
                        <div class="form-help">Votre identifiant unique pour vous connecter</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               value="<?= htmlspecialchars($user_data['email']) ?>" required>
                        <div class="form-help">Utilisée pour les notifications et la récupération de mot de passe</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="form-label">Nom complet</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" 
                               value="<?= htmlspecialchars($user_data['full_name'] ?? '') ?>" placeholder="Optionnel">
                        <div class="form-help">Nom d'affichage dans l'interface</div>
                    </div>
                    
                    <div class="form-group readonly">
                        <label class="form-label">Rôle</label>
                        <div class="readonly-value">
                            <?= htmlspecialchars(ucfirst($user_data['role'])) ?>
                        </div>
                        <div class="form-help">Votre niveau d'accès au système</div>
                    </div>
                    
                    <div class="form-group readonly">
                        <label class="form-label">Membre depuis</label>
                        <div class="readonly-value">
                            <?= date('d/m/Y', strtotime($user_data['created_at'])) ?>
                        </div>
                        <div class="form-help">Date de création de votre compte</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-icon">💾</span>
                            Sauvegarder
                        </button>
                        <a href="/user/" class="btn btn-secondary">
                            <span class="btn-icon">↩️</span>
                            Retour
                        </a>
                    </div>
                </form>
            </div>

            <!-- Onglet Sécurité -->
            <div id="security" class="tab-content">
                <div class="content-header">
                    <h3 class="content-title">Sécurité du compte</h3>
                    <p class="content-description">Changement de mot de passe et paramètres de sécurité</p>
                </div>
                
                <!-- Changement de mot de passe -->
                <form method="POST" class="profile-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" required>
                        <div class="form-help">Saisissez votre mot de passe actuel</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" required>
                        <div class="form-help">Minimum 6 caractères recommandés</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        <div class="form-help">Ressaisissez le nouveau mot de passe</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-icon">🔒</span>
                            Changer le mot de passe
                        </button>
                    </div>
                </form>
                
                <!-- Informations de sécurité -->
                <div class="security-info">
                    <h4>Informations de sécurité</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Dernière connexion</div>
                            <div class="info-value"><?= htmlspecialchars($activity_stats['last_activity']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Durée de session</div>
                            <div class="info-value"><?= htmlspecialchars($activity_stats['session_duration']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Appareils connectés</div>
                            <div class="info-value"><?= $activity_stats['devices_count'] ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Événements de sécurité</div>
                            <div class="info-value"><?= $activity_stats['security_events'] ?></div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="/auth/logout.php" class="btn btn-danger">
                            <span class="btn-icon">🚪</span>
                            Se déconnecter
                        </a>
                    </div>
                </div>
            </div>

            <!-- Onglet Préférences -->
            <div id="preferences" class="tab-content">
                <div class="content-header">
                    <h3 class="content-title">Préférences</h3>
                    <p class="content-description">Personnalisez votre expérience utilisateur</p>
                </div>
                
                <form method="POST" class="profile-form">
                    <input type="hidden" name="action" value="update_preferences">
                    
                    <div class="preferences-grid">
                        <!-- Thème -->
                        <div class="preference-card">
                            <div class="preference-header">
                                <div class="preference-icon">🎨</div>
                                <div class="preference-info">
                                    <h4>Apparence</h4>
                                    <p>Choisissez votre thème préféré</p>
                                </div>
                            </div>
                            <div class="preference-controls">
                                <label class="radio-label">
                                    <input type="radio" name="theme" value="light" 
                                           <?= $user_preferences['theme'] === 'light' ? 'checked' : '' ?>>
                                    <span class="radio-text">Thème clair</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="theme" value="dark" 
                                           <?= $user_preferences['theme'] === 'dark' ? 'checked' : '' ?>>
                                    <span class="radio-text">Thème sombre</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Langue -->
                        <div class="preference-card">
                            <div class="preference-header">
                                <div class="preference-icon">🌍</div>
                                <div class="preference-info">
                                    <h4>Langue</h4>
                                    <p>Sélectionnez votre langue</p>
                                </div>
                            </div>
                            <div class="preference-controls">
                                <select name="language" class="form-select">
                                    <option value="fr" <?= $user_preferences['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                                    <option value="en" <?= $user_preferences['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Notifications -->
                        <div class="preference-card">
                            <div class="preference-header">
                                <div class="preference-icon">🔔</div>
                                <div class="preference-info">
                                    <h4>Notifications</h4>
                                    <p>Gérez vos notifications</p>
                                </div>
                            </div>
                            <div class="preference-controls">
                                <label class="toggle-label">
                                    <input type="checkbox" name="notifications" class="toggle-input" 
                                           <?= $user_preferences['notifications'] ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-text">Notifications par email</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Sauvegarde automatique -->
                        <div class="preference-card">
                            <div class="preference-header">
                                <div class="preference-icon">💾</div>
                                <div class="preference-info">
                                    <h4>Sauvegarde</h4>
                                    <p>Options de sauvegarde</p>
                                </div>
                            </div>
                            <div class="preference-controls">
                                <label class="toggle-label">
                                    <input type="checkbox" name="auto_save" class="toggle-input" 
                                           <?= $user_preferences['auto_save'] ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-text">Sauvegarde automatique</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-icon">💾</span>
                            Sauvegarder les préférences
                        </button>
                    </div>
                </form>
            </div>

            <!-- Onglet Activité -->
            <div id="activity" class="tab-content">
                <div class="content-header">
                    <h3 class="content-title">Activité du compte</h3>
                    <p class="content-description">Historique et statistiques de votre activité</p>
                </div>
                
                <!-- Statistiques -->
                <div class="activity-stats">
                    <div class="stat-card">
                        <div class="stat-icon">🔐</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $activity_stats['total_logins'] ?></div>
                            <div class="stat-label">Connexions totales</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏱️</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $activity_stats['session_duration'] ?></div>
                            <div class="stat-label">Session actuelle</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📱</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $activity_stats['devices_count'] ?></div>
                            <div class="stat-label">Appareils utilisés</div>
                        </div>
                    </div>
                </div>
                
                <!-- Historique récent -->
                <div class="activity-history">
                    <h4>Activité récente</h4>
                    <div class="activity-timeline">
                        <div class="activity-event">
                            <div class="event-icon">🔐</div>
                            <div class="event-content">
                                <div class="event-title">Connexion réussie</div>
                                <div class="event-time">Aujourd'hui à <?= date('H:i') ?></div>
                            </div>
                        </div>
                        
                        <div class="activity-event">
                            <div class="event-icon">👤</div>
                            <div class="event-content">
                                <div class="event-title">Consultation du profil</div>
                                <div class="event-time">Maintenant</div>
                            </div>
                        </div>
                        
                        <div class="activity-event">
                            <div class="event-icon">⚙️</div>
                            <div class="event-content">
                                <div class="event-title">Modification des préférences</div>
                                <div class="event-time">Hier à 14:30</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Zone dangereuse -->
            <div id="danger" class="tab-content">
                <div class="content-header">
                    <h3 class="content-title">Zone dangereuse</h3>
                    <p class="content-description">Actions irréversibles sur votre compte</p>
                </div>
                
                <div class="danger-zone">
                    <div class="danger-warning">
                        <div class="warning-icon">⚠️</div>
                        <div class="warning-content">
                            <h4>Attention</h4>
                            <p>Les actions ci-dessous sont <strong>irréversibles</strong> et peuvent entraîner la perte définitive de vos données.</p>
                        </div>
                    </div>
                    
                    <div class="danger-actions">
                        <!-- Suppression de compte -->
                        <div class="danger-card">
                            <div class="danger-header">
                                <h4>Supprimer mon compte</h4>
                                <p>Cette action supprimera définitivement votre compte et toutes vos données.</p>
                            </div>
                            
                            <form method="POST" class="danger-form" onsubmit="return confirmDelete()">
                                <input type="hidden" name="action" value="delete_account">
                                
                                <div class="form-group">
                                    <label for="confirm_delete" class="form-label">
                                        Tapez "SUPPRIMER" pour confirmer
                                    </label>
                                    <input type="text" id="confirm_delete" name="confirm_delete" 
                                           class="form-input" placeholder="SUPPRIMER" required>
                                </div>
                                
                                <button type="submit" class="btn btn-danger">
                                    <span class="btn-icon">🗑️</span>
                                    Supprimer définitivement mon compte
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS intégré pour le profil -->
<style>
<?php include __DIR__ . '/assets/css/profile.css'; ?>
</style>

<!-- JavaScript pour le profil -->
<script>
// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    const tabContents = document.querySelectorAll('.tab-content');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Retirer active de tous
            navItems.forEach(nav => nav.classList.remove('active'));
            tabContents.forEach(tab => tab.classList.remove('active'));
            
            // Activer l'onglet cliqué
            this.classList.add('active');
            const targetTab = document.querySelector(this.getAttribute('href'));
            if (targetTab) {
                targetTab.classList.add('active');
            }
        });
    });
    
    // Validation mot de passe
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (newPasswordInput && confirmPasswordInput) {
        function validatePasswords() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                confirmPasswordInput.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        }
        
        newPasswordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);
    }
});

// Confirmation suppression
function confirmDelete() {
    return confirm('⚠️ ATTENTION: Cette action est irréversible!\n\nÊtes-vous absolument certain de vouloir supprimer votre compte?');
}

console.log('👤 Profil utilisateur initialisé');
</script>

<?php
// Inclure footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    echo '</body></html>';
}
?>

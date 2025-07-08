<?php
/**
 * Titre: Page de profil utilisateur
 * Chemin: /user/profile.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Configuration et sécurité
define('ROOT_PATH', dirname(__DIR__));

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

// Vérification authentification
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];

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
                // Simulation changement de mot de passe
                $success_message = 'Mot de passe modifié avec succès';
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
                    <?= strtoupper(substr($user_data['username'], 0, 1)) ?>
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
                               value="<?= htmlspecialchars($user_data['full_name'] ?? '') ?>">
                        <div class="form-help">Votre nom complet pour l'affichage</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-icon">💾</span>
                            Sauvegarder les modifications
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Onglet Sécurité -->
            <div id="security" class="tab-content">
                <div class="content-header">
                    <h3 class="content-title">Sécurité du compte</h3>
                    <p class="content-description">Gérez votre mot de passe et la sécurité de votre compte</p>
                </div>
                
                <form method="POST" class="profile-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" required>
                        <div class="form-help">Minimum 6 caractères, incluez des lettres et des chiffres</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-icon">🔒</span>
                            Changer le mot de passe
                        </button>
                    </div>
                </form>
                
                <div class="security-info">
                    <h4>Informations de sécurité</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Dernière connexion</div>
                            <div class="info-value"><?= date('d/m/Y H:i', strtotime($user_data['last_login'])) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Compte créé</div>
                            <div class="info-value"><?= date('d/m/Y', strtotime($user_data['created_at'])) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Dernière modification</div>
                            <div class="info-value"><?= date('d/m/Y H:i', strtotime($user_data['updated_at'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Onglet Préférences -->
            <div id="preferences" class="tab-content">
                <div class="content-header">
                    <h3 class="content-title">Préférences</h3>
                    <p class="content-description">Personnalisez votre expérience du portail</p>
                </div>
                
                <div class="preferences-grid">
                    <div class="preference-card">
                        <div class="preference-header">
                            <div class="preference-icon">🎨</div>
                            <div class="preference-info

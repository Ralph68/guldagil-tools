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
                            <div class="preference-info">
                                <h4>Apparence</h4>
                                <p>Thème et affichage</p>
                            </div>
                        </div>
                        <div class="preference-controls">
                            <label class="toggle-label">
                                <input type="checkbox" class="toggle-input">
                                <span class="toggle-slider"></span>
                                Mode sombre
                            </label>
                        </div>
                    </div>
                    
                    <div class="preference-card">
                        <div class="preference-header">
                            <div class="preference-icon">🔔</div>
                            <div class="preference-info">
                                <h4>Notifications</h4>
                                <p>Alertes et rappels</p>
                            </div>
                        </div>
                        <div class="preference-controls">
                            <label class="toggle-label">
                                <input type="checkbox" class="toggle-input" checked>
                                <span class="toggle-slider"></span>
                                Notifications email
                            </label>
                            <label class="toggle-label">
                                <input type="checkbox" class="toggle-input">
                                <span class="toggle-slider"></span>
                                Notifications push
                            </label>
                        </div>
                    </div>
                    
                    <div class="preference-card">
                        <div class="preference-header">
                            <div class="preference-icon">🌍</div>
                            <div class="preference-info">
                                <h4>Localisation</h4>
                                <p>Langue et région</p>
                            </div>
                        </div>
                        <div class="preference-controls">
                            <select class="preference-select">
                                <option value="fr">Français</option>
                                <option value="en">English</option>
                            </select>
                            <select class="preference-select">
                                <option value="FR">France</option>
                                <option value="BE">Belgique</option>
                                <option value="CH">Suisse</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Onglet Activité -->
            <div id="activity" class="tab-content">
                <div class="content-header">
                    <h3 class="content-title">Activité récente</h3>
                    <p class="content-description">Historique de vos actions sur le portail</p>
                </div>
                
                <div class="activity-timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">🔐</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Connexion au portail</div>
                            <div class="timeline-description">Accès depuis l'adresse IP 192.168.1.100</div>
                            <div class="timeline-time">Aujourd'hui à 14:32</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">🚛</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Calcul frais de port</div>
                            <div class="timeline-description">Devis transport vers Lyon - 25kg</div>
                            <div class="timeline-time">Aujourd'hui à 11:15</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">⚠️</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Déclaration ADR</div>
                            <div class="timeline-description">Création expédition MD-2024-001</div>
                            <div class="timeline-time">Hier à 16:45</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">👤</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Mise à jour du profil</div>
                            <div class="timeline-description">Modification de l'adresse email</div>
                            <div class="timeline-time">Hier à 09:20</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS intégré pour la page profil -->
<style>
    .profile-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: var(--spacing-xl) var(--spacing-md);
    }
    
    .alert {
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-md);
        padding: var(--spacing-lg);
        border-radius: var(--radius-lg);
        margin-bottom: var(--spacing-xl);
        border: 1px solid;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        border-color: #10b981;
        color: #065f46;
    }
    
    .alert-error {
        background: linear-gradient(135deg, #fef2f2, #fecaca);
        border-color: #ef4444;
        color: #7f1d1d;
    }
    
    .alert-icon {
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .alert-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .profile-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: var(--spacing-2xl);
        align-items: start;
    }
    
    /* Sidebar */
    .profile-sidebar {
        position: sticky;
        top: var(--spacing-xl);
    }
    
    .user-card {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        padding: var(--spacing-xl);
        text-align: center;
        margin-bottom: var(--spacing-lg);
        box-shadow: var(--shadow-md);
    }
    
    .user-avatar-large {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        margin: 0 auto var(--spacing-lg);
        border: 4px solid var(--gray-100);
    }
    
    .user-name {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 var(--spacing-sm);
        color: var(--gray-900);
    }
    
    .user-email {
        color: var(--gray-600);
        font-size: 0.875rem;
        margin-bottom: var(--spacing-md);
    }
    
    .user-role-badge {
        display: inline-block;
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
        padding: var(--spacing-xs) var(--spacing-md);
        border-radius: var(--radius-lg);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .profile-nav {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }
    
    .nav-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        padding: var(--spacing-lg);
        color: var(--gray-700);
        text-decoration: none;
        transition: var(--transition-fast);
        border-bottom: 1px solid var(--gray-100);
    }
    
    .nav-item:last-child {
        border-bottom: none;
    }
    
    .nav-item:hover {
        background: var(--gray-50);
        color: var(--primary-blue);
    }
    
    .nav-item.active {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
    }
    
    .nav-icon {
        font-size: 1.25rem;
        width: 24px;
        text-align: center;
    }
    
    .nav-text {
        font-weight: 500;
    }
    
    /* Contenu principal */
    .profile-content {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    
    .tab-content {
        display: none;
        padding: var(--spacing-2xl);
    }
    
    .tab-content.active {
        display: block;
    }
    
    .content-header {
        margin-bottom: var(--spacing-2xl);
        padding-bottom: var(--spacing-lg);
        border-bottom: 1px solid var(--gray-200);
    }
    
    .content-title {
        font-size: 1.75rem;
        font-weight: 600;
        margin: 0 0 var(--spacing-sm);
        color: var(--gray-900);
    }
    
    .content-description {
        color: var(--gray-600);
        font-size: 1rem;
        margin: 0;
    }
    
    /* Formulaires */
    .profile-form {
        max-width: 600px;
    }
    
    .form-group {
        margin-bottom: var(--spacing-xl);
    }
    
    .form-label {
        display: block;
        font-weight: 500;
        color: var(--gray-700);
        margin-bottom: var(--spacing-sm);
        font-size: 0.875rem;
    }
    
    .form-input {
        width: 100%;
        padding: var(--spacing-md);
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-md);
        font-size: 1rem;
        transition: var(--transition-fast);
    }
    
    .form-input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-help {
        font-size: 0.75rem;
        color: var(--gray-500);
        margin-top: var(--spacing-xs);
    }
    
    .form-actions {
        margin-top: var(--spacing-2xl);
        padding-top: var(--spacing-lg);
        border-top: 1px solid var(--gray-200);
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-md) var(--spacing-xl);
        border: none;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: var(--transition-normal);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-lg);
    }
    
    .btn-icon {
        font-size: 1rem;
    }
    
    /* Section sécurité */
    .security-info {
        margin-top: var(--spacing-2xl);
        padding-top: var(--spacing-xl);
        border-top: 1px solid var(--gray-200);
    }
    
    .security-info h4 {
        margin-bottom: var(--spacing-lg);
        color: var(--gray-900);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-lg);
    }
    
    .info-item {
        background: var(--gray-50);
        padding: var(--spacing-lg);
        border-radius: var(--radius-md);
    }
    
    .info-label {
        font-size: 0.75rem;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: var(--spacing-xs);
    }
    
    .info-value {
        font-weight: 500;
        color: var(--gray-900);
    }
    
    /* Préférences */
    .preferences-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--spacing-xl);
    }
    
    .preference-card {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        padding: var(--spacing-xl);
    }
    
    .preference-header {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
    }
    
    .preference-icon {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .preference-info h4 {
        margin: 0 0 0.25rem;
        color: var(--gray-900);
    }
    
    .preference-info p {
        margin: 0;
        color: var(--gray-600);
        font-size: 0.875rem;
    }
    
    .preference-controls {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .toggle-label {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        cursor: pointer;
        font-size: 0.875rem;
        color: var(--gray-700);
    }
    
    .toggle-input {
        display: none;
    }
    
    .toggle-slider {
        width: 48px;
        height: 24px;
        background: var(--gray-300);
        border-radius: 12px;
        position: relative;
        transition: var(--transition-normal);
    }
    
    .toggle-slider::before {
        content: '';
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: var(--transition-normal);
        box-shadow: var(--shadow-sm);
    }
    
    .toggle-input:checked + .toggle-slider {
        background: var(--primary-blue);
    }
    
    .toggle-input:checked + .toggle-slider::before {
        transform: translateX(24px);
    }
    
    .preference-select {
        padding: var(--spacing-sm) var(--spacing-md);
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-md);
        background: white;
        font-size: 0.875rem;
    }
    
    /* Timeline activité */
    .activity-timeline {
        position: relative;
    }
    
    .activity-timeline::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--gray-200);
    }
    
    .timeline-item {
        position: relative;
        display: flex;
        gap: var(--spacing-lg);
        margin-bottom: var(--spacing-xl);
    }
    
    .timeline-icon {
        width: 40px;
        height: 40px;
        background: white;
        border: 2px solid var(--primary-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.125rem;
        z-index: 1;
        flex-shrink: 0;
    }
    
    .timeline-content {
        flex: 1;
        background: var(--gray-50);
        padding: var(--spacing-lg);
        border-radius: var(--radius-md);
        border: 1px solid var(--gray-200);
    }
    
    .timeline-title {
        font-weight: 500;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
    }
    
    .timeline-description {
        color: var(--gray-600);
        font-size: 0.875rem;
        margin-bottom: var(--spacing-sm);
    }
    
    .timeline-time {
        color: var(--gray-500);
        font-size: 0.75rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .profile-layout {
            grid-template-columns: 1fr;
            gap: var(--spacing-xl);
        }
        
        .profile-sidebar {
            position: static;
        }
        
        .preferences-grid {
            grid-template-columns: 1fr;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- JavaScript pour les onglets -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des onglets
    const navItems = document.querySelectorAll('.nav-item');
    const tabContents = document.querySelectorAll('.tab-content');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTab = this.getAttribute('data-tab');
            
            // Retirer active de tous les éléments
            navItems.forEach(nav => nav.classList.remove('active'));
            tabContents.forEach(tab => tab.classList.remove('active'));
            
            // Ajouter active aux éléments sélectionnés
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
    
    // Validation du formulaire de mot de passe
    const passwordForm = document.querySelector('form[action*="change_password"]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères');
                return false;
            }
        });
    }
    
    // Animation d'apparition
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.preference-card, .timeline-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});
</script>

<?php
// Inclure le footer
include ROOT_PATH . '/templates/footer.php';
?>

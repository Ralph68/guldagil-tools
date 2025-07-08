<?php
/**
 * Titre: Page de changement de mot de passe sécurisé
 * Chemin: /user/change-password.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Configuration et sécurité
define('ROOT_PATH', dirname(__DIR__));

// Vérifier si le dossier existe, sinon le créer
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
$page_title = 'Changer mon mot de passe';
$page_subtitle = 'Sécurité renforcée de votre compte';
$page_description = 'Changement de mot de passe sécurisé - Bonnes pratiques';
$current_module = 'security';
$module_css = true;
$user_authenticated = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '👤', 'text' => 'Mon Profil', 'url' => '/user/profile.php', 'active' => false],
    ['icon' => '🔒', 'text' => 'Changer mot de passe', 'url' => '/user/change-password.php', 'active' => true]
];

// Configuration sécurité mot de passe
$password_rules = [
    'min_length' => 12,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => true,
    'prevent_common' => true,
    'prevent_personal' => true
];

// Mots de passe faibles communs à éviter
$common_passwords = [
    'password', '123456', '123456789', 'qwerty', 'abc123', 'password123',
    'admin', 'user', 'letmein', 'welcome', 'monkey', 'dragon', 'master',
    'azerty', 'motdepasse', 'guldagil', 'portail'
];

// Messages de feedback
$success_message = '';
$error_message = '';
$password_strength = 0;
$strength_feedback = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation du mot de passe actuel
    if (empty($current_password)) {
        $error_message = 'Le mot de passe actuel est requis';
    }
    // Simulation vérification mot de passe actuel (à remplacer par vraie vérification BDD)
    elseif (!verifyCurrentPassword($current_password, $current_user)) {
        $error_message = 'Le mot de passe actuel est incorrect';
    }
    // Validation du nouveau mot de passe
    elseif (empty($new_password)) {
        $error_message = 'Le nouveau mot de passe est requis';
    }
    elseif ($new_password !== $confirm_password) {
        $error_message = 'Les nouveaux mots de passe ne correspondent pas';
    }
    else {
        // Vérification complète avec bonnes pratiques
        $validation = validatePassword($new_password, $current_user, $password_rules, $common_passwords);
        
        if (!$validation['valid']) {
            $error_message = 'Mot de passe non conforme : ' . implode(', ', $validation['errors']);
        }
        else {
            // Changement de mot de passe réussi
            if (changeUserPassword($current_user, $new_password)) {
                $success_message = 'Mot de passe changé avec succès ! Votre compte est maintenant plus sécurisé.';
                
                // Log de sécurité
                logSecurityEvent('PASSWORD_CHANGED', $current_user['username'], [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            } else {
                $error_message = 'Erreur lors du changement de mot de passe. Veuillez réessayer.';
            }
        }
    }
}

/**
 * Validation complète du mot de passe avec bonnes pratiques
 */
function validatePassword($password, $user, $rules, $common_passwords) {
    $errors = [];
    $score = 0;
    
    // Longueur minimum
    if (strlen($password) < $rules['min_length']) {
        $errors[] = "minimum {$rules['min_length']} caractères";
    } else {
        $score += 2;
    }
    
    // Majuscules
    if ($rules['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "au moins une majuscule";
    } else {
        $score += 1;
    }
    
    // Minuscules
    if ($rules['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
        $errors[] = "au moins une minuscule";
    } else {
        $score += 1;
    }
    
    // Chiffres
    if ($rules['require_numbers'] && !preg_match('/[0-9]/', $password)) {
        $errors[] = "au moins un chiffre";
    } else {
        $score += 1;
    }
    
    // Caractères spéciaux
    if ($rules['require_symbols'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "au moins un caractère spécial (!@#$%^&*)";
    } else {
        $score += 2;
    }
    
    // Éviter mots de passe communs
    if ($rules['prevent_common'] && in_array(strtolower($password), $common_passwords)) {
        $errors[] = "ne doit pas être un mot de passe courant";
    } else {
        $score += 1;
    }
    
    // Éviter informations personnelles
    if ($rules['prevent_personal']) {
        $personal_data = [
            strtolower($user['username']),
            'guldagil',
            date('Y'),
            date('y')
        ];
        
        foreach ($personal_data as $data) {
            if (stripos($password, $data) !== false) {
                $errors[] = "ne doit pas contenir des informations personnelles";
                break;
            }
        }
        
        if (!in_array("ne doit pas contenir des informations personnelles", $errors)) {
            $score += 1;
        }
    }
    
    // Vérifier la complexité (pas de répétitions)
    if (!preg_match('/(.)\1{2,}/', $password)) {
        $score += 1;
    }
    
    // Bonus pour longueur élevée
    if (strlen($password) >= 16) $score += 1;
    if (strlen($password) >= 20) $score += 1;
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'score' => min($score, 10),
        'strength' => getPasswordStrengthLabel($score)
    ];
}

function getPasswordStrengthLabel($score) {
    if ($score <= 2) return ['label' => 'Très faible', 'class' => 'very-weak'];
    if ($score <= 4) return ['label' => 'Faible', 'class' => 'weak'];
    if ($score <= 6) return ['label' => 'Moyen', 'class' => 'medium'];
    if ($score <= 8) return ['label' => 'Fort', 'class' => 'strong'];
    return ['label' => 'Très fort', 'class' => 'very-strong'];
}

function verifyCurrentPassword($password, $user) {
    // Simulation - à remplacer par vérification BDD avec password_verify()
    $temp_passwords = [
        'admin' => 'admin123',
        'user' => 'user123',
        'dev' => 'dev123'
    ];
    
    return isset($temp_passwords[$user['username']]) && 
           $temp_passwords[$user['username']] === $password;
}

function changeUserPassword($user, $new_password) {
    // Hashage sécurisé du mot de passe
    $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iterations
        'threads' => 3          // 3 threads
    ]);
    
    // Simulation sauvegarde - à remplacer par requête BDD
    // UPDATE auth_users SET password = ?, updated_at = NOW() WHERE username = ?
    
    return true; // Simulation succès
}

function logSecurityEvent($event, $username, $data) {
    $log_entry = json_encode([
        'event' => $event,
        'username' => $username,
        'data' => $data
    ]);
    
    // Log dans fichier sécurité
    $log_dir = ROOT_PATH . '/storage/logs';
    if (is_dir($log_dir) || mkdir($log_dir, 0755, true)) {
        file_put_contents($log_dir . '/security.log', $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// Inclure le header
include ROOT_PATH . '/templates/header.php';
?>

<div class="password-container">
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

    <div class="password-header">
        <h1 class="password-title">
            <span class="title-icon">🔒</span>
            Changer mon mot de passe
        </h1>
        <p class="password-description">
            Sécurisez votre compte avec un mot de passe fort suivant les meilleures pratiques
        </p>
    </div>

    <div class="password-layout">
        <!-- Formulaire principal -->
        <div class="password-form-section">
            <form method="POST" class="password-form" id="passwordForm">
                
                <!-- Mot de passe actuel -->
                <div class="form-group">
                    <label for="current_password" class="form-label">
                        <span class="label-icon">🔓</span>
                        Mot de passe actuel
                    </label>
                    <div class="password-input-group">
                        <input type="password" id="current_password" name="current_password" 
                               class="form-input" required autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    <div class="form-help">Votre mot de passe actuel pour confirmer votre identité</div>
                </div>
                
                <!-- Nouveau mot de passe -->
                <div class="form-group">
                    <label for="new_password" class="form-label">
                        <span class="label-icon">🔐</span>
                        Nouveau mot de passe
                    </label>
                    <div class="password-input-group">
                        <input type="password" id="new_password" name="new_password" 
                               class="form-input" required autocomplete="new-password"
                               oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    
                    <!-- Indicateur de force -->
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText">Saisissez un mot de passe</div>
                    </div>
                </div>
                
                <!-- Confirmation -->
                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <span class="label-icon">🔐</span>
                        Confirmer le nouveau mot de passe
                    </label>
                    <div class="password-input-group">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-input" required autocomplete="new-password"
                               oninput="checkPasswordMatch()">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <span class="btn-icon">🔒</span>
                        Changer le mot de passe
                    </button>
                    <a href="/user/profile.php" class="btn btn-secondary">
                        <span class="btn-icon">↩️</span>
                        Annuler
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Guide sécurité -->
        <div class="security-guide">
            <div class="guide-card">
                <h3 class="guide-title">
                    <span class="guide-icon">🛡️</span>
                    Exigences de sécurité
                </h3>
                
                <div class="requirements-list">
                    <div class="requirement-item" id="req-length">
                        <span class="req-icon">❌</span>
                        <span class="req-text">Au moins <?= $password_rules['min_length'] ?> caractères</span>
                    </div>
                    <div class="requirement-item" id="req-uppercase">
                        <span class="req-icon">❌</span>
                        <span class="req-text">Une majuscule (A-Z)</span>
                    </div>
                    <div class="requirement-item" id="req-lowercase">
                        <span class="req-icon">❌</span>
                        <span class="req-text">Une minuscule (a-z)</span>
                    </div>
                    <div class="requirement-item" id="req-number">
                        <span class="req-icon">❌</span>
                        <span class="req-text">Un chiffre (0-9)</span>
                    </div>
                    <div class="requirement-item" id="req-symbol">
                        <span class="req-icon">❌</span>
                        <span class="req-text">Un caractère spécial (!@#$%^&*)</span>
                    </div>
                    <div class="requirement-item" id="req-common">
                        <span class="req-icon">❌</span>
                        <span class="req-text">Pas un mot de passe courant</span>
                    </div>
                </div>
            </div>
            
            <div class="guide-card">
                <h3 class="guide-title">
                    <span class="guide-icon">💡</span>
                    Conseils de sécurité
                </h3>
                
                <div class="tips-list">
                    <div class="tip-item">
                        <span class="tip-icon">🎯</span>
                        <div>
                            <strong>Phrase de passe</strong>
                            <p>Utilisez une phrase memorable : "J'adore manger 3 pizzas à 19h30 !"</p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">🔄</span>
                        <div>
                            <strong>Gestionnaire de mots de passe</strong>
                            <p>Utilisez LastPass, 1Password ou Bitwarden pour générer et stocker</p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">🚫</span>
                        <div>
                            <strong>À éviter</strong>
                            <p>Informations personnelles, dates, noms de famille ou d'animaux</p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">⏰</span>
                        <div>
                            <strong>Renouvellement</strong>
                            <p>Changez votre mot de passe tous les 6 mois ou si compromis</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS intégré pour la page -->
<style>
    .password-container {
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
    
    .password-header {
        text-align: center;
        margin-bottom: var(--spacing-2xl);
        padding-bottom: var(--spacing-xl);
        border-bottom: 1px solid var(--gray-200);
    }
    
    .password-title {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-md);
        font-size: 2rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 var(--spacing-md);
    }
    
    .title-icon {
        font-size: 2rem;
    }
    
    .password-description {
        color: var(--gray-600);
        font-size: 1.125rem;
        margin: 0;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .password-layout {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: var(--spacing-2xl);
        align-items: start;
    }
    
    /* Formulaire */
    .password-form-section {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        padding: var(--spacing-2xl);
        box-shadow: var(--shadow-md);
    }
    
    .password-form {
        max-width: 500px;
    }
    
    .form-group {
        margin-bottom: var(--spacing-xl);
    }
    
    .form-label {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        font-weight: 500;
        color: var(--gray-700);
        margin-bottom: var(--spacing-sm);
        font-size: 0.875rem;
    }
    
    .label-icon {
        font-size: 1rem;
    }
    
    .password-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .form-input {
        width: 100%;
        padding: var(--spacing-md);
        padding-right: 3rem;
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
    
    .password-toggle {
        position: absolute;
        right: 0.75rem;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem;
        color: var(--gray-500);
        font-size: 1rem;
    }
    
    .password-toggle:hover {
        color: var(--gray-700);
    }
    
    .form-help {
        font-size: 0.75rem;
        color: var(--gray-500);
        margin-top: var(--spacing-xs);
    }
    
    /* Force du mot de passe */
    .password-strength {
        margin-top: var(--spacing-sm);
    }
    
    .strength-bar {
        width: 100%;
        height: 8px;
        background: var(--gray-200);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: var(--spacing-xs);
    }
    
    .strength-fill {
        height: 100%;
        width: 0%;
        transition: all 0.3s ease;
        border-radius: 4px;
    }
    
    .strength-fill.very-weak { background: #ef4444; width: 20%; }
    .strength-fill.weak { background: #f97316; width: 40%; }
    .strength-fill.medium { background: #eab308; width: 60%; }
    .strength-fill.strong { background: #22c55e; width: 80%; }
    .strength-fill.very-strong { background: #16a34a; width: 100%; }
    
    .strength-text {
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .strength-text.very-weak { color: #ef4444; }
    .strength-text.weak { color: #f97316; }
    .strength-text.medium { color: #eab308; }
    .strength-text.strong { color: #22c55e; }
    .strength-text.very-strong { color: #16a34a; }
    
    /* Correspondance mot de passe */
    .password-match {
        margin-top: var(--spacing-xs);
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .password-match.match { color: #22c55e; }
    .password-match.no-match { color: #ef4444; }
    
    /* Guide sécurité */
    .security-guide {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-xl);
        position: sticky;
        top: var(--spacing-xl);
    }
    
    .guide-card {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        padding: var(--spacing-xl);
        box-shadow: var(--shadow-md);
    }
    
    .guide-title {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0 0 var(--spacing-lg);
    }
    
    .guide-icon {
        font-size: 1.25rem;
    }
    
    /* Liste des exigences */
    .requirements-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .requirement-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        font-size: 0.875rem;
        transition: var(--transition-fast);
    }
    
    .req-icon {
        font-size: 1rem;
        width: 20px;
        text-align: center;
    }
    
    .requirement-item.valid .req-icon { color: #22c55e; }
    .requirement-item.invalid .req-icon { color: #ef4444; }
    
    .requirement-item.valid .req-text { color: var(--gray-700); }
    .requirement-item.invalid .req-text { color: var(--gray-500); }
    
    /* Conseils */
    .tips-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-lg);
    }
    
    .tip-item {
        display: flex;
        gap: var(--spacing-md);
        align-items: flex-start;
    }
    
    .tip-icon {
        font-size: 1.25rem;
        flex-shrink: 0;
        margin-top: 0.125rem;
    }
    
    .tip-item strong {
        display: block;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
        font-size: 0.875rem;
    }
    
    .tip-item p {
        margin: 0;
        color: var(--gray-600);
        font-size: 0.75rem;
        line-height: 1.4;
    }
    
    /* Boutons */
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
    
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: var(--shadow-lg);
    }
    
    .btn-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
        border: 1px solid var(--gray-300);
    }
    
    .btn-secondary:hover {
        background: var(--gray-200);
        border-color: var(--gray-400);
    }
    
    .btn-icon {
        font-size: 1rem;
    }
    
    .form-actions {
        margin-top: var(--spacing-2xl);
        padding-top: var(--spacing-lg);
        border-top: 1px solid var(--gray-200);
        display: flex;
        gap: var(--spacing-md);
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .password-layout {
            grid-template-columns: 1fr;
            gap: var(--spacing-xl);
        }
        
        .security-guide {
            position: static;
            order: -1;
        }
    }
    
    @media (max-width: 768px) {
        .password-container {
            padding: var(--spacing-lg) var(--spacing-sm);
        }
        
        .password-form-section,
        .guide-card {
            padding: var(--spacing-lg);
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            justify-content: center;
        }
    }
</style>

<!-- JavaScript pour validation en temps réel -->
<script>
// Configuration des règles
const passwordRules = <?= json_encode($password_rules) ?>;
const commonPasswords = <?= json_encode($common_passwords) ?>;
const currentUsername = '<?= htmlspecialchars($current_user['username']) ?>';

// État global
let passwordStrength = 0;
let isPasswordMatch = false;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Focus sur le premier champ
    document.getElementById('current_password').focus();
    
    // Événements
    document.getElementById('new_password').addEventListener('input', function() {
        checkPasswordStrength(this.value);
        checkPasswordMatch();
        updateSubmitButton();
    });
    
    document.getElementById('confirm_password').addEventListener('input', function() {
        checkPasswordMatch();
        updateSubmitButton();
    });
    
    document.getElementById('current_password').addEventListener('input', function() {
        updateSubmitButton();
    });
});

// Vérification force du mot de passe
function checkPasswordStrength(password) {
    const validation = validatePasswordClient(password);
    passwordStrength = validation.score;
    
    // Mettre à jour l'indicateur visuel
    updateStrengthIndicator(validation);
    
    // Mettre à jour les exigences
    updateRequirements(password);
}

function validatePasswordClient(password) {
    let score = 0;
    let errors = [];
    
    // Longueur
    if (password.length >= passwordRules.min_length) score += 2;
    else errors.push('length');
    
    // Majuscules
    if (/[A-Z]/.test(password)) score += 1;
    else errors.push('uppercase');
    
    // Minuscules
    if (/[a-z]/.test(password)) score += 1;
    else errors.push('lowercase');
    
    // Chiffres
    if (/[0-9]/.test(password)) score += 1;
    else errors.push('numbers');
    
    // Caractères spéciaux
    if (/[^A-Za-z0-9]/.test(password)) score += 2;
    else errors.push('symbols');
    
    // Mots de passe communs
    if (!commonPasswords.includes(password.toLowerCase())) score += 1;
    else errors.push('common');
    
    // Informations personnelles
    const personal = [currentUsername.toLowerCase(), 'guldagil', new Date().getFullYear().toString()];
    let hasPersonal = false;
    for (let data of personal) {
        if (password.toLowerCase().includes(data)) {
            hasPersonal = true;
            break;
        }
    }
    if (!hasPersonal) score += 1;
    else errors.push('personal');
    
    // Bonus complexité
    if (!/(.)\1{2,}/.test(password)) score += 1; // Pas de répétitions
    if (password.length >= 16) score += 1;
    if (password.length >= 20) score += 1;
    
    return {
        score: Math.min(score, 10),
        errors: errors,
        strength: getStrengthLevel(score)
    };
}

function getStrengthLevel(score) {
    if (score <= 2) return { label: 'Très faible', class: 'very-weak' };
    if (score <= 4) return { label: 'Faible', class: 'weak' };
    if (score <= 6) return { label: 'Moyen', class: 'medium' };
    if (score <= 8) return { label: 'Fort', class: 'strong' };
    return { label: 'Très fort', class: 'very-strong' };
}

function updateStrengthIndicator(validation) {
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    // Supprimer les classes précédentes
    strengthFill.className = 'strength-fill ' + validation.strength.class;
    strengthText.className = 'strength-text ' + validation.strength.class;
    strengthText.textContent = validation.strength.label;
}

function updateRequirements(password) {
    const requirements = {
        'req-length': password.length >= passwordRules.min_length,
        'req-uppercase': /[A-Z]/.test(password),
        'req-lowercase': /[a-z]/.test(password),
        'req-number': /[0-9]/.test(password),
        'req-symbol': /[^A-Za-z0-9]/.test(password),
        'req-common': !commonPasswords.includes(password.toLowerCase())
    };
    
    for (let [id, valid] of Object.entries(requirements)) {
        const element = document.getElementById(id);
        const icon = element.querySelector('.req-icon');
        
        if (valid) {
            element.className = 'requirement-item valid';
            icon.textContent = '✅';
        } else {
            element.className = 'requirement-item invalid';
            icon.textContent = '❌';
        }
    }
}

function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchElement = document.getElementById('passwordMatch');
    
    if (confirmPassword === '') {
        matchElement.textContent = '';
        matchElement.className = 'password-match';
        isPasswordMatch = false;
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchElement.textContent = '✅ Les mots de passe correspondent';
        matchElement.className = 'password-match match';
        isPasswordMatch = true;
    } else {
        matchElement.textContent = '❌ Les mots de passe ne correspondent pas';
        matchElement.className = 'password-match no-match';
        isPasswordMatch = false;
    }
}

function updateSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    const canSubmit = currentPassword !== '' && 
                     newPassword !== '' && 
                     confirmPassword !== '' &&
                     passwordStrength >= 6 && 
                     isPasswordMatch;
    
    submitBtn.disabled = !canSubmit;
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = field.nextElementSibling.querySelector('.toggle-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.textContent = '🙈';
    } else {
        field.type = 'password';
        toggle.textContent = '👁️';
    }
}

// Validation avant soumission
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const validation = validatePasswordClient(newPassword);
    
    if (validation.score < 6) {
        e.preventDefault();
        alert('Votre mot de passe n\'est pas assez fort. Veuillez suivre toutes les exigences de sécurité.');
        return false;
    }
    
    if (!isPasswordMatch) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas.');
        return false;
    }
    
    // Confirmation finale
    if (!confirm('Êtes-vous sûr de vouloir changer votre mot de passe ?')) {
        e.preventDefault();
        return false;
    }
    
    return true;
});

// Génération automatique de mot de passe fort
function generateStrongPassword() {
    const length = 16;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?";
    let password = "";
    
    // Assurer au moins un caractère de chaque type
    password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)]; // Majuscule
    password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)]; // Minuscule
    password += "0123456789"[Math.floor(Math.random() * 10)]; // Chiffre
    password += "!@#$%^&*"[Math.floor(Math.random() * 8)]; // Symbole
    
    // Compléter avec des caractères aléatoires
    for (let i = password.length; i < length; i++) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }
    
    // Mélanger les caractères
    return password.split('').sort(() => Math.random() - 0.5).join('');
}

// Bouton de génération (optionnel - peut être ajouté plus tard)
function addPasswordGenerator() {
    const generateBtn = document.createElement('button');
    generateBtn.type = 'button';
    generateBtn.className = 'btn btn-secondary';
    generateBtn.innerHTML = '<span class="btn-icon">🎲</span>Générer un mot de passe fort';
    generateBtn.onclick = function() {
        const newPassword = generateStrongPassword();
        document.getElementById('new_password').value = newPassword;
        document.getElementById('confirm_password').value = newPassword;
        checkPasswordStrength(newPassword);
        checkPasswordMatch();
        updateSubmitButton();
    };
    
    document.querySelector('.form-actions').appendChild(generateBtn);
}

// Appeler si souhaité
// addPasswordGenerator();
</script>

<?php
// Inclure le footer
include ROOT_PATH . '/templates/footer.php';
?>

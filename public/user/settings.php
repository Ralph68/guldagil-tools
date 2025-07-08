<?php
/**
 * Titre: Page de paramètres utilisateur
 * Chemin: /public/user/settings.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Configuration - ROOT_PATH pour /public/user/
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Chargement configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Vérification authentification
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];

// Variables pour template
$page_title = 'Paramètres';
$page_subtitle = 'Configuration personnelle';
$current_module = 'settings';
$user_authenticated = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚙️', 'text' => 'Paramètres', 'url' => '/user/settings.php', 'active' => true]
];

// Paramètres par défaut
$default_settings = [
    'theme' => 'light',
    'language' => 'fr',
    'notifications_email' => true,
    'items_per_page' => 25,
    'default_module' => 'calculateur'
];

// Charger paramètres utilisateur
$user_settings = array_merge($default_settings, $_SESSION['user_settings'] ?? []);

// Messages
$message = '';
$error = '';

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_general':
            $user_settings['language'] = $_POST['language'] ?? 'fr';
            $user_settings['items_per_page'] = (int)($_POST['items_per_page'] ?? 25);
            $user_settings['default_module'] = $_POST['default_module'] ?? 'calculateur';
            $message = 'Paramètres généraux sauvegardés';
            break;
            
        case 'save_interface':
            $user_settings['theme'] = $_POST['theme'] ?? 'light';
            $message = 'Interface mise à jour';
            break;
            
        case 'save_notifications':
            $user_settings['notifications_email'] = isset($_POST['notifications_email']);
            $message = 'Notifications mises à jour';
            break;
            
        case 'reset':
            if ($_POST['confirm'] === 'RESET') {
                $user_settings = $default_settings;
                $message = 'Paramètres remis à zéro';
            } else {
                $error = 'Confirmation incorrecte';
            }
            break;
    }
    
    if (empty($error)) {
        $_SESSION['user_settings'] = $user_settings;
    }
}

// Inclure header
include ROOT_PATH . '/templates/header.php';
?>

<div class="settings-page">
    <!-- Messages -->
    <?php if ($message): ?>
    <div class="alert success">
        <span class="icon">✅</span>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert error">
        <span class="icon">❌</span>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="page-header">
        <h1>⚙️ Paramètres</h1>
        <p>Personnalisez votre expérience du portail</p>
    </div>

    <div class="settings-layout">
        <!-- Navigation -->
        <nav class="settings-nav">
            <a href="#general" class="nav-item active" data-tab="general">
                <span>🔧</span> Général
            </a>
            <a href="#interface" class="nav-item" data-tab="interface">
                <span>🎨</span> Interface
            </a>
            <a href="#notifications" class="nav-item" data-tab="notifications">
                <span>🔔</span> Notifications
            </a>
            <a href="#reset" class="nav-item" data-tab="reset">
                <span>🔄</span> Réinitialiser
            </a>
        </nav>

        <!-- Contenu -->
        <div class="settings-content">
            
            <!-- Général -->
            <section id="general" class="tab-section active">
                <h2>Paramètres généraux</h2>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_general">
                    
                    <div class="form-group">
                        <label>🌍 Langue</label>
                        <select name="language">
                            <option value="fr" <?= $user_settings['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                            <option value="en" <?= $user_settings['language'] === 'en' ? 'selected' : '' ?>>English</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📄 Éléments par page</label>
                        <select name="items_per_page">
                            <option value="10" <?= $user_settings['items_per_page'] === 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $user_settings['items_per_page'] === 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $user_settings['items_per_page'] === 50 ? 'selected' : '' ?>>50</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>🏠 Module par défaut</label>
                        <select name="default_module">
                            <option value="home" <?= $user_settings['default_module'] === 'home' ? 'selected' : '' ?>>Accueil</option>
                            <option value="calculateur" <?= $user_settings['default_module'] === 'calculateur' ? 'selected' : '' ?>>Calculateur</option>
                            <option value="adr" <?= $user_settings['default_module'] === 'adr' ? 'selected' : '' ?>>Module ADR</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn primary">💾 Sauvegarder</button>
                </form>
            </section>

            <!-- Interface -->
            <section id="interface" class="tab-section">
                <h2>Interface</h2>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_interface">
                    
                    <div class="form-group">
                        <label>🎨 Thème</label>
                        <div class="theme-selector">
                            <label class="theme-option">
                                <input type="radio" name="theme" value="light" <?= $user_settings['theme'] === 'light' ? 'checked' : '' ?>>
                                <div class="theme-preview light">
                                    <div class="preview-header"></div>
                                    <div class="preview-content"></div>
                                </div>
                                <span>Clair</span>
                            </label>
                            
                            <label class="theme-option">
                                <input type="radio" name="theme" value="dark" <?= $user_settings['theme'] === 'dark' ? 'checked' : '' ?>>
                                <div class="theme-preview dark">
                                    <div class="preview-header"></div>
                                    <div class="preview-content"></div>
                                </div>
                                <span>Sombre</span>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn primary">🎨 Appliquer</button>
                </form>
            </section>

            <!-- Notifications -->
            <section id="notifications" class="tab-section">
                <h2>Notifications</h2>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_notifications">
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notifications_email" <?= $user_settings['notifications_email'] ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            📧 Notifications par email
                        </label>
                        <div class="help-text">Recevoir des emails pour les événements importants</div>
                    </div>
                    
                    <button type="submit" class="btn primary">🔔 Sauvegarder</button>
                </form>
            </section>

            <!-- Réinitialiser -->
            <section id="reset" class="tab-section">
                <h2>Réinitialisation</h2>
                
                <div class="warning-box">
                    <h3>⚠️ Attention</h3>
                    <p>Cette action remettra tous vos paramètres aux valeurs par défaut.</p>
                    
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="reset">
                        
                        <div class="form-group">
                            <label>Tapez "RESET" pour confirmer :</label>
                            <input type="text" name="confirm" placeholder="RESET" required>
                        </div>
                        
                        <button type="submit" class="btn warning" onclick="return confirm('Êtes-vous sûr ?')">
                            🔄 Réinitialiser tout
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
/* CSS simplifié et efficace */
.settings-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert.error {
    background: #fef2f2;
    color: #7f1d1d;
    border: 1px solid #ef4444;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.page-header h1 {
    font-size: 2rem;
    margin: 0 0 0.5rem;
    color: #111827;
}

.page-header p {
    color: #6b7280;
    margin: 0;
}

.settings-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
    align-items: start;
}

.settings-nav {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1rem;
    position: sticky;
    top: 1rem;
}

.nav-item {
    display: block;
    padding: 0.75rem 1rem;
    color: #6b7280;
    text-decoration: none;
    border-radius: 0.5rem;
    margin-bottom: 0.25rem;
    transition: all 0.2s;
}

.nav-item:hover,
.nav-item.active {
    background: #3182ce;
    color: white;
}

.settings-content {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 2rem;
}

.tab-section {
    display: none;
}

.tab-section.active {
    display: block;
}

.tab-section h2 {
    margin: 0 0 1.5rem;
    color: #111827;
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 500;
    color: #374151;
}

.form-group select,
.form-group input[type="text"] {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 1rem;
}

.form-group select:focus,
.form-group input:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    align-self: flex-start;
}

.btn.primary {
    background: #3182ce;
    color: white;
}

.btn.primary:hover {
    background: #2563eb;
}

.btn.warning {
    background: #f59e0b;
    color: white;
}

.btn.warning:hover {
    background: #d97706;
}

/* Thèmes */
.theme-selector {
    display: flex;
    gap: 1rem;
}

.theme-option {
    cursor: pointer;
    text-align: center;
}

.theme-option input {
    display: none;
}

.theme-preview {
    width: 80px;
    height: 60px;
    border: 2px solid #d1d5db;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    overflow: hidden;
    transition: border-color 0.2s;
}

.theme-option input:checked + .theme-preview {
    border-color: #3182ce;
}

.theme-preview.light {
    background: #f9fafb;
}

.theme-preview.dark {
    background: #1f2937;
}

.preview-header {
    height: 15px;
    background: #3182ce;
}

.preview-content {
    height: 45px;
    background: rgba(0,0,0,0.05);
}

.theme-preview.dark .preview-content {
    background: rgba(255,255,255,0.1);
}

/* Checkbox */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.checkbox-label input {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 0.25rem;
    position: relative;
}

.checkbox-label input:checked + .checkmark {
    background: #3182ce;
    border-color: #3182ce;
}

.checkbox-label input:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 0.75rem;
}

.help-text {
    font-size: 0.875rem;
    color: #6b7280;
    margin-left: 2.75rem;
}

.warning-box {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.warning-box h3 {
    margin: 0 0 0.75rem;
    color: #92400e;
}

.warning-box p {
    margin: 0 0 1.5rem;
    color: #92400e;
}

/* Responsive */
@media (max-width: 768px) {
    .settings-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .settings-nav {
        display: flex;
        overflow-x: auto;
        position: static;
    }
    
    .nav-item {
        white-space: nowrap;
        margin-right: 0.5rem;
        margin-bottom: 0;
    }
    
    .theme-selector {
        justify-content: center;
    }
}
</style>

<script>
// JavaScript simple pour les onglets
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.tab-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTab = this.getAttribute('data-tab');
            
            // Retirer active
            navItems.forEach(nav => nav.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            
            // Ajouter active
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
});
</script>

<?php include ROOT_PATH . '/templates/footer.php'; ?>
